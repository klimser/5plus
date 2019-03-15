<?php

namespace backend\controllers;

use common\components\ComponentContainer;
use common\components\GroupComponent;
use common\models\GroupPupil;
use common\models\Group;
use common\models\Payment;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use yii;
use yii\web\ForbiddenHttpException;

/**
 * ReportController implements vary reports.
 */
class ReportController extends AdminController
{
    public function actionGroupMovement()
    {
        if (!Yii::$app->user->can('reportGroupMovement')) throw new ForbiddenHttpException('Access denied!');

        if (\Yii::$app->request->isPost) {
            [$month, $year] = explode('.', \Yii::$app->request->post('date', ''));
            if ($month && $year) {
                $startDate = new \DateTime("$year-$month-01");
                $endDate = clone $startDate;
                $endDate->modify('last day of this month');
                $startDateString = $startDate->format('Y-m-d');
                $endDateString = $endDate->format('Y-m-d');

                /** @var GroupPupil[] $groupPupils */
                $groupPupils = GroupPupil::find()
                    ->andWhere(['BETWEEN', 'date_start', $startDateString, $endDateString])
                    ->orWhere(['BETWEEN', 'date_end', $startDateString, $endDateString])
                    ->all();
                $timeLine = [];
                foreach ($groupPupils as $groupPupil) {
                    if ($groupPupil->date_start >= $startDateString && $groupPupil->date_start <= $endDateString) {
                        $timeLine[] = ['type' => 'in', 'date' => $groupPupil->date_start, 'user' => $groupPupil->user_id, 'group' => $groupPupil->group_id];
                    }
                    if ($groupPupil->date_end >= $startDateString && $groupPupil->date_end <= $endDateString) {
                        $timeLine[] = ['type' => 'out', 'date' => $groupPupil->date_end, 'user' => $groupPupil->user_id, 'group' => $groupPupil->group_id];
                    }
                }

                usort($timeLine, function($a, $b) {
                    if ($a['date'] < $b['date']) return -1;
                    elseif ($a['date'] > $b['date']) return 1;
                    else return $a['type'] < $b['type'] ? -1 : 1;
                });

                $dataMap = [];
                $pupilMap = [];
                foreach ($timeLine as $value) {
                    if (!array_key_exists($value['group'], $dataMap)) $dataMap[$value['group']] = ['in' => 0, 'out' => 0];
                    $dataMap[$value['group']][$value['type']]++;

                    $key = $value['user'] . '|' . $value['group'];
                    if (!array_key_exists($key, $pupilMap)) {
                        $pupilMap[$key] = ['in' => 0, 'out' => 0];
                    }
                    if ($value['type'] == 'in') {
                        if ($pupilMap[$key]['out'] > $pupilMap[$key]['in']
                            || ($pupilMap[$key]['out'] > 0 && $pupilMap[$key]['out'] == $pupilMap[$key]['in'])) {
                            $dataMap[$value['group']]['in']--;
                            $dataMap[$value['group']]['out']--;
                        } elseif ($pupilMap[$key]['in'] > $pupilMap[$key]['out']) {
                            ComponentContainer::getErrorLogger()->logError(
                                'report/group-movement',
                                "Strange numbers, check: user $value[user] group $value[group]",
                                true
                            );
                        }
                    } else {
                        if ($pupilMap[$key]['out'] > $pupilMap[$key]['in']) {
                            ComponentContainer::getErrorLogger()->logError(
                                'report/group-movement',
                                "Strange numbers, check: user $value[user] group $value[group]",
                                true
                            );
                        }
                    }
                    $pupilMap[$key][$value['type']]++;
                }

                /** @var Group[] $groups */
                $groups = Group::find()
                    ->andWhere(['id' => array_keys($dataMap)])
                    ->orderBy(['subject_id' => 'ASC', 'teacher_id' => 'ASC'])
                    ->all();

                $spreadsheet = new Spreadsheet();
                $spreadsheet->getActiveSheet()->getPageSetup()->setOrientation(PageSetup::ORIENTATION_PORTRAIT);
                $spreadsheet->getActiveSheet()->getPageSetup()->setPaperSize(PageSetup::PAPERSIZE_A4);
                $spreadsheet->getActiveSheet()->getPageSetup()->setFitToWidth(1);
                $spreadsheet->getActiveSheet()->getPageSetup()->setFitToHeight(0);

                $spreadsheet->getActiveSheet()->mergeCells('A1:G1');
                $spreadsheet->getActiveSheet()->setCellValue('A1', "Отчёт по студентам $month $year");
                $spreadsheet->getActiveSheet()->mergeCells('I1:O1');
                $spreadsheet->getActiveSheet()->setCellValue('I1', "KIDS");
                $spreadsheet->getActiveSheet()->getStyle('A1:I1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $spreadsheet->getActiveSheet()->getStyle('A1:I1')->getFont()->setBold(true)->setSize(16);

                for ($i = 0; $i < 2; $i++) {
                    $offset = $i * 8;
                    $spreadsheet->getActiveSheet()
                        ->setCellValueByColumnAndRow($offset + 1, 2, "№")
                        ->setCellValueByColumnAndRow($offset + 2, 2, "группа")
                        ->setCellValueByColumnAndRow($offset + 3, 2, "учитель")
                        ->setCellValueByColumnAndRow($offset + 4, 2, "прибыло")
                        ->setCellValueByColumnAndRow($offset + 5, 2, "убыло")
                        ->setCellValueByColumnAndRow($offset + 6, 2, "всего занималось")
                        ->setCellValueByColumnAndRow($offset + 7, 2, "сальдо");
                }

                $nums = [0 => 1, 1 => 1];
                $rows = [0 => 3, 1 => 3];
                $groupCollections = [];
                foreach ($groups as $group) {
                    $groupParam = GroupComponent::getGroupParam($group, $startDate);
                    $totalPupils = GroupPupil::find()
                        ->andWhere(['group_id' => $group->id])
                        ->andWhere(['<=', 'date_start', $endDateString])
                        ->andWhere(['or', ['date_end' => null], ['>=', 'date_end', $startDateString]])
                        ->select('COUNT(DISTINCT user_id)')
                        ->scalar();
                    $finalPupils = GroupPupil::find()
                        ->andWhere(['group_id' => $group->id])
                        ->andWhere(['<=', 'date_start', $endDateString])
                        ->andWhere(['or', ['date_end' => null], ['>=', 'date_end', $endDateString]])
                        ->select('COUNT(DISTINCT user_id)')
                        ->scalar();

                    $index = $group->isKids() ? 1 : 0;
                    if (!array_key_exists($index, $groupCollections)) $groupCollections[$index] = [];
                    $groupCollections[$index][] = $group->id;
                    $offset = $index * 8;
                    $spreadsheet->getActiveSheet()
                        ->setCellValueByColumnAndRow($offset + 1, $rows[$index], $nums[$index])
                        ->setCellValueByColumnAndRow($offset + 2, $rows[$index], $group->name)
                        ->setCellValueByColumnAndRow($offset + 3, $rows[$index], $groupParam->teacher->name)
                        ->setCellValueByColumnAndRow($offset + 4, $rows[$index], $dataMap[$group->id]['in'])
                        ->setCellValueByColumnAndRow($offset + 5, $rows[$index], $dataMap[$group->id]['out'])
                        ->setCellValueByColumnAndRow($offset + 6, $rows[$index], $totalPupils)
                        ->setCellValueByColumnAndRow($offset + 7, $rows[$index], $finalPupils);
                    $nums[$index]++;
                    $rows[$index]++;
                }

                foreach ($groupCollections as $index => $groupIds) {
                    $offset = $index * 8;
                    $row = $rows[$index] - 1;

                    $spreadsheet->getActiveSheet()->getStyleByColumnAndRow($offset + 1, 2, $offset + 7, $row)->applyFromArray([
                        'borders' => [
                            'allBorders' => [
                                'borderStyle' => Border::BORDER_THIN,
                                'color' => ['argb' => 'FF000000'],
                            ],
                        ],
                    ]);
                    $row += 2;

                    $inUsers = GroupPupil::find()
                        ->andWhere(['BETWEEN', 'date_start', $startDateString, $endDateString])
                        ->andWhere(['group_id' => $groupIds])
                        ->select('user_id')
                        ->distinct(true)
                        ->column();
                    $excludeUsersCount = GroupPupil::find()
                        ->andWhere(['<', 'date_start', $startDateString])
                        ->andWhere(['group_id' => $groupIds])
                        ->andWhere(['user_id' => $inUsers])
                        ->count('DISTINCT user_id');
                    $totalIn = count($inUsers) - $excludeUsersCount;

                    $outUsers = GroupPupil::find()
                        ->andWhere(['BETWEEN', 'date_end', $startDateString, $endDateString])
                        ->andWhere(['group_id' => $groupIds])
                        ->select('user_id')
                        ->distinct(true)
                        ->column();
                    $excludeUsersCount = GroupPupil::find()
                        ->andWhere(['or', ['date_end' => null], ['>', 'date_end', $endDateString]])
                        ->andWhere(['group_id' => $groupIds])
                        ->andWhere(['user_id' => $outUsers])
                        ->count('DISTINCT user_id');
                    $totalOut = count($outUsers) - $excludeUsersCount;

                    $startPupils = GroupPupil::find()
                        ->andWhere(['<', 'date_start', $startDateString])
                        ->andWhere(['or', ['date_end' => null], ['>=', 'date_end', $startDateString]])
                        ->andWhere(['group_id' => $groupIds])
                        ->select(new  \yii\db\Expression('COUNT(DISTINCT CONCAT(user_id, "|", group_id))'))
                        ->scalar();
                    $startUsers = GroupPupil::find()
                        ->andWhere(['<', 'date_start', $startDateString])
                        ->andWhere(['group_id' => $groupIds])
                        ->andWhere(['or', ['date_end' => null], ['>=', 'date_end', $startDateString]])
                        ->select('COUNT(DISTINCT user_id)')
                        ->scalar();
                    $totalPupils = GroupPupil::find()
                        ->andWhere(['<=', 'date_start', $endDateString])
                        ->andWhere(['group_id' => $groupIds])
                        ->andWhere(['or', ['date_end' => null], ['>=', 'date_end', $startDateString]])
                        ->select(new  \yii\db\Expression('COUNT(DISTINCT CONCAT(user_id, "|", group_id))'))
                        ->scalar();
                    $totalUsers = GroupPupil::find()
                        ->andWhere(['<=', 'date_start', $endDateString])
                        ->andWhere(['group_id' => $groupIds])
                        ->andWhere(['or', ['date_end' => null], ['>=', 'date_end', $startDateString]])
                        ->select('COUNT(DISTINCT user_id)')
                        ->scalar();
                    $finalPupils = GroupPupil::find()
                        ->andWhere(['<=', 'date_start', $endDateString])
                        ->andWhere(['group_id' => $groupIds])
                        ->andWhere(['or', ['date_end' => null], ['>', 'date_end', $endDateString]])
                        ->select(new  \yii\db\Expression('COUNT(DISTINCT CONCAT(user_id, "|", group_id))'))
                        ->scalar();
                    $finalUsers = GroupPupil::find()
                        ->andWhere(['<=', 'date_start', $endDateString])
                        ->andWhere(['group_id' => $groupIds])
                        ->andWhere(['or', ['date_end' => null], ['>', 'date_end', $endDateString]])
                        ->select('COUNT(DISTINCT user_id)')
                        ->scalar();

                    $spreadsheet->getActiveSheet()->getStyleByColumnAndRow($offset + 1, $row, $offset + 7, $row + 4)
                        ->getFont()->setBold(true);

                    $spreadsheet->getActiveSheet()
                        ->mergeCellsByColumnAndRow($offset + 1, $row, $offset + 7, $row)
                        ->setCellValueByColumnAndRow($offset + 1, $row, "Итого новых студентов: $totalIn");
                    $row++;
                    $spreadsheet->getActiveSheet()
                        ->mergeCellsByColumnAndRow($offset + 1, $row, $offset + 7, $row)
                        ->setCellValueByColumnAndRow($offset + 1, $row, "Итого ушли из учебного центра: $totalOut");
                    $row++;

                    $spreadsheet->getActiveSheet()
                        ->mergeCellsByColumnAndRow($offset + 1, $row, $offset + 7, $row)
                        ->setCellValueByColumnAndRow($offset + 1, $row, "В начале месяца было $startUsers человек - $startPupils студентов в гуппах");
                    $row++;
                    $spreadsheet->getActiveSheet()
                        ->mergeCellsByColumnAndRow($offset + 1, $row, $offset + 7, $row)
                        ->setCellValueByColumnAndRow($offset + 1, $row, "В этом месяце занималось $totalUsers человек - $totalPupils студентов в гуппах");
                    $row++;
                    $spreadsheet->getActiveSheet()
                        ->mergeCellsByColumnAndRow($offset + 1, $row, $offset + 7, $row)
                        ->setCellValueByColumnAndRow($offset + 1, $row, "В конце месяца было $finalUsers человек - $finalPupils студентов в гуппах");
                }

                ob_start();
                $objWriter = IOFactory::createWriter($spreadsheet, 'Xlsx');
                $objWriter->save('php://output');
                return \Yii::$app->response->sendContentAsFile(
                    ob_get_clean(),
                    "report-$year-$month.xlsx",
                    ['mimeType' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
                );
            }
        }

        return $this->render('group-movement');
    }

    /**
     * Get all money debts.
     * @return mixed
     * @throws \Exception
     * @throws \yii\db\Exception
     */
    public function actionDebt()
    {
        if (!Yii::$app->user->can('reportDebt')) throw new ForbiddenHttpException('Access denied!');

        $spreadsheet = new Spreadsheet();
        $spreadsheet->getActiveSheet()->getPageSetup()->setOrientation(PageSetup::ORIENTATION_PORTRAIT);
        $spreadsheet->getActiveSheet()->getPageSetup()->setPaperSize(PageSetup::PAPERSIZE_A4);
        $spreadsheet->getActiveSheet()->getPageSetup()->setFitToWidth(1);
        $spreadsheet->getActiveSheet()->getPageSetup()->setFitToHeight(0);

        $spreadsheet->getActiveSheet()->mergeCells('A1:D1');
        $spreadsheet->getActiveSheet()->setCellValue('A1', "Задолженности");
        $spreadsheet->getActiveSheet()->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $spreadsheet->getActiveSheet()->getStyle('A1')->getFont()->setBold(true)->setSize(16);

        $spreadsheet->getActiveSheet()->getColumnDimension('A')->setWidth(32);
        $spreadsheet->getActiveSheet()->getColumnDimension('B')->setWidth(40);
        $spreadsheet->getActiveSheet()->getColumnDimension('C')->setWidth(5);
        $spreadsheet->getActiveSheet()->getColumnDimension('D')->setWidth(15);

        $row = 3;
        /** @var Group[] $groups */
        $groups = Group::find()
            ->andWhere([Group::tableName() . '.active' => Group::STATUS_ACTIVE])
            ->joinWith('groupPupils')
            ->andWhere([GroupPupil::tableName() . '.active' => GroupPupil::STATUS_ACTIVE])
            ->andWhere(['<', GroupPupil::tableName() . '.paid_lessons', 0])
            ->all();
        foreach ($groups as $group) {
            $spreadsheet->getActiveSheet()->mergeCells("A$row:D$row");
            $spreadsheet->getActiveSheet()->setCellValue("A$row", $group->name);
            $spreadsheet->getActiveSheet()->getStyle("A$row")->getFont()->setItalic(true)->setSize(14);
            $row++;

            foreach ($group->activeGroupPupils as $groupPupil) {
                if ($groupPupil->paid_lessons < 0) {
                    $spreadsheet->getActiveSheet()->setCellValue("A$row", $groupPupil->user->name);
                    $spreadsheet->getActiveSheet()->setCellValue(
                        "B$row",
                        $groupPupil->user->phoneFull . ($groupPupil->user->phone2 ? ', ' . $groupPupil->user->phone2Full : '')
                    );
                    $spreadsheet->getActiveSheet()->setCellValue("C$row", $groupPupil->paid_lessons * (-1));
                    $spreadsheet->getActiveSheet()->setCellValue("D$row", $groupPupil->chargeDateObject->format('d.m.Y'));

                    $row++;
                }
            }
            $row++;
        }

        $spreadsheet->getActiveSheet()->getStyle("C3:C$row")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        ob_start();
        $objWriter = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $objWriter->save('php://output');
        return \Yii::$app->response->sendContentAsFile(
            ob_get_clean(),
            'report-debt-' . date('Y-m-d') . '.xlsx',
            ['mimeType' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
        );
    }

    public function actionMoney()
    {
        if (!Yii::$app->user->can('reportMoney')) throw new ForbiddenHttpException('Access denied!');

        if (\Yii::$app->request->isPost) {
            [$month, $year] = explode('.', \Yii::$app->request->post('date', ''));
            if ($month && $year) {
                $startDate = new \DateTime("$year-$month-01");
                $endDate = clone $startDate;
                $endDate->modify('last day of this month');
                $startDateString = $startDate->format('Y-m-d');
                $endDateString = $endDate->format('Y-m-d');

                $spreadsheet = new Spreadsheet();
                $spreadsheet->getActiveSheet()->getPageSetup()->setOrientation(PageSetup::ORIENTATION_PORTRAIT);
                $spreadsheet->getActiveSheet()->getPageSetup()->setPaperSize(PageSetup::PAPERSIZE_A4);
                $spreadsheet->getActiveSheet()->getPageSetup()->setFitToWidth(1);
                $spreadsheet->getActiveSheet()->getPageSetup()->setFitToHeight(0);



                $groupId = \Yii::$app->request->post('group');
                if ($groupId == 'all') {
                    if (!Yii::$app->user->can('reportMoneyTotal')) throw new ForbiddenHttpException('Access denied!');

                    $spreadsheet->getActiveSheet()
                        ->mergeCells('A1:A2')
                        ->setCellValue('A1', 'Группа')
                        ->mergeCells('B1:D1')
                        ->setCellValue('B1', 'Принесли в кассу')
                        ->setCellValue('B2', 'Со скидкой')
                        ->setCellValue('C2', 'Без скидки')
                        ->setCellValue('D2', 'Всего');

                    $spreadsheet->getActiveSheet()
                        ->mergeCells('E1:G1')
                        ->setCellValue('E1', 'Списано за занятия')
                        ->setCellValue('E2', 'Со скидкой')
                        ->setCellValue('F2', 'Без скидки')
                        ->setCellValue('G2', 'Всего');

                    $spreadsheet->getActiveSheet()->getStyle('A1:E1')->getFont()->setBold(true);
                    $spreadsheet->getActiveSheet()->getStyle('B1:E1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $spreadsheet->getActiveSheet()->getStyle('A1')->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);

                    $spreadsheet->getActiveSheet()->getColumnDimension('A')->setWidth(30);
                    $spreadsheet->getActiveSheet()->getColumnDimension('B')->setWidth(15);
                    $spreadsheet->getActiveSheet()->getColumnDimension('C')->setWidth(15);
                    $spreadsheet->getActiveSheet()->getColumnDimension('D')->setWidth(15);
                    $spreadsheet->getActiveSheet()->getColumnDimension('E')->setWidth(15);
                    $spreadsheet->getActiveSheet()->getColumnDimension('F')->setWidth(15);
                    $spreadsheet->getActiveSheet()->getColumnDimension('G')->setWidth(15);

                    $groupIds = Payment::find()
                        ->andWhere(['>=', 'created_at', $startDateString])
                        ->andWhere(['<', 'created_at', $endDateString])
                        ->andWhere(['>', 'amount', 0])
                        ->select('group_id')
                        ->distinct(true)
                        ->column();
                    /** @var Group[] $groups */
                    $groups = Group::find()
                        ->andWhere(['id' => $groupIds])
                        ->orderBy('name')
                        ->all();
                    $groupMap = [];
                    foreach ($groups as $group) {
                        $groupMap[$group['id']] = $group->toArray();
                        $groupMap[$group['id']]['kids'] = $group->isKids();
                        $groupMap[$group['id']]['in_normal'] = $groupMap[$group['id']]['in_discount']
                            = $groupMap[$group['id']]['out_normal'] = $groupMap[$group['id']]['out_discount'] = 0;
                    }

                    $amounts = Payment::find()
                        ->andWhere(['group_id' => $groupIds])
                        ->andWhere(['>', 'amount', 0])
                        ->andWhere(['>=', 'created_at', $startDateString])
                        ->andWhere(['<', 'created_at', $endDateString])
                        ->select(['group_id', 'discount', 'SUM(amount) as amount'])
                        ->groupBy(['group_id', 'discount'])
                        ->asArray()
                        ->all();
                    foreach ($amounts as $record) {
                        $groupMap[$record['group_id']][$record['discount'] == Payment::STATUS_ACTIVE ? 'in_discount' : 'in_normal'] = $record['amount'];
                    }
                    $amounts = Payment::find()
                        ->andWhere(['group_id' => $groupIds])
                        ->andWhere(['<', 'amount', 0])
                        ->andWhere(['>=', 'created_at', $startDateString])
                        ->andWhere(['<', 'created_at', $endDateString])
                        ->select(['group_id', 'discount', 'SUM(amount) as amount'])
                        ->groupBy(['group_id', 'discount'])
                        ->asArray()
                        ->all();
                    foreach ($amounts as $record) {
                        $groupMap[$record['group_id']][$record['discount'] == Payment::STATUS_ACTIVE ? 'out_discount' : 'out_normal'] = abs($record['amount']);
                    }

                    $renderTable = function(bool $kids, int $row) use ($spreadsheet, $groupMap) {
                        $total = ['in_normal' => 0, 'in_discount' => 0, 'out_normal' => 0, 'out_discount' => 0];
                        foreach ($groupMap as $groupData) {
                            if ($groupData['kids'] != $kids) continue;
                            $spreadsheet->getActiveSheet()->setCellValue("A$row", $groupData['name']);
                            $spreadsheet->getActiveSheet()->setCellValueExplicit("B$row", $groupData['in_discount'], DataType::TYPE_NUMERIC);
                            $spreadsheet->getActiveSheet()->setCellValueExplicit("C$row", $groupData['in_normal'], DataType::TYPE_NUMERIC);
                            $spreadsheet->getActiveSheet()->setCellValueExplicit("D$row", $groupData['in_discount'] + $groupData['in_normal'], DataType::TYPE_NUMERIC);
                            $spreadsheet->getActiveSheet()->setCellValueExplicit("E$row", $groupData['out_discount'], DataType::TYPE_NUMERIC);
                            $spreadsheet->getActiveSheet()->setCellValueExplicit("F$row", $groupData['out_normal'], DataType::TYPE_NUMERIC);
                            $spreadsheet->getActiveSheet()->setCellValueExplicit("G$row", $groupData['out_discount'] + $groupData['out_normal'], DataType::TYPE_NUMERIC);
                            foreach ($total as $key => $value) {
                                $total[$key] += $groupData[$key];
                            }
                            $row++;
                        }

                        $spreadsheet->getActiveSheet()->setCellValue("A$row", 'Итого');
                        $spreadsheet->getActiveSheet()->setCellValueExplicit("B$row", $total['in_discount'], DataType::TYPE_NUMERIC);
                        $spreadsheet->getActiveSheet()->setCellValueExplicit("C$row", $total['in_normal'], DataType::TYPE_NUMERIC);
                        $spreadsheet->getActiveSheet()->setCellValueExplicit("D$row", $total['in_discount'] + $total['in_normal'], DataType::TYPE_NUMERIC);
                        $spreadsheet->getActiveSheet()->setCellValueExplicit("E$row", $total['out_discount'], DataType::TYPE_NUMERIC);
                        $spreadsheet->getActiveSheet()->setCellValueExplicit("F$row", $total['out_normal'], DataType::TYPE_NUMERIC);
                        $spreadsheet->getActiveSheet()->setCellValueExplicit("G$row", $total['out_discount'] + $total['out_normal'], DataType::TYPE_NUMERIC);
                        $spreadsheet->getActiveSheet()->getStyle("A$row:G$row")->getFont()->setBold(true);
                        $spreadsheet->getActiveSheet()->getStyle("B3:G$row")->getNumberFormat()->setFormatCode('#,##0');

                        return $row;
                    };
                    $nextRow = $renderTable(false, 3);
                    $nextRow += 3;
                    $renderTable(true, $nextRow);
                } else {
                    [$devNull, $groupId] = explode('_', $groupId);
                    $group = Group::findOne($groupId);
                    if (!$group) throw new yii\web\NotFoundHttpException('Invalid group!');

                    $total = ['in_normal' => 0, 'in_discount' => 0, 'out_normal' => 0, 'out_discount' => 0];
                    $amounts = Payment::find()
                        ->andWhere(['group_id' => $group->id])
                        ->andWhere(['>', 'amount', 0])
                        ->andWhere(['>=', 'created_at', $startDateString])
                        ->andWhere(['<', 'created_at', $endDateString])
                        ->select(['discount', 'SUM(amount) as amount'])
                        ->groupBy('discount')
                        ->asArray()
                        ->all();
                    foreach ($amounts as $record) {
                        $total[$record['discount'] == Payment::STATUS_ACTIVE ? 'in_discount' : 'in_normal'] = $record['amount'];
                    }
                    $amounts = Payment::find()
                        ->andWhere(['group_id' => $group->id])
                        ->andWhere(['<', 'amount', 0])
                        ->andWhere(['>=', 'created_at', $startDateString])
                        ->andWhere(['<', 'created_at', $endDateString])
                        ->select(['discount', 'SUM(amount) as amount'])
                        ->groupBy('discount')
                        ->asArray()
                        ->all();
                    foreach ($amounts as $record) {
                        $total[$record['discount'] == Payment::STATUS_ACTIVE ? 'out_discount' : 'out_normal'] = abs($record['amount']);
                    }

                    $spreadsheet->getActiveSheet()
                        ->setCellValue('A1', 'Группа')
                        ->setCellValue('A2', 'Собрано денег со скидкой')
                        ->setCellValue('A3', 'Собрано денег без скидки')
                        ->setCellValue('A4', 'Собрано всего')
                        ->setCellValue('A5', 'Списано за занятия со скидкой')
                        ->setCellValue('A6', 'Списано за занятия без скидки')
                        ->setCellValue('A7', 'Списано за занятия всего')
                        ->setCellValue('B1', $group->name)
                        ->setCellValueExplicit('B2', $total['in_discount'], DataType::TYPE_NUMERIC)
                        ->setCellValueExplicit('B3', $total['in_normal'], DataType::TYPE_NUMERIC)
                        ->setCellValueExplicit('B4', $total['in_discount'] + $total['in_normal'], DataType::TYPE_NUMERIC)
                        ->setCellValueExplicit('B5', $total['out_discount'], DataType::TYPE_NUMERIC)
                        ->setCellValueExplicit('B6', $total['out_normal'], DataType::TYPE_NUMERIC)
                        ->setCellValueExplicit('B7', $total['out_discount'] + $total['out_normal'], DataType::TYPE_NUMERIC);
                    $spreadsheet->getActiveSheet()->getColumnDimension('A')->setWidth(30);
                    $spreadsheet->getActiveSheet()->getColumnDimension('B')->setWidth(15);
                    $spreadsheet->getActiveSheet()->getStyle("B1:B7")->getFont()->setBold(true);
                    $spreadsheet->getActiveSheet()->getStyle("B2:B7")->getNumberFormat()->setFormatCode('#,##0');
                }

                ob_start();
                $objWriter = IOFactory::createWriter($spreadsheet, 'Xlsx');
                $objWriter->save('php://output');
                return \Yii::$app->response->sendContentAsFile(
                    ob_get_clean(),
                    "report-$year-$month.xlsx",
                    ['mimeType' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
                );
            }
        }

        return $this->render('money', [
            'groups' => Group::find()->orderBy('name')->all(),
            'allowedTotal' => Yii::$app->user->can('reportMoneyTotal')
        ]);
    }
}
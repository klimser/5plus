<?php

namespace backend\controllers;

use common\components\ComponentContainer;
use common\components\GroupComponent;
use common\models\GroupPupil;
use common\models\Group;
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
                $spreadsheet->getActiveSheet()->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $spreadsheet->getActiveSheet()->getStyle('A1')->getFont()->setBold(true)->setSize(16);

                $spreadsheet->getActiveSheet()->setCellValue('A2', "№");
                $spreadsheet->getActiveSheet()->setCellValue('B2', "группа");
                $spreadsheet->getActiveSheet()->setCellValue('C2', "учитель");
                $spreadsheet->getActiveSheet()->setCellValue('D2', "прибыло");
                $spreadsheet->getActiveSheet()->setCellValue('E2', "убыло");
                $spreadsheet->getActiveSheet()->setCellValue('F2', "всего занималось");
                $spreadsheet->getActiveSheet()->setCellValue('G2', "сальдо");

                $i = 1;
                $row = 3;
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

                    $spreadsheet->getActiveSheet()->setCellValue("A$row", $i);
                    $spreadsheet->getActiveSheet()->setCellValue("B$row", $group->name);
                    $spreadsheet->getActiveSheet()->setCellValue("C$row", $groupParam->teacher->name);
                    $spreadsheet->getActiveSheet()->setCellValue("D$row", $dataMap[$group->id]['in']);
                    $spreadsheet->getActiveSheet()->setCellValue("E$row", $dataMap[$group->id]['out']);
                    $spreadsheet->getActiveSheet()->setCellValue("F$row", $totalPupils);
                    $spreadsheet->getActiveSheet()->setCellValue("G$row", $finalPupils);
                    $i++;
                    $row++;
                }

                $row--;
                $spreadsheet->getActiveSheet()->getStyle("A2:G$row")->applyFromArray([
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
                    ->select('user_id')
                    ->distinct(true)
                    ->column();
                $excludeUsers = GroupPupil::find()
                    ->andWhere(['<', 'date_start', $startDateString])
                    ->andWhere(['user_id' => $inUsers])
                    ->select('user_id')
                    ->distinct(true)
                    ->column();
                $totalIn = count(array_diff($inUsers, $excludeUsers));

                $outUsers = GroupPupil::find()
                    ->andWhere(['BETWEEN', 'date_end', $startDateString, $endDateString])
                    ->select('user_id')
                    ->distinct(true)
                    ->column();
                $excludeUsers = GroupPupil::find()
                    ->andWhere(['or', ['date_end' => null], ['>', 'date_end', $endDateString]])
                    ->andWhere(['user_id' => $outUsers])
                    ->select('user_id')
                    ->distinct(true)
                    ->column();
                $totalOut = count(array_diff($outUsers, $excludeUsers));

                $totalPupils = GroupPupil::find()
                    ->andWhere(['<=', 'date_start', $endDateString])
                    ->andWhere(['or', ['date_end' => null], ['>=', 'date_end', $startDateString]])
                    ->select(new  \yii\db\Expression('COUNT(DISTINCT CONCAT(user_id, "|", group_id))'))
                    ->scalar();
                $totalUsers = GroupPupil::find()
                    ->andWhere(['<=', 'date_start', $endDateString])
                    ->andWhere(['or', ['date_end' => null], ['>=', 'date_end', $startDateString]])
                    ->select('COUNT(DISTINCT user_id)')
                    ->scalar();
                $finalPupils = GroupPupil::find()
                    ->andWhere(['<=', 'date_start', $endDateString])
                    ->andWhere(['or', ['date_end' => null], ['>=', 'date_end', $endDateString]])
                    ->select(new  \yii\db\Expression('COUNT(DISTINCT CONCAT(user_id, "|", group_id))'))
                    ->scalar();
                $finalUsers = GroupPupil::find()
                    ->andWhere(['<=', 'date_start', $endDateString])
                    ->andWhere(['or', ['date_end' => null], ['>=', 'date_end', $endDateString]])
                    ->select('COUNT(DISTINCT user_id)')
                    ->scalar();

                $spreadsheet->getActiveSheet()->getStyle("A$row:G" . ($row + 4))->getFont()->setBold(true);

                $spreadsheet->getActiveSheet()->mergeCells("A$row:G$row");
                $spreadsheet->getActiveSheet()->setCellValue("A$row", "Итого новых студентов: $totalIn");
                $row++;
                $spreadsheet->getActiveSheet()->mergeCells("A$row:G$row");
                $spreadsheet->getActiveSheet()->setCellValue("A$row", "Итого ушли из учебного центра: $totalOut");
                $row++;

                $spreadsheet->getActiveSheet()->mergeCells("A$row:G$row");
                $spreadsheet->getActiveSheet()->setCellValue("A$row", "В этом месяце занималось $totalUsers человек - $totalPupils студентов в гуппах");
                $row++;
                $spreadsheet->getActiveSheet()->mergeCells("A$row:G$row");
                $spreadsheet->getActiveSheet()->setCellValue("A$row", "В конце месяца было $finalUsers человек - $finalPupils студентов в гуппах");

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
}
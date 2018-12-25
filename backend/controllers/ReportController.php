<?php

namespace backend\controllers;

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
                            \Yii::$app->errorLogger->logError('report/group-movement', "Strange numbers, check: user $value[user] group $value[group]", true);
                        }
                    } else {
                        if ($pupilMap[$key]['out'] > $pupilMap[$key]['in']) {
                            \Yii::$app->errorLogger->logError('report/group-movement', "Strange numbers, check: user $value[user] group $value[group]", true);
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

                $spreadsheet->getActiveSheet()->mergeCells('A1:F1');
                $spreadsheet->getActiveSheet()->setCellValue('A1', "Отчёт по студентам $month $year");
                $spreadsheet->getActiveSheet()->getStyle("A1")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $spreadsheet->getActiveSheet()->getStyle("A1")->getFont()->setBold(true)->setSize(16);

                $spreadsheet->getActiveSheet()->setCellValue('A2', "№");
                $spreadsheet->getActiveSheet()->setCellValue('B2', "группа");
                $spreadsheet->getActiveSheet()->setCellValue('C2', "учитель");
                $spreadsheet->getActiveSheet()->setCellValue('D2', "прибыло");
                $spreadsheet->getActiveSheet()->setCellValue('E2', "убыло");
                $spreadsheet->getActiveSheet()->setCellValue('F2', "всего занималось");

                $i = 1;
                $row = 3;
                $total = ['in' => 0, 'out' => 0, 'pupils' => 0];
                foreach ($groups as $group) {
                    $groupParam = GroupComponent::getGroupParam($group, $startDate);
                    $totalPupils = GroupPupil::find()
                        ->andWhere(['group_id' => $group->id])
                        ->andWhere(['<=', 'date_start', $endDateString])
                        ->andWhere(['or', ['date_end' => null], ['>=', 'date_end', $startDateString]])
                        ->select('COUNT(DISTINCT user_id)')
                        ->scalar();

                    $spreadsheet->getActiveSheet()->setCellValue("A$row", $i);
                    $spreadsheet->getActiveSheet()->setCellValue("B$row", $group->name);
                    $spreadsheet->getActiveSheet()->setCellValue("C$row", $groupParam->teacher->name);
                    $spreadsheet->getActiveSheet()->setCellValue("D$row", $dataMap[$group->id]['in']);
                    $spreadsheet->getActiveSheet()->setCellValue("E$row", $dataMap[$group->id]['out']);
                    $spreadsheet->getActiveSheet()->setCellValue("F$row", $totalPupils);
                    $total['in'] += $dataMap[$group->id]['in'];
                    $total['out'] += $dataMap[$group->id]['out'];
                    $total['pupils'] += $totalPupils;
                    $i++;
                    $row++;
                }

                $spreadsheet->getActiveSheet()->setCellValue("C$row", 'Итого');
                $spreadsheet->getActiveSheet()->setCellValue("D$row", $total['in']);
                $spreadsheet->getActiveSheet()->setCellValue("E$row", $total['out']);
                $spreadsheet->getActiveSheet()->setCellValue("F$row", $total['pupils']);
                $spreadsheet->getActiveSheet()->getStyle("A$row:F$row")->getFont()->setBold(true);

                $spreadsheet->getActiveSheet()->getStyle("A2:F$row")->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['argb' => 'FF000000'],
                        ],
                    ],
                ]);

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
}
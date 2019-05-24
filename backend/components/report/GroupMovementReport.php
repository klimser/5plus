<?php

namespace backend\components\report;

use common\components\GroupComponent;
use common\models\Group;
use common\models\GroupPupil;
use DateTime;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use yii\db\Expression;
use yii\helpers\ArrayHelper;

class GroupMovementReport
{
    public static function create(DateTime $startDate, DateTime $endDate): Spreadsheet
    {
        $startDateString = $startDate->format('Y-m-d');
        $endDateString = $endDate->format('Y-m-d');

        /** @var Group[] $groups */
        $groups = Group::find()
            ->andWhere([
                'AND',
                ['<=', 'date_start', $endDateString],
                ['OR', ['>=', 'date_end', $startDateString], ['date_end' => null]]
            ])
            ->orderBy(['name' => SORT_ASC])
            ->all();

        $groupPupilCount = function($condition): array {
            return ArrayHelper::map(
                GroupPupil::find()
                    ->andWhere($condition)
                    ->select(['group_id', 'COUNT(DISTINCT user_id) as cnt'])
                    ->groupBy(['group_id'])
                    ->asArray(true)->all(),
                'group_id',
                'cnt'
            );
        };
        $inPupilsCount = $groupPupilCount(['BETWEEN', 'date_start', $startDateString, $endDateString]);
        $outPupilsCount = $groupPupilCount(['BETWEEN', 'date_end', $startDateString, $endDateString]);
        $totalPupilsCount = $groupPupilCount([
            'AND',
            ['<=', 'date_start', $endDateString],
            ['OR', ['>=', 'date_end', $startDateString], ['date_end' => null]]
        ]);
        $startPupilsCount = $groupPupilCount([
            'AND',
            ['<', 'date_start', $startDateString],
            ['OR', ['>=', 'date_end', $startDateString], ['date_end' => null]]
        ]);
        $endPupilsCount = $groupPupilCount([
            'AND',
            ['<=', 'date_start', $endDateString],
            ['OR', ['>', 'date_end', $endDateString], ['date_end' => null]]
        ]);

        $spreadsheet = new Spreadsheet();
        $spreadsheet->getActiveSheet()->getPageSetup()->setOrientation(PageSetup::ORIENTATION_PORTRAIT);
        $spreadsheet->getActiveSheet()->getPageSetup()->setPaperSize(PageSetup::PAPERSIZE_A4);
        $spreadsheet->getActiveSheet()->getPageSetup()->setFitToWidth(1);
        $spreadsheet->getActiveSheet()->getPageSetup()->setFitToHeight(0);

        $spreadsheet->getActiveSheet()->mergeCells('A1:G1');
        $spreadsheet->getActiveSheet()->setCellValue('A1', "Отчёт по студентам {$startDate->format('m Y')}");
        $spreadsheet->getActiveSheet()->mergeCells('I1:O1');
        $spreadsheet->getActiveSheet()->setCellValue('I1', "KIDS");
        $spreadsheet->getActiveSheet()->getStyle('A1:I1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $spreadsheet->getActiveSheet()->getStyle('A1:I1')->getFont()->setBold(true)->setSize(16);

        for ($i = 0; $i < 2; $i++) {
            $offset = $i * 9;
            $spreadsheet->getActiveSheet()
                ->setCellValueByColumnAndRow($offset + 1, 2, "№")
                ->setCellValueByColumnAndRow($offset + 2, 2, "группа")
                ->setCellValueByColumnAndRow($offset + 3, 2, "учитель")
                ->setCellValueByColumnAndRow($offset + 4, 2, "в начале месяца")
                ->setCellValueByColumnAndRow($offset + 5, 2, "прибыло")
                ->setCellValueByColumnAndRow($offset + 6, 2, "убыло")
                ->setCellValueByColumnAndRow($offset + 7, 2, "всего занималось")
                ->setCellValueByColumnAndRow($offset + 8, 2, "в конце месяца");
        }

        $nums = [0 => 1, 1 => 1];
        $rows = [0 => 3, 1 => 3];
        $groupCollections = [];
        foreach ($groups as $group) {
            if ($group->groupPupils) {
                $teacher = GroupComponent::getGroupParam($group, $startDate)->teacher;
            } else {
                $teacher = $group->teacher;
            }

            $index = $group->isKids() ? 1 : 0;
            if (!array_key_exists($index, $groupCollections)) $groupCollections[$index] = [];
            $groupCollections[$index][] = $group->id;
            $offset = $index * 9;
            $spreadsheet->getActiveSheet()
                ->setCellValueByColumnAndRow($offset + 1, $rows[$index], $nums[$index])
                ->setCellValueByColumnAndRow($offset + 2, $rows[$index], $group->name)
                ->setCellValueByColumnAndRow($offset + 3, $rows[$index], $teacher->name)
                ->setCellValueByColumnAndRow($offset + 4, $rows[$index], array_key_exists($group->id, $startPupilsCount) ? $startPupilsCount[$group->id] : 0)
                ->setCellValueByColumnAndRow($offset + 5, $rows[$index], array_key_exists($group->id, $inPupilsCount) ? $inPupilsCount[$group->id] : 0)
                ->setCellValueByColumnAndRow($offset + 6, $rows[$index], array_key_exists($group->id, $outPupilsCount) ? $outPupilsCount[$group->id] : 0)
                ->setCellValueByColumnAndRow($offset + 7, $rows[$index], array_key_exists($group->id, $totalPupilsCount) ? $totalPupilsCount[$group->id] : 0)
                ->setCellValueByColumnAndRow($offset + 8, $rows[$index], array_key_exists($group->id, $endPupilsCount) ? $endPupilsCount[$group->id] : 0);
            $nums[$index]++;
            $rows[$index]++;
        }

        foreach ($groupCollections as $index => $groupIds) {
            $offset = $index * 9;
            $row = $rows[$index] - 1;

            $spreadsheet->getActiveSheet()->getStyleByColumnAndRow($offset + 1, 2, $offset + 8, $row)->applyFromArray([
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
                ->select(new  Expression('COUNT(DISTINCT CONCAT(user_id, "|", group_id))'))
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
                ->select(new  Expression('COUNT(DISTINCT CONCAT(user_id, "|", group_id))'))
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
                ->select(new  Expression('COUNT(DISTINCT CONCAT(user_id, "|", group_id))'))
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

        return $spreadsheet;
    }
}

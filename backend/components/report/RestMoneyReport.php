<?php

namespace backend\components\report;

use common\models\Group;
use common\models\GroupPupil;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;

class RestMoneyReport
{
    public static function create(): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getActiveSheet()->getPageSetup()->setOrientation(PageSetup::ORIENTATION_PORTRAIT);
        $spreadsheet->getActiveSheet()->getPageSetup()->setPaperSize(PageSetup::PAPERSIZE_A4);
        $spreadsheet->getActiveSheet()->getPageSetup()->setFitToWidth(1);
        $spreadsheet->getActiveSheet()->getPageSetup()->setFitToHeight(0);

        $spreadsheet->getActiveSheet()->mergeCells('A1:C1');
        $spreadsheet->getActiveSheet()->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $spreadsheet->getActiveSheet()->getStyle('A1')->getFont()->setBold(true)->setSize(16);
        $spreadsheet->getActiveSheet()->getStyle('A3:C3')->getFont()->setBold(true);
        $spreadsheet->getActiveSheet()->getColumnDimension('A')->setWidth(35);
        $spreadsheet->getActiveSheet()->getColumnDimension('B')->setWidth(40);
        $spreadsheet->getActiveSheet()->getColumnDimension('C')->setWidth(35);
        $spreadsheet->getActiveSheet()
            ->setCellValue('A1', "Остатки")
            ->setCellValue('A3', 'Студент')
            ->setCellValue('B3', 'Выбыл')
            ->setCellValue('C3', 'Остаток');

        $row = 5;
        $data = Group::find()->alias('g')
            ->andWhere(['g.active' => Group::STATUS_ACTIVE])
            ->leftJoin(['gp1' => GroupPupil::tableName()], 'gp1.group_id = g.id')
            ->leftJoin(
                ['gp2' => GroupPupil::tableName()],
                'gp2.group_id = gp1.group_id AND gp2.user_id = gp1.user_id AND gp2.id != gp1.id '
                . 'AND gp2.active = ' . GroupPupil::STATUS_ACTIVE
            )
            ->andWhere([
                'gp1.active' => GroupPupil::STATUS_INACTIVE,
                'gp2.id' => null,
            ])
            ->orderBy(['gp1.date_end' => SORT_DESC])
            ->select(['group_id' => 'g.id', 'group_pupil_id' => 'gp1.id'])
            ->asArray()
            ->all();
        $groupPupilIds = [];
        $groupPupilMap = [];
        $groupMap = [];
        foreach ($data as $record) {
            $groupPupilIds[] = $record['group_pupil_id'];
            if (!array_key_exists($record['group_id'], $groupMap)) {
                $groupMap[$record['group_id']] = ['entity' => null, 'pupils' => []];
            }
            $groupMap[$record['group_id']]['pupils'][] = $record['group_pupil_id'];
        }
        $groups = Group::find()->andWhere(['id' => array_keys($groupMap)])->all();
        foreach ($groups as $group) $groupMap[$group->id]['entity'] = $group;
        $groupPupils = GroupPupil::find()->andWhere(['id' => $groupPupilIds])->all();
        foreach ($groupPupils as $groupPupil) $groupPupilMap[$groupPupil->id] = $groupPupil;
        $totalSum = 0;
        foreach ($groupMap as $groupData) {
            $titleRendered = false;
            foreach ($groupData['pupils'] as $groupPupilId) {
                $groupPupil = $groupPupilMap[$groupPupilId];
                if ($groupPupil->moneyLeft > 0) {
                    if (!$titleRendered) {
                        $spreadsheet->getActiveSheet()->mergeCells("A$row:C$row");
                        $spreadsheet->getActiveSheet()->setCellValue("A$row", $groupData['entity']->name);
                        $spreadsheet->getActiveSheet()->getStyle("A$row")->getFont()->setItalic(true)->setSize(14);
                        $row++;
                        $titleRendered = true;
                    }
                    $spreadsheet->getActiveSheet()->setCellValue("A$row", $groupPupil->user->name);
                    $spreadsheet->getActiveSheet()->setCellValue(
                        "B$row",
                        $groupPupil->endDateObject->format('d.m.Y')
                    );
                    $spreadsheet->getActiveSheet()->setCellValueExplicit(
                        "C$row",
                        $groupPupil->moneyLeft,
                        DataType::TYPE_NUMERIC
                    );
                    $totalSum += $groupPupil->moneyLeft;

                    $row++;
                }
            }
            $row++;
        }

        $row++;
        $spreadsheet->getActiveSheet()->setCellValue("A$row", 'Итого');
        $spreadsheet->getActiveSheet()->setCellValueExplicit("C$row", $totalSum, DataType::TYPE_NUMERIC);
        $spreadsheet->getActiveSheet()->getStyle("A$row:C$row")->getFont()->setBold(true);

        $spreadsheet->getActiveSheet()->getStyle("C5:C$row")->getNumberFormat()->setFormatCode('#,##0');

        return $spreadsheet;
    }
}
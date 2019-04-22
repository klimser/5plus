<?php

namespace backend\components\report;

use common\models\Group;
use common\models\GroupPupil;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;

class DebtReport
{
    public static function create(): Spreadsheet
    {
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

        return $spreadsheet;
    }
}
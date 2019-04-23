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
        /** @var Group[] $groups */
        $groups = Group::find()
            ->andWhere([Group::tableName() . '.active' => Group::STATUS_ACTIVE])
            ->joinWith('groupPupils')
            ->andWhere([GroupPupil::tableName() . '.active' => GroupPupil::STATUS_INACTIVE])
            ->orderBy([GroupPupil::tableName() . '.date_end' => SORT_DESC])
            ->all();
        $totalSum = 0;
        foreach ($groups as $group) {
            $titleRendered = false;
            foreach ($group->finishedGroupPupils as $groupPupil) {
                if ($groupPupil->moneyLeft > 0) {
                    if (!$titleRendered) {
                        $spreadsheet->getActiveSheet()->mergeCells("A$row:C$row");
                        $spreadsheet->getActiveSheet()->setCellValue("A$row", $group->name);
                        $spreadsheet->getActiveSheet()->getStyle("A$row")->getFont()->setItalic(true)->setSize(14);
                        $row++;
                        $titleRendered = true;
                    }
                    $spreadsheet->getActiveSheet()->setCellValue("A$row", $groupPupil->user->name);
                    $spreadsheet->getActiveSheet()->setCellValue("B$row", $groupPupil->chargeDateObject->format('d.m.Y'));
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
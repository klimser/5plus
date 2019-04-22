<?php

namespace backend\components\report;

use common\models\Group;
use common\models\Payment;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;

class MoneyReport
{
    public static function createGroup(Group $group, \DateTime $startDate, \DateTime $endDate): Spreadsheet
    {
        $startDateString = $startDate->format('Y-m-d');
        $endDateString = $endDate->format('Y-m-d');

        $spreadsheet = new Spreadsheet();
        $spreadsheet->getActiveSheet()->getPageSetup()->setOrientation(PageSetup::ORIENTATION_PORTRAIT);
        $spreadsheet->getActiveSheet()->getPageSetup()->setPaperSize(PageSetup::PAPERSIZE_A4);
        $spreadsheet->getActiveSheet()->getPageSetup()->setFitToWidth(1);
        $spreadsheet->getActiveSheet()->getPageSetup()->setFitToHeight(0);

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

        return $spreadsheet;
    }

    public static function createAll(\DateTime $startDate, \DateTime $endDate): Spreadsheet
    {
        $startDateString = $startDate->format('Y-m-d');
        $endDateString = $endDate->format('Y-m-d');

        $spreadsheet = new Spreadsheet();
        $spreadsheet->getActiveSheet()->getPageSetup()->setOrientation(PageSetup::ORIENTATION_PORTRAIT);
        $spreadsheet->getActiveSheet()->getPageSetup()->setPaperSize(PageSetup::PAPERSIZE_A4);
        $spreadsheet->getActiveSheet()->getPageSetup()->setFitToWidth(1);
        $spreadsheet->getActiveSheet()->getPageSetup()->setFitToHeight(0);

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

        return $spreadsheet;
    }
}
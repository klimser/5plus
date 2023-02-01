<?php

namespace backend\components\report;

use common\models\CourseCategory;
use common\models\Payment;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;

class CashReport
{
    public static function create(\DateTimeImmutable $date, CourseCategory $courseCategory): Spreadsheet
    {
        $date = $date->modify('midnight');
        $endDate = $date->modify('+1 day -1 second');

        /** @var Payment[] $payments */
        $payments = Payment::find()
            ->andWhere(['>', 'amount', 0])
            ->andWhere(['BETWEEN', 'created_at', $date->format('Y-m-d H:i:s'), $endDate->format('Y-m-d H:i:s')])
            ->andWhere('contract_id IS NOT NULL')
            ->andWhere('admin_id IS NOT NULL')
            ->joinWith('course c')
            ->andWhere('c.category_id = :category', ['category' => $courseCategory->id])
            ->with('admin', 'contract.payments')
            ->all();

        $adminSumMap = [];
        $adminMap = [];
        foreach ($payments as $payment) {
            if (!array_key_exists($payment->admin_id, $adminSumMap)) {
                $adminSumMap[$payment->admin_id] = 0;
            }
            $adminMap[$payment->admin_id] = $payment->admin->name;

            if (count($payment->contract->payments) == 1) {
                $adminSumMap[$payment->admin_id] += $payment->amount;
            } else {
                $ok = true;
                $minDate = $payment->created_at;
                $paymentSum = 0;
                foreach ($payment->contract->payments as $coPayment) {
                    $paymentSum += $coPayment->amount;
                    if ($coPayment->created_at < $minDate) {
                        $ok = false;
                        break;
                    }
                }
                if ($ok) {
                    $adminSumMap[$payment->admin_id] += $paymentSum;
                }
            }
        }

        $spreadsheet = new Spreadsheet();
        $spreadsheet->getActiveSheet()->getPageSetup()->setOrientation(PageSetup::ORIENTATION_PORTRAIT);
        $spreadsheet->getActiveSheet()->getPageSetup()->setPaperSize(PageSetup::PAPERSIZE_A4);
        $spreadsheet->getActiveSheet()->getPageSetup()->setFitToWidth(1);
        $spreadsheet->getActiveSheet()->getPageSetup()->setFitToHeight(0);

        $spreadsheet->getActiveSheet()->mergeCells('A1:B1');
        $spreadsheet->getActiveSheet()
            ->setCellValue('A1', "Касса за {$date->format('d.m.Y')}" . $courseCategory->name);
        $spreadsheet->getActiveSheet()->getStyle('A1')->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_CENTER);
        $spreadsheet->getActiveSheet()->getStyle('A1')->getFont()->setBold(true)->setSize(16);

        $spreadsheet->getActiveSheet()
            ->setCellValue('A2', 'Менеджер')
            ->setCellValue('B2', 'Принято денег');

        $totalCash = 0;
        $row = 3;
        foreach ($adminSumMap as $adminId => $sum) {
            $spreadsheet->getActiveSheet()
                ->setCellValueByColumnAndRow(1, $row, $adminMap[$adminId])
                ->setCellValueExplicitByColumnAndRow(2, $row, $sum, DataType::TYPE_NUMERIC);
            $totalCash += $sum;
            $row++;
        }

        $spreadsheet->getActiveSheet()
            ->setCellValueByColumnAndRow(1, $row, 'Итого')
            ->setCellValueExplicitByColumnAndRow(2, $row, $totalCash, DataType::TYPE_NUMERIC);
        $spreadsheet->getActiveSheet()->getStyleByColumnAndRow(1, $row, 2, $row)
            ->getFont()->setBold(true)->setSize(12);

        $spreadsheet->getActiveSheet()->getColumnDimensionByColumn(1)->setAutoSize(true);
        $spreadsheet->getActiveSheet()->getColumnDimensionByColumn(2)->setAutoSize(true);

        return $spreadsheet;
    }
}

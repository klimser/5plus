<?php

namespace backend\components\report;

use common\models\Course;
use common\models\Payment;
use common\models\User;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;

class StudentReport
{
    public static function create(User $student, Course $course): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getActiveSheet()->getPageSetup()->setOrientation(PageSetup::ORIENTATION_PORTRAIT);
        $spreadsheet->getActiveSheet()->getPageSetup()->setPaperSize(PageSetup::PAPERSIZE_A4);
        $spreadsheet->getActiveSheet()->getPageSetup()->setFitToWidth(1);
        $spreadsheet->getActiveSheet()->getPageSetup()->setFitToHeight(0);

        $greenColor = '9FF298';
        $spreadsheet->getActiveSheet()->mergeCells('A1:H1');
        $spreadsheet->getActiveSheet()->setCellValue('A1', $student->name);
        $spreadsheet->getActiveSheet()->getStyle("A1")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $spreadsheet->getActiveSheet()->getStyle("A1")->getFont()->setBold(true)->setSize(16);

        $spreadsheet->getActiveSheet()->setCellValue('H3', date('d.m.Y'));
        $spreadsheet->getActiveSheet()->mergeCells('A4:H4');
        $spreadsheet->getActiveSheet()->setCellValue('A4', 'Табель оплат');
        $spreadsheet->getActiveSheet()->getStyle("A4")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $spreadsheet->getActiveSheet()->setCellValue('A5', '№');
        $spreadsheet->getActiveSheet()->setCellValue('B5', 'Предмет');
        $spreadsheet->getActiveSheet()->setCellValue('C5', 'Договор №');
        $spreadsheet->getActiveSheet()->setCellValue('D5', 'Дата');
        $spreadsheet->getActiveSheet()->setCellValue('E5', 'Сумма');
        $spreadsheet->getActiveSheet()->setCellValue('F5', 'С');
        $spreadsheet->getActiveSheet()->setCellValue('G5', 'По');
        $spreadsheet->getActiveSheet()->setCellValue('H5', 'Остаток');

        $spreadsheet->getActiveSheet()->getColumnDimension('A')->setWidth(5);
        $spreadsheet->getActiveSheet()->getColumnDimension('B')->setWidth(20);
        $spreadsheet->getActiveSheet()->getColumnDimension('C')->setWidth(16);
        $spreadsheet->getActiveSheet()->getColumnDimension('D')->setWidth(13);
        $spreadsheet->getActiveSheet()->getColumnDimension('E')->setWidth(15);
        $spreadsheet->getActiveSheet()->getColumnDimension('F')->setWidth(13);
        $spreadsheet->getActiveSheet()->getColumnDimension('G')->setWidth(13);
        $spreadsheet->getActiveSheet()->getColumnDimension('H')->setWidth(15);

        $row = 6;
        /** @var Payment[] $payments */
        $payments = Payment::find()
            ->andWhere(['user_id' => $student->id, 'course_id' => $course->id])
            ->andWhere(['>', 'amount', 0])
            ->orderBy(['created_at' => SORT_ASC])
            ->with('payments')
            ->all();

        $num = 1;
        foreach ($payments as $payment) {
            $spreadsheet->getActiveSheet()->setCellValueExplicit("A$row", $num, DataType::TYPE_NUMERIC);
            $spreadsheet->getActiveSheet()->setCellValue("B$row", $payment->courseConfig->name);
            if ($payment->contract) {
                $spreadsheet->getActiveSheet()->setCellValueExplicit("C$row", $payment->contract->number, DataType::TYPE_STRING);
            }
            $spreadsheet->getActiveSheet()->setCellValue("D$row", Date::PHPToExcel($payment->createDate));
            $spreadsheet->getActiveSheet()->setCellValueExplicit("E$row", $payment->amount, DataType::TYPE_NUMERIC);

            if ($payment->discount == Payment::STATUS_ACTIVE) {
                $spreadsheet->getActiveSheet()->getStyle("E$row")->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setRGB($greenColor);
            }

            $minDate = $maxDate = null;
            foreach ($payment->payments as $childPayment) {
                if (!$minDate || $childPayment->createDate < $minDate) $minDate = $childPayment->createDate;
                if (!$maxDate || $childPayment->createDate > $maxDate) $maxDate = $childPayment->createDate;
            }
            if ($minDate) {
                $spreadsheet->getActiveSheet()->setCellValue("F$row", Date::PHPToExcel($minDate));
            }
            if ($maxDate && $payment->amount == $payment->paymentsSum) {
                $spreadsheet->getActiveSheet()->setCellValue("G$row", Date::PHPToExcel($maxDate));
            }
            $spreadsheet->getActiveSheet()->setCellValueExplicit("H$row", $payment->amount - $payment->paymentsSum, DataType::TYPE_NUMERIC);

            $num++;
            $row++;
        }
        $row--;

        $spreadsheet->getActiveSheet()->getStyle("A4:H$row")->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => 'FF000000'],
                ],
            ],
        ]);
        $spreadsheet->getActiveSheet()->getStyle("A4:H5")->getFont()->setItalic(true);
        $spreadsheet->getActiveSheet()->getStyle("D6:D$row")->getNumberFormat()
            ->setFormatCode(NumberFormat::FORMAT_DATE_DMYMINUS);
        $spreadsheet->getActiveSheet()->getStyle("F6:G$row")->getNumberFormat()
            ->setFormatCode(NumberFormat::FORMAT_DATE_DMYMINUS);
        $spreadsheet->getActiveSheet()->getStyle("E6:E$row")->getNumberFormat()->setFormatCode('# ##0');
        $spreadsheet->getActiveSheet()->getStyle("H6:H$row")->getNumberFormat()->setFormatCode('# ##0');

        return $spreadsheet;
    }
}

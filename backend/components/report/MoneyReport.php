<?php

namespace backend\components\report;

use common\models\Course;
use common\models\Payment;
use DateTimeImmutable;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;

class MoneyReport
{
    public static function createForOneCourse(Course $course, DateTimeImmutable $startDate, DateTimeImmutable $endDate): Spreadsheet
    {
        $startDateString = $startDate->format('Y-m-d H:i:s');
        $endDateString = $endDate->format('Y-m-d H:i:s');

        $spreadsheet = new Spreadsheet();
        $spreadsheet->getActiveSheet()->getPageSetup()->setOrientation(PageSetup::ORIENTATION_PORTRAIT);
        $spreadsheet->getActiveSheet()->getPageSetup()->setPaperSize(PageSetup::PAPERSIZE_A4);
        $spreadsheet->getActiveSheet()->getPageSetup()->setFitToWidth(1);
        $spreadsheet->getActiveSheet()->getPageSetup()->setFitToHeight(0);

        $total = ['in_normal' => 0, 'in_discount' => 0, 'out_normal' => 0, 'out_discount' => 0];
        $amounts = Payment::find()
            ->andWhere(['course_id' => $course->id])
            ->andWhere(['>', 'amount', 0])
            ->andWhere(['>=', 'created_at', $startDateString])
            ->andWhere(['<', 'created_at', $endDateString])
            ->select(['discount', 'SUM(amount) as amount'])
            ->groupBy('discount')
            ->asArray()
            ->all();
        foreach ($amounts as $record) {
            $total[Payment::STATUS_ACTIVE == $record['discount'] ? 'in_discount' : 'in_normal'] = $record['amount'];
        }
        $amounts = Payment::find()
            ->andWhere(['course_id' => $course->id])
            ->andWhere(['<', 'amount', 0])
            ->andWhere(['>=', 'created_at', $startDateString])
            ->andWhere(['<', 'created_at', $endDateString])
            ->andWhere('used_payment_id IS NOT NULL')
            ->select(['discount', 'SUM(amount) as amount'])
            ->groupBy('discount')
            ->asArray()
            ->all();
        foreach ($amounts as $record) {
            $total[Payment::STATUS_ACTIVE == $record['discount'] ? 'out_discount' : 'out_normal'] = abs($record['amount']);
        }

        $spreadsheet->getActiveSheet()
            ->setCellValue('A1', 'Группа')
            ->setCellValue('A2', 'Собрано денег со скидкой')
            ->setCellValue('A3', 'Собрано денег без скидки')
            ->setCellValue('A4', 'Собрано всего')
            ->setCellValue('A5', 'Списано за занятия со скидкой')
            ->setCellValue('A6', 'Списано за занятия без скидки')
            ->setCellValue('A7', 'Списано за занятия всего')
            ->setCellValue('B1', $course->getCourseConfigByDate($endDate->modify('-1 day'))->name)
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

    public static function createForAllCourses(DateTimeImmutable $startDate, DateTimeImmutable $endDate): Spreadsheet
    {
        $startDateString = $startDate->format('Y-m-d H:i:s');
        $endDateString = $endDate->format('Y-m-d H:i:s');

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

        $courseIds = Payment::find()
            ->andWhere(['>=', 'created_at', $startDateString])
            ->andWhere(['<', 'created_at', $endDateString])
            ->andWhere(['>', 'amount', 0])
            ->select('course_id')
            ->distinct()
            ->column();
        /** @var Course[] $courses */
        $courses = Course::findAll(['id' => $courseIds]);
        $courseMap = [];
        foreach ($courses as $course) {
            $courseMap[$course->id] = [
                'name' => $course->getCourseConfigByDate($endDate->modify('-1 day'))->name,
                'kids' => $course->kids,
                'in_normal' => 0,
                'in_discount' => 0,
                'out_normal' => 0,
                'out_discount' => 0,
            ];
        }

        $amounts = Payment::find()
            ->andWhere(['course_id' => $courseIds])
            ->andWhere(['>', 'amount', 0])
            ->andWhere(['>=', 'created_at', $startDateString])
            ->andWhere(['<', 'created_at', $endDateString])
            ->select(['course_id', 'discount', 'SUM(amount) as amount'])
            ->groupBy(['course_id', 'discount'])
            ->asArray()
            ->all();
        foreach ($amounts as $record) {
            $courseMap[$record['course_id']][Payment::STATUS_ACTIVE == $record['discount'] ? 'in_discount' : 'in_normal'] = $record['amount'];
        }
        $amounts = Payment::find()
            ->andWhere(['course_id' => $courseIds])
            ->andWhere(['<', 'amount', 0])
            ->andWhere(['>=', 'created_at', $startDateString])
            ->andWhere(['<', 'created_at', $endDateString])
            ->andWhere('used_payment_id IS NOT NULL')
            ->select(['course_id', 'discount', 'SUM(amount) as amount'])
            ->groupBy(['course_id', 'discount'])
            ->asArray()
            ->all();
        foreach ($amounts as $record) {
            $courseMap[$record['course_id']][Payment::STATUS_ACTIVE == $record['discount'] ? 'out_discount' : 'out_normal'] = abs($record['amount']);
        }

        $renderTable = function (bool $kids, int $row) use ($spreadsheet, $courseMap) {
            $startRow = $row;
            foreach ($courseMap as $courseData) {
                if ($courseData['kids'] != $kids) {
                    continue;
                }
                $spreadsheet->getActiveSheet()->setCellValue("A$row", $courseData['name']);
                $spreadsheet->getActiveSheet()->setCellValueExplicit("B$row", $courseData['in_discount'], DataType::TYPE_NUMERIC);
                $spreadsheet->getActiveSheet()->setCellValueExplicit("C$row", $courseData['in_normal'], DataType::TYPE_NUMERIC);
                $spreadsheet->getActiveSheet()->setCellValue("D$row", "=B$row+C$row");
                $spreadsheet->getActiveSheet()->setCellValueExplicit("E$row", $courseData['out_discount'], DataType::TYPE_NUMERIC);
                $spreadsheet->getActiveSheet()->setCellValueExplicit("F$row", $courseData['out_normal'], DataType::TYPE_NUMERIC);
                $spreadsheet->getActiveSheet()->setCellValue("G$row", "=E$row+F$row");
                ++$row;
            }

            if ($row !== $startRow) {
                $spreadsheet->getActiveSheet()->setCellValue("A$row", 'Итого');
                $spreadsheet->getActiveSheet()->setCellValue("B$row", "=SUM(B$startRow:B" . ($row - 1) . ')');
                $spreadsheet->getActiveSheet()->setCellValue("C$row", "=SUM(C$startRow:C" . ($row - 1) . ')');
                $spreadsheet->getActiveSheet()->setCellValue("D$row", "=SUM(D$startRow:D" . ($row - 1) . ')');
                $spreadsheet->getActiveSheet()->setCellValue("E$row", "=SUM(E$startRow:E" . ($row - 1) . ')');
                $spreadsheet->getActiveSheet()->setCellValue("F$row", "=SUM(F$startRow:F" . ($row - 1) . ')');
                $spreadsheet->getActiveSheet()->setCellValue("G$row", "=SUM(G$startRow:G" . ($row - 1) . ')');
                $spreadsheet->getActiveSheet()->getStyle("A$row:G$row")->getFont()->setBold(true);
                $spreadsheet->getActiveSheet()->getStyle("B$startRow:G$row")->getNumberFormat()->setFormatCode('#,##0');
            }

            return $row;
        };
        $nextRow = $renderTable(false, 3);
        $renderTable(true, $nextRow + 3);

        return $spreadsheet;
    }
}

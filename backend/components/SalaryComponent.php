<?php

namespace backend\components;

use backend\models\Event;
use backend\models\EventMember;
use common\components\helpers\Calendar;
use common\models\Course;
use common\models\CourseConfig;
use common\models\Payment;
use DateTimeImmutable;
use Exception;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;

class SalaryComponent
{
    public const RED_COLOR = 'f2dede';
    public const GREEN_COLOR = 'def2de';

    public static function getCourseSalarySpreadsheet(Course $course, DateTimeImmutable $date): Spreadsheet
    {
        $dateFrom = $date->modify('first day of this month midnight');
        $dateTo = $date->modify('first day of next month midnight');
        /** @var CourseConfig[] $courseConfigs */
        $courseConfigs = CourseConfig::find()
            ->andWhere(['course_id' => $course->id])
            ->andWhere(['<', 'date_from', $dateTo->format('Y-m-d H:i:s')])
            ->andWhere(['or', ['date_to' => null], ['>', 'date_to', $dateFrom->format('Y-m-d H:i:s')]])
            ->with('teacher')
            ->all();
        $courseConfigMap = [];
        foreach ($courseConfigs as $courseConfig) {
            $courseConfigMap[$courseConfig->teacher_id][] = $courseConfig;
        }

        $daysCount = intval($date->format('t'));

        $spreadsheet = new Spreadsheet();
        $sheetIndex = 0;

        foreach ($courseConfigMap as $courseConfigs) {
            if (0 < $sheetIndex) {
                $spreadsheet->createSheet($sheetIndex);
                $spreadsheet->setActiveSheetIndex($sheetIndex);
            }
            ++$sheetIndex;

            $courseConfig = reset($courseConfigs);
            $spreadsheet->getActiveSheet()->getPageSetup()->setOrientation(PageSetup::ORIENTATION_LANDSCAPE);
            $spreadsheet->getActiveSheet()->getPageSetup()->setPaperSize(PageSetup::PAPERSIZE_A4);
            $spreadsheet->getActiveSheet()->getPageSetup()->setFitToWidth(1);
            $spreadsheet->getActiveSheet()->getPageSetup()->setFitToHeight(0);
            $spreadsheet->getActiveSheet()->setTitle(mb_substr($courseConfig->teacher->name, 0, 30));

            self::fillTeacherCourseTable($spreadsheet, $courseConfigs, $date, 1);

            for ($i = 1; $i < $daysCount + 2; $i++) {
                $spreadsheet->getActiveSheet()->getColumnDimensionByColumn($i)->setAutoSize(true);
            }
        }

        return $spreadsheet;
    }

    public static function getMonthDetailedSalarySpreadsheet(DateTimeImmutable $date): Spreadsheet
    {
        $dateFrom = $date->modify('first day of this month midnight');
        $dateTo = $date->modify('first day of next month midnight');

        /** @var CourseConfig[] $courseConfigs */
        $courseConfigs = CourseConfig::find()
            ->alias('cc')
            ->andWhere(['<', 'date_from', $dateTo->format('Y-m-d H:i:s')])
            ->andWhere(['or', ['date_to' => null], ['>', 'date_to', $dateFrom->format('Y-m-d H:i:s')]])
            ->leftJoin(
                ['e' => Event::tableName()],
                'cc.course_id = e.course_id AND e.event_date BETWEEN :dateFrom AND :dateTo AND e.status = :passed',
                ['dateFrom' => $dateFrom->format('Y-m-d H:i:s'), 'dateTo' => $dateTo->format('Y-m-d H:i:s'), 'passed' => Event::STATUS_PASSED]
            )
            ->select(['cc.*', 'COUNT(e.id) as cnt'])
            ->groupBy('cc.id')
            ->having('cnt > 0')
            ->with('teacher')
            ->all();
        $courseConfigMap = [];
        foreach ($courseConfigs as $courseConfig) {
            $courseConfigMap[$courseConfig->teacher_id][$courseConfig->course_id][] = $courseConfig;
        }

        $daysCount = intval($date->format('t'));
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getActiveSheet()->getPageSetup()->setOrientation(PageSetup::ORIENTATION_LANDSCAPE);
        $spreadsheet->getActiveSheet()->getPageSetup()->setPaperSize(PageSetup::PAPERSIZE_A4);
        $spreadsheet->getActiveSheet()->getPageSetup()->setFitToWidth(1);
        $spreadsheet->getActiveSheet()->getPageSetup()->setFitToHeight(0);
        $spreadsheet->getActiveSheet()->setTitle($date->format('m-Y'));

        $row = 1;
        foreach ($courseConfigMap as $courseConfigMapByCourse) {
            foreach ($courseConfigMapByCourse as $courseConfigs) {
                $row = self::fillTeacherCourseTable($spreadsheet, $courseConfigs, $date, $row);
                ++$row;
            }
        }

        for ($i = 1; $i < $daysCount + 2; $i++) {
            $spreadsheet->getActiveSheet()->getColumnDimensionByColumn($i)->setAutoSize(true);
        }

        return $spreadsheet;
    }

    public static function getMonthSalarySpreadsheet(DateTimeImmutable $date): Spreadsheet
    {
        $dateFrom = $date->modify('first day of this month midnight');
        $dateTo = $date->modify('first day of next month midnight');

        /** @var CourseConfig[] $courseConfigs */
        $courseConfigs = CourseConfig::find()
            ->alias('cc')
            ->andWhere(['<', 'date_from', $dateTo->format('Y-m-d H:i:s')])
            ->andWhere(['or', ['date_to' => null], ['>', 'date_to', $dateFrom->format('Y-m-d H:i:s')]])
            ->with(['teacher', 'course'])
            ->orderBy(['cc.teacher_id' => SORT_ASC])->all();

        if (empty($courseConfigs)) throw new Exception('No salary data found');

        $salaryMap = [];
        foreach ($courseConfigs as $courseConfig) {
            if (!isset($salaryMap[$courseConfig->teacher_id][$courseConfig->course_id])) {
                $salaryMap[$courseConfig->teacher_id][$courseConfig->course_id] = [
                    'teacher' => $courseConfig->teacher->name,
                    'course' => $courseConfig->name,
                    'amount' => 0
                ];
            }
            /** @var DateTimeImmutable $eventDateFrom */
            $eventDateFrom = max($dateFrom, $courseConfig->dateFromObject);
            /** @var DateTimeImmutable $eventDateTo */
            $eventDateTo = (null === $courseConfig->dateToObject) ? $dateTo : min($dateTo, $courseConfig->dateToObject);

            if (null !== $courseConfig->teacher_rate) {
                $paymentSum = Payment::find()
                    ->andWhere(['between', 'created_at', $eventDateFrom->format('Y-m-d H:i:s'), $eventDateTo->format('Y-m-d H:i:s')])
                    ->andWhere(['course_id' => $courseConfig->course_id])
                    ->andWhere('amount < 0')
                    ->andWhere('used_payment_id IS NOT NULL')
                    ->select('SUM(amount)')
                    ->scalar() ?? 0;
                $salary = round(abs($paymentSum) * $courseConfig->teacher_rate / 100);
            } else {
                $eventPassed = Event::find()
                    ->andWhere(['between', 'event_date', $eventDateFrom->format('Y-m-d H:i:s'), $eventDateTo->format('Y-m-d H:i:s')])
                    ->andWhere(['course_id' => $courseConfig->course_id])
                    ->andWhere(['status' => Event::STATUS_PASSED])
                    ->select('COUNT(id)')
                    ->scalar();
                $salary = round($eventPassed * $courseConfig->teacher_lesson_pay);
            }

            $salaryMap[$courseConfig->teacher_id][$courseConfig->course_id]['amount'] += $salary;
        }

        $spreadsheet = new Spreadsheet();
        $spreadsheet->getActiveSheet()->getPageSetup()->setOrientation(PageSetup::ORIENTATION_LANDSCAPE);
        $spreadsheet->getActiveSheet()->getPageSetup()->setPaperSize(PageSetup::PAPERSIZE_A4);
        $spreadsheet->getActiveSheet()->getPageSetup()->setFitToWidth(1);
        $spreadsheet->getActiveSheet()->getPageSetup()->setFitToHeight(0);
        $spreadsheet->getActiveSheet()->setTitle($date->format('m-Y'));

        $spreadsheet->getActiveSheet()->mergeCells("A1:I1");
        $spreadsheet->getActiveSheet()->setCellValue('A1', Calendar::$monthNames[intval($date->format('n'))] . ' ' . $date->format('Y'));
        $spreadsheet->getActiveSheet()->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $spreadsheet->getActiveSheet()->getStyle('A1')->getFont()->setSize(22);
        $spreadsheet->getActiveSheet()->getStyle('A1')->getFont()->setBold(true);

        $spreadsheet->getActiveSheet()->setCellValue('A2', '№');
        $spreadsheet->getActiveSheet()->setCellValue('B2', 'ФИО учителя');
        $spreadsheet->getActiveSheet()->setCellValue('C2', 'Группа');
        $spreadsheet->getActiveSheet()->setCellValue('D2', 'Сумма');
        $spreadsheet->getActiveSheet()->setCellValue('E2', 'Итого за группы');
        $spreadsheet->getActiveSheet()->setCellValue('F2', '30,5%');
        $spreadsheet->getActiveSheet()->setCellValue('G2', 'На карту');
        $spreadsheet->getActiveSheet()->setCellValue('H2', 'Гарант');
        $spreadsheet->getActiveSheet()->setCellValue('I2', 'Итого');
        $spreadsheet->getActiveSheet()->getStyle("A2:I2")->getFont()->setBold(true);
        $spreadsheet->getActiveSheet()->getStyle("A2:I2")->getFont()->setItalic(true);
        $spreadsheet->getActiveSheet()->getColumnDimension('A')->setWidth(3);
        $spreadsheet->getActiveSheet()->getColumnDimension('B')->setWidth(30);
        $spreadsheet->getActiveSheet()->getColumnDimension('C')->setWidth(30);
        $spreadsheet->getActiveSheet()->getColumnDimension('D')->setWidth(12);
        $spreadsheet->getActiveSheet()->getColumnDimension('E')->setWidth(12);
        $spreadsheet->getActiveSheet()->getColumnDimension('F')->setWidth(12);
        $spreadsheet->getActiveSheet()->getColumnDimension('G')->setWidth(12);
        $spreadsheet->getActiveSheet()->getColumnDimension('H')->setWidth(12);
        $spreadsheet->getActiveSheet()->getColumnDimension('I')->setWidth(12);

        $row = 3;
        $num = 1;
        foreach ($salaryMap as $salaryMapByCourse) {
            $teacherName = null;
            $startRow = $row;
            foreach ($salaryMapByCourse as $salaryData) {
                $teacherName = $salaryData['teacher'];
                $spreadsheet->getActiveSheet()->setCellValue("C$row", $salaryData['course']);
                $spreadsheet->getActiveSheet()->setCellValueExplicit("D$row", $salaryData['amount'], DataType::TYPE_NUMERIC);
                ++$row;
            }

            $spreadsheet->getActiveSheet()->mergeCells("A$startRow:A" . ($row - 1));
            $spreadsheet->getActiveSheet()->mergeCells("B$startRow:B" . ($row - 1));
            $spreadsheet->getActiveSheet()->mergeCells("E$startRow:E" . ($row - 1));
            $spreadsheet->getActiveSheet()->mergeCells("F$startRow:F" . ($row - 1));
            $spreadsheet->getActiveSheet()->mergeCells("G$startRow:g" . ($row - 1));
            $spreadsheet->getActiveSheet()->mergeCells("H$startRow:H" . ($row - 1));
            $spreadsheet->getActiveSheet()->mergeCells("I$startRow:I" . ($row - 1));

            $spreadsheet->getActiveSheet()->setCellValue("A$startRow", $num);
            $spreadsheet->getActiveSheet()->setCellValue("B$startRow", $teacherName);
            $spreadsheet->getActiveSheet()->setCellValue("E$startRow", "=SUM(D$startRow:D" . ($row - 1) . ')');
            $spreadsheet->getActiveSheet()->setCellValue("F$startRow", "=E$startRow*0.12");
            $spreadsheet->getActiveSheet()->setCellValue("I$startRow", "=E$startRow-F$startRow-G$startRow");

            ++$num;
        }

        --$row;
        $spreadsheet->getActiveSheet()->getStyle("A3:A$row")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $spreadsheet->getActiveSheet()->getStyle("B3:C$row")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        $spreadsheet->getActiveSheet()->getStyle("D3:I$row")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $spreadsheet->getActiveSheet()->getStyle("A3:I$row")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        $spreadsheet->getActiveSheet()->getStyle("A2:I$row")->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => 'FF000000'],
                ],
            ],
        ]);

        return $spreadsheet;
    }

    /**
     * @param array<CourseConfig> $courseConfigs
     */
    private static function fillTeacherCourseTable(Spreadsheet $spreadsheet, array $courseConfigs, DateTimeImmutable $date, int $row): int
    {
        $dateFrom = $date->modify('first day of this month midnight');
        $dateTo = $date->modify('first day of next month midnight');
        $daysCount = intval($date->format('t'));
        $lastColumn = $daysCount + 2;

        $courseConfig = reset($courseConfigs);

        $spreadsheet->getActiveSheet()->mergeCellsByColumnAndRow(1, $row, $lastColumn, $row);
        $spreadsheet->getActiveSheet()->setCellValue(
            "A$row",
            "$courseConfig->name - " . Calendar::$monthNames[intval($date->format('n'))] . ' ' . $date->format('Y')
        );
        $spreadsheet->getActiveSheet()->getStyle("A$row")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $spreadsheet->getActiveSheet()->getStyle("A$row")->getFont()->setSize(22);
        $spreadsheet->getActiveSheet()->getStyle("A$row")->getFont()->setBold(true);

        $row += 2;
        $spreadsheet->getActiveSheet()->setCellValue("A$row", 'Преподаватель');
        $spreadsheet->getActiveSheet()->mergeCellsByColumnAndRow(2, $row, $lastColumn, $row);
        $spreadsheet->getActiveSheet()->setCellValue("B$row", $courseConfig->teacher->name);
        $spreadsheet->getActiveSheet()->getStyle("B$row")->getFont()->setBold(true);

        $dataMap = ['student' => [], 'teacher' => [], 'config' => [], 'event' => []];

        foreach ($courseConfigs as $courseConfig) {
            /** @var DateTimeImmutable $eventDateFrom */
            $eventDateFrom = max($dateFrom, $courseConfig->dateFromObject);
            /** @var DateTimeImmutable $eventDateTo */
            $eventDateTo = (null === $courseConfig->dateToObject) ? $dateTo : min($dateTo, $courseConfig->dateToObject);

            /** @var Event[] $events */
            $events = Event::find()
                ->andWhere(['course_id' => $courseConfig->course_id])
                ->andWhere(['BETWEEN', 'event_date', $eventDateFrom->format('Y-m-d H:i:s'), $eventDateTo->format('Y-m-d H:i:s')])
                ->with('members.payments')
                ->with('members.courseStudent.user')
                ->all();

            foreach ($events as $event) {
                $day = (int) $event->eventDateTime->format('j');
                $dataMap['config'][$day] = [
                    'rate' => $courseConfig->teacher_rate,
                    'fix' => $courseConfig->teacher_lesson_pay,
                    'price' => $courseConfig->lesson_price,
                    'price_discount' => $courseConfig->lesson_price_discount,
                ];
                $dataMap['event'][$day] = $event->status;
                foreach ($event->members as $eventMember) {
                    if (empty($dataMap['student'][$eventMember->course_student_id])) {
                        $dataMap['student'][$eventMember->course_student_id] = [
                            'name' => $eventMember->courseStudent->user->name,
                            'payments' => [],
                            'status' => [],
                        ];
                    }

                    if ($eventMember->payments) {
                        $paymentSum = 0;
                        foreach ($eventMember->payments as $payment) {
                            if ($payment->used_payment_id) {
                                $paymentSum -= $payment->amount;
                            }
                        }
                        $dataMap['student'][$eventMember->course_student_id]['status'][$day] = $eventMember->status;
                        $dataMap['student'][$eventMember->course_student_id]['payments'][$day] = $paymentSum;
                        $dataMap['teacher'][$day] = ($dataMap['teacher'][$day] ?? 0) + $paymentSum;
                    }
                }
            }
        }

        $row += 2;
        if (empty($dataMap['teacher'])) {
            $spreadsheet->getActiveSheet()->mergeCellsByColumnAndRow(1, $row, $lastColumn, $row);
            $spreadsheet->getActiveSheet()->getStyle("A$row")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $spreadsheet->getActiveSheet()->setCellValue("A$row", 'В группе не было занятий');
        } else {
            for ($i = 1; $i <= $daysCount; ++$i) {
                $spreadsheet->getActiveSheet()
                    ->setCellValueExplicitByColumnAndRow($i + 1, $row, $i, DataType::TYPE_NUMERIC);
            }
            $spreadsheet->getActiveSheet()->setCellValueByColumnAndRow($lastColumn, $row, 'Итого');
            $spreadsheet->getActiveSheet()->getStyleByColumnAndRow(2, $row, $lastColumn, $row)
                ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $spreadsheet->getActiveSheet()->getStyleByColumnAndRow(2, $row, $lastColumn, $row)
                ->getFont()->setBold(true);
            $tableTopRow = $row;

            ++$row;
            $spreadsheet->getActiveSheet()->setCellValueByColumnAndRow(1, $row, 'Стоимость занятия');
            $spreadsheet->getActiveSheet()->setCellValueByColumnAndRow(1, $row + 1, 'Стоимость со скидкой');
            $spreadsheet->getActiveSheet()->getStyleByColumnAndRow(2, $row, $lastColumn, $row + 1)
                ->getFill()->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setRGB(self::GREEN_COLOR);

            // Цена занятий
            foreach ($dataMap['config'] as $day => $config) {
                $spreadsheet->getActiveSheet()
                    ->setCellValueExplicitByColumnAndRow($day + 1, $row, $config['price'], DataType::TYPE_NUMERIC);
                $spreadsheet->getActiveSheet()
                    ->setCellValueExplicitByColumnAndRow($day + 1, $row + 1, $config['price_discount'], DataType::TYPE_NUMERIC);
            }

            // Студенты и их оплаты
            $row += 2;
            foreach ($dataMap['student'] as $data) {
                $spreadsheet->getActiveSheet()->setCellValue("A$row", $data['name']);
                foreach ($data['payments'] as $day => $amount) {
                    $spreadsheet->getActiveSheet()
                        ->setCellValueExplicitByColumnAndRow($day + 1, $row, $amount, DataType::TYPE_NUMERIC);
                    if (EventMember::STATUS_MISS === $data['status'][$day]) {
                        $spreadsheet->getActiveSheet()->getStyleByColumnAndRow($day + 1, $row)
                            ->getFill()->setFillType(Fill::FILL_SOLID)
                            ->getStartColor()->setRGB(self::RED_COLOR);
                    }
                }

                ++$row;
            }

            // Проведенные занятия
            foreach ($dataMap['event'] as $day => $status) {
                if (Event::STATUS_CANCELED === $status) {
                    $spreadsheet->getActiveSheet()->getStyleByColumnAndRow($day + 1, $tableTopRow, $day + 1, $row)
                        ->getFill()->setFillType(Fill::FILL_SOLID)
                        ->getStartColor()->setRGB(self::RED_COLOR);
                }
            }

            // Зарплата
            $spreadsheet->getActiveSheet()->setCellValue("A$row", 'Зарплата преподавателю');
            foreach ($dataMap['teacher'] as $day => $amount) {
                if (null !== $dataMap['config'][$day]['rate']) {
                    $salary = round($amount * $dataMap['config'][$day]['rate'] / 100);
                } else {
                    $salary = $dataMap['config'][$day]['fix'];
                }
                $spreadsheet->getActiveSheet()->setCellValueExplicitByColumnAndRow($day + 1, $row, $salary, DataType::TYPE_NUMERIC);
            }

            for ($i = $tableTopRow + 3; $i <= $row; ++$i) {
                $spreadsheet->getActiveSheet()->setCellValueByColumnAndRow(
                    $lastColumn,
                    $i,
                    "=SUM(B$i:" . Coordinate::stringFromColumnIndex($lastColumn - 1) . $i . ')',
                );
            }

            $spreadsheet->getActiveSheet()->getStyleByColumnAndRow($lastColumn, $tableTopRow, $lastColumn, $row)->getFont()->setBold(true);
            $spreadsheet->getActiveSheet()->getStyleByColumnAndRow(1, $row, $lastColumn, $row)->getFont()->setBold(true);
            $spreadsheet->getActiveSheet()->getStyleByColumnAndRow(1, $tableTopRow, $lastColumn, $row)->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['argb' => 'FF000000'],
                    ],
                ],
            ]);
        }

        return $row + 1;
    }
}

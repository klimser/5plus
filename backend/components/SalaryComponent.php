<?php

namespace backend\components;

use backend\models\Event;
use backend\models\EventMember;
use common\components\helpers\Calendar;
use common\models\Group;
use common\models\GroupParam;
use common\models\GroupPupil;
use common\models\User;
use DateTime;
use Exception;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;

class SalaryComponent
{
    public static function getGroupSalarySpreadsheet(Group $group, DateTime $date): Spreadsheet
    {
        $groupParam = GroupParam::findByDate($group, $date);
        if (!$groupParam) throw new Exception('There is no salary for this month');

        $daysCount = intval($date->format('t'));
        $lastColumn = $daysCount + 2;

        $spreadsheet = new Spreadsheet();
        $spreadsheet->getActiveSheet()->getPageSetup()->setOrientation(PageSetup::ORIENTATION_LANDSCAPE);
        $spreadsheet->getActiveSheet()->getPageSetup()->setPaperSize(PageSetup::PAPERSIZE_A4);
        $spreadsheet->getActiveSheet()->getPageSetup()->setFitToWidth(1);
        $spreadsheet->getActiveSheet()->getPageSetup()->setFitToHeight(0);
        $spreadsheet->getActiveSheet()->setTitle($date->format('m-Y') . ' ' . mb_substr($group->name, 0, 22));

        $spreadsheet->getActiveSheet()->mergeCellsByColumnAndRow(1, 1, $lastColumn, 1);
        $spreadsheet->getActiveSheet()->setCellValue(
            'A1',
            "$group->name - " . Calendar::$monthNames[intval($date->format('n'))] . ' ' . $date->format('Y')
        );
        $spreadsheet->getActiveSheet()->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $spreadsheet->getActiveSheet()->getStyle('A1')->getFont()->setSize(22);
        $spreadsheet->getActiveSheet()->getStyle('A1')->getFont()->setBold(true);

        $spreadsheet->getActiveSheet()->setCellValue('A3', 'Преподаватель');
        $spreadsheet->getActiveSheet()->setCellValue('A4', 'Стоимость занятия');
        $spreadsheet->getActiveSheet()->setCellValue('A5', 'Стоимость со скидкой');

        $spreadsheet->getActiveSheet()->mergeCellsByColumnAndRow(2, 3, $lastColumn, 3);
        $spreadsheet->getActiveSheet()->mergeCellsByColumnAndRow(2, 4, $lastColumn, 4);
        $spreadsheet->getActiveSheet()->mergeCellsByColumnAndRow(2, 5, $lastColumn, 5);
        $spreadsheet->getActiveSheet()->setCellValue('B3', $groupParam->teacher->name);
        $spreadsheet->getActiveSheet()->setCellValueExplicit('B4', $groupParam->lesson_price, DataType::TYPE_NUMERIC);
        $spreadsheet->getActiveSheet()->setCellValueExplicit('B5', $groupParam->lesson_price_discount, DataType::TYPE_NUMERIC);
        $spreadsheet->getActiveSheet()->getStyle('B3:B5')->getFont()->setBold(true);

        $nextMonth = clone $date;
        $nextMonth->modify('+1 month');
        /** @var Event[] $events */
        $events = Event::find()
            ->andWhere(['group_id' => $group->id])
            ->andWhere(['BETWEEN', 'event_date', $date->format('Y-m-d H:i:s'), $nextMonth->format('Y-m-d H:i:s')])
            ->with('members.payments')
            ->all();

        if (!$events) {
            $spreadsheet->getActiveSheet()->mergeCellsByColumnAndRow(1, 7, $lastColumn, 7);
            $spreadsheet->getActiveSheet()->getStyle('A7')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $spreadsheet->getActiveSheet()->setCellValue('A7', 'В группе не было занятий');
        } else {
            $groupPupilMap = [];
            $userMap = [];
            $userChargeMap = [];
            /** @var GroupPupil[] $groupPupils */
            $groupPupils = GroupPupil::find()
                ->andWhere(['group_id' => $group->id])
                ->andWhere(['<', 'date_start', $nextMonth->format('Y-m-d')])
                ->andWhere(['or', 'date_end IS NULL', ['>=', 'date_end', $date->format('Y-m-d')]])
                ->joinWith('user')
                ->orderBy([User::tableName() . '.name' => SORT_ASC])
                ->all();
            $row = 8;
            foreach ($groupPupils as $groupPupil) {
                if (!array_key_exists($groupPupil->user_id, $userMap)) {
                    $spreadsheet->getActiveSheet()->setCellValue("A$row", $groupPupil->user->name);
                    $userMap[$groupPupil->user_id] = $row;
                    $groupPupilMap[$groupPupil->id] = $row;
                    $row++;
                } else {
                    $groupPupilMap[$groupPupil->id] = $userMap[$groupPupil->user_id];
                }
                $userChargeMap[$groupPupil->user_id] = 0;
            }

            for ($i = 1; $i <= $daysCount; $i++) {
                $spreadsheet->getActiveSheet()
                    ->setCellValueExplicitByColumnAndRow($i + 1,7, $i, DataType::TYPE_NUMERIC);
            }
            $spreadsheet->getActiveSheet()->setCellValueByColumnAndRow($lastColumn, 7, 'Итого');
            $spreadsheet->getActiveSheet()->getStyleByColumnAndRow(2, 7, $lastColumn, 7)
                ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $spreadsheet->getActiveSheet()->getStyleByColumnAndRow(2, 7, $lastColumn, 7)
                ->getFont()->setBold(true);

            $spreadsheet->getActiveSheet()->setCellValue("A$row", 'Зарплата преподавателю');
            $redColor = 'f2dede';
            foreach ($events as $event) {
                $column = intval($event->eventDateTime->format('j')) + 1;

                if ($event->status == Event::STATUS_CANCELED) {
                    $spreadsheet->getActiveSheet()->getStyleByColumnAndRow($column, 7, $column, $row)
                        ->getFill()->setFillType(Fill::FILL_SOLID)
                        ->getStartColor()->setRGB($redColor);
                } else {
                    $totalSum = 0;
                    foreach ($event->members as $member) {
                        if ($member->payments) {
                            $paymentSum = 0;
                            foreach ($member->payments as $payment) {
                                if ($payment->used_payment_id) {
                                    $paymentSum += $payment->amount;
                                    $userChargeMap[$payment->user_id] += $payment->amount;
                                    $totalSum += $payment->amount;
                                }
                            }
                            $spreadsheet->getActiveSheet()
                                ->setCellValueExplicitByColumnAndRow($column, $groupPupilMap[$member->group_pupil_id], $paymentSum * -1, DataType::TYPE_NUMERIC);
                        }

                        if ($member->status == EventMember::STATUS_MISS) {
                            $spreadsheet->getActiveSheet()->getStyleByColumnAndRow($column, $groupPupilMap[$member->group_pupil_id])
                                ->getFill()->setFillType(Fill::FILL_SOLID)
                                ->getStartColor()->setRGB($redColor);
                        }
                    }

                    $spreadsheet->getActiveSheet()
                        ->setCellValueExplicitByColumnAndRow($column, $row, round($totalSum * (-1) / 100 * $groupParam->teacher_rate), DataType::TYPE_NUMERIC);
                }
            }

            foreach ($userChargeMap as $userId => $chargeAmount) {
                $spreadsheet->getActiveSheet()
                    ->setCellValueExplicitByColumnAndRow($lastColumn, $userMap[$userId], $chargeAmount * (-1), DataType::TYPE_NUMERIC);
            }
            $spreadsheet->getActiveSheet()
                ->setCellValueExplicitByColumnAndRow($lastColumn, $row, $groupParam->teacher_salary, DataType::TYPE_NUMERIC);
            $spreadsheet->getActiveSheet()->getStyleByColumnAndRow(1, $row, $lastColumn, $row)->getFont()->setBold(true);
            $spreadsheet->getActiveSheet()->getStyleByColumnAndRow(1, 7, $lastColumn, $row)->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['argb' => 'FF000000'],
                    ],
                ],
            ]);
        }

        for ($i = 1; $i < $daysCount + 2; $i++) {
            $spreadsheet->getActiveSheet()->getColumnDimensionByColumn($i)->setAutoSize(true);
        }

        return $spreadsheet;
    }

    public static function getMonthSalarySpreadsheet(DateTime $date): Spreadsheet
    {
        /** @var GroupParam[] $groupParams */
        $groupParams = GroupParam::find()
            ->andWhere(['year' => $date->format('Y'), 'month' => $date->format('n')])
            ->andWhere(['>', 'teacher_salary', 0])
            ->with(['teacher', 'group'])
            ->orderBy([GroupParam::tableName() . '.teacher_id' => SORT_ASC])->all();

        if (empty($groupParams)) throw new Exception('No salary data found');

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

        $teacherSet = [];
        $num = 0;
        $row = 3;
        $startRow = null;
        $totalByTeacher = 0;
        foreach ($groupParams as $groupParam) {
            if (!array_key_exists($groupParam->teacher_id, $teacherSet)) {
                $num++;
                if ($startRow !== null) {
                    $spreadsheet->getActiveSheet()->mergeCells("A$startRow:A" . ($row - 1));
                    $spreadsheet->getActiveSheet()->mergeCells("B$startRow:B" . ($row - 1));
                    $spreadsheet->getActiveSheet()->mergeCells("E$startRow:E" . ($row - 1));
                    $spreadsheet->getActiveSheet()->mergeCells("F$startRow:F" . ($row - 1));
                    $spreadsheet->getActiveSheet()->mergeCells("G$startRow:g" . ($row - 1));
                    $spreadsheet->getActiveSheet()->mergeCells("H$startRow:H" . ($row - 1));
                    $spreadsheet->getActiveSheet()->mergeCells("I$startRow:I" . ($row - 1));

                    $spreadsheet->getActiveSheet()->setCellValueExplicit("E$startRow", $totalByTeacher, DataType::TYPE_NUMERIC);
                    $spreadsheet->getActiveSheet()->setCellValue("F$startRow", "=E$startRow*0.12");
                    $spreadsheet->getActiveSheet()->setCellValue("I$startRow", "=E$startRow-F$startRow-G$startRow");
                }

                $spreadsheet->getActiveSheet()->setCellValue("A$row", $num);
                $spreadsheet->getActiveSheet()->setCellValue("B$row", $groupParam->teacher->name);
                $totalByTeacher = 0;
                $startRow = $row;
                $teacherSet[$groupParam->teacher_id] = true;
            }

            $spreadsheet->getActiveSheet()->setCellValue("C$row", $groupParam->group->name);
            $spreadsheet->getActiveSheet()->setCellValueExplicit("D$row", $groupParam->teacher_salary, DataType::TYPE_NUMERIC);
            $totalByTeacher += $groupParam->teacher_salary;
            $row++;
        }

        $row--;
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
}

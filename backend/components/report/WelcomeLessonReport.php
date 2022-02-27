<?php

namespace backend\components\report;

use backend\models\WelcomeLesson;
use common\components\GroupComponent;
use common\models\GroupParam;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class WelcomeLessonReport
{
    public static function create(\DateTimeInterface $startDate, \DateTimeInterface $endDate): Spreadsheet
    {
        $startDateString = $startDate->format('Y-m-d');
        $endDateString = $endDate->format('Y-m-d');

        $lessonsAll = WelcomeLesson::find()
            ->andWhere(['BETWEEN', 'lesson_date', $startDateString, $endDateString])
            ->andWhere(['NOT', ['status' => WelcomeLesson::STATUS_RESCHEDULED]])
            ->count('id');

        $lessonsPassed = WelcomeLesson::find()
            ->andWhere(['BETWEEN', 'lesson_date', $startDateString, $endDateString])
            ->andWhere(['status' => [WelcomeLesson::STATUS_PASSED, WelcomeLesson::STATUS_SUCCESS, WelcomeLesson::STATUS_DENIED]])
            ->count('id');

        $lessonsPending = WelcomeLesson::find()
            ->andWhere(['BETWEEN', 'lesson_date', $startDateString, $endDateString])
            ->andWhere(['status' => WelcomeLesson::STATUS_PASSED])
            ->count('id');

        $lessonsSuccess = WelcomeLesson::find()
            ->andWhere(['BETWEEN', 'lesson_date', $startDateString, $endDateString])
            ->andWhere(['status' => WelcomeLesson::STATUS_SUCCESS])
            ->count('id');

        $lessonsDenied = WelcomeLesson::find()
            ->andWhere(['BETWEEN', 'lesson_date', $startDateString, $endDateString])
            ->andWhere(['status' => WelcomeLesson::STATUS_DENIED])
            ->count('id');

        $spreadsheet = new Spreadsheet();
        $spreadsheet->getActiveSheet()->getPageSetup()->setOrientation(PageSetup::ORIENTATION_PORTRAIT);
        $spreadsheet->getActiveSheet()->getPageSetup()->setPaperSize(PageSetup::PAPERSIZE_A4);
        $spreadsheet->getActiveSheet()->getPageSetup()->setFitToWidth(1);
        $spreadsheet->getActiveSheet()->getPageSetup()->setFitToHeight(0);
        $spreadsheet->getActiveSheet()->setTitle('Сводка');

        $spreadsheet->getActiveSheet()
            ->setCellValue('A1', "Записалось на пробные уроки: $lessonsAll");
        $spreadsheet->getActiveSheet()
            ->setCellValue('A2', "Пришли на пробные уроки: $lessonsPassed");
        $spreadsheet->getActiveSheet()
            ->setCellValue('A3', "Остались в группах: $lessonsSuccess");
        $spreadsheet->getActiveSheet()
            ->setCellValue('A4', "Отказались ходить: $lessonsDenied");
        $spreadsheet->getActiveSheet()
            ->setCellValue('A5', "Статус так и остался \"проведено\": $lessonsPending");

        /* ----------------------------------------------------------------- */

        /** @var WelcomeLesson[] $lessons */
        $lessons = WelcomeLesson::find()
            ->andWhere(['BETWEEN', 'lesson_date', $startDateString, $endDateString])
            ->orderBy(['lesson_date' => SORT_ASC])
            ->with('user')
            ->all();

        $spreadsheet->addSheet(new Worksheet(null, 'Список уроков'));
        $spreadsheet->setActiveSheetIndex(1);
        $spreadsheet->getActiveSheet()->getPageSetup()->setOrientation(PageSetup::ORIENTATION_LANDSCAPE);
        $spreadsheet->getActiveSheet()->getPageSetup()->setPaperSize(PageSetup::PAPERSIZE_A4);
        $spreadsheet->getActiveSheet()->getPageSetup()->setFitToWidth(1);
        $spreadsheet->getActiveSheet()->getPageSetup()->setFitToHeight(0);

        $spreadsheet->getActiveSheet()->setCellValue('A1', 'Группа');
        $spreadsheet->getActiveSheet()->setCellValue('B1', 'Дата');
        $spreadsheet->getActiveSheet()->setCellValue('C1', 'Учитель');
        $spreadsheet->getActiveSheet()->setCellValue('D1', 'Студент');
        $spreadsheet->getActiveSheet()->setCellValue('E1', 'Менеджер');
        $spreadsheet->getActiveSheet()->setCellValue('F1', 'Статус');
        $spreadsheet->getActiveSheet()->setCellValue('G1', 'Причина отказа');
        $spreadsheet->getActiveSheet()->setCellValue('H1', 'Коментарий');

        $row = 2;
        /** @var GroupParam[] $groupParamMap */
        $groupParamMap = [];
        foreach ($lessons as $lesson) {
            if (!isset($groupParamMap[$lesson->group_id])) {
                $groupParamMap[$lesson->group_id] = GroupComponent::getGroupParam($lesson->group, $lesson->lessonDateTime);
            }
            $spreadsheet->getActiveSheet()->setCellValue("A$row", $lesson->group->name);
            $spreadsheet->getActiveSheet()->setCellValue("B$row", Date::PHPToExcel($lesson->lessonDateTime));
            $spreadsheet->getActiveSheet()->setCellValue("C$row", $groupParamMap[$lesson->group_id]->teacher->name);
            $spreadsheet->getActiveSheet()->setCellValue(
                "D$row",
                $lesson->user->name . "\n" . $lesson->user->phoneInternational
                . ($lesson->user->phone2 ? "\n" . $lesson->user->phone2International : '')
            );
            $spreadsheet->getActiveSheet()->setCellValue("E$row", $lesson->createdAdmin->name);
            $spreadsheet->getActiveSheet()->setCellValue("F$row", WelcomeLesson::STATUS_LABELS[$lesson->status]);
            $spreadsheet->getActiveSheet()->setCellValue(
                "G$row",
                WelcomeLesson::STATUS_DENIED === $lesson->status && $lesson->deny_reason
                    ? WelcomeLesson::DENY_REASON_LABELS[$lesson->deny_reason]
                    : ''
            );
            $spreadsheet->getActiveSheet()->setCellValue("H$row", $lesson->comment);

            $row++;
        }
        $spreadsheet->getActiveSheet()->getStyle("B2:B$row")->getNumberFormat()->setFormatCode('dd mmmm yy');
        $spreadsheet->getActiveSheet()->getStyle("D2:D$row")->getAlignment()->setWrapText(true);
        for ($i = 1; $i <= 8; $i++) {
            $spreadsheet->getActiveSheet()->getStyleByColumnAndRow($i, 2, $i, $row)->getAlignment()->setVertical(Alignment::VERTICAL_TOP);
            $spreadsheet->getActiveSheet()->getColumnDimensionByColumn($i)->setAutoSize(true);
        }

        return $spreadsheet;
    }
}

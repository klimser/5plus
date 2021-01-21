<?php

namespace backend\components\report;

use backend\models\WelcomeLesson;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;

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


        return $spreadsheet;
    }
}

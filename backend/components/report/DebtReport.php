<?php

namespace backend\components\report;

use common\models\Course;
use common\models\CourseStudent;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;

class DebtReport
{
    private Spreadsheet $report;

    public function __construct()
    {
        $this->report = new Spreadsheet();
        $this->report->getActiveSheet()->getPageSetup()->setOrientation(PageSetup::ORIENTATION_PORTRAIT);
        $this->report->getActiveSheet()->getPageSetup()->setPaperSize(PageSetup::PAPERSIZE_A4);
        $this->report->getActiveSheet()->getPageSetup()->setFitToWidth(1);
        $this->report->getActiveSheet()->getPageSetup()->setFitToHeight(0);

        $this->report->getActiveSheet()->mergeCells('A1:E1');
        $this->report->getActiveSheet()->setCellValue('A1', "Задолженности");
        $this->report->getActiveSheet()->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $this->report->getActiveSheet()->getStyle('A1')->getFont()->setBold(true)->setSize(16);

        $this->report->getActiveSheet()->getColumnDimension('A')->setWidth(32);
        $this->report->getActiveSheet()->getColumnDimension('B')->setWidth(40);
        $this->report->getActiveSheet()->getColumnDimension('C')->setWidth(5);
        $this->report->getActiveSheet()->getColumnDimension('D')->setWidth(15);
        $this->report->getActiveSheet()->getColumnDimension('E')->setWidth(50);

        $row = 3;
        /** @var Course[] $courses */
        $courses = Course::find()
            ->joinWith('courseStudents')
            ->andWhere(['<', CourseStudent::tableName() . '.paid_lessons', 0])
            ->addOrderBy([Course::tableName() . '.name' => SORT_ASC])
            ->all();
        foreach ($courses as $course) {
            $this->report->getActiveSheet()->mergeCells("A$row:D$row");
            $this->report->getActiveSheet()->setCellValue("A$row", $course->courseConfig->name);
            $this->report->getActiveSheet()->getStyle("A$row")->getFont()->setItalic(true)->setSize(14);
            $row++;

            foreach ($course->activeCourseStudents as $courseStudent) {
                if ($courseStudent->paid_lessons < 0) {
                    $this->report->getActiveSheet()->setCellValue("A$row", $courseStudent->user->name);
                    $this->report->getActiveSheet()->setCellValue(
                        "B$row",
                        $courseStudent->user->phoneFull . ($courseStudent->user->phone2 ? ', ' . $courseStudent->user->phone2Full : '')
                    );
                    $this->report->getActiveSheet()->setCellValue("C$row", $courseStudent->paid_lessons * (-1));
                    $this->report->getActiveSheet()->setCellValue("D$row", $courseStudent->chargeDateObject->format('d.m.Y'));
                    $this->report->getActiveSheet()->setCellValue("E$row", $courseStudent->user->note);

                    $row++;
                }
            }
            $row++;
        }

        $this->report->getActiveSheet()->getStyle("C3:C$row")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    }

    public function getReport(): Spreadsheet
    {
        return $this->report;
    }
}

<?php

namespace backend\components\report;

use common\models\Course;
use common\models\CourseStudent;
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
        $data = Course::find()->alias('c')
            ->andWhere(['c.active' => Course::STATUS_ACTIVE])
            ->leftJoin(['cs1' => CourseStudent::tableName()], 'cs1.course_id = c.id')
            ->leftJoin(
                ['cs2' => CourseStudent::tableName()],
                'cs2.course_id = cs1.course_id AND cs2.user_id = cs1.user_id AND cs2.id != cs1.id '
                . 'AND cs2.active = ' . CourseStudent::STATUS_ACTIVE
            )
            ->andWhere([
                'cs1.active' => CourseStudent::STATUS_INACTIVE,
                'cs2.id' => null,
            ])
            ->orderBy(['cs1.date_end' => SORT_DESC])
            ->select(['course_id' => 'c.id', 'course_student_id' => 'cs1.id'])
            ->asArray()
            ->all();
        $courseStudentIds = $courseStudentMap = $courseStudentIdMap = $courseMap = [];
        foreach ($data as $record) {
            $courseStudentIds[] = $record['course_student_id'];
            $courseStudentIdMap[$record['course_id']][] = $record['course_student_id'];
        }
        /** @var Course[] $courses */
        $courses = Course::find()->andWhere(['id' => array_keys($courseStudentIdMap)])->all();
        foreach ($courses as $course) $courseMap[$course->id] = $course;
        /** @var CourseStudent[] $courseStudents */
        $courseStudents = CourseStudent::find()->andWhere(['id' => $courseStudentIds])->all();
        foreach ($courseStudents as $courseStudent) $courseStudentMap[$courseStudent->id] = $courseStudent;
        foreach ($courseStudentIdMap as $courseId => $courseStudentIds) {
            $titleRendered = false;
            foreach ($courseStudentIds as $courseStudentId) {
                $courseStudent = $courseStudentMap[$courseStudentId];
                if ($courseStudent->moneyLeft > 0) {
                    if (!$titleRendered) {
                        $spreadsheet->getActiveSheet()->mergeCells("A$row:C$row");
                        $spreadsheet->getActiveSheet()->setCellValue("A$row", $courseMap[$courseId]->getCourseConfigByDate($courseStudent->endDateObject)->name);
                        $spreadsheet->getActiveSheet()->getStyle("A$row")->getFont()->setItalic(true)->setSize(14);
                        $row++;
                        $titleRendered = true;
                    }
                    $spreadsheet->getActiveSheet()->setCellValue("A$row", $courseStudent->user->name);
                    $spreadsheet->getActiveSheet()->setCellValue(
                        "B$row",
                        $courseStudent->endDateObject->format('d.m.Y')
                    );
                    $spreadsheet->getActiveSheet()->setCellValueExplicit(
                        "C$row",
                        $courseStudent->moneyLeft,
                        DataType::TYPE_NUMERIC
                    );

                    $row++;
                }
            }
            $row++;
        }

        $row++;
        $spreadsheet->getActiveSheet()->setCellValue("A$row", 'Итого');
        $spreadsheet->getActiveSheet()->setCellValue("C$row", '=SUM(C5:C' . ($row - 1) . ')');
        $spreadsheet->getActiveSheet()->getStyle("A$row:C$row")->getFont()->setBold(true);

        $spreadsheet->getActiveSheet()->getStyle("C5:C$row")->getNumberFormat()->setFormatCode('#,##0');

        return $spreadsheet;
    }
}
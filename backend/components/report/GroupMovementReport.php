<?php

namespace backend\components\report;

use common\components\CourseComponent;
use common\models\Course;
use common\models\CourseStudent;
use DateTime;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use yii\db\Expression;
use yii\helpers\ArrayHelper;

class GroupMovementReport
{
    public static function create(DateTime $startDate, DateTime $endDate): Spreadsheet
    {
        $startDateString = $startDate->format('Y-m-d');
        $endDateString = $endDate->format('Y-m-d');

        /** @var Course[] $courses */
        $courses = Course::find()
            ->andWhere([
                'AND',
                ['<=', 'date_start', $endDateString],
                ['OR', ['>=', 'date_end', $startDateString], ['date_end' => null]]
            ])
            ->orderBy(['name' => SORT_ASC])
            ->all();

        $courseStudentCount = function($condition): array {
            return ArrayHelper::map(
                CourseStudent::find()
                    ->andWhere($condition)
                    ->select(['course_id', 'COUNT(DISTINCT user_id) as cnt'])
                    ->groupBy(['course_id'])
                    ->asArray()->all(),
                'course_id',
                'cnt'
            );
        };
        $inStudentCount = $courseStudentCount(['BETWEEN', 'date_start', $startDateString, $endDateString]);
        $outStudentCount = $courseStudentCount(['BETWEEN', 'date_end', $startDateString, $endDateString]);
        $totalStudentCount = $courseStudentCount([
            'AND',
            ['<=', 'date_start', $endDateString],
            ['OR', ['>=', 'date_end', $startDateString], ['date_end' => null]]
        ]);
        $startStudentCount = $courseStudentCount([
            'AND',
            ['<', 'date_start', $startDateString],
            ['OR', ['>=', 'date_end', $startDateString], ['date_end' => null]]
        ]);
        $endStudentCount = $courseStudentCount([
            'AND',
            ['<=', 'date_start', $endDateString],
            ['OR', ['>', 'date_end', $endDateString], ['date_end' => null]]
        ]);

        $spreadsheet = new Spreadsheet();
        $spreadsheet->getActiveSheet()->getPageSetup()->setOrientation(PageSetup::ORIENTATION_PORTRAIT);
        $spreadsheet->getActiveSheet()->getPageSetup()->setPaperSize(PageSetup::PAPERSIZE_A4);
        $spreadsheet->getActiveSheet()->getPageSetup()->setFitToWidth(1);
        $spreadsheet->getActiveSheet()->getPageSetup()->setFitToHeight(0);

        $spreadsheet->getActiveSheet()->mergeCells('A1:G1');
        $spreadsheet->getActiveSheet()->setCellValue('A1', "Отчёт по студентам {$startDate->format('m Y')}");
        $spreadsheet->getActiveSheet()->mergeCells('I1:O1');
        $spreadsheet->getActiveSheet()->setCellValue('I1', "KIDS");
        $spreadsheet->getActiveSheet()->getStyle('A1:I1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $spreadsheet->getActiveSheet()->getStyle('A1:I1')->getFont()->setBold(true)->setSize(16);

        for ($i = 0; $i < 2; $i++) {
            $offset = $i * 9;
            $spreadsheet->getActiveSheet()
                ->setCellValueByColumnAndRow($offset + 1, 2, "№")
                ->setCellValueByColumnAndRow($offset + 2, 2, "группа")
                ->setCellValueByColumnAndRow($offset + 3, 2, "учитель")
                ->setCellValueByColumnAndRow($offset + 4, 2, "в начале месяца")
                ->setCellValueByColumnAndRow($offset + 5, 2, "прибыло")
                ->setCellValueByColumnAndRow($offset + 6, 2, "убыло")
                ->setCellValueByColumnAndRow($offset + 7, 2, "всего занималось")
                ->setCellValueByColumnAndRow($offset + 8, 2, "в конце месяца");
        }

        $nums = [0 => 1, 1 => 1];
        $rows = [0 => 3, 1 => 3];
        $courseCollections = [];
        foreach ($courses as $course) {
            $courseConfig = CourseComponent::getCourseConfig($course, $startDate);

            $index = $course->kids;
            if (!array_key_exists($index, $courseCollections)) $courseCollections[$index] = [];
            $courseCollections[$index][] = $course->id;
            $offset = $index * 9;
            $spreadsheet->getActiveSheet()
                ->setCellValueByColumnAndRow($offset + 1, $rows[$index], $nums[$index])
                ->setCellValueByColumnAndRow($offset + 2, $rows[$index], $courseConfig->name)
                ->setCellValueByColumnAndRow($offset + 3, $rows[$index], $courseConfig->teacher->name)
                ->setCellValueByColumnAndRow($offset + 4, $rows[$index], array_key_exists($course->id, $startStudentCount) ? $startStudentCount[$course->id] : 0)
                ->setCellValueByColumnAndRow($offset + 5, $rows[$index], array_key_exists($course->id, $inStudentCount) ? $inStudentCount[$course->id] : 0)
                ->setCellValueByColumnAndRow($offset + 6, $rows[$index], array_key_exists($course->id, $outStudentCount) ? $outStudentCount[$course->id] : 0)
                ->setCellValueByColumnAndRow($offset + 7, $rows[$index], array_key_exists($course->id, $totalStudentCount) ? $totalStudentCount[$course->id] : 0)
                ->setCellValueByColumnAndRow($offset + 8, $rows[$index], array_key_exists($course->id, $endStudentCount) ? $endStudentCount[$course->id] : 0);
            $nums[$index]++;
            $rows[$index]++;
        }

        foreach ($courseCollections as $index => $courseIds) {
            $offset = $index * 9;
            $row = $rows[$index] - 1;

            $spreadsheet->getActiveSheet()->getStyleByColumnAndRow($offset + 1, 2, $offset + 8, $row)->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['argb' => 'FF000000'],
                    ],
                ],
            ]);
            $row += 2;

            $inUsers = CourseStudent::find()
                ->andWhere(['BETWEEN', 'date_start', $startDateString, $endDateString])
                ->andWhere(['course_id' => $courseIds])
                ->select('user_id')
                ->distinct()
                ->column();
            $excludeUsersCount = CourseStudent::find()
                ->andWhere(['<', 'date_start', $startDateString])
                ->andWhere(['course_id' => $courseIds])
                ->andWhere(['user_id' => $inUsers])
                ->count('DISTINCT user_id');
            $totalIn = count($inUsers) - $excludeUsersCount;
            
            $totalNewCourseStudent = CourseStudent::find()
                ->alias('cs1')
                ->leftJoin(['cs2' => CourseStudent::tableName()], "cs1.id != cs2.id AND cs2.user_id = cs1.user_id AND cs2.course_id = cs1.course_id AND cs2.date_start < '$startDateString'")
                ->andWhere(['BETWEEN', 'cs1.date_start', $startDateString, $endDateString])
                ->andWhere(['cs1.course_id' => $courseIds])
                ->andWhere(['cs2.id' => null])
                ->count('DISTINCT cs1.id');

            $outUsers = CourseStudent::find()
                ->andWhere(['BETWEEN', 'date_end', $startDateString, $endDateString])
                ->andWhere(['course_id' => $courseIds])
                ->select('user_id')
                ->distinct()
                ->column();
            $excludeUsersCount = CourseStudent::find()
                ->andWhere(['or', ['date_end' => null], ['>', 'date_end', $endDateString]])
                ->andWhere(['course_id' => $courseIds])
                ->andWhere(['user_id' => $outUsers])
                ->count('DISTINCT user_id');
            $totalOut = count($outUsers) - $excludeUsersCount;

            $startStudents = CourseStudent::find()
                ->andWhere(['<', 'date_start', $startDateString])
                ->andWhere(['or', ['date_end' => null], ['>=', 'date_end', $startDateString]])
                ->andWhere(['course_id' => $courseIds])
                ->select(new  Expression('COUNT(DISTINCT CONCAT(user_id, "|", course_id))'))
                ->scalar();
            $startUsers = CourseStudent::find()
                ->andWhere(['<', 'date_start', $startDateString])
                ->andWhere(['course_id' => $courseIds])
                ->andWhere(['or', ['date_end' => null], ['>=', 'date_end', $startDateString]])
                ->select('COUNT(DISTINCT user_id)')
                ->scalar();
            $totalStudents = CourseStudent::find()
                ->andWhere(['<=', 'date_start', $endDateString])
                ->andWhere(['course_id' => $courseIds])
                ->andWhere(['or', ['date_end' => null], ['>=', 'date_end', $startDateString]])
                ->select(new  Expression('COUNT(DISTINCT CONCAT(user_id, "|", course_id))'))
                ->scalar();
            $totalUsers = CourseStudent::find()
                ->andWhere(['<=', 'date_start', $endDateString])
                ->andWhere(['course_id' => $courseIds])
                ->andWhere(['or', ['date_end' => null], ['>=', 'date_end', $startDateString]])
                ->select('COUNT(DISTINCT user_id)')
                ->scalar();
            $finalStudents = CourseStudent::find()
                ->andWhere(['<=', 'date_start', $endDateString])
                ->andWhere(['course_id' => $courseIds])
                ->andWhere(['or', ['date_end' => null], ['>', 'date_end', $endDateString]])
                ->select(new  Expression('COUNT(DISTINCT CONCAT(user_id, "|", course_id))'))
                ->scalar();
            $finalUsers = CourseStudent::find()
                ->andWhere(['<=', 'date_start', $endDateString])
                ->andWhere(['course_id' => $courseIds])
                ->andWhere(['or', ['date_end' => null], ['>', 'date_end', $endDateString]])
                ->select('COUNT(DISTINCT user_id)')
                ->scalar();

            $spreadsheet->getActiveSheet()->getStyleByColumnAndRow($offset + 1, $row, $offset + 7, $row + 4)
                ->getFont()->setBold(true);

            $spreadsheet->getActiveSheet()
                ->mergeCellsByColumnAndRow($offset + 1, $row, $offset + 7, $row)
                ->setCellValueByColumnAndRow($offset + 1, $row, "Итого новых студентов: $totalIn");
            $row++;
            
            $spreadsheet->getActiveSheet()
                ->mergeCellsByColumnAndRow($offset + 1, $row, $offset + 7, $row)
                ->setCellValueByColumnAndRow($offset + 1, $row, "Начали заниматься в группах (для бонуса): $totalNewCourseStudent");
            $row++;
            
            $spreadsheet->getActiveSheet()
                ->mergeCellsByColumnAndRow($offset + 1, $row, $offset + 7, $row)
                ->setCellValueByColumnAndRow($offset + 1, $row, "Итого ушли из учебного центра: $totalOut");
            $row++;

            $spreadsheet->getActiveSheet()
                ->mergeCellsByColumnAndRow($offset + 1, $row, $offset + 7, $row)
                ->setCellValueByColumnAndRow($offset + 1, $row, "В начале месяца было $startUsers человек - $startStudents студентов в гуппах");
            $row++;
            $spreadsheet->getActiveSheet()
                ->mergeCellsByColumnAndRow($offset + 1, $row, $offset + 7, $row)
                ->setCellValueByColumnAndRow($offset + 1, $row, "В этом месяце занималось $totalUsers человек - $totalStudents студентов в гуппах");
            $row++;
            $spreadsheet->getActiveSheet()
                ->mergeCellsByColumnAndRow($offset + 1, $row, $offset + 7, $row)
                ->setCellValueByColumnAndRow($offset + 1, $row, "В конце месяца было $finalUsers человек - $finalStudents студентов в гуппах");
        }

        return $spreadsheet;
    }
}

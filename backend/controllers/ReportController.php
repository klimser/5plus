<?php

namespace backend\controllers;

use backend\components\report\CashReport;
use backend\components\report\DebtReport;
use backend\components\report\GroupMovementReport;
use backend\components\report\ManagerSalaryReport;
use backend\components\report\MoneyReport;
use backend\components\report\RestMoneyReport;
use backend\components\report\TeacherTimeReport;
use backend\components\report\WelcomeLessonReport;
use backend\models\Event;
use backend\models\TeacherSubjectLink;
use common\components\CourseComponent;
use common\models\Course;
use common\models\CourseConfig;
use common\models\Subject;
use common\models\Teacher;
use DateTimeImmutable;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use Yii;
use yii\web\NotFoundHttpException;

/**
 * ReportController implements vary reports.
 */
class ReportController extends AdminController
{
    public function actionGroupMovement()
    {
        $this->checkAccess('reportGroupMovement');

        if (Yii::$app->request->isPost) {
            [$month, $year] = explode('.', Yii::$app->request->post('date', ''));
            if ($month && $year) {
                $startDate = new \DateTime("$year-$month-01");
                $endDate = clone $startDate;
                $endDate->modify('last day of this month');

                ob_start();
                $objWriter = IOFactory::createWriter(GroupMovementReport::create($startDate, $endDate), 'Xlsx');
                $objWriter->save('php://output');
                return Yii::$app->response->sendContentAsFile(
                    ob_get_clean(),
                    "report-$year-$month.xlsx",
                    ['mimeType' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
                );
            }
        }

        return $this->render('group-movement');
    }

    /**
     * Get all money debts.
     * @return mixed
     * @throws \Exception
     */
    public function actionDebt()
    {
        $this->checkAccess('reportDebt');

        ob_start();
        $objWriter = IOFactory::createWriter((new DebtReport)->getReport(), 'Xlsx');
        $objWriter->save('php://output');
        return Yii::$app->response->sendContentAsFile(
            ob_get_clean(),
            'report-debt-' . date('Y-m-d') . '.xlsx',
            ['mimeType' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
        );
    }

    public function actionMoney()
    {
        $this->checkAccess('reportMoney');

        $courseMap = [null => 'Все'];
        foreach (CourseComponent::getAllSortedByActiveAndName() as $course) {
            $courseMap[$course->id] = $course->courseConfig->name;
        }

        if (Yii::$app->request->isPost) {
            [$month, $year] = explode('.', Yii::$app->request->post('date', ''));
            if ($month && $year) {
                $startDate = new DateTimeImmutable("$year-$month-01 midnight");
                $endDate = $startDate->modify('first day of next month midnight');

                $courseId = Yii::$app->request->post('course');
                if (empty($courseId)) {
                    $this->checkAccess('reportMoneyTotal');

                    $spreadsheet = MoneyReport::createForAllCourses($startDate, $endDate);
                } else {
                    $course = Course::findOne($courseId);
                    if (!$course) throw new NotFoundHttpException('Invalid course!');

                    if ($course->startDateObject > $startDate || ($course->date_end && $course->endDateObject < $endDate)) {
                        Yii::$app->session->addFlash('error', 'Группа не занималась в этом месяце');

                        return $this->render('money', [
                            'courseMap' => $courseMap,
                            'allowedTotal' => Yii::$app->user->can('reportMoneyTotal')
                        ]);
                    }

                    $spreadsheet = MoneyReport::createForOneCourse($course, $startDate, $endDate);
                }

                ob_start();
                $objWriter = IOFactory::createWriter($spreadsheet, 'Xlsx');
                $objWriter->save('php://output');
                return Yii::$app->response->sendContentAsFile(
                    ob_get_clean(),
                    "money-$year-$month.xlsx",
                    ['mimeType' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
                );
            }
        }

        return $this->render('money', [
            'courseMap' => $courseMap,
            'allowedTotal' => Yii::$app->user->can('reportMoneyTotal')
        ]);
    }

    public function actionCash()
    {
        $this->checkAccess('reportCash');

        if (Yii::$app->request->isPost) {
            $date = new DateTimeImmutable(Yii::$app->request->post('date', 'now'));
            if (!$date) throw new NotFoundHttpException('Wrong date');

            ob_start();
            $objWriter = IOFactory::createWriter(
                CashReport::create($date, boolval(Yii::$app->request->post('kids', 0))),
                'Xlsx'
            );
            $objWriter->save('php://output');
            return Yii::$app->response->sendContentAsFile(
                ob_get_clean(),
                "cash-{$date->format('d.m.Y')}.xlsx",
                ['mimeType' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
            );
        }

        return $this->render('cash');
    }

    public function actionRestMoney()
    {
        $this->checkAccess('reportCash');

        ob_start();
        $objWriter = IOFactory::createWriter(
            RestMoneyReport::create(),
            'Xlsx'
        );
        $objWriter->save('php://output');
        return Yii::$app->response->sendContentAsFile(
            ob_get_clean(),
            'rest-money-' . date('d.m.Y') . '.xlsx',
            ['mimeType' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
        );
    }
    
    public function actionManagerSalary()
    {
        $this->checkAccess('reportMoney');

        if (Yii::$app->request->isPost) {
            $date = new DateTimeImmutable(Yii::$app->request->post('date', 'now'));
            if (!$date) throw new NotFoundHttpException('Wrong date');

            ob_start();
            $objWriter = IOFactory::createWriter(
                ManagerSalaryReport::create($date, Yii::$app->request->post('month') > 0),
                'Xlsx'
            );
            $objWriter->save('php://output');
            return Yii::$app->response->sendContentAsFile(
                ob_get_clean(),
                "managers-salary-{$date->format('d.m.Y')}.xlsx",
                ['mimeType' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
            );
        }

        return $this->render('manager-salary');
    }

    public function actionTeacherTime()
    {
        $this->checkAccess('manageTeachers');

        if (Yii::$app->request->isPost) {
            [$month, $year] = explode('.', Yii::$app->request->post('date', ''));
            $teacher = Teacher::findOne(Yii::$app->request->post('teacher_id'));
            $subject = Subject::findOne(Yii::$app->request->post('subject_id'));
            $allTeachers = Yii::$app->request->post('all');
            $allSubjects = Yii::$app->request->post('one-teacher');
            if ($month && $year) {
                $startDate = new DateTimeImmutable("$year-$month-01 midnight");
                $endDate = $startDate->modify('last day of this month')->modify('+1 day midnight');
                
                $generatePage = function(TeacherSubjectLink $subjectTeacher, ?PhpWord $doc = null) use ($startDate, $endDate): PhpWord {
                    $courseIds = Course::find()
                        ->andWhere(['<', 'date_start', $endDate->format('Y-m-d H:i:s')])
                        ->andWhere(['or', ['date_end' => null], ['>', 'date_end', $startDate->format('Y-m-d H:i:s')]])
                        ->andWhere(['subject_id' => $subjectTeacher->subject_id])
                        ->select('id')
                        ->asArray()
                        ->column();

                    $eventData = Event::find()
                        ->alias('e')
                        ->leftJoin(
                            CourseConfig::tableName() . ' cc',
                            'e.course_id = cc.course_id AND cc.date_from <= e.event_date AND (cc.date_to IS NULL OR cc.date_to > e.event_date)'
                        )
                        ->andWhere(['between', 'e.event_date', $startDate->format('Y-m-d H:i:s'), $endDate->format('Y-m-d H:i:s')])
                        ->andWhere(['cc.teacher_id' => $subjectTeacher->teacher_id])
                        ->andWhere(['e.status' => Event::STATUS_PASSED])
                        ->andWhere(['e.course_id' => $courseIds])
                        ->select(['e.course_id', 'SUM(cc.lesson_duration) as duration'])
                        ->groupBy('e.course_id')
                        ->asArray()
                        ->all();

                    $totalHours = 0;
                    foreach ($eventData as $data) {
                        $totalHours += floor($data['duration'] / 40);
                    }

                    return TeacherTimeReport::create($subjectTeacher, $startDate, $totalHours, $doc);
                };
                
                switch (true) {
                    case $allTeachers:
                        $data = CourseConfig::find()
                            ->alias('cc')
                            ->joinWith('course c', false)
                            ->andWhere(['<', 'cc.date_from', $endDate->format('Y-m-d')])
                            ->andWhere(['or', ['cc.date_to' => null], ['>', 'date_to', $startDate->format('Y-m-d')]])
                            ->select(['cc.teacher_id', 'c.subject_id'])
                            ->distinct()
                            ->orderBy(['cc.teacher_id' => SORT_ASC, 'c.subject_id' => SORT_ASC])
                            ->asArray()
                            ->all();
                        $doc = null;
                        foreach ($data as $row) {
                            $subjectTeacher = TeacherSubjectLink::findOne(['teacher_id' => $row['teacher_id'], 'subject_id' => $row['subject_id']]);
                            $doc = $generatePage($subjectTeacher, $doc);
                        }
                        break;
                    case $allSubjects:
                        if ($teacher) {
                            $subjectTeachers = TeacherSubjectLink::find()->andWhere(['teacher_id' => $teacher->id])->all();
                            $doc = null;
                            foreach ($subjectTeachers as $subjectTeacher) {
                                $doc = $generatePage($subjectTeacher, $doc);
                            }
                        }
                        break;
                    default:
                        if ($teacher && $subject
                            && $subjectTeacher = TeacherSubjectLink::findOne(['teacher_id' => $teacher->id, 'subject_id' => $subject->id])) {
                            $doc = $generatePage($subjectTeacher);
                        }
                }
                
                if (!empty($doc)) {
                    ob_start();
                    $objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($doc, 'Word2007');
                    $objWriter->save('php://output');

                    return Yii::$app->response->sendContentAsFile(
                        ob_get_clean(),
                        "teacher-$year-$month.docx",
                        ['mimeType' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document']
                    );
                }
            }
        }

        return $this->render('teacher-time', [
            'teachers' => Teacher::find()->orderBy(['name' => SORT_ASC])->all(),
            'subjects' => Subject::find()->orderBy(['name' => SORT_ASC])->all(),
        ]);
    }

    public function actionWelcomeLesson()
    {
        $this->checkAccess('reportGroupMovement');

        if (Yii::$app->request->isPost) {
            [$month, $year] = explode('.', Yii::$app->request->post('date', ''));
            if ($month && $year) {
                $startDate = new DateTimeImmutable("$year-$month-01");
                $endDate = $startDate->modify('last day of this month');

                ob_start();
                $objWriter = IOFactory::createWriter(WelcomeLessonReport::create($startDate, $endDate), 'Xlsx');
                $objWriter->save('php://output');
                return Yii::$app->response->sendContentAsFile(
                    ob_get_clean(),
                    "welcome-lessons-$year-$month.xlsx",
                    ['mimeType' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
                );
            }
        }

        return $this->render('welcome-lesson');
    }
}

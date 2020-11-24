<?php

namespace backend\controllers;

use backend\components\report\CashReport;
use backend\components\report\DebtReport;
use backend\components\report\GroupMovementReport;
use backend\components\report\ManagerSalaryReport;
use backend\components\report\MoneyReport;
use backend\components\report\RestMoneyReport;
use backend\components\report\TeacherTimeReport;
use backend\models\Event;
use backend\models\TeacherSubjectLink;
use common\models\Group;
use common\models\GroupParam;
use common\models\Subject;
use common\models\Teacher;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use Yii;
use yii\helpers\ArrayHelper;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;

/**
 * ReportController implements vary reports.
 */
class ReportController extends AdminController
{
    public function actionGroupMovement()
    {
        if (!Yii::$app->user->can('reportGroupMovement')) throw new ForbiddenHttpException('Access denied!');

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
        if (!Yii::$app->user->can('reportDebt')) throw new ForbiddenHttpException('Access denied!');

        ob_start();
        $objWriter = IOFactory::createWriter(DebtReport::create(), 'Xlsx');
        $objWriter->save('php://output');
        return Yii::$app->response->sendContentAsFile(
            ob_get_clean(),
            'report-debt-' . date('Y-m-d') . '.xlsx',
            ['mimeType' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
        );
    }

    public function actionMoney()
    {
        if (!Yii::$app->user->can('reportMoney')) throw new ForbiddenHttpException('Access denied!');

        if (Yii::$app->request->isPost) {
            [$month, $year] = explode('.', Yii::$app->request->post('date', ''));
            if ($month && $year) {
                $startDate = new \DateTime("$year-$month-01");
                $endDate = clone $startDate;
                $endDate->modify('last day of this month');

                $groupId = Yii::$app->request->post('group');
                if ($groupId == 'all') {
                    if (!Yii::$app->user->can('reportMoneyTotal')) throw new ForbiddenHttpException('Access denied!');

                    $spreadsheet = MoneyReport::createAll($startDate, $endDate);
                } else {
                    [$devNull, $groupId] = explode('_', $groupId);
                    $group = Group::findOne($groupId);
                    if (!$group) throw new NotFoundHttpException('Invalid group!');

                    $spreadsheet = MoneyReport::createGroup($group, $startDate, $endDate);
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
            'groups' => Group::find()->orderBy('name')->all(),
            'allowedTotal' => Yii::$app->user->can('reportMoneyTotal')
        ]);
    }

    public function actionCash()
    {
        if (!Yii::$app->user->can('reportCash')) throw new ForbiddenHttpException('Access denied!');

        if (Yii::$app->request->isPost) {
            $date = new \DateTimeImmutable(Yii::$app->request->post('date', 'now'));
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
        if (!Yii::$app->user->can('reportCash')) throw new ForbiddenHttpException('Access denied!');

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
        if (!Yii::$app->user->can('reportMoney')) throw new ForbiddenHttpException('Access denied!');

        if (Yii::$app->request->isPost) {
            $date = new \DateTimeImmutable(Yii::$app->request->post('date', 'now'));
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
                $startDate = new \DateTimeImmutable("$year-$month-01 midnight");
                $endDate = $startDate->modify('last day of this month')->modify('+1 day midnight');
                
                $generatePage = function(TeacherSubjectLink $subjectTeacher, ?PhpWord $doc = null) use ($startDate, $endDate): PhpWord {
                    $groupParams = GroupParam::find()
                        ->alias('gp')
                        ->joinWith('group g')
                        ->andWhere([
                            'gp.year' => $startDate->format('Y'),
                            'gp.month' => $startDate->format('n'),
                            'gp.teacher_id' => $subjectTeacher->teacher_id,
                            'g.subject_id' => $subjectTeacher->subject_id,
                        ])
                        ->all();
                    $groupIds = ArrayHelper::getColumn($groupParams, 'group_id');

                    $eventData = Event::find()
                        ->andWhere(['between', 'event_date', $startDate->format('Y-m-d H:i:s'), $endDate->format('Y-m-d H:i:s')])
                        ->andWhere(['group_id' => $groupIds])
                        ->andWhere(['status' => Event::STATUS_PASSED])
                        ->select(['group_id', 'COUNT(id) AS cnt'])
                        ->groupBy('group_id')
                        ->asArray()
                        ->all();

                    $totalHours = 0;
                    foreach ($eventData as $data) {
                        $group = Group::findOne($data['group_id']);
                        $totalHours += floor($group->lesson_duration * $data['cnt'] / 45);
                    }

                    $totalAmount = array_sum(ArrayHelper::getColumn($groupParams, 'teacher_salary'));

                    return TeacherTimeReport::create($subjectTeacher, $startDate, $totalHours, $totalAmount, $doc);
                };
                
                switch (true) {
                    case $allTeachers:
                        $data = GroupParam::find()
                            ->alias('gp')
                            ->joinWith('group g', false)
                            ->andWhere([
                                'gp.year' => $startDate->format('Y'),
                                'gp.month' => $startDate->format('n'),
                            ])
                            ->select(['gp.teacher_id', 'g.subject_id'])
                            ->distinct()
                            ->orderBy(['gp.teacher_id' => SORT_ASC, 'g.subject_id' => SORT_ASC])
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
}

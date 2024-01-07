<?php

namespace backend\controllers;

use backend\components\EventComponent;
use backend\models\CourseNote;
use common\components\Action;
use common\components\ComponentContainer;
use common\components\CourseComponent;
use common\components\MoneyComponent;
use common\models\Course;
use common\models\CourseCategory;
use common\models\CourseConfig;
use common\models\CourseStudent;
use common\models\CourseSearch;
use common\models\CourseType;
use common\models\Payment;
use common\models\Subject;
use common\models\Teacher;
use common\models\User;
use DateTimeImmutable;
use yii;
use yii\bootstrap4\Html;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

/**
 * CourseController implements the CRUD actions for Course model.
 */
class CourseController extends AdminController
{
    /**
     * Lists all Course models.
     * @return mixed
     */
    public function actionIndex()
    {
        $this->checkAccess('viewGroups');

        if (\Yii::$app->user->identity->role == User::ROLE_ROOT) {
//        $user = User::findOne(27934);
//        foreach ($user->courseStudents as $courseStudent) {
//            EventComponent::fillSchedule($courseStudent->course);
//            MoneyComponent::rechargeStudent($courseStudent->user, $courseStudent->course);
//            MoneyComponent::setUserChargeDates($courseStudent->user, $courseStudent->course);
//            MoneyComponent::recalculateDebt($courseStudent->user, $courseStudent->course);
//        }

//        $courseStudent = CourseStudent::findOne(2993);
//        EventComponent::fillSchedule($courseStudent->course);
//        MoneyComponent::rechargeStudent($courseStudent->user, $courseStudent->course);
//        MoneyComponent::setUserChargeDates($courseStudent->user, $courseStudent->course);

//            $course = Course::findOne(192);
//            EventComponent::fillSchedule($course);
//            foreach ($course->courseStudents as $courseStudent) {
//                MoneyComponent::rechargeStudent($courseStudent->user, $courseStudent->course);
//                MoneyComponent::setUserChargeDates($courseStudent->user, $courseStudent->course);
//                MoneyComponent::recalculateDebt($courseStudent->user, $courseStudent->course);
//            }
        }


        return $this->renderList(['active' => Course::STATUS_ACTIVE]);
    }

    public function actionInactive()
    {
        $this->checkAccess('viewGroups');

        return $this->renderList(['active' => Course::STATUS_INACTIVE]);
    }

    private function renderList(array $filter)
    {
        /** @var User $currentUser */
        $currentUser = Yii::$app->user->identity;
        
        $searchModel = new CourseSearch();
        $searchParams = array_key_exists('CourseSearch', Yii::$app->request->queryParams) ? Yii::$app->request->queryParams['CourseSearch'] : [];
        if ($currentUser->isTeacher()) {
            $filter['teacher_id'] = $currentUser->teacher_id;
        }
        $dataProvider = $searchModel->search(['CourseSearch' => array_merge($searchParams, $filter)]);

        return $this->render('index', [
            'dataProvider' => $dataProvider,
            'searchModel' => $searchModel,
            'subjectMap' => ArrayHelper::map(
                Subject::find()->orderBy('name')->select(['id', 'name'])->asArray()->all(),
                'id',
                'name'
            ),
            'teacherMap' => ArrayHelper::map(
                Teacher::find()
                    ->andWhere(['active' => Teacher::STATUS_ACTIVE])
                    ->orderBy('name')
                    ->select(['id', 'name'])->asArray()->all(),
                'id',
                'name'
            ),
            'canEdit' => Yii::$app->user->can('manageGroups'),
            'isTeacher' => $currentUser->isTeacher(),
        ]);
    }

    /**
     * Creates a new Course model.
     * If creation is successful, the browser will be redirected to the 'update' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $this->checkAccess('manageGroups');

        $course = new Course();
        $course->loadDefaultValues();

        return $this->processCourseData($course);
    }

    /**
     * Updates an existing Course model.
     * If update is successful, the browser will be redirected to the 'same' page.
     * @param int $id
     * @return mixed
     */
    public function actionUpdate($id)
    {
        $this->checkAccess('manageGroups');

        $course = $this->findModel($id);
        return $this->processCourseData($course);
    }

    /**
     * View Course info.
     * @param int $id
     * @return mixed
     */
    public function actionView($id)
    {
        $this->checkAccess('viewGroups');

        $course = $this->findModel($id);

        return $this->render('view', [
            'course' => $course,
        ]);
    }

    /**
     * @param Course $course
     *
     * @return string|Response
     * @throws yii\db\Exception
     */
    private function processCourseData(Course $course)
    {
        if (Yii::$app->request->isPost) {
            if (empty($course->courseStudents)) {
                $course->scenario = Course::SCENARIO_EMPTY;
            }
            $course->load(Yii::$app->request->post());
            $courseVal = Yii::$app->request->post('Course', []);
            $newStudents = Yii::$app->request->post('student', []);
            $error = false;

            if (empty($course->courseStudents)) {
                $course->startDateObject = !empty($courseVal['date_start']) ? new DateTimeImmutable($courseVal['date_start']) : null;
            }
            $course->endDateObject = !empty($courseVal['date_end']) ? new DateTimeImmutable($courseVal['date_end']) : null;

            if (!$course->date_start && !empty($newStudents)) {
                Yii::$app->session->addFlash('error', 'Введите дату начала занятий группы!');
                $error = true;
            } elseif ($course->date_end && !$course->date_start) {
                Yii::$app->session->addFlash('error', 'Введите дату начала занятий группы прежде чем вносить дату завершения занятий');
                $error = true;
            } elseif ($course->date_end && $course->date_end <= $course->date_start) {
                Yii::$app->session->addFlash('error', 'Введённые даты начала и завершения занятий группы недопустимы');
                $error = true;
            }
            if (!$course->date_end || $course->endDateObject > new DateTimeImmutable()) {
                $course->active = Course::STATUS_ACTIVE;
            }

            $courseConfigData = Yii::$app->request->post('CourseConfig');
            if (!empty($courseConfigData)) {
                $weektime = Yii::$app->request->post('weektime', []);
                $latestCourseConfig = $course->latestCourseConfig;
                if (empty(array_filter($weektime))) {
                    Yii::$app->session->addFlash('error', 'Не указано время занятий');
                    $error = true;
                } elseif ($latestCourseConfig && (new DateTimeImmutable($courseConfigData['date_from'] . ' midnight')) <= $latestCourseConfig->dateFromObject) {
                    Yii::$app->session->addFlash('error', 'Выбрана неверная дата изменения параметров');
                    $error = true;
                } else {
                    $courseConfig = new CourseConfig();
                    $courseConfig->load($courseConfigData, '');
                    $courseConfig->schedule = $weektime;
                    if ($course->isNewRecord) {
                        $courseConfig->date_from = $course->date_start;
                    } else {
                        $courseConfig->dateFromObject = new DateTimeImmutable($courseConfigData['date_from'] . ' midnight');
                    }
                }
            }

            if (!$error) {
                $transaction = Yii::$app->db->beginTransaction();
                try {
                    $isNew = $course->isNewRecord;
                    $courseDiff = $course->getDiffMap();
                    if ($course->save()) {
                        if (!empty($courseDiff)) {
                            ComponentContainer::getActionLogger()->log(
                                $isNew ? Action::TYPE_COURSE_ADDED : Action::TYPE_COURSE_UPDATED,
                                null,
                                null,
                                $course,
                                json_encode($courseDiff, JSON_UNESCAPED_UNICODE)
                            );
                        }
                        if (isset($courseConfig)) {
                            $courseConfigs = $course->courseConfigs;
                            if (!empty($courseConfigs)) {
                                $previousCourseConfig = end($courseConfigs);
                                $previousCourseConfig->date_to = $courseConfig->date_from;
                                if (!$previousCourseConfig->save()) {
                                    throw new \Exception('Unable to save course config: ' . $previousCourseConfig->getErrorsAsString());
                                }
                            }
                            $course->link('courseConfigs', $courseConfig);
                        } elseif ($isNew) {
                            throw new \Exception('Course cannot be created without initial config');
                        }

                        $courseConfigs = $course->courseConfigs;
                        if ($course->date_start) {
                            while (true) {
                                $firstConfig = reset($courseConfigs);
                                if ($firstConfig->date_from == $course->date_start) {
                                    break;
                                }
                                if ($firstConfig->date_to && $firstConfig->date_to < $course->date_start) {
                                    $course->unlink('courseConfigs', $firstConfig, true);
                                    array_shift($courseConfigs);
                                    continue;
                                }
                                $firstConfig->date_from = $course->date_start;
                                $firstConfig->save();
                                break;
                            }
                        }

                        if ($course->date_end) {
                            while (true) {
                                $lastConfig = end($courseConfigs);
                                if ($lastConfig->date_to == $course->date_end) {
                                    break;
                                }
                                if ($lastConfig->date_from > $course->date_end) {
                                    $course->unlink('courseConfigs', $lastConfig, true);
                                    array_pop($courseConfigs);
                                    continue;
                                }
                                $lastConfig->date_to = $course->date_end;
                                $lastConfig->save();
                                break;
                            }
                        }

                        $this->saveCourseStudents($course, $newStudents);
                        /** @var Course $course */
                        $course = Course::find()->with(['courseStudents', 'events.members.payments'])->andWhere(['id' => $course->id])->one();
                        EventComponent::fillSchedule($course);

                        foreach ($course->courseStudents as $courseStudent) {
                            MoneyComponent::setUserChargeDates($courseStudent->user, $course);
                        }

                        $transaction->commit();
                        Yii::$app->session->addFlash('success', 'Данные успешно сохранены');

                        return $this->redirect(['update', 'id' => $course->id]);
                    } else {
                        $transaction->rollBack();
                        $course->moveErrorsToFlash();
                        Yii::$app->session->addFlash('error', 'Внутренняя ошибка сервера');
                    }
                } catch (\Throwable $ex) {
                    $transaction->rollBack();
                    ComponentContainer::getErrorLogger()
                        ->logError('course/update', $ex->getMessage(), true);
                    Yii::$app->session->addFlash('error', 'Внутренняя ошибка сервера: ' . $ex->getMessage());
                }
            }
        }
        return $this->render('update', [
            'course' => $course,
            'courseTypes' => CourseType::find()->orderBy('name')->all(),
            'courseCategories' => CourseCategory::find()->orderBy('name')->all(),
            'subjects' => Subject::find()->orderBy('name')->with('teachers')->all(),
            'canMoveMoney' => Yii::$app->user->can('moveMoney'),
        ]);
    }

    private function saveCourseStudents(Course $course, array $newStudents) {
        /** @var array<int,DateTimeImmutable[]> $studentMap */
        $studentMap = [];
        if (count($course->courseStudents) === 0) {
            foreach ($newStudents as $studentId) {
                $studentMap[$studentId] = ['startDate' => $course->startDateObject, 'endDate' => $course->endDateObject];
            }
        } else {
            $studentStartDates = Yii::$app->request->post('student_start', []);
            $studentEndDates = Yii::$app->request->post('student_end', []);
            $reasonIds = Yii::$app->request->post('reason_id', []);
            $reasonComments = Yii::$app->request->post('reason_comment', []);
            foreach ($newStudents as $key => $studentId) {
                $student = User::findOne(['id' => $studentId, 'role' => User::ROLE_STUDENT]);
                if ($student === null) {
                    throw new \Exception('Студент не найден');
                }
                if (!$studentStartDates[$key]) {
                    throw new \Exception('Введите корректную дату начала занятий студента ' . $student->name);
                }

                $startDate = new DateTimeImmutable($studentStartDates[$key] . ' midnight');
                if ($startDate < $course->startDateObject) {
                    $startDate = $course->startDateObject;
                }

                $endDate = !empty($studentEndDates[$key]) ? new DateTimeImmutable($studentEndDates[$key] . ' midnight') : null;
                if ($endDate && $course->date_end && $endDate > $course->endDateObject) $endDate = $course->endDateObject;
                if ($endDate && $endDate <= $startDate) throw new \Exception('Введённые даты начала и завершения занятий студента ' . $student->name . ' недопустимы');
                $studentMap[$studentId] = [
                    'startDate' => $startDate,
                    'endDate' => $endDate,
                ];
            }
            foreach ($course->activeCourseStudents as $courseStudent) {
                if (array_key_exists($courseStudent->user_id, $studentMap)
                    && ($courseStudent->startDateObject !== $studentMap[$courseStudent->user_id]['startDate']
                        || $courseStudent->endDateObject !== $studentMap[$courseStudent->user_id]['endDate'])) {
                    CourseComponent::checkStudentDates($courseStudent, $studentMap[$courseStudent->user_id]['startDate'], $studentMap[$courseStudent->user_id]['endDate']);
                    $courseStudent->date_start = $studentMap[$courseStudent->user_id]['startDate']->format('Y-m-d');
                    $courseStudent->date_end = $studentMap[$courseStudent->user_id]['endDate'] ? $studentMap[$courseStudent->user_id]['endDate']->format('Y-m-d') : null;

                    if ($courseStudent->date_end !== null && !empty($reasonIds[$courseStudent->id])) {
                        $courseStudent->end_reason = $reasonIds[$courseStudent->id];
                        $courseStudent->comment = $reasonComments[$courseStudent->id];
                    }
                    ComponentContainer::getActionLogger()->log(
                        Action::TYPE_COURSE_STUDENT_UPDATED,
                        $courseStudent->user,
                        null,
                        $course,
                        json_encode($courseStudent->getDiffMap(), JSON_UNESCAPED_UNICODE)
                    );

                    if (!$courseStudent->save()) throw new \Exception($courseStudent->getErrorsAsString());

                    EventComponent::fillSchedule($course);
                    MoneyComponent::rechargeStudent($courseStudent->user, $course);
                }
                unset($studentMap[$courseStudent->user_id]);
            }
        }

        foreach ($studentMap as $studentId => $studentData) {
            CourseComponent::addStudentToCourse(User::findOne($studentId), $course, $studentData['startDate'], $studentData['endDate'], false);
        }
    }

    public function actionMoveStudent($courseStudentId = null)
    {
        $this->checkAccess('manageGroups');

        $courseStudent = null;
        if ($courseStudentId) {
            $courseStudent = CourseStudent::findOne(['id' => $courseStudentId, 'active' => CourseStudent::STATUS_ACTIVE]);
        }
        
        return $this->render('move_student', [
            'courseStudent' => $courseStudent,
            'courseList' => Course::find()->andWhere(['active' => Course::STATUS_ACTIVE])->orderBy('name')->all(),
        ]);
    }

    public function actionProcessMoveStudent()
    {
        $this->checkRequestIsAjax();
        $this->checkAccess('manageGroups');
        Yii::$app->response->format = Response::FORMAT_JSON;

        $formData = Yii::$app->request->post('course-move', []);
        if (!isset($formData['id'], $formData['course_id'], $formData['date_from'], $formData['date_to'])) {
            return self::getJsonErrorResult('Wrong request');
        }

        $courseStudent = CourseStudent::findOne($formData['id']);
        $courseTo = Course::findOne($formData['course_id']);

        if (!$courseStudent || $courseStudent->active !== CourseStudent::STATUS_ACTIVE) {
            return self::getJsonErrorResult('Студент не найден');
        }
        if (!$courseTo || $courseTo->active !== Course::STATUS_ACTIVE) {
            return self::getJsonErrorResult('Группа В не найдена');
        }
        
        $dateFrom =  new DateTimeImmutable($formData['date_from'] . ' +1 day midnight');
        $dateTo =  new DateTimeImmutable($formData['date_to'] . ' midnight');
        if (!$dateFrom
            || !$dateTo
            || ($courseStudent->course->date_end && $dateFrom > $courseStudent->course->endDateObject)
            || $dateTo < $courseTo->startDateObject
        ) {
            self::getJsonErrorResult('Неверная дата перевода');
        }
        $unknownEvent = EventComponent::getUncheckedEvent($courseStudent->course, $dateFrom);
        if ($unknownEvent !== null) {
            return self::getJsonErrorResult(
                'В группе ИЗ остались неотмеченные занятия, отметьте их чтобы в дальнейшем не возникало долгов: '
                . Html::a(
                    $unknownEvent->eventDateTime->format('d.m.Y'),
                    Url::to(['event/index', 'date' => $unknownEvent->eventDateTime->format('d.m.Y')])
                )
            );
        }

        $moneyLeft = Payment::find()
            ->andWhere(['user_id' => $courseStudent->user_id, 'course_id' => $courseStudent->course_id])
            ->andWhere(['or',
                ['>', 'amount', 0],
                ['and', ['<', 'amount', 0], ['<', 'created_at', $dateFrom->format('Y-m-d H:i:s')]]
            ])
            ->select('SUM(amount)')->scalar();
        if ($moneyLeft < 0) {
            return self::getJsonErrorResult('Студент не может быть переведён пока не погасит долг - ' . (0 - $moneyLeft));
        }

        $transaction = CourseStudent::getDb()->beginTransaction();
        try {
            if (!$courseStudent->endDateObject || $courseStudent->endDateObject > $dateFrom) {
                $courseStudent->date_end = $dateFrom->format('Y-m-d');
                $courseStudent->moved = CourseStudent::STATUS_ACTIVE;
                $courseStudent->active = CourseStudent::STATUS_INACTIVE;
                if (!$courseStudent->save()) {
                    throw new \Exception($courseStudent->getErrorsAsString());
                }
                EventComponent::fillSchedule($courseStudent->course);
            }
            CourseComponent::addStudentToCourse($courseStudent->user, $courseTo, $dateTo, null, false);
            CourseComponent::moveMoney($courseStudent->course, $courseTo, $courseStudent->user, $dateTo);

            $transaction->commit();
            return self::getJsonOkResult(['userId' => $courseStudent->user_id]);
        } catch (\Throwable $ex) {
            $transaction->rollBack();
            ComponentContainer::getErrorLogger()
                ->logError('course/move-student', $ex->getMessage(), true);
            return self::getJsonErrorResult('Server error: ' . $ex->getMessage());
        }
    }

    /**
     * @param int $courseStudentId
     *
     * @return string|Response
     * @throws BadRequestHttpException
     * @throws ForbiddenHttpException
     */
    public function actionMoveMoney(int $courseStudentId)
    {
        $this->checkAccess('moveMoney');

        $courseStudent = CourseStudent::findOne($courseStudentId);
        if (!$courseStudent) throw new BadRequestHttpException('Student not found');
        
        if (CourseStudent::findOne(['user_id' => $courseStudent->user_id, 'course_id' => $courseStudent->course_id, 'active' => CourseStudent::STATUS_ACTIVE, 'date_end' => null])) {
            throw new BadRequestHttpException('Студент ещё занимается в группе');
        }

        $moneyLeft = Payment::find()->andWhere(['user_id' => $courseStudent->user_id, 'course_id' => $courseStudent->course_id])->select('SUM(amount)')->scalar();
        if ($moneyLeft <= 0) throw new BadRequestHttpException('Не осталось денег для перевода');

        $dateEnd = CourseStudent::find()->andWhere(['user_id' => $courseStudent->user_id, 'course_id' => $courseStudent->course_id])->select('MAX(date_end)')->scalar();
        if (!$dateEnd) throw new BadRequestHttpException('Студент ещё занимается в группе');
        $dateEnd = new DateTimeImmutable($dateEnd);
        
        $unknownEvent = EventComponent::getUncheckedEvent($courseStudent->course, $dateEnd);
        if ($unknownEvent !== null) {
            throw new BadRequestHttpException("В группе {$courseStudent->course->courseConfig->name} остались неотмеченные занятия, отметьте их чтобы в дальнейшем не возникало долгов: "
                . Html::a(
                    $unknownEvent->eventDateTime->format('d.m.Y'),
                    Url::to(['event/index', 'date' => $unknownEvent->eventDateTime->format('d.m.Y')])
                )
            );
        }

        if (Yii::$app->request->isPost) {
            $courseToId = Yii::$app->request->post('money-move')['courseId'];
            if (!$courseToId) throw new BadRequestHttpException('No course selected');
            $courseTo = Course::findOne($courseToId);
            if (!$courseTo) throw new BadRequestHttpException('Course not found');
            $courseStudentsTo = CourseStudent::findAll(['user_id' => $courseStudent->user_id, 'course_id' => $courseTo->id, 'active' => CourseStudent::STATUS_ACTIVE]);
            if (count($courseStudentsTo) === 0) throw new BadRequestHttpException('Student in destination course is not found');

            $transaction = Yii::$app->db->beginTransaction();
            try {
                CourseComponent::moveMoney($courseStudent->course, $courseTo, $courseStudent->user);

                foreach (CourseStudent::findAll(['user_id' => $courseStudent->user_id, 'course_id' => $courseStudent->course_id, 'active' => CourseStudent::STATUS_ACTIVE]) as $oldCourseStudent) {
                    $oldCourseStudent->active = CourseStudent::STATUS_INACTIVE;
                    $oldCourseStudent->save();
                }
                $transaction->commit();
                Yii::$app->session->addFlash('success', 'Средства перенесены');
            } catch (\Throwable $exception) {
                $transaction->rollBack();
                Yii::$app->session->addFlash('error', $exception->getMessage());
            }
        }

        return $this->render('move_money', [
            'courseStudent' => $courseStudent,
            'moneyLeft' => $moneyLeft,
            'courseList' => Course::find()
                ->alias('c')
                ->leftJoin(CourseStudent::tableName() . ' cs', 'cs.course_id = c.id')
                ->andWhere(['cs.active' => CourseStudent::STATUS_ACTIVE, 'cs.user_id' => $courseStudent->user_id])
                ->andWhere('c.id != :courseFrom', ['courseFrom' => $courseStudent->course_id])
                ->distinct()
                ->all(),
        ]);
    }
    
    public function actionProcessMoveMoney()
    {
        $this->checkRequestIsAjax();
        $this->checkAccess('moveMoney');
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        $formData = Yii::$app->request->post('money-move', []);
        if (!isset($formData['id'], $formData['courseId'])) {
            return self::getJsonErrorResult('Wrong request');
        }
        /** @var CourseStudent $courseStudent */
        $courseStudent = CourseStudent::find()->andWhere(['id' => $formData['id'], 'active' => CourseStudent::STATUS_INACTIVE])->one();
        if (!$courseStudent) {
            return self::getJsonErrorResult('Student not found');
        }
        /** @var Course $courseTo */
        $courseTo = Course::findOne($formData['courseId']);
        if (!$courseTo) {
            return self::getJsonErrorResult('Course not found');
        }
        
        $courseStudentsTo = CourseStudent::find()->andWhere(['user_id' => $courseStudent->user_id, 'course_id' => $courseTo->id, 'active' => CourseStudent::STATUS_ACTIVE])->all();
        if (count($courseStudentsTo) == 0) {
            /** @var CourseStudent[] $courseStudentsTo */
            $courseStudentsTo = CourseStudent::find()->andWhere(['user_id' => $courseStudent->user_id, 'course_id' => $courseTo->id, 'active' => CourseStudent::STATUS_INACTIVE])->all();
            if (count($courseStudentsTo) == 0 || $courseStudentsTo[0]->moneyLeft > 0) {
                return self::getJsonErrorResult('Student in destination group is not found');
            }
        }

        $unknownEvent = EventComponent::getUncheckedEvent($courseStudent->course, $courseStudent->endDateObject);
        if ($unknownEvent !== null) {
            return self::getJsonErrorResult("В группе {$courseStudent->course->courseConfig->name} остались неотмеченные занятия, отметьте их чтобы в дальнейшем не возникало долгов: "
                . Html::a(
                    $unknownEvent->eventDateTime->format('d.m.Y'),
                    Url::to(['event/index', 'date' => $unknownEvent->eventDateTime->format('d.m.Y')])
                )
            );
        }

        $transaction = \Yii::$app->db->beginTransaction();
        try {
            CourseComponent::moveMoney($courseStudent->course, $courseTo, $courseStudent->user);
            $transaction->commit();
            return self::getJsonOkResult(['userId' => $courseStudent->user_id]);
        } catch (\Throwable $exception) {
            $transaction->rollBack();
            return self::getJsonErrorResult($exception->getMessage());
        }
    }

    public function actionEndStudent()
    {
        $this->checkRequestIsAjax();
        $this->checkAccess('manageGroups');
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        $formData = Yii::$app->request->post('end-student');
        if (!isset($formData['id'], $formData['date'], $formData['reasonId'])) {
            return self::getJsonErrorResult('Wrong request');
        }
        /** @var CourseStudent $courseStudent */
        $courseStudent = CourseStudent::findOne(['id' => $formData['id'], 'active' => CourseStudent::STATUS_ACTIVE]);
        if (!$courseStudent) {
            return self::getJsonErrorResult('Student not found');
        }
        
        $endDate =  new DateTimeImmutable($formData['date'] . ' +1 day midnight');
        if (!$endDate || $endDate < $courseStudent->startDateObject || ($courseStudent->course->date_end && $courseStudent->course->endDateObject < $endDate)) {
            return self::getJsonErrorResult('Неверная дата');
        }

        $unknownEvent = EventComponent::getUncheckedEvent($courseStudent->course, $endDate);
        if ($unknownEvent !== null) {
            return self::getJsonErrorResult(
                'В группе остались неотмеченные занятия, отметьте их чтобы в дальнейшем не возникало долгов: '
                . Html::a(
                    $unknownEvent->eventDateTime->format('d.m.Y'),
                    Url::to(['event/index', 'date' => $unknownEvent->eventDateTime->format('d.m.Y')])
                )
            );
        }

        $courseStudent->date_end = $endDate->format('Y-m-d');
        $courseStudent->end_reason = $formData['reasonId'];
        $courseStudent->comment = $formData['reasonComment'];

        if ($endDate <= new DateTimeImmutable('tomorrow midnight')) {
            $courseStudent->active = CourseStudent::STATUS_INACTIVE;
        }

        ComponentContainer::getActionLogger()->log(
            Action::TYPE_COURSE_STUDENT_UPDATED,
            $courseStudent->user,
            null,
            $courseStudent->course,
            json_encode($courseStudent->getDiffMap(), JSON_UNESCAPED_UNICODE)
        );
        
        if (!$courseStudent->save()) {
            return self::getJsonErrorResult($courseStudent->getErrorsAsString());
        }
        EventComponent::fillSchedule($courseStudent->course);
        
        return self::getJsonOkResult(['userId' => $courseStudent->user_id]);
    }

    /**
     * @return Response
     * @throws BadRequestHttpException
     */
    public function actionListJson(?int $studentId = null)
    {
        $this->checkRequestIsAjax();

        $jsonData = [];
        if ($studentId) {
            $student = User::findOne($studentId);
            if ($student) {
                foreach ($student->activeCourseStudents as $courseStudent) {
                    $courseData = [
                        'id' => $courseStudent->id,
                        'course_id' => $courseStudent->course_id,
                        'date_start' => $courseStudent->startDateObject->format('d.m.Y'),
                        'date_end' => $courseStudent->date_end ? $courseStudent->endDateObject->format('d.m.Y') : '',
                    ];
                    $jsonData[] = $courseData;
                }
            }
        } else {
            /** @var Course[] $courses */
            $courses = Course::find()->andWhere(['active' => Course::STATUS_ACTIVE])->all();
            foreach ($courses as $course) {
                $courseConfig = $course->courseConfig;
                $courseData = $courseConfig->toArray(['course_id', 'name', 'lesson_price', 'lesson_price_discount']);
                $courseData['lesson_12_price'] = $courseConfig->price12Lesson;
                $jsonData[] = $courseData;
            }
        }

        return $this->asJson($jsonData);
    }

    public function actionNoteAdd()
    {
        $this->checkRequestIsAjax();
        $this->checkAccess('teacher');
        Yii::$app->response->format = Response::FORMAT_JSON;

        /** @var User $currentUser */
        $currentUser = Yii::$app->user->identity;
        $courseId = Yii::$app->request->post('course_id');
        if (!$courseId || !($course = Course::findOne($courseId)) || $course->courseConfig->teacher_id !== $currentUser->teacher_id) {
            return self::getJsonErrorResult('Invalid course');
        }
        if (!$note = Yii::$app->request->post('note')) {
            return self::getJsonErrorResult('Empty note is not allowed');
        }
        
        $courseNote = new CourseNote();
        $courseNote->course_id = $courseId;
        $courseNote->teacher_id = $currentUser->teacher_id;
        $courseNote->topic = $note;
        if (!$courseNote->save()) {
            return self::getJsonErrorResult($courseNote->getErrorsAsString());
        }

        return self::getJsonOkResult();
    }
    
    public function actionNotes()
    {
        $this->checkAccess('viewNotes');

        $searchModel = new CourseSearch();
        $searchParams = array_key_exists('CourseSearch', Yii::$app->request->queryParams) ? Yii::$app->request->queryParams['CourseSearch'] : [];
        $dataProvider = $searchModel->search(['CourseSearch' => array_merge($searchParams, ['active' => Course::STATUS_ACTIVE])]);

        $courseMap = [null => 'Все'];
        foreach (CourseComponent::getActiveSortedByName() as $course) {
            $courseMap[$course->id] = $course->courseConfig->name;
        }

        return $this->render('notes', [
            'dataProvider' => $dataProvider,
            'searchModel' => $searchModel,
            'subjectMap' => ArrayHelper::map(
                Subject::find()->orderBy('name')->select(['id', 'name'])->asArray()->all(),
                'id',
                'name'
            ),
            'teacherMap' => ArrayHelper::map(
                Teacher::find()
                    ->andWhere(['active' => Teacher::STATUS_ACTIVE])
                    ->orderBy('name')
                    ->select(['id', 'name'])->asArray()->all(),
                'id',
                'name'
            ),
            'courseMap' => $courseMap,
        ]);
    }
    
    public function actionNote(int $id)
    {
        $course = $this->findModel($id);

        return $this->render('note_history', [
            'course' => $course
        ]);
    }

    /**
     * Finds the Group model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     *
     * @param integer $id
     *
     * @return Course the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Course::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }
}

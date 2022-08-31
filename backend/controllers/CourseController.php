<?php

namespace backend\controllers;

use backend\components\EventComponent;
use backend\models\CourseNote;
use common\components\Action;
use common\components\ComponentContainer;
use common\components\CourseComponent;
use common\components\MoneyComponent;
use common\models\Course;
use common\models\CourseConfig;
use common\models\CourseStudent;
use common\models\CourseSearch;
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
//        $user = User::findOne(7227);
//        foreach ($user->groupPupils as $groupPupil) {
//            EventComponent::fillSchedule($groupPupil->group);
//            MoneyComponent::rechargePupil($groupPupil->user, $groupPupil->group);
//            MoneyComponent::setUserChargeDates($groupPupil->user, $groupPupil->group);
//            GroupComponent::calculateTeacherSalary($groupPupil->group);
//            MoneyComponent::recalculateDebt($groupPupil->user, $groupPupil->group);
//        }

//        $groupPupil = GroupPupil::findOne(1146);
//        EventComponent::fillSchedule($groupPupil->group);
//        MoneyComponent::rechargePupil($groupPupil->user, $groupPupil->group);
//        MoneyComponent::setUserChargeDates($groupPupil->user, $groupPupil->group);
//        GroupComponent::calculateTeacherSalary($groupPupil->group);

//        $group = Group::findOne(192);
//        EventComponent::fillSchedule($group);
//        foreach ($group->groupPupils as $groupPupil) {
//            MoneyComponent::rechargePupil($groupPupil->user, $groupPupil->group);
//            MoneyComponent::setUserChargeDates($groupPupil->user, $groupPupil->group);
//        }
//        GroupComponent::calculateTeacherSalary($group);
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
     * Creates a new Group model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        if (!Yii::$app->user->can('manageGroups')) throw new ForbiddenHttpException('Access denied!');

        $group = new Course();
        $group->loadDefaultValues();

        return $this->processGroupData($group);
    }

    /**
     * Updates an existing Group model.
     * If update is successful, the browser will be redirected to the 'same' page.
     * @param int $id
     * @return mixed
     */
    public function actionUpdate($id)
    {
        if (!Yii::$app->user->can('manageGroups')) throw new ForbiddenHttpException('Access denied!');

        $group = $this->findModel($id);
        return $this->processGroupData($group);
    }

    /**
     * View Group info.
     * @param int $id
     * @return mixed
     */
    public function actionView($id)
    {
        if (!Yii::$app->user->can('viewGroups')) throw new ForbiddenHttpException('Access denied!');

        $group = $this->findModel($id);

        return $this->render('view', [
            'group' => $group,
        ]);
    }

    /**
     * @param Course $group
     *
     * @return string|Response
     * @throws yii\db\Exception
     */
    private function processGroupData(Course $group)
    {
        if (Yii::$app->request->isPost) {
            if (empty($group->groupPupils)) {
                $group->scenario = Course::SCENARIO_EMPTY;
            }
            $group->load(Yii::$app->request->post());
            $groupVal = Yii::$app->request->post('Group', []);
            $newPupils = Yii::$app->request->post('pupil', []);
            $error = false;

            if (empty($group->groupPupils)) {
                $group->startDateObject = !empty($groupVal['date_start']) ? new \DateTime($groupVal['date_start']) : null;
            }
            $group->endDateObject = !empty($groupVal['date_end']) ? new \DateTime($groupVal['date_end']) : null;

            if (!$group->date_start && !empty($newPupils)) {
                Yii::$app->session->addFlash('error', 'Введите дату начала занятий группы!');
                $error = true;
            } elseif ($group->date_end && !$group->date_start) {
                Yii::$app->session->addFlash('error', 'Введите дату начала занятий группы прежде чем вносить дату завершения занятий');
                $error = true;
            } elseif ($group->date_end && $group->date_end <= $group->date_start) {
                Yii::$app->session->addFlash('error', 'Введённые даты начала и завершения занятий группы недопустимы');
                $error = true;
            }
            if (!$group->date_end || $group->endDateObject > new \DateTime()) {
                $group->active = Course::STATUS_ACTIVE;
            }

            $groupConfigData = Yii::$app->request->post('GroupConfig');
            if (!empty($groupConfigData)) {
                $weektime = Yii::$app->request->post('weektime', []);
                if (empty(array_filter($weektime))) {
                    Yii::$app->session->addFlash('error', 'Не указано время занятий');
                    $error = true;
                } else {
                    $groupConfig = new CourseConfig();
                    $groupConfig->load($groupConfigData, '');
                    $groupConfig->schedule = $weektime;
                    if ($group->isNewRecord) {
                        $groupConfig->date_from = $group->date_start;
                        // TODO remove this after migration
                        $group->teacher_id = $groupConfig->teacher_id;
                        $group->lesson_price = $groupConfig->lesson_price;
                    } else {
                        $groupConfig->dateFromObject = new DateTimeImmutable($groupConfigData['date_from']);
                    }
                    if ($group->groupConfig) {
                        $group->groupConfig->dateToObject = new DateTimeImmutable($groupConfigData['date_from']);
                        $group->groupConfig->save();
                    }
                }
            }

            if (!$error) {
                $transaction = Yii::$app->db->beginTransaction();
                try {
                    $isNew = $group->isNewRecord;
                    $groupDiff = $group->getDiffMap();
                    if ($group->save()) {
                        if (!empty($groupDiff)) {
                            ComponentContainer::getActionLogger()->log(
                                $isNew ? Action::TYPE_GROUP_ADDED : Action::TYPE_GROUP_UPDATED,
                                null,
                                null,
                                $group,
                                json_encode($groupDiff, JSON_UNESCAPED_UNICODE)
                            );
                        }
                        if (empty($group->groupPupils) && !empty($newPupils)) {
                            $this->fillGroupParams($group);
                        }
                        if (isset($groupConfig)) {
                            $group->link('groupConfigs', $groupConfig);
//                            if (!$groupConfig->save()) {
//                                $transaction->rollBack();
//                                $groupConfig->moveErrorsToFlash();
//                                Yii::$app->session->addFlash('error', 'Внутренняя ошибка сервера');
//                                $error = true;
//                            }
                        }
                        if (!$error) {
                            $this->saveGroupPupils($group, $newPupils);
                            /** @var Course $group */
                            $group = Course::find()->with(['groupPupils', 'events.members.payments'])->andWhere(['id' => $group->id])->one();
                            EventComponent::fillSchedule($group);
                            CourseComponent::calculateTeacherSalary($group);
                            foreach ($group->groupPupils as $groupPupil) {
                                MoneyComponent::setUserChargeDates($groupPupil->user, $group);
                            }

                            $transaction->commit();
                            Yii::$app->session->addFlash('success', 'Данные успешно сохранены');

                            return $this->redirect(['update', 'id' => $group->id]);
                        }
                    } else {
                        $transaction->rollBack();
                        $group->moveErrorsToFlash();
                        Yii::$app->session->addFlash('error', 'Внутренняя ошибка сервера');
                    }
                } catch (\Throwable $ex) {
                    $transaction->rollBack();
                    ComponentContainer::getErrorLogger()
                        ->logError('group/update', $ex->getMessage(), true);
                    Yii::$app->session->addFlash('error', 'Внутренняя ошибка сервера: ' . $ex->getMessage());
                }
            }
        }
        return $this->render('update', [
            'group' => $group,
            'groupTypes' => GroupType::find()->orderBy('name')->all(),
            'subjects' => Subject::find()->orderBy('name')->with('teachers')->all(),
            'canMoveMoney' => Yii::$app->user->can('moveMoney'),
        ]);
    }

    /**
     * @param Course $group
     *
     * @throws \Exception
     */
    private function fillGroupParams(Course $group) {
        $currMonth = clone $group->startDateObject;
        $currMonth->modify('first day of this month midnight');
        $nextMonth = new \DateTime('first day of next month midnight');
        $monthInterval = new \DateInterval('P1M');
        while ($currMonth < $nextMonth) {
            CourseComponent::getGroupParam($group, $currMonth);
            $currMonth->add($monthInterval);
        }
    }

    /**
     * @param Course $group
     * @param array  $newPupils
     *
     * @throws \Exception
     */
    private function saveGroupPupils(Course $group, array $newPupils) {
        /** @var \DateTime[][] $pupilsMap */
        $pupilsMap = [];
        $salaryRecalcDate = null;
        if (count($group->groupPupils) == 0) {
            foreach ($newPupils as $pupilId) {
                $pupilsMap[$pupilId] = ['startDate' => $group->startDateObject, 'endDate' => $group->endDateObject];
            }
        } else {
            $pupilStartDates = Yii::$app->request->post('pupil_start', []);
            $pupilEndDates = Yii::$app->request->post('pupil_end', []);
            $reasonIds = Yii::$app->request->post('reason_id', []);
            $reasonComments = Yii::$app->request->post('reason_comment', []);
            foreach ($newPupils as $key => $pupilId) {
                $startDate = new \DateTime($pupilStartDates[$key] . ' midnight');
                $pupil = User::findOne($pupilId);
                if ($pupil === null || $pupil->role != User::ROLE_STUDENT) throw new \Exception('Студент не найден');
                elseif (!$startDate) throw new \Exception('Введите корректную дату начала занятий студента ' . $pupil->name);
                if ($startDate < $group->startDateObject) $startDate = clone $group->startDateObject;

                $endDate = !empty($pupilEndDates[$key]) ? new \DateTime($pupilEndDates[$key] . ' midnight') : null;
                if ($endDate && $group->date_end && $endDate > $group->endDateObject) $endDate = clone $group->endDateObject;
                if ($endDate && $endDate <= $startDate) throw new \Exception('Введённые даты начала и завершения занятий студента ' . $pupil->name . ' недопустимы');
                $pupilsMap[$pupilId] = [
                    'startDate' => $startDate,
                    'endDate' => $endDate,
                ];
            }
            foreach ($group->activeGroupPupils as $groupPupil) {
                if (array_key_exists($groupPupil->user_id, $pupilsMap)
                    && ($groupPupil->startDateObject != $pupilsMap[$groupPupil->user_id]['startDate']
                        || $groupPupil->endDateObject != $pupilsMap[$groupPupil->user_id]['endDate'])) {
                    CourseComponent::checkPupilDates($groupPupil, $pupilsMap[$groupPupil->user_id]['startDate'], $pupilsMap[$groupPupil->user_id]['endDate']);
                    $groupPupil->date_start = $pupilsMap[$groupPupil->user_id]['startDate']->format('Y-m-d');
                    $groupPupil->date_end = $pupilsMap[$groupPupil->user_id]['endDate'] ? $pupilsMap[$groupPupil->user_id]['endDate']->format('Y-m-d') : null;

                    if ($groupPupil->date_end !== null && !empty($reasonIds[$groupPupil->id])) {
                        $groupPupil->end_reason = $reasonIds[$groupPupil->id];
                        $groupPupil->comment = $reasonComments[$groupPupil->id];
                    }
                    ComponentContainer::getActionLogger()->log(
                        Action::TYPE_GROUP_PUPIL_UPDATED,
                        $groupPupil->user,
                        null,
                        $group,
                        json_encode($groupPupil->getDiffMap(), JSON_UNESCAPED_UNICODE)
                    );

                    if (!$groupPupil->save()) throw new \Exception($groupPupil->getErrorsAsString());

                    EventComponent::fillSchedule($group);
                    MoneyComponent::rechargePupil($groupPupil->user, $group);
                }
                unset($pupilsMap[$groupPupil->user_id]);
            }
        }

        foreach ($pupilsMap as $pupilId => $pupilData) {
            CourseComponent::addPupilToGroup(User::findOne($pupilId), $group, $pupilData['startDate'], $pupilData['endDate'], false);
        }
    }

    /**
     * @param null $groupPupilId
     * @return string|Response
     * @throws ForbiddenHttpException
     */
    public function actionMovePupil($groupPupilId = null)
    {
        $this->checkAccess('manageGroups');

        $groupPupil = null;
        if ($groupPupilId) {
            $groupPupil = CourseStudent::findOne(['id' => $groupPupilId, 'active' => CourseStudent::STATUS_ACTIVE]);
        }
        
        return $this->render('move_pupil', [
            'groupPupil' => $groupPupil,
            'groupList' => Course::find()->andWhere(['active' => Course::STATUS_ACTIVE])->orderBy('name')->all(),
        ]);
    }

    public function actionProcessMovePupil()
    {
        $this->checkRequestIsAjax();
        $this->checkAccess('manageGroups');
        Yii::$app->response->format = Response::FORMAT_JSON;

        $formData = Yii::$app->request->post('group-move', []);
        if (!isset($formData['id'], $formData['group_id'], $formData['date_from'], $formData['date_to'])) {
            return self::getJsonErrorResult('Wrong request');
        }

        $courseStudent = CourseStudent::findOne($formData['id']);
        $groupTo = Course::findOne($formData['group_id']);

        if (!$courseStudent || $courseStudent->active !== CourseStudent::STATUS_ACTIVE) {
            return self::getJsonErrorResult('Студент не найден');
        }
        if (!$groupTo || $groupTo->active !== Course::STATUS_ACTIVE) {
            return self::getJsonErrorResult('Группа В не найдена');
        }
        
        $dateFrom =  new DateTimeImmutable($formData['date_from'] . ' +1 day midnight');
        $dateTo =  new DateTimeImmutable($formData['date_to'] . ' midnight');
        if (!$dateFrom
            || !$dateTo
            || ($courseStudent->group->date_end && $dateFrom > $courseStudent->group->endDateObject)
            || $dateTo < $groupTo->startDateObject
        ) {
            self::getJsonErrorResult('Неверная дата перевода');
        }
        $unknownEvent = EventComponent::getUncheckedEvent($courseStudent->group, $dateFrom);
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
                EventComponent::fillSchedule($courseStudent->group);
                CourseComponent::calculateTeacherSalary($courseStudent->group);
            }
            CourseComponent::addPupilToGroup($courseStudent->user, $groupTo, $dateTo, null, false);
            CourseComponent::moveMoney($courseStudent->group, $groupTo, $courseStudent->user, $dateTo);

            $transaction->commit();
            return self::getJsonOkResult(['userId' => $courseStudent->user_id]);
        } catch (\Throwable $ex) {
            $transaction->rollBack();
            ComponentContainer::getErrorLogger()
                ->logError('group/move-pupil', $ex->getMessage(), true);
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
        if (!$courseStudent) throw new BadRequestHttpException('Pupil not found');
        
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
                ->joinWith('courseStudents')
                ->andWhere([CourseStudent::tableName() . '.active' => CourseStudent::STATUS_ACTIVE, CourseStudent::tableName() . '.user_id' => $courseStudent->user_id])
                ->andWhere(Course::tableName() . '.id != :courseFrom', ['courseFrom' => $courseStudent->course_id])
                ->distinct(true)
                ->orderBy(Course::tableName() . '.name')->all(),
        ]);
    }
    
    public function actionProcessMoveMoney()
    {
        $this->checkRequestIsAjax();
        $this->checkAccess('moveMoney');
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        $formData = Yii::$app->request->post('money-move', []);
        if (!isset($formData['id'], $formData['groupId'])) {
            return self::getJsonErrorResult('Wrong request');
        }
        /** @var CourseStudent $groupPupil */
        $groupPupil = CourseStudent::find()->andWhere(['id' => $formData['id'], 'active' => CourseStudent::STATUS_INACTIVE])->one();
        if (!$groupPupil) {
            return self::getJsonErrorResult('Pupil not found');
        }
        /** @var Course $groupTo */
        $groupTo = Course::findOne($formData['groupId']);
        if (!$groupTo) {
            return self::getJsonErrorResult('Group not found');
        }
        
        $groupPupilsTo = CourseStudent::find()->andWhere(['user_id' => $groupPupil->user_id, 'group_id' => $groupTo->id, 'active' => CourseStudent::STATUS_ACTIVE])->all();
        if (count($groupPupilsTo) == 0) {
            return self::getJsonErrorResult('Pupil in destination group is not found');
        }

        $unknownEvent = EventComponent::getUncheckedEvent($groupPupil->group, $groupPupil->endDateObject);
        if ($unknownEvent !== null) {
            return self::getJsonErrorResult("В группе {$groupPupil->group->name} остались неотмеченные занятия, отметьте их чтобы в дальнейшем не возникало долгов: "
                . Html::a(
                    $unknownEvent->eventDateTime->format('d.m.Y'),
                    Url::to(['event/index', 'date' => $unknownEvent->eventDateTime->format('d.m.Y')])
                )
            );
        }

        $transaction = \Yii::$app->db->beginTransaction();
        try {
            CourseComponent::moveMoney($groupPupil->group, $groupTo, $groupPupil->user);
            $transaction->commit();
            return self::getJsonOkResult(['userId' => $groupPupil->user_id]);
        } catch (\Throwable $exception) {
            $transaction->rollBack();
            return self::getJsonErrorResult($exception->getMessage());
        }
    }

    public function actionEndPupil()
    {
        $this->checkRequestIsAjax();
        $this->checkAccess('manageGroups');
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        $formData = Yii::$app->request->post('end-pupil');
        if (!isset($formData['id'], $formData['date'], $formData['reasonId'])) {
            return self::getJsonErrorResult('Wrong request');
        }
        /** @var CourseStudent $groupPupil */
        $groupPupil = CourseStudent::findOne(['id' => $formData['id'], 'active' => CourseStudent::STATUS_ACTIVE]);
        if (!$groupPupil) {
            return self::getJsonErrorResult('Pupil not found');
        }
        
        $endDate =  new DateTimeImmutable($formData['date'] . ' +1 day midnight');
        if (!$endDate || $endDate < $groupPupil->startDateObject || ($groupPupil->group->date_end && $groupPupil->group->endDateObject < $endDate)) {
            return self::getJsonErrorResult('Неверная дата');
        }

        $unknownEvent = EventComponent::getUncheckedEvent($groupPupil->group, $endDate);
        if ($unknownEvent !== null) {
            return self::getJsonErrorResult(
                'В группе остались неотмеченные занятия, отметьте их чтобы в дальнейшем не возникало долгов: '
                . Html::a(
                    $unknownEvent->eventDateTime->format('d.m.Y'),
                    Url::to(['event/index', 'date' => $unknownEvent->eventDateTime->format('d.m.Y')])
                )
            );
        }

        $groupPupil->date_end = $endDate->format('Y-m-d');
        $groupPupil->end_reason = $formData['reasonId'];
        $groupPupil->comment = $formData['reasonComment'];

        if ($endDate <= new DateTimeImmutable('tomorrow midnight')) {
            $groupPupil->active = CourseStudent::STATUS_INACTIVE;
        }

        ComponentContainer::getActionLogger()->log(
            Action::TYPE_GROUP_PUPIL_UPDATED,
            $groupPupil->user,
            null,
            $groupPupil->group,
            json_encode($groupPupil->getDiffMap(), JSON_UNESCAPED_UNICODE)
        );
        
        if (!$groupPupil->save()) {
            return self::getJsonErrorResult($groupPupil->getErrorsAsString());
        }
        EventComponent::fillSchedule($groupPupil->group);
        CourseComponent::calculateTeacherSalary($groupPupil->group);
        
        return self::getJsonOkResult(['userId' => $groupPupil->user_id]);
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
            return self::getJsonErrorResult('Invalid group');
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

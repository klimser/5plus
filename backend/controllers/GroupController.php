<?php

namespace backend\controllers;

use backend\components\EventComponent;
use common\components\Action;
use common\components\ComponentContainer;
use common\components\MoneyComponent;
use common\models\Group;
use common\models\GroupParam;
use common\models\GroupPupil;
use common\models\GroupSearch;
use common\models\GroupType;
use common\models\Payment;
use common\models\User;
use common\components\GroupComponent;
use common\models\Subject;
use common\models\Teacher;
use yii;
use yii\web\NotFoundHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\BadRequestHttpException;
use yii\web\Response;
use yii\bootstrap4\Html;
use yii\helpers\Url;
use yii\helpers\ArrayHelper;

/**
 * GroupController implements the CRUD actions for Teacher model.
 */
class GroupController extends AdminController
{
    /**
     * Lists all Group models.
     * @return mixed
     */
    public function actionIndex()
    {
        if (!Yii::$app->user->can('viewGroups')) throw new ForbiddenHttpException('Access denied!');

        if (\Yii::$app->user->identity->role == User::ROLE_ROOT) {
//        $user = User::findOne(6523);
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


        return $this->renderList(['active' => Group::STATUS_ACTIVE]);
    }

    public function actionInactive()
    {
        if (!Yii::$app->user->can('viewGroups')) throw new ForbiddenHttpException('Access denied!');
        return $this->renderList(['active' => Group::STATUS_INACTIVE]);
    }

    private function renderList(array $filter)
    {
        $searchModel = new GroupSearch();
        $searchParams = array_key_exists('GroupSearch', Yii::$app->request->queryParams) ? Yii::$app->request->queryParams['GroupSearch'] : [];
        $dataProvider = $searchModel->search(['GroupSearch' => array_merge($searchParams, $filter)]);

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

        $group = new Group();
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
     * @param Group $group
     * @return string|Response
     * @throws yii\db\Exception
     */
    private function processGroupData(Group $group)
    {
        if (Yii::$app->request->isPost) {
            $group->load(Yii::$app->request->post());
            $groupVal = Yii::$app->request->post('Group', []);
            $newPupils = Yii::$app->request->post('pupil', []);
            $error = false;
            if (array_key_exists('date_start', $groupVal)) {
                if (!empty($group->groupPupils)) {
                    Yii::$app->session->addFlash('error', 'Вы не можете изменять дату начала занятий группы');
                    $error = true;
                } else {
                    $group->startDateObject = $groupVal['date_start'] ? date_create_from_format('d.m.Y', $groupVal['date_start']) : null;
                }
            }
            if (array_key_exists('date_end', $groupVal)) {
                $group->endDateObject = $groupVal['date_end'] ? date_create_from_format('d.m.Y', $groupVal['date_end']) : null;
            }
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
                $group->active = Group::STATUS_ACTIVE;
            }

            $weekday = Yii::$app->request->post('weekday', []);
            $weektime = Yii::$app->request->post('weektime', []);
            $scheduleArray = [];
            for ($i = 0; $i < 7; $i++) {
                if (isset($weekday[$i]) && !$weektime[$i]) {
                    Yii::$app->session->addFlash('error', 'Не указано время занятий');
                    $error = true;
                }
                $scheduleArray[$i] = isset($weekday[$i]) ? $weektime[$i] : '';
            }
            $group->scheduleData = $scheduleArray;

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
                        $this->saveGroupPupils($group, $newPupils);
                        /** @var Group $group */
                        $group = Group::find()->with(['groupPupils', 'events.members.payments'])->andWhere(['id' => $group->id])->one();
                        EventComponent::fillSchedule($group);
                        GroupComponent::calculateTeacherSalary($group);
                        foreach ($group->groupPupils as $groupPupil) {
                            MoneyComponent::setUserChargeDates($groupPupil->user, $group);
                        }

                        $transaction->commit();
                        Yii::$app->session->addFlash('success', 'Данные успешно сохранены');
                        return $this->redirect(['update', 'id' => $group->id]);
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
     * @param Group $group
     * @throws \Exception
     */
    private function fillGroupParams(Group $group) {
        $currMonth = clone $group->startDateObject;
        $currMonth->modify('first day of this month midnight');
        $nextMonth = new \DateTime('first day of next month midnight');
        $monthInterval = new \DateInterval('P1M');
        while ($currMonth < $nextMonth) {
            GroupComponent::getGroupParam($group, $currMonth);
            $currMonth->add($monthInterval);
        }
    }

    /**
     * @param Group $group
     * @param array $newPupils
     * @throws \Exception
     */
    private function saveGroupPupils(Group $group, array $newPupils) {
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
                $startDate = date_create_from_format('d.m.Y H:i:s', $pupilStartDates[$key] . ' 00:00:00');
                $pupil = User::findOne($pupilId);
                if ($pupil === null || $pupil->role != User::ROLE_PUPIL) throw new \Exception('Студент не найден');
                elseif (!$startDate) throw new \Exception('Введите корректную дату начала занятий студента ' . $pupil->name);
                if ($startDate < $group->startDateObject) $startDate = clone $group->startDateObject;

                $endDate = $pupilEndDates[$key] ? date_create_from_format('d.m.Y H:i:s', $pupilEndDates[$key] . ' 00:00:00') : null;
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
                    GroupComponent::checkPupilDates($groupPupil, $pupilsMap[$groupPupil->user_id]['startDate'], $pupilsMap[$groupPupil->user_id]['endDate']);
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
            GroupComponent::addPupilToGroup(User::findOne($pupilId), $group, $pupilData['startDate'], $pupilData['endDate'], false);
        }
    }

    /**
     * @return string|Response
     * @throws ForbiddenHttpException
     */
    public function actionMovePupil()
    {
        $this->checkAccess('manageGroups');

        $groupPupilId = Yii::$app->request->get('group_pupil_id', null);
        $groupPupil = null;
        if ($groupPupilId) {
            $groupPupil = GroupPupil::findOne(['id' => $groupPupilId, 'active' => GroupPupil::STATUS_ACTIVE]);
        }
        
        return $this->render('move_pupil', [
            'groupPupil' => $groupPupil,
            'groupList' => Group::find()->andWhere(['active' => Group::STATUS_ACTIVE])->orderBy('name')->all(),
        ]);
    }

    public function actionProcessMovePupil()
    {
        $this->checkRequestIsAjax();
        $this->checkAccess('manageGroups');
        Yii::$app->response->format = Response::FORMAT_JSON;

        $formData = Yii::$app->request->post('group-move', []);
        if (!isset($formData['id'], $formData['group_id'], $formData['date'])) {
            return self::getJsonErrorResult('Wrong request');
        }

        $groupPupil = GroupPupil::findOne($formData['id']);
        $groupTo = Group::findOne($formData['group_id']);

        if (!$groupPupil || $groupPupil->active !== GroupPupil::STATUS_ACTIVE) {
            return self::getJsonErrorResult('Студент не найден');
        }
        if (!$groupTo || $groupTo->active !== Group::STATUS_ACTIVE) {
            return self::getJsonErrorResult('Группа В не найдена');
        }
        
        $moveDate =  date_create_from_format('d.m.Y', $formData['date']);
        if (!$moveDate || $moveDate <= $groupPupil->startDateObject || $moveDate < $groupTo->startDateObject) {
            self::getJsonErrorResult('Неверная дата перевода');
        }
        
        $moveDate->modify('midnight');
        $unknownEvent = EventComponent::getUncheckedEvent($groupPupil->group, $moveDate);
        if ($unknownEvent !== null) {
            return self::getJsonErrorResult(
                'В группе ИЗ остались неотмеченные занятия, отметьте их чтобы в дальнейшем не возникало долгов: '
                . Html::a(
                    $unknownEvent->eventDateTime->format('d.m.Y'),
                    Url::to(['event/index', 'date' => $unknownEvent->eventDateTime->format('d.m.Y')])
                )
            );
        }

        $income = Payment::find()
            ->andWhere(['user_id' => $groupPupil->user_id, 'group_id' => $groupPupil->group_id])
            ->andWhere(['>', 'amount', 0])
            ->select('SUM(amount)')->scalar();
        $outcome = Payment::find()
            ->andWhere(['user_id' => $groupPupil->user_id, 'group_id' => $groupPupil->group_id])
            ->andWhere(['<', 'amount', 0])
            ->andWhere(['<', 'created_at', $moveDate->format('Y-m-d H:i:s')])
            ->select('SUM(amount)')->scalar();
        if ($income + $outcome < 0) {
            return self::getJsonErrorResult('Студент не может быть переведён пока не погасит долг - ' . (0 - $income - $outcome));
        }

        $transaction = GroupPupil::getDb()->beginTransaction();
        try {
            if (!$groupPupil->endDateObject || $groupPupil->endDateObject > $moveDate) {
                $groupPupil->date_end = $moveDate->format('Y-m-d');
                $groupPupil->moved = GroupPupil::STATUS_ACTIVE;
                $groupPupil->active = GroupPupil::STATUS_INACTIVE;
                if (!$groupPupil->save()) {
                    throw new \Exception($groupPupil->getErrorsAsString());
                }
                EventComponent::fillSchedule($groupPupil->group);
                GroupComponent::calculateTeacherSalary($groupPupil->group);
            }
            $groupPupilTo = new GroupPupil();
            $groupPupilTo->user_id = $groupPupil->user_id;
            $groupPupilTo->group_id = $groupTo->id;
            $groupPupilTo->date_start = $moveDate->format('Y-m-d');
            if (!$groupPupilTo->save()) {
                throw new \Exception($groupPupilTo->getErrorsAsString());
            }
            $groupTo->link('groupPupils', $groupPupilTo);

            GroupComponent::moveMoney($groupPupil->group, $groupTo, $groupPupil->user, $moveDate);

            $transaction->commit();
            return self::getJsonOkResult(['userId' => $groupPupil->user_id]);
        } catch (\Throwable $ex) {
            $transaction->rollBack();
            ComponentContainer::getErrorLogger()
                ->logError('group/move-pupil', $ex->getMessage(), true);
            return self::getJsonErrorResult('Server error: ' . $ex->getMessage());
        }
    }

    /**
     * @param int $userId
     * @param int $groupId
     * @return string|Response
     * @throws BadRequestHttpException
     * @throws ForbiddenHttpException
     * @throws \Throwable
     */
    public function actionMoveMoney(int $userId, int $groupId)
    {
        if (!Yii::$app->user->can('moveMoney')) throw new ForbiddenHttpException('Access denied!');

        $user = User::findOne($userId);
        $group = Group::findOne($groupId);
        if (!$user) throw new BadRequestHttpException('User not found');
        if (!$group) throw new BadRequestHttpException('Group not found');
        if (GroupPupil::find()->andWhere(['user_id' => $user->id, 'group_id' => $group->id, 'active' => GroupPupil::STATUS_ACTIVE, 'date_end' => null])->one()) {
            throw new BadRequestHttpException('Студент ещё занимается в группе');
        }
        /** @var GroupPupil[] $groupPupils */
        $groupPupils = GroupPupil::find()->andWhere(['user_id' => $user->id, 'group_id' => $group->id])->all();
        if (count($groupPupils) == 0) throw new BadRequestHttpException('Pupil not found');

        $moneyLeft = Payment::find()->andWhere(['user_id' => $user->id, 'group_id' => $group->id])->select('SUM(amount)')->scalar();
        if ($moneyLeft <= 0) throw new BadRequestHttpException('Не осталось денег для перевода');

        $dateEnd = null;
        foreach ($groupPupils as $groupPupil) {
            if (!$dateEnd || $groupPupil->endDateObject > $dateEnd) {
                $dateEnd = $groupPupil->endDateObject;
            }
        }
        $unknownEvent = EventComponent::getUncheckedEvent($group, $dateEnd);
        if ($unknownEvent !== null) {
            throw new BadRequestHttpException("В группе {$group->name} остались неотмеченные занятия, отметьте их чтобы в дальнейшем не возникало долгов: "
                . Html::a(
                    $unknownEvent->eventDateTime->format('d.m.Y'),
                    Url::to(['event/index', 'date' => $unknownEvent->eventDateTime->format('d.m.Y')])
                )
            );
        }

        if (\Yii::$app->request->isPost) {
            $groupToId = \Yii::$app->request->post('group_to');
            if (!$groupToId) throw new BadRequestHttpException('No group selected');
            $groupTo = Group::findOne($groupToId);
            if (!$groupTo) throw new BadRequestHttpException('Group not found');
            $groupPupilsTo = GroupPupil::find()->andWhere(['user_id' => $user->id, 'group_id' => $groupTo->id, 'active' => GroupPupil::STATUS_ACTIVE])->all();
            if (count($groupPupilsTo) == 0) throw new BadRequestHttpException('Pupil in destination group is not found');

            $transaction = \Yii::$app->db->beginTransaction();
            try {
                GroupComponent::moveMoney($group, $groupTo, $user);

                foreach ($groupPupils as $groupPupil) {
                    $groupPupil->active = GroupPupil::STATUS_INACTIVE;
                    $groupPupil->save();
                }
                $transaction->commit();
                \Yii::$app->session->addFlash('success', 'Средства перенесены');
            } catch (\Throwable $exception) {
                $transaction->rollBack();
                \Yii::$app->session->addFlash('error', $exception->getMessage());
                throw $exception;
            }
        }

        return $this->render('move_money', [
            'group' => $group,
            'user' => $user,
            'moneyLeft' => $moneyLeft,
            'groupList' => Group::find()
                ->joinWith('groupPupils')
                ->andWhere([GroupPupil::tableName() . '.active' => GroupPupil::STATUS_ACTIVE, GroupPupil::tableName() . '.user_id' => $user->id])
                ->andWhere(Group::tableName() . '.id != :groupFrom', ['groupFrom' => $group->id])
                ->distinct(true)
                ->orderBy(Group::tableName() . '.name')->all(),
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
        /** @var GroupPupil $groupPupil */
        $groupPupil = GroupPupil::find()->andWhere(['id' => $formData['id'], 'active' => GroupPupil::STATUS_INACTIVE])->one();
        if (!$groupPupil) {
            return self::getJsonErrorResult('Pupil not found');
        }
        /** @var Group $groupTo */
        $groupTo = Group::findOne($formData['groupId']);
        if (!$groupTo) {
            return self::getJsonErrorResult('Group not found');
        }
        
        $groupPupilsTo = GroupPupil::find()->andWhere(['user_id' => $groupPupil->user_id, 'group_id' => $groupTo->id, 'active' => GroupPupil::STATUS_ACTIVE])->all();
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
            GroupComponent::moveMoney($groupPupil->group, $groupTo, $groupPupil->user);
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
        /** @var GroupPupil $groupPupil */
        $groupPupil = GroupPupil::find()->andWhere(['id' => $formData['id'], 'active' => GroupPupil::STATUS_ACTIVE])->one();
        if (!$groupPupil) {
            return self::getJsonErrorResult('Pupil not found');
        }
        
        $endDate =  date_create_from_format('d.m.Y', $formData['date']);
        if (!$endDate || $endDate <= $groupPupil->startDateObject) {
            self::getJsonErrorResult('Неверная дата');
        }

        $endDate->modify('midnight');
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
        $groupPupil->active = GroupPupil::STATUS_INACTIVE;
        $groupPupil->end_reason = $formData['reasonId'];
        $groupPupil->comment = $formData['reasonComment'];
        if (!$groupPupil->save()) {
            return self::getJsonErrorResult($groupPupil->getErrorsAsString());
        }
        EventComponent::fillSchedule($groupPupil->group);
        GroupComponent::calculateTeacherSalary($groupPupil->group);
        
        return self::getJsonOkResult(['userId' => $groupPupil->user_id]);
    }

    /**
     * @param int|null $pupilId
     * @return Response
     * @throws BadRequestHttpException
     */
    public function actionListJson($pupilId = null)
    {
        $this->checkRequestIsAjax();
        $jsonData = [];
        if ($pupilId) {
            $pupil = User::findOne($pupilId);
            if ($pupil) {
                foreach ($pupil->activeGroupPupils as $groupPupil) {
                    $groupData = [
                        'id' => $groupPupil->group_id,
                        'date_start' => $groupPupil->startDateObject->format('d.m.Y'),
                        'date_end' => $groupPupil->date_end ? $groupPupil->endDateObject->format('d.m.Y') : '',
                    ];
                    $jsonData[] = $groupData;
                }
            }
        } else {
            /** @var Group[] $groups */
            $groups = Group::find()->andWhere(['active' => Group::STATUS_ACTIVE])->all();
            foreach ($groups as $group) {
                $groupData = $group->toArray(['id', 'name', 'lesson_price', 'lesson_price_discount']);
                $groupParam = GroupParam::findByDate($group, new \DateTime());
                if (!$groupParam) {
                    $groupParam = new GroupParam();
                    $groupParam->lesson_price = $group->lesson_price;
                    $groupParam->lesson_price_discount = $group->lesson_price_discount;
                    $groupParam->schedule = $group->schedule;
                }
                $groupData['month_price'] = $groupParam->priceMonth;
                $groupData['discount_price'] = $groupParam->price3Month;
                $jsonData[] = $groupData;
            }
        }

        return $this->asJson($jsonData);
    }

    /**
     * Finds the Group model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Group the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Group::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }
}

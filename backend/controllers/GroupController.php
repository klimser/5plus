<?php

namespace backend\controllers;

use backend\components\EventComponent;
use backend\components\MoneyComponent;
use backend\models\Group;
use backend\models\GroupPupil;
use backend\models\GroupSearch;
use backend\models\GroupType;
use backend\models\Payment;
use backend\models\User;
use backend\components\GroupComponent;
use common\components\helpers\Calendar;
use common\models\Subject;
use common\models\Teacher;
use yii;
use yii\web\NotFoundHttpException;

/**
 * GroupController implements the CRUD actions for Teacher model.
 */
class GroupController extends AdminController
{
    protected $accessRule = 'manageGroups';

    /**
     * Lists all Group models.
     * @return mixed
     */
    public function actionIndex()
    {

//        /** @var User[] $users */
//        $users = User::find()->andWhere(['role' => User::ROLE_PUPIL])->all();
//
//        foreach ($users as $user) MoneyComponent::setUserChargeDates($user);

//        $user = User::findOne(3236);
//        MoneyComponent::setUserChargeDates($user);

//        $groupPupil = GroupPupil::findOne(885);
//        GroupComponent::rechargeGroupPupil($groupPupil);
//        MoneyComponent::recalculateDebt($groupPupil->user);
//        MoneyComponent::setUserChargeDates($groupPupil->user);
//        GroupComponent::calculateTeacherSalary($groupPupil->group);

//        $group = Group::findOne(54);
//        EventComponent::fillSchedule($group);
//        foreach ($group->groupPupils as $groupPupil) {
//            GroupComponent::rechargeGroupPupil($groupPupil);
//            MoneyComponent::recalculateDebt($groupPupil->user);
//            MoneyComponent::setUserChargeDates($groupPupil->user);
//        }
//        GroupComponent::calculateTeacherSalary($group);




        return $this->renderList(['active' => Group::STATUS_ACTIVE]);
    }

    public function actionInactive()
    {
        return $this->renderList(['active' => Group::STATUS_INACTIVE]);
    }

    private function renderList($filter)
    {
        $searchModel = new GroupSearch();
        $searchParams = array_key_exists('GroupSearch', Yii::$app->request->queryParams) ? Yii::$app->request->queryParams['GroupSearch'] : [];
        $dataProvider = $searchModel->search(['GroupSearch' => array_merge($searchParams, $filter)]);

        return $this->render('index', [
            'dataProvider' => $dataProvider,
            'searchModel' => $searchModel,
            'subjectMap' => yii\helpers\ArrayHelper::map(Subject::find()->select(['id', 'name'])->asArray()->all(), 'id', 'name'),
            'teacherMap' => yii\helpers\ArrayHelper::map(Teacher::find()->select(['id', 'name'])->asArray()->all(), 'id', 'name'),
        ]);
    }

    /**
     * Creates a new Group model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
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
        $group = $this->findModel($id);

        return $this->render('view', [
            'group' => $group,
        ]);
    }

    /**
     * @param Group $group
     * @return string|yii\web\Response
     * @throws yii\db\Exception
     */
    private function processGroupData(Group $group)
    {
        if (Yii::$app->request->isPost) {
            $group->load(Yii::$app->request->post());
            $groupVal = Yii::$app->request->post('Group', []);
            $newPupils = Yii::$app->request->post('pupil', []);
            $error = false;
            if (array_key_exists('date_start', $groupVal) && $groupVal['date_start']) {
                if (!empty($group->groupPupils)) {
                    Yii::$app->session->addFlash('error', 'Вы не можете изменять дату начала занятий группы');
                    $error = true;
                } else $group->date_start = date_create_from_format('d.m.Y', $groupVal['date_start'])->format('Y-m-d');
            }
            if (array_key_exists('date_end', $groupVal) && $groupVal['date_end']) {
                $group->date_end = date_create_from_format('d.m.Y', $groupVal['date_end'])->format('Y-m-d');
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

            $weekday = Yii::$app->request->post('weekday', []);
            $weektime = Yii::$app->request->post('weektime', []);
            $weekdayString = '';
            $scheduleArray = [];
            for ($i = 0; $i < 6; $i++) {
                $weekdayString .= isset($weekday[$i]) ? '1' : '0';
                if (isset($weekday[$i]) && !$weektime[$i]) {
                    Yii::$app->session->addFlash('error', 'Не указано время занятий');
                    $error = true;
                }
                $scheduleArray[$i] = isset($weekday[$i]) ? $weektime[$i] : '';
            }
            $group->scheduleData = $scheduleArray;
            $group->weekday = $weekdayString;

            if (!$error) {
                $transaction = Yii::$app->db->beginTransaction();
                try {
                    if ($group->save()) {
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
                    Yii::$app->errorLogger->logError('group/update', $ex->getMessage(), true);
                    Yii::$app->session->addFlash('error', 'Внутренняя ошибка сервера: ' . $ex->getMessage());
                }
            }
        }
        return $this->render('update', [
            'group' => $group,
            'groupTypes' => GroupType::find()->orderBy('name')->all(),
            'subjects' => Subject::find()->orderBy('name')->with('teachers')->all(),
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
                    $groupPupil->date_start = $pupilsMap[$groupPupil->user_id]['startDate']->format('Y-m-d');
                    $groupPupil->date_end = $pupilsMap[$groupPupil->user_id]['endDate'] ? $pupilsMap[$groupPupil->user_id]['endDate']->format('Y-m-d') : null;

                    if (!$groupPupil->save()) throw new \Exception($groupPupil->getErrorsAsString());

                    MoneyComponent::rechargePupil($groupPupil->user, $group);
                }
                unset($pupilsMap[$groupPupil->user_id]);
            }
        }

        foreach ($pupilsMap as $pupilId => $pupilData) {
            $groupPupil = new GroupPupil();
            $groupPupil->user_id = $pupilId;
            $groupPupil->group_id = $group->id;
            $groupPupil->date_start = $pupilData['startDate']->format('Y-m-d');
            $groupPupil->date_end = $pupilData['endDate'] ? $pupilData['endDate']->format('Y-m-d') : null;
            if (!$groupPupil->save()) throw new \Exception('Server error: ' . $groupPupil->getErrorsAsString());
            $group->link('groupPupils', $groupPupil);
        }
    }

    /**
     * @return string|yii\web\Response
     */
    public function actionMovePupil()
    {
        return $this->render('move_pupil', [
            'userId' => Yii::$app->request->get('user', 0),
            'groupId' => Yii::$app->request->get('group', 0),
            'groupList' => Group::find()->andWhere(['active' => Group::STATUS_ACTIVE])->all(),
        ]);
    }

    /**
     * @return yii\web\Response
     * @throws yii\web\BadRequestHttpException
     */
    public function actionProcessMovePupil()
    {
        if (!Yii::$app->request->isAjax) throw new yii\web\BadRequestHttpException('Request is not AJAX');

        $userId = Yii::$app->request->post('user_id', 0);
        $groupFromId = Yii::$app->request->post('group_from', 0);
        $groupToId = Yii::$app->request->post('group_to', 0);
        $moveDate =  date_create_from_format('d.m.Y', Yii::$app->request->post('move_date', ''));

        $user = User::findOne($userId);
        $groupFrom = Group::findOne($groupFromId);
        $groupTo = Group::findOne($groupToId);

        if (!$user) return $this->asJson(self::getJsonErrorResult('Студент не найден'));
        if (!$groupFrom) return $this->asJson(self::getJsonErrorResult('Группа ИЗ не найдена'));
        if (!$groupTo) return $this->asJson(self::getJsonErrorResult('Группа ИЗ не найдена'));
        if (!$moveDate) return $this->asJson(self::getJsonErrorResult('Неверная дата перевода'));

        $groupPupilFrom = GroupPupil::findOne(['group_id' => $groupFrom->id, 'user_id' => $user->id, 'active' => GroupPupil::STATUS_ACTIVE]);
        if (!$groupPupilFrom) return $this->asJson(self::getJsonErrorResult('Студент не занимается в группе ИЗ'));

        $groupPupilTo = GroupPupil::findOne(['group_id' => $groupTo->id, 'user_id' => $user->id, 'active' => GroupPupil::STATUS_ACTIVE]);
        if ($groupPupilTo) return $this->asJson(self::getJsonErrorResult('Студент уже занимается в группе В'));

        $transaction = GroupPupil::getDb()->beginTransaction();
        try {
            $moveDate->modify('midnight');
            if (!$groupPupilFrom->endDateObject || $groupPupilFrom->endDateObject > $moveDate) {
                $groupPupilFrom->date_end = $moveDate->format('Y-m-d');
                if ($groupPupilFrom->date_end < date('Y-m-d')) $groupPupilFrom->active = GroupPupil::STATUS_INACTIVE;
                if ($groupPupilFrom->save()) {
                    EventComponent::fillSchedule($groupFrom);
                    GroupComponent::calculateTeacherSalary($groupFrom);
                    $balance = Payment::find()
                        ->andWhere(['user_id' => $user->id, 'group_id' => $groupFrom->id])
                        ->select('SUM(amount)')->scalar();
                    if ($balance > 0) {
                        $groupPupilFrom->date_charge_till = $moveDate->format('Y-m-d H:i:s');

                    }
                    MoneyComponent::setUserChargeDates($user, $groupFrom);
                } else throw new \Exception($groupPupilFrom->getErrorsAsString());
            }
            $groupPupilTo = new GroupPupil();
            $groupPupilTo->user_id = $user->id;
            $groupPupilTo->group_id = $groupTo->id;
            $groupPupilTo->date_start = $moveDate->format('Y-m-d');
            if (!$groupPupilTo->save()) throw new \Exception('Server error: ' . $groupPupilTo->getErrorsAsString());
            $groupTo->link('groupPupils', $groupPupilTo);

            EventComponent::fillSchedule($groupTo);

            /** @var Payment[] $discountPayments */
            $discountPayments = Payment::find()
                ->andWhere(['group_pupil_id' => $groupPupilFrom->id])
                ->andWhere(['>', 'amount', 0])
                ->all();

            foreach ($discountPayments as $discountPayment) {
                if ($discountPayment->paymentsSum < $discountPayment->amount) {
                    $diff = $discountPayment->amount - $discountPayment->paymentsSum;
                    if ($discountPayment->paymentsSum) {
                        MoneyComponent::decreasePayment($discountPayment, $discountPayment->paymentsSum);

                        $newPayment = new Payment();
                        $newPayment->user_id = $discountPayment->user_id;
                        $newPayment->admin_id = Yii::$app->user->id;
                        $newPayment->group_pupil_id = $groupPupilTo->id;
                        $newPayment->amount = $diff;
                        $newPayment->created_at = $moveDate->format('Y-m-d H:i:s');
                        $newPayment->comment = 'Автоперевод средств при переводе студента из группы ' . $groupFrom->name . ' в группу ' . $groupTo->name;
                        MoneyComponent::registerIncome($newPayment);
                    } else {
                        $discountPayment->group_pupil_id = $groupPupilTo->id;
                        $discountPayment->save();
                        GroupComponent::rechargeGroupPupil($groupPupilTo);
                        if (!MoneyComponent::recalculateDebt($user, $groupPupilTo->group)) throw new \Exception('Error on pupil\'s debt calculation');
                    }
                }
            }
            GroupComponent::calculateTeacherSalary($groupTo);
            MoneyComponent::setUserChargeDates($user, $groupTo);

            $transaction->commit();
            return $this->asJson(self::getJsonOkResult());
        } catch (\Throwable $ex) {
            $transaction->rollBack();
            Yii::$app->errorLogger->logError('group/move-pupil', $ex->getMessage(), true);
            return $this->asJson(self::getJsonErrorResult($ex->getMessage()));
        }
    }

        /**
     * @param int|null $pupilId
     * @return yii\web\Response
     */
    public function actionListJson($pupilId = null) {
        $jsonData = [];
        if (Yii::$app->request->isAjax) {
            if ($pupilId) {
                $jsonData = [];
                $pupil = User::findOne($pupilId);
                if ($pupil) {
                    foreach ($pupil->pupilGroups as $groupPupil) {
                        if ($groupPupil->active == GroupPupil::STATUS_ACTIVE && $groupPupil->group->active == Group::STATUS_ACTIVE) {
                            $elem = $groupPupil->group->toArray(['id', 'name', 'lesson_price', 'lesson_price_discount']);
                            $elem['startDate'] = $groupPupil->startDateObject->format('d.m.Y');
                            $elem['endDate'] = $groupPupil->endDateObject ? $groupPupil->endDateObject->format('d.m.Y') : '';
                            $jsonData[] = $elem;
                        }
                    }
                }
            } else {
                $jsonData = Group::find()->andWhere(['active' => Group::STATUS_ACTIVE])->with(['pupils' => function (yii\db\ActiveQuery $query) {
                    $query->select('id');
                }])->asArray()->select(['id', 'subject_id', 'teacher_id', 'name'])->all();
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

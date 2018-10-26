<?php

namespace backend\controllers;

use backend\components\EventComponent;
use backend\components\GroupComponent;
use backend\components\MoneyComponent;
use backend\components\UserComponent;
use backend\models\Contract;
use backend\models\EventMember;
use backend\models\Group;
use backend\models\GroupPupil;
use backend\models\Payment;
use backend\models\User;
use backend\models\UserSearch;
use common\components\Action;
use common\components\helpers\Money;
use yii;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\BadRequestHttpException;

/**
 * PageController implements the CRUD actions for Page model.
 */
class UserController extends AdminController
{
    /**
     * Lists all User models.
     * @return mixed
     * @throws ForbiddenHttpException
     */
    public function actionIndex()
    {
        if (!Yii::$app->user->can('manageUsers')) throw new ForbiddenHttpException('Access denied!');

        $searchModel = new UserSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'dataProvider' => $dataProvider,
            'searchModel' => $searchModel,
            'firstLetter' => mb_strtoupper(Yii::$app->request->get('letter', 'all'), 'UTF-8'),
            'selectedYear' => Yii::$app->request->get('year', -1),
            'canManageEmployees' => Yii::$app->user->can('manageEmployees'),
        ]);
    }

    /**
     * Creates a new Pupil.
     * If creation is successful, the browser will be redirected to the 'index' page.
     * @return mixed
     * @throws ForbiddenHttpException
     * @throws \Throwable
     * @throws yii\db\Exception
     */
    public function actionCreatePupil()
    {
        if (!Yii::$app->user->can('manageUsers')) throw new ForbiddenHttpException('Access denied!');

        $parent = new User(['scenario' => User::SCENARIO_USER]);
        $company = new User(['scenario' => User::SCENARIO_USER]);
        $pupil = new User(['scenario' => User::SCENARIO_USER]);
        $groupData = [];
        $paymentData = [];
        $contractData = [];

        if (Yii::$app->request->isPost) {
            User::loadMultiple(['parent' => $parent, 'company' => $company, 'pupil' => $pupil], Yii::$app->request->post());
            $pupil->role = User::ROLE_PUPIL;
            $groupData = Yii::$app->request->post('group', []);
            $paymentData = Yii::$app->request->post('payment', []);
            $contractData = Yii::$app->request->post('contract', []);

            $transaction = User::getDb()->beginTransaction();
            try {
                $personType = Yii::$app->request->post('person_type', User::ROLE_PARENTS);
                switch ($personType) {
                    case User::ROLE_PARENTS:
                        $parent = $this->processParent($parent, 'parent', $personType);
                        $pupil->parent_id = $parent->id;
                        break;
                    case User::ROLE_COMPANY:
                        $company = $this->processParent($company, 'company', $personType);
                        $pupil->parent_id = $company->id;
                        break;
                }

                if ($pupil->save()) {
                    $transaction->commit();
                    Yii::$app->session->addFlash('success', 'Добавлено');
                    UserComponent::clearSearchCache();

                    $addGroup = array_key_exists('add', $groupData) && $groupData['add'];
                    $addPayment = array_key_exists('add', $paymentData) && $paymentData['add'];
                    $addContract = array_key_exists('add', $contractData) && $contractData['add'];

                    $groupPupil = null;
                    if ($addGroup) {
                        $groupPupil = $this->addPupilToGroup($pupil, $groupData);

                        if ($addPayment) {
                            $this->addPupilMoneyIncome($groupPupil, $paymentData);
                            MoneyComponent::setUserChargeDates($pupil, $groupPupil->group);
                        }
                    }
                    if (!$addPayment && $addContract) {
                        $this->addPupilContract($pupil, $groupPupil, $contractData);
                    }

                    return $this->redirect(['index']);
                } else {
                    $pupil->moveErrorsToFlash();
                    $transaction->rollBack();
                }
            } catch (\Throwable $e) {
                $transaction->rollBack();
                \Yii::$app->session->addFlash('error', $e->getMessage());
            }
        }

        return $this->render('create-pupil', [
            'parent' => $parent,
            'company' => $company,
            'pupil' => $pupil,
            'groupData' => $groupData,
            'paymentData' => $paymentData,
            'contractData' => $contractData,
            'groups' => Group::find()->andWhere(['active' => Group::STATUS_ACTIVE])->orderBy(['name' => SORT_ASC])->all(),
            'existedParents' => User::find()->andWhere(['role' => User::ROLE_PARENTS])->orderBy(['name' => SORT_ASC])->all(),
            'existedCompanies' => User::find()->andWhere(['role' => User::ROLE_COMPANY])->orderBy(['name' => SORT_ASC])->all(),
        ]);
    }

    /**
     * @param User $parent
     * @param string $prefix
     * @param int $personType
     * @return User
     * @throws \Exception
     */
    private function processParent(User $parent, string $prefix, int $personType): User
    {
        $parentType = Yii::$app->request->post($prefix . '_type', 'new');
        switch ($parentType) {
            case 'exist':
                $parentId = Yii::$app->request->post($prefix . '_exists');
                if (!$parentId) throw new \Exception("Choose $prefix from the list");
                $parent = $this->findModel($parentId);
                if ($parent->role != $personType || $parent->status == User::STATUS_LOCKED) throw new \Exception('Parents not found');
                break;
            case 'new':
                $parent->role = $personType;
                if (!$parent->save()) {
                    $parent->moveErrorsToFlash();
                    throw new \Exception('Server error');
                }
                break;
        }
        return $parent;
    }

    /**
     * @param User $pupil
     * @param array $groupData
     * @return GroupPupil
     * @throws \Exception
     */
    private function addPupilToGroup(User $pupil, array $groupData): GroupPupil
    {
        $group = Group::findOne($groupData['id']);
        $startDate = date_create_from_format('d.m.Y', $groupData['date_from']);
        /** @var \DateTime $endDate */
        $endDate = date_create_from_format('d.m.Y', $groupData['date_to']) ?: null;
        if (!$group || !$startDate || ($endDate && $endDate < $startDate)) {
            throw new \Exception('Ученик не добавлен в группу, введены некорректные значения даты начала и завершения занятий!');
        } else {
            $startDate->modify('midnight');
            if ($group->endDateObject && $startDate > $group->endDateObject) {
                throw new \Exception('Ученик не добавлен в группу, выбрана дата начала занятий позже завершения занятий группы!');
            } else {
                $groupPupil = new GroupPupil();
                $groupPupil->user_id = $pupil->id;
                $groupPupil->group_id = $group->id;
                $groupPupil->date_start = $startDate < $group->startDateObject ? $group->date_start : $startDate->format('Y-m-d');
                if ($endDate) {
                    $endDate->modify('midnight');
                    if ($group->endDateObject && $endDate > $group->endDateObject) $endDate = $group->endDateObject;
                    if ($endDate < $group->startDateObject) $endDate = $group->startDateObject;
                    $groupPupil->date_end = $endDate->format('Y-m-d');
                }
                if (!$groupPupil->save()) {
                    Yii::$app->errorLogger->logError('user/pupil-to-group', $groupPupil->getErrorsAsString(), true);
                    throw new \Exception('Внутренняя ошибка сервера: ' . $groupPupil->getErrorsAsString());
                } else {
                    $group->link('groupPupils', $groupPupil);

                    EventComponent::fillSchedule($group);
                    GroupComponent::calculateTeacherSalary($group);

                    Yii::$app->session->addFlash('success', 'Ученик добавлен в группу');
                    return $groupPupil;
                }
            }
        }
    }

    /**
     * @param GroupPupil $groupPupil
     * @param array $paymentData
     * @return int
     * @throws \Exception
     */
    private function addPupilMoneyIncome(GroupPupil $groupPupil, array $paymentData)
    {
        $amount = intval($paymentData['amount']);
        $isDiscount = array_key_exists('discount', $paymentData) && $paymentData['discount'];
        if ($amount > 0) {
            $payment = new Payment();
            $payment->admin_id = Yii::$app->user->id;
            $payment->user_id = $groupPupil->user_id;
            $payment->group_id = $groupPupil->group_id;
            $payment->amount = $amount;
            $payment->discount = $isDiscount ? Payment::STATUS_ACTIVE : Payment::STATUS_INACTIVE;
            $payment->comment = $paymentData['comment'] ?: null;
            $payment->created_at = $groupPupil->startDateObject->format('Y-m-d') . ' ' . date('H:i:s');

            if ($paymentData['contract']) {
                $contract = $this->addPupilContract(
                    $groupPupil->user,
                    $groupPupil,
                    [
                        'number' => $paymentData['contract'],
                        'amount' => $payment->amount,
                        'discount' => $isDiscount,
                        'group' => $groupPupil->group_id
                    ]
                );
                $contract->paid_at = $payment->created_at;
                $contract->paid_admin_id = $payment->admin_id;
                $contract->status = Contract::STATUS_PAID;
                $contract->payment_type = Contract::PAYMENT_TYPE_MANUAL;
                if (!$contract->save()) throw new \Exception('Не удалось создать договор: ' . $contract->getErrorsAsString());
                $contract->link('payments', $payment);

                \Yii::$app->actionLogger->log(
                    $contract->user,
                    Action::TYPE_CONTRACT_PAID,
                    $contract->amount,
                    $contract->group,
                    null
                );
            }

            try {
                $paymentId = MoneyComponent::registerIncome($payment);
                Yii::$app->session->addFlash('success', 'Внесение денег зарегистрировано, номер транзакции: ' . $paymentId);
                return $paymentId;
            } catch (\Throwable $ex) {
                throw new \Exception('Ошибка при регистрации платежа: ' . $ex->getMessage());
            }
        } else throw new \Exception('Сумма платежа не может быть отрицательной');
    }

    /**
     * @param User $pupil
     * @param GroupPupil|null $groupPupil
     * @param array $contractData
     * @return Contract
     * @throws \Exception
     */
    private function addPupilContract(User $pupil, ?GroupPupil $groupPupil, array $contractData)
    {
        $amount = intval($contractData['amount']);
        if ($amount <= 0) throw new \Exception('Сумма договора не может быть отрицательной');

        $contract = new Contract();
        $contract->created_admin_id = Yii::$app->user->id;
        $contract->user_id = $pupil->id;
        $contract->amount = $amount;
        $contract->discount = array_key_exists('discount', $contractData) && $contractData['discount'] ? Contract::STATUS_ACTIVE : Contract::STATUS_INACTIVE;
        $contract->created_at = date('Y-m-d H:i:s');

        $groupParam = null;
        $group = null;
        if ($groupPupil) {
            $contract->created_at = $groupPupil->date_start;
            $group = $groupPupil->group;
            if ($groupPupil->startDateObject->format('Y-m') <= date('Y-m')) {
                $groupParam = GroupComponent::getGroupParam($groupPupil->group, $groupPupil->startDateObject);
            }
        } else {
            $group = Group::findOne($contractData['group']);
        }
        $contract->group_id = $group->id;
        if ($contract->discount == Contract::STATUS_ACTIVE
            && (($groupParam && $amount < $groupParam->price3Month) || (!$groupParam && $amount < $group->price3Month))) {
            throw new \Exception('Договор по скидочной цене может быть не менее чем за 3 месяца');
        }

        if (array_key_exists('number', $contractData) && $contractData['number']) {
            $contract->number = strval($contractData['number']);
        } else {
            $numberPrefix = $contract->createDate->format('Ymd') . $pupil->id;
            $numberAffix = 1;
            while (Contract::find()->andWhere(['number' => $numberPrefix . $numberAffix])->select('COUNT(id)')->scalar() > 0) {
                $numberAffix++;
            }
            $contract->number = $numberPrefix . $numberAffix;
        }

        if (!$contract->save()) throw new \Exception('Не удалось создать договор: ' . $contract->getErrorsAsString());

        Yii::$app->session->addFlash(
            'success',
            'Договор ' . $contract->number . ' зарегистрирован '
            . '<a target="_blank" href="' . yii\helpers\Url::to(['contract/print', 'id' => $contract->id]) . '">Распечатать</a>'
        );
        \Yii::$app->actionLogger->log(
            $pupil,
            Action::TYPE_CONTRACT_ADDED,
            $contract->amount,
            $contract->group
        );
        return $contract;
    }

    /**
     * Creates a new Employee.
     * If creation is successful, the browser will be redirected to the 'update' page.
     * @return mixed
     * @throws ForbiddenHttpException
     * @throws \Exception
     * @throws yii\db\Exception
     */
    public function actionCreateEmployee()
    {
        if (!Yii::$app->user->can('manageEmployees')) throw new ForbiddenHttpException('Access denied!');

        $employee = new User(['scenario' => User::SCENARIO_ADMIN]);

        if (Yii::$app->request->isPost) {
            $employee->load(Yii::$app->request->post());
            $employee->setPassword($employee->password);
            $employee->generateAuthKey();

            if ($employee->save()) {
                Yii::$app->session->addFlash('success', 'Сотрудник добавлен');
                UserComponent::clearSearchCache();

                return $this->redirect(['update', 'id' => $employee->id]);
            } else {
                $employee->moveErrorsToFlash();
            }
        }

        return $this->render('create-employee', [
            'user' => $employee,
        ]);
    }

    /**
     * Updates an existing User model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param string $id
     * @return mixed
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException
     */
    public function actionUpdate($id = null)
    {
        $userToEdit = $id ?: Yii::$app->user->id;
        if (!Yii::$app->user->can('editUser', ['user' => $userToEdit])) throw new ForbiddenHttpException('Access denied!');

        $user = $this->findModel($userToEdit);
        $user->setScenario(in_array($user->role, [User::ROLE_PUPIL, User::ROLE_PARENTS]) ? User::SCENARIO_USER : User::SCENARIO_ADMIN);
        $isAdmin = Yii::$app->user->can('manageUsers');
        $editACL = Yii::$app->user->can('manageEmployees');
        $auth = Yii::$app->authManager;

        if (Yii::$app->request->isPost) {
            if ($user->load(Yii::$app->request->post())) {
                $fields = null;
                if (!$isAdmin) $fields = ['username', 'password'];
                if ($user->save(true, $fields)) {
                    Yii::$app->session->addFlash('success', 'Успешно обновлено');
                    if ($editACL) {
                        $newRules = Yii::$app->request->post('acl', []);
                        foreach (UserComponent::ACL_RULES as $key => $devNull) {
                            $role = $auth->getRole($key);
                            if (array_key_exists($key, $newRules)) {
                                if (!$auth->getAssignment($role->name, $user->id)) $auth->assign($role, $user->id);
                            } else {
                                if ($auth->getAssignment($role->name, $user->id)) $auth->revoke($role, $user->id);
                            }
                        }
                    }
                    UserComponent::clearSearchCache();
                    return $this->redirect(['update', 'id' => $id]);
                } else {
                    $user->moveErrorsToFlash();
                }
            } else Yii::$app->session->addFlash('error', 'Внутренняя ошибка сервера');
        }

        return $this->render('update', [
            'user' => $user,
            'isAdmin' => $isAdmin,
            'editACL' => $editACL,
            'authManager' => $auth,
        ]);
    }

    /**
     * @param $id
     * @return \yii\web\Response
     */
    public function actionChangeActive($id)
    {
        $jsonData = [];
        if (\Yii::$app->request->isAjax) {
            if (!Yii::$app->user->can('manageUsers')) $jsonData = self::getJsonErrorResult('Access denied!');
            else {
                $user = $this->findModel($id);

                $activeState = Yii::$app->request->post('active');
                $jsonData = self::getJsonOkResult(['id' => $user->id]);
                if (($user->status == User::STATUS_ACTIVE) != $activeState) {
                    $user->status = $activeState ? User::STATUS_ACTIVE : User::STATUS_LOCKED;
                    if (!$user->save()) $jsonData = self::getJsonErrorResult($user->getErrorsAsString());
                    UserComponent::clearSearchCache();
                }
            }
        }
        return $this->asJson($jsonData);
    }

    public function actionFindByPhone()
    {
        if (!Yii::$app->user->can('manageUsers')) throw new ForbiddenHttpException('Access denied!');
        if (!Yii::$app->request->isAjax) throw new BadRequestHttpException('Request is not AJAX');

        $jsonData = self::getJsonOkResult();
        $phone = preg_replace('#\D#', '', Yii::$app->request->post('phone', ''));

        if (!empty($phone) && strlen($phone) == 9) {
            $searchString = "+998$phone";
            $pupils = [];
            $searchResult = User::find()
                ->andWhere(['role' => [User::ROLE_PUPIL, User::ROLE_PARENTS]])
                ->andWhere(['!=', 'status', User::STATUS_LOCKED])
                ->andWhere('phone = :phone OR phone2 = :phone', ['phone' => $searchString])
                ->all();
            if ($searchResult) {
                /** @var User $user */
                foreach ($searchResult as $user) {
                    if ($user->role == User::ROLE_PUPIL) $pupils[] = $user;
                    else $pupils = array_merge($pupils, $user->children);
                }
            }

            if (!empty($pupils)) {
                $jsonData['pupils'] = [];
                /** @var User $pupil */
                foreach ($pupils as $pupil) {
                    $data = $pupil->toArray(['id', 'name']);
                    $data['groups'] = [];
                    foreach ($pupil->activeGroups as $group) {
                        $groupParam = GroupComponent::getGroupParam($group, new \DateTime());
                        $groupData = $group->toArray(['id', 'name', 'lesson_price', 'lesson_price_discount']);
                        $groupData['month_price'] = Money::roundThousand($groupParam->lesson_price * GroupComponent::getTotalClasses($groupParam->weekday));
                        $groupData['month_price_discount'] = Money::roundThousand($groupParam->lesson_price_discount * GroupComponent::getTotalClasses($groupParam->weekday));

                        $data['groups'][] = $groupData;
                    }
                    $jsonData['pupils'][] = $data;
                }
            }
        }

        return $this->asJson($jsonData);
    }

    /**
     * @param int|null $id
     * @param string|null $month
     * @return string
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException
     */
    public function actionSchedule($id = null, $month = null)
    {
        $userToWatch = $id ?: Yii::$app->user->id;
        if (!Yii::$app->user->can('viewSchedule', ['user' => $userToWatch])) throw new ForbiddenHttpException('Access denied!');

        $user = $this->findModel($userToWatch);
        if ($user->role != User::ROLE_PUPIL) {
            if ($user->role == User::ROLE_PARENTS) $pupilCollection = $user->children;
            else $pupilCollection = User::find()->where(['status' => User::STATUS_ACTIVE, 'role' => User::ROLE_PUPIL])->orderBy('name')->all();

            if ($pupilCollection && count($pupilCollection) == 1) {
                $user = reset($pupilCollection);
            } else {
                return $this->render('select_pupil_schedule', [
                    'pupilCollection' => $pupilCollection,
                    'month' => $month,
                    'user' => $user,
                ]);
            }
        }
        if ($month) $eventMonth = \DateTime::createFromFormat('Y-m', $month);
        if (!isset($eventMonth) || !$eventMonth) $eventMonth = new \DateTime();
        $endDate = clone($eventMonth);
        $endDate->add(new \DateInterval('P1M'));
        $eventMemberCollection = EventMember::find()
            ->innerJoinWith('event')
            ->where(['user_id' => $user->id])
            ->andWhere('event_date > :startDate', [':startDate' => $eventMonth->format('Y-m') . '-01 00:00:00'])
            ->andWhere('event_date < :endDate', [':endDate' => $endDate->format('Y-m') . '-01 00:00:00'])
            ->all();
        $groupIdSet = [];
        $eventMap = [];
        /** @var EventMember $eventMember */
        foreach ($eventMemberCollection as $eventMember) {
            $day = $eventMember->event->eventDateTime->format('Y-m-d');
            if (!isset($eventMap[$day])) $eventMap[$day] = [];
            $eventMap[$day][$eventMember->event->eventTime] = $eventMember;
            if ($eventMember->event->group_id) $groupIdSet[$eventMember->event->group_id] = true;
        }

        $groupMap = [];
        foreach ($groupIdSet as $groupId => $devNull) {
            $groupMap[$groupId] = [
                'group' => Group::findOne($groupId),
                'payments' => Payment::find()
                    ->andWhere('created_at >= :from', ['from' => $eventMonth->format('Y-m-d H:i:s')])
                    ->andWhere('created_at < :to', ['to' => $endDate->format('Y-m-d H:i:s')])
                    ->andWhere(['group_id' => $groupId, 'user_id' => $user->id])
                    ->all(),
            ];
        }

        return $this->render('schedule', [
            'eventMonth' => $eventMonth,
            'user' => $user,
            'eventMap' => $eventMap,
            'groupMap' => $groupMap,
        ]);
    }

    /**
     * @param int|null $id
     * @return string
     * @throws ForbiddenHttpException
     */
    public function actionMoneyHistory($id = null)
    {
        $userToWatch = $id ?: Yii::$app->user->id;
        if (!Yii::$app->user->can('viewSchedule', ['user' => $userToWatch])) throw new ForbiddenHttpException('Access denied!');
        $user = $this->findModel($userToWatch);

        if ($user->role != User::ROLE_PUPIL) {
            if ($user->role == User::ROLE_PARENTS) $pupilCollection = $user->children;
            else $pupilCollection = User::find()->where(['status' => User::STATUS_ACTIVE, 'role' => User::ROLE_PUPIL])->orderBy('name')->all();

            if ($pupilCollection && count($pupilCollection) == 1) {
                $user = reset($pupilCollection);
            } else {
                return $this->render('select_pupil_money', [
                    'pupilCollection' => $pupilCollection,
                    'user' => $user,
                ]);
            }
        }

        $pager = new yii\data\Pagination(['pageSize' => 30, 'totalCount' => Payment::find()->andWhere(['user_id' => $userToWatch])->count()]);

        return $this->render('money-history', [
            'user' => $user,
            'payments' => Payment::find()->andWhere(['user_id' => $userToWatch])->limit($pager->limit)->offset($pager->offset)->orderBy(['created_at' => SORT_DESC])->all(),
            'pager' => $pager,
        ]);
    }


    /**
     * @return yii\web\Response
     */
    public function actionPupils() {
        $jsonData = [];
        if (Yii::$app->request->isAjax) {
            $jsonData = User::find()->where(['status' => User::STATUS_ACTIVE, 'role' => User::ROLE_PUPIL])->orderBy('name')->select(['id', 'name'])->asArray()->all();
        }
        return $this->asJson($jsonData);
    }

    /**
     * Finds the User model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param string $id
     * @return User the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = User::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }
}
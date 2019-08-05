<?php

namespace backend\controllers;

use backend\models\TeacherSubjectLink;
use backend\models\WelcomeLesson;
use common\components\GroupComponent;
use common\components\MoneyComponent;
use backend\components\UserComponent;
use common\models\Company;
use common\models\Contract;
use backend\models\EventMember;
use common\models\Group;
use common\models\GroupPupil;
use common\models\Payment;
use common\models\Subject;
use common\models\Teacher;
use common\models\User;
use common\models\UserSearch;
use yii\data\Pagination;
use yii\helpers\Url;
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
        if (!\Yii::$app->user->can('manageUsers')) throw new ForbiddenHttpException('Access denied!');

        $searchModel = new UserSearch();
        $dataProvider = $searchModel->search(\Yii::$app->request->queryParams);

        return $this->render('index', [
            'dataProvider' => $dataProvider,
            'searchModel' => $searchModel,
            'firstLetter' => mb_strtoupper(\Yii::$app->request->get('letter', 'all'), 'UTF-8'),
            'selectedYear' => \Yii::$app->request->get('year', -1),
            'canManageEmployees' => \Yii::$app->user->can('manageEmployees'),
        ]);
    }

    /**
     * Creates a new Pupil.
     * If creation is successful, the browser will be redirected to the 'index' page.
     * @return mixed
     * @throws ForbiddenHttpException
     * @throws \Throwable
     * @throws \yii\db\Exception
     */
    public function actionCreatePupil()
    {
        if (!\Yii::$app->user->can('manageUsers')) throw new ForbiddenHttpException('Access denied!');

        $parent = new User(['scenario' => User::SCENARIO_USER]);
        $parentCompany = new User(['scenario' => User::SCENARIO_USER]);
        $pupil = new User(['scenario' => User::SCENARIO_USER]);
        $groupData = [];
        $paymentData = [];
        $contractData = [];
        $welcomeLessonData = [];
        $amount = 0;
        $companyId = null;

        if (\Yii::$app->request->isPost) {
            User::loadMultiple(['parent' => $parent, 'parentCompany' => $parentCompany, 'pupil' => $pupil], \Yii::$app->request->post());
            $pupil->role = User::ROLE_PUPIL;
            $groupData = \Yii::$app->request->post('group', []);
            $welcomeLessonData = \Yii::$app->request->post('welcome_lesson', []);
            $paymentData = \Yii::$app->request->post('payment', []);
            $contractData = \Yii::$app->request->post('contract', []);
            $amount = intval(\Yii::$app->request->post('amount', 0));
            $companyId = \Yii::$app->request->post('company_id');

            $transaction = User::getDb()->beginTransaction();
            try {
//                if (UserComponent::isPhoneUsed(User::ROLE_PUPIL, $pupil->phone, $pupil->phone2)) {
//                    throw new \Exception('Студент с таким номером телефона уже существует!');
//                }

                $personType = \Yii::$app->request->post('person_type', User::ROLE_PARENTS);
                switch ($personType) {
                    case User::ROLE_PARENTS:
                        $parent = $this->processParent($parent, 'parent', $personType);
                        $pupil->parent_id = $parent->id;
                        break;
                    case User::ROLE_COMPANY:
                        $parentCompany = $this->processParent($parentCompany, 'company', $personType);
                        $pupil->parent_id = $parentCompany->id;
                        break;
                }

                if ($pupil->save()) {
                    $addGroup = array_key_exists('add', $groupData) && $groupData['add'];
                    $addWelcomeLesson = array_key_exists('add', $welcomeLessonData) && $welcomeLessonData['add'];
                    $addPayment = array_key_exists('add', $paymentData) && $paymentData['add'];
                    $addContract = array_key_exists('add', $contractData) && $contractData['add'];

                    $company = Company::findOne($companyId);
                    if (($addPayment || $addContract) && !$company) throw new \Exception('Не выбран учебный центр!');

                    $groupPupil = null;
                    if ($addGroup) {
                        $groupPupil = $this->addPupilToGroup($pupil, $groupData);

                        if (\Yii::$app->user->can('moneyManagement') && $addPayment) {
                            $this->addPupilMoneyIncome($company, $groupPupil, $amount, $paymentData);
                            MoneyComponent::setUserChargeDates($pupil, $groupPupil->group);
                        }
                    } elseif ($addWelcomeLesson) {
                        $this->addPupilToWelcomeLesson($pupil, $welcomeLessonData);
                    }
                    if (!$addPayment && $addContract) {
                        $contract = MoneyComponent::addPupilContract(
                            $company,
                            $pupil,
                            $amount,
                            $groupPupil ? $groupPupil->group : Group::findOne($contractData['group'])
                        );

                        \Yii::$app->session->addFlash(
                            'success',
                            'Договор ' . $contract->number . ' зарегистрирован '
                            . '<a target="_blank" href="' . Url::to(['contract/print', 'id' => $contract->id]) . '">Распечатать</a>'
                        );
                    }

                    $transaction->commit();
                    \Yii::$app->session->addFlash('success', 'Добавлено');
                    UserComponent::clearSearchCache();

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
            'parentCompany' => $parentCompany,
            'pupil' => $pupil,
            'groupData' => $groupData,
            'paymentData' => $paymentData,
            'contractData' => $contractData,
            'welcomeLessonData' => $welcomeLessonData,
            'amount' => $amount,
            'incomeAllowed' => \Yii::$app->user->can('moneyManagement'),
            'contractAllowed' => \Yii::$app->user->can('contractManagement'),
            'companyId' => $companyId,
            'groups' => Group::find()->andWhere(['active' => Group::STATUS_ACTIVE])->orderBy(['name' => SORT_ASC])->all(),
            'subjects' => Subject::find()->andWhere(['active' => Subject::STATUS_ACTIVE])->with('subjectCategory')
                ->orderBy(['category_id' => SORT_ASC, 'name' => SORT_ASC])->all(),
            'existedParents' => User::find()->andWhere(['role' => User::ROLE_PARENTS])->orderBy(['name' => SORT_ASC])->all(),
            'existedCompanies' => User::find()->andWhere(['role' => User::ROLE_COMPANY])->orderBy(['name' => SORT_ASC])->all(),
            'companies' => Company::find()->orderBy(['second_name' => SORT_ASC])->all(),
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
        $parentType = \Yii::$app->request->post($prefix . '_type', 'new');
        switch ($parentType) {
            case 'exist':
                $parentId = \Yii::$app->request->post($prefix . '_exists');
                if (!$parentId) throw new \Exception("Choose $prefix from the list");
                $parent = $this->findModel($parentId);
                if ($parent->role != $personType || $parent->status == User::STATUS_LOCKED) throw new \Exception('Parents not found');
                break;
            case 'new':
                $parent->role = $personType;

                if (UserComponent::isPhoneUsed($personType, $parent->phone, $parent->phone2)) {
                    throw new \Exception('Родитель/компания с таким номером телефона уже существует!');
                }

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
        /** @var Group $group */
        $group = Group::find()->andWhere(['id' => $groupData['id'], 'active' => Group::STATUS_ACTIVE])->one();
        if (!$group) throw new \Exception('Группа не найдена');
        $startDate = date_create_from_format('d.m.Y', $groupData['date_from']);
        if (!$startDate) throw new \Exception('Неверная дата начала занятий');

        $groupPupil = GroupComponent::addPupilToGroup($pupil, $group, $startDate);

        \Yii::$app->session->addFlash('success', 'Ученик добавлен в группу');
        return $groupPupil;
    }

    /**
     * @param User $pupil
     * @param array $welcomeLessonData
     * @return WelcomeLesson
     * @throws \Exception
     */
    private function addPupilToWelcomeLesson(User $pupil, array $welcomeLessonData): WelcomeLesson
    {
        /** @var Subject $subject */
        $subject = Subject::find()->andWhere(['id' => $welcomeLessonData['subject_id'], 'active' => Subject::STATUS_ACTIVE])->one();
        if (!$subject) throw new \Exception('Предмет не найден');
        /** @var Teacher $teacher */
        $teacher = Teacher::find()->andWhere(['id' => $welcomeLessonData['teacher_id'], 'active' => Teacher::STATUS_ACTIVE])->one();
        if (!$teacher) throw new \Exception('Учитель не найден');
        $teacherSubject = TeacherSubjectLink::find()->andWhere(['teacher_id' => $teacher->id, 'subject_id' => $subject->id])->one();
        if (!$teacherSubject) throw new \Exception('Учитель не найден');
        $startDate = date_create_from_format('d.m.Y', $welcomeLessonData['date']);
        if (!$startDate) throw new \Exception('Неверная дата начала занятий');

        $welcomeLesson = new WelcomeLesson();
        $welcomeLesson->subject_id = $subject->id;
        $welcomeLesson->teacher_id = $teacher->id;
        $welcomeLesson->user_id = $pupil->id;
        $welcomeLesson->lessonDateTime = $startDate;

        if (!$welcomeLesson->save()) {
            throw new \Exception('Server error: ' . $welcomeLesson->getErrorsAsString());
        }

        return $welcomeLesson;
    }

    /**
     * @param Company $company
     * @param GroupPupil $groupPupil
     * @param int $amount
     * @param array $paymentData
     * @return int
     */
    private function addPupilMoneyIncome(Company $company, GroupPupil $groupPupil, int $amount, array $paymentData)
    {
        $contract = MoneyComponent::addPupilContract(
            $company,
            $groupPupil->user,
            $amount,
            $groupPupil->group
        );

        \Yii::$app->session->addFlash(
            'success',
            'Договор ' . $contract->number . ' зарегистрирован '
            . '<a target="_blank" href="' . Url::to(['contract/print', 'id' => $contract->id]) . '">Распечатать</a>'
        );

        $paymentId = MoneyComponent::payContract($contract, null, Contract::PAYMENT_TYPE_MANUAL, $paymentData['comment']);

        \Yii::$app->session->addFlash('success', 'Внесение денег зарегистрировано, номер транзакции: ' . $paymentId);
        return $paymentId;
    }

    public function actionAddToGroup($userId)
    {
        if (!\Yii::$app->user->can('manageUsers')) throw new ForbiddenHttpException('Access denied!');
        /** @var User $pupil */
        $pupil = User::find()
            ->andWhere(['id' => $userId, 'role' => User::ROLE_PUPIL])
            ->andWhere('status != :locked', ['locked' => User::STATUS_LOCKED])
            ->one();
        if (!$pupil) throw new NotFoundHttpException('Pupil not found');

        $groupData = [];
        if (\Yii::$app->request->isPost) {
            $groupData = \Yii::$app->request->post('group', []);

            $transaction = \Yii::$app->db->beginTransaction();
            try {
                $this->addPupilToGroup($pupil, $groupData);
                $transaction->commit();
                \Yii::$app->session->addFlash('success', 'Добавлено');
            } catch (\Throwable $e) {
                $transaction->rollBack();
                \Yii::$app->session->addFlash('error', $e->getMessage());
            }
        }

        return $this->render('add-to-group', [
            'pupil' => $pupil,
            'groups' => Group::find()->andWhere(['active' => Group::STATUS_ACTIVE])->orderBy(['name' => SORT_ASC])->all(),
            'groupData' => $groupData,
        ]);
    }

    /**
     * Creates a new Employee.
     * If creation is successful, the browser will be redirected to the 'update' page.
     * @return mixed
     * @throws ForbiddenHttpException
     * @throws \Exception
     * @throws \yii\db\Exception
     */
    public function actionCreateEmployee()
    {
        if (!\Yii::$app->user->can('manageEmployees')) throw new ForbiddenHttpException('Access denied!');

        $employee = new User(['scenario' => User::SCENARIO_ADMIN]);

        if (\Yii::$app->request->isPost) {
            $employee->load(\Yii::$app->request->post());
            $employee->setPassword($employee->password);
            $employee->generateAuthKey();

            if ($employee->save()) {
                \Yii::$app->session->addFlash('success', 'Сотрудник добавлен');
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
        $userToEdit = $id ?: \Yii::$app->user->id;
        if (!\Yii::$app->user->can('editUser', ['user' => $userToEdit])) throw new ForbiddenHttpException('Access denied!');

        $user = $this->findModel($userToEdit);
        $user->setScenario(in_array($user->role, [User::ROLE_PUPIL, User::ROLE_PARENTS]) ? User::SCENARIO_USER : User::SCENARIO_ADMIN);
        $isAdmin = \Yii::$app->user->can('manageUsers');
        $editACL = \Yii::$app->user->can('manageEmployees');
        $auth = \Yii::$app->authManager;
        $parent = new User(['scenario' => User::SCENARIO_USER]);

        if (\Yii::$app->request->isPost) {
            $transaction = \Yii::$app->db->beginTransaction();
            try {
                if ($user->load(\Yii::$app->request->post())) {
                    $fields = null;
                    if (!$isAdmin) $fields = ['username', 'password'];
                    if ($user->save(true, $fields)) {
                        if ($user->role == User::ROLE_PUPIL && !$user->parent_id) {
                            $parent->load(\Yii::$app->request->post('User', []), 'parent');
                            $parent = $this->processParent($parent, 'parent', User::ROLE_PARENTS);
                            $user->parent_id = $parent->id;
                            $user->save(false, ['parent_id']);
                        }
                        if ($editACL && ($user->role == User::ROLE_MANAGER || $user->role == User::ROLE_ROOT)) {
                            $newRules = \Yii::$app->request->post('acl', []);
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
                        $transaction->commit();
                        \Yii::$app->session->addFlash('success', 'Успешно обновлено');
                        return $this->redirect(['update', 'id' => $id]);
                    } else {
                        $transaction->rollBack();
                        $user->moveErrorsToFlash();
                    }
                } else throw new \Exception('Внутренняя ошибка сервера');
            } catch (\Throwable $exception) {
                $transaction->rollBack();
                \Yii::$app->session->addFlash('error', $exception->getMessage());
            }
        }

        return $this->render('update', [
            'user' => $user,
            'isAdmin' => $isAdmin,
            'editACL' => $editACL,
            'authManager' => $auth,
            'existedParents' => User::find()->andWhere(['role' => User::ROLE_PARENTS])->orderBy(['name' => SORT_ASC])->all(),
            'parent' => $parent,
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
            if (!\Yii::$app->user->can('manageUsers')) $jsonData = self::getJsonErrorResult('Access denied!');
            else {
                $user = $this->findModel($id);

                $activeState = \Yii::$app->request->post('active');
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
        if (!\Yii::$app->user->can('manageUsers')) throw new ForbiddenHttpException('Access denied!');
        if (!\Yii::$app->request->isAjax) throw new BadRequestHttpException('Request is not AJAX');

        $jsonData = self::getJsonOkResult(['phone' => \Yii::$app->request->post('phone', '')]);
        $phone = preg_replace('#\D#', '', $jsonData['phone']);

        if (!empty($phone) && strlen($phone) == 9) {
            $searchString = "+998$phone";
            $pupils = [];
            $searchResult = User::find()
                ->andWhere(['role' => [User::ROLE_PUPIL, User::ROLE_PARENTS]])
                ->andWhere(['!=', 'status', User::STATUS_LOCKED])
                ->andWhere('phone = :phone OR phone2 = :phone', ['phone' => $searchString])
                ->with(['activeGroupPupils.group', 'children.activeGroupPupils.group'])
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
                    foreach ($pupil->activeGroupPupils as $groupPupil) {
                        $groupParam = GroupComponent::getGroupParam($groupPupil->group, new \DateTime());
                        $groupData = $groupPupil->group->toArray(['id', 'name', 'lesson_price', 'lesson_price_discount']);
                        $groupData['month_price'] = $groupParam->priceMonth;
                        $groupData['discount_price'] = $groupParam->price3Month;
                        $groupData['date_start'] = $groupPupil->startDateObject->format('d.m.Y');
                        $groupData['date_charge_till'] = $groupPupil->chargeDateObject ? $groupPupil->chargeDateObject->format('d.m.Y') : '';

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
        $userToWatch = $id ?: \Yii::$app->user->id;
        if (!\Yii::$app->user->can('viewSchedule', ['user' => $userToWatch])) throw new ForbiddenHttpException('Access denied!');

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
        $userToWatch = $id ?: \Yii::$app->user->id;
        if (!\Yii::$app->user->can('viewSchedule', ['user' => $userToWatch])) throw new ForbiddenHttpException('Access denied!');
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

        $pager = new Pagination(['pageSize' => 30, 'totalCount' => Payment::find()->andWhere(['user_id' => $userToWatch])->count()]);

        return $this->render('money-history', [
            'user' => $user,
            'payments' => Payment::find()->andWhere(['user_id' => $userToWatch])->limit($pager->limit)->offset($pager->offset)->orderBy(['created_at' => SORT_DESC])->all(),
            'pager' => $pager,
        ]);
    }


    /**
     * @return \yii\web\Response
     */
    public function actionPupils() {
        $jsonData = [];
        if (\Yii::$app->request->isAjax) {
            $jsonData = User::find()
                ->andWhere(['role' => User::ROLE_PUPIL])
                ->andWhere('status != :locked', ['locked' => User::STATUS_LOCKED])
                ->orderBy('name')->select(['id', 'name'])
                ->asArray()->all();
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

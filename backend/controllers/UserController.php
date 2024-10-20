<?php

namespace backend\controllers;

use backend\components\EventComponent;
use backend\models\Consultation;
use backend\models\WelcomeLesson;
use common\components\ComponentContainer;
use common\components\GroupComponent;
use common\components\MoneyComponent;
use backend\components\UserComponent;
use common\models\Company;
use common\models\Contract;
use backend\models\EventMember;
use common\models\Group;
use common\models\GroupParam;
use common\models\GroupPupil;
use common\models\Payment;
use common\models\Subject;
use common\models\Teacher;
use common\models\User;
use common\models\UserSearch;
use DateTime;
use Exception;
use Throwable;
use Yii;
use yii\data\Pagination;
use yii\helpers\Url;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

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
            'isRoot' => Yii::$app->user->can('root'),
        ]);
    }

    private function saveConsultations(User $pupil)
    {
        $consultationData = Yii::$app->request->post('consultation', []);
        $errors = [];

        foreach ($consultationData as $subjectId) {
            $consultation = new Consultation();
            $consultation->subject_id = $subjectId;
            $consultation->user_id = $pupil->id;

            if (!$consultation->save()) {
                $errors = array_merge($errors, $consultation->getErrorsAsStringArray());
            }
        }

        return $errors;
    }

    private function saveWelcomeLessons(User $pupil)
    {
        $welcomeLessonData = self::remapRequestData(Yii::$app->request->post('welcome_lesson', []));
        $errors = [];

        $ids = [];
        foreach ($welcomeLessonData as $welcomeLessonInfo) {
            try {
                $welcomeLesson = $this->addPupilToWelcomeLesson($pupil, $welcomeLessonInfo);
                $ids[] = $welcomeLesson->id;
            } catch (Throwable $exception) {
                $errors = array_merge($errors, [$exception->getMessage()]);
            }
        }
        $infoFlashArray = [];
        if (!empty($ids) && empty($errors)) {
            $infoFlashArray[] = '<a target="_blank" href="' . Url::to(['welcome-lesson/print', 'id' => $ids]) . '">Распечатать информацию о пробных уроках</a>';
        }

        return [$errors, $infoFlashArray];
    }

    private function saveGroups(User $pupil)
    {
        $groupData = self::remapRequestData(Yii::$app->request->post('group', []));
        $errors = $infoFlashArray = [];

        foreach ($groupData as $groupInfo) {
            try {
                /** @var Group $group */
                $group = Group::find()->andWhere(['id' => $groupInfo['groupId'], 'active' => Group::STATUS_ACTIVE])->one();
                if (!$group) throw new Exception('Группа не найдена');

                if ($groupInfo['dateDefined']) {
                    $this->addPupilToGroup($pupil, $groupInfo);
                    MoneyComponent::setUserChargeDates($pupil, $group);
                }
                if (Yii::$app->user->can('moneyManagement') && !empty($groupInfo['payment'])) {
                    $contract = MoneyComponent::addPupilContract(
                        Company::findOne(Company::COMPANY_EXCLUSIVE_ID),
                        $pupil,
                        $groupInfo['amount'],
                        $group
                    );
                    MoneyComponent::payContract($contract, null, Contract::PAYMENT_TYPE_MANUAL, $groupInfo['paymentComment']);

                    $infoFlashArray[] = 'Оплата внесена. <a target="_blank" href="' . Url::to(['contract/print', 'id' => $contract->id]) . '">Распечатать спецификацию</a>';
                }
            } catch (Throwable $exception) {
                $errors = array_merge($errors, [$exception->getMessage()]);
            }
        }

        return [$errors, $infoFlashArray];
    }

    /**
     * Creates a new Pupil.
     * If creation is successful, the browser will be redirected to the 'index' page.
     * @return mixed
     * @throws ForbiddenHttpException
     * @throws Throwable
     */
    public function actionCreatePupil()
    {
        $this->checkAccess('manageUsers');

        $parent = new User(['scenario' => User::SCENARIO_USER]);
        $parentCompany = new User(['scenario' => User::SCENARIO_USER]);
        $pupil = new User(['scenario' => User::SCENARIO_USER]);
        $consultationData = [];
        $welcomeLessonData = [];
        $groupData = [];
        $incomeAllowed = Yii::$app->user->can('moneyManagement');
        $contractAllowed = Yii::$app->user->can('contractManagement');
        $personType = User::ROLE_PARENTS;
        $parentType = $companyType = 'new';

        if (Yii::$app->request->isPost) {
            User::loadMultiple(['parent' => $parent, 'company' => $parentCompany, 'pupil' => $pupil], Yii::$app->request->post(), Yii::$app->request->isAjax ? '' : null);
            $pupil->role = User::ROLE_PUPIL;

            $transaction = User::getDb()->beginTransaction();
            try {
                if (UserComponent::isPhoneUsed(User::ROLE_PUPIL, $pupil->phone, $pupil->phone2)) {
                    throw new Exception('Студент с таким номером телефона уже существует!');
                }

                $personType = Yii::$app->request->post('person_type', User::ROLE_PARENTS);
                $parentType = Yii::$app->request->post('parent_type', 'new');
                $parentId = Yii::$app->request->post('parent', [])['id'] ?? null;
                $companyType = Yii::$app->request->post('company_type', 'new');
                $companyId = Yii::$app->request->post('company', [])['id'] ?? null;
                
                switch ($personType) {
                    case User::ROLE_PARENTS:
                        $pupil->individual = 1;
                        $parent = $this->processParent($parent, $parentType, $personType, $parentId);
                        $pupil->parent_id = $parent->id;
                        break;
                    case User::ROLE_COMPANY:
                        $pupil->individual = 0;
                        $parentCompany = $this->processParent($parentCompany, $companyType, $personType, $companyId);
                        $pupil->parent_id = $parentCompany->id;
                        break;
                }

                if (!$pupil->save()) {
                    $transaction->rollBack();

                    if (Yii::$app->request->isAjax) {
                        return $this->asJson(self::getJsonErrorResult($pupil->getErrorsAsString()));
                    }

                    $pupil->moveErrorsToFlash();
                } else {
                    $errors = $infoFlashArray = [];
                    $errors = array_merge($errors, $this->saveConsultations($pupil));
                    $welcomeLessonResults = $this->saveWelcomeLessons($pupil);
                    $errors = array_merge($errors, $welcomeLessonResults[0]);
                    $infoFlashArray = array_merge($infoFlashArray, $welcomeLessonResults[1]);
                    $groupResults = $this->saveGroups($pupil);
                    $errors = array_merge($errors, $groupResults[0]);
                    $infoFlashArray = array_merge($infoFlashArray, $groupResults[1]);

                    if (empty($errors)) {
                        $transaction->commit();
                        UserComponent::clearSearchCache();

                        if (Yii::$app->request->isAjax) {
                            return $this->asJson(self::getJsonOkResult(['name' => $pupil->name]));
                        }

                        Yii::$app->session->addFlash('success', 'Добавлено');
                        foreach ($infoFlashArray as $message) {
                            Yii::$app->session->addFlash('info', $message);
                        }

                        return $this->redirect(['index']);
                    } else {
                        $transaction->rollBack();

                        if (Yii::$app->request->isAjax) {
                            return $this->asJson(self::getJsonErrorResult(implode(', ', $errors)));
                        }

                        foreach ($errors as $error) {
                            Yii::$app->session->addFlash('error', $error);
                        }
                    }
                }
            } catch (Throwable $e) {
                $transaction->rollBack();

                if (Yii::$app->request->isAjax) {
                    return $this->asJson(self::getJsonErrorResult($e->getMessage()));
                }

                Yii::$app->session->addFlash('error', $e->getMessage());
            }
        }

        $this->checkAccess('root');

        return $this->render('create-pupil', [
            'parent' => $parent,
            'parentCompany' => $parentCompany,
            'pupil' => $pupil,
            'personType' => $personType,
            'parentData' => ['type' => $parentType, 'id' => $parentId ?? null],
            'companyData' => ['type' => $companyType, 'id' => $companyId ?? null],
            'consultationData' => $consultationData,
            'welcomeLessonData' => $welcomeLessonData,
            'groupData' => $groupData,
            'pupilLimitDate' => GroupComponent::getPupilLimitDate(),
            'incomeAllowed' => $incomeAllowed,
            'contractAllowed' => $contractAllowed,
        ]);
    }

    /**
     * @param User $parent
     * @param string $parentType
     * @param int $personType
     * @param null|int $existParentId
     * @return User
     * @throws NotFoundHttpException
     */
    private function processParent(User $parent, string $parentType, int $personType, ?int $existParentId = null): User
    {
        switch ($parentType) {
            case 'exist':
                if (!$existParentId) throw new Exception("Choose parent from the list");
                $parent = $this->findModel($existParentId);
                if ($parent->role != $personType || $parent->status == User::STATUS_LOCKED) throw new Exception('Parents not found');
                break;
            case 'new':
                $parent->role = $personType;

                if (UserComponent::isPhoneUsed($personType, $parent->phone, $parent->phone2)) {
                    throw new Exception('Родитель/компания с таким номером телефона уже существует!');
                }

                if (!$parent->save()) {
                    $parent->moveErrorsToFlash();
                    throw new Exception('Server error');
                }
                break;
        }
        return $parent;
    }

    /**
     * @param User $pupil
     * @param array $groupData
     * @return GroupPupil
     * @throws Exception
     */
    private function addPupilToGroup(User $pupil, array $groupData): GroupPupil
    {
        /** @var Group $group */
        $group = Group::find()->andWhere(['id' => $groupData['groupId'], 'active' => Group::STATUS_ACTIVE])->one();
        if (!$group) throw new Exception('Группа не найдена');
        $startDate = new \DateTime($groupData['date']);
        if (!$startDate) throw new Exception('Неверная дата начала занятий');

        return GroupComponent::addPupilToGroup($pupil, $group, $startDate);
    }

    /**
     * @param User $pupil
     * @param array $welcomeLessonData
     * @return WelcomeLesson
     * @throws Exception
     */
    private function addPupilToWelcomeLesson(User $pupil, array $welcomeLessonData): WelcomeLesson
    {
        $welcomeLesson = new WelcomeLesson();

        /** @var Group $group */
        $group = Group::find()->andWhere(['id' => $welcomeLessonData['groupId'], 'active' => Subject::STATUS_ACTIVE])->one();
        if (!$group) throw new Exception('Группа не найдена');
        $welcomeLesson->group_id = $group->id;
        
        $startDate = new \DateTime($welcomeLessonData['date']);
        if (!$startDate) throw new Exception('Неверная дата пробного урока');
        $groupParam = GroupParam::findByDate($group, $startDate);
        if (!$groupParam) {
            $groupParam = $group;
        }
        if (!$groupParam->hasLesson($startDate)) throw new Exception('Неверная дата пробного урока');

        $welcomeLesson->user_id = $pupil->id;
        $welcomeLesson->lesson_date = $groupParam->getLessonDateTime($startDate);

        if (!$welcomeLesson->save()) {
            throw new Exception('Server error: ' . $welcomeLesson->getErrorsAsString());
        }
        
        EventComponent::fillSchedule($group);

        return $welcomeLesson;
    }

    public function actionAddToGroup($userId)
    {
        $this->checkAccess('manageUsers');

        /** @var User $pupil */
        $pupil = User::find()
            ->andWhere(['id' => $userId, 'role' => User::ROLE_PUPIL])
            ->andWhere('status != :locked', ['locked' => User::STATUS_LOCKED])
            ->one();
        if (!$pupil) {
            throw new NotFoundHttpException('Pupil not found');
        }

        $groupData = [];
        if (Yii::$app->request->isPost) {
            $groupData = Yii::$app->request->post('group', []);

            $transaction = Yii::$app->db->beginTransaction();
            try {
                $groupPupil = $this->addPupilToGroup($pupil, $groupData);
                MoneyComponent::setUserChargeDates($pupil, $groupPupil->group);
                $transaction->commit();
                Yii::$app->session->addFlash('success', 'Ученик добавлен в группу');
            } catch (Throwable $e) {
                $transaction->rollBack();
                Yii::$app->session->addFlash('error', $e->getMessage());
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
     * @return mixed
     * @throws ForbiddenHttpException
     * @throws \yii\base\Exception
     */
    public function actionCreateEmployee()
    {
        if (!Yii::$app->user->can('manageEmployees')) throw new ForbiddenHttpException('Access denied!');

        $employee = new User(['scenario' => User::SCENARIO_ADMIN]);

        if (Yii::$app->request->isPost) {
            if (!$employee->load(Yii::$app->request->post())) {
                Yii::$app->session->addFlash('error', 'Form data not found');
            } elseif (!$employee->save()) {
                $employee->moveErrorsToFlash();
            } else {
                Yii::$app->session->addFlash('success', 'Сотрудник добавлен');
                UserComponent::clearSearchCache();
                return $this->redirect(['update', 'id' => $employee->id]);
            }
        }

        return $this->render('create-employee', [
            'user' => $employee,
        ]);
    }

    /**
     * Creates a new Employee.
     * @return mixed
     * @throws ForbiddenHttpException
     * @throws \yii\base\Exception
     */
    public function actionCreateTeacher()
    {
        if (!Yii::$app->user->can('manageTeachers') || !Yii::$app->user->can('manageUsers')) {
            throw new ForbiddenHttpException('Access denied!');
        }

        $userTeacher = new User(['scenario' => User::SCENARIO_ADMIN, 'role' => User::ROLE_TEACHER]);

        if (Yii::$app->request->isPost) {
            if (!$userTeacher->load(Yii::$app->request->post())) {
                Yii::$app->session->addFlash('error', 'Form data not found');
            } elseif (!$userTeacher->save()) {
                $userTeacher->moveErrorsToFlash();
            } else {
                Yii::$app->session->addFlash('success', 'Учитель добавлен');
                UserComponent::clearSearchCache();
                return $this->redirect(['update', 'id' => $userTeacher->id]);
            }
        } elseif ($teacherId = Yii::$app->request->get('teacher_id')) {
            $teacher = Teacher::findOne($teacherId);
            if ($teacher) {
                $userTeacher->teacher_id = $teacher->id;
                $userTeacher->name = $teacher->name;
                $userTeacher->phone = $teacher->phone;
            }
        }

        return $this->render('create-teacher', [
            'user' => $userTeacher,
            'teachers' => Teacher::find()->alias('t')
                ->select(['t.*'])
                ->leftJoin(['u' => User::tableName()], ['u.teacher_id' => 't.id'])
                ->andWhere(['u.id' => null])
                ->addOrderBy(['t.name' => SORT_ASC])
                ->all(),
        ]);
    }

    public function actionUpdateAjax()
    {
        $this->checkRequestIsAjax();
        $this->checkAccess('manageUsers');
        Yii::$app->response->format = Response::FORMAT_JSON;

        $usersData = Yii::$app->request->post('User', []);
        if (!isset($usersData['pupil'], $usersData['pupil']['id'])) {
            return self::getJsonErrorResult('Wrong request');
        }
        $pupilData = $usersData['pupil'];
        $parentData = $usersData['parent'] ?? [];
        $parentType = Yii::$app->request->post('parent_type', null);

        /** @var User $pupil */
        $pupil = User::find()
            ->andWhere(['id' => $pupilData['id'], 'role' => User::ROLE_PUPIL])
            ->andWhere(['not', ['status' => User::STATUS_LOCKED]])
            ->one();
        if (!$pupil) {
            return self::getJsonErrorResult('Pupil not found');
        }
        $pupil->setScenario(User::SCENARIO_USER);
        $pupil->load($pupilData, '');

        if (UserComponent::isPhoneUsed(User::ROLE_PUPIL, $pupil->phone, $pupil->phone2, $pupil)) {
            return self::getJsonErrorResult('Студент с таким номером телефона уже существует!');
        }
        
        $errors = [];
        $transaction = User::getDb()->beginTransaction();

        if (!$pupil->save()) {
            $errors = array_merge($errors, $pupil->getErrorsAsStringArray());
        }
        
        if ($pupil->parent_id) {
            if ($parentData) {
                $pupil->parent->setScenario(User::SCENARIO_USER);
                $pupil->parent->load($parentData, '');
                if (!$pupil->parent->save()) {
                    $errors = array_merge($errors, $pupil->parent->getErrorsAsStringArray());
                }
            }
        } elseif ($parentType) {
            $parentRole = $pupil->individual ? User::ROLE_PARENTS : User::ROLE_COMPANY;
            switch ($parentType) {
                case 'exist':
                    /** @var User $parent */
                    $parent = User::find()
                        ->andWhere(['role' => $parentRole, 'id' => $parentData['id']])
                        ->andWhere(['not', ['status' => User::STATUS_LOCKED]])
                        ->one();
                    if (!$parent) {
                        $errors[] = 'Parent not found';
                    } else {
                        $pupil->parent_id = $parent->id;
                        $pupil->save(true, ['parent_id']);
                    }
                    break;
                case 'new':
                    $parent = new User(['scenario' => User::SCENARIO_USER]);
                    $parent->role = $parentRole;
                    $parent->load($parentData, '');

                    if (UserComponent::isPhoneUsed($parentRole, $parent->phone, $parent->phone2)) {
                        $errors[] = 'Родитель/компания с таким номером телефона уже существует!';
                    } elseif (!$parent->save()) {
                        $errors = array_merge($errors, $parent->getErrorsAsStringArray());
                    } else {
                        $pupil->parent_id = $parent->id;
                        $pupil->save(true, ['parent_id']);
                    }
                    break;
            }
        }

        $infoFlashArray = [];
        $errors = array_merge($errors, $this->saveConsultations($pupil));
        $welcomeLessonResults = $this->saveWelcomeLessons($pupil);
        $errors = array_merge($errors, $welcomeLessonResults[0]);
        $infoFlashArray = array_merge($infoFlashArray, $welcomeLessonResults[1]);
        $groupResults = $this->saveGroups($pupil);
        $errors = array_merge($errors, $groupResults[0]);
        $infoFlashArray = array_merge($infoFlashArray, $groupResults[1]);

        if (empty($errors)) {
            $transaction->commit();
            return self::getJsonOkResult(['infoFlash' => $infoFlashArray]);
        }

        $transaction->rollBack();
        return array_merge(['errors' => $errors], self::getJsonErrorResult());
    }

    /**
     * Updates an existing User model.
     * @param string|int|null $id
     * @return mixed
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException
     */
    public function actionUpdate($id = null)
    {
        $userToEdit = $id ?: Yii::$app->user->id;
        if (!Yii::$app->user->can('editUser', ['user' => $userToEdit])) throw new ForbiddenHttpException('Access denied!');

        $isAdmin = Yii::$app->user->can('manageUsers');
        $user = $this->findModel($userToEdit);
        $user->setScenario(
            in_array($user->role, [User::ROLE_PUPIL, User::ROLE_PARENTS, User::ROLE_COMPANY])
                ? ($isAdmin ? User::SCENARIO_USER : User::SCENARIO_CUSTOMER)
                : User::SCENARIO_ADMIN
        );

        $editACL = Yii::$app->user->can('manageEmployees');
        $auth = Yii::$app->authManager;
        $parent = new User(['scenario' => User::SCENARIO_USER]);

        if (Yii::$app->request->isPost) {
            $transaction = Yii::$app->db->beginTransaction();
            try {
                if (!$user->load(Yii::$app->request->post())) {
                    throw new Exception('Внутренняя ошибка сервера');
                }

                $fields = null;
                if (!$isAdmin) {
                    $fields = ['username', 'password'];
                } else {
                    $user->bitrix_sync_status = 0;
                }

                if ($user->role == User::ROLE_PUPIL && UserComponent::isPhoneUsed(User::ROLE_PUPIL, $user->phone, $user->phone2, $user)) {
                    throw new Exception('Студент с таким номером телефона уже существует!');
                }
                
                if (!$user->save(true, $fields)) {
                    $transaction->rollBack();
                    $user->moveErrorsToFlash();
                } else {
                    if ($user->role == User::ROLE_PUPIL && !$user->parent_id) {
                        $usersData = Yii::$app->request->post('User', []);
                        $parent->load($usersData, 'parent');
                        $parentType = Yii::$app->request->post('parent_type', 'new');
                        $existParentId = !empty($usersData['parent']['id']) ? (int)$usersData['parent']['id'] : 0;
                        $parent = $this->processParent($parent, $parentType, $user->individual ? User::ROLE_PARENTS : User::ROLE_COMPANY, $existParentId);
                        if ($parent->id) {
                            $user->link('parent', $parent);
                        }
                    }
                    if ($editACL && ($user->role == User::ROLE_MANAGER || $user->role == User::ROLE_ROOT || $user->role == User::ROLE_TEACHER)) {
                        $newRules = Yii::$app->request->post('acl', []);
                        foreach (($user->role == User::ROLE_TEACHER ? UserComponent::ACL_TEACHER_RULES : UserComponent::ACL_RULES) as $key => $devNull) {
                            $role = $auth->getRole($key) ?? $auth->getPermission($key);
                            if (array_key_exists($key, $newRules)) {
                                if (!$auth->getAssignment($role->name, $user->id)) $auth->assign($role, $user->id);
                            } else {
                                if ($auth->getAssignment($role->name, $user->id)) $auth->revoke($role, $user->id);
                            }
                        }
                    }
                    UserComponent::clearSearchCache();
                    $transaction->commit();
                    Yii::$app->session->addFlash('success', 'Успешно обновлено');
                    return $this->redirect(['update', 'id' => $id]);
                }
            } catch (Throwable $exception) {
                $transaction->rollBack();
                Yii::$app->session->addFlash('error', $exception->getMessage());
            }
        }

        return $this->render('update', [
            'user' => $user,
            'isAdmin' => $isAdmin,
            'editACL' => $editACL,
            'authManager' => $auth,
            'parent' => $parent,
        ]);
    }

    public function actionView(int $id, ?string $tab = null)
    {
        $this->checkRequestIsAjax();
        $this->checkAccess('manageUsers');
        
        $pupil = $this->findModel($id);

        if (!$tab) {
            if (!empty($pupil->activeGroupPupils)) {
                $tab = 'group';
            } elseif (!empty($pupil->welcomeLessons)) {
                $tab = 'welcome_lesson';
            } else {
                $tab = 'consultation';
            }
        }
        
        return $this->renderPartial('view', [
            'pupil' => $pupil,
            'activeTab' => $tab,
            'incomeAllowed' => Yii::$app->user->can('moneyManagement'),
            'debtAllowed' => Yii::$app->user->can('root'),
            'contractAllowed' => Yii::$app->user->can('contractManagement'),
            'groupManagementAllowed' => Yii::$app->user->can('manageGroups'),
            'moveMoneyAllowed' => Yii::$app->user->can('moveMoney'),
            'welcomeLessonsAllowed' => Yii::$app->user->can('welcomeLessons'),
        ]);
    }
    
    public function actionFind(string $term, int $role = User::ROLE_PUPIL)
    {
        $this->checkRequestIsAjax();
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        return User::find()
            ->andWhere(['role' => $role])
            ->andWhere(['or', ['like', 'name', "$term%", false], ['like', 'name', "% $term%", false]])
            ->orderBy(['name' => SORT_ASC])
            ->select(['id', 'name AS label'])
            ->asArray()
            ->all();
        
    }

    public function actionChangeActive($id)
    {
        $this->checkRequestIsAjax();
        $this->checkAccess('manageUsers');
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        $user = $this->findModel($id);

        $activeState = Yii::$app->request->post('active');
        if (($user->status == User::STATUS_ACTIVE) != $activeState) {
            $user->status = $activeState ? User::STATUS_ACTIVE : User::STATUS_LOCKED;
            if (!$user->save()) {
                return self::getJsonErrorResult($user->getErrorsAsString());
            }
            UserComponent::clearSearchCache();
        }

        return self::getJsonOkResult(['id' => $user->id]);
    }

    public function actionFindByPhone()
    {
        $this->checkRequestIsAjax();
        $this->checkAccess('manageUsers');

        $jsonData = self::getJsonOkResult(['phone' => Yii::$app->request->post('phone', '')]);
        $phone = preg_replace('#\D#', '', $jsonData['phone']);

        if (!empty($phone) && strlen($phone) === 9) {
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
                        $groupData = [
                            'id' => $groupPupil->group_id,
                            'date_start' => $groupPupil->startDateObject->format('d.m.Y'),
                            'date_charge_till' => $groupPupil->chargeDateObject ? $groupPupil->chargeDateObject->format('d.m.Y') : '',
                        ];

                        $data['groups'][] = $groupData;
                    }
                    $jsonData['pupils'][] = $data;
                }
            }
        }

        return $this->asJson($jsonData);
    }

    private function renderSingleSchedule(User $pupil, $month = null)
    {
        if ($month) $eventMonth = DateTime::createFromFormat('Y-m', $month);
        if (!isset($eventMonth) || !$eventMonth) $eventMonth = new DateTime();
        $eventMonth->modify('first day of this month midnight');
        $endDate = clone($eventMonth);
        $endDate->modify('+1 month');
        $eventMemberCollection = EventMember::find()
            ->innerJoinWith('event')
            ->andWhere(['user_id' => $pupil->id])
            ->andWhere('event_date > :startDate', [':startDate' => $eventMonth->format('Y-m-d H:i:s')])
            ->andWhere('event_date < :endDate', [':endDate' => $endDate->format('Y-m-d H:i:s')])
            ->all();
        $groupMap = [];
        $eventMap = [];
        /** @var EventMember $eventMember */
        foreach ($eventMemberCollection as $eventMember) {
            $day = $eventMember->event->eventDateTime->format('Y-m-d');
            if (!isset($eventMap[$day])) $eventMap[$day] = [];
            $eventMap[$day][$eventMember->event->eventTime] = $eventMember;

            if (!array_key_exists($eventMember->event->group_id, $groupMap)) {
                $groupMap[$eventMember->event->group_id] = [
                    'group' => Group::findOne($eventMember->event->group_id),
                    'payments' => Payment::find()
                        ->andWhere('created_at >= :from', ['from' => $eventMonth->format('Y-m-d H:i:s')])
                        ->andWhere('created_at < :to', ['to' => $endDate->format('Y-m-d H:i:s')])
                        ->andWhere(['group_id' => $eventMember->event->group_id, 'user_id' => $pupil->id])
                        ->all(),
                ];
            }
        }

        return $this->render('schedule', [
            'eventMonth' => $eventMonth,
            'user' => $pupil,
            'eventMap' => $eventMap,
            'groupMap' => $groupMap,
        ]);
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
        if ($user->role === User::ROLE_PUPIL) {
            return $this->renderSingleSchedule($user, $month);
        }

        if (in_array($user->role, [User::ROLE_COMPANY, User::ROLE_PARENTS])) {
            $pupilCollection = $user->children;
            if ($pupilCollection && count($pupilCollection) == 1) {
                return $this->renderSingleSchedule(reset($pupilCollection), $month);
            }
        } else {
            $pupilCollection = User::find()->where(['status' => User::STATUS_ACTIVE, 'role' => User::ROLE_PUPIL])->orderBy('name')->all();
        }

        return $this->render('select_pupil_schedule', [
            'pupilCollection' => $pupilCollection,
            'month' => $month,
            'user' => $user,
        ]);
    }

    /**
     * @param int|null $id
     * @return string
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException
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

        $pager = new Pagination(['pageSize' => 30, 'totalCount' => Payment::find()->andWhere(['user_id' => $userToWatch])->count()]);

        return $this->render('money-history', [
            'user' => $user,
            'payments' => Payment::find()->andWhere(['user_id' => $userToWatch])->limit($pager->limit)->offset($pager->offset)->orderBy(['created_at' => SORT_DESC])->all(),
            'pager' => $pager,
        ]);
    }

    /**
     * @return Response
     */
    public function actionPupils()
    {
        $jsonData = [];
        if (Yii::$app->request->isAjax) {
            $jsonData = User::find()
                ->andWhere(['role' => User::ROLE_PUPIL])
                ->andWhere('status != :locked', ['locked' => User::STATUS_LOCKED])
                ->orderBy('name')->select(['id', 'name'])
                ->asArray()->all();
        }
        return $this->asJson($jsonData);
    }
    
    public function actionSendAgeSms()
    {
        $this->checkRequestIsAjax();
        $this->checkAccess('moneyManagement');
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        $userId = Yii::$app->request->post('user_id');
        $phone = Yii::$app->request->post('phone');
        $user = $this->findModel($userId);
        $phoneList = array_filter([$user->phone, $user->phone2, $user->parent_id ? $user->parent->phone : null, $user->parent_id ? $user->parent->phone2 : null]);
        if (!in_array($phone, $phoneList)) {
            return self::getJsonErrorResult('Invalid phone number');
        }

        if ($blockUntil = ComponentContainer::getAgeValidator()->getBlockUntilDate($phone)) {
            return self::getJsonErrorResult('СМС не могут быть отправлены слишком часто, дождитесь получения СМС на телефон или запросите повторную отправку после ' . $blockUntil->format('H:i:s d.m.Y'));
        }

        if (ComponentContainer::getAgeValidator()->add($phone, [$user])) {
            return self::getJsonOkResult(['message' => 'СМС отправлена']);
        }

        return self::getJsonErrorResult('Что-то пошло не так, СМС не отправлена');
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

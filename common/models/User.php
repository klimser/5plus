<?php
namespace common\models;

use backend\models\Action;
use backend\models\Consultation;
use backend\models\EventMember;
use backend\models\WelcomeLesson;
use common\components\helpers\MaskString;
use common\models\traits\InsertedUpdated;
use common\models\traits\Phone;
use common\models\traits\Phone2;
use yii;
use yii\base\NotSupportedException;
use \common\components\extended\ActiveRecord;
use yii\db\ActiveQuery;
use yii\web\IdentityInterface;

/**
 * User model
 *
 * @property int $id
 * @property string $name
 * @property string $phone
 * @property string $username
 * @property string $note
 * @property string $password_hash
 * @property string $password_reset_token
 * @property string $auth_key
 * @property int $status
 * @property int $money
 * @property int $role
 * @property int $individual
 * @property int $parent_id
 * @property int $teacher_id
 * @property int $tg_chat_id
 * @property-read string $tg_params
 * @property array $telegramSettings
 * @property int $bitrix_id
 * @property int $bitrix_sync_status
 * @property int $age_confirmed
 * @property int $created_by
 * @property string $password write-only password
 * @property array $nameParts
 * @property array $firstName
 * @property User $parent
 * @property Teacher $teacher
 * @property User[] $children
 * @property User[] $notLockedChildren
 * @property GroupPupil[] $groupPupils
 * @property GroupPupil[] $groupPupilsAggregated
 * @property Group[] $groups
 * @property GroupPupil[] $activeGroupPupils
 * @property Group[] $activeGroups
 * @property Action[] $actions
 * @property Action[] $actionsAsAdmin
 * @property Payment[] $payments
 * @property Payment[] $paymentsAsAdmin
 * @property Debt[] $debts
 * @property Consultation[] $consultations
 * @property WelcomeLesson[] $welcomeLessons
 * @property Contract[] $contracts
 * @property string $nameHidden
 * @property User $createdAdmin
 */
class User extends ActiveRecord implements IdentityInterface
{
    use InsertedUpdated, Phone, Phone2;

    const SYSTEM_USER_ID = 4;

    const STATUS_LOCKED   = 0;
    const STATUS_ACTIVE   = 10;
    const STATUS_INACTIVE = 20;

    const ROLE_ROOT    = 1;
    const ROLE_PARENTS = 2;
    const ROLE_PUPIL   = 3;
    const ROLE_COMPANY = 4;
    const ROLE_MANAGER = 10;
    const ROLE_TEACHER = 20;

    public $password;

    const SCENARIO_ADMIN    = 'admin';
    const SCENARIO_USER     = 'user';
    const SCENARIO_CUSTOMER = 'customer';

    public function scenarios()
    {
        $scenarios = parent::scenarios();
        $scenarios[self::SCENARIO_ADMIN] = ['username', 'name', 'phone', 'phone2', 'phoneFormatted', 'phone2Formatted', 'role', 'password', 'teacher_id'];
        $scenarios[self::SCENARIO_CUSTOMER] = ['username', 'password'];
        $scenarios[self::SCENARIO_USER] = ['name', 'note', 'phone', 'phone2', 'phoneFormatted', 'phone2Formatted', 'password'];

        return $scenarios;
    }

    public static function tableName()
    {
        return '{{%user}}';
    }


    public function rules()
    {
        return [
//            [['username', 'name', 'auth_key', 'password_hash'], 'required'],
            [['name', 'username', 'note'], 'trim'],
            ['status', 'default', 'value' => self::STATUS_INACTIVE, 'on' => self::SCENARIO_USER],
            ['status', 'default', 'value' => self::STATUS_ACTIVE, 'on' => self::SCENARIO_ADMIN],
            ['individual', 'default', 'value' => 1],
            ['role', 'default', 'value' => self::ROLE_PUPIL],
            ['bitrix_sync_status', 'default', 'value' => 0],
            [['password_hash'], 'default', 'value' => ''],
            [['username', 'note', 'password_reset_token', 'phone2'], 'default', 'value' => null],

            [
                ['name'],
                'required',
                'whenClient' => "function (attribute, value) {
                var parentExpr = /\[parent\].*/;
                var companyExpr = /\[parentCompany\].*/;
                if (parentExpr.test(attribute.name)
                    && ($('input[name=\"person_type\"]:checked').val() != \"" . self::ROLE_PARENTS . "\" || $('input[name=\"parent_type\"]:checked').val() != \"new\")) {
                    return false;
                }
                if (companyExpr.test(attribute.name)
                    && ($('input[name=\"person_type\"]:checked').val() != \"" . self::ROLE_COMPANY . "\" || $('input[name=\"company_type\"]:checked').val() != \"new\")) {
                    return false;
                }
                return true;
            }"
            ],
            [['created_by'], 'required'],
            [['status', 'money', 'role', 'parent_id', 'teacher_id', 'bitrix_id', 'tg_chat_id'], 'integer'],
            [['username', 'note', 'password_hash', 'password_reset_token'], 'string', 'max' => 255],
            [['name'], 'string', 'max' => 127],
            [['name'], 'match', 'pattern' => '#^[a-zа-яё -]+$#iu'],
            [['auth_key'], 'string', 'max' => 32],
            [['phone', 'phone2'], 'string', 'min' => 13, 'max' => 13],
            [['phone', 'phone2'], 'match', 'pattern' => '#^\+998\d{9}$#'],
            [['phoneFormatted', 'phone2Formatted'], 'string', 'min' => 11, 'max' => 11],
            [['phoneFormatted', 'phone2Formatted'], 'match', 'pattern' => '#^\d{2} \d{3}-\d{4}$#'],
            [['username'], 'unique'],
            [['password_reset_token'], 'unique'],
            [['password'], 'safe'],
            [
                ['password'],
                'required',
                'on' => self::SCENARIO_ADMIN,
                'when' => function ($model) {
                    return $model->isNewRecord;
                },
                'whenClient' => "function (attribute, value) {
                    return $(attribute.input).data(\"id\").length === 0;
                }"
            ],

            ['status', 'in', 'range' => [self::STATUS_ACTIVE, self::STATUS_INACTIVE, self::STATUS_LOCKED]],
            ['role', 'in', 'range' => [self::ROLE_PUPIL, self::ROLE_PARENTS, self::ROLE_COMPANY, self::ROLE_ROOT, self::ROLE_MANAGER, self::ROLE_TEACHER]],
            ['bitrix_sync_status', 'in', 'range' => [0, 1]],
            ['teacher_id', 'exist', 'targetRelation' => 'teacher'],
            ['created_by', 'exist', 'targetRelation' => 'createdAdmin'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'username' => 'Логин',
            'name' => 'Имя',
            'note' => 'Заметки',
            'phone' => 'Телефон',
            'phone2' => 'Доп. телефон',
            'phoneFormatted' => 'Телефон',
            'phone2Formatted' => 'Доп. телефон',
            'phoneFull' => 'Телефон',
            'phone2Full' => 'Доп. телефон',
            'role' => 'Уровень доступа',
            'status' => 'Статус',
            'money' => 'Баланс',
            'password' => 'Пароль',
            'teacher_id' => 'Учитель',
        ];
    }

    public function getPassword()
    {
        return '';
    }

    /**
     * @return ActiveQuery
     */
    public function getParent()
    {
        return $this->hasOne(User::class, ['id' => 'parent_id']);
    }

    /**
     * @return ActiveQuery
     */
    public function getChildren()
    {
        return $this->hasMany(User::class, ['parent_id' => 'id'])->orderBy('name')->inverseOf('parent');
    }

    /**
     * @return ActiveQuery
     */
    public function getNotLockedChildren()
    {
        return $this->hasMany(User::class, ['parent_id' => 'id'])->andWhere('status != :locked', [':locked' => self::STATUS_LOCKED])->orderBy('name');
    }

    /**
     * @return ActiveQuery
     */
    public function getTeacher()
    {
        return $this->hasOne(Teacher::class, ['id' => 'teacher_id'])->inverseOf('user');
    }

    /**
     * @return ActiveQuery
     */
    public function getGroupPupils()
    {
        return $this->hasMany(GroupPupil::class, ['user_id' => 'id'])->inverseOf('user');
    }

    /**
     * @return ActiveQuery
     */
    public function getGroups()
    {
        return $this->hasMany(Group::class, ['id' => 'group_id'])
            ->via('groupPupils');
    }

    /**
     * @return ActiveQuery
     */
    public function getActiveGroupPupils()
    {
        return $this->hasMany(GroupPupil::class, ['user_id' => 'id'])->andWhere([GroupPupil::tableName() . '.active' => GroupPupil::STATUS_ACTIVE]);
    }

    /**
     * @return ActiveQuery
     */
    public function getActiveGroups()
    {
        return $this->hasMany(Group::class, ['id' => 'group_id'])
            ->via('activeGroupPupils');
    }

    /**
     * @return ActiveQuery
     */
    public function getActions()
    {
        return $this->hasMany(Action::class, ['user_id' => 'id']);
    }

    /**
     * @return ActiveQuery
     */
    public function getActionsAsAdmin()
    {
        return $this->hasMany(Action::class, ['admin_id' => 'id']);
    }

    /**
     * @return ActiveQuery
     */
    public function getDebts()
    {
        return $this->hasMany(Debt::class, ['user_id' => 'id']);
    }

    /**
     * @return ActiveQuery
     */
    public function getEventMembers()
    {
        return $this->hasMany(EventMember::class, ['user_id' => 'id']);
    }

    /**
     * @return ActiveQuery
     */
    public function getContracts()
    {
        return $this->hasMany(Contract::class, ['user_id' => 'id'])->orderBy(['created_at' => SORT_DESC]);
    }

    /**
     * @return ActiveQuery
     */
    public function getPayments()
    {
        return $this->hasMany(Payment::class, ['user_id' => 'id'])->orderBy(['created_at' => SORT_DESC]);
    }

    /**
     * @return ActiveQuery
     */
    public function getPaymentsAsAdmin()
    {
        return $this->hasMany(Payment::class, ['admin_id' => 'id']);
    }

    /**
     * @return ActiveQuery
     */
    public function getCreatedAdmin()
    {
        return $this->hasOne(User::class, ['id' => 'created_by']);
    }

    /**
     * @return ActiveQuery
     */
    public function getConsultations()
    {
        return $this->hasMany(Consultation::class, ['user_id' => 'id'])->with('subject')->addOrderBy(['created_at' => SORT_DESC]);
    }

    /**
     * @return ActiveQuery
     */
    public function getWelcomeLessons()
    {
        return $this->hasMany(WelcomeLesson::class, ['user_id' => 'id'])->with(['group'])->addOrderBy(['lesson_date' => SORT_DESC]);
    }

    /**
     * @return string[]
     */
    public function getTelegramSettings(): array
    {
        return json_decode($this->getAttribute('tg_params'), true) ?: [];
    }

    /**
     * @param string[] $value
     */
    public function setTelegramSettings(array $value)
    {
        $this->setAttribute('tg_params', json_encode($value));
    }

    /**
     * @param Group $group
     *
     * @return Debt|null
     */
    public function getDebt(Group $group): ?Debt
    {
        foreach ($this->debts as $debt) {
            if ($debt->group_id == $group->id) {
                return $debt;
            }
        }

        return null;
    }

    public function getNameParts(): array
    {
        if (empty($this->name)) {
            return [];
        }
        $parts = explode(' ', $this->name);
        if (count($parts) > 2) {
            return [$parts[0], $parts[1], implode(' ', array_slice($parts, 2))];
        }

        return $parts;
    }

    public function getFirstName(): string
    {
        $nameParts = $this->nameParts;
        if (count($nameParts) === 1) {
            return $nameParts[0];
        } else {
            return $nameParts[1];
        }
    }

    public function getNameHidden(): string
    {
        $nameParts = $this->nameParts;
        $userName = MaskString::generate($nameParts[0], 2, 1, 4);
        if (!empty($nameParts[1])) {
            $userName .= ' ' . MaskString::generate($nameParts[1], 1, 0, 4);
        }

        return $userName;
    }

    public function getGroupPupilsAggregated(): array
    {
        $result = [];
        /** @var GroupPupil $groupPupil */
        foreach ($this->getGroupPupils()->orderBy(['date_start' => SORT_DESC])->with('group')->all() as $groupPupil) {
            if (!array_key_exists($groupPupil->group_id, $result)) {
                $result[$groupPupil->group_id] = [];
            }
            $result[$groupPupil->group_id][] = $groupPupil;
        }

        return $result;
    }

    public function beforeValidate()
    {
        if ($this->isNewRecord) {
            $this->generateAuthKey();
            $this->created_by = Yii::$app->user->id;
        } elseif (!$this->created_by) {
            $this->created_by = Yii::$app->user->id ?? self::SYSTEM_USER_ID;
        }
        if ($this->password) {
            $this->setPassword($this->password);
        }
        if ($this->password_hash === null) {
            $this->password_hash = '';
        }
        $this->name = preg_replace('#[ ]+#', ' ', $this->name);

        if (!parent::beforeValidate()) {
            return false;
        }

        return true;
    }


    public static function findIdentity($id)
    {
        return static::findOne(['id' => $id, 'status' => self::STATUS_ACTIVE]);
    }


    public static function findIdentityByAccessToken($token, $type = null)
    {
        throw new NotSupportedException('"findIdentityByAccessToken" is not implemented.');
    }

    /**
     * Finds user by username
     *
     * @param string $username
     *
     * @return static|null
     */
    public static function findByUsername($username)
    {
        return static::findOne(['username' => $username, 'status' => self::STATUS_ACTIVE]);
    }

    /**
     * Finds user by password reset token
     *
     * @param string $token password reset token
     *
     * @return static|null
     */
    public static function findByPasswordResetToken($token)
    {
        if (!static::isPasswordResetTokenValid($token)) {
            return null;
        }

        return static::findOne([
            'password_reset_token' => $token,
            'status' => self::STATUS_ACTIVE,
        ]);
    }

    /**
     * Finds out if password reset token is valid
     *
     * @param string $token password reset token
     *
     * @return boolean
     */
    public static function isPasswordResetTokenValid($token)
    {
        if (empty($token)) {
            return false;
        }

        $timestamp = (int) substr($token, strrpos($token, '_') + 1);
        $expire = Yii::$app->params['user.passwordResetTokenExpire'];

        return $timestamp + $expire >= time();
    }


    public function getId()
    {
        return $this->getPrimaryKey();
    }


    public function getAuthKey()
    {
        return $this->auth_key;
    }


    public function validateAuthKey($authKey)
    {
        return $this->getAuthKey() === $authKey;
    }

    /**
     * Validates password
     *
     * @param string $password password to validate
     *
     * @return boolean if password provided is valid for current user
     */
    public function validatePassword($password)
    {
        return Yii::$app->security->validatePassword($password, $this->password_hash);
    }

    /**
     * Generates password hash from password and sets it to the model
     *
     * @param string $password
     *
     * @throws yii\base\Exception
     */
    public function setPassword($password)
    {
        $this->password_hash = Yii::$app->security->generatePasswordHash($password);
    }

    /**
     * Generates "remember me" authentication key
     */
    public function generateAuthKey()
    {
        $this->auth_key = Yii::$app->security->generateRandomString();
    }

    /**
     * Generates new password reset token
     */
    public function generatePasswordResetToken()
    {
        $this->password_reset_token = Yii::$app->security->generateRandomString() . '_' . time();
    }

    /**
     * Removes password reset token
     */
    public function removePasswordResetToken()
    {
        $this->password_reset_token = null;
    }

    public function isTeacher(): bool
    {
        return self::ROLE_TEACHER === $this->role;
    }

    public function isAgeConfirmed(): bool
    {
        return $this->age_confirmed > 0 || ($this->parent_id && $this->parent->age_confirmed > 0);
    }

    /**
     * @return User[]|array
     */
    public static function findActiveCustomersByPhone(string $phoneFull): array
    {
        return self::find()
            ->andWhere(['or', ['phone' => $phoneFull], ['phone2' => $phoneFull]])
            ->andWhere(['role' => [User::ROLE_PARENTS, User::ROLE_COMPANY, User::ROLE_PUPIL]])
            ->andWhere(['!=', 'status', User::STATUS_LOCKED])
            ->all();
    }

    public static function findActiveCustomerById(int $id): ?User
    {
        return self::find()
            ->andWhere(['id' => $id])
            ->andWhere(['!=', 'status', User::STATUS_LOCKED])
            ->one();
    }

    public static function getNameById(int $userId): ?string
    {
        $user = self::findOne($userId);

        return $user?->name;
    }
}

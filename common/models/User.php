<?php
namespace common\models;

use backend\models\Action;
use backend\models\EventMember;
use common\models\traits\InsertedUpdated;
use common\models\traits\Phone;
use common\models\traits\Phone2;
use yii;
use yii\base\NotSupportedException;
use \common\components\extended\ActiveRecord;
use yii\web\IdentityInterface;

/**
 * User model
 *
 * @property int $id
 * @property string $name
 * @property string $username
 * @property string $password_hash
 * @property string $password_reset_token
 * @property string $auth_key
 * @property int $status
 * @property int $money
 * @property int $role
 * @property int $parent_id
 * @property string $password write-only password
 * @property User $parent
 * @property User[] $children
 * @property GroupPupil[] $groupPupils
 * @property Group[] $groups
 * @property GroupPupil[] $activeGroupPupils
 * @property Group[] $activeGroups
 * @property Action[] $actions
 * @property Action[] $actionsAsAdmin
 * @property Payment[] $payments
 * @property Payment[] $paymentsAsAdmin
 * @property Debt[] $debts
 * @property int $balance
 */
class User extends ActiveRecord implements IdentityInterface
{
    use InsertedUpdated, Phone, Phone2;

    const SYSTEM_USER_ID = 4;
    
    const STATUS_LOCKED = 0;
    const STATUS_ACTIVE = 10;
    const STATUS_INACTIVE = 20;

    const ROLE_ROOT   = 1;
    const ROLE_PARENTS = 2;
    const ROLE_PUPIL   = 3;
    const ROLE_COMPANY = 4;
    const ROLE_MANAGER = 10;

    public $password;

    const SCENARIO_ADMIN = 'admin';
    const SCENARIO_USER = 'user';

    public function scenarios()
    {
        $scenarios = parent::scenarios();
        $scenarios[self::SCENARIO_ADMIN] = ['username', 'name', 'phone', 'phone2', 'phoneFormatted', 'phone2Formatted', 'role', 'password'];
        $scenarios[self::SCENARIO_USER] = ['name', 'phone', 'phone2', 'phoneFormatted', 'phone2Formatted'];
        return $scenarios;
    }

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%user}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
//            [['username', 'name', 'auth_key', 'password_hash'], 'required'],
            [['name'], 'required', 'whenClient' => "function (attribute, value) {
                var parentExpr = /\[parent\].*/;
                var companyExpr = /\[company\].*/;
                if (parentExpr.test(attribute.name)
                    && ($('input[name=\"person_type\"]:checked').val() != \"" . self::ROLE_PARENTS . "\" || $('input[name=\"parent_type\"]:checked').val() != \"new\")) {
                    return false;
                }
                if (companyExpr.test(attribute.name)
                    && ($('input[name=\"person_type\"]:checked').val() != \"" . self::ROLE_COMPANY . "\" || $('input[name=\"company_type\"]:checked').val() != \"new\")) {
                    return false;
                }
                return true;
            }"],
            [['status', 'money', 'role', 'parent_id'], 'integer'],
            [['username', 'password_hash', 'password_reset_token'], 'string', 'max' => 255],
            [['name'], 'string', 'max' => 127],
            [['auth_key'], 'string', 'max' => 32],
            [['phone', 'phone2'], 'string', 'min' => 13, 'max' => 13],
            [['phone', 'phone2'], 'match', 'pattern' => '#^\+998\d{9}$#'],
            [['phoneFormatted', 'phone2Formatted'], 'string', 'min' => 11, 'max' => 11],
            [['phoneFormatted', 'phone2Formatted'], 'match', 'pattern' => '#^\d{2} \d{3}-\d{4}$#'],
            [['username'], 'unique'],
            [['phone'], 'unique', 'on' => self::SCENARIO_USER],
            [['password_reset_token'], 'unique'],
            [['password'], 'safe'],
            [['password'], 'required', 'on' => self::SCENARIO_ADMIN, 'when' => function ($model, $attribute) {return $model->isNewRecord;},
                'whenClient' => "function (attribute, value) {
                    return $(attribute.input).data(\"id\").length == 0;
                }"],
            [['phoneFormatted'], 'required', 'on' => self::SCENARIO_USER,
                'whenClient' => "function (attribute, value) {
                    var parentExpr = /\[parent\].*/;
                    var companyExpr = /\[company\].*/;
                    if (parentExpr.test(attribute.name)
                        && ($('input[name=\"person_type\"]:checked').val() != \"" . self::ROLE_PARENTS . "\" || $('input[name=\"parent_type\"]:checked').val() != \"new\")) {
                        return false;
                    }
                    if (companyExpr.test(attribute.name)
                        && ($('input[name=\"person_type\"]:checked').val() != \"" . self::ROLE_COMPANY . "\" || $('input[name=\"company_type\"]:checked').val() != \"new\")) {
                        return false;
                    }
                    return true;
                }"],

            ['status', 'default', 'value' => self::STATUS_INACTIVE, 'on' => self::SCENARIO_USER],
            ['status', 'default', 'value' => self::STATUS_ACTIVE,  'on' => self::SCENARIO_ADMIN],
            ['status', 'in', 'range' => [self::STATUS_ACTIVE, self::STATUS_INACTIVE, self::STATUS_LOCKED]],
            ['role', 'default', 'value' => self::ROLE_PUPIL],
            ['role', 'in', 'range' => [self::ROLE_PUPIL, self::ROLE_PARENTS, self::ROLE_COMPANY, self::ROLE_ROOT, self::ROLE_MANAGER]],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id'        => 'ID',
            'username'  => 'Логин',
            'name'      => 'Имя',
            'phone' => 'Телефон',
            'phone2' => 'Доп. телефон',
            'phoneFormatted' => 'Телефон',
            'phone2Formatted' => 'Доп. телефон',
            'phoneFull' => 'Телефон',
            'phone2Full' => 'Доп. телефон',
            'role' => 'Уровень доступа',
            'status'    => 'Статус',
            'money'     => 'Баланс',
            'password'  => 'Пароль',
        ];
    }

    public function getPassword() {return '';}

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getParent()
    {
        return $this->hasOne(User::class, ['id' => 'parent_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getChildren()
    {
        return $this->hasMany(User::class, ['parent_id' => 'id'])->orderBy('name')->inverseOf('parent');
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getGroupPupils()
    {
        return $this->hasMany(GroupPupil::class, ['user_id' => 'id'])->inverseOf('user');
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getGroups()
    {
        return $this->hasMany(Group::class, ['id' => 'group_id'])
            ->via('pupilGroups');
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getActiveGroupPupils()
    {
        return $this->hasMany(GroupPupil::class, ['user_id' => 'id'])->andWhere(['active' => GroupPupil::STATUS_ACTIVE]);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getActiveGroups()
    {
        return $this->hasMany(Group::class, ['id' => 'group_id'])
            ->via('activeGroupPupils');
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getActions()
    {
        return $this->hasMany(Action::class, ['user_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getActionsAsAdmin()
    {
        return $this->hasMany(Action::class, ['admin_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getDebts()
    {
        return $this->hasMany(Debt::class, ['user_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getEventMembers()
    {
        return $this->hasMany(EventMember::class, ['user_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPayments()
    {
        return $this->hasMany(Payment::class, ['user_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPaymentsAsAdmin()
    {
        return $this->hasMany(Payment::class, ['admin_id' => 'id']);
    }

    /**
     * @param array $data
     * @param null $formName
     * @return bool
     */
    public function load($data, $formName = null)
    {
        if (!parent::load($data, $formName)) return false;

        if ($this->name !== null) $this->name = trim($this->name);

        $this->loadPhone();
        $this->loadPhone2();

        return true;
    }

    public function beforeValidate() {
        if (parent::beforeValidate()) {
            if ($this->isNewRecord) {
                $this->generateAuthKey();
            }
            if ($this->password) {
                $this->setPassword($this->password);
            }
            return true;
        } else {
            return false;
        }
    }

    /**
     * @inheritdoc
     */
    public static function findIdentity($id)
    {
        return static::findOne(['id' => $id, 'status' => self::STATUS_ACTIVE]);
    }

    /**
     * @inheritdoc
     */
    public static function findIdentityByAccessToken($token, $type = null)
    {
        throw new NotSupportedException('"findIdentityByAccessToken" is not implemented.');
    }

    /**
     * Finds user by username
     *
     * @param string $username
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

    /**
     * @inheritdoc
     */
    public function getId()
    {
        return $this->getPrimaryKey();
    }

    /**
     * @inheritdoc
     */
    public function getAuthKey()
    {
        return $this->auth_key;
    }

    /**
     * @inheritdoc
     */
    public function validateAuthKey($authKey)
    {
        return $this->getAuthKey() === $authKey;
    }

    /**
     * Validates password
     *
     * @param string $password password to validate
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
}

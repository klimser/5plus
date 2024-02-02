<?php

namespace backend\components;

use common\models\User;
use Yii;
use yii\base\Component;

class UserComponent extends Component
{
    public const ROLE_LABELS = [
        User::ROLE_ROOT   => 'Администратор',
        User::ROLE_MANAGER => 'Офис-менеджер',
        User::ROLE_COMPANY => 'Компания',
        User::ROLE_PARENTS => 'Родители',
        User::ROLE_STUDENT   => 'Студент',
        User::ROLE_TEACHER   => 'Учитель',
    ];

    public const ACL_RULES = [
        'registrator' => 'Регистрация договоров',
        'cashier' => 'Кассир (приём денег)',
        'groupManager' => 'Редактор групп',
        'moneyMover' => 'Перенос остатков денег в другую группу',
        'relieveDebt' => 'Возвраты',
        'scheduler' => 'Расписание',
        'accountant' => 'Бухгалтер (зарплата)',
        'support' => 'Техподдержка (заявки и т п)',
        'content' => 'Контент-менеджер',
        'sendPush' => 'Отправлять сообщения подписчикам в Telegram',
        'viewNotes' => 'Смотреть темы групп',
        'reportMoneyTotal' => 'Просмотр финансового отчета по всем группам',
        'adminSchedule' => 'Администратор расписания (изменение статуса занятий/студентов)',
        'reportWelcomeLesson' => 'Просмотр отчёта по пробным урокам',
    ];
    
    public const ACL_TEACHER_RULES =[
        'viewNotes' => 'Смотреть темы групп',
    ];

    /**
     * @return string[]
     */
    public static function getFirstLetters(): array
    {
        return Yii::$app->cache->getOrSet('user.letters', function() {
            $letters = User::find()
                ->select(['SUBSTR(name, 1, 1)'])
                ->andWhere('name != ""')
                ->andWhere('status != :locked', ['locked' => User::STATUS_LOCKED])
                ->distinct(true)
                ->asArray(true)
                ->column();
            array_walk($letters, function (&$value) {
                $value = mb_strtoupper($value, 'UTF-8');
            });
            sort($letters);
            
            return $letters;
        });
    }

    /**
     * @return int[]
     */
    public static function getStartYears(): array
    {
        return Yii::$app->cache->getOrSet('user.years', function() {
            /** @var User[] $users */
            $years = User::find()
                ->select(['SUBSTR(created_at, 1, 4)'])
                ->andWhere('status != :locked', ['locked' => User::STATUS_LOCKED])
                ->distinct(true)
                ->asArray(true)
                ->column();
            array_walk($years, function(&$value){$value = intval($value);});
            sort($years);
            
            return $years;
        });
    }

    public static function clearSearchCache()
    {
        Yii::$app->cache->delete('user.letters');
        Yii::$app->cache->delete('user.years');
    }

    public static function isPhoneUsed(int $role, ?string $phone, ?string $phone2 = null, ?User $currentUser = null): bool
    {
        $phones = [];
        if ($phone) {
            $phones[] = $phone;
        }
        if ($phone2) {
            $phones[] = $phone2;
        }
        if (empty($phones)) {
            return false;
        }

        $qB = User::find()
            ->alias('u')
            ->andWhere(['u.role' => $role])
            ->andWhere(['!=', 'u.status', User::STATUS_LOCKED])
            ->andWhere('u.phone IN (:phones) OR u.phone2 IN (:phones)', [':phones' => implode(', ', $phones)]);
        if ($currentUser && $currentUser->id) {
            $qB->andWhere(['not', ['u.id' => $currentUser->id]]);
        }
        return null !== $qB->one();
    }
}

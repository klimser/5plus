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
        User::ROLE_PUPIL   => 'Студент',
        User::ROLE_TEACHER   => 'Учитель',
    ];

    public const ACL_RULES = [
        'registrator' => 'Регистрация договоров',
        'cashier' => 'Кассир (приём денег)',
        'groupManager' => 'Редактор групп',
        'moneyMover' => 'Перенос остатков денег в другую группу',
        'scheduler' => 'Расписание',
        'accountant' => 'Бухгалтер (зарплата)',
        'support' => 'Техподдержка (заявки и т п)',
        'content' => 'Контент-менеджер',
        'sendPush' => 'Отправлять сообщения подписчикам в Telegram',
    ];

    /**
     * @return string[]
     */
    public static function getFirstLetters(): array
    {
        $letters = Yii::$app->cache->get('user.letters');
        if (!$letters) {
            $letters = User::find()
                ->select(['SUBSTR(name, 1, 1)'])
                ->andWhere('name != ""')
                ->andWhere('status != :locked', ['locked' => User::STATUS_LOCKED])
                ->distinct(true)
                ->asArray(true)
                ->column();
            array_walk($letters, function(&$value){$value = mb_strtoupper($value, 'UTF-8');});
            sort($letters);
            Yii::$app->cache->set('user.letters', $letters);
        }
        return $letters;
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
        if (empty($phone)) throw new \Exception('Phone is mandatory');
        $phones = [$phone];
        if ($phone2) {
            $phones[] = $phone2;
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

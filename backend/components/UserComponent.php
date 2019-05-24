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
        $years = Yii::$app->cache->get('user.years');
        if (!$years) {
            /** @var User[] $users */
            $years = User::find()
                ->select(['SUBSTR(created_at, 1, 4)'])
                ->andWhere('status != :locked', ['locked' => User::STATUS_LOCKED])
                ->distinct(true)
                ->asArray(true)
                ->column();
            array_walk($years, function(&$value){$value = intval($value);});
            sort($years);
            Yii::$app->cache->set('user.years', $years);
        }
        return $years;
    }

    public static function clearSearchCache()
    {
        Yii::$app->cache->delete('user.letters');
        Yii::$app->cache->delete('user.years');
    }

    public static function isPhoneUsed(int $role, string $phone, ?string $phone2 = null): bool
    {
        $qB = User::find()
            ->orWhere(['phone' => $phone])
            ->orWhere(['phone2' => $phone]);
        if ($phone2) {
            $qB->orWhere(['phone' => $phone2])
                ->orWhere(['phone2' => $phone2]);
        }
        $qB->andWhere(['role' => $role]);
        return null !== $qB->one();
    }
}

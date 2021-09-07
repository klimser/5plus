<?php

namespace common\components;

use common\components\helpers\StringGenerator;
use common\models\AgeConfirmation;
use common\models\ConfirmationCode;
use common\models\User;
use DateTimeImmutable;
use Yii;
use yii\base\BaseObject;
use yii\log\Logger;

class AgeValidator extends BaseObject
{
    const TEMPLATE_AGE_CONFIRMATION = 610;

    /**
     * @param User[]|array $users
     */
    public function add(string $phone, int $validDays = 7, array $users = []): bool
    {
        $transaction = Yii::$app->db->beginTransaction();

        try {
            if (!$this->invalidate($phone, $users)) {
                $transaction->rollBack();
                return false;
            }
            $code = StringGenerator::generate(5, true, false, true);
            $ageConfirmations = [];
            foreach ($users as $user) {
                $ageConfirmation = new AgeConfirmation();
                $ageConfirmation->phone = $phone;
                $ageConfirmation->user_id = $user->id;
                $ageConfirmation->validUntilDate = date_create_immutable("+$validDays days");
                $ageConfirmation->generateHash($code);
                if (!$ageConfirmation->save()) {
                    $transaction->rollBack();
                    ComponentContainer::getErrorLogger()
                        ->logError('age-validator/add', $ageConfirmation->getErrorsAsString(), true);
                    return false;
                }
                $ageConfirmations[] = $ageConfirmation;
            }

            Yii::$app->log->logger->log('Code: '  . $code, Logger::LEVEL_INFO);
            $params = [
                'code' => $code,
            ];
            ComponentContainer::getPaygramApi()
                ->sendSms(self::TEMPLATE_AGE_CONFIRMATION, substr($phone, -12, 12), $params);

            foreach ($ageConfirmations as $ageConfirmation) {
                $ageConfirmation->status = AgeConfirmation::STATUS_SENT;
                $ageConfirmation->sentDate = new DateTimeImmutable();
                $ageConfirmation->save();
            }
            $transaction->commit();
            return true;
        } catch (\Exception $exception) {
            $transaction->rollBack();
            ComponentContainer::getErrorLogger()
                ->logError('age-validator/add', $exception->getMessage() . ' - ' . $exception->getTraceAsString(), true);
            return false;
        }
    }

    /**
     * @param string $phoneFull
     * @param User|null $user
     * @return array|AgeConfirmation[]
     */
    public function findValid(string $phoneFull, ?User $user = null): array
    {
        $qB = AgeConfirmation::find()
            ->andWhere(['phone' => $phoneFull, 'status' => AgeConfirmation::STATUS_SENT])
            ->andWhere(['>', 'valid_until', date('Y-m-d H:i:s')]);
        if ($user) {
            $ids = [$user->id];
            if ($user->parent_id) {
                $ids[] = $user->parent_id;
            }
            foreach ($user->children as $child) {
                $ids[] = $child->id;
            }
            $qB->andWhere(['user_id' => $ids]);
        }
        return $qB->all();
    }
    
    public function validate(string $phoneFull, ?User $user, string $code): bool
    {
        $ageConfirmations = $this->findValid($phoneFull, $user);
        $transaction = Yii::$app->db->beginTransaction();
        foreach ($ageConfirmations as $ageConfirmation) {
            if ($ageConfirmation->validateHash($code)) {
                $ageConfirmation->confirmed_at = date('Y-m-d H:i:s');
                $ageConfirmation->status = AgeConfirmation::STATUS_CONFIRMED;
                $ageConfirmation->user->age_confirmed = 1;
                if ($ageConfirmation->user->parent_id
                    && ($ageConfirmation->user->parent->phone === $phoneFull || $ageConfirmation->user->parent->phone2 === $phoneFull)) {
                    $ageConfirmation->user->parent->age_confirmed = 1;
                }
                if (!$ageConfirmation->save() || !$ageConfirmation->user->save() || ($ageConfirmation->user->parent_id && !$ageConfirmation->user->parent->save())) {
                    $transaction->rollBack();
                    ComponentContainer::getErrorLogger()
                        ->logError('age-validator/validate', $ageConfirmation->getErrorsAsString() . ' - ' . $ageConfirmation->user->getErrorsAsString(), true);
                    return false;
                }
            } else {
                $transaction->rollBack();
                $ageConfirmation->attempt++;
                $ageConfirmation->save();
                return false;
            }
        }
        $transaction->commit();
        return true;
    }
    
    public function invalidate(string $phone, array $users = [])
    {
        $qB = AgeConfirmation::find()
            ->andWhere(['phone' => $phone])
            ->andWhere(['>=', 'valid_until', date('Y-m-d H:i:s')]);
        if (!empty($users)) {
            $qB->andWhere(['user_id' => array_map(fn ($user) => $user->id, $users)]);
        }
        /** @var AgeConfirmation[] $entries */
        $entries = $qB->all();
        foreach ($entries as $entry) {
            $entry->validUntilDate = date_create_immutable('-1 minute');
            $entry->status = AgeConfirmation::STATUS_INVALID;
            if (!$entry->save()) {
                ComponentContainer::getErrorLogger()
                    ->logError('age-validator/invalidate', $entry->getErrorsAsString(), true);
                return false;
            }
        }
        return true;
    }
    
    public function getBlockUntilDate(string $phoneFull): ?DateTimeImmutable
    {
        /** @var AgeConfirmation[] $messages */
        $messages = AgeConfirmation::find()
            ->andWhere(['phone' => $phoneFull])
            ->andWhere(['not', ['status' => AgeConfirmation::STATUS_NEW]])
            ->addOrderBy(['created_at' => SORT_DESC])
            ->all();
        $blockDate = null;
        foreach ($messages as $i => $message) {
            switch ($i) {
                case 0:
                    if ($message->sentDate > new DateTimeImmutable('-1 minute')) {
                        $blockDate = $message->sentDate->modify('+1 minute');
                    }
                    break;
                case 1:
                    if ($message->sentDate > new DateTimeImmutable('-2 minute')) {
                        $blockDate = $message->sentDate->modify('+2 minute');
                    }
                    break;
                case 2:
                    if ($message->sentDate > new DateTimeImmutable('-5 minute')) {
                        $blockDate = $message->sentDate->modify('+5 minute');
                    }
                    break;
                default:
                    if ($message->sentDate > new DateTimeImmutable('-30 minute')) {
                        $blockDate = $message->sentDate->modify('+30 minute');
                    }
                    break;
            }
        }
        return $blockDate;
    }
}

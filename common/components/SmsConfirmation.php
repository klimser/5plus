<?php

namespace common\components;

use common\components\helpers\StringGenerator;
use common\models\ConfirmationCode;
use yii\base\BaseObject;

class SmsConfirmation extends BaseObject
{
    const TEMPLATE_CONFIRMATION_CODE = 5;

    public static function add(string $phone, int $validMinutes = 10): bool
    {
        $transaction = \Yii::$app->db->beginTransaction();

        try {
            if (!self::invalidate($phone)) {
                $transaction->rollBack();
                return false;
            }

            $confirmation = new ConfirmationCode();
            $confirmation->phone = $phone;
            $confirmation->code = StringGenerator::generate(5);
            $confirmation->validUntilDate = date_create("+$validMinutes minutes");
            if (!$confirmation->save()) {
                ComponentContainer::getErrorLogger()
                    ->logError('sms-confirmation/add', $confirmation->getErrorsAsString(), true);
                $transaction->rollBack();
                return false;
            }

            $params = [
                'code' => $confirmation->code,
                'valid' => $validMinutes
            ];
            ComponentContainer::getPaygramApi()
                ->sendSms(self::TEMPLATE_CONFIRMATION_CODE, substr($phone, -12, 12), $params);

            $transaction->commit();
            return true;
        } catch (\Exception $exception) {
            ComponentContainer::getErrorLogger()
                ->logError('sms-confirmation/add', $exception->getMessage() . ' - ' . $exception->getTraceAsString(), true);
            $transaction->rollBack();
            return false;
        }
    }

    public static function validate(string $phone, string $code): bool
    {
        $confirmationCode = ConfirmationCode::find()
            ->andWhere(['phone' => $conversation->notes['sms_confirm_phone'] ?? null, 'code' => $code])
            ->andWhere(['>', 'valid_until' => date('Y-m-d H:i:s')])
            ->one();
        return $confirmationCode !== null;
    }
    
    public static function invalidate($phone)
    {
        /** @var ConfirmationCode[] $entries */
        $entries = ConfirmationCode::find()
            ->andWhere(['phone' => $phone])
            ->andWhere(['>=', 'valid_until', date('Y-m-d H:i:s')])
            ->all();
        foreach ($entries as $entry) {
            $entry->validUntilDate = date_create('-1 minute');
            if (!$entry->save()) {
                ComponentContainer::getErrorLogger()
                    ->logError('sms-confirmation/add', $entry->getErrorsAsString(), true);
                return false;
            }
        }
        return true;
    }
}

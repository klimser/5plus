<?php

/**
 * This file is part of the PHP Telegram Bot example-bot package.
 * https://github.com/php-telegram-bot/example-bot/
 *
 * (c) PHP Telegram Bot Team
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Pre-checkout query required for "/payment" command
 *
 * In this command you can perform any necessary verifications and checks
 * to allow or disallow the final checkout and payment of the invoice.
 */

namespace Longman\TelegramBot\Commands\SystemCommands;

use common\components\ComponentContainer;
use common\models\Contract;
use common\models\Group;
use common\models\GroupPupil;
use common\models\User;
use JsonException;
use Longman\TelegramBot\Commands\SystemCommand;
use Longman\TelegramBot\Entities\ServerResponse;

class PrecheckoutqueryCommand extends SystemCommand
{
    /**
     * @var string
     */
    protected $name = 'precheckoutquery';

    /**
     * @var string
     */
    protected $description = 'Pre-Checkout Query Handler';

    /**
     * @var string
     */
    protected $version = '0.1.0';

    /**
     * Main command execution
     *
     * @return ServerResponse
     */
    public function execute(): ServerResponse
    {
        $payload = $this->getPreCheckoutQuery()->getInvoicePayload();
        if (preg_match('#\d+#', $payload) && !empty(Contract::findOne(['number'=> $payload]))) {
            return $this->getPreCheckoutQuery()->answer(true);
        }

        try {
            $payloadData = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);
            if (!empty($payloadData['user_id']) && !empty($payloadData['group_id'])) {
                $user = User::findOne($payloadData['user_id']);
                $group = Group::findOne($payloadData['group_id']);
                if ($user && $group) {
                    if (!empty(GroupPupil::find()
                        ->andWhere(['user_id' => $user->id, 'group_id' => $group->id, 'active' => GroupPupil::STATUS_ACTIVE])
                        ->one())) {
                        return $this->getPreCheckoutQuery()->answer(true);
                    }
                }
            }
        } catch (JsonException $ex) {
            ComponentContainer::getErrorLogger()->logError('telegram/pay', 'Something impossible happened, payload: ' . $payload);
        }

        return $this->getPreCheckoutQuery()->answer(false, ['error_message' => 'Оплата не может быть принята. Попробуйте начать процесс оплаты сначала.']);
    }
}

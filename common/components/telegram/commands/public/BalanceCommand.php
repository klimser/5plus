<?php

namespace Longman\TelegramBot\Commands\UserCommands;

use common\components\helpers\WordForm;
use common\components\PaymentComponent;
use common\components\telegram\Request;
use common\models\User;
use Longman\TelegramBot\Commands\UserCommand;

/**
 * Balance command
 */
class BalanceCommand extends UserCommand
{
    /**
     * @var string
     */
    protected $name = 'balance';

    /**
     * @var string
     */
    protected $description = 'Получить текущую информацию об оплате';

    /**
     * @var string
     */
    protected $usage = '/balance';

    /**
     * @var string
     */
    protected $version = '1.0.0';

    /**
     * Command execute method
     *
     * @return mixed
     * @throws \Longman\TelegramBot\Exception\TelegramException
     */
    public function execute()
    {
        $chatId = $this->getMessage()->getChat()->getId();
        $data = ['chat_id' => $chatId];

        $getLineMap = function (User $pupil): array {
            $lineMap = [];
            foreach ($pupil->groupPupils as $groupPupil) {
                if ($groupPupil->active || $groupPupil->paid_lessons < 0) {
                    if (!array_key_exists($groupPupil->group_id, $lineMap)) {
                        $lineMap[$groupPupil->group_id] = ['name' => $groupPupil->group->legal_name, 'paid_lessons' => 0];
                    }
                    $lineMap[$groupPupil->group_id]['paid_lessons'] += $groupPupil->paid_lessons;
                }
            }
            return $lineMap;
        };

        $user = User::findOne(['tg_chat_id' => $chatId]);
        if (!$user) {
            $data = array_merge($data, SubscribeCommand::getSubscribeRequestData());
        } else {
            $resultList = [];
            if ($user->role == User::ROLE_PUPIL) {
                $resultList[] = [
                    'id' => $user->id,
                    'name' => $user->name,
                    'lines' => $getLineMap($user),
                ];
            } else {
                foreach ($user->children as $pupil) {
                    $resultList[] = [
                        'name' => $user->name,
                        'lines' => $getLineMap($pupil),
                    ];
                }
            }

            $data['parse_mode'] = 'Markdown';
            $single = count($resultList) == 1;
            $text = '';
            foreach ($resultList as $result) {
                if (!empty($result['lines'])) {
                    if (!$single) $text .= "*{$result['name']}*\n";
                    foreach ($result['lines'] as $groupId => $line) {
                        $text .= $line['name'] . ' - '
                            . ($line['paid_lessons'] >= 0 ? 'осталось' : 'долг') . ' '
                            . abs($line['paid_lessons']) . ' '
                            . WordForm::getLessonsForm(abs($line['paid_lessons']));
                        if ($line['paid_lessons'] <= 1) {
                            $text .= ' [Оплатить онлайн](' . PaymentComponent::getPaymentLink($result['id'], $groupId)->url . ')';
                        }
                        $text .= "\n";
                    }
                }
            }
            if (empty($text)) $text = 'Не найдено ни одной группы';
            $data['text'] = $text;
        }
        return Request::sendMessage($data);
    }
}

<?php

namespace Longman\TelegramBot\Commands\UserCommands;

use backend\models\Event;
use backend\models\EventMember;
use common\components\PaymentComponent;
use common\components\telegram\commands\ConversationTrait;
use common\components\telegram\commands\StepableTrait;
use common\components\telegram\Request;
use common\components\telegram\text\PublicMain;
use common\models\BotPush;
use common\models\GroupPupil;
use common\models\Payment;
use common\models\User;
use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Conversation;
use Longman\TelegramBot\Entities\Keyboard;
use Longman\TelegramBot\Entities\ServerResponse;

/**
 * Account command
 */
class AccountCommand extends UserCommand
{
    use ConversationTrait, StepableTrait;
    
    /**
     * @var string
     */
    protected $name = 'account';

    /**
     * @var string
     */
    protected $description = 'Личный кабинет';

    /**
     * @var string
     */
    protected $usage = '/account';

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
        $message = $this->getMessage();
        $chatId = $message->getChat()->getId();

        $user = User::findOne(['tg_chat_id' => $chatId]);
        if (!$user) {
            return $this->telegram->executeCommand('register');
        }
        
        if (!$conversation = $this->handleMessage($this->getMessage())) {
            return $this->telegram->executeCommand('start');
        }

        $result = $this->process($conversation);
        if ($result instanceof ServerResponse) {
            return $result;
        }

        return Request::sendMessage(array_merge(['chat_id' => $chatId], $result));
    }

    /**
     * @param Conversation $conversation
     * @return array|ServerResponse
     * @throws \Longman\TelegramBot\Exception\TelegramException
     */
    private function process(Conversation $conversation)
    {
        $message = $this->getMessage();
        $chatId = $message->getChat()->getId();
        /** @var User[] $users */
        $users = User::find()->andWhere(['tg_chat_id' => $chatId])->orderBy(['name' => SORT_ASC])->all();
        switch ($conversation->notes['step']) {
            case 1:
                $this->removeNote($conversation, 'step2');
                $buttons = [
                    [PublicMain::ACCOUNT_BUTTON_ATTEND, PublicMain::ACCOUNT_BUTTON_MARKS],
                    [PublicMain::ACCOUNT_BUTTON_BALANCE, PublicMain::ACCOUNT_BUTTON_PAYMENT],
                    [PublicMain::ACCOUNT_SUBSCRIPTION],
                    [PublicMain::ACCOUNT_EDIT_PUPILS],
                ];
                $row = [];
                foreach ($users as $user) {
                    if (!$user->telegramSettings['trusted']) {
                        $row[] = PublicMain::ACCOUNT_CONFIRM;
                        break;
                    }
                }
                $row[] = PublicMain::TO_MAIN;
                $buttons[] = $row;
                $keyboard = new Keyboard(...$buttons);
                $keyboard->setResizeKeyboard(true)->setSelective(false);
                
                return [
                    'text' => PublicMain::ACCOUNT_STEP_1_TEXT,
                    'reply_markup' => $keyboard,
                ];
                break;
            default:
                $parameter = $message->getText();
                if (array_key_exists('step2', $conversation->notes)) {
                    $parameter = $conversation->notes['step2'];
                    $this->removeNote($conversation, 'step2');
                }

                switch ($parameter) {
                    case PublicMain::ACCOUNT_BUTTON_ATTEND:
                        return $this->processAttend($conversation);
                        break;
                    case PublicMain::ACCOUNT_BUTTON_MARKS:
                        return $this->processMarks($conversation);
                        break;
                    case PublicMain::ACCOUNT_BUTTON_BALANCE:
                        return $this->processBalance($conversation);
                        break;
                    case PublicMain::ACCOUNT_BUTTON_PAYMENT:
                        return $this->processPayments($conversation);
                        break;
                    case PublicMain::ACCOUNT_SUBSCRIPTION:
                        return $this->processSubscribtion($conversation);
                        break;
                    case PublicMain::ACCOUNT_EDIT_PUPILS:
                        return $this->processUsers($conversation);
                        break;
                    case PublicMain::ACCOUNT_CONFIRM:
                        return $this->accountConfirm($conversation);
                        break;
                    default:
                        return $this->stepBack($conversation);
                        break;
                }
                break;
        }
    }
    
    
    
    private function processUserSelect(Conversation $conversation)
    {
        /** @var User[] $users */
        $users = User::find()
            ->andWhere(['tg_chat_id' => $this->getMessage()->getChat()->getId()])
            ->orderBy(['name' => SORT_ASC])
            ->all();

        if (count($users) === 1) {
            return $users[0];
        }
        switch ($conversation->notes['step']) {
            case 3:
                if (preg_match('#^(\d+)\D*#', $this->getMessage()->getText(), $matches)) {
                    /** @var User $user */
                    $user = User::find()
                        ->andWhere(['tg_chat_id' => $this->getMessage()->getChat()->getId()])
                        ->orderBy(['name' => SORT_ASC])
                        ->offset($matches[1] - 1)
                        ->limit(1)
                        ->one();
                    if ($user) {
                        return $user;
                    }
                }
                
                $conversation->notes['step'] = 2;
                $conversation->update();
                return $this->processUserSelect($conversation);
                break;
            default:
                $buttons = [];
                foreach ($users as $i => $user) {
                    $buttons[] = ($i + 1) . ' ' . ($user->telegramSettings['trusted'] ? $user->name : $user->nameHidden);
                }
                $buttons[] = [PublicMain::TO_BACK, PublicMain::TO_MAIN];
                $keyboard = new Keyboard(...$buttons);
                $keyboard->setResizeKeyboard(true)->setSelective(false);
                return [
                    'text' => PublicMain::ACCOUNT_STEP_2_SELECT_USER,
                    'reply_markup' => $keyboard,
                ];
        }
    }

    private function processAttend(Conversation $conversation)
    {
        if (($conversation->notes['step'] ?? 0) > 3) {
            return $this->stepBack($conversation);
        }
        
        $userResult = $this->processUserSelect($conversation);
        
        if (!$userResult instanceof User) {
            $this->addNote($conversation, 'step2', PublicMain::ACCOUNT_BUTTON_ATTEND);
            return $userResult;
        }

        /** @var EventMember[] $eventMembers */
        $eventMembers = EventMember::find()
            ->joinWith('event', true)
            ->joinWith('groupPupil')
            ->andWhere([
                Event::tableName() . '.status' => Event::STATUS_PASSED,
                EventMember::tableName() . '.status' => EventMember::STATUS_MISS,
                GroupPupil::tableName() . '.user_id' => $userResult->id,
            ])
            ->andWhere(['>', Event::tableName() . '.event_date', date_create('-90 days')->format('Y-m-d H:i:s')])
            ->with('event.group')
            ->orderBy([Event::tableName() . '.group_id' => SORT_ASC, Event::tableName() . '.event_date' => SORT_ASC])
            ->all();
        
        $rows = [];
        if (count($eventMembers) === 0) {
            $rows[] = PublicMain::ATTEND_NO_MISSED;
        } else {
            $rows[] = sprintf(PublicMain::ATTEND_HAS_MISSED, count($eventMembers));
            $groupId = null;
            foreach ($eventMembers as $eventMember) {
                if ($eventMember->event->group_id != $groupId) {
                    $rows[] = "*{$eventMember->event->group->name}*";
                    $groupId = $eventMember->event->group_id;
                }
                $rows[] = $eventMember->event->eventDateTime->format('d.m.Y H:i');
            }
        }

        $conversation->notes['step']--;
        $conversation->update();
        
        return [
            'parse_mode' => 'markdown',
            'text' => implode("\n", $rows),
        ];
    }

    private function processMarks(Conversation $conversation)
    {
        if (($conversation->notes['step'] ?? 0) > 3) {
            return $this->stepBack($conversation);
        }

        $userResult = $this->processUserSelect($conversation);

        if (!$userResult instanceof User) {
            $this->addNote($conversation, 'step2', PublicMain::ACCOUNT_BUTTON_MARKS);
            return $userResult;
        }

        /** @var EventMember[] $eventMembers */
        $eventMembers = EventMember::find()
            ->joinWith('event', true)
            ->joinWith('groupPupil')
            ->andWhere([
                Event::tableName() . '.status' => Event::STATUS_PASSED,
                EventMember::tableName() . '.status' => EventMember::STATUS_ATTEND,
                GroupPupil::tableName() . '.user_id' => $userResult->id,
            ])
            ->andWhere(['not', [EventMember::tableName() . '.mark' => null]])
            ->andWhere(['>', Event::tableName() . '.event_date', date_create('-30 days')->format('Y-m-d H:i:s')])
            ->with('event.group')
            ->orderBy([Event::tableName() . '.group_id' => SORT_ASC, Event::tableName() . '.event_date' => SORT_ASC])
            ->all();

        $rows = [];
        if (count($eventMembers) === 0) {
            $rows[] = PublicMain::MARKS_NONE;
        } else {
            $rows[] = PublicMain::MARKS_TEXT;
            $groupId = null;
            foreach ($eventMembers as $eventMember) {
                if ($eventMember->event->group_id != $groupId) {
                    $rows[] = "*{$eventMember->event->group->name}*";
                    $groupId = $eventMember->event->group_id;
                }
                $rows[] = $eventMember->event->eventDateTime->format('d.m.Y H:i') . ' - *' . $eventMember->mark . '*';
            }
        }

        $conversation->notes['step']--;
        $conversation->update();

        return [
            'parse_mode' => 'markdown',
            'text' => implode("\n", $rows),
        ];
    }

    private function processBalance(Conversation $conversation)
    {
        if (($conversation->notes['step'] ?? 0) > 3) {
            return $this->stepBack($conversation);
        }

        $userResult = $this->processUserSelect($conversation);

        if (!$userResult instanceof User) {
            $this->addNote($conversation, 'step2', PublicMain::ACCOUNT_BUTTON_BALANCE);
            return $userResult;
        }

        $rows = $groupSet = [];
        foreach ($userResult->groupPupils as $groupPupil) {
            if (!array_key_exists($groupPupil->group_id, $groupSet)) {
                $groupSet[$groupPupil->group_id] = true;
                $balance = Payment::find()
                    ->andWhere(['user_id' => $userResult->id, 'group_id' => $groupPupil->group_id])
                    ->select('SUM(amount)')
                    ->scalar();
                if ($groupPupil->active || $balance < 0) {
                    $rows[] = $groupPupil->group->name . ': *' . ($balance > 0 ? $balance : PublicMain::DEBT . ' ' . (0 - $balance)) . '* ' . PublicMain::CURRENCY_SIGN . ' '
                        . '(*' . abs($groupPupil->paid_lessons) . '* ' . PublicMain::LESSONS . ') '
                        . '[' . PublicMain::PAY_ONLINE . '](' . PaymentComponent::getPaymentLink($groupPupil->user_id, $groupPupil->group_id)->url . ')';
                }
            }
        }
        if (!empty($rows)) {
            array_unshift($rows, '*' . PublicMain::BANALCE_TEXT . '*');
        }

        $conversation->notes['step']--;
        $conversation->update();

        return [
            'parse_mode' => 'markdown',
            'disable_web_page_preview' => true,
            'text' => empty($rows) ? PublicMain::BALANCE_NO_GROUP : implode("\n", $rows),
        ];
    }

    private function processPayments(Conversation $conversation)
    {
        if (($conversation->notes['step'] ?? 0) > 3) {
            return $this->stepBack($conversation);
        }

        $userResult = $this->processUserSelect($conversation);

        if (!$userResult instanceof User) {
            $this->addNote($conversation, 'step2', PublicMain::ACCOUNT_BUTTON_PAYMENT);
            return $userResult;
        }

        /** @var Payment[] $payments */
        $payments = Payment::find()
            ->andWhere(['user_id' => $userResult->id])
            ->andWhere(['<', 'amount', 0])
            ->andWhere(['>', 'created_at', date_create('-30 days')->format('Y-m-d H:i:s')])
            ->orderBy(['group_id' => SORT_ASC, 'created_at' => SORT_ASC])
            ->with('group')
            ->all();
        
        $rows = $groupSet = [];
        foreach ($payments as $payment) {
            if (!array_key_exists($payment->group_id, $groupSet)) {
                $groupSet[$payment->group_id] = true;
                $rows[] = "\n*" . $payment->group->name . '*';
            }
            $rows[] = $payment->createDate->format('d.m.Y') . ' - ' . abs($payment->amount) . PublicMain::CURRENCY_SIGN;
        }
        if (!empty($rows)) {
            array_unshift($rows, '*' . PublicMain::PAYMENT_TEXT . '*');
        }

        $conversation->notes['step']--;
        $conversation->update();

        return [
            'parse_mode' => 'markdown',
            'text' => empty($rows) ? PublicMain::PAYMENT_NO_PAYMENTS : implode("\n", $rows),
        ];
    }
    
    private function processSubscribtion(Conversation $conversation)
    {
        $this->addNote($conversation, 'step2', PublicMain::ACCOUNT_SUBSCRIPTION);

        if (($conversation->notes['step'] ?? 0) > 3) {
            return $this->stepBack($conversation);
        }
        
        if ($conversation->notes['step'] === 3) {
            if (preg_match('#^(\d+) ([' . PublicMain::ICON_CHECK . PublicMain::ICON_CROSS . '])#u', $this->getMessage()->getText(), $matches)
                || (preg_match('#^([' . PublicMain::ICON_CHECK . PublicMain::ICON_CROSS . '])#u', $this->getMessage()->getText(), $matches))) {
                $offset = count($matches) > 2 ? $matches[1] - 1 : 0;
                $icon = count($matches) > 2 ? $matches[2] : $matches[1];
                /** @var User $user */
                $user = User::find()
                    ->andWhere(['tg_chat_id' => $this->getMessage()->getChat()->getId()])
                    ->orderBy(['name' => SORT_ASC])
                    ->offset($offset)
                    ->limit(1)
                    ->one();
                if ($user) {
                    $settings = $user->telegramSettings;
                    $settings['subscribe'] = ($icon === PublicMain::ICON_CHECK);
                    $user->telegramSettings = $settings;
                    $user->save();
                }
            }

            $conversation->notes['step']--;
            $conversation->update();
        }

        /** @var User[] $users */
        $users = User::find()
            ->andWhere(['tg_chat_id' => $this->getMessage()->getChat()->getId()])
            ->orderBy(['name' => SORT_ASC])
            ->all();
        
        if (count($users) === 1) {
            $text = $users[0]->telegramSettings['subscribe'] ? PublicMain::SUBSCRIPTION_YES : PublicMain::SUBSCRIPTION_NO;
            $buttons = [
                $users[0]->telegramSettings['subscribe'] ? PublicMain::SUBSCRIPTION_DISABLE : PublicMain::SUBSCRIPTION_ENABLE,
                [PublicMain::TO_BACK, PublicMain::TO_MAIN]
            ];
            $keyboard = new Keyboard(...$buttons);
            $keyboard->setResizeKeyboard(true)->setSelective(false);
        } else {
            $rows = $buttons = [];
            foreach ($users as $i => $user) {
                $rows[] = ($user->telegramSettings['trusted'] ? $user->name : $user->nameHidden) . ' - '
                    . ($user->telegramSettings['subscribe'] ? PublicMain::ICON_CHECK : PublicMain::ICON_CROSS);
                $buttons[] = ($i + 1) . ' ' . ($user->telegramSettings['subscribe'] ? PublicMain::ICON_CROSS : PublicMain::ICON_CHECK)
                    . ' ' . ($user->telegramSettings['trusted'] ? $user->name : $user->nameHidden);
            }
            $text = implode("\n", $rows);
            $buttons[] = [PublicMain::TO_BACK, PublicMain::TO_MAIN];
            $keyboard = new Keyboard(...$buttons);
            $keyboard->setResizeKeyboard(true)->setSelective(false);
        }

        return [
            'text' => $text,
            'reply_markup' => $keyboard,
        ];
    }
    
    private function processUsers(Conversation $conversation)
    {
        $this->addNote($conversation, 'step2', PublicMain::ACCOUNT_EDIT_PUPILS);
        
        $text = PublicMain::PUPILS_TEXT;
        
        if ($conversation->notes['step'] === 3) {
            if ($this->getMessage()->getText() === PublicMain::PUPILS_ADD) {
                return $this->telegram->executeCommand('register');
            }

            if (preg_match('#^(\d+) ' . PublicMain::ICON_REMOVE . '#u', $this->getMessage()->getText(), $matches)) {
                /** @var User $user */
                $user = User::find()
                    ->andWhere(['tg_chat_id' => $this->getMessage()->getChat()->getId()])
                    ->orderBy(['name' => SORT_ASC])
                    ->offset($matches[1] - 1)
                    ->limit(1)
                    ->one();
                if ($user) {
                    $user->tg_chat_id = null;
                    $user->save();
                }
            }

            $conversation->notes['step']--;
            $conversation->update();
        }

        /** @var User[] $users */
        $users = User::find()
            ->andWhere(['tg_chat_id' => $this->getMessage()->getChat()->getId()])
            ->orderBy(['name' => SORT_ASC])
            ->all();
        
        if (count($users) === 0) {
            return $this->telegram->executeCommand('start');
        }
        
        $buttons = [];
        foreach ($users as $i => $user) {
            $buttons[] = ($i + 1) . ' ' . PublicMain::ICON_REMOVE
                . ' ' . ($user->telegramSettings['trusted'] ? $user->name : $user->nameHidden);
        }
        $buttons[] = [PublicMain::PUPILS_ADD];
        $buttons[] = [PublicMain::TO_BACK, PublicMain::TO_MAIN];
        $keyboard = new Keyboard(...$buttons);
        $keyboard->setResizeKeyboard(true)->setSelective(false);
        
        return [
            'text' => $text,
            'reply_markup' => $keyboard,
        ];
    }
    
    private function accountConfirm(Conversation $conversation)
    {
        $message = $this->getMessage();
        $text = PublicMain::ACCOUNT_CONFIRM_TEXT;
        if ($message->getContact()) {
            $phone = $message->getContact()->getPhoneNumber();
            $phoneDigits = preg_replace('#\D#', '', $phone);
            $phoneFull = '+998' . substr($phoneDigits, -9);

            /** @var User[] $users */
            $users = User::find()
                ->andWhere(['role' => [User::ROLE_PUPIL, User::ROLE_PARENTS]])
                ->andWhere(['!=', 'status', User::STATUS_LOCKED])
                ->andWhere('phone = :phone OR phone2 = :phone', ['phone' => $phoneFull])
                ->all();
            if (count($users) === 0) {
                $text = PublicMain::ACCOUNT_CHECK_FAILED_NOT_FOUND;
                $conversation->notes['step']--;
                $conversation->update();
            } else {
                $affected = false;
                $rows = [];
                foreach ($users as $user) {
                    if ($user->tg_chat_id === $message->getChat()->getId()) {
                        continue;
                    }
                    if ($user->tg_chat_id && $user->tg_chat_id !== $message->getChat()->getId()) {
                        if ($user->telegramSettings['trusted']) {
                            $rows[] = $user->nameHidden . ' - ' . PublicMain::REGISTER_STEP_2_LOCKED;
                            continue;
                        } else {
                            $push = new BotPush();
                            $push->chat_id = $user->tg_chat_id;
                            $push->messageArray = ['text' => PublicMain::REGISTER_RESET_BY_TRUSTED];
                            $push->save();
                        }
                    }
                    $user->tg_chat_id = $message->getChat()->getId();
                    $user->save();
                    $rows[] = $user->name . ' - ' . PublicMain::ACCOUNT_CHECK_SUCCESS;
                    $affected = true;
                }
                if (!$affected) {
                    $rows[] = PublicMain::ACCOUNT_CHECK_SUCCESS_NONE;
                }

                Request::sendMessage([
                    'chat_id' => $message->getChat()->getId(),
                    'text' => implode("\n", $rows),
                ]);
                
                return $this->stepBack($conversation);
            }
        }

        $this->addNote($conversation, 'step2', PublicMain::ACCOUNT_CONFIRM);
        return [
            'text' => $text,
            'reply_markup' => PublicMain::getPhoneKeyboard(),
        ];
    }
}

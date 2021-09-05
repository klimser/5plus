<?php

namespace Longman\TelegramBot\Commands\UserCommands;

use backend\models\Event;
use backend\models\EventMember;
use common\components\ComponentContainer;
use common\components\helpers\WordForm;
use common\components\MoneyComponent;
use common\components\PaymentComponent;
use common\components\SmsConfirmation;
use common\components\telegram\commands\ConversationTrait;
use common\components\telegram\commands\StepableTrait;
use common\models\Company;
use common\models\Contract;
use common\models\Group;
use common\models\GroupPupil;
use JsonException;
use Longman\TelegramBot\Entities\Entity;
use Longman\TelegramBot\Entities\Payments\LabeledPrice;
use Longman\TelegramBot\Entities\Payments\SuccessfulPayment;
use Longman\TelegramBot\Request;
use common\components\telegram\text\PublicMain;
use common\models\Payment;
use common\models\User;
use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Conversation;
use Longman\TelegramBot\Entities\Keyboard;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Exception\TelegramException;
use Yii;
use yii\db\Query;

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
     * @throws TelegramException
     */
    public function execute(): ServerResponse
    {
        $message = $this->getMessage();
        $chatId = $message->getChat()->getId();

        $user = User::findOne(['status' => User::STATUS_ACTIVE, 'tg_chat_id' => $chatId]);
        if (!$user) {
            return $this->telegram->executeCommand('login');
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
     * @throws TelegramException
     */
    private function process(Conversation $conversation)
    {
        $message = $this->getMessage();
        $chatId = $message->getChat()->getId();

        $payload = trim($message->getText(true));
        if ('pay' === $payload) {
            $this->addNote($conversation, 'step', 2);
            $this->addNote($conversation, 'step2', PublicMain::BUTTON_PAY);
        }
        
        switch ($conversation->notes['step']) {
            case 1:
                $this->removeNote($conversation, 'step2');
                $buttons = [
                    PublicMain::BUTTON_PAY,
                    [PublicMain::ACCOUNT_BUTTON_ATTEND, PublicMain::ACCOUNT_BUTTON_MARKS],
                    [PublicMain::ACCOUNT_BUTTON_BALANCE, PublicMain::ACCOUNT_BUTTON_PAYMENT],
                    PublicMain::ACCOUNT_SUBSCRIPTION,
                    PublicMain::ACCOUNT_EDIT_PUPILS,
                ];
                $row = [];
                /** @var User[] $users */
                $users = User::find()->andWhere(['status' => User::STATUS_ACTIVE, 'tg_chat_id' => $chatId])->orderBy(['name' => SORT_ASC])->all();
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
                    'parse_mode' => 'MarkdownV2',
                    'text' => Entity::escapeMarkdownV2(PublicMain::ACCOUNT_STEP_1_TEXT),
                    'reply_markup' => $keyboard,
                ];
            default:
                $parameter = $message->getText();
                if (PublicMain::BUTTON_PAY === $parameter) {
                    $this->addNote($conversation, 'step', 2);
                    $this->removeNote($conversation, 'step2');
                }
                if (array_key_exists('step2', $conversation->notes)) {
                    $parameter = $conversation->notes['step2'];
                    $this->removeNote($conversation, 'step2');
                }

                switch ($parameter) {
                    case PublicMain::ACCOUNT_BUTTON_ATTEND:
                        return $this->processAttend($conversation);
                    case PublicMain::ACCOUNT_BUTTON_MARKS:
                        return $this->processMarks($conversation);
                    case PublicMain::ACCOUNT_BUTTON_BALANCE:
                        return $this->processBalance($conversation);
                    case PublicMain::ACCOUNT_BUTTON_PAYMENT:
                        return $this->processPayments($conversation);
                    case PublicMain::ACCOUNT_SUBSCRIPTION:
                        return $this->processSubscribtion($conversation);
                    case PublicMain::ACCOUNT_EDIT_PUPILS:
                        return $this->processUsers($conversation);
                    case PublicMain::ACCOUNT_CONFIRM:
                        return $this->accountConfirm($conversation);
                    case PublicMain::BUTTON_PAY:
                        return $this->processPay($conversation);
                    default:
                        return $this->stepBack($conversation);
                }
        }
    }

    /**
     * @return string[][]
     */
    private function getPupilList(): array
    {
        /** @var User[] $users */
        $users = User::find()
            ->alias('u')
            ->joinWith('children u2')
            ->andWhere(['u.tg_chat_id' => $this->getMessage()->getChat()->getId()])
            ->andWhere(['not', ['u.status' => User::STATUS_LOCKED]])
            ->andWhere(['or', ['u2.id' => null], ['not', ['u2.status' => User::STATUS_LOCKED]]])
            ->orderBy(['u.name' => SORT_ASC, 'u2.name' => SORT_ASC])
            ->all();

        $pupils = [];
        foreach ($users as $user) {
            $trusted = $user->telegramSettings['trusted'];
            if ($user->role === User::ROLE_PUPIL) {
                $pupils[] = ['name' => $trusted ? $user->name : $user->nameHidden, 'entity' => $user];
            } else {
                foreach ($user->children as $child) {
                    $pupils[] = ['name' => $trusted ? $child->name : $child->nameHidden, 'entity' => $child];
                }
            }
        }
        
        return $pupils;
    }
    
    private function processUserSelect(Conversation $conversation)
    {
        $pupils = $this->getPupilList();

        if (count($pupils) === 1) {
            return $pupils[0]['entity'];
        }
        if ($conversation->notes['step'] === 3
            && preg_match('#^(\d+)\D*#', $this->getMessage()->getText(), $matches)
            && isset($pupils[$matches[1] - 1])) {
            return $pupils[$matches[1] - 1]['entity'];
        }
                
        $buttons = [];
        foreach ($pupils as $i => $pupilData) {
            $buttons[] = ($i + 1) . ' ' . $pupilData['name'];
        }
        $buttons[] = [PublicMain::TO_BACK, PublicMain::TO_MAIN];
        $keyboard = new Keyboard(...$buttons);
        $keyboard->setResizeKeyboard(true)->setSelective(false);
        return [
            'parse_mode' => 'MarkdownV2',
            'text' => Entity::escapeMarkdownV2(PublicMain::ACCOUNT_STEP_2_SELECT_USER),
            'reply_markup' => $keyboard,
        ];
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
        if ($conversation->notes['step'] > 2) {
            $this->addNote($conversation, 'step2', PublicMain::ACCOUNT_BUTTON_ATTEND);
        }

        /** @var EventMember[] $eventMembers */
        $eventMembers = EventMember::find()
            ->alias('em')
            ->joinWith('event e', true)
            ->joinWith('groupPupil gp')
            ->andWhere([
                'e.status' => Event::STATUS_PASSED,
                'em.status' => EventMember::STATUS_MISS,
                'gp.user_id' => $userResult->id,
            ])
            ->andWhere(['>', 'e.event_date', date_create('-90 days')->format('Y-m-d H:i:s')])
            ->with('event.group')
            ->orderBy(['e.group_id' => SORT_ASC, 'e.event_date' => SORT_ASC])
            ->all();
        
        $rows = [];
        if (count($eventMembers) === 0) {
            $rows[] = Entity::escapeMarkdownV2(PublicMain::ATTEND_NO_MISSED);
        } else {
            $rows[] = Entity::escapeMarkdownV2(sprintf(PublicMain::ATTEND_HAS_MISSED, count($eventMembers)));
            $groupId = null;
            foreach ($eventMembers as $eventMember) {
                if ($eventMember->event->group_id != $groupId) {
                    $rows[] = '*' . Entity::escapeMarkdownV2($eventMember->event->group->legal_name) . '*';
                    $groupId = $eventMember->event->group_id;
                }
                $rows[] = Entity::escapeMarkdownV2($eventMember->event->eventDateTime->format('d.m.Y H:i'));
            }
        }

        $conversation->notes['step']--;
        $conversation->update();
        
        return [
            'parse_mode' => 'MarkdownV2',
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
        if ($conversation->notes['step'] > 2) {
            $this->addNote($conversation, 'step2', PublicMain::ACCOUNT_BUTTON_MARKS);
        }

        /** @var EventMember[] $eventMembers */
        $eventMembers = EventMember::find()
            ->alias('em')
            ->joinWith('event e', true)
            ->joinWith('groupPupil gp')
            ->andWhere([
                'e.status' => Event::STATUS_PASSED,
                'em.status' => EventMember::STATUS_ATTEND,
                'gp.user_id' => $userResult->id,
            ])
            ->andWhere(['not', ['em.mark' => null]])
            ->andWhere(['>', 'e.event_date', date_create('-30 days')->format('Y-m-d H:i:s')])
            ->with('event.group')
            ->orderBy(['e.group_id' => SORT_ASC, 'e.event_date' => SORT_ASC])
            ->all();

        $rows = [];
        if (count($eventMembers) === 0) {
            $rows[] = Entity::escapeMarkdownV2(PublicMain::MARKS_NONE);
        } else {
            $rows[] = Entity::escapeMarkdownV2(PublicMain::MARKS_TEXT);
            $groupId = null;
            foreach ($eventMembers as $eventMember) {
                if ($eventMember->event->group_id != $groupId) {
                    $rows[] = '*' . Entity::escapeMarkdownV2($eventMember->event->group->legal_name) . '*';
                    $groupId = $eventMember->event->group_id;
                }
                $rows[] = Entity::escapeMarkdownV2($eventMember->event->eventDateTime->format('d.m.Y H:i') . ' - ') . "*{$eventMember->mark}*";
            }
        }

        $conversation->notes['step']--;
        $conversation->update();

        return [
            'parse_mode' => 'MarkdownV2',
            'text' => implode("\n", $rows),
        ];
    }

    private function processBalance(Conversation $conversation)
    {
        $step = $conversation->notes['step'] ?? 0;
        if ($step > 4) {
            return $this->stepBack($conversation);
        }

        $userResult = $this->processUserSelect($conversation);
        $this->addNote($conversation, 'step2', PublicMain::ACCOUNT_BUTTON_BALANCE);

        if (!$userResult instanceof User) {
            return $userResult;
        }

        $rows = $groupSet = [];
        foreach ($userResult->groupPupils as $groupPupil) {
            if (!array_key_exists($groupPupil->group_id, $groupSet)) {
                $balance = $groupPupil->moneyLeft;
                if ($groupPupil->active || $balance < 0) {
                    $groupSet[$groupPupil->group_id] = true;
                    $rows[] = Entity::escapeMarkdownV2($groupPupil->group->legal_name) . ': *' . ($balance > 0 ? $balance : PublicMain::DEBT . ' ' . (0 - $balance)) . '* ' . PublicMain::CURRENCY_SIGN . ' '
                        . '\\(*' . abs($groupPupil->paid_lessons) . '* ' . WordForm::getLessonsForm(abs($groupPupil->paid_lessons)) . '\\) '
                        . '[' . PublicMain::PAY_ONLINE . '](' . PaymentComponent::getPaymentLink($groupPupil->user_id, $groupPupil->group_id)->url . ')';
                }
            }
        }
        if (!empty($rows)) {
            array_unshift($rows, '*' . Entity::escapeMarkdownV2(PublicMain::BANALCE_TEXT) . '*');
        }

        $buttons = [
            PublicMain::BUTTON_PAY,
            [PublicMain::TO_BACK, PublicMain::TO_MAIN],
        ];
        $keyboard = new Keyboard(...$buttons);
        $keyboard->setResizeKeyboard(true)->setSelective(false);

        return [
            'parse_mode' => 'MarkdownV2',
            'text' => empty($rows) ? Entity::escapeMarkdownV2(PublicMain::BALANCE_NO_GROUP) : implode("\n", $rows),
            'reply_markup' => $keyboard,
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
        if ($conversation->notes['step'] > 2) {
            $this->addNote($conversation, 'step2', PublicMain::ACCOUNT_BUTTON_PAYMENT);
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
                $rows[] = "\n*" . Entity::escapeMarkdownV2($payment->group->legal_name) . '*';
            }
            $rows[] = Entity::escapeMarkdownV2($payment->createDate->format('d.m.Y') . ' - ' . abs($payment->amount) . PublicMain::CURRENCY_SIGN);
        }
        if (!empty($rows)) {
            array_unshift($rows, '*' . Entity::escapeMarkdownV2(PublicMain::PAYMENT_TEXT) . '*');
        }

        $conversation->notes['step']--;
        $conversation->update();

        return [
            'parse_mode' => 'MarkdownV2',
            'text' => empty($rows) ? Entity::escapeMarkdownV2(PublicMain::PAYMENT_NO_PAYMENTS) : implode("\n", $rows),
        ];
    }
    
    private function processSubscribtion(Conversation $conversation)
    {
        $this->addNote($conversation, 'step2', PublicMain::ACCOUNT_SUBSCRIPTION);

        if (($conversation->notes['step'] ?? 0) > 3) {
            return $this->stepBack($conversation);
        }

        /** @var User[] $users */
        $users = User::find()
            ->andWhere(['status' => User::STATUS_ACTIVE, 'tg_chat_id' => $this->getMessage()->getChat()->getId()])
            ->orderBy(['name' => SORT_ASC])
            ->with(['children' => function(Query $query) { $query->orderBy(['name' => SORT_ASC]); }])
            ->all();
        
        if ($conversation->notes['step'] === 3) {
            if (preg_match('#^(\d+) ([' . PublicMain::ICON_CHECK . PublicMain::ICON_CROSS . '])#u', $this->getMessage()->getText(), $matches)
                || (preg_match('#^([' . PublicMain::ICON_CHECK . PublicMain::ICON_CROSS . '])#u', $this->getMessage()->getText(), $matches))) {
                
                $offset = count($matches) > 2 ? $matches[1] - 1 : 0;
                $icon = count($matches) > 2 ? $matches[2] : $matches[1];

                $pupils = [];
                foreach ($users as $user) {
                    if ($user->role === User::ROLE_PUPIL || count($user->children) > 0) {
                        $pupils[] = $user;
                    }
                }

                if (isset($pupils[$offset])) {
                    /** @var User $pupil */
                    $pupil = $pupils[$offset];
                    $settings = $pupil->telegramSettings;
                    $settings['subscribe'] = ($icon === PublicMain::ICON_CHECK);
                    $pupil->telegramSettings = $settings;
                    $pupil->save();
                }
            }

            $conversation->notes['step']--;
            $conversation->update();
        }
        
        if (count($users) === 1) {
            $text = Entity::escapeMarkdownV2($users[0]->telegramSettings['subscribe'] ? PublicMain::SUBSCRIPTION_YES : PublicMain::SUBSCRIPTION_NO);
            $buttons = [
                $users[0]->telegramSettings['subscribe'] ? PublicMain::SUBSCRIPTION_DISABLE : PublicMain::SUBSCRIPTION_ENABLE,
                [PublicMain::TO_BACK, PublicMain::TO_MAIN]
            ];
            $keyboard = new Keyboard(...$buttons);
            $keyboard->setResizeKeyboard(true)->setSelective(false);
        } else {
            $rows = $buttons = [];
            $i = 1;
            foreach ($users as $user) {
                $trusted = $user->telegramSettings['trusted'];
                $name = null;
                if ($user->role === User::ROLE_PUPIL) {
                    $name = $trusted ? $user->name : $user->nameHidden;
                } else {
                    if (count($user->children) > 0) {
                        $child = $user->children[0];
                        $name = ($trusted ? $child->name : $child->nameHidden);
                        if (count($user->children) > 1) {
                            $name .= ' +' . (count($user->children) - 1);
                        }
                    }
                }
                
                if ($name) {
                    $rows[] = Entity::escapeMarkdownV2($name . ' - ' . ($user->telegramSettings['subscribe'] ? PublicMain::ICON_CHECK : PublicMain::ICON_CROSS));
                    $buttons[] = $i . ' ' . ($user->telegramSettings['subscribe'] ? PublicMain::ICON_CROSS : PublicMain::ICON_CHECK) . ' ' . $name;
                    $i++;
                }
            }
            $text = implode("\n", $rows);
            $buttons[] = [PublicMain::TO_BACK, PublicMain::TO_MAIN];
            $keyboard = new Keyboard(...$buttons);
            $keyboard->setResizeKeyboard(true)->setSelective(false);
        }

        return [
            'parse_mode' => 'MarkdownV2',
            'text' => $text,
            'reply_markup' => $keyboard,
        ];
    }
    
    private function processUsers(Conversation $conversation)
    {
        $this->addNote($conversation, 'step2', PublicMain::ACCOUNT_EDIT_PUPILS);
        
        $text = Entity::escapeMarkdownV2(PublicMain::PUPILS_TEXT);
        
        if ($conversation->notes['step'] === 3) {
            if ($this->getMessage()->getText() === PublicMain::PUPILS_ADD) {
                return $this->telegram->executeCommand('login');
            }

            if (preg_match('#^(\d+) ' . PublicMain::ICON_REMOVE . '#u', $this->getMessage()->getText(), $matches)) {
                /** @var User $user */
                $user = User::find()
                    ->andWhere(['status' => User::STATUS_ACTIVE, 'tg_chat_id' => $this->getMessage()->getChat()->getId()])
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
            ->andWhere(['status' => User::STATUS_ACTIVE, 'tg_chat_id' => $this->getMessage()->getChat()->getId()])
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
            'parse_mode' => 'MarkdownV2',
            'text' => $text,
            'reply_markup' => $keyboard,
        ];
    }
    
    private function accountConfirm(Conversation $conversation)
    {
        $message = $this->getMessage();
        
        $filterUntrusted = function(User $user) {
            return !$user->telegramSettings['trusted'];
        };
        
        /** @var User[] $users */
        $users = User::find()
            ->andWhere([
                'status' => User::STATUS_ACTIVE,
                'role' => [User::ROLE_PUPIL, User::ROLE_PARENTS, User::ROLE_COMPANY],
                'tg_chat_id' => $message->getChat()->getId(),
            ])
            ->all();
        /** @var User[] $untrustedUsers */
        $untrustedUsers = array_filter($users, $filterUntrusted);

        if (empty($untrustedUsers)) {
            Request::sendMessage([
                'chat_id' => $message->getChat()->getId(),
                'text' => PublicMain::ACCOUNT_CONFIRM_NO_USERS,
            ]);
            
            return $this->stepBack($conversation);
        }

        $this->addNote($conversation, 'step2', PublicMain::ACCOUNT_CONFIRM);
        switch ($conversation->notes['step']) {
            case 2:
                return [
                    'parse_mode' => 'MarkdownV2',
                    'text' => Entity::escapeMarkdownV2(PublicMain::ACCOUNT_CONFIRM_TEXT),
                    'reply_markup' => PublicMain::getPhoneKeyboard([PublicMain::ACCOUNT_CONFIRM_SMS]),
                ];
                break;
            case 3:
                if ($message->getContact()) {
                    $phone = $message->getContact()->getPhoneNumber();
                    $phoneDigits = preg_replace('#\D#', '', $phone);
                    $phoneFull = '+998' . substr($phoneDigits, -9);

                    $conversation->notes['step']--;
                    $conversation->update();

                    /** @var User[] $users */
                    $users = User::find()
                        ->andWhere(['role' => [User::ROLE_PUPIL, User::ROLE_PARENTS, User::ROLE_COMPANY]])
                        ->andWhere(['!=', 'status', User::STATUS_LOCKED])
                        ->andWhere('phone = :phone OR phone2 = :phone', ['phone' => $phoneFull])
                        ->all();
                    if (count($users) === 0) {
                        return [
                            'parse_mode' => 'MarkdownV2',
                            'text' => Entity::escapeMarkdownV2(PublicMain::ACCOUNT_CHECK_FAILED_NOT_FOUND),
                            'reply_markup' => PublicMain::getPhoneKeyboard([PublicMain::ACCOUNT_CONFIRM_SMS]),
                        ];
                    }
                    
                    $rows = $this->confirmUsers($users);
                    if (empty($rows)) {
                        $rows[] = PublicMain::ACCOUNT_CHECK_SUCCESS_NONE;
                    }

                    Request::sendMessage([
                        'parse_mode' => 'MarkdownV2',
                        'chat_id' => $message->getChat()->getId(),
                        'text' => implode("\n", $rows),
                    ]);
                    $this->addNote($conversation, 'step', 2);

                    return $this->stepBack($conversation);
                } else {
                    return [
                        'parse_mode' => 'MarkdownV2',
                        'text' => Entity::escapeMarkdownV2(PublicMain::ACCOUNT_CONFIRM_STEP_3_TEXT),
                        'reply_markup' => $this->getPhonesListKeyboard($untrustedUsers),
                    ];
                }
                break;
            case 4:
                $phoneFull = '+' . preg_replace('#\D#', '', $message->getText());
                /** @var User[] $users */
                $users = User::find()
                    ->andWhere(['role' => [User::ROLE_PUPIL, User::ROLE_PARENTS, User::ROLE_COMPANY]])
                    ->andWhere(['!=', 'status', User::STATUS_LOCKED])
                    ->andWhere('phone = :phone OR phone2 = :phone', ['phone' => $phoneFull])
                    ->all();
                $untrustedPhoneUsers = array_filter($users, $filterUntrusted);
                
                $errorMessage = null;
                if (count($users) === 0) {
                    $errorMessage = PublicMain::ACCOUNT_CHECK_FAILED_NOT_FOUND;
                } elseif (count($untrustedPhoneUsers) === 0) {
                    $errorMessage = PublicMain::ACCOUNT_CHECK_SUCCESS_NONE;
                } else {
                    foreach ($untrustedPhoneUsers as $untrustedPhoneUser) {
                        $smsData = $untrustedPhoneUser->telegramSettings['sms_data'][$phoneFull] ?? [];
                        if (($smsData['sms_sent'] ?? 0) >= 3) {
                            $errorMessage = PublicMain::ACCOUNT_CONFIRM_SMS_LOCKED;
                            break;
                        }
                    }
                }
                
                if ($errorMessage) {
                    $conversation->notes['step']--;
                    $conversation->update();
                    
                    return [
                        'parse_mode' => 'MarkdownV2',
                        'text' => Entity::escapeMarkdownV2($errorMessage),
                        'reply_markup' => $this->getPhonesListKeyboard($untrustedUsers),
                    ];
                }
                
                if (SmsConfirmation::add($phoneFull, 10)) {
                    $this->addNote($conversation, 'sms_confirm_phone', $phoneFull);
                    $this->addNote($conversation, 'sms_confirm_attempts', 0);
                    
                    foreach ($untrustedPhoneUsers as $untrustedPhoneUser) {
                        $telegramData = $untrustedPhoneUser->telegramSettings;
                        $smsData = $telegramData['sms_data'][$phoneFull] ?? [];
                        $smsSent = 0;
                        if (empty($smsData['sms_date']) || $smsData['sms_date'] !== date('Y-m-d')) {
                            $smsData['sms_date'] = date('Y-m-d');
                        } else {
                            $smsSent = $smsData['sms_sent'] ?? 0;
                        }
                        $smsData['sms_sent'] = $smsSent + 1;
                        $telegramData['sms_data'][$phoneFull] = $smsData;
                        $untrustedPhoneUser->telegramSettings = $telegramData;
                        if (!$untrustedPhoneUser->save()) {
                            ComponentContainer::getErrorLogger()
                                ->logError('bot/confirm', $untrustedPhoneUser->getErrorsAsString(), true);
                        }
                    }
                    
                    return [
                        'parse_mode' => 'MarkdownV2',
                        'text' => Entity::escapeMarkdownV2(PublicMain::ACCOUNT_CONFIRM_STEP_4_TEXT),
                        'reply_markup' => Keyboard::remove(),
                    ];
                } else {
                    $conversation->notes['step']--;
                    $conversation->update();
                    
                    return [
                        'parse_mode' => 'MarkdownV2',
                        'text' => Entity::escapeMarkdownV2(PublicMain::ACCOUNT_CONFIRM_STEP_4_FAILED),
                        'reply_markup' => $this->getPhonesListKeyboard($untrustedUsers),
                    ];
                }
                break;
            case 5:
                $errorMessage = null;
                if (empty($conversation->notes['sms_confirm_phone'])) {
                    $errorMessage = PublicMain::ACCOUNT_CHECK_FAILED_NOT_FOUND;
                } elseif (($conversation->notes['sms_confirm_attempts'] ?? 0) >= 5) {
                    $errorMessage = PublicMain::ACCOUNT_CONFIRM_SMS_TOO_MUCH_ATTEMPTS;
                    SmsConfirmation::invalidate($conversation->notes['sms_confirm_phone']);
                } elseif (!SmsConfirmation::validate($conversation->notes['sms_confirm_phone'], trim($message->getText()))) {
                    $errorMessage = PublicMain::ACCOUNT_CHECK_FAILED_CODE_INVALID;
                    $this->addNote($conversation, 'sms_confirm_attempts', ($conversation->notes['sms_confirm_attempts'] ?? 0) + 1);
                }

                if ($errorMessage) {
                    $conversation->notes['step']--;
                    $conversation->update();

                    return [
                        'parse_mode' => 'MarkdownV2',
                        'text' => Entity::escapeMarkdownV2($errorMessage),
                        'reply_markup' => PublicMain::getBackAndMainKeyboard(),
                    ];
                }

                /** @var User[] $users */
                $users = User::find()
                    ->andWhere(['role' => [User::ROLE_PUPIL, User::ROLE_PARENTS, User::ROLE_COMPANY]])
                    ->andWhere(['!=', 'status', User::STATUS_LOCKED])
                    ->andWhere('phone = :phone OR phone2 = :phone', ['phone' => $conversation->notes['sms_confirm_phone']])
                    ->all();
                $rows = $this->confirmUsers($users);
                if (empty($rows)) {
                    $rows[] = Entity::escapeMarkdownV2(PublicMain::ACCOUNT_CHECK_SUCCESS_NONE);
                } else {
                    SmsConfirmation::invalidate($conversation->notes['sms_confirm_phone']);
                    $this->removeNote($conversation, 'sms_confirm_phone');
                }

                Request::sendMessage([
                    'parse_mode' => 'MarkdownV2',
                    'chat_id' => $message->getChat()->getId(),
                    'text' => implode("\n", $rows),
                ]);
                $this->addNote($conversation, 'step', 2);

                return $this->stepBack($conversation);
                break;
            default:
                return $this->stepBack($conversation);
        }
    }

    private function processPay(Conversation $conversation)
    {
        $step = $conversation->notes['step'] ?? 2;
        if (2 === $step) {
            $this->removeNote($conversation, 'userId');
        }

        $userResult = $this->processUserSelect($conversation);
        $this->addNote($conversation, 'step2', PublicMain::BUTTON_PAY);

        if (!$userResult instanceof User) {
            if (isset($conversation->notes['userId'])) {
                $user = User::find()
                    ->andWhere(['id' => $conversation->notes['userId']])
                    ->andWhere(['u.tg_chat_id' => $this->getMessage()->getChat()->getId()])
                    ->andWhere(['not', ['u.status' => User::STATUS_LOCKED]])
                    ->one();
            }
            if (isset($user)) {
                $userResult = $user;
            } else {
                return $userResult;
            }
        }

        $this->addNote($conversation, 'userId', $userResult->id);

        
        $buttons = $groupMap = [];
        foreach ($userResult->groupPupils as $groupPupil) {
            if (!array_key_exists($groupPupil->group_id, $groupMap) && ($groupPupil->active || $groupPupil->moneyLeft < 0)) {
                $groupMap[$groupPupil->group_id] = $groupPupil->group->legal_name;
                $buttons[] = Entity::escapeMarkdownV2($groupPupil->group->legal_name);
            }
        }

        if (count($buttons) > 1) {
            if (isset($conversation->notes['groupId']) && array_key_exists($conversation->notes['groupId'], $groupMap)) {
                $groupId = $conversation->notes['groupId'];
            } elseif ($key = array_search($this->getMessage()->getText(), $groupMap)) {
                $groupId = $key;
            } else {
                $buttons[] = [PublicMain::TO_BACK, PublicMain::TO_MAIN];
                $keyboard = new Keyboard(...$buttons);
                $keyboard->setResizeKeyboard(true)->setSelective(false);
                return [
                    'parse_mode' => 'MarkdownV2',
                    'text' => Entity::escapeMarkdownV2(PublicMain::PAY_CHOOSE_GROUP),
                    'reply_markup' => $keyboard,
                ];
            }
        } else {
            $groupIds = array_keys($groupMap);
            $groupId = reset($groupIds);
        }
        
        $this->addNote($conversation, 'groupId', $groupId);
        $group = Group::findOne($groupId);

        switch ($this->getMessage()->getText()) {
            case PublicMain::PAY_ONE_LESSON:
                $amount = $group->lesson_price;
                break;
            case PublicMain::PAY_ONE_MONTH:
                $amount = $group->priceMonth;
                break;
            default:
                $amount = intval($this->getMessage()->getText());
                break;
        }

        if ($amount > 0) {
            $conversation->notes['step']--;
            $conversation->update();
        }

        if ($amount >= PublicMain::PAY_MIN_AMOUNT) {
            $chatId = $this->getMessage()->getFrom()->getId();
            $prices = [
                new LabeledPrice(['label' => sprintf(PublicMain::PAY_ITEM_TITLE, $group->legal_name), 'amount' => $amount * 100]),
            ];
            $transaction = Yii::$app->db->beginTransaction();
            try {
                $contract = MoneyComponent::addPupilContract(Company::findOne(Company::COMPANY_EXCLUSIVE_ID), $userResult, $amount, $group);
                $transaction->commit();
            } catch (\Throwable $ex) {
                ComponentContainer::getErrorLogger()->logError('telegram/pay', 'Contract not created: ' . $ex->getMessage(), true);
                $transaction->rollBack();
            }

            return Request::sendInvoice([
                'chat_id'               => $chatId,
                'title'                 => sprintf(PublicMain::PAY_ITEM_TITLE, $group->legal_name),
                'description'           => sprintf(PublicMain::PAY_ITEM_DESCRIPTION, $group->legal_name, $group->lesson_price),
                'payload'               => !empty($contract) ? $contract->number : json_encode(['user_id' => $userResult->id, 'group_id' => $group->id, 'amount' => $amount]),
                'start_parameter'       => 'pay',
                'provider_token'        => $this->getConfig('payment_provider_token'),
                'currency'              => 'UZS',
                'prices'                => $prices,
                'need_shipping_address' => false,
                'is_flexible'           => false,
                'max_tip_amount'        => 1000000,
                'suggested_tip_amounts' => [100000, 200000, 500000, 1000000],
            ]);
        }

        $buttons = [
            [PublicMain::PAY_ONE_LESSON, PublicMain::PAY_ONE_MONTH],
            [PublicMain::TO_BACK, PublicMain::TO_MAIN],
        ];
        $keyboard = new Keyboard(...$buttons);
        $keyboard->setResizeKeyboard(true)->setSelective(false);
        return [
            'parse_mode' => 'MarkdownV2',
            'text' => Entity::escapeMarkdownV2(PublicMain::PAY_CHOOSE_AMOUNT),
            'reply_markup' => $keyboard,
        ];
    }

    /**
     * @param User[] $untrustedUsers
     * @return Keyboard
     */
    private function getPhonesListKeyboard(array $untrustedUsers): Keyboard
    {
        $phoneSet = [];
        foreach ($untrustedUsers as $untrustedUser) {
            $phoneSet[$untrustedUser->phone] = true;
            if ($untrustedUser->phone2) {
                $phoneSet[$untrustedUser->phone2] = true;
            }
        }

        $buttons = [];
        foreach ($phoneSet as $phone => $devNull) {
            $buttons[] = $phone;
        }
        $buttons[] = [PublicMain::TO_BACK, PublicMain::TO_MAIN];

        $keyboard = new Keyboard(...$buttons);
        $keyboard->setResizeKeyboard(true)->setSelective(false);
        
        return $keyboard;
    }
    
    private function confirmUsers(array $users): array
    {
        $rows = [];
        $chatId = $this->getMessage()->getChat()->getId();
        foreach ($users as $user) {
            if ($user->tg_chat_id !== $chatId) {
                continue;
            }
            if (!$user->telegramSettings['trusted']) {
                $user->telegramSettings = array_merge($user->telegramSettings, ['trusted' => true]);
                $user->save();
                $rows[] = Entity::escapeMarkdownV2($user->name . ' - ' . PublicMain::ACCOUNT_CHECK_SUCCESS);
            }
        }
        
        return $rows;
    }

    public static function handleSuccessfulPayment(SuccessfulPayment $payment, int $chatId): ServerResponse
    {
        $payload = $payment->getInvoicePayload();
        $transaction = Yii::$app->db->beginTransaction();
        $success = false;
        if (preg_match('#\d+#', $payload)) {
            $contract = Contract::findOne(['number' => $payload]);
            if (!empty($contract) && Contract::STATUS_NEW == $contract->status && (int) ($payment->getTotalAmount() / 100) == $contract->amount) {
                try {
                    MoneyComponent::payContract($contract, null, Contract::PAYMENT_TYPE_TELEGRAM_PAYME, $payment->getTelegramPaymentChargeId());
                    $contract->external_id = $payment->getProviderPaymentChargeId();
                    $contract->save();
                    $transaction->commit();
                    $success = true;
                } catch (\Throwable $ex) {
                    $transaction->rollBack();
                    ComponentContainer::getErrorLogger()->logError('telegram/pay', 'Payment is not applied!!! ' . $ex->getMessage(), true);
                }
            }

            if (!$success) {
                if (!$contract) {
                    $transaction->rollBack();
                    ComponentContainer::getErrorLogger()->logError('telegram/pay', 'Payment is not applied, contract does not exists: ' . $payload, true);
                } else {
                    try {
                        $newContract = MoneyComponent::addPupilContract(Company::findOne(Company::COMPANY_EXCLUSIVE_ID), $contract->user, (int) ($payment->getTotalAmount() / 100), $contract->group);
                        MoneyComponent::payContract($newContract, null, Contract::PAYMENT_TYPE_TELEGRAM_PAYME, $payment->getTelegramPaymentChargeId());
                        $newContract->external_id = $payment->getProviderPaymentChargeId();
                        $newContract->save();
                        $transaction->commit();
                        $success = true;
                    } catch (\Throwable $ex) {
                        $transaction->rollBack();
                        ComponentContainer::getErrorLogger()->logError('telegram/pay', 'Payment is not applied, Contract not created: ' . $ex->getMessage(), true);
                    }
                }
            }
        } else {
            try {
                $payloadData = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);
                if (!empty($payloadData['user_id']) && !empty($payloadData['group_id'])) {
                    $user = User::findOne($payloadData['user_id']);
                    $group = Group::findOne($payloadData['group_id']);
                    if ($user && $group) {
                        /** @var GroupPupil $groupPupil */
                        $groupPupil = GroupPupil::find()
                            ->andWhere(['user_id' => $user->id, 'group_id' => $group->id, 'active' => GroupPupil::STATUS_ACTIVE])
                            ->one();
                        if ($groupPupil) {
                            $newContract = MoneyComponent::addPupilContract(Company::findOne(Company::COMPANY_EXCLUSIVE_ID), $user, (int) ($payment->getTotalAmount() / 100), $group);
                            MoneyComponent::payContract($newContract, null, Contract::PAYMENT_TYPE_TELEGRAM_PAYME, $payment->getTelegramPaymentChargeId());
                            $newContract->external_id = $payment->getProviderPaymentChargeId();
                            $newContract->save();
                            $transaction->commit();
                            $success = true;
                        }
                    }
                }
            } catch (JsonException $ex) {
                $transaction->rollBack();
                ComponentContainer::getErrorLogger()->logError('telegram/pay', 'Payload malformed: ' . $payload, true);
            }
        }

        if (!$success) {
            ComponentContainer::getErrorLogger()->logError(
                'telegram/pay',
                'Unknown error, payload: ' . $payload . ', amount: ' . ($payment->getTotalAmount() / 100) . ', chatId: ' . $chatId,
                true
            );
        }

        return Request::sendMessage([
            'chat_id' => $chatId,
            'text' => 'Оплата принята. Спасибо. Ждем вас в Вашем учебном центре "Пять с плюсом"!',
        ]);
    }
}

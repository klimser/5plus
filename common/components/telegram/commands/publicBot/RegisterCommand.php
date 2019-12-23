<?php

namespace Longman\TelegramBot\Commands\UserCommands;

use common\components\ComponentContainer;
use common\components\telegram\commands\ConversationTrait;
use common\components\telegram\commands\StepableTrait;
use common\components\telegram\Request;
use common\components\telegram\text\PublicMain;
use common\models\BotPush;
use common\models\User;
use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Conversation;
use Longman\TelegramBot\Entities\Keyboard;
use Longman\TelegramBot\Entities\ServerResponse;

/**
 * Register command
 */
class RegisterCommand extends UserCommand
{
    use StepableTrait, ConversationTrait;
    
    /**
     * @var string
     */
    protected $name = 'register';

    /**
     * @var string
     */
    protected $description = 'Зарегистрироваться';

    /**
     * @var string
     */
    protected $usage = '/register';

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
        if (!$conversation = $this->handleMessage($this->getMessage())) {
            return $this->telegram->executeCommand('start');
        }

        $result = $this->process($conversation);
        if ($result instanceof ServerResponse) {
            return $result;
        }

        return Request::sendMessage(array_merge(['chat_id' => $this->getMessage()->getChat()->getId()], $result));
    }

    /**
     * @param Conversation $conversation
     * @return array|ServerResponse
     * @throws \Longman\TelegramBot\Exception\TelegramException
     */
    private function process(Conversation $conversation)
    {
        $message = $this->getMessage();
        switch ($conversation->notes['step']) {
            case 1:
                return [
                    'text' => PublicMain::REGISTER_STEP_1_TEXT,
                    'reply_markup' => PublicMain::getPhoneKeyboard(),
                ];
                break;
            case 2:
                if ($message->getText() !== PublicMain::TO_BACK) {
                    $phone = $message->getContact() ? $message->getContact()->getPhoneNumber() : $message->getText();
                    $phoneDigits = preg_replace('#\D#', '', $phone);
                    if (preg_match('#^\+#', $phone) && !preg_match('#^\+998#', $phone)) {
                        $conversation->notes['step']--;
                        $conversation->update();
                        return ['text' => PublicMain::ERROR_PHONE_PREFIX];
                    }
                    if (strlen($phoneDigits) < 9 || (preg_match('#^\+998#', $phone) && strlen($phoneDigits) < 12)) {
                        $conversation->notes['step']--;
                        $conversation->update();
                        return ['text' => PublicMain::ERROR_PHONE_LENGTH];
                    }
                    $this->addNote($conversation, 'phone', '+998' . substr($phoneDigits, -9));
                    
                    if ($message->getContact() && $message->getContact()->getUserId() === $message->getFrom()->getId()) {
                        $this->addNote($conversation, 'trusted', 1);
                    } else {
                        $this->removeNote($conversation, 'trusted');
                    }
                }

                $trusted = !empty($conversation->notes['trusted']);
                $users = User::find()
                    ->andWhere(['role' => [User::ROLE_PUPIL, User::ROLE_PARENTS]])
                    ->andWhere(['!=', 'status', User::STATUS_LOCKED])
                    ->andWhere('phone = :phone OR phone2 = :phone', ['phone' => $conversation->notes['phone']])
                    ->andWhere(['or', ['tg_chat_id' => null], ['!=', 'tg_chat_id', $message->getChat()->getId()]])
                    ->all();
                
                if (count($users) === 1) {
                    return $this->setUserRegister($conversation, $users[0], $trusted);
                }
                
                if (count($users) <= 0) {
                    $conversation->notes['step']--;
                    $conversation->update();
                    
                    return [
                        'text' => PublicMain::REGISTER_STEP_2_FAILED,
                        'reply_markup' => PublicMain::getPhoneKeyboard(),
                    ];
                }
                
                $data = [
                    'text' => PublicMain::REGISTER_STEP_2_MULTIPLE,
                    'reply_markup' => Keyboard::remove(),
                ];
                if ($trusted) {
                    $buttons = [];
                    foreach ($users as $user) {
                        $buttons[] = $user->name;
                    }
                    $buttons[] = [PublicMain::TO_BACK, PublicMain::TO_MAIN];
                    $keyboard = new Keyboard(...$buttons);
                    $keyboard->setResizeKeyboard(true)->setSelective(false);
                    $data['reply_markup'] = $keyboard;
                }
                
                return $data;
                break;
            case 3:
                $trusted = !empty($conversation->notes['trusted']);
                $users = User::find()
                    ->andWhere(['role' => [User::ROLE_PUPIL, User::ROLE_PARENTS]])
                    ->andWhere(['!=', 'status', User::STATUS_LOCKED])
                    ->andWhere('phone = :phone OR phone2 = :phone', ['phone' => $conversation->notes['phone']])
                    ->andWhere(['or', ['tg_chat_id' => null], ['!=', 'tg_chat_id', $message->getChat()->getId()]])
                    ->andWhere(['like', 'name', $message->getText()])
                    ->all();

                if (count($users) === 1) {
                    return $this->setUserRegister($conversation, $users[0], $trusted);
                }
                
                if ($trusted) {
                    $buttons = [];
                    foreach ($users as $user) {
                        $buttons[] = $user->name;
                    }
                    $buttons[] = [PublicMain::TO_BACK, PublicMain::TO_MAIN];
                    $keyboard = new Keyboard(...$buttons);
                    $keyboard->setResizeKeyboard(true)->setSelective(false);
                } else {
                    $keyboard = Keyboard::remove();
                }

                $data = ['reply_markup' => $keyboard];
                if (count($users) <= 0) {
                    $conversation->notes['step']--;
                    $conversation->update();

                    $data['text'] = PublicMain::REGISTER_STEP_3_FAILED;
                } else {
                    $data['text'] = PublicMain::REGISTER_STEP_3_MULTIPLE;
                }
                
                return $data;
                break;
        }
        return Request::emptyResponse();
    }

    /**
     * @param Conversation $conversation
     * @param User $user
     * @param bool $trusted
     * @return array|ServerResponse
     * @throws \Longman\TelegramBot\Exception\TelegramException
     */
    private function setUserRegister(Conversation $conversation, User $user, bool $trusted = false)
    {
        if ($user->tg_chat_id === $this->getMessage()->getChat()->getId()) {
            $this->flushConversation();
            $keyboard = new Keyboard([PublicMain::TO_MAIN]);
            $keyboard->setResizeKeyboard(true)->setSelective(false);
            return [
                'text' => PublicMain::REGISTER_SUCCESS,
                'reply_markup' => $keyboard,
            ];
        }
        
        if ($user->tg_chat_id) {
            if ($user->telegramSettings['trusted']) {
                $conversation->notes['step']--;
                $conversation->update();
                return [
                    'text' => PublicMain::REGISTER_STEP_2_LOCKED,
                    'reply_markup' => PublicMain::getPhoneKeyboard(),
                ];
            }
            
            if ($trusted) {
                $push = new BotPush();
                $push->chat_id = $user->tg_chat_id;
                $push->messageArray = ['text' => PublicMain::REGISTER_RESET_BY_TRUSTED];
                $push->save();
            } else {
                $conversation->notes['step']--;
                $conversation->update();
                return [
                    'text' => PublicMain::REGISTER_STEP_2_LOCKED_UNTRUSTED,
                    'reply_markup' => PublicMain::getPhoneKeyboard(),
                ];
            }
        }

        $user->tg_chat_id = $this->getMessage()->getChat()->getId();
        $telegramSettings = $user->telegramSettings;
        $telegramSettings['trusted'] = $trusted;
        $telegramSettings['subscribe'] = true;
        $user->telegramSettings = $telegramSettings;
        if ($user->save()) {
            return $this->telegram->executeCommand('account');
//            $data['text'] = PublicMain::REGISTER_SUCCESS;
        } else {
            ComponentContainer::getErrorLogger()
                ->logError('public-bot/register', $user->getErrorsAsString() . ', ChatID: ' . $this->getMessage()->getChat()->getId(), true);

            $conversation->notes['step']--;
            $conversation->update();
            $keyboard = new Keyboard([PublicMain::TO_MAIN]);
            $keyboard->setResizeKeyboard(true)->setSelective(false);
            $data = [
                'reply_markup' => $keyboard,
                'text' => 'Произошла ошибка, не удалось привязать аккаунт, мы уже знаем о случившемся и как можно скорее исправим это.',
            ];
        }
        
        return $data;
    }
}

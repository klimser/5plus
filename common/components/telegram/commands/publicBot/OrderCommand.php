<?php

namespace Longman\TelegramBot\Commands\UserCommands;

use common\components\ComponentContainer;
use common\components\telegram\commands\ConversationTrait;
use common\components\telegram\commands\StepableTrait;
use common\components\telegram\Request;
use common\components\telegram\text\PublicMain;
use common\models\Order;
use common\models\Subject;
use common\models\SubjectCategory;
use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Conversation;
use Longman\TelegramBot\Entities\Keyboard;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Exception\TelegramException;

/**
 * Order command
 */
class OrderCommand extends UserCommand
{
    use StepableTrait, ConversationTrait;
    
    /**
     * @var string
     */
    protected $name = 'order';

    /**
     * @var string
     */
    protected $description = 'Оставить заявку на бесплатное занятие';

    /**
     * @var string
     */
    protected $usage = '/order';

    /**
     * @var string
     */
    protected $version = '1.0.0';

    const STEP_BACK_TEXT = 'На предыдущий шаг';
    const CONFIRM_TEXT = 'Отправить';

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
     * @return array
     * @throws TelegramException
     */
    private function addOrder(Conversation $conversation): array
    {
        $order = new Order(['scenario' => Order::SCENARIO_TELEGRAM]);
        $order->subject = $conversation->notes['category'] . ': ' . $conversation->notes['subject'];
        $order->name = $conversation->notes['name'];
        $order->phone = $conversation->notes['phone'];
        $order->user_comment = $conversation->notes['comment'];
        if ($this->getMessage()->getChat()->getUsername()) {
            $order->tg_login = $this->getMessage()->getChat()->getUsername();
        }

        $keyboard = new Keyboard([PublicMain::TO_MAIN]);
        $keyboard->setResizeKeyboard(true)->setSelective(false);
        $data = [
            'parse_mode' => 'MarkdownV2',
            'reply_markup' => $keyboard,
        ];
        
        if (!$order->save(true)) {
            ComponentContainer::getErrorLogger()
                ->logError('Order.create', $order->getErrorsAsString() , true);
            Request::sendContact([
                'chat_id' => $this->getMessage()->getChat()->getId(),
                'phone_number' => PublicMain::CONTACT_PHONE,
                'first_name' => PublicMain::CONTACT_NAME,
                'last_name' => PublicMain::CONTACT_SURNAME,
                'vcard' => PublicMain::CONTACT_VCARD,
            ]);
            
            $data['text'] = Request::escapeMarkdownV2('К сожалению, не удалось добавить заявку. Наши технические специалисты уже получили уведомление и как можно скорее устранят проблему. Можете позвонить нашим менеджерам и записаться на занятие у них.');
        } else {
            $order->notifyAdmin();
            $data['text'] = Request::escapeMarkdownV2(PublicMain::ORDER_STEP_6_TEXT);
            $conversation->stop();
        }

        return $data;
    }

    /**
     * @param Conversation $conversation
     * @return array|ServerResponse
     * @throws TelegramException
     */
    private function process(Conversation $conversation)
    {
        $message = $this->getMessage();
        switch ($conversation->notes['step']) {
            case 1:
                $buttons = [];

                $userName = trim($this->getMessage()->getFrom()->getFirstName());
                if ($this->getMessage()->getFrom()->getLastName()) {
                    $userName .= ' ' . trim($this->getMessage()->getFrom()->getLastName());
                }
                if (!empty($userName)) {
                    $buttons[] = $userName;
                }

                if (!empty($conversation->notes['name']) && $conversation->notes['name'] !== $userName) {
                    $buttons[] = $conversation->notes['name'];
                }
                
                if (!empty($buttons)) {
                    $buttons[] = [PublicMain::TO_BACK, PublicMain::TO_MAIN];
                    $keyboard = new Keyboard(...$buttons);
                    $keyboard->setResizeKeyboard(true)->setSelective(false);
                } else {
                    $keyboard = Keyboard::remove();
                }
                return [
                    'parse_mode' => 'MarkdownV2',
                    'text' => Request::escapeMarkdownV2(PublicMain::ORDER_STEP_1_TEXT),
                    'reply_markup' => $keyboard,
                ];
                break;
            case 2:
                if ($message->getText() !== PublicMain::TO_BACK) {
                    $this->addNote($conversation, 'name', $message->getText());
                }
                return [
                    'parse_mode' => 'MarkdownV2',
                    'text' => Request::escapeMarkdownV2(PublicMain::ORDER_STEP_2_TEXT),
                    'reply_markup' => PublicMain::getPhoneKeyboard(),
                ];
                break;
            case 3:
                if ($message->getText() !== PublicMain::TO_BACK) {
                    $phone = $message->getContact() ? $message->getContact()->getPhoneNumber() : $message->getText();
                    $phoneDigits = preg_replace('#\D#', '', $phone);
//                    if (preg_match('#^\+#', $phone) && !preg_match('#^\+998#', $phone)) {
//                        $conversation->notes['step']--;
//                        $conversation->update();
//                        return [
//                            'parse_mode' => 'MarkdownV2',
//                            'text' => Request::escapeMarkdownV2(PublicMain::ERROR_PHONE_PREFIX),
//                        ];
//                    }
                    if (strlen($phone) > 50 || strlen($phoneDigits) < 9 || (preg_match('#^\+998#', $phone) && strlen($phoneDigits) < 12)) {
                        $conversation->notes['step']--;
                        $conversation->update();
                        return [
                            'parse_mode' => 'MarkdownV2',
                            'text' => Request::escapeMarkdownV2(PublicMain::ERROR_PHONE_LENGTH),
                        ];
                    }

                    $this->addNote($conversation, 'phone', $phone);
                }

                /** @var SubjectCategory[] $categories */
                $categories = SubjectCategory::find()->joinWith('activeSubjects', true, 'INNER JOIN')->all();
                $buttons = [];
                foreach ($categories as $category) {
                    $buttons[] = [$category->name];
                }
                $buttons[] = [PublicMain::TO_BACK, PublicMain::TO_MAIN];

                $keyboard = new Keyboard(...$buttons);
                $keyboard->setResizeKeyboard(true)->setSelective(false);
                
                return [
                    'parse_mode' => 'MarkdownV2',
                    'text' => Request::escapeMarkdownV2(PublicMain::ORDER_STEP_3_TEXT),
                    'reply_markup' => $keyboard,
                ];
                break;
            case 4:
                if ($message->getText() !== PublicMain::TO_BACK) {
                    /** @var SubjectCategory $subjectCategory */
                    $subjectCategory = SubjectCategory::find()->andWhere(['name' => $message->getText()])->one();
                    if (!$subjectCategory) {
                        $conversation->notes['step']--;
                        $conversation->update();
                        return [
                            'parse_mode' => 'MarkdownV2',
                            'text' => Request::escapeMarkdownV2(PublicMain::ORDER_STEP_3_ERROR),
                        ];
                    }

                    $this->addNote($conversation, 'category_id', $subjectCategory->id);
                    $this->addNote($conversation, 'category', $subjectCategory->name);
                } elseif (empty($conversation->notes['category'])) {
                    $this->stepBack($conversation);
                }

                $buttons = [];
                /** @var SubjectCategory $subjectCategory */
                $subjectCategory = SubjectCategory::findOne($conversation->notes['category_id']);
                /** @var Subject[] $subjects */
                $subjects = Subject::getActiveListQuery()->andWhere(['category_id' => $subjectCategory->id])->all();
                foreach ($subjects as $subject) {
                    $buttons[] = [$subject->name];
                }
                $buttons[] = [PublicMain::TO_BACK, PublicMain::TO_MAIN];

                $keyboard = new Keyboard(...$buttons);
                $keyboard->setResizeKeyboard(true)->setSelective(false);
                
                return [
                    'parse_mode' => 'MarkdownV2',
                    'text' => Request::escapeMarkdownV2(PublicMain::ORDER_STEP_4_TEXT),
                    'reply_markup' => $keyboard,
                ];
                break;
            case 5:
                if ($message->getText() !== PublicMain::TO_BACK) {
                    /** @var Subject $subject */
                    $subject = Subject::findOne(['name' => $message->getText()]);
                    if (!$subject) {
                        $conversation->notes['step']--;
                        $conversation->update();
                        return [
                            'parse_mode' => 'MarkdownV2',
                            'text' => Request::escapeMarkdownV2(PublicMain::ORDER_STEP_4_ERROR),
                        ];
                    }

                    $this->addNote($conversation, 'subject', $subject->name);
                }
                
                $keyboard = new Keyboard([PublicMain::ORDER_STEP_5_BUTTON], [PublicMain::TO_BACK, PublicMain::TO_MAIN]);
                $keyboard->setResizeKeyboard(true)->setSelective(false);
                return [
                    'parse_mode' => 'MarkdownV2',
                    'text' => Request::escapeMarkdownV2(PublicMain::ORDER_STEP_5_TEXT),
                    'reply_markup' => $keyboard,
                ];
                break;
            case 6:
                $this->addNote($conversation, 'comment', $message->getText() === PublicMain::ORDER_STEP_5_BUTTON ? '' : $message->getText());
                return $this->addOrder($conversation);
                break;
        }
        return Request::emptyResponse();
    }
}

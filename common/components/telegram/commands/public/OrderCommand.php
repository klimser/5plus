<?php

namespace Longman\TelegramBot\Commands\UserCommands;

use common\components\ComponentContainer;
use common\components\telegram\Request;
use common\models\Order;
use common\models\Subject;
use common\models\SubjectCategory;
use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Conversation;
use Longman\TelegramBot\Entities\Keyboard;

/**
 * Order command
 */
class OrderCommand extends UserCommand
{
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
        $data = [
            'chat_id' => $chatId,
        ];

        //If a conversation is busy, execute the conversation command after handling the message
        $conversation = new Conversation(
            $message->getFrom()->getId(),
            $chatId
        );

        //Fetch conversation command if it exists and execute it
        if ($conversation->exists()) {
            if ($conversation->getCommand() != $this->name) {
                return $this->telegram->executeCommand($conversation->getCommand());
            } else {
                if ($message->getText() != self::STEP_BACK_TEXT) {
                    $conversation->notes['step']++;
                    $conversation->update();
                } elseif ($conversation->notes['step'] == 1) {
                    $conversation->stop();
                    return null;
                }
            }
        } else {
            $conversation = new Conversation(
                $message->getFrom()->getId(),
                $chatId,
                $this->name
            );
            $conversation->notes = ['step' => 1];
            $conversation->update();
        }
        $data = array_merge($data, $this->getOrderRequestData($conversation));

        return Request::sendMessage($data);
    }

    /**
     * @return string
     */
    private function getGreetingText(): string
    {
        return 'Как вас зовут?';
    }

    private function getPhoneRequestKeyboard(): Keyboard
    {
        $buttons = [
            ['text' => 'Отправить свой телефон', 'request_contact' => true],
            self::STEP_BACK_TEXT,
        ];
        $keyboard = new Keyboard($buttons);
        $keyboard->setResizeKeyboard(true)
            ->setOneTimeKeyboard(true)
            ->setSelective(false);
        return $keyboard;
    }

    /**
     * @return string[]
     */
    private function getActiveCategoryButtons(): array
    {
        /** @var SubjectCategory[] $categories */
        $categories = SubjectCategory::find()->with('activeSubjects')->all();
        $buttons = [];
        foreach ($categories as $category) {
            if (!empty($category->activeSubjects)) {
                $buttons[] = [$category->name];
            }
        }
        return $buttons;
    }

    /**
     * @param SubjectCategory $subjectCategory
     * @return string[]
     */
    private function getSubjectButtons(SubjectCategory $subjectCategory): array
    {
        $buttons = [];
        /** @var Subject[] $subjects */
        $subjects = Subject::find()
            ->andWhere(['active' => Subject::STATUS_ACTIVE, 'category_id' => $subjectCategory->id])
            ->all();
        foreach ($subjects as $subject) {
            $buttons[] = [$subject->name];
        }
        return $buttons;
    }

    /**
     * @param Conversation $conversation
     * @return array
     */
    private function addOrder(Conversation $conversation): array
    {
        $order = new Order(['scenario' => Order::SCENARIO_TELEGRAM]);
        $order->subject = $conversation->notes['category'] . ': ' . $conversation->notes['subject'];
        $order->name = $conversation->notes['name'];
        $order->phone = $conversation->notes['phone'];
        $order->user_comment = $conversation->notes['comment'];

        if (!$order->save(true)) {
            ComponentContainer::getErrorLogger()
                ->logError('Order.create', $order->getErrorsAsString() , true);
            $data['text'] = 'К сожалению, не удалось добавить заявку. Наши технические специалисты уже получили уведомление и как можно скорее устранят проблему. Можете позвонить нашим менеджерам и записаться на занятие у них.';
            $data['contact'] = [
                'phone_number' => '+998712000350',
                'first_name' => 'Менеджер',
                'last_name' => '5 с плюсом',
            ];
        } else {
            $order->notifyAdmin();
            $data['text'] = 'Ваша заявка принята. Наши менеджеры свяжутся с вами в ближайшее время.';
            $conversation->stop();
        }
        $data['reply_markup'] = Keyboard::remove();

        return $data;
    }

    private function getOrderRequestData(Conversation $conversation): array
    {
        $message = $this->getMessage();
        $data = [];
        $keyboard = null;
        switch ($conversation->notes['step']) {
            case 1:
                $data['text'] = $this->getGreetingText();
                break;
            case 2:
                if ($message->getText() == self::STEP_BACK_TEXT) {
                    $conversation->notes['step']--;
                    $conversation->update();
                    $data['text'] = $this->getGreetingText();
                } else {
                    $conversation->notes['name'] = $message->getText();
                    $conversation->update();
                    $data['text'] = 'Ваш номер телефона для связи?';
                    $keyboard = $this->getPhoneRequestKeyboard();
                }
                break;
            case 3:
                if ($message->getText() == self::STEP_BACK_TEXT) {
                    $data['text'] = 'Ваш номер телефона для связи?';
                    $keyboard = $this->getPhoneRequestKeyboard();
                } else {
                    $phone = $message->getContact() ? $message->getContact()->getPhoneNumber() : $message->getText();
                    $phoneDigits = preg_replace('#\D#', '', $phone);
                    if (strlen($phoneDigits) < 9) {
                        $data['text'] = 'Укажите корректный номер телефона, как минимум код оператора и 7-значный номер';
                        $keyboard = $this->getPhoneRequestKeyboard();
                        $conversation->notes['step']--;
                        $conversation->update();
                    } elseif (preg_match('#^\+#', $phone) && !preg_match('#^\+998#', $phone)) {
                        $data['text'] = 'Укажите корректный номер телефона для Узбекистана';
                        $keyboard = $this->getPhoneRequestKeyboard();
                        $conversation->notes['step']--;
                        $conversation->update();
                    } else {
                        $conversation->notes['phone'] = '+998' . substr($phoneDigits, -9);
                        $conversation->update();
                        $data['text'] = 'Выберите направление';

                        $buttons = $this->getActiveCategoryButtons();
                        $buttons[] = [self::STEP_BACK_TEXT];

                        $keyboard = new Keyboard(...$buttons);
                        $keyboard->setResizeKeyboard(true)
                            ->setOneTimeKeyboard(true)
                            ->setSelective(false);
                    }
                }
                break;
            case 4:
                /** @var SubjectCategory $subjectCategory */
                $subjectCategory = SubjectCategory::find()->andWhere(['name' => $message->getText()])->one();
                if (!$subjectCategory) {
                    $data['text'] = 'Выберите направление';
                    $buttons = $this->getActiveCategoryButtons();
                    $conversation->notes['step']--;
                    $conversation->update();
                } else {
                    $conversation->notes['category'] = $subjectCategory->name;
                    $conversation->update();
                    $data['text'] = 'Выберите предмет';
                    $buttons = $this->getSubjectButtons($subjectCategory);
                }
                $buttons[] = [self::STEP_BACK_TEXT];

                $keyboard = new Keyboard(...$buttons);
                $keyboard->setResizeKeyboard(true)
                    ->setOneTimeKeyboard(true)
                    ->setSelective(false);
                break;
            case 5:
                /** @var SubjectCategory $subjectCategory */
                $subjectCategory = SubjectCategory::find()->andWhere(['name' => $conversation->notes['category']])->one();
                /** @var Subject $subject */
                $subject = Subject::find()->andWhere(['category_id' => $subjectCategory->id, 'name' => $message->getText()])->one();
                if (!$subject) {
                    $data['text'] = 'Выберите предмет';

                    $buttons = $this->getSubjectButtons($subjectCategory);
                    $buttons[] = [self::STEP_BACK_TEXT];

                    $keyboard = new Keyboard(...$buttons);
                    $keyboard->setResizeKeyboard(true)
                        ->setOneTimeKeyboard(true)
                        ->setSelective(false);

                    $conversation->notes['step']--;
                    $conversation->update();
                } else {
                    $conversation->notes['subject'] = $subject->name;
                    $conversation->update();
                    $data['text'] = 'Напишите дополнительную информацию к вашей заявке или нажмите "' . self::CONFIRM_TEXT . '".';
                    $keyboard = new Keyboard([self::CONFIRM_TEXT, self::STEP_BACK_TEXT]);
                    $keyboard->setResizeKeyboard(true)
                        ->setOneTimeKeyboard(true)
                        ->setSelective(false);
                }
                break;
            case 6:
                $conversation->notes['comment'] = $message->getText() == self::CONFIRM_TEXT ? null : $message->getText();
                $conversation->update();
                $data = $this->addOrder($conversation);
                break;
        }
        if ($keyboard) {
            $data['reply_markup'] = $keyboard;
        }

        return $data;
    }
}

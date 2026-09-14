<?php

namespace Longman\TelegramBot\Commands\UserCommands;

use common\components\telegram\commands\ConversationTrait;
use common\components\telegram\commands\StepableTrait;
use common\components\telegram\text\PublicMain;
use common\models\BotSubject;
use common\models\BotTeacher;
use common\models\BotTeacherSubject;
use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Conversation;
use Longman\TelegramBot\Entities\Entity;
use Longman\TelegramBot\Entities\InlineKeyboard;
use Longman\TelegramBot\Entities\InlineKeyboardButton;
use Longman\TelegramBot\Entities\Keyboard;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Exception\TelegramException;
use Longman\TelegramBot\Request;

/**
 * User "/help" command
 *
 * Command that lists all available commands and displays them in User and Admin sections.
 */
class InfoCommand extends UserCommand
{
    use StepableTrait, ConversationTrait;

    /**
     * @var string
     */
    protected $name = 'info';

    /**
     * @var string
     */
    protected $description = 'Вся информация о Вашем учебном центре "5 с плюсом"';

    /**
     * @var string
     */
    protected $usage = '/info';

    /**
     * @var string
     */
    protected $version = '1.1.0';

    public function execute(): ServerResponse
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
     *
     * @return array|ServerResponse|mixed
     * @throws TelegramException
     */
    protected function process(Conversation $conversation)
    {
        $message = $this->getMessage();
        switch ($conversation->notes['step']) {
            case 1:
                $this->removeNote($conversation, 'step2');
                $keyboard = $this->getMainKeyboard();

                return [
                    'parse_mode' => 'MarkdownV2',
                    'text' => Entity::escapeMarkdownV2(PublicMain::INFO_STEP_1_TEXT),
                    'reply_markup' => $keyboard,
                ];
            default:
                $parameter = $message->getText();
                if (array_key_exists('step2', $conversation->notes)) {
                    $parameter = $conversation->notes['step2'];
                    $this->removeNote($conversation, 'step2');
                }

                return match ($parameter) {
                    PublicMain::INFO_STEP_BUTTON_PRICES => $this->processPrices($conversation),
                    PublicMain::INFO_STEP_BUTTON_SUBJECTS => $this->processSubjects($conversation),
                    PublicMain::INFO_STEP_BUTTON_TEACHERS => $this->processTeachers($conversation),
                    default => $this->stepBack($conversation),
                };
        }
    }

    private function getMainKeyboard()
    {
        $buttons = [
            PublicMain::INFO_STEP_BUTTON_TEACHERS,
            PublicMain::INFO_STEP_BUTTON_SUBJECTS,
            PublicMain::INFO_STEP_BUTTON_PRICES,
            [PublicMain::TO_BACK, PublicMain::TO_MAIN],
        ];
        $keyboard = new Keyboard(...$buttons);
        $keyboard->setResizeKeyboard(true)->setSelective(false);

        return $keyboard;
    }

    private function processPrices(Conversation $conversation)
    {
        if (($conversation->notes['step'] ?? 0) > 2) {
            return $this->stepBack($conversation);
        }

        $conversation->notes['step']--;
        $conversation->update();

        return [
            'parse_mode' => 'MarkdownV2',
            'text' => sprintf(PublicMain::INFO_STEP_2_PRICE_TEXT, "https://5plus.uz/ru/price"),
        ];
    }

    private function processSubjects(Conversation $conversation)
    {
        /** @var BotSubject[] $subjects */
        $subjects = BotSubject::find()->orderBy('name->"$.ru" ASC')->all();

//        $textLines = ['*' . Entity::escapeMarkdownV2(PublicMain::INFO_STEP_3_SUBJECT_TEXT) . '*'];
        $textLines = [];
        foreach ($subjects as $subject) {
            $textLines[] = "[{$subject->name['ru']}](https://5plus.uz/ru{$subject->url})";
        }
        $conversation->notes['step']--;
        $conversation->update();

        return [
            'parse_mode' => 'MarkdownV2',
            'disable_web_page_preview' => true,
            'text' => implode("\n", $textLines),
            'reply_markup' => $this->getMainKeyboard(),
        ];
    }

    private function processTeachers(Conversation $conversation)
    {
        switch ($conversation->notes['step'] ?? 0) {
            case 2:
                $this->addNote($conversation, 'step2', PublicMain::INFO_STEP_BUTTON_TEACHERS);

                $subjectIds = BotTeacherSubject::find()
                    ->select('subject_id')
                    ->distinct()
                    ->column();

                /** @var BotSubject[] $subjects */
                $subjects = BotSubject::find()
                    ->andWhere(['in', 'id', $subjectIds])
                    ->orderBy('name->"$.ru" ASC')
                    ->all();
                $buttons = [];
                foreach ($subjects as $subject) {
                    $buttons[] = $subject->name['ru'];
                }

                $officeStaffCount = BotTeacher::find()
                    ->leftJoin(BotTeacherSubject::tableName(), BotTeacher::tableName() . '.id = ' . BotTeacherSubject::tableName() . '.teacher_id')
                    ->andWhere([BotTeacherSubject::tableName() . '.id' => null])
                    ->count(BotTeacher::tableName() . '.id');
                if ($officeStaffCount > 0) {
                    $buttons[] = 'Администрация';
                }
                $buttons[] = [PublicMain::TO_BACK, PublicMain::TO_MAIN];
                $keyboard = new Keyboard(...$buttons);
                $keyboard->setResizeKeyboard(true)->setSelective(false);

                return [
                    'parse_mode' => 'MarkdownV2',
                    'text' => Entity::escapeMarkdownV2(PublicMain::INFO_STEP_2_TEACHER_TEXT),
                    'reply_markup' => $keyboard,
                ];
            case 3:
                $this->addNote($conversation, 'step2', PublicMain::INFO_STEP_BUTTON_TEACHERS);
                $subjectName = $this->getMessage()->getText();

                if ($subjectName === 'Администрация') {
                    $text = PublicMain::INFO_STEP_3_TEACHER_TEXT_OFFICE;

                    /** @var BotTeacher[] $teachers */
                    $teachers = BotTeacher::find()
                        ->leftJoin(BotTeacherSubject::tableName(), BotTeacher::tableName() . '.id = ' . BotTeacherSubject::tableName() . '.teacher_id')
                        ->andWhere([BotTeacherSubject::tableName() . '.id' => null])
                        ->orWhere([BotTeacher::tableName() . '.id' => BotTeacher::CHIEF_OF_THE_BOARD_ID])
                        ->orderBy([BotTeacher::tableName() . '.name->"$.ru"' => SORT_ASC])
                        ->all();
                } else {
                    $text = PublicMain::INFO_STEP_3_TEACHER_TEXT;

                    /** @var BotSubject $subject */
                    $subject = BotSubject::find()
                        ->andWhere('name->"$.ru" = :subject', ['subject' => $subjectName])
                        ->one();
                    if (!$subject) {
                        return $this->stepBack($conversation);
                    }
                    /** @var BotTeacher[] $teachers */
                    $teachers = BotTeacher::find()
                        ->innerJoin(BotTeacherSubject::tableName(), BotTeacher::tableName() . '.id = ' . BotTeacherSubject::tableName() . '.teacher_id')
                        ->andWhere([BotTeacherSubject::tableName() . '.subject_id' => $subject->id])
                        ->orderBy([BotTeacher::tableName() . '.name->"$.ru"' => SORT_ASC])
                        ->all();
                }

                $chatId = $this->getMessage()->getChat()->getId();

                Request::sendMessage([
                    'chat_id' => $chatId,
                    'parse_mode' => 'MarkdownV2',
                    'text' => '*' . Entity::escapeMarkdownV2($text) . '*',
                    'reply_markup' => PublicMain::getBackAndMainKeyboard(),
                ]);

                foreach ($teachers as $teacher) {
                    $inlineKeyboard = new InlineKeyboard([
                        new InlineKeyboardButton([
                            'text' => 'ПОДРОБНЕЕ',
                            'callback_data' => "teacher_info {$teacher->id}",
                        ]),
                    ]);
                    Request::sendMessage([
                        'chat_id' => $chatId,
                        'parse_mode' => 'MarkdownV2',
                        'disable_web_page_preview' => true,
                        'text' => "[{$teacher->name['ru']}](https://5plus.uz/ru{$teacher->url})",
                        'reply_markup' => $inlineKeyboard,
                    ]);
                }

                return Request::emptyResponse();
        }

        return $this->stepBack($conversation);
    }
}

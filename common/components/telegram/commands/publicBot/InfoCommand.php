<?php

namespace Longman\TelegramBot\Commands\UserCommands;

use backend\models\TeacherSubjectLink;
use common\components\telegram\commands\ConversationTrait;
use common\components\telegram\commands\StepableTrait;
use Longman\TelegramBot\Entities\Entity;
use Longman\TelegramBot\Request;
use common\components\telegram\text\PublicMain;
use common\models\Page;
use common\models\Subject;
use common\models\SubjectCategory;
use common\models\Teacher;
use common\models\Webpage;
use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Conversation;
use Longman\TelegramBot\Entities\InlineKeyboard;
use Longman\TelegramBot\Entities\InlineKeyboardButton;
use Longman\TelegramBot\Entities\Keyboard;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Exception\TelegramException;

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
     * @return array|ServerResponse|mixed
     * @throws TelegramException
     */
    protected function process(Conversation $conversation)
    {
        $message = $this->getMessage();
        switch ($conversation->notes['step']) {
            case 1:
                $this->removeNote($conversation, 'step2');
                $buttons = [
                    PublicMain::INFO_STEP_BUTTON_TEACHERS,
                    PublicMain::INFO_STEP_BUTTON_SUBJECTS,
                    PublicMain::INFO_STEP_BUTTON_PRICES,
                    [PublicMain::TO_BACK, PublicMain::TO_MAIN],
                ];
                $keyboard = new Keyboard(...$buttons);
                $keyboard->setResizeKeyboard(true)->setSelective(false);
                return [
                    'parse_mode' => 'MarkdownV2',
                    'text' => Entity::escapeMarkdownV2(PublicMain::INFO_STEP_1_TEXT),
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
                    case PublicMain::INFO_STEP_BUTTON_PRICES:
                        return $this->processPrices($conversation);
                        break;
                    case PublicMain::INFO_STEP_BUTTON_SUBJECTS:
                        return $this->processSubjects($conversation);
                        break;
                    case PublicMain::INFO_STEP_BUTTON_TEACHERS:
                        return $this->processTeachers($conversation);
                        break;
                    default:
                        return $this->stepBack($conversation);
                        break;
                }
                break;
        }
    }
    
    private function processPrices(Conversation $conversation)
    {
        if (($conversation->notes['step'] ?? 0) > 2) {
            return $this->stepBack($conversation);
        }
        
        $priceWebpage = Webpage::findOne(['url' => Page::PRICE_PAGE_URL]);
        $link = '//5plus.uz';
        if ($priceWebpage) {
            $link .= '/' . $priceWebpage->url;
            $pricePage = Page::findOne(['webpage_id' => $priceWebpage->id]);
            if ($pricePage) {
                $content = $pricePage->content;
                if (preg_match('#<a[^>]+href=[\'"]([^\'"]+)[\'"]#', $content, $matches)) {
                    $link = $matches[1];
                }
            }
        }
        if (preg_match('#^\/\/#', $link)) {
            $link = "https:$link";
        }
        
        $conversation->notes['step']--;
        $conversation->update();
        return [
            'parse_mode' => 'MarkdownV2',
            'text' => sprintf(PublicMain::INFO_STEP_2_PRICE_TEXT, $link),
        ];
    }

    private function processSubjects(Conversation $conversation)
    {
        switch ($conversation->notes['step'] ?? 0) {
            case 2:
                $this->addNote($conversation, 'step2', PublicMain::INFO_STEP_BUTTON_SUBJECTS);
                
                /** @var SubjectCategory[] $activeCategories */
                $activeCategories = SubjectCategory::find()
                    ->joinWith('activeSubjects', true, 'INNER JOIN')
                    ->with('activeSubjects.webpage')
                    ->all();
                $buttons = [];
                foreach ($activeCategories as $category) {
                    $buttons[] = $category->name;
                }
                $buttons[] = [PublicMain::TO_BACK, PublicMain::TO_MAIN];
                $keyboard = new Keyboard(...$buttons);
                $keyboard->setResizeKeyboard(true)->setSelective(false);
                return [
                    'parse_mode' => 'MarkdownV2',
                    'text' => Entity::escapeMarkdownV2(PublicMain::INFO_STEP_2_SUBJECT_TEXT),
                    'reply_markup' => $keyboard,
                ];
            case 3:
                $this->addNote($conversation, 'step2', PublicMain::INFO_STEP_BUTTON_SUBJECTS);
                
                /** @var SubjectCategory $category */
                $category = SubjectCategory::find()
                    ->andWhere(['name' => $this->getMessage()->getText()])
                    ->with('activeSubjects.webpage')
                    ->one();
                if (!$category) {
                    return $this->stepBack($conversation);
                }

                $textLines = ['*' . Entity::escapeMarkdownV2(PublicMain::INFO_STEP_3_SUBJECT_TEXT) . '*'];
                foreach ($category->activeSubjects as $subject) {
                    $textLines[] = "[{$subject->name}](https://5plus.uz/{$subject->webpage->url})";
                }

                $buttons = [[PublicMain::TO_BACK, PublicMain::TO_MAIN]];
                $keyboard = new Keyboard(...$buttons);
                $keyboard->setResizeKeyboard(true)->setSelective(false);

                return [
                    'parse_mode' => 'MarkdownV2',
                    'disable_web_page_preview' => true,
                    'text' => implode("\n", $textLines),
                    'reply_markup' => $keyboard,
                ];
        }
        return $this->stepBack($conversation);
    }
    
    private function processTeachers(Conversation $conversation)
    {
        switch ($conversation->notes['step'] ?? 0) {
            case 2:
                $this->addNote($conversation, 'step2', PublicMain::INFO_STEP_BUTTON_TEACHERS);
                
                /** @var Subject[] $activeSubjects */
                $activeSubjects = Subject::find()
                    ->joinWith('subjectTeachers.teacher')
                    ->andWhere([
                        Teacher::tableName() . '.page_visibility' => Teacher::STATUS_ACTIVE,
                        Teacher::tableName() . '.active' => Teacher::STATUS_ACTIVE,
                        Subject::tableName() . '.active' => Subject::STATUS_ACTIVE,
                    ])
                    ->all();
                $buttons = [];
                foreach ($activeSubjects as $subject) {
                    $buttons[] = $subject->name;
                }

                $officeStaffCount = Teacher::find()
                    ->leftJoin(TeacherSubjectLink::tableName(), Teacher::tableName() . '.id = ' . TeacherSubjectLink::tableName() . '.teacher_id')
                    ->andWhere([
                        TeacherSubjectLink::tableName() . '.id' => null,
                        Teacher::tableName() . '.page_visibility' => Teacher::STATUS_ACTIVE,
                        Teacher::tableName() . '.active' => Teacher::STATUS_ACTIVE,
                    ])
                    ->count(Teacher::tableName() . '.id');
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
                break;
            case 3:
                $this->addNote($conversation, 'step2', PublicMain::INFO_STEP_BUTTON_TEACHERS);
                $subjectName = $this->getMessage()->getText();
                
                if ($subjectName === 'Администрация') {
                    $text = PublicMain::INFO_STEP_3_TEACHER_TEXT_OFFICE;

                    $teachers = Teacher::find()
                        ->leftJoin(TeacherSubjectLink::tableName(), Teacher::tableName() . '.id = ' . TeacherSubjectLink::tableName() . '.teacher_id')
                        ->andWhere([
                            TeacherSubjectLink::tableName() . '.id' => null,
                            Teacher::tableName() . '.page_visibility' => Teacher::STATUS_ACTIVE,
                            Teacher::tableName() . '.active' => Teacher::STATUS_ACTIVE,
                        ])
                        ->orWhere([Teacher::tableName() . '.id' => Teacher::CHIEF_OF_THE_BOARD_ID])
                        ->orderBy([Teacher::tableName() . '.page_order' => SORT_ASC, Teacher::tableName() . '.name' => SORT_ASC])
                        ->all();
                } else {
                    $text = PublicMain::INFO_STEP_3_TEACHER_TEXT;
                    
                    /** @var Subject $subject */
                    $subject = Subject::find()
                        ->andWhere(['name' => $subjectName])
                        ->with('visibleTeachers.webpage')
                        ->one();
                    if (!$subject) {
                        return $this->stepBack($conversation);
                    }
                    $teachers = $subject->visibleTeachers;
                }

                $chatId = $this->getMessage()->getChat()->getId();
                
                Request::sendMessage([
                    'chat_id' => $chatId,
                    'parse_mode' => 'MarkdownV2',
                    'text' => '*' . Entity::escapeMarkdownV2($text) . '*',
                    'reply_markup' => PublicMain::getBackAndMainKeyboard(),
                ]);

                foreach ($teachers as $teacher) {
                    $inlineKeyboard = new InlineKeyboard([new InlineKeyboardButton([
                        'text' => 'ПОДРОБНЕЕ',
                        'callback_data' => "teacher_info {$teacher->id}",
                    ])]);
                    Request::sendMessage([
                        'chat_id' => $chatId,
                        'parse_mode' => 'MarkdownV2',
                        'disable_web_page_preview' => true,
                        'text' => Entity::escapeMarkdownV2($teacher->title) . " [{$teacher->officialName}](https://5plus.uz/{$teacher->webpage->url})",
                        'reply_markup' => $inlineKeyboard,
                    ]);
                }

                return Request::emptyResponse();
        }
        return $this->stepBack($conversation);
    }
}

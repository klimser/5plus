<?php
/**
 * This file is part of the TelegramBot package.
 *
 * (c) Avtandil Kikabidze aka LONGMAN <akalongman@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Longman\TelegramBot\Commands\SystemCommands;

use common\components\telegram\commands\publicBot\TeacherCallbackCommand;
use Longman\TelegramBot\Request;
use Longman\TelegramBot\Commands\SystemCommand;
use Longman\TelegramBot\Entities\Update;
use Longman\TelegramBot\Telegram;

/**
 * Callback query command
 */
class CallbackqueryCommand extends SystemCommand
{
    /**
     * @var callable[]
     */
    protected static $callbacks = [];
    
    protected static $callbacksInitialized = false;

    /**
     * @var string
     */
    protected $name = 'callbackquery';

    /**
     * @var string
     */
    protected $description = 'Reply to callback query';

    /**
     * @var string
     */
    protected $version = '1.0.0';
    
    public function __construct(Telegram $telegram, Update $update = null)
    {
        parent::__construct($telegram, $update);

        if (!self::$callbacksInitialized) {
            self::addCallbackHandler([TeacherCallbackCommand::class, 'process']);
            self::$callbacksInitialized = true;
        }
    }

    /**
     * Command execute method
     *
     * @return mixed
     */
    public function execute()
    {
        //$callback_query = $this->getUpdate()->getCallbackQuery();
        //$user_id        = $callback_query->getFrom()->getId();
        //$query_id       = $callback_query->getId();
        //$query_data     = $callback_query->getData();

        // Call all registered callbacks.
        foreach (self::$callbacks as $callback) {
            $callback($this->getUpdate()->getCallbackQuery());
        }

        return Request::answerCallbackQuery(['callback_query_id' => $this->getUpdate()->getCallbackQuery()->getId()]);
    }

    /**
     * Add a new callback handler for callback queries.
     *
     * @param $callback
     */
    public static function addCallbackHandler($callback)
    {
        self::$callbacks[] = $callback;
    }
}

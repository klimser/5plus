<?php

namespace common\components;

use Longman\TelegramBot\Commands\SystemCommands\CallbackqueryCommand;
use Longman\TelegramBot\TelegramLog;
use yii\base\BaseObject;
use Longman\TelegramBot\Telegram as TelegramBot;

/**
 * @property TelegramBot $telegram
 */
class Telegram extends BaseObject
{
    protected $apiKey;
    protected $botName;
    protected $commandsPath;
    protected $tablePrefix;
    protected $webhookKey;
    protected $callbackHandlers = [];

    /**
     * @param mixed $apiKey
     */
    public function setApiKey($apiKey)
    {
        $this->apiKey = $apiKey;
    }

    /**
     * @param mixed $botName
     */
    public function setBotName($botName)
    {
        $this->botName = $botName;
    }

    /**
     * @param mixed $commandsPath
     */
    public function setCommandsPath($commandsPath)
    {
        $this->commandsPath = $commandsPath;
    }

    /**
     * @param mixed $webhookKey
     */
    public function setWebhookKey($webhookKey)
    {
        $this->webhookKey = $webhookKey;
    }

    /**
     * @param mixed $tablePrefix
     */
    public function setTablePrefix($tablePrefix)
    {
        $this->tablePrefix = $tablePrefix;
    }

    /**
     * @param array $callbackHandlers
     */
    public function setCallbackHandlers(array $callbackHandlers): void
    {
        $this->callbackHandlers = $callbackHandlers;
    }
    
    private $bot;

    public function getTelegram(): TelegramBot
    {
        if ($this->bot === null) {
            $this->bot = new TelegramBot($this->apiKey, $this->botName);
            if ($this->tablePrefix) $this->bot->enableExternalMySql(\Yii::$app->db->pdo, $this->tablePrefix);
            $this->bot->addCommandsPath(\Yii::getAlias($this->commandsPath));
            foreach ($this->callbackHandlers as $callbackHandler) {
                CallbackqueryCommand::addCallbackHandler($callbackHandler);
            }
            TelegramLog::initErrorLog(\Yii::getAlias('@runtime/telegram') . '/' . $this->botName . '_error.log');
            if (YII_ENV === 'dev') {
                TelegramLog::initUpdateLog(\Yii::getAlias('@runtime/telegram') . '/' . $this->botName . '_update.log');
                TelegramLog::initDebugLog(\Yii::getAlias('@runtime/telegram') . '/' . $this->botName . '_debug.log');
            }
        }
        return $this->bot;
    }

    public function checkAccess(\yii\web\Request $request): bool
    {
        if ($this->webhookKey) {
            return $request->getQueryParam('key') == $this->webhookKey;
        }
        return true;
    }
}

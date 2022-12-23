<?php

namespace common\components;

use Longman\TelegramBot\Commands\SystemCommands\CallbackqueryCommand;
use Longman\TelegramBot\TelegramLog;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Yii;
use yii\base\BaseObject;
use common\components\telegram\Telegram as TelegramBot;
use yii\web\Request;

/**
 * @property TelegramBot|null $telegram
 */
class Telegram extends BaseObject
{
    private ?TelegramBot $bot = null;

    protected string $apiKey;
    protected string $botName;
    protected string $commandsPath;
    protected string $tablePrefix = '';
    protected string $webhookKey;
    protected array $callbackHandlers = [];
    protected string $paymentToken;

    public function setApiKey(string $apiKey)
    {
        $this->apiKey = $apiKey;
    }

    public function setBotName(string $botName)
    {
        $this->botName = $botName;
    }

    public function setCommandsPath(string $commandsPath)
    {
        $this->commandsPath = $commandsPath;
    }

    public function setWebhookKey(string $webhookKey)
    {
        $this->webhookKey = $webhookKey;
    }

    public function setTablePrefix(string $tablePrefix)
    {
        $this->tablePrefix = $tablePrefix;
    }

    public function setCallbackHandlers(array $callbackHandlers): void
    {
        $this->callbackHandlers = $callbackHandlers;
    }
    
    public function setPaymentToken(string $token): void
    {
        $this->paymentToken = $token;
    }
    
    public function initBot(): void
    {
        if ($this->bot === null) {
            $this->bot = new TelegramBot($this->apiKey, $this->botName);
            if (isset($this->paymentToken)) {
                $this->bot->setCommandConfig('account', ['payment_provider_token' => $this->paymentToken]);
            }
            if ($this->tablePrefix) $this->bot->enableExternalMySql(Yii::$app->db->pdo, $this->tablePrefix);
            $this->bot->addCommandsPath(Yii::getAlias($this->commandsPath));
            foreach ($this->callbackHandlers as $callbackHandler) {
                CallbackqueryCommand::addCallbackHandler($callbackHandler);
            }

            $errorLogger = (new StreamHandler(Yii::getAlias('@runtime/telegram') . '/' . $this->botName . '_error.log', Logger::ERROR))
                ->setFormatter(new LineFormatter(null, null, true));

            if (YII_ENV === 'dev') {
                TelegramLog::initialize(
                    new Logger('telegram_bot_' . $this->botName, [
                        (new StreamHandler(Yii::getAlias('@runtime/telegram') . '/' . $this->botName . '_debug.log', Logger::DEBUG))
                            ->setFormatter(new LineFormatter(null, null, true)),
                        $errorLogger,
                    ]),
                    new Logger('telegram_bot_updates', [
                        (new StreamHandler(Yii::getAlias('@runtime/telegram') . '/' . $this->botName . '_update.log', Logger::INFO))
                            ->setFormatter(new LineFormatter('%message%' . PHP_EOL)),
                    ])
                );
            } else {
                TelegramLog::initialize(new Logger('telegram_bot_' . $this->botName, [$errorLogger]));
            }
        }
    }

    public function getTelegram(): TelegramBot
    {
        $this->initBot();
        return $this->bot;
    }

    public function checkAccess(Request $request): bool
    {
        if ($this->webhookKey) {
            return $request->getQueryParam('key') == $this->webhookKey;
        }
        return true;
    }
}

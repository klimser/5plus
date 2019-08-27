<?php

namespace console\controllers;

use common\components\bitrix\PaymentSyncronizer;
use common\components\bitrix\UserSyncronizer;
use common\components\bitrix\WelcomeLessonSyncronizer;
use common\components\ComponentContainer;
use yii;
use yii\console\Controller;

/**
 * BitrixSyncController is used to sync data with Bitrix.
 */
class BitrixSyncController extends Controller
{
    const TIME_LIMIT = 50;

    /** @var float */
    private $startTime;

    /**
     * @return bool
     */
    private function isTimeExceeded(): bool
    {
        return microtime(true) - $this->startTime > self::TIME_LIMIT;
    }

    /**
     * Search for a not sent notifications and sends it.
     * @return int
     */
    public function actionRun()
    {
        $this->startTime = microtime(true);

        // USERS

        $userSyncronizer = new UserSyncronizer(ComponentContainer::getBitrix());
        $userSyncronizer->trimUsers();

        while ($userSyncronizer->syncNextUser()) {
            if ($this->isTimeExceeded()) return yii\console\ExitCode::OK;
        }

        // PAYMENTS

        $paymentSyncronizer = new PaymentSyncronizer(ComponentContainer::getBitrix());
        while ($paymentSyncronizer->syncNextPayment()) {
            if ($this->isTimeExceeded()) return yii\console\ExitCode::OK;
        }

        // WELCOME LESSONS

        $welcomeLessonSyncronizer = new WelcomeLessonSyncronizer(ComponentContainer::getBitrix());
        while ($welcomeLessonSyncronizer->syncNextWelcomeLesson()) {
            if ($this->isTimeExceeded()) return yii\console\ExitCode::OK;
        }

        return yii\console\ExitCode::OK;
    }
}

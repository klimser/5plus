<?php

namespace backend\controllers;

use common\components\ComponentContainer;
use common\components\SmsServiceComponent;
use DateTimeImmutable;
use Yii;

/**
 * SmsController shows list of SMS messages sent via SMS service.
 */
class SmsController extends AdminController
{
    protected $accessRule = 'manager';

    private const PAGE_SIZE = SmsServiceComponent::MAX_PAGE_SIZE;
    private const MONTHS_IN_LIST = 24;
    public const PERIOD_CUSTOM = 'custom';

    public function actionIndex()
    {
        $request = Yii::$app->request;
        $isRoot = Yii::$app->user->can('root');

        $monthList = $this->getMonthList();
        $period = (string)$request->get('period', array_key_first($monthList));
        $dateFrom = $dateTo = null;
        if (self::PERIOD_CUSTOM === $period) {
            $dateFrom = $this->parseDate($request->get('date_from'));
            $dateTo = $this->parseDate($request->get('date_to'));
            if ($dateFrom && $dateTo && $dateFrom > $dateTo) {
                [$dateFrom, $dateTo] = [$dateTo, $dateFrom];
            }
        } else {
            if (!array_key_exists($period, $monthList)) {
                $period = array_key_first($monthList);
            }
            $dateFrom = new DateTimeImmutable($period . '-01');
            $dateTo = $dateFrom->modify('last day of this month');
        }

        $phone = preg_replace('#\D#', '', (string)$request->get('phone', ''));
        $page = max(1, (int)$request->get('page', 1));

        $messages = [];
        $error = null;
        try {
            $messages = ComponentContainer::getSmsApi()->getMessages(
                $dateFrom,
                $dateTo,
                $phone ?: null,
                $page,
                self::PAGE_SIZE,
                $isRoot ? (Yii::$app->params['sms_decode_key'] ?? null) : null,
            );
        } catch (\Throwable $e) {
            $error = $e->getMessage();
        }

        return $this->render('index', [
            'messages' => $messages,
            'error' => $error,
            'isRoot' => $isRoot,
            'monthList' => $monthList,
            'period' => $period,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'phone' => $phone,
            'page' => $page,
            'hasNextPage' => count($messages) >= self::PAGE_SIZE,
        ]);
    }

    /**
     * @return array<string, string> month ('Y-m') => label
     */
    private function getMonthList(): array
    {
        $list = [];
        $month = new DateTimeImmutable('first day of this month midnight');
        for ($i = 0; $i < self::MONTHS_IN_LIST; $i++) {
            $list[$month->format('Y-m')] = $month->format('m.Y');
            $month = $month->modify('-1 month');
        }

        return $list;
    }

    private function parseDate($value): ?DateTimeImmutable
    {
        if (!is_string($value) || '' === $value) {
            return null;
        }
        $date = DateTimeImmutable::createFromFormat('!d.m.Y', $value);

        return $date ?: null;
    }
}

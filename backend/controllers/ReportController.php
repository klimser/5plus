<?php

namespace backend\controllers;

use backend\components\report\CashReport;
use backend\components\report\DebtReport;
use backend\components\report\GroupMovementReport;
use backend\components\report\MoneyReport;
use backend\components\report\RestMoneyReport;
use common\models\Group;
use common\models\GroupPupil;
use PhpOffice\PhpSpreadsheet\IOFactory;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;

/**
 * ReportController implements vary reports.
 */
class ReportController extends AdminController
{
    public function actionGroupMovement()
    {
        if (!\Yii::$app->user->can('reportGroupMovement')) throw new ForbiddenHttpException('Access denied!');

        if (\Yii::$app->request->isPost) {
            [$month, $year] = explode('.', \Yii::$app->request->post('date', ''));
            if ($month && $year) {
                $startDate = new \DateTime("$year-$month-01");
                $endDate = clone $startDate;
                $endDate->modify('last day of this month');

                ob_start();
                $objWriter = IOFactory::createWriter(GroupMovementReport::create($startDate, $endDate), 'Xlsx');
                $objWriter->save('php://output');
                return \Yii::$app->response->sendContentAsFile(
                    ob_get_clean(),
                    "report-$year-$month.xlsx",
                    ['mimeType' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
                );
            }
        }

        return $this->render('group-movement');
    }

    /**
     * Get all money debts.
     * @return mixed
     * @throws \Exception
     */
    public function actionDebt()
    {
        if (!\Yii::$app->user->can('reportDebt')) throw new ForbiddenHttpException('Access denied!');

        ob_start();
        $objWriter = IOFactory::createWriter(DebtReport::create(), 'Xlsx');
        $objWriter->save('php://output');
        return \Yii::$app->response->sendContentAsFile(
            ob_get_clean(),
            'report-debt-' . date('Y-m-d') . '.xlsx',
            ['mimeType' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
        );
    }

    public function actionMoney()
    {
        if (!\Yii::$app->user->can('reportMoney')) throw new ForbiddenHttpException('Access denied!');

        if (\Yii::$app->request->isPost) {
            [$month, $year] = explode('.', \Yii::$app->request->post('date', ''));
            if ($month && $year) {
                $startDate = new \DateTime("$year-$month-01");
                $endDate = clone $startDate;
                $endDate->modify('last day of this month');

                $groupId = \Yii::$app->request->post('group');
                if ($groupId == 'all') {
                    if (!\Yii::$app->user->can('reportMoneyTotal')) throw new ForbiddenHttpException('Access denied!');

                    $spreadsheet = MoneyReport::createAll($startDate, $endDate);
                } else {
                    [$devNull, $groupId] = explode('_', $groupId);
                    $group = Group::findOne($groupId);
                    if (!$group) throw new NotFoundHttpException('Invalid group!');

                    $spreadsheet = MoneyReport::createGroup($groupId, $startDate, $endDate);
                }

                ob_start();
                $objWriter = IOFactory::createWriter($spreadsheet, 'Xlsx');
                $objWriter->save('php://output');
                return \Yii::$app->response->sendContentAsFile(
                    ob_get_clean(),
                    "money-$year-$month.xlsx",
                    ['mimeType' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
                );
            }
        }

        return $this->render('money', [
            'groups' => Group::find()->orderBy('name')->all(),
            'allowedTotal' => \Yii::$app->user->can('reportMoneyTotal')
        ]);
    }

    public function actionCash()
    {
        if (!\Yii::$app->user->can('reportCash')) throw new ForbiddenHttpException('Access denied!');

        if (\Yii::$app->request->isPost) {
            $date = new \DateTimeImmutable(\Yii::$app->request->post('date', 'now'));
            if (!$date) throw new NotFoundHttpException('Wrong date');

            ob_start();
            $objWriter = IOFactory::createWriter(
                CashReport::create($date, boolval(\Yii::$app->request->post('kids', 0))),
                'Xlsx'
            );
            $objWriter->save('php://output');
            return \Yii::$app->response->sendContentAsFile(
                ob_get_clean(),
                "cash-{$date->format('d.m.Y')}.xlsx",
                ['mimeType' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
            );
        }

        return $this->render('cash');
    }

    public function actionRestMoney()
    {
        if (!\Yii::$app->user->can('reportCash')) throw new ForbiddenHttpException('Access denied!');

//        ob_start();
        $objWriter = IOFactory::createWriter(
            RestMoneyReport::create(),
            'Xlsx'
        );
        return $this->render('site/index');
//        $objWriter->save('php://output');
//        return \Yii::$app->response->sendContentAsFile(
//            ob_get_clean(),
//            'rest-money-' . date('d.m.Y') . '.xlsx',
//            ['mimeType' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
//        );
    }
}

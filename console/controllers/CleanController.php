<?php

namespace console\controllers;

use common\models\QuizResult;
use yii;
use yii\console\Controller;

/**
 * CleanController is used to clean old records from DB.
 */
class CleanController extends Controller
{
    /**
     * Deletes old records from DB.
     * @return int
     */
    public function actionClean()
    {
        $cleanDate = new \DateTime('-1 month');

        /** @var QuizResult[] $results */
        $results = QuizResult::find()
            ->andWhere(['<', 'created_at', $cleanDate->format('Y-m-d H:i:S')])
            ->all();

        foreach ($results as $result) {
            $result->delete();
        }

        return yii\console\ExitCode::OK;
    }
}
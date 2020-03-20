<?php

namespace backend\controllers;

use common\components\extended\Controller;
use yii;

abstract class AdminController extends Controller
{
    /** @var string|null Если на весь контроллер применяется одно правило доступа */
    protected $accessRule = null;
    
    public function beforeAction($action)
    {
        if (!parent::beforeAction($action)) {
            return false;
        }

        if (Yii::$app->user->isGuest) {
            $this->redirect(['site/login']);
            return false;
        }

        if ($this->accessRule && !Yii::$app->user->can($this->accessRule)) {
            throw new yii\web\ForbiddenHttpException('Access denied!');
        }

        return true;
    }
    
    protected function checkRequestIsAjax()
    {
        if (!Yii::$app->request->isAjax) throw new yii\web\BadRequestHttpException('Request is not AJAX');
    }
    
    protected static function remapRequestData(array $data): array
    {
        $result = [];
        foreach ($data as $field => $records) {
            foreach ($records as $id => $value) {
                if (!array_key_exists($id, $result)) {
                    $result[$id] = [];
                }
                $result[$id][$field] = $value;
            }
        }
        
        return $result;
    }
}

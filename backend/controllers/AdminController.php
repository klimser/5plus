<?php

namespace backend\controllers;

use common\components\extended\Controller;
use yii;
use yii\web\ForbiddenHttpException;
use yii\web\BadRequestHttpException;

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

        if ($this->accessRule) {
            $this->checkAccess($this->accessRule);
        }

        return true;
    }
    
    protected function checkRequestIsAjax(): void
    {
        if (!Yii::$app->request->isAjax) throw new BadRequestHttpException('Request is not AJAX');
    }
    
    protected function checkAccess(string $permissionName, array $params = []): void
    {
        if (!Yii::$app->user->can($permissionName, $params)) throw new ForbiddenHttpException('Access denied!');
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

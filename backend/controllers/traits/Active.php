<?php

namespace backend\controllers\traits;
use common\components\extended\ActiveRecord;

/**
 * Trait Active
 * @package backend\controllers\traits
 */
trait Active
{
    protected abstract function findModel($id);
    protected abstract static function getJsonOkResult(array $resultDataArray = []): array;
    protected abstract static function getJsonErrorResult(string $message = ''): array;
    public abstract function asJson($data);

    /**
     * @param $id
     * @return \yii\web\Response
     */
    public function actionChangeActive($id)
    {
        $jsonData = [];
        if (\Yii::$app->request->isAjax) {
            $model = $this->findModel($id);

            $newActive = \Yii::$app->request->post('active');
            $jsonData = self::getJsonOkResult(['id' => $model->id]);
            if ($model->active != $newActive) {
                $model->active = $newActive;
                if (!$model->save()) $jsonData = self::getJsonErrorResult($model->getErrorsAsString('active'));
            }
        }
        return $this->asJson($jsonData);
    }
}

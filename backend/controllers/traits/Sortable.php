<?php

namespace backend\controllers\traits;
use common\components\extended\ActiveRecord;

/**
 * Trait Sortable
 * @package backend\controllers\traits
 */
trait Sortable
{
    protected abstract function findModel($id): ActiveRecord;
    protected abstract static function getJsonOkResult(array $resultDataArray = []): array;
    protected abstract static function getJsonErrorResult(string $message = ''): array;
    public abstract function asJson($data);

    /**
     * @param string $prefix
     */
    public function saveSortedData(string $prefix)
    {
        $sortOrder = \Yii::$app->request->post('sorted-list');
        if ($sortOrder) {
            $data = explode(',', $sortOrder);
            for ($i = 1; $i <= count($data); $i++) {
                $entityId = str_replace($prefix, '', $data[$i - 1]);
                $entity = $this->findModel($entityId);
                $entity->page_order = $i;
                $entity->save(true, ['page_order']);
            }
        }
    }
}

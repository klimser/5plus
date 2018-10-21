<?php

namespace backend\controllers;

use common\models\Error;
use common\models\MenuItem;
use yii;
use common\models\Menu;
use yii\data\ActiveDataProvider;
use yii\web\NotFoundHttpException;
use yii\web\Response;

/**
 * MenuController implements the CRUD actions for Menu model.
 */
class MenuController extends AdminController
{
    protected $accessRule = 'manageMenu';

    /**
     * Lists all Menu models.
     * @return mixed
     */
    public function actionIndex()
    {
        $dataProvider = new ActiveDataProvider([
            'query' => Menu::find(),
        ]);

        return $this->render('index', [
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Creates a new Menu model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $menu = new Menu();

        if ($menu->load(Yii::$app->request->post())) {
            if ($menu->save()) {
                return $this->redirect(['update', 'id' => $menu->id]);
            } else $menu->moveErrorsToFlash();
        }
        return $this->render('create', [
            'menu' => $menu,
        ]);
    }

    /**
     * Updates an existing Menu model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     */
    public function actionUpdate($id)
    {
        $menu = $this->findModel($id);

        if ($menu->load(Yii::$app->request->post())) {
            if ($menu->save()) {
                Menu::clearMenuCache($menu->id);
                return $this->redirect(['update', 'id' => $menu->id]);
            } else $menu->moveErrorsToFlash();
        }
        $newItem = new MenuItem();
        $newItem->menu_id = $menu->id;
        return $this->render('update', [
            'newItem' => $newItem,
            'menu' => $menu,
        ]);
    }

    /**
     * Deletes an existing Menu model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     */
    public function actionDelete($id)
    {
        $this->findModel($id)->delete();

        return $this->redirect(['index']);
    }

    /**
     * @param int $menuId
     * @return Response
     */
    public function actionReorder($menuId) {
        $jsonData = [];
        if (Yii::$app->request->isAjax) {
            $menuItemsMap = MenuItem::getListAsMap(MenuItem::find()->where(['menu_id' => $menuId])->all());
            $orderData = Yii::$app->request->post('ordered_data');
            $errors = '';
            foreach ($orderData as $order => $element) {
                if (isset($element['id']) && $element['id'] && isset($menuItemsMap[$element['id']])) {
                    /** @var MenuItem $menuItem */
                    $menuItem = $menuItemsMap[$element['id']];
                    $menuItem->parent_id = $element['parent_id'];
                    $menuItem->orderby = $order;
                    if (!$menuItem->save(true, ['parent_id', 'orderby'])) $errors .= $menuItem->getErrorsAsString() . ' ';
                }
            }
            $jsonData = $errors ? self::getJsonErrorResult(trim($errors)) : self::getJsonOkResult();
            Menu::clearMenuCache($menuId);
        }
        return $this->asJson($jsonData);
    }

    public function actionAddItem() {
        $menuItem = new MenuItem();

        if ($menuItem->load(Yii::$app->request->post())) {
            $maxOrder = MenuItem::find()->where(['menu_id' => $menuItem->menu_id, 'parent_id' => null])->max('orderby');
            $menuItem->orderby = $maxOrder + 1;
            if ($menuItem->save()) {
                Menu::clearMenuCache($menuItem->menu_id);
                return $this->redirect(['update', 'id' => $menuItem->menu_id]);
            }
        }
        $menuItem->moveErrorsToFlash();
        return $this->render('update', [
            'newItem' => $menuItem,
            'menu' => $menuItem->menu,
        ]);
    }
    public function actionUpdateItem() {
        $menuItemData = Yii::$app->request->post('MenuItem');
        $menuItemId = $menuItemData['id'];
        /** @var MenuItem $menuItem */
        $menuItem = MenuItem::findOne($menuItemId);

        if ($menuItem && $menuItem->load(Yii::$app->request->post()) && $menuItem->save()) {
            Menu::clearMenuCache($menuItem->menu_id);
            return $this->redirect(['update', 'id' => $menuItem->menu_id]);
        } else {
            $menuItem->moveErrorsToFlash();
            return $this->render('update', [
                'editItem' => $menuItem,
                'menu' => $menuItem->menu,
            ]);
        }
    }
    public function actionDeleteItem() {
        $jsonData = [];
        if (Yii::$app->request->isAjax) {
            $itemId = Yii::$app->request->post('item_id');
            if ($itemId) {
                /** @var MenuItem $menuItem */
                $menuItem = MenuItem::findOne($itemId);
                if ($menuItem) {
                    if ($menuItem->delete() === false) {
                        Error::logError('MenuItem.delete', $menuItem->id);
                        $jsonData = self::getJsonErrorResult();
                    } else {
                        $jsonData = self::getJsonOkResult();
                        Menu::clearMenuCache($menuItem->menu_id);
                    }
                } else $jsonData = self::getJsonErrorResult('Menu Item not found');
            } else $jsonData = self::getJsonErrorResult('Wrong request');
        }
        return $this->asJson($jsonData);
    }

    /**
     * Finds the Menu model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Menu the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Menu::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }
}

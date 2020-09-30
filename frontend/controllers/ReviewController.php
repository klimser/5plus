<?php

namespace frontend\controllers;

use common\components\ComponentContainer;
use common\models\Review;
use common\models\Webpage;
use yii;
use yii\data\Pagination;
use common\components\extended\Controller;
use yii\web\Response;

/**
 * ReviewController implements the CRUD actions for Page model.
 */
class ReviewController extends Controller
{
    /**
     * Displays Reviews page.
     * @param Webpage $webpage
     * @return mixed
     */
    public function actionIndex($webpage)
    {
        $itemsPerPage = 5;

        $qB = Review::getActiveListQuery();
        $pager = new Pagination([
            'totalCount' => $qB->count(),
            'defaultPageSize' => $itemsPerPage,
            'route' => 'reviews/webpage',
            'params' => array_merge($_GET, ['id' => $webpage->id])
        ]);
        return $this->render('index', [
            'reviews' => $qB->limit($pager->limit)->offset($pager->offset)->all(),
            'pager' => $pager,
            'webpage' => $webpage,
            'h1' => $webpage->title,
        ]);
    }

    /**
     * Creates a new Review model.
     * @return mixed
     * @throws yii\web\BadRequestHttpException
     */
    public function actionCreate()
    {
        $this->checkRequestIsAjax();
        Yii::$app->response->format = Response::FORMAT_JSON;

        $review = new Review(['scenario' => Review::SCENARIO_USER]);
        $review->setAttributes(Yii::$app->request->post('review'));
        if (Yii::$app->request->post('g-recaptcha-response')) $review->reCaptcha = Yii::$app->request->post('g-recaptcha-response');

        $review->status = Review::STATUS_NEW;
        $review->ip = Yii::$app->request->userIP;

        if (!$review->save(true)) {
            ComponentContainer::getErrorLogger()
                ->logError('Order.create', $review->getErrorsAsString(), true);
            $jsonData = self::getJsonErrorResult('Server error');
            $jsonData['errors'] = $review->getErrorsAsString();
            return $jsonData;
        }

        $review->notifyAdmin();
        return self::getJsonOkResult();
    }
}

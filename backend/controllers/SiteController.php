<?php
namespace backend\controllers;

use common\models\User;
use common\models\Feedback;
use common\models\Order;
use common\models\Review;
use yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use backend\models\LoginForm;
use yii\web\ForbiddenHttpException;

/**
 * Site controller
 */
class SiteController extends Controller
{
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'actions' => ['login', 'error'],
                        'allow' => true,
                    ],
                    [
                        'actions' => ['logout', 'index', 'clear-cache'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
        ];
    }

    public function actions()
    {
        return [
            'error' => [
                'class' => 'yii\web\ErrorAction',
            ],
        ];
    }

    public function actionIndex()
    {
        if (!Yii::$app->user->can('viewIndex')) throw new ForbiddenHttpException('Access denied!');
        /** @var User $currentUser */
        $currentUser = Yii::$app->user->identity;
        $viewFile = 'index_pupil';
        switch ($currentUser->role) {
            case User::ROLE_ROOT:
            case User::ROLE_MANAGER:
            case User::ROLE_TEACHER:
                $viewFile = 'index';
                break;
            case User::ROLE_PARENTS: if (count($currentUser->children) > 1) $viewFile = 'index_parent'; break;
        }
        $params = ['admin' => Yii::$app->user];
        if (Yii::$app->user->can('support')) {
            $params['orderCount'] = Order::find()->andWhere(['status' => [Order::STATUS_UNREAD, Order::STATUS_READ]])->count('id');
            $params['feedbackCount'] = Feedback::find()->andWhere(['status' => [Feedback::STATUS_NEW, Feedback::STATUS_READ]])->count('id');
            $params['reviewCount'] = Review::find()->andWhere(['status' => Review::STATUS_NEW])->count('id');
        }
        return $this->render($viewFile, $params);
    }

    public function actionLogin()
    {
        if (!\Yii::$app->user->isGuest) {
            return $this->goHome();
        }

        $model = new LoginForm();
        if ($model->load(Yii::$app->request->post()) && $model->login()) {
            return $this->goBack();
        } else {
            return $this->render('login', [
                'model' => $model,
            ]);
        }
    }

    public function actionLogout()
    {
        Yii::$app->user->logout();

        return $this->goHome();
    }

    public function actionClearCache()
    {
        if (\Yii::$app->user->identity->role == User::ROLE_ROOT) {
            \Yii::$app->cache->flush();
        }

        return $this->goHome();
    }
}

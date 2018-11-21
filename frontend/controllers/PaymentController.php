<?php

namespace frontend\controllers;

use common\components\extended\Controller;
use common\models\User;
use himiklab\yii2\recaptcha\ReCaptchaValidator;

class PaymentController extends Controller
{
    public function actionIndex()
    {
        return $this->render('index');
    }

    public function actionFind()
    {
        $validator = new ReCaptchaValidator();

        if (!$validator->validate(\Yii::$app->request->post('reCaptcha'), $error)) {
            \Yii::$app->session->addFlash('error', 'Проверка на робота не пройдена');
            return $this->render('index');
        } else {
            $phoneFull = '+998' . substr(preg_replace('#\D#', '', \Yii::$app->request->post('pupil-phone')), -9);
            $users = User::find()
                ->andWhere(['or', ['phone' => $phoneFull], ['phone2' => $phoneFull]])
                ->andWhere(['role' => [User::ROLE_PARENTS, User::ROLE_COMPANY, User::ROLE_PUPIL]])
                ->all();
            if (count($users) == 0) {
                \Yii::$app->session->addFlash('error', 'По данному номеру студенты не найдены');
                return $this->render('index');
            } elseif (count($users) == 1) {
                return $this->render('find', ['user' => reset($users)]);
            } else {
                return $this->render('find', ['users' => $users]);
            }
        }
    }
}

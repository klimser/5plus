<?php

namespace frontend\controllers;

use common\components\extended\Controller;
use common\models\Group;
use common\models\User;
use himiklab\yii2\recaptcha\ReCaptchaValidator;
use yii\web\BadRequestHttpException;

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
            $phoneFull = '+998' . substr(preg_replace('#\D#', '', \Yii::$app->request->post('phoneFormatted')), -9);
            /** @var User[] $users */
            $users = User::find()
                ->andWhere(['or', ['phone' => $phoneFull], ['phone2' => $phoneFull]])
                ->andWhere(['role' => [User::ROLE_PARENTS, User::ROLE_COMPANY, User::ROLE_PUPIL]])
                ->all();
            if (count($users) == 0) {
                \Yii::$app->session->addFlash('error', 'По данному номеру студенты не найдены');
                return $this->render('index');
            } else {
                $params = ['user' => null, 'users' => []];
                if (count($users) == 1) {
                    $user = reset($users);
                    if ($user->role == User::ROLE_PUPIL) $params['user'] = $user;
                    else {
                        if (count($user->children) == 1) $params['user'] = reset($user->children);
                        else $params['users'] = $user->children;
                    }
                } else $params['users'] = $users;

                return $this->render('find', $params);
            }
        }
    }

    public function actionCreate()
    {
        if (!\Yii::$app->request->isAjax) throw new BadRequestHttpException('Wrong request');

        $pupilId = \Yii::$app->request->post('pupil');
        $groupId = \Yii::$app->request->post('group');
        $amount = intval(\Yii::$app->request->post('amount'));

        if (!$pupilId) return self::getJsonErrorResult('No pupil ID');
        if (!$groupId) return self::getJsonErrorResult('No pupil ID');
        if ($amount < 1000) return self::getJsonErrorResult('Wrong payment amount');

        $pupil = User::find()->andWhere(['id' => $pupilId, 'role' => User::ROLE_PUPIL, 'status' => User::STATUS_ACTIVE])->one();
        $group = Group::find()->andWhere(['id' => $groupId, 'active' => Group::STATUS_ACTIVE])->one();

        if (!$pupil) return self::getJsonErrorResult('Pupil not found');
        if (!$group) return self::getJsonErrorResult('Group not found');


    }
}

<?php

namespace frontend\controllers;

use common\components\extended\Controller;

/**
 * LandingController implements landings.
 */
class LandingController extends Controller
{
    public function actionOnline()
    {
        $this->layout = 'online';
        return $this->render('online');
    }
}

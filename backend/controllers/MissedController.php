<?php

namespace backend\controllers;
use backend\models\Event;
use backend\models\EventMember;
use common\models\GroupPupil;

/**
 * MissedController implements list of pupils that missing lessons.
 */
class MissedController extends AdminController
{
    public function actionList()
    {
        $data = EventMember::find()
            ->joinWith('event')
            ->andWhere([Event::tableName() . '.status' => Event::STATUS_PASSED])
            ->select([Event::tableName() . '.group_id', EventMember::tableName() . '.group_pupil_id'])
            ->distinct(true)
            ->orderBy([Event::tableName() . '.event_date' => SORT_DESC])
            ->asArray(true)->all();

    }
}
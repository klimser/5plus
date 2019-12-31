<?php

namespace backend\controllers;

use backend\controllers\traits\Active;
use common\components\ComponentContainer;
use common\components\extended\ActiveRecord;
use common\components\telegram\Request;
use common\models\User;
use Longman\TelegramBot\Entities\Message;
use Yii;
use yii\web\UploadedFile;

/**
 * PushController allows to send custom push-messages.
 */
class PushController extends AdminController
{
    use Active;

    protected $accessRule = 'sendPush';

    public function actionIndex()
    {
        if (Yii::$app->request->isPost) {
            \Yii::$app->db->open();
            ComponentContainer::getTelegramPublic()->telegram;
            
            $messageText = str_replace('<br>', "\n", Yii::$app->request->post('text'));
            $newPath = null;
            $photoId = null;
            $imageFile = UploadedFile::getInstanceByName('image');
            if ($imageFile) {
                $newPath = Yii::getAlias('@uploads') . '/telegram/' . $imageFile->name;
                $imageFile->saveAs($newPath);
            }
            /** @var User[] $users */
            $users = User::find()->andWhere(['not', ['tg_chat_id' => null]])->all();
            $users = array_filter($users, function($value) { return $value->telegramSettings['subscribe']; });
            foreach ($users as $user) {

                $data = [
                    'chat_id' => $user->tg_chat_id,
                    'parse_mode' => 'HTML',
                ];
                if ($newPath) {
                    $data['caption'] = $messageText;
                    if (!$photoId) {
                        $data['photo'] = preg_replace('#^\/\/#', 'https://', Yii::getAlias('@uploadsUrl') . '/telegram/' . $imageFile->name);
                    } else {
                        $data['photo'] = $photoId;
                    }
                    $response = Request::sendPhoto($data);
                    if ($response->isOk() && !$photoId) {
                        /** @var Message $result */
                        $result = $response->getResult();
                        $photoId = $result->getPhoto()[0]->getFileId();
                    }
                } else {
                    Request::sendMessage([
                        'chat_id' => $user->tg_chat_id,
                        'parse_mode' => 'HTML',
                        'text' => $messageText,
                    ]);
                }
            }
        }
        
        return $this->render('index');
    }

    protected function findModel(int $id): ActiveRecord
    {
        throw new \Exception('No model here!');
    }
}

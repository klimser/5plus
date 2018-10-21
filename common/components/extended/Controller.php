<?php

namespace common\components\extended;


use common\models\Webpage;
use yii\base\InvalidArgumentException;

class Controller extends \yii\web\Controller
{
    /**
     * @param string $message
     * @return array
     */
    protected static function getJsonErrorResult(string $message = ''): array
    {
        return ['status' => 'error', 'message' => $message ?: 'Server error'];
    }

    /**
     * @param array $resultDataArray
     * @return array
     */
    protected static function getJsonOkResult(array $resultDataArray = []): array
    {
        $resultDataArray['status'] = 'ok';
        return $resultDataArray;
    }

    /**
     * @return int
     */
    public static function getCurrentAdminId()
    {
        return \Yii::$app->user->identity->id;
    }

    /**
     * @param string $view the view name.
     * @param array $params the parameters (name-value pairs) that should be made available in the view.
     * @return string the rendering result.
     * @throws InvalidArgumentException if the view file or the layout file does not exist.
     */
    public function render($view, $params = [])
    {
        if (array_key_exists('webpage', $params) && $params['webpage'] instanceof Webpage) {
            $this->view->params['webpage'] = $params['webpage'];
            $this->view->title = $params['webpage']->title;
            if ($params['webpage']->description) {
                $this->view->registerMetaTag([
                    'name' => 'description',
                    'content' => $params['webpage']->description,
                ]);
            }
            if ($params['webpage']->keywords) {
                $this->view->registerMetaTag([
                    'name' => 'keywords',
                    'content' => $params['webpage']->keywords,
                ]);
            }
        }

        if (array_key_exists('h1', $params)) $this->view->params['h1'] = $params['h1'];

        return parent::render($view, $params);
    }
}
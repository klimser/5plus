<?php
namespace frontend\components;

use common\models\Module;
use common\models\Webpage;
use yii\web\Request;
use yii\web\UrlRule;

class WebpageUrlRule extends UrlRule
{
    public $pattern = 'all';
    public $route = 'all';

    public function createUrl($manager, $route, $params)
    {
        if (preg_match('#^[a-z_-]+/webpage$#i', $route) && isset($params['id'])) {
            $webpage = Webpage::findOne($params['id']);
            if ($webpage) {
                unset($params['id']);
                $url = \Yii::$app->homeUrl . $webpage->url;
                if (!empty($params)) $url .= '?' . http_build_query($params);
                return $url;
            }
        }
        return false;  // this rule does not apply
    }

    /**
     * @param \yii\web\UrlManager $manager
     * @param Request $request
     * @return array|bool
     * @throws \yii\base\InvalidConfigException
     */
    public function parseRequest($manager, $request)
    {
        $pathInfo = $request->getPathInfo();

        if ($pathInfo) {
            if ($pathInfo == 'sitemap.xml') return ['site/sitemap', []];

            $webpage = \common\components\Webpage::getByUrl($pathInfo);
            if (!empty($webpage) && $webpage->main) $webpage = null;
        } else {
            $webpage = \common\components\Webpage::getMain();
        }
        if (!empty($webpage) && $webpage->active == Webpage::STATUS_ACTIVE
            && $webpage->module_id
            && $webpage->module->active == Module::STATUS_ACTIVE) {
            $params = [
                'id' => $webpage->record_id,
                'webpage' => $webpage,
            ];
            return [$webpage->module->controller . '/' . $webpage->module->action, $params];
        }

        return false;  // this rule does not apply
    }
}

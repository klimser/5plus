<?php

namespace common\components;

use yii\base\BaseObject;

class Webpage extends BaseObject
{
    /**
     * @param \common\models\Webpage|int|string $webpage
     * @return string
     */
    private static function getCacheKey($webpage)
    {
        if (is_int($webpage)) $webpage = \common\models\Webpage::findOne($webpage);
        $webpageUrl = '';
        if (is_string($webpage)) $webpageUrl = $webpage;
        elseif ($webpage instanceof \common\models\Webpage) $webpageUrl = $webpage->url;
        return 'webpage|' . $webpageUrl;
    }

    /**
     * @param string $url
     * @return \common\models\Webpage|null
     */
    public static function getByUrl(string $url): ?\common\models\Webpage
    {
        if (empty($url)) return null;
        $webpageData = \Yii::$app->cache->getOrSet(self::getCacheKey($url), function() use ($url) {
            return \common\models\Webpage::find()->andWhere(['url' => $url])->with('module')->one();
        });
        return $webpageData;
    }

    /**
     * @return \common\models\Webpage|null
     */
    public static function getMain(): ?\common\models\Webpage
    {
        $webpageData = \Yii::$app->cache->getOrSet('webpage-main', function() {
            return \common\models\Webpage::find()->andWhere(['main' => 1])->with('module')->one();
        });
        return $webpageData;
    }

    /**
     * @param \common\models\Webpage $webpage
     */
    public static function clearCache(\common\models\Webpage $webpage)
    {
        if (array_key_exists('url', $webpage->oldAttributes) && $webpage->oldAttributes['url'] != $webpage->url) {
            \Yii::$app->cache->delete(self::getCacheKey($webpage->oldAttributes['url']));
        }
        \Yii::$app->cache->delete(self::getCacheKey($webpage));
        \Yii::$app->cache->delete('webpage-main');
    }
}
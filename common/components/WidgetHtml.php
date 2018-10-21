<?php

namespace common\components;

use yii\base\BaseObject;

class WidgetHtml extends BaseObject
{
    /**
     * @param \common\models\WidgetHtml|int|string $widget
     * @return string
     */
    private static function getCacheKey($widget)
    {
        if (is_int($widget)) $widget = \common\models\WidgetHtml::findOne($widget);
        $widgetName = '';
        if (is_string($widget)) $widgetName = $widget;
        elseif ($widget instanceof \common\models\WidgetHtml) $widgetName = $widget->name;
        return 'widget.html.' . $widgetName;
    }

    /**
     * @param string $name
     * @return mixed|string
     */
    public static function getByName(string $name)
    {
        if (empty($name)) return '';
        $widget = \Yii::$app->cache->getOrSet(self::getCacheKey($name), function() use ($name) {
            $widgetHtml = \common\models\WidgetHtml::findOne(['name' => $name]);
            return $widgetHtml ? $widgetHtml->content : '';
        });
        return $widget;
    }

    /**
     * @return string
     */
    public static function getBanner() {
        $banner = Yii::$app->cache->get('banner');
        if (!$banner) {
            $bannerEntity = self::findOne(self::BANNER_ID);
            $banner = $bannerEntity->content;
            Yii::$app->cache->set('banner', $banner);
        }
        return $banner;
    }

    /**
     * @return bool
     */
    public static function clearBannerCache()
    {
        return Yii::$app->cache->delete('banner');
    }

    /**
     * @return string
     */
    public static function getMetrika() {
        $metrika = Yii::$app->cache->get('metrika');
        if (!$metrika) {
            $metrikaEntity = self::findOne(self::METRIKA_ID);
            $metrika = $metrikaEntity->content;
            Yii::$app->cache->set('metrika', $metrika);
        }
        return $metrika;
    }

    /**
     * @return bool
     */
    public static function clearMetrikaCache()
    {
        return Yii::$app->cache->delete('metrika');
    }

    /**
     * @return string
     */
    public static function getAnalytics() {
        $analytics = Yii::$app->cache->get('analytics');
        if (!$analytics) {
            $analyticsEntity = self::findOne(self::ANALYTICS_ID);
            $analytics = $analyticsEntity->content;
            Yii::$app->cache->set('analytics', $analytics);
        }
        return $analytics;
    }

    /**
     * @return bool
     */
    public static function clearAnalyticsCache()
    {
        return Yii::$app->cache->delete('analytics');
    }

    /**
     * @param \common\models\WidgetHtml $widget
     * @return bool
     */
    public static function clearCache(\common\models\WidgetHtml $widget)
    {
        return \Yii::$app->cache->delete(self::getCacheKey($widget));
    }
}
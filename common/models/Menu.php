<?php

namespace common\models;

use common\components\extended\ActiveRecord;
use Yii;

/**
 * This is the model class for table "{{%widget_menu}}".
 *
 * @property int $id
 * @property string $name
 * @property string $title
 *
 * @property MenuItem[] $menuItems
 */
class Menu extends ActiveRecord
{
    const MAIN_MENU_ID = 1;
    const BOTTOM_MENU_ID = 3;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%widget_menu}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['name'], 'required'],
            [['name'], 'string', 'max' => 63],
            [['title'], 'string', 'max' => 255],
            [['name', 'title'], 'safe'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => 'Название меню',
            'title' => 'Заголовок меню',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMenuItems()
    {
        return $this->hasMany(MenuItem::class, ['menu_id' => 'id'])->orderBy('orderby')->inverseOf('menu');
    }

    /**
     * @param MenuItem[] $menuItems
     * @return array
     */
    private static function getItemsArray($menuItems) {
        $itemsArray = [];
        foreach ($menuItems as $menuItem) {
            if ($menuItem->active) {
                $elem = ['label' => $menuItem->title];
                if ($menuItem->menuItems) $elem['items'] = self::getItemsArray($menuItem->menuItems);
                else $elem['url'] = $menuItem->webpage_id ? Yii::$app->homeUrl . ($menuItem->webpage->main ? '' : $menuItem->webpage->url) : $menuItem->url;
                $itemsArray[] = $elem;
            }
        }
        return $itemsArray;
    }
    public static function getMenuItemsCached($menuId, ?Webpage $webpage = null)
    {
        $itemsArray = Yii::$app->cache->get('menu.' . $menuId);
        if (!$itemsArray) {
            $menuItems = MenuItem::find()->with('webpage')->where(['menu_id' => $menuId, 'parent_id' => null])->orderBy('orderby')->all();
            $itemsArray = self::getItemsArray($menuItems);
            Yii::$app->cache->set('menu.' . $menuId, $itemsArray);
        }
        if ($webpage) {
            foreach ($itemsArray as &$menuItem) {
                if (array_key_exists('url', $menuItem) && $menuItem['url'] == Yii::$app->homeUrl . ($webpage->main ? '' : $webpage->url)) $menuItem['active'] = true;
            }
        }
        return $itemsArray;
    }

    public static function clearMenuCache($menuId)
    {
        Yii::$app->cache->delete('menu.' . $menuId);
    }
}

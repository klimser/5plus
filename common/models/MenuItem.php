<?php

namespace common\models;

use common\components\extended\ActiveRecord;

/**
 * This is the model class for table "{{%widget_menu_item}}".
 *
 * @property string $id
 * @property int $menu_id
 * @property int $parent_id
 * @property int $webpage_id
 * @property string $url
 * @property string $title
 * @property int $active
 * @property string $attr
 * @property int $orderby
 *
 * @property Webpage $webpage
 * @property MenuItem $parent
 * @property MenuItem[] $menuItems
 * @property Menu $menu
 */
class MenuItem extends ActiveRecord
{

    public static function tableName()
    {
        return '{{%widget_menu_item}}';
    }


    public function rules()
    {
        return [
            [['menu_id'], 'required'],
            [['menu_id', 'parent_id', 'webpage_id', 'active', 'orderby'], 'integer'],
            [['attr'], 'string'],
            [['url'], 'string', 'max' => 255],
            [['title'], 'string', 'max' => 127],
            [['url', 'title', 'attr'], 'safe'],

            ['active', 'in', 'range' => [0, 1]],
        ];
    }


    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'menu_id' => 'Меню',
            'parent_id' => 'Родительский пункт меню',
            'webpage_id' => 'Ссылка на страницу сайта',
            'url' => 'URL',
            'title' => 'Текст пункта меню',
            'active' => 'Показывать или нет',
            'attr' => 'Доп параметры',
            'orderby' => 'Порядок',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getWebpage()
    {
        return $this->hasOne(Webpage::class, ['id' => 'webpage_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getParent()
    {
        return $this->hasOne(MenuItem::class, ['id' => 'parent_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMenuItems()
    {
        return $this->hasMany(MenuItem::class, ['parent_id' => 'id'])->orderBy('orderby');
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMenu()
    {
        return $this->hasOne(Menu::class, ['id' => 'menu_id']);
    }
}

<?php

use yii\bootstrap4\Html;
use \common\models\MenuItem;

/* @var $this yii\web\View */
/* @var $menu common\models\Menu */
/* @var $newItem MenuItem */
/* @var $editItem MenuItem */

$this->registerJs('Menu.id = ' . $menu->id . '; NestedSortable.init("ol.nested-sortable-container");');

$this->title = 'Изменить меню: ' . ' ' . $menu->name;
$this->params['breadcrumbs'][] = ['label' => 'Меню', 'url' => ['index']];
$this->params['breadcrumbs'][] = $menu->name;

/**
 * @param MenuItem $menuItem
 */
$renderMenuItem = function (MenuItem $menuItem) use (&$renderMenuItem) { ?>
    <li id="item-<?= $menuItem->id; ?>">
        <div <?php if (!$menuItem->active): ?> class="inactive-item"<?php endif; ?>>
            <span class="fas fa-chevron-right mr-1"></span>
            <?= $menuItem->title; ?>
            <button class="float-right btn btn-outline-dark btn-sm" onclick="Menu.deleteItem(<?= $menuItem->id; ?>);">
                <span class="fas fa-times"></span>
            </button>
            <button class="float-right btn btn-outline-dark btn-sm" data-id="<?= $menuItem->id; ?>" data-title="<?= $menuItem->title; ?>" data-webpage="<?= $menuItem->webpage_id; ?>" data-url="<?= $menuItem->url; ?>" data-active="<?= $menuItem->active; ?>" data-attr="<?= $menuItem->attr; ?>" onclick="Menu.editItem(this);">
                <span class="fas fa-pencil-alt"></span>
            </button>
            <span class="clearfix"></span>
        </div>
        <?php if ($menuItem->menuItems): ?>
            <ol>
                <?php foreach ($menuItem->menuItems as $subItem) echo $renderMenuItem($subItem); ?>
            </ol>
        <?php endif; ?>
    </li>
<?php } ?>
<div class="menu-update">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'menu' => $menu,
    ]) ?>

    <div id="message_board"></div>

    <div class="row">
        <div class="col-12 col-md-6">
            <ol class="nested-sortable-container list-unstyled mt-3" data-callback-url="<?= \yii\helpers\Url::to(['reorder', 'menuId' => $menu->id]); ?>">
                <?php foreach ($menu->menuItems as $menuItem) if (!$menuItem->parent_id) $renderMenuItem($menuItem); ?>
            </ol>
            <hr>
            <button class="btn btn-info mb-3" onclick="$('#new_element_form').collapse('toggle'); $(this).hide();">Добавить новый элемент</button>
            <fieldset class="collapse <?php if (!$newItem->isNewRecord): ?> show <?php endif; ?>" id="new_element_form">
                <legend>Добавить новый элемент</legend>
                <?= $this->render('_item_form', [
                    'model' => $newItem,
                    'config' => ['action' => '/menu/add-item'],
                ]); ?>
            </fieldset>
        </div>
        <div id="edit_element_form" class="col-12 col-md-6 collapse <?php if (isset($editItem)): ?> show <?php endif; ?>">
            <?php if (!isset($editItem)) {$editItem = new MenuItem(); $editItem->menu_id = $menu->id;} ?>
            <?= $this->render('_item_form', [
                'model' => $editItem,
                'config' => ['action' => '/menu/update-item'],
            ]); ?>
        </div>
    </div>
</div>

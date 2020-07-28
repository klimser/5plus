<?php
/* @var $this \yii\web\View */
/* @var $items \common\models\MenuItem[] */

$renderList = function(array $item) {
    if (isset($item['items']) && $item['items']) {
        ?>
        <dl class="col-12 col-md-6 col-lg-3">

            <?php if ($item['label']): ?>
                <dt class="bottom_menu_header"><?= $item['label']; ?></dt>
            <?php endif;
            foreach ($item['items'] as $menuItem) {
                if (isset($menuItem['url'])) echo \yii\bootstrap4\Html::tag('dd', \yii\bootstrap4\Html::a($menuItem['label'], $menuItem['url']));
            }
            ?>
        </dl>
        <?php
    }
};

if (!empty($items)): ?>
    <h4 class="upper_blue">Карта сайта</h4>
    <div class="row">
        <?php foreach ($items as $item) $renderList($item); ?>
    </div>
<?php endif; ?>

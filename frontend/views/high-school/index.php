<?php

/* @var $this \frontend\components\extended\View */
/* @var $webpage common\models\Webpage */
/* @var $highSchools \common\models\HighSchool[] */

$this->registerJs('HighSchool.init();');

$tocContents = [];
?>

<div class="two-col-box container">
    <aside class="left">
        <nav class="universities-menu">
            <h6 class="title">ВУЗЫ <div class="btn-catalog-open"><span class="l1"></span><span class="l2"></span><span class="l3"></span></div></h6>
            <div class="box">
                <ul class="universities-menu-list">
                    <?php foreach ($highSchools as $highSchool): ?>
                        <li class="item">
                            <a href="#highschool<?= $highSchool->id; ?>"><?= $highSchool->name_short; ?></a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </nav>
    </aside>
    <div class="right">
        <div class="collapseing-list">
            <?php foreach ($highSchools as $highSchool): ?>
                <div class="item" id="highschool<?= $highSchool->id; ?>">
                    <div class="box">
                        <div class="img-n-name">
                            <?php if ($highSchool->photo): ?>
                                <div class="img">
                                    <span class="sp">
                                        <img src="<?= $highSchool->photoUrl; ?>" alt="<?= $highSchool->name; ?>">
                                    </span>
                                </div>
                            <?php endif; ?>
                            <div class="name"><?= $highSchool->name; ?></div>
                        </div>
                        <div class="desc">
                            <div class="short">
                                <?= $highSchool->short_description; ?>
                            </div>
                            <div class="full">
                                <?= $highSchool->description; ?>
                            </div>
                            <button type="button" class="btn btn-default btn-arr">Подробнее <span class="fas fa-angle-double-down"></span></button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

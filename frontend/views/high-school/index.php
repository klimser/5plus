<?php

/* @var $this \frontend\components\extended\View */
/* @var $webpage common\models\Webpage */
/* @var $highSchools \common\models\HighSchool[] */

$this->registerJs('HighSchool.init();');

$tocContents = [];
?>
<div class="row">
    <div class="col-xs-12 col-md-9 highschools-content">
        <?php
        $lastHighSchool = count($highSchools);
        $i = 0;
        foreach ($highSchools as $highSchool): ?>
            <div class="row">
                <div class="col-xs-12">
                    <div class="highschool_block">
                        <?php if ($highSchool->photo): ?>
                            <div class="highschool_photo_wrapper col-xs-6 col-sm-5">
                                <img src="<?= $highSchool->photoUrl; ?>" class="highschool_photo" alt="<?= $highSchool->name; ?>">
                            </div>
                        <?php endif; ?>
                        <h2 class="highschool_title" id="highschool<?= $i; ?>"><?= $highSchool->name; ?></h2>
                        <div class="highschool_descr"><?= $highSchool->short_description; ?></div>
                        <button class="btn btn-info pull-right highschool_more_btn" onclick="HighSchool.showFullDescr(this);">подробнее</button>
                        <div class="clearfix"></div>
                        <div class="highschool_descr highschool_descr_full hidden"><?= $highSchool->description; ?></div>
                    </div>
                    <div class="clearfix"></div>
                    <?php
                        $tocContents[] = $highSchool->name_short;
                        $i++;
                        if ($i < $lastHighSchool) echo '<hr>';
                    ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <div class="col-md-3">
        <nav class="toc hidden-print hidden-sm hidden-xs">
            <ul class="nav">
                <?php foreach ($tocContents as $id => $descr): ?>
                    <li>
                        <a href="#highschool<?= $id; ?>"><?= $descr; ?></a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </nav>
    </div>
</div>

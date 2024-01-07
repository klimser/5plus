<?php
/* @var $subjectCategory \common\models\SubjectCategory */
/* @var $buttonLeft bool */
/* @var $index int */

$vars = [
    1 => [
        'section_class' => 'preparation-for-admission-to-universities-box',
        'subname' => 'ВУЗЫ',
        'slider_class' => 'swiper-preparation-for-admission-to-universities-slider',
        'button_class' => 'btn-danger',
    ],
    2 => [
        'section_class' => 'language-classes-box',
        'subname' => 'ЯЗЫКИ',
        'slider_class' => 'swiper-language-classes-slider',
        'button_class' => 'btn-link',
    ],
    3 => [
        'section_class' => 'preparatory-courses-for-students-box',
        'subname' => 'ШКОЛА',
        'slider_class' => 'swiper-preparatory-courses-for-students-slider',
        'button_class' => 'btn-primary',
    ],
    4 => [
        'section_class' => 'business-courses-box',
        'subname' => 'БИЗНЕС',
        'slider_class' => 'swiper-business-courses-slider',
        'button_class' => 'btn-link',
    ]
];

?>

<section class="<?= $vars[$subjectCategory->id]['section_class']; ?>">
    <div class="container">
        <?php if ($subjectCategory->id === 1): ?>
            <div class="body">
        <?php endif; ?>
            <div class="shadow-title"><?= $vars[$subjectCategory->id]['subname']; ?></div>
            <h2 class="block-title"><?= $subjectCategory->name; ?></h2>
            <div class="<?= $vars[$subjectCategory->id]['slider_class']; ?> swiper-container">
                <div class="swiper-wrapper">
                    <?php foreach ($subjectCategory->activeSubjects as $subject): ?>
                        <div class="swiper-slide item">
                                <?php if ($subjectCategory->id === 1): ?>
                                    <a href="<?= Yii::$app->homeUrl . $subject->webpage->url; ?>" class="card">
                                        <span class="img"><img src="<?= $subject->imageUrl; ?>" alt="<?= $subject->name['ru']; ?>"></span>
                                        <span class="name"><?= $subject->name['ru']; ?></span>
                                    </a>
                                <?php elseif ($subjectCategory->id === 2): ?>
                                    <a href="<?= Yii::$app->homeUrl . $subject->webpage->url; ?>" class="card">
                                        <span class="ico"><img src="<?= $subject->imageUrl; ?>" alt="<?= $subject->name['ru']; ?>"></span>
                                        <span class="name"><?= $subject->name['ru']; ?></span>
                                    </a>
                                <?php elseif ($subjectCategory->id === 3): ?>
                                    <div class="card">
                                        <img src="<?= $subject->imageUrl; ?>" alt="<?= $subject->name['ru']; ?>" class="img">
                                        <div class="text">
                                            <div class="name"><?= $subject->name['ru']; ?></div>
                                            <a href="<?= Yii::$app->homeUrl . $subject->webpage->url; ?>" class="link-readmore">подробнее</a>
                                        </div>
                                    </div>
                                <?php elseif ($subjectCategory->id === 4): ?>
                                    <div class="card">
                                        <a href="<?= Yii::$app->homeUrl . $subject->webpage->url; ?>" class="img">
                                            <img src="<?= $subject->imageUrl; ?>" alt="<?= $subject->name['ru']; ?>">
                                        </a>
                                        <a href="<?= Yii::$app->homeUrl . $subject->webpage->url; ?>" class="name"><?= $subject->name['ru']; ?></a>
                                    </div>
                                <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="swiper-button-prev"><img src="<?= Yii::$app->homeUrl; ?>assets/grunt/images/main-slider-arr.png" alt="arrow"></div>
            <div class="swiper-button-next"><img src="<?= Yii::$app->homeUrl; ?>assets/grunt/images/main-slider-arr.png" alt="arrow"></div>
            <button type="button" class="btn btn-enroll-in-a-course <?= $vars[$subjectCategory->id]['button_class']; ?>" onclick="MainPage.launchModal(); return false;">Записаться на курс</button>
        <?php if ($subjectCategory->id === 1): ?>
            </div>
            <img src="<?= Yii::$app->homeUrl; ?>assets/grunt/images/girl1.png" alt="girl" class="girl d-none d-xl-block">
        <?php endif; ?>
        <?php if ($subjectCategory->id === 3): ?>
            <img src="<?= Yii::$app->homeUrl; ?>assets/grunt/images/girl2.png" alt="girl" class="girl d-none d-xl-block">
        <?php endif; ?>
    </div>
</section>

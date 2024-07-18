<?php

use himiklab\yii2\recaptcha\ReCaptcha2;
use yii\bootstrap4\Html;
use yii\helpers\Url;

/* @var $this \yii\web\View */

?>

<div id="main-container">
    <header id="slide-1" class="section pp-section overflow-hidden">
        <div class="pt-3 pt-lg-5 container position-relative">
            <div class="row">
                <div class="logo-block col-12 col-lg-6 d-flex justify-content-center justify-content-lg-start align-items-center order-lg-1">
                    <img src="<?= Yii::$app->homeUrl; ?>images/logo.svg" class="img-fluid">
                    <div class="logo-text ml-3">Онлайн<br> школа</div>
                </div>
                <div class="slogan order-lg-3 col-12 col-lg-10 offset-lg-1 mt-5 mb-3 mb-lg-5 order-lg-3">
                    <h1 class="text-uppercase text-center">Онлайн-школа английского языка и подготовки в ВУЗЫ</h1>
                    <div class="text-shadow">Скоро онлайн школа!</div>
                </div>
                <div class="order-button-block col-12 col-lg-6 order-lg-2 d-flex justify-content-end align-items-center mb-3 mb-lg-0">
                    <button class=" btn order-button" onclick="MainPage.launchModal(); return false;">Оставить заявку</button>
                </div>
                <div class="video-box col-12 col-lg-8 offset-lg-2 order-lg-4 p-3">
                    <video width="100%" controls>
                        <source src="<?= Yii::$app->homeUrl; ?>video/welcome.mp4" type="video/mp4">
                    </video>
                </div>
                <div class="design-rectangle d-none d-lg-block"></div>
                <div class="design-circe d-none d-lg-block rounded-circle"></div>
            </div>
        </div>
    </header>
    <section id="slide-2" class="section pp-section overflow-hidden">
        <div class="container position-relative">
            <div class="row">
                <h2 class="col-12 col-lg-10 offset-lg-1 text-center mt-5 mb-4">Поступить в ВУЗ вашей мечты, выучить английский или подтянуть любой школьный предмет на 5+ сможет каждый!</h2>
            </div>
            <div class="row justify-content-between align-items-end">
                <div class="col">
                    <img class="img-fluid" src="<?= Yii::$app->homeUrl; ?>images/girl1.png">
                </div>
                <div class="col">
                    <img class="img-fluid" src="<?= Yii::$app->homeUrl; ?>images/girl2.png">
                </div>
                <div class="col">
                    <img class="img-fluid" src="<?= Yii::$app->homeUrl; ?>images/girl3.png">
                </div>
            </div>
            <div class="design-figure-1 rounded-circle"></div>
            <div class="design-figure-2 rounded-circle"></div>
        </div>
    </section>
    <section id="slide-3" class="section pp-section">
        <div class="container pb-3 pb-lg-5">
            <div class="row">
                <h2 class="col-12 col-lg-10 offset-lg-1 text-center mt-2 mt-lg-5 mb-3 mb-lg-5">За годы работы с учениками мы стали не просто учебным центром, а одной большой семьей, в которой мы ценим успехи и достижения каждого нашего студента</h2>
            </div>
            <div class="row">
                <div class="col-8 col-lg-6">
                    И теперь, мы создали для Вас удобную и качественную онлайн – платформу в которой есть
                </div>
            </div>
            <div class="row my-2 my-lg-4">
                <div class="col-12">
                    <div class="btn btn-blue btn-blue-1 my-2 my-lg-3 px-3 px-lg-4 py-1 py-lg-2">Домашние задания</div><br>
                    <div class="btn btn-blue btn-blue-2 my-2 my-lg-3 px-3 px-lg-4 py-1 py-lg-2">Контрольные работы</div><br>
                    <div class="btn btn-blue btn-blue-3 my-2 my-lg-3 px-3 px-lg-4 py-1 py-lg-2">Таблица посещений</div><br>
                    <div class="btn btn-blue btn-blue-4 my-2 my-lg-3 px-3 px-lg-4 py-1 py-lg-2">Журнал успеваемости</div><br>
                    <div class="btn btn-blue btn-blue-5 my-2 my-lg-3 px-3 px-lg-4 py-1 py-lg-2">Собственная библиотека книг</div><br>
                    <div class="btn btn-blue btn-blue-6 my-2 my-lg-3 px-3 px-lg-4 py-1 py-lg-2">Аудио и видеоматериалы</div>
                </div>
            </div>
            <div class="row">
                <div class="col-8 col-lg-6">
                    Общение с преподавателем проходит в режиме реального времени по видеосвязи и в чате. Все для Вашего комфортного обучения!
                </div>
            </div>
        </div>
    </section>
    <section id="slide-4" class="section pp-section">
        <div class="container pt-3 pt-lg-5">
            <div class="row">
                <h2 class="col-5 col-lg-4">Наблюдайте за своими уроками в личном кабинете</h2>
            </div>
        </div>
    </section>
    <section id="slide-5" class="section pp-section">
        <div class="container pt-lg-5 pb-4">
            <div class="row">
                <h2 class="col col-lg-6 offset-lg-3 text-center my-lg-5 mb-3">Оставьте заявку и наш специалист свяжется с вами</h2>
            </div>
            <?= Html::beginForm(Url::to(['order/create-online']), 'post', ['onsubmit' => 'return MainPage.completeOrder(this, false);', 'class' => 'order_form transparent-form']); ?>
                <div class="row mt-lg-5">
                    <div class="col-12 col-sm-6 mb-3">
                        <?= ReCaptcha2::widget(['name' => 'order[reCaptcha]']) ?>
                    </div>
                    <div class="col-12 col-sm-6">
                        <input name="order[subject]" class="form-control border mb-3 mb-lg-5" required minlength="2" maxlength="50" placeholder="Предмет">
                    </div>
                </div>    
                <div class="row">
                    <div class="col-12 col-sm-6">
                        <input name="order[name]" class="form-control border mb-3 mb-lg-5" required minlength="2" maxlength="50" placeholder="Имя">
                    </div>
                    <div class="col-12 col-sm-6">
                        <div class="input-group">
                            <div class="input-group-prepend mb-3 mb-lg-5">
                                <span class="input-group-text">+998</span>
                            </div>
                            <input type="tel" name="order[phoneFormatted]" class="form-control phone-formatted border mb-3 mb-lg-5" maxlength="11" pattern="\d{2} \d{3}-\d{4}" required placeholder="Телефон">
                        </div>
                    </div>
                </div>
                <div class="order_form_extra collapse"></div>
                <div class="row">
                    <div class="col">
                        <button class="btn btn-blue px-4 py-3">Отправить заявку</button>
                    </div>
                </div>
            <?= Html::endForm(); ?>
        </div>
    </section>
    <section id="slide-6" class="section pp-section">
        <div class="container">
            <div class="row align-items-center">
                <h2 class="col-12 col-lg-6 col-lg-4 order-lg-2 my-4 mb-lg-0">Онлайн-репетиторы для Ваших высоких результатов</h2>
                <div class="col-12 col-lg-6 order-lg-1">
                    <img class="img-fluid" src="<?= Yii::$app->homeUrl; ?>images/slide-6-bg.png">
                </div>
            </div>
        </div>
    </section>
    <section id="slide-results">
        <div class="container">
            <div class="row">
                <h2 class="col text-center my-3 my-lg-5">Результаты</h2>
            </div>
            <div class="row">
                <div class="col">
                    <div class="swiper-container">
                        <div class="swiper-wrapper">
                            <div class="swiper-slide item">
                                <div class="box">
                                    <video width="100%" controls>
                                        <source src="<?= Yii::$app->homeUrl; ?>video/result.mp4" type="video/mp4">
                                    </video>
                                </div>
                            </div>
                            <?php for ($i = 1; $i <= 33; $i++): ?>
                                <div class="swiper-slide item">
                                    <div class="box">
                                        <a class="img" href="<?= Yii::$app->homeUrl; ?>images/results/results_<?= $i; ?>.jpg" data-fancybox="">
                                            <img class="img-fluid" src="<?= Yii::$app->homeUrl; ?>images/results/results_<?= $i; ?>.jpg">
                                        </a>
                                    </div>
                                </div>
                            <?php endfor; ?>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col text-center my-4">
                    <button type="button" class="results-prev btn btn-secondary rounded-circle mr-3"><span class="fas fa-chevron-left"></span></button>
                    <button type="button" class="results-next btn btn-secondary rounded-circle ml-3"><span class="fas fa-chevron-right"></span></button>
                </div>
            </div>
            <?php /*<div class="row">
                <div class="col-12 col-lg-4 offset-lg-4 text-center">
                    <video width="100%" controls>
                        <source src="<?= Yii::$app->homeUrl; ?>video/result.mp4" type="video/mp4">
                    </video>
                </div>
            </div>*/ ?>
        </div>
    </section>
    <section id="slide-7" class="section pp-section overflow-hidden">
        <div class="container position-relative">
            <div class="row">
                <h2 class="col-12 col-lg-8 offset-lg-2 text-center my-4">Учитесь, где Вам удобно, главное - иметь выход в интернет</h2>
                <div class="col-12 text-center">
                    <img class="img-fluid" src="<?= Yii::$app->homeUrl; ?>images/slide-7-bg.png">
                </div>
            </div>
            <div class="design-figure-1 rounded-circle"></div>
        </div>
    </section>
    <section id="slide-8" class="section pp-section">
        <div class="container">
            <div class="row">
                <h2 class="col-12 text-center my-4">Удобная система оплаты</h2>
                <div class="col-12">
                    <img class="img-fluid" src="<?= Yii::$app->homeUrl; ?>images/slide-8-bg.jpg">
                </div>
            </div>
        </div>
    </section>
    <section id="slide-9" class="section pp-section">
        <div class="container">
            <div class="row">
                <h2 class="col-12 col-lg-8 offset-lg-2 text-center mt-4 mt-lg-5">Помощь и поддержка администрации на протяжении всего периода обучения</h2>
            </div>
        </div>
    </section>
    <section id="slide-10" class="section pp-section">
        <div class="container pt-3 pt-lg-5 pb-5">
            <div class="row">
                <h2 class="col my-3 mt-lg-5">Оставляйте Вашу заявку</h2>
            </div>
            <?= Html::beginForm(Url::to(['order/create-online']), 'post', ['onsubmit' => 'return MainPage.completeOrder(this, false);', 'class' => 'order_form transparent-form']); ?>
            <div class="row">
                <div class="col-12 col-sm-6">
                    <input name="order[name]" class="form-control border mb-3 mb-lg-4" required minlength="2" maxlength="50" placeholder="Имя">
                </div>
                <div class="col-12 col-sm-6">
                    <input name="order[subject]" class="form-control border mb-3 mb-lg-4" required minlength="2" maxlength="50" placeholder="Предмет">
                </div>
            </div>
            <div class="row">
                <div class="col-12 col-sm-6">
                    <div class="input-group">
                        <div class="input-group-prepend mb-3 mb-lg-4">
                            <span class="input-group-text">+998</span>
                        </div>
                        <input type="tel" name="order[phoneFormatted]" class="form-control phone-formatted border mb-3 mb-lg-4" maxlength="11" pattern="\d{2} \d{3}-\d{4}" required placeholder="Телефон">
                    </div>
                </div>
                <div class="col-12 col-sm-6 mb-3">
                    <?= ReCaptcha2::widget(['name' => 'order[reCaptcha]']) ?>
                </div>
            </div>
            <div class="order_form_extra collapse"></div>
            <div class="row">
                <div class="col text-center text-lg-right">
                    <button class="btn btn-white font-weight-bold text-uppercase px-5 py-2">Отправить заявку</button>
                </div>
            </div>
            <?= Html::endForm(); ?>
        </div>
    </section>
    <footer id="slide-11" class="section pp-section position-relative overflow-hidden">
        <div class="pt-4 container">
            <div class="row">
                <div class="col-6 logo-block">
                    <div class="row align-items-center">
                        <div class="col col-lg-2">
                            <img src="<?= Yii::$app->homeUrl; ?>images/logo_inverse.svg" class="img-fluid">
                        </div>
                        <div class="col col-lg-10 logo-text">
                            Онлайн<br> школа
                        </div>
                    </div>
                    <div class="row pt-4 pb-3">
                        <div class="col text-center text-lg-left">
                            <button class="btn btn-blue-2" onclick="MainPage.launchModal(); return false;">Задать вопрос</button>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-lg-4 offset-lg-2 contacts-block">
                    <div class="row">
                        <div class="col-12 col-lg-6">
                            <h2>Контакты</h2>
                            г. Ташкент, ул. Ойбек, 16
                            <div class="row my-3">
                                <div class="col"><a href="https://www.instagram.com/5plus_studycenter/" target="_blank"><i class="fab fa-instagram"></i></a></div>
                                <div class="col"><a href="https://www.facebook.com/education85" target="_blank"><i class="fab fa-facebook-f"></i></a></div>
                                <div class="col"><a href="https://t.me/fiveplus" target="_blank"><i class="fab fa-telegram-plane"></i></a></div>
                                <div class="col"><a href="https://g.page/5plus_studycenter" target="_blank"><i class="fab fa-google"></i></a></div>
                            </div>
                        </div>
                        <div class="col-12 col-lg-6 yellow">
                            <a href="tel:+998787770350" class="text-decoration-none">78 777 03 50</a><br>
                            <a href="https://t.me/fiveplus_public_bot" class="text-decoration-none">@fiveplus_public_bot</a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="design-figure-1 rounded-circle"></div>
        </div>
    </footer>
</div>

<div id="order_form" class="modal fade" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <?= Html::beginForm(
                Url::to(['order/create-online']),
                'post',
                [
                    'onsubmit' => 'return MainPage.completeOrder(this, false);'
                ]
            ); ?>
            <div class="modal-header">
                <h4 class="modal-title">Записаться на курс</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="order_form_body collapse show">
                    <div class="form-group">
                        <label for="order-name">Ваше имя</label>
                        <input name="order[name]" id="order-name" class="form-control" required minlength="2" maxlength="50">
                    </div>
                    <div class="form-group">
                        <label for="order-subject">Предмет</label>
                        <input name="order[subject]" id="order-subject" class="form-control" required minlength="2" maxlength="50">
                    </div>
                    <div class="form-group">
                        <label for="order-phone">Ваш номер телефона</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text">+998</span>
                            </div>
                            <input type="tel" name="order[phoneFormatted]" id="order-phone" class="form-control phone-formatted" maxlength="11" pattern="\d{2} \d{3}-\d{4}" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="order-comment">Дополнительные сведения, пожелания</label>
                        <textarea name="order[user_comment]" id="order-comment" class="form-control" maxlength="255"></textarea>
                    </div>
                    <?= ReCaptcha2::widget(['name' => 'order[reCaptcha]']) ?>
                </div>
                <div class="order_form_extra collapse"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">отмена</button>
                <button class="btn btn-primary">Отправить заявку</button>
            </div>
            <?= Html::endForm(); ?>
        </div>
    </div>
</div>

<?php

    $this->registerJs(<<<SCRIPT
var mySwiper = new Swiper('.swiper-container', {
  speed: 500,
  loop: true,
  spaceBetween: 25,
  slidesPerView: 1,
  watchSlidesVisibility: true,
  disableOnInteraction: true,
  watchOverflow: true,
  autoplay: {
    delay: 6000,
  },
  navigation: {
    nextEl: '.results-next',
    prevEl: '.results-prev',
  },
  breakpoints: {
    576: {
      slidesPerView: 2,
    },
    768: {
      slidesPerView: 3,
    },
    992: {
      slidesPerView: 4,
    },
  }
});
SCRIPT
);

//    $this->registerJsFile('https://cdnjs.cloudflare.com/ajax/libs/pagePiling.js/1.5.6/jquery.pagepiling.min.js', ['integrity' => 'sha512-FcXc9c211aHVJEHxoj2fNFeT8+wUESf/4mUDIR7c31ccLF3Y6m+n+Wsoq4dp2sCnEEDVmjhuXX6TfYNJO6AG6A==', 'crossorigin' => 'anonymous', 'depends' => [\yii\web\JqueryAsset::class]]);

//    $this->registerJs(<<<SCRIPT
//        $('#main-container').pagepiling({
//            menu: null,
//            direction: 'vertical',
//            verticalCentered: true,
//            sectionsColor: [],
//            anchors: [],
//            scrollingSpeed: 700,
//            easing: 'swing',
//            loopBottom: false,
//            loopTop: false,
//            css3: true,
//            navigation: {
//                'textColor': '#000',
//                'bulletsColor': '#000',
//                'position': 'right',
//                'tooltips': ['section1', 'section2', 'section3', 'section4']
//            },
//            normalScrollElements: null,
//            normalScrollElementTouchThreshold: 5,
//            touchSensitivity: 5,
//            keyboardScrolling: true,
//            sectionSelector: '.section',
//            animateAnchor: false,
//        });
//SCRIPT
//);

<?php
/* @var $this \frontend\components\extended\View */
/* @var $title string */
?>
<div class="row">
    <?php /*<div class="col-xs-12 visible-xs social_links text-right">
        <a href="https://www.facebook.com/education85" target="_blank">
            <img src="/images/fb_logo.png" alt="Наша группа в Facebook" title="Следите за нашими новостями на Facebook">
        </a>
        <a href="https://www.instagram.com/5plus_studycenter/" target="_blank">
            <img src="/images/instagram_logo.png" alt="Наш профиль в Instagram" title="Наш профиль в Instagram">
        </a>
        <a href="https://telegram.me/fiveplus" target="_blank">
            <img src="/images/telegram_logo.png" alt="Наш канал в Telegram" title="Наш канал в Telegram">
        </a>
    </div>*/ ?>
    <div class="col-xs-12">
        <div class="social_links pull-right">
            <a href="https://www.facebook.com/education85" target="_blank" rel="noopener noreferrer">
                <img src="/images/fb_logo.png" alt="Наша группа в Facebook" title="Следите за нашими новостями на Facebook">
            </a>
            <a href="https://www.instagram.com/5plus_studycenter/" target="_blank" rel="noopener noreferrer">
                <img src="/images/instagram_logo.png" alt="Наш профиль в Instagram" title="Наш профиль в Instagram">
            </a>
            <a href="https://telegram.me/fiveplus" target="_blank" rel="noopener noreferrer">
                <img src="/images/telegram_logo.png" alt="Наш канал в Telegram" title="Наш канал в Telegram">
            </a>
        </div>
        <h1><?= $title; ?></h1>
    </div>
</div>

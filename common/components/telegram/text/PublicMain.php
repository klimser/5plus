<?php

namespace common\components\telegram\text;

use Longman\TelegramBot\Entities\Keyboard;

class PublicMain
{
    const ICON_CHECK = '✅';
    const ICON_CROSS = '❌';
    const ICON_REMOVE = 'Сбросить';

    const CURRENCY_SIGN = 'сум';
    const DEBT = 'долг';
    const PAY_ONLINE = 'Оплатить онлайн';
    
    const BUTTON_INFO = '📚Про Ваш "Пять с Плюсом"';
    const BUTTON_REGISTER = '📑Зарегистрироваться';
    const BUTTON_ACCOUNT = '🏰Личный кабинет';
    const BUTTON_CONTACT = '📱Связаться с нами';
    const BUTTON_ORDER = '💻Оставить заявку';
    const BUTTON_PAY = '💳Оплатить';
    
    const GREETING = '👩‍🏫Добро пожаловать в Ваш любимый "Пять с Плюсом"!';
    const TO_MAIN = '⬅️На главную';
    const TO_BACK = '⬅️ Назад';

    const CONTACT_NAME = 'Пять с Плюсом';
    const CONTACT_SURNAME = 'Ваш Учебный центр';
    const CONTACT_PHONE = '+99878-777-03-50';
    const CONTACT_VCARD = <<<VCARD
BEGIN:VCARD
VERSION:3.0
N:Пять с Плюсом;Ваш Учебный центр;;;™
FN:Пять с Плюсом™
KIND:organization
URL:https://5plus.uz
PHOTO;TYPE=PNG:https://5plus.uz/icons/apple-touch-icon.png
TEL;TYPE=work,voice;VALUE=main:+99878-777-03-50
ADR;TYPE=WORK;GEO="41.297022,69.274574";LANGUAGE="RU";LABEL="Ваш Учебный центр Пять с Плюсом":;2 этаж;ул. Ойбек 16;Ташкент;;100015;Узбекистан
GEO:41.297022,69.274574
EMAIL:5plus.center@gmail.com
TZ:Asia/Tashkent
LANG:ru
LANG:uz
LANG:en
REV:20191206T124648Z
END:VCARD
VCARD;

    const LOCATION_LATITUDE = '41.29696932989273';
    const LOCATION_LONGITUDE = '69.27454679248275';
    const LOCATION_TITLE = 'Пять с Плюсом';
    const LOCATION_ADDRESS = 'Ташкент, ул. Ойбек 16';
    const GOOGLE_PLACE_ID = 'ChIJ8dAoiiiLrjgR9deiQfCakq0';

    const CONTACT_MESSAGE = <<<MESSAGE
Написать нам в Telegram: @fiveplusuz\_bot
🏢Наш адрес: *г\.Ташкент, ул\. Ойбек, 16*
[Мы в Facebook](https://www.facebook.com/education85)
[Мы в Инстаграм](https://www.instagram.com/5plus_studycenter/)
Наши телеграм\-каналы:
 Пять с Плюсом @fiveplus
 Пять с Плюсом KIDS @fivepluskids
MESSAGE;
    
    const INFO_STEP_1_TEXT = '✍️Выберите категорию';
    const INFO_STEP_BUTTON_TEACHERS = '💁‍♀️Список преподавателей';
    const INFO_STEP_BUTTON_SUBJECTS = '🔍Предметы';
    const INFO_STEP_BUTTON_PRICES = '💵Цены';
    const INFO_STEP_2_PRICE_TEXT = '💳Наши цены: [тут](%s)';
    const INFO_STEP_2_SUBJECT_TEXT = '📚Выберите направление';
    const INFO_STEP_3_SUBJECT_TEXT = '📚Список предметов';
    const INFO_STEP_2_TEACHER_TEXT = '💁‍♀Выберите предмет';
    const INFO_STEP_3_TEACHER_TEXT = "👩‍🏫👨‍🏫Ваши преподаватели:";
    const INFO_STEP_3_TEACHER_TEXT_OFFICE = "👩‍💼👨‍💼Администрация:";
    
    const ORDER_STEP_1_TEXT = '🗒Как вас зовут?';
    const ORDER_STEP_2_TEXT = '📱Ваш номер телефона для связи?';
    const SEND_PHONE_BUTTON = '📱Отправить мой телефон';
    const ERROR_PHONE_LENGTH = '😮Укажите корректный номер телефона, как минимум код оператора и 7-значный номер';
    const ERROR_PHONE_PREFIX = '😦Укажите корректный номер телефона для Узбекистана';
    const ORDER_STEP_3_TEXT = '🎒📚Выберите направление';
    const ORDER_STEP_3_ERROR = '🤷‍♂️Выберите направление из списка';
    const ORDER_STEP_4_TEXT = '📚Выберите предмет';
    const ORDER_STEP_4_ERROR = '🤷‍♂️Выберите предмет из списка';
    const ORDER_STEP_5_BUTTON = '👨‍💻Отправить заявку';
    const ORDER_STEP_5_TEXT = '😀Напишите дополнительную информацию к вашей заявке или просто нажмите "' . self::ORDER_STEP_5_BUTTON . '".';
    const ORDER_STEP_6_TEXT = '😁Ваша заявка принята! Ваш любимый менеджер совсем скоро свяжется с Вами😘';

    const LOGIN_SUCCESS = '👏Вы авторизованы!';
    const LOGIN_RESET_BY_TRUSTED = '🚷Ваш аккаунт сброшен, так как был связан с другим подтвержденным аккаунтом Telegram.';
    const LOGIN_STEP_1_TEXT = '📲Введите или отправьте номер телефона, указанный Вами при регистрации в "Пять с Плюсом"';
    const LOGIN_STEP_2_FAILED = '😢Не найдено совпадений. Введите, пожалуйста, номер телефона еще раз';
    const LOGIN_STEP_2_LOCKED = '🛑Аккаунт уже связан с другим аккаунтом Telegram. Если это ваш аккаунт, используйте функцию "Сбросить" в личном кабинете, затем попробуйте авторизоваться снова.';
    const LOGIN_STEP_2_LOCKED_UNTRUSTED = '🛑Аккаунт уже связан с другим аккаунтом Telegram. Попробуйте отправить ваш номер нажав кнопку "' . self::SEND_PHONE_BUTTON . '". Если это ваш аккаунт, используйте функцию "Сброс" в личном кабинете, затем попробуйте авторизоваться снова.';
    const LOGIN_STEP_2_MULTIPLE = '👥Найдено несколько пользователей. Напишите, пожалуйста, вашу фамилию или имя.';
    const LOGIN_STEP_3_FAILED = '😢Не найдено совпадений. Напишите, пожалуйста, вашу фамилию или имя еще раз';
    const LOGIN_STEP_3_MULTIPLE = '👥Найдено несколько пользователей. Напишите, пожалуйста, вашу фамилию и имя.';

    const ACCOUNT_BUTTON_ATTEND = '🍔Посещаемость';
    const ACCOUNT_BUTTON_MARKS = '5️⃣➕Оценки';
    const ACCOUNT_BUTTON_BALANCE = '💰Оплатить';
    const ACCOUNT_BUTTON_PAYMENT = '💷История списаний';
    const ACCOUNT_SUBSCRIPTION  = '📢Управление подпиской на уведомления';
    const ACCOUNT_EDIT_STUDENTS = '🧒Добавить/сбросить студентов';
    const ACCOUNT_CONFIRM       = '✅Подтвердить аккаунт';
    
    const ACCOUNT_STEP_1_TEXT = '👋Добро пожаловать';
    const ACCOUNT_STEP_2_SELECT_USER = '👥Выберите студента';
    
    const ATTEND_HAS_MISSED = '⚽️Пропущено %d занятий за последние 90 дней';
    const ATTEND_NO_MISSED = '🙇‍♂️Нет пропусков за последние 90 дней';

    const MARKS_NONE = '🗒За последние 90 дней оценок нет';
    const MARKS_TEXT = '🗓Оценки за последние 90 дней';

    const BANALCE_TEXT      = '🖥Ваш баланс';
    const BALANCE_NO_COURSE = 'Студент не занимается ни в одной группе';
    
    const PAYMENT_TEXT = '📈Списания за последние 90 дней:';
    const PAYMENT_NO_PAYMENTS = '📉У вас нет списаний за последние 90 дней';
    
    const SUBSCRIPTION_YES = '✅Подписка включена';
    const SUBSCRIPTION_NO = '❌Подписка отключена';
    const SUBSCRIPTION_ENABLE = '✅Включить подписку';
    const SUBSCRIPTION_DISABLE = '❌Отключить подписку';
    
    const STUDENTS_TEXT = 'Вы можете добавить несколько студентов на один Telegram аккаунт';
    const STUDENTS_ADD  = '👤➕Добавить';
    
    const ACCOUNT_CONFIRM_TEXT = '✔️Вы можете подтвердить свой аккаунт нажав на кнопку ' . self::SEND_PHONE_BUTTON . ' или при помощи SMS';
    const ACCOUNT_CONFIRM_SMS = '📄Подтвердить по SMS';
    const ACCOUNT_CONFIRM_NO_USERS = '✔️Все студенты уже подтверждены';
    const ACCOUNT_CHECK_FAILED_NOT_FOUND = '😢Не найдено совпадений';
    const ACCOUNT_CHECK_FAILED_CODE_INVALID = 'Неверный код подтверждения';
    const ACCOUNT_CHECK_SUCCESS = '😃Подтверждён';
    const ACCOUNT_CHECK_SUCCESS_NONE = 'Для этого номера телефона все студенты уже подтверждены';
    const ACCOUNT_CONFIRM_STEP_3_TEXT = '☎️Выберите телефон для отправки SMS';
    const ACCOUNT_CONFIRM_STEP_4_FAILED = '🙁Не удалось отправить SMS. Попробуйте снова.';
    const ACCOUNT_CONFIRM_SMS_LOCKED = '🙅‍♂️Номер заблокирован, отправить SMS снова можно будет через 1 день';
    const ACCOUNT_CONFIRM_SMS_TOO_MUCH_ATTEMPTS = '🙅‍♂️Слишком много неудачных попыток. Код подтверждения заблокирован, отправьте новый код';
    const ACCOUNT_CONFIRM_STEP_4_TEXT = 'Введите код подтверждения из SMS.';

    const ATTENDANCE_ATTEND = '✅%s присутствует на занятии "%s"';
    const ATTENDANCE_MISS = '❌%s отсутствует на занятии "%s"';
    const ATTENDANCE_MARK = '📓%s оценка %d в группе "%s"';

    public const PAY_MIN_AMOUNT    = 20000;
    public const PAY_CHOOSE_COURSE = '🧑‍🤝‍🧑Выберите группу';
    public const PAY_NO_COURSE     = 'Нет групп для оплаты. Обратитесь за помощью к менеджерам учебного центра.';
    public const PAY_ONE_LESSON   = 'Одно занятие';
    public const PAY_ONE_MONTH = 'Один месяц';
    public const PAY_CHOOSE_AMOUNT = '💰Выберите сумму для оплаты из представленных или введите свою сумму (не менее ' . self::PAY_MIN_AMOUNT . ').';
    public const PAY_ITEM_TITLE = 'Занятия в группе "%s"';
    public const PAY_ITEM_ATTENTION = 'Внимание! Оплата с повышенной стоимостью занятия. Для оплаты с обычной стоимостью оплачивайте не менее 12 занятий.';
    public const PAY_ITEM_DESCRIPTION = 'Оплата за обучение в группе "%s". Стоимость одного занятия - %d.';
    public const PAY_SUCCESSFUL = 'Оплата принята. Спасибо. Ждем вас в Вашем учебном центре "Пять с плюсом"!';

    public const PUBLIC_OFFER_LINK = '[Публичной офертой](https://5plus.uz/docs/public_offer_2026.pdf)';
    public const AGE_GREETING = 'Для оплаты услуг учебного центра "Пять с плюсом" необходимо согласие с %s. Выберите номер телефона для получения кода подтверждения.';
    public const AGE_SEND_SMS = 'Получить код';
    public const AGE_ENTER_THE_CODE = 'Введите код из СМС или нажмите "' . self::AGE_SEND_SMS . '" если СМС еще не была вам отправлена.';
    public const AGE_SMS_SENT = 'СМС отправлена. Введите код из СМС.';
    public const AGE_SMS_DELAY = 'СМС не могут быть отправлены слишком часто, дождитесь получения СМС на телефон или запросите повторную отправку позже (%s)';
    public const AGE_SMS_FAILED = 'Не удалось отправить СМС. Попробуйте позже или обратитесь за помощью к менеджерам учебного центра';
    public const AGE_AGREEMENT = 'Подтверждаю, что ознакомлен(-а) и согласен(-на) с %s и подтверждаю, что мне исполнилось 18 лет.';
    public const AGE_FAILED = 'Не подтверждено. Проверьте корректность введенного кода из СМС.';
    public const AGE_COMPLETE = 'Спасибо, желаем Вам шикарной учебы!';
    
    public static function getBackAndMainKeyboard()
    {
        $keyboard = new Keyboard([self::TO_BACK, self::TO_MAIN]);
        $keyboard->setResizeKeyboard(true)->setSelective(false);
        return $keyboard;
    }
    
    public static function getPhoneKeyboard(array $buttons = [])
    {
        $buttons[] = [['text' => PublicMain::SEND_PHONE_BUTTON, 'request_contact' => true]];
        $buttons[] = [PublicMain::TO_BACK, PublicMain::TO_MAIN];
        $keyboard = new Keyboard(...$buttons);
        $keyboard->setResizeKeyboard(true)->setSelective(false);
        return $keyboard;
    }
}

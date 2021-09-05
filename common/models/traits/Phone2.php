<?php

namespace common\models\traits;

use common\components\helpers\Phone as PhoneHelper;

/**
 * Trait Phone2
 * @package common\models\traits
 * @property string $phone2
 * @property string $phone2Formatted
 * @property string $phone2Full
 * @property string $phone2International
 */
trait Phone2
{
    public function setPhone2Formatted(string $value): void
    {
        $this->phone2 = empty($value) ? null : '+998' . substr(preg_replace('#\D#', '', $value), -9);
    }

    private function getPhone2AttributeValue(): ?string
    {
        return property_exists($this, 'phone2') ? $this->phone2 : null;
    }

    protected function getPhone2DigitsOnly(): ?string
    {
        if (!$phone = $this->getPhone2AttributeValue()) {
            return $phone;
        }

        return PhoneHelper::getPhoneDigitsOnly($phone);
    }

    public function getPhone2Formatted(): ?string
    {
        if (!$phone = $this->getPhone2AttributeValue()) {
            return $phone;
        }

        return PhoneHelper::getPhoneFormatted($phone);
    }

    public function getPhone2Full(): ?string
    {
        if (!$phone = $this->getPhone2AttributeValue()) {
            return $phone;
        }

        return PhoneHelper::getPhoneFull($phone);
    }

    public function getPhone2International(): ?string
    {
        if (!$phone = $this->getPhone2AttributeValue()) {
            return $phone;
        }

        return PhoneHelper::getPhoneInternational($phone);
    }
}

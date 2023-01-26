<?php

namespace common\models\traits;

use common\components\helpers\PhoneHelper as PhoneHelper;

/**
 * Trait Phone
 * @package common\models\traits
 * @property string $phoneFormatted
 * @property string $phoneFull
 * @property string $phoneInternational
 */
trait Phone
{
    private function getPhoneAttributeName(): string
    {
        return property_exists($this, 'phoneAttribute') ? $this->phoneAttribute : 'phone';
    }
    
    private function getPhoneAttributeValue(): ?string
    {
        $attr = $this->getPhoneAttributeName();
        return $this->$attr ?? null;
    }

    public function setPhoneFormatted(string $value): void
    {
        $attr = $this->getPhoneAttributeName();
        $this->$attr = empty($value) ? null : '+998' . substr(preg_replace('#\D#', '', $value), -9);
    }

    protected function getPhoneDigitsOnly(): ?string
    {
        if (!$phone = $this->getPhoneAttributeValue()) {
            return $phone;
        }

        return PhoneHelper::getPhoneDigitsOnly($phone);
    }

    public function getPhoneFormatted(): ?string
    {
        if (!$phone = $this->getPhoneAttributeValue()) {
            return $phone;
        }

        return PhoneHelper::getPhoneFormatted($phone);
    }

    public function getPhoneFull(): ?string
    {
        if (!$phone = $this->getPhoneAttributeValue()) {
            return $phone;
        }

        return PhoneHelper::getPhoneFull($phone);
    }

    public function getPhoneInternational(): ?string
    {
        if (!$phone = $this->getPhoneAttributeValue()) {
            return $phone;
        }

        return PhoneHelper::getPhoneInternational($phone);
    }
}

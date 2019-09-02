<?php

namespace common\models\traits;

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

    public function setPhoneFormatted(string $value): void
    {
        $attr = $this->getPhoneAttributeName();
        $this->$attr = empty($value) ? null : '+998' . substr(preg_replace('#\D#', '', $value), -9);
    }

    protected function getPhoneDigitsOnly(): ?string
    {
        $attr = $this->getPhoneAttributeName();
        if (!$this->$attr) return $this->$attr;

        return preg_replace('#\D#', '', $this->$attr);
    }

    public function getPhoneFormatted(): ?string
    {
        $digits = $this->getPhoneDigitsOnly();
        if (!$digits || strlen($digits) !== 12) return $digits;

        return substr($digits, -9, 2) . ' ' . substr($digits, -7, 3) . '-' . substr($digits, -4);
    }

    public function getPhoneFull(): ?string
    {
        $digits = $this->getPhoneDigitsOnly();
        if (!$digits || strlen($digits) !== 12) return $digits;

        return '+' . substr($digits, -12, 3) . '(' . substr($digits, -9, 2) . ') ' . substr($digits, -7, 3) . '-' . substr($digits, -4);
    }

    public function getPhoneInternational(): ?string
    {
        $digits = $this->getPhoneDigitsOnly();
        if (!$digits || strlen($digits) !== 12) return $digits;

        return "+$digits";
    }
}

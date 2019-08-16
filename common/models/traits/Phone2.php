<?php

namespace common\models\traits;

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
    protected $phone2Formatted;

    public function setPhone2Formatted($value): void
    {
        $this->phone2Formatted = $value;
    }

    protected function getPhone2DigitsOnly(): ?string
    {
        if (!$this->phone2) return $this->phone2;
        return preg_replace('#\D#', '', $this->phone2);
    }

    public function getPhone2Formatted(): ?string
    {
        $digits = $this->getPhone2DigitsOnly();
        if (!$digits || strlen($digits) !== 12) return $digits;

        return substr($digits, -9, 2) . ' ' . substr($digits, -7, 3) . '-' . substr($digits, -4);
    }

    public function getPhone2Full(): ?string
    {
        $digits = $this->getPhone2DigitsOnly();
        if (!$digits || strlen($digits) !== 12) return $digits;

        return '+' . substr($digits, -12, 3) . '(' . substr($digits, -9, 2) . ') ' . substr($digits, -7, 3) . '-' . substr($digits, -4);
    }

    public function getPhone2International(): ?string
    {
        $digits = $this->getPhoneDigitsOnly();
        if (!$digits || strlen($digits) !== 12) return $digits;

        return "+$digits";
    }

    protected function loadPhone2()
    {
        if (!empty($this->phone2Formatted)) {
            $this->phone2 = '+998' . substr(preg_replace('#\D#', '', $this->phone2Formatted), -9);
        }
    }
}

<?php

namespace common\models\traits;

/**
 * Trait Phone
 * @package common\models\traits
 * @property string $phone
 * @property string $phoneFormatted
 * @property string $phoneFull
 */
trait Phone
{
    protected $phoneFormatted;

    public function setPhoneFormatted($value)
    {
        $this->phoneFormatted = $value;
    }

    public function getPhoneFormatted()
    {
        if (!$this->phone) return $this->phone;

        $digits = preg_replace('#\D#', '', $this->phone);
        if (strlen($digits) == 12) {
            return substr($digits, -9, 2) . ' ' . substr($digits, -7, 3) . '-' . substr($digits, -4);
        } else return $this->phone;
    }

    public function getPhoneFull()
    {
        if (!$this->phone) return $this->phone;

        $digits = preg_replace('#\D#', '', $this->phone);
        if (strlen($digits) == 12) {
            return '+' . substr($digits, -12, 3) . '(' . substr($digits, -9, 2) . ') ' . substr($digits, -7, 3) . '-' . substr($digits, -4);
        } else return $this->phone;
    }

    protected function loadPhone()
    {
        if (!empty($this->phoneFormatted)) {
            $this->phone = '+998' . substr(preg_replace('#\D#', '', $this->phoneFormatted), -9);
        }
    }
}
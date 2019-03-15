<?php

namespace common\models\traits;

/**
 * Trait Phone
 * @package common\models\traits
 * @property string $phoneFormatted
 * @property string $phoneFull
 */
trait Phone
{
    protected $phoneFormatted;

    private function getPhoneAttributeName(): string
    {
        return property_exists($this, 'phoneAttribute') ? $this->phoneAttribute : 'phone';
    }

    public function setPhoneFormatted($value)
    {
        $this->phoneFormatted = $value;
    }

    public function getPhoneFormatted()
    {
        $attr = $this->getPhoneAttributeName();
        if (!$this->$attr) return $this->$attr;

        $digits = preg_replace('#\D#', '', $this->$attr);
        if (strlen($digits) == 12) {
            return substr($digits, -9, 2) . ' ' . substr($digits, -7, 3) . '-' . substr($digits, -4);
        } else return $this->$attr;
    }

    public function getPhoneFull()
    {
        $attr = $this->getPhoneAttributeName();
        if (!$this->$attr) return $this->$attr;

        $digits = preg_replace('#\D#', '', $this->$attr);
        if (strlen($digits) == 12) {
            return '+' . substr($digits, -12, 3) . '(' . substr($digits, -9, 2) . ') ' . substr($digits, -7, 3) . '-' . substr($digits, -4);
        } else return $this->$attr;
    }

    protected function loadPhone()
    {
        $attr = $this->getPhoneAttributeName();
        if (!empty($this->phoneFormatted)) {
            $this->$attr = '+998' . substr(preg_replace('#\D#', '', $this->phoneFormatted), -9);
        }
    }
}
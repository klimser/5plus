<?php

namespace common\models\traits;

/**
 * Trait Phone2
 * @package common\models\traits
 * @property string $phone2
 * @property string $phone2Formatted
 * @property string $phone2Full
 */
trait Phone2
{
    protected $phone2Formatted;

    public function setPhone2Formatted($value)
    {
        $this->phone2Formatted = $value;
    }

    public function getPhone2Formatted()
    {
        if (!$this->phone2) return $this->phone2;

        $digits = preg_replace('#\D#', '', $this->phone2);
        if (strlen($digits) == 12) {
            return substr($digits, -9, 2) . ' ' . substr($digits, -7, 3) . '-' . substr($digits, -4);
        } else return $this->phone2;
    }

    public function getPhone2Full()
    {
        if (!$this->phone2) return $this->phone2;

        $digits = preg_replace('#\D#', '', $this->phone2);
        if (strlen($digits) == 12) {
            return '+' . substr($digits, -12, 3) . '(' . substr($digits, -9, 2) . ') ' . substr($digits, -7, 3) . '-' . substr($digits, -4);
        } else return $this->phone2;
    }

    protected function loadPhone2()
    {
        if (!empty($this->phone2Formatted)) {
            $this->phone2 = '+998' . substr(preg_replace('#\D#', '', $this->phone2Formatted), -9);
        }
    }
}
<?php

namespace common\components\extended;

class ActiveRecord extends \yii\db\ActiveRecord
{
    const STATUS_ACTIVE = 1;
    const STATUS_INACTIVE = 0;

    public function moveErrorsToFlash()
    {
        if (!empty($this->errors)) {
            foreach ($this->getErrors() as $field => $errorArray) {
                foreach ($errorArray as $error) {
                    \Yii::$app->session->addFlash('error', $field . ': ' . $error);
                }
            }
        }
    }

    /**
     * @return array|string[]
     */
    public function getErrorsAsStringArray(): array
    {
        $errors = [];
        if (!empty($this->errors)) {
            foreach ($this->getErrors() as $field => $errorArray) {
                foreach ($errorArray as $error) {
                    $errors[] = $field . ': ' . $error;
                }
            }
        }
        return $errors;
    }

    /**
     * @param string|null $field
     * @return string
     */
    public function getErrorsAsString($field = null): string
    {
        $output = '';
        if (!empty($this->errors)) {
            if ($field) {
                foreach ($this->getErrors($field) as $error) {
                    if ($output) $output .= ', ';
                    $output .= $field . ': ' . $error;
                }
            } else {
                foreach ($this->getErrors() as $field => $errorArray) {
                    foreach ($errorArray as $error) {
                        if ($output) $output .= ', ';
                        $output .= $field . ': ' . $error;
                    }
                }
            }
        }
        return $output;
    }

    /**
     * @param \yii\db\ActiveRecord[] $list
     * @param string $fieldName
     * @return array
     */
    public static function getListAsMap($list, $fieldName = 'id') {
        $map = [];
        /** @var \yii\db\ActiveRecord $list */
        foreach ($list as $element) {
            if ($element->$fieldName !== null) $map[$element->$fieldName] = $element;
        }
        return $map;
    }

    /**
     * @param string $text
     * @param string $highlightClassName
     * @return string
     */
    public static function convertTextForEditor($text, $highlightClassName) {
        if (!is_string($text)) $text = '';
        $text = str_replace(['<span class="' . $highlightClassName . '">', '</span>', '</p><p>'], ['{{', '}}', "\n"], $text);
        $text = str_replace(['<p>', '</p>'], '', $text);
        return $text;
    }

    /**
     * @param string $text
     * @param string $highlightClassName
     * @return string
     */
    public static function convertTextForDB($text, $highlightClassName) {
        if (!is_string($text)) $text = '';
        $text = str_replace(['{{', '}}'], ['<span class="' . $highlightClassName . '">', '</span>'], trim($text));
        $text = '<p>' . str_replace(["\r\n", "\n\r", "\n", "\r"], '</p><p>', $text) . '</p>';
        $text = str_replace('<p></p>', '', $text);
        return $text;
    }

    /**
     * @param string $filename
     */
    public static function deleteImages($filename) {
        if (file_exists($filename)) @unlink($filename);
        $parts = explode('.', $filename);
        if (count($parts) > 1) {
            $parts[count($parts) - 2] .= '@2x';
            $filename = implode('.', $parts);
            if (file_exists($filename)) @unlink($filename);
        }
    }

    public function getDiffMap(): array
    {
        $diff = [];
        foreach ($this->attributes as $name => $value) {
            if (!$this->isNewRecord && array_key_exists($name, $this->oldAttributes)) {
                $changed = ($value != $this->oldAttributes[$name]);
            } else {
                $changed = !empty($value);
            }
            if ($changed) {
                $diff[$name] = $value;
            }
        }
        return $diff;
    }
}

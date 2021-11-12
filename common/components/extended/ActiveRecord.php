<?php

namespace common\components\extended;

class ActiveRecord extends \yii\db\ActiveRecord
{
    public const STATUS_ACTIVE = 1;
    public const STATUS_INACTIVE = 0;

    public function moveErrorsToFlash(): void
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
     * @return array<string>
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

    public function getErrorsAsString(?string $field = null): string
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
     */
    public static function getListAsMap(array $list, string $fieldName = 'id'): array
    {
        $map = [];
        /** @var \yii\db\ActiveRecord $list */
        foreach ($list as $element) {
            if ($element->$fieldName !== null) $map[$element->$fieldName] = $element;
        }
        return $map;
    }

    public static function convertTextForEditor(string $text, string $highlightClassName): string
    {
        if (!is_string($text)) $text = '';
        $text = str_replace(['<span class="' . $highlightClassName . '">', '</span>', '</p><p>'], ['{{', '}}', "\n"], $text);
        $text = str_replace(['<p>', '</p>'], '', $text);
        return $text;
    }

    public static function convertTextForDB(string $text, string $highlightClassName): string
    {
        if (!is_string($text)) $text = '';
        $text = str_replace(['{{', '}}'], ['<span class="' . $highlightClassName . '">', '</span>'], trim($text));
        $text = '<p>' . str_replace(["\r\n", "\n\r", "\n", "\r"], '</p><p>', $text) . '</p>';
        $text = str_replace('<p></p>', '', $text);
        return $text;
    }

    public static function deleteImages(string $filename): void
    {
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

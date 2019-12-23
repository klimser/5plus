<?php

namespace common\components;

use Tinify\Source;
use yii\base\BaseObject;

class Tinifier extends BaseObject
{
    protected $apiKey;

    /**
     * @param mixed $apiKey
     */
    public function setApiKey($apiKey)
    {
        $this->apiKey = $apiKey;
    }

    public function init()
    {
        parent::init();

        if ($this->apiKey) \Tinify\setKey($this->apiKey);
    }

    /**
     * @param string $filePath
     * @return Source|bool
     */
    public function getFromFile(string $filePath)
    {
        if (!$this->apiKey || !is_file($filePath)) return false;
        return \Tinify\fromFile($filePath);
    }
}

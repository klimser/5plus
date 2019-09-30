<?php

namespace common\components;

use backend\models\EventMember;
use common\models\GroupPupil;
use yii\base\BaseObject;

class PyBot extends BaseObject
{
    /** @var string */
    protected $gatewayUrl;

    /**
     * @return string
     */
    public function getGatewayUrl(): string
    {
        return $this->gatewayUrl;
    }

    /**
     * @param string $gatewayUrl
     */
    public function setGatewayUrl(string $gatewayUrl)
    {
        $this->gatewayUrl = $gatewayUrl;
    }

    /**
     * Выполнить запрос
     * @param string $urlAddon
     * @param array $params
     * @return mixed
     */
    private function execute(string $urlAddon, array $params = [])
    {
        $curl = curl_init($this->gatewayUrl . $urlAddon);
        $postParams = http_build_query($params);

        curl_setopt_array($curl, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $postParams,
            CURLOPT_HEADER => false,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_RETURNTRANSFER => true,
        ]);

        try {
            $response = curl_exec($curl);
            curl_close($curl);
        } catch (\Throwable $ex) {
            if (is_resource($curl)) curl_close($curl);
            return null;
//            throw new \Exception($ex->getMessage(), $ex->getCode(), $ex);
        }

        return $response;
    }

    /**
     * @param EventMember $eventMember
     * @return mixed
     */
    public function attendance(EventMember $eventMember)
    {
        return $this->execute('/bot_info/attendance', [
            'phone' => $eventMember->groupPupil->user->phone,
            'group_id' => $eventMember->groupPupil->group_id,
        ]);
    }

    /**
     * @param EventMember $eventMember
     * @return mixed
     */
    public function mark(EventMember $eventMember)
    {
        return $this->execute('/bot_info/mark', [
            'phone' => $eventMember->groupPupil->user->phone,
            'group_id' => $eventMember->groupPupil->group_id,
            'mark' => $eventMember->mark,
        ]);
    }

    /**
     * @param GroupPupil $groupPupil
     * @return mixed
     */
    public function lowBalance(GroupPupil $groupPupil)
    {
        return $this->execute('/bot_info/low_balans', [
            'phone' => $groupPupil->user->phone,
            'group_id' => $groupPupil->group_id,
        ]);
    }
}

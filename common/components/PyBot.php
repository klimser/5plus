<?php

namespace common\components;

use backend\models\EventMember;
use common\models\BotQueue;
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
    
    private function queue(string $urlAddon, array $params = [], ?\DateTime $validUntil = null): bool
    {
        $entity = new BotQueue();
        $entity->path = $urlAddon;
        $entity->payload = $params ? json_encode($params, JSON_UNESCAPED_UNICODE) : null;
        $entity->valid_until = $validUntil ? $validUntil->format('Y-m-d H:i:s') : null;
        return $entity->save();
    }
    
    public function process(BotQueue $entity): bool
    {
        if ($entity->valid_until !== null && date_create($entity->valid_until) < date_create()) {
            return true;
        }
        
        $response = $this->execute($entity->path, $entity->payload ? json_decode($entity->payload, true) : []);
        if ($response === null) {
            return false;
        }
        
        // TODO check response value
        return true;
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
     * @return bool
     */
    public function attendance(EventMember $eventMember)
    {
        return $this->queue(
            '/bot_info/attendance',
            [
                'phone' => $eventMember->groupPupil->user->phone,
                'group_id' => $eventMember->groupPupil->group_id,
                'status' => $eventMember->status === EventMember::STATUS_ATTEND ? 1 : 0,
            ],
            new \DateTime('+3 hour')
        );
    }

    /**
     * @param EventMember $eventMember
     * @return bool
     */
    public function mark(EventMember $eventMember)
    {
        return $this->queue(
            '/bot_info/mark', 
            [
                'phone' => $eventMember->groupPupil->user->phone,
                'group_id' => $eventMember->groupPupil->group_id,
                'mark' => $eventMember->mark,
            ],
            new \DateTime('+3 hour')
        );
    }

    /**
     * @param GroupPupil $groupPupil
     * @return bool
     */
    public function lowBalance(GroupPupil $groupPupil)
    {
        return $this->queue(
            '/bot_info/low_balans', 
            [
                'phone' => $groupPupil->user->phone,
                'group_id' => $groupPupil->group_id,
            ],
            new \DateTime('+3 hour')
        );
    }
}

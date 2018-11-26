<?php

namespace common\components;

use common\models\Contract;

class ContractComponent
{
    public static function generateContractNumber(Contract $contract)
    {
        if (!$contract->user_id) throw new \Exception('Assign user to contract first!');

        $numberPrefix = $contract->createDate->format('Ymd') . $contract->user_id;
        $numberAffix = 1;
        while (Contract::find()->andWhere(['number' => $numberPrefix . $numberAffix])->select('COUNT(id)')->scalar() > 0) {
            $numberAffix++;
        }
        $contract->number = $numberPrefix . $numberAffix;

        return $contract;
    }
}
<?php

namespace common\components;

use common\models\Group;
use common\models\GroupParam;
use DateTime;
use InvalidArgumentException;
use yii\base\BaseObject;
use yii\caching\CacheInterface;

class GroupParamComponent extends BaseObject
{
    const CACHE_KEY = 'group_param';
    
    /** @var CacheInterface */
    private $cache;

    /**
     * GroupParamComponent constructor.
     * @param CacheInterface $cache
     * @param array $config
     */
    public function __construct(CacheInterface $cache, $config = [])
    {
        $this->cache = $cache;
        parent::__construct($config);
    }

    /**
     * @param Group $group
     * @return string
     */
    protected function getCacheKeyByGroup(Group $group): string
    {
        return self::CACHE_KEY . ':' . $group->id;
    }

    /**
     * @param Group $group
     * @return GroupParam[]
     */
    public function getByGroup(Group $group): array 
    {
        if ($group->isNewRecord) {
            throw new InvalidArgumentException('Cannot get parameters for new group');
        }
        
        return $this->cache->getOrSet($this->getCacheKeyByGroup($group), function() use ($group) {
            return $groupParams = GroupParam::find()
                ->andWhere(['group_id' => $group->id])
                ->addOrderBy(['valid_from' => SORT_ASC])
                ->all();
        });
    }

    /**
     * @param Group $group
     * @param DateTime $date
     * @return GroupParam|null
     * @throws InvalidArgumentException
     */
    public function getByGroupAndDate(Group $group, DateTime $date): ?GroupParam
    {
        if ($group->isNewRecord) {
            throw new InvalidArgumentException('Cannot get parameters for new group');
        }

        $groupParams = $this->getByGroup($group);
        
        $return = null;
        foreach ($groupParams as $groupParam) {
            if ($date >= $groupParam->validFromDateTime) {
                $return = $groupParam;
            } else {
                break;
            }
        }
        return $return;
    }
}

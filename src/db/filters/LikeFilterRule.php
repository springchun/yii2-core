<?php

namespace springchun\yii2\core\db\filters;

use springchun\yii2\core\db\ActiveQuery;

/**
 * Class LikeFilterRule
 * @package springchun\yii2\core\db\filters
 */
class LikeFilterRule extends BaseFilterRule
{
    /**
     * @param ActiveQuery $query
     * @param string      $attribute
     * @param mixed       $value
     * @return mixed|void
     */
    public function execute(ActiveQuery $query, $attribute, $value)
    {
        $conditions = ['or'];
        foreach ((array)$attribute as $attribute) {
            $conditions[] = [
                'like', $query->quoteColumn($attribute), $value
            ];
        }
        $query->andWhere($conditions);
    }
}

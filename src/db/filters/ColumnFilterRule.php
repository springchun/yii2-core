<?php

namespace springchun\yii2\core\db\filters;

use springchun\yii2\core\db\ActiveQuery;
use function is_array;

/**
 * Class ColumnFilterRule
 * @package app\db\filters
 */
class ColumnFilterRule extends BaseFilterRule
{
    public function execute(ActiveQuery $query, $attribute, $value)
    {
        $query->andWhere([
            is_array($value) ? 'in' : '=', $query->quoteColumn($attribute), $value
        ]);
    }
}

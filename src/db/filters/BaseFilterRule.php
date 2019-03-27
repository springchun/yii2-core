<?php

namespace springchun\yii2\core\db\filters;

use springchun\yii2\core\db\ActiveQuery;
use yii\base\BaseObject;

/**
 * Class BaseFilterRule
 * @package springchun\yii2\core\db\filters
 */
abstract class  BaseFilterRule extends BaseObject
{
    public $attributes = [];
    public $targetAttribute;

    /**
     * @param ActiveQuery $query
     * @param string      $attribute
     * @param mixed       $value
     * @return mixed
     */
    abstract public function execute(ActiveQuery $query, $attribute, $value);
}

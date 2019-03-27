<?php
namespace springchun\yii2\core\db\filters;
use function call_user_func;
use springchun\yii2\core\db\ActiveQuery;

/**
 * Class ClosureFilterRule
 * @package springchun\yii2\core\db\filters
 */
class ClosureFilterRule extends BaseFilterRule
{
    /** @var callable */
    public $closure;
    public function execute(ActiveQuery $query, $attribute, $value)
    {
        call_user_func($this->closure,$query,$attribute,$value);
    }
}

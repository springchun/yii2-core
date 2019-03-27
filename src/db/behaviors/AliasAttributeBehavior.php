<?php
namespace springchun\yii2\core\db\behaviors;
use function array_keys;
use function in_array;
use yii\base\Behavior;
use yii\helpers\ArrayHelper;

/**
 * 设置别名Attribute
 * Class AliasAttributeBehavior
 * @package springchun\yii2\core\db\behaviors
 */
class AliasAttributeBehavior extends Behavior
{
    /**
     * @var array
     */
    public $attributes = [];

    /**
     * @param string $name
     * @param bool   $checkVars
     * @return bool
     */
    public function canGetProperty($name, $checkVars = true)
    {
        return $this->_hasAttribute($name);
    }

    /**
     * @param string $name
     * @return mixed
     */
    public function __get($name)
    {
        return ArrayHelper::getValue($this->owner,$this->attributes[$name]);
    }

    /**
     * @return array
     */
    public function aliasAttributes()
    {
        return array_keys($this->attributes);
    }


    /**
     * @param $name
     * @return bool
     */
    private function _hasAttribute($name)
    {
        return in_array($name,$this->attributes);
    }
}

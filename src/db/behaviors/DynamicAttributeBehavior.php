<?php
namespace springchun\yii2\core\db\behaviors;
use function array_keys;
use function in_array;
use yii\base\Behavior;
use yii\db\ActiveRecord;

/**
 * 动态字段
 * Class DynamicAttributeBehavior
 * @package springchun\yii2\core\db\behaviors
 * @property ActiveRecord $owner
 */
class DynamicAttributeBehavior extends Behavior
{
    /**
     * @var array
     */
    public $attributes = [];
    /**
     * @var array
     */
    private $_values = [];

    /**
     * @param \yii\base\Component $owner
     */
    public function attach($owner)
    {
        parent::attach($owner);
        if(empty($this->attributes)){
            $this->attributes = array_keys($this->owner->attributeLabels());
        }
    }

    /**
     * @param string $name
     * @param bool   $checkVars
     * @return bool
     */
    public function canSetProperty($name, $checkVars = true)
    {
        return $this->_hasAttribute($name);
    }

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
     * @param mixed  $value
     */
    public function __set($name, $value)
    {
        $this->_values[$name] = $value;
    }

    /**
     * @param string $name
     * @return mixed|null
     */
    public function __get($name)
    {
        return isset($this->_values[$name])?$this->_values[$name]:null;
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

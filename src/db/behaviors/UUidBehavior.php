<?php
namespace springchun\yii2\core\db\behaviors;
use springchun\yii2\core\snowflake\SnowFlake;
use yii\base\Behavior;
use yii\db\ActiveRecord;

/**
 * Class UUidBehavior
 * @package springchun\yii2\core\db\behaviors
 * @property ActiveRecord $owner
 */
class UUidBehavior extends Behavior
{
    public $attribute = 'id';
    public function events()
    {
        return [
            ActiveRecord::EVENT_BEFORE_INSERT => 'beforeSave',
            ActiveRecord::EVENT_BEFORE_UPDATE => 'beforeSave',
        ];
    }

    /**
     * @throws \yii\base\InvalidConfigException
     */
    public function beforeSave()
    {
        if (!$this->owner->{$this->attribute}) {
            $class = $this->owner;
            $this->owner->{$this->attribute} = SnowFlake::getId(
                $class::getTableSchema()->getColumn($this->attribute)->type === 'string'
            );
        }
    }
}

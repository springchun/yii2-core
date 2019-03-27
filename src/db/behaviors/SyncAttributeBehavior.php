<?php
namespace springchun\yii2\core\db\behaviors;
use springchun\yii2\core\db\ActiveRecord;
use yii\base\Behavior;
use yii\helpers\ArrayHelper;

/**
 * Class SyncAttributeBehaviore
 * @package springchun\yii2\core\db\behaviors
 */
class SyncAttributeBehaviore extends Behavior
{
    /**
     * @var array 需要同步的Attribute
     */
    public $syncAttributes = [];

    /**
     * @return array
     */
    public function events()
    {
        return [
            ActiveRecord::EVENT_BEFORE_VALIDATE=>'beforeValidate'
        ];
    }

    public function beforeValidate()
    {
        foreach ($this->syncAttributes as $syncAttribute=>$targetAttribute){
            $this->owner->$syncAttribute = ArrayHelper::getValue($this->owner,$targetAttribute);
        }
    }
}

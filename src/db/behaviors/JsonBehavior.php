<?php
namespace springchun\yii2\core\db\behaviors;
use function floatval;
use function is_string;
use function json_decode;
use yii\base\Behavior;
use yii\db\ActiveRecord;

/**
 * Class JsonBehavior
 * @package springchun\yii2\core\db\behaviors
 * @property ActiveRecord $owner
 */
class JsonBehavior extends Behavior
{
    /**
     * @var array
     */
    public $attributes = [];

    /**
     * @return bool
     */
    public function getIsSupportJson()
    {
        return floatval($this->owner->db->getServerVersion())>=5.7;
    }
    /**
     * @return array
     */
    public function events()
    {
        return [
            ActiveRecord::EVENT_BEFORE_INSERT => 'beforeSave',
            ActiveRecord::EVENT_BEFORE_UPDATE => 'beforeSave',
            ActiveRecord::EVENT_AFTER_INSERT => 'afterSave',
            ActiveRecord::EVENT_AFTER_UPDATE => 'afterSave',
            ActiveRecord::EVENT_AFTER_FIND => 'decode'
        ];
    }

    /**
     *
     */
    public function beforeSave()
    {
        if(!$this->getIsSupportJson()){
            $this->encode();
        }
    }

    /**
     *
     */
    public function afterSave()
    {
        if(!$this->getIsSupportJson()){
            $this->decode();
        }
    }

    /**
     *
     */
    public function decode()
    {
        foreach ($this->attributes as $attribute){
            if(!$this->owner->$attribute !== null &&is_string($this->owner->$attribute)){
                $this->owner->$attribute = @json_decode($this->owner->$attribute,true);
            }
        }
    }

    /**
     *
     */
    public function encode()
    {
        foreach ($this->attributes as $attribute) {
            if ($this->owner->$attribute !== null) {
                $this->owner->$attribute = @json_encode($this->owner->$attribute,JSON_UNESCAPED_UNICODE);
            }
        }
    }
}

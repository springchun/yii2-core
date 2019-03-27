<?php

namespace springchun\yii2\core\db\behaviors;

use yii\base\Behavior;
use yii\base\InvalidCallException;
use yii\base\InvalidConfigException;
use yii\db\ActiveRecord;

/**
 * Class RelationBehavior
 * @package hgwl\models\behaviors
 */
class RelationBehavior extends Behavior
{
    /** @var  ActiveRecord */
    public $owner;
    public $relations = [];
    private $_values = [];

    /**
     * @return array
     */
    public function events()
    {
        return [
            ActiveRecord::EVENT_AFTER_VALIDATE => 'afterValidate',
            ActiveRecord::EVENT_AFTER_INSERT => 'afterSave',
            ActiveRecord::EVENT_AFTER_UPDATE => 'afterSave'
        ];
    }

    /**
     * @param string $name
     * @param bool   $checkVars
     * @return bool
     */
    public function canGetProperty($name, $checkVars = true)
    {
        if ($this->hasRelation($name)) {
            return true;
        } else {
            return parent::canSetProperty($name, $checkVars);
        }
    }

    /**
     * @param string $name
     * @return mixed
     * @throws \yii\base\UnknownPropertyException
     */
    public function __get($name)
    {
        if ($this->hasRelation($name)) {
            if (!isset($this->_values[$name])) {
                return $this->owner->{$this->relations[$name]};
            } else {
                return $this->_values[$name];
            }
        } else {
            return parent::__get($name);
        }
    }

    /**
     * @param string $name
     * @param mixed  $value
     * @throws InvalidConfigException
     * @throws \yii\base\UnknownPropertyException
     */
    public function __set($name, $value)
    {
        if ($this->hasRelation($name)) {
            list($class, $multiple, $relationField, $sourceField) = $this->getRelation($name);
            if (!$multiple) {
                if ($value instanceof $class) {
                    $newModel = $value;
                } else {
                    $newModel = new $class([
                        $relationField => $this->owner->$sourceField
                    ]);
                    if (is_array($value)) {
                        $newModel->load($value, '');
                    }
                }
                $this->_values[$name] = $newModel;
            } else {
                /** @var ActiveRecord[] $models */
                $oldModels = $this->owner->$name;
                $newModels = [];
                foreach ($value as $k => $v) {
                    if ($v instanceof $class) {
                        $newModels[$k] = $v;
                    } else {
                        if (isset($oldModels[$k])) {
                            $newModels[$k] = $oldModels[$k];
                        } else {
                            $newModels[$k] = new $class([
                                $relationField => $this->owner->$sourceField
                            ]);
                        }
                        if (is_array($v)) {
                            $newModels[$k]->load($v, '');
                        }
                    }
                }
                $this->_values[$name] = $newModels;
            }
        } else {
            return parent::__set($name, $value);
        }
    }

    /**
     * @param string $name
     * @param bool   $checkVars
     * @return bool
     */
    public function canSetProperty($name, $checkVars = true)
    {
        if ($this->hasRelation($name)) {
            return true;
        } else {
            return parent::canSetProperty($name, $checkVars);
        }
    }

    /**
     * @throws InvalidConfigException
     */
    public function afterValidate()
    {
        foreach (array_keys($this->_values) as $name) {
            list($class, $multiple, $relationField, $sourceField) = $this->getRelation($name);
            /** @var ActiveRecord $model */
            $model = new $class;
            $attributes = $model->attributes();
            if (($key = array_search($relationField, $attributes)) !== false) {
                unset($attributes[$key]);
            }
            $models = $multiple ? $this->owner->$name : [$this->owner->$name];
            if (!ActiveRecord::validateMultiple($models, $attributes)) {
                foreach ($models as $model) {
                    foreach ($model->firstErrors as $error) {
                        $this->owner->addError($name, $error);
                    }
                }
            }
        }
    }

    /**
     * @throws InvalidConfigException
     */
    public function afterSave()
    {
        foreach (array_keys($this->_values) as $name) {
            list($class, $multiple, $relationField, $sourceField) = $this->getRelation($name);
            /** @var ActiveRecord[] $models */
            $models = $multiple ? $this->owner->$name : [$this->owner->$name];
            foreach ($models as $model) {
                if ($model->getIsNewRecord()) {
                    $model->$relationField = $this->owner->$sourceField;
                }
                $model->scenario = $this->owner->scenario;
                if (!$model->save()) {
                    throw new InvalidConfigException(implode("", $model->firstErrors));
                }
            }
            $this->_values[$name] = $this->owner->$name;
        }
    }

    /**
     * @param      $name
     * @param null $data
     * @param null $formName
     * @return bool
     * @throws InvalidConfigException
     * @throws \yii\base\UnknownPropertyException
     */
    public function loadRelation($name, $data = null, $formName = null)
    {
        if (!$this->hasRelation($name)) {
            return false;
        } else {
            if ($data === null) {
                $data = \Yii::$app->request->post();
            }
            if ($formName === null) {
                /** @var ActiveRecord $model */
                list($class) = $this->getRelation($name);
                $model = new $class;
                $formName = $model->formName();
            }
            if ($formName === '') {
                throw new InvalidCallException("表单项不能为空");
            } else {
                if (!empty($data[$formName])) {
                    $this->__set($name, $data[$formName]);
                    return true;
                }
            }
            return false;
        }
    }

    /**
     * @param $attribute
     * @return bool
     */
    protected function hasRelation($attribute)
    {
        return isset($this->relations[$attribute]);
    }

    /**
     * @param      $attribute
     * @param bool $throwException
     * @return array|null
     * @throws InvalidConfigException
     */
    protected function getRelation($attribute, $throwException = true)
    {
        $query = $this->owner->getRelation($this->relations[$attribute]);
        if (count($query->link) !== 1) {
            if ($throwException) {
                throw new InvalidConfigException("不支持复合主键");
            } else {
                return null;
            }
        } else if ($query->via) {
            if ($throwException) {
                throw new InvalidConfigException("不支持中间表");
            } else {
                return null;
            }
        } else {
            $link = $query->link;
            return [$query->modelClass, $query->multiple, key($link), reset($link)];
        }
    }
}

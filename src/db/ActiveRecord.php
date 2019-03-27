<?php

namespace springchun\yii2\core\db;

use springchun\yii2\core\base\ModelException;
use springchun\yii2\core\base\ModelTrait;
use Yii;
use yii\base\InvalidArgumentException;
use yii\mutex\MysqlMutex;

/**
 * Class ActiveRecord
 * @package springchun\yii2\core\db
 */
class ActiveRecord extends \yii\db\ActiveRecord
{
    const OPTIMISTICLOCK_ATTRIBUTE = 'optimistic_lock';
    const CREATE_ATTRIBUTES = ['add_time', 'create_time'];
    const UPDATE_ATTRIBUTES = ['update_time'];
    use ModelTrait;
    /** @var array[] */
    private static $_attributeLabels = [];

    /**
     * @return object|ActiveQuery|\yii\db\ActiveQuery
     * @throws \yii\base\InvalidConfigException
     */
    public static function find()
    {
        return Yii::createObject(ActiveQuery::class, static::class);
    }

    /**
     * 全局过滤器
     * @return array
     * @throws \yii\base\InvalidConfigException
     */
    public static function filters()
    {
        return [
            [static::getTableSchema()->columnNames, ColumnFilterRule::class]
        ];
    }

    /**
     * 得到表名
     * @return string
     */
    public static function tableName()
    {
        static $tableNames = [];
        $class = static::class;
        if (!isset($tableNames[$class])) {
            list(, $table) = explode('models\\', get_called_class());
            $table = preg_replace_callback('#(_?)([A-Z])#', function ($args) {
                return '_' . strtolower($args[2]);
            }, strtr(lcfirst($table), ['\\' => '_']));
            $tableNames[$class] = '{{%' . $table . '}}';
        }
        return $tableNames[$class];
    }

    /**
     * @return array
     */
    public function scenarios()
    {
        $scenarios = parent::scenarios();
        if (!isset($scenarios[$this->scenario])) {
            $scenarios[$this->scenario] = $scenarios[self::SCENARIO_DEFAULT];
        }
        return $scenarios;
    }

    /**
     * @return array
     */
    public function denyFields()
    {
        return [
            self::OPTIMISTICLOCK_ATTRIBUTE
        ];
    }

    /**
     * @return array
     * @throws \yii\base\InvalidConfigException
     */
    public function attributeLabels()
    {
        $class = static::class;
        if (!isset(self::$_attributeLabels[$class])) {
            self::$_attributeLabels[$class] = [
                'id' => '编号',
                'add_time' => '添加时间',
                'create_time' => '添加时间',
                'update_time' => '更新时间'
            ];
            foreach (static::getTableSchema()->columns as $column) {
                if (!empty($column->comment)) {
                    self::$_attributeLabels[$class][$column->name] = $column->comment;
                }
            }
        }
        return self::$_attributeLabels[$class];
    }

    /**
     * 查询attributes
     * @param null $query
     * @return array
     */
    public function queryAttribute($query = null)
    {
        if (in_array($query, [null, '*'])) {
            return $this->attributes();
        } else if (is_string($query)) {
            $result = [];
            $attributes = $this->attributes();
            foreach (explode(',', $query) as $query) {
                if (strpos($query, '*') !== false) {
                    $regexp = '#^' . strtr($query, ['*' => '.*']) . '$#';
                    foreach ($attributes as $attribute) {
                        if (preg_match($regexp, $attribute)) {
                            $result[] = $attribute;
                        }
                    }
                } else if (strpos($query, '-')) {
                    $tmp = explode('-', $query);
                    if (($start = array_search($tmp[0], $attributes)) !== false && ($end = array_search($tmp[1], $attributes)) !== false) {
                        $result = array_merge($result, array_slice($attributes, $start, $end - $start + 1));
                    } else {
                        throw new InvalidArgumentException("无效的查询参数 $query");
                    }
                } else {
                    $result[] = $query;
                }
            }
            return array_unique($result);
        }
    }

    /**
     * @param $fn
     * @return bool|mixed
     * @throws \Throwable
     */
    public function trySave($fn)
    {
        try {
            if ($this->optimisticLock()) {
                return static::getDb()->transaction($fn);
            } else {
                return call_user_func($fn);
            }
        } catch (ModelException $exception) {
            return false;
        }
    }

    /**
     * 设置并保存变量
     * @param      $attributes
     * @param bool $runValidation
     * @return bool
     */
    public function setAndSave($attributes, $runValidation = true)
    {
        $oldAttributes = [];
        foreach ($attributes as $attribute => $value) {
            $oldAttributes[$attributes] = $this->getAttribute($attribute);
        }
        $this->setAttributes($attributes, false);
        if ($this->save($runValidation)) {
            return false;
        } else {
            $this->setAttributes($oldAttributes, false);
            return false;
        }
    }

    /**
     * @param bool $insert
     * @return bool
     */
    public function beforeSave($insert)
    {
        $attributes = $this->attributes();
        if ($insert) {
            foreach (self::CREATE_ATTRIBUTES as $attribute) {
                if (!in_array($attribute, $attributes) && !$this->$attribute) {
                    $this->$attribute = time();
                }
            }
        }
        foreach (self::UPDATE_ATTRIBUTES as $attribute) {
            if (!in_array($attribute, $attributes) && !$this->$attribute) {
                $this->$attribute = time();
            }
        }
        return parent::beforeSave($insert);
    }

    /**
     * @param $fn
     * @return mixed
     * @throws ModelException
     * @throws \yii\base\InvalidConfigException
     */
    public function mutex($fn)
    {
        $mutex = Yii::createObject([
            'class' => MysqlMutex::class,
            'db' => static::getDb()
        ]);
        if (!$mutex->acquire(static::class, 10)) {
            throw ModelException::createException($this);
        }
        $result = call_user_func($fn);
        $mutex->release(static::class);
        return $result;
    }

    /**
     * @return array
     * @throws \yii\base\InvalidConfigException
     */
    public function transactions()
    {
        if ($this->optimisticLock() !== null) {
            return [
                $this->scenario = self::OP_ALL
            ];
        } else {
            return parent::transactions();
        }
    }

    /**
     * 得到乐观锁字段名称
     * @return string|null
     * @throws \yii\base\InvalidConfigException
     */
    public function optimisticLock()
    {
        if (in_array(self::OPTIMISTICLOCK_ATTRIBUTE, $this->attributes())) {
            if ($this->{self::OPTIMISTICLOCK_ATTRIBUTE} === null) {
                $this->{self::OPTIMISTICLOCK_ATTRIBUTE} = static::getTableSchema()
                    ->getColumn(self::OPTIMISTICLOCK_ATTRIBUTE)
                    ->defaultValue;
            }
            return self::OPTIMISTICLOCK_ATTRIBUTE;
        } else {
            return null;
        }
    }
}

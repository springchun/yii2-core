<?php

namespace springchun\yii2\core\base;

use function is_int;
use ReflectionClass;
use function array_merge;
use function lcfirst;
use function strpos;

trait ModelTrait
{
    /** @var array[] */
    private static $_extraFields = [];

    /**
     * 返回表单名称
     * @return string
     */
    public function formName()
    {
        return '';
    }

    /**
     * 读取数据
     * @param null $data
     * @param null $formName
     * @return bool
     * @throws \yii\base\InvalidConfigException
     */
    public function load($data = null, $formName = null)
    {
        if ($data === null) {
            $data = array_merge(
                \Yii::$app->request->getQueryParams(),
                \Yii::$app->request->getBodyParams()
            );
        }
        return parent::load($data, $formName);
    }


    /**
     * @return array
     * @throws \ReflectionException
     */
    public function extraFields()
    {
        $class = static::class;
        if (!isset(self::$_extraFields[$class])) {
            $refClass = new ReflectionClass($this);
            foreach ($refClass->getMethods() as $method) {
                if (strpos($method->name, 'get') === 0
                    && $method->getNumberOfRequiredParameters() === 0) {
                    self::$_extraFields[$class][] = lcfirst(substr($method->name, 3));
                }
            }
        }
        return self::$_extraFields[$class];
    }

    /**
     * @return array
     */
    public function denyFields()
    {
        return [];
    }

    /**
     * @param array $fields
     * @param array $expand
     * @return array
     */
    protected function resolveFields(array $fields, array $expand)
    {
        if (!$denyFields = $this->denyFields()) {
            return parent::resolveFields($fields, $expand);
        } else {
            $fields = parent::resolveFields($fields, $expand);
            foreach ($denyFields as $key => $field) {
                if (is_int($key)) {
                    if (isset($fields[$field])) {
                        unset($fields[$field]);
                    }
                } else {
                    if ($field) {
                        unset($fields[$field]);
                    }
                }
            }
            return $fields;
        }
    }
}

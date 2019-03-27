<?php
namespace springchun\yii2\core\base;
use yii\base\Exception;
use yii\base\Model;

/**
 * Class ModelException
 * @package springchun\yii2\core\base
 */
class ModelException extends Exception
{
    /** @var Model */
    public $model;

    /**
     * @param Model $model
     * @return ModelException
     */
    public static function createException(Model $model)
    {
        $exception = new static();
        $exception->model = $model;
        return $exception;
    }
}

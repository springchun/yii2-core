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
}

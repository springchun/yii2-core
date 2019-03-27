<?php

namespace springchun\yii2\core\api;

use Exception;
use yii\rest\Serializer;

/**
 * Class ApiFormatter
 * @package springchun\yii2\core\api
 */
class JsonResponseFormatter extends \yii\web\JsonResponseFormatter
{
    /**
     * @param \yii\web\Response $response
     */
    protected function formatJson($response)
    {
        if (\Yii::$app->errorHandler->exception instanceof Exception) {
            $response->data = \Yii::$app->errorHandler->exception;
        }
        if ($response->data) {
            if ($response->data instanceof Exception) {
                $response->setStatusCodeByException($response->data);
                $response->data = [
                    'code' => $response->data->getCode(),
                    'message' => $response->data->getMessage(),
                    'data' => null
                ];
            } else {
                $data = (new Serializer())->serialize($response->data);
                if ($response->isSuccessful) {
                    $response->data = [
                        'code' => 0,
                        'message' => null,
                        'data' => $data
                    ];
                } else {
                    $response->data = [
                        'code' => 1,
                        'data' => null,
                        'message' => $response->statusText
                    ];
                }
            }
        }
        parent::formatJson($response);
    }
}

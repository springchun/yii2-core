<?php

namespace springchun\yii2\core\db;

use function implode;
use springchun\yii2\core\db\filters\BaseFilterRule;
use springchun\yii2\core\db\filters\ClosureFilterRule;
use yii\helpers\ArrayHelper;
use yii\base\InvalidCallException;

class ActiveQuery extends \yii\db\ActiveQuery
{
    /**
     * 得到主表的别名
     * @return array
     */
    public function getTableNameAndAlias()
    {
        return parent::getTableNameAndAlias();
    }

    /**
     * 得到关系的表名
     * @param null $relation
     * @return mixed
     * @throws \yii\base\InvalidConfigException
     */
    public function getRelationTableName($relation = null)
    {
        /** @var ActiveQuery $query */
        $query = $this;
        while (!empty($relation)) {
            list($prefix, $relation) = explode(".", $relation, 2);
            $query = $query->$prefix;
        }
        return array_keys($query->getTablesUsedInFrom())[0];
    }

    /**
     * @param $column
     * @return string
     */
    public function quoteColumn($column)
    {
        return implode(".", [
            $this->getPrimaryTableName(),
            $column
        ]);
    }

    /**
     * @param null $params
     * @param null $relation
     * @return ActiveQuery
     * @throws \yii\base\InvalidConfigException
     */
    public function search($params = null, $relation = null)
    {
        if (!empty($relation)) {
            return $this->joinWith([$relation => function ($query) use ($params) {
                $query->search($params);
            }], false);
        }
        if (empty($params)) {
            $params = \Yii::$app->request->getQueryParams();
        }
        $filters = $this->getFilters();
        $filterParams = [];
        foreach ($params as $key => $value) {
            if ($value !== null && $value !== '') {
                if (isset($filters[$key])) {
                    $filterParams[$key] = $value;
                }
            }
        }
        return $this->filter($filterParams);
    }


    /**
     * @param null $params
     * @param null $relation
     * @return $this|ActiveQuery
     * @throws \yii\base\InvalidConfigException
     */
    public function filter($params = null, $relation = null)
    {
        if(!empty($relation)){
            return $this->joinWith([$relation=>function(ActiveQuery $query)use($params){
                $query->filter($params);
            }],false);
        }
        $filters = $this->getFilters();
        foreach ($params as $key=>$value){
            if(!isset($filters[$key])){
                throw new InvalidCallException("无效的过滤器 $key");
            }else{
                $filter = $filters[$key];
                $filter->execute($this, $filter->targetAttribute ?: $key, $value);
            }
        }
        return $this;
    }

    /**
     * @return mixed
     * @throws \yii\base\InvalidConfigException
     */
    public function getFilters()
    {
        static $filters = [];
        /** @var ActiveRecord|string $modelClass */
        $modelClass = $this->modelClass;
        if (!isset($filters[$modelClass])) {
            $filters[$modelClass] = [];
            foreach ($modelClass::filters() as $key => $filter) {
                if ($filter instanceof \Closure) {
                    $filter = [[$key], $filter];
                }
                if (!is_object($filter)) {
                    if (isset($filter[0])) {
                        $filter['attributes'] = (array)ArrayHelper::remove($filter, '0');
                        $filter['class'] = ArrayHelper::remove($filter, '1');
                        if ($filter['class'] instanceof \Closure) {
                            $filter['closure'] = $filter['class'];
                            $filter['class'] = ClosureFilterRule::class;
                        }
                    }
                    /** @var BaseFilterRule $filter */
                    $filter = \Yii::createObject($filter);
                    foreach ($filter->attributes as $attribute) {
                        $filters[$modelClass][$attribute] = $filter;
                    }
                }
            }
        }
        return $filters[$modelClass];
    }
}

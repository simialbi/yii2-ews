<?php
/**
 * @package yii2-ews
 * @author Simon Karlen <simi.albi@gmail.com>
 */

namespace simialbi\yii2\ews\conditions;


use jamesiarmes\PhpEws\Type\AndType;
use jamesiarmes\PhpEws\Type\OrType;
use Yii;
use yii\db\ExpressionInterface;
use yii\helpers\StringHelper;

class ConjunctionConditionBuilder extends \yii\db\conditions\ConjunctionConditionBuilder
{
    /**
     * @var \simialbi\yii2\ews\QueryBuilder
     */
    protected $queryBuilder;

    /**
     * {@inheritDoc}
     * @return object|object[]
     * @throws \yii\base\InvalidConfigException
     */
    public function build(ExpressionInterface $condition, array &$params = []): object|array
    {
        /** @var \yii\db\conditions\ConjunctionCondition $condition */
        $expressions = $condition->getExpressions();
        $config = [];
        $class = ($condition->getOperator() === 'OR') ? OrType::class : AndType::class;
        foreach ($expressions as $expression) {
            $built = $this->queryBuilder->buildCondition($expression, $params);

            if (!is_object($built)) {
                continue;
            }
            // Strip "Type"
            $config[substr(StringHelper::basename(get_class($built)), 0, -4)][] = $built;
        }

        foreach ($config as $key => $value) {
            if (count($value) === 1) {
                $config[$key] = $value[0];
                continue;
            }
            $config[$class] = $this->queryBuilder->buildCondition([$condition->getOperator(), $value], $params);
        }

        $config['class'] = $class;

        return Yii::createObject($config);
    }
}

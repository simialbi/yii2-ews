<?php
/**
 * @package yii2-ews
 * @author Simon Karlen <simi.albi@gmail.com>
 */

namespace simialbi\yii2\ews\conditions;


use jamesiarmes\PhpEws\Type\AndType;
use jamesiarmes\PhpEws\Type\OrType;
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
     * @return array
     */
    public function build(ExpressionInterface $condition, array &$params = [])
    {
        /** @var \yii\db\conditions\ConjunctionCondition $condition */
        $expressions = $condition->getExpressions();
        $config = [];
        $class = ($condition->getOperator() === 'OR') ? OrType::class : AndType::class;
        foreach ($expressions as $expression) {
            $built = $this->queryBuilder->buildExpression($expression, $params);
            if (!isset($built['class'])) {
                continue;
            }
            // Strip "Type"
            $config[substr(StringHelper::basename($built['class']), -4)][] = $built;
        }

        foreach ($config as $key => $value) {
            if (count($value) === 1) {
                $config[$key] = $value;
                continue;
            }
            $config[$class] = $this->queryBuilder->buildCondition([$condition->getOperator(), $value], $params);
        }

        $config['class'] = $class;

        return $config;
    }
}

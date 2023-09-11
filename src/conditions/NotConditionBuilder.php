<?php
/**
 * @package yii2-ews
 * @author Simon Karlen <simi.albi@gmail.com>
 */

namespace simialbi\yii2\ews\conditions;

use jamesiarmes\PhpEws\Type\NotType;
use Yii;
use yii\db\ExpressionInterface;
use yii\helpers\StringHelper;

class NotConditionBuilder extends \yii\db\conditions\NotConditionBuilder
{
    /**
     * @var \simialbi\yii2\ews\QueryBuilder
     */
    protected $queryBuilder;

    /**
     * {@inheritDoc}
     * @return object|array
     * @throws \yii\base\InvalidConfigException
     */
    public function build(ExpressionInterface $expression, array &$params = []): object|array
    {
        /** @var \yii\db\conditions\NotCondition $expression */
        $operand = $expression->getCondition();
        if ($operand === '') {
            return [];
        }

        $config = ['class' => NotType::class];

        $built = $this->queryBuilder->buildCondition($operand, $params);
        if (!isset($built['class'])) {
            return [];
        }
        // Strip "Type"
        $config[substr(StringHelper::basename(get_class($built)), 0, -4)] = $built;

        return Yii::createObject($config);
    }
}

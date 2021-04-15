<?php
/**
 * @package yii2-ews
 * @author Simon Karlen <simi.albi@gmail.com>
 */

namespace simialbi\yii2\ews\conditions;


use jamesiarmes\PhpEws\Enumeration\ContainmentComparisonType;
use jamesiarmes\PhpEws\Enumeration\ContainmentModeType;
use jamesiarmes\PhpEws\Type\ConstantValueType;
use jamesiarmes\PhpEws\Type\ContainsExpressionType;
use jamesiarmes\PhpEws\Type\PathToUnindexedFieldType;
use yii\db\ExpressionInterface;

class LikeConditionBuilder extends \yii\db\conditions\LikeConditionBuilder
{
    /**
     * @var \simialbi\yii2\ews\QueryBuilder
     */
    protected $queryBuilder;

    /**
     * {@inheritDoc}
     * @return array
     */
    public function build(ExpressionInterface $expression, array &$params = [])
    {
        /** @var \yii\db\conditions\LikeCondition $expression */
        return [
            'class' => ContainsExpressionType::class,
            'ContainmentComparison' => ContainmentComparisonType::IGNORE_CASE_AND_NON_SPACING_CHARS,
            'ContainmentMode' => ContainmentModeType::SUBSTRING,
            'FieldURI' => [
                'class' => PathToUnindexedFieldType::class,
                'FieldURI' => $this->queryBuilder->getUriFromProperty($expression->getColumn())
            ],
            'Constant' => [
                'class' => ConstantValueType::class,
                'Value' => $expression->getValue()
            ]
        ];
    }
}

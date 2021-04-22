<?php
/**
 * @package yii2-ews
 * @author Simon Karlen <simi.albi@gmail.com>
 */

namespace simialbi\yii2\ews\conditions;


use jamesiarmes\PhpEws\Type\ConstantValueType;
use jamesiarmes\PhpEws\Type\FieldURIOrConstantType;
use jamesiarmes\PhpEws\Type\IsEqualToType;
use jamesiarmes\PhpEws\Type\IsGreaterThanOrEqualToType;
use jamesiarmes\PhpEws\Type\IsGreaterThanType;
use jamesiarmes\PhpEws\Type\IsLessThanOrEqualToType;
use jamesiarmes\PhpEws\Type\IsLessThanType;
use jamesiarmes\PhpEws\Type\PathToUnindexedFieldType;
use Yii;
use yii\db\ExpressionInterface;

class SimpleConditionBuilder extends \yii\db\conditions\SimpleConditionBuilder
{
    /**
     * @var \simialbi\yii2\ews\QueryBuilder
     */
    protected $queryBuilder;

    /**
     * {@inheritDoc}
     * @return object|array
     */
    public function build(ExpressionInterface $expression, array &$params = [])
    {
        /** @var \yii\db\conditions\SimpleCondition $expression */
        switch ($expression->getOperator()) {
            case '>':
                $class = IsGreaterThanType::class;
                break;
            case '<':
                $class = IsLessThanType::class;
                break;
            case '>=':
                $class = IsGreaterThanOrEqualToType::class;
                break;
            case '<=':
                $class = IsLessThanOrEqualToType::class;
                break;
            case '=':
                $class = IsEqualToType::class;
                break;
            default:
                return [];
        }

        return Yii::createObject([
            'class' => $class,
            'FieldURI' => Yii::createObject([
                'class' => PathToUnindexedFieldType::class,
                'FieldURI' => $this->queryBuilder->getUriFromProperty($expression->getColumn())
            ]),
            'FieldURIOrConstant' => Yii::createObject([
                'class' => FieldURIOrConstantType::class,
                'Constant' => Yii::createObject([
                    'class' => ConstantValueType::class,
                    'Value' => $expression->getValue()
                ])
            ])
        ]);
    }
}

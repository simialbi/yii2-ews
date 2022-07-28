<?php
/**
 * @package yii2-ews
 * @author Simon Karlen <simi.albi@gmail.com>
 */

namespace simialbi\yii2\ews\conditions;

use jamesiarmes\PhpEws\Type\ConstantValueType;
use jamesiarmes\PhpEws\Type\FieldURIOrConstantType;
use jamesiarmes\PhpEws\Type\IsEqualToType;
use jamesiarmes\PhpEws\Type\PathToUnindexedFieldType;
use Yii;
use yii\db\ExpressionInterface;

/**
 * {@inheritDoc}
 */
class HashConditionBuilder extends \yii\db\conditions\HashConditionBuilder
{
    /**
     * @var \simialbi\yii2\ews\QueryBuilder
     */
    protected $queryBuilder;

    /**
     * {@inheritDoc}
     * @return array
     */
    public function build(ExpressionInterface $expression, array &$params = []): array
    {
        /** @var \yii\db\conditions\HashCondition $expression */
        $hash = $expression->getHash();

        $elements = [];
        foreach ($hash as $column => $value) {
            $elements[] = Yii::createObject([
                'class' => IsEqualToType::class,
                'FieldURI' => Yii::createObject([
                    'class' => PathToUnindexedFieldType::class,
                    'FieldURI' => $this->queryBuilder->getUriFromProperty($column)
                ]),
                'FieldURIOrConstant' => Yii::createObject([
                    'class' => FieldURIOrConstantType::class,
                    'Constant' => Yii::createObject([
                        'class' => ConstantValueType::class,
                        'Value' => $value
                    ])
                ])
            ]);
        }

        return count($elements) === 1 ? $elements[0] : $this->queryBuilder->buildCondition(['AND', $elements], $params);
    }
}

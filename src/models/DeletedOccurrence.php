<?php
/**
 * @package yii2-ews
 * @author Simon Karlen <simi.albi@gmail.com>
 */

namespace simialbi\yii2\ews\models;

use jamesiarmes\PhpEws\Type\DeletedOccurrenceInfoType;
use simialbi\yii2\ews\ActiveRecord;
use yii\behaviors\AttributeTypecastBehavior;

/**
 * Class Attendee
 * @package simialbi\yii2\ews\models
 *
 * @property string|\DateTime|integer $start => Start
 */
class DeletedOccurrence extends ActiveRecord
{
    /**
     * {@inheritDoc}
     */
    public static function modelName(): string
    {
        return DeletedOccurrenceInfoType::class;
    }

    /**
     * {@inheritDoc}
     */
    public function rules(): array
    {
        return [
            ['start', 'datetime', 'format' => 'yyyy-MM-dd HH:mm xxx', 'timestampAttribute' => 'start'],
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function behaviors(): array
    {
        return [
            'typecast' => [
                'class' => AttributeTypecastBehavior::class,
                'typecastAfterSave' => false,
                'typecastAfterValidate' => false,
                'typecastBeforeSave' => false,
                'typecastAfterFind' => true,
                'attributeTypes' => [
                    'start' => [$this, 'typeCastDateTime'],
                ]
            ]
        ];
    }
}

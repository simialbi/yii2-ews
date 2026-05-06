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
 * @property string $id => \jamesiarmes\PhpEws\Type\ItemIdType:ItemId.Id
 * @property string $changeKey => \jamesiarmes\PhpEws\Type\ItemIdType:ItemId.ChangeKey
 * @property string|\DateTime|integer $start => Start
 * @property string|\DateTime|integer $end => End
 * @property string|\DateTime|integer $originalStart => OriginalStart
 */
class ModifiedOccurrence extends ActiveRecord
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
            [['id', 'changeKey'], 'string'],
            ['start', 'datetime', 'format' => 'yyyy-MM-dd HH:mm xxx', 'timestampAttribute' => 'start'],
            ['end', 'datetime', 'format' => 'yyyy-MM-dd HH:mm xxx', 'timestampAttribute' => 'end'],
            ['originalStart', 'datetime', 'format' => 'yyyy-MM-dd HH:mm xxx', 'timestampAttribute' => 'originalStart'],
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
                    'end' => [$this, 'typeCastDateTime'],
                    'originalStart' => [$this, 'typeCastDateTime'],
                ]
            ]
        ];
    }
}

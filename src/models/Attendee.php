<?php
/**
 * @package yii2-ews
 * @author Simon Karlen <simi.albi@gmail.com>
 */

namespace simialbi\yii2\ews\models;

use jamesiarmes\PhpEws\Type\AttendeeType;
use simialbi\yii2\ews\ActiveRecord;

/**
 * Class Attendee
 * @package simialbi\yii2\ews\models
 *
 * @property string $name => \jamesiarmes\PhpEws\Type\EmailAddressType:Mailbox.Name
 * @property string $email => \jamesiarmes\PhpEws\Type\EmailAddressType:Mailbox.EmailAddress
 */
class Attendee extends ActiveRecord
{
    /**
     * {@inheritDoc}
     */
    public static function modelName(): string
    {
        return AttendeeType::class;
    }

    /**
     * {@inheritDoc}
     */
    public function rules(): array
    {
        return [
            ['name', 'string'],
            ['email', 'email', 'enableIDN' => function_exists('idn_to_ascii')]
        ];
    }
}

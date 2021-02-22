<?php
/**
 * @package yii2-ews
 * @author Simon Karlen <simi.albi@gmail.com>
 */

namespace simialbi\yii2\ews\models;

use yii\base\Model;

/**
 * Class Contact
 * @package simialbi\yii2\ews\models
 */
class Contact extends Model
{
    /**
     * @var string
     */
    public $email;
    /**
     * @var string
     */
    public $changeKey;
    /**
     * @var string
     */
    public $id;
    /**
     * @var string
     */
    public $name;

    /**
     * {@inheritDoc}
     */
    public function rules()
    {
        return [
            [['name', 'changeKey', 'id'], 'string'],
            ['email', 'email', 'enableIDN' => function_exists('idn_to_ascii')]
        ];
    }
}

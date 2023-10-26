<?php
/**
 * @package yii2-ews
 * @author Simon Karlen <simi.albi@gmail.com>
 */

namespace simialbi\yii2\ews;

/**
 * Exception represents an exception that is caused by some EWS-related operations.
 *
 * @author Simon Karlen <simi.albi@outlook.com>
 */
class Exception extends \yii\db\Exception
{
    /**
     * {@inheritDoc}
     */
    public function getName(): string
    {
        return 'Exchange Web Services Exception';
    }
}

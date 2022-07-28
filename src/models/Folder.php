<?php
/**
 * @package yii2-ews
 * @author Simon Karlen <simi.albi@gmail.com>
 */

namespace simialbi\yii2\ews\models;

use jamesiarmes\PhpEws\Type\FolderType;
use simialbi\yii2\ews\ActiveRecord;

/**
 * Class Folder
 * @package simialbi\yii2\ews\models
 *
 * @property string $id => \jamesiarmes\PhpEws\Type\ItemIdType:ItemId.Id
 * @property string $changeKey => \jamesiarmes\PhpEws\Type\ItemIdType:ItemId.ChangeKey
 * @property string $parentFolderId => \jamesiarmes\PhpEws\Type\FolderIdType:ParentFolderId.Id
 * @property string $parentFolderChangeKey => \jamesiarmes\PhpEws\Type\FolderIdType:ParentFolderId.ChangeKey
 * @property string $name => DisplayName
 * @property integer $unreadCount => UnreadCount
 * @property integer $totalCount => TotalCount
 * @property integer $childrenCount => ChildFolderCount
 */
class Folder extends ActiveRecord
{
    /**
     * {@inheritDoc}
     */
    public static function modelName(): string
    {
        return FolderType::class;
    }

    /**
     * {@inheritDoc}
     */
    public function rules(): array
    {
        return [
            [['id', 'changeKey', 'name'], 'string'],
            [['unreadCount', 'totalCount', 'childrenCount'], 'integer']
        ];
    }
}

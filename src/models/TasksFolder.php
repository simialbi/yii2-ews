<?php
/**
 * @package yii2-ews
 * @author Simon Karlen <simi.albi@gmail.com>
 */

namespace simialbi\yii2\ews\models;

use jamesiarmes\PhpEws\Type\TasksFolderType;

/**
 * Class Folder
 * @package simialbi\yii2\ews\models
 *
 * @property string $id => \jamesiarmes\PhpEws\Type\FolderIdType:FolderId.Id
 * @property string $changeKey => \jamesiarmes\PhpEws\Type\FolderIdType:FolderId.Id
 * @property string $parentFolderId => \jamesiarmes\PhpEws\Type\FolderIdType:ParentFolderId.Id
 * @property string $parentFolderChangeKey => \jamesiarmes\PhpEws\Type\FolderIdType:ParentFolderId.ChangeKey
 * @property string $name => DisplayName
 * @property integer $unreadCount => UnreadCount
 * @property integer $totalCount => TotalCount
 * @property integer $childrenCount => ChildFolderCount
 */
class TasksFolder extends Folder
{
    /**
     * {@inheritDoc}
     */
    public static function modelName(): string
    {
        return TasksFolderType::class;
    }
}

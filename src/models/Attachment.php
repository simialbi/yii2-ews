<?php

namespace simialbi\yii2\ews\models;

use jamesiarmes\PhpEws\ArrayType\NonEmptyArrayOfRequestAttachmentIdsType;
use jamesiarmes\PhpEws\Request\GetAttachmentType;
use jamesiarmes\PhpEws\Type\FileAttachmentType;
use jamesiarmes\PhpEws\Type\RequestAttachmentIdType;
use simialbi\yii2\ews\ActiveRecord;
use yii\base\InvalidConfigException;

/**
 * @property string $id => \jamesiarmes\PhpEws\Type\AttachmentIdType:AttachmentId.Id
 * @property string $rootId => \jamesiarmes\PhpEws\Type\AttachmentIdType:AttachmentId.RootItemId
 * @property string $rootChangeKey => \jamesiarmes\PhpEws\Type\AttachmentIdType:AttachmentId.RootItemChangeKey
 * @property string $contentId => ContentId
 * @property string $location => ContentLocation
 * @property string $mime => ContentType
 * @property boolean $isInline => IsInline
 * @property string $name => Name
 * @property integer $size => Size
 * @property string $content => \jamesiarmes\PhpEws\Type\FileAttachmentType:Content
 * @property boolean $isContactPhoto => \jamesiarmes\PhpEws\Type\FileAttachmentType:IsContactPhoto
 */
class Attachment extends ActiveRecord
{
    /**
     * {@inheritDoc}
     */
    public static function modelName(): string
    {
        return FileAttachmentType::class;
    }

    /**
     * {@inheritDoc}
     */
    public function rules(): array
    {
        return [
            [['id', 'rootId', 'rootChangeKey', 'contentId', 'location', 'mime', 'name', 'content'], 'string'],
            [['isInline', 'isContactPhoto'], 'boolean'],
            ['size', 'integer'],

            [['isInline', 'isContactPhoto'], 'default', 'value' => false]
        ];
    }

    /**
     * {@inheritDoc}
     * @throws InvalidConfigException
     * @todo Find better solution
     */
    public function afterFind(): void
    {
        parent::afterFind();

        if (empty($this->content)) {
            $request = new GetAttachmentType();
            $request->AttachmentIds = new NonEmptyArrayOfRequestAttachmentIdsType();

            $id = new RequestAttachmentIdType();
            $id->Id = $this->id;
            $request->AttachmentIds->AttachmentId[] = $id;

            $response = self::getDb()->getClient()->GetAttachment($request);
            $message = $response->ResponseMessages->GetAttachmentResponseMessage[0];

            $this->content = $message->Attachments->FileAttachment[0]->Content;
            $this->isContactPhoto = $message->Attachments->FileAttachment[0]->IsContactPhoto;

            $this->setOldAttribute('content', $this->content);
            $this->setOldAttribute('isContactPhoto', $this->isContactPhoto);
        }
    }
}

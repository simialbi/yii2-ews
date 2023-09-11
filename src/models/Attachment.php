<?php

namespace simialbi\yii2\ews\models;

use jamesiarmes\PhpEws\ArrayType\NonEmptyArrayOfRequestAttachmentIdsType;
use jamesiarmes\PhpEws\Request\GetAttachmentType;
use jamesiarmes\PhpEws\Type\AttachmentType;
use jamesiarmes\PhpEws\Type\RequestAttachmentIdType;
use simialbi\yii2\ews\ActiveRecord;
use yii\helpers\ArrayHelper;

/**
 * @property string $id => \jamesiarmes\PhpEws\Type\AttachmentIdType:AttachmentId.Id
 * @property string $rootId => \jamesiarmes\PhpEws\Type\AttachmentIdType:AttachmentId.RootItemId
 * @property string $rootChangeKey => \jamesiarmes\PhpEws\Type\AttachmentIdType:AttachmentId.RootItemChangeKey
 * @property string $contentId => ContentId
 * @property string $location => ContentLocation
 * @property string $mime => ContentType
 * @property bool $isInline => IsInline
 * @property string $name => Name
 * @property int $size => Size
 *
 * @property-read $content
 */
class Attachment extends ActiveRecord
{
    /**
     * @var string The binary content
     */
    private string $_content;

    /**
     * {@inheritDoc}
     */
    public static function modelName(): string
    {
        return AttachmentType::class;
    }

    /**
     * {@inheritDoc}
     */
    public function rules(): array
    {
        return [
            [['id', 'changeKey', 'contentId', 'location', 'mime', 'name'], 'string'],
            [['isInline'], 'boolean'],
            ['size', 'int'],

            ['isInline', 'default', 'value' => false]
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function fields(): array
    {
        return ArrayHelper::merge(parent::fields(), ['content' => 'content']);
    }

    /**
     * Load binary content
     * @return string
     * @throws \yii\base\InvalidConfigException
     * @todo Find better solution
     */
    public function getContent(): string
    {
        if (!isset($this->_content)) {
            $request = new GetAttachmentType();
            $request->AttachmentIds = new NonEmptyArrayOfRequestAttachmentIdsType();

            $id = new RequestAttachmentIdType();
            $id->Id = $this->id;
            $request->AttachmentIds->AttachmentId[] = $id;

            $response = self::getDb()->getClient()->GetAttachment($request);
            $message = $response->ResponseMessages->GetAttachmentResponseMessage[0];

            $this->_content = $message->Attachments->FileAttachment[0]->Content;
        }

        return $this->_content;
    }
}

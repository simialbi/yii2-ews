<?php
/**
 * @package yii2-ews
 * @author Simon Karlen <simi.albi@gmail.com>
 */

namespace simialbi\yii2\ews;

use Yii;
use yii\base\InvalidConfigException;
use yii\db\BaseActiveRecord;
use yii\helpers\ArrayHelper;

/**
 * Class ActiveRecord
 *
 * @property string $id => \jamesiarmes\PhpEws\Type\ItemIdType:ItemId.Id
 * @property string $changeKey => \jamesiarmes\PhpEws\Type\ItemIdType:ItemId.ChangeKey
 * @property string $parentFolderId => \jamesiarmes\PhpEws\Type\FolderIdType:ParentFolderId.Id
 * @property string $parentFolderChangeKey => \jamesiarmes\PhpEws\Type\FolderIdType:ParentFolderId.ChangeKey
 */
class ActiveRecord extends BaseActiveRecord
{
    /**
     * @var array
     */
    private $_attributeFields = [];

    /**
     * Declares the name of the corresponding php ews model name.
     * @return string
     * @throws InvalidConfigException
     */
    public static function modelName(): string
    {
        throw new InvalidConfigException(__METHOD__ . ' must be overridden.');
    }

    /**
     * Declares the representative fields from this AR class with the corresponding ews class.
     *
     * @return array
     * @throws \ReflectionException|\Exception
     */
    public static function attributeMapping(): array
    {
        return self::parseAttributes()[1];
    }

    /**
     * {@inheritDoc}
     */
    public static function primaryKey(): array
    {
        return ['id', 'changeKey'];
    }

    /**
     * {@inheritDoc}
     * @throws \yii\base\InvalidConfigException
     */
    public static function find(): ActiveQuery
    {
        return Yii::createObject(ActiveQuery::class, [get_called_class()]);
    }

    /**
     * {@inheritDoc}
     * @return \simialbi\yii2\ews\Connection
     * @throws \yii\base\InvalidConfigException
     */
    public static function getDb(): Connection
    {
        return Yii::$app->get('ews');
    }

    /**
     * {@inheritDoc}
     * @return boolean
     * @throws \yii\base\NotSupportedException
     * @throws Exception
     * @throws InvalidConfigException
     */
    public static function updateAll($attributes, $condition = null): bool
    {
        $command = static::getDb()->createCommand();
        $command->update(static::class, $attributes, $condition);

        return $command->execute() !== false;
    }

    /**
     * {@inheritDoc}
     *
     * @return int|void
     * @throws \yii\base\NotSupportedException
     */
    public static function deleteAll($condition = null)
    {
        parent::deleteAll($condition);
    }

    /**
     * Parse attributes from PHP doc and return
     * @return array
     * @throws \ReflectionException|\Exception
     */
    private static function parseAttributes(): array
    {
        $regex = '#^@property(?:-(read|write))?(?:\s+([^\s]+))?\s+\$([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)(?:\s+=>\s+(\\\\[\\\\a-zA-Z0-9\x7f-\xff]+)?:?([a-zA-Z0-9\x7f-\xff._]+))?#';
//        $typeRegex = '#^(bool(ean)?|int(eger)?|float|double|string|array)$#';
        $reflection = new \ReflectionClass(get_called_class());
        $docLines = preg_split('~\R~u', $reflection->getDocComment());
        $attributeFields = [];
        $attributeMeta = [];
        foreach ($docLines as $docLine) {
            $matches = [];
            $docLine = ltrim($docLine, "\t* ");
            if (preg_match($regex, $docLine, $matches) && isset($matches[3])) {
                if ($matches[1] === 'read' || empty($matches[2])) {
                    continue;
                }
                $attributeFields[] = $matches[3];
                $attributeMeta[$matches[3]] = [
                    'dataType' => explode('|', $matches[2]),
                    'foreignModel' => ArrayHelper::getValue($matches, 4),
                    'foreignField' => ArrayHelper::getValue($matches, 5)
                ];
            }
        }

        return [$attributeFields, $attributeMeta];
    }

    /**
     * {@inheritDoc}
     * @throws \ReflectionException
     */
    public function attributes(): array
    {
        if (empty($this->_attributeFields)) {
            [$this->_attributeFields,] = self::parseAttributes();
        }

        return $this->_attributeFields;
    }

    /**
     * {@inheritDoc}
     * @param array $params the binding parameters that will be generated by this method.
     * They should be bound to the DB command later.
     * @throws \Throwable
     * @throws \yii\db\Exception
     * @throws \yii\db\StaleObjectException
     */
    public function save($runValidation = true, $attributeNames = null, array $params = []): bool
    {
        if ($this->getIsNewRecord()) {
            return $this->insert($runValidation, $attributeNames, $params);
        }

        return $this->update($runValidation, $attributeNames) !== false;
    }

    /**
     * {@inheritDoc}
     * @param array $params the binding parameters that will be generated by this method.
     * They should be bound to the DB command later.
     * @throws \Exception|\Throwable in case insert failed.
     */
    public function insert($runValidation = true, $attributes = null, array $params = []): bool
    {
        if ($runValidation && !$this->validate($attributes)) {
            Yii::info('Model not inserted due to validation error.', __METHOD__);

            return false;
        }

        if (!$this->beforeSave(true)) {
            return false;
        }
        $values = $this->getDirtyAttributes($attributes);
        if (false === ($data = static::getDb()->createCommand()->insert(static::class, $values, $params)->execute())) {
            return false;
        }

        $this->setAttributes($values);
        foreach ($data as $name => $value) {
            if ($name === 'ChangeKey') {
                $this->setAttribute('changeKey', $value);
            } elseif ($name === 'Id') {
                $this->setAttribute('id', $value);
            }
        }

        $changedAttributes = array_fill_keys(array_keys($values), null);
        $this->setOldAttributes($values);
        $this->afterSave(true, $changedAttributes);

        return true;
    }
}

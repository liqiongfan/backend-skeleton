<?php

namespace app\models;

use Yii;
use yii\base\ErrorException;
use yii\db\ActiveRecord;
use yii\db\Query;
use yii\helpers\ArrayHelper;
use yii\web\UploadedFile;

/**
 * This is the model class for table "{{%meta}}".
 *
 * @property integer $id
 * @property string $table_name
 * @property string $key
 * @property string $label
 * @property string $description
 * @property string $input_type
 * @property string $input_candidate_value
 * @property integer $return_value_type
 * @property string $default_value
 * @property integer $enabled
 * @property integer $created_by
 * @property integer $created_at
 * @property integer $updated_by
 * @property integer $updated_at
 * @property integer $deleted_by
 * @property integer $deleted_at
 */
class Meta extends \yii\db\ActiveRecord
{

    public $validatorsList;

    /**
     * 数据输入方式
     */
    const INPUT_TYPE_TEXT = 0;
    const INPUT_TYPE_TEXTAREA = 1;
    const INPUT_TYPE_DROPDOWNLIST = 2;
    const INPUT_TYPE_CHECKBOXLIST = 3;
    const INPUT_TYPE_RADIOLIST = 4;
    const INPUT_TYPE_FILE = 5;

    /**
     * 数据值返回类型
     */
    const RETURN_VALUE_TYPE_STRING = 1;
    const RETURN_VALUE_TYPE_TEXT = 2;
    const RETURN_VALUE_TYPE_INTEGER = 3;
    const RETURN_VALUE_TYPE_DECIMAL = 4;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%meta}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['table_name', 'key', 'label', 'description', 'input_type'], 'required'],
            [['return_value_type', 'enabled', 'created_by', 'created_at', 'updated_by', 'updated_at', 'deleted_by', 'deleted_at'], 'integer'],
            ['return_value_type', 'default', 'value' => self::RETURN_VALUE_TYPE_STRING],
            ['enabled', 'boolean'],
            [['table_name'], 'string', 'max' => 60],
            [['key'], 'string', 'max' => 30],
            [['key'], 'trim'],
            [['label', 'description'], 'string', 'max' => 255],
            [['input_type', 'default_value'], 'string', 'max' => 16],
            [['input_candidate_value'], 'string'],
            ['validatorsList', 'safe'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'table_name' => Yii::t('meta', 'Table Name'),
            'key' => Yii::t('meta', 'Key'),
            'label' => Yii::t('meta', 'Label'),
            'description' => Yii::t('meta', 'Description'),
            'input_type' => Yii::t('meta', 'Input Type'),
            'input_type_text' => Yii::t('meta', 'Input Type'),
            'input_candidate_value' => Yii::t('meta', 'Input Candidate Value'),
            'return_value_type' => Yii::t('meta', 'Return Value Type'),
            'return_value_type_text' => Yii::t('meta', 'Return Value Type'),
            'default_value' => Yii::t('meta', 'Default Value'),
            'enabled' => Yii::t('app', 'Enabled'),
            'created_by' => Yii::t('app', 'Created By'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_by' => Yii::t('app', 'Updated By'),
            'updated_at' => Yii::t('app', 'Updated At'),
            'deleted_by' => Yii::t('app', 'Deleted By'),
            'deleted_at' => Yii::t('app', 'Deleted At'),
        ];
    }

    /**
     * 输入类型选项
     *
     * @return array
     */
    public static function inputTypeOptions()
    {
        return [
            self::INPUT_TYPE_TEXT => '文本',
            self::INPUT_TYPE_TEXTAREA => '大段文本',
            self::INPUT_TYPE_DROPDOWNLIST => '下拉框',
            self::INPUT_TYPE_CHECKBOXLIST => '复选框',
            self::INPUT_TYPE_RADIOLIST => '单选框',
            self::INPUT_TYPE_FILE => '文件上传',
        ];
    }

    /**
     * 数据输入方式
     *
     * @return string|mixed
     */
    public function getInput_type_text()
    {
        $options = self::inputTypeOptions();

        return isset($options[$this->input_type]) ? $options[$this->input_type] : null;
    }

    /**
     * 返回数据类型选项
     *
     * @retrun array
     */
    public static function returnValueTypeOptions()
    {
        return [
            self::RETURN_VALUE_TYPE_STRING => '字符串',
            self::RETURN_VALUE_TYPE_TEXT => '大段内容',
            self::RETURN_VALUE_TYPE_INTEGER => '数字（不带小数）',
            self::RETURN_VALUE_TYPE_DECIMAL => '数字（带小数）',
        ];
    }

    /**
     * 返回数据类型
     *
     * @return string|mixed
     */
    public function getReturn_value_type_text()
    {
        $options = self::returnValueTypeOptions();

        return isset($options[$this->return_value_type]) ? $options[$this->return_value_type] : null;
    }

    /**
     * 获取对象的 Meta 数据
     *
     * @param \yii\db\ActiveRecord $activeRecord
     * @return array
     */
    public static function getItems(ActiveRecord $activeRecord)
    {
        $items = [];
        $rawData = (new Query())->select(['id', 'key', 'label', 'description', 'input_type', 'input_candidate_value', 'default_value', 'return_value_type'])
            ->from(static::tableName())
            ->where([
                'table_name' => strtr($activeRecord->tableName(), ['{{%' => '', '}}' => '']),
                'enabled' => Constant::BOOLEAN_TRUE,
            ])
            ->indexBy('id')
            ->all();

        $objectId = $activeRecord->isNewRecord ? null : $activeRecord->getPrimaryKey();
        if ($objectId) {
            $values = [];
            $rawValues = (new Query())
                ->select('*')
                ->from('{{%meta_value}}')
                ->where([
                    'meta_id' => array_keys($rawData),
                    'object_id' => $objectId,
                ])
                ->all();
            foreach ($rawValues as $item) {
                $key = "{$item['meta_id']}.{$item['object_id']}";
                switch ($rawData[$item['meta_id']]['return_value_type']) {
                    case self::RETURN_VALUE_TYPE_STRING:
                        $value = $item['string_value'];
                        break;

                    case self::RETURN_VALUE_TYPE_TEXT:
                        $value = $item['text_value'];
                        break;

                    case self::RETURN_VALUE_TYPE_INTEGER:
                        $value = $item['integer_value'];
                        break;

                    case self::RETURN_VALUE_TYPE_DECIMAL:
                        $value = $item['decimal_value'];
                        break;

                    default:
                        $value = $item['string_value'];
                        break;
                }
                if (!isset($values[$key])) {
                    $values[$key] = $value;
                } else {
                    if (is_array($values[$key])) {
                        $values[$key][] = $value;
                    } else {
                        $values[$key] = [$values[$key], $value];
                    }
                }
            }
        } else {
            $values = [];
        }

        $rawRules = [];
        $validators = (new Query())->select(['meta_id', 'name', 'options'])
            ->from('{{%meta_validator}}')
            ->where(['meta_id' => array_keys($rawData)])
            ->all();
        foreach ($validators as $validator) {
            $options = unserialize($validator['options']) ?: [];
            foreach ($options as $key => $value) {
                if (trim($value) == '') {
                    unset($options[$key]);
                }
            }
            !isset($rawRules[$validator['meta_id']]) && $rawRules[$validator['meta_id']] = [];
            $options && $rawRules[$validator['meta_id']][$validator['name']] = $options;
        }

        foreach ($rawData as $data) {
            $data['value'] = $values && isset($values["{$data['id']}.{$objectId}"]) ? $values["{$data['id']}.{$objectId}"] : null;
            switch ($data['input_type']) {
                case self::INPUT_TYPE_TEXTAREA:
                    $data['input_type'] = 'textarea';
                    $data['input_candidate_value'] = [];
                    break;

                case self::INPUT_TYPE_DROPDOWNLIST:
                case self::INPUT_TYPE_CHECKBOXLIST:
                case self::INPUT_TYPE_RADIOLIST:
                    if ($data['input_type'] == self::INPUT_TYPE_DROPDOWNLIST) {
                        $data['input_type'] = 'dropDownList';
                    } elseif ($data['input_type'] == self::INPUT_TYPE_CHECKBOXLIST) {
                        $data['input_type'] = 'checkboxList';
                    } elseif ($data['input_type'] == self::INPUT_TYPE_RADIOLIST) {
                        $data['input_type'] = 'radioList';
                    }

                    // 候选值处理
                    $inputCandidateValue = [];
                    $rawInputCandidateValue = trim($data['input_candidate_value']);
                    if (!empty($rawInputCandidateValue)) {
                        // 检查是否为类静态函数调用方式 (\app\models\Option::boolean())
                        if (preg_match('/(\\\[a-z\\\]*[A-Z][a-z]*)::([A-Za-z]*)\((.*)\)/', $rawInputCandidateValue, $matches)) {
                            $params = array_map(function ($v) {
                                $v = str_replace([' ', '', "'"], '', $v);

                                return $v;
                            }, explode(',', $matches[3]));
                            $inputCandidateValue = call_user_func_array([$matches[1], $matches[2]], $params);
                        } else {
                            /**
                             * 处理如下格式的内容
                             *
                             * 1:China
                             * 2:USA
                             * 3:China:Japan
                             */
                            foreach (explode(PHP_EOL, $rawInputCandidateValue) as $row) {
                                $row = array_map('trim', explode(':', $row));
                                if (isset($row[1]) && !empty($row[0]) && !empty($row[1])) {
                                    $key = array_shift($row);
                                    $inputCandidateValue[$key] = isset($row[2]) ? implode(':', $row) : $row[1];
                                }
                            }
                        }
                    }
                    $data['input_candidate_value'] = $inputCandidateValue;
                    break;

                case self::INPUT_TYPE_FILE:
                    $data['input_type'] = 'fileInput';
                    $data['input_candidate_value'] = [];
                    break;

                default:
                    $data['input_type'] = 'textInput';
                    $data['input_candidate_value'] = [];
                    break;
            }
            $data['rules'] = (isset($rawRules[$data['id']]) && $rawRules[$data['id']]) ? $rawRules[$data['id']] : ['safe' => []];
            $items[$data['key']] = $data;
        }

        return $items;
    }

    /**
     * 移除表前缀，获取单独的表名称
     *
     * @param $tableName
     * @return mixed|string
     */
    private static function _fixTableName($tableName)
    {
        $tableName = strtolower(trim($tableName));
        if (strpos($tableName, '{{') !== false) {
            $tableName = str_replace(['{', '%', '}'], '', $tableName);
        }

        return $tableName;
    }

    /**
     * 获取数据验证规则
     *
     * @param $tableName
     * @return array
     * @throws \yii\db\Exception
     */
    public static function getRules($tableName)
    {
        $rules = [];
        $validators = Yii::$app->getDb()->createCommand('SELECT [[name]], [[options]] FROM {{%meta_validator}} WHERE [[meta_id]] IN (SELECT [[id]] FROM {{%meta}} WHERE [[table_name]] = :tableName)', [':tableName' => self::_fixTableName($tableName)])->queryAll();
        foreach ($validators as $validator) {
            $options = unserialize($validator['options']) ?: [];
            foreach ($options as $key => $value) {
                if (trim($value) == '') {
                    unset($options[$key]);
                }
            }
            $rules[$validator['name']] = $options ?: ['safe' => []];
        }

        return $rules;
    }

    /**
     * 获取 Meta 对象的验证规则
     *
     * @param $metaId
     * @return array
     * @throws \yii\db\Exception
     */
    public static function getMetaRules($metaId)
    {
        $rules = [];
        $validators = Yii::$app->getDb()->createCommand('SELECT [[name]], [[options]] FROM {{%meta_validator}} WHERE [[meta_id]] = :metaId', [':metaId' => (int) $metaId])->queryAll();
        foreach ($validators as $validator) {
            $options = unserialize($validator['options']) ?: [];
            foreach ($options as $key => $value) {
                if (trim($value) == '') {
                    unset($options[$key]);
                }
            }
            $rules[$validator['name']] = $options;
        }

        return $rules;
    }

    /**
     * 保存 Meta 数据
     *
     * @param ActiveRecord $activeRecord
     * @param \yii\base\DynamicModel $dynamicModel
     * @param bool $throwException
     * @return bool|null
     * @throws ErrorException
     * @throws \yii\db\Exception
     */
    public static function saveValues(\yii\db\ActiveRecord $activeRecord, \yii\base\DynamicModel $dynamicModel, $throwException = false)
    {
        $db = \Yii::$app->getDb();
        $command = $db->createCommand();
        $transaction = $db->beginTransaction();
        try {
            $attributes = $dynamicModel->attributes;
            if (!$attributes) {
                return null;
            }
            $objectId = $activeRecord->getPrimaryKey();
            $metaList = [];
            $keys = array_keys($attributes);
            if ($keys) {
                $rawMetaList = $db->createCommand("SELECT [[id]], [[key]], [[return_value_type]] FROM {{%meta}} WHERE [[key]] IN ('" . implode("', '", $keys) . "')")->queryAll();
                foreach ($rawMetaList as $item) {
                    $metaList[$item['key']] = [
                        'id' => $item['id'],
                        'returnValueType' => $item['return_value_type']
                    ];
                }
            }

            $batchInsertRows = [];
            $reservedMetaIds = [];
            $validators = $dynamicModel->validators;
            foreach ($attributes as $key => $value) {
                $isFile = false;
                foreach ($validators as $validator) {
                    if ($validator instanceof \yii\validators\FileValidator) {
                        $validatorAttributes = $validator->attributes;
                        foreach ($validatorAttributes as $attr) {
                            if ($key == $attr) {
                                $isFile = true;
                                break;
                            }
                        }
                    }
                }
                if ($isFile) {
                    $file = UploadedFile::getInstance($dynamicModel, $key);
                    if ($file) {
                        $directory = Yii::getAlias('@webroot');
                        $path = '/uploads/' . date('Ymd');
                        if (!is_dir($directory . $path)) {
                            FileHelper::createDirectory($directory . $path);
                        }
                        $filename = Yii::$app->getSecurity()->generateRandomString() . '.' . $file->getExtension();
                        $file->saveAs($directory . $path . '/' . $filename);
                        $value = $path . '/' . $filename;
                    } else {
                        if ($activeRecord->isNewRecord) {
                            continue;
                        } else {
                            $value = null;
                            $reservedMetaIds[] = $metaList[$key]['id'];
                        }
                    }
                } else {
                    $value = (string) $value;
                }
                if (!isset($metaList[$key]) || $value === '' || $value === null || (is_string($value) && trim($value) === '') || ($value == '' && !$isFile)) {
                    continue;
                }
                $columns = [
                    'object_id' => $objectId,
                    'meta_id' => $metaList[$key]['id'],
                    'string_value' => null,
                    'text_value' => null,
                    'integer_value' => null,
                    'decimal_value' => null,
                ];
                switch ($metaList[$key]['returnValueType']) {
                    case self::RETURN_VALUE_TYPE_STRING:
                        $valueField = 'string_value';
                        break;

                    case self::RETURN_VALUE_TYPE_TEXT:
                        $valueField = 'text_value';
                        break;

                    case self::RETURN_VALUE_TYPE_INTEGER:
                        $valueField = 'integer_value';
                        break;

                    case self::RETURN_VALUE_TYPE_DECIMAL:
                        $valueField = 'decimal_value';
                        break;

                    default:
                        $valueField = 'string_value';
                        break;
                }
                $columns[$valueField] = $value;
                $batchInsertRows[] = $columns;
            }

            if (!$activeRecord->isNewRecord) {
                $deleteMetaIds = ArrayHelper::getColumn($metaList, 'id');
                if ($reservedMetaIds) {
                    $deleteMetaIds = array_diff($deleteMetaIds, $reservedMetaIds);
                }
                $condition = ['object_id' => $objectId];
                if ($deleteMetaIds) {
                    $condition['meta_id'] = $deleteMetaIds;
                }
                $command->delete('{{%meta_value}}', $condition)->execute();
            }

            if ($batchInsertRows) {
                $command->batchInsert('{{%meta_value}}', array_keys($batchInsertRows[0]), $batchInsertRows)->execute();
            }

            $transaction->commit();

            return true;
        } catch (\Exception $exc) {
            $transaction->rollBack();
            if ($throwException) {
                throw new ErrorException($exc->getMessage());
            } else {
                return false;
            }
        }
    }

    public static function parseReturnKey($returnValueType = self::RETURN_VALUE_TYPE_STRING)
    {
        switch ($returnValueType) {
            case self::RETURN_VALUE_TYPE_TEXT:
                $key = 'text';
                break;

            case self::RETURN_VALUE_TYPE_INTEGER:
                $key = 'integer';
                break;

            case self::RETURN_VALUE_TYPE_DECIMAL:
                $key = 'decimal';
                break;

            default:
                $key = 'string';
                break;
        }

        return "{$key}_value";
    }

    /**
     * 根据返回值类型获取返回的值
     *
     * @param $values
     * @param int $returnValueType
     * @return null
     */
    public static function parseReturnValue($values, $returnValueType = self::RETURN_VALUE_TYPE_STRING)
    {
        switch ($returnValueType) {
            case self::RETURN_VALUE_TYPE_TEXT:
                $key = 'text';
                break;

            case self::RETURN_VALUE_TYPE_INTEGER:
                $key = 'integer';
                break;

            case self::RETURN_VALUE_TYPE_DECIMAL:
                $key = 'decimal';
                break;

            default:
                $key = 'string';
                break;
        }

        return isset($values["{$key}_value"]) ? $values["{$key}_value"] : null;
    }

    /**
     * 获取自定义字段内容值
     *
     * @param $tableName
     * @param $objectId
     * @param $keys
     * @return mixed
     */
    public static function getValues($tableName, $objectId, $keys)
    {
        $values = [];
        if (!is_array($keys)) {
            $keys = [(string) $keys];
        }
        foreach ($keys as $key => $value) {
            if (is_int($key)) {
                // 0 => 'key' 形式，无默认值
                $k = $value;
                $v = null;
            } else {
                //　'key' => 'default value'
                $k = $key;
                $v = $value;
            }
            $values[$k] = [
                'id' => null,
                'label' => null,
                'description' => null,
                'value' => $v,
            ];
        }

        $where = [
            'table_name' => self::_fixTableName($tableName)
        ];
        if ($keys) {
            $where['key'] = array_keys($values);
        }
        $rawValues = (new Query())
            ->select(['m.id', 'm.key', 'm.label', 'm.description', 'm.return_value_type', 't.string_value', 't.text_value', 't.integer_value', 't.decimal_value'])
            ->from('{{%meta_value}} t')
            ->leftJoin('{{%meta}} m', '[[t.meta_id]] = [[m.id]]')
            ->where(['t.object_id' => (int) $objectId])
            ->andWhere(['IN', 't.meta_id', (new Query())->select(['id'])->from('{{%meta}}')->where($where)])
            ->all();
        foreach ($rawValues as $data) {
            switch ($data['returnValueType']) {
                case self::RETURN_VALUE_TYPE_STRING:
                    $value = $data['string_value'];
                    break;

                case self::RETURN_VALUE_TYPE_TEXT:
                    $value = $data['text_value'];
                    break;

                case self::RETURN_VALUE_TYPE_INTEGER:
                    $value = $data['integer_value'];
                    break;

                case self::RETURN_VALUE_TYPE_DECIMAL:
                    $value = $data['decimal_value'];
                    break;

                default:
                    $value = $data['string_value'];
                    break;
            }
            $values[$data['key']] = [
                'id' => $data['id'],
                'label' => $data['label'],
                'description' => $data['description'],
                'value' => $value,
            ];
        }

        return $values;
    }

    public static function getValue($tableName, $objectId, $key, $defaultValue = null)
    {
        $value = null;
        $db = Yii::$app->getDb();
        $meta = $db->createCommand('SELECT [[id]], [[return_value_type]] FROM {{%meta}} WHERE [[table_name]] = :tableName AND [[key]] = :key', [':tableName' => self::_fixTableName($tableName), ':key' => trim($key)])->queryOne();
        if ($meta) {
            if (is_array($objectId)) {
                $rawValues = $db->createCommand('SELECT [[object_id]], [[string_value]], [[text_value]], [[integer_value]], [[decimal_value]] FROM {{%meta_value}} WHERE [[meta_id]] = :metaId AND [[object_id]] IN (' . implode(', ', $objectId) . ')', [':metaId' => $meta['id']])->queryAll();
                if ($rawValues) {
                    $values = [];
                    foreach ($rawValues as $item) {
                        $values[$item['object_id']] = self::parseReturnValue($item, $meta['return_value_type']);
                    }

                    return $values;
                }
            } else {
                $values = $db->createCommand('SELECT [[string_value]], [[text_value]], [[integer_value]], [[decimal_value]] FROM {{%meta_value}} WHERE [[meta_id]] = :metaId AND [[object_id]] = :objectId', [':metaId' => $meta['id'], ':objectId' => (int) $objectId])->queryOne();
                if ($values) {
                    return self::parseReturnValue($values, $meta['return_value_type']);
                }
            }
        }

        if (is_array($objectId)) {
            return [];
        } else {
            return $value == null ? $defaultValue : $value;
        }
    }

    /**
     * 更新自定义表单数据值
     *
     * @param $tableName
     * @param $objectId
     * @param $key
     * @param $value
     * @return bool
     * @throws \yii\db\Exception
     */
    public static function updateValue($tableName, $objectId, $key, $value)
    {
        $success = false;
        $db = Yii::$app->getDb();
        $metaId = $db->createCommand('SELECT [[id]] FROM {{%meta}} WHERE [[table_name]] = :tableName AND [[key]] = :key', [':tableName' => self::_fixTableName($tableName), ':key' => trim($key)])->queryScalar();
        if ($metaId) {
            $v = $db->createCommand('SELECT [[value]] FROM {{%meta_value}} WHERE [[meta_id]] = :metaId AND [[object_id]] = :objectId', [':metaId' => $metaId, ':objectId' => (int) $objectId])->queryScalar() ?: null;
            // @todo 验证 objectId 是否有效
            if ($v === null) {
                // insert
                $db->createCommand()->insert('{{%meta_value}}', [
                    'meta_id' => $metaId,
                    'object_id' => $objectId,
                    'value' => $value,
                ])->execute();
            } else {
                // Update
                $db->createCommand()->update('{{%meta_value}}', [
                    'value' => $value,
                ], [
                    'meta_id' => $metaId,
                    'object_id' => $objectId
                ])->execute();
            }
            $success = true;
        }

        return $success;
    }

    /**
     * 增加自定义表单项目值
     *
     * @param $tableName
     * @param $objectId
     * @param $key
     * @param $value
     * @return int|null
     * @throws \yii\db\Exception
     */
    public static function increaseValue($tableName, $objectId, $key, $value = 1)
    {
        $result = null;
        $db = Yii::$app->getDb();
        $metaId = $db->createCommand('SELECT [[id]] FROM {{%meta}} WHERE [[table_name]] = :tableName AND [[key]] = :key', [':tableName' => self::_fixTableName($tableName), ':key' => trim($key)])->queryScalar();
        if ($metaId) {
            $v = $db->createCommand('SELECT [[value]] FROM {{%meta_value}} WHERE [[meta_id]] = :metaId AND [[object_id]] = :objectId', [':metaId' => $metaId, ':objectId' => (int) $objectId])->queryScalar();
            // @todo 验证 objectId 是否有效
            if ($v === false) {
                // Insert
                $db->createCommand()->insert('{{%meta_value}}', [
                    'value' => (int) $value,
                    'meta_id' => $metaId,
                    'object_id' => $objectId
                ])->execute();
            } else {
                $value = intval($v) + (int) $value;
                // Update
                $db->createCommand()->update('{{%meta_value}}', [
                    'value' => $value,
                ], [
                    'meta_id' => $metaId,
                    'object_id' => $objectId
                ])->execute();
                $result = $value;
            }
        }

        return $result;
    }

    /**
     * 减少自定义表单项目值
     *
     * @param $tableName
     * @param $objectId
     * @param $key
     * @param $value
     * @return int|null
     * @throws \yii\db\Exception
     */
    public static function decreaseValue($tableName, $objectId, $key, $value = 1)
    {
        $result = null;
        $db = Yii::$app->getDb();
        $metaId = $db->createCommand('SELECT [[id]] FROM {{%meta}} WHERE [[table_name]] = :tableName AND [[key]] = :key', [':tableName' => self::_fixTableName($tableName), ':key' => trim($key)])->queryScalar();
        if ($metaId) {
            $v = $db->createCommand('SELECT [[value]] FROM {{%meta_value}} WHERE [[meta_id]] = :metaId AND [[object_id]] = :objectId', [':metaId' => $metaId, ':objectId' => (int) $objectId])->queryScalar();
            // @todo 验证 objectId 是否有效
            if ($v === false) {
                // Insert
                $db->createCommand()->insert('{{%meta_value}}', [
                    'value' => (int) $value,
                    'meta_id' => $metaId,
                    'object_id' => $objectId
                ])->execute();
            } else {
                $value = intval($v) - (int) $value;
                // Update
                $db->createCommand()->update('{{%meta_value}}', [
                    'value' => $value,
                ], [
                    'meta_id' => $metaId,
                    'object_id' => $objectId
                ])->execute();
                $result = $value;
            }
        }

        return $result;
    }

    // Events
    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            if ($insert) {
                $this->created_at = $this->updated_at = time();
                $this->created_by = $this->updated_by = Yii::$app->getUser()->getId();
            } else {
                $this->updated_at = time();
                $this->updated_by = Yii::$app->getUser()->getId();
            }

            if ($this->input_type == self::INPUT_TYPE_TEXT || $this->input_type == self::INPUT_TYPE_FILE) {
                $this->return_value_type = self::RETURN_VALUE_TYPE_STRING;
            } elseif ($this->input_type == self::INPUT_TYPE_TEXTAREA) {
                $this->return_value_type = self::RETURN_VALUE_TYPE_TEXT;
            }

            $defaultValue = trim($this->default_value);
            switch ($this->return_value_type) {
                case self::RETURN_VALUE_TYPE_INTEGER:
                    $defaultValue = intval($defaultValue);
                    break;

                case self::RETURN_VALUE_TYPE_DECIMAL:
                    $defaultValue = floatval($defaultValue);
                    break;
            }
            $this->default_value = $defaultValue;

            return true;
        } else {
            return false;
        }
    }

    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);
        $command = Yii::$app->getDb()->createCommand();
        if (!$insert) {
            $command->delete('{{%meta_validator}}', ['meta_id' => $this->id])->execute();
        }

        $batchInsertRows = [];
        foreach (is_array($this->validatorsList) ? $this->validatorsList : [] as $key => $item) {
            if (!isset($item['name'])) {
                // 未选择
                continue;
            }
            $columns = [
                'meta_id' => $this->id,
                'name' => $key,
                'options' => serialize(isset($item['options']) ? $item['options'] : [])
            ];
            $batchInsertRows[] = array_values($columns);
        }
        if ($batchInsertRows) {
            $command->batchInsert('{{%meta_validator}}', array_keys($columns), $batchInsertRows)->execute();
        }
    }

    public function afterDelete()
    {
        parent::afterDelete();
        // 删除 meta 数据同时清理掉相关的验证规则以及保存的值
        $cmd = Yii::$app->getDb()->createCommand();
        $cmd->delete('{{%meta_validator}}', ['meta_id' => $this->id])->execute();
        $cmd->delete('{{%meta_value}}', ['meta_id' => $this->id])->execute();
    }

}

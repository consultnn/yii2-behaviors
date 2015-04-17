<?php
namespace consultnn\behaviors;

use yii\base\Behavior;
use yii\db\ActiveRecord;
use yii\validators\BooleanValidator;
use yii\validators\NumberValidator;

/**
 * Class AttributeTypeBehavior
 * @property \yii\base\Model owner
 * @package common\behaviors
 */
class AttributeTypeBehavior extends Behavior
{
    /**
     * Simple types is default
     */
    const TYPE_INTEGER = 'integer';
    const TYPE_FLOAT = 'float';
    const TYPE_BOOLEAN = 'boolean';
    const TYPE_MONGO_ID = 'MongoId';
    
    /**
     * supported types:
     *  all simple types http://php.net/manual/ru/function.settype.php
     *  MongoId
     * @var array [{attributeName} => {attributeType}]
     */
    public $attributes = [];

    public $events = [
        ActiveRecord::EVENT_BEFORE_INSERT,
        ActiveRecord::EVENT_BEFORE_UPDATE
    ]
    
    /**
     * @inheritdoc
     */
    public function events()
    {
        return array_fill_keys($this->events, 'convert');
    }

    /**
     * Main convert function
     */
    public function convert()
    {
        $this->parseValidators();
        $this->convertAttributes();
    }

    /**
     * set attribute type by validator
     */
    private function parseValidators()
    {
        foreach ($this->owner->getValidators() as $validator) {
            $attributes = $validator->attributes;
            if ($validator instanceof NumberValidator) {
                if ($validator->integerOnly) {
                    $this->addAttributes($attributes, self::TYPE_INTEGER);
                } else {
                    $this->addAttributes($attributes, self::TYPE_FLOAT);
                }
            } elseif ($validator instanceof BooleanValidator) {
                $this->addAttributes($attributes, self::TYPE_BOOLEAN);
            }
        }
    }

    /**
     * Add attributes for convert
     * @param array $attributes
     * @param string $type
     */
    private function addAttributes($attributes, $type)
    {
        $this->attributes = array_merge(array_fill_keys($attributes, $type),$this->attributes);
    }

    /**
     * Convert attribute type
     */
    private function convertAttributes()
    {
        foreach ($this->attributes as $attribute => $type) {
            if ($this->owner->$attribute !== null) {
                $value = $this->owner->$attribute;
                $methodName = 'set'.$type;
                if ($this->hasMethod($methodName)) {
                    $this->$methodName($value);
                } else {
                    $this->setType($value, $type);
                }
                $this->owner->$attribute = $value;
            }
        }
    }

    private function setType(&$value, $type)
    {
        settype($value, $type);
    }

    private function setMongoId(&$value)
    {
       if (\MongoId::isValid($value)) {
           $value = new \MongoId($value);
       } else {
           throw new TypeException();
       }
    }
}

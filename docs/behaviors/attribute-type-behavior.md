## AttributeTypeBehavior

### Description 
Class **AttributeTypeBehavior** convert attribute for all simple [types](https://github.com/yiisoft/yii2/blob/master/framework/base/Behavior.php) and mongoDb types.
Number and boolean type taken from the corresponding attribute validator

### Usage

Add behavior in model
```php
    public function behaviors()
    {
        return [
            [
                'class' => AttributeTypeBehavior::className(),
                'attributes' => [
                    '_id' => AttributeTypeBehavior::TYPE_MONGO_ID,
                    'attribute1' => [AttributeTypeBehavior::TYPE_ARRAY_OF => AttributeTypeBehavior::TYPE_INTEGER]
                ]
            ]
        ];
    }
```  

Default events `ActiveRecord::EVENT_BEFORE_INSERT`, `ActiveRecord::EVENT_BEFORE_UPDATE` 
  
##### Example add convert MongoDate type.
Add `attributes => ['date' => 'MongoDate']`
Add a method to the class AttributeTypeBehavior:
```php
    private function setMongoDate(&$value)
    {
        $value = new \MongoDate(strtotime($value));
    }
```
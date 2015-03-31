# yii2-behaviors
Extension of the class [yii\base\Behavior](https://github.com/yiisoft/yii2/blob/master/framework/base/Behavior.php)
***
###Class **AttributeTypeBehavior** to converts attribute for all simple [types](https://github.com/yiisoft/yii2/blob/master/framework/base/Behavior.php) and mongoDb types.   
Number and boolean type taken from the corresponding attribute validator
##### Example of a call from the model.
```php
    public function behaviors()
    {
        return [
            [
                'class' => AttributeTypeBehavior::className(),
                'attributes' => [
                    '_id' => 'MongoId',
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
***
###Class **TreeViewBehavior** to dynamically update the model attributes with the use of plug-in [jstree](https://github.com/vakata/jstree)
Events `ActiveRecord::EVENT_BEFORE_INSERT`, `ActiveRecord::EVENT_BEFORE_UPDATE`, `ActiveRecord::EVENT_BEFORE_DELETE`

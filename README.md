# yii2-mongodb-types
Расширение класса [yii\base\Behavior](https://github.com/yiisoft/yii2/blob/master/framework/base/Behavior.php)
для преобразования типов атрибутов модели mongoDb.
##### Преобразуемые типы.
* integer
* float
* boolean  

##### Вызов из модели.
```php
public function behaviors()
    {
        return [
            [
                'class' => AttributeTypeBehavior::className()
            ],
        ];
    }
```
##### Настройка событий.
```php
public function events()
    {
        return [
            ActiveRecord::EVENT_BEFORE_INSERT => 'convert',
            ActiveRecord::EVENT_BEFORE_UPDATE => 'convert',
        ];
    }
```
Ключом возвращаемого массива является поведением ([варианты](https://github.com/yiisoft/yii2/blob/master/framework/db/BaseActiveRecord.php)) при котором вызывается метод указанный в значении. 

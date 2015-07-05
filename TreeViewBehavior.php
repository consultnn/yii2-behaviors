<?php
/**
 * Created by PhpStorm.
 * User: st1gz
 * Date: 20.03.15
 * Time: 10:16
 */

namespace consultnn\behaviors;

use yii\base\Behavior;
use yii\db\ActiveRecord;

/**
 * Class TreeViewBehavior
 * @property \yii\db\ActiveRecord owner
 * @package consultnn\treeview
 */
class TreeViewBehavior extends Behavior
{
    public $parentAttribute = 'parent_id';
    public $positionAttribute = 'position';
    public $levelAttribute = 'level';

    public $events = [
        ActiveRecord::EVENT_BEFORE_DELETE => 'beforeDelete',
        ActiveRecord::EVENT_BEFORE_INSERT => 'beforeSave',
        ActiveRecord::EVENT_BEFORE_UPDATE => 'beforeSave',
    ];

    public function events()
    {
        return $this->events;
    }

    /**
     * @inheritdoc
     */
    public function beforeDelete()
    {
        $children = $this->getChildren();
        if (!empty($children)) {
            foreach ($children as $child) {
                $child->delete();
            }
        }
    }

    /**
     * @return bool
     */
    public function hasChildren()
    {
        return $this->owner->find()->andWhere([$this->parentAttribute => $this->owner->getPrimaryKey()])->count() !== 0;
    }

    /**
     * @return null|ActiveRecord[]
     */
    public function getChildren()
    {
        return $this->owner
            ->find()
            ->andWhere([$this->parentAttribute => $this->owner->getPrimaryKey()])
            ->orderBy('position')
            ->all();
    }

    /**
     * @return bool
     */
    public function hasParent()
    {
        return $this->owner->{$this->parentAttribute} !== null;
    }

    /**
     * @return null|ActiveRecord
     */
    public function getParent()
    {
        if ($this->hasParent()) {
            return $this->owner->findOne($this->owner->{$this->parentAttribute});
        } else {
            return null;
        }
    }

    /**
     * @return ActiveRecord[]|[]
     */
    public function getParents()
    {
        $parents = [];
        $parent = $this;
        while ($parent = $parent->getParent()) {
            $parents[] = $parent;
        }
        return $parents;
    }

    /**
     * @param string|null $parent
     * @param int $position
     * @return bool
     */
    public function move($parent = null, $position = null)
    {
        if ($parent == $this->owner->getPrimaryKey()) {
            return false;
        }

        $this->owner->{$this->parentAttribute} = $parent;
        $this->calculatePosition($position);

        return $this->owner->save();
    }

    public function beforeSave()
    {
        $this->calculatePosition();
        $this->setLevel();
    }

    public function setLevel()
    {
        if (!$this->owner->hasAttribute($this->levelAttribute)) {
            return;
        }
        
        if (empty($this->owner->parent_id)) {
            $this->owner->{$this->levelAttribute} = 1;
        } else {
            $this->owner->{$this->levelAttribute} = $this->owner->findOne($this->owner->parent_id)->{$this->levelAttribute} + 1;
        }
    }

    /**
     * @param int $position
     * @return float|int|mixed
     */
    public function calculatePosition($position = null)
    {
        if (!empty($this->owner->{$this->positionAttribute}) && $position === null) {
            return $this->owner->{$this->positionAttribute};
        } elseif (empty($position)) {
            return $this->setMinPosition();
        }

        $query = $this->owner->find();
        $query->andWhere([$this->parentAttribute => $this->owner->{$this->parentAttribute}]);
        if ($this->owner->getPrimaryKey()) {
            $query->andWhere(['NOT IN', $this->owner->primaryKey(), $this->owner->getPrimaryKey()]);
        }

        $brothers =$query->orderBy('position')->offset($position - 1)->limit(2)->all();
        switch (count($brothers)) {
            case 0:
                $this->setMinPosition();
                break;
            case 1:
                $this->owner->{$this->positionAttribute} = ceil($brothers[0]->{$this->positionAttribute} + 1);
                break;
            case 2:
                list($prev, $next) = $brothers;
                $rise = ($next->{$this->positionAttribute} - $prev->{$this->positionAttribute}) / 2;
                if ($rise == 0) {
                    $this->updatePositions();
                    $this->calculatePosition(null, $position);
                } else {
                    $this->owner->{$this->positionAttribute} = $prev->{$this->positionAttribute} + $rise;
                }
                break;
        }

        return $this->owner->{$this->positionAttribute};
    }

    /**
     * @return float|int
     */
    private function setMinPosition()
    {
        $min = $this->owner->find()->andWhere([$this->parentAttribute => $this->owner->getPrimaryKey()])->min('position');
        $minPosition = $min / 2;

        if (!$min) {
            $this->owner->{$this->positionAttribute} = 1;
        } elseif ($minPosition != 0) {
            $this->owner->{$this->positionAttribute} = $minPosition;
        } else {
            $this->updatePositions();
            $this->setMinPosition();
        }

        return $this->owner->{$this->positionAttribute};
    }

    private function updatePositions()
    {
        $position = 0;

        /** @var ActiveRecord[] $objects */
        $objects = $this->owner->find()->andWhere([$this->parentAttribute => $this->owner->getPrimaryKey()])->orderBy('position')->all();
        foreach ($objects as $object) {
            $object->updateAttributes(['position' => ++$position]);
        }
    }
}

<?php
/**
 * Created by PhpStorm.
 * User: st1gz
 * Date: 20.03.15
 * Time: 10:16
 */

namespace consultnn\behaviors\treeview;

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

    public $events = [
        ActiveRecord::EVENT_BEFORE_DELETE => 'beforeDelete',
        ActiveRecord::EVENT_BEFORE_INSERT => 'setPosition',
        ActiveRecord::EVENT_BEFORE_UPDATE => 'setPosition',
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
        /** @var ActiveRecord[] $children */
        $children = $this->owner->findAll([$this->parentAttribute => $this->owner->getPrimaryKey()]);
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
        return $this->owner->findOne([$this->parentAttribute => $this->owner->getPrimaryKey()]) != null;
    }

    /**
     * @return null|ActiveRecord[]
     */
    public function getChildren()
    {
        return $this->owner
            ->find()
            ->where([$this->parentAttribute => $this->owner->getPrimaryKey()])
            ->orderBy('position')
            ->all();
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

    public function setPosition($event)
    {
        $this->calculatePosition();
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
        $min = $this->owner->find()->where([$this->parentAttribute => $this->owner->getPrimaryKey()])->min('position');
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
        $objects = $this->owner->find()->where([$this->parentAttribute => $this->owner->getPrimaryKey()])->orderBy('position')->all();
        foreach ($objects as $object) {
            $object->updateAttributes(['position' => ++$position]);
        }
    }
}

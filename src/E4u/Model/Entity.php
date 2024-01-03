<?php
namespace E4u\Model;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\EntityManager,
    Doctrine\ORM\Event,
    Doctrine\ORM\Mapping\ClassMetadata,
    Doctrine\DBAL\Types\Type,
    Doctrine\Common\Collections\ArrayCollection,
    Doctrine\Common\Util\Debug,
    Laminas\Stdlib\ArrayUtils;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\PersistentCollection;
use E4u\Common\Variable;
use E4u\Exception\LogicException;

/**
 * @Annotation
 * @ORM\MappedSuperclass
 * @ORM\HasLifecycleCallbacks
 */
class Entity extends Base
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     */
    protected $id;

    /** @var EntityManager */
    protected static $em;

    /** validation errors */
    protected $_errors = [];

    /** is entity read-only */
    protected $_readonly = false;

    public function __construct($attributes = [])
    {
        $meta = $this->getClassMetadata();
        $associations = $meta->getAssociationNames();
        foreach ($associations as $association) {
            if ($meta->isCollectionValuedAssociation($association)) {
                $this->$association = new ArrayCollection();
            }
        }

        parent::__construct($attributes);
        if ($meta->hasField('created_at') && is_null($this->_get('created_at'))) {
            $this->setCreatedNow();
        }
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return int
     */
    public function id()
    {
        return $this->getId();
    }

    /**
     * Defined by ArrayAccess.
     *
     * @param string $offset
     * @return boolean
     */
    public function offsetExists($offset)
    {
        $method = self::propertyGetMethod($offset);
        if (method_exists($this, $method)) {
            return true;
        }

        $meta = $this->getClassMetadata();
        if ($meta->hasField($offset) || $meta->hasAssociation($offset)) {
            return true;
        }

        return false;
    }

    /**
     * @param \E4u\Form\Base $form
     * @return static
     */
    public function loadForm($form)
    {
        $meta = $this->getClassMetadata();
        $values = $form->getValues(array_merge($meta->getFieldNames(), $meta->getAssociationNames()));
        $this->loadArray($values);
        return $this;
    }

    /**
     * @param  boolean $useGetters - deprecated
     * @return array
     */
    public function toArray($useGetters = true)
    {
        $meta = $this->getClassMetadata();
        $array = [];
        foreach ($meta->getFieldNames() as $field) {
            $method = self::propertyGetMethod($field);
            $array[ $field ] = $useGetters
                ? $this->$method()
                : $this->$field;
        }

        return $array;
    }

    /**
     * @param  bool $showFields
     * @param  int  $maxLevel
     * @param  int  $currentLevel
     * @return string
     */
    public function showEntity($showFields = true, $maxLevel = 0, $currentLevel = 0)
    {
        $meta = $this->getClassMetadata();
        $string = str_repeat("\t", $currentLevel) . "#{$this->getId()} " . get_class($this);

        if ($showFields) {

            $fields = [];
            foreach ($meta->getFieldNames() as $field) {
                $fields[] = "'$field' => " . $this->_show($field);
            }

            foreach ($meta->getAssociationNames() as $field) {

                $value = '';
                if ($meta->isCollectionValuedAssociation($field)) {
                    if (is_null($this->$field)) {
                        $value = 'NULL';
                    }
                    else {
                        $values = [];

                        if (method_exists($this->$field, 'initialize')) {
                            $this->$field->initialize();
                        }

                        foreach ($this->$field as $val) {

                            if ($val instanceof Entity) {

                                $values[] = $maxLevel > $currentLevel
                                    ? "\n".$val->showEntity($showFields, $maxLevel, $currentLevel+1)."\n"
                                    : '#'.$val->getId();

                            }
                            elseif (method_exists($val, 'getId')) {
                                $values[] = '#'.$val->getId();
                            }

                        }

                        $value = get_class($this->$field).' [ '.join(', ', $values).' ]';
                    }
                }
                elseif (!is_null($this->$field)) {

                    $val = $this->$field;
                    if ($val instanceof Entity) {

                        $value = $maxLevel > $currentLevel
                            ? "\n".$val->showEntity($showFields, $maxLevel, $currentLevel+1)."\n"
                            : '#'.$val->getId();

                    }
                    elseif (method_exists($val, 'getId')) {
                        $value = '#'.$val->getId();
                    }

                }
                else {
                    $value = "NULL";
                }

                $fields[] = "'$field' => $value";
            }

            $string .= '([ '.join(', ', $fields).' ])';
        }

        return $string;
    }

    private function _show($field)
    {
        $value = $this[ $field ];

        if (null === $value) {
            return 'NULL';
        }

        $meta = $this->getClassMetadata();
        switch ($meta->getTypeOfField($field)) {
            case Type::DATETIME:
            case Type::DATE:
                return $value instanceof \DateTime
                    ? "'".$value->format('Y-m-d H:i:s')."'"
                    : "'{$value}'";

            case Type::BOOLEAN:
                return $value ? 'TRUE' : 'FALSE';

            case Type::INTEGER:
            case Type::DECIMAL:
            case Type::FLOAT:
            case Type::BIGINT:
            case Type::SMALLINT:
                return $value;

            case Type::STRING:
            case Type::TEXT:
                return "'" . mb_strimwidth($value, 0, 50, '...') . "'";

            default:
                return $meta->getTypeOfField($field);
        }
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->showEntity(true);
    }

    /**
     * Dumps the entire entity or a property of the entity,
     * using Doctrine\Common\Util\Debug::dump(), instead of normal var_dump()
     * because of very large recursive structure which is impossible
     * to render and read.
     *
     * @param  null|string $property
     * @return string
     */
    public function dump($property = null)
    {
        return is_null($property)
            ? Debug::dump($this)
            : Debug::dump($this->$property);
    }

    /**
     * @todo: powinno usuwać element z asocjacji (np. user->preferences)
     * Destroys entire entity.
     */
    public function destroy($now = true)
    {
        if (empty($this->getId())) {
            return;
        }

        self::getEM()->remove($this);
        if ($now) {
            self::getEM()->flush();
        }
    }

    /**
     * @param  string $property
     * @return boolean
     */
    protected function _has($property)
    {
        $meta = $this->getClassMetadata();
        if ($meta->isCollectionValuedAssociation($property)) {
            return count($this->$property) > 0;
        }

        return parent::_has($property);
    }

    /**
     * Automagical property setter for fields and associations.
     *
     * It works both with collections associations (OneToMany and ManyToMany)
     * and single value associations (OneToOne and ManyToOne).
     *
     * $value can be an integer (id of the entity), a string (fixture id),
     * an entity or array of those (only for collections).
     *
     * @see _call()
     * @param  string  $property        Association name
     * @param  mixed   $value           Value, entity, id of the entity of array of those
     * @param  boolean $keepConsistency Keep consistency for both sides of the association
     * @return static
     */
    protected function _set($property, $value, $keepConsistency = true)
    {
        $meta = $this->getClassMetadata();
        if ($meta->hasAssociation($property)) {
            // *-ToOne
            if ($meta->isSingleValuedAssociation($property)) {
                self::normalizeValue($value, $meta->getAssociationTargetClass($property));
                if (true == $keepConsistency) {
                    $this->_followAssociation($property, $value, 'add');
                }

                $this->$property = $value;
                return $this;
            }

            // *-ToMany
            if (!is_array($value)) {
                throw new LogicException(
                    sprintf('Value of %s::$%s is collection value association, thus it must be set to array, %s given.',
                        get_class($this), $property, Variable::getType($value)));
            }

            // TODO: remove reference from associated objects?
            $this->_normalizeProperty($property);
            $this->$property->clear();

            $method = self::propertyAddToMethod($property);
            foreach ($value as $item) {
                $this->$method($item, $keepConsistency);
            }

            return $this;
        }

        parent::_set($property, $value);
        return $this;
    }

    /**
     * @param  string $property
     * @param  mixed $value
     * @param  bool $keepConsistency
     * @return static
     */
    protected function _addTo($property, $value, $keepConsistency = true)
    {
        $meta = $this->getClassMetadata();
        if (!$meta->isCollectionValuedAssociation($property)) {
            throw new LogicException(
                sprintf('Undefined property %s::$%s.',
                    get_class($this), $property));
        }

        // multiple values support: $user->addToGroups([1, 2, 3])
        if (is_array($value) && !ArrayUtils::isHashTable($value)) {
            foreach ($value as $key => $val) {
                $this->_addTo($property, $val, $keepConsistency);
            }

            return $this;
        }

        // deal with the other end of bi-directional association
        self::normalizeValue($value, $meta->getAssociationTargetClass($property));
        if (true == $keepConsistency) {
            $this->_followAssociation($property, $value, 'add');
        }

        $this->_normalizeProperty($property);
        if (!$this->$property->contains($value)) {
            $map = $meta->getAssociationMapping($property);
            if (isset($map['indexBy'])) {
                $index = $value[ $map['indexBy'] ];
                $this->$property->set($index, $value);
            }
            else {
                $this->$property->add($value);
            }
        }

        return $this;
    }

    /**
     * @param  string $property
     * @param  mixed $value
     * @param  bool $keepConsistency
     * @return static
     */
    protected function _delFrom($property, $value, $keepConsistency = true)
    {
        $meta = $this->getClassMetadata();
        if (!$meta->isCollectionValuedAssociation($property)) {
            throw new LogicException(
                sprintf('You can run _delFrom() only for collection value associations, %s::%s is not.',
                    get_class($this), $property));
        }

        // multiple values support: $user->delFromGroups([1, 2, 3])
        if (is_array($value) && !ArrayUtils::isHashTable($value)) {
            foreach ($value as $key => $val) {
                $this->_delFrom($property, $val, $keepConsistency);
            }

            return $this;
        }

        // deal with the other end of bi-directional association
        self::normalizeValue($value, $meta->getAssociationTargetClass($property));
        if (true == $keepConsistency) {
            $this->_followAssociation($property, $value, 'remove');
        }

        $this->_normalizeProperty($property);
        if ($this->$property->contains($value)) {
            $this->$property->removeElement($value);
        }

        return $this;
    }

    /**
     * Invokes the other end of the association, to keep consistency at runtime.
     *
     * ManyToOne:  $player->setGame(1)           => $game->addToPlayers($player)
     * ManyToMany: $user->addToGroups(1)         => $group->addToUsers($user)
     * OneToMany:  $player->addToActions(1)      => $action->setPlayer($player)
     * OneToMany:  $player->removeFromActions(1) => $action->unsetPlayer()
     * OneToOne:   $user->setProfile(1)          => $profile->setUser($user)
     *
     * @param  string $property Association name
     * @param  Base $value
     * @param  string $operation add|remove
     * @return static
     */
    protected function _followAssociation($property, $value, $operation = 'add')
    {
        if ($value instanceof Base) {
            $meta = $this->getClassMetadata();
            $association = $meta->getAssociationMapping($property);
            $referencedProperty = $meta->isAssociationInverseSide($property)
                ? $association['mappedBy']
                : $association['inversedBy'];

            if (!empty($referencedProperty)) {
                switch ($association['type'])
                {
                    case ClassMetadata::MANY_TO_MANY:
                    case ClassMetadata::MANY_TO_ONE:
                        $method = ($operation == 'add')
                            ? self::propertyAddToMethod($referencedProperty)
                            : self::propertyDelFromMethod($referencedProperty);
                        $value->$method($this, false);
                        break;
                    case ClassMetadata::ONE_TO_MANY:
                    case ClassMetadata::ONE_TO_ONE:
                        if ($operation == 'add') {
                            $method = self::propertySetMethod($referencedProperty);
                            $value->$method($this, false);
                        }
                        else {
                            $method = self::propertyUnsetMethod($referencedProperty);
                            $value->$method();
                        }

                        break;
                }

            }
        }

        return $this;
    }

    /**
     * Get a reference instead of actual finding an entity (performing SQL query).
     * Useable only if you are *sure* the entity with given ID exists, otherwise
     * the proxy will throw exception upon initialization.
     * @see find()
     *
     * @param  int $id
     * @return static
     */
    public static function getReference($id)
    {
        return self::getEM()->getReference(static::class, $id);
    }

    /**
     * If $value is int|string, returns reference to the instance of $targetEntity.
     * If $value is associative array, create new instance of $targetEntity.
     * Otherwise returns the value itself.
     *
     * @param  Entity|int|string[]|string $value
     * @param  string $targetEntity
     */
    public static function normalizeValue(&$value, $targetEntity)
    {
        if ($value instanceof $targetEntity) {
            return;
        }

        if (($value === "") || ($value === 0) || ($value === null)) {
            $value = null;
            return;
        }

        if (is_string($value) || is_int($value)) {
            $value = self::getEM()->getReference($targetEntity, $value);
            return;
        }

        if (ArrayUtils::isHashTable($value)) {

            $meta = self::getEM()->getClassMetadata($targetEntity);
            if ($meta->inheritanceType == ClassMetadata::INHERITANCE_TYPE_SINGLE_TABLE) {

                $discriminatorColumn = $meta->discriminatorColumn['name'];

                if (isset($value[ $discriminatorColumn ])) {
                    $type = $value[ $discriminatorColumn ];
                    unset($value[ $discriminatorColumn ]);

                    if (!class_exists($type)) {
                        $type = $meta->discriminatorMap[$type];
                    }

                    $value = new $type($value);
                    return;
                }
            }

            $value = new $targetEntity($value);
            return;
        }

        throw new LogicException(
            sprintf("%s expected, %s given.",
                $targetEntity, Variable::getType($value)));
    }

    /**
     * Normalize property of collection association to ArrayCollection instance.
     *
     * @param  string $property Association name
     * @return static
     */
    protected function _normalizeProperty($property)
    {
        if (!$this->$property instanceof \Doctrine\Common\Collections\Collection) {
            if (is_array($this->$property)) {
                $this->$property = new ArrayCollection($this->$property);
            }
            elseif (is_null($this->$property)) {
                $this->$property = new ArrayCollection();
            }
            else {
                throw new LogicException(
                    sprintf('%s::$%s must be null, array or ArrayCollection, %s given.',
                        get_class($this), $property, Variable::getType($this->$property)));
            }
        }

        return $this;
    }

    /**
     * Returns the ORM metadata descriptor for a class.
     *
     * @return ClassMetadata
     */
    public function getClassMetadata()
    {
        $className = get_class($this);
        return self::getEM()->getClassMetadata($className);
    }

    /**
     * Indicates if the entity is allowed to be
     * persisted / updated / removed.
     *
     * @return bool
     */
    public function isReadOnly()
    {
        return $this->_readonly || $this->getClassMetadata()->isReadOnly;
    }

    /**
     * Disallow the entity to be persisted / updated / removed.
     *
     * @param  bool
     * @return static
     */
    public function setReadOnly($flag = true)
    {
        $this->_readonly = (bool)$flag;
        return $this;
    }

    /** @ORM\PrePersist */
    public function prePersist()
    {
        if ($this->isReadOnly()) {
            throw new Exception(sprintf(
                "%s is marked as read-only, persist denied.",
                $this->showEntity(false)));
        }

        $this->setUpdatedNow();
        $this->validate();
    }

    /**
     * @ORM\PreUpdate
     * @param Event\PreUpdateEventArgs $event
     */
    public function preUpdate(Event\PreUpdateEventArgs $event)
    {
        $changes = $event->getEntityChangeSet();
        foreach ($changes as $key => $values) {
            if (($values[0] instanceof \DateTime) && ($values[0] == $values[1])) {
                $event->setNewValue($key, $values[0]);
                unset($changes[$key]);
            }
        }

        if ($this->isReadOnly() && !empty($changes)) {
            throw new Exception(sprintf(
                "%s is marked as read-only, update denied.",
                $this->showEntity(false)));
        }

        $this->setUpdatedNow();
        $this->validate();
    }

    /** @ORM\PreRemove */
    public function preRemove()
    {
        if ($this->isReadOnly()) {
            throw new Exception(sprintf(
                "%s is marked as read-only, removal denied.",
                $this->showEntity(false)));
        }
    }

    /** @ORM\PostLoad */
    public function postLoad() {}

    /** @ORM\PostPersist */
    public function postPersist() {}

    /** @ORM\PostRemove */
    public function postRemove() {}

    /**
     * @ORM\PostUpdate
     * @param Event\LifecycleEventArgs $event
     */
    public function postUpdate(Event\LifecycleEventArgs $event) {}

    /**
     * @return $this
     */
    public function setUpdatedNow()
    {
        if (property_exists($this, 'updated_at')) {
            $this->updated_at = new \DateTime();
        }

        return $this;
    }

    /**
     * @return $this
     */
    public function setCreatedNow()
    {
        if (property_exists($this, 'created_at')) {
            $this->created_at = new \DateTime();
        }

        return $this;
    }

    /**
     * @return boolean
     */
    public function valid()
    {
        $this->_errors = [];
        $meta = $this->getClassMetadata();

        foreach ($meta->getFieldNames() as $field) {
            if (!$meta->isNullable($field)
                && is_null($this->$field)
                && !in_array($field, $meta->getIdentifier())) {
                $this->addError(sprintf('%s::$%s cannot be null.', get_class($this), $field), $field);
            }
        }

        return empty($this->_errors);
    }

    /**
     * @param  string $field
     * @return array|string
     */
    public function getErrors($field = null)
    {
        if (!is_null($field)) {
            return isset($this->_errors[ $field ])
                ? $this->_errors[ $field ]
                : null;
        }

        return $this->_errors;
    }

    /**
     *
     * @param  string $message
     * @param  string $field
     * @return static
     */
    public function addError($message, $field = '')
    {
        empty($field) ?
            $this->_errors[] = $message :
            $this->_errors[ $field ] = $message;
        return $this;
    }

    /*
     * @return static
     * @throws Exception when entity is not valid
     */
    public function validate()
    {
        if (!$this->valid()) {
            throw new Exception(join(" \n", $this->_errors));
        }

        return $this;
    }

    /**
     * Is current entity managed by Entity Manager?
     *
     * @return boolean
     */
    public function isManaged()
    {
        return self::getEM()->contains($this);
    }

    /**
     * Saves the entity (and only it),
     * if it is managed by EM and has ID.
     *
     * @return static
     * @throws Exception when entity is not valid
     */
    public function update()
    {
        if ($this->isManaged() && $this->id()) {
            self::getEM()->flush($this);
        }

        return $this;
    }

    public function refresh()
    {
        if ($this->isManaged()) {
            self::getEM()->refresh($this);
        }

        return $this;
    }

    public function doSave(array &$visited)
    {
        $oid = spl_object_hash($this);
        if (isset($visited[ $oid ])) {
            return false; // Prevent infinite recursion
        }

        $visited[ $oid ] = $this; // Mark visited

        $meta = $this->getClassMetadata();
        foreach ($meta->getAssociationMappings() as $property => $map) {
            if ($map['isCascadePersist']) {
                if ($meta->isSingleValuedAssociation($property)) {
                    if ($this->$property instanceof Entity) {
                        $this->$property->doSave($visited);
                    }
                }
                elseif ($meta->isCollectionValuedAssociation($property)) {
                    if (!$this->$property instanceof PersistentCollection
                        || $this->$property->isDirty()
                        || $this->$property->isInitialized()) {

                        foreach ($this->$property as $key => $value) {
                            if ($value instanceof Entity) {
                                $value->doSave($visited);
                            }
                        }

                    }
                }
            }
        }

        return true;
    }

    /**
     * @return static
     * @throws Exception when entity is not valid
     */
    public function save()
    {
        $visited = [];
        $this->doSave($visited);

        self::getEM()->persist($this);
        self::getEM()->flush($visited);

        return $this;
    }

    /**
     * @param array $criteria
     * @param array|string $orderBy
     * @return static
     */
    public static function findOneBy($criteria, $orderBy = null)
    {
        $result = self::findBy($criteria, $orderBy, 1);
        return empty($result)
            ? null
            : $result[0];
    }

    /**
     * @param array $orderBy
     * @return static[]
     */
    public static function findAll($orderBy = null)
    {
        return self::findBy([], $orderBy);
    }

    /**
     *
     * @param array $criteria
     * @param array|string $orderBy
     * @param int $limit
     * @param int $offset
     * @return static[]
     */
    public static function findBy($criteria, $orderBy = null, $limit = null, $offset = null)
    {
        if (is_string($orderBy)) {
            $orderBy = [ $orderBy => 'ASC' ];
        }

        return self::getRepository()->findBy($criteria, $orderBy, $limit, $offset);
    }

    /**
     *
     * @return EntityRepository The repository class.
     */
    public static function getRepository()
    {
        return self::getEM()->getRepository(get_called_class());
    }

    /**
     * @return EntityManager
     */
    public static function getEM()
    {
        if (null == self::$em) {
            self::$em = \E4u\Loader::getDoctrine();
        }

        return self::$em;
    }

    /**
     * Convient way of calling repository methods
     * My\Model::findBy(...), My\Model::findOneBy(...)
     * and magic finders, like My\Model::findByUser(...)
     *
     * @param  string $method
     * @param  array $argv
     * @return array
     */
    public static function __callStatic($method, $argv)
    {
        $repository = self::getRepository();
        return call_user_func_array([$repository, $method], $argv);
    }

    /**
     * Finds a record by its id.
     * If you are sure a record with given id exists,
     * you may want to @see getReference() instead.
     *
     * @param   int $id
     * @return  static|object|null
     */
    public static function find($id)
    {
        return self::getRepository()->find($id);
    }

    /**
     * @param  array $attributes
     * @return static
     */
    public static function create($attributes)
    {
        $instance = new static($attributes);
        $instance->save();
        return $instance;
    }

    private function _mergeCollection($assoc, array &$visited)
    {
        /** @var Entity[] $collection */
        $collection = $this->$assoc;
        foreach ($collection as $key => $v) {
            if (!is_null($v) && $v instanceof Entity) {
                $collection[ $key ]->cascadeMerge($visited);
                if ($v->id()) {
                    $collection[ $key ] = self::getEM()->merge($v);
                }

            }
        }

        return $this;
    }

    private function _mergeAssociation($assoc, array &$visited)
    {
        if ($this->$assoc instanceof \Doctrine\ORM\Proxy\Proxy) {
            if (!$this->$assoc->__isInitialized()) {
                self::getEM()->getProxyFactory()->resetUninitializedProxy($this->$assoc);
                return $this;
            }
        }

        if ($this->$assoc instanceof Entity) {
            $this->$assoc->cascadeMerge($visited);
            if ($this->$assoc->id() && !self::getEM()->contains($this->$assoc)) {
                $this->$assoc = self::getEM()->merge($this->$assoc);
            }

        }

        return $this;
    }

    /**
     * To use after deserialization:
     * <code>
     * $visited = [];
     * $detachedEntity = $_SESSION['entity'];
     * $detachedEntity->cascadeMerge($visited);
     * if ($detachedEntity->id()) {
     *      $managedEntity = Entity::getEM()->merge($detachedEntity);
     *      $_SESSION['entity'] = $managedEntity;
     * }
     * </code>
     *
     * @param  array $visited
     * @return static|object
     */
    public function cascadeMerge(array &$visited)
    {
        $oid = spl_object_hash($this);
        if (isset($visited[ $oid ])) {
            return $visited[ $oid ]; // Prevent infinite recursion
        }

        $visited[ $oid ] = $this;
        $meta = $this->getClassMetadata();
        foreach ($meta->getAssociationNames() as $assoc) {
            if ($meta->isCollectionValuedAssociation($assoc)) {
                $this->_mergeCollection($assoc, $visited);
            }
            elseif (!is_null($this->$assoc)) {
                $this->_mergeAssociation($assoc, $visited);
            }
        }

        return $this->id() && !self::getEM()->contains($this)
            ? self::getEM()->merge($this)
            : $this;
    }
}

<?php
namespace E4u\Authentication\Identity;

use Doctrine\ORM\EntityRepository;
use E4u\Authentication\Exception;
use E4u\Authentication\Identity;
use E4u\Common\Variable;
use E4u\Exception\ConfigException;
use E4u\Model\Entity;

/**
 * Abstract class for database-based Identity implementation.
 *
 * @MappedSuperclass
 */
abstract class User extends Entity implements Identity
{
    const MAX_PASSWORD_LENGTH = 48;

    /** @Column(type="string", unique=true, nullable=true) */
    protected $login;

    /** @Column(type="string", length=255, nullable=true) */
    protected $encrypted_password;

    /** @Column(type="boolean") */
    protected $active = true;

    /** @Column(type="datetime") */
    protected $created_at;

    /** @Column(type="datetime", nullable=true) */
    protected $updated_at;

    /**
     * Encrypts password.
     * @link http://pl1.php.net/manual/en/book.password.php
     *
     * @param  $password
     * @return $this
     */
    public function setPassword($password)
    {
        if (strlen($password) > self::MAX_PASSWORD_LENGTH) {
            throw new Exception\PasswordTooLongException(sprintf('Maximum password length is %d characters.', self::MAX_PASSWORD_LENGTH));
        }

        if (!empty($password)) {
            $this->encrypted_password = password_hash($password, PASSWORD_DEFAULT);
        }

        return $this;
    }

    /**
     * Security
     * @return null
     */
    public function getPassword()
    {
        return null;
    }

    /**
     * Security
     * @return null
     */
    public function getEncryptedPassword()
    {
        return null;
    }

    /**
     * @param  string $login
     * @return $this
     */
    public function setLogin($login)
    {
        $this->login = $login;
        return $this;
    }

    /**
     * @return bool
     */
    public function isActive()
    {
        return (bool)$this->active;
    }

    /**
     * @param  bool $active
     * @return $this
     */
    public function setActive($active)
    {
        $this->active = (bool)$active;
        return $this;
    }

    /**
     * Implements Identity
     *
     * @return int
     */
    public function id()
    {
        return $this->id;
    }

    /**
     * Implements Identity
     *
     * @return string
     */
    public function getLogin()
    {
        return $this->login;
    }

    /**
     * Implements Identity
     *
     * @return string
     */
    public function getCookie()
    {
        return null;
    }

    /**
     * Implements Identity. This method should be overwritten by extending
     * class to provide necessary level of access-control (like groups,
     * privileges, etc.).
     *
     * @param  int $privilege
     * @return boolean
     */
    public function hasPrivilege($privilege)
    {
        return true;
    }

    /**
     * Checks plain text password against the encrypted password from database.
     *
     * @param  string $password
     * @return boolean
     */
    public function verifyPassword($password)
    {
        return password_verify($password, $this->encrypted_password);
    }

    public function valid()
    {
        $login = $this->getLogin();

        if (empty($this->id) && !empty($login)) {
            $test = static::findOneBy([ 'login' => $login ]);
            if (!empty($test)) {
                if ($this->id != $test->id()) {
                    $this->_errors['login'] = 'User with this login already exists.';
                    return false;
                }
            }
        }

        return parent::valid();
    }

    /**
     * @return null
     */
    public function getLocale()
    {
        return null;
    }

    /**
     * Implements Identity
     * Returns instance of a User if login/password match.
     *
     * @param  string $login
     * @param  string $password
     * @return User
     * @throws Exception\AuthenticationException
     */
    public static function login($login, $password)
    {
        if (empty($login)) {
            throw new Exception\UserNotFoundException();
        }

        $repository = static::getRepository();
        $user = $repository->findOneByLogin($login);
        if (null === $user) {
            throw new Exception\UserNotFoundException();
        }

        if (!$user instanceof Identity) {
            throw new ConfigException(sprintf(
                'User#findOneByLogin() should return Identity, %s returned instead.',
                Variable::getType($user)));
        }

        if (!$user->verifyPassword($password)) {
            throw new Exception\InvalidPasswordException();
        }

        if (!$user->isActive()) {
            throw new Exception\UserNotActiveException();
        }

        return $user;
    }

    /**
     * Implements Identity
     * @return User
     */
    public static function findByID($id)
    {
        return self::find($id);
    }

    /**
     * Implements Identity
     * @return null
     */
    public static function findByCookie($cookie)
    {
        return null;
    }

    /**
     * @return Repository|EntityRepository
     */
    public static function getRepository()
    {
        return self::getEM()->getRepository(get_called_class());
    }
}

<?php
namespace E4u\Authentication\Identity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\EntityRepository;
use E4u\Authentication\Exception;
use E4u\Authentication\Identity;
use E4u\Common\Variable;
use E4u\Exception\ConfigException;
use E4u\Model\Entity;

/**
 * Abstract class for database-based Identity implementation.
 *
 * @ORM\MappedSuperclass
 */
abstract class User extends Entity implements Identity
{
    const int
        MAX_PASSWORD_LENGTH = 48;

    /** @ORM\Column(type="string", unique=true, nullable=true) */
    protected ?string $login;

    /** @ORM\Column(type="string", length=255, nullable=true) */
    protected ?string $encrypted_password;

    /** @ORM\Column(type="boolean") */
    protected bool $active = true;

    /** @ORM\Column(type="datetime") */
    protected \DateTime $created_at;

    /** @ORM\Column(type="datetime", nullable=true) */
    protected ?\DateTime $updated_at;

    /**
     * Encrypts password.
     * @link http://pl1.php.net/manual/en/book.password.php
     */
    public function setPassword(string $password): static
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
     */
    public function getPassword(): null
    {
        return null;
    }

    /**
     * Security
     */
    public function getEncryptedPassword(): null
    {
        return null;
    }

    public function setLogin(?string $login): static
    {
        $this->login = $login;
        return $this;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): static
    {
        $this->active = $active;
        return $this;
    }

    /**
     * Implements Identity
     */
    public function id(): int
    {
        return $this->id;
    }

    /**
     * Implements Identity
     */
    public function getLogin(): ?string
    {
        return $this->login;
    }

    /**
     * Implements Identity
     */
    public function getCookie(): ?string
    {
        return null;
    }

    /**
     * Implements Identity. This method should be overwritten by extending
     * class to provide necessary level of access-control (like groups,
     * privileges, etc.).
     */
    public function hasPrivilege(int $privilege): bool
    {
        return true;
    }

    /**
     * Checks plain text password against the encrypted password from database.
     */
    public function verifyPassword(string $password): bool
    {
        return password_verify($password, $this->encrypted_password);
    }

    public function valid(): bool
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

    public function getLocale(): ?string
    {
        return null;
    }

    /**
     * Implements Identity
     * Returns instance of a User if login/password match.
     */
    public static function login(string $login, string $password): static
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
            throw new Exception\InvalidPasswordException()->setUser($user);
        }

        if (!$user->isActive()) {
            throw new Exception\UserNotActiveException()->setUser($user);
        }

        return $user;
    }

    /**
     * Implements Identity
     */
    public static function findByID(int $id): ?static
    {
        return self::find($id);
    }

    /**
     * Implements Identity
     */
    public static function findByCookie(string $cookie): ?static
    {
        return null;
    }

    public static function getRepository(): EntityRepository
    {
        return self::getEM()->getRepository(get_called_class());
    }
}

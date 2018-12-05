<?php

namespace console\components;

use yii\base\Component;
use yii\base\InvalidConfigException;
use yii\base\InvalidValueException;
use yii\web\IdentityInterface;

/**
 * User is the class for the `user` console application component that manages the user authentication status.
 */
class User extends Component
{
    /**
     * @var string the class name of the [[identity]] object.
     */
    public $identityClass;

    public $enableAutoLogin = false;

    /**
     * Initializes the application component.
     */
    public function init()
    {
        parent::init();

        if ($this->identityClass === null) {
            throw new InvalidConfigException('User::identityClass must be set.');
        }
    }

    private $_identity = null;

    /**
     * Returns the identity object associated with the currently logged-in user.
     * When [[enableSession]] is true, this method may attempt to read the user's authentication data
     * stored in session and reconstruct the corresponding identity object, if it has not done so before.
     * This is only useful when [[enableSession]] is true.
     * @return IdentityInterface|null the identity object associated with the currently logged-in user.
     * `null` is returned if the user is not logged in (not authenticated).
     * @see login()
     * @see logout()
     */
    public function getIdentity()
    {
        return $this->_identity;
    }

    /**
     * Sets the user identity object.
     *
     * Note that this method does not deal with session or cookie. You should usually use [[switchIdentity()]]
     * to change the identity of the current user.
     *
     * @param IdentityInterface|null $identity the identity object associated with the currently logged user.
     * If null, it means the current user will be a guest without any associated identity.
     * @throws InvalidValueException if `$identity` object does not implement [[IdentityInterface]].
     */
    public function setIdentity($identity)
    {
        if ($identity instanceof IdentityInterface) {
            $this->_identity = $identity;
        } elseif ($identity === null) {
            $this->_identity = null;
        } else {
            throw new InvalidValueException('The identity object must implement IdentityInterface.');
        }
    }

    /**
     * Logs in a user.
     *
     * @param IdentityInterface $identity the user identity (which should already be authenticated)
     * @return bool whether the user is logged in
     */
    public function login(IdentityInterface $identity)
    {
        $this->setIdentity($identity);

        return !$this->getIsGuest();
    }

    /**
     * Logs out the current user.
     * @return bool whether the user is logged out
     */
    public function logout()
    {
        $identity = $this->getIdentity();
        if ($identity !== null) {
            $this->setIdentity(null);
        }

        return $this->getIsGuest();
    }

    /**
     * Returns a value indicating whether the user is a guest (not authenticated).
     * @return bool whether the current user is a guest.
     * @see getIdentity()
     */
    public function getIsGuest()
    {
        return $this->getIdentity() === null;
    }

    /**
     * Returns a value that uniquely represents the user.
     * @return string|int the unique identifier for the user. If `null`, it means the user is a guest.
     * @see getIdentity()
     */
    public function getId()
    {
        $identity = $this->getIdentity();

        return $identity !== null ? $identity->getId() : null;
    }
}

<?php declare(strict_types=1);

namespace AdminBundle\Entity;

use FOS\UserBundle\Model\User as BaseUser;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity
 * @ORM\Table(name="fos_user")
 */
class User extends BaseUser
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    // added second & 3rd assert
    /**
     * @ORM\Column(type="string", name="display_name", nullable=true)
     * @Assert\Length(max=100, groups={"Registration", "Profile"})
     * @Assert\Length(min=3, groups={"Registration", "Profile"})
     * @Assert\NotBlank
     *
     * @var string|null
     */
    protected $displayName;

    /**
     * @ORM\Column(type="string", name="api_key", nullable=true)
     *
     * @var UuidInterface
     */
    protected $apiKey;

    /**
     * @var bool
     */
    protected $resetApiKey;

    /**
     * @throws \Exception
     */
    public function __construct()
    {
        parent::__construct();

        $this->doResetApiKey();
    }

    /**
     * @return null|string
     */
    public function getDisplayName() : ?string
    {
        return $this->displayName;
    }

    /**
     * @param null|string $displayName
     * @return void
     */
    public function setDisplayName(?string $displayName) : void
    {
        $this->displayName = $displayName;
    }

    /**
     * @return null|string
     */
    public function getApiKey() : ?string
    {
        return $this->apiKey;
    }

    /**
     * @param bool $resetApiKey
     */
    public function setResetApiKey(bool $resetApiKey = true) : void
    {
        $this->resetApiKey = $resetApiKey;
    }

    /**
     * @return bool
     */
    public function getResetApiKey() : bool
    {
        return true === $this->resetApiKey;
    }

    /**
     * @throws \Exception
     */
    public function doResetApiKey() : void
    {
        $this->apiKey = Uuid::uuid4()->toString();
    }
}
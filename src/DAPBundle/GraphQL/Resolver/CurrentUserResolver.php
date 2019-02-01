<?php declare(strict_types=1);

namespace DAPBundle\GraphQL\Resolver;

use AdminBundle\Entity\User;
use Overblog\GraphQLBundle\Definition\Resolver\AliasedInterface;
use Overblog\GraphQLBundle\Definition\Resolver\ResolverInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class CurrentUserResolver implements
    ResolverInterface,
    AliasedInterface
{
    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @param TokenStorageInterface $tokenStorage
     */
    public function __construct(TokenStorageInterface $tokenStorage)
    {
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * {@inheritdoc}
     */
    public function resolve()
    {
        if ((null === $token = $this->tokenStorage->getToken()) || !$token instanceof TokenInterface) {
            return null;
        }

        $user = $token->getUser();

        if (!is_object($user) || !$user instanceof User) {
            return null;
        }

        return $user;
    }

    /**
     * {@inheritdoc}
     */
    public static function getAliases(): array
    {
        return [
            'resolve' => 'CurrentUser'
        ];
    }
}

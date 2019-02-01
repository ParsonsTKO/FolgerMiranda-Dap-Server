<?php declare(strict_types=1);

namespace AppBundle\Event;

use AdminBundle\Entity\User;
use FOS\UserBundle\Event\FilterUserResponseEvent;
use FOS\UserBundle\Event\UserEvent;
use FOS\UserBundle\FOSUserEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Http\SecurityEvents;

class UserRedirectSubscriber implements EventSubscriberInterface
{
    private const ROUTE_LOGIN                   = 'fos_user_security_login';
    private const ROUTE_LOGIN_CHECK             = 'fos_user_security_check';
    private const ROUTE_REGISTER                = 'fos_user_registration_register';
    private const ROUTE_REGISTER_CONFIRM        = 'fos_user_registration_confirm';
    private const ROUTE_PROFILE                 = 'fos_user_profile_show';
    private const ROUTE_RESET_PASSWORD          = 'fos_user_resetting_request';
    private const ROUTE_RESET_PASSWORD_RESET    = 'fos_user_resetting_reset';

    private const ROUTES = [
        self::ROUTE_LOGIN,
        self::ROUTE_REGISTER,
        self::ROUTE_REGISTER_CONFIRM,
        self::ROUTE_PROFILE,
        self::ROUTE_RESET_PASSWORD,
        self::ROUTE_RESET_PASSWORD_RESET,
    ];

    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @var string
     */
    private $clientCallbackUrl;

    /**
     * @param TokenStorageInterface $tokenStorage
     * @param string $clientCallbackUrl
     */
    public function __construct(
        TokenStorageInterface $tokenStorage,
        string $clientCallbackUrl
    ) {
        $this->tokenStorage = $tokenStorage;
        $this->clientCallbackUrl = $clientCallbackUrl;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::REQUEST               => [
                ['redirectIfAlreadyLoggedIn', 10],
                ['setSession', 20],
            ],
            SecurityEvents::INTERACTIVE_LOGIN       => 'redirectAfterLogin',
            FOSUserEvents::SECURITY_IMPLICIT_LOGIN  => 'redirectAfterRegistrationConfirmed',
        ];
    }

    public function setSession(GetResponseEvent $event)
    {
        $request = $event->getRequest();

        if (!in_array($request->attributes->get('_route'), self::ROUTES)) {
            return;
        }

        if (!$request->query->has('_redirect') && !$request->request->has('_redirect')) {
            return;
        }

        $request->getSession()->set('_security.redirect', $request->get('_redirect'));
    }

    /**
     * @param GetResponseEvent $event
     * @return void
     */
    public function redirectIfAlreadyLoggedIn(GetResponseEvent $event)
    {
        $request = $event->getRequest();

        if (self::ROUTE_LOGIN !== $request->attributes->get('_route')) {
            return;
        }

        if (null === $user = $this->getUserFromTokenStorage()) {
            return;
        }

        $this->redirectUser($request, $user);
    }

    /**
     * @param InteractiveLoginEvent $event
     */
    public function redirectAfterLogin(InteractiveLoginEvent $event)
    {
        $request = $event->getRequest();

        if (!in_array($request->attributes->get('_route'), [
            self::ROUTE_LOGIN,
            self::ROUTE_LOGIN_CHECK,
        ])) {
            return;
        }

        /** @var User $user */
        $user = $event->getAuthenticationToken()->getUser();

        $this->redirectUser($request, $user);
    }

    /**
     * @param UserEvent $event
     * @throws \Exception
     */
    public function redirectAfterRegistrationConfirmed(UserEvent $event)
    {
        if (null === $user = $this->getUserFromTokenStorage()) {
            return;
        }

        $this->redirectUser($event->getRequest(), $user);
    }

    /**
     * @return null|User
     */
    private function getUserFromTokenStorage()
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
     * @param Request $request
     * @param User|UserInterface|object $user
     */
    private function redirectUser(Request $request, User $user) : void
    {
        if (null === $user->getApiKey()) {
            // Log and handle
            throw new \Exception('user has no api key');
        }

        if ($request->getSession()->has('_security.redirect')) {
            $redirect = sprintf(
                'Location: %s?api-key=%s&email=%s&displayname=%s&username=%s',
                $this->clientCallbackUrl,
                $user->getApiKey(),
                $user->getEmail(),
                $user->getDisplayName(),
                $user->getUsername()
            );

            $request->getSession()->remove('_security.redirect');

            header($redirect, false, 302);

            exit;
        }
    }
}
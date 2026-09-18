<?php

namespace App\Security;

use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

final class ApiTokenAuthenticator extends AbstractAuthenticator
{
    public function __construct(private UserRepository $users)
    {
    }

    public function supports(Request $request): bool
    {
        return str_starts_with($request->headers->get('Authorization', ''), 'Bearer ');
    }

    public function authenticate(Request $request): Passport
    {
        $token = substr($request->headers->get('Authorization', ''), strlen('Bearer '));

        if ($token === '') {
            throw new CustomUserMessageAuthenticationException('No API token provided.');
        }

        $user = $this->users->findOneByApiToken($token);
        if (!$user) {
            throw new CustomUserMessageAuthenticationException('Invalid API token.');
        }

        return new SelfValidatingPassport(new UserBadge($token, fn () => $user));
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
    {
        return new JsonResponse(['error' => 'unauthorized', 'message' => $exception->getMessage()], 401);
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return null;
    }
}

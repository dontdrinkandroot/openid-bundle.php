<?php

namespace Dontdrinkandroot\OpenIdBundle\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Dontdrinkandroot\Common\Asserted;
use Dontdrinkandroot\OpenIdBundle\Service\Nonce\NonceServiceInterface;
use Exception;
use Lcobucci\JWT\Encoding\JoseEncoder;
use Lcobucci\JWT\Token\Parser;
use Lcobucci\JWT\Token\RegisteredClaims;
use Lcobucci\JWT\UnencryptedToken;
use League\Bundle\OAuth2ServerBundle\Model\AccessToken;
use League\Bundle\OAuth2ServerBundle\Model\RefreshToken;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

use function preg_replace;
use function trim;

class LogoutAction extends AbstractController
{
    public function __construct(
        private readonly ManagerRegistry $registry,
        private readonly NonceServiceInterface $nonceService
    ) {
    }

    public function __invoke(Request $request): Response
    {
        $idTokenHint = $request->query->get('id_token_hint');
        if (null !== $idTokenHint) {
            $idToken = Asserted::instanceOf((new Parser(new JoseEncoder()))->parse($idTokenHint), UnencryptedToken::class);
            $alg = $idToken->headers()->get('alg');
            $nonceClaim = $idToken->claims()->get('nonce');
            $subClaim = $idToken->claims()->get('sub');

            // TODO: Verify signature

            $accessTokenId = $this->nonceService->findAccessTokenIdByNonce($nonceClaim);
            if (null === $accessTokenId) {
                throw new BadRequestHttpException('Invalid nonce');
            }

            $this->removeAccessTokens($accessTokenId);
            $this->removeRefreshTokens($accessTokenId);
            $this->nonceService->removeNonceByAccessTokenId($accessTokenId);

            $postLogoutRedirectUri = $request->query->get('post_logout_redirect_uri');
            if (is_string($postLogoutRedirectUri)) {
                return $this->redirect($postLogoutRedirectUri);
            }

            return new Response('OK');
        }

        // TODO: Proceed with authHeader invalidation
        throw new NotFoundHttpException();

//        if (
//            (
//                null === ($authHeader = $request->headers->get('authorization'))
//                && null === ()
//            )
//            || !str_starts_with($authHeader, 'Bearer ')
//        ) {
//            throw new NotFoundHttpException();
//        }

//        try {
//            $jwt = Asserted::nonEmptyString(trim((string)preg_replace('/^(?:\s+)?Bearer\s/', '', $authHeader)));
//            $token = (new Parser(new JoseEncoder()))->parse($jwt);
//            /** @phpstan-ignore method.notFound */
//            $accessTokenIdentifier = $token->claims()->get(RegisteredClaims::ID);
//            $this->removeAccessTokens($accessTokenIdentifier);
//            $this->removeRefreshTokens($accessTokenIdentifier);
//
//            $postLogoutRedirectUri = $request->query->get('post_logout_redirect_uri');
//            if (is_string($postLogoutRedirectUri)) {
//                return $this->redirect($postLogoutRedirectUri);
//            }
//
//            return new Response('OK');
//        } catch (Exception $e) {
//            $this->logger->error('Could not remove access token', ['exception' => $e]);
//            throw new HttpException(500, 'Invalid token provided');
//        }
    }

    protected function removeRefreshTokens(string $accessTokenIdentifier): void
    {
        $entityManager = $this->registry->getManagerForClass(RefreshToken::class);
        assert($entityManager instanceof EntityManagerInterface);

        $refreshTokens = $entityManager->createQueryBuilder()
            ->select('refreshToken')
            ->from(RefreshToken::class, 'refreshToken')
            ->where('refreshToken.accessToken = :accessTokenIdentifier')
            ->setParameter('accessTokenIdentifier', $accessTokenIdentifier)
            ->getQuery()->getResult();
        foreach ($refreshTokens as $refreshToken) {
            $entityManager->remove($refreshToken);
            $entityManager->flush();
        }
    }

    protected function removeAccessTokens(string $accessTokenIdentifier): void
    {
        $entityManager = $this->registry->getManagerForClass(AccessToken::class);
        assert($entityManager instanceof EntityManagerInterface);

        $accessTokens = $entityManager->createQueryBuilder()
            ->select('accessToken')
            ->from(AccessToken::class, 'accessToken')
            ->where('accessToken.identifier = :accessTokenIdentifier')
            ->setParameter('accessTokenIdentifier', $accessTokenIdentifier)
            ->getQuery()->getResult();
        foreach ($accessTokens as $accessToken) {
            $entityManager->remove($accessToken);
            $entityManager->flush();
        }
    }
}

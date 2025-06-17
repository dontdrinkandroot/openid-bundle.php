<?php

namespace Dontdrinkandroot\OpenIdBundle\Model;

use DateTimeImmutable;
use Dontdrinkandroot\Common\Asserted;
use Dontdrinkandroot\OpenIdBundle\Service\CryptService;
use Dontdrinkandroot\OpenIdBundle\Service\Nonce\NonceServiceInterface;
use Lcobucci\JWT\Encoding\ChainedFormatter;
use Lcobucci\JWT\Encoding\JoseEncoder;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use Lcobucci\JWT\Token\Builder;
use League\Bundle\OAuth2ServerBundle\Model\AccessToken;
use League\OAuth2\Server\Entities\AccessTokenEntityInterface;
use League\OAuth2\Server\Entities\ScopeEntityInterface;
use League\OAuth2\Server\ResponseTypes\BearerTokenResponse;
use Override;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Support for a minimalistic ID token so we are compatible with OpenId Connect spec.
 */
class IdTokenResponse extends BearerTokenResponse
{
    public function __construct(
        private readonly NonceServiceInterface $nonceService,
        private readonly CryptService $cryptService,
        private readonly RequestStack $requestStack,
        private readonly ?string $keyIdentifier = null
    ) {
    }

    #[Override]
    protected function getExtraParams(AccessTokenEntityInterface $accessToken): array
    {
        if (false === $this->isOpenIDRequest($accessToken->getScopes())) {
            return [];
        }

        $builder = $this->getBuilder($accessToken, Asserted::notNull($accessToken->getUserIdentifier()));
        if ($this->keyIdentifier !== null) {
            $builder = $builder->withHeader('kid', $this->keyIdentifier);
        }

        $key = InMemory::plainText(
            Asserted::nonEmptyString($this->privateKey->getKeyContents()),
            (string)$this->privateKey->getPassPhrase()
        );
        $token = $builder->getToken(new Sha256(), $key);

        return [
            'id_token' => $token->toString(),
        ];
    }

    /**
     * @param non-empty-string $userIdentifier
     */
    protected function getBuilder(AccessTokenEntityInterface $accessToken, string $userIdentifier): \Lcobucci\JWT\Builder
    {
        $request = $this->requestStack->getMainRequest();

        $claimsFormatter = ChainedFormatter::withUnixTimestampDates();
        $builder = new Builder(new JoseEncoder(), $claimsFormatter);

        $expiresAt = $accessToken->getExpiryDateTime();

        /**
         * @see NonceListener
         */
        if (null !== ($code = $request?->request->get('code'))) {
            $decodedCode = $this->cryptService->decryptCode($code);
            $authCodeId = $decodedCode['auth_code_id'];
            $nonce = $this->nonceService->findNonceByAuthCodeId($authCodeId);
            if ($nonce !== null) {
                $builder = $builder->withClaim('nonce', $nonce);
                $this->nonceService->removeNonceByAuthCodeId($authCodeId);
                $this->nonceService->findNonceByAccessTokenId($accessToken->getIdentifier());
            }
        }

        return $builder
            ->permittedFor($accessToken->getClient()->getIdentifier())
            ->issuedBy(Asserted::nonEmptyString($request?->getSchemeAndHttpHost()))
            ->issuedAt(new DateTimeImmutable())
            ->expiresAt($expiresAt)
            ->relatedTo($userIdentifier);
    }

    /** @param ScopeEntityInterface[] $scopes */
    private function isOpenIDRequest(array $scopes): bool
    {
        return array_any($scopes, fn($scope) => $scope->getIdentifier() === 'openid');
    }

}

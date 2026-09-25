AGENTS.md
=========

## Project summary

TODO

## Instructions

* We use a sober and scientific style.
* Get back to the user: When seemingly stuck, when an approach does not work as expected, or when new decisions have to be taken, the LLM Agent MUST stop and get back to the user with the situation and options instead of continuing with assumptions. Do not silently pivot to a different approach.

## Tools

* **Web research**: use the `exa_web_search_exa` and `exa_web_fetch_exa` tools for research, validating information and looking things up when unsure — do not guess. Search first with `exa_web_search_exa`, then fetch the full page with `exa_web_fetch_exa` when highlights are insufficient. Verify API contracts, library versions, spec details and upstream behavior against primary sources before relying on them; cite the sources you checked in your summary.
* **GitHub issues**
  * Always set the **issue type** (`Bug` / `Feature` / `Task`).
  * Every **bug** additionally gets the **`Priority`** field (`Low`, `Medium`, `High`, `Urgent`)
  * Avoid redundant labels (like "bug" for a bug).
  * **Proactively create issues for newly discovered bugs** — file them, don't just mention them.
  * GitHub issues are the sole issue tracker.
  * When adding an issue, make sure it is not duplicated.

## Coding Rules

* We always adhere to Clean Code and SOLID principles. Keep in mind that this avoids unnecessary comments and rather uses speaking variable and function names.
* We use test-driven development by default, in small red→green→refactor cycles writing tests first. The tests are documenting our specification and expectations. If a test would be overly complicated, ask the user first if it is worth it.
* Tests follow the Arrange-Act-Assert pattern

## Project layout

Symfony bundle `dontdrinkandroot/openid-bundle`: a bridge that adds OpenID Connect capabilities on top of `league/oauth2-server-bundle`.

```
├── bin/
│   ├── update-dev                     # Helper: git pull + composer update
│   └── validate                       # Full validation: PHPUnit with coverage (CRAP), PHPStan, CRAP threshold check
├── config/
│   ├── routes.php                     # Bundle routes: oauth2/logout, oauth2/userinfo, .well-known/openid-configuration, .well-known/jwks.json (imports league/oauth2-server-bundle routes under /oauth2)
│   └── services.php                   # Service definitions (explicit wiring, incl. event listener tags and aliases)
├── recipe/                            # Placeholder for a future Symfony recipe endpoint (empty)
├── src/
│   ├── Config/
│   │   ├── DependencyInjection/ParamName.php   # Container parameter name constants
│   │   ├── DependencyInjection/TagName.php     # DI tag name constants
│   │   └── RouteName.php                       # Route name constants (prefix ddr.openid.*)
│   ├── Controller/
│   │   ├── JwksAction.php                    # GET /.well-known/jwks.json: exposes the OAuth2 public key
│   │   ├── LogoutAction.php                  # GET/POST /oauth2/logout
│   │   ├── OpenidConfigurationAction.php     # GET /.well-known/openid-configuration: OIDC discovery document
│   │   └── UserInfoAction.php                # GET /oauth2/userinfo: claims from the authenticated OAuth2 user, enriched by ScopeProviders
│   ├── DependencyInjection/
│   │   ├── Configuration.php           # Config tree for the `ddr_openid` key (whitelisted_clients, resolve_user_provider)
│   │   └── DdrOpenIdExtension.php      # Bundle extension: loads config/services.php, wires config, prepends NelmioCors config if present
│   ├── Event/Listener/
│   │   ├── AuthorizationCodeListener.php # Resolves league's authorization request: auto-approves whitelisted clients, otherwise renders an approve form
│   │   ├── NonceListener.php             # kernel.response listener: captures the OIDC nonce from authorize requests and stores it by auth code id
│   │   └── UserResolveListener.php       # Resolves league's user resolve event: loads the user via the configured user provider and validates the password
│   ├── Model/
│   │   └── IdTokenResponse.php         # BearerTokenResponse extension that adds a minimalistic signed ID token (OpenID Connect compatibility)
│   ├── Service/
│   │   ├── CryptService.php                    # Decrypts OAuth2 authorization codes (reuses league's encryption key)
│   │   ├── Nonce/CachedNonceService.php        # NonceServiceInterface impl storing nonces by auth code id in cache.app
│   │   ├── Nonce/NonceServiceInterface.php     # Nonce storage contract
│   │   ├── ScopeProvider/OpenIdScopeProvider.php # Provides the default `openid` scope
│   │   └── ScopeProvider/ScopeProviderInterface.php # Extension point to enrich claims/scopes per user
│   └── DdrOpenIdBundle.php             # Bundle class (path = repo root so config/templates/translations resolve)
├── templates/
│   ├── base.html.twig                  # Base layout for bundle templates
│   └── grant.html.twig                 # Authorization approve form rendered by AuthorizationCodeListener
├── tests/
│   ├── Acceptance/                     # Acceptance tests booting tests/TestApp/TestKernel (full HTTP stack)
│   ├── Fixture/TestUser.php            # Minimal non-empty UserInterface impl for unit tests
│   ├── Fixture/TestPasswordUser.php    # TestUser + PasswordAuthenticatedUserInterface
│   ├── TestApp/TestKernel.php          # Minimal Symfony kernel (MicroKernelTrait) for acceptance tests
│   ├── TestApp/config/                 # Test app config: bundles.php, packages/*.php (framework, security, league, doctrine, ddr_open_id), routes.php
│   ├── TestApp/config/reference.php    # AUTO-GENERATED by PhpConfigReferenceDumpPass on kernel boot (excluded from PHPStan)
│   └── Unit/                           # Unit tests mirroring src/ (Controller, Event/Listener, Service, Service/Nonce)
├── translations/
│   ├── DdrOpenId.de.yaml               # German translations
│   └── DdrOpenId.en.yaml               # English translations
├── .github/workflows/continuous-integration.yml  # CI pipeline
├── composer.json                       # Package metadata, dependencies (php >=8.5, Symfony ^7, league/oauth2-server-bundle)
├── phpstan.neon                        # PHPStan configuration (strict rules, symfony extension)
└── phpunit.xml.dist                    # PHPUnit configuration, boots tests/TestApp/TestKernel
```

## Features

TODO

## Pitfalls and Learnings

* PHPStan-only types (`non-empty-string`, ...) must never be used as native type declarations — PHP parses `non-empty-string` as `non - empty - string`. In tests, narrow with a `@param`/`@return` docblock instead.
* `CachedNonceService::removeNonceByAccessTokenId()` must read the nonce *before* deleting the token index, otherwise the reverse index (`nonce_<nonce>` → token id) leaks. Covered by `CachedNonceServiceTest::testRemoveNonceByAccessTokenIdDeletesBothIndexes`.
* Unit tests use `tests/Unit/**` (mirror `src/`), namespace `...Tests\Unit\...`, Symfony `ArrayAdapter` as in-memory cache double, `tests/Fixture/TestUser.php` as minimal user. phpstan.neon analyzes `tests/` at level 8 + strict rules, so data providers need iterable value types and overridden methods need `#[Override]`.
* PHPUnit 12 flags expectation-free mocks as notices (`failOnNotice`): use test stubs (inline anonymous classes) for collaborators whose behavior is irrelevant (`UnusedKernel` in NonceListenerTest).
* `AbstractController` subclasses need `setContainer()` with a container exposing their services (`security.token_storage` for UserInfoAction) — see `UserInfoActionTest::createAction` and the inline `FixedTokenStorage` double.
* `ScopeProviderInterface<...>` is invariant in T; scope-provider doubles must be declared `@implements ScopeProviderInterface<UserInterface>` (not `<TestUser>`) to fit `UserInfoAction`'s mixed-providers iterable.
* Test app config key for this bundle is `ddr_open_id` — the DI extension alias is derived from `DdrOpenIdBundle`, not from the `ddr_openid` tree name in `Configuration.php`.
* `MicroKernelTrait` reads bundles from `config/bundles.php` only (a `tests/TestApp/bundles.php` next to the kernel is silently ignored).
* Booting `DdrOpenIdBundle` requires the `twig` + `form.factory` services (TwigBundle) and a `ManagerRegistry` (DoctrineBundle) — `config/services.php` wires them eagerly.
* The kernel-boot acceptance test uses `self::bootKernel()` + `$kernel->handle(Request::create(...))` (no `createClient()`, so no `framework.test`/session setup needed); league keys (`OAUTH_PRIVATE_KEY`/`OAUTH_PUBLIC_KEY` env) are only touched when `AuthorizationServer`/`ResourceServer`/`JwksAction` are actually used — the discovery endpoint boots without real key files.
* Acceptance tests boot the kernel with `$kernel->handle(Request::create(...))` (no `createClient()`, avoids session/security setup); a stale `/tmp/ddr_openid_bundle/cache` makes route changes invisible — clear it when routes/config change.

## Constraints

TODO

## Definition of Done

A task is considered done when:

* The original request is verified complete, not just "the tooling passes".
* Tests and other validations are green (validate script runs) — any intentional deviation is explicitly justified in the commit.
* AGENTS.md has been updated:
  * **Add** only what a *future* session would actually benefit from knowing (learnings, decisions, gotchas, current architecture facts, ...). Prefer consolidating over appending; if in doubt, leave it out.
  * **Remove** outdated, obsolete, or bloated information so the file stays
    lean and useful.

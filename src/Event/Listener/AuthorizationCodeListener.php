<?php

namespace Dontdrinkandroot\OpenIdBundle\Event\Listener;

use League\Bundle\OAuth2ServerBundle\Event\AuthorizationRequestResolveEvent;
use RuntimeException;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\SubmitButton;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;

class AuthorizationCodeListener
{
    private const string AUTHORIZATION_GRANT = 'approve';

    /**
     * @param string[] $whitelistedClients
     */
    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly Environment $twig,
        private readonly FormFactoryInterface $formFactory,
        private readonly array $whitelistedClients
    ) {
    }

    public function onAuthorizationRequestResolve(AuthorizationRequestResolveEvent $event): void
    {
        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            throw new RuntimeException('No current request found');
        }

        $client = $event->getClient();

        if (in_array($client->getIdentifier(), $this->whitelistedClients, true)) {
            $event->resolveAuthorization(AuthorizationRequestResolveEvent::AUTHORIZATION_APPROVED);
            return;
        }

        $form = $this->formFactory->createBuilder(options: [
            'translation_domain' => 'DdrOpenId'
        ])
            ->add('approve', SubmitType::class, ['label' => 'action.grant'])
            ->add('deny', SubmitType::class, ['label' => 'action.deny', 'attr' => ['class' => 'btn btn-link']])
            ->getForm();
        $form->handleRequest($request);
        if ($form->isSubmitted()) {
            if (
                ($approveButton = $form->get('approve')) instanceof SubmitButton
                && $approveButton->isClicked()
            ) {
                $event->resolveAuthorization(AuthorizationRequestResolveEvent::AUTHORIZATION_APPROVED);
                return;
            }

            $event->resolveAuthorization(AuthorizationRequestResolveEvent::AUTHORIZATION_DENIED);
            return;
        }

        $content = $this->twig->render(
            '@DdrOpenId/grant.html.twig',
            [
                'scopes' => $event->getScopes(),
                'client' => $client,
                'form' => $form->createView(),
                'grant' => self::AUTHORIZATION_GRANT,
            ]
        );

        $response = new Response($content);
        $event->setResponse($response);
    }
}

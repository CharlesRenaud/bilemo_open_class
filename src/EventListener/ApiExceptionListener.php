<?php

namespace App\EventListener;

use App\Api\Response\ApiResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Event Listener pour gérer les exceptions et les convertir en réponses JSON
 * avec support HATEOAS (liens utiles).
 * 
 * Transforme les exceptions en réponses API cohérentes avec des liens
 * qui guident le client vers les prochaines actions possibles.
 */
class ApiExceptionListener
{
    public function __construct(
        private UrlGeneratorInterface $urlGenerator,
    ) {
        // Configurer le générateur d'URLs pour ApiResponse
        ApiResponse::setUrlGenerator($urlGenerator);
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();
        $request = $event->getRequest();

        // Ne traiter que les requêtes API
        if (!str_starts_with($request->getPathInfo(), '/api')) {
            return;
        }

        // Créer une réponse JSON appropriée
        if ($exception instanceof HttpExceptionInterface) {
            $statusCode = $exception->getStatusCode();
            $message = $exception->getMessage() ?: 'An error occurred';

            // Enrichir avec des liens utiles selon le type d'erreur
            $links = $this->getHelpfulLinks($statusCode, $message);

            $response = match ($statusCode) {
                404 => ApiResponse::notFound($message, $links),
                401 => ApiResponse::unauthorized($message, $links),
                403 => ApiResponse::forbidden($message, $links),
                400 => ApiResponse::badRequest($message, null, $links),
                409 => ApiResponse::conflict($message, $links),
                422 => ApiResponse::unprocessable($message, null, $links),
                default => ApiResponse::error($message, $statusCode, null, $links),
            };
        } else {
            // Erreur non HTTP (exception générale)
            $response = ApiResponse::internalServerError(
                'An unexpected error occurred',
                $this->getHelpfulLinks(500, 'Internal Server Error')
            );
        }

        $event->setResponse($response);
    }

        /**
         * Retourne les liens utiles basés sur le type d'erreur
         */
    private function getHelpfulLinks(int $statusCode, string $message): ?array
    {
        return match ($statusCode) {
            401 => [
                'admin_login' => [
                    'href' => $this->urlGenerator->generate('api_admin_login'),
                    'method' => 'POST',
                    'title' => 'Connexion administrateur'
                ],
                'client_login' => [
                    'href' => $this->urlGenerator->generate('api_client_login'),
                    'method' => 'POST',
                    'title' => 'Connexion client'
                ],
            ],
            404 => [
                'api_root' => [
                    'href' => $this->urlGenerator->generate('api_root'),
                    'method' => 'GET',
                    'title' => 'Découvrir les endpoints disponibles'
                ],
                'products' => [
                    'href' => $this->urlGenerator->generate('api_products_list'),
                    'method' => 'GET',
                    'title' => 'Lister les produits'
                ],
                'client_profile' => [
                    'href' => $this->urlGenerator->generate('api_clients_list'),
                    'method' => 'GET',
                    'title' => 'Profil du client'
                ],
                'client_users' => [
                    'href' => $this->urlGenerator->generate('api_clients_list_users'),
                    'method' => 'GET',
                    'title' => 'Liste des utilisateurs du client'
                ],
            ],
            403 => [
                'api_root' => [
                    'href' => $this->urlGenerator->generate('api_root'),
                    'method' => 'GET',
                    'title' => 'Retour à l\'API'
                ],
                'client_profile' => [
                    'href' => $this->urlGenerator->generate('api_clients_list'),
                    'method' => 'GET',
                    'title' => 'Profil du client (si connecté)'
                ],
                'client_users' => [
                    'href' => $this->urlGenerator->generate('api_clients_list_users'),
                    'method' => 'GET',
                    'title' => 'Liste des utilisateurs du client (si connecté)'
                ],
            ],
            500 => [
                'api_status' => [
                    'href' => $this->urlGenerator->generate('api_status'),
                    'method' => 'GET',
                    'title' => 'Vérifier l\'état de l\'API'
                ],
            ],
            default => null,
        };
    }

}
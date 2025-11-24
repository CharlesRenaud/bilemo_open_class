<?php

namespace App\EventListener;

use App\Api\Response\ApiResponse;
use App\Service\HateoasBuilder;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

/**
 * Event Listener pour gérer les exceptions et les convertir en réponses JSON
 * avec support HATEOAS.
 */
class ApiExceptionListener
{
    public function __construct(
        private HateoasBuilder $hateoas
    ) {}

    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();
        $request = $event->getRequest();

        // Ne traiter que les requêtes API
        if (!str_starts_with($request->getPathInfo(), '/api')) {
            return;
        }

        if ($exception instanceof HttpExceptionInterface) {
            $statusCode = $exception->getStatusCode();
            $message = $exception->getMessage() ?: 'An error occurred';
            $links = $this->getHelpfulLinks($statusCode);

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
            $response = ApiResponse::internalServerError(
                'An unexpected error occurred',
                $this->getHelpfulLinks(500)
            );
        }

        $event->setResponse($response);
    }

    private function getHelpfulLinks(int $statusCode): ?array
    {
        return match ($statusCode) {
            401 => [
                'admin_login' => $this->hateoas->createLink('admin_login', $this->hateoas->getAdminLoginUrl(), 'POST', 'Connexion administrateur'),
                'client_login' => $this->hateoas->createLink('client_login', $this->hateoas->getClientLoginUrl(), 'POST', 'Connexion client'),
            ],
            404 => [
                'api_root' => $this->hateoas->createLink('root', $this->hateoas->getRootUrl(), 'GET', 'Découvrir l\'API'),
                'products' => $this->hateoas->createLink('products', $this->hateoas->getProductsListUrl(), 'GET', 'Lister les produits'),
                'client_profile' => $this->hateoas->createClientLinks(0, 'Client')['self'],
                'client_users' => $this->hateoas->createClientLinks(0, 'Client')['users'],
            ],
            403 => [
                'api_root' => $this->hateoas->createLink('root', $this->hateoas->getRootUrl(), 'GET', 'Retour à l\'API'),
                'client_profile' => $this->hateoas->createClientLinks(0, 'Client')['self'],
                'client_users' => $this->hateoas->createClientLinks(0, 'Client')['users'],
            ],
            500 => [
                'api_status' => $this->hateoas->createLink('status', $this->hateoas->getStatusUrl(), 'GET', 'Vérifier l\'état de l\'API'),
            ],
            default => null,
        };
    }
}

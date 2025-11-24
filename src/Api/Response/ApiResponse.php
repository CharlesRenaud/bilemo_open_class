<?php

namespace App\Api\Response;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ApiResponse
{
    private static ?UrlGeneratorInterface $urlGenerator = null;

    /**
     * Configure le générateur d'URLs pour les liens HATEOAS
     * 
     * @param UrlGeneratorInterface $urlGenerator
     */
    public static function setUrlGenerator(UrlGeneratorInterface $urlGenerator): void
    {
        self::$urlGenerator = $urlGenerator;
    }

    public static function success(mixed $data, int $statusCode = Response::HTTP_OK): JsonResponse
    {
        return new JsonResponse([
            'success' => true,
            'data' => $data,
        ], $statusCode);
    }

    public static function error(
        string $message,
        int $statusCode = Response::HTTP_BAD_REQUEST,
        ?array $errors = null,
        ?array $links = null
    ): JsonResponse {
        $response = [
            'success' => false,
            'message' => $message,
        ];

        if ($errors) {
            $response['errors'] = $errors;
        }

        if ($links) {
            $response['_links'] = $links;
        }

        return new JsonResponse($response, $statusCode);
    }

    public static function created(mixed $data): JsonResponse
    {
        return self::success($data, Response::HTTP_CREATED);
    }

    public static function notFound(string $message = 'Resource not found', ?array $links = null): JsonResponse
    {
        return self::error($message, Response::HTTP_NOT_FOUND, null, $links);
    }

    public static function unauthorized(
        string $message = 'Unauthorized',
        ?array $links = null
    ): JsonResponse {
        // Ajouter automatiquement les liens de login si non fournis
        if (!$links && self::$urlGenerator) {
            $links = [
                'admin_login' => [
                    'href' => self::$urlGenerator->generate('api_admin_login'),
                    'method' => 'POST',
                    'title' => 'Connexion administrateur'
                ],
                'client_login' => [
                    'href' => self::$urlGenerator->generate('api_client_login'),
                    'method' => 'POST',
                    'title' => 'Connexion client'
                ],
            ];
        }

        return self::error($message, Response::HTTP_UNAUTHORIZED, null, $links);
    }

    public static function forbidden(string $message = 'Forbidden', ?array $links = null): JsonResponse
    {
        return self::error($message, Response::HTTP_FORBIDDEN, null, $links);
    }

    public static function badRequest(
        string $message = 'Bad Request',
        ?array $errors = null,
        ?array $links = null
    ): JsonResponse {
        return self::error($message, Response::HTTP_BAD_REQUEST, $errors, $links);
    }

    public static function conflict(string $message = 'Conflict', ?array $links = null): JsonResponse
    {
        return self::error($message, Response::HTTP_CONFLICT, null, $links);
    }

    public static function unprocessable(
        string $message = 'Unprocessable Entity',
        ?array $errors = null,
        ?array $links = null
    ): JsonResponse {
        return self::error($message, Response::HTTP_UNPROCESSABLE_ENTITY, $errors, $links);
    }

    public static function internalServerError(
        string $message = 'Internal Server Error',
        ?array $links = null
    ): JsonResponse {
        return self::error($message, Response::HTTP_INTERNAL_SERVER_ERROR, null, $links);
    }
}

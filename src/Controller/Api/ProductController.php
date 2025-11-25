<?php

namespace App\Controller\Api;

use App\Api\Response\ApiResponse;
use App\Dto\ProductOutput;
use App\Entity\Product;
use App\Repository\ProductRepository;
use App\Service\CacheService;
use App\Service\HateoasBuilder;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/products', name: 'api_products_')]
#[OA\Tag(name: 'Products')]
class ProductController extends AbstractController
{
    public function __construct(
        private ProductRepository $productRepository,
        private CacheService $cacheService,
        private HateoasBuilder $hateoas,
    ) {}

    #[Route('', name: 'list', methods: ['GET'])]
    #[OA\Get(
        path: '/api/products',
        summary: 'Récupère la liste paginée des produits',
        tags: ['Products']
    )]
    #[OA\Parameter(
        name: 'page',
        in: 'query',
        description: 'Numéro de page',
        required: false,
        schema: new OA\Schema(type: 'integer', default: 1, minimum: 1)
    )]
    #[OA\Parameter(
        name: 'limit',
        in: 'query',
        description: 'Nombre d\'éléments par page',
        required: false,
        schema: new OA\Schema(type: 'integer', default: 10, minimum: 1, maximum: 100)
    )]
    #[OA\Parameter(
        name: 'sort',
        in: 'query',
        description: 'Champ de tri',
        required: false,
        schema: new OA\Schema(
            type: 'string',
            default: 'id',
            enum: ['id', 'name', 'price', 'brand', 'createdAt']
        )
    )]
    #[OA\Parameter(
        name: 'order',
        in: 'query',
        description: 'Ordre de tri',
        required: false,
        schema: new OA\Schema(
            type: 'string',
            default: 'ASC',
            enum: ['ASC', 'DESC']
        )
    )]
    #[OA\Response(
        response: 200,
        description: 'Liste paginée des produits récupérée avec succès',
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(
                    property: 'items',
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/ProductOutput')
                ),
                new OA\Property(
                    property: 'pagination',
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'page', type: 'integer', example: 1),
                        new OA\Property(property: 'limit', type: 'integer', example: 10),
                        new OA\Property(property: 'total', type: 'integer', example: 50),
                        new OA\Property(property: 'pages', type: 'integer', example: 5)
                    ]
                ),
                new OA\Property(
                    property: '_links',
                    type: 'object',
                    properties: [
                        new OA\Property(
                            property: 'self',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'href', type: 'string', example: '/api/products?page=1&limit=10'),
                                new OA\Property(property: 'method', type: 'string', example: 'GET')
                            ]
                        ),
                        new OA\Property(
                            property: 'next',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'href', type: 'string', example: '/api/products?page=2&limit=10'),
                                new OA\Property(property: 'method', type: 'string', example: 'GET')
                            ]
                        ),
                        new OA\Property(
                            property: 'prev',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'href', type: 'string', example: '/api/products?page=1&limit=10'),
                                new OA\Property(property: 'method', type: 'string', example: 'GET')
                            ]
                        )
                    ]
                )
            ]
        )
    )]
    #[OA\Response(
        response: 400,
        description: 'Paramètres de requête invalides'
    )]
    public function list(Request $request): JsonResponse
    {
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = min(100, max(1, (int) $request->query->get('limit', 10)));
        $sort = $this->validateSort($request->query->get('sort', 'id'));
        $order = strtoupper($request->query->get('order', 'ASC')) === 'DESC' ? 'DESC' : 'ASC';

        $cacheKey = sprintf('products_list_%d_%d_%s_%s', $page, $limit, $sort, $order);

        $data = $this->cacheService->get($cacheKey, function () use ($page, $limit, $sort, $order): array {
            $offset = ($page - 1) * $limit;
            $products = $this->productRepository->findPaginated($offset, $limit, $sort, $order);
            $total = $this->productRepository->count([]);

            $items = array_map(fn(Product $p) => $this->serializeProductWithLinks($p), $products);

            return [
                'items' => $items,
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => $total,
                    'pages' => (int) ceil($total / $limit),
                ],
            ];
        });

        $paginationLinks = $this->hateoas->createPaginationLinks(
            $page,
            $limit,
            $data['pagination']['total'],
            'api_products_list',
            ['sort' => $page === 1 ? 'id' : $sort, 'order' => $page === 1 ? 'ASC' : $order]
        );

        $data['_links'] = $paginationLinks;

        $response = ApiResponse::success($data);
        $this->setCacheHeaders($response, 3600);

        return $response;
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    #[OA\Get(
        path: '/api/products/{id}',
        summary: 'Récupère les détails d\'un produit',
        tags: ['Products']
    )]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        description: 'ID du produit',
        required: true,
        schema: new OA\Schema(type: 'integer', minimum: 1)
    )]
    #[OA\Response(
        response: 200,
        description: 'Détails du produit récupérés avec succès',
        content: new OA\JsonContent(ref: '#/components/schemas/ProductOutput')
    )]
    #[OA\Response(
        response: 404,
        description: 'Produit non trouvé',
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(property: 'error', type: 'string', example: 'Product not found'),
                new OA\Property(
                    property: '_links',
                    type: 'object',
                    properties: [
                        new OA\Property(
                            property: 'list',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'href', type: 'string', example: '/api/products'),
                                new OA\Property(property: 'method', type: 'string', example: 'GET'),
                                new OA\Property(property: 'title', type: 'string', example: 'Retour à la liste des produits')
                            ]
                        )
                    ]
                )
            ]
        )
    )]
    public function show(int $id): JsonResponse
    {
        $cacheKey = sprintf('product_%d', $id);

        $product = $this->cacheService->get($cacheKey, fn() => $this->productRepository->find($id));

        if (!$product) {
            $links = [
                'list' => $this->hateoas->createLink('list', $this->generateUrl('api_products_list'), 'GET', 'Retour à la liste des produits'),
            ];
            return ApiResponse::notFound('Product not found', $links);
        }

        $response = ApiResponse::success($this->serializeProductWithLinks($product));
        $this->setCacheHeaders($response, 3600);

        return $response;
    }

    private function serializeProduct(Product $product): array
    {
        return [
            'id' => $product->getId(),
            'name' => $product->getName(),
            'brand' => $product->getBrand(),
            'model' => $product->getModel(),
            'price' => $product->getPrice(),
            'description' => $product->getDescription(),
            'imageUrl' => $product->getImageUrl(),
            'availability' => $product->isAvailable(),
            'createdAt' => $product->getCreatedAt()?->format('c'),
            'updatedAt' => $product->getUpdatedAt()?->format('c'),
        ];
    }

    private function serializeProductWithLinks(Product $product): array
    {
        $data = $this->serializeProduct($product);

        $links = [
            'self' => $this->hateoas->createResourceLink('api_products_show', $product->getId(), title: $product->getName()),
        ];

        return $this->hateoas->addLinks($data, $links);
    }

    private function validateSort(string $sort): string
    {
        $allowedSorts = ['id', 'name', 'price', 'brand', 'createdAt'];
        return in_array($sort, $allowedSorts) ? $sort : 'id';
    }

    private function setCacheHeaders(JsonResponse $response, int $maxAge = 3600): void
    {
        $response->setPublic();
        $response->setMaxAge($maxAge);
        $response->headers->set('Cache-Control', sprintf('public, max-age=%d, must-revalidate', $maxAge));
    }
}
<?php

namespace App\Controller\Api;

use App\Api\Response\ApiResponse;
use App\Dto\ProductOutput;
use App\Entity\Product;
use App\Repository\ProductRepository;
use App\Service\CacheService;
use App\Service\HateoasBuilder;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/products', name: 'api_products_')]
#[IsGranted('ROLE_CLIENT')]
#[OA\Tag(name: 'Products')]
#[OA\Security(name: 'Bearer')]
class ProductController extends AbstractController
{
    public function __construct(
        private ProductRepository $productRepository,
        private CacheService $cacheService,
        private HateoasBuilder $hateoas,
    ) {}

    #[Route('', name: 'list', methods: ['GET'])]
    #[OA\Get(summary: 'Récupère la liste paginée des produits')]
    #[OA\Security(name: 'Bearer')]
    #[OA\Parameter(
        name: 'page',
        in: 'query',
        schema: new OA\Schema(type: 'integer', default: 1, minimum: 1)
    )]
    #[OA\Parameter(
        name: 'limit',
        in: 'query',
        schema: new OA\Schema(type: 'integer', default: 10, minimum: 1, maximum: 100)
    )]
    #[OA\Parameter(
        name: 'sort',
        in: 'query',
        schema: new OA\Schema(
            type: 'string',
            default: 'id',
            enum: ['id', 'name', 'price', 'brand', 'createdAt']
        )
    )]
    #[OA\Parameter(
        name: 'order',
        in: 'query',
        schema: new OA\Schema(
            type: 'string',
            default: 'ASC',
            enum: ['ASC', 'DESC']
        )
    )]
    #[OA\Response(
        response: 200,
        description: 'Liste paginée des produits',
        content: new OA\JsonContent(ref: '#/components/schemas/ProductListResponse')
    )]
    public function list(Request $request): JsonResponse
    {
        $page  = max(1, (int) $request->query->get('page', 1));
        $limit = min(100, max(1, (int) $request->query->get('limit', 10)));
        $sort  = $this->validateSort($request->query->get('sort', 'id'));
        $order = strtoupper($request->query->get('order', 'ASC')) === 'DESC' ? 'DESC' : 'ASC';

        $cacheKey = sprintf('products_list_%d_%d_%s_%s', $page, $limit, $sort, $order);

        $data = $this->cacheService->get($cacheKey, function () use ($page, $limit, $sort, $order): array {
            $offset   = ($page - 1) * $limit;
            $products = $this->productRepository->findPaginated($offset, $limit, $sort, $order);
            $total    = $this->productRepository->count([]);

            return [
                'items'      => array_map(fn(Product $p) => $this->serializeProductWithLinks($p), $products),
                'pagination' => [
                    'page'  => $page,
                    'limit' => $limit,
                    'total' => $total,
                    'pages' => (int) ceil($total / $limit),
                ],
            ];
        });

        $data['_links'] = $this->hateoas->createPaginationLinks(
            $page,
            $limit,
            $data['pagination']['total'],
            'api_products_list',
            ['sort' => $sort, 'order' => $order]
        );

        $response = ApiResponse::success($data);
        $this->setCacheHeaders($response, 3600);

        return $response;
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    #[OA\Get(summary: 'Récupère les détails d\'un produit')]
    #[OA\Security(name: 'Bearer')]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        required: true,
        description: 'ID du produit',
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
            return ApiResponse::notFound('Product not found', [
                'list' => $this->hateoas->createLink(
                    'list',
                    $this->generateUrl('api_products_list'),
                    'GET',
                    'Retour à la liste des produits'
                ),
            ]);
        }

        $response = ApiResponse::success($this->serializeProductWithLinks($product));
        $this->setCacheHeaders($response, 3600);

        return $response;
    }

    private function serializeProduct(Product $product): array
    {
        return [
            'id'           => $product->getId(),
            'name'         => $product->getName(),
            'brand'        => $product->getBrand(),
            'model'        => $product->getModel(),
            'price'        => $product->getPrice(),
            'description'  => $product->getDescription(),
            'imageUrl'     => $product->getImageUrl(),
            'availability' => $product->isAvailable(),
            'createdAt'    => $product->getCreatedAt()?->format('c'),
            'updatedAt'    => $product->getUpdatedAt()?->format('c'),
        ];
    }

    private function serializeProductWithLinks(Product $product): array
    {
        $data = $this->serializeProduct($product);

        $links = [
            'self' => $this->hateoas->createResourceLink(
                'api_products_show',
                $product->getId(),
                title: $product->getName()
            ),
        ];

        return $this->hateoas->addLinks($data, $links);
    }

    private function validateSort(string $sort): string
    {
        $allowed = ['id', 'name', 'price', 'brand', 'createdAt'];
        return in_array($sort, $allowed) ? $sort : 'id';
    }

    private function setCacheHeaders(JsonResponse $response, int $maxAge = 3600): void
    {
        $response->setPublic();
        $response->setMaxAge($maxAge);
        $response->headers->set(
            'Cache-Control',
            sprintf('public, max-age=%d, must-revalidate', $maxAge)
        );
    }
}

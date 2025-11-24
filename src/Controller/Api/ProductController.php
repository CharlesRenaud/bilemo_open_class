<?php

namespace App\Controller\Api;

use App\Api\Response\ApiResponse;
use App\Entity\Product;
use App\Repository\ProductRepository;
use App\Service\CacheService;
use App\Service\HateoasBuilder;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/products', name: 'api_products_')]
class ProductController extends AbstractController
{
    public function __construct(
        private ProductRepository $productRepository,
        private CacheService $cacheService,
        private HateoasBuilder $hateoas,
    ) {
    }

    /**
     * GET /api/products - Récupère la liste paginée des produits
     * 
     * Query parameters:
     * - page: int (default: 1)
     * - limit: int (default: 10, max: 100)
     * - sort: string (default: 'id', options: 'id', 'name', 'price', 'brand')
     * - order: string (default: 'ASC', options: 'ASC', 'DESC')
     */
    #[Route('', name: 'list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = min(100, max(1, (int) $request->query->get('limit', 10)));
        $sort = $this->validateSort($request->query->get('sort', 'id'));
        $order = strtoupper($request->query->get('order', 'ASC')) === 'DESC' ? 'DESC' : 'ASC';

        // Clé de cache unique basée sur les paramètres de pagination et tri
        $cacheKey = sprintf('products_list_%d_%d_%s_%s', $page, $limit, $sort, $order);

        $data = $this->cacheService->get($cacheKey, function () use ($page, $limit, $sort, $order): array {
            $offset = ($page - 1) * $limit;
            $products = $this->productRepository->findPaginated($offset, $limit, $sort, $order);
            $total = $this->productRepository->count([]);

            // Sérialiser les produits avec HATEOAS
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

        // Ajouter les liens de pagination
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

    /**
     * GET /api/products/{id} - Récupère les détails d'un produit
     */
    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        $cacheKey = sprintf('product_%d', $id);

        $product = $this->cacheService->get($cacheKey, function () use ($id): ?Product {
            return $this->productRepository->find($id);
        });

        if (!$product) {
            $links = [
                'list' => $this->hateoas->createLink(
                    'list',
                    $this->generateUrl('api_products_list'),
                    'GET',
                    'Retour à la liste des produits'
                ),
            ];
            return ApiResponse::notFound('Product not found', $links);
        }

        $response = ApiResponse::success($this->serializeProductWithLinks($product));
        $this->setCacheHeaders($response, 3600);

        return $response;
    }

    /**
     * Sérialise un produit sans les liens (pour les collections)
     */
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

    /**
     * Sérialise un produit avec les liens HATEOAS
     */
    private function serializeProductWithLinks(Product $product): array
    {
        $data = $this->serializeProduct($product);

        // Ajouter les liens HATEOAS
        $links = [
            'self' => $this->hateoas->createResourceLink(
                'api_products_show',
                $product->getId(),
                title: $product->getName()
            ),
        ];

        return $this->hateoas->addLinks($data, $links);
    }

    /**
     * Valide et retourne le paramètre de tri
     */
    private function validateSort(string $sort): string
    {
        $allowedSorts = ['id', 'name', 'price', 'brand', 'createdAt'];
        return in_array($sort, $allowedSorts) ? $sort : 'id';
    }

    /**
     * Définit les en-têtes de cache HTTP (RFC 7234)
     * - Cache-Control: max-age pour le cache public
     * - ETag pour la validation
     * 
     * @param int $maxAge Durée en secondes
     */
    private function setCacheHeaders(JsonResponse $response, int $maxAge = 3600): void
    {
        $response->setPublic();
        $response->setMaxAge($maxAge);
        $response->headers->set('Cache-Control', sprintf('public, max-age=%d, must-revalidate', $maxAge));
    }
}

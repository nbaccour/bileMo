<?php

namespace App\Controller;

use App\Entity\Product;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use OpenApi\Annotations as OA;

class ProductController extends AbstractController
{


    protected $repository;
    protected $paginator;
    protected $manager;
    protected $serializer;
    protected $validator;

    public function __construct(
        ProductRepository $repository,
        PaginatorInterface $paginator,
        EntityManagerInterface $manager,
        SerializerInterface $serializer,
        ValidatorInterface $validator
    ) {
        $this->repository = $repository;
        $this->paginator = $paginator;
        $this->manager = $manager;
        $this->serializer = $serializer;
        $this->validator = $validator;
    }


    /**
     * @OA\Get(
     *     path="/api/products",
     *     tags={"Products"},
     *     security={"bearer"},
     *     @OA\Response(
     *          response="200",
     *          description="liste des produits",
     *          @OA\JsonContent(type="array",@OA\Items(ref="#/components/schemas/Products")),
     *      ),
     *      @OA\Response(
     *          response="404",
     *          ref="#/components/responses/NotFound"),
     *      )
     *)
     * @Route("/api/products", name="product_index", methods={"GET"})
     */
    public function index(Request $request): Response
    {
        $products = $this->repository->findAll();
        if (!$products) {
            $data = [
                'status' => 404,
                'errors' => "Produits non trouvés",
            ];
            return $this->json($data, 404);
        }


        $productslist = $this->paginator->paginate($products, $request->query->getInt('page', 1), 3);
        $response = $this->json($productslist, 200, [], []);


        return $response;
    }


    /**
     * @OA\Get(
     *     path="/api/products/{id}",
     *     tags={"Products"},
     *     security={"bearer"},
     *     @OA\Parameter(ref="#/components/parameters/id"),
     *     @OA\Response(
     *          response="200",
     *          description="detail d'un produit",
     *          @OA\JsonContent(ref="#/components/schemas/ProductDetail"),
     *      ),
     *      @OA\Response(
     *          response="404",
     *          ref="#/components/responses/NotFound"),
     *      )
     *)
     * @Route("/api/products/{id}", name="product_detail", methods={"GET"})
     */
    public function detail($id)
    {
        $product = $this->repository->find($id);
        if (!$product) {
            $data = [
                'status' => 404,
                'errors' => "Produit non trouvé",
            ];
            return $this->json($data, 404);
        }

        $response = $this->json($product, 200, [], []);

        return $response;

    }


    /**
     * @OA\Put(
     *     path="/api/products/{id}",
     *     tags={"Products"},
     *     @OA\Parameter(ref="#/components/parameters/id"),
     *     security={"bearer"},
     *     @OA\RequestBody(
     *          request="UpdateProduct",
     *          required=true,
     *          @OA\JsonContent(
     *              required={"name","description","brand","price"},
     *              @OA\Property(type="string", property="name"),
     *              @OA\Property(type="string", property="description"),
     *              @OA\Property(type="string", property="brand"),
     *              @OA\Property(type="integer", property="price"),
     *           )
     *      ),
     *     @OA\Response(
     *          response="200",
     *          description="produit mis à jour",
     *          @OA\JsonContent(ref="#/components/schemas/ProductDetail"),
     *      ),
     *      @OA\Response(
     *          response="404",
     *          ref="#/components/responses/NotFound"),
     *      )
     *)s
     * @Route("/api/products/{id}", name="product_update", methods={"PUT"})
     */
    public function update($id, Request $request)
    {
        $productExist = $this->repository->find($id);
        if (!$productExist) {
            $data = [
                'status' => 404,
                'errors' => "Produit non trouvé",
            ];
            return $this->json($data, 404);
        }

        $json = $request->getContent();
        $product = $this->serializer->deserialize($json, Product::class, 'json');

        $errors = $this->validator->validate($product);
        if (count($errors) > 0) {
            return $this->json($errors, 400);
        }

        $productExist->setName($product->getName())
            ->setDescription($product->getDescription())
            ->setPrice($product->getPrice())
            ->setBrand($product->getBrand());
        $this->manager->flush();

        $response = $this->json($productExist, 200, [], []);

        return $response;

    }
}

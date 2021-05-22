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

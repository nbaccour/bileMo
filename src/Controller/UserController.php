<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\ClientRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Serializer\Exception\NotEncodableValueException;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use OpenApi\Annotations as OA;

class UserController extends AbstractController
{


    protected $encoder;
    protected $authorizationChecker;
    protected $clientbRepository;
    protected $userbRepository;
    protected $manager;
    protected $serializer;
    protected $paginator;
    protected $validator;

    public function __construct(

        UserPasswordEncoderInterface $encoder,
        SerializerInterface $serializer,
        AuthorizationCheckerInterface $authorizationChecker,
        ClientRepository $clientRepository,
        UserRepository $userRepository,
        EntityManagerInterface $manager,
        PaginatorInterface $paginator,
        ValidatorInterface $validator
    ) {
        $this->encoder = $encoder;
        $this->authorizationChecker = $authorizationChecker;
        $this->clientRepository = $clientRepository;
        $this->userRepository = $userRepository;
        $this->manager = $manager;
        $this->serializer = $serializer;
        $this->paginator = $paginator;
        $this->validator = $validator;
    }


    /**
     * @OA\Get(
     *     path="/api/{name}/users",
     *     tags={"Users"},
     *     security={"bearer"},
     *     @OA\Parameter(
     *       name="name",
     *       in="path",
     *       description="le nom d'un client",
     *       required=true,
     *       @OA\Schema(type="string")
     *      ),
     *     @OA\Response(
     *          response="200",
     *          description="liste des utilisateurs",
     *          @OA\JsonContent(type="array",@OA\Items(ref="#/components/schemas/Users")),
     *      ),
     *      @OA\Response(
     *          response="404",
     *          ref="#/components/responses/NotFound"),
     *      )
     *)
     * @Route("/api/{name}/users", name="user_index", methods={"GET"})
     *
     */
    public function index($name, Request $request)
    {
        $client = $this->clientRepository->findBy(['name' => $name]);
        if (!$client) {
            $data = [
                'status' => 404,
                'errors' => "Client (" . $name . ") non trouvé ",
            ];
            return $this->json($data, 404);
        }

        $users = $this->userRepository->findByCustomer($client[0]);
        if (!$users) {
            $data = [
                'status' => 404,
                'errors' => "Pas d'utilisateurs pour ce client " . $name,
            ];
            return $this->json($data, 404);
        }

        $userslist = $this->paginator->paginate(
            $users, // Requête contenant les données à paginer (ici nos utilisateurs)
            $request->query->getInt('page', 1), // Numéro de la page en cours, passé dans l'URL, 1 si aucune page
            6 // Nombre de résultats par page
        );
        $response = $this->json($userslist, 200, [], ["groups" => "user:read"]);

        return $response;

    }


    /**
     * @OA\Get(
     *     path="/api/{name}/users/{id}",
     *     tags={"Users"},
     *     security={"bearer"},
     *     @OA\Parameter(ref="#/components/parameters/id"),
     *     @OA\Parameter(
     *       name="name",
     *       in="path",
     *       description="le nom d'un client",
     *       required=true,
     *       @OA\Schema(type="string")
     *      ),
     *     @OA\Response(
     *          response="200",
     *          description="detail d'un utilisateur",
     *          @OA\JsonContent(ref="#/components/schemas/UserDetail"),
     *      ),
     *      @OA\Response(
     *          response="404",
     *          ref="#/components/responses/NotFound"),
     *      )
     *)
     * @Route("/api/{name}/users/{id}", name="user_detail", methods={"GET"})
     */
    public function detail($name, $id)
    {
        $client = $this->clientRepository->findBy(['name' => $name]);
        if (!$client) {
            $data = [
                'status' => 404,
                'errors' => "Client (" . $name . ") non trouvé ",
            ];
            return $this->json($data, 404);
        }


        $user = $this->userRepository->findByCustomerAndUser($client[0], $id);
        if (!$user) {
            $data = [
                'status' => 404,
                'errors' => "Utilisateur non trouvé",
            ];
            return $this->json($data, 404);
        }

        $response = $this->json($user, 200, [], ["groups" => "user:read"]);

        return $response;

    }


    /**
     * @OA\Post(
     *     path="/api/{name}/user",
     *     tags={"Users"},
     *     security={"bearer"},
     *     @OA\RequestBody(
     *          request="AddUser",
     *          required=true,
     *          @OA\JsonContent(
     *              required={"email","password","username"},
     *              @OA\Property(type="string", property="email"),
     *              @OA\Property(type="string", property="password"),
     *              @OA\Property(type="string", property="username"),
     *           )
     *      ),
     *     @OA\Parameter(
     *       name="name",
     *       in="path",
     *       description="le nom d'un client",
     *       required=true,
     *       @OA\Schema(type="string")
     *      ),
     *     @OA\Response(
     *          response="201",
     *          description="utilisateur ajouté",
     *          @OA\JsonContent(ref="#/components/schemas/UserDetail"),
     *      ),
     *      @OA\Response(
     *          response="404",
     *          ref="#/components/responses/NotFound"),
     *      )
     *)
     * @Route("/api/{name}/user", name="user_add", methods={"POST"})
     */
    public function add(Request $request, $name)
    {
        $json = $request->getContent();

        $client = $this->clientRepository->findBy(['name' => $name]);
        if (!$client) {
            $data = [
                'status' => 404,
                'errors' => "Client (" . $name . ") non trouvé ",
            ];
            return $this->json($data, 404);
        }

        try {
            $user = $this->serializer->deserialize($json, User::class, 'json');

            $errors = $this->validator->validate($user);
            if (count($errors) > 0) {
                return $this->json($errors, 400);
            }


            $hash = $this->encoder->encodePassword($user, "password");
            $user->setPassword($hash)
                ->setClient($client[0]);

            try {
                $this->manager->persist($user);
                $this->manager->flush();
                $response = $this->json($user, 201, [], ["groups" => "user:read"]);

                return $response;
            } catch (NotEncodableValueException $error) {

                return $this->json([
                    'status' => 400,
                    'errors' => $error->getMessage(),
                ], 400);
            }


        } catch (NotEncodableValueException $e) {

            return $this->json([
                'status' => 400,
                'errors' => $e->getMessage(),
            ], 400);
        }


    }


    /**
     * @OA\Delete(
     *     path="/api/users/{id}",
     *     tags={"Users"},
     *     @OA\Parameter(ref="#/components/parameters/id"),
     *     security={"bearer"},
     *     @OA\RequestBody(
     *          request="DeleteUser",
     *          required=true,
     *          @OA\JsonContent(
     *              required={"id"},
     *              @OA\Property(type="boolean", property="id"),
     *           )
     *      ),
     *     @OA\Response(
     *          response="204",
     *          description="utilisateur supprimé",
     *      ),
     *      @OA\Response(
     *          response="404",
     *          ref="#/components/responses/NotFound"),
     *      )
     *)
     * @Route("/api/users/{id}", name="user_delete", methods={"DELETE"})
     */
    public function delete($id)
    {
        $user = $this->userRepository->find($id);
        if (!$user) {
            $data = [
                'status' => 404,
                'errors' => "Utilisateur non trouvé",
            ];
            return $this->json($data, 404);
        }

        $this->manager->remove($user);
        $this->manager->flush();

        $response = $this->json('', 204, [], []);

        return $response;

    }


    /**
     * @OA\Put(
     *     path="/api/users/{id}",
     *     tags={"Users"},
     *     @OA\Parameter(ref="#/components/parameters/id"),
     *     security={"bearer"},
     *     @OA\RequestBody(
     *          request="UpdateUser",
     *          required=true,
     *          @OA\JsonContent(
     *              required={"email","password","username"},
     *              @OA\Property(type="string", property="email"),
     *              @OA\Property(type="string", property="password"),
     *              @OA\Property(type="string", property="username"),
     *           )
     *      ),
     *     @OA\Response(
     *          response="200",
     *          description="mise à jour d'utilisateur",
     *      ),
     *      @OA\Response(
     *          response="404",
     *          ref="#/components/responses/NotFound"),
     *      )
     *)
     * @Route("/api/users/{id}", name="user_update", methods={"PUT"})
     */
    public function update($id, Request $request)
    {
        $userExist = $this->userRepository->find($id);
        if (!$userExist) {
            $data = [
                'status' => 404,
                'errors' => "Utilisateur non trouvé",
            ];
            return $this->json($data, 404);
        }

        $json = $request->getContent();
        $user = $this->serializer->deserialize($json, User::class, 'json');

        $errors = $this->validator->validate($user);
        if (count($errors) > 0) {
            return $this->json($errors, 400);
        }
        $hash = $this->encoder->encodePassword($user, $user->getPassword());
        $userExist->setEmail($user->getEmail())
            ->setFullname($user->getFullname())
            ->setPassword($hash);

        $this->manager->flush();

        $response = $this->json($userExist, 200, [], ["groups" => "user:read"]);

        return $response;


    }
}

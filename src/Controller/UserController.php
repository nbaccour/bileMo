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

<?php

namespace App\DataFixtures;

use App\Entity\Client;
use App\Entity\Product;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class AppFixtures extends Fixture
{

    protected $encoder;

    public function __construct(UserPasswordEncoderInterface $encoder)
    {
        $this->encoder = $encoder;
    }

    public function load(ObjectManager $manager)
    {

        $faker = Factory::create('fr_FR');

        $phoneData = [
            [
                'Galaxy Note 9',
                'Samsung',
                'Le Samsung Galaxy Note 9 est le dernier-né de la gamme Note du géant coréen. C’est, aujourd’hui, l’une des solutions les plus complètes et les plus abouties sous Android.',
                '399.99',
            ],
            [
                'Pixel 3',
                'Google',
                'Vous ne jurez que par Android Stock ? Ne cherchez pas plus loin, c’est le Google Pixel 3 qu’il vous faut. Ce smartphone associe à la perfection la partie matérielle avec la partie logicielle. Premièrement, il possède un design assez unique avec un dos en verre dépoli garantissant une meilleure tenue du smartphone. C’est sobre et ça fonctionne très bien. Il n’est pas très grand avec son écran OLED de 5,5 pouces et ravira les personnes à la recherche d’un appareil un peu passe-partout.',
                '899.99',
            ],
            [
                'P20 Pro',
                'Huawei',
                'Le Huawei P20 Pro est un très beau smartphone. Lorsqu’on le retourne, on découvre un dos en verre du plus bel effet pouvant servir occasionnellement de miroir au besoin (oui, oui). L’écran OLED de 6,1 pouces est très équilibré et possède une grande luminosité ainsi que des noirs très profonds. Un vrai plaisir à regarder au quotidien.',
                '549.99',
            ],
            [
                'Xiaomi Mi 10T 256 Go gris',
                'Xiaomi',
                'Diagonale: 6,67 pouces Résolution du capteur: 64 mégapixels Capacité: 5260 mAh Capacité mémoire: 256 Go ',
                '520',
            ],
            [
                'Honor 20 6Go 128Go Bleu',
                'Honor',
                'Honor 20 6Go 128Go Bleu Kirin 980 Octa Core 6.26 pouces 48MP Quatre Cam téléphone portable Google Play Super Charge NFC Smartphone',
                '597',
            ],
            [
                'Samsung Galaxy S20',
                'Samsung',
                'Samsung Galaxy S20 Ultra 5G SM-G988N 256Go Noir',
                '794',
            ],
            [
                'Samsung Galaxy S10 5G',
                'Samsung',
                'Samsung Galaxy S10 5G Débloqué Smartphone 256GB Téléphone Portable Crown',
                '637.99',
            ],
        ];

        foreach ($phoneData as $aData) {


            $product = new Product();
            $product->setName($aData[0])
                ->setBrand($aData[1])
                ->setDescription($aData[2])
                ->setPrice($aData[3]);

            $manager->persist($product);
        }

        $aDataClient = [
            [
                'BILEMO',
            ],
            [
                'SFR',
            ],
            [
                'ORANGE',
            ],
            [
                'FREE',
            ],
        ];

        $aClients = [];
        foreach ($aDataClient as $clientData) {
            $client = new Client();
            $client->setName($clientData[0]);

            $manager->persist($client);

            $aClients[] = $client;
        }

        foreach ($aClients as $oClient) {

            if ($oClient->getName() === 'BILEMO') {
                $user = new User();
                $hash = $this->encoder->encodePassword($user, "password");
                $user->setEmail('admin@' . strtolower($oClient->getName()) . '.com')
                    ->setFullname('admin')
                    ->setRoles(['ROLE_ADMIN'])
                    ->setClient($oClient)
                    ->setPassword($hash);

                $manager->persist($user);
            } else {
                for ($u = 0; $u < 10; $u++) {
                    $user = new User();
                    $hash = $this->encoder->encodePassword($user, "password");
                    $user->setEmail('user' . $u . '@' . strtolower($oClient->getName()) . '.com')
                        ->setFullname($faker->firstName())
                        ->setClient($oClient)
                        ->setPassword($hash);

                    $manager->persist($user);
                }
            }


        }

        $manager->flush();
    }
}

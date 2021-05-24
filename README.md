# Projet : API REST openclassrooms
API BileMo

# Technologie utilisée

1. Symfony 5.2.8
2. PHP 7.4.13
3. JWT Authentication
4. Swagger
5. Postman

# Installation

1. Clonez le dépot où vous voulez : `git clone https://github.com/nbaccour/bileMo.git`
2. Modifier le fichier .env : `connexion à la base de données`
3. Créez la base de données : `php bin/console doctrine:database:create`
4. Installez les dépendances : `composer install`
5. Jouez les migrations : `php bin/console d:m:m`
6. Jouez les fixtures : `php bin/console d:f:l --no-interaction`
7. Lancez le server : `symfony serve` ou `php -S localhost:8000 -t public`

# Installation de Postman

Pour interagir avec l'API, vous devez installer Postman : 
`https://www.postman.com/`

# Informations sur la génération des cles
- création du répertoire jwt :
`mkdir config/jwt`
- création d'une clé privée :
`openssl genrsa -out config/jwt/private.pem -aes256 4896`
- création d'une clé public :
`openssl rsa -pubout -in config/jwt/private.pem -out config/jwt/public.pem`

# Informations sur l'API

- L'obtention du token afin de s'authentifier à l'API se fait via l'envoie des identifiants sur l'URL : `https://127.0.0.1:8000/api/login`
- via Postman dans le body indiquez ce code : 
`
{
    "username":"admin@bilemo.com",
    "password":"password"
}
`

# Lien Documentation en locale

- `https://127.0.0.1:8000/swagger/`

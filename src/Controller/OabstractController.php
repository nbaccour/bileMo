<?php
/**
 * Created by PhpStorm.
 * User: msi-n
 * Date: 16/05/2021
 * Time: 21:16
 */

namespace App\Controller;


use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use OpenApi\Annotations as OA;

/**
 * @OA\Parameter(
 *       name="id",
 *       in="path",
 *       description="ID de la resource",
 *       required=true,
 *       @OA\Schema(type="integer")
 *      )
 *
 * @OA\Response(
 *      response="NotFound",
 *      description="La resource n'existe pas",
 *      @OA\JsonContent(
 *          @OA\Property(property="message", type="string", example="La resource n'existe pas")
 *      ),
 *
 *     )
 *
 * @OA\SecurityScheme(bearerFormat="JWT", type="apiKey", securityScheme="bearer")
 *
 */
class OabstractController extends AbstractController
{

}
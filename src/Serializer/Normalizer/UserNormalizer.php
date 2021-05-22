<?php

namespace App\Serializer\Normalizer;

use Symfony\Component\Serializer\Normalizer\CacheableSupportsMethodInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="Users",
 *     description="list des utilisateurs",
 *     @OA\Property(type="integer", property="id"),
 *     @OA\Property(type="string", property="email", nullable=false),
 *     @OA\Property(type="string", property="password", nullable=false),
 *     @OA\Property(type="string", property="fullname", nullable=false),
 * )
 *
 * * @OA\Schema(
 *     schema="UserDetail",
 *     description="detail d'un utilisateur",
 *     allOf={@OA\Schema(ref="#/components/schemas/Users")}
 * )
 */
class UserNormalizer implements NormalizerInterface, CacheableSupportsMethodInterface
{
    private $normalizer;

    public function __construct(ObjectNormalizer $normalizer)
    {
        $this->normalizer = $normalizer;
    }

    public function normalize($object, $format = null, array $context = []): array
    {
        $data = $this->normalizer->normalize($object, $format, $context);

        // Here: add, edit, or delete some data

        return $data;
    }

    public function supportsNormalization($data, $format = null): bool
    {
        return $data instanceof \App\Entity\User;
    }

    public function hasCacheableSupportsMethod(): bool
    {
        return true;
    }
}

<?php

namespace App\Serializer\Normalizer;

use Symfony\Component\Serializer\Normalizer\CacheableSupportsMethodInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="Products",
 *     description="list des produits",
 *     @OA\Property(type="integer", property="id"),
 *     @OA\Property(type="string", property="name", nullable=false),
 *     @OA\Property(type="string", property="description", nullable=false),
 *     @OA\Property(type="string", property="brand", nullable=false),
 *     @OA\Property(type="integer", property="price", nullable=false),
 * )
 *
 * * @OA\Schema(
 *     schema="ProductDetail",
 *     description="detail d'un produit",
 *     allOf={@OA\Schema(ref="#/components/schemas/Products")}
 * )
 */
class ProductNormalizer implements NormalizerInterface, CacheableSupportsMethodInterface
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
        return $data instanceof \App\Entity\Product;
    }

    public function hasCacheableSupportsMethod(): bool
    {
        return true;
    }
}

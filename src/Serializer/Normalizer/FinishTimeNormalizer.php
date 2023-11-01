<?php declare(strict_types=1);

namespace App\Serializer\Normalizer;

use App\Model\FinishTime;
use Symfony\Component\PropertyInfo\Type;
use Symfony\Component\Serializer\Exception\NotNormalizableValueException;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class FinishTimeNormalizer implements NormalizerInterface, DenormalizerInterface
{
    public function normalize(mixed $object, string $format = null, array $context = [])
    {
        /** @var FinishTime $object */
        return $object->toString();
    }

    public function supportsNormalization(mixed $data, string $format = null)
    {
        return $data instanceof FinishTime;
    }

    public function denormalize(mixed $data, string $type, string $format = null, array $context = [])
    {
        if ($data === null) {
            return null;
        }

        try {
            return FinishTime::fromTime($data);
        } catch(\Exception $exception) {
            throw NotNormalizableValueException::createForUnexpectedDataType(
                $exception->getMessage(), $data, [Type::BUILTIN_TYPE_STRING], $context['deserialization_path'] ?? null, false, $exception->getCode(), $exception
            );
        }
    }

    public function supportsDenormalization(mixed $data, string $type, string $format = null)
    {
        return $type === FinishTime::class;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            FinishTime::class => true,
        ];
    }
}

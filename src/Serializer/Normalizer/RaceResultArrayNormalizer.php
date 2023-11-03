<?php

namespace App\Serializer\Normalizer;

use App\Model\FinishTime;
use Symfony\Component\PropertyInfo\Type;
use Symfony\Component\Serializer\Exception\NotNormalizableValueException;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class RaceResultArrayNormalizer implements NormalizerInterface
{
    public function normalize($object, string $format = null, array $context = []): array
    {
        $data = [];

        try {
            foreach ($object as $result) {
                $data[] = [
                    'race_id'                => $context['race_id'],
                    'full_name'              => $result['fullName'],
                    'distance'               => $result['distance'],
                    'finish_time'            => FinishTime::fromTime($result['time'])->toSeconds(),
                    'overall_placement'      => null,
                    'age_category_placement' => null,
                    'age_category'           => $result['ageCategory']
                ];
            }
        } catch(\Exception $exception) {
            throw NotNormalizableValueException::createForUnexpectedDataType(
                $exception->getMessage(), $data, [Type::BUILTIN_TYPE_ARRAY], $context['deserialization_path'] ?? null, false, $exception->getCode(), $exception
            );
        }

        return $data;
    }

    public function supportsNormalization($data, string $format = null, array $context = []): bool
    {
        return $format === 'csv' && is_array($data) && array_key_exists('race_id', $context);
    }

    public static function getSupportedTypes(?string $format): array
    {
        return [
            'object' => null,
            '*' => false,
        ];
    }
}

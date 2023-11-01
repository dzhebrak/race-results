<?php declare(strict_types=1);

namespace App\Serializer\NameConverter;

use App\Entity\RaceResult;
use Symfony\Component\Serializer\NameConverter\AdvancedNameConverterInterface;

class RaceResultFinishTimeNameConverter implements AdvancedNameConverterInterface
{
    public function normalize(string $propertyName, string $class = null, string $format = null, array $context = []): string
    {
        if ($class === RaceResult::class && $propertyName === 'finishTime') {
            return 'time';
        }

        return $propertyName;
    }

    public function denormalize(string $propertyName, string $class = null, string $format = null, array $context = []): string
    {
        if ($class === RaceResult::class && $propertyName === 'time') {
            return 'finishTime';
        }

        return $propertyName;
    }
}

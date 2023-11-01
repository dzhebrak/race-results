<?php declare(strict_types=1);

namespace App\DBAL\Type;

use App\Model\FinishTime;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\ConversionException;
use Doctrine\DBAL\Types\Type;

class FinishTimeType extends Type
{
    public const NAME = 'finish_time';

    public function getName(): string
    {
        return self::NAME;
    }

    /**
     * {@inheritDoc}
     */
    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return $platform->getIntegerTypeDeclarationSQL($column);
    }

    /**
     * {@inheritDoc}
     */
    public function getBindingType(): int
    {
        return ParameterType::INTEGER;
    }

    public function convertToPHPValue($value, AbstractPlatform $platform): mixed
    {
        try {
            return new FinishTime($value);
        } catch(\Exception $e) {
            throw ConversionException::conversionFailedFormat(
                $value,
                $this->getName(),
                $platform->getDateTimeFormatString(),
                $e,
            );
        }
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform): mixed
    {
        if ($value === null) {
            return $value;
        }

        if ($value instanceof FinishTime) {
            return $value->toSeconds();
        }

        throw ConversionException::conversionFailedInvalidType(
            $value,
            $this->getName(),
            ['null', FinishTime::class],
        );
    }
}

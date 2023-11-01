<?php

namespace App\Factory;

use App\Entity\Race;
use App\Model\FinishTime;
use App\Repository\RaceRepository;
use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Proxy;
use Zenstruck\Foundry\RepositoryProxy;

/**
 * @extends ModelFactory<Race>
 *
 * @method        Race|Proxy                     create(array|callable $attributes = [])
 * @method static Race|Proxy                     createOne(array $attributes = [])
 * @method static Race|Proxy                     find(object|array|mixed $criteria)
 * @method static Race|Proxy                     findOrCreate(array $attributes)
 * @method static Race|Proxy                     first(string $sortedField = 'id')
 * @method static Race|Proxy                     last(string $sortedField = 'id')
 * @method static Race|Proxy                     random(array $attributes = [])
 * @method static Race|Proxy                     randomOrCreate(array $attributes = [])
 * @method static RaceRepository|RepositoryProxy repository()
 * @method static Race[]|Proxy[]                 all()
 * @method static Race[]|Proxy[]                 createMany(int $number, array|callable $attributes = [])
 * @method static Race[]|Proxy[]                 createSequence(iterable|callable $sequence)
 * @method static Race[]|Proxy[]                 findBy(array $attributes)
 * @method static Race[]|Proxy[]                 randomRange(int $min, int $max, array $attributes = [])
 * @method static Race[]|Proxy[]                 randomSet(int $number, array $attributes = [])
 */
final class RaceFactory extends ModelFactory
{
    protected function getDefaults(): array
    {
        return [
            'averageFinishTimeForLongDistance' => new FinishTime(self::faker()->numberBetween(2 * 60 * 60, 32 * 60 * 60)),
            'averageFinishTimeForMediumDistance' => new FinishTime(self::faker()->numberBetween(30 * 60, 5 * 60 * 60)),
            'date' => \DateTimeImmutable::createFromMutable(self::faker()->dateTime()),
            'title' => self::faker()->text(255),
        ];
    }

    protected static function getClass(): string
    {
        return Race::class;
    }
}

<?php

namespace App\Factory;

use App\Entity\RaceResult;
use App\Repository\RaceResultRepository;
use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Proxy;
use Zenstruck\Foundry\RepositoryProxy;

/**
 * @extends ModelFactory<RaceResult>
 *
 * @method        RaceResult|Proxy                     create(array|callable $attributes = [])
 * @method static RaceResult|Proxy                     createOne(array $attributes = [])
 * @method static RaceResult|Proxy                     find(object|array|mixed $criteria)
 * @method static RaceResult|Proxy                     findOrCreate(array $attributes)
 * @method static RaceResult|Proxy                     first(string $sortedField = 'id')
 * @method static RaceResult|Proxy                     last(string $sortedField = 'id')
 * @method static RaceResult|Proxy                     random(array $attributes = [])
 * @method static RaceResult|Proxy                     randomOrCreate(array $attributes = [])
 * @method static RaceResultRepository|RepositoryProxy repository()
 * @method static RaceResult[]|Proxy[]                 all()
 * @method static RaceResult[]|Proxy[]                 createMany(int $number, array|callable $attributes = [])
 * @method static RaceResult[]|Proxy[]                 createSequence(iterable|callable $sequence)
 * @method static RaceResult[]|Proxy[]                 findBy(array $attributes)
 * @method static RaceResult[]|Proxy[]                 randomRange(int $min, int $max, array $attributes = [])
 * @method static RaceResult[]|Proxy[]                 randomSet(int $number, array $attributes = [])
 */
final class RaceResultFactory extends ModelFactory
{
    protected function getDefaults(): array
    {
        return [
            'ageCategory' => self::faker()->randomElement([
                'M18-25', 'F18-25', 'M26-34', 'F26-34', 'M35-43', 'F35-43',
            ]),
            'distance' => self::faker()->boolean(70) ? 'medium' : 'long',
            'fullName' => sprintf('%s %s', self::faker()->firstName(self::faker()->boolean() ? 'male' : 'female'), self::faker()->lastName()),
            'race' => RaceFactory::random(),
        ];
    }

    protected static function getClass(): string
    {
        return RaceResult::class;
    }
}

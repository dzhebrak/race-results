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
    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#factories-as-services
     *
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#model-factories
     */
    protected function getDefaults(): array
    {
        return [
            'averageFinishTimeForLongDistance' => new FinishTime(self::faker()->numberBetween(59 * 60 /* 59 minutes */, 32 * 60 * 60 /* 32 hours */)),
            'averageFinishTimeForMediumDistance' => new FinishTime(self::faker()->numberBetween(29 * 60 /* 29 minutes */, 24 * 60 * 60 /* 24 hours */)),
            'date' => \DateTimeImmutable::createFromMutable(self::faker()->dateTime()),
            'title' => self::faker()->text(255),
        ];
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
     */
    protected function initialize(): self
    {
        return $this
            // ->afterInstantiate(function(Race $race): void {})
        ;
    }

    protected static function getClass(): string
    {
        return Race::class;
    }
}

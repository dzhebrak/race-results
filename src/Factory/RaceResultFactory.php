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
    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#factories-as-services
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
            'ageCategory' => self::faker()->randomElement([
                'M18-25', 'F18-25', 'M26-34', 'F26-34', 'M35-43', 'F35-43',
            ]),
            'distance' => self::faker()->randomElement(['long', 'medium']),
            'finishTime' => null, // TODO add FINISH_TIME type manually
            'fullName' => sprintf('%s %s', self::faker()->firstName(self::faker()->boolean() ? 'male' : 'female'), self::faker()->lastName()),
            'race' => RaceFactory::random(),
        ];
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
     */
    protected function initialize(): self
    {
        return $this
            // ->afterInstantiate(function(RaceResult $raceResult): void {})
        ;
    }

    protected static function getClass(): string
    {
        return RaceResult::class;
    }
}

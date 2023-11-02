<?php declare(strict_types=1);

namespace App\Tests\Functional\ApiResource;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Symfony\Bundle\Test\Client;
use App\Entity\Race;
use App\Entity\RaceResult;
use App\Model\FinishTime;
use App\Model\RaceDistance;
use App\Tests\Functional\ApiTestHydraContextBuilderTrait;
use Doctrine\ORM\EntityManagerInterface;

// TODO: test violations
class RaceResultTest extends ApiTestCase
{
    use ApiTestHydraContextBuilderTrait;

    private Client $client;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = $this->client->getContainer()->get('doctrine.orm.entity_manager');
    }

    public function testGetRaceResults()
    {
        $race = $this->entityManager->createQuery('SELECT r, RAND() as HIDDEN rand from App\Entity\Race r ORDER BY rand')->setMaxResults(1)->getOneOrNullResult();
        self::assertInstanceOf(Race::class, $race);

        $response = $this->client->request('GET', sprintf('/api/races/%d/results', $race->getId()));

        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        self::assertJsonContains($this->buildHydraCollectionSubset(
            '/api/contexts/RaceResult',
            sprintf('/api/races/%d/results', $race->getId()),
            $race->getResults()->count(),
            [
                'order[fullName]', 'order[finishTime]', 'order[distance]', 'order[ageCategory]', 'order[overallPlacement]', 'order[ageCategoryPlacement]',
                'fullName', 'fullName[]', 'distance', 'distance[]', 'ageCategory', 'ageCategory[]'
            ]
        ));

        self::assertIsArray($response->toArray()['hydra:member']);
        self::assertCount(min($race->getResults()->count(), 30), $response->toArray()['hydra:member']);

        $item = $response->toArray()['hydra:member'][0];

        self::assertIsArray($item);
        self::assertSame(array_keys($response->toArray()['hydra:member'][0]), [
            'fullName', 'distance', 'time', 'overallPlacement', 'ageCategoryPlacement', 'ageCategory'
        ]);

        self::assertNotEmpty($item['fullName']);
        self::assertContains($item['distance'], RaceDistance::values());
        self::assertMatchesRegularExpression(FinishTime::TIME_REGEX_PATTERN, $item['time']);
        self::assertTrue($item['overallPlacement'] === null || is_int($item['overallPlacement']));
        self::assertTrue($item['ageCategoryPlacement'] === null || is_int($item['ageCategoryPlacement']));
        self::assertMatchesRegularExpression(RaceResult::AGE_CATEGORY_REGEX_PATTERN, $item['ageCategory']);

        // ensure that there are no placements set for medium distance
        self::assertCount(0, array_filter($response->toArray()['hydra:member'], static fn(array $item) => $item['distance'] === 'medium' && ($item['overallPlacement'] || $item['ageCategoryPlacement'])));

        self::assertMatchesResourceItemJsonSchema(RaceResult::class);
    }

    public function testUpdateRaceResult()
    {
        $raceResult = $this->entityManager->createQuery('SELECT r, RAND() as HIDDEN rand from App\Entity\RaceResult r ORDER BY rand')->setMaxResults(1)->getOneOrNullResult();
        self::assertInstanceOf(RaceResult::class, $raceResult);

        $raceResultId = $raceResult->getId();
        $newFullName = 'New full name 1';
        $newFinishTime = '2:30:00';
        $newDistance = $raceResult->getDistance() === 'long' ? 'medium' : 'long';
        $newAgeCategory = 'F35-43';

        $response = $this->client->request('PATCH', sprintf('/api/race_results/%s', $raceResult->getId()), [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => [
                'fullName' => $newFullName,
                'finishTime' => $newFinishTime,
                'distance' => $newDistance,
                'ageCategory' => $newAgeCategory
            ]
        ]);

        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        self::assertSame($newFullName, $response->toArray()['fullName']);
        self::assertSame($newFinishTime, $response->toArray()['time']);
        self::assertSame($newAgeCategory, $response->toArray()['ageCategory']);
        self::assertSame($newDistance, $response->toArray()['distance']);
        // TODO: test placements
    }
}

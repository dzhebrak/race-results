<?php declare(strict_types=1);

namespace App\Tests\Functional\ApiResource;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Entity\Race;
use App\Entity\RaceResult;
use App\Model\FinishTime;
use App\Story\RaceStory;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class RaceTest extends ApiTestCase
{
    use ResetDatabase, Factories;

    public function testGetCollection()
    {
        RaceStory::load();

        $client = static::createClient();
        $response = $client->request('GET', '/api/races');

        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');

        self::assertJsonContains([
            '@context'         => '/api/contexts/Race',
            '@id'              => '/api/races',
            '@type'            => 'hydra:Collection',
            'hydra:totalItems' => RaceStory::RACES_NUMBER,
            'hydra:view'       => [
                '@id'         => '/api/races?page=1',
                '@type'       => 'hydra:PartialCollectionView',
                'hydra:first' => '/api/races?page=1',
                'hydra:last'  => sprintf('/api/races?page=%d', ceil(RaceStory::RACES_NUMBER / 30)),
                'hydra:next'  => '/api/races?page=2',
            ],
            'hydra:search' => [
                '@type' => "hydra:IriTemplate",
                'hydra:template' => "/api/races{?order[title],order[date],order[averageFinishTimeForMediumDistance],order[averageFinishTimeForLongDistance],title}",
                'hydra:variableRepresentation' => 'BasicRepresentation',
                'hydra:mapping' => [
                    [
                        "@type"    => "IriTemplateMapping",
                        "variable" => "order[title]",
                        "property" => "title",
                        "required" => false
                    ],
                    [
                        "@type"=> "IriTemplateMapping",
                        "variable" => "order[date]",
                        "property" => "date",
                        "required" => false
                    ],
                    [
                        "@type" => "IriTemplateMapping",
                        "variable" => "order[averageFinishTimeForMediumDistance]",
                        "property"=> "averageFinishTimeForMediumDistance",
                        "required"=> false
                    ],
                    [
                        "@type" => "IriTemplateMapping",
                        "variable" => "order[averageFinishTimeForLongDistance]",
                        "property" => "averageFinishTimeForLongDistance",
                        "required" => false
                    ],
                    [
                        "@type" => "IriTemplateMapping",
                        "variable" => "title",
                        "property" => "title",
                        "required" => false
                    ]
                ],
            ]
        ]);

        self::assertIsArray($response->toArray()['hydra:member']);
        self::assertCount(30, $response->toArray()['hydra:member']);

        $item = $response->toArray()['hydra:member'][0];

        self::assertIsArray($item);
        self::assertSame(array_keys($response->toArray()['hydra:member'][0]), [
            'id', 'title', 'date', 'averageFinishTimeForMediumDistance', 'averageFinishTimeForLongDistance',
        ]);

        self::assertIsInt($item['id']);
        self::assertNotEmpty($item['title']);
        self::assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}$/', $item['date']);
        self::assertMatchesRegularExpression(FinishTime::TIME_REGEX_PATTERN, $item['averageFinishTimeForMediumDistance']);
        self::assertMatchesRegularExpression(FinishTime::TIME_REGEX_PATTERN, $item['averageFinishTimeForLongDistance']);

        self::assertMatchesResourceItemJsonSchema(Race::class);
    }

    public function testCreateRace()
    {
        $client = static::createClient();

        $file = new UploadedFile(
              $client->getContainer()->getParameter('kernel.project_dir').'/var/datasets/race-results.csv',
            'race-results.csv',
            'application/csv',
        );

        $raceTitle = 'Test Race title';
        $raceDate = '2023-11-01';

        $client->request(Request::METHOD_POST, '/api/races', [
            'headers' => ['Content-Type' => 'multipart/form-data'],
            'extra'   => [
                'parameters' => [
                    'title' => $raceTitle,
                    'date'  => $raceDate,
                ],
                'files' => [
                    'file' => $file,
                ],
            ],
        ]);

        self::assertResponseStatusCodeSame(201);
        self::assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');

        $manager = $client->getContainer()->get('doctrine.orm.entity_manager');
        $race = $manager->createQuery('SELECT r FROM App\Entity\Race r WHERE r.title=:title AND r.date=:date')->setParameters(['title' => $raceTitle, 'date' => $raceDate])->getOneOrNullResult();

        self::assertInstanceOf(Race::class, $race);

        self::assertEquals(100, $manager->createQuery('SELECT COUNT(rr) FROM App\Entity\RaceResult rr WHERE rr.race=:race')->setParameters(['race' => $race])->getSingleScalarResult());
        self::assertEquals(
            36,
            $manager->createQuery('SELECT COUNT(rr) FROM App\Entity\RaceResult rr WHERE rr.race=:race AND rr.distance=:distance')->setParameters(['race' => $race, 'distance' => 'long'])->getSingleScalarResult()
        );
        self::assertEquals(
            64,
            $manager->createQuery('SELECT COUNT(rr) FROM App\Entity\RaceResult rr WHERE rr.race=:race AND rr.distance=:distance')->setParameters(['race' => $race, 'distance' => 'medium'])->getSingleScalarResult()
        );

        // Medium distance results don’t have placements.
        self::assertEquals(
            0,
            $manager->createQuery(
                'SELECT COUNT(rr) FROM App\Entity\RaceResult rr WHERE rr.race=:race AND rr.distance=:distance AND (rr.overallPlacement IS NOT NULL OR rr.ageCategoryPlacement IS NOT NULL)'
            )->setParameters(['race' => $race, 'distance' => 'medium'])->getSingleScalarResult()
        );

        // first in F18-25 ageCategory
        $raceResult = $manager
            ->createQuery('SELECT rr FROM App\Entity\RaceResult rr WHERE rr.race=:race AND rr.fullName=:fullName')
            ->setParameters(['race' => $race, 'fullName' => 'Jalen Bahringer'])->getOneOrNullResult()
        ;

        self::assertInstanceOf(RaceResult::class, $raceResult);
        self::assertSame('F18-25', $raceResult->getAgeCategory());
        self::assertSame(10, $raceResult->getOverallPlacement());
        self::assertSame(1, $raceResult->getAgeCategoryPlacement());
        self::assertSame('4:22:44', $raceResult->getFinishTime()->toString());

        // last in F18-25 ageCategory
        $raceResult = $manager
            ->createQuery('SELECT rr FROM App\Entity\RaceResult rr WHERE rr.race=:race AND rr.fullName=:fullName')
            ->setParameters(['race' => $race, 'fullName' => 'Madelynn Corwin'])->getOneOrNullResult()
        ;

        self::assertInstanceOf(RaceResult::class, $raceResult);
        self::assertSame('F18-25', $raceResult->getAgeCategory());
        self::assertSame(35, $raceResult->getOverallPlacement());
        self::assertSame(6, $raceResult->getAgeCategoryPlacement());
        self::assertSame('13:21:08', $raceResult->getFinishTime()->toString());

        // random racer
        $raceResult = $manager
            ->createQuery('SELECT rr FROM App\Entity\RaceResult rr WHERE rr.race=:race AND rr.fullName=:fullName')
            ->setParameters(['race' => $race, 'fullName' => 'Selmer Wolff'])->getOneOrNullResult()
        ;

        self::assertInstanceOf(RaceResult::class, $raceResult);
        self::assertSame('M35-43', $raceResult->getAgeCategory());
        self::assertSame(20, $raceResult->getOverallPlacement());
        self::assertSame(5, $raceResult->getAgeCategoryPlacement());
        self::assertSame('7:32:31', $raceResult->getFinishTime()->toString());
    }
}

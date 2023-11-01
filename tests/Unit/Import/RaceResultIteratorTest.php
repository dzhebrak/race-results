<?php

namespace App\Tests\Unit\Import;

use App\Import\RaceResultsIterator;
use PHPUnit\Framework\TestCase;

class RaceResultIteratorTest extends TestCase
{
    public function testIterator()
    {
        $this->assertEquals(
            $this->getSortedElements(),
            array_values(iterator_to_array(new RaceResultsIterator($this->getElements())))
        );
    }

    private function getElements(): array
    {
        return [
            ['fullName' => 'Matthias Floyd', 'distance' => 'medium', 'time' => '05:15:24', 'ageCategory' => 'M18-25'],
            ['fullName' => 'Toby Phillips', 'distance' => 'long', 'time' => '04:07:45', 'ageCategory' => 'M26-34'],
            ['fullName' => 'Paloma Mclean', 'distance' => 'long', 'time' => '04:04:31', 'ageCategory' => 'F18-25'],
            ['fullName' => 'Willow Brock', 'distance' => 'medium', 'time' => '03:04:30', 'ageCategory' => 'M18-25'],
            ['fullName' => 'Alissa Harris', 'distance' => 'long', 'time' => '05:04:24', 'ageCategory' => 'F18-25'],
            ['fullName' => 'Dania Travis', 'distance' => 'long', 'time' => '06:04:12', 'ageCategory' => 'F26-34'],
            ['fullName' => 'Lorena Villegas', 'distance' => 'medium', 'time' => '02:09:31', 'ageCategory' => 'F26-34'],
            ['fullName' => 'Marc Rivera', 'distance' => 'long', 'time' => '06:23:14', 'ageCategory' => 'M26-34'],
            ['fullName' => 'Ryan Roberts', 'distance' => 'long', 'time' => '06:15:45', 'ageCategory' => 'M26-34'],
            ['fullName' => 'Sergio Spears', 'distance' => 'medium', 'time' => '02:13:45', 'ageCategory' => 'M35-43'],
        ];
    }

    private function getSortedElements(): array
    {
        return [
            ['fullName' => 'Lorena Villegas', 'distance' => 'medium', 'time' => '02:09:31', 'ageCategory' => 'F26-34'],
            ['fullName' => 'Sergio Spears', 'distance' => 'medium', 'time' => '02:13:45', 'ageCategory' => 'M35-43'],
            ['fullName' => 'Willow Brock', 'distance' => 'medium', 'time' => '03:04:30', 'ageCategory' => 'M18-25'],
            ['fullName' => 'Paloma Mclean', 'distance' => 'long', 'time' => '04:04:31', 'ageCategory' => 'F18-25'],
            ['fullName' => 'Toby Phillips', 'distance' => 'long', 'time' => '04:07:45', 'ageCategory' => 'M26-34'],
            ['fullName' => 'Alissa Harris', 'distance' => 'long', 'time' => '05:04:24', 'ageCategory' => 'F18-25'],
            ['fullName' => 'Matthias Floyd', 'distance' => 'medium', 'time' => '05:15:24', 'ageCategory' => 'M18-25'],
            ['fullName' => 'Dania Travis', 'distance' => 'long', 'time' => '06:04:12', 'ageCategory' => 'F26-34'],
            ['fullName' => 'Ryan Roberts', 'distance' => 'long', 'time' => '06:15:45', 'ageCategory' => 'M26-34'],
            ['fullName' => 'Marc Rivera', 'distance' => 'long', 'time' => '06:23:14', 'ageCategory' => 'M26-34'],
        ];
    }
}

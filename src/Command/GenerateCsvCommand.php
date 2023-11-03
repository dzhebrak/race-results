<?php

namespace App\Command;

use App\Model\FinishTime;
use Faker\Factory;
use Faker\Generator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Serializer\SerializerInterface;

#[AsCommand(
    name: 'app:generate-csv',
    description: 'Add a short description for your command',
)]
class GenerateCsvCommand extends Command
{

    private Generator $faker;
    public function __construct(private SerializerInterface $serializer)
    {
        parent::__construct();
        $this->faker = Factory::create();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('rows', InputArgument::OPTIONAL, 'Number of rows for csv', 100)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $totalRows = $input->getArgument('rows');
        $rows = [];

        for ($j = 0; $j < $totalRows; $j++) {
            $rows[] = [
                'fullName' => sprintf('%s %s', $this->faker->firstName($this->faker->boolean() ? 'male' : 'female'), $this->faker->lastName()),
                'distance' => $this->faker->boolean(70) ? 'medium' : 'long',
                'time' => (new FinishTime($this->faker->numberBetween(30 * 60, 30 * 60 * 60)))->toString(),
                'ageCategory' => $this->faker->randomElement([
                    'M18-25', 'F18-25', 'M26-34', 'F26-34', 'M35-43', 'F35-43',
                ]),
            ];
        }

        $io->write(
            $this->serializer->serialize($rows, 'csv')
        );

        return Command::SUCCESS;
    }
}

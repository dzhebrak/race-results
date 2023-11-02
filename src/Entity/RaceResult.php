<?php

namespace App\Entity;

use ApiPlatform\Doctrine\Common\Filter\SearchFilterInterface;
use ApiPlatform\Doctrine\Orm\Filter\NumericFilter;
use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Patch;
use App\Model\FinishTime;
use App\Model\RaceDistance;
use App\Repository\RaceResultRepository;
use App\State\RaceResultStateProcessor;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation as Serializer;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[ORM\Entity(repositoryClass: RaceResultRepository::class)]
#[ApiResource(
    operations: [
        new Patch(
            processor: RaceResultStateProcessor::class,
        ),
        new GetCollection(
            uriTemplate: '/races/{raceId}/results',
            uriVariables: [
                'raceId' => new Link(
                    fromProperty: 'results',
                    fromClass: Race::class,
                )
            ],
        )
    ],
    normalizationContext: [
        'groups' => ['race-result:read'],
        'skip_null_values' => false,
    ],
    denormalizationContext: [
        'groups' => ['race-result:write']
    ],
)]
#[ApiFilter(OrderFilter::class, properties: ['fullName', 'finishTime', 'distance', 'ageCategory', 'overallPlacement', 'ageCategoryPlacement'])]
class RaceResult
{
    public const AGE_CATEGORY_REGEX_PATTERN = '/^[A-Z]\d{1,3}\-\d{1,3}$/';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Serializer\Groups(['race:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    #[Serializer\Groups(['race-result:read', 'race-result:write'])]
    #[ApiFilter(SearchFilter::class, strategy: SearchFilterInterface::STRATEGY_EXACT)]
    private ?string $fullName = null;

    #[ORM\Column(length: 16)]
    #[Assert\NotBlank()]
    #[Assert\Choice(callback: [RaceDistance::class, 'values'])]
    #[Serializer\Groups(['race-result:read', 'race-result:write'])]
    #[ApiFilter(SearchFilter::class, strategy: SearchFilterInterface::STRATEGY_EXACT)]
    private ?string $distance = null;

    #[ORM\Column(type: 'finish_time')]
    #[Assert\NotNull()]
    #[Serializer\Groups(['race-result:read', 'race-result:write'])]
    #[ApiProperty(
        openapiContext: [
            'example' => '4:07:45',
            'type' => 'string',
            'format' => 'finish_time',
            'pattern' => FinishTime::TIME_REGEX_PATTERN,
        ]
    )]
    #[ApiFilter(NumericFilter::class)]
    private ?FinishTime $finishTime = null;

    #[ORM\Column(nullable: true)]
    #[Serializer\Groups(['race-result:read'])]
    private ?int $overallPlacement = null;

    #[ORM\Column(nullable: true)]
    #[Serializer\Groups(['race-result:read'])]
    private ?int $ageCategoryPlacement = null;

    #[ORM\Column(length: 16)]
    #[Assert\NotBlank()]
    #[Assert\Regex(pattern: self::AGE_CATEGORY_REGEX_PATTERN)]
    #[Serializer\Groups(['race-result:read', 'race-result:write'])]
    #[ApiProperty(
        openapiContext: [
            'example' => 'M18-25',
        ]
    )]
    #[ApiFilter(SearchFilter::class, strategy: SearchFilterInterface::STRATEGY_EXACT)]
    private ?string $ageCategory = null;

    #[ORM\ManyToOne(inversedBy: 'results')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull]
    private ?Race $race = null;

    #[Serializer\Ignore]
    public function isOverallPlacementRequired(): bool
    {
        return $this->isLongDistance();
    }

    #[Serializer\Ignore]
    public function isAgeCategoryPlacementRequired(): bool
    {
        return $this->isLongDistance();
    }

    #[Serializer\Ignore]
    public function isLongDistance(): bool
    {
        return $this->getDistance() === RaceDistance::Long->value;
    }

    #[Serializer\Ignore]
    public function isMediumDistance(): bool
    {
        return $this->getDistance() === RaceDistance::Medium->value;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFullName(): ?string
    {
        return $this->fullName;
    }

    public function setFullName(string $fullName): static
    {
        $this->fullName = $fullName;

        return $this;
    }

    public function getDistance(): ?string
    {
        return $this->distance;
    }

    public function setDistance(string $distance): static
    {
        $this->distance = $distance;

        return $this;
    }

    public function getFinishTime(): ?FinishTime
    {
        return $this->finishTime;
    }

    public function setFinishTime(FinishTime $finishTime): static
    {
        $this->finishTime = $finishTime;

        return $this;
    }

    public function getOverallPlacement(): ?int
    {
        return $this->overallPlacement;
    }

    public function setOverallPlacement(?int $overallPlacement): static
    {
        if (!$this->isOverallPlacementRequired()) {
            return $this;
        }

        $this->overallPlacement = $overallPlacement;

        return $this;
    }

    public function getAgeCategoryPlacement(): ?int
    {
        return $this->ageCategoryPlacement;
    }

    public function setAgeCategoryPlacement(?int $ageCategoryPlacement): static
    {
        if (!$this->isAgeCategoryPlacementRequired()) {
            return $this;
        }

        $this->ageCategoryPlacement = $ageCategoryPlacement;

        return $this;
    }

    public function getAgeCategory(): ?string
    {
        return $this->ageCategory;
    }

    public function setAgeCategory(string $ageCategory): static
    {
        $this->ageCategory = $ageCategory;

        return $this;
    }

    public function getRace(): ?Race
    {
        return $this->race;
    }

    public function setRace(?Race $race): static
    {
        $this->race = $race;

        return $this;
    }
}

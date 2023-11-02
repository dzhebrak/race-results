<?php

namespace App\Entity;

use ApiPlatform\Doctrine\Common\Filter\SearchFilterInterface;
use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use App\Model\FinishTime;
use App\Repository\RaceRepository;
use App\State\RaceCreateStateProcessor;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Serializer\Annotation as Serializer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Validator\Constraints as Assert;


#[ORM\Entity(repositoryClass: RaceRepository::class)]
#[ORM\UniqueConstraint(name: 'title_date_uidx', columns: ['title', 'date'])]
#[UniqueEntity(['title', 'date'])]
#[ApiResource(
    operations: [
        new GetCollection(

        ),
        new Post(
            inputFormats: ['multipart' => ['multipart/form-data']],
            processor: RaceCreateStateProcessor::class,
        )
    ],
    normalizationContext: [
        'groups' => ['race:read'],
    ],
    denormalizationContext: [
        'groups' => ['race:write'],
    ]
)]
#[ApiFilter(OrderFilter::class, properties: ['title', 'date', 'averageFinishTimeForMediumDistance', 'averageFinishTimeForLongDistance'])]
class Race
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Serializer\Groups(['race:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank()]
    #[Serializer\Groups(['race:read', 'race:write'])]
    #[ApiFilter(SearchFilter::class, strategy: SearchFilterInterface::STRATEGY_PARTIAL)]
    private ?string $title = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    #[Assert\NotNull()]
    #[Serializer\Groups(['race:read', 'race:write'])]
    #[Serializer\Context(
        normalizationContext: [
            DateTimeNormalizer::FORMAT_KEY => 'Y-m-d'
        ],
    )]
    #[ApiProperty(
        openapiContext: [
            'format'=> 'date',
            'example' => '2023-01-01'
        ],
    )]
    private ?\DateTimeImmutable $date = null;

    #[Assert\NotNull()]
    #[Assert\File(extensions: ['csv'])]
    #[Serializer\Groups(['race:write'])]
    #[ApiProperty(
        openapiContext: [
            'type'    => 'string',
            'format'  => 'binary',
        ]
    )]
    public ?File $file = null;

    #[ORM\Column(type: 'finish_time')]
    #[Assert\NotNull(groups: ['import'])]
    #[Serializer\Groups(['race:read'])]
    #[ApiProperty(
        openapiContext: [
            'example' => '4:07:45',
            'type'   => 'string',
            'format' => 'finish_time',
            'pattern' => FinishTime::TIME_REGEX_PATTERN,
        ]
    )]
    private ?FinishTime $averageFinishTimeForMediumDistance = null;

    #[ORM\Column(type: 'finish_time')]
    #[Assert\NotNull(groups: ['import'])]
    #[Serializer\Groups(['race:read'])]
    #[ApiProperty(
        openapiContext: [
            'example' => '6:23:14',
            'type'   => 'string',
            'format' => 'finish_time',
            'pattern' => FinishTime::TIME_REGEX_PATTERN,
        ],
    )]
    private ?FinishTime $averageFinishTimeForLongDistance = null;

    #[ORM\OneToMany(mappedBy: 'race', targetEntity: RaceResult::class, cascade: ['persist'], fetch: 'EXTRA_LAZY', orphanRemoval: true)]
    private Collection $results;

    public function __construct()
    {
        $this->results = new ArrayCollection();
        $this->averageFinishTimeForMediumDistance = new FinishTime(0);
        $this->averageFinishTimeForLongDistance = new FinishTime(0);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getDate(): ?\DateTimeImmutable
    {
        return $this->date;
    }

    public function setDate(\DateTimeImmutable $date): static
    {
        $this->date = $date;

        return $this;
    }

    public function getAverageFinishTimeForMediumDistance(): ?FinishTime
    {
        return $this->averageFinishTimeForMediumDistance;
    }

    public function setAverageFinishTimeForMediumDistance(FinishTime $averageFinishTimeForMediumDistance): static
    {
        $this->averageFinishTimeForMediumDistance = $averageFinishTimeForMediumDistance;

        return $this;
    }

    public function getAverageFinishTimeForLongDistance(): ?FinishTime
    {
        return $this->averageFinishTimeForLongDistance;
    }

    public function setAverageFinishTimeForLongDistance(FinishTime $averageFinishTimeForLongDistance): static
    {
        $this->averageFinishTimeForLongDistance = $averageFinishTimeForLongDistance;

        return $this;
    }

    /**
     * @return Collection<int, RaceResult>
     */
    public function getResults(): Collection
    {
        return $this->results;
    }

    public function addResult(RaceResult $result): static
    {
        if (!$this->results->contains($result)) {
            $this->results->add($result);
            $result->setRace($this);
        }

        return $this;
    }

    public function removeResult(RaceResult $result): static
    {
        if ($this->results->removeElement($result)) {
            // set the owning side to null (unless already changed)
            if ($result->getRace() === $this) {
                $result->setRace(null);
            }
        }

        return $this;
    }
}

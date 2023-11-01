<?php declare(strict_types=1);

namespace App\Tests\Functional;

trait ApiTestHydraContextBuilderTrait
{
    private function buildHydraCollectionSubset(string $context, string $iri, int $totalResults, array $filters, int $resultsPerPage = 30): array
    {
        $expectedSubset = [
            '@context'         => $context,
            '@id'              => $iri,
            '@type'            => 'hydra:Collection',
            'hydra:totalItems' => $totalResults,
            'hydra:search'     => $this->buildHydraSearchProperty($iri, $filters)
        ];

        if ($totalResults > $resultsPerPage) {
            $expectedSubset['hydra:view'] = $this->buildHydraViewProperty($iri, $totalResults, $resultsPerPage);
        }

        return $expectedSubset;
    }

    private function buildHydraViewProperty(string $iri, int $totalResults, int $resultsPerPage=30): array
    {
        return [
            '@id'         => sprintf('%s?page=1', $iri),
            '@type'       => 'hydra:PartialCollectionView',
            'hydra:first' => sprintf('%s?page=1', $iri),
            'hydra:last'  => sprintf('%s?page=%d', $iri, (int)ceil($totalResults / $resultsPerPage)),
            'hydra:next'  => sprintf('%s?page=2', $iri),
        ];
    }

    private function buildHydraSearchProperty(string $iri, array $filters): array
    {
        $context = [
            '@type'                        => "hydra:IriTemplate",
            'hydra:template'               => sprintf('%s{?%s}', $iri, implode(',', $filters)),
            'hydra:variableRepresentation' => 'BasicRepresentation',
            'hydra:mapping' => []
        ];

        foreach ($filters as $filter) {
            preg_match('/\[(.+?)]/', $filter, $matches);
            $property = count($matches) === 2 ? $matches[1] : $filter;

            if (str_ends_with($property, '[]')) {
                $property = mb_substr($property, 0, -2);
            }

            $context['hydra:mapping'][] = [
                "@type"    => "IriTemplateMapping",
                "variable" => $filter,
                "property" => $property,
                "required" => false
            ];
        }

        return $context;
    }
}

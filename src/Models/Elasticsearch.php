<?php declare(strict_types=1);

namespace VitesseCms\Search\Models;

use Elasticsearch\Client;
use ONGR\ElasticsearchDSL\Query\FullText\MatchQuery;
use ONGR\ElasticsearchDSL\Query\FullText\QueryStringQuery;
use ONGR\ElasticsearchDSL\Query\TermLevel\RangeQuery;
use ONGR\ElasticsearchDSL\Search;
use VitesseCms\Content\Models\Item;
use VitesseCms\Core\AbstractInjectable;
use VitesseCms\Core\Utils\DebugUtil;
use VitesseCms\Datafield\Models\Datafield;
use VitesseCms\Datagroup\Models\Datagroup;
use VitesseCms\Language\Models\Language;
use function count;
use function is_array;
use function is_object;
use function is_string;

//TODO : https://www.elastic.co/guide/en/elasticsearch/client/php-api/current/_configuration.html

/**
 * curl -XGET '127.0.0.1:9200/craftbeershirts_nl/_search?pretty=true' -d '
 * {
 * "query" : {
 * "match_all" : {}
 * }
 * }'
 */
class Elasticsearch extends AbstractInjectable
{

    /**
     * @var Client
     */
    protected $client;

    /**
     * @var bool
     */
    protected $clientEnabled;

    /**
     * @var string
     */
    protected $index;

    public function __construct(Client $client, string $index)
    {
        $this->client = $client;
        $this->index = $index;
        $this->clientEnabled = false;

        if (!$this->client->ping()) :
            if (!DebugUtil::isDev()) :
                /*
                $this->mailer->sendMail(
                    '',
                    'Elasticsearch ligt eruit',
                    ''
                );*/
            endif;
        else :
            $this->clientEnabled = true;
        endif;
    }

    public function add(Item $item): void
    {
        $datagroup = Datagroup::findById($item->_('datagroup'));
        if ($datagroup) :
            $datafields = [];
            foreach ((array)$datagroup->_('datafields') as $field) :
                if (!empty($field['filterable'])) :
                    $datafield = Datafield::findById($field['id']);
                    /** @var Datafield $datafield */
                    if (is_object($datafield) && $datafield->_('published')) :
                        $datafields[] = $datafield;
                    endif;
                endif;
            endforeach;

            foreach (Language::findAll() as $language) :
                $fields = [];
                foreach ($datafields as $datafield) :
                    $elasticSearchField = $datafield->_('calling_name') . '_' . $language->_('short');
                    $fields[$elasticSearchField] = $datafield->getSearchValue(
                        $item,
                        $language->_('short')
                    );

                    if (
                        isset($fields[$datafield->_('calling_name')])
                        && is_string($fields[$datafield->_('calling_name')])
                    ) :
                        $fields[$elasticSearchField] = strtolower($fields[$datafield->_('calling_name')]);
                    endif;
                endforeach;

                $params = [
                    'index' => $this->index . '_' . $language->_('short'),
                    'type' => 'item',
                    'id' => (string)$item->getId(),
                    'routing' => $item->_('slug', $language->_('short')),
                    'body' => $fields
                ];
                $this->client->index($params);
            endforeach;
        endif;
    }

    public function deleteIndex(): void
    {
        foreach (Language::findAll() as $language) :
            $index = $this->index . '_' . $language->_('short');
            if ($this->indexExists($index)) :
                $params = ['index' => $index];
                $this->client->indices()->delete($params);
            endif;
        endforeach;
    }

    public function indexExists(string $index): bool
    {
        $params = ['index' => $index];
        return $this->client->indices()->exists($params);
    }

    public function delete(Item $item): void
    {
        foreach (Language::findAll() as $language) :
            $params = [
                'index' => $this->index . '_' . $language->_('short'),
                'type' => 'item',
                'id' => $item->getId(),
            ];

            $this->client->delete($params);
        endforeach;
    }

    public function search(): array
    {
        $params = [
            'index' => $this->index . '_' . $this->configuration->getLanguageShort(),
            'type' => $this->request->get('searchGroups'),
            'body' => $this->buildQueryFromFilter($this->request->get('filter', null, [])),
            'size' => 99
        ];

        if ($this->clientEnabled && !empty($params['body'])) :
            $searchResult = $this->client->search($params);
        else :
            $searchResult = ['hits' => ['total' => 0]];
        endif;

        return $searchResult;
    }

    /**
     * https://github.com/ongr-io/ElasticsearchDSL/blob/master/docs/index.md
     */
    protected function buildQueryFromFilter(array $filter): array
    {
        $searchTerm = strtolower(trim($this->request->get('search')));
        $search = new Search();
        $stringQueryFields = [];
        $langugaeShort = $this->configuration->getLanguageShort();

        foreach ($filter as $type => $filterItem) :
            switch ($type) :
                case 'textFields':
                    if ($this->request->get('search') && !empty($this->request->get('search'))) :
                        foreach ((array)$filterItem as $fieldName) :
                            $stringQueryFields[] = $fieldName . '_' . $langugaeShort;
                        endforeach;
                    endif;
                    break;
                case 'range' :
                    foreach ((array)$filterItem as $fieldName => $range) :
                        $range = explode(',', $range);
                        $search->addQuery(new RangeQuery(
                            $fieldName . '_' . $langugaeShort,
                            [
                                'gte' => $range[0],
                                'lte' => $range[1]
                            ]
                        ));
                    endforeach;
                    break;
                default:
                    if (is_array($filterItem)) :
                        $terms = [];
                        foreach ($filterItem as $term) :
                            if (is_array($term)) :
                                $term = implode(' OR ', $term);
                            endif;
                            $terms[] = $term;
                        endforeach;
                        $matchQuery = new MatchQuery(
                            $type . '_' . $langugaeShort,
                            implode(' OR ', $terms)
                        );
                        $search->addQuery($matchQuery);
                    else:
                        if (!empty($filterItem)) :
                            $queryStringQuery = new QueryStringQuery($filterItem);
                            $queryStringQuery->addParameter(
                                'fields',
                                [$type . '_' . $langugaeShort]
                            );
                            $search->addQuery($queryStringQuery);
                        endif;
                    endif;
                    break;
            endswitch;
        endforeach;

        if (count($stringQueryFields) > 0) :
            $queryStringQuery = new QueryStringQuery('*' . $searchTerm . '*');
            $queryStringQuery->addParameter('fields', $stringQueryFields);
            $search->addQuery($queryStringQuery);
        endif;

        return $search->toArray();
    }
}

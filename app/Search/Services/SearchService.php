<?php

namespace Search\Services;

use App\BudgetTracker\Enums\EntryType;
use DateTime;
use Exception;
use Illuminate\Support\Collection;
use Search\Entity\Search;
use Search\Repository\EntryRepository;

class SearchService
{
    const COLUMN = ['id', 'uuid', 'date_time', 'amount', 'note', 'type', 'waranty', 'confirmed', 'planned', 'category_id', 'account_id', 'currency_id', 'payee_id', 'transfer_id', 'payment_type', 'geolocation'];
    const FILTER = ['account_id', 'category_id', 'label'];

    /** @var DateTime */
    private $dateTime;

    public function __construct(string $month, string $year)
    {
        $this->dateTime = new DateTime("$year-$month-01 00:00:00");
    }

    /**
     * find all data
     * @param array $filter {"account":[31],"category":[16],"type":["incoming"],"tags":[201],"text":"test","planned":"0","year":2022,"month":4}
     * @param int $cursor
     * 
     * @return Search
     */
    public function find(array $filter): Search
    {
        $repository = new EntryRepository();

        if (!empty($filter['account'])) {
            $repository->account($filter['account']);
        }

        if (!empty($filter['category'])) {
            $repository->category($filter['category']);
        }

        if (!empty($filter['label'])) {
            $repository->label($filter['label']);
        }

        if (!empty($filter['text'])) {
            $repository->note($filter['text']);
        }

        if (!empty($filter['planned'])) {
            $repository->planned($filter['planned']);
        }

        if (!empty($filter['confirmed'])) {
            $repository->confirmed($filter['confirmed']);
        }

        if (!empty($filter['type'])) {
            foreach ($filter['type'] as $type) {
                $repository->$type();
            }
        }

        $repository->dateTime($this->dateTime, '<=');

        $result = $repository->get(self::COLUMN);

        return $this->makeObj($result);
    }

    /**
     * group by one column
     * @param array $obj
     * @param string $column
     * 
     * @return array
     * @throw Exception
     */
    public function groupBy(array $objs, string $filter): array
    {
        if (!in_array($filter, self::FILTER)) {
            throw new Exception('{"error":"no filter avaible for the selecion, change your filter"}');
        }

        foreach ($objs as $obj) {
            $id = $obj->$filter;
            $label = $this->getLabelGroup($filter,$id);

            if (!isset($group[$label])) {
                $group[$label] = [];
            }

            $group[$label][] = $obj;
        }

        return $group;
    }

    private function makeObj(Collection $collections): Search
    {
        $entity = new Search();
        foreach ($collections as $collection) {
                $entity->setEntry($collection);
        }

        return $entity;
    }

    private function getLabelGroup(string $filter, int $id): string
    {
        $label = '';

        switch ($filter) {
            case 'category_id':
                $label = EntryRepository::getCategoryName($id);
                break;
            case 'account_id':
                $label = EntryRepository::getAccountName($id);
                break;
            default:
                throw new Exception('{"error":"no filter setted"}');
                break;
        }

        $label = json_decode($label);
        return $label[0]->name;

    }

}

<?php

namespace MattSplat\TableQueries;

use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;


/**
 * Class TableQueryBuilder
 * @package App\Library\Query
 */
class TableQueryBuilder
{
    /**
     * @var array
     */
    protected $options;
    /**
     * @var array
     */
    protected $relations;
    /**
     * @var string
     */
    protected $modelTable;
    /**
     * @var Builder|\Illuminate\Database\Eloquent\Model
     */
    protected $model;
    /**
     * @var Builder
     */
    protected $tableQuery;
    /**
     * @var array
     */
    protected $fields;
    /**
     * @var array
     */
    protected $subQueries = [];

    /**
     * @var int
     */
    protected $count = 0;

    /**
     * TableQueryBuilder constructor.
     * @param Builder $query
     */
    public function __construct(Builder $query)
    {
        $this->tableQuery = $query;
        $this->model = $this->tableQuery->getModel();
        $this->modelTable = $this->model->getTable();
        $this->options = [];
    }

    /**
     * @param array $options
     * @return $this
     */
    public function setOptions(array $options)
    {
        $this->options = $options;
        return $this;
    }

    /**
     * @param $relational
     * @return $this
     * @throws Exception
     */
    public function setRelational($relational)
    {
        $this->extractRelational($relational);
        return $this;
    }


    /**
     * @param $fields
     * @return $this
     */
    public function setFields($fields)
    {
        $this->fields = $fields;
        return $this;
    }

    /**
     * @param callable $subQuery
     * @return $this
     */
    public function addSubQuery(callable $subQuery)
    {
        $this->subQueries [] = $subQuery;
        return $this;
    }

    /**
     * @return Builder
     */
    public function get()
    {
        $this->build();
        $this->search();

        //$this->paginate();
        $this->order();

        $results = $this->tableQuery;

        return $results;
    }

    /**
     *  Build query with only base model , relations and sub queries
     */
    protected function build()
    {
        if ($this->relations) {
            $this->addRelational();
        }
        $this->loadSubQueries();
    }

    /**
     * @param null $searchString
     * @param string $byColumn
     */
    protected function search($searchString = null, string $byColumn = '')
    {
        $byColumn = $byColumn ?? $this->options['byColumn'] ?? null;
        $search = $searchString ?? $this->options['query'] ?? null;

        if (!empty($search)) {
            !empty($byColumn) ? $this->filterByColumn($search, $byColumn) : $this->filterAllColumns($search);
        }
    }

    /**
     * @param $search
     * @param $column
     * @return Builder
     */
    protected function filterByColumn($search, $column)
    {
        return $this->tableQuery->where(function ($q) use ($search, $column) {
            if (is_string($search)) {
                if (in_array($column, $this->fields)) {
                    $q->orWhere($this->modelTable . '.' . $column, 'LIKE', "%{$search}%");
                } else if ($key = array_search($column, array_column($this->relations, $column))) {
                    $relation = $this->relations[$key];
                    $q->orWhere($relation['table'] . '.' . $relation['column'], 'LIKE', "%{$search}%");
                }

            } else {
                $start = Carbon::createFromFormat('Y-m-d', $search['start'])->startOfDay();
                $end = Carbon::createFromFormat('Y-m-d', $search['end'])->endOfDay();

                $q->whereBetween($column, [$start, $end]);
            }
        });
    }

    /**
     * @param $search
     */
    protected function filterAllColumns($search)
    {
        $this->tableQuery->where(function ($q) use ($search) {
            if (is_string($search)) {
                foreach ($this->fields as $index => $field) {
                    if (is_numeric($index)) {
                        $q->orWhere($this->modelTable . '.' . $field, 'LIKE', "%{$search}%");
                    }
                }
                foreach ($this->relations as $relation) {
                    $q->orWhere($relation['table'] . '.' . $relation['column'], 'LIKE', "%{$search}%");
                }
            }
        });
    }

    /**
     * Add BelongsTo Relations
     */
    protected function addRelational()
    {
        if (empty($this->relations)) return;

        $this->tableQuery->select($this->modelTable . '.*');

        foreach ($this->relations as $relation) {
            $this->tableQuery = $relation->addRelationalQuery($this->tableQuery);
        }
    }

    /**
     * @param $relationals
     * @throws Exception
     */
    public function extractRelational($relationals)
    {
        foreach ($relationals as $key => $relational) {

            if (!is_numeric($key)) {
                if (is_callable($relational)) {
                    $subQuery = $relational;
                }
                $relational = $key;
            }
            $this->relations [] = new TableRelation($relational, $this->model, $subQuery ?? null);
        }
    }

    /**
     * Run sub queries using callback
     */
    protected function loadSubQueries()
    {
        foreach ($this->subQueries as $subQuery) {
            $subQuery($this->tableQuery);
        }
    }

    /**
     * @param null $ascending
     * @param null $orderBy
     */
    public function order($ascending = null, $orderBy = null)
    {
        $ascending = $ascending ?? $this->options['ascending'] ?? null;

        $orderBy = $orderBy ?? $this->options['orderBy'] ?? $this->modelTable . '.created_at';
        if (in_array($orderBy, $this->fields)) {
            $orderBy = $this->modelTable . '.' . $orderBy;
        }

        $direction = ($ascending === 'true' || $ascending === true) ? 'ASC' : 'DESC';
        $this->tableQuery->orderBy($orderBy, $direction);
    }

    /**
     * @param null $page
     * @param null $limit
     */
    public function paginate($page = null, $limit = null)
    {
        $limit = $limit ?? $this->options['limit'] ?? null;
        $page = $page ?? $this->options['page'] ?? null;
        $this->count = 0;//$this->tableQuery->count();
        $limit = !empty($limit) ? $limit : 10;
        $this->tableQuery->limit($limit)
            ->skip($limit * (($page ?? 1) - 1));
    }

}

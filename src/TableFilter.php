<?php

namespace MattSplat\TableQueries;

class TableFilter
{
    public $operator;
    public $column;
    public $value;

    protected $allowedOperators = [
        'gt'  => '>',
        'gte' => '>=',
        'eq'  => '=',
        'lte' => '<=',
        'lt'  => '<',
        'ne'  => '!=',
        'like',
        'in',
        'nin' => 'not in',
        'between',
        'rlike',
    ];

    public function __construct(string $filter, $delimiter = ';')
    {
        $this->parseFilterString($filter, $delimiter);
    }

    protected function parseFilterString($filter, $delimiter)
    {
        $filter = strtolower($filter);

        $segments = explode($delimiter, $filter);
        if (count($segments) === 3) {
            [$this->column, $this->operator, $this->value] = $segments;
        } elseif (count($segments) === 2) {
            [$this->column, $this->value] = $segments;
            $this->operator = '=';
        } elseif (count($segments) === 4 && strpos($segments[1], 'between')) {
            $this->column = $segments[0];
            $this->operator = $segments[1];
            $this->value = ['start' => $segments[2], 'end' => $segments[3]];
        }
        if (!in_array($this->operator, $this->allowedOperators) &&
            (!in_array($this->operator, array_keys($this->allowedOperators)) || is_numeric($this->operator))) {
            throw new \Exception("Invalid Filter Format {$filter}");
        }

        if (in_array($this->operator, array_keys($this->allowedOperators), 1)) {
            $this->operator = $this->allowedOperators[$this->operator];
        }
    }

    public function toQuery($query)
    {
        if ($this->operator === 'between') {
            return $query->whereBetween($this->column, [$this->value['start'], $this->value['end']]);
        }

        return $query->where($this->column, $this->operator, $this->value);
    }
}

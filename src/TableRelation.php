<?php


namespace MattSplat\TableQueries;


use Illuminate\Support\Facades\DB;

class TableRelation
{
    protected $relational;
    public $parentModel;
    public $callback;
    public $relation;
    public $column;
    public $table;
    public $as;
    public $foreignKey;
    public $model;
    public $primaryKey;
    public $type;

    public function __construct($relational, $parentModel, $callback = null)
    {
        $this->relational = $relational;
        $this->parentModel = $parentModel;
        $this->callback = $callback;

        $this->extractRelational();
    }

    public function extractRelational()
    {

        /// separate string (related|column as alias)
        $formatted = str_ireplace('AS', 'as', $this->relational);

        $segments = explode(' as ', $formatted);
        $names = explode('|', $segments[0]);
        if (count($names) < 2 && empty($this->callback)) {
            throw new Exception('Incorrectly formatted relation ' . $this->relational);
        } else {
            $this->relation = $names[0];
            $this->column = $names[1] ?? $this->callback;
        }

        // get related model
        $this->model = $this->parentModel->{$this->relation}()->getRelated();

        // get table of related model
        $this->table = $this->model->getTable();

        /// get foreign key of relation
        $this->foreignKey = $this->parentModel->{$this->relation}()->getForeignKeyName();

        if (count($segments) < 2) {
            $this->as = $this->table . '_' . $this->column;
        } else {
            $this->as = $segments[1];
        }
        $this->primaryKey = $this->model->getKeyName();
        $this->type = $this->getRelationType($this->relation);

    }

    public function addRelationalQuery($tableQuery)
    {

        if (is_callable($this->column)) {
            $query = $this->model::query()->whereColumn(
                $this->model->qualifyColumn($this->primaryKey),
                $this->model->qualifyColumn($this->foreignKey)
            );

            $subQuery = ($this->column)($query);
            $tableQuery->selectSub($subQuery->limit(1), $this->as );
        } else {
            // add the related column to the select 'location|name as location',
            $select = strpos($this->column, ' ')? DB::raw($this->column . ' as ' . $this->as):
                $this->table . '.' . $this->column . ' as ' . $this->as;
            $table_alias =  $this->table.'_sub_'.$this->as;


            $tableQuery->addSelect($select);
            // if join is already made don't add duplicate
            if(!$tableQuery->getQuery()->joins || !collect($tableQuery->getQuery()->joins)->contains('table', $this->table)) {
                $tableQuery->leftJoin(
                    $this->table ,
                    $this->parentModel->qualifyColumn($this->foreignKey),
                    '=',
                    $this->model->qualifyColumn($this->primaryKey)
                );
            }

        }

        return $tableQuery;
    }


    protected function getRelationType($name)
    {
        return get_class($this->parentModel->{$name}());
    }
}

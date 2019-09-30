<?php


use App\Library\Query\TableQueryBuilder;
use App\Note;
use App\Worker;
use Carbon\Carbon;

class ExampleTableQuery
{
    protected $options;

    public function __construct($options = [])
    {
        $this->options = $options; /// Available options: String 'query', Number 'limit', Number 'page',
                                    ///  String 'orderBy', Boolean 'ascending', String 'byColumn'
    }

    public function get()
    {
        /// create a query of the model
        $query = Worker::query()
            /// with methods can be used on query
        ->withCount(['notes as MandyNotes' => function ($q) {
            $q->where('consumer_id', 11);
        }]);


        /// new instance of Table Query is created
        $results = (new TableQueryBuilder($query))
            // search sort options
            ->setOptions($this->options)
            // relations loaded can be used to filter search or order
            ->setRelational($this->relationalColumns())
            // load searchable fields on model
            ->setFields($this->getSearchableFields())
            ->get(); // instance of builder is returned



        $results = $results->paginate();
        return $results;
    }

    /// columns that will be added to the query and returned as if they are properties of the model
    public function relationalColumns()
    {
        return [
            /// relation name | column name as alias
            /// if alias is not supplied it will be relation_column
            'company|name as company',

            /// if name is multiple words it will be interpreted as raw sql
            /// in this case an alias is required
            'company|concat(users.name, "-", users.id) as company_concat',

            /// optionally a callback can be used to modify the query
            /// the column will not be needed here as a select can be added to the query
            'notes as notes_count' => function ($q) {
                return $q->selectRaw('count(*)');
            },
        ];
    }

    /// properties of the model that can be searched
    protected function getSearchableFields()
    {
        return [
            'id',
            'name',
            'email',
            'phone',
        ];
    }

}

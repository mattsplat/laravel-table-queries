# Laravel Table Queries


### Contents

### [What Is It?](#what)

### [Setup](#setup)

#### [Sorting](#sorting)

#### [Searching](#searching)

#### [Relationships](#relationships) 

#### [Filters](#filters)

## <a name="#what"></a>What Is It?
If you need to view your data in table or similar format that combines data from relational models and you need perforance that the client side can't provide. With a large dataset it can become too slow for the client to render so we can use server side rendering. If we rely on data that is from a relational table it can be tricky to filter, search or sort by this data.
This package is aimed at making this easier.

Say you have the following database structure
```
users-
    id
    name
    company_id
    
companies-
    id
    name

sales-
    id
    amount
    user_id
    date
```

You might need to display a table of users with a column for company name, total number of sales and total amount of sales in the last year.

You can define the company name as a simple belongs to relationships with `company|name as company_name'`. This is formatted `relation|column  as alias`. The relation must be defined on the model.
This will add company_name to the select statement allowing it to be filtered or sorted. 

For total sales we can use `sales|count(*) as sales_count`

In total sales in the last year we want to get the sum of all the sales by using
```
'sales as year_sales' => function ($q) {
    $q->selectRaw('sum(sales.amount)')->where('date', '>', today()->subYear())
}
```

This works similar to using 
```
User::with(['sales' => function ($q) { $q->where('date', '>', today()->subYear()) }])
``` 
but instead of adding the entire relation as nested object it adds a field that can be added to `orderBy` or `where` or accessed like a property of the model.


## <a name="#setup"></a>Setup
To install with composer run
`composer require mattsplat/table-queries`

For each query a [class](ExampleTableQuery.php) that implements TableQueryable is created.
This class would typically called from a controller.
```php
$options = $request->only(['query', 'limit', 'page', 'orderBy', 'ascending', 'filter']);

$results = (new NoteTableQuery($options))->get();

return $results;
```  

## <a name="#sorting"></a>Sorting
The TableQueryBuilder class accepts options `orderBy` and `ascending` which can be passed as part of the setOptions method.
```
$results = (new TableQueryBuilder($query))
                ->setOptions(['orderBy' => 'name', 'ascending' => true]);
```

or the order method can be called directly and passed a s arguments
```
$results = (new TableQueryBuilder($query))->order($orderBy, $ascending)
```

In either case if `ascending` is not provided then the default order will be `desc` 

## <a name="#searching"></a>Searching
You can search text across many fields even relations fields using the search option.
```
$results = (new TableQueryBuilder($query))
                ->setOptions(['search' => 'something']);
```

or can be called directly 

```
$results = (new TableQueryBuilder($query))->search($searchText)
```

## <a name="#relationships"></a>Relationships

Relationships can be loaded using the normal with method but this won't work if you need to sort or filter by this data. 
With an easy to read syntax you can add basic relational data to the query itself.
```$xslt
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
```

## <a name="#filters"></a>Filters

A filter can be called for an specific column using sql like syntax. 
Filter array can be passed in the options array  in the format `filter=column:operator:value` 
```
$filters = [
            'age:>:21',
            'start_date:gte:11/21/2009',
            'id:in:33,44,200'                                                                
           ]
$results = (new TableQueryBuilder($query))
                ->setOptions(['filters' => $filters]);
```
Available Operators Include
```
'gt' , '>', 'gte' , '>=', 'eq' , '=', 'lte' , '<=', 'lt' , '<',
'ne' , '!=', 'like', 'in', 'nin' , 'not in', 'between', 'rlike'
```

Since this doesn't read directly from the request if your request format differs it can modified before sending to the class.

Example get request `/users?filter[id]=nin:9&filter[name]=like:S%`
```
$filters = collect($request->filter)->map(function ($name, $f) {
    return $name.$f;
})
```

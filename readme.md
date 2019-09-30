# Laravel Table Queries


### Contents

### [Setup](#setup)

#### [Sorting](#sorting)

#### [Searching](#searching)

#### [Relationships](#relationships) 

## <a name="#setup"></a>Setup
To install with composer run
`composer require mattsplat/table-queries`

For each query a [class](ExampleTableQuery.php) that implements TableQueryable is created. 

## <a name="#sorting"></a>Sorting

## <a name="#searching"></a>Searching

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

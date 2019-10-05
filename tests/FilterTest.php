<?php


namespace MattSplat\TableQueries\Tests;



use MattSplat\TableQueries\TableFilter;
use MattSplat\TableQueries\Tests\TestClasses\Models\TestModel;
use PHPUnit\Framework\TestCase;

class FilterTest extends TestCase
{
    /** @test */
    public function it_parses_operator()
    {
        $filter = new TableFilter('age;>;21');

        return $this->assertEquals('>', $filter->operator);
    }

    /** @test */
    public function it_parses_operator_alias()
    {
        $filter = new TableFilter('age;gte;21');

        return $this->assertSame('>=', $filter->operator);
    }

    /** @test */
    public function it_parses_column()
    {
        $filter = new TableFilter('really_bad_name;>;21');

        return $this->assertEquals('really_bad_name', $filter->column);
    }

    /** @test */
    public function it_parses_int_value()
    {
        $filter = new TableFilter('really_bad_name;>;21');

        return $this->assertEquals(21, $filter->value);
    }

    /** @test */
    public function it_parses_string_value()
    {
        $filter = new TableFilter('really_bad_name;>;twenty one');

        return $this->assertEquals('twenty one', $filter->value);
    }

    /** @test */
    public function it_parses_int_value_with_custom_delimiter()
    {
        $filter = new TableFilter('really_bad_name^>^21','^');

        return $this->assertEquals(21, $filter->value);
    }

//    /** @test */
//    public function it_builds_query()
//    {
//        $filter = new TableFilter('really_bad_name:>:21',':');
//
//        return $this->assertEquals(null, $filter->toQuery(TestModel::query()));
//    }
}

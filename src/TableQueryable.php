<?php

namespace MattSplat\TableQueries;

interface TableQueryable
{
    public function get();

    public function relationalColumns();
}

<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\Event;

use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\SearchBundle\Event\SearchResultBefore;
use Oro\Bundle\SearchBundle\Query\SearchQueryInterface;

class SearchResultBeforeTest extends \PHPUnit_Framework_TestCase
{
    public function testEvent()
    {
        $datagrid = $this->getMock(DatagridInterface::class);
        $query    = $this->getMock(SearchQueryInterface::class);

        $event = new SearchResultBefore(
            $datagrid,
            $query
        );
        $this->assertEquals($query, $event->getQuery());
    }
}

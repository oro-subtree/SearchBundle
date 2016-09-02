<?php

namespace Oro\Bundle\SearchBundle\Datasource;

use Oro\Bundle\SearchBundle\Query\Result\Item;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datasource\DatasourceInterface;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface;
use Oro\Bundle\SearchBundle\Event\SearchResultAfter;
use Oro\Bundle\SearchBundle\Event\SearchResultBefore;
use Oro\Bundle\SearchBundle\Extension\SearchQueryInterface;
use Oro\Bundle\SearchBundle\Query\Factory\QueryFactoryInterface;

class SearchDatasource implements DatasourceInterface
{
    const TYPE = 'search';

    /** @var QueryFactoryInterface */
    protected $queryFactory;

    /** @var SearchQueryInterface */
    protected $query;

    /** @var DatagridInterface */
    protected $datagrid;

    /**
     * @param QueryFactoryInterface    $factory
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(
        QueryFactoryInterface $factory,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->queryFactory = $factory;
        $this->dispatcher   = $eventDispatcher;
    }

    /**
     * {@inheritDoc}
     */
    public function process(DatagridInterface $grid, array $config)
    {
        $this->datagrid = $grid;

        $this->query = $this->queryFactory->create($grid, $config);

        $grid->setDatasource(clone $this);
    }

    /**
     * @return ResultRecordInterface[]
     */
    public function getResults()
    {
        $results = $this->query->execute();
        /** @var Item[] $results */

        $event = new SearchResultBefore($this->datagrid, $this->query);
        $this->dispatcher->dispatch(SearchResultBefore::NAME, $event);

        $rows = [];
        foreach ($results as $result) {
            $resultRecord = new ResultRecord($result);
            $resultRecord->addData($result->getSelectedData());
            $rows[] = $resultRecord;
        }

        $event = new SearchResultAfter($this->datagrid, $rows, $this->query);
        $this->dispatcher->dispatch(SearchResultAfter::NAME, $event);

        return $rows;
    }

    /**
     * @return SearchQueryInterface
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * The SearchQuery is a builder itself.
     *
     * @return SearchQueryInterface
     */
    public function getQueryBuilder()
    {
        return $this->getQuery();
    }
}

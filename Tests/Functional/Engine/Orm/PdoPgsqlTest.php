<?php

namespace Oro\Bundle\SearchBundle\Tests\Functional\Engine\Orm;

use Oro\Bundle\EntityBundle\ORM\DatabaseDriverInterface;
use Oro\Bundle\SearchBundle\Engine\Orm\PdoPgsql;

use Doctrine\ORM\Configuration;

/**
 * @dbIsolation
 * @dbReindex
 */
class PdoPgsqlTest extends AbstractDriverTest
{
    const ENTITY_TITLE = 'test-entity-title';
    const DRIVER = DatabaseDriverInterface::DRIVER_POSTGRESQL;
    const ENVIRONMENT_NAME = 'PostgreSQL';

    public function testGetPlainSql()
    {
        $recordString = PdoPgsql::getPlainSql();
        $this->assertTrue(strpos($recordString, 'to_tsvector') > 0);
    }

    /**
     * {@inheritdoc}
     */
    protected function getDriver()
    {
        return new PdoPgsql();
    }

    /**
     * {@inheritdoc}
     */
    protected function assertInitConfiguration(Configuration $configuration)
    {
        $this->assertEquals(
            'Oro\Bundle\SearchBundle\Engine\Orm\PdoMysql\MatchAgainst',
            $configuration->getCustomStringFunction('MATCH_AGAINST')
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function assertTruncateQueries(array $queries)
    {
        $this->assertCount(7, $queries);

        $expectedQueries = [
            'SET FOREIGN_KEY_CHECKS=0',
            'TRUNCATE oro_search_item CASCADE',
            'TRUNCATE oro_search_index_text CASCADE',
            'TRUNCATE oro_search_index_integer CASCADE',
            'TRUNCATE oro_search_index_decimal CASCADE',
            'TRUNCATE oro_search_index_datetime CASCADE',
            'SET FOREIGN_KEY_CHECKS=1'
        ];

        $this->assertEquals($expectedQueries, $queries);
    }
}


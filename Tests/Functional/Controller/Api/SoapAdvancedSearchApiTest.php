<?php

namespace Oro\Bundle\SearchBundle\Tests\Functional\Controller\Api;

use Oro\Bundle\SearchBundle\Tests\Functional\Controller\DataFixtures\LoadSearchItemData;
use Oro\Bundle\SearchBundle\Tests\Functional\SearchExtensionTrait;
use Oro\Bundle\TestFrameworkBundle\Entity\Item;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @outputBuffering enabled
 * @group soap
 * @group search
 */
class SoapAdvancedSearchApiTest extends WebTestCase
{
    use SearchExtensionTrait;

    /** Default value for offset and max_records */
    const DEFAULT_VALUE = 0;

    protected static $hasLoaded = false;

    protected function setUp()
    {
        parent::setUp();

        $this->initClient([], $this->generateWsseAuthHeader(), true);
        $this->initSoapClient();
        $this->startTransaction();

        $alias = $this->getSearchObjectMapper()->getEntityAlias(Item::class);
        $this->getSearchIndexer()->resetIndex(Item::class);
        $this->ensureItemsLoaded($alias, 0);

        $this->loadFixtures([LoadSearchItemData::class], true);
        $this->getSearchIndexer()->reindex(Item::class);
        $this->ensureItemsLoaded($alias, LoadSearchItemData::COUNT);
    }

    protected function tearDown()
    {
        parent::tearDown();

        $this->rollbackTransaction();
    }

    /**
     * @param array $request
     * @param array $response
     *
     * @dataProvider advancedSearchDataProvider
     */
    public function testAdvancedSearch(array $request, array $response)
    {
        $result = $this->soapClient->advancedSearch($request['query']);
        $result = $this->valueToArray($result);
        $this->assertEquals($response['records_count'], $result['recordsCount']);
        $this->assertEquals($response['count'], $result['count']);

        // if only one element
        if (empty($result['elements']['item'][0])) {
            $result['elements']['item'] = [$result['elements']['item']];
        }

        // remove ID references
        foreach (array_keys($result['elements']['item']) as $key) {
            unset($result['elements']['item'][$key]['recordId']);
        }

        $this->addOroDefaultPrefixToUrlInParameterArray($response['soap']['data'], 'recordUrl');
        $this->assertSame($response['soap']['data'], $result['elements']['item']);
    }

    public function testAdvancedSearchNoResults()
    {
        $queryString = 'from oro_test_item where stringValue = item5';

        $result = $this->soapClient->advancedSearch($queryString);
        $result = $this->valueToArray($result);

        $this->assertEquals(0, $result['recordsCount']);
        $this->assertEquals(0, $result['count']);
        $this->assertEmpty($result['elements']);
    }

    /**
     * Data provider for SOAP API tests
     *
     * @return array
     */
    public function advancedSearchDataProvider()
    {
        return $this->getApiRequestsData(
            __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'advanced_requests'
        );
    }
}

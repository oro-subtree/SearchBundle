<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\EventListener;

use Doctrine\ORM\Event\OnClearEventArgs;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Oro\Bundle\SearchBundle\Engine\IndexerInterface;
use Oro\Bundle\SearchBundle\EventListener\IndexListener;
use Oro\Bundle\SearchBundle\Provider\SearchMappingProvider;
use Oro\Bundle\SearchBundle\Tests\Unit\Fixture\Entity\Product;

class IndexListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $doctrineHelper;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $searchIndexer;

    /**
     * @var array
     */
    protected $entitiesMapping = [
        'Oro\Bundle\SearchBundle\Tests\Unit\Fixture\Entity\Product' => [
            'fields' => [
                [
                    'name' => 'field',
                ],
            ],
        ],
    ];

    protected function setUp()
    {
        $this->doctrineHelper = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->searchIndexer = $this->getMock(IndexerInterface::class);
    }

    public function testOnFlush()
    {
        $insertedEntity = $this->createTestEntity('inserted');
        $updatedEntity = $this->createTestEntity('updated');
        $deletedEntity = $this->createTestEntity('deleted');
        $notSupportedEntity = new \stdClass();

        $entityClass = 'Product';
        $entityId = 1;
        $deletedEntityReference = new \stdClass();
        $deletedEntityReference->class = $entityClass;
        $deletedEntityReference->id = $entityId;

        $unitOfWork = $this->getMockBuilder('Doctrine\ORM\UnitOfWork')
            ->disableOriginalConstructor()
            ->getMock();
        $unitOfWork->expects($this->once())->method('getScheduledEntityInsertions')
            ->will($this->returnValue([
                'inserted' => $insertedEntity,
                'not_supported' => $notSupportedEntity,
            ]));
        $unitOfWork->expects($this->once())->method('getScheduledEntityUpdates')
            ->will($this->returnValue([
                'updated' => $updatedEntity,
                'not_supported' => $notSupportedEntity,
            ]));
        $unitOfWork->expects($this->once())->method('getScheduledEntityDeletions')
            ->will($this->returnValue([
                'deleted' => $deletedEntity,
                'not_supported' => $notSupportedEntity,
            ]));
        $unitOfWork->expects($this->once())
            ->method('getEntityChangeSet')
            ->with($updatedEntity)
            ->will($this->returnValue([
                'field' => ['val1', 'val2'],
            ]));

        $this->doctrineHelper->expects($this->once())->method('getEntityClass')->with($deletedEntity)
            ->will($this->returnValue($entityClass));
        $this->doctrineHelper->expects($this->once())->method('getSingleEntityIdentifier')->with($deletedEntity)
            ->will($this->returnValue($entityId));

        $entityManager = $this->createEntityManager();
        $entityManager->expects($this->any())->method('getUnitOfWork')
            ->will($this->returnValue($unitOfWork));
        $entityManager->expects($this->once())->method('getReference')->with($entityClass, $entityId)
            ->will($this->returnValue($deletedEntityReference));

        $listener = $this->createListener();
        $listener->onFlush(new OnFlushEventArgs($entityManager));

        $this->assertAttributeEquals(
            ['inserted' => $insertedEntity, 'updated' => $updatedEntity],
            'savedEntities',
            $listener
        );
        $this->assertAttributeEquals(
            ['deleted' => $deletedEntityReference],
            'deletedEntities',
            $listener
        );
    }

    public function testPostFlushNoEntities()
    {
        $this->searchIndexer->expects($this->never())->method('save');
        $this->searchIndexer->expects($this->never())->method('delete');

        $listener = $this->createListener();
        $listener->postFlush(new PostFlushEventArgs($this->createEntityManager()));
    }

    public function testPostFlush()
    {
        $insertedEntity = $this->createTestEntity('inserted');
        $insertedEntities = ['inserted' => $insertedEntity];
        $deletedEntity = $this->createTestEntity('deleted');
        $deletedEntities = ['deleted' => $deletedEntity];

        $unitOfWork = $this->getMockBuilder('Doctrine\ORM\UnitOfWork')
            ->disableOriginalConstructor()
            ->getMock();
        $unitOfWork->expects($this->once())->method('getScheduledEntityInsertions')
            ->will($this->returnValue($insertedEntities));
        $unitOfWork->expects($this->once())->method('getScheduledEntityUpdates')
            ->will($this->returnValue([]));
        $unitOfWork->expects($this->once())->method('getScheduledEntityDeletions')
            ->will($this->returnValue($deletedEntities));

        $entityManager = $this->createEntityManager();
        $entityManager->expects($this->any())->method('getUnitOfWork')
            ->will($this->returnValue($unitOfWork));
        $entityManager->expects($this->once())->method('getReference')
            ->will($this->returnValue($deletedEntity));

        $this->searchIndexer
            ->expects($this->once())
            ->method('save')
            ->with($insertedEntities)
        ;

        $this->searchIndexer
            ->expects($this->once())
            ->method('delete')
            ->with($deletedEntities)
        ;

        $listener = $this->createListener();
        $listener->onFlush(new OnFlushEventArgs($entityManager));
        $listener->postFlush(new PostFlushEventArgs($entityManager));

        $this->assertAttributeEmpty('savedEntities', $listener);
        $this->assertAttributeEmpty('deletedEntities', $listener);
    }

    public function testOnClear()
    {
        $insertedEntity = $this->createTestEntity('inserted');
        $insertedEntities = ['inserted' => $insertedEntity];
        $deletedEntity = $this->createTestEntity('deleted');
        $deletedEntities = ['deleted' => $deletedEntity];

        $unitOfWork = $this->getMockBuilder('Doctrine\ORM\UnitOfWork')
            ->disableOriginalConstructor()->getMock();
        $unitOfWork->expects($this->once())->method('getScheduledEntityInsertions')
            ->will($this->returnValue($insertedEntities));
        $unitOfWork->expects($this->once())->method('getScheduledEntityUpdates')
            ->will($this->returnValue([]));
        $unitOfWork->expects($this->once())->method('getScheduledEntityDeletions')
            ->will($this->returnValue($deletedEntities));

        $entityManager = $this->createEntityManager();
        $entityManager->expects($this->any())->method('getUnitOfWork')
            ->will($this->returnValue($unitOfWork));
        $entityManager->expects($this->once())->method('getReference')
            ->will($this->returnValue($deletedEntity));

        $listener = $this->createListener();
        $listener->onFlush(new OnFlushEventArgs($entityManager));
        $listener->onClear(new OnClearEventArgs($entityManager));

        $this->assertAttributeEmpty('savedEntities', $listener);
        $this->assertAttributeEmpty('deletedEntities', $listener);
    }

    public function testSetEntitiesConfig()
    {
        $listener = $this->createListener();
        $config = ['key' => 'value'];

        $this->assertAttributeEquals($this->entitiesMapping, 'entitiesConfig', $listener);
        $listener->setEntitiesConfig($config);
        $this->assertAttributeEquals($config, 'entitiesConfig', $listener);
    }

    /**
     * @return IndexListener
     */
    protected function createListener()
    {
        $listener = new IndexListener($this->doctrineHelper, $this->searchIndexer);
        $listener->setEntitiesConfig($this->entitiesMapping);

        $eventDispatcher = $this->getMockBuilder('Symfony\Component\EventDispatcher\EventDispatcher')
            ->disableOriginalConstructor()->getMock();
        $mapperProvider = new SearchMappingProvider($eventDispatcher);
        $mapperProvider->setMappingConfig($this->entitiesMapping);
        $listener->setMappingProvider($mapperProvider);

        return $listener;
    }

    /**
     * @param  string  $name
     * @return Product
     */
    protected function createTestEntity($name)
    {
        $result = new Product();
        $result->setName($name);

        return $result;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function createEntityManager()
    {
        return $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
    }
}

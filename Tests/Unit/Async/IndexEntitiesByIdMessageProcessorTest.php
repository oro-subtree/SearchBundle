<?php
namespace Oro\Bundle\SearchBundle\Tests\Unit\Async;

use Oro\Bundle\SearchBundle\Async\IndexEntitiesByIdMessageProcessor;
use Oro\Bundle\SearchBundle\Async\Topics;
use Oro\Component\MessageQueue\Client\MessageProducer;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\Null\NullMessage;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Psr\Log\LoggerInterface;

class IndexEntitiesByIdMessageProcessorTest extends \PHPUnit_Framework_TestCase
{
    public function testCouldBeConstructedByRequiredArguments()
    {
        new IndexEntitiesByIdMessageProcessor(
            $this->createMessageProducerMock(),
            $this->createLoggerMock()
        );
    }

    public function testShouldReturnSubscribedTopics()
    {
        $expectedSubscribedTopics = [Topics::INDEX_ENTITIES_BY_ID];

        $this->assertEquals($expectedSubscribedTopics, IndexEntitiesByIdMessageProcessor::getSubscribedTopics());
    }

    public function testShouldRejectMessageIfIsNotArray()
    {
        $producer = $this->createMessageProducerMock();
        $producer
            ->expects($this->never())
            ->method('sendTo')
        ;

        $logger = $this->createLoggerMock();
        $logger
            ->expects($this->once())
            ->method('error')
            ->with('Expected array but got: "NULL"')
        ;

        $processor = new IndexEntitiesByIdMessageProcessor($producer, $logger);

        $message = new NullMessage();

        $result = $processor->process($message, $this->getMock(SessionInterface::class));

        $this->assertEquals(MessageProcessorInterface::REJECT, $result);
    }

    public function testShouldLogErrorIfClassWasNotFound()
    {
        $producer = $this->createMessageProducerMock();
        $producer
            ->expects($this->never())
            ->method('sendTo')
        ;

        $logger = $this->createLoggerMock();
        $logger
            ->expects($this->once())
            ->method('error')
            ->with('Message is invalid. Class was not found. message: "[[]]"')
        ;

        $processor = new IndexEntitiesByIdMessageProcessor($producer, $logger);

        $message = new NullMessage();
        $message->setProperties([
            'json_body' => [
                [],
            ],
        ]);

        $result = $processor->process($message, $this->getMock(SessionInterface::class));

        $this->assertEquals(MessageProcessorInterface::ACK, $result);
    }

    public function testShouldLogErrorIfIdWasNotFound()
    {
        $producer = $this->createMessageProducerMock();
        $producer
            ->expects($this->never())
            ->method('sendTo')
        ;

        $logger = $this->createLoggerMock();
        $logger
            ->expects($this->once())
            ->method('error')
            ->with('Message is invalid. Id was not found. message: "[{"class":"class-name"}]"')
        ;

        $processor = new IndexEntitiesByIdMessageProcessor($producer, $logger);

        $message = new NullMessage();
        $message->setProperties([
            'json_body' => [
                [
                    'class' => 'class-name',
                ],
            ],
        ]);

        $result = $processor->process($message, $this->getMock(SessionInterface::class));

        $this->assertEquals(MessageProcessorInterface::ACK, $result);
    }

    public function testShouldPublishMessageToProducer()
    {
        $producer = $this->createMessageProducerMock();
        $producer
            ->expects($this->once())
            ->method('sendTo')
            ->with(Topics::INDEX_ENTITY, ['class' => 'class-name', 'id' => 'id'])
        ;

        $logger = $this->createLoggerMock();
        $logger
            ->expects($this->never())
            ->method('error')
        ;

        $processor = new IndexEntitiesByIdMessageProcessor($producer, $logger);

        $message = new NullMessage();
        $message->setProperties([
            'json_body' => [
                [
                    'class' => 'class-name',
                    'id' => 'id',
                ],
            ],
        ]);

        $result = $processor->process($message, $this->getMock(SessionInterface::class));

        $this->assertEquals(MessageProcessorInterface::ACK, $result);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|LoggerInterface
     */
    protected function createLoggerMock()
    {
        return $this->getMock(LoggerInterface::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|MessageProducer
     */
    protected function createMessageProducerMock()
    {
        return $this->getMock(MessageProducer::class, [], [], '', false);
    }
}

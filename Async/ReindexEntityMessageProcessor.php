<?php
namespace Oro\Bundle\SearchBundle\Async;

use Oro\Bundle\SearchBundle\Engine\IndexerInterface;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;

class ReindexEntityMessageProcessor implements MessageProcessorInterface, TopicSubscriberInterface
{
    /**
     * @var IndexerInterface
     */
    protected $indexer;

    /**
     * @var MessageProducerInterface
     */
    protected $producer;

    /**
     * @param IndexerInterface         $indexer
     * @param MessageProducerInterface $producer
     */
    public function __construct(IndexerInterface $indexer, MessageProducerInterface $producer)
    {
        $this->indexer = $indexer;
        $this->producer = $producer;
    }

    /**
     * {@inheritdoc}
     */
    public function process(MessageInterface $message, SessionInterface $session)
    {
        var_dump($message->getBody());

        $classes = JSON::decode($message->getBody());

        if (false == $classes) {
            $this->indexer->resetIndex();
            $entityNames = $this->indexer->getClassesForReindex();
        } else {
            $classes = is_array($classes) ? $classes : [$classes];

            $entityNames = [];
            foreach ($classes as $class) {
                $entityNames = array_merge($entityNames, $this->indexer->getClassesForReindex($class));
            }

            $entityNames = array_unique($entityNames);

            foreach ($entityNames as $entityName) {
                $this->indexer->resetIndex($entityName);
            }
        }

        foreach ($entityNames as $entityName) {
            $this->producer->send(Topics::INDEX_ENTITY_TYPE, $entityName);
        }

        return self::ACK;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedTopics()
    {
        return [Topics::REINDEX];
    }
}

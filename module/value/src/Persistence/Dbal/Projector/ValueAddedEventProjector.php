<?php

/**
 * Copyright © Bold Brand Commerce Sp. z o.o. All rights reserved.
 * See LICENSE.txt for license details.
 */

declare(strict_types = 1);

namespace Ergonode\Value\Persistence\Dbal\Projector;

use Doctrine\DBAL\Connection;
use Ergonode\SharedKernel\Domain\Aggregate\AttributeId;
use Ergonode\Value\Domain\Event\ValueAddedEvent;
use JMS\Serializer\SerializerInterface;
use Ramsey\Uuid\Uuid;

/**
 */
class ValueAddedEventProjector
{
    private const NAMESPACE = '0cc20207-d1b7-460b-8ef6-6898d00de4c0';
    private const VALUE_TABLE = 'attribute_value';
    private const RELATION_TABLE = 'entity_attribute_value';

    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @param Connection          $connection
     * @param SerializerInterface $serializer
     */
    public function __construct(Connection $connection, SerializerInterface $serializer)
    {
        $this->connection = $connection;
        $this->serializer = $serializer;
    }

    /**
     * @param ValueAddedEvent $event
     *
     * @throws \Throwable
     */
    public function __invoke(ValueAddedEvent $event): void
    {
        $this->connection->transactional(function () use ($event) {
            $attributeId = AttributeId::fromKey($event->getAttributeCode()->getValue());
            $type = get_class($event->getValue());
            $value = $this->serializer->serialize($event->getValue(), 'json');

            $valueId = Uuid::uuid5(self::NAMESPACE, $value);

            $qb = $this->connection->createQueryBuilder();
            $result = $qb->select('*')
                ->from(self::VALUE_TABLE)
                ->where($qb->expr()->eq('id', ':id'))
                ->setParameter(':id', $valueId->toString())
                ->execute()
                ->fetch();

            if (false === $result) {
                $this->connection->executeQuery(
                    sprintf(
                        'INSERT INTO %s (id, type, value) VALUES (?, ?, ?) ON CONFLICT DO NOTHING',
                        self::VALUE_TABLE
                    ),
                    [$valueId->toString(), $type, $value]
                );
            }

            $this->connection->insert(
                self::RELATION_TABLE,
                [
                    'entity_id' => $event->getAggregateId()->getValue(),
                    'attribute_id' => $attributeId->getValue(),
                    'value_id' => $valueId->toString(),
                ]
            );
        });
    }
}

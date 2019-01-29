<?php

namespace TalisOrm\Schema;

use Doctrine\DBAL\Schema\Schema;
use InvalidArgumentException;

final class AggregateSchemaProvider
{
    /**
     * @var string[]
     */
    private $aggregateClasses;

    public function __construct(array $aggregateClasses)
    {
        $this->aggregateClasses = $aggregateClasses;
    }

    /**
     * @return Schema
     */
    public function createSchema()
    {
        $schema = new Schema();

        foreach ($this->aggregateClasses as $aggregateClass) {
            if (!is_a($aggregateClass, SpecifiesSchema::class, true)) {
                throw new InvalidArgumentException(sprintf(
                    'Class "%s" was expected to implement "%s"',
                    $aggregateClass,
                    SpecifiesSchema::class
                ));
            }

            $aggregateClass::specifySchema($schema);
        }

        return $schema;
    }
}

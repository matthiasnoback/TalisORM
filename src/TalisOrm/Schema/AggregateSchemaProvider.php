<?php

namespace TalisOrm\Schema;

use Doctrine\DBAL\Schema\Schema;
use InvalidArgumentException;
use Webmozart\Assert\Assert;

final class AggregateSchemaProvider
{
    /**
     * @var string[]
     *
     * @phpstan-var array<class-string<SpecifiesSchema>>
     */
    private $aggregateClasses;

    /**
     * @param string[] $aggregateClasses
     *
     * @phpstan-param array<class-string<SpecifiesSchema>> $aggregateClasses
     */
    public function __construct(array $aggregateClasses)
    {
        Assert::allString($aggregateClasses);
        $this->aggregateClasses = $aggregateClasses;
    }

    public function createSchema(): Schema
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

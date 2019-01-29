<?php

namespace TalisOrm;

use RuntimeException;

final class ConcurrentUpdateOccurred extends RuntimeException
{
    public static function ofEntity(Entity $entity): ConcurrentUpdateOccurred
    {
        return new self(sprintf(
            'A concurrent update occurred of an entity of type "%s" with identifier: %s',
            get_class($entity),
            self::renderIdentifier($entity->identifier())
        ));
    }

    private static function renderIdentifier(array $identifier): string
    {
        $parts = [];

        foreach ($identifier as $columnName => $value) {
            $parts[] = $columnName . ' = ' . var_export($value, true);
        }

        return implode(', ', $parts);
    }
}

<?php

namespace TalisOrm;

use RuntimeException;

final class ConcurrentUpdateOccurred extends RuntimeException
{
    /**
     * @param Entity $entity
     * @return ConcurrentUpdateOccurred
     */
    public static function ofEntity(Entity $entity)
    {
        return new self(sprintf(
            'A concurrent update occurred of an entity of type "%s" with identifier: %s',
            get_class($entity),
            self::renderIdentifier($entity->identifier())
        ));
    }

    /**
     * @param array $identifier
     * @return string
     */
    private static function renderIdentifier(array $identifier)
    {
        $parts = [];

        foreach ($identifier as $columnName => $value) {
            $parts[] = $columnName . ' = ' . var_export($value, true);
        }

        return implode(', ', $parts);
    }
}

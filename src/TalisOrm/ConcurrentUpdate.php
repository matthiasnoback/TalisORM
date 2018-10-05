<?php

namespace TalisOrm;

use RuntimeException;

final class ConcurrentUpdate extends RuntimeException
{
    /**
     * @param Entity $entity
     * @return ConcurrentUpdate
     */
    public static function ofEntity(Entity $entity)
    {
        return new self(sprintf(
            'A concurrent update occurred of an entity of type "%s"',
            get_class($entity)
        ));
    }
}

<?php

namespace TalisOrm;

use DateTimeImmutable;
use Webmozart\Assert\Assert;

final class DateTimeUtil
{
    /**
     * @param string $date
     * @return DateTimeImmutable
     */
    public static function createDateTimeImmutable($date)
    {
        Assert::string($date);

        $dateTimeImmutable = DateTimeImmutable::createFromFormat('!Y-m-d', $date);
        Assert::isInstanceOf($dateTimeImmutable, DateTimeImmutable::class);

        return $dateTimeImmutable;
    }
}

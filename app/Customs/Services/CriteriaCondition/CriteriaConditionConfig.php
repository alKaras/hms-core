<?php

namespace App\Customs\Services\CriteriaCondition;

class CriteriaConditionConfig
{
    public const ALLOWED_FEED_COLUMNS = [
        'created_at' => 'o.created_at',
        'confirmed_at' => 'o.confirmed_at',
        'status' => 'o.status',
        'hospital_title' => 'hc.title'
        //@TODO Add another columns if needed
    ];

    public const ALLOWED_OPERATORS = [
        '=',
        '!=',
        '<>',
        '>',
        '<',
        '>=',
        '<=',
        'LIKE',
        'IN',
        'NOT IN',
    ];

    /**
     * Get allowed feed columns for filter
     * @return array
     */
    public static function getAllowedFeedColumns(): array
    {
        return self::ALLOWED_FEED_COLUMNS;
    }

    /**
     * Get allowed feed operators for filters
     * @return array
     */
    public static function getAllowedOperators(): array
    {
        return self::ALLOWED_OPERATORS;
    }
}
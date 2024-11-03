<?php

namespace App\Customs\Services\CriteriaCondition;

use Illuminate\Database\Query\Builder;

class CriteriaConditionService
{
    /**
     * Apply conditions to feed
     * @param Builder $query
     * @param array $criteriaConditions
     * @return mixed
     */
    public function applyConditions(Builder $query, array $criteriaConditions)
    {
        foreach ($criteriaConditions as $criteria) {
            if (count($criteria) === 3) {
                [$column, $operator, $value] = $criteria;

                if (array_key_exists($column, CriteriaConditionConfig::ALLOWED_FEED_COLUMNS)) {
                    $actualColumn = CriteriaConditionConfig::ALLOWED_FEED_COLUMNS[$column];

                    $query->whereRaw("$actualColumn $operator ?", [$value]);
                } else {
                    throw new \InvalidArgumentException("Invalid column: $column");
                }
            }
        }
        return $query;
    }

}
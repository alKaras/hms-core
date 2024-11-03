<?php

namespace App\Customs\Services\CriteriaCondition;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\Validator;
use App\Customs\Services\CriteriaCondition\CriteriaConditionConfig;

class CriteriaConditionService
{

    /**
     * Validator for criteria conditions
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Validation\Validator
     */
    public function validateConditionRequest(Request $request)
    {
        $criteriaValidator = Validator::make($request->all(), [
            //Criteria Condition
            'criteriaCondition' => ['array', 'sometimes'],
            'criteriaCondition.*' => ['array', 'size:3'],
            'criteriaCondition.*.0' => [
                'string',
                Rule::in(array_keys(CriteriaConditionConfig::ALLOWED_FEED_COLUMNS)),
            ],
            'criteriaCondition.*.1' => [
                'string',
                Rule::in(CriteriaConditionConfig::ALLOWED_OPERATORS),
            ],
            'criteriaCondition.*.2' => 'required',
        ]);

        return $criteriaValidator;

    }

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
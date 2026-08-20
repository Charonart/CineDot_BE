<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

trait FilterableAndSortable
{
    /**
     * Scope a query to apply dynamic filters, search, and sorting from Request.
     *
     * @param Builder $query
     * @param Request|array $request
     * @param array $allowedFilters Whitelist of filterable columns
     * @param array $allowedSorts Whitelist of sortable columns
     * @param array $searchableFields Whitelist of fields for global full-text search
     * @param array $columnAliases Map frontend column keys to DB column names (e.g. ['id' => 'user_id'])
     * @return Builder
     */
    public function scopeApplyDataTableQuery(
        Builder $query,
        $request,
        array $allowedFilters = [],
        array $allowedSorts = [],
        array $searchableFields = [],
        array $columnAliases = []
    ): Builder {
        $params = $request instanceof Request ? $request->all() : (array) $request;

        // Default Column Aliases for commonly used columns
        $aliases = array_merge([
            'id'         => $this->getKeyName() ?: 'id',
            'point'      => 'total_points',
            'points'     => 'total_points',
            'name'       => 'fullname',
            'status'     => 'is_active',
            'user_tier'  => 'total_points',
        ], $columnAliases);

        $driver = DB::connection()->getDriverName();
        $likeOp = $driver === 'pgsql' ? 'ILIKE' : 'LIKE';

        // 1. Global Full-Text Search
        $searchTerm = $params['search'] ?? null;
        if (!empty($searchTerm) && !empty($searchableFields)) {
            $query->where(function (Builder $subQuery) use ($searchTerm, $searchableFields, $aliases, $likeOp) {
                foreach ($searchableFields as $index => $field) {
                    $realField = $aliases[$field] ?? $field;

                    if (str_contains($realField, '.')) {
                        [$relation, $relField] = explode('.', $realField, 2);
                        $method = $index === 0 ? 'whereHas' : 'orWhereHas';
                        $subQuery->{$method}($relation, function (Builder $relQuery) use ($relField, $searchTerm, $likeOp) {
                            $relQuery->where($relField, $likeOp, "%{$searchTerm}%");
                        });
                    } else {
                        $method = $index === 0 ? 'where' : 'orWhere';
                        $subQuery->{$method}($realField, $likeOp, "%{$searchTerm}%");
                    }
                }
            });
        }

        // 2. Granular Column-level Filters (Notion / Sheets style)
        $filters = $params['filters'] ?? [];
        if (is_string($filters)) {
            $decoded = json_decode($filters, true);
            if (is_array($decoded)) {
                $filters = $decoded;
            }
        }

        if (is_array($filters) && !empty($filters)) {
            foreach ($filters as $columnKey => $filterRule) {
                if (empty($filterRule) && $filterRule !== 0 && $filterRule !== '0' && $filterRule !== false) {
                    continue;
                }

                if (!is_array($filterRule)) {
                    $op = 'eq';
                    $val = $filterRule;
                } elseif (isset($filterRule['op']) || array_key_exists('val', $filterRule)) {
                    $op = strtolower($filterRule['op'] ?? 'eq');
                    $val = $filterRule['val'] ?? null;
                } else {
                    // Associative array from nested query string e.g. filters[email][contains]=lequy
                    $op = strtolower((string) array_key_first($filterRule));
                    $val = $filterRule[$op] ?? reset($filterRule);
                }

                // Check allowed whitelist
                if (!empty($allowedFilters) && !in_array($columnKey, $allowedFilters, true) && !in_array($aliases[$columnKey] ?? '', $allowedFilters, true)) {
                    continue;
                }

                if ($val === null && !in_array($op, ['is_null', 'is_not_null'], true)) {
                    continue;
                }

                if (is_string($val) && trim($val) === '' && !in_array($op, ['is_null', 'is_not_null'], true)) {
                    continue;
                }

                $realColumn = $aliases[$columnKey] ?? $columnKey;

                // Handle Relationship or Special Column
                if ($columnKey === 'role' || $realColumn === 'role') {
                    $this->applyRoleFilter($query, $op, $val);
                } elseif ($columnKey === 'province' || $realColumn === 'province') {
                    $this->applyProvinceFilter($query, $op, $val, $likeOp);
                } elseif ($columnKey === 'user_tier') {
                    $this->applyUserTierFilter($query, $op, $val);
                } else {
                    $this->applyColumnOperator($query, $realColumn, $op, $val, $likeOp);
                }
            }
        }

        // 3. Dynamic Sorting
        $sortColumn = $params['sort_by'] ?? ($params['sort']['column'] ?? null);
        $sortDir = strtolower($params['sort_dir'] ?? ($params['sort_direction'] ?? ($params['sort']['direction'] ?? 'desc')));
        if (!in_array($sortDir, ['asc', 'desc'], true)) {
            $sortDir = 'desc';
        }

        if (!empty($sortColumn)) {
            $realSortColumn = $aliases[$sortColumn] ?? $sortColumn;
            $query->orderBy($realSortColumn, $sortDir);
        } else {
            // Default sort by created_at or primary key desc
            $defaultSort = in_array('created_at', $allowedSorts, true) ? 'created_at' : ($this->getKeyName() ?: 'created_at');
            $query->orderBy($defaultSort, 'desc');
        }

        return $query;
    }

    /**
     * Apply operator to a query column.
     */
    protected function applyColumnOperator(Builder $query, string $column, string $op, $val, string $likeOp = 'LIKE'): void
    {
        switch ($op) {
            case 'eq':
            case '=':
                if (is_string($val) && in_array(strtolower($val), ['true', 'false'], true)) {
                    $query->where($column, strtolower($val) === 'true');
                } else {
                    $query->where($column, '=', $val);
                }
                break;

            case 'neq':
            case '!=':
            case '<>':
                if (is_string($val) && in_array(strtolower($val), ['true', 'false'], true)) {
                    $query->where($column, '!=', strtolower($val) === 'true');
                } else {
                    $query->where($column, '!=', $val);
                }
                break;

            case 'contains':
            case 'like':
                $query->where($column, $likeOp, "%{$val}%");
                break;

            case 'starts_with':
                $query->where($column, $likeOp, "{$val}%");
                break;

            case 'ends_with':
                $query->where($column, $likeOp, "%{$val}");
                break;

            case 'gt':
            case '>':
                $query->where($column, '>', $val);
                break;

            case 'gte':
            case '>=':
                $query->where($column, '>=', $val);
                break;

            case 'lt':
            case '<':
                $query->where($column, '<', $val);
                break;

            case 'lte':
            case '<=':
                $query->where($column, '<=', $val);
                break;

            case 'between':
                if (is_array($val) && count($val) === 2) {
                    $query->whereBetween($column, [$val[0], $val[1]]);
                } elseif (is_string($val) && str_contains($val, ',')) {
                    $parts = explode(',', $val, 2);
                    $query->whereBetween($column, [trim($parts[0]), trim($parts[1])]);
                }
                break;

            case 'in':
                $arrayVal = is_array($val) ? $val : explode(',', (string) $val);
                $arrayVal = array_map('trim', $arrayVal);
                $query->whereIn($column, $arrayVal);
                break;

            case 'not_in':
                $arrayVal = is_array($val) ? $val : explode(',', (string) $val);
                $arrayVal = array_map('trim', $arrayVal);
                $query->whereNotIn($column, $arrayVal);
                break;

            case 'is_null':
                $query->whereNull($column);
                break;

            case 'is_not_null':
                $query->whereNotNull($column);
                break;

            default:
                $query->where($column, '=', $val);
                break;
        }
    }

    /**
     * Apply filter on role relation
     */
    protected function applyRoleFilter(Builder $query, string $op, $val): void
    {
        $roles = is_array($val) ? $val : [$val];
        $query->whereHas('userRoles.role', function (Builder $q) use ($roles, $op) {
            if ($op === 'neq' || $op === 'not_in') {
                $q->whereNotIn('name', $roles);
            } else {
                $q->whereIn('name', $roles);
            }
        });
    }

    /**
     * Apply filter on province relation
     */
    protected function applyProvinceFilter(Builder $query, string $op, $val, string $likeOp = 'LIKE'): void
    {
        if (is_numeric($val)) {
            $query->where('province_id', (int) $val);
        } else {
            $query->whereHas('province', function (Builder $q) use ($val, $likeOp) {
                $q->where('province_name', $likeOp, "%{$val}%");
            });
        }
    }

    /**
     * Apply filter on user_tier (which maps to total_points ranges)
     */
    protected function applyUserTierFilter(Builder $query, string $op, $val): void
    {
        $tiers = is_array($val) ? $val : [$val];
        $tierModels = \App\Models\UserTier::whereIn('tier', $tiers)->orderBy('min_points', 'asc')->get();

        if ($tierModels->isNotEmpty()) {
            $query->where(function (Builder $sub) use ($tierModels) {
                foreach ($tierModels as $t) {
                    $nextTier = \App\Models\UserTier::where('min_points', '>', $t->min_points)->orderBy('min_points', 'asc')->first();
                    $sub->orWhere(function (Builder $q) use ($t, $nextTier) {
                        $q->where('total_points', '>=', $t->min_points);
                        if ($nextTier) {
                            $q->where('total_points', '<', $nextTier->min_points);
                        }
                    });
                }
            });
        }
    }
}

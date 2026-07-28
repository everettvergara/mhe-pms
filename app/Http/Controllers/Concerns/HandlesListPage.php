<?php

namespace App\Http\Controllers\Concerns;

use App\Services\ListStateService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

trait HandlesListPage
{
    protected int $defaultPerPage = 50;

    /** @var array<int, int> */
    protected array $allowedPerPage = [25, 50, 100, 250];

    /**
     * @param  array<string, mixed>  $defaults
     * @return array<string, mixed>
     */
    protected function resolveListState(Request $request, string $module, array $defaults = []): array
    {
        $defaults = array_merge([
            'search' => '',
            'sort' => $this->defaultSortColumn(),
            'direction' => 'desc',
            'per_page' => $this->defaultPerPage,
            'page' => 1,
            'filters' => [],
        ], $defaults);

        if ($request->boolean('reset')) {
            $this->listStateService()->forget($module);

            return $defaults;
        }

        if ($request->hasAny(['search', 'sort', 'direction', 'per_page', 'page', 'filters'])) {
            $state = [
                'search' => $request->string('search')->toString(),
                'sort' => $request->string('sort')->toString() ?: $defaults['sort'],
                'direction' => strtolower($request->string('direction')->toString()) === 'asc' ? 'asc' : 'desc',
                'per_page' => $this->resolvePerPage($request),
                'page' => max(1, (int) $request->input('page', 1)),
                'filters' => $request->input('filters', []),
            ];

            $this->listStateService()->save($module, $state);

            return $state;
        }

        return $this->listStateService()->restore($module, $defaults);
    }

    protected function resolvePerPage(Request $request): int
    {
        $perPage = (int) $request->input('per_page', $this->defaultPerPage);

        return in_array($perPage, $this->allowedPerPage, true) ? $perPage : $this->defaultPerPage;
    }

    protected function defaultSortColumn(): string
    {
        return 'created_at';
    }

    /**
     * @return array<int, string>
     */
    protected function sortableColumns(): array
    {
        return ['created_at'];
    }

    /**
     * @return array<int, string>
     */
    protected function searchableColumns(): array
    {
        return [];
    }

    protected function applyListQuery(Builder $query, array $state): Builder
    {
        $search = trim((string) ($state['search'] ?? ''));

        if ($search !== '' && $this->searchableColumns() !== []) {
            $query->where(function (Builder $builder) use ($search): void {
                foreach ($this->searchableColumns() as $column) {
                    if (str_contains($column, '.')) {
                        [$relation, $field] = explode('.', $column, 2);
                        $builder->orWhereHas($relation, fn (Builder $q) => $q->where($field, 'like', "%{$search}%"));
                    } else {
                        $builder->orWhere($column, 'like', "%{$search}%");
                    }
                }
            });
        }

        $filters = $state['filters'] ?? [];

        if (is_array($filters)) {
            $query = $this->applyFilters($query, $filters);
        }

        $sort = (string) ($state['sort'] ?? $this->defaultSortColumn());
        $direction = strtolower((string) ($state['direction'] ?? 'desc')) === 'asc' ? 'asc' : 'desc';

        if (in_array($sort, $this->sortableColumns(), true)) {
            $query->orderBy($sort, $direction);
        } else {
            $query->orderBy($this->defaultSortColumn(), 'desc');
        }

        return $query;
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    protected function applyFilters(Builder $query, array $filters): Builder
    {
        return $query;
    }

    /**
     * @param  array<string, mixed>  $state
     */
    protected function paginateList(Builder $query, array $state): LengthAwarePaginator
    {
        return $query->paginate(
            (int) ($state['per_page'] ?? $this->defaultPerPage),
            ['*'],
            'page',
            (int) ($state['page'] ?? 1),
        )->withQueryString();
    }

    protected function listStateService(): ListStateService
    {
        return app(ListStateService::class);
    }
}

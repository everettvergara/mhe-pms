<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HandlesListPage;
use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ActivityLogController extends Controller
{
    use HandlesListPage;

    public function index(Request $request): View
    {
        $this->authorize('viewAny', ActivityLog::class);

        $state = $this->resolveListState($request, 'activity-logs', ['sort' => 'created_at', 'direction' => 'desc']);

        $query = ActivityLog::query()->with('user');
        $records = $this->paginateList($this->applyListQuery($query, $state), $state);

        return view('activity-logs.index', compact('records', 'state'));
    }

    public function show(ActivityLog $activityLog): View
    {
        $this->authorize('view', $activityLog);

        $activityLog->load('user');

        return view('activity-logs.show', compact('activityLog'));
    }

    protected function sortableColumns(): array
    {
        return ['module', 'action', 'created_at'];
    }

    protected function searchableColumns(): array
    {
        return ['module', 'action', 'description'];
    }

    protected function applyFilters(Builder $query, array $filters): Builder
    {
        if (! empty($filters['module'])) {
            $query->where('module', $filters['module']);
        }

        if (! empty($filters['action'])) {
            $query->where('action', $filters['action']);
        }

        if (! empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        return $query;
    }
}

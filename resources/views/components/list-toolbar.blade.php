@props([
    'route',
    'state' => [],
    'createRoute' => null,
    'createLabel' => 'New',
    'showCreate' => true,
    'filters' => null,
])

<div class="card mb-3">
    <div class="card-body py-3">
        <form method="GET" action="{{ $route }}" class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label small mb-1">Search</label>
                <input type="text" name="search" class="form-control form-control-sm" value="{{ $state['search'] ?? '' }}" placeholder="Keyword search..." maxlength="255">
            </div>
            <div class="col-md-2">
                <label class="form-label small mb-1">Per Page</label>
                <select name="per_page" class="form-select form-select-sm">
                    @foreach([25, 50, 100, 250] as $size)
                        <option value="{{ $size }}" @selected(($state['per_page'] ?? 50) == $size)>{{ $size }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small mb-1">Sort</label>
                <select name="sort" class="form-select form-select-sm">
                    {{ $sortOptions ?? '' }}
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small mb-1">Direction</label>
                <select name="direction" class="form-select form-select-sm">
                    <option value="asc" @selected(($state['direction'] ?? 'desc') === 'asc')>ASC</option>
                    <option value="desc" @selected(($state['direction'] ?? 'desc') === 'desc')>DESC</option>
                </select>
            </div>
            <div class="col-md-2 d-flex gap-1">
                <button type="submit" class="btn btn-primary btn-sm flex-grow-1"><i class="bi bi-search"></i> Apply</button>
                <a href="{{ $route }}?reset=1" class="btn btn-outline-secondary btn-sm" title="Reset"><i class="bi bi-arrow-counterclockwise"></i></a>
            </div>
            @if($filters)
                <div class="col-12">{{ $filters }}</div>
            @endif
        </form>
        @if($showCreate && $createRoute)
            <div class="mt-2">
                <a href="{{ $createRoute }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1"></i>{{ $createLabel }}</a>
            </div>
        @endif
    </div>
</div>

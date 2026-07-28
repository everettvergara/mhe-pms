@props([
    'model',
    'showRoute' => null,
    'editRoute' => null,
    'deleteRoute' => null,
    'deleteConfirm' => 'Delete this record?',
    'showView' => null,
    'showEdit' => null,
    'showDelete' => null,
    'relatedRoute' => null,
    'relatedTitle' => 'View PMS',
    'relatedIcon' => 'bi-clipboard-check',
])

@php
    $canView = $showView ?? auth()->user()->can('view', $model);
    $canEdit = $showEdit ?? auth()->user()->can('update', $model);
    $canDelete = $showDelete ?? auth()->user()->can('delete', $model);
@endphp

<div class="d-flex gap-1 justify-content-end">
    @if($relatedRoute)
        <a href="{{ $relatedRoute }}" class="btn btn-sm btn-outline-secondary" title="{{ $relatedTitle }}" target="_blank" rel="noopener noreferrer">
            <i class="bi {{ $relatedIcon }}"></i>
        </a>
    @endif
    @if($showRoute && $canView)
        <a href="{{ $showRoute }}" class="btn btn-sm btn-outline-secondary" title="View"><i class="bi bi-eye"></i></a>
    @endif
    @if($editRoute && $canEdit)
        <a href="{{ $editRoute }}" class="btn btn-sm btn-outline-primary" title="Edit"><i class="bi bi-pencil"></i></a>
    @endif
    @if($deleteRoute && $canDelete)
        <form method="POST" action="{{ $deleteRoute }}" class="d-inline" onsubmit="return confirm(@js($deleteConfirm))">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete"><i class="bi bi-trash"></i></button>
        </form>
    @endif
</div>

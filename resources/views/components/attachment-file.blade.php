@props([
    'attachment',
    'canDelete' => false,
    'deleteFormClass' => 'd-inline',
])

@if($attachment->isAvailable())
    <a href="{{ $attachment->url() }}" target="_blank" rel="noopener">{{ $attachment->displayLabel() }}</a>
@else
    <span class="attachment-placeholder text-muted" title="File was not copied from the source system">
        <i class="bi bi-file-earmark-x me-1" aria-hidden="true"></i>
        <span>{{ $attachment->displayLabel() }}</span>
        <span class="badge text-bg-light border ms-1">Unavailable</span>
    </span>
@endif

@if($canDelete)
    @can('delete', $attachment)
        <form method="POST" action="{{ route('attachments.destroy', $attachment) }}" class="{{ $deleteFormClass }}" onsubmit="return confirm('Delete attachment?')">
            @csrf
            @method('DELETE')
            <button class="btn btn-link btn-sm text-danger p-0">Remove</button>
        </form>
    @endcan
@endif

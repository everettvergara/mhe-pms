@props([
    'attachments',
    'canDelete' => false,
    'compact' => false,
    'inline' => false,
    'externalDeleteForms' => false,
])

@php
    $thumbClass = $compact ? 'attachment-thumb attachment-thumb-sm' : 'attachment-thumb';
    $gridClass = $inline ? 'attachment-grid attachment-grid-inline' : 'attachment-grid';
@endphp

@if($attachments->isNotEmpty())
    <div {{ $attributes->merge(['class' => $gridClass]) }}>
        @foreach($attachments as $attachment)
            <div @class(['attachment-item', 'attachment-item-inline' => $inline && $canDelete])>
                <a href="{{ $attachment->url() }}" target="_blank" rel="noopener" title="{{ $attachment->original_filename }}" @class(['attachment-thumb-link' => $inline && $canDelete])>
                    <img src="{{ $attachment->url() }}" alt="{{ $attachment->original_filename }}" class="{{ $thumbClass }}">
                </a>
                @if($canDelete)
                    @if($externalDeleteForms)
                        <button
                            type="submit"
                            form="delete-attachment-{{ $attachment->id }}"
                            class="attachment-delete-btn"
                            title="Remove"
                            onclick="return confirm('Remove this photo?')"
                        >
                            <i class="bi bi-x" aria-hidden="true"></i>
                        </button>
                    @else
                        <form method="POST" action="{{ route('attachments.destroy', $attachment) }}" @class(['attachment-delete-form' => ! $inline, 'attachment-delete-inline' => $inline]) onsubmit="return confirm('Remove this photo?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="attachment-delete-btn" title="Remove">
                                <i class="bi bi-x" aria-hidden="true"></i>
                            </button>
                        </form>
                    @endif
                @endif
            </div>
        @endforeach
    </div>
@elseif(!$canDelete)
    <span class="text-muted small">—</span>
@endif

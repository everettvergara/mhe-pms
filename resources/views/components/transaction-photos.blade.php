@props([
    'attachments',
    'canUpload' => false,
    'configKey' => 'pms.attachments',
    'uploadAction' => null,
    'inputId' => 'transaction-photos',
    'title' => 'Transaction Photos',
    'wrapperClass' => 'transaction-photos px-3',
])

@php
    $maxPerRecord = (int) config("{$configKey}.max_per_record", 10);
    $remainingSlots = max(0, $maxPerRecord - $attachments->count());
    $accept = '.jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp';
@endphp

<div
    {{ $attributes->merge(['class' => $wrapperClass]) }}
    @if($canUpload && ! $uploadAction) data-deferred-photo-upload data-max-files="{{ $maxPerRecord }}" @endif
>
    <div class="d-flex align-items-center justify-content-between mb-2">
        <strong class="small">{{ $title }}</strong>
        @if($canUpload)
            <span class="text-muted small" @if(! $uploadAction) data-photo-count @endif>{{ $attachments->count() }} / {{ $maxPerRecord }}</span>
        @endif
    </div>

    <x-attachment-thumbnails :attachments="$attachments" :can-delete="$canUpload && $uploadAction" class="mb-2" />

    @if($canUpload && ! $uploadAction)
        <div data-photo-preview class="attachment-grid mb-2 d-none"></div>
    @endif

    @error('files')<div class="text-danger small mb-2">{{ $message }}</div>@enderror
    @error('files.*')<div class="text-danger small mb-2">{{ $message }}</div>@enderror

    @if($canUpload && $remainingSlots > 0)
        @if($uploadAction)
            <form
                id="upload-{{ $inputId }}"
                method="POST"
                action="{{ $uploadAction }}"
                enctype="multipart/form-data"
                class="visually-hidden"
            >
                @csrf
                <input
                    type="file"
                    name="files[]"
                    id="{{ $inputId }}"
                    accept="{{ $accept }}"
                    multiple
                    onchange="this.form.submit()"
                >
            </form>
        @else
            <input
                type="file"
                name="files[]"
                id="{{ $inputId }}"
                accept="{{ $accept }}"
                multiple
                class="visually-hidden"
                data-deferred-photo-input
            >
        @endif
        <label for="{{ $inputId }}" class="btn btn-outline-primary btn-sm mb-0" data-photo-add-label title="Add photos">
            <i class="bi bi-camera me-1"></i>Add Photos
        </label>
    @endif
</div>

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

<div {{ $attributes->merge(['class' => $wrapperClass]) }}>
    <div class="d-flex align-items-center justify-content-between mb-2">
        <strong class="small">{{ $title }}</strong>
        @if($canUpload)
            <span class="text-muted small">{{ $attachments->count() }} / {{ $maxPerRecord }}</span>
        @endif
    </div>

    <x-attachment-thumbnails :attachments="$attachments" :can-delete="$canUpload" class="mb-2" />

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
            >
        @endif
        <label for="{{ $inputId }}" class="btn btn-outline-primary btn-sm mb-0" title="Add photos">
            <i class="bi bi-camera me-1"></i>Add Photos
        </label>
    @endif
</div>

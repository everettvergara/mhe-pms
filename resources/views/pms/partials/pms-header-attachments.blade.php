@php
    $canUploadAttachments = $canUploadAttachments ?? false;
    $remainingSlots = max(0, (int) config('pms.attachments.max_per_record', 10) - $pms->attachments->count());
@endphp

<div class="pms-header-attachments px-3">
    <div class="d-flex align-items-center justify-content-between mb-2">
        <strong class="small">Transaction Photos</strong>
        @if($canUploadAttachments)
            <span class="text-muted small">{{ $pms->attachments->count() }} / {{ config('pms.attachments.max_per_record', 10) }}</span>
        @endif
    </div>

    <x-attachment-thumbnails :attachments="$pms->attachments" :can-delete="$canUploadAttachments" class="mb-2" />

    @error('files')<div class="text-danger small mb-2">{{ $message }}</div>@enderror
    @error('files.*')<div class="text-danger small mb-2">{{ $message }}</div>@enderror

    @if($canUploadAttachments && $remainingSlots > 0)
        <form
            id="upload-pms-{{ $pms->id }}"
            method="POST"
            action="{{ route('pms.attachments.store', $pms) }}"
            enctype="multipart/form-data"
            class="visually-hidden"
        >
            @csrf
            <input
                type="file"
                name="files[]"
                id="file-pms-{{ $pms->id }}"
                accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                multiple
                onchange="this.form.submit()"
            >
        </form>
        <label for="file-pms-{{ $pms->id }}" class="btn btn-outline-primary btn-sm mb-0" title="Add photos">
            <i class="bi bi-camera me-1"></i>Add Photos
        </label>
    @endif
</div>

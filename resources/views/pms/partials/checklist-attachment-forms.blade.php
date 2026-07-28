@php
    $canUploadAttachments = $canUploadAttachments ?? false;
    $sortedDetails = $pms->pmsDetails->sortBy(
        fn ($detail) => ($detail->checklistItem?->checklistGroup?->sequence ?? 0).'-'.($detail->checklistItem?->sequence ?? 0)
    );
@endphp

@if($canUploadAttachments)
    @foreach($sortedDetails as $detail)
        <form
            id="upload-detail-{{ $detail->id }}"
            method="POST"
            action="{{ route('pms-details.attachments.store', $detail) }}"
            enctype="multipart/form-data"
            class="visually-hidden"
        >
            @csrf
            <input
                type="file"
                name="files[]"
                id="file-detail-{{ $detail->id }}"
                accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                multiple
                onchange="this.form.submit()"
            >
        </form>

        @foreach($detail->attachments as $attachment)
            <form
                id="delete-attachment-{{ $attachment->id }}"
                method="POST"
                action="{{ route('attachments.destroy', $attachment) }}"
                class="visually-hidden"
            >
                @csrf
                @method('DELETE')
            </form>
        @endforeach
    @endforeach
@endif

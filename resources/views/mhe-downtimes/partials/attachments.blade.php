@php
    $canUploadAttachments = $canUploadAttachments ?? false;
    $isNew = $isNew ?? false;
    $attachments = $isNew ? collect() : $downtime->attachments;
    $uploadAction = $isNew ? null : route('mhe-downtimes.attachments.store', $downtime);
    $inputId = $isNew ? 'file-downtime-new' : 'file-downtime-'.$downtime->id;
    $wrapperClass = $isNew ? 'transaction-photos border-top pt-3 mt-3' : 'transaction-photos px-3';
@endphp

<x-transaction-photos
    :attachments="$attachments"
    :can-upload="$canUploadAttachments"
    config-key="mhe_downtime.attachments"
    :upload-action="$uploadAction"
    :input-id="$inputId"
    :wrapper-class="$wrapperClass"
/>

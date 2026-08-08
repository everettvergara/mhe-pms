<x-transaction-photos
    :attachments="$pms->attachments"
    :can-upload="$canUploadAttachments ?? false"
    config-key="pms.attachments"
    :upload-action="route('pms.attachments.store', $pms)"
    :input-id="'file-pms-'.$pms->id"
/>

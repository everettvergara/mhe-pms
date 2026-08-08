@extends('layouts.app')
@section('title', $isNew ? 'New MHE Downtime' : 'MHE Downtime #'.$downtime->id)
@section('content')
<x-page-header
    :title="$isNew ? 'New MHE Downtime' : 'MHE Downtime #'.$downtime->id"
    :breadcrumbs="['Transactions'=>null,'MHE Downtimes'=>route('mhe-downtimes.index'),($isNew ? 'New' : '#'.$downtime->id)=>null]"
/>

<div class="card mb-3">
    @if($isNew || ($canEdit ?? false) || ($canEditTimes ?? false))
        <form id="downtime-form" method="POST" action="{{ $isNew ? route('mhe-downtimes.store') : route('mhe-downtimes.update', $downtime) }}" @if($isNew) enctype="multipart/form-data" @endif>
            @csrf
            @if(!$isNew) @method('PUT') @endif
            <div class="card-body">
                @include('mhe-downtimes.partials.header-form', compact('downtime', 'isNew', 'sites', 'suppliers', 'mheTypes', 'mheCategories', 'canEdit', 'canEditTimes'))
                @if($isNew)
                    @include('mhe-downtimes.partials.attachments', compact('downtime', 'isNew', 'canUploadAttachments'))
                @endif
            </div>
        </form>

        @if(!$isNew)
            <div class="card-body border-top py-2">
                @include('mhe-downtimes.partials.attachments', compact('downtime', 'isNew', 'canUploadAttachments'))
            </div>
        @endif
    @else
        <div class="card-body">
            @include('mhe-downtimes.partials.header-form', [
                'downtime' => $downtime,
                'isNew' => false,
                'sites' => $sites,
                'suppliers' => $suppliers ?? collect(),
                'mheTypes' => $mheTypes,
                'mheCategories' => $mheCategories,
                'canEdit' => false,
                'canEditTimes' => false,
            ])
            <div class="border-top py-2">
                @include('mhe-downtimes.partials.attachments', ['downtime' => $downtime, 'isNew' => false, 'canUploadAttachments' => false])
            </div>
        </div>
    @endif

    @if(!$isNew)
        <div class="card-body border-top pt-0">
            @include('mhe-downtimes.partials.action-plans', [
                'downtime' => $downtime,
                'isNew' => false,
                'canManageActionPlans' => $canManageActionPlans ?? false,
                'progressStatuses' => $progressStatuses ?? [],
            ])
        </div>
    @endif

    <div class="card-footer d-flex flex-wrap gap-2 align-items-center">
        @if($isNew)
            <button type="submit" form="downtime-form" class="btn btn-primary btn-sm">Save</button>
        @elseif($canEdit ?? false)
            <button type="submit" form="downtime-form" name="save_as" value="draft" class="btn btn-primary btn-sm">Save</button>
        @elseif($canEditTimes ?? false)
            <button type="submit" form="downtime-form" class="btn btn-primary btn-sm">Save</button>
        @endif

        @if($canPost ?? false)
            <button type="submit" form="downtime-form" name="save_as" value="post" class="btn btn-success btn-sm" onclick="return confirm('Post this downtime record?')">Post</button>
        @endif

        <a href="{{ route('mhe-downtimes.index') }}" class="btn btn-secondary btn-sm">Back</a>

        @if($canRevertToDraft ?? false)
            <form method="POST" action="{{ route('mhe-downtimes.revert-to-draft', $downtime) }}" class="d-inline" onsubmit="return confirm('Revert this record to draft?')">
                @csrf
                <button type="submit" class="btn btn-warning btn-sm">Revert to Draft</button>
            </form>
        @endif

        @if(($canCancel ?? false) && ($downtime->isDraft() ?? false))
            <form method="POST" action="{{ route('mhe-downtimes.cancel', $downtime) }}" class="d-inline" onsubmit="return confirm('Cancel this downtime record?')">
                @csrf
                <button type="submit" class="btn btn-danger btn-sm">Cancel</button>
            </form>
        @endif

        @if(!$isNew && auth()->user()->can('delete', $downtime))
            <form method="POST" action="{{ route('mhe-downtimes.destroy', $downtime) }}" class="d-inline ms-auto" onsubmit="return confirm('Delete this draft record?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-outline-danger btn-sm">Delete</button>
            </form>
        @endif
    </div>
</div>
@endsection

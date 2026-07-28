@props(['pmsDetail', 'actionPlan' => null])

@php
    $isEdit = $actionPlan !== null;
@endphp

<form
    method="POST"
    action="{{ $isEdit ? route('action-plans.update', $actionPlan) : route('action-plans.store') }}"
    class="compact-action-form mb-1"
>
    @csrf
    @if($isEdit) @method('PUT') @endif
    <input type="hidden" name="return_to" value="pms">
    @if(!$isEdit)
        <input type="hidden" name="pms_detail_id" value="{{ $pmsDetail->id }}">
    @endif
    <div class="d-flex flex-wrap gap-1 align-items-end">
        <div style="min-width: 8rem; flex: 1;">
            <input name="title" class="form-control form-control-sm" placeholder="Title *" value="{{ old('title', $actionPlan?->title) }}" required>
        </div>
        <div style="min-width: 7rem; flex: 1;">
            <input name="responsible_person" class="form-control form-control-sm" placeholder="Responsible *" value="{{ old('responsible_person', $actionPlan?->responsible_person) }}" required>
        </div>
        <div style="min-width: 10rem; flex: 2;">
            <input name="description" class="form-control form-control-sm" placeholder="Description *" value="{{ old('description', $actionPlan?->description) }}" required>
        </div>
        <div style="width: 9rem;">
            <input type="date" name="timeline_from" class="form-control form-control-sm" value="{{ old('timeline_from', $actionPlan?->timeline_from?->format('Y-m-d')) }}" required>
        </div>
        <div style="width: 9rem;">
            <input type="date" name="timeline_to" class="form-control form-control-sm" value="{{ old('timeline_to', $actionPlan?->timeline_to?->format('Y-m-d')) }}" required>
        </div>
        <button type="submit" class="btn btn-sm btn-primary">{{ $isEdit ? 'Update' : 'Add' }}</button>
    </div>
</form>

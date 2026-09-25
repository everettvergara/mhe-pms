@extends('layouts.app')
@section('title', $isEdit?'Edit Action Plan':'New Action Plan')
@section('content')
<x-page-header :title="$isEdit?'Edit Action Plan':'New Action Plan'" :breadcrumbs="['Transactions'=>null,'PMS'=>(($pms ?? $pmsDetail?->pmsHeader) ? route('pms.show', $pms ?? $pmsDetail->pmsHeader) : route('pms.index')),($isEdit?'Edit':'New')=>null]" />
@if($isEdit && $pms)
    @include('action-plans.partials.pms-context-card', [
        'pms' => $pms,
        'highlightDetailId' => $highlightPmsDetailId,
        'progressStatuses' => $progressStatuses ?? [],
        'canManageActionPlans' => $canManageActionPlans ?? false,
    ])
@endif
<form method="POST" action="{{ $isEdit?route('action-plans.update',$actionPlan):route('action-plans.store') }}">@csrf @if($isEdit)@method('PUT')@endif
@if(!$isEdit && $pmsDetail)<input type="hidden" name="pms_detail_id" value="{{ $pmsDetail->id }}">@endif
<div class="card"><div class="card-body row g-3">
@if(!$isEdit && $pmsDetail)<div class="col-12"><div class="alert alert-info small mb-0">PMS: {{ $pmsDetail->pmsHeader?->pms_no }} — Finding: {{ $pmsDetail->checklistItem?->description }}</div></div>@endif
<div class="col-12"><label class="form-label">Title <span class="required-mark">*</span></label><input name="title" class="form-control" value="{{ old('title',$actionPlan->title) }}" {{ !$canEdit?'readonly':'' }} required></div>
<div class="col-12"><label class="form-label">Description <span class="required-mark">*</span></label><textarea name="description" class="form-control" rows="4" {{ !$canEdit?'readonly':'' }} required>{{ old('description',$actionPlan->description) }}</textarea></div>
<div class="col-md-4"><label class="form-label">Responsible Person <span class="required-mark">*</span></label><input name="responsible_person" class="form-control" value="{{ old('responsible_person',$actionPlan->responsible_person) }}" {{ !$canEdit?'readonly':'' }} required></div>
<div class="col-md-4"><label class="form-label">Timeline From <span class="required-mark">*</span></label><input type="date" name="timeline_from" class="form-control" value="{{ old('timeline_from',$actionPlan->timeline_from?->format('Y-m-d')) }}" {{ !$canEdit?'readonly':'' }} required></div>
<div class="col-md-4"><label class="form-label">Timeline To <span class="required-mark">*</span></label><input type="date" name="timeline_to" class="form-control" value="{{ old('timeline_to',$actionPlan->timeline_to?->format('Y-m-d')) }}" {{ !$canEdit?'readonly':'' }} required></div>
</div><div class="card-footer d-flex gap-2">@if($canEdit)<button class="btn btn-primary">{{ $isEdit?'Update':'Save' }}</button>@endif<a href="{{ ($pms ?? $pmsDetail?->pmsHeader) ? route('pms.show', $pms ?? $pmsDetail->pmsHeader) : route('pms.index') }}" class="btn btn-secondary">Back</a></div></div></form>
@endsection

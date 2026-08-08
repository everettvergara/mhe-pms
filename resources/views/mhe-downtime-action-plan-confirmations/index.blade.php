@extends('layouts.app')
@section('title', 'Downtime Action Plan Confirmations')
@section('content')
<x-page-header title="Downtime Action Plan Confirmation" :breadcrumbs="['Transactions'=>null,'Confirmation'=>null]" />
<x-list-toolbar :route="route('mhe-downtime-action-plan-confirmations.index')" :state="$state" :show-create="false" />
<div class="card"><div class="table-responsive"><table class="table table-hover mb-0"><thead><tr><th>Action Plan</th><th>Downtime</th><th>Supplier</th><th>Site</th><th>Unit</th><th>Responsible</th><th class="col-actions">Actions</th></tr></thead>
<tbody>@forelse($records as $ap)<tr data-href="{{ route('mhe-downtime-action-plan-confirmations.show',$ap) }}"><td><div>{{ $ap->action_plan_no }}</div><div class="text-muted small">{{ $ap->title }}</div></td><td>#{{ $ap->mheDowntime?->id }}</td><td>{{ $ap->mheDowntime?->supplier?->supplier_name }}</td><td>{{ $ap->mheDowntime?->site?->site_name }}</td><td>{{ $ap->mheDowntime?->ref_unit_no ?? $ap->mheDowntime?->mheInventory?->unit_no ?? '—' }}</td><td>{{ $ap->responsible_person }}</td><td class="col-actions"><x-row-actions :model="$ap" :show-route="route('mhe-downtime-action-plan-confirmations.show', $ap)" /></td></tr>@empty<tr><td colspan="7" class="text-center text-muted py-4">No pending confirmations.</td></tr>@endforelse</tbody></table></div>@if($records->hasPages())<div class="card-footer">{{ $records->links() }}</div>@endif</div>
@endsection

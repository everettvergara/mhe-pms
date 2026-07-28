@extends('layouts.app')
@section('title', 'Action Plan Confirmations')
@section('content')
<x-page-header title="Action Plan Confirmation" :breadcrumbs="['Transactions'=>null,'Confirmation'=>null]" />
<x-list-toolbar :route="route('action-plan-confirmations.index')" :state="$state" :show-create="false" />
<div class="card"><div class="table-responsive"><table class="table table-hover mb-0"><thead><tr><th>Action Plan</th><th>PMS</th><th>Supplier</th><th>Site</th><th>Responsible</th><th class="col-actions">Actions</th></tr></thead>
<tbody>@forelse($records as $ap)<tr data-href="{{ route('action-plan-confirmations.show',$ap) }}"><td>{{ $ap->action_plan_no }}</td><td>{{ $ap->pmsDetail?->pmsHeader?->pms_no }}</td><td>{{ $ap->pmsDetail?->pmsHeader?->supplier?->supplier_name }}</td><td>{{ $ap->pmsDetail?->pmsHeader?->site?->site_name }}</td><td>{{ $ap->responsible_person }}</td><td class="col-actions"><x-row-actions :model="$ap" :show-route="route('action-plan-confirmations.show', $ap)" /></td></tr>@empty<tr><td colspan="6" class="text-center text-muted py-4">No pending confirmations.</td></tr>@endforelse</tbody></table></div>@if($records->hasPages())<div class="card-footer">{{ $records->links() }}</div>@endif</div>
@endsection

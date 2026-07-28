@extends('layouts.app')
@section('title', $title)
@section('content')
<x-page-header :title="$title" :breadcrumbs="['Reports'=>route('reports.index'),$title=>null]">
<x-slot:actions>
<a href="{{ route('reports.export', [$type, 'csv']) }}?{{ http_build_query($filters) }}" class="btn btn-outline-success btn-sm">Export CSV</a>
<a href="{{ route('reports.export', [$type, 'pdf']) }}?{{ http_build_query($filters) }}" class="btn btn-outline-danger btn-sm">Export PDF</a>
</x-slot:actions>
</x-page-header>
<div class="card mb-3"><div class="card-body"><form method="GET" class="row g-2 align-items-end">
<div class="col-md-3"><label class="form-label small">Supplier</label><select name="supplier_id" class="form-select form-select-sm"><option value="">All</option>@foreach($suppliers as $s)<option value="{{ $s->id }}" @selected(($filters['supplier_id']??'')==$s->id)>{{ $s->supplier_name }}</option>@endforeach</select></div>
<div class="col-md-3"><label class="form-label small">Site</label><select name="site_id" class="form-select form-select-sm"><option value="">All</option>@foreach($sites as $s)<option value="{{ $s->id }}" @selected(($filters['site_id']??'')==$s->id)>{{ $s->site_name }}</option>@endforeach</select></div>
<div class="col-md-2"><label class="form-label small">Date From</label><input type="date" name="date_from" class="form-control form-control-sm" value="{{ $filters['date_from'] ?? '' }}"></div>
<div class="col-md-2"><label class="form-label small">Date To</label><input type="date" name="date_to" class="form-control form-control-sm" value="{{ $filters['date_to'] ?? '' }}"></div>
<div class="col-md-2"><button class="btn btn-primary btn-sm w-100">Filter</button></div>
</form></div></div>
<div class="card"><div class="table-responsive"><table class="table table-sm table-hover mb-0"><thead><tr>@foreach($columns as $col)<th>{{ $col }}</th>@endforeach</tr></thead><tbody>
@forelse($records as $row)<tr>@foreach($rowMapper($row) as $cell)<td>{{ $cell }}</td>@endforeach</tr>@empty<tr><td colspan="{{ count($columns) }}" class="text-center text-muted py-4">No data.</td></tr>@endforelse
</tbody></table></div>@if($records->hasPages())<div class="card-footer">{{ $records->links() }}</div>@endif</div>
@endsection

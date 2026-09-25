@extends('layouts.app')

@section('title', 'Units for my sites')

@section('content')
<x-page-header title="Units for my sites" :breadcrumbs="['Dashboard' => route('dashboard'), 'Units' => null]" />

<div class="card">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <strong>PMS for the month {{ number_format($data['kpis']['pms_month_done']) }}/{{ number_format($data['kpis']['pms_month_total']) }}</strong>
        <a href="{{ route('dashboard') }}" class="small">Back to dashboard</a>
    </div>
    @include('dashboard.partials.supplier-units-table', ['units' => $data['units']])
</div>
@endsection

@extends('layouts.app')
@section('title', 'Reports')
@section('content')
<x-page-header title="Reports" :breadcrumbs="['Reports'=>null]" />
<div class="row g-3">@foreach($reportTypes as $slug => $title)<div class="col-md-6 col-lg-4"><a href="{{ route('reports.show',$slug) }}" class="text-decoration-none"><div class="card h-100"><div class="card-body"><i class="bi bi-file-earmark-text text-primary fs-4"></i><h5 class="mt-2 mb-0">{{ $title }}</h5></div></div></a></div>@endforeach</div>
@endsection

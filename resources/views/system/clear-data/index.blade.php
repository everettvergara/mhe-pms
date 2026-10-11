@extends('layouts.app')
@section('title', 'Clear Data')
@section('content')
<x-page-header title="Clear Data" :breadcrumbs="['System' => null, 'Clear Data' => null]" />

<div class="alert alert-danger">
    This permanently deletes every PMS transaction and every MHE downtime record. It cannot be undone. Run this before a migration reload of those records.
</div>

<div class="row g-3 mb-3">
    <div class="col-lg-7">
        <div class="card h-100">
            <div class="card-header">Will be deleted</div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <thead>
                        <tr>
                            <th>Records</th>
                            <th class="text-end">Rows</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($counts as $row)
                            <tr>
                                <td>{{ $row['label'] }}</td>
                                <td class="text-end">{{ number_format($row['count']) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card h-100">
            <div class="card-header">Will be kept</div>
            <div class="card-body">
                <ul class="mb-0">
                    <li>Equipment inventory units. Next PMS date and the last PMS link on each unit are cleared.</li>
                    <li>Users, roles, and permissions</li>
                    <li>Suppliers, sites, districts, and regions</li>
                    <li>MHE types and categories</li>
                    <li>Checklists</li>
                    <li>User assignments</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">Confirmation</div>
    <div class="card-body">
        <form method="POST" action="{{ route('system.clear-data.clear') }}" class="row g-3">
            @csrf
            <div class="col-md-8">
                <label class="form-label" for="confirmation">Type <code>{{ $confirmationPhrase }}</code></label>
                <input id="confirmation" type="text" name="confirmation" class="form-control @error('confirmation') is-invalid @enderror" value="{{ old('confirmation') }}" autocomplete="off" required>
                @error('confirmation')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-12">
                <button type="submit" class="btn btn-danger">Clear data</button>
            </div>
        </form>
    </div>
</div>
@endsection

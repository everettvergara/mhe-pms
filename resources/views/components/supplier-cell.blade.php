@props(['supplier'])

@if($supplier)
    <div class="d-flex align-items-center gap-2">
        @if($supplier->imageUrl())
            <img src="{{ $supplier->imageUrl() }}" alt="{{ $supplier->supplier_name }}" class="supplier-thumb" width="28" height="28">
        @endif
        <span>{{ $supplier->supplier_name }}</span>
    </div>
@else
    —
@endif

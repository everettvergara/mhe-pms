@props(['model'])

<div class="card mt-3">
    <div class="card-header bg-light py-2"><strong>Audit Information</strong></div>
    <div class="card-body small">
        <div class="row g-2">
            @if(isset($model->created_at))
                <div class="col-md-6"><strong>Created:</strong> {{ $model->created_at?->format('Y-m-d H:i') }}</div>
            @endif
            @if(isset($model->creator))
                <div class="col-md-6"><strong>Created By:</strong> {{ $model->creator?->name ?? '—' }}</div>
            @endif
            @if(isset($model->updated_at))
                <div class="col-md-6"><strong>Updated:</strong> {{ $model->updated_at?->format('Y-m-d H:i') }}</div>
            @endif
            @if(isset($model->updater))
                <div class="col-md-6"><strong>Updated By:</strong> {{ $model->updater?->name ?? '—' }}</div>
            @endif
            {{ $slot }}
        </div>
    </div>
</div>

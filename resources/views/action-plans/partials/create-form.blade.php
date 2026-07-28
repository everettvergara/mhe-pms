@props(['pmsDetail'])

<div class="border rounded p-3 mb-2 bg-white">
    <h6 class="small text-muted mb-2">Add Action Plan</h6>
    <form method="POST" action="{{ route('action-plans.store') }}">
        @csrf
        <input type="hidden" name="pms_detail_id" value="{{ $pmsDetail->id }}">
        <input type="hidden" name="return_to" value="pms">
        <div class="row g-2">
            <div class="col-md-6">
                <label class="form-label small">Title <span class="required-mark">*</span></label>
                <input name="title" class="form-control form-control-sm" value="{{ old('title') }}" required>
            </div>
            <div class="col-md-6">
                <label class="form-label small">Responsible Person <span class="required-mark">*</span></label>
                <input name="responsible_person" class="form-control form-control-sm" value="{{ old('responsible_person') }}" required>
            </div>
            <div class="col-12">
                <label class="form-label small">Description <span class="required-mark">*</span></label>
                <textarea name="description" class="form-control form-control-sm" rows="2" required>{{ old('description') }}</textarea>
            </div>
            <div class="col-md-6">
                <label class="form-label small">Timeline From <span class="required-mark">*</span></label>
                <input type="date" name="timeline_from" class="form-control form-control-sm" value="{{ old('timeline_from') }}" required>
            </div>
            <div class="col-md-6">
                <label class="form-label small">Timeline To <span class="required-mark">*</span></label>
                <input type="date" name="timeline_to" class="form-control form-control-sm" value="{{ old('timeline_to') }}" required>
            </div>
            <div class="col-12">
                <button type="submit" class="btn btn-sm btn-primary">Save Action Plan</button>
            </div>
        </div>
    </form>
</div>

@php
    $sortedDetails = $pms->pmsDetails->sortBy(
        fn ($detail) => ($detail->checklistItem?->checklistGroup?->sequence ?? 0).'-'.($detail->checklistItem?->sequence ?? 0)
    );
    $lastGroup = null;
    $detailIndex = 0;
    $checklistRowIndex = 0;
    $canManageActionPlans = $canManageActionPlans ?? false;
    $canUploadAttachments = $canUploadAttachments ?? false;
    $pmsIsDraft = $pms->isDraft();
    $progressStatuses = $progressStatuses ?? [];
    $forPrint = $forPrint ?? false;
    $showActionModals = ! $forPrint && ($canManageActionPlans || $sortedDetails->contains(fn ($detail) => $detail->actionPlans->isNotEmpty()));
    $renderModals = $renderModals ?? true;
    $renderTable = $renderTable ?? true;
    $highlightDetailId = $highlightDetailId ?? null;
@endphp

@if($renderTable)
<div class="table-responsive">
    <table class="table table-sm table-hover table-checklist mb-0">
        <thead>
            <tr>
                <th>Group</th>
                <th>Item</th>
                <th>Answer</th>
                <th>Remarks</th>
                <th>Photos</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($sortedDetails as $detail)
                @php
                    $groupName = $detail->checklistItem?->checklistGroup?->group_name ?? 'General';
                    $stripeClass = $checklistRowIndex % 2 === 0 ? 'checklist-row-odd' : 'checklist-row-even';
                @endphp
                @if($editable)
                    <tr class="checklist-row {{ $stripeClass }}" data-remarks-required>
                        <td class="text-nowrap align-middle py-1 px-2">
                            @if($groupName !== $lastGroup)
                                {{ $groupName }}
                            @endif
                        </td>
                        <td class="align-middle py-1 px-2">{{ $detail->checklistItem?->description }}</td>
                        <td class="align-middle py-1 px-2">
                            <input type="hidden" name="details[{{ $detailIndex }}][id]" value="{{ $detail->id }}">
                            @foreach(\App\Enums\ChecklistAnswer::cases() as $answer)
                                <div class="form-check form-check-inline mb-0">
                                    <input
                                        class="form-check-input"
                                        type="radio"
                                        name="details[{{ $detailIndex }}][answer]"
                                        value="{{ $answer->value }}"
                                        id="ans_{{ $detail->id }}_{{ $loop->index }}"
                                        {{ old("details.{$detailIndex}.answer", $detail->answer?->value) === $answer->value ? 'checked' : '' }}
                                    >
                                    <label class="form-check-label small" for="ans_{{ $detail->id }}_{{ $loop->index }}">{{ $answer->value }}</label>
                                </div>
                            @endforeach
                        </td>
                        <td class="align-middle py-1 px-2">
                            <div class="remarks-wrap {{ $detail->answer?->value !== 'No Good' ? 'd-none' : '' }}">
                                <input
                                    type="text"
                                    name="details[{{ $detailIndex }}][remarks]"
                                    data-remarks-field
                                    class="form-control form-control-sm"
                                    placeholder="Remarks"
                                    value="{{ old("details.{$detailIndex}.remarks", $detail->remarks) }}"
                                >
                            </div>
                        </td>
                        <td class="align-middle py-1 px-2 checklist-photos-cell">
                            <div class="checklist-photos-inline">
                                <x-attachment-thumbnails
                                    :attachments="$detail->attachments"
                                    :can-delete="$canUploadAttachments"
                                    :external-delete-forms="$canUploadAttachments"
                                    compact
                                    inline
                                />
                                @if($canUploadAttachments && $detail->attachments->count() < config('pms.attachments.max_per_record', 10))
                                    <label for="file-detail-{{ $detail->id }}" class="btn btn-outline-secondary btn-sm py-0 px-1 checklist-photo-add-btn" title="Add photo">
                                        <i class="bi bi-camera"></i>
                                    </label>
                                @endif
                            </div>
                        </td>
                        <td class="align-middle py-1 px-2">
                            @include('action-plans.partials.pms-action-cell', [
                                'detail' => $detail,
                                'canManageActionPlans' => $canManageActionPlans,
                                'forPrint' => $forPrint,
                            ])
                        </td>
                    </tr>
                    @php $detailIndex++; @endphp
                @else
                    <tr class="checklist-row {{ $stripeClass }}{{ $highlightDetailId === $detail->id ? ' table-warning' : '' }}">
                        <td class="text-nowrap align-middle py-1 px-2">
                            @if($groupName !== $lastGroup)
                                {{ $groupName }}
                            @endif
                        </td>
                        <td class="align-middle py-1 px-2">{{ $detail->checklistItem?->description }}</td>
                        <td class="align-middle py-1 px-2">
                            @if($detail->answer)
                                <x-status-badge :status="$detail->answer" />
                            @else
                                —
                            @endif
                        </td>
                        <td class="align-middle py-1 px-2">{{ $detail->remarks ?? '—' }}</td>
                        <td class="align-middle py-1 px-2 checklist-photos-cell">
                            <x-attachment-thumbnails :attachments="$detail->attachments" compact inline />
                        </td>
                        <td class="align-middle py-1 px-2">
                            @include('action-plans.partials.pms-action-cell', [
                                'detail' => $detail,
                                'canManageActionPlans' => $canManageActionPlans,
                                'forPrint' => $forPrint,
                            ])
                        </td>
                    </tr>
                @endif
                @php
                    $lastGroup = $groupName;
                    $checklistRowIndex++;
                @endphp
            @endforeach
        </tbody>
    </table>
</div>
@endif

@if($showActionModals && $renderModals)
    @include('action-plans.partials.pms-action-modals', [
        'pms' => $pms,
        'sortedDetails' => $sortedDetails,
        'canManageActionPlans' => $canManageActionPlans,
        'pmsIsDraft' => $pmsIsDraft,
        'progressStatuses' => $progressStatuses,
    ])
@endif

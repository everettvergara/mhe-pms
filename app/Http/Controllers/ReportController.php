<?php

namespace App\Http\Controllers;

use App\Enums\ActionPlanStatus;
use App\Enums\ChecklistAnswer;
use App\Enums\PmsStatus;
use App\Http\Controllers\Concerns\HandlesListPage;
use App\Http\Requests\ReportFilterRequest;
use App\Models\ActionPlan;
use App\Models\PmsHeader;
use App\Models\Site;
use App\Models\Supplier;
use App\Models\User;
use App\Services\SupplierComplianceReportService;
use App\Services\UserDataScopeService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    use HandlesListPage;

    public function __construct(
        protected UserDataScopeService $userDataScopeService,
        protected SupplierComplianceReportService $supplierComplianceReportService,
    ) {}

    /** @var array<string, string> */
    protected array $reportTypes;

    public function index(): RedirectResponse
    {
        $firstSlug = array_key_first($this->reportTypes());

        return redirect()->route('reports.show', $firstSlug);
    }

    public function show(ReportFilterRequest $request, string $type): View
    {
        abort_unless(isset($this->reportTypes()[$type]), 404);

        $user = $request->user();
        $filters = $this->applyUserAssignmentFilters($request->validated(), $user);

        if ($type === 'supplier-compliance') {
            return $this->showSupplierCompliance($request, $filters, $user);
        }

        $state = $this->resolveListState($request, "reports.{$type}", ['sort' => 'created_at', 'direction' => 'desc']);
        $data = $this->buildReportData($type, $filters, $user);
        $records = $this->paginateList($data['query'], $state);
        $rowMapper = $data['rowMapper'];

        $suppliersQuery = Supplier::query()->orderBy('supplier_name');
        $sitesQuery = Site::query()->orderBy('site_name');
        $this->userDataScopeService->scopeSupplier($suppliersQuery, $user);
        $this->userDataScopeService->scopeSite($sitesQuery, $user);

        return view('reports.show', [
            'type' => $type,
            'title' => $this->reportTypes()[$type],
            'records' => $records,
            'state' => $state,
            'filters' => $filters,
            'columns' => $data['columns'],
            'rowMapper' => $rowMapper,
            'suppliers' => $suppliersQuery->get(),
            'sites' => $sitesQuery->get(),
        ]);
    }

    public function export(ReportFilterRequest $request, string $type, string $format): Response|StreamedResponse
    {
        abort_unless(isset($this->reportTypes()[$type]), 404);
        abort_unless(in_array($format, ['csv', 'pdf'], true), 404);

        $user = $request->user();
        $filters = $this->applyUserAssignmentFilters($request->validated(), $user);

        if ($type === 'supplier-compliance') {
            return $this->exportSupplierCompliance($filters, $format, $user);
        }

        $data = $this->buildReportData($type, $filters, $user);
        $rows = $data['query']->get();
        $title = $this->reportTypes()[$type];

        if ($format === 'csv') {
            return $this->exportCsv($title, $data['columns'], $rows, $data['rowMapper']);
        }

        $pdf = Pdf::loadView('reports.pdf', [
            'title' => $title,
            'columns' => $data['columns'],
            'rows' => $rows->map($data['rowMapper']),
            'filters' => $filters,
            'generatedBy' => $user->name,
            'generatedAt' => now()->format('Y-m-d H:i:s'),
        ]);

        return $pdf->download(str($title)->slug().'.pdf');
    }

    /**
     * @return array<string, string>
     */
    protected function reportTypes(): array
    {
        return $this->reportTypes ??= config('reports.types', []);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    protected function showSupplierCompliance(ReportFilterRequest $request, array $filters, User $user): View
    {
        $rows = $this->supplierComplianceReportService->build($filters, $user);
        $summaryChartData = $this->supplierComplianceReportService->summaryChartData($rows);
        $supplierSiteCharts = $this->supplierComplianceReportService->supplierSiteChartsData($rows);
        $view = match ($request->input('view', 'table')) {
            'chart-month-supplier' => 'chart-month-supplier',
            'chart-supplier-site' => 'chart-supplier-site',
            default => 'table',
        };

        $sitesQuery = Site::query()->with('district')->orderBy('site_name');
        $this->userDataScopeService->scopeSite($sitesQuery, $user);

        return view('reports.supplier-compliance', [
            'type' => 'supplier-compliance',
            'title' => $this->reportTypes()['supplier-compliance'],
            'rows' => $rows,
            'summaryChartData' => $summaryChartData,
            'supplierSiteCharts' => $supplierSiteCharts,
            'view' => $view,
            'filters' => $filters,
            'sites' => $sitesQuery->get(),
            'columns' => [
                'Supplier',
                'District',
                'Site',
                'Month',
                'Action Items Completed',
                'Action Items to be Completed',
                'Compliance',
            ],
        ]);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    protected function exportSupplierCompliance(array $filters, string $format, User $user): Response|StreamedResponse
    {
        $rows = $this->supplierComplianceReportService->build($filters, $user);
        $title = $this->reportTypes()['supplier-compliance'];
        $columns = [
            'Supplier',
            'District',
            'Site',
            'Month',
            'Action Items Completed',
            'Action Items to be Completed',
            'Compliance',
        ];

        $rowMapper = fn (object $row): array => [
            $row->supplier_name,
            $row->district_name,
            $row->site_name,
            $row->month_label,
            $row->completed_on_time,
            $row->items_to_complete,
            $row->compliance_percent.'%',
        ];

        if ($format === 'csv') {
            return $this->exportCsv($title, $columns, $rows, $rowMapper);
        }

        $pdf = Pdf::loadView('reports.pdf', [
            'title' => $title,
            'columns' => $columns,
            'rows' => $rows->map($rowMapper),
            'filters' => $filters,
            'generatedBy' => $user->name,
            'generatedAt' => now()->format('Y-m-d H:i:s'),
        ]);

        return $pdf->download(str($title)->slug().'.pdf');
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{query: Builder, columns: array<int, string>, rowMapper: callable}
     */
    protected function buildReportData(string $type, array $filters, $user): array
    {
        return match ($type) {
            'pms-summary' => $this->pmsSummaryReport($filters),
            'findings' => $this->findingsReport($filters),
            'action-plans' => $this->actionPlansReport($filters),
            'pending-confirmation' => $this->pendingConfirmationReport($filters),
            default => abort(404),
        };
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    protected function pmsSummaryReport(array $filters): array
    {
        $query = PmsHeader::query()->with(['supplier', 'site', 'mheType']);
        $this->applyPmsFilters($query, $filters);

        return [
            'query' => $query,
            'columns' => ['PMS No.', 'Site', 'Supplier', 'Technician', 'MHE Type', 'Unit No.', 'Status', 'Date From', 'Date To'],
            'rowMapper' => fn (PmsHeader $p) => [
                $p->pms_no,
                $p->site?->site_name,
                $p->supplier?->supplier_name,
                $p->technician_name,
                $p->mheType?->code,
                $p->unit_number,
                $p->status?->value,
                $p->date_from?->format('Y-m-d'),
                $p->date_to?->format('Y-m-d'),
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    protected function findingsReport(array $filters): array
    {
        $query = PmsHeader::query()
            ->where('status', PmsStatus::WithFindings)
            ->with(['supplier', 'site', 'pmsDetails' => fn ($q) => $q->where('answer', ChecklistAnswer::NoGood)->with('checklistItem.checklistGroup')]);

        $this->applyPmsFilters($query, $filters);

        return [
            'query' => $query,
            'columns' => ['PMS No.', 'Supplier', 'Site', 'Technician', 'Status', 'Submitted'],
            'rowMapper' => fn (PmsHeader $p) => [
                $p->pms_no,
                $p->supplier?->supplier_name,
                $p->site?->site_name,
                $p->technician_name,
                $p->status?->value,
                $p->submitted_at?->format('Y-m-d'),
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    protected function actionPlansReport(array $filters): array
    {
        $query = ActionPlan::query()
            ->with(['pmsDetail.checklistItem', 'pmsDetail.pmsHeader.supplier', 'pmsDetail.pmsHeader.site']);

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['supplier_id'])) {
            $query->whereHas('pmsDetail.pmsHeader', fn (Builder $q) => $q->where('supplier_id', $filters['supplier_id']));
        }

        if (! empty($filters['supplier_ids'])) {
            $query->whereHas('pmsDetail.pmsHeader', fn (Builder $q) => $q->whereIn('supplier_id', $filters['supplier_ids']));
        }

        $this->applyActionPlanSiteFilters($query, $filters);

        return [
            'query' => $query,
            'columns' => ['Action Plan No.', 'PMS No.', 'Supplier', 'Site', 'Title', 'Responsible', 'Status', 'Timeline'],
            'rowMapper' => fn (ActionPlan $a) => [
                $a->action_plan_no,
                $a->pmsDetail?->pmsHeader?->pms_no,
                $a->pmsDetail?->pmsHeader?->supplier?->supplier_name,
                $a->pmsDetail?->pmsHeader?->site?->site_name,
                $a->title,
                $a->responsible_person,
                $a->status?->value,
                $a->timeline_from?->format('Y-m-d').' - '.$a->timeline_to?->format('Y-m-d'),
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    protected function pendingConfirmationReport(array $filters): array
    {
        $query = ActionPlan::query()
            ->where('status', ActionPlanStatus::WaitingForFastConfirmation)
            ->with(['pmsDetail.checklistItem', 'pmsDetail.pmsHeader.supplier', 'pmsDetail.pmsHeader.site']);

        if (! empty($filters['supplier_id'])) {
            $query->whereHas('pmsDetail.pmsHeader', fn (Builder $q) => $q->where('supplier_id', $filters['supplier_id']));
        }

        if (! empty($filters['supplier_ids'])) {
            $query->whereHas('pmsDetail.pmsHeader', fn (Builder $q) => $q->whereIn('supplier_id', $filters['supplier_ids']));
        }

        $this->applyActionPlanSiteFilters($query, $filters);

        return [
            'query' => $query,
            'columns' => ['Action Plan No.', 'PMS No.', 'Supplier', 'Site', 'Checklist Item', 'Responsible', 'Updated'],
            'rowMapper' => fn (ActionPlan $a) => [
                $a->action_plan_no,
                $a->pmsDetail?->pmsHeader?->pms_no,
                $a->pmsDetail?->pmsHeader?->supplier?->supplier_name,
                $a->pmsDetail?->pmsHeader?->site?->site_name,
                $a->pmsDetail?->checklistItem?->description,
                $a->responsible_person,
                $a->updated_at?->format('Y-m-d'),
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    protected function applyPmsFilters(Builder $query, array $filters): void
    {
        if (! empty($filters['supplier_id'])) {
            $query->where('supplier_id', $filters['supplier_id']);
        }

        if (! empty($filters['supplier_ids'])) {
            $query->whereIn('supplier_id', $filters['supplier_ids']);
        }

        if (! empty($filters['site_id'])) {
            $query->where('site_id', $filters['site_id']);
        }

        if (! empty($filters['site_ids'])) {
            $query->whereIn('site_id', $filters['site_ids']);
        }

        if (! empty($filters['mhe_type_id'])) {
            $query->where('mhe_type_id', $filters['mhe_type_id']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['date_from'])) {
            $query->whereDate('date_from', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('date_to', '<=', $filters['date_to']);
        }
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    protected function applyActionPlanSiteFilters(Builder $query, array $filters): void
    {
        if (! empty($filters['site_id'])) {
            $query->whereHas('pmsDetail.pmsHeader', fn (Builder $q) => $q->where('site_id', $filters['site_id']));
        }

        if (! empty($filters['site_ids'])) {
            $query->whereHas('pmsDetail.pmsHeader', fn (Builder $q) => $q->whereIn('site_id', $filters['site_ids']));
        }
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    protected function applyUserAssignmentFilters(array $filters, User $user): array
    {
        if ($user->isSuperAdmin()) {
            return $filters;
        }

        $supplierIds = $user->assignedSupplierIds();

        if ($supplierIds !== []) {
            $filters['supplier_ids'] = $supplierIds;

            if (count($supplierIds) === 1) {
                $filters['supplier_id'] = $supplierIds[0];
            }
        }

        $siteIds = $user->assignedSiteIds();

        if ($siteIds !== []) {
            if (! empty($filters['site_ids'])) {
                $filters['site_ids'] = array_values(array_intersect(
                    array_map('intval', $filters['site_ids']),
                    $siteIds,
                ));
            } else {
                $filters['site_ids'] = $siteIds;
            }

            if (count($filters['site_ids']) === 1) {
                $filters['site_id'] = $filters['site_ids'][0];
            }
        }

        return $filters;
    }

    /**
     * @param  array<int, string>  $columns
     */
    protected function exportCsv(string $title, array $columns, Collection $rows, callable $rowMapper): StreamedResponse
    {
        $filename = str($title)->slug().'.csv';

        return response()->streamDownload(function () use ($columns, $rows, $rowMapper): void {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $columns);

            foreach ($rows as $row) {
                fputcsv($handle, $rowMapper($row));
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}

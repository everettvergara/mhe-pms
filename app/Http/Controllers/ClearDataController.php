<?php

namespace App\Http\Controllers;

use App\Http\Requests\ClearPmsMheRecordsRequest;
use App\Services\ClearPmsMheRecordsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ClearDataController extends Controller
{
    public function index(ClearPmsMheRecordsService $clearData): View
    {
        return view('system.clear-data.index', [
            'counts' => $clearData->counts(),
            'confirmationPhrase' => config('data_clear.confirmation_phrase'),
        ]);
    }

    public function clear(ClearPmsMheRecordsRequest $request, ClearPmsMheRecordsService $clearData): RedirectResponse
    {
        $deleted = $clearData->clear($request->user());
        $total = array_sum(array_column($deleted, 'count'));

        return redirect()
            ->route('system.clear-data.index')
            ->with('success', 'Cleared PMS transactions and MHE downtime history. '.$total.' row(s) removed. Equipment inventory, users, and master data were kept.');
    }
}

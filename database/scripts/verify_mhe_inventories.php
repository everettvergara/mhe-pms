<?php

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$inv = App\Models\MheInventory::count();
$defaultId = App\Models\Site::where('site_code', 'default')->value('id');
$default = App\Models\MheInventory::where('site_id', $defaultId)->count();
$nullType = App\Models\MheInventory::whereNull('mhe_type_id')->count();
$nullUnit = App\Models\MheInventory::whereNull('unit_no')->count();

echo "inventories={$inv} default_site={$default} null_type={$nullType} null_unit={$nullUnit}\n";
echo 'status='.json_encode(App\Models\MheInventory::selectRaw('equipment_status, count(*) as c')->groupBy('equipment_status')->pluck('c', 'equipment_status'))."\n";

echo "types:\n";
foreach (App\Models\MheType::orderBy('code')->get(['code', 'description']) as $t) {
    echo "  {$t->code} => {$t->description}\n";
}

echo "suppliers:\n";
foreach (App\Models\Supplier::orderBy('supplier_code')->get(['supplier_code', 'supplier_name']) as $s) {
    echo "  {$s->supplier_code} => {$s->supplier_name}\n";
}

echo "unmatched excel sites (on default):\n";
foreach (App\Models\MheInventory::where('site_id', $defaultId)->select('site')->distinct()->orderBy('site')->pluck('site') as $s) {
    echo "  {$s}\n";
}

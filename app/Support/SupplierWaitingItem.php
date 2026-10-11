<?php

namespace App\Support;

final class SupplierWaitingItem
{
    public function __construct(
        public string $unit,
        public string $site,
        public ?string $action_plan_no,
        public string $title,
        public ?string $due,
        public ?string $status,
        public bool $missing_action_plan,
        public ?string $url,
    ) {}
}

<?php

namespace App\Services;

class ListStateService
{
    public function save(string $module, array $state): void
    {
        session()->put($this->sessionKey($module), $state);
    }

    /**
     * @return array<string, mixed>
     */
    public function restore(string $module, array $defaults = []): array
    {
        /** @var array<string, mixed> $state */
        $state = session()->get($this->sessionKey($module), $defaults);

        return $state;
    }

    public function forget(string $module): void
    {
        session()->forget($this->sessionKey($module));
    }

    protected function sessionKey(string $module): string
    {
        return "list_state.{$module}";
    }
}

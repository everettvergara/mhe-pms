@props(['checked' => false])

<span data-unit-safe class="d-inline-flex align-items-center" title="I guarantee that the unit is safe to use">
    <input
        type="checkbox"
        class="form-check-input m-0"
        @checked($checked)
        disabled
        tabindex="-1"
        aria-label="I guarantee that the unit is safe to use"
    >
</span>

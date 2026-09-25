const originalSiteOptions = new WeakMap();

export function applyDistrictSiteFilter(districtSelect) {
    if (!(districtSelect instanceof HTMLSelectElement)) {
        return;
    }

    const siteSelect = document.getElementById(districtSelect.dataset.controlsSite || '');

    if (!(siteSelect instanceof HTMLSelectElement)) {
        return;
    }

    if (!originalSiteOptions.has(siteSelect)) {
        originalSiteOptions.set(
            siteSelect,
            Array.from(siteSelect.options).map((option) => option.cloneNode(true)),
        );
    }

    const districtId = districtSelect.value;
    const selected = siteSelect.value;

    siteSelect.replaceChildren();

    originalSiteOptions.get(siteSelect).forEach((option) => {
        const clone = option.cloneNode(true);
        const matchesDistrict = districtId !== '' && clone.dataset.districtId === districtId;

        if (clone.value === '' || matchesDistrict) {
            siteSelect.appendChild(clone);
        }
    });

    const stillThere = Array.from(siteSelect.options).some((option) => option.value === selected);
    siteSelect.value = stillThere ? selected : '';
}

export function initDistrictSiteFilters() {
    document.querySelectorAll('[data-controls-site]').forEach((districtSelect) => {
        if (!(districtSelect instanceof HTMLSelectElement)) {
            return;
        }

        applyDistrictSiteFilter(districtSelect);
        districtSelect.addEventListener('change', () => applyDistrictSiteFilter(districtSelect));
    });
}

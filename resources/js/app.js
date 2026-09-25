import './bootstrap';
import './recaptcha';
import { applyDistrictSiteFilter, initDistrictSiteFilters } from './district-site-filter';
import { Collapse, Modal } from 'bootstrap';
import $ from 'jquery';
import Chart from 'chart.js/auto';

window.$ = window.jQuery = $;
window.Chart = Chart;
window.applyDistrictSiteFilter = applyDistrictSiteFilter;

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('tr[data-href]').forEach((row) => {
        row.addEventListener('click', (event) => {
            if (event.target.closest('a, button, input, form, select, textarea, label, [data-unit-safe]')) {
                return;
            }

            window.location = row.dataset.href;
        });
    });

    document.querySelectorAll('[data-remarks-required]').forEach((group) => {
        const radios = group.querySelectorAll('input[type=radio]');
        const remarks = group.querySelector('[data-remarks-field]');

        const toggle = () => {
            const selected = group.querySelector('input[type=radio]:checked');
            const noGood = selected && selected.value === 'No Good';
            if (remarks) {
                remarks.required = noGood;
                remarks.closest('.remarks-wrap')?.classList.toggle('d-none', !noGood);
            }
        };

        radios.forEach((radio) => radio.addEventListener('change', toggle));
        toggle();
    });

    document.addEventListener('change', (event) => {
        const select = event.target.closest('[data-progress-status]');
        if (!select) {
            return;
        }

        const form = select.closest('form');
        const checkbox = form?.querySelector('[data-unit-safe-checkbox]');
        if (!checkbox) {
            return;
        }

        const implementing = select.value === 'Implemented';
        checkbox.disabled = !implementing;
        checkbox.required = implementing;

        if (!implementing) {
            checkbox.checked = false;
        }
    });

    initDistrictSiteFilters();
    initPmsActionPlanModals();
    initDowntimeActionPlanModals();
    initPmsDateSync();
    initUserFormToggles();
    initAssignedSitesPicker();
    initSidebarToggle();
});

const jsonRequestHeaders = {
    'X-Requested-With': 'XMLHttpRequest',
    Accept: 'application/json',
};

async function requestJson(url, options = {}) {
    const response = await fetch(url, {
        ...options,
        headers: {
            ...jsonRequestHeaders,
            ...(options.headers || {}),
        },
    });

    const data = await response.json().catch(() => ({}));

    if (!response.ok) {
        const message = data.message
            || (data.errors && Object.values(data.errors).flat().join(' '))
            || 'Request failed.';

        throw new Error(message);
    }

    return data;
}

async function savePmsDraftFromForm() {
    const pmsForm = document.getElementById('pms-save-form');
    if (!pmsForm) {
        return;
    }

    const formData = new FormData(pmsForm);
    formData.set('save_as', 'draft');

    await requestJson(pmsForm.action, {
        method: 'POST',
        body: formData,
    });
}

async function submitPmsContextActionPlanForm(targetForm) {
    await savePmsDraftFromForm();

    await requestJson(targetForm.action, {
        method: 'POST',
        body: new FormData(targetForm),
    });

    window.location.reload();
}

async function submitDowntimeContextActionPlanForm(targetForm) {
    await requestJson(targetForm.action, {
        method: 'POST',
        body: new FormData(targetForm),
    });

    window.location.reload();
}

function initDowntimeActionPlanModals() {
    const modalEl = document.getElementById('downtimeActionPlanModal');
    if (!modalEl) {
        return;
    }

    const modal = Modal.getOrCreateInstance(modalEl);
    const views = {
        list: document.getElementById('dt-ap-view-list'),
        form: document.getElementById('dt-ap-view-form'),
        detail: document.getElementById('dt-ap-view-detail'),
    };
    const titleEl = document.getElementById('dt-ap-modal-title');
    const contextEl = document.getElementById('dt-ap-modal-context');
    const listContainer = document.getElementById('dt-ap-list-container');
    const detailContainer = document.getElementById('dt-ap-detail-container');
    const form = document.getElementById('dt-ap-form');
    const formSubmit = document.getElementById('dt-ap-form-submit');
    const timelineFromInput = document.getElementById('dt-ap-timeline-from');
    const timelineToInput = document.getElementById('dt-ap-timeline-to');

    let currentDowntimeId = null;
    let currentItemLabel = '';

    const showView = (name) => {
        Object.entries(views).forEach(([key, element]) => {
            element?.classList.toggle('d-none', key !== name);
        });
    };

    const resetForm = () => {
        if (!form || !formSubmit) {
            return;
        }

        form.reset();
        form.action = form.dataset.storeUrl;
        form.method = 'post';
        form.querySelector('input[name="_method"]')?.remove();
        formSubmit.textContent = 'Save';
        if (timelineToInput) {
            timelineToInput.removeAttribute('min');
        }
    };

    const syncTimelineToWithFrom = () => {
        if (!timelineFromInput || !timelineToInput || !timelineFromInput.value) {
            return;
        }

        timelineToInput.value = timelineFromInput.value;
        timelineToInput.min = timelineFromInput.value;
    };

    if (timelineFromInput && timelineToInput) {
        timelineFromInput.addEventListener('change', syncTimelineToWithFrom);
        timelineFromInput.addEventListener('input', syncTimelineToWithFrom);
    }

    if (form) {
        form.addEventListener('submit', async (event) => {
            event.preventDefault();

            if (!formSubmit) {
                return;
            }

            formSubmit.disabled = true;

            try {
                await submitDowntimeContextActionPlanForm(form);
            } catch (error) {
                formSubmit.disabled = false;
                window.alert(error.message || 'Unable to save action item.');
            }
        });
    }

    listContainer?.addEventListener('submit', async (event) => {
        const targetForm = event.target;
        if (!(targetForm instanceof HTMLFormElement) || !targetForm.action.includes('/action-plans/')) {
            return;
        }

        event.preventDefault();

        const submitButton = targetForm.querySelector('button[type="submit"]');
        if (submitButton) {
            submitButton.disabled = true;
        }

        try {
            await submitDowntimeContextActionPlanForm(targetForm);
        } catch (error) {
            if (submitButton) {
                submitButton.disabled = false;
            }

            window.alert(error.message || 'Unable to complete this action.');
        }
    });

    const openList = (downtimeId, itemLabel) => {
        currentDowntimeId = downtimeId;
        currentItemLabel = itemLabel;
        const template = document.getElementById(`dt-ap-list-${downtimeId}`);
        listContainer.innerHTML = template ? template.innerHTML : '<p class="text-muted small mb-0">No action items.</p>';
        titleEl.textContent = 'Action Items';
        contextEl.textContent = itemLabel;
        showView('list');
        modal.show();
    };

    const openForm = (downtimeId, itemLabel, planData = null) => {
        currentDowntimeId = downtimeId;
        currentItemLabel = itemLabel;
        resetForm();

        if (planData) {
            form.action = planData.updateUrl;
            const methodInput = document.createElement('input');
            methodInput.type = 'hidden';
            methodInput.name = '_method';
            methodInput.value = 'PUT';
            form.appendChild(methodInput);
            document.getElementById('dt-ap-title').value = planData.title;
            document.getElementById('dt-ap-description').value = planData.description;
            document.getElementById('dt-ap-responsible-person').value = planData.responsiblePerson;
            document.getElementById('dt-ap-timeline-from').value = planData.timelineFrom;
            document.getElementById('dt-ap-timeline-to').value = planData.timelineTo;
            if (timelineToInput && planData.timelineFrom) {
                timelineToInput.min = planData.timelineFrom;
            }
            titleEl.textContent = 'Edit Action Item';
            formSubmit.textContent = 'Update';
        } else {
            titleEl.textContent = 'Add Action Item';
            formSubmit.textContent = 'Save';
        }

        contextEl.textContent = itemLabel;
        showView('form');
        modal.show();
    };

    const openDetail = (planId, itemLabel) => {
        const template = document.getElementById(`dt-ap-detail-${planId}`);
        detailContainer.innerHTML = template ? template.innerHTML : '';
        titleEl.textContent = 'Action Item Status';
        contextEl.textContent = itemLabel;
        showView('detail');
        modal.show();
    };

    document.addEventListener('click', (event) => {
        const button = event.target.closest('[data-ap-action]');
        if (!button) {
            return;
        }

        if (!button.closest('.action-items-toolbar')
            && !button.closest('#downtimeActionPlanModal')
            && !button.closest('.border-top.pt-3')) {
            return;
        }

        if (!button.dataset.downtimeId) {
            return;
        }

        const action = button.dataset.apAction;
        const downtimeId = button.dataset.downtimeId;
        const itemLabel = button.dataset.itemLabel || '';
        const planId = button.dataset.planId;

        if (action === 'list') {
            event.preventDefault();
            openList(downtimeId, itemLabel);
            return;
        }

        if (action === 'add') {
            event.preventDefault();
            openForm(downtimeId, itemLabel);
            return;
        }

        if (action === 'edit') {
            event.preventDefault();
            openForm(downtimeId, itemLabel, {
                updateUrl: button.dataset.updateUrl,
                title: button.dataset.title || '',
                description: button.dataset.description || '',
                responsiblePerson: button.dataset.responsiblePerson || '',
                timelineFrom: button.dataset.timelineFrom || '',
                timelineTo: button.dataset.timelineTo || '',
            });
            return;
        }

        if (action === 'detail') {
            event.preventDefault();
            openDetail(planId, itemLabel);
            return;
        }

        if (action === 'back-list' && currentDowntimeId) {
            event.preventDefault();
            openList(currentDowntimeId, currentItemLabel);
        }
    });

    const deeplinkPlanId = document.getElementById('action-plan-deeplink')?.dataset.planId;
    if (deeplinkPlanId && document.getElementById(`dt-ap-detail-${deeplinkPlanId}`)) {
        openDetail(deeplinkPlanId, '');
    }

    modalEl.addEventListener('hidden.bs.modal', () => {
        listContainer.innerHTML = '';
        detailContainer.innerHTML = '';
        resetForm();
        currentDowntimeId = null;
        currentItemLabel = '';
        showView('list');
    });
}

function initPmsDateSync() {
    const form = document.getElementById('pms-save-form');
    if (!form) {
        return;
    }

    const dateFromInput = form.querySelector('input[name="date_from"]');
    const dateToInput = form.querySelector('input[name="date_to"]');

    if (!dateFromInput || !dateToInput) {
        return;
    }

    const syncDateToWithFrom = () => {
        if (!dateFromInput.value) {
            return;
        }

        dateToInput.value = dateFromInput.value;
        dateToInput.min = dateFromInput.value;
    };

    dateFromInput.addEventListener('change', syncDateToWithFrom);
    dateFromInput.addEventListener('input', syncDateToWithFrom);

    if (dateFromInput.value) {
        dateToInput.min = dateFromInput.value;

        if (!dateToInput.value) {
            dateToInput.value = dateFromInput.value;
        }
    }
}

function initPmsActionPlanModals() {
    const modalEl = document.getElementById('pmsActionPlanModal');
    if (!modalEl) {
        return;
    }

    const modal = Modal.getOrCreateInstance(modalEl);
    const views = {
        list: document.getElementById('ap-view-list'),
        form: document.getElementById('ap-view-form'),
        detail: document.getElementById('ap-view-detail'),
    };
    const titleEl = document.getElementById('ap-modal-title');
    const contextEl = document.getElementById('ap-modal-context');
    const listContainer = document.getElementById('ap-list-container');
    const detailContainer = document.getElementById('ap-detail-container');
    const form = document.getElementById('ap-form');
    const formSubmit = document.getElementById('ap-form-submit');
    const pmsDetailInput = document.getElementById('ap-pms-detail-id');
    const timelineFromInput = document.getElementById('ap-timeline-from');
    const timelineToInput = document.getElementById('ap-timeline-to');

    let currentDetailId = null;
    let currentItemLabel = '';

    const showView = (name) => {
        Object.entries(views).forEach(([key, element]) => {
            element?.classList.toggle('d-none', key !== name);
        });
    };

    const resetForm = () => {
        if (!form || !pmsDetailInput || !formSubmit) {
            return;
        }

        form.reset();
        form.action = form.dataset.storeUrl;
        form.method = 'post';
        form.querySelector('input[name="_method"]')?.remove();
        pmsDetailInput.disabled = false;
        formSubmit.textContent = 'Save';
        if (timelineToInput) {
            timelineToInput.removeAttribute('min');
        }
    };

    const syncTimelineToWithFrom = () => {
        if (!timelineFromInput || !timelineToInput || !timelineFromInput.value) {
            return;
        }

        timelineToInput.value = timelineFromInput.value;
        timelineToInput.min = timelineFromInput.value;
    };

    if (timelineFromInput && timelineToInput) {
        timelineFromInput.addEventListener('change', syncTimelineToWithFrom);
        timelineFromInput.addEventListener('input', syncTimelineToWithFrom);
    }

    if (form) {
        form.addEventListener('submit', async (event) => {
            event.preventDefault();

            if (!formSubmit) {
                return;
            }

            formSubmit.disabled = true;

            try {
                await submitPmsContextActionPlanForm(form);
            } catch (error) {
                formSubmit.disabled = false;
                window.alert(error.message || 'Unable to save action item.');
            }
        });
    }

    listContainer?.addEventListener('submit', async (event) => {
        const targetForm = event.target;
        if (!(targetForm instanceof HTMLFormElement) || !targetForm.action.includes('/action-plans/')) {
            return;
        }

        event.preventDefault();

        const submitButton = targetForm.querySelector('button[type="submit"]');
        if (submitButton) {
            submitButton.disabled = true;
        }

        try {
            await submitPmsContextActionPlanForm(targetForm);
        } catch (error) {
            if (submitButton) {
                submitButton.disabled = false;
            }

            window.alert(error.message || 'Unable to complete this action.');
        }
    });

    const openList = (detailId, itemLabel) => {
        currentDetailId = detailId;
        currentItemLabel = itemLabel;
        const template = document.getElementById(`ap-list-${detailId}`);
        listContainer.innerHTML = template ? template.innerHTML : '<p class="text-muted small mb-0">No action items.</p>';
        titleEl.textContent = 'Action Items';
        contextEl.textContent = itemLabel;
        showView('list');
        modal.show();
    };

    const openForm = (detailId, itemLabel, planData = null) => {
        currentDetailId = detailId;
        currentItemLabel = itemLabel;
        resetForm();
        pmsDetailInput.value = detailId;

        if (planData) {
            form.action = planData.updateUrl;
            const methodInput = document.createElement('input');
            methodInput.type = 'hidden';
            methodInput.name = '_method';
            methodInput.value = 'PUT';
            form.appendChild(methodInput);
            pmsDetailInput.disabled = true;
            document.getElementById('ap-title').value = planData.title;
            document.getElementById('ap-description').value = planData.description;
            document.getElementById('ap-responsible-person').value = planData.responsiblePerson;
            document.getElementById('ap-timeline-from').value = planData.timelineFrom;
            document.getElementById('ap-timeline-to').value = planData.timelineTo;
            if (timelineToInput && planData.timelineFrom) {
                timelineToInput.min = planData.timelineFrom;
            }
            titleEl.textContent = 'Edit Action Item';
            formSubmit.textContent = 'Update';
        } else {
            titleEl.textContent = 'Add Action Item';
            formSubmit.textContent = 'Save';
        }

        contextEl.textContent = itemLabel;
        showView('form');
        modal.show();
    };

    const openDetail = (planId, itemLabel) => {
        const template = document.getElementById(`ap-detail-${planId}`);
        detailContainer.innerHTML = template ? template.innerHTML : '';
        titleEl.textContent = 'Action Item Status';
        contextEl.textContent = itemLabel;
        showView('detail');
        modal.show();
    };

    document.addEventListener('click', (event) => {
        const button = event.target.closest('[data-ap-action]');
        if (!button) {
            return;
        }

        if (!button.closest('.pms-action-cell')
            && !button.closest('.action-items-toolbar')
            && !button.closest('#pmsActionPlanModal')) {
            return;
        }

        if (button.dataset.downtimeId) {
            return;
        }

        const action = button.dataset.apAction;
        const detailId = button.dataset.pmsDetailId;
        const itemLabel = button.dataset.itemLabel || '';
        const planId = button.dataset.planId;

        if (action === 'list') {
            event.preventDefault();
            openList(detailId, itemLabel);
            return;
        }

        if (action === 'add') {
            event.preventDefault();
            openForm(detailId, itemLabel);
            return;
        }

        if (action === 'edit') {
            event.preventDefault();
            openForm(detailId, itemLabel, {
                updateUrl: button.dataset.updateUrl,
                title: button.dataset.title || '',
                description: button.dataset.description || '',
                responsiblePerson: button.dataset.responsiblePerson || '',
                timelineFrom: button.dataset.timelineFrom || '',
                timelineTo: button.dataset.timelineTo || '',
            });
            return;
        }

        if (action === 'detail') {
            event.preventDefault();
            openDetail(planId, itemLabel);
            return;
        }

        if (action === 'back-list' && currentDetailId) {
            event.preventDefault();
            openList(currentDetailId, currentItemLabel);
        }
    });

    const deeplinkPlanId = document.getElementById('action-plan-deeplink')?.dataset.planId;
    if (deeplinkPlanId && document.getElementById(`ap-detail-${deeplinkPlanId}`)) {
        openDetail(deeplinkPlanId, '');
    }

    modalEl.addEventListener('hidden.bs.modal', () => {
        listContainer.innerHTML = '';
        detailContainer.innerHTML = '';
        resetForm();
        currentDetailId = null;
        currentItemLabel = '';
        showView('list');
    });
}

function initUserFormToggles() {
    const form = document.getElementById('user-form');
    if (!form) {
        return;
    }

    const roleSelect = document.getElementById('role_id');
    const superAdminCheckbox = document.getElementById('is_super_admin');
    const supplierSection = document.getElementById('supplier-section');
    const sitesSection = document.getElementById('sites-section');
    const supplierRequiredMark = form.querySelector('.supplier-required-mark');
    const sitesRequiredMark = form.querySelector('.sites-required-mark');

    if (!roleSelect || !superAdminCheckbox) {
        return;
    }

    const supplierRoleId = roleSelect.dataset.supplierRoleId;

    const toggle = () => {
        const isSuperAdmin = superAdminCheckbox.checked;
        const isSupplierRole = roleSelect.value === supplierRoleId;
        const showSites = !isSuperAdmin;
        const showSuppliers = !isSuperAdmin && isSupplierRole;

        supplierSection?.classList.toggle('d-none', !showSuppliers);
        sitesSection?.classList.toggle('d-none', !showSites);
        supplierRequiredMark?.classList.toggle('d-none', !showSuppliers);
        sitesRequiredMark?.classList.toggle('d-none', !showSites);

        supplierSection?.querySelectorAll('.supplier-checkbox').forEach((checkbox) => {
            checkbox.disabled = !showSuppliers;
            if (!showSuppliers) {
                checkbox.checked = false;
            }
        });

        sitesSection?.querySelectorAll('.site-checkbox').forEach((checkbox) => {
            checkbox.disabled = !showSites;
            if (!showSites) {
                checkbox.checked = false;
            }
        });

        if (showSites) {
            document.dispatchEvent(new CustomEvent('assigned-sites:refresh'));
        }
    };

    roleSelect.addEventListener('change', toggle);
    superAdminCheckbox.addEventListener('change', toggle);
    toggle();
}

function initAssignedSitesPicker() {
    const sitesSection = document.getElementById('sites-section');
    if (!sitesSection) {
        return;
    }

    const districtFilter = document.getElementById('site-district-filter');
    const summary = document.getElementById('site-selection-summary');
    const districtGroups = sitesSection.querySelectorAll('.district-site-group');

    const allSiteCheckboxes = () => Array.from(sitesSection.querySelectorAll('.site-checkbox'));

    const visibleDistrictGroups = () => {
        const selectedDistrictId = districtFilter?.value ?? '';

        return Array.from(districtGroups).filter((group) => {
            if (selectedDistrictId === '') {
                return true;
            }

            return group.dataset.districtId === selectedDistrictId;
        });
    };

    const visibleSiteCheckboxes = () => visibleDistrictGroups()
        .flatMap((group) => Array.from(group.querySelectorAll('.site-checkbox')));

    const districtSiteCheckboxes = (districtId) => Array.from(
        sitesSection.querySelectorAll(`.site-checkbox[data-district-id="${districtId}"]`),
    );

    const updateSummary = () => {
        if (!summary) {
            return;
        }

        const selectedCount = allSiteCheckboxes().filter((checkbox) => checkbox.checked).length;
        const districtCount = new Set(
            allSiteCheckboxes()
                .filter((checkbox) => checkbox.checked)
                .map((checkbox) => checkbox.dataset.districtId),
        ).size;

        summary.textContent = `${selectedCount} site${selectedCount === 1 ? '' : 's'} selected across ${districtCount} district${districtCount === 1 ? '' : 's'}`;
    };

    const applyDistrictFilter = () => {
        const selectedDistrictId = districtFilter?.value ?? '';

        districtGroups.forEach((group) => {
            const isVisible = selectedDistrictId === '' || group.dataset.districtId === selectedDistrictId;
            group.classList.toggle('d-none', !isVisible);
        });
    };

    districtFilter?.addEventListener('change', applyDistrictFilter);

    sitesSection.addEventListener('click', (event) => {
        const button = event.target.closest('[data-site-bulk]');
        if (!button) {
            return;
        }

        event.preventDefault();

        const action = button.dataset.siteBulk;

        if (action === 'select-visible') {
            visibleSiteCheckboxes().forEach((checkbox) => {
                checkbox.checked = true;
            });
        }

        if (action === 'unselect-visible') {
            visibleSiteCheckboxes().forEach((checkbox) => {
                checkbox.checked = false;
            });
        }

        if (action === 'select-district') {
            districtSiteCheckboxes(button.dataset.districtId).forEach((checkbox) => {
                checkbox.checked = true;
            });
        }

        if (action === 'unselect-district') {
            districtSiteCheckboxes(button.dataset.districtId).forEach((checkbox) => {
                checkbox.checked = false;
            });
        }

        updateSummary();
    });

    sitesSection.addEventListener('change', (event) => {
        if (event.target.classList.contains('site-checkbox')) {
            updateSummary();
        }
    });

    document.addEventListener('assigned-sites:refresh', () => {
        applyDistrictFilter();
        updateSummary();
    });

    applyDistrictFilter();
    updateSummary();
}

const sidebarStorageKey = 'mhe-sidebar-collapsed';
const desktopSidebarQuery = window.matchMedia('(min-width: 992px)');

function isDesktopSidebar() {
    return desktopSidebarQuery.matches;
}

function setSidebarToggleUi(toggleButton, sidebar, isOpen) {
    const openIcon = toggleButton.querySelector('.sidebar-toggle__icon--open');
    const closedIcon = toggleButton.querySelector('.sidebar-toggle__icon--closed');

    openIcon?.classList.toggle('d-none', !isOpen);
    closedIcon?.classList.toggle('d-none', isOpen);
    toggleButton.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    toggleButton.setAttribute('aria-label', isOpen ? 'Hide admin panel' : 'Show admin panel');
    toggleButton.setAttribute('title', isOpen ? 'Hide admin panel' : 'Show admin panel');

    if (sidebar) {
        sidebar.classList.toggle('show', isOpen);
    }
}

function initSidebarToggle() {
    const toggleButton = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('sidebarMenu');

    if (!toggleButton || !sidebar) {
        return;
    }

    const syncDesktopState = () => {
        const isCollapsed = document.documentElement.classList.contains('sidebar-collapsed');
        setSidebarToggleUi(toggleButton, sidebar, !isCollapsed);
    };

    const syncMobileState = () => {
        setSidebarToggleUi(toggleButton, sidebar, sidebar.classList.contains('show'));
    };

    const syncSidebarToggle = () => {
        if (isDesktopSidebar()) {
            syncDesktopState();
            return;
        }

        syncMobileState();
    };

    toggleButton.addEventListener('click', () => {
        if (isDesktopSidebar()) {
            const willCollapse = !document.documentElement.classList.contains('sidebar-collapsed');
            document.documentElement.classList.toggle('sidebar-collapsed', willCollapse);

            try {
                localStorage.setItem(sidebarStorageKey, willCollapse ? '1' : '0');
            } catch (error) {
                // Ignore storage errors in restricted environments.
            }

            syncDesktopState();
            return;
        }

        Collapse.getOrCreateInstance(sidebar).toggle();
    });

    sidebar.addEventListener('shown.bs.collapse', () => {
        if (!isDesktopSidebar()) {
            document.documentElement.classList.add('sidebar-mobile-open');
        }

        syncMobileState();
    });

    sidebar.addEventListener('hidden.bs.collapse', () => {
        if (!isDesktopSidebar()) {
            document.documentElement.classList.remove('sidebar-mobile-open');
        }

        syncMobileState();
    });

    document.addEventListener('click', (event) => {
        if (isDesktopSidebar() || !document.documentElement.classList.contains('sidebar-mobile-open')) {
            return;
        }

        if (event.target.closest('#sidebarMenu, #sidebarToggle')) {
            return;
        }

        Collapse.getOrCreateInstance(sidebar).hide();
    });

    desktopSidebarQuery.addEventListener('change', () => {
        if (isDesktopSidebar()) {
            sidebar.classList.remove('show');
            syncDesktopState();
            return;
        }

        document.documentElement.classList.remove('sidebar-collapsed');
        syncMobileState();
    });

    syncSidebarToggle();
}

const menuButton = document.querySelector('[data-menu-button]');
const mobileMenu = document.querySelector('[data-mobile-menu]');

menuButton?.addEventListener('click', () => {
    const isOpen = menuButton.getAttribute('aria-expanded') === 'true';

    menuButton.setAttribute('aria-expanded', String(! isOpen));
    mobileMenu?.classList.toggle('hidden', isOpen);
});

document.querySelectorAll('[data-city-select]').forEach((citySelect) => {
    const form = citySelect.closest('form');
    const governorateSelect = form?.querySelector('[data-governorate-select]');

    if (! governorateSelect) {
        return;
    }

    const cityOptions = [...citySelect.options].map((option) => option.cloneNode(true));

    function refreshCities() {
        const selectedGovernorate = governorateSelect.value;
        const selectedCity = citySelect.dataset.selected || citySelect.value;
        const placeholder = cityOptions[0].cloneNode(true);
        const matchingOptions = cityOptions
            .slice(1)
            .filter((option) => option.dataset.governorate === selectedGovernorate)
            .map((option) => option.cloneNode(true));

        citySelect.replaceChildren(placeholder, ...matchingOptions);
        citySelect.disabled = selectedGovernorate === '';

        if ([...citySelect.options].some((option) => option.value === selectedCity)) {
            citySelect.value = selectedCity;
        }

        citySelect.dataset.selected = citySelect.value;
    }

    governorateSelect.addEventListener('change', () => {
        citySelect.dataset.selected = '';
        refreshCities();
    });

    refreshCities();
});

document.querySelectorAll('[data-schedule-exception-form]').forEach((form) => {
    const closedSelect = form.elements.is_closed;
    const timeInputs = [form.elements.opens_at, form.elements.closes_at];
    const refreshTimes = () => {
        timeInputs.forEach((input) => {
            input.disabled = closedSelect.value === '1';
            input.required = closedSelect.value === '0';
        });
    };

    closedSelect.addEventListener('change', refreshTimes);
    refreshTimes();

    form.closest('section').querySelectorAll('[data-edit-schedule-exception]').forEach((button) => {
        button.addEventListener('click', () => {
            form.elements.date.value = button.dataset.date;
            closedSelect.value = button.dataset.closed;
            form.elements.opens_at.value = button.dataset.opens;
            form.elements.closes_at.value = button.dataset.closes;
            refreshTimes();
            form.scrollIntoView({ behavior: 'smooth', block: 'center' });
            form.elements.date.focus({ preventScroll: true });
        });
    });
});

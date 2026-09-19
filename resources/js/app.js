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

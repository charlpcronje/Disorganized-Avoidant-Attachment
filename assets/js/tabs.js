document.addEventListener('DOMContentLoaded', function () {
    const exampleContainers = document.querySelectorAll('.example-container');

    exampleContainers.forEach(container => {
        const researchTab = container.querySelector('.tab-content[data-tab="research"]');
        const personalTab = container.querySelector('.tab-content[data-tab="personal"]');
        const researchBtn = container.querySelector('.tab-btn[data-tab="research"]');
        const personalBtn = container.querySelector('.tab-btn[data-tab="personal"]');

        if (researchTab && personalTab && researchBtn && personalBtn) {
            // Determine initial state based on which button has the 'active' class
            if (personalBtn.classList.contains('active')) {
                personalTab.classList.add('active');
                researchTab.classList.remove('active');
            } else {
                // Default to research tab if neither or research is active
                researchTab.classList.add('active');
                personalTab.classList.remove('active');
                researchBtn.classList.add('active');
                personalBtn.classList.remove('active');
            }

            // Key difference: using container scoping to only affect this tab group
            researchBtn.addEventListener('click', function () {
                // Only affect tabs within this container
                researchTab.classList.add('active');
                personalTab.classList.remove('active');
                researchBtn.classList.add('active');
                personalBtn.classList.remove('active');
            });

            personalBtn.addEventListener('click', function () {
                // Only affect tabs within this container
                researchTab.classList.remove('active');
                personalTab.classList.add('active');
                personalBtn.classList.add('active');
                researchBtn.classList.remove('active');
            });
        }
    });
});
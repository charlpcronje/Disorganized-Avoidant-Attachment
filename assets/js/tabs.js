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
                personalTab.style.display = 'block';
                researchTab.style.display = 'none';
            } else {
                // Default to research tab if neither or research is active
                researchTab.style.display = 'block';
                personalTab.style.display = 'none';
                researchBtn.classList.add('active');
                personalBtn.classList.remove('active');
            }

            // Key difference: using container scoping to only affect this tab group
            researchBtn.addEventListener('click', function () {
                researchTab.style.display = 'block';
                personalTab.style.display = 'none';
                researchBtn.classList.add('active');
                personalBtn.classList.remove('active');
            });

            personalBtn.addEventListener('click', function () {
                researchTab.style.display = 'none';
                personalTab.style.display = 'block';
                personalBtn.classList.add('active');
                researchBtn.classList.remove('active');
            });
        }
    });
});
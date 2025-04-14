document.addEventListener('DOMContentLoaded', function () {
    console.log('Tabs.js: Initializing tab functionality');
    const exampleContainers = document.querySelectorAll('.example-container');
    console.log('Tabs.js: Found', exampleContainers.length, 'example containers');

    exampleContainers.forEach((container, index) => {
        console.log('Tabs.js: Setting up container #', index, 'with ID:', container.id);
        const researchTab = container.querySelector('.tab-content[data-tab="research"]');
        const personalTab = container.querySelector('.tab-content[data-tab="personal"]');
        const researchBtn = container.querySelector('.tab-btn[data-tab="research"]');
        const personalBtn = container.querySelector('.tab-btn[data-tab="personal"]');

        if (researchTab && personalTab && researchBtn && personalBtn) {
            console.log('Tabs.js: All tab elements found for container:', container.id);

            // Determine initial state based on which button has the 'active' class
            if (personalBtn.classList.contains('active')) {
                console.log('Tabs.js: Personal tab is initially active for container:', container.id);
                personalTab.classList.add('active');
                researchTab.classList.remove('active');
            } else {
                console.log('Tabs.js: Research tab is initially active for container:', container.id);
                // Default to research tab if neither or research is active
                researchTab.classList.add('active');
                personalTab.classList.remove('active');
                researchBtn.classList.add('active');
                personalBtn.classList.remove('active');
            }

            // Key difference: using container scoping to only affect this tab group
            researchBtn.addEventListener('click', function (event) {
                console.log('Tabs.js: Research button clicked for container:', container.id);
                console.log('Tabs.js: Current container:', container);

                // Only affect tabs within this container
                researchTab.classList.add('active');
                personalTab.classList.remove('active');
                researchBtn.classList.add('active');
                personalBtn.classList.remove('active');

                // Prevent default behavior and stop propagation
                event.preventDefault();
                event.stopPropagation();
            });

            personalBtn.addEventListener('click', function (event) {
                console.log('Tabs.js: Personal button clicked for container:', container.id);
                console.log('Tabs.js: Current container:', container);

                // Only affect tabs within this container
                researchTab.classList.remove('active');
                personalTab.classList.add('active');
                personalBtn.classList.add('active');
                researchBtn.classList.remove('active');

                // Prevent default behavior and stop propagation
                event.preventDefault();
                event.stopPropagation();
            });
        } else {
            console.warn('Tabs.js: Not all tab elements found for container:', container.id);
        }
    });

    console.log('Tabs.js: Tab initialization complete');
});
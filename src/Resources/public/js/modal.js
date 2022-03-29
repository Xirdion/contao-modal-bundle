const modal = function () {
    const activeModals = [];
    // Add key down listener to close current active modal with ESC
    document.addEventListener('keydown', (event) => {
        // Check the pressed key
        if ('Escape' !== event.key) {
            return;
        }

        // Check if there are any active modals
        if (0 === activeModals.length) {
            return;
        }

        // Try to hide the last added modal
        const modal = document.getElementById(activeModals[activeModals.length - 1]);
        hideModal(modal);
    });

    // Add click events to possible modal close buttons and modal open buttons
    document.addEventListener('click', (event) => {
        const element = event.target;
        if (element) {
            // Close-button was clicked
            if (element.classList.contains('js-modal-close')) {
                // Hide the modal
                hideModal(document.getElementById(element.dataset.modal));

                return;
            }

            // Open-button was clicked
            if (element.classList.contains('js-modal-open')) {
                // Show the modal
                showModal(document.getElementById(element.dataset.modal));
            }
        }
    });

    // Automatically open the modals, if these have a start-time
    const timeModals = document.querySelectorAll('.js-modal-time');
    timeModals.forEach((m) => {
        startModal(m, parseInt(m.dataset.startTime));
    });

    // Show the modal after a specific amount of time
    function startModal(modal, startTime) {
        setTimeout(() => {
            showModal(modal, true);
        }, startTime);
    }

    // Create an intersection observer to show modals with scroll option
    const modalObserver = new IntersectionObserver(entries => {
        entries.forEach(modalElement => {
            if (modalElement.isIntersecting) {
                const modal = modalElement.target.querySelector('.js-modal')
                showModal(modal, true);
                modalObserver.unobserve(modal);
            }
        });
    });

    // Add all modals with scroll option to the observer
    const scrollModals = document.querySelectorAll('.js-modal-scroll');
    scrollModals.forEach((m) => {
        modalObserver.observe(m.closest('.modal-element'));
    });

    function showModal(modal, checkSession = false) {
        const storageTime = parseInt(modal.dataset.stopTime);
        const currentTime = new Date().getTime();
        let showModal = !(checkSession && 0 !== storageTime);

        if (false === showModal) {
            const modalName = 'Modal' + modal.id;
            const maxStorageTime = new Date();
            maxStorageTime.setDate(maxStorageTime.getDate() + storageTime);

            // use localStorage when storage time is defined, otherwise sessionStorage
            const storage = window.localStorage;
            const value = maxStorageTime.getTime();
            const item = storage.getItem(modalName);

            // Check if modal should really be shown
            if (!item || parseInt(item) < currentTime) {
                // storage value is the max timestamp until the modal is visible again
                storage.setItem(modalName, value.toString());
                showModal = true;
            }
        }

        if (showModal && !modal.hasAttribute('open')) {
            activeModals.push(modal.id);
            modal.classList.add('visible');
            modal.classList.remove('hide');
            modal.showModal();
        }
    }

    function hideModal(modal) {
        const index = activeModals.indexOf(modal.id);
        if (index > -1) {
            // remove the modal from the active list
            activeModals.splice(index, 1);
        }
        modal.classList.add('hide');

        setTimeout(function () {
            modal.classList.remove('visible');
            modal.close();
        }, 300);
    }
};
modal();

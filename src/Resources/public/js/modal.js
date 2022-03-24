const modal = function () {
    const activeModals = [];
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
    const modals = document.getElementsByClassName('js-modal');
    for (let i = 0; i < modals.length; i++) {
        const startTime = parseInt(modals[i].dataset.startTime);
        if (startTime) {
            startModal(modals[i], startTime);
        }
    }

    // Show the modal after a specific amount of time
    function startModal(modal, startTime) {
        setTimeout(function () {
            showModal(modal, true);
        }, startTime);
    }

    function showModal(modal, checkSession) {
        const storageTime = modal.dataset.stopTime;
        const currentTime = new Date().getTime();
        let showModal = !checkSession;

        if (checkSession) {
            const modalName = 'Modal' + modal.id;
            const maxStorageTime = new Date();
            maxStorageTime.setDate(maxStorageTime.getDate() + parseInt(storageTime));

            // use localStorage when storage time is defined, otherwise sessionStorage
            const storage = storageTime ? window.localStorage : window.sessionStorage;
            const value = storageTime ? maxStorageTime.getTime() : currentTime;
            const item = storage.getItem(modalName);

            // Check if modal should really be shown
            if (!item || parseInt(item) < currentTime) {
                // storage value is the max timestamp until the modal is visible again
                storage.setItem(modalName, value.toString());
                showModal = true;
            }
        }

        if (showModal) {
            activeModals.push(modal.id);
            modal.classList.add('visible');
            modal.classList.remove('hide');
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
        }, 300);
    }
};
modal();

document.addEventListener('DOMContentLoaded', function () {
    const contentModalTrigger = document.querySelectorAll('[data-behaviour="content-modal-trigger"]');

    contentModalTrigger.forEach((trigger) => {
        trigger.addEventListener('click', (e) => {
            e.preventDefault();
            e.target.nextSibling.classList.toggle('content-modal--active');
        });
    });

    const contentModalClose = document.querySelectorAll('[data-behaviour="close-content-modal"]');

    contentModalClose.forEach((trigger) => {
        trigger.addEventListener('click', (e) => {
            e.preventDefault();
            e.target.parentNode.classList.toggle('content-modal--active');
        });
    });
});

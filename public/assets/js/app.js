// Small helpers used across the site.
document.addEventListener('click', function (e) {
    // Close buttons on flash messages
    const close = e.target.closest('.flash-close');
    if (close) {
        const box = close.closest('.flash');
        if (box) box.remove();
    }
});

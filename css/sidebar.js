document.addEventListener('DOMContentLoaded', () => {
console.log('DOM listo');

    const toggleBtn = document.getElementById('toggleSidebar');
    const sidebar   = document.getElementById('sidebar');

    if (!toggleBtn || !sidebar) return;
console.log('toggleBtn:', toggleBtn);
    console.log('sidebar:', sidebar);

    toggleBtn.addEventListener('click', () => {
        sidebar.classList.toggle('hidden');
        document.body.classList.toggle('sidebar-hidden');
    });

});

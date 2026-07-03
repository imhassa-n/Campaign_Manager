    <!-- Footer -->
    <footer class="site-footer">
        <p>
            <span class="footer-brand">WebDex</span> Campaign Manager Pro &copy; <?php echo date('Y'); ?>
            &middot; Your Digital Partner
        </p>
    </footer>

</div><!-- /.main-content -->

<!-- Dark Mode Toggle -->
<button class="dark-mode-toggle" id="darkModeBtn" title="Toggle Dark Mode">
    <i class="bi bi-moon-fill" id="darkModeIcon"></i>
</button>

<!-- Bootstrap JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- WebDex Scripts -->
<script>
// Dark Mode Toggle
const darkBtn = document.getElementById('darkModeBtn');
const darkIcon = document.getElementById('darkModeIcon');

// Load saved preference
if (localStorage.getItem('webdex_dark') === 'true') {
    document.body.classList.add('dark-mode');
    darkIcon.className = 'bi bi-sun-fill';
}

darkBtn.addEventListener('click', function() {
    document.body.classList.toggle('dark-mode');
    const isDark = document.body.classList.contains('dark-mode');
    darkIcon.className = isDark ? 'bi bi-sun-fill' : 'bi bi-moon-fill';
    localStorage.setItem('webdex_dark', isDark);
});

// Mobile Sidebar Toggle
function openSidebar() {
    document.getElementById('sidebar').classList.add('active');
    document.getElementById('sidebarOverlay').classList.add('active');
}

function closeSidebar() {
    document.getElementById('sidebar').classList.remove('active');
    document.getElementById('sidebarOverlay').classList.remove('active');
}
</script>

</body>
</html>

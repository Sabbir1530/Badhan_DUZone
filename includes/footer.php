
<footer class="footer">
    &copy; <?= date('Y') ?> Badhan DU Zone &mdash; Blood Requisition Management System v<?= APP_VERSION ?>
</footer>

<script>
// Minimal JS for mobile menu toggle
document.addEventListener('DOMContentLoaded', function() {
    const toggle = document.querySelector('.menu-toggle');
    if (toggle) {
        toggle.addEventListener('click', function() {
            document.querySelector('.nav-links').classList.toggle('open');
        });
    }
});
</script>
</body>
</html>

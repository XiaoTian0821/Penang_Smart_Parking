        </div>
    </main>

    <footer class="bg-light py-3 mt-auto">
        <div class="container text-center text-muted">
            <small>&copy; <?php echo date('Y'); ?> <?php echo APP_NAME; ?>. All rights reserved.</small>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="<?php echo APP_URL; ?>/assets/js/main.js?v=<?php echo filemtime(BASE_PATH . '/assets/js/main.js'); ?>"></script>
</body>
</html>

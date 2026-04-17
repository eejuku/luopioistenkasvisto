<footer class="site-footer">
    <div class="kasvi-container footer-inner">
        <div class="footer-info">
            <p>&copy; <?php echo date('Y'); ?> Luopioisten kasvisto</p>
        </div>
        <div class="footer-links">
            </div>
    </div>

</footer>

<?php wp_footer(); ?>

<script src="https://cdn.jsdelivr.net/npm/glightbox/dist/js/glightbox.min.js"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/glightbox/dist/css/glightbox.min.css" />

<script>
(function() {
    document.addEventListener('click', function(e) {
        const link = e.target.closest('.karttalinkki');
        
        if (link) {
            e.preventDefault();
            let mapURL = link.getAttribute('href');

            if (typeof GLightbox !== 'undefined') {
                const singleLightbox = GLightbox({
                    // Automaattinen asetusten haku iframe-tyypille
                    elements: [{
                        href: mapURL,
                        type: 'iframe', // Pakotetaan iframe
                        source: 'local', // Joissain versioissa auttaa ohittamaan tunnistusongelmat
                        width: '90vw',
                        height: '85vh'
                    }]
                });
                singleLightbox.open();
            } else {
                window.open(mapURL, '_blank');
            }
        }
    }, false);
})();
</script>
</body>
</html>
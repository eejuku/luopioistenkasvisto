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

<style>
    /* Varmistetaan näkyvyys */
    .glightbox-container { z-index: 999999 !important; }
    
    /* Pakotetaan iframe näkymään heti */
    .gslide-inline iframe {
        width: 100% !important;
        height: 85vh !important;
        border: none;
    }

    /* Tyylitellään Lightbox-ikkunaa hieman siistimmäksi */
    .gslide-inline {
        background: #fff !important;
        padding: 10px;
        border-radius: 4px;
        max-width: 95vw !important;
    }
</style>

<script>
(function() {
    document.addEventListener('click', function(e) {
        const link = e.target.closest('.karttalinkki');
        
        if (link) {
            e.preventDefault();
            const mapURL = link.getAttribute('href');

            if (typeof GLightbox !== 'undefined') {
                // Luodaan "inline"-tyyppinen sisältö, eli syötetään suoraan iframe-koodi
                // Tämä ohittaa GLightboxin oman iframe-lataustarkistuksen
                const instance = GLightbox({
                    elements: [{
                        'content': `<iframe src="${mapURL}" allowfullscreen></iframe>`,
                        'width': '95vw',
                        'height': '85vh'
                    }]
                });

                instance.open();
            } else {
                window.open(mapURL, '_blank');
            }
        }
    }, false);
})();
</script>
</body>
</html>
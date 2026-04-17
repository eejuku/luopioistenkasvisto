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
    /* Pakotetaan GLightbox kaiken yläpuolelle */
    .glightbox-container { z-index: 999999 !important; }

    /* Poistetaan harmaa verho iframen päältä ja spinneri */
    /* Nämä kaksi riviä ratkaisevat "verho-ongelman" */
    .gloader { display: none !important; }
    .goverlay { background: rgba(0,0,0,0.85) !important; } /* Tausta saa olla tumma, mutta ei iframen päällä */
    
    /* Varmistetaan että sisältö on klikattavissa */
    .gslide-iframe .gslide-content {
        background: white !important;
        opacity: 1 !important;
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
                const instance = GLightbox({
                    elements: [{
                        href: mapURL,
                        type: 'iframe',
                        width: '90vw',
                        height: '85vh'
                    }],
                    touchNavigation: true,
                    keyboardNavigation: true
                });

                // Kun lightbox aukeaa, poistetaan latausindikaattorit väkisin
                instance.on('open', () => {
                    setTimeout(() => {
                        const loader = document.querySelector('.gloader');
                        if (loader) loader.style.display = 'none';
                    }, 500); // Puolen sekunnin varmistusviive
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
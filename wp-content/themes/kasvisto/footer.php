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
    /* Pakotetaan GLightbox näkyviin ja annetaan sille korkea z-index */
    .glightbox-container {
        z-index: 999999 !important;
    }
    /* Varmistetaan että iframe täyttää sille varatun tilan */
    .giframe {
        width: 100% !important;
        height: 100% !important;
    }
</style>

<script>
(function() {
    // Luodaan yksi pysyvä Lightbox-instanssi
    let mapLightbox;

    function initMapLightbox() {
        if (typeof GLightbox !== 'undefined' && !mapLightbox) {
            mapLightbox = GLightbox({
                selector: '.karttalinkki', // Seuraa suoraan näitä linkkejä
                touchNavigation: true,
                loop: false,
                width: '90vw',
                height: '85vh'
            });
        }
    }

    // Tarkistetaan klikkaukset
    document.addEventListener('click', function(e) {
        const link = e.target.closest('.karttalinkki');
        if (link) {
            e.preventDefault();
            
            const mapURL = link.getAttribute('href');
            
            // Luodaan dynaaminen instanssi joka pakottaa iframen
            const instance = GLightbox({
                elements: [
                    {
                        'href': mapURL,
                        'type': 'video', // TEMP: Käytetään video-tyyppiä iframen sijaan joskus
                        'source': 'local', // Pakottaa iframen herkemmin
                        'width': '90vw',
                        'height': '85vh'
                    }
                ]
            });
            
            // Jos kyseessä on Paikkatietoikkuna tai Wikipedia, pakotetaan iframe-asetukset
            instance.setElements([{
                href: mapURL,
                type: 'iframe',
                width: '90vw',
                height: '85vh'
            }]);
            
            instance.open();
        }
    });

    initMapLightbox();
})();
</script>
</body>
</html>
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

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
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
    .gslide-inline iframe {
        width: 100% !important;
        height: 70vh !important; /* Puhelimella hieman matalampi, jotta sulkupainike mahtuu */
        border: none;
    }

    @media (min-width: 768px) {
        .gslide-inline iframe {
            height: 85vh !important; /* Tietokoneella korkeampi */
        }
    }
    /* Estetään sivun skrollaus kartan alla kun se on auki */
    .glightbox-open {
        overflow: hidden;
    }
    .glightbox-clean .gclose {
        background: rgba(0,0,0,0.5) !important;
        border-radius: 50%;
        width: 40px;
        height: 40px;
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
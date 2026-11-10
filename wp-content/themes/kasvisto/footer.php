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

<style>
    /* Varmistetaan näkyvyys */
    .glightbox-container { z-index: 999999 !important; }
    
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

<?php
/* Karttalinkkien (.karttalinkki) ponnahdusikkuna poistettu käytöstä 11/2026.
   Linkit aukeavat nyt tavallisina linkkeinä (Natura-sivulla uuteen välilehteen).
   Vanha koodi löytyy git-historiasta, jos sitä vielä tarvitaan. */
?>
</body>
</html>
                <?php if ($tieteellinen): ?>
                    <div class="media-group">
                        <div id="lajifi-kartta" style="height: 350px;"></div>
                        <div id="map-status" class="map-status-clean"></div>
                        <button id="nayta-havainnot-btn" class="text-link-btn">Näytä havainnot listana ▼</button>
                        
                        <div id="havainto-taulukko-wrap" style="display: none;">
                            <table class="simple-obs-table">
                                <tbody id="taulukko-body"></tbody>
                            </table>
                        </div>
                    </div>
                <?php endif; ?>



<script>
document.addEventListener('DOMContentLoaded', function() {
    const lightbox = GLightbox({ selector: '.glightbox' });
    const karttaElement = document.getElementById('lajifi-kartta');
    if (!karttaElement) return;
    });



const kartta = L.map('lajifi-kartta').setView([61.36, 24.85], 11);
    L.tileLayer('https://avoin-karttakuva.maanmittauslaitos.fi/avoin/wmts/1.0.0/maastokartta/default/WGS84_Pseudo-Mercator/{z}/{y}/{x}.png?api-key=API-AVAIMESI', {
        attribution: '&copy; MML'
    }).addTo(kartta);

    <?php 
    $pisteet = [];
    if (function_exists('hae_laji_fi_havainnot') && $tieteellinen) {
        $havainnot = hae_laji_fi_havainnot($tieteellinen);
        if (isset($havainnot['results'])) {
            foreach ($havainnot['results'] as $unit) {
                $coords = $unit['gathering']['conversions']['wgs84CenterPoint'] ?? null;
                if ($coords) $pisteet[] = ['lat' => $coords['lat'], 'lon' => $coords['lon'], 'paikka' => $unit['gathering']['locality'] ?? ''];
            }
        }
    }
    ?>
    const pisteet = <?php echo json_encode($pisteet); ?>;
    if (pisteet.length > 0) {
        const markerGroup = L.featureGroup();
        pisteet.forEach(p => {
            L.circleMarker([p.lat, p.lon], { radius: 6, color: '#e74c3c' }).addTo(markerGroup);
            document.getElementById('taulukko-body').innerHTML += `<tr><td>${p.paikka}</td></tr>`;
        });
        markerGroup.addTo(kartta);
        kartta.fitBounds(markerGroup.getBounds());
    }
    
    document.getElementById('nayta-havainnot-btn').addEventListener('click', function() {
        const wrap = document.getElementById('havainto-taulukko-wrap');
        wrap.style.display = wrap.style.display === 'none' ? 'block' : 'none';
    });
});
</script>
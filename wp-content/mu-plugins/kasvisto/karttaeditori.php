<?php
/**
 * wp-adminin Kasvikartat-työkalu: löytöpisteiden piirto kartalle.
 *
 * Siirretty Code Snippets -snippetistä #16 (vienti 10.11.2026).
 * Rekisteröinti: ../kasvisto-loader.php
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * KASVIKARTTA-EDITORIN TÄYDELLINEN KOODI v5.2
 * - Tieteelliset nimet listauksessa
 * - Pisteiden raahaus (Drag & Drop)
 * - Automaattinen värikorjaus (musta -> black)
 * - Tyhjän kentän nollaus (jos ei pisteitä)
 */

function kasvisto_karttaeditori_menu() {
    add_menu_page('Kasvikartat', 'Kasvikartat', 'manage_options', 'kasvikartat-editori', 'kasvisto_karttaeditori_render', 'dashicons-location-alt', 30);
}

function kasvisto_karttaeditori_tallenna() {
    $post_id = intval($_POST['post_id']);
    $json_data = $_POST['json_data']; // Tämä on nyt joko JSON-merkkijono tai tyhjä ""
    
    if ($post_id && current_user_can('edit_post', $post_id)) {
        // Jos data on tyhjä, poistetaan meta-tieto tai tallennetaan tyhjänä
        update_post_meta($post_id, 'karttapisteet_json', $json_data);
        wp_send_json_success();
    }
    wp_send_json_error();
}

function kasvisto_karttaeditori_render() {
    $args = array('post_type' => array('kasvi', 'post'), 'posts_per_page' => -1, 'orderby' => 'title', 'order' => 'ASC');
    $query = new WP_Query($args);
    $kasvi_lista = [];

    while ($query->have_posts()) {
        $query->the_post();
        $pid = get_the_ID();
        $tieteellinen = get_field('tieteellinen_nimi', $pid);
        $display_name = !empty($tieteellinen) ? $tieteellinen : get_the_title();
        $raw_data = get_field('karttapisteet_json', $pid);
        
        $kasvi_lista[] = [
            'id' => $pid,
            'nimi' => $display_name,
            'data' => is_string($raw_data) ? json_decode($raw_data, true) : $raw_data
        ];
    }
    wp_reset_postdata();

    $pohjakartta_url = '/wp-content/themes/kasvisto/images/karttapohja.png'; 
    ?>

    <style>
        #kk-editor { display: flex; height: calc(100vh - 160px); background: #f0f0f0; margin-top: 20px; border: 1px solid #ccc; font-family: sans-serif; }
        #kk-sidebar { width: 320px; border-right: 1px solid #ccc; display: flex; flex-direction: column; background: #fff; }
        #kk-content { flex-grow: 1; display: flex; flex-direction: column; background: #555; overflow: hidden; position: relative; }
        .kk-search-wrap { padding: 15px; border-bottom: 1px solid #ddd; }
        .kk-list { overflow-y: auto; flex-grow: 1; }
        .kk-item { padding: 10px 15px; border-bottom: 1px solid #eee; cursor: pointer; display: flex; justify-content: space-between; align-items: center; font-size: 13px; }
        .kk-item:hover { background: #f9f9f9; }
        .kk-item.active { background: #2271b1; color: #fff; }
        .kk-toolbar { padding: 12px 20px; background: #fff; border-bottom: 1px solid #ccc; display: flex; gap: 15px; align-items: center; font-weight: 500; }
        #canvas-container { flex-grow: 1; overflow: auto; display: flex; justify-content: center; align-items: flex-start; padding: 30px; }
        canvas { background: white; box-shadow: 0 5px 15px rgba(0,0,0,0.3); cursor: crosshair; }
        .save-footer { padding: 15px; background: #fff; border-top: 1px solid #ddd; }
        .btn-save { width: 100%; padding: 10px; background: #2271b1; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; }
        .btn-save:disabled { background: #ccc; cursor: not-allowed; }
    </style>

    <div class="wrap">
        <h1>Kasvikartta-työkalu v5.2</h1>
        <div id="kk-editor">
            <div id="kk-sidebar">
                <div class="kk-search-wrap">
                    <input type="text" id="kk-search" placeholder="Hae kasvia..." style="width:100%; padding:8px;">
                </div>
                <div id="kk-list" class="kk-list"></div>
                <div class="save-footer">
                    <button class="btn-save" id="kk-save" disabled>TALLENNA MUUTOKSET</button>
                    <div id="kk-msg" style="margin-top:8px; font-size:12px; text-align:center;"></div>
                </div>
            </div>
            <div id="kk-content">
                <div class="kk-toolbar">
                    <span>Väri: <select id="kk-color"><option value="black">Musta</option><option value="red">Punainen</option><option value="green">Vihreä</option></select></span>
                    <span>Koko: <select id="kk-size"><option value="15">Pieni</option><option value="30">Iso</option></select></span>
                    <small style="color:#888; font-weight:400;">Shift+Klik = Poista | Vedä pistettä siirtääksesi</small>
                </div>
                <div id="canvas-container"><canvas id="kk-canvas"></canvas></div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
    (function($) {
        let kasvit = <?php echo json_encode($kasvi_lista); ?>;
        const POHJA = { L: 42, T: 7, R: 535, B: 500 };
        const bgImg = new Image();
        bgImg.src = '<?php echo $pohjakartta_url; ?>';

        let currentId = null;
        let points = [];
        let draggedPoint = null;
        const canvas = document.getElementById('kk-canvas');
        const ctx = canvas.getContext('2d');

        bgImg.onload = function() {
            canvas.width = bgImg.width;
            canvas.height = bgImg.height;
            renderList();
        };

        function renderList() {
            const term = $('#kk-search').val().toLowerCase();
            const $list = $('#kk-list').empty();
            kasvit.sort((a,b) => a.nimi.localeCompare(b.nimi)).forEach(k => {
                if (term && k.nimi.toLowerCase().indexOf(term) === -1) return;
                let pts = (k.data && k.data.points) ? k.data.points : (Array.isArray(k.data) ? k.data : []);
                $list.append($(`<div class="kk-item ${k.id === currentId ? 'active' : ''}" data-id="${k.id}">
                    <span>${k.nimi}</span><span style="opacity:0.6;">${pts.length}</span>
                </div>`).on('click', () => selectKasvi(k)));
            });
        }

        function selectKasvi(kasvi) {
            currentId = kasvi.id;
            $('.kk-item').removeClass('active');
            $(`.kk-item[data-id="${kasvi.id}"]`).addClass('active');

            if (kasvi.data && kasvi.data.points) points = kasvi.data.points;
            else if (Array.isArray(kasvi.data)) points = kasvi.data;
            else points = [];

            $('#kk-size').val((kasvi.data && kasvi.data.size) ? kasvi.data.size : "15");
            $('#kk-save').prop('disabled', false);
            $('#kk-msg').text('');
            draw();
        }

        function draw() {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            ctx.drawImage(bgImg, 0, 0);
            if (!currentId) return;

            const size = parseInt($('#kk-size').val());
            points.forEach(p => {
                const dx = POHJA.L + (p.x * (POHJA.R - POHJA.L));
                const dy = POHJA.T + (p.y * (POHJA.B - POHJA.T));
                
                let color = p.c || 'black';
                if (color === 'musta') color = 'black'; // Piirto-korjaus

                ctx.beginPath();
                ctx.arc(dx, dy, size / 2, 0, Math.PI * 2);
                ctx.fillStyle = color;
                ctx.fill();
                ctx.strokeStyle = "white"; ctx.lineWidth = 2; ctx.stroke();
            });
        }

        function getMousePos(e) {
            const rect = canvas.getBoundingClientRect();
            const scaleX = canvas.width / rect.width;
            const scaleY = canvas.height / rect.height;
            const x = (e.clientX - rect.left) * scaleX;
            const y = (e.clientY - rect.top) * scaleY;
            return {
                xPct: (x - POHJA.L) / (POHJA.R - POHJA.L),
                yPct: (y - POHJA.T) / (POHJA.B - POHJA.T)
            };
        }

        $(canvas).on('mousedown', function(e) {
            if (!currentId) return;
            const pos = getMousePos(e);
            const hitIndex = points.findIndex(p => Math.sqrt((p.x-pos.xPct)**2 + (p.y-pos.yPct)**2) < 0.025);

            if (e.shiftKey && hitIndex !== -1) {
                points.splice(hitIndex, 1);
                draw();
            } else if (hitIndex !== -1) {
                draggedPoint = points[hitIndex];
            } else {
                const newPoint = { x: pos.xPct, y: pos.yPct, c: $('#kk-color').val() };
                points.push(newPoint);
                draggedPoint = newPoint;
                draw();
            }
        });

        $(window).on('mousemove', function(e) {
            if (!draggedPoint) return;
            const pos = getMousePos(e);
            draggedPoint.x = pos.xPct;
            draggedPoint.y = pos.yPct;
            draw();
        });

        $(window).on('mouseup', function() { draggedPoint = null; });
        $('#kk-size, #kk-color').on('change', draw);
        $('#kk-search').on('input', renderList);

        $('#kk-save').on('click', function() {
            const $btn = $(this).text('Tallennetaan...');
            
            let finalData = "";
            
            // Tarkistetaan onko pisteitä. Jos on, luodaan JSON.
            if (points.length > 0) {
                const payload = {
                    size: $('#kk-size').val(),
                    points: points.map(p => {
                        let c = p.c || 'black';
                        if (c === 'musta') c = 'black'; // Korjataan "musta" -> "black"
                        return { 
                            x: parseFloat(p.x.toFixed(5)), 
                            y: parseFloat(p.y.toFixed(5)), 
                            c: c 
                        };
                    })
                };
                finalData = JSON.stringify(payload);
            }

            $.post(ajaxurl, {
                action: 'save_kasvi_data',
                post_id: currentId,
                json_data: finalData // Lähetetään joko JSON tai tyhjä ""
            }, function(res) {
                if (res.success) {
                    const idx = kasvit.findIndex(k => k.id === currentId);
                    kasvit[idx].data = finalData ? JSON.parse(finalData) : null;
                    $('#kk-msg').html('<span style="color:green">Tallennettu!</span>');
                    renderList();
                }
                setTimeout(() => { $btn.text('TALLENNA MUUTOKSET'); }, 1000);
            });
        });
    })(jQuery);
    </script>
    <?php
}

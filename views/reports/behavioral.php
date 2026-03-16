<?php
$pageTitle = 'Behavioral Report';
$extraHead = '<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>';
ob_start();

// build a lookup for counts
$countMap = [];
foreach ($counts as $c) $countMap[$c['event_type']] = (int)$c['cnt'];
?>

<h3>Behavioral Report</h3>
<br>
<?php require __DIR__ . '/_date_filter.php'; ?>

<!-- Summary -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card text-center p-3">
            <h4 data-count="<?= (int)($countMap['click'] ?? 0) ?>"><?= number_format($countMap['click'] ?? 0) ?></h4>
            <small class="text-muted">Clicks</small>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center p-3">
            <h4 data-count="<?= (int)($countMap['mousemove'] ?? 0) ?>"><?= number_format($countMap['mousemove'] ?? 0) ?></h4>
            <small class="text-muted">Mouse Moves</small>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center p-3">
            <h4 data-count="<?= (int)($countMap['submit'] ?? 0) ?>"><?= number_format($countMap['submit'] ?? 0) ?></h4>
            <small class="text-muted">Form Submissions</small>
        </div>
    </div>
</div>

<!-- Click Heatmap -->
<div class="card p-3 mb-4">
    <h5>Click Heatmap</h5>
    <p class="text-muted small mb-2">
        Shows where users clicked on <a href="https://test.alansdomain.xyz" target="_blank">test.alansdomain.xyz</a>.
        To add clicks, open that site in a separate tab and click around, then refresh this page.
        The preview below is read-only — clicking on it does not record data.
    </p>

    <?php if (empty($clicks)): ?>
        <p class="text-muted">No click data available for this date range.</p>
    <?php else: ?>

    <?php
    // Group clicks by page_url, keeping raw x/y plus the viewport they were recorded at
    $clicksByPage = [];
    foreach ($clicks as $c) {
        $url = $c['page_url'];
        if (!isset($clicksByPage[$url])) $clicksByPage[$url] = [];
        $vw = (float)($c['vw'] ?? 0);
        $vh = (float)($c['vh'] ?? 0);
        $clicksByPage[$url][] = [
            'x'  => (float)$c['x'],
            'y'  => (float)$c['y'],
            'vw' => $vw > 0 ? $vw : null,
            'vh' => $vh > 0 ? $vh : null,
        ];
    }
    $pages = array_keys($clicksByPage);

    // Determine the most-used viewport size per page (for iframe sizing).
    // Clicks should be plotted at raw coords on a canvas that matches this size,
    // then the whole thing is scaled down to fit — so the page layout stays correct.
    $baseDims = [];
    foreach ($clicksByPage as $url => $pts) {
        $vwCount = [];
        foreach ($pts as $p) {
            if ($p['vw'] && $p['vh']) {
                $key = $p['vw'] . 'x' . $p['vh'];
                $vwCount[$key] = ($vwCount[$key] ?? 0) + 1;
            }
        }
        if ($vwCount) {
            arsort($vwCount);
            [$bw, $bh] = explode('x', array_key_first($vwCount));
            $baseDims[$url] = ['w' => (int)$bw, 'h' => (int)$bh];
        } else {
            $baseDims[$url] = ['w' => 1920, 'h' => 1080]; // legacy — most common recorded size
        }
    }

    // sessions per page lookup for legend
    $sessionsByPage = [];
    foreach ($clickSessions as $s) $sessionsByPage[$s['page_url']] = (int)$s['sessions'];
    ?>

    <!-- Page cycler -->
    <div class="d-flex align-items-center gap-3 mb-2">
        <button class="btn btn-sm btn-outline-secondary" id="prevPage">&#8592; Prev</button>
        <div class="text-center flex-grow-1">
            <small class="text-muted">Page</small>
            <div id="pageLabel" class="fw-semibold text-truncate" style="max-width:600px; margin:0 auto;"></div>
            <small class="text-muted" id="pageCounter"></small>
        </div>
        <button class="btn btn-sm btn-outline-secondary" id="nextPage">Next &#8594;</button>
    </div>

    <!-- iframe rendered at original viewport size, scaled down with transform — keeps page layout intact -->
    <div id="heatmapWrapper" style="position:relative; width:100%; overflow:hidden; border:1px solid #dee2e6; background:#f8f9fa;">
        <iframe id="heatmapFrame" style="position:absolute; top:0; left:0; border:none; pointer-events:none; transform-origin:top left;" scrolling="no"></iframe>
        <canvas id="heatmapCanvas" style="position:absolute; top:0; left:0; transform-origin:top left;"></canvas>
        <!-- Lazy-load overlay: removed once user explicitly loads the preview -->
        <div id="heatmapOverlay" style="position:absolute; top:0; left:0; width:100%; height:100%; display:flex; align-items:center; justify-content:center; background:rgba(248,249,250,0.97); z-index:10;">
            <div class="text-center">
                <p class="text-muted mb-3">Page preview is not loaded to avoid tracking phantom events and browser lag.</p>
                <button id="loadPreviewBtn" class="btn btn-outline-primary">Load Page Preview</button>
            </div>
        </div>
    </div>

    <!-- Legend -->
    <div class="d-flex align-items-center gap-2 mt-2 flex-wrap" style="font-size:0.82rem;">
        <span class="text-muted">Click density:</span>
        <span class="text-muted">1</span>
        <div style="width:160px; height:13px; border-radius:4px; border:1px solid #ddd;
             background: linear-gradient(to right,
               rgba(255,220,50,0.35),
               rgba(255,140,0,0.65),
               rgba(255,30,30,0.95));"></div>
        <span class="text-muted" id="legendMax">—</span>
    </div>

    <script>
    (function() {
        var pages    = <?= json_encode($pages) ?>;
        var allData  = <?= json_encode($clicksByPage) ?>;
        var baseDims = <?= json_encode($baseDims) ?>;
        var sessions = <?= json_encode($sessionsByPage) ?>;
        var current  = 0;

        var canvas  = document.getElementById('heatmapCanvas');
        var ctx     = canvas.getContext('2d');
        var frame   = document.getElementById('heatmapFrame');
        var wrapper = document.getElementById('heatmapWrapper');
        var label   = document.getElementById('pageLabel');
        var counter = document.getElementById('pageCounter');

        // Render iframe at the original recording viewport size (bw×bh), then
        // scale both iframe and canvas down uniformly to fit the container.
        // This preserves the exact page layout so raw click coords line up correctly.
        function scaleToFit(bw, bh) {
            var scale = wrapper.clientWidth / bw;
            frame.style.width     = bw + 'px';
            frame.style.height    = bh + 'px';
            frame.style.transform = 'scale(' + scale + ')';
            canvas.width          = bw;
            canvas.height         = bh;
            canvas.style.transform = 'scale(' + scale + ')';
            wrapper.style.height   = (bh * scale) + 'px';
        }

        function drawHeatmap(points, bw, bh) {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            if (!points.length) return;

            var R = Math.round(bw * 0.025);
            var density = {};
            points.forEach(function(p) {
                if (!p.x || !p.y) return;
                // If click was recorded at a different viewport size, normalize to base dims
                var nx = p.vw ? (p.x / p.vw) * bw : p.x;
                var ny = p.vh ? (p.y / p.vh) * bh : p.y;
                var key = Math.floor(nx / R) + ',' + Math.floor(ny / R);
                density[key] = (density[key] || 0) + 1;
            });

            var vals = Object.values(density);
            var maxDensity = Math.max(1, Math.max.apply(null, vals));
            document.getElementById('legendMax').textContent = maxDensity + ' clicks';

            Object.keys(density).forEach(function(key) {
                var parts = key.split(',');
                var cx = (parseInt(parts[0]) + 0.5) * R;
                var cy = (parseInt(parts[1]) + 0.5) * R;
                var t  = Math.min(1, density[key] / maxDensity);
                var a  = 0.2 + t * 0.75;

                var grad = ctx.createRadialGradient(cx, cy, 0, cx, cy, R * 1.5);
                grad.addColorStop(0,    'rgba(255, 30,  30,  ' + a + ')');
                grad.addColorStop(0.35, 'rgba(255, 140, 0,   ' + (a * 0.55) + ')');
                grad.addColorStop(1,    'rgba(255, 220, 50,  0)');
                ctx.fillStyle = grad;
                ctx.beginPath();
                ctx.arc(cx, cy, R * 1.5, 0, Math.PI * 2);
                ctx.fill();
            });
        }

        var previewLoaded = false;

        function showPage(index) {
            var url    = pages[index];
            var points = allData[url] || [];
            var dims   = baseDims[url] || {w: 1920, h: 1080};
            var sess   = sessions[url] || 0;
            var cps    = sess > 0 ? (points.length / sess).toFixed(1) : '—';

            label.textContent   = url;
            counter.textContent = (index + 1) + ' of ' + pages.length + ' pages'
                                + '  ·  ' + points.length + ' clicks'
                                + '  ·  ' + sess + ' session' + (sess !== 1 ? 's' : '')
                                + '  ·  ' + cps + ' clicks/session';
            if (previewLoaded) {
                frame.src = /^https?:\/\//i.test(url) ? url : 'about:blank';
            }
            scaleToFit(dims.w, dims.h);
            drawHeatmap(points, dims.w, dims.h);
        }

        document.getElementById('loadPreviewBtn').addEventListener('click', function() {
            previewLoaded = true;
            var overlay = document.getElementById('heatmapOverlay');
            if (overlay) overlay.remove();
            var url = pages[current];
            frame.src = /^https?:\/\//i.test(url) ? url : 'about:blank';
        });

        document.getElementById('prevPage').addEventListener('click', function() {
            current = (current - 1 + pages.length) % pages.length;
            showPage(current);
        });
        document.getElementById('nextPage').addEventListener('click', function() {
            current = (current + 1) % pages.length;
            showPage(current);
        });

        window.addEventListener('resize', function() {
            var url  = pages[current];
            var dims = baseDims[url] || {w: 1920, h: 1080};
            scaleToFit(dims.w, dims.h);
            drawHeatmap(allData[url] || [], dims.w, dims.h);
        });

        showPage(0); // renders label + heatmap dots; iframe stays blank until user clicks Load
    })();
    </script>
    <noscript>
        <p class="text-muted mt-2">JavaScript is required to display the interactive heatmap. Click data is available in the table below.</p>
    </noscript>
    <?php endif; ?>
</div>

<!-- Event type pie -->
<div class="card p-3 mb-4">
    <h5>Interaction Type Breakdown</h5>
    <div style="height:350px"><canvas id="typeChart"></canvas></div>
    <noscript>
        <table class="table table-bordered table-sm mt-2">
            <thead class="table-dark"><tr><th>Event Type</th><th>Count</th></tr></thead>
            <tbody>
                <?php foreach ($counts as $c): ?>
                <tr><td><?= htmlspecialchars($c['event_type']) ?></td><td><?= number_format((int)$c['cnt']) ?></td></tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </noscript>
</div>

<!-- Clicks by page -->
<div class="card p-3 mb-4">
    <h5>Clicks by Page</h5>
    <div style="height:400px"><canvas id="clickChart"></canvas></div>
    <noscript>
        <table class="table table-bordered table-sm mt-2">
            <thead class="table-dark"><tr><th>Page URL</th><th>Clicks</th></tr></thead>
            <tbody>
                <?php foreach ($clickPages as $cp): ?>
                <tr><td><?= htmlspecialchars($cp['page_url']) ?></td><td><?= number_format((int)$cp['clicks']) ?></td></tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </noscript>
</div>

<!-- Behavioral events over time -->
<div class="card p-3 mb-4">
    <h5>Interactions Over Time</h5>
    <div style="height:350px"><canvas id="timeChart"></canvas></div>
    <noscript>
        <table class="table table-bordered table-sm mt-2">
            <thead class="table-dark"><tr><th>Date</th><th>Interactions</th></tr></thead>
            <tbody>
                <?php foreach ($byDay as $d): ?>
                <tr><td><?= htmlspecialchars($d['day']) ?></td><td><?= number_format((int)$d['cnt']) ?></td></tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </noscript>
</div>

<!-- Form submissions by page -->
<div class="card p-3 mb-4">
    <h5>Form Submissions by Page</h5>
    <div style="height:350px"><canvas id="formChart"></canvas></div>
    <noscript>
        <table class="table table-bordered table-sm mt-2">
            <thead class="table-dark"><tr><th>Page</th><th>Form Action</th><th>Submissions</th></tr></thead>
            <tbody>
                <?php foreach ($formSubmissions as $f): ?>
                <tr>
                    <td class="truncate"><?= htmlspecialchars($f['page_url']) ?></td>
                    <td class="truncate"><?= htmlspecialchars($f['form_action'] ?? '(unknown)') ?></td>
                    <td><?= number_format((int)$f['cnt']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </noscript>
    <?php if (empty($formSubmissions)): ?>
    <p class="text-muted mt-2 mb-0">No form submissions recorded yet.</p>
    <?php endif; ?>
</div>

<!-- Raw behavioral events -->
<div class="card p-3 mb-4">
    <h5>Raw Behavioral Events (last 50)</h5>
    <div class="table-responsive">
        <table class="table table-bordered table-striped table-sm">
            <thead class="table-dark"><tr><th>ID</th><th>Type</th><th>Page</th><th>Data</th><th>Time</th></tr></thead>
            <tbody>
                <?php foreach ($raw as $r): ?>
                <tr>
                    <td><?= (int)$r['id'] ?></td>
                    <td><?= htmlspecialchars($r['event_type']) ?></td>
                    <td class="truncate"><?= htmlspecialchars($r['page_url']) ?></td>
                    <td class="truncate"><?= htmlspecialchars(substr($r['data'],0,60)) ?></td>
                    <td style="white-space:nowrap"><?= htmlspecialchars($r['created_at']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Save report form -->
<?php if (in_array($_SESSION['role'] ?? '', ['super_admin','analyst'])): ?>
<div class="card p-3 mb-4">
    <h5>Save this Report</h5>
    <form method="POST" action="/reports/save">
        <?= csrfField() ?>
        <input type="hidden" name="category" value="behavioral">
        <div class="mb-2">
            <label class="form-label">Report Title</label>
            <input type="text" name="title" class="form-control" required placeholder="e.g. Behavioral Analysis - March 2026">
        </div>
        <div class="mb-2">
            <label class="form-label">Analyst Comments</label>
            <textarea name="comments" class="form-control" rows="4" placeholder="Write your analysis here..."></textarea>
        </div>
        <div class="form-check mb-2">
            <input class="form-check-input" type="checkbox" name="publish" id="publish" value="1">
            <label class="form-check-label" for="publish">Publish (make visible to viewers)</label>
        </div>
        <button type="submit" class="btn btn-primary">Save Report</button>
    </form>
</div>
<?php endif; ?>

<script>
var typeColorMap = { click: '#ff6384', mousemove: '#36a2eb', submit: '#4bc0c0' };
var typeLabels = <?= json_encode(array_column($counts, 'event_type')) ?>;
new Chart(document.getElementById('typeChart'), {
    type: 'pie',
    data: {
        labels: typeLabels,
        datasets: [{ data: <?= json_encode(array_map('intval', array_column($counts,'cnt'))) ?>, backgroundColor: typeLabels.map(function(t){ return typeColorMap[t] || '#aaa'; }) }]
    },
    options: { responsive: true, maintainAspectRatio: false }
});

var cpLabels = <?= json_encode(array_map(function($p) {
    $u = $p['page_url']; return strlen($u)>40?'...'.substr($u,-37):$u;
}, $clickPages)) ?>;
new Chart(document.getElementById('clickChart'), {
    type: 'bar',
    data: {
        labels: cpLabels,
        datasets: [{ label: 'Clicks', data: <?= json_encode(array_map('intval', array_column($clickPages,'clicks'))) ?>, backgroundColor: '#ff6384', borderRadius: 4 }]
    },
    options: { responsive: true, maintainAspectRatio: false }
});

var fLabels = <?= json_encode(array_map(function($f) {
    $u = $f['page_url']; return strlen($u)>40?'...'.substr($u,-37):$u;
}, $formSubmissions)) ?>;
<?php if (!empty($formSubmissions)): ?>
new Chart(document.getElementById('formChart'), {
    type: 'bar',
    data: {
        labels: fLabels,
        datasets: [{ label: 'Submissions', data: <?= json_encode(array_map('intval', array_column($formSubmissions,'cnt'))) ?>, backgroundColor: '#4bc0c0', borderRadius: 4 }]
    },
    options: { responsive: true, maintainAspectRatio: false }
});
<?php endif; ?>

new Chart(document.getElementById('timeChart'), {
    type: 'line',
    data: {
        labels: <?= json_encode(array_column($byDay,'day')) ?>,
        datasets: [{ label: 'Interactions', data: <?= json_encode(array_map('intval', array_column($byDay,'cnt'))) ?>, borderColor:'#ff6384', fill:false }]
    },
    options: { responsive: true, maintainAspectRatio: false }
});
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';
?>

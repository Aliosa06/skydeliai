<?php
require_once 'database/database.php';

$project_id = $_GET['project_id'] ?? null;
if (!$project_id) {
    die("No project selected.");
}

$db = new Database();
$conn = $db->connect();

// Load project
$stmt = $conn->prepare("SELECT name, total_price, `rows`, `cols`, panel_layout FROM projects WHERE id = ?");
$stmt->bind_param("i", $project_id);
$stmt->execute();
$project = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$project) {
    die("Project not found.");
}

// Load components - FIX: Added missing comma
$result = $conn->query("SELECT id, name, icon_path, type, description, width, height, price FROM components");
$components = $result->fetch_all(MYSQLI_ASSOC);
$result->close();
$db->close();

// Decode layout and build component map
$layout = json_decode($project['panel_layout'], true);
$occupied = [];
$componentMap = [];
foreach ($components as $comp) {
    $componentMap[$comp['id']] = $comp;
}

if (isset($layout['slots'])) {
    foreach ($layout['slots'] as $slot) {
        $r = $slot['row'];
        $c = $slot['col'];
        $compId = $slot['component_id'];
        $width = $componentMap[$compId]['width'] ?? 1;
        $height = $componentMap[$compId]['height'] ?? 1;
        
        // Mark all slots that this component occupies
        for ($dr = 0; $dr < $height; $dr++) {
            for ($dc = 0; $dc < $width; $dc++) {
                $occupied[$r + $dr][$c + $dc] = [
                    'id' => $compId,
                    'width' => $width,
                    'height' => $height,
                    'origin_row' => $r,
                    'origin_col' => $c
                ];
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Project: <?php echo htmlspecialchars($project['name']); ?></title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f5f5f5;
            margin: 0;
            padding: 20px;
        }
        h1 {
            color: #333;
            margin-bottom: 10px;
        }
        .container {
            display: flex;
            gap: 20px;
            margin-top: 20px;
        }
        .sidebar {
            background: white;
            padding: 15px;
            border: 1px solid #ddd;
            width: 220px;
            max-height: 80vh;
            overflow-y: auto;
        }
        .sidebar h2 {
            margin-top: 0;
            color: #333;
            font-size: 16px;
            border-bottom: 1px solid #ddd;
            padding-bottom: 8px;
        }
        .search-box {
            width: 100%;
            padding: 8px;
            margin-bottom: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 14px;
            box-sizing: border-box;
        }
        .search-box:focus {
            outline: none;
            border-color: #007bff;
        }
        .component {
            margin: 8px 0;
            padding: 8px;
            border: 1px solid #ccc;
            background: #fafafa;
            cursor: move;
            position: relative;
        }
        .component.hidden {
            display: none;
        }
        .component img {
            width: 40px;
            height: 40px;
            display: block;
            margin: 0 auto 5px;
        }
        .component-name {
            font-size: 11px;
            text-align: center;
            margin: 2px 0;
        }
        .component-tooltip {
            visibility: hidden;
            position: absolute;
            left: 105%;
            top: 0;
            width: 200px;
            background: #333;
            color: white;
            padding: 8px;
            border-radius: 4px;
            font-size: 12px;
            z-index: 1000;
            box-shadow: 0 2px 8px rgba(0,0,0,0.3);
        }
        .component:hover .component-tooltip {
            visibility: visible;
        }
        .component-tooltip::before {
            content: "";
            position: absolute;
            right: 100%;
            top: 10px;
            border: 6px solid transparent;
            border-right-color: #333;
        }
        .panel-container {
            background: white;
            padding: 15px;
            border: 1px solid #ddd;
            overflow: auto;
        }
        .panel-wrapper {
            display: inline-block;
        }
        .panel-with-labels {
            display: grid;
            gap: 0;
        }
        .corner-label {
            width: 40px;
            height: 40px;
        }
        .col-label {
            width: 120px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 16px;
            background: #e0e0e0;
            border: 1px solid #999;
        }
        .row-label {
            width: 40px;
            height: 180px;  /* Increased from 120px */
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 16px;
            background: #e0e0e0;
            border: 1px solid #999;
        }
        .panel {
            display: grid;
            gap: 0;
            border: 2px solid #000;
            width: fit-content;
        }
        .slot {
            border: 1px solid #ccc;
            width: 120px;
            height: 180px;  /* Increased from 120px */
            display: flex;
            align-items: center;
            justify-content: center;
            background: #fff;
        }
        .slot:hover:not(.occupied) {
            background: #f0f0f0;
        }
        .slot.occupied {
            cursor: pointer;
        }
        .slot.occupied:hover {
            opacity: 0.9;
        }
        /* Color coding by component type */
        .slot.occupied.type-breaker {
            background: #667eea;
        }
        .slot.occupied.type-rcd {
            background: #f093fb;
        }
        .slot.occupied.type-rcbo {
            background: #4facfe;
        }
        .slot.occupied.type-light {
            background: #43e97b;
        }
        .slot.occupied.type-switch {
            background: #fa709a;
        }
        .slot.occupied.type-socket {
            background: #feca57;
        }
        .slot.occupied.type-contactor {
            background: #ff6b6b;
        }
        .slot.occupied.type-timer {
            background: #ee5a6f;
        }
        .slot.occupied.type-meter {
            background: #c44569;
        }
        .slot.occupied.type-relay {
            background: #786fa6;
        }
        .slot.occupied.type-terminal {
            background: #95afc0;
        }
        .slot.occupied.type-other {
            background: #a8a8a8;
        }
        .controls {
            margin: 15px 0;
            display: flex;
            gap: 10px;
            align-items: center;
        }
        button {
            background: #007bff;
            color: white;
            border: none;
            padding: 10px 20px;
            cursor: pointer;
            font-size: 14px;
        }
        button:hover {
            background: #0056b3;
        }
        a {
            color: #007bff;
            text-decoration: none;
        }
        .price-display {
            font-size: 20px;
            font-weight: bold;
            color: #28a745;
        }
    </style>
</head>
<body>
    <h1>Editing: <?php echo htmlspecialchars($project['name']); ?> (<?php echo $project['rows']; ?>x<?php echo $project['cols']; ?>)</h1>
    
    <div class="controls">
        <span>Total: <span class="price-display">€<span id="total-price"><?php echo $project['total_price']; ?></span></span></span>
        <button id="save">Save Project</button>
        <a href="index.php">Back to Projects</a>
    </div>

    <div class="container">
        <div class="sidebar">
            <h2>Components</h2>
            <input type="text" id="search-box" class="search-box" placeholder="Search components...">
            <?php foreach ($components as $comp): ?>
                <div class="component" 
                     data-id="<?php echo $comp['id']; ?>" 
                     data-width="<?php echo $comp['width'] ?? 1; ?>" 
                     data-height="<?php echo $comp['height'] ?? 1; ?>" 
                     data-price="<?php echo $comp['price']; ?>"
                     data-name="<?php echo strtolower(htmlspecialchars($comp['name'])); ?>"
                     data-description="<?php echo strtolower(htmlspecialchars($comp['description'] ?? '')); ?>"
                     data-type="<?php echo strtolower(htmlspecialchars($comp['type'])); ?>">
                    <img src="<?php echo $comp['icon_path']; ?>" alt="<?php echo $comp['name']; ?>">
                    <div class="component-name"><?php echo htmlspecialchars($comp['name']); ?></div>
                    <div class="component-name" style="color: #28a745;">€<?php echo $comp['price']; ?></div>
                    <?php if (!empty($comp['description'])): ?>
                        <div class="component-tooltip">
                            <strong><?php echo htmlspecialchars($comp['name']); ?></strong><br>
                            <?php echo htmlspecialchars($comp['description']); ?><br>
                            <em>Width: <?php echo $comp['width'] ?? 1; ?> module(s)</em>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="panel-container">
            <div class="panel-wrapper">
                <div class="panel-with-labels" style="grid-template-columns: 40px repeat(<?php echo $project['cols']; ?>, 120px); grid-template-rows: 40px repeat(<?php echo $project['rows']; ?>, 180px);">
                    <!-- Corner cell -->
                    <div class="corner-label"></div>
                    
                    <!-- Column labels -->
                    <?php for ($c = 0; $c < $project['cols']; $c++): ?>
                        <div class="col-label"><?php echo $c + 1; ?></div>
                    <?php endfor; ?>
                    
                    <!-- Rows with labels and slots -->
                    <?php for ($r = 0; $r < $project['rows']; $r++): ?>
                        <!-- Row label -->
                        <div class="row-label"><?php echo $r + 1; ?></div>
                        
                        <!-- Slots for this row -->
                        <?php for ($c = 0; $c < $project['cols']; $c++): ?>
                            <?php
                            $isOccupied = isset($occupied[$r][$c]);
                            $compData = $isOccupied ? $occupied[$r][$c] : null;
                            $compId = $compData['id'] ?? null;
                            $compWidth = $compData['width'] ?? 1;
                            $compHeight = $compData['height'] ?? 1;
                            $originRow = $compData['origin_row'] ?? $r;
                            $originCol = $compData['origin_col'] ?? $c;
                            $isOrigin = ($r == $originRow && $c == $originCol);
                            $compType = isset($componentMap[$compId]) ? $componentMap[$compId]['type'] : '';
                            ?>
                            <div class="slot <?php echo $isOccupied ? 'occupied type-' . htmlspecialchars($compType) : ''; ?>" 
                                 data-row="<?php echo $r; ?>" 
                                 data-col="<?php echo $c; ?>"
                                 <?php if ($isOccupied): ?>
                                 data-component-id="<?php echo $compId; ?>"
                                 data-component-type="<?php echo htmlspecialchars($compType); ?>"
                                 data-width="<?php echo $compWidth; ?>"
                                 data-height="<?php echo $compHeight; ?>"
                                 data-origin-row="<?php echo $originRow; ?>"
                                 data-origin-col="<?php echo $originCol; ?>"
                                 <?php endif; ?>>
                                <?php if ($isOccupied && $isOrigin && isset($componentMap[$compId])): ?>
                                    <img src="<?php echo $componentMap[$compId]['icon_path']; ?>" alt="Component">
                                <?php endif; ?>
                            </div>
                        <?php endfor; ?>
                    <?php endfor; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        $(function() {
            // Search functionality
            $('#search-box').on('keyup', function() {
                const searchTerm = $(this).val().toLowerCase();
                
                $('.component').each(function() {
                    const name = $(this).data('name') || '';
                    const description = $(this).data('description') || '';
                    const type = $(this).data('type') || '';
                    
                    const matches = name.includes(searchTerm) || 
                                  description.includes(searchTerm) || 
                                  type.includes(searchTerm);
                    
                    if (matches || searchTerm === '') {
                        $(this).removeClass('hidden');
                    } else {
                        $(this).addClass('hidden');
                    }
                });
            });

            $('.component').draggable({ 
                helper: 'clone',
                cursorAt: { left: 60, top: 60 }  // Adjusted for bigger grid
            });

            $('.slot').droppable({
                accept: '.component',
                tolerance: 'pointer',
                drop: function(event, ui) {
                    const row = $(this).data('row');
                    const col = $(this).data('col');
                    const comp = ui.draggable;
                    const w = parseInt(comp.data('width')) || 1;
                    const h = parseInt(comp.data('height')) || 1;
                    const compId = comp.data('id');
                    const compType = comp.data('type');

                    // Check space
                    let free = true;
                    for (let r = row; r < row + h; r++) {
                        for (let c = col; c < col + w; c++) {
                            if ($(`.slot[data-row=${r}][data-col=${c}]`).hasClass('occupied')) {
                                free = false;
                            }
                        }
                    }

                    if (free) {
                        // Place and mark all affected slots
                        for (let r = row; r < row + h; r++) {
                            for (let c = col; c < col + w; c++) {
                                $(`.slot[data-row=${r}][data-col=${c}]`)
                                    .addClass('occupied')
                                    .addClass('type-' + compType)
                                    .attr('data-component-id', compId)
                                    .attr('data-component-type', compType)
                                    .attr('data-width', w)
                                    .attr('data-height', h)
                                    .attr('data-origin-row', row)
                                    .attr('data-origin-col', col);
                            }
                        }
                        // Add image only to origin slot
                        const img = comp.find('img').clone();
                        $(this).html(img);
                        updatePrice();
                    } else {
                        alert('Not enough space!');
                    }
                }
            });

            // Click to remove component
            $(document).on('click', '.slot.occupied', function() {
                const compId = $(this).data('component-id');
                const compType = $(this).data('component-type');
                const w = parseInt($(this).data('width')) || 1;
                const h = parseInt($(this).data('height')) || 1;
                const originRow = parseInt($(this).data('origin-row') ?? $(this).data('row'));
                const originCol = parseInt($(this).data('origin-col') ?? $(this).data('col'));

                // Remove component from all occupied slots
                for (let r = originRow; r < originRow + h; r++) {
                    for (let c = originCol; c < originCol + w; c++) {
                        const slot = $(`.slot[data-row=${r}][data-col=${c}]`);
                        if (slot.data('component-id') == compId) {
                            slot.removeClass('occupied')
                                .removeClass('type-' + compType)
                                .removeAttr('data-component-id')
                                .removeAttr('data-component-type')
                                .removeAttr('data-width')
                                .removeAttr('data-height')
                                .removeAttr('data-origin-row')
                                .removeAttr('data-origin-col')
                                .html('');
                        }
                    }
                }
                updatePrice();
            });

            $('#save').click(function() {
                const slots = [];
                const counted = new Set();
                $('.slot.occupied').each(function() {
                    const row = $(this).data('row');
                    const col = $(this).data('col');
                    const compId = $(this).data('component-id');
                    const originRow = $(this).data('origin-row') ?? row;
                    const originCol = $(this).data('origin-col') ?? col;
                    
                    // Only save origin slots
                    if (row == originRow && col == originCol) {
                        const key = row + '-' + col + '-' + compId;
                        if (!counted.has(key) && compId) {
                            slots.push({ row, col, component_id: compId });
                            counted.add(key);
                        }
                    }
                });
                const layout = { rows: <?php echo $project['rows']; ?>, cols: <?php echo $project['cols']; ?>, slots };
                $.post('save_project.php', { 
                    project_id: <?php echo $project_id; ?>, 
                    layout: JSON.stringify(layout), 
                    total_price: $('#total-price').text() 
                }, function(response) {
                    alert('Saved successfully!');
                });
            });

            function updatePrice() {
                let total = 0;
                const counted = new Set();
                $('.slot.occupied').each(function() {
                    const compId = $(this).data('component-id');
                    if (compId && !counted.has(compId)) {
                        const comp = <?php echo json_encode($components); ?>.find(c => c.id == compId);
                        if (comp) total += parseFloat(comp.price);
                        counted.add(compId);
                    }
                });
                $('#total-price').text(total.toFixed(2));
            }
        });
    </script>
</body>
</html>
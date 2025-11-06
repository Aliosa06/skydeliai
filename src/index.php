<?php

require_once 'database/database.php';

$db = new Database();
$conn = $db->connect();

$result = $conn->query("SELECT id, name, total_price, `rows`, `cols`, panel_layout FROM projects ORDER BY created_at DESC");
$projects = $result->fetch_all(MYSQLI_ASSOC);
$result->close();
$db->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Project Dashboard</title>
</head>
<body>
    <h1>Electrical Panel Projects</h1>
    <a href="create_project.php"><button>Create New Project</button></a>
    <a href="manage_components.php"><button>Manage Components</button></a>
    <div>
        <?php if (empty($projects)): ?>
            <p>No projects yet. Create one!</p>
        <?php else: ?>
            <?php foreach ($projects as $project): ?>
                <div class="project">
                    <h3><?php echo htmlspecialchars($project['name']); ?></h3>
                    <p>Total Price: €<?php echo $project['total_price']; ?></p>
                    <div class="mini-panel" style="grid-template-rows: repeat(<?php echo $project['rows']; ?>, 1fr); grid-template-columns: repeat(<?php echo $project['cols']; ?>, 1fr);">
                        <?php
                        $layout = json_decode($project['panel_layout'], true);
                        for ($r = 0; $r < $project['rows']; $r++) {
                            for ($c = 0; $c < $project['cols']; $c++) {
                                $occupied = false;
                                if (isset($layout['slots'])) {
                                    foreach ($layout['slots'] as $slot) {
                                        if ($slot['row'] == $r && $slot['col'] == $c) {
                                            $occupied = true;
                                            break;
                                        }
                                    }
                                }
                                echo '<div class="mini-slot' . ($occupied ? ' occupied' : '') . '"></div>';
                            }
                        }
                        ?>
                    </div>
                    <a href="editor.php?project_id=<?php echo $project['id']; ?>"><button>Edit</button></a>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</body>
</html>
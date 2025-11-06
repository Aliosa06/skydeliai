<?php

require_once 'database/database.php';

$project_id = $_POST['project_id'] ?? null;
$layout = $_POST['layout'] ?? null;
$total_price = $_POST['total_price'] ?? 0;

if ($project_id && $layout) {
    $db = new Database();
    $conn = $db->connect();
    $stmt = $conn->prepare("UPDATE projects SET panel_layout = ?, total_price = ? WHERE id = ?");
    $stmt->bind_param("sdi", $layout, $total_price, $project_id);
    $stmt->execute();
    $stmt->close();
    $db->close();
    echo "Saved";
} else {
    echo "Error: Missing data";
}
?>
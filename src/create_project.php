<?php
require_once 'database/database.php';

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db = new Database();
    $conn = $db->connect();
    $name = htmlspecialchars($_POST['name']);
    $rows = (int)$_POST['rows'];
    $cols = (int)$_POST['cols'];
    $layout = json_encode(['rows' => $rows, 'cols' => $cols, 'slots' => []]);

    $stmt = $conn->prepare("INSERT INTO projects (name, `rows`, `cols`, panel_layout) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("siis", $name, $rows, $cols, $layout);
    if ($stmt->execute()) {
        $stmt->close();
        $db->close();
        header("Location: index.php");
        exit;
    } else {
        $error = "Failed to create project.";
    }
    $stmt->close();
    $db->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Create New Project</title>
</head>
<body>
    <h1>Create New Project</h1>
    <form method="post">
        <label>Project Name: <input type="text" name="name" required></label><br>
        <label>Rows: <input type="number" name="rows" value="3" min="1" required></label><br>
        <label>Cols: <input type="number" name="cols" value="12" min="1" required></label><br>
        <button type="submit">Create Project</button>
    </form>
    <a href="index.php">Back to Projects</a>
    <?php if ($error) echo "<p style='color:red;'>$error</p>"; ?>
</body>
</html>
<?php

require_once 'database/database.php';

$db = new Database();
$conn = $db->connect();

// Handle delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM components WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    header("Location: manage_components.php");
    exit;
}

$result = $conn->query("SELECT * FROM components ORDER BY type, name");
$components = $result->fetch_all(MYSQLI_ASSOC);
$result->close();
$db->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Components</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #4CAF50; color: white; }
        img { width: 40px; height: 40px; }
        .actions a { margin-right: 10px; }
    </style>
</head>
<body>
    <h1>Component Library</h1>
    <a href="add_component.php"><button>Add New Component</button></a>
    <a href="index.php"><button>Back to Projects</button></a>
    
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Icon</th>
                <th>Name</th>
                <th>Size (W×H)</th>
                <th>Price</th>
                <th>Description</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($components as $comp): ?>
                <tr>
                    <td><?php echo $comp['id']; ?></td>
                    <td><img src="<?php echo $comp['icon_path']; ?>" alt="Icon"></td>
                    <td><?php echo htmlspecialchars($comp['name']); ?></td>
                    <td><?php echo $comp['width']; ?>×<?php echo $comp['height']; ?></td>
                    <td>€<?php echo $comp['price']; ?></td>
                    <td><?php echo htmlspecialchars($comp['description']); ?></td>
                    <td class="actions">
                        <a href="edit_component.php?id=<?php echo $comp['id']; ?>">Edit</a>
                        <a href="?delete=<?php echo $comp['id']; ?>" onclick="return confirm('Delete this component?')">Delete</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>
<?php

require_once 'database/database.php';

$error = null;
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db = new Database();
    $conn = $db->connect();
    
    $name = htmlspecialchars($_POST['name']);
    $type = htmlspecialchars($_POST['type']);
    $price = (float)$_POST['price'];
    $width = (int)$_POST['width'];
    $height = (int)$_POST['height'];
    $description = htmlspecialchars($_POST['description']);
    
    // Handle file upload for icon
    $icon_path = '/images/placeholder.png'; // Default
    if (isset($_FILES['icon']) && $_FILES['icon']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = __DIR__ . '/images/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_ext = pathinfo($_FILES['icon']['name'], PATHINFO_EXTENSION);
        $file_name = uniqid('component_') . '.' . $file_ext;
        $upload_path = $upload_dir . $file_name;
        
        if (move_uploaded_file($_FILES['icon']['tmp_name'], $upload_path)) {
            $icon_path = '/images/' . $file_name;
        }
    }
    
    $stmt = $conn->prepare("INSERT INTO components (name, type, price, icon_path, description, width, height) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssdssii", $name, $type, $price, $icon_path, $description, $width, $height);
    
    if ($stmt->execute()) {
        $success = "Component added successfully!";
    } else {
        $error = "Failed to add component.";
    }
    
    $stmt->close();
    $db->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Component</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        form { max-width: 500px; }
        label { display: block; margin-top: 10px; }
        input, textarea, select { width: 100%; padding: 8px; margin-top: 5px; }
        button { margin-top: 15px; padding: 10px 20px; cursor: pointer; }
        .success { color: green; }
        .error { color: red; }
    </style>
</head>
<body>
    <h1>Add New Component</h1>
    <a href="manage_components.php">View All Components</a> | <a href="index.php">Back to Projects</a>
    
    <?php if ($success): ?>
        <p class="success"><?php echo $success; ?></p>
    <?php endif; ?>
    <?php if ($error): ?>
        <p class="error"><?php echo $error; ?></p>
    <?php endif; ?>
    
    <form method="post" enctype="multipart/form-data">
        <label>Component Name:
            <input type="text" name="name" required>
        </label>
        
        <label>Type:
            <select name="type" required>
                <option value="breaker">Breaker</option>
                <option value="switch">Switch</option>
                <option value="socket">Socket</option>
                <option value="light">Light</option>
                <option value="contactor">Contactor</option>
                <option value="module">Module</option>
                <option value="other">Other</option>
            </select>
        </label>
        
        <label>Price (€):
            <input type="number" name="price" step="0.01" min="0" required>
        </label>
        
        <label>Width (grid units):
            <input type="number" name="width" value="1" min="1" max="10" required>
        </label>
        
        <label>Height (grid units):
            <input type="number" name="height" value="1" min="1" max="10" required>
        </label>
        
        <label>Description:
            <textarea name="description" rows="3"></textarea>
        </label>
        
        <label>Icon Image:
            <input type="file" name="icon" accept="image/*">
        </label>
        
        <button type="submit">Add Component</button>
    </form>
</body>
</html>
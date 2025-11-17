<?php
require_once 'config.php';
requireLogin();

$db = getDB();
$message = '';

// Create uploads directory if it doesn't exist
if (!file_exists(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0777, true);
}

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['images'])) {
    $projectId = $_POST['project_id'] ?? 0;
    
    if ($projectId > 0) {
        $uploadedCount = 0;
        $errors = [];
        
        foreach ($_FILES['images']['tmp_name'] as $key => $tmpName) {
            if ($_FILES['images']['error'][$key] === UPLOAD_ERR_OK) {
                $fileName = $_FILES['images']['name'][$key];
                $fileSize = $_FILES['images']['size'][$key];
                $fileTmp = $_FILES['images']['tmp_name'][$key];
                
                // Validate file
                $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                
                if (!in_array($fileExt, ALLOWED_EXTENSIONS)) {
                    $errors[] = "$fileName: Invalid file type. Allowed: " . implode(', ', ALLOWED_EXTENSIONS);
                    continue;
                }
                
                if ($fileSize > MAX_FILE_SIZE) {
                    $errors[] = "$fileName: File too large. Max size: 5MB";
                    continue;
                }
                
                // Generate unique filename
                $newFileName = uniqid('img_', true) . '.' . $fileExt;
                $uploadPath = UPLOAD_DIR . $newFileName;
                
                if (move_uploaded_file($fileTmp, $uploadPath)) {
                    // Save to database
                    $stmt = $db->prepare("INSERT INTO project_images (project_id, image_path, image_name) VALUES (?, ?, ?)");
                    if ($stmt->execute([$projectId, 'uploads/' . $newFileName, $fileName])) {
                        $uploadedCount++;
                    }
                } else {
                    $errors[] = "$fileName: Upload failed";
                }
            }
        }
        
        if ($uploadedCount > 0) {
            $message = '<div class="alert alert-success">Successfully uploaded ' . $uploadedCount . ' image(s)!</div>';
        }
        if (!empty($errors)) {
            $message .= '<div class="alert alert-warning">' . implode('<br>', $errors) . '</div>';
        }
    } else {
        $message = '<div class="alert alert-danger">Please select a project.</div>';
    }
}

// Handle image deletion
if (isset($_GET['delete'])) {
    $imageId = $_GET['delete'];
    $stmt = $db->prepare("SELECT image_path FROM project_images WHERE id = ?");
    $stmt->execute([$imageId]);
    $image = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($image) {
        // Delete file
        $filePath = __DIR__ . '/../' . $image['image_path'];
        if (file_exists($filePath)) {
            unlink($filePath);
        }
        
        // Delete from database
        $stmt = $db->prepare("DELETE FROM project_images WHERE id = ?");
        if ($stmt->execute([$imageId])) {
            $message = '<div class="alert alert-success">Image deleted successfully!</div>';
        }
    }
}

// Get all projects
$projects = $db->query("SELECT * FROM projects ORDER BY title")->fetchAll(PDO::FETCH_ASSOC);

// Get selected project images
$selectedProjectId = $_GET['project_id'] ?? ($_POST['project_id'] ?? 0);
$projectImages = [];
if ($selectedProjectId > 0) {
    $stmt = $db->prepare("SELECT * FROM project_images WHERE project_id = ? ORDER BY uploaded_at DESC");
    $stmt->execute([$selectedProjectId]);
    $projectImages = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Images - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.15.4/css/all.css"/>
    <style>
        .sidebar {
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .sidebar .nav-link {
            color: rgba(255,255,255,0.8);
            padding: 12px 20px;
            margin: 5px 0;
            border-radius: 5px;
        }
        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            background: rgba(255,255,255,0.2);
            color: white;
        }
        .image-preview {
            max-width: 200px;
            max-height: 200px;
            object-fit: cover;
            border-radius: 8px;
            margin: 10px;
        }
        .image-item {
            position: relative;
            display: inline-block;
            margin: 10px;
        }
        .image-item .delete-btn {
            position: absolute;
            top: 5px;
            right: 5px;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <?php include 'sidebar.php'; ?>
            
            <div class="col-md-9 col-lg-10 p-4">
                <h2>Upload Project Images</h2>
                <?php echo $message; ?>
                
                <div class="card shadow mt-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-upload me-2"></i>Upload Images</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label class="form-label">Select Project</label>
                                <select class="form-select" name="project_id" id="projectSelect" required onchange="this.form.submit()">
                                    <option value="">-- Select a Project --</option>
                                    <?php foreach ($projects as $project): ?>
                                        <option value="<?php echo $project['id']; ?>" <?php echo $selectedProjectId == $project['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($project['title']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <?php if ($selectedProjectId > 0): ?>
                                <div class="mb-3">
                                    <label class="form-label">Select Images (Multiple files allowed)</label>
                                    <input type="file" class="form-control" name="images[]" multiple accept="image/*" required>
                                    <small class="text-muted">Allowed formats: JPG, PNG, GIF, WEBP. Max size: 5MB per file</small>
                                </div>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-upload me-2"></i>Upload Images
                                </button>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>
                
                <?php if ($selectedProjectId > 0 && !empty($projectImages)): ?>
                    <div class="card shadow mt-4">
                        <div class="card-header">
                            <h5 class="mb-0">Project Images (<?php echo count($projectImages); ?>)</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <?php foreach ($projectImages as $image): ?>
                                    <div class="col-md-3 mb-3">
                                        <div class="image-item">
                                            <img src="../<?php echo htmlspecialchars($image['image_path']); ?>" 
                                                 alt="<?php echo htmlspecialchars($image['image_name']); ?>" 
                                                 class="img-fluid image-preview">
                                            <a href="?delete=<?php echo $image['id']; ?>&project_id=<?php echo $selectedProjectId; ?>" 
                                               class="btn btn-sm btn-danger delete-btn" 
                                               onclick="return confirm('Delete this image?');">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </div>
                                        <small class="d-block text-muted"><?php echo htmlspecialchars($image['image_name']); ?></small>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                <?php elseif ($selectedProjectId > 0): ?>
                    <div class="alert alert-info mt-4">
                        No images uploaded for this project yet. Upload images using the form above.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>


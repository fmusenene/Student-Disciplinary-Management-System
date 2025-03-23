<?php
require_once 'includes/header.php';
require_once 'config/database.php';

if (!hasPermission('admin')) {
    header('Location: index.php');
    exit();
}

$success = $error = '';

// Handle user creation/update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $userId = $_POST['user_id'] ?? '';
    $username = $_POST['username'] ?? '';
    $email = $_POST['email'] ?? '';
    $fullName = $_POST['full_name'] ?? '';
    $role = $_POST['role'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($email) || empty($fullName) || empty($role)) {
        $error = 'Required fields are missing';
    } else {
        try {
            if ($action === 'create') {
                if (empty($password)) {
                    $error = 'Password is required for new users';
                } else {
                    $stmt = $conn->prepare("
                        INSERT INTO users (username, email, full_name, role, password)
                        VALUES (?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([
                        $username,
                        $email,
                        $fullName,
                        $role,
                        password_hash($password, PASSWORD_DEFAULT)
                    ]);
                    $success = 'User created successfully';
                }
            } else {
                $sql = "UPDATE users SET username = ?, email = ?, full_name = ?, role = ?";
                $params = [$username, $email, $fullName, $role];
                
                if (!empty($password)) {
                    $sql .= ", password = ?";
                    $params[] = password_hash($password, PASSWORD_DEFAULT);
                }
                
                $sql .= " WHERE id = ?";
                $params[] = $userId;
                
                $stmt = $conn->prepare($sql);
                $stmt->execute($params);
                $success = 'User updated successfully';
            }
        } catch (Exception $e) {
            $error = 'Error: ' . $e->getMessage();
        }
    }
}

// Get all users
$stmt = $conn->query("SELECT * FROM users ORDER BY role, full_name");
$users = $stmt->fetchAll();
?>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Manage Users</h5>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#userModal">
                    Add New User
                </button>
            </div>
            <div class="card-body">
                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Created At</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                                <td><?php echo htmlspecialchars($user['username']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td><?php echo ucfirst(str_replace('_', ' ', $user['role'])); ?></td>
                                <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#userModal<?php echo $user['id']; ?>">
                                        Edit
                                    </button>
                                </td>
                            </tr>
                            
                            <!-- Edit User Modal -->
                            <div class="modal fade" id="userModal<?php echo $user['id']; ?>" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Edit User</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <form method="POST">
                                                <input type="hidden" name="action" value="update">
                                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                
                                                <div class="mb-3">
                                                    <label for="username" class="form-label">Username</label>
                                                    <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <label for="email" class="form-label">Email</label>
                                                    <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <label for="full_name" class="form-label">Full Name</label>
                                                    <input type="text" class="form-control" id="full_name" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <label for="role" class="form-label">Role</label>
                                                    <select class="form-select" id="role" name="role" required>
                                                        <option value="admin" <?php echo $user['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                                                        <option value="teacher" <?php echo $user['role'] === 'teacher' ? 'selected' : ''; ?>>Teacher</option>
                                                        <option value="chairperson" <?php echo $user['role'] === 'chairperson' ? 'selected' : ''; ?>>Chairperson</option>
                                                        <option value="deputy_ethics" <?php echo $user['role'] === 'deputy_ethics' ? 'selected' : ''; ?>>Deputy Ethics</option>
                                                        <option value="deputy_academics" <?php echo $user['role'] === 'deputy_academics' ? 'selected' : ''; ?>>Deputy Academics</option>
                                                        <option value="deputy_admin" <?php echo $user['role'] === 'deputy_admin' ? 'selected' : ''; ?>>Deputy Admin</option>
                                                        <option value="dean" <?php echo $user['role'] === 'dean' ? 'selected' : ''; ?>>Dean</option>
                                                        <option value="headteacher" <?php echo $user['role'] === 'headteacher' ? 'selected' : ''; ?>>Headteacher</option>
                                                        <option value="parent" <?php echo $user['role'] === 'parent' ? 'selected' : ''; ?>>Parent</option>
                                                    </select>
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <label for="password" class="form-label">New Password (leave blank to keep current)</label>
                                                    <input type="password" class="form-control" id="password" name="password">
                                                </div>
                                                
                                                <button type="submit" class="btn btn-primary">Update User</button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add User Modal -->
<div class="modal fade" id="userModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form method="POST">
                    <input type="hidden" name="action" value="create">
                    
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" class="form-control" id="username" name="username" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="full_name" class="form-label">Full Name</label>
                        <input type="text" class="form-control" id="full_name" name="full_name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="role" class="form-label">Role</label>
                        <select class="form-select" id="role" name="role" required>
                            <option value="admin">Admin</option>
                            <option value="teacher">Teacher</option>
                            <option value="chairperson">Chairperson</option>
                            <option value="deputy_ethics">Deputy Ethics</option>
                            <option value="deputy_academics">Deputy Academics</option>
                            <option value="deputy_admin">Deputy Admin</option>
                            <option value="dean">Dean</option>
                            <option value="headteacher">Headteacher</option>
                            <option value="parent">Parent</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Create User</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?> 
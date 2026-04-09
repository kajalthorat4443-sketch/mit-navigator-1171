<?php
session_start();

$dataPath = __DIR__ . '/../data/users.json';

// Initialize users.json with default admin account if it doesn't exist
if (!file_exists($dataPath)) {
    $dir = dirname($dataPath);
    if (!is_dir($dir)) mkdir($dir, 0777, true);
    
    $defaultUsers = [
        'themorevaibhav@gmail.com' => [
            'email' => 'themorevaibhav@gmail.com',
            'password' => password_hash('themorevaibhav@gmail.com', PASSWORD_DEFAULT),
            'role' => 'admin'
        ]
    ];
    file_put_contents($dataPath, json_encode($defaultUsers, JSON_PRETTY_PRINT));
}

$action = $_GET['action'] ?? '';

if ($action === 'login') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        header("Location: ../login.php?error=Please fill in all fields.");
        exit;
    }
    
    $users = json_decode(file_get_contents($dataPath), true);
    
    if (isset($users[$email]) && password_verify($password, $users[$email]['password'])) {
        // Success
        $_SESSION['user'] = [
            'email' => $email,
            'role' => $users[$email]['role']
        ];
        
        if ($users[$email]['role'] === 'admin') {
            header("Location: ../admin.php");
        } else {
            header("Location: ../map.php");
        }
    } else {
        header("Location: ../login.php?error=Invalid email or password.");
    }
    exit;
}

if ($action === 'register') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        header("Location: ../register.php?error=Please fill in all fields.");
        exit;
    }
    
    // Email validation
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
         header("Location: ../register.php?error=Please enter a valid email address.");
         exit;
    }
    
    $users = json_decode(file_get_contents($dataPath), true);
    
    if (isset($users[$email])) {
        header("Location: ../register.php?error=Email already exists.");
        exit;
    }
    
    // Register as standard user
    $users[$email] = [
        'email' => $email,
        'password' => password_hash($password, PASSWORD_DEFAULT),
        'role' => 'user'
    ];
    
    file_put_contents($dataPath, json_encode($users, JSON_PRETTY_PRINT));
    
    // Auto login
    $_SESSION['user'] = [
        'email' => $email,
        'role' => 'user'
    ];
    
    header("Location: ../map.php");
    exit;
}

if ($action === 'create_user') {
    header('Content-Type: application/json');
    
    // Authorization: only the super admin can create users this way
    if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin' || $_SESSION['user']['email'] !== 'themorevaibhav@gmail.com') {
        echo json_encode(['success' => false, 'error' => 'Unauthorized. Only themorevaibhav@gmail.com can create users.']);
        exit;
    }
    
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'user';
    
    if (empty($email) || empty($password)) {
        echo json_encode(['success' => false, 'error' => 'Please fill in all fields.']);
        exit;
    }
    
    // Email validation
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
         echo json_encode(['success' => false, 'error' => 'Please enter a valid email address.']);
         exit;
    }
    
    if (!in_array($role, ['user', 'admin'])) {
        echo json_encode(['success' => false, 'error' => 'Invalid role specified.']);
        exit;
    }
    
    $users = json_decode(file_get_contents($dataPath), true);
    
    if (isset($users[$email])) {
        echo json_encode(['success' => false, 'error' => 'Email already exists.']);
        exit;
    }
    
    // Register the new user
    $users[$email] = [
        'email' => $email,
        'password' => password_hash($password, PASSWORD_DEFAULT),
        'role' => $role
    ];
    
    if (file_put_contents($dataPath, json_encode($users, JSON_PRETTY_PRINT))) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to write to file.']);
    }
    exit;
}

if ($action === 'logout') {
    session_destroy();
    header("Location: ../index.php");
    exit;
}

header("Location: ../index.php");

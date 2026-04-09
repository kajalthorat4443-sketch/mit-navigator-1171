<?php

$email = $_POST['email'];
$password = $_POST['password'];
$role = "user";

$file = "users.json";

$data = json_decode(file_get_contents($file), true);

// check if user already exists
if (isset($data[$email])) {
    echo "User already exists";
    exit;
}

// hash password
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

// create new user
$data[$email] = [
    "email" => $email,
    "password" => $hashedPassword,
    "role" => $role
];

// save back to file
file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT));

echo "User created successfully";

?>
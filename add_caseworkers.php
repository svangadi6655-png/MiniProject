<?php
include 'db_connect.php';

$workers = [
    ["Worker1", "w1@example.com", "123456"],
    ["Worker2", "w2@example.com", "123456"],
    ["Worker3", "w3@example.com", "123456"],
    ["Worker4", "w4@example.com", "123456"],
    ["Worker5", "w5@example.com", "123456"],
    ["Worker6", "w6@example.com", "123456"],
    ["Worker7", "w7@example.com", "123456"],
    ["Worker8", "w8@example.com", "123456"],
    ["Worker9", "w9@example.com", "123456"],
    ["Worker10", "w10@example.com", "123456"]
];

foreach ($workers as $w) {
    $name = $w[0];
    $email = $w[1];
    $password = password_hash($w[2], PASSWORD_DEFAULT); // <-- HASHING PASSWORD
    $role = "caseworker";

    $sql = "INSERT INTO users (name, email, password, role) VALUES ('$name', '$email', '$password', '$role')";
    $conn->query($sql);
}

echo "10 caseworkers added successfully!";

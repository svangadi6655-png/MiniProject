<?php
$password = 'A_NEW_TEMPORARY_PASSWORD'; // <-- CHOOSE A NEW PASSWORD HERE
$hashed_password = password_hash($password, PASSWORD_DEFAULT);
echo "Use this hash for your admin: " . $hashed_password;
?>
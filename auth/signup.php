<?php

include '../connect.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    echo "<h1>Method Not Allowed</h1>";
    exit;
}
try {
    $checkTable = $conn->query("SELECT 1 FROM users LIMIT 1");
} catch (PDOException $e) {
    $conn->query("CREATE TABLE users (
            id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `name` VARCHAR(30) NOT NULL,
            email VARCHAR(50) NOT NULL,
            `password` VARCHAR(255) NOT NULL,
            phone VARCHAR(15) NOT NULL,
            userImage varchar (255) null,
            token VARCHAR(255) NULL
        )");
}


if (strpos($_SERVER["CONTENT_TYPE"], "application/json") !== false) {
    $rawData = file_get_contents("php://input");

    $decodedData = json_decode($rawData, true);
    $name = $decodedData['name'];
    $email = $decodedData['email'];
    $password = $decodedData['password'];
    $phone = $decodedData['phone'];
} else {
    $name = filterRequest('name');
    $email = filterRequest('email');
    $password = filterRequest('password');
    $phone = filterRequest('phone');
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(array(
        'status' => 'error',
        "message" => "Invalid email format",
        "data" => null
    ));
    exit;
}

$emailCheck = $conn->prepare("SELECT * FROM users WHERE email=?");
$emailCheck->execute([$email]);
$phoneCheck = $conn->prepare("SELECT * FROM users WHERE phone=?");
$phoneCheck->execute([$phone]);
if ($emailCheck->rowCount() > 0) {
    echo json_encode(array(
        'status' => 'error',
        "message" => "Email already exists",
        "data" => null
    ));
    exit;
}else if($phoneCheck->rowCount() > 0){
    echo json_encode(array(
        'status' => 'error',
        "message" => "Phone number already exists",
        "data" => null
    ));
    exit;

} else {
    $password = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("INSERT INTO users (name, email, password, phone) VALUES (?, ?, ?, ?)");
    $stmt->execute([$name, $email, $password, $phone]);
    if ($stmt->rowCount() == 0) {
        echo json_encode(array(
            'status' => 'error',
            "message" => "User not created",
            "data" => null
        ));
        exit;
    } else {
        http_response_code(200);
        $token = bin2hex(random_bytes(32));
        $stmt = $conn->prepare("UPDATE users SET token=? WHERE email=?");
        $stmt->execute([$token, $email]);
        echo json_encode(array(
            'status' => 'Success',
            "message" => "User created successfully",
            "data" => [
                "name" => $name,
                "email" => $email,
                "phone" => $phone,
                'token' => $token
            ]
        ));
        exit;
    }
}



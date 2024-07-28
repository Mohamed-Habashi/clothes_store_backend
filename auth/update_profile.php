<?php

include '../connect.php';

if ($_SERVER['REQUEST_METHOD'] != 'PUT') {
    echo "<h1>Invalid Method</h1>";
    exit;
}

$token = getBearerToken();

if (strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false) {
    $rawData = file_get_contents('php://input');
    $decodedData = json_decode($rawData, true);

    $name = $decodedData['name'] ?? null;
    $email = $decodedData['email'] ?? null;
    $phone = $decodedData['phone'] ?? null;
} else {
    parse_str(file_get_contents('php://input'), $put_vars);
    $name=$put_vars['name'] ?? null;
    $email=$put_vars['email'] ?? null;
    $phone=$put_vars['phone'] ?? null;
}
$stmt = $conn->prepare('SELECT * FROM users WHERE email=? AND token!=?');
$stmt->execute([$email, $token]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
if($user){
    echo json_encode(
        array(
            'status' => 'Error',
            'message' => 'Email already exist',
            'data' => null
        )
    );
    exit;
}

$stmt = $conn->prepare('SELECT * FROM users WHERE phone=? AND token!=?');
$stmt->execute([$phone, $token]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
if($user){
    echo json_encode(
        array(
            'status' => 'Error',
            'message' => 'Phone already exist',
            'data' => null
        )
    );
    exit;
}



if ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(
        array(
            'status' => 'Error',
            'message' => 'Invalid E-mail',
            'data' => null
        )
    );
    exit;
}

$fieldsToUpdate = [];
$params = [];

if ($name !== null) {
    $fieldsToUpdate[] = '`name` = ?';
    $params[] = $name;
}

if ($email !== null) {
    $fieldsToUpdate[] = 'email = ?';
    $params[] = $email;
}

if ($phone !== null) {
    $fieldsToUpdate[] = 'phone = ?';
    $params[] = $phone;
}

if (empty($fieldsToUpdate)) {
    echo json_encode(
        array(
            'status' => 'Error',
            'message' => 'No data provided for update',
            'data' => null
        )
    );
    exit;
}

$params[] = $token;
$sql = 'UPDATE users SET ' . implode(', ', $fieldsToUpdate) . ' WHERE token=?';

$stmt = $conn->prepare($sql);
$stmt->execute($params);

if ($stmt->rowCount() > 0) {
    echo json_encode(
        array(
            'status' => 'Success',
            'Message' => 'Edit success',
            'data' => array_filter([
                'name' => $name,
                'email' => $email,
                'phone' => $phone,
                'token' => $token,
            ])
        )
    );
} else {
    echo json_encode(
        array(
            'status' => 'Error',
            'Message' => 'Update failed or no changes made',
            'data' => null
        )
    );
}
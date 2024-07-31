<?php

include '../connect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(array(
        'status' => 'Error',
        'status_message' => 'Method Not Allowed'
    ));
    exit;
}

if (strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false) {
    $rawData = file_get_contents("php://input");
    $decodedData = json_decode($rawData, true);
    $orderDate = $decodedData['order_date'];
    $userLocation = $decodedData['location']; 
    $products = $decodedData['products'];
} else {
    $orderDate = filterRequest('order_date');
    $userLocation = filterRequest('location'); 
    
}

$token = getBearerToken();

if ($token) {
    $stmt = $conn->prepare('SELECT * FROM users WHERE token = ?');
    $stmt->execute(array($token));
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        $distance=getDistance($userLocation).'Km';
        $stmt=$conn->prepare(
            'INSERT INTO orders (`location`,order_date, user_id) VALUES (?, ?,?)'
        );
        $stmt->execute(array(
            $userLocation,
            $orderDate, 
            $user['id']));
        
        $stmt=$conn->prepare(
            'SELECT * FROM cart where user_id=?'
        );
        $stmt->execute(array($user['id']));
        $cart=$stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach($cart as $item){
            $stmt=$conn->prepare(
                'SELECT * from products where product_id=?'
            );
            $stmt->execute(array($item['product_id']));
            $product=$stmt->fetch(PDO::FETCH_ASSOC);
            $products[]=$product;
        }
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
        $baseURL = $protocol . $_SERVER['HTTP_HOST'] . '/e-commerce/uploads/products/';
        foreach ($products as &$product) {
            if (!empty($product['product_images'])) {
                $images = explode(',', $product['product_images']);
                foreach ($images as &$imagePath) {
                    $imagePath = $baseURL . trim($imagePath);
                }
                $product['product_images'] = $images;
            }
        }
        $response = array(
            'status' => 'Success',
            'status_message' => 'Order added successfully',
            'distance' => $distance,
            'products' => $products
        );
        echo json_encode($response);
        
    } else {
        http_response_code(401);
        $response = array(
            'status' => 'Error',
            'status_message' => 'Invalid Token'
        );
        echo json_encode($response);
        exit;
    }
} else {
    http_response_code(401);
    $response = array(
        'status' => 'Error',
        'status_message' => 'Access Denied'
    );
    echo json_encode($response);
    exit;
}
?>
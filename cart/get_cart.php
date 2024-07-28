<?php

include '../connect.php';

if ($_SERVER['REQUEST_METHOD'] != 'GET') {
    echo 'Method not allowed';
    http_response_code(405);
    exit;
}

$token = getBearerToken();

if ($token) {
    $stmt = $conn->prepare(
        'SELECT * FROM users where token=?'
    );
    $stmt->execute(array(
        $token
    ));
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        $stmt = $conn->prepare(
            'SELECT * FROM cart where user_id=?'
        );
        $stmt->execute(array(
            $user['id']
        ));
        $cart = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $total=0;
        

        foreach ($cart as $key => $cartItem) {
            $stmt = $conn->prepare(
                'SELECT * FROM products where product_id=?'
            );
            $stmt->execute(array(
                $cartItem['product_id']
            ));
            $product = $stmt->fetch(PDO::FETCH_ASSOC);
            $total+=$product['product_price'];
            $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
            $baseURL = $protocol . $_SERVER['HTTP_HOST'] . '/e-commerce/uploads/products/';

            if (!empty($product['product_images'])) {
                $images = explode(',', $product['product_images']);
                foreach ($images as &$imagePath) {
                    $imagePath = $baseURL . trim($imagePath);
                }
                $product['product_images'] = $images;
            }
            unset($cart[$key]['product_id']);
            unset($cart[$key]['user_id']);
            $cart[$key]['product'] = $product;
        }
        echo json_encode(
            array(
                'status' => 'Success',
                'message' => 'cart fetched successfully',
                'total' => $total,
                'data' => $cart
            )
        );
    } else {
        http_response_code(401);
        echo json_encode(
            array(
                'message' => 'Invalid user'
            )
        );
    }
} else {
    http_response_code(401);
    echo json_encode(
        array(
            'message' => 'Token not found'
        )
    );
}

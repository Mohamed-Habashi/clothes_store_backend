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
            'SELECT * FROM favorites where user_id=?'
        );
        $stmt->execute(array(
            $user['id']
        ));
        $favourites = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($favourites as $key => $favourite) {
            $stmt = $conn->prepare(
                'SELECT * FROM products where product_id=?'
            );
            $stmt->execute(array(
                $favourite['product_id']
            ));
            $product = $stmt->fetch(PDO::FETCH_ASSOC);
            $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
            $baseURL = $protocol . $_SERVER['HTTP_HOST'] . '/e-commerce/uploads/products/';

            if (!empty($product['product_images'])) {
                $images = explode(',', $product['product_images']);
                foreach ($images as &$imagePath) {
                    $imagePath = $baseURL . trim($imagePath);
                }
                $product['product_images'] = $images;
            }
            unset($favourites[$key]['product_id']);
            unset($favourites[$key]['user_id']);
            $favourites[$key]['product'] = $product;
        }
        echo json_encode(
            array(
                'status' => 'Success',
                'message' => 'Favourites fetched successfully',
                'data' => $favourites
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

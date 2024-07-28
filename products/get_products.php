<?php

include '../connect.php';

if ($_SERVER['REQUEST_METHOD'] != 'GET') {
    echo json_encode(
        array(
            'Message' => 'Error Invalid Method'
        )
    );
    exit;
}

$token = getBearerToken();

if ($token) {
    $stmt = $conn->prepare(
        'SELECT * FROM users where token =?'
    );
    $stmt->execute(
        array(
            $token
        )
    );
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user) {
        $stmt = $conn->prepare(
            'SELECT * FROM products'
        );
        $stmt->execute();

        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
            unset($product); 
            foreach ($products as &$product) {
                $product['is_favorite'] = false;
                $checkFavourite = $conn->prepare(
                    'SELECT * FROM favorites where product_id=? and user_id=?'
                );
                $checkFavourite->execute(
                    array(
                        $product['product_id'],
                        $user['id']
                    )
                );
                if ($checkFavourite->rowCount() > 0) {
                    $product['is_favorite'] = true;
                }
            }

            foreach ($products as &$product) {
                $product['in_cart'] = false;
                $checkCart = $conn->prepare(
                    'SELECT * FROM cart where product_id=? and user_id=?'
                );
                $checkCart->execute(
                    array(
                        $product['product_id'],
                        $user['id']
                    )
                );
                if ($checkCart->rowCount() > 0) {
                    $product['in_cart'] = true;
                }
            }

        if ($stmt->rowCount() > 0) {
            echo json_encode(
                array(
                    'status' => 'Success',
                    'message' => 'Get products Success',
                    'data' => $products
                )
            );
        }
    } else {
        http_response_code(401);
        echo json_encode(
            array(
                'status' => 'Error',
                'message' => 'Wrong token',
                'data' => null
            )
        );
    }
} else {
    http_response_code(401);
    echo json_encode(
        array(
            'status' => 'Error',
            'message' => 'Get products Failed',
            'data' => null
        )
    );
}

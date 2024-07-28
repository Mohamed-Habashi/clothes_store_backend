<?php

include '../connect.php';
ini_set('display_errors', 1);
error_reporting(E_ALL);



if ($_SERVER['REQUEST_METHOD'] != "POST") {
    echo json_encode(['status' => 'Error', 'message' => 'Method not allowed']);
    exit;
}

$token = getBearerToken();

if (strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false) {
    $rawData = file_get_contents('php://input');
    $decodedData = json_decode($rawData, true);

    $productName = $decodedData['product_name'] ?? null;
    $productDescription = $decodedData['product_description'] ?? null;
    $productCategory = $decodedData['product_category'] ?? null;
    $productImages = uploadProductImages('product_images');
    $productPrice = $decodedData['product_price'] ?? null;
} else {
    $productName = filterRequest('product_name');
    $productDescription = filterRequest('product_description');
    $productCategory = filterRequest('product_category');
    $productImages = uploadProductImages('product_images');
    $productPrice = filterRequest('product_price');
}

if ($token) {
    $stmt = $conn->prepare("SELECT * FROM users WHERE token=?");
    $stmt->execute([$token]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        if ($productImages != 'fail') {
            $productImagesString = is_array($productImages) ? implode(',', $productImages) : $productImages;
            $stmt = $conn->prepare(
                'INSERT INTO `products` (`product_name`, `product_description`, `product_images`, `product_category`, `product_price`, `product_owner`)
                VALUES (?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([
                $productName,
                $productDescription,
                $productImagesString,
                $productCategory,
                $productPrice,
                $user['id']
            ]);

            $lastId = $conn->lastInsertId();
            $stmt = $conn->prepare("SELECT * FROM products WHERE product_id = ?");
            $stmt->execute([$lastId]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);

            $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
            $baseURL = $protocol . $_SERVER['HTTP_HOST'] . '/e-commerce/uploads/products/';

            if (!empty($product['product_images'])) {
                $images = explode(',', $product['product_images']);
                foreach ($images as &$imagePath) {
                    $imagePath = $baseURL . $imagePath;
                }
                $product['product_images'] = $images;
            }

            echo json_encode([
                'status' => 'Success',
                'message' => 'Product Added Successfully',
                'data' => $product
            ]);
        } else {
            echo json_encode([
                'status' => 'Error',
                'message' => 'Failed to upload product image'
            ]);
        }
    } else {
        echo json_encode([
            'status' => 'Error',
            'message' => 'User not found'
        ]);
    }
} else {
    echo json_encode([
        'status' => 'Error',
        'message' => 'Authentication token is missing or invalid'
    ]);
}
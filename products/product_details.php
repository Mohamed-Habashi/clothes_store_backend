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
$requestUri = $_SERVER['REQUEST_URI'];

$uriSegments = explode('/', $requestUri);

$productId = end($uriSegments);

if (!is_numeric($productId)) {
    echo json_encode(['status' => 400, 'message' => 'Invalid product ID']);
    exit;
}

$token=getBearerToken();

if($token){
    $stmt=$conn->prepare(
        'SELECT * FROM users where token=?'
    );
    $stmt->execute(
        array(
            $token
        )
    );
    $user=$stmt->fetch(PDO::FETCH_ASSOC);
    if($user){
        $stmt=$conn->prepare(
            'SELECT * FROM products where product_id=?'
        );
        $stmt->execute(
            array(
                $productId
            )
        );
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
        $baseURL = $protocol . $_SERVER['HTTP_HOST'] . '/e-commerce/uploads/products/';

        $product=$stmt->fetch(PDO::FETCH_ASSOC);

        if (!empty($product['product_images'])) {
            $images = explode(',', $product['product_images']);
            foreach ($images as &$imagePath) {
                $imagePath = $baseURL . trim($imagePath); 
            }
            $product['product_images'] = $images; 
        }

        if($stmt->rowCount()>0){
            echo json_encode(
                array(
                    'status'=>'Success',
                    'message'=>'Product Details success',
                    'data'=>$product
                )
            );
        }else{
            echo json_encode(
                array(
                    'status'=>"Error",
                    'message'=>'No data was found',
                    'data'=>null
                )
            );
        }
    }else{
        http_response_code(401);
        echo json_encode(
            array(
                'status'=>"Error",
                'message'=>'Invalid token',
                'data'=>null
            )
        );
    }
}else{
    http_response_code(401);
    echo json_encode(
        array(
            'status'=>"Error",
            'message'=>'Un authorized',
            'data'=>null
        )
    );
}
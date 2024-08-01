<?php

include '../connect.php';

if($_SERVER['REQUEST_METHOD']!='GET'){
    http_response_code(405);
    echo json_encode(array(
        'status'=>'Error',
        'status_message'=>'Method Not Allowed'
    ));
    exit;
}

if(strpos($_SERVER['CONTENT_TYPE'],'application/json')!==false){
    $rawData=file_get_contents("php://input");
    $decodedData=json_decode($rawData,true);
    
}else{
}

$token=getBearerToken();

if($token){
    $stmt=$conn->prepare('SELECT * FROM users where token=?');
    $stmt->execute(array($token));
    $user=$stmt->fetch(PDO::FETCH_ASSOC);
    if($user){
        $stmt=$conn->prepare('SELECT * FROM orders where user_id=?');
        $stmt->execute(array($user['id']));
        $orders=$stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt=$conn->prepare('SELECT * FROM cart where user_id=?');
        $stmt->execute(array($user['id']));
        $cart=$stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach($cart as $item){
            $stmt=$conn->prepare('SELECT * FROM products where product_id=?');
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
        foreach($orders as &$order){
            $order['products']=$products;
        }

        echo json_encode(array(
            'status'=>'Success',
            'status_message'=>'Orders fetched',
            'orders'=>$orders,
        ));
    }else{
        http_response_code(401);
        echo json_encode(array(
            'status'=>'Error',
            'status_message'=>'Invalid user'
        ));
        exit;
    }
}else{
    http_response_code(401);
    echo json_encode(array(
        'status'=>'Error',
        'status_message'=>'Unauthorized'
    ));
    exit;
}
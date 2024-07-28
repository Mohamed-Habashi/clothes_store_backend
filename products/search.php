<?php

include '../connect.php';

if($_SERVER['REQUEST_METHOD']!='POST'){
    http_response_code(401);
    echo json_encode(
        array(
            'message'=>'Invalid Method',
            'data'=>'null'
        )
    );
    exit;
}

$token=getBearerToken();

if(strpos($_SERVER['CONTENT_TYPE'],'application/json')!==false){
    $rawData=file_get_contents('php://input');
    $decodedData=json_decode($rawData,true);

    $text=$decodedData['text'];

}else{
    $text=filterRequest('text');
}

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
        $text = '%'.$text.'%';
        $stmt=$conn->prepare(
            'SELECT * FROM products where product_name like ?'
        );
        $stmt->execute(
            array(
                $text
            )
        );
        $products=$stmt->fetchAll(PDO::FETCH_ASSOC);
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
        if($stmt->rowCount()>0){
            http_response_code(200);
            echo json_encode(
                array(
                    'status'=>true,
                    'message'=>'Products get successfully',
                    'data'=>$products
                )
            );
        }else{
            echo json_encode(
                array(
                    'status'=>false,
                    'message'=>'product not found',
                    'data'=>null
                )
            );
        }
    }else{
        http_response_code(401);
    echo json_encode(
        array(
            'status'=>false,
            'message'=>'user not found',
            'data'=>null
        )
    );
    }
}else{
    http_response_code(401);
    echo json_encode(
        array(
            'status'=>false,
            'message'=>'invalid token',
            'data'=>null
        )
    );
}
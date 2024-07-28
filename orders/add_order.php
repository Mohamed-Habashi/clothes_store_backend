<?php

include '../connect.php';

if($_SERVER['REQUEST_METHOD'] !== 'POST'){
    http_response_code(405);
    echo json_encode(
        array(
            'status' => 'Error',
            'status_message' => 'Method Not Allowed'
        )
    );
    exit;
}

if(strpos($_SERVER['CONTENT_TYPE'],'application/json') !== false){
    $rawData=file_get_contents("php://input");
    $decodedData=json_decode($rawData,true);
    $orderDate=$decodedData['order_date'];
    $orderLocatoin=$decodedData['location'];
    $orderStatus=$decodedData['status'];
    $productId=$decodedData['product_id'];
}else{
    $orderDate=filterRequest('order_date');
    $orderLocatoin=filterRequest('location');
    $orderStatus=filterRequest('status');
    $productId=filterRequest('product_id');
}

$token=getBearerToken();

if($token){
    $stmt=$conn->prepare(
        'SELECT * FROM users where token=?'
    );
    $stmt->execute(
        array($token)
    );
    $user=$stmt->fetch(PDO::FETCH_ASSOC);

    if($user){
        $stmt=$conn->prepare(
            'SELECT * FROM products where product_id=?'
        );
        $stmt->execute(
            array($productId)
        );
        $product=$stmt->fetch(PDO::FETCH_ASSOC);
        if($product){
            $stmt=$conn->prepare(
                'INSERT INTO orders (`order_date`,`location`,`status`,`product_id`,`user_id`) VALUES (?,?,?,?,?)'
            );
            $stmt->execute(
                array(
                    $orderDate,
                    $orderLocatoin,
                    $orderStatus,
                    $productId,
                    $user['id']
                )
            );
            $order=$stmt->fetch(PDO::FETCH_ASSOC);
            echo json_encode(
                array(
                    'status' => "Success",
                    'status_message' => 'Order Added Successfully',
                )
            );
        }else{
            http_response_code(404);
            $response=array(
                'status' => 'Error',
                'status_message' => 'Product Not Found'
            );
            echo json_encode($response);
            exit;
        }
    }else{
        http_response_code(401);
        $response=array(
            'status' => 'Error',
            'status_message' => 'Invalid Token'
        );
        echo json_encode($response);
        exit;
    
    }

}else{
    http_response_code(401);
    $response=array(
        'status' => 0,
        'status_message' => 'Access Denied'
    );
    echo json_encode($response);
    exit;
}
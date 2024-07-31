<?php

include '../connect.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if($_SERVER['REQUEST_METHOD']!='POST'){
    echo json_encode(
        array(
            'message'=>'Invalid method'
        )
    );

    exit;
}

if(strpos($_SERVER['CONTENT_TYPE'],'application/json')!==false){
    $rawData=file_get_contents('php://input');
    $decodedData=json_decode($rawData,true);

    $product_id=$decodedData['product_id'];
}else{
    $product_id=filterRequest('product_id');
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
        $checkCart=$conn->prepare(
            'SELECT * FROM cart where product_id=? and user_id=?'
        );
        $checkCart->execute(
            array(
                $product_id,
                $user['id']
            )
        );

        $stmt=$conn->prepare(
            'SELECT * FROM products where product_id=?'
        );
        $stmt->execute(
            array(
                $product_id
            )
        );
        $product=$stmt->fetch(PDO::FETCH_ASSOC);

        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
        $baseURL = $protocol . $_SERVER['HTTP_HOST'] . '/e-commerce/uploads/products/';

        if (!empty($product['product_images'])) {
            $images = explode(',', $product['product_images']);
            foreach ($images as &$imagePath) {
                $imagePath = $baseURL . trim($imagePath); 
            }
            $product['product_images'] = $images; 
        }
        if($stmt->rowCount()>0){
            if($checkCart->rowCount()>0){
                $stmt=$conn->prepare(
                    'DELETE FROM cart where product_id=? and user_id=?'
                );
                $product['in_cart']=false;
                $stmt->execute(
                    array(
                        $product_id,
                        $user['id']
                    )
                );                
                if($stmt->rowCount()>0){
                    unset($product['in_cart']);
                    echo json_encode(
                        array(
                            'status'=>"Success",
                            'message'=>'Product Deleted Successfully',
                            'data'=>$product
                        )
                    );
                }else{
                    echo json_encode(
                        array(
                            'status'=>"Error",
                            'message'=>'Failed to delete product',
                            'data'=>null
                        )
                    );
                }
            }else{
                $add=$conn->prepare(
                    'INSERT INTO cart (product_price,product_id,user_id) VALUES (?,?,?)'
                );
                $product['in_cart']=true;
                unset($product['in_cart']);
                $add->execute(
                    array(
                        $product['product_price'],
                        $product_id,
                        $user['id']
                    )
                );
                if($add->rowCount()>0){
                    echo json_encode(
                        array(
                            'status'=>"Success",
                            'message'=>'Product Added Successfully',
                            'data'=>$product
                        )
                    );
                }else{
                    echo json_encode(
                        array(
                            'status'=>"Error",
                            'message'=>'Failed to add product',
                            'data'=>null
                        )
                    );
                }
            }
            
        }else{
            echo json_encode(
                array(
                    'status'=>"Error",
                    'message'=>'no product found',
                    'data'=>null
                )
            );
        }
       
    }else{
        http_response_code(401);
        echo json_encode(
            array(
                'status'=>"Error",
                'message'=>'user not found',
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
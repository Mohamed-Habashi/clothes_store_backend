<?php

include '../connect.php';

if($_SERVER['REQUEST_METHOD']!='GET'){
    echo '<h1>Invalid Method</h1>';
    exit;
}


$token=getBearerToken();



if($token){
    $stmt=$conn->prepare(
        'SELECT * FROM `users` WHERE token=?'
    );
    $stmt->execute(array(
        $token
    ));
    $user=$stmt->fetchAll(PDO::FETCH_ASSOC);
    if($user){
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
        $baseURL = $protocol . $_SERVER['HTTP_HOST'] . '/e-commerce/uploads/users/';

        // Prepend the base URL to the user image path
        if (!empty($user[0]['userImage'])) {
            $user[0]['userImage'] = $baseURL . $user[0]['userImage'];
        }
        unset($user[0]['password']);
        echo json_encode(
            array(
                'status'=>'Success',
                'Message'=>'Get user data successfully',
                'data'=>$user
            )
        );
    }else{
        echo json_encode(
            array(
                'status'=>'Error',
                'Message'=>'User not found',
                'data'=>null
            )
        );
    }
}else{
    echo json_encode(
        array(
            'status'=>'Error',
            'Message'=>'Invalid',
            'data'=>null
        )
    );
}
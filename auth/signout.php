<?php

include '../connect.php';

$token=getBearerToken();

if($_SERVER['REQUEST_METHOD']!='POST'){
    echo '<h1> Invalid Method</h1>';
    exit;
}

if($token){
    $stmt=$conn->prepare('UPDATE users set token=null where token=?');

    $stmt->execute(
        array($token)
    );

    if($stmt->rowCount()>0){
        echo json_encode(
            array(
                'status'=>'Success',
                'message'=>"Signout Success"
            )
        );
    }else{
        echo json_encode(
            array(
                'status'=>'Error',
                'message'=>"Signout Failed"
            )
        );
    }
}else{
    echo json_encode(
        array(
            'status'=>'Error',
            'message'=>"Invalid token"
        )
    );
}
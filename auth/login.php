<?php 

include '../connect.php';
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
if($_SERVER['REQUEST_METHOD']!="POST"){
    echo "<h1> Invalid Method</h1>";
    exit;
}

if(strpos($_SERVER["CONTENT_TYPE"], "application/json") !== false){
    $rawData=file_get_contents('php://input');

    $decodedData=json_decode($rawData,true);

    $email=$decodedData['email'];
    $password=$decodedData['password'];

}else{
    $email=filterRequest('email');
    $password=filterRequest('password');

}


$stmt=$conn->prepare('SELECT * FROM users where email=?');

$stmt->execute(
    array(
        $email,
    )
);

$user=$stmt->fetch(PDO::FETCH_ASSOC);

if($user){
    if(password_verify($password,$user['password'])){
        $token=bin2hex(random_bytes(32));
        $stmt=$conn->prepare('UPDATE `users` SET token=? where email=?');
        $stmt->execute(array(
            $token,
            $email
        ));
        echo json_encode(array(
            'status'=>'Success',
            'message'=>'Login Successfully',
            'data'=>array(
                'id'=>$user['id'],
                'name'=>$user['name'],
                'email'=>$user['email'],
                'phone'=>$user['phone'],
                'token'=>$token
            )
        ));
    }else{
        echo json_encode(
            array(
                'status'=>'Error',
                'message'=>'Wrong password',
                'data'=>null
            )
        );
    }
}else{
    echo json_encode(
        array(
            'status'=>'Error',
            'message'=>'User not found',
            'data'=>null
        )
    );
}
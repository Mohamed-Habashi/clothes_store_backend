<?php 

include '../connect.php';

$token = getBearerToken();

if ($token) {
    $stmt = $conn->prepare(
        'SELECT * FROM `users` WHERE token=?'
    );
    $stmt->execute(array(
        $token
    ));
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user) {
        $userImage = uploadUserImage('userImage');
        if ($userImage != 'fail') {
            $stmt = $conn->prepare(
                'SELECT userImage FROM `users` WHERE token=?'
            );
            $stmt->execute(array(
                $token
            ));
            $oldImage = $stmt->fetch(PDO::FETCH_ASSOC)['userImage'];
            if ($oldImage) {
                deleteOldImage($oldImage);
            }
            $stmt = $conn->prepare(
                'UPDATE `users` SET userImage=? WHERE token=?'
            );
            $stmt->execute(array(
                $userImage,
                $token
            ));

            // Generate the full URL for the user image
            $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
            $baseURL = $protocol . $_SERVER['HTTP_HOST'] . '/e-commerce/uploads/users/';
            $fullUserImageURL = $baseURL . $userImage;

            echo json_encode(
                array(
                    'status' => 'Success',
                    'Message' => 'Image uploaded successfully',
                    'data' => $fullUserImageURL
                )
            );
        }
    } else {
        echo json_encode(
            array(
                'status' => 'Fail',
                'Message' => 'Invalid token'
            )
        );
    }
} else {
    echo json_encode(
        array(
            'status' => 'Fail',
            'Message' => 'Token not found'
        )
    );
}


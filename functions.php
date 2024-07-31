<?php

define('MB','1048576');
function filterRequest($command){
    return htmlspecialchars(strip_tags($_POST[$command]));
}
function filterGet($command){
    return htmlspecialchars(strip_tags($$_GET[$command]));
}

function getAuthorizationHeader(){
    $header=null;

    if(function_exists('apache_request_headers')){
       $header= apache_request_headers();
    }else{
        $header=getallheaders();
    }
    if(isset($header['Authorization'])){
        return $header['Authorization'];
    }else if (isset($header['authorization'])){
        return $header['authorization'];
    }
}

function getBearerToken(){
    $header=getAuthorizationHeader();
    if (!empty($header) && preg_match('/Bearer\s(\S+)/', $header, $matches)) {
        return $matches[1];
    }
}

function uploadUserImage($imageRequest){
    global $messageError;
    $imageName=$_FILES[$imageRequest]['name'];
    $imageTmpName=$_FILES[$imageRequest]['tmp_name'];
    $imageSize=$_FILES[$imageRequest]['size'];
    $allowExtension=array('jpg','jpeg','png','heic');
    $ext=explode('.',$imageName);
    $ext=strtolower(end($ext));
    if(!in_array($ext,$allowExtension)&&!empty($imageName)){
        $messageError[]="File extension not allowed";
    }
    if($imageSize>2*MB){
        $messageError[]="File size is too large";
    }
    if(empty($messageError)){
        $newName=uniqid('',true).".".$ext;
        move_uploaded_file($imageTmpName,"../uploads/users/".$newName);
        return $newName;
    }else{
        return 'fail';
    }

    
}

function deleteOldImage($imageName){
    if(file_exists("../uploads/users/".$imageName)){
        unlink("../uploads/users/".$imageName);
    }

}

function uploadProductImages($imageRequest)
{
    if (!isset($_FILES[$imageRequest])) {
        return 'fail: no files provided';
    }

    if (!defined('MB')) {
        define('MB', 1024 * 1024); // Define MB if not defined
    }

    $allowExtension = ['jpg', 'jpeg', 'png', 'heic'];
    $uploadedFiles = [];
    $messageError = [];

    $files = $_FILES[$imageRequest];
    $fileCount = is_array($files['name']) ? count($files['name']) : 1;

    for ($i = 0; $i < $fileCount; $i++) {
        $imageName = is_array($files['name']) ? $files['name'][$i] : $files['name'];
        $imageTmpName = is_array($files['tmp_name']) ? $files['tmp_name'][$i] : $files['tmp_name'];
        $imageSize = is_array($files['size']) ? $files['size'][$i] : $files['size'];

        if (empty($imageName)) {
            continue; // Skip empty file slots.
        }

        $imageNameParts = explode('.', $imageName);
        $ext = strtolower(end($imageNameParts));

        if (!in_array($ext, $allowExtension)) {
            $messageError[] = "File extension not allowed for file $imageName";
            continue;
        }

        if ($imageSize > 2 * MB) {
            $messageError[] = "File size is too large for file $imageName";
            continue;
        }

        $newName = uniqid('', true) . "." . $ext;
        $uploadPath = "../uploads/products/" . $newName;

        if (!move_uploaded_file($imageTmpName, $uploadPath)) {
            $messageError[] = "Failed to move uploaded file for $imageName";
            continue;
        }

        $uploadedFiles[] = $newName;
    }

    if (!empty($messageError)) {
        // Optionally, change this to return or log specific errors.
        return 'fail: ' . implode('; ', $messageError);
    }

    return !empty($uploadedFiles) ? $uploadedFiles : 'fail: no files uploaded';
}




function deleteProductImage($imageName){
    if(file_exists("../uploads/products/".$imageName)){
        unlink("../uploads/products/".$imageName);
    }

}

// get user location
function getPublicIP() {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://api.ipify.org?format=json");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $response = curl_exec($ch);
    curl_close($ch);
    $data = json_decode($response, true);
    return $data['ip'];
}

function getLocationFromIP($ip) {
    $url = "http://ipinfo.io/{$ip}/json";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $response = curl_exec($ch);
    curl_close($ch);
    return json_decode($response, true);
}

function haversineGreatCircleDistance($latitudeFrom, $longitudeFrom, $latitudeTo, $longitudeTo, $earthRadius = 6371) {
    $latFrom = deg2rad($latitudeFrom);
    $lonFrom = deg2rad($longitudeFrom);
    $latTo = deg2rad($latitudeTo);
    $lonTo = deg2rad($longitudeTo);

    $latDelta = $latTo - $latFrom;
    $lonDelta = $lonTo - $lonFrom;

    $angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) +
      cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)));
    return $angle * $earthRadius;
}

function getDistance($userLocation){
    $clientIp = getPublicIP();
        error_log("Public IP: " . $clientIp);

        $locationData = getLocationFromIP($clientIp);
        error_log("Location Data: " . json_encode($locationData));
        if (isset($locationData['loc'])) {
            list($lat, $lon) = explode(',', $locationData['loc']);
            error_log("Coordinates: Lat = $lat, Lon = $lon");

            list($userLat, $userLon) = explode(',', $userLocation);
            error_log("User Coordinates: Lat = $userLat, Lon = $userLon");

            // Calculate distance
            $distance = haversineGreatCircleDistance($lat, $lon, $userLat, $userLon);
            error_log("Distance: " . $distance . " km");
            return $distance;
        } else {
            $response = array(
                'status' => 'Error',
                'status_message' => 'Location data not found'
            );
        }
}


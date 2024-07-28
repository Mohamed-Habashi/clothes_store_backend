<?php
include 'connect.php';

$image=$_FILES['images'];
echo '<pre>';
print_r($image);
echo '</pre>';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Multiple Files</title>
</head>
<body>
    <form action="index.php" method="post" enctype="multipart/form-data">
        <label for="fileUpload">Select files to upload:</label>
        <input type="file" name="images[]" id="fileUpload" multiple>
        <button type="submit">Upload</button>
    </form>
</body>
</html>
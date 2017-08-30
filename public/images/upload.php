<?php
//Allow Headers
//header('Access-Control-Allow-Origin: *');
print_r($_FILES);
   $new_image_name = "YEAH.jpg";
   move_uploaded_file($_FILES["image"]["tmp_name"], "/images/".$new_image_name);
?>
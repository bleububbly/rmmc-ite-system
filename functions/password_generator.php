<?php


function generatePassword(){


$characters =
"ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789";


$password="";


for($i=0;$i<8;$i++){


$password .= 

$characters[rand(0,strlen($characters)-1)];


}


return $password;


}


?>
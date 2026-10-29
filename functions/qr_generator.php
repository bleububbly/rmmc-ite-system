<?php

include "../libraries/phpqrcode/qrlib.php";


function generateQR($data, $filename){

    $folder="../qr_codes/students/";


    if(!is_dir($folder)){

        mkdir($folder,0777,true);

    }


    $path=$folder.$filename.".png";


    QRcode::png(
        $data,
        $path,
        QR_ECLEVEL_L,
        5
    );


    return $filename.".png";

}

?>
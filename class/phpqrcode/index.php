<?php    


    $PNG_TEMP_DIR = dirname(__FILE__).DIRECTORY_SEPARATOR.'temp'.DIRECTORY_SEPARATOR;
    
    $PNG_WEB_DIR = 'temp/';

    include "qrlib.php";    
    
	
    if (!file_exists($PNG_TEMP_DIR))
        mkdir($PNG_TEMP_DIR);
    
    
    $filename = $PNG_TEMP_DIR.'test.png';
    
    $errorCorrectionLevel = 'H';
	
    $matrixPointSize = min(max((int)5, 1), 10);
	$dato="15";
	$filename = $PNG_TEMP_DIR.'test'.md5($dato.'|'.$errorCorrectionLevel.'|'.$matrixPointSize).'.png';
    QRcode::png($dato, $filename, $errorCorrectionLevel, $matrixPointSize, 2);    
    
	
    echo '<img src="'.$PNG_WEB_DIR.basename($filename).'" /><hr/>';  
        

?> 
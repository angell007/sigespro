<?php

include('phpbarcode/BarcodeGenerator.php');
include('phpbarcode/BarcodeGeneratorPNG.php');
include('phpbarcode/BarcodeGeneratorSVG.php');
include('phpbarcode/BarcodeGeneratorJPG.php');
include('phpbarcode/BarcodeGeneratorHTML.php');

function generabarras($codigo){   
    $generator = new \Picqer\Barcode\BarcodeGeneratorJPG();
    return '<img src="data:image/jpg;base64,' . base64_encode($generator->getBarcode($codigo, $generator::TYPE_EAN_13, 1,20)) . '">';      
}


?>

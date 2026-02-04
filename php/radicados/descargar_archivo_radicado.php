<?php

$file_path = __DIR__.'/../../ARCHIVOS/RADICADOS/';
$doc = ( isset( $_REQUEST['doc'] ) ? $_REQUEST['doc'] : 0 );
// We will be outputting a PDF 
header('Content-Type: application/pdf'); 

header('Content-Disposition: attachment; filename="'.$doc.'"'); 

readfile($file_path.$doc);
 echo $imagepdf; 
?>
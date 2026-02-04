<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');


include_once('../class/class.complex.php');
include_once('../class/class.consulta.php');


$sel = ' Select * from Auditoria WHERE Estado_Archivo = 3 AND Archivo is not null ';

$oCon = new consulta();
$oCon->setQuery($sel);
$oCon->setTipo('Multiple');
$audList = $oCon->getData();
unset($oCon);
 //$filebase = '/home/sigesproph/public_html/IMAGENES/AUDITORIAS/';
foreach($audList as $aud ){
    $filebase = '/home/sigesproph/public_html/IMAGENES/AUDITORIAS/'.$aud['Id_Auditoria'].'/';
     echo '<br> --- <br> Aud: '.$aud['Id_Auditoria'].' :'.$aud['Archivo'].'  <br>';
     //auditoriaConError($aud);
    obtener_estructura_directorios($filebase,$aud['Id_Auditoria'],$aud['Archivo']);
}

function auditoriaConError($aud){
  
    
    $oItem = new complex('Auditoria','Id_Auditoria',$aud['Id_Auditoria']); 

    $filebase = '/home/sigesproph/public_html/IMAGENES/AUDITORIAS/'.$aud['Id_Auditoria'].'/'.$aud['Archivo'];

    echo $filebase;
    if( file_exists( $filebase ) ){
        echo '<br> '.$aud['Id_Auditoria'].'  correcto <br>';
        $oItem->Estado_Archivo = 1;
    }else{
        echo '<br> '.$aud['Id_Auditoria'].'  Error <br>';
        $oItem->Estado_Archivo = 2;
        
    }
    $oItem->save();
    unset($oItem);

  
}

function obtener_estructura_directorios($ruta, $idAuditoria,$archivoOrigin){
   
    // Se comprueba que realmente sea la ruta de un directorio
    if (is_dir($ruta)){
        // Abre un gestor de directorios para la ruta indicada
        $gestor = opendir($ruta);
        echo "<ul>";

        // Recorre todos los elementos del directorio
        while (($archivo = readdir($gestor)) !== false)  {
                
            $ruta_completa = $ruta  . $archivo;

            // Se muestran todos los archivos y carpetas excepto "." y ".."
            if ($archivo != "." && $archivo != ".." ) {
                // Si es un directorio se recorre recursivamente
                if (is_dir($ruta_completa)) {
                   
                    obtener_estructura_directorios($ruta_completa,$idAuditoria,$archivoOrigin);
                } else {
                   
                    $nombre =  str_replace(".pdf", "_.pdf", $archivoOrigin);
                    
                    if($nombre == $archivo ){
                        
                        $ruta_nueva= $ruta . $archivoOrigin;
                        
                        echo '<br>Aud: '.$idAuditoria.' :'.$archivoOrigin.'  <br>';
                        echo '<br>Ruta: '.$ruta_completa.'  <br>';
                        echo '<br>Ruta: '.$ruta_nueva.'  <br>';
                        
                        rename ($ruta_completa, $ruta_nueva);
                        echo  "<li>" . $archivo . "</li>" ;
                        $oItem = new complex( 'Auditoria' , 'Id_Auditoria' , $idAuditoria ); 
                        $oItem->Estado_Archivo = 3;
                        $oItem->save();
                        unset($oItem);
                         
                    }
               
                   
                }
            }
        }
        
        // Cierra el gestor de directorios
        closedir($gestor);
        echo "</ul>";
    } else {
        echo "No es una ruta de directorio valida<br/>";
     /*   $oItem = new complex('Auditoria','Id_Auditoria',$idAuditoria); 
        $oItem->Estado_Archivo = 1;
        $oItem->save();*/
        unset($oItem);
    }
}
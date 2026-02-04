<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');
require_once('../../class/html2pdf.class.php');
include_once('../../class/NumeroALetra.php');


include_once('../../class/class.querybasedatos.php');
include_once('../../class/class.http_response.php');
require_once('../../class/class.configuracion.php');
include_once('../../class/class.mipres.php');


$query = 'SELECT * FROM Dispensacion_Mipres ';

$oCon = new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$dispensacionesM = $oCon->getData();
unset($oCon);


foreach ($dispensacionesM as $key => $dispensacionM) {
    # code...
    $query = 'SELECT Id_Dispensacion FROM Dispensacion WHERE Id_Dispensacion_Mipres = '.$dispensacionM['Id_Dispensacion_Mipres'];
    $oCon = new consulta();

    $oCon->setQuery($query);
    $dispensacion = $oCon->getData();
    unset($oCon);

    $query = 'SELECT Id_Dispensacion_Mipres, '.$dispensacion['Id_Dispensacion'].' "Id_Dispensacion" ,
        SUM(IFNULL(IdProgramacion,0)) AS IdProgramacion,
        SUM(IFNULL(IdEntrega,0)) AS IdEntrega,
        SUM(IFNULL(IdReporteEntrega,0)) AS IdReporteEntrega
    FROM Producto_Dispensacion_Mipres
     WHERE Id_Dispensacion_Mipres = '.$dispensacionM['Id_Dispensacion_Mipres'];
    $oCon = new consulta();
    $oCon->setQuery($query);
    $producto = $oCon->getData();
    unset($oCon);


//cambiar estado
     if ($dispensacion && !$producto['IdEntrega'] && !$producto['IdReporteEntrega']) {
        echo $dispensacionM['Id_Dispensacion_Mipres'] .  ' - ' . $dispensacionM['Estado'] . ' - Update -> Radicado Programado <br>'; 
        $query= 'UPDATE Dispensacion_Mipres SET Estado="Radicado Programado" WHERE Id_Dispensacion_Mipres = '.$dispensacionM['Id_Dispensacion_Mipres'];

    }else if($dispensacion && $producto['IdEntrega'] && !$producto['IdReporteEntrega']){
        echo $dispensacionM['Id_Dispensacion_Mipres'] .  ' - ' . $dispensacionM['Estado'] . '- Update -> Entregado <br>'; 
        $query= 'UPDATE Dispensacion_Mipres SET Estado="Entregado" WHERE Id_Dispensacion_Mipres = '.$dispensacionM['Id_Dispensacion_Mipres'];

    }else if($dispensacion && $producto['IdEntrega'] && $producto['IdReporteEntrega']){
        echo $dispensacionM['Id_Dispensacion_Mipres'] .  ' - ' . $dispensacionM['Estado'] . '- Update -> Facturado <br>'; 
        $query= 'UPDATE Dispensacion_Mipres SET Estado="Facturado" WHERE Id_Dispensacion_Mipres = '.$dispensacionM['Id_Dispensacion_Mipres'];

    }else if(!$dispensacion && $producto['IdProgramacion']=='0' ){
        echo $dispensacionM['Id_Dispensacion_Mipres'] .  ' - ' . $dispensacionM['Estado'] . '- Update -> Pendiente <br>'; 
        $query= 'UPDATE Dispensacion_Mipres SET Estado="Pendiente" WHERE Id_Dispensacion_Mipres = '.$dispensacionM['Id_Dispensacion_Mipres'];

    }else if(!$dispensacion && $producto['IdProgramacion']!='0'){
        echo $dispensacionM['Id_Dispensacion_Mipres'] .  ' - ' . $dispensacionM['Estado'] . '- Update -> Programado <br>'; 
        $query= 'UPDATE Dispensacion_Mipres SET Estado="Programado" WHERE Id_Dispensacion_Mipres = '.$dispensacionM['Id_Dispensacion_Mipres'];
    }

    if ($query) {
       
    $oCon = new consulta(); 
    $oCon->setQuery($query);
    $oCon->createData();
    
    }

}

                
                
                
?>
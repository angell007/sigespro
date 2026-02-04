<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

include_once('../../class/class.consulta.php');

$id = isset($_REQUEST['Id_Lista_Ganancia']) ? $_REQUEST['Id_Lista_Ganancia'] : false;

$cum = isset($_REQUEST['Cum']) ? $_REQUEST['Cum'] : false;
$nombre = isset($_REQUEST['Nombre_Comercial']) ? $_REQUEST['Nombre_Comercial'] : false;

$currentsItems = isset($_REQUEST['currentsItems']) ? $_REQUEST['currentsItems'] : false;
$maxItems = isset($_REQUEST['maxItems']) ? $_REQUEST['maxItems'] : false;

if ($id) {
    # code...
    $cond = '';
    if ($cum) {
        # code...
        $cond='  AND PL.Cum = "'.$cum.'"';
    }

    if ($nombre) {
    
            $cond.=' AND P.Nombre_Comercial LIKE "%'.$nombre.'%"';
        
    }
    $query = ' SELECT PL.* , P.Nombre_Comercial,IFNULL(CONCAT(P.Principio_Activo," ",P.Presentacion," ",P.Concentracion," ", P.Cantidad," ", P.Unidad_Medida)
                     ,CONCAT(P.Nombre_Comercial," ",P.Laboratorio_Comercial)) as Nombre_Producto
            FROM  Producto_Lista_Ganancia PL
            INNER JOIN Producto P ON P.Codigo_Cum = PL.Cum
            WHERE  Id_Lista_Ganancia = '.$id.' '.$cond.'
            ORDER BY Ultima_Actualizacion DESC
            LIMIT '.$currentsItems.' , '.$maxItems.'
            ';
        
    $oCon = new consulta();
    $oCon->setQuery($query);
    $oCon->setTipo('Multiple');
    $result = $oCon->getData();
   
    echo json_encode($result);

}else{
    echo 'Se necesita el Id de la lista';
}
<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$id_remision = ( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : '' );


$query = 'SELECT R.*, R.Nombre_Origen  as Nombre_Bodega,
(CASE  
  WHEN  R.Tipo_Bodega="REFRIGERADOS" THEN "Si" 
  ELSE "No" 
END) as Temperatura
FROM Remision R
WHERE R.Id_Remision='.$id_remision ;

$oCon= new consulta();
$oCon->setQuery($query);
$remision = $oCon->getData();
unset($oCon); 

$query=ObtenerQuery($remision['Entrega_Pendientes'], $id_remision);      
$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$productos = $oCon->getData();
unset($oCon);

$productos_Pendientes=ObtenerProductosPendientes($remision['Entrega_Pendientes'], $id_remision);


$resultado["Datos"]=$remision;
$resultado["Productos"]=$productos;
if(count($productos_Pendientes)>0){
    $resultado["Productos_Pendientes"]=$productos_Pendientes;
}

echo json_encode($resultado);


function ObtenerQuery($tipo, $id_remision){

    $query='';
    if($tipo=='Si'){

        $query='SELECT PR.*, IFNULL(CONCAT(P.Principio_Activo," ",P.Presentacion," ",P.Concentracion," ", P.Cantidad," ", P.Unidad_Medida),CONCAT(P.Nombre_Comercial," ",P.Laboratorio_Comercial)) as Nombre_Producto, P.Codigo_Cum,
        P.Embalaje, P.Invima, CONCAT_WS(" / ",P.Laboratorio_Comercial, P.Laboratorio_Generico) AS Laboratorios, P.Nombre_Comercial,P.Presentacion, "Si" as Cumple, "Si" as Revisado, "1" AS Id_Causal_No_Conforme, 0 as Seleccionado,
        (PR.Cantidad-IFNULL((SELECT SUM(PD.Cantidad) FROM Producto_Descarga_Pendiente_Remision PD WHERE PD.Id_Producto_Remision=PR.Id_Producto_Remision),0)) as Cantidad, "" as Temperatura
        From Producto_Remision PR
        INNER JOIN Producto P
        ON PR.Id_Producto=P.Id_Producto
        WHERE PR.Id_Remision='.$id_remision.' HAVING Cantidad>0 ';
    }else{
       $query='SELECT PR.*, IFNULL(CONCAT(P.Principio_Activo," ",P.Presentacion," ",P.Concentracion," ", P.Cantidad," ", P.Unidad_Medida),CONCAT(P.Nombre_Comercial," ",P.Laboratorio_Comercial)) as Nombre_Producto, P.Codigo_Cum,0 as Seleccionado,
        P.Embalaje, P.Invima, CONCAT_WS(" / ",P.Laboratorio_Comercial, P.Laboratorio_Generico) AS Laboratorios, P.Nombre_Comercial,P.Presentacion, "Si" as Cumple, "Si" as Revisado, "1" AS Id_Causal_No_Conforme
        From Producto_Remision PR
        INNER JOIN Producto P
        ON PR.ID_Producto=P.Id_Producto
        WHERE PR.Id_Remision='.$id_remision;
      
    }

    return $query;
}

function ObtenerProductosPendientes($tipo, $id_remision){
    if($tipo=='Si'){
        $query = 'SELECT PR.Lote, CONCAT(P.Principio_Activo," ",P.Presentacion," ",P.Concentracion, P.Cantidad," ", P.Unidad_Medida, " ") AS Nombre_Producto,P.Nombre_Comercial, PR.Cantidad, PR.Cantidad, P.Laboratorio_Generico, P.Embalaje,
        (SELECT CONCAT(P.Id_Paciente," - ",P.Primer_Nombre," ",Primer_Apellido," ",P.Segundo_Apellido) FROM Paciente P WHERE P.Id_Paciente=PR.Id_Paciente ) as Paciente, (SELECT D.Codigo FROM Dispensacion D WHERE D.Id_Dispensacion=PR.Id_Dispensacion) as DIS
        FROM Producto_Descarga_Pendiente_Remision PR
        INNER JOIN Producto P ON PR.Id_Producto=P.Id_Producto
        WHERE PR.Id_Remision='.$id_remision.' ORDER BY Nombre_Comercial';
        
        $oCon= new consulta();
        $oCon->setQuery($query);
        $oCon->setTipo('Multiple');
        $productos_pendientes= $oCon->getData();
        unset($oCon);
       
    }else{
        $productos_pendientes=[];

    }
    return $productos_pendientes;
}
          
?>
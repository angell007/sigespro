<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$id_acta = ( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : '' );

$query = 'SELECT AR.*, P.Nombre as Nombre_Punto, R.Codigo as Codigo_Remision, R.Nombre_Origen
FROM Acta_Recepcion_Remision AR
INNER JOIN Punto_Dispensacion P
ON AR.Id_Punto_Dispensacion=P.Id_Punto_Dispensacion
INNER JOIN Remision R
ON AR.ID_Remision=R.Id_Remision
WHERE AR.Id_Acta_Recepcion_Remision='.$id_acta;

$oCon= new consulta();
$oCon->setQuery($query);
$datos = $oCon->getData();
unset($oCon);

$query2 = ObtenerQuery($datos['Entrega_Pendientes'],$id_acta);
      
$oCon= new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query2);
$productos_acta = $oCon->getData();
unset($oCon);

$resultado=[];

$resultado["Datos"]=$datos;
$resultado["Productos"]=$productos_acta;  



echo json_encode($resultado);

function ObtenerQuery($tipo, $id){

    $query='';
    if($tipo=='Si'){

        $query='SELECT PR.*, IFNULL(CONCAT(P.Principio_Activo," ",P.Presentacion," ",P.Concentracion," ", P.Cantidad," ", P.Unidad_Medida),CONCAT(P.Nombre_Comercial," ",P.Laboratorio_Comercial)) as Nombre_Producto, P.Codigo_Cum,
        P.Embalaje, P.Invima, CONCAT_WS(" / ",P.Laboratorio_Comercial, P.Laboratorio_Generico) AS Laboratorios, P.Nombre_Comercial,P.Presentacion, (PR.Cantidad-IFNULL((SELECT SUM(PD.Cantidad) FROM Producto_Descarga_Pendiente_Remision PD WHERE PD.Id_Producto_Remision=PR.Id_Producto_Remision),0)) as Cantidad,IFNULL((SELECT SUM(PD.Cantidad) FROM Producto_Descarga_Pendiente_Remision PD WHERE PD.Id_Producto_Remision=PR.Id_Producto_Remision),0 ) as Cantidad_Disp
        From Producto_Acta_Recepcion_Remision PR
        INNER JOIN Producto P
        ON PR.Id_Producto=P.Id_Producto
        WHERE PR.Id_Acta_Recepcion_Remision='.$id;
     
    }else{
       $query='SELECT P.*,IFNULL(CONCAT(PRD.Principio_Activo," ",PRD.Presentacion," ",PRD.Concentracion," ", PRD.Cantidad," ", PRD.Unidad_Medida),CONCAT(PRD.Nombre_Comercial," ",PRD.Laboratorio_Comercial)) as Nombre_Producto,PRD.Nombre_Comercial, PRD.Embalaje, PRD.Invima, CONCAT_WS(" / ", PRD.Laboratorio_Comercial, PRD.Laboratorio_Generico) AS Laboratorios
       FROM Producto_Acta_Recepcion_Remision P
       INNER JOIN Producto PRD
       ON P.Id_Producto=PRD.Id_Producto
       WHERE P.Id_Acta_Recepcion_Remision='.$id;
      
    }

    return $query;
}

?>
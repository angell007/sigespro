<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$id = ( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : '' );

$query = 'SELECT A.Id_Auditoria, A.Id_Tipo_Servicio, SA.Tipo_Soporte, P.Id_Paciente, CONCAT(P.Primer_Nombre," ",P.Segundo_Nombre," ",P.Primer_Apellido," ",P.Segundo_Apellido) as NombrePaciente, R.Nombre as NombreRegimen, 
P.EPS, N.Nombre as Nombre_Nivel, TS.Nombre as Nombre_Tipo_Servicio, SA.Archivo, SA.Id_Soporte_Auditoria
FROM Auditoria A
INNER JOIN Paciente P
ON A.Id_Paciente=P.Id_Paciente
INNER JOIN Tipo_Servicio TS
ON A.Id_Tipo_Servicio=TS.Id_Tipo_Servicio

INNER JOIN Regimen R
ON P.Id_Regimen=R.Id_Regimen
INNER JOIN Nivel N
ON P.Id_Nivel=N.Id_Nivel
INNER JOIN Soporte_Auditoria SA
ON A.Id_Auditoria=SA.Id_Auditoria
WHERE A.Id_Turnero is null AND A.Estado<>"Auditado" AND A.Id_Auditoria='.$id.'
ORDER BY SA.Id_Soporte_Auditoria ASC';


$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$auditoria = $oCon->getData();
unset($oCon);

$query2 = 'SELECT  CONCAT(P.Principio_Activo, " ",P.Presentacion, " ",P.Concentracion, " (",P.Nombre_Comercial,") ", P.Cantidad," ", P.Unidad_Medida, " ") as Nombre, 
AP.Cantidad_Formulada
            
FROM Producto_Auditoria AP
INNER JOIN Auditoria A
ON AP.Id_Auditoria=A.Id_Auditoria
INNER JOIN Producto P
ON AP.Id_Producto=P.Id_Producto
WHERE AP.Id_Auditoria='.$id ;

$oCon= new consulta();
$oCon->setQuery($query2);
$oCon->setTipo('Multiple');
$listaproducto = $oCon->getData();
unset($oCon);

$resultado["paciente"]=$auditoria;
$resultado["listaproductos"]=$listaproducto;

echo json_encode($resultado);
          
?>
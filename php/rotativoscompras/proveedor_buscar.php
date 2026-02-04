<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$condicion = '';

if (isset($_REQUEST['nombre']) && $_REQUEST['nombre'] != "") {
    $condicion .= "WHERE P.Nombre LIKE '%$_REQUEST[nombre]%'";
  }
if ($condicion != "") {
    if (isset($_REQUEST['nit']) && $_REQUEST['nit'] != "") {
        $condicion .= " AND P.Nit LIKE '%$_REQUEST[nit]%'";
    }
} else {
    if (isset($_REQUEST['nit']) && $_REQUEST['nit'] != "") {
        $condicion .= "WHERE P.Nit LIKE '%$_REQUEST[nit]%'";
    }
}
$query = 'SELECT P.Id_Proveedor, P.Nombre, "true" AS Desabilitado
           FROM Proveedor P '.$condicion;

$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$proveedorbucar = $oCon->getData();
unset($oCon);


echo json_encode($proveedorbucar);
          
?>
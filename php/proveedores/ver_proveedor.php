<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$id = ( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : '' );

$query = 'SELECT C.*,  IF(C.Tipo="Juridico", C.Nombre, CONCAT_WS(" ", C.Primer_Nombre, C.Segundo_Nombre, C.Primer_Apellido, C.Segundo_Apellido ) ) as Nombre, (SELECT M.Nombre FROM Municipio M WHERE M.Id_Municipio=C.Id_Municipio) as Ciudad, (SELECT CC.Descripcion  FROM Codigo_Ciiu CC WHERE CC.Id_Codigo_Ciiu=C.Id_Codigo_Ciiu ) as Actividad_Economica,  (SELECT CONCAT(PC.Codigo," ", PC.Nombre) FROM Plan_Cuentas PC WHERE Id_Plan_Cuentas=C.Id_Plan_Cuenta_Reteica) as Cuenta_Reteica  FROM Proveedor C WHERE C.Id_Proveedor='.$id ;

$oCon= new consulta();
$oCon->setQuery($query);
$vista['Cliente'] = $oCon->getData();
unset($oCon);

echo json_encode($vista);
<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

date_default_timezone_set('America/Bogota');

require_once('../../config/start.inc.php');
include_once('../../class/class.querybasedatos.php');
include_once('../../class/class.paginacion.php');
include_once('../../class/class.http_response.php');

$datos = (isset($_REQUEST['datos']) ? $_REQUEST['datos'] : '');
$datos = json_decode($datos, true);

$query = "SELECT Id_Dispensacion FROM Dispensacion WHERE Codigo = '$datos[Codigo]'";

$oCon = new consulta();
$oCon->setQuery($query);
$dis = $oCon->getData();
unset($oCon);


if($dis["Id_Dispensacion"]) {

    $query = "SELECT * 
              FROM Positiva_Data 
              WHERE Id_Dispensacion = '$dis[Id_Dispensacion]'";

    $oCon = new consulta();
    $oCon->setQuery($query);
    $positiva = $oCon->getData();
    unset($oCon);

    if($positiva) {
        $resultado['titulo'] = "Positiva";
        $resultado['mensaje'] = "Esta Dispensación se encuentra asociada en otra autorización.";
        $resultado['tipo'] = "error";

    } else {

        $oItem = new complex('Positiva_Data', 'id', $datos['numeroAutorizacion']);
        $posData = $oItem->getData();
        // unset($oItem);
        if ($posData['Estado'] != '') {
            $oItem->Id_Dispensacion = $dis['Id_Dispensacion'];
            $oItem->save();
            
            
            $query = "UPDATE Dispensacion D INNER JOIN Positiva_Data PD ON PD.Id_Dispensacion= D.Id_Dispensacion
                        SET D.Tipo_Entrega = if( PD.Pdomicilio = '1' , 'Domicilio', 'Fisico')
                        WHERE D.Id_Dispensacion=$dis[Id_Dispensacion] ";

            $oCon = new consulta();
            $oCon->setQuery($query);
            $data = $oCon->createData();
            unset($oCon);

            $resultado['titulo'] = "Positiva";
            $resultado['mensaje'] = "Dispensación asociada existosamente.";
            $resultado['tipo'] = "success";
        } else {
            $resultado['titulo'] = "Positiva";
            $resultado['mensaje'] = "Autorizacion Anulada anteriormente";
            $resultado['tipo'] = "error";
        }

    }

} else {
    $resultado['titulo'] = "Dispensación";
    $resultado['mensaje'] = "Dispensación no Existe.";
    $resultado['tipo'] = "error";
}

echo json_encode($resultado);

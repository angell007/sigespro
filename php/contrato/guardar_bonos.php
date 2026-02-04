<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php'); 
require_once('../../vendor/autoload.php');

use Carbon\Carbon as Carbon;

$datos = ( isset( $_REQUEST['datos'] ) ? $_REQUEST['datos'] : '' );

$datos = (array) json_decode($datos, true);

$sat= new Carbon($datos['Fechaf']);
$fechafin = $sat->endOfMonth()->format('Y-m-d');

if($datos['recurrente'] != 1)
{
    $sat= new Carbon($datos['Fechai']);
    $fechafin = $sat->endOfMonth()->format('Y-m-d');

}

$fechainicio =$datos['Fechai'].'-01';

$oItem = new complex("Bono_Funcionario","Id_Bono_Funcionario");   
$oItem->Id_Funcionario  = $datos['Id_Funcionario'];
$oItem->Id_Tipo_Detalle = $datos['Bono'];
$oItem->Descripcion     = $datos['Descripcion'];
$oItem->Valor           = $datos['Valor'];
$oItem->Fecha_Inicio    = $fechainicio;
$oItem->Fecha_Fin       = $fechafin;
$oItem->Estado          = "Activo";
$oItem->Recurrente      = $datos['recurrente'];
$oItem->save();
unset($oItem);

$resultado['title']   = "Bono Guardado";
$resultado['mensaje'] = "El Bono se Guardó de forma correcta";
$resultado['tipo']    = "success";

    
echo json_encode($resultado);
   
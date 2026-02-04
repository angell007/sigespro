<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$query2 = 'SELECT * FROM Tipo_Bono';
$oCon= new consulta();
$oCon->setQuery($query2);
$oCon->setTipo('Multiple');
$resultado["Tipos"] = $oCon->getData();
unset($oCon);

$id = ( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : '' );

$query2 = 'SELECT TP.Nombre NombreBono, DB.Nombre Detalle, DB.Id_Detalle_Bono 
            FROM Tipo_Bono TP
            INNER JOIN Detalle_Bono DB ON TP.Id_Tipo_Bono = DB.Id_Tipo_Bono
            WHERE TP.Id_Tipo_Bono = "'.$id.'"';
$oCon= new consulta();
$oCon->setQuery($query2);
$oCon->setTipo('Multiple');
$resultado["Listado"] = $oCon->getData();
unset($oCon);


$query = 'SELECT BF.Id_Bono_Funcionario, 
                 BF.Id_Funcionario, 
                 BF.Descripcion, 
                 BF.Valor as Valor, 
                 BF.Fecha_Inicio as FechaInicio, 
                 BF.Fecha_Fin as FechaFin, 
                 BF.Estado, 
                 TI.Tipo, 
                 TI.Nombre as Bono 
            FROM Bono_Funcionario BF
            INNER JOIN Tipo_Ingreso TI ON TI.Id_Tipo_Ingreso = BF.Id_Tipo_Detalle
            WHERE BF.Id_Funcionario =  "'.$id.'"
            ORDER BY BF.Id_Bono_Funcionario DESC';
$consult = new consulta();
$consult->setQuery($query);
$consult->setTipo('Multiple');
$resultado["ListadoBonos"] = $consult->getData();
unset($consult);

echo json_encode($resultado);


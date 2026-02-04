<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$id = ( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : '' );
$identificacion_Funcionario = ( isset( $_REQUEST['Identificacion_Funcionario'] ) ? $_REQUEST['Identificacion_Funcionario'] : '' );

$query = 'SELECT  NM.Id_Nomina_Funcionario, 
                  NEF.Identificacion_Funcionario, 
                  CONCAT(F.Nombres, " ", F.Apellidos) as Funcionario, 
                  NEF.Fecha_Reporte, 
                  NEF.Estado, 
                  NEF.Codigo_Nomina, 
                  NEF.Respuesta_Dian, 
                  NEF.Cune, 
                  NM.Total_Ingresos
            FROM Nomina_Electronica_Funcionario NEF
            INNER JOIN Funcionario F ON F.Identificacion_Funcionario = NEF.Identificacion_Funcionario
            left JOIN Nomina_Funcionario NM ON NM.Identificacion_Funcionario = NEF.Identificacion_Funcionario
            inner JOIN Nomina N ON N.Id_Nomina = NM.Id_Nomina
            WHERE F.Identificacion_Funcionario = '.$identificacion_Funcionario.' and N.Id_Nomina =  '.$id.'  ';   
       
$oCon= new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$funcionarios= $oCon->getData();
unset($oCon);

$resultado['Funcionarios']=$funcionarios;

echo json_encode($resultado);
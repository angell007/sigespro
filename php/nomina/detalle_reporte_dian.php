<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$id = ( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : '' );
$Identificacion_Funcionario = ( isset( $_REQUEST['Identificacion_Funcionario'] ) ? $_REQUEST['Identificacion_Funcionario'] : '' );

$condicion = '';

if (isset($_REQUEST['detalle'])) {
    $condicion .= " NEF.Identificacion_Funcionario = '$_REQUEST[Identificacion_Funcionario]' AND";
}
// else{
//     // $condicion .= " NEF.Estado = 'Exito' AND";
   
// }

/*
$query = 'SELECT  NM.Id_Nomina_Funcionario, 
                  NEF.Identificacion_Funcionario, 
                  CONCAT(F.Nombres, " ", F.Apellidos) as Funcionario, 
                  NEF.Fecha_Reporte, 
                  NEF.Estado, 
                  NEF.Codigo_Nomina, 
                  NEF.Respuesta_Dian, 
                  NM.Cune, 
                  NM.Total_Ingresos
            FROM Nomina_Electronica_Funcionario NEF
            INNER JOIN Funcionario F ON F.Identificacion_Funcionario = NEF.Identificacion_Funcionario
            INNER JOIN Nomina_Funcionario NM ON NM.Id_Nomina_Funcionario = NEF.Id_Nomina_Funcionario
            INNER JOIN Nomina N ON N.Id_Nomina = NM.Id_Nomina
            WHERE '.$condicion.' N.Id_Nomina = ' .$id ;   
 */
 
 $query = 'SELECT NM.Id_Nomina_Funcionario, 
                  NM.Identificacion_Funcionario, 
                  CONCAT(F.Nombres, " ", F.Apellidos) as Funcionario, 
                  NEF.Fecha_Reporte, 
                  NM.Estado_Nomina as Estado, 
                  NM.Codigo_Nomina, 
                  NEF.Respuesta_Dian, 
                  NM.Cune, 
                  N.Id_Nomina,
                  NM.Total_Ingresos
            FROM Nomina_Funcionario NM
            LEFT JOIN Nomina_Electronica_Funcionario NEF ON NM.Id_Nomina_Funcionario = NEF.Id_Nomina_Funcionario
            INNER JOIN Funcionario F ON F.Identificacion_Funcionario = NM.Identificacion_Funcionario    
            INNER JOIN Nomina N ON N.Id_Nomina = NM.Id_Nomina
            WHERE N.Id_Nomina = '.$id.' group by NM.Identificacion_Funcionario order by NM.Id_Nomina_Funcionario asc ';
       
$oCon= new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$funcionarios= $oCon->getData();
unset($oCon);

$query = 'SELECT COUNT(NF.Id_Nomina_Funcionario) AS TotalReportados
            FROM Nomina_Funcionario NF
            INNER JOIN Nomina N ON N.Id_Nomina = NF.Id_Nomina
            WHERE NF.Cune != " " and N.Id_Nomina = ' .$id ;   
$oCon= new consulta();
$oCon->setQuery($query);
$TotalReportados= $oCon->getData();
unset($oCon);

$query = 'SELECT COUNT(NF.Id_Nomina_Funcionario) AS TotalReportadosExito
            FROM Nomina_Funcionario NF
            INNER JOIN Nomina N ON N.Id_Nomina = NF.Id_Nomina
            WHERE NF.Estado_Nomina = "Exito" and N.Id_Nomina = ' .$id ;   
$oCon= new consulta();
$oCon->setQuery($query);
$TotalReportadosExito= $oCon->getData();
unset($oCon);


$query = 'SELECT COUNT(NF.Id_Nomina_Funcionario) AS TotalReportadosError
            FROM Nomina_Funcionario NF
            INNER JOIN Nomina N ON N.Id_Nomina = NF.Id_Nomina
            WHERE NF.Estado_Nomina = "Error" and N.Id_Nomina = ' .$id ;   
$oCon= new consulta();
$oCon->setQuery($query);
$TotalReportadosError= $oCon->getData();
unset($oCon);


$query = 'SELECT COUNT(NF.Id_Nomina_Funcionario) AS TotalReportadosPendiente
            FROM Nomina_Funcionario NF
            INNER JOIN Nomina N ON N.Id_Nomina = NF.Id_Nomina
            WHERE NF.Estado_Nomina = "Pendiente" and N.Id_Nomina = ' .$id ;   
$oCon= new consulta();
$oCon->setQuery($query);
$TotalReportadosPendiente= $oCon->getData();
unset($oCon);


$resultado['Funcionarios']=$funcionarios;
$resultado['TotalReportados']=$TotalReportados;
$resultado['TotalReportadosExito']=$TotalReportadosExito;
$resultado['TotalReportadosError']=$TotalReportadosError;
$resultado['TotalReportadosPendiente']=$TotalReportadosPendiente;


echo json_encode($resultado);
<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$condicion = '';

$id = ( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : '' );

if ($id) {
        $query = 'SELECT TB.Nombre, BF.Detalle, BF.Valor
             FROM bono_funcionario BF
             INNER JOIN tipo_bono TB ON BF.Id_Tipo_Bono = TB.Id_Tipo_Bono
             WHERE BF.Id_Funcionario = '.$id.'';
     
     $oCon= new consulta();
     $oCon->setQuery($query);
     $total = $oCon->getData();
     unset($oCon);     
}
     
if (isset($_REQUEST['cargo']) && $_REQUEST['cargo'] != "") {
 
        $condicion .= " AND F.Id_Cargo LIKE '%$_REQUEST[cargo]%'"; 
}
if (isset($_REQUEST['nom']) && $_REQUEST['nom'] != "") {

        $condicion .= " AND CONCAT(F.Nombres, ' ', F.Apellidos) LIKE '%$_REQUEST[nom]%'";   
}
if (isset($_REQUEST['grupo']) && $_REQUEST['grupo'] != "") {
   
        $condicion .= " AND F.Id_Grupo LIKE '%$_REQUEST[grupo]%'";      
}
if (isset($_REQUEST['depen']) && $_REQUEST['depen'] != "") {
 
        $condicion .= " AND F.Id_Dependencia LIKE '%$_REQUEST[depen]%'";
}

$query =  'SELECT CONCAT(F.Nombres," ",F.Apellidos) as Nombre, F.Identificacion_Funcionario, 
                                                            F.Direccion_Residencia,
                        (SELECT C.Nombre FROM Cargo C WHERE C.Id_Cargo = F.Id_Cargo) as
                            Cargo, 
                            F.Suspendido AS PreLiquidado, 
                            F.Imagen,
                            CF.Fecha_Inicio_Contrato,
                            CF.Fecha_Fin_Contrato,
                            F.Salario, 
                            CF.Estado AS Estado_Funcionario,
                            CF.Id_Contrato_Funcionario AS Id_Contrato
                        FROM Funcionario F 
                        INNER JOIN Contrato_Funcionario CF ON F.Identificacion_Funcionario = CF.Identificacion_Funcionario
                        WHERE CF.Estado = "Activo"
                        '.$condicion.' ORDER BY CF.Estado ';
$oCon= new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$funcionarios = $oCon->getData();
unset($oCon);

echo json_encode($funcionarios);
?>
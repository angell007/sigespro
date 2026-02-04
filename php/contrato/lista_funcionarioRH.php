<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$condicion = '';

if (isset($_REQUEST['cargo']) && $_REQUEST['cargo'] != "") {
    if($condicion==''){
        $condicion .= " WHERE F.Id_Cargo LIKE '%$_REQUEST[cargo]%'";
    }else{
        $condicion .= " AND F.Id_Cargo LIKE '%$_REQUEST[cargo]%'";
    }
    
} 
if (isset($_REQUEST['nom']) && $_REQUEST['nom'] != "") {
    if($condicion==''){
        $condicion .= " WHERE CONCAT(F.Nombres, ' ', F.Apellidos) LIKE '%$_REQUEST[nom]%'";
    }else{
        $condicion .= " AND CONCAT(F.Nombres, ' ', F.Apellidos) LIKE '%$_REQUEST[nom]%'";
    }
    
}
if (isset($_REQUEST['grupo']) && $_REQUEST['grupo'] != "") {
    if($condicion==''){
        $condicion .= " WHERE F.Id_Grupo LIKE '%$_REQUEST[grupo]%'";
    }else{
        $condicion .= " AND F.Id_Grupo LIKE '%$_REQUEST[grupo]%'";
    }
    
}
if (isset($_REQUEST['depen']) && $_REQUEST['depen'] != "") {
    if($condicion==''){
        $condicion .= " WHERE F.Id_Dependencia LIKE '%$_REQUEST[depen]%'";
    }else{
        $condicion .= " AND F.Id_Dependencia LIKE '%$_REQUEST[depen]%'";
    }
    
}
$estado = ( isset( $_REQUEST['est'] ) ? $_REQUEST['est'] : '' );
if ($estado!="") {    
    if($condicion==''){
        $condicion .= $estado == 'Liquidado' ? " WHERE F.Liquidado = 'SI'" : " WHERE F.Preliquidado = 'SI'";
    }else{
        $condicion .= $estado == 'Liquidado' ? " AND F.Liquidado = 'SI'" : " AND F.Preliquidado = 'SI'";
    }
}else{
    if($condicion==''){
        $condicion .= " WHERE CF.Estado = 'Activo' AND F.Preliquidado = 'NO'";
    }else{
        $condicion .= " AND CF.Estado = 'Activo' AND F.Preliquidado = 'NO'";
    }
}


$query='SELECT CONCAT(F.Nombres," ",F.Apellidos) as Nombre, F.Identificacion_Funcionario, 
                                                            F.Direccion_Residencia,
                     (SELECT C.Nombre FROM Cargo C WHERE C.Id_Cargo=F.Id_Cargo) as
                        Cargo, 
                        F.Imagen,
                        CF.Fecha_Inicio_Contrato,
                        CF.Fecha_Fin_Contrato,
                        F.Salario, 
                     (CASE
                        WHEN F.Liquidado    = "SI" THEN "Liquidado"
                        WHEN F.Preliquidado = "SI" THEN "Preliquidado" 
                        ELSE "Activo"
                     END) AS Estado_Funcionario,
                        LF.Id_Liquidacion_Funcionario,
                     IFNULL((SELECT PD.Id_Proceso_Disciplinario FROM Proceso_Disciplinario PD 
                             WHERE PD.Identificacion_Funcionario = F.Identificacion_Funcionario
                                                 AND PD.Estado   = "Abierto" LIMIT 1),0) as Procesos
                     FROM Funcionario F 
                     INNER JOIN Contrato_Funcionario CF    ON F.Identificacion_Funcionario = CF.Identificacion_Funcionario
                     LEFT  JOIN Liquidacion_Funcionario LF ON LF.Identificacion_Funcionario = F.Identificacion_Funcionario '.$condicion;
echo $query;
exit;
$oCon= new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$funcionarios = $oCon->getData();
unset($oCon);

echo json_encode($funcionarios);
?>
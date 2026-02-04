<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$year = ( isset( $_REQUEST['year'] ) ? $_REQUEST['year'] : '' );
$mes = ( isset( $_REQUEST['mes'] ) ? $_REQUEST['mes'] : '' );
$funcionario = ( isset( $_REQUEST['funcionario'] ) ? $_REQUEST['funcionario'] : '' );

$query = 'SELECT C.Nombre, MC.Valor_Medicamento, MC.Valor_Material
FROM Meta_Cliente MC
INNER JOIN Meta M ON MC.Id_Meta = M.Id_Meta
INNER JOIN Cliente C ON C.Id_Cliente = MC.Id_Cliente
WHERE M.Anio='.$year.' AND MC.Mes = '.((INT)$mes+1).'
';
       
$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$clientes = $oCon->getData();
unset($oCon);


$med=0;
$mat=0;
$i=-1;
foreach($clientes as $cli){$i++;
    $clientes[$i]["Nombre_Corto"]=substr($cli["Nombre"], 0, 15)."...";
    $clientes[$i]["Medi_P"]=number_format($cli["Valor_Medicamento"],0,",",".");
    $clientes[$i]["Mat_P"]=number_format($cli["Valor_Material"],0,",",".");
    $clientes[$i]["Total"]=number_format($cli["Valor_Material"]+$cli["Valor_Medicamento"],0,",",".");
    $med+=$cli["Valor_Medicamento"];
    $mat+=$cli["Valor_Material"];
}

$totales["Materiales_Num"]=$mat;
$totales["Medicamentos_Num"]=$med;
$totales["Materiales_P"]=number_format($mat,0,",",".");
$totales["Medicamentos_P"]=number_format($med,0,",",".");

$resultado["Totales"]=$totales;
$resultado["Clientes"]=$clientes;


echo json_encode($resultado);

?>
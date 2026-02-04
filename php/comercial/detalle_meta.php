<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');
include_once('../../class/class.utility.php');

$util=new Utility();

$condicion = '';

if (isset($_REQUEST['id']) && $_REQUEST['id'] != "") {
    if($condicion==''){
        $condicion .= " WHERE M.Id_Meta=$_REQUEST[id] ";
    }
    
}



$query = 'SELECT M.*,F.Imagen, UPPER(CONCAT(F.Nombres, " ", F.Apellidos)) as Funcionario, (SELECT Z.Nombre FROM Zona Z WHERE Z.Id_Zona=M.Id_Zona) as Zona,(SELECT SUM(MC.Valor_Medicamento) FROM Meta_Cliente MC WHERE MC.Id_Meta=M.Id_Meta) as Medicamento, (SELECT SUM(MC.Valor_Material) FROM Meta_Cliente MC WHERE MC.Id_Meta=M.Id_Meta) as Material
FROM Meta M
INNER JOIN Funcionario F
On M.Identificacion_Funcionario=F.Identificacion_Funcionario
'.$condicion;

$oCon= new consulta();
$oCon->setQuery($query);
$datos['Encabezado'] = $oCon->getData();
unset($oCon);


$query = ' SELECT M.Id_Cliente, (SELECT C.Nombre FROM Cliente C WHERE C.Id_Cliente=M.Id_Cliente) as Cliente, SUM(M.Valor_Medicamento) as Medicamento, SUM(M.Valor_Material) as Material FROM Meta_Cliente M 
'.$condicion.' GROUP BY Id_Cliente ';




$oCon= new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$clientes = $oCon->getData();
unset($oCon);
$i=-1;
foreach ($clientes as  $value) {$i++;
     $query = ' SELECT M.* FROM Meta_Cliente M 
    '.$condicion.' AND  M.Id_Cliente='.$value['Id_Cliente'].' ORDER BY Mes ASC';
 
    $oCon= new consulta();
    $oCon->setTipo('Multiple');
    $oCon->setQuery($query);
    $meses = $oCon->getData();
    unset($oCon);

    $j=-1;
    foreach ($meses as $item) {$j++;
      $meses[$j]['Nombre_Mes']=$util->ObtenerMesString($item['Mes']);
    }

    $clientes[$i]['Meses']=$meses;
    


}

$datos['Clientes']=$clientes;


echo json_encode($datos);

?>
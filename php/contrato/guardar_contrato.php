<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$mod = ( isset( $_REQUEST['modulo'] ) ? $_REQUEST['modulo'] : '' );
$datos = ( isset( $_REQUEST['datos'] ) ? $_REQUEST['datos'] : '' );
$Id_Cliente = ( isset( $_REQUEST['Id_Cliente'] ) ? $_REQUEST['Id_Cliente'] : '' );
$soportes = ( isset( $_REQUEST['soportes'] ) ? $_REQUEST['soportes'] : '' );
$datos = (array) json_decode($datos);

$respuesta=[];
$query_insert=[];
$errores='';

if(isset($datos["id"])&&$datos["id"] != ""){
    $oItem = new complex($mod,"Id_".$mod,$datos["id"]);	
}else{
	$oItem = new complex($mod,"Id_".$mod);
}

foreach($datos as $index=>$value) {
    $oItem->$index=$value;  
}

$oItem->Id_Cliente = $Id_Cliente;
$oItem->save();
$id_cliente = $oItem->getId();
unset($oItem);

$resultado['Titulo'] = "Operación Exitosa";
$resultado['Mensaje'] = "Cantidades Agregadas Correctamente";
$resultado['Tipo'] = "success";

echo json_encode($resultado);















// $oLista = new lista($mod);
// $lista= $oLista->getlist();
// unset($oLista);

// echo json_encode($lista);




// if (!empty($_FILES['soportes']['name'])){

//     $handle = fopen($_FILES['soportes']['tmp_name'], "r");
    
//     if($handle){
//         $i=0;
//         while (($data = fgetcsv($handle, 1000, ";")) !== FALSE) { $i++;
        
        
//             $query_insert[]="(null,$id_cliente, '$data[0]',$data[1], ".number_format($data[2],0,'','').", $data[3])";
//         }
        
//     }
//     // $oCon= new consulta();
//     // $oCon->setQuery("INSERT INTO Producto_Contrato VALUES ".implode(",",$query_insert));
//     // $consultas = $oCon->createData();
//     // unset($oCon);
// }
        
?>

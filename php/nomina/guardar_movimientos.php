<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
require_once('../../class/class.configuracion.php');
include_once('../../class/class.consulta.php');

$datos = ( isset( $_REQUEST['datos'] ) ? $_REQUEST['datos'] : '' );
$datos = (array) json_decode($datos,true); 

$final =[];

$i=-1;
foreach($datos as $clave => $valor){
    if(strpos($clave, "Id_Movimiento_Funcionario")!==false){ $i++;
        $final[$i]["Id_Movimiento_Funcionario"]=$valor;
    }elseif(strpos($clave, "Identificacion_Funcionario")!==false){
        $final[$i]["Identificacion_Funcionario"]=$valor;
    }elseif(strpos($clave, "Tipos")!==false){
        $final[$i]["Tipo"]=$valor; 
    }elseif(strpos($clave, "Id_Tipo")!==false){
        $final[$i]["Id_Tipo"]=$valor;
    }elseif(strpos($clave, "Quincena")!==false){
        $final[$i]["Quincena"]=$valor;
    }elseif(strpos($clave, "Valor")!==false){
        $final[$i]["Valor"]=$valor;
    }
}
$bandera = false;

//elimino prestamo y libranza si existieran 
unset($final[6],$final[7]);
foreach($final as $item){
    if($item["Id_Movimiento_Funcionario"]!=""){
        $oItem = new complex("Movimiento_Funcionario","Id_Movimiento_Funcionario",$item["Id_Movimiento_Funcionario"]);
        foreach($item as $index=>$value) {
            if($index!="Id_Movimiento_Funcionario"){   
                $oItem->$index=(STRING)$value; 
            }
        }
        $oItem->save();
        unset($oItem);
        $bandera= true;
    }elseif($item["Valor"]!=0){
        unset($item["Id_Movimiento_Funcionario"]);
        $oItem = new complex("Movimiento_Funcionario","Id_Movimiento_Funcionario");
        foreach($item as $index=>$value) {
            $oItem->$index=$value;
        }
        $oItem->save();
        unset($oItem);
        $bandera= true;
    }
}

if ($bandera) {
  $resultado['Mensaje'] = "Se ha guardadon correctamente los Movimiento";
  $resultado['Tipo'] = "success";
  $resultado['Titulo'] = "Exitoso";
} else {
  $resultado['Mensaje'] = "No se encontraron movimientos para guardar, por favor revise e intente nuevamente";
  $resultado['Tipo'] = "error";
  $resultado['Titulo'] = "Error";
}

echo json_encode($resultado);

?>
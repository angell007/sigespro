<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');

$datos = ( isset( $_REQUEST['datos'] ) ? $_REQUEST['datos'] : '' );
$soportes = ( isset( $_REQUEST['soportes'] ) ? $_REQUEST['soportes'] : '' );
$id_funcionario = ( isset( $_REQUEST['id_funcionario'] ) ? $_REQUEST['id_funcionario'] : '' );
$productos = ( isset( $_REQUEST['productos'] ) ? $_REQUEST['productos'] : '' );
$punto = ( isset( $_REQUEST['punto'] ) ? $_REQUEST['punto'] : '' );

$datos = (array) json_decode($datos , true);
$soportes=(array) json_decode($soportes , true);
$productos=(array) json_decode($productos , true);


$datos["Funcionario_Preauditoria"]=$id_funcionario;
$datos["Punto_Pre_Auditoria"]=$punto;
$datos["Fecha_Preauditoria"]=date("Y-m-d H:i:s");
if(isset($datos["Tipo_Servicio"])&&$datos["Tipo_Servicio"]==""){
    $datos["Id_Tipo_Servicio"]="4";
}else{
$datos["Id_Tipo_Servicio"]=$datos["Tipo_Servicio"];
    
}
$datos["Id_Paciente"]=$datos["Numero_Documento"];
$datos["Nombre_Tipo_Servicio"]=$datos["Tipo"];


$oItem = new complex("Auditoria","Id_Auditoria");
foreach($datos as $index=>$value) {
    $oItem->$index=$value;
}
$oItem->save();
$id_auditoria = $oItem->getId();
unset($oItem);


$i=-1;
if (!file_exists( $MY_FILE.'IMAGENES/AUDITORIAS/'.$id_auditoria)) {
    mkdir($MY_FILE.'IMAGENES/AUDITORIAS/'.$id_auditoria, 0777, true);
}
foreach($soportes as $soporte){ $i++;

    if (!empty($_FILES['Archivo'.$i]['name'])){
    	$posicion1 = strrpos($_FILES['Archivo'.$i]['name'],'.')+1;
    	$extension1 =  substr($_FILES['Archivo'.$i]['name'],$posicion1);
    	$extension1 =  strtolower($extension1);
    	$_filename1 = uniqid() . "." . $extension1;
    	$_file1 = $MY_FILE . "IMAGENES/AUDITORIAS/".$id_auditoria."/" . $_filename1;
    	
    	$subido1 = move_uploaded_file($_FILES['Archivo'.$i]['tmp_name'], $_file1);
    		if ($subido1){		
    			@chmod ( $_file1, 0777 );
    			$soporte["Archivo"] = $_filename1;
    		} 
    }
    $soporte["Id_Auditoria"]=$id_auditoria;
    $oItem = new complex('Soporte_Auditoria',"Id_Soporte_Auditoria");
    foreach($soporte as $index=>$value) {
        $oItem->$index=$value;
    }
   $oItem->save();
    unset($oItem);
}

unset($productos[count($productos)-1]);

foreach($productos as $producto){$i++;
    /** Modifcado el 20 de Julio 2020 Augusto - Cambia Inventario por Inventario_Nuevo */
    $oItem = new complex('Producto_Auditoria',"Id_Producto_Auditoria");
    $oItem->Id_Producto = $producto["Id_Producto"];
    $oItem->Id_Inventario_Nuevo = $producto["Id_Inventario_Nuevo"];
    $oItem->Cantidad_Formulada = $producto["Cantidad_Formulada"];
    $oItem->Cum = $producto["Cum"];
    $oItem->Id_Auditoria=$id_auditoria;
    $oItem->Nombre = $producto["producto"];
    
    $oItem->save();
    unset($oItem);
}

$resultado['mensaje'] = "¡Pre-Auditoria Guardado Exitosamente!";
$resultado['tipo'] = "success";

echo json_encode($resultado);

?>
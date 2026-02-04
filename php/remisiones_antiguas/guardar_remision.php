<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
require_once('../../class/class.configuracion.php');
include_once('../../class/class.consulta.php');
require_once('../../class/class.qr.php');  /* AGREGAR ESTA CLASE PARA GENERAR QR */

$configuracion = new Configuracion();
$mod = ( isset( $_REQUEST['modulo'] ) ? $_REQUEST['modulo'] : '' );
$datos = ( isset( $_REQUEST['datos'] ) ? $_REQUEST['datos'] : '' );
$productos = ( isset( $_REQUEST['productos'] ) ? $_REQUEST['productos'] : '' );
$codigo = ( isset( $_REQUEST['codigo'] ) ? $_REQUEST['codigo'] : '' );
 
$datos = (array) json_decode($datos);
$productos = (array) json_decode($productos , true);

/*$oItem = new complex('Configuracion','Id_Configuracion',1);
$nc = $oItem->getData();
$oItem->Remision=$oItem->Remision+1;$oItem->save();
$num_remison=$nc["Remision"];
unset($oItem);*/
//var_dump($datos);
$cod = $configuracion->Consecutivo('Remision_Antigua');
$datos['Codigo']=$cod;

if($datos['Tipo']=="Cliente"){
      
    $cliente=explode("-",$datos['Id_Cliente']);
    $datos['Id_Destino']=$cliente[1];
    $datos['Tipo_Destino']="Cliente";
   
    
    $tipo=explode("-",$datos['Lista_Ganancia']);
   
    if($tipo[0]=="L"){
        $datos['Tipo_Lista']="Lista_Ganancia";
        $datos['Id_Lista']=$tipo[1];
    }elseif($tipo[0]=="C"){
        $datos['Tipo_Lista']="Contrato";
        $datos['Id_Lista']=$tipo[1];
    }

    
}elseif($datos['Tipo']=="Interna"){
    $destino=explode("-",$datos['Id_Destino']);
    
    $datos['Id_Destino']=$destino[1];
	if($destino[0]=="P"){
	    $datos['Tipo_Destino']="Punto_Dispensacion";
	}elseif($destino[0]=="B"){
	    $datos['Tipo_Destino']="Bodega";
	}	
}

$origen=explode("-",$datos['Id_Origen']);
$datos['Id_Origen']=$origen[1];
if($origen[0]=="B"){
    $datos['Tipo_Origen']="Bodega";
}elseif($origen[0]=="P"){
    $datos['Tipo_Origen']="Punto_Dispensacion";
}
if($origen[0]=="B" && $datos['Id_Origen']==2){
    $datos['Tipo_Bodega']="MATERIALES";
}
$datos['Estado']="Pendiente";
$datos['Estado_Alistamiento']=0;

$oItem = new complex($mod,"Id_".$mod);
foreach($datos as $index=>$value) {
    $oItem->$index=$value;
}
$oItem->save();
$id_remision = $oItem->getId();
unset($oItem);

/* AQUI GENERA QR */
$qr = generarqr('remision',$id_remision,'/IMAGENES/QR/');
$oItem = new complex("Remision","Id_Remision",$id_remision);
$oItem->Codigo_Qr=$qr;
$oItem->save();
unset($oItem);
/* HASTA AQUI GENERA QR */
unset($productos[count($productos)-1]);

foreach($productos as $producto){$i++;
		if($producto['Descontar']=="Si"){
		
           	//var_dump($producto['Id_Inventario']);
           	
           	$oItem = new complex('Inventario',"Id_Inventario",$producto['Id_Inventario']);
            $oItem->Cantidad_Leo=$producto['Cantidad'];
            $oItem->save();
            unset($oItem);
        
	        }
                $oItem = new complex('Producto_Remision_Antigua',"Producto_Remision_Antigua");
                $oItem->Id_Remision=$id_remision;
                $oItem->Id_Inventario = $producto["Id_Inventario"];
                $oItem->Lote = $producto["Lote"];
                //$oItem->Fecha_Vencimiento = $lote["Fecha_Vencimiento"];
                $oItem->Cantidad = $producto["Cantidad"];
                $oItem->Id_Producto = $producto["Id_Producto"];
               // $oItem->Nombre_Producto = $producto["Nombre_Producto"];	                   
                $oItem->Cantidad = $producto["Cantidad"];
                $oItem->Precio = $producto["Precio_Venta"];
                $oItem->Descuento = $producto["Descuento"];
                $oItem->Impuesto = $producto["Impuesto"];
                $oItem->Subtotal = $producto["Subtotal"];
                $oItem->save();
                unset($oItem);
	            
        }
        
        
$oItem = new complex('Actividad_Remision_Antigua',"Id_Actividad_Remision_Antigua");
$oItem->Id_Remision=$id_remision;
$oItem->Identificacion_Funcionario=$datos["Identificacion_Funcionario"];
$oItem->Detalles="Se creo la remision con codigo ".$cod;
$oItem->save();
unset($oItem);
  

if($id_remision != ""){
	
	    $resultado['mensaje'] = "Se ha guardado correctamente la Remision con codigo: <b>".$cod."</b><br>";
	    $resultado['tipo'] = "success";
}else{
    $resultado['mensaje'] = "Ha ocurrido un error guardando la informacion, por favor verifique";
    $resultado['tipo'] = "error";
}

echo json_encode($resultado);

?>

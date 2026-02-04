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
$productos = ( isset( $_REQUEST['productos'] ) ? $_REQUEST['productos'] : '' );
$func = ( isset( $_REQUEST['funcionario'] ) ? $_REQUEST['funcionario'] : '' );
$celular_paciente = ( isset( $_REQUEST['celular_paciente'] ) ? $_REQUEST['celular_paciente'] : '' );

$datos = (array) json_decode($datos , true);
$productos = (array) json_decode($productos , true);



## ACTUALIZAR CELULAR PACIENTE
if ($celular_paciente != '' && $celular_paciente != null) {
    updateCelularPaciente($datos['Numero_Documento'], $celular_paciente);
}

// var_dump($productos);

$cantidadentregada=0;

$imagen=$datos["Firma_Digital"];

if ($imagen != "") {
    list($type, $imagen) = explode(';', $imagen);
    list(, $imagen)      = explode(',', $imagen);
    $imagen = base64_decode($imagen);

    $fot="firma".uniqid().".jpg";
    $archi=$MY_FILE . "IMAGENES/FIRMAS-DIS/".$fot;
    file_put_contents($archi, $imagen);
    chmod($archi, 0644);
}

$productos_no_entregados = [];
$id_producto='';
$cantidadE=0;

foreach($productos as $producto){
    $cantidadentregada=$producto["Cantidad_Entregada"]+$producto["Entregar_Faltante"];

    if (validarEntregaProducto($producto["Entregar_Faltante"],$producto["IdInventario"])) {
        $oItem = new complex('Producto_Dispensacion',"Id_Producto_Dispensacion",$producto["Id_Producto_Dispensacion"]); 
        $comparar=$oItem->getData();
       // $cantidad_faltante=(INT)$comparar['Entregar_Faltante']-$producto["Entregar_Faltante"];
        if (isset($producto['Semejante']) && $producto['Semejante']!= '') { // Si viene un producto de otro laboratorio
            if ($comparar["Cantidad_Entregada"] == 0) { // Cambiando el producto por el del otro laboratorio
                $oItem = new complex('Producto_Dispensacion',"Id_Producto_Dispensacion",$producto["Id_Producto_Dispensacion"]);
                $oItem->Id_Producto=$producto["Id_Producto"];
                $oItem->Id_Inventario=$producto["IdInventario"];
                $oItem->Cum=$producto["Cum"];
                $oItem->Lote=$producto["Lote"];
                $oItem->Entregar_Faltante=$producto["Entregar_Faltante"];
                $oItem->Cantidad_Entregada=number_format($producto["Entregar_Faltante"],0,"","");
                $oItem->save();
                $id_producto_pendiente=$oItem->getId();
                unset($oItem);
            } else {
                $oItem = new complex('Producto_Dispensacion',"Id_Producto_Dispensacion",$producto["Id_Producto_Dispensacion"]); // Modificando los datos del producto para poder registrar un producto con el otro laboratorio y así cumplir entregar el pendiente completo.
                $oItem->Entregar_Faltante=0;
                $oItem->Cantidad_Formulada=number_format($comparar["Cantidad_Entregada"],0,"","");
                $autorizacion=$oItem->Numero_Autorizacion;
                $fecha_autorizacion=$oItem->Fecha_Autorizacion;
                $prescripcion=$oItem->Numero_Prescripcion;
                $oItem->save();
                unset($oItem);
                
               

                $cantidad_pendiente=$comparar['Cantidad_Formulada']-$comparar['Cantidad_Entregada'];
                $oItem = new complex('Producto_Dispensacion',"Id_Producto_Dispensacion");
                $oItem->Id_Dispensacion=$producto["Id_Dispensacion"];
                $oItem->Id_Producto=$producto["Id_Producto"];
                $oItem->Id_Inventario=$producto["IdInventario"];
                $oItem->Cum=$producto["Cum"];
                $oItem->Lote=$producto["Lote"];
                $oItem->Entregar_Faltante=$producto["Entregar_Faltante"];
                $oItem->Cantidad_Entregada=$producto["Entregar_Faltante"];
                $oItem->Cantidad_Formulada=$cantidad_pendiente;
                $oItem->Numero_Autorizacion=$autorizacion;
                $oItem->Fecha_Autorizacion=$fecha_autorizacion;
                $oItem->Numero_Prescripcion=$prescripcion;
                $oItem->save();
                $id_producto_pendiente=$oItem->getId();
                unset($oItem);
            }
        } else {
            
            /* $oItem = new complex('Producto_Dispensacion',"Id_Producto_Dispensacion",$producto["Id_Producto_Dispensacion"]);
            $oItem->Entregar_Faltante=$producto["Entregar_Faltante"];
            $oItem->Cantidad_Entregada=number_format($cantidadentregada,0,"","");
            //$oItem->Id_Producto=$producto["Id_Producto"];
            $oItem->Id_Inventario=$producto["IdInventario"];
            //$oItem->Cum=$producto["Cum"];
            $oItem->Lote=$producto["Lote"];
            $oItem->save();
            unset($oItem); */
            if($id_producto!=$producto["Id_Producto"]){
                $id_producto=$producto["Id_Producto"];
                $cantidadE=$producto["Cantidad_Entregada"];
            }else{
                $cantidadE=$producto["Entregar_Faltante"];
            }
           
            if ($comparar['Cantidad_Entregada'] == 0) { 

                $oItem = new complex('Producto_Dispensacion',"Id_Producto_Dispensacion",$producto["Id_Producto_Dispensacion"]);
                $oItem->Id_Producto=$producto["Id_Producto"];
                $oItem->Id_Inventario=$producto["IdInventario"];
                $oItem->Cum=$producto["Cum"];
                $oItem->Lote=$producto["Lote"];
                $oItem->Entregar_Faltante=$producto["Entregar_Faltante"];
                $oItem->Cantidad_Entregada=number_format($producto["Entregar_Faltante"],0,"","");
                $oItem->save();
                $id_producto_pendiente=$oItem->getId();
                unset($oItem);
            } else {
                $oItem = new complex('Producto_Dispensacion',"Id_Producto_Dispensacion",$producto["Id_Producto_Dispensacion"]); // Modificando los datos del producto para poder registrar un producto con el otro laboratorio y así cumplir entregar el pendiente completo.
                $oItem->Entregar_Faltante=0;
                $cantidad_formulada1= $oItem->Cantidad_Formulada;
                $oItem->Cantidad_Formulada=number_format($comparar["Cantidad_Entregada"],0,"","");;
                $autorizacion=$oItem->Numero_Autorizacion;
                $fecha_autorizacion=$oItem->Fecha_Autorizacion;
                $prescripcion=$oItem->Numero_Prescripcion;
                $oItem->save();
                unset($oItem);

                $cantidad_pendiente=$comparar['Cantidad_Formulada']-$comparar['Cantidad_Entregada'];
                
                $oItem = new complex('Producto_Dispensacion',"Id_Producto_Dispensacion");
                $oItem->Id_Dispensacion=$producto["Id_Dispensacion"];
                $oItem->Id_Producto=$producto["Id_Producto"];
                $oItem->Id_Inventario=$producto["IdInventario"];
                $oItem->Cum=$producto["Cum"];
                $oItem->Lote=$producto["Lote"];
                $oItem->Entregar_Faltante=$producto["Entregar_Faltante"];
                $oItem->Cantidad_Entregada=$producto["Entregar_Faltante"];
                $oItem->Cantidad_Formulada=$cantidad_pendiente;
                $oItem->Numero_Autorizacion=$autorizacion;
                $oItem->Fecha_Autorizacion=$fecha_autorizacion;
                $oItem->Numero_Prescripcion=$prescripcion;
                $oItem->save();
                $id_producto_pendiente=$oItem->getId();
                unset($oItem);
            }
        }
    
        $oItem = new complex('Producto_Dispensacion_Pendiente',"Id_Producto_Dispensacion_Pendiente");
        $oItem->Id_Producto_Dispensacion=$id_producto_pendiente;
        $oItem->Cantidad_Entregada=$producto["Cantidad_Entregada"];
        $oItem->Cantidad_Pendiente=$producto["Cantidad_Pendiente"];
        $oItem->Entregar_Faltante=$producto["Entregar_Faltante"];
        $oItem->save();
        unset($oItem);
        
        $oItem = new complex('Inventario',"Id_Inventario", $producto['IdInventario']);
        $cantidad_entregada = number_format($producto["Entregar_Faltante"],0,"","");
        $inv_act=$oItem->getData();
        $cantidad_inventario= number_format((int) $inv_act["Cantidad"],0,"","");
        $cantidad_final = $cantidad_inventario- $cantidad_entregada;
        $oItem->Cantidad = number_format($cantidad_final,0,"","");
        $oItem->save();
        unset($oItem);
    
        $oItem = new complex('Dispensacion',"Id_Dispensacion", $producto['Id_Dispensacion']);
        $pendientes = $oItem->Pendientes - $producto["Entregar_Faltante"];
        $entregados = $oItem->Productos_Entregados + $producto["Entregar_Faltante"];
        if ($pendientes >= 0) {
            $oItem->Pendientes = number_format($pendientes,0,"","");
            $oItem->Productos_Entregados = number_format($entregados,0,"","");
        } else { // Evitar por si cae en negativo.
            $oItem->Pendientes = 0;
            $oItem->Productos_Entregados = number_format($entregados,0,"","");
        }
        $oItem->save();
        unset($oItem);
        
        $oItem = new complex('Actividades_Dispensacion',"Id_Actividad_Dispensacion");
        $oItem->Id_Dispensacion = $producto["Id_Dispensacion"];
        $oItem->Identificacion_Funcionario = $func;
        $oItem->Detalle = "Se entrego la dispensacion pendiente. Producto: $producto[producto] - Cantidad: $producto[Entregar_Faltante]";
        $oItem->Firma_Reclamante = $fot;
        $oItem->Estado = "Creado";
        $oItem->save();
        unset($oItem);

        if (!empty($_FILES['acta']['name'])){ // Archivo de la Acta de Entrega.
            $posicion1 = strrpos($_FILES['acta']['name'],'.')+1;
            $extension1 =  substr($_FILES['acta']['name'],$posicion1);
            $extension1 =  strtolower($extension1);
            $_filename1 = uniqid() . "." . $extension1;
            $_file1 = $MY_FILE . "ARCHIVOS/DISPENSACION/ACTAS_ENTREGAS/" . $_filename1;
            
            $subido1 = move_uploaded_file($_FILES['acta']['tmp_name'], $_file1);
                if ($subido1){      
                    @chmod ( $_file1, 0777 );
                    $nombre_archivo = $_filename1;
                    $oItem = new complex('Dispensacion','Id_Dispensacion',$producto["Id_Dispensacion"]);
                    $oItem->Acta_Entrega = $nombre_archivo;
                    $oItem->save();
                    unset($oItem);
                } 
        }
    } else {
        $productos_no_entregados[] = $producto;
    }


}

if(json_encode($lista)){

    if (count($productos_no_entregados) == count($productos)) {
        $resultado['mensaje'] = "No se entregó ningun pendiente debido a que todos los productos seleccionados no tienen inventario.";
        $resultado['tipo'] = "warning";
        $resultado['titulo'] = "Productos no entregados";
        $resultado['productos_no_entregados'] = $productos_no_entregados;
        $resultado['status'] = 3;
    } elseif (getStatus() == 1) {
        $resultado['mensaje'] = "Se ha guardado correctamente la dispensación pendiente.";
        $resultado['tipo'] = "success";
        $resultado['titulo'] = "Dispensación Entregada Correctamente";
        $resultado['productos_no_entregados'] = $productos_no_entregados;
        $resultado['status'] = 1;
    } else {
        $resultado['mensaje'] = "Se ha guardado correctamente la dispensación pendiente.";
        $resultado['tipo'] = "success";
        $resultado['titulo'] = "Dispensación Entregada Correctamente";
        $resultado['productos_no_entregados'] = $productos_no_entregados;
        $resultado['status'] = 2;
    }

}else{
    $resultado['mensaje'] = "ha ocurrido un error guardando la información, por favor verifique";
    $resultado['tipo'] = "error";
    $resultado['titulo'] = "Error Entregado Dispensación";
}

echo json_encode($resultado);

function cantidadInventario($id_inventario) {

    $query = "SELECT (Cantidad-Cantidad_Apartada-Cantidad_Seleccionada) AS Cantidad FROM Inventario WHERE Id_Inventario = $id_inventario";
    
    $oCon = new consulta();
    $oCon->setQuery($query);
    $cantidad = $oCon->getData()['Cantidad'];
    unset($oCon);

    return $cantidad;
    
}

function validarEntregaProducto($cant_entrega, $id_inventario){

    $cantidad_inventario = cantidadInventario($id_inventario);

    if (($cantidad_inventario-$cant_entrega) >= 0) {
        return true;
    }

    return false;
    
}

function updateCelularPaciente($paciente, $celular)
{
    $oItem = new complex('Paciente','Id_Paciente',$paciente,'Varchar');
    $oItem->Telefono = $celular;
    $oItem->save();
    unset($oItem);

    return true;
}

function getStatus() {
    global $productos_no_entregados;

    if (count($productos_no_entregados) > 0) {
        return 1;
    } else {
        return 2;
    }
}

?>
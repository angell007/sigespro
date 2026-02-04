<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
require_once('../../class/class.configuracion.php');
require_once('../../class/class.consulta.php');
require_once('../../class/class.qr.php');  /* AGREGAR ESTA CLASE PARA GENERAR QR */
require('../../class/class.guardar_archivos.php');

//Objeto de la clase que almacena los archivos    
$storer = new FileStorer();

$configuracion = new Configuracion();
//$mod = ( isset( $_REQUEST['modulo'] ) ? $_REQUEST['modulo'] : '' );
$datos = ( isset( $_REQUEST['datos'] ) ? $_REQUEST['datos'] : '' );
$productos = ( isset( $_REQUEST['productos'] ) ? $_REQUEST['productos'] : '' );
$func = ( isset( $_REQUEST['funcionario'] ) ? $_REQUEST['funcionario'] : '' );
$punto = ( isset( $_REQUEST['punto'] ) ? $_REQUEST['punto'] : '' );
$soportes = ( isset( $_REQUEST['soportes'] ) ? $_REQUEST['soportes'] : '' );
$cie = ( isset( $_REQUEST['Cie'] ) ? $_REQUEST['Cie'] : '' );
$producto_entregado = ( isset( $_REQUEST['producto_entregado'] ) ? $_REQUEST['producto_entregado'] : '' );
$idauditoria = ( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : '' );
$celular_paciente = ( isset( $_REQUEST['celular_paciente'] ) ? $_REQUEST['celular_paciente'] : '' );
$resultado = [];

$datos = (array) json_decode($datos, true);
$productos = (array) json_decode($productos , true);
$soportes = (array) json_decode($soportes , true);

date_default_timezone_set('America/Bogota');

if (count($productos) > 1) {
    $cie= str_replace('"','',$cie);

    /* GUARDAR DISPENSACIÓN */
    
    $oItem = new complex('Configuracion','Id_Configuracion',1);
    $nc = $oItem->getData();
    
    $oItem->Consecutivo=$oItem->Consecutivo+1;
    $oItem->save();
    $num_dispensacion=$nc["Consecutivo"];
    unset($oItem);
    
    $cod = "DIS".sprintf("%05d", $num_dispensacion); 
    
    $imagen=$datos["Firma_Digital"];
    
    $fot = '';
    
    if ($imagen != "") {
        list($type, $imagen) = explode(';', $imagen);
        list(, $imagen)      = explode(',', $imagen);
        $imagen = base64_decode($imagen);
    
        $fot="firma".uniqid().".jpg";
        $archi=$MY_FILE . "IMAGENES/FIRMAS-DIS/".$fot;
        file_put_contents($archi, $imagen);
        chmod($archi, 0644);
        //$storer->UpdateFile("IMAGENES/FIRMAS-DIS/".$fot, $imagen);
    }
    
    $datos["Firma_Reclamante"]=$fot;
    $datos["Fecha_Actual"]= date("Y-m-d H:i:s");
    $datos["Identificacion_Funcionario"]=$func;
    $datos["Id_Punto_Dispensacion"]=$punto;
    
    if($datos["Tipo"]=="Capita"){
        unset($datos["Tipo_Servicio"]);
    }else{
        //$datos["Fecha_Formula"]="0000-00-00";
    }
    $entregas=$datos["Cantidad_Entregas"];
    $entrega_actual=$datos["Entrega_Actual"];
    $fechaformula=$datos["Fecha_Formula"];
    $datos['Productos_Entregados']=$producto_entregado;
    $datos['Codigo']=$cod;
    $datos["Estado"] = "Entregado";
    $datos["Estado_Dispensacion"] = "Activa";
    $datos["Funcionario_Preauditoria"] = $func;
    $datos["Punto_Pre_Auditoria"] = $punto;
    //$datos["Pendientes"] = 0;
    if($cie=="undefined"){
        $datos["CIE"] = "";
    }else{
      $datos["CIE"] = $cie;  
    }
    $datos['Id_Turnero'] = $datos['Id_Turnero'] != '' ? $datos['Id_Turnero'] : '0';
    $ActividadDis["Identificacion_Funcionario"]=$func;
    
    $oItem = new complex("Dispensacion","Id_Dispensacion");
    
    /* if (isset($datos['Id_Dispensacion']) && $datos['Id_Dispensacion'] == '') {
        // unset($datos['Id_Dispensacion']);
        $datos['Id_Dispensacion'] = NULL;
    } */
    
    foreach($datos as $index=>$value) {
        if ($index != 'Id_Dispensacion' && $value!='') {
            $oItem->$index=$value;
        }
        
    }
    $oItem->save();
    $id_dis= $oItem->getId();
    $resultado = array();
    
    
    /* AQUI GENERA QR */
    //$qr = generarqr('dispensacion',$id_dis,$MY_FILE.'/MY_FILES/QR/');
    $qr = generarqr('dispensacion',$id_dis,'IMAGENES/QR/');
    $oItem = new complex("Dispensacion","Id_Dispensacion",$id_dis);
    $oItem->Codigo_Qr=$qr;
    $oItem->save();
    unset($oItem);
    /* HASTA AQUI GENERA QR */

    ## ACTUALIZAR CELULAR PACIENTE
    if ($celular_paciente != '' && $celular_paciente != null) {
        updateCelularPaciente($datos['Numero_Documento'], $celular_paciente);
    }
    
    $ActividadDis['Fecha'] = date("Y-m-d H:i:s");
    $ActividadDis["Id_Dispensacion"] = $id_dis;
    $ActividadDis["Detalle"] = "Esta dispensacion fue agregada";
    $ActividadDis["Estado"] = "Creado";
    
    $oItem = new complex("Actividades_Dispensacion","Id_Actividades_Dispensacion");
    foreach($ActividadDis as $index=>$value) {
        $oItem->$index=$value;
    }
    $oItem->save();
    unset($oItem);
    
    
    unset($productos[count($productos)-1]);
    
    // var_dump($productos);
    
    foreach($productos as $producto){

        
        if($datos["Tipo"]=="Capita"){
            unset($producto["Fecha_Autorizacion"]);
        }
        //$producto["Entregar_Faltante"]=$producto["Cantidad_Formulada"]-$producto["Cantidad_Entregada"];
        if($producto["Entregar_Faltante"]==""){
            $producto["Entregar_Faltante"]="0";
        }
        if($producto["Id_Inventario"]==""){
            $producto["Id_Inventario"]="0";
        }
        /*$oItem = new complex('Producto_Dispensacion',"Id_Producto_Dispensacion");
        $producto["Id_Dispensacion"]=$id_dis;
        foreach($producto as $index=>$value){
            $oItem->$index=$value;
        }
        $oItem->save();
        unset($oItem);
        
        
        if($producto["Id_Inventario"]!="0"){
            $producto['Id_Inventario'] = (int) $producto['Id_Inventario'];
            // var_dump($producto['Id_Inventario']);
            $oItem = new complex('Inventario',"Id_Inventario",$producto['Id_Inventario']);
            $cantidad = number_format((int) $producto["Cantidad"],0,"","");
            $cantidad_entregada = number_format($producto["Cantidad_Entregada"],0,"","");
            $cantidad_total = $cantidad - $cantidad_entregada;
            $producto["Cantidad"]= number_format($cantidad_total,0,"","");
            foreach($producto as $index=>$value){
               $oItem->$index=  $value;
            }
           $oItem->save();
            unset($oItem);
        }*/
        if (validarEntregaProducto($producto["Cantidad_Entregada"],$producto['Id_Inventario'])) {
        
            //$producto["Entregar_Faltante"]=$producto["Cantidad_Formulada"]-$producto["Cantidad_Entregada"];
            if($producto["Id_Inventario"]!="0"){
                $producto['Id_Inventario'] = (int) $producto['Id_Inventario'];
                // var_dump($producto['Id_Inventario']);
                $oItem = new complex('Inventario',"Id_Inventario",$producto['Id_Inventario']);
                $inv_act=$oItem->getData();
                $cantidad = number_format((int) $inv_act["Cantidad"],0,"","");
                $cantidad_entregada = number_format($producto["Cantidad_Entregada"],0,"","");
                $cantidad_total = $cantidad - $cantidad_entregada;
                if($cantidad_total<0){
                    $cantidad_total=0;
                    $producto['Cantidad_Entregada'] =$cantidad ;
                    $producto['Entregar_Faltante'] =$cantidad_entregada-$cantidad;
                }
                $oItem->Cantidad= number_format($cantidad_total,0,"","");             
                $oItem->save();
                unset($oItem);
            }
            $oItem = new complex('Producto_Dispensacion',"Id_Producto_Dispensacion");
            foreach($producto as $index=>$value){
                $oItem->$index=$value;
            }
            $oItem->Cantidad_Formulada_Total = $producto['Cantidad_Formulada'];
            $oItem->Id_Dispensacion = $id_dis;
            $oItem->save();
            unset($oItem);
    
        } else {
            $oItem = new complex('Producto_Dispensacion',"Id_Producto_Dispensacion");
            $productos_no_entregados[] = $producto;    
            $producto['Cantidad_Entregada'] = 0;    
            foreach($producto as $index=>$value){
                $oItem->$index=$value;
            }
            $oItem->Cantidad_Formulada_Total = $producto['Cantidad_Formulada'];
            $oItem->Id_Dispensacion = $id_dis;
            $oItem->save();
            unset($oItem);
        }
    }
    
    /* var_dump($productos);
    exit; */
    
    
    
    if($datos["Id_Dispensacion_Fecha_Entrega"]!=""){
        $fechahoy= date("Y-m-d");
               $oItem = new complex("Dispensacion_Fecha_Entrega","Id_Dispensacion_Fecha_Entrega",$datos["Id_Dispensacion_Fecha_Entrega"]);
                $oItem->Fecha=$fechahoy;
                $oItem->save();
                unset($oItem);
               
            }else{
        
        for($i=($entrega_actual-1); $i < $entregas; $i++){
           $dias=30*$i;  
           $fecha = date('Y-m-d');
           $nuevafecha = strtotime ( '+'.$dias.' day' , strtotime ( $fecha ) ) ;
           $nuevafecha = date ( 'Y-m-d' , $nuevafecha );
           
           $oItem = new complex('Dispensacion_Fecha_Entrega',"Id_Dispensacion_Fecha_Entrega");
           $oItem->Id_Paciente=$datos["Numero_Documento"];
              $oItem->Fecha_Entrega=$nuevafecha;
              if($i==($entrega_actual-1)){
                $oItem->Fecha=$fecha;
              }
              $oItem->Fecha_Formula=$datos["Fecha_Formula"];
              $datos["Entrega_Actual"]=$i+1;  
              $oItem->Entrega_Actual=$datos["Entrega_Actual"];
              $oItem->Entrega_Total=$entregas;
              $oItem->Id_Dispensacion=$id_dis;
              $oItem->save();
              unset($oItem);
        }
    }
    
    /** FIN DE GUARDAR DISPENSACIÓN */
    
    /** GUARDAR AUDITORIA */
    
    $datos["Fecha_Preauditoria"]=date("Y-m-d H:i:s");
    
    $oItem = new complex("Auditoria","Id_Auditoria");
    foreach($datos as $index=>$value) {
        $oItem->$index=$value;
    }
    $oItem->Id_Paciente = $datos['Numero_Documento'];
    $oItem->Estado = 'Pre Auditado';
    $oItem->Id_Dispensacion = $id_dis;
    $oItem->Id_Tipo_Servicio = $datos['Tipo_Servicio'] != '' ? $datos['Tipo_Servicio'] : getIdTipoServicio($datos['Tipo']);
    $oItem->Funcionario_Preauditoria = $func;
    $oItem->Punto_Pre_Auditoria = $punto;
    $oItem->save();
    $id_auditoria = $oItem->getId();
    unset($oItem);
    
    // if (!file_exists( $MY_FILE.'IMAGENES/AUDITORIAS/'.$id_auditoria)) {
    //     mkdir($MY_FILE.'IMAGENES/AUDITORIAS/'.$id_auditoria, 0777, true);
    // }
    if (!empty($_FILES['Archivo']['name'])){
        //GUARDAR ARCHIVO Y RETORNAR NOMBRE DEL MISMO
        $nombre_archivo = $storer->UploadFileToRemoteServer($_FILES, 'store_remote_files', 'IMAGENES/AUDITORIAS/'.$id_auditoria.'/');
        $nombre_archivo  = $nombre_archivo[0];

        // $posicion1 = strrpos($_FILES['Archivo']['name'],'.')+1;
        // $extension1 =  substr($_FILES['Archivo']['name'],$posicion1);
        // $extension1 =  strtolower($extension1);
        // $_filename1 = uniqid() . "." . $extension1;
        // $_file1 = $MY_FILE . "IMAGENES/AUDITORIAS/".$id_auditoria."/" . $_filename1;
        
        // $subido1 = move_uploaded_file($_FILES['Archivo']['tmp_name'], $_file1);
        //     if ($subido1){		
        //         @chmod ( $_file1, 0777 );
        //         $nombre_archivo = $_filename1;
        //     } 
    }
    if( $nombre_archivo){
        $oItem = new complex("Auditoria","Id_Auditoria",$id_auditoria );
        $oItem->Archivo=$nombre_archivo;
        $oItem->save();
        unset($oItem);
    }
    
    /** FIN DE GUARDAR AUDITORIA */
    
    /** GUARDAR SOPORTES */
    
    foreach($soportes as $soporte){ $i++;
        $oItem = new complex('Soporte_Auditoria',"Id_Soporte_Auditoria");
        $soporte['Id_Auditoria']=$id_auditoria;
        foreach($soporte as $index=>$value) {
            $oItem->$index=$value;
        }
        $oItem->save();
        unset($oItem);
    }
    
    /** FIN DE GUARDAR SOPORTES */
    
     
    if($id_dis){
        $resultado['mensaje'] = "Se ha guardado correctamente la dispensación con codigo: ". $datos['Codigo'];
        $resultado['tipo'] = "success";
        $resultado['titulo'] = "Dispensación Entregada Correctamente";
        $resultado['status'] = getStatus();
        $resultado['productos_no_entregados'] = $productos_no_entregados;
    }else{
        $resultado['mensaje'] = "Ha ocurrido un error guardando la información, por favor verifique";
        $resultado['tipo'] = "error";
        $resultado['titulo'] = "Error Entregando Dispensación";
    }
    
    $resultado['id_dispensacion'] = $id_dis;  
} else {
    $resultado['mensaje'] = "Ha ocurrido un error en listado de productos, por favor contactenos inmediatamente: (037)6421003 (Bucaramanga)";
    $resultado['tipo'] = "error";
    $resultado['titulo'] = "Error Entregando Dispensación";
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

function getIdTipoServicio($tipo_servicio) {
    $oCon = new consulta();
    $oCon->setQuery("SELECT Id_Tipo_Servicio FROM Tipo_Servicio WHERE Nombre = '". strtoupper($tipo_servicio)  . "'" );
    $result = $oCon->getData();
    unset($oCon);

    return $result['Id_Tipo_Servicio'];

}


?>
<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
// include_once('../../class/class.lista.php');
// include_once('../../class/class.complex.php');
// include_once('../../class/class.consulta.php');
require_once('../../class/class.configuracion.php');
require_once('../../class/class.qr.php');  /* AGREGAR ESTA CLASE PARA GENERAR QR */
require('../../class/class.guardar_archivos.php');
include_once('../../class/class.querybasedatos.php');
include_once('../../class/class.facturaccionmasiva.php');
// include_once('../../class/class.portal_clientes.php');

$queryObj = new QueryBaseDatos();

$storer = new FileStorer();
$facturaccion=new  Facturacion_Masiva();
// $portalClientes = new PortalCliente($queryObj);

$configuracion = new Configuracion();
$datos = ( isset( $_REQUEST['modelo'] ) ? $_REQUEST['modelo'] : '' );
$productos = ( isset( $_REQUEST['productos'] ) ? $_REQUEST['productos'] : '' );
$soportes = ( isset( $_REQUEST['soportes'] ) ? $_REQUEST['soportes'] : '' );
$resultado = [];

$datos = (array) json_decode($datos, true);
$productos = (array) json_decode(utf8_decode($productos) , true);
$soportes = (array) json_decode($soportes , true);
$idFactura = 0;

if (count($productos) > 0) {

    /* GUARDAR DISPENSACIÓN */
    
    $oItem = new complex('Configuracion','Id_Configuracion',1);
    $nc = $oItem->getData();
    
    $oItem->Consecutivo=$oItem->Consecutivo+1;
    $oItem->save();
    $num_dispensacion=$nc["Consecutivo"];
    unset($oItem);
    
    $cod = "DIS".sprintf("%05d", $num_dispensacion); 
    
    $imagen=$datos["Firma_Reclamante"];
    
    $fot = '';
    
    if ($imagen != "") {
        list($type, $imagen) = explode(';', $imagen);
        list(, $imagen)      = explode(',', $imagen);
        $imagen = base64_decode($imagen);
    
        $fot="firma".uniqid().".jpg";
        $archi=$MY_FILE . "IMAGENES/FIRMAS-DIS/".$fot;
        file_put_contents($archi, $imagen);
        chmod($archi, 0644);

        $datos["Firma_Reclamante"]=$fot;
    }
   
    
    
  
    $entregas=$datos["Cantidad_Entregas"];
    $entrega_actual=$datos["Entrega_Actual"];
    $fechaformula=$datos["Fecha_Formula"];
    $datos['Codigo']=$cod;
    $datos["Estado"] = "Entregado";
    $datos["Estado_Dispensacion"] = "Activa";
    $datos["Funcionario_Preauditoria"] = $datos['Identificacion_Funcionario'];
    $datos["Punto_Pre_Auditoria"] = $datos['Id_Punto_Dispensacion'];
    $datos["Estado_Auditoria"] = "Auditada";
    $datos['Fecha_Actual'] = date('Y-m-d H:i:s');
    
    $datos['Id_Turnero'] = $datos['Id_Turnero'] != '' ? $datos['Id_Turnero'] : '0';
    $ActividadDis["Identificacion_Funcionario"]= $datos['Identificacion_Funcionario'];
    
    $oItem = new complex("Dispensacion","Id_Dispensacion");

    foreach($datos as $index=>$value) {
        if($index=='Pendientes'){               
            $oItem->$index=number_format($value,0,"","");
        }
        if ($index != 'Id_Dispensacion' && $value != '') {          
                $oItem->$index=$value;
        }
    }
    $oItem->save();
    $id_dis= $oItem->getId();
    $resultado = array();
    
    
    /* AQUI GENERA QR */
    $qr = generarqr('dispensacion',$id_dis,'IMAGENES/QR/');
    $oItem = new complex("Dispensacion","Id_Dispensacion",$id_dis);
    $oItem->Codigo_Qr=$qr;
    $oItem->save();
    unset($oItem);
    /* HASTA AQUI GENERA QR */

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
    
    foreach($productos as $producto){
        
        if($datos["Tipo"]=="Capita"){
            unset($producto["Fecha_Autorizacion"]);
        }
        if($producto["Entregar_Faltante"]==""){
            $producto["Entregar_Faltante"]="0";
        }
        if($producto["Id_Inventario"]==""){
            $producto["Id_Inventario"]="0";
        }

        $codigo_cum = $producto['Codigo_Cum'];

        if (validarEntregaProducto($producto["Cantidad_Entregada"],$producto['Id_Inventario'])) {
        
            if($producto["Id_Inventario"]!="0"){
                $producto['Id_Inventario'] = (int) $producto['Id_Inventario'];
                $oItem = new complex('Inventario',"Id_Inventario",$producto['Id_Inventario']);
                $inv_act=$oItem->getData();
                $cantidad = number_format((int) $inv_act["Cantidad"],0,"","");
                $cantidad_entregada = number_format((int)$producto["Cantidad_Entregada"],0,"","");
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
            $oItem->Id_Dispensacion = $id_dis;
            $oItem->Cum = $codigo_cum;
            $oItem->Cantidad_Formulada_Total = $producto['Cantidad_Formulada'];
            $oItem->save();
            unset($oItem);
    
        } else {
            $oItem = new complex('Producto_Dispensacion',"Id_Producto_Dispensacion");
            $productos_no_entregados[] = $producto;    
            $producto['Cantidad_Entregada'] = 0;    
            foreach($producto as $index=>$value){
                $oItem->$index=$value;
            }
            $oItem->Id_Dispensacion = $id_dis;
            $oItem->Cum = $codigo_cum;
            $oItem->Cantidad_Formulada_Total = $producto['Cantidad_Formulada'];
            $oItem->save();
            unset($oItem);
        }
    }
    
    if($datos['Id_Dispensacion_Fecha_Entrega']){
        ActualizarFechaEntrega($datos['Id_Dispensacion_Fecha_Entrega']);
    }else{
        CrearFechaEntrega($datos);
    }
     
    /** FIN DE GUARDAR DISPENSACIÓN */
    
    /** GUARDAR AUDITORIA */

    $servicio = getNombreTipoServicio($datos['Id_Tipo_Servicio']);


    if (strpos(strtolower($servicio),'capita') === false) { // GUARDA LA AUDITORIA SIEMPRE Y CUANDO NO SEA CAPITA.
        $datos["Fecha_Preauditoria"]=date("Y-m-d H:i:s");
    
        $oItem = new complex("Auditoria","Id_Auditoria");
        foreach($datos as $index=>$value) {
            if ($index != 'Id_Auditoria') {
                $oItem->$index=$value;
            }
        }
        $oItem->Id_Paciente = $datos['Numero_Documento'];
        $oItem->Estado = 'Pre Auditado';
        $oItem->Id_Dispensacion = $id_dis;
        $oItem->Id_Tipo_Servicio = $datos['Id_Tipo_Servicio'];
        $oItem->Funcionario_Preauditoria = $datos['Identificacion_Funcionario'];
        $oItem->Punto_Pre_Auditoria = $datos['Id_Punto_Dispensacion'];
        $oItem->save();
        $id_auditoria = $oItem->getId();
        unset($oItem);
        
        /* if (!file_exists( $MY_FILE.'IMAGENES/AUDITORIAS/'.$id_auditoria)) {
            mkdir($MY_FILE.'IMAGENES/AUDITORIAS/'.$id_auditoria, 0777, true);
        }
        if (!empty($_FILES['Archivo']['name'])){
            $posicion1 = strrpos($_FILES['Archivo']['name'],'.')+1;
            $extension1 =  substr($_FILES['Archivo']['name'],$posicion1);
            $extension1 =  strtolower($extension1);
            $_filename1 = uniqid() . "." . $extension1;
            $_file1 = $MY_FILE . "IMAGENES/AUDITORIAS/".$id_auditoria."/" . $_filename1;
            
            $subido1 = move_uploaded_file($_FILES['Archivo']['tmp_name'], $_file1);
                if ($subido1){		
                    @chmod ( $_file1, 0777 );
                    $nombre_archivo = $_filename1;
                } 
        } */
        $nombre_archivo = $storer->UploadFileToRemoteServer($_FILES, 'store_remote_files', 'IMAGENES/AUDITORIAS/'.$id_auditoria.'/');
        $nombre_archivo = $nombre_archivo[0];  
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
          
    }
    
    if($id_dis){
        $resultado['mensaje'] = "Se ha guardado correctamente la dispensación con codigo: ". $datos['Codigo'];
        $resultado['tipo'] = "success";
        $resultado['titulo'] = "Dispensación Entregada Correctamente";
        $resultado['status'] = getStatus();
        $resultado['productos_no_entregados'] = $productos_no_entregados;
        $resultado['id_dispensacion'] =  $id_dis;

        ValidarDispensacionFacturacion($id_dis);
    }else{
        $resultado['mensaje'] = "Ha ocurrido un error guardando la información, por favor verifique";
        $resultado['tipo'] = "error";
        $resultado['titulo'] = "Error Entregando Dispensación";
    }
    
    $resultado['id_dispensacion'] = $id_dis; 
    $resultado['id_factura']=$idFactura;

// GuardarDispensacionPortalClientes($id_disp); 
} else {
    $resultado['mensaje'] = "Ha ocurrido un error en listado de productos, por favor contactenos inmediatamente: (037)6421003 (Bucaramanga)";
    $resultado['tipo'] = "error";
    $resultado['titulo'] = "Error Entregando Dispensación";
}


 echo json_encode($resultado);

 function ValidarDispensacionFacturacion($idDis){

    global $queryObj,$facturaccion,$datos,$idFactura;

    $query="SELECT (SELECT Nombre FROM Tipo_Servicio WHERE Id_Tipo_Servicio=D.Id_Tipo_Servicio) as Tipo_Servicio,D.Pendientes,D.Id_Dispensacion, P.Nit as Id_Cliente,D.Id_Servicio 
    FROM Dispensacion D INNER JOIN ( SELECT Nit, Id_Paciente FROm Paciente ) P ON D.Numero_Documento=P.Id_Paciente WHERE D.Id_Dispensacion=$idDis";
    $queryObj->SetQuery($query);
    $dispensacion = $queryObj->ExecuteQuery('simple');

     

    if(strtolower( $dispensacion['Tipo_Servicio'])=='evento' &&  $dispensacion['Id_Servicio']=='1' ){
        $query="SELECT PD.Id_Dispensacion,PD.Id_Producto,(SELECT Codigo FROM Dispensacion WHERE Id_Dispensacion=PD.Id_Dispensacion) as Codigo, CONCAT(P.Principio_Activo,' ',P.Presentacion,' ',P.Concentracion,' ', P.Cantidad,' ', P.Unidad_Medida) as Nombre,P.Nombre_Comercial,P.Codigo_Cum
        FROM Producto_Dispensacion PD 
        INNER JOIN Producto P ON PD.Id_Producto=P.Id_Producto
        WHERE PD.Id_Dispensacion=$idDis AND PD.Cantidad_Formulada !=PD.Cantidad_Entregada";
    
        $queryObj->SetQuery($query);
        $pendientes = $queryObj->ExecuteQuery('Multiple');
      
      
        if(count($pendientes)==0){
            $productos_sin_precio=GetProductosSinPrecio($dispensacion,$idDis);
       
            if(count($productos_sin_precio)==0){
                if(strtolower($dispensacion['Tipo_Servicio'])=='evento'){
                    $tipo='Evento';
                }elseif(strtolower($dispensacion['Tipo_Servicio'])=='cohortes'){
                    $tipo='Cohortes';
                }else{
                    return ;
                }
              
                
                $facturaccion->Facturacion($idDis,$datos['Identificacion_Funcionario'],$tipo);
        
                $idFactura=GetIdFactura($idDis);
                $total=GetTotalFactura($idFactura);

                if($total>30000){
                    $idFactura=0;
                }
            }
        }      
    }
    
   }

   function GetProductosSinPrecio($dispensacion,$idDis){

    global $queryObj;

    if(strtolower($dispensacion['Tipo_Servicio'])=="evento"){
        $exits=" AND NOT exists (SELECT Codigo_Cum FROM Producto_Evento WHERE Codigo_Cum=P.Codigo_Cum AND Nit_EPS=$dispensacion[Id_Cliente] AND Precio>0 )  ";
    }elseif (strtolower($dispensacion['Tipo_Servicio'])=='cohortes'){
        $exits=" AND NOT exists (SELECT Id_Producto FROM Producto_Cohorte WHERE Id_Producto=PD.Id_Producto AND Nit_EPS=$dispensacion[Id_Cliente] ) ";
    }

    $query="SELECT PD.Id_Producto,P.Nombre_Comercial,P.Codigo_Cum, IFNULL((SELECT Precio FROM Precio_Regulado WHERE Codigo_Cum=P.Codigo_Cum),0) as Precio, CONCAT(P.Principio_Activo,' ',P.Presentacion,' ',P.Concentracion,' ', P.Cantidad,' ', P.Unidad_Medida) as Nombre
    FROM Producto_Dispensacion PD 
    INNER JOIN Producto P ON PD.Id_Producto=P.Id_Producto
    WHERE PD.Id_Dispensacion=$idDis ".$exits." GROUP BY PD.Id_Producto HAVING Precio=0 ";
    

    $queryObj->SetQuery($query);
    $productos_sin_precio = $queryObj->ExecuteQuery('Multiple');

    return $productos_sin_precio;
}

function GetIdFactura($idDis){
    global $queryObj;

    $query="SELECT Id_Factura FROM Factura WHERE Id_Dispensacion=$idDis";
    $queryObj->SetQuery($query);
    $fact = $queryObj->ExecuteQuery('simple');



    return $fact['Id_Factura'];
}
function GetTotalFactura($id){
    global $queryObj;

    $query="SELECT SUM(Subtotal) as Total FROM Producto_Factura WHERE Id_Factura=$id";
    $queryObj->SetQuery($query);
    $fact = $queryObj->ExecuteQuery('simple');



    return $fact['Total']; 
}

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

function ActualizarFechaEntrega($id){
    global $id_dis;
    $fecha=date("Y-m-d");

    $entrega=GetDatosFechaEntrega($id);

    $oItem = new complex("Dispensacion_Fecha_Entrega","Id_Dispensacion_Fecha_Entrega",$id);
    $oItem->Fecha=$fecha;
    $oItem->Id_Dispensacion=$id_dis;
    $oItem->save();
    unset($oItem);
    
    /*se valida que la fecha actual sea mayor a la registrada para actualizar las fecha de las siguientes entregas*/
    if($entrega['Entrega_Actual']<$fecha){
        $entregas_faltantes=GetEntregasFaltantes($entrega);
        if(count($entregas_faltantes)>0){
            $i=0;
            foreach ($entregas_faltantes as  $value) {$i++;
                $nuevafecha = strtotime ( '+'.$i.' month' , strtotime ( $fecha ) ) ;
                $nuevafecha = date ( 'Y-m-d' , $nuevafecha );
                $oItem = new complex("Dispensacion_Fecha_Entrega","Id_Dispensacion_Fecha_Entrega",$value['Id_Dispensacion_Fecha_Entrega']);
                $oItem->Fecha_Entrega=$nuevafecha; 
                $oItem->save();
                unset($oItem);
            }
        }
    }

}

function GetDatosFechaEntrega($id){
    global $queryObj;

    $query="SELECT * FROM Dispensacion_Fecha_Entrega WHERE Id_Dispensacion_Fecha_Entrega=$id";
    $queryObj->SetQuery($query);
    $entrega = $queryObj->ExecuteQuery('simple');
    return $entrega;
  }

  function GetEntregasFaltantes($modelo){
    global $queryObj;
    $query="SELECT * FROM Dispensacion_Fecha_Entrega WHERE 	Id_Paciente='$modelo[Id_Paciente]' AND Fecha_Formula='$modelo[Fecha_Formula]' AND Id_Dispensacion=$modelo[Id_Dispensacion] AND Fecha='0000-00-00' ORDER BY Id_Dispensacion_Fecha_Entrega ASC";
    $queryObj->SetQuery($query);
    $entrega = $queryObj->ExecuteQuery('Multiple');
    return $entrega;
  }

  function CrearFechaEntrega($modelo){
    global $id_dis;
    $entrega_actual=(INT)$modelo['Entrega_Actual'];
    $entrega_Final=(INT)$modelo['Cantidad_Entregas'];

    $fecha = date('Y-m-d');
    

    $index=0;

    for ($i=$entrega_actual; $i <=$entrega_Final ; $i++) { 
          $oItem = new complex('Dispensacion_Fecha_Entrega',"Id_Dispensacion_Fecha_Entrega");
          $oItem->Id_Paciente=$modelo["Numero_Documento"];
          $oItem->Fecha_Formula=$modelo["Fecha_Formula"];
        if($index==0){
          
          $oItem->Fecha=$fecha;            
          $oItem->Fecha_Entrega=$fecha;            
          $oItem->Entrega_Actual=$i;            
          $oItem->Entrega_Total=$entrega_Final;
          $oItem->Id_Dispensacion=$id_dis;
          $oItem->Estado='Entregada';
         
        }else{
          $nuevafecha = strtotime ( '+'.$index.' month' , strtotime ( $fecha ) ) ;
          $nuevafecha = date ( 'Y-m-d' , $nuevafecha );
          $oItem->Fecha='0000-00-00';            
          $oItem->Fecha_Entrega=$nuevafecha;            
          $oItem->Entrega_Actual=$i;            
          $oItem->Entrega_Total=$entrega_Final;
          $oItem->Id_Dispensacion=$id_dis;
        }
        $oItem->save();
        unset($oItem);

        $index++;
    }
}

function getNombreTipoServicio($id_tipo_servicio) {
    $oItem = new complex("Tipo_Servicio","Id_Tipo_Servicio",$id_tipo_servicio);
    $servicio = $oItem->getData()['Nombre'];

    return $servicio;
}


  // function GuardarDispensacionPortalClientes($idDis){
  //   global $portalClientes;

  //   $response = $portalClientes->ActualizarDispensacion($idDis);

  // }


?>
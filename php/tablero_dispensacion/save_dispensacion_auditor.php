<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

include_once('../../class/class.consulta.php');
require_once('../../class/fpdf/fpdf.php');
require_once('../../class/fpdf/fpdi.php');
include_once('../../class/PDFMerge/PDFMerger.php');
include_once('../../class/class.http_response.php');
include_once('../../class/class.utility.php');
include_once('../../class/class.complex.php');

require '../../class/class.awsS3.php';

require_once('../../config/start.inc.php');
require_once('../../class/class.configuracion.php');
require_once('../../class/class.qr.php');  /* AGREGAR ESTA CLASE PARA GENERAR QR */
require_once('../../class/class.guardar_archivos.php');
include_once('../../class/class.querybasedatos.php');
include_once('../../class/class.mensajes.php');
require_once('./dividir/dividir_pdf_dispensacion_auditor.php');


$queryObj = new QueryBaseDatos();

$storer = new FileStorer();
// $portalClientes = new PortalCliente($queryObj);

$configuracion = new Configuracion();
$datos = ( isset( $_REQUEST['modelo'] ) ? $_REQUEST['modelo'] : '' );
$productos = ( isset( $_REQUEST['productos'] ) ? $_REQUEST['productos'] : '' );
$soportes = ( isset( $_REQUEST['soportes'] ) ? $_REQUEST['soportes'] : '' );
$reclamante = ( isset( $_REQUEST['reclamante'] ) ? $_REQUEST['reclamante'] : '' );
$resultado = [];

$datos = (array) json_decode($datos, true);
$productos = (array) json_decode(utf8_decode($productos) , true);
$soportes = (array) json_decode($soportes , true);
$reclamante = (array) json_decode($reclamante , true);
$idFactura=0;

date_default_timezone_set('America/Bogota');

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
    $datos["Estado_Auditoria"] = "Sin Auditar";
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
    if($datos['Id_Turero']!='' && $datos['Id_Turnero']!=NULL){
        $id=(INT)$datos['Id_Turero'];
        if($id!=0){
        $oItem = new complex("Turnero","Id_Turnero",$id);
        $oItem->Estado = "Atendido";
        $oItem->save();
        unset($oItem);
        }
    }
    $prods_mipres = '';
    $id_dis_mipres = '';
    foreach($productos as $producto){
        /** Modifcado el 20 de Julio 2020 Augusto - Cambia Inventario por Inventario_Nuevo */
        
        if($datos["Tipo"]=="Capita"){
            unset($producto["Fecha_Autorizacion"]);
        }
        if($producto["Entregar_Faltante"]==""){
            $producto["Entregar_Faltante"]="0";
        }
        if($producto["Id_Inventario_Nuevo"]==""){
            $producto["Id_Inventario_Nuevo"]="0";
        }

        $codigo_cum = $producto['Codigo_Cum'];

        if (validarEntregaProducto($producto["Cantidad_Entregada"],$producto['Id_Inventario_Nuevo'])) {
        
            if($producto["Id_Inventario_Nuevo"]!="0"){
                $producto['Id_Inventario_Nuevo'] = (int) $producto['Id_Inventario_Nuevo'];
                $oItem = new complex('Inventario_Nuevo',"Id_Inventario_Nuevo",$producto['Id_Inventario_Nuevo']);
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
            $producto["Id_Producto_Mipres"]=(INT)$producto["Id_Producto_Mipres"];
            $producto["Cum"]=$codigo_cum;
            foreach($producto as $index=>$value){
                $oItem->$index=$value;
            }
            $oItem->Id_Dispensacion = $id_dis;
            $oItem->Cum = $codigo_cum;
            $oItem->Cantidad_Formulada_Total = $producto['Cantidad_Formulada'];
            if (isset($producto['Id_Producto_Dispensacion_Mipres']) && $producto['Id_Producto_Dispensacion_Mipres'] != '') {
                $oItem->Id_Producto_Mipres = $producto['Id_Producto'];
                //$id_dis_mipres=ActualizaProductoDispensacionMipres($producto['Id_Producto_Dispensacion_Mipres'],$producto);
                $prods_mipres.=$producto['Id_Producto_Dispensacion_Mipres'].",";
            }
            $oItem->save();
            unset($oItem);
    
        } else {
            $oItem = new complex('Producto_Dispensacion',"Id_Producto_Dispensacion");
            $productos_no_entregados[] = $producto;    
            $producto['Cantidad_Entregada'] = 0;    
            $producto["Id_Producto_Mipres"]=(INT)$producto["Id_Producto_Mipres"];
            $producto["Cum"]=$codigo_cum;
            foreach($producto as $index=>$value){
                $oItem->$index=$value;
            }
            $oItem->Id_Dispensacion = $id_dis;
            $oItem->Cum = $codigo_cum;
            $oItem->Cantidad_Formulada_Total = $producto['Cantidad_Formulada'];
            if (isset($producto['Id_Producto_Dispensacion_Mipres']) && $producto['Id_Producto_Dispensacion_Mipres'] != '') {
                $oItem->Id_Producto_Mipres = $producto['Id_Producto'];
                //$id_dis_mipres=ActualizaProductoDispensacionMipres($producto['Id_Producto_Dispensacion_Mipres'],$producto);
                $prods_mipres.=$producto['Id_Producto_Dispensacion_Mipres'].",";
            }
            $oItem->save();
            unset($oItem);
        }
        if (isset($producto['Id_Producto_Dispensacion_Mipres']) && $producto['Id_Producto_Dispensacion_Mipres'] != '') {
            updateProductoDispensacionMipres($producto['Id_Producto_Dispensacion_Mipres'],$producto['Id_Producto']);
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

        $query = 'UPDATE  Dispensacion D INNER JOIN Auditoria A  SET D.Estado_Auditoria = "Auditada" WHERE A.Id_Auditoria='.$id_auditoria.' AND A.Id_Dispensacion = D.Id_Dispensacion';
    
        $oCon = new consulta();
        $oCon->setQuery($query);
        $oCon->createData();
        unset($oCon);
        
        $nombre_archivo = $storer->UploadFileToRemoteServer($_FILES, 'store_remote_files', 'IMAGENES/AUDITORIAS/'.$id_auditoria.'/');
        $nombre_archivo = $nombre_archivo[0];
        
        if( $nombre_archivo){
            $oItem = new complex("Auditoria","Id_Auditoria",$id_auditoria );
            $oItem->Archivo=$nombre_archivo;

            $oItem->save();
            unset($oItem);
              
            
        }

        if($datos['Id_Dispensacion_Mipres']!='0' ){
            $paciente=GetPaciente($id_auditoria);
           /* if($paciente['Id_Regimen']=='1'){
                $oItem = new complex("Auditoria","Id_Auditoria",$id_auditoria );
                $oItem->Estado="Aceptar";
                $oItem->save();
                unset($oItem);
            }*/
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

        if($datos['Id_Dispensacion_Mipres']!='0'){
            $oItem = new complex("Dispensacion_Mipres","Id_Dispensacion_Mipres",$datos["Id_Dispensacion_Mipres"]);
            $oItem->Estado='Radicado Programado';
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
        if($modelo['Id_Tipo_Servicio']==15){
            AsociarDispensacionAutorizacion($modelo['Autorizacion'], $id_dis);
        }
    }else{
        $resultado['mensaje'] = "Ha ocurrido un error guardando la información, por favor verifique";
        $resultado['tipo'] = "error";
        $resultado['titulo'] = "Error Entregando Dispensación";
    }
    
    $resultado['id_dispensacion'] = $id_dis; 
    
    
    
    
    
     //envio datos para dividir pdf
     consultaDividirPdf($id_auditoria);


    
    
    
    
    
    
    
    

    // GuardarDispensacionPortalClientes($id_dis); 
    GuardarDatosReclamante($reclamante);
    ValidarDispensacionFacturacion($id_dis);
    $response['id_factura']=$idFactura;
    if ($prods_mipres!='') {
        ValidarDispensacionMipres(trim($prods_mipres,","),$datos["Id_Dispensacion_Mipres"]);
        ActualizaProductosDispensacionMipres(trim($prods_mipres,","),$datos["Id_Dispensacion_Mipres"],$id_dis);
    }
    
    if(isset($datos["Codigo_Radicacion"])&&$datos["Codigo_Radicacion"]!=''){
        
        
        $oItem=new complex2('radicacion','Codigo',$datos["Codigo_Radicacion"]);
        $rad = $oItem->getData();
        unset($oItem);
        $oItem=new complex2('radicacion','id',$rad["id"]);
        $oItem->Estado='Validacion Soportes';
        $oItem->Fecha_Dispensacion=date("Y-m-d H:i:s");
        $oItem->save();
        unset($oItem);
        
        if($rad["Celular_Reclamante"]!=""){
            $texto=$rad["Nombre_Reclamante"].", su Radicacion Web ".$rad["Codigo"]." ha sido validada, pronto nos pondremos en contacto. Gracias, ProH S.A.";
            $oCon= new Mensaje();
            $resp=$oCon->Enviar($rad["Celular_Reclamante"],$texto);
        }
        
    }

} else {
    $resultado['mensaje'] = "Ha ocurrido un error en listado de productos, por favor contactenos inmediatamente: (037)6421003 (Bucaramanga)";
    $resultado['tipo'] = "error";
    $resultado['titulo'] = "Error Entregando Dispensación";
}


 echo json_encode($resultado);

function cantidadInventario($id_inventario_nuevo) {
    
    /** Modifcado el 20 de Julio 2020 Augusto - Cambia Inventario por Inventario_Nuevo */

    $query = "SELECT (Cantidad-Cantidad_Apartada-Cantidad_Seleccionada) AS Cantidad FROM Inventario_Nuevo WHERE Id_Inventario_Nuevo = $id_inventario_nuevo";
    
    $oCon = new consulta();
    $oCon->setQuery($query);
    $cantidad = $oCon->getData()['Cantidad'];
    unset($oCon);

    return $cantidad;
    
}

function validarEntregaProducto($cant_entrega, $id_inventario_nuevo){

/** Modifcado el 20 de Julio 2020 Augusto - Cambia Inventario por Inventario_Nuevo */
    $cantidad_inventario_nuevo = cantidadInventario($id_inventario_nuevo);

    if (($cantidad_inventario_nuevo-$cant_entrega) >= 0) {
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

    if (gettype($productos_no_entregados) == 'array' && count($productos_no_entregados) > 0 )  {
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
          #$oItem->Fecha='0000-00-00';            
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


function GuardarDispensacionPortalClientes($idDis){
    global $portalClientes;

    $response = $portalClientes->ActualizarDispensacion($idDis);

   }

   function ValidarDispensacionFacturacion($idDis){
/*
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

                if($total>300000){
                    $idFactura=0;
                }
            }
        }      
    }
    */
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
function ActualizaProductosDispensacionMipres($entregados,$id_dis_mipres,$id_dis){
    global $queryObj,$mipres,$reclamante;
    $codigo_sede_mp=GetCodigoSede();
    $nit_mp=GetNitProh();
    $productos = GetProductosDispensacionMipres($id_dis);
    /* SE COMENTA SOLO PARA ALGO DE OMAR 24/06/2020
    foreach($productos as $prod){
        $data['ID']=(INT)$prod['ID'];
        $data['CodSerTecEntregado']=$prod['Cum'];
        $data['CantTotEntregada']=$prod['Entregada'];
        $data['EntTotal']=0;
        $data['CausaNoEntrega']=0;
        $data['FecEntrega']=date('Y-m-d');
        $data['NoLote']=$prod["Lote"];
        $data['TipoIDRecibe']=$reclamante['Codigo'];
        $data['NoIDRecibe']=$reclamante['Id_Reclamante'];
        $entrega=$mipres->ReportarEntrega($data);
        if($entrega[0]['Id']){
            $oItem=new complex('Producto_Dispensacion_Mipres','Id_Producto_Dispensacion_Mipres',$prod['Id_Producto_Dispensacion_Mipres']);
            $oItem->IdEntrega=$entrega[0]['IdEntrega'];
            $oItem->Fecha_Entrega = date("Y-m-d H:i:s");
            $oItem->save();
            unset($oItem);
            
            $oItem=new complex('Dispensacion_Mipres','Id_Dispensacion_Mipres',$prod['Id_Dispensacion_Mipres']);
            $oItem->Estado='Entregado';
            $oItem->save();
            unset($oItem);
        }
    }
    if(count($productos)==0){
        $oItem=new complex('Dispensacion_Mipres','Id_Dispensacion_Mipres',$id_dis_mipres);
        $oItem->Estado='Radicado Programado';
        $oItem->save();
        unset($oItem);
    }
    */
}


/*
function ActualizaProductoDispensacionMipres($id_mipres,$producto){
    global $queryObj,$mipres,$reclamante;

    
    $codigo_sede_mp=GetCodigoSede();
    $nit_mp=GetNitProh();
    $pm = GetProductoDispensacionMipres($id_mipres);
    
    //var_dump($pm);
    
    $data_mp['ID']=(INT)$pm['ID'];
    $data_mp['FecMaxEnt']=$pm['Fecha_Maxima_Entrega'];
    $data_mp['TipoIDSedeProv']='NI';
    $data_mp['NoIDSedeProv']=$nit_mp;
    $data_mp['CodSedeProv']=$codigo_sede_mp;
    $data_mp['CodSerTecAEntregar']=$pm['CodSerTecAEntregar'];
    $data_mp['CantTotAEntregar']=$pm['Cantidad'];
    $respuesta=$mipres->Programacion($data_mp);
    if($respuesta[0]['Id']){
        $oItem=new complex('Producto_Dispensacion_Mipres','Id_Producto_Dispensacion_Mipres',$id_mipres);
        $oItem->IdProgramacion=$respuesta[0]['IdProgramacion'];
        $oItem->Fecha_Programacion = date("Y-m-d H:i:s");
        $oItem->save();
        unset($oItem);
        
        $oItem=new complex('Dispensacion_Mipres','Id_Dispensacion_Mipres',$pm["Id_Dispensacion_Mipres"]);
        $oItem->Estado="Programado";
        $oItem->save();
        unset($oItem);
    }
    
    if($producto["Cantidad_Entregada"]>0){
        $data['ID']=(INT)$pm['ID'];
        $data['CodSerTecEntregado']=$producto['Cum'];
        $data['CantTotEntregada']=$producto['Cantidad_Entregada'];
        $data['EntTotal']=0;
        $data['CausaNoEntrega']=0;
        $data['FecEntrega']=date('Y-m-d');
        $data['NoLote']=$producto["Lote"];
        $data['TipoIDRecibe']=$reclamante['Codigo'];
        $data['NoIDRecibe']=$reclamante['Id_Reclamante'];
        $entrega=$mipres->ReportarEntrega($data);
        if($entrega[0]['Id']){
            $oItem=new complex('Producto_Dispensacion_Mipres','Id_Producto_Dispensacion_Mipres',$pm['Id_Producto_Dispensacion_Mipres']);
            $oItem->IdEntrega=$entrega[0]['IdEntrega'];
            $oItem->Fecha_Entrega = date("Y-m-d H:i:s");
            $oItem->save();
            unset($oItem);
            
            $oItem=new complex('Dispensacion_Mipres','Id_Dispensacion_Mipres',$pm['Id_Dispensacion_Mipres']);
            $oItem->Estado='Entregado';
            $oItem->save();
            unset($oItem);
        }
        
    }
    //echo "es un ID: ".$pm["Id_Dispensacion_Mipres"];
    return $pm["Id_Dispensacion_Mipres"];
}
*/
function ValidarDispensacionMipres($ids,$mipres){
    global $queryObj;
    $query = 'SELECT D.*, GROUP_CONCAT(Id_Producto_Dispensacion_Mipres) as Productos
    
    FROM Producto_Dispensacion_Mipres PD 
    INNER JOIN Dispensacion_Mipres D ON PD.Id_Dispensacion_Mipres=D.Id_dispensacion_Mipres
    WHERE PD.Id_Producto_Dispensacion_Mipres NOT IN ('.$ids.') AND PD.Id_Dispensacion_Mipres='.$mipres;
    //echo $query."<br>";
    $queryObj->SetQuery($query);
    $datos_mipres = $queryObj->ExecuteQuery('simple');
    
    if($datos_mipres){
       unset($datos_mipres["Id_Dispensacion_Mipres"]);
        $oItem = new complex('Dispensacion_Mipres',"Id_Dispensacion_Mipres");
        $datos_mipres["Estado"]="Programado";
        $datos_mipres["Bandera"]="Separado";
        
        unset($datos_mipres["Id_Dispensacion_Mipres"]);
       
        unset($datos_mipres["NoSubEntrega"]);
        
        foreach($datos_mipres as $index=>$value){
            $oItem->$index=$value;
        }
        $oItem->save();
        $id_nuevo = $oItem->getId();
        unset($oItem); 
        if($id_nuevo && $datos_mipres["Productos"]!=''){
           $query="UPDATE Producto_Dispensacion_Mipres SET Id_Dispensacion_Mipres=".$id_nuevo." WHERE Id_Producto_Dispensacion_Mipres IN (".$datos_mipres["Productos"].")";
          // echo $query."<br>";
            $oCon = new consulta();
            $oCon->setQuery($query);
            $oCon->createData();
            unset($oCon); 
        }
        
    }
}
/*
function ValidarDispensacionMipres($idDis){
    global $queryObj,$mipres,$reclamante;

    $pendientes=GetPendientes($idDis);
    $codigo_sede=GetCodigoSede();
	$nit=GetNitProh();

    if(count($pendientes)==0){
        $dispensacion=GetDispensacion($idDis);
        if($dispensacion['Id_Dispensacion_Mipres']!='0'){
            $productos_mipres=GetProductosMipres($dispensacion['Id_Dispensacion_Mipres']);
            foreach ($productos_mipres as  $pm) {
                
                $lote=GetLoteEntregado($pm['Id_Producto'],$idDis);
                
            }
            

        }
    }
}
*/
function GetPendientes($idDis){
    global $queryObj;

    $query="SELECT PD.Id_Dispensacion,PD.Id_Producto,(SELECT Codigo FROM Dispensacion WHERE Id_Dispensacion=PD.Id_Dispensacion) as Codigo, CONCAT(P.Principio_Activo,' ',P.Presentacion,' ',P.Concentracion,' ', P.Cantidad,' ', P.Unidad_Medida) as Nombre,P.Nombre_Comercial,P.Codigo_Cum
    FROM Producto_Dispensacion PD 
    INNER JOIN Producto P ON PD.Id_Producto=P.Id_Producto
    WHERE PD.Id_Dispensacion=$idDis AND PD.Cantidad_Formulada !=PD.Cantidad_Entregada";

    $queryObj->SetQuery($query);
    $pendientes = $queryObj->ExecuteQuery('Multiple');

    return $pendientes;
}

function GetDispensacion($idDis){
    global $queryObj;

    $query="SELECT Id_Dispensacion_Mipres,Id_Dispensacion FROM Dispensacion WHERE Id_Dispensacion=$idDis";
    $queryObj->SetQuery($query);
    $dispensacion = $queryObj->ExecuteQuery('simple');



    return $dispensacion; 
}

function GetProductosMipres($id){
    global $queryObj;
    $query = 'SELECT
    PD.*, D.Fecha_Maxima_Entrega		
    FROM Producto_Dispensacion_Mipres PD 
    INNER JOIN Dispensacion_Mipres D ON PD.Id_Dispensacion_Mipres=D.Id_dispensacion_Mipres
    WHERE
    PD.Id_Dispensacion_Mipres='.$id;

    $queryObj->SetQuery($query);
    $productos = $queryObj->ExecuteQuery('Multiple');
    return $productos;
}
function GetProductoDispensacionMipres($id){
    global $queryObj;
    $query = 'SELECT
    PD.*, D.Fecha_Maxima_Entrega, D.Id_Dispensacion_Mipres		
    FROM Producto_Dispensacion_Mipres PD 
    INNER JOIN Dispensacion_Mipres D ON PD.Id_Dispensacion_Mipres=D.Id_Dispensacion_Mipres
    WHERE PD.Id_Producto_Dispensacion_Mipres='.$id;

    $queryObj->SetQuery($query);
    $productos = $queryObj->ExecuteQuery('simple');
    return $productos;
}
function GetProductosDispensacionMipres($id_dis){
    global $queryObj;
    $query = 'SELECT SUM(PD.Cantidad_Formulada) AS Formulada, SUM(PD.Cantidad_Entregada) AS Entregada, PDM.ID, PD.Id_Dispensacion, PD.Cum, PD.Lote, PDM.Id_Producto_Dispensacion_Mipres, PDM.Id_Dispensacion_Mipres
    FROM Producto_Dispensacion PD 
    INNER JOIN Producto_Dispensacion_Mipres PDM ON PDM.Id_Producto_Dispensacion_Mipres = PD.Id_Producto_Dispensacion_Mipres
    WHERE PD.Id_Dispensacion='.$id_dis.'
    GROUP BY PD.Id_Producto_Dispensacion_Mipres
    HAVING Entregada = Formulada
    ';

    $queryObj->SetQuery($query);
    $productos = $queryObj->ExecuteQuery('Multiple');
    return $productos;
}

function GetCodigoSede(){
    global $queryObj;

    $query = '
        SELECT
            Codigo_Sede				
        FROM Configuracion
        WHERE
            Id_Configuracion=1';

    $queryObj->SetQuery($query);
    $dato = $queryObj->ExecuteQuery('simple');
    return $dato['Codigo_Sede'];
}

function GetNitProh(){
    global $queryObj;
    $query = '
        SELECT
            NIT				
        FROM Configuracion
        WHERE
            Id_Configuracion=1';

    $queryObj->SetQuery($query);
    $dato = $queryObj->ExecuteQuery('simple');

    $n=explode('-',$dato['NIT']);
    $nit=$n[0];
    $nit=str_replace('.','',$nit);
    return $nit;
    
}

function GetLoteEntregado($idProducto,$idDis){
    global $queryObj;

    $query = "SELECT Lote 
        From Producto_Dispensacion 
        WHERE Id_Producto=$idProducto AND Id_Dispensacion=$idDis ";

    $queryObj->SetQuery($query);
    $lote = $queryObj->ExecuteQuery('simple');

    return $lote['Lote'];
}

function GuardarDatosReclamante($reclamante){
    global $queryObj;
    if($reclamante['Id_Reclamante']!=''){
        $query="SELECT * FROM Reclamante WHERE Id_Reclamante=$reclamante[Id_Reclamante]";
        $queryObj->SetQuery($query);
        $usuario = $queryObj->ExecuteQuery('simple');

        if(!$usuario){
            $oItem=new complex('Reclamante','Id_Reclamante');
            $oItem->Nombre=$reclamante['Nombre'];
            $oItem->Id_Reclamante=$reclamante['Id_Reclamante'];
            $oItem->save();
            unset($oItem);
        }
    }
   
}

function GetPaciente($idAuditoria){
    global $queryObj;
    $query="SELECT A.Id_Paciente,P.Id_Regimen FROM Auditoria A INNER JOIN (SELECT Id_Paciente, Id_Regimen FROM Paciente ) P ON A.Id_Paciente=P.Id_Paciente WHERE A.Id_Auditoria=$idAuditoria ";
    $queryObj->SetQuery($query);
    $paciente = $queryObj->ExecuteQuery('simple');

    return $paciente;

}

function updateProductoDispensacionMipres($id_producto_mipres, $id_producto) {
    $oItem = new complex('Producto_Dispensacion_Mipres','Id_Producto_Dispensacion_Mipres',$id_producto_mipres);
    $oItem->Id_Producto = $id_producto;
    $oItem->save();
    unset($oItem);
}


function AsociarDispensacionAutorizacion($numeroAutorizacion, $id_dis)
{
    $oItem = new complex('Positiva_Data','numeroAutorizacion',$numeroAutorizacion);
    $aut= $oItem->getData();
    if($aut){
        if($aut['Id_Dispensacion'] == ''){
            $oItem->Id_Producto = $id_dis;
            $oItem->save();
            unset($oItem);
        }
    }
}
?>
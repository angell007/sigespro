<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

date_default_timezone_set('America/Bogota');

require_once('../../config/start.inc.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.configuracion.php');
include_once('../../class/class.http_response.php');
include_once('../../class/class.consulta.php');
require_once('../../class/class.configuracion.php');
require_once('../../class/class.qr.php');

$http_response = new HttpResponse();
$response = array();

$modelo = ( isset( $_REQUEST['modelo'] ) ? $_REQUEST['modelo'] : '');
$datos = ( isset( $_REQUEST['datos'] ) ? $_REQUEST['datos'] : '');

$modelo = json_decode($modelo, true);
$datos = json_decode($datos, true);


$query='SELECT PD.Fecha FROM Producto_Descarga_Pendiente_Remision PD WHERE PD.Id_Remision'.$datos['Id_Remison'].' ORDER BY PD.Id_Producto_Descarga_Pendiente_Remision DESC LIMIT 1';
$oCon= new consulta();
$oCon->setQuery($query);
$fecha = $oCon->getData();
unset($oCon);   

$configuracion = new Configuracion();
$cod = $configuracion->Consecutivo('Acta_Recepcion_Remision'); 

$oItem = new complex('Remision','Id_Remision',$datos["Id_Remision"]);
$oItem->Estado="Recibida";
$oItem->save();
$remision= $oItem->getData();
unset($oItem);

$datos['Id_Punto_Dispensacion']=$remision['Id_Destino'];
$datos['Entrega_Pendientes']=$remision['Entrega_Pendientes'];


$datos['Codigo']=$cod;
$datos['Fecha']=$fecha['Fecha'];
$oItem = new complex("Acta_Recepcion_Remision","Id_Acta_Recepcion_Remision");

foreach($datos as $index=>$value) {
    $oItem->$index=$value;
}
$oItem->save();
$id_Acta_Recepcion_remision = $oItem->getId();
unset($oItem);

/* AQUI GENERA QR */
$qr = generarqr('actarecepcionremision',$id_Acta_Recepcion_remision,'/IMAGENES/QR/');
$oItem = new complex("Acta_Recepcion_Remision","Id_Acta_Recepcion_Remision",$id_Acta_Recepcion_remision);
$oItem->Codigo_Qr=$qr;
$oItem->save();
unset($oItem);
/* HASTA AQUI GENERA QR */


$query='SELECT PR.*, P.Codigo_Cum FROM Producto_Remision PR INNER JOIN Producto P ON PR.Id_Producto=P.Id_Producto WHERE PR.Id_Remision ='.$datos['Id_Remision'];


$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$productos = $oCon->getData();
unset($oCon);

foreach ($productos as  $item) {  
    $queryInsert[]="($item[Id_Producto],'$item[Lote]','$item[Fecha_Vencimiento]',$item[Cantidad],$item[Id_Remision],$item[Id_Producto_Remision],$id_Acta_Recepcion_remision)";
}
if(count($queryInsert)>0){
	registrarSaldos($queryInsert);
}

foreach ($modelo as  $item) {
   

    $query = 'SELECT I.Id_Inventario 
    FROM Inventario I
    WHERE I.Id_Punto_Dispensacion='.$datos['Id_Punto_Dispensacion'].' AND I.Id_Producto='.$item['Id_Producto'].' AND  I.Lote="'.$item['Lote'].'"' ;
    $oCon= new consulta();
    $oCon->setQuery($query);
    $inventario = $oCon->getData();
    unset($oCon);
   
    if($inventario){
        $query2="UPDATE Inventario SET  Cantidad=(Cantidad+$item[Cantidad]), Cantidad_Pendientes=(Cantidad_Pendientes-$item[Cantidad]) WHERE Id_Inventario=$inventario[Id_Inventario]";

        $oCon = new consulta();
        $oCon->setQuery($query2);
        $oCon->createData();
        unset($oCon);
    }else{
      
        $fecha=date("Y-m-d H:i:s");
        $queryInsertInventario[] = "($item[Id_Producto],'$item[Lote]',$item[Cantidad],'$item[Precio]','$item[Fecha_Vencimiento]',0,$datos[Id_Punto_Dispensacion],'$item[Codigo_Cum]',$datos[Identificacion_Funcionario],'$fecha' )";
      
    }

    $query = 'SELECT PAR.Id_Producto_Acta_Recepcion_Remision 
    FROM Producto_Acta_Recepcion_Remision PAR
    WHERE PAR.Id_Acta_Recepcion_Remision='.$id_Acta_Recepcion_remision.' AND PAR.Id_Producto_Remision='.$item['Id_Producto_Remision'];

    $oCon= new consulta();
    $oCon->setQuery($query);
    $productoacta = $oCon->getData();
    unset($oCon);
    if($productoacta){
        if($item['Temperatura']!=''){
            $query2="UPDATE Producto_Acta_Recepcion_Remision SET  Cumple='$item[Cumple]', Revisado='$item[Revisado]',Temperatura='$item[Temperatura]' WHERE Id_Producto_Acta_Recepcion_Remision=$productoacta[Id_Producto_Acta_Recepcion_Remision]";

            $oCon = new consulta();
            $oCon->setQuery($query2);
            $oCon->createData();
            unset($oCon);
        }else{
            $query2="UPDATE Producto_Acta_Recepcion_Remision SET  Cumple='$item[Cumple]', Revisado='$item[Revisado]' WHERE Id_Producto_Acta_Recepcion_Remision=$productoacta[Id_Producto_Acta_Recepcion_Remision]";
            $oCon = new consulta();
            $oCon->setQuery($query2);
            $oCon->createData();
            unset($oCon);
        }
       

    }


} 
if(count($queryInsertInventario)>0){
	registrarInventario($queryInsertInventario);
}

GuardarActividadRemision($datos,$remision);
//GuardarAlerta($modelo['Id_Auditoria']);

$http_response->SetRespuesta(0, 'Registro Exitoso', 'Se ha(n) agregado(s) exitosamente todos los productos !');
$response = $http_response->GetRespuesta();

echo json_encode($response);

function registrarSaldos($queryInsert){
    $query = "INSERT INTO Producto_Acta_Recepcion_Remision (Id_Producto,Lote,Fecha_Vencimiento,Cantidad,Id_Remision,Id_Producto_Remision,Id_Acta_Recepcion_Remision) VALUES " . implode(',',$queryInsert);

    $oCon = new consulta();
    $oCon->setQuery($query);
    $oCon->createData();
    unset($oCon);

    return;
}
function registrarInventario($queryInsertInventario){
    $query = "INSERT INTO Inventario (Id_Producto,Lote,Cantidad_Pendientes,Costo,Fecha_Vencimiento,Id_Bodega,Id_Punto_Dispensacion,Codigo_CUM,Identificacion_Funcionario,Fecha_Carga) VALUES " . implode(',',$queryInsertInventario); 

    $oCon = new consulta();
    $oCon->setQuery($query);
    $oCon->createData();
    unset($oCon);

    return;
}

function GuardarActividadRemision($datos, $remision){
    $oItem = new complex('Actividad_Remision',"Id_Actividad_Remision");
    $oItem->Id_Remision = $datos["Id_Remision"];
    $oItem->Identificacion_Funcionario = $datos['Identificacion_Funcionario'];
    $oItem->Detalles = "Se hace el acta de recepcion de la  ".$remision["Codigo"];
    $oItem->Estado = "Recibida";
    $oItem->save();  
    unset($oItem);    

}



	

	
?>
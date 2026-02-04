<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
require_once('../../helper/response.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');
include_once('../../class/class.contabilizar.php');
include_once('../../class/class.http_response.php');


$contabilizar = new Contabilizar();
$response = array();
$http_response = new HttpResponse(); 

$listado_inventario = isset($_REQUEST['listado_inventario']) ? $_REQUEST['listado_inventario'] : false;
$funcionario = isset($_REQUEST['id_funcionario']) ? $_REQUEST['id_funcionario'] : false;
$inventarios = isset($_REQUEST['inventarios']) ? $_REQUEST['inventarios'] : false;

$listado_inventario = (array) json_decode($listado_inventario, true);


foreach ($listado_inventario as $value) {
    
    // Registrar (actualizar) el conteo final en el producto de inventario físico

    if($value['Id_Producto_Doc_Inventario_Auditable']!=0){
        $id_inventario=explode(",",$value['Id_Producto_Doc_Inventario_Auditable']);
        for ($i=0; $i < count( $id_inventario) ; $i++) { 
            if($i!=0){
                $oItem = new complex('Producto_Doc_Inventario_Auditable', 'Id_Producto_Doc_Inventario_Auditable', $id_inventario[$i]);
                $oItem->delete();
                unset($oItem);
             }else{
                $oItem = new complex('Producto_Doc_Inventario_Auditable', 'Id_Producto_Doc_Inventario_Auditable', $id_inventario[$i]);
                $cantidad = number_format((INT)$value['Cantidad_Final'],0,'',''); // parseando
                $conteo1 = number_format((INT)$value['Cantidad_Encontrada'],0,'',''); // parseando
                $oItem->Segundo_Conteo = $cantidad;
                $oItem->Primer_Conteo = $conteo1;
                $oItem->Cantidad_Inventario =$value['Cantidad_Inventario'];
                $oItem->Fecha_Segundo_Conteo= date('Y-m-d');
                $oItem->save();
                unset($oItem);
             }
        } 
    
    }else{
        
        // $oItem = new complex('Producto_Doc_Inventario_Fisico', 'Id_Producto_Doc_Inventario_Fisico');
        // $cantidad = number_format((INT)$value['Cantidad_Final'],0,'',''); // parseando
        // $oItem->Segundo_Conteo = $cantidad;
        // $oItem->Id_Producto =$value['Id_Producto'];
        // $oItem->Id_Inventario_Nuevo =$value['Id_Inventario_Nuevo'];
        // $oItem->Primer_Conteo ="0";
        // $oItem->Fecha_Primer_Conteo = date('Y-m-d');
        // $oItem->Fecha_Segundo_Conteo = date('Y-m-d');
        // $oItem->Cantidad_Inventario = number_format($value['Cantidad_Inventario'],0,"","");
        // $oItem->Id_Doc_Inventario_Fisico = AsignarIdInventarioFisico($inventarios);
        // $oItem->Lote = strtoupper($value['Lote']);
        // $oItem->Fecha_Vencimiento = $value['Fecha_Vencimiento'];
        // $oItem->save();
        // unset($oItem);
    }
    
}

$query2='UPDATE Doc_Inventario_Auditable
SET Estado ="Segundo Conteo", Fecha_Fin="'.date('Y-m-d H:i:s').'" , Funcionario_Autorizo='.$funcionario.'
WHERE  Id_Doc_Inventario_Auditable IN ('.$inventarios.')';
$oCon= new consulta();
$oCon->setQuery($query2);     
$oCon->createData();     
unset($oCon);


//acutalizar los que no tienen diferencia
$query2='UPDATE Producto_Doc_Inventario_Auditable
SET Segundo_Conteo = Primer_Conteo
WHERE Segundo_Conteo IS NULL AND Id_Doc_Inventario_Auditable IN ('.$inventarios.')';
$oCon= new consulta();
$oCon->setQuery($query2);     
$oCon->createData();     
unset($oCon);




    $resultado['titulo'] = "Operación Exitosa";
    $resultado['mensaje'] = "Se ha guardado el segundo conteo exitosamente!";
    $resultado['tipo'] = "success";

echo json_encode($resultado);

function AsignarIdInventarioFisico($inventarios){
    $inv=explode(',',$inventarios);

    return $inv[0];
}
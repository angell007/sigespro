<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$id_acta_recepcion = isset($_REQUEST['id']) ? $_REQUEST['id'] : false;
$funcionario = isset($_REQUEST['funcionario']) ? $_REQUEST['funcionario'] : false;

if ($id_acta_recepcion) {

    $query = "SELECT PAR.Id_Producto, P.Codigo_Cum, PAR.Lote, PAR.Fecha_Vencimiento, AR.Id_Bodega, PAR.Cantidad, PAR.Precio FROM Producto_Acta_Recepcion PAR INNER JOIN Producto P ON PAR.Id_Producto=P.Id_Producto INNER JOIN Acta_Recepcion AR ON PAR.Id_Acta_Recepcion=AR.Id_Acta_Recepcion WHERE PAR.Id_Acta_Recepcion=$id_acta_recepcion" ;
    $oCon= new consulta();
    $oCon->setQuery($query);
    $oCon->setTipo('Multiple');
    $productos = $oCon->getData();
    unset($oCon);

    foreach ($productos as $producto) {
        $query = "SELECT Id_Inventario FROM Inventario WHERE Id_Producto=$producto[Id_Producto] AND Lote='$producto[Lote]' AND Fecha_Vencimiento='$producto[Fecha_Vencimiento]' AND Id_Bodega=$producto[Id_Bodega]";

        $oCon= new consulta();
        $oCon->setQuery($query);
        $inventario = $oCon->getData();
        unset($oCon);

        if ($inventario) {
            $oItem = new complex('Inventario','Id_Inventario', $inventario['Id_Inventario']);
            $cantidad = number_format($producto["Cantidad"],0,"","");
            $cantidad_final = $oItem->Cantidad + $cantidad;
            $oItem->Cantidad = $cantidad_final;
            $oItem->Costo = $producto['Precio'];
            $id_inventario = $oItem->Id_Inventario;
        } else {
            $oItem = new complex('Inventario','Id_Inventario');
            $oItem->Codigo = substr(hexdec(uniqid()),2,12);
            $oItem->Cantidad=$producto["Cantidad"];
            $oItem->Id_Producto=$producto["Id_Producto"];
            $oItem->Codigo_CUM=$producto["Codigo_Cum"];
            $oItem->Lote=$producto["Lote"];
            $oItem->Fecha_Vencimiento=$producto["Fecha_Vencimiento"];
            $oItem->Id_Bodega = $producto["Id_Bodega"];
            $oItem->Costo = $producto['Precio'];
            $oItem->Identificacion_Funcionario = $funcionario;
        }
        $oItem->save();
        $id_inventario = $oItem->getId();
        unset($oItem);

    
    }

 

    $oItem = new complex('Acta_Recepcion','Id_Acta_Recepcion', $id_acta_recepcion);
    $oItem->Estado = 'Aprobada';
    $oItem->save();
    unset($oItem);

    //Consultar el codigo del acta y el id de la orden de compra
    $query_codido_acta = 'SELECT 
                            Codigo,
                            Id_Orden_Compra_Nacional
                        FROM
                            Acta_Recepcion
                        WHERE
                            Id_Acta_Recepcion = '.$id_acta_recepcion;

    $oCon= new consulta();
    $oCon->setQuery($query_codido_acta);
    $acta_data = $oCon->getData();
    unset($oCon);

    //Guardando paso en el seguimiento del acta en cuestion
    $oItem = new complex('Actividad_Orden_Compra',"Id_Acta_Recepcion_Compra");
    $oItem->Id_Orden_Compra_Nacional=$acta_data['Id_Orden_Compra_Nacional'];
    $oItem->Id_Acta_Recepcion_Compra=$id_acta_recepcion;
    $oItem->Identificacion_Funcionario=$funcionario;
    $oItem->Detalles="Se aprobo y se ingreso el Acta con codigo ".$acta_data['Codigo'];
    $oItem->Fecha=date("Y-m-d H:i:s");
    $oItem->Estado ='Aprobacion';
    $oItem->save();
    unset($oItem);

    if ($id_inventario) {
        $resultado['mensaje'] = "Se ha aprobado e ingresado correctamente el acta al inventario";
        $resultado['tipo'] = "success";
        $resultado['titulo'] = "Operación Exitosa";
    } else {
        $resultado['mensaje'] = "Ha ocurrido un error inesperado. Por favor intentelo de nuevo";
        $resultado['tipo'] = "error";
        $resultado['titulo'] = "Error";
    }

    echo json_encode($resultado);

}



?>
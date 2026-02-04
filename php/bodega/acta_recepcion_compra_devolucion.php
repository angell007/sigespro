<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$codigo = ( isset( $_REQUEST['codigo'] ) ? $_REQUEST['codigo'] : '' );
$tipoCompra = ( isset( $_REQUEST['compra'] ) ? $_REQUEST['compra'] : '' );
$id = ( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : '' );

/* $oItem = new complex('Orden_Compra_'.$tipoCompra, 'Codigo', $codigo, 'Varchar');
$attr = 'Id_Orden_Compra_' . $tipoCompra;
$id_compra = $oItem->$attr; */

switch($tipoCompra){
    
    case "Nacional":{
        $query = 'SELECT  
        IFNULL(CONCAT( P.Principio_Activo, " ", P.Presentacion, " ", P.Concentracion, " (", P.Nombre_Comercial,") ", P.Cantidad," ", P.Unidad_Medida, " LAB- ", P.Laboratorio_Comercial ),CONCAT(P.Nombre_Comercial, " LAB-",P.Laboratorio_Comercial)) as Nombre_Producto,
        PNC.Cantidad as CantidadProducto,
      (SELECT POC.Costo FROM Producto_Orden_Compra_Nacional POC WHERE POC.Id_Producto=PNC.Id_Producto AND POC.Id_Orden_Compra_Nacional=PNC.Id_Compra LIMIT 1 ) as CostoProducto,
         (SELECT POC.Id_Producto_Orden_Compra_Nacional FROM Producto_Orden_Compra_Nacional POC WHERE POC.Id_Producto=PNC.Id_Producto AND POC.Id_Orden_Compra_Nacional=PNC.Id_Compra LIMIT 1 ) AS Id_Producto_Orden_Compra,
        P.Embalaje,P.Nombre_Comercial,
        P.Id_Producto as Id_Producto,
        P.Codigo_Cum as Codigo_CUM,
        IF(P.Gravado="Si",19,0) AS Impuesto,
        P.Imagen AS Foto,
        P.Id_Categoria,
        P.Peso_Presentacion_Regular AS Peso,
        IF(P.Codigo_Barras IS NULL, "No", "Si") AS Codigo_Barras,
        0 as Cantidad,
        0 as Cantidad_Band,	
        0 as Precio,
        0 as Subtotal,
        0 as Iva,	
        "" as Lote,
        "" as Fecha_Vencimiento,	
        0 as No_Conforme,
        false as Checkeado,
        true AS Required, 
        "No"as Eliminado
   FROM
    Producto_No_Conforme PNC           
       INNER JOIN Producto P 
        ON P.Id_Producto = PNC.Id_Producto                
   WHERE PNC.Id_No_Conforme='.$id ;
           
           $query1 ="SELECT 'Nacional' AS Tipo, Id_Orden_Compra_Nacional AS Id_Orden_Compra, Codigo, Identificacion_Funcionario, Id_Bodega, (SELECT P.Nombre FROM Proveedor P WHERE P.Id_Proveedor=OCN.Id_Proveedor) AS Proveedor, OCN.Id_Proveedor FROM Orden_Compra_Nacional OCN WHERE Codigo = '".$codigo."'";
           
        break;
    }
 
}

$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$res = $oCon->getData();
unset($oCon);


$oCon= new consulta();
$oCon->setQuery($query1);
$res1 = $oCon->getData();
unset($oCon);

$resultado['encabezado'] = $res1;
$resultado['producto'] = $res;

$i = -1;
foreach ($res as $value) {$i++;
    $resultado['producto'][$i]['producto'][] = $res[$i];
}

$query_retenciones = '
  SELECT
    P.Tipo_Retencion,
    P.Id_Plan_Cuenta_Retefuente,
    (IF(P.Id_Plan_Cuenta_Retefuente IS NULL OR P.Id_Plan_Cuenta_Retefuente = 0, "", (SELECT Nombre FROM Plan_Cuentas WHERE Id_Plan_Cuentas = P.Id_Plan_Cuenta_Retefuente))) AS Nombre_Retefuente,
    (IF(P.Id_Plan_Cuenta_Retefuente IS NULL OR P.Id_Plan_Cuenta_Retefuente = 0, "0", (SELECT Porcentaje FROM Retencion WHERE Id_Plan_Cuenta = P.Id_Plan_Cuenta_Retefuente))) AS Porcentaje_Retefuente,
    (IF(P.Id_Plan_Cuenta_Retefuente IS NULL OR P.Id_Plan_Cuenta_Retefuente = 0, "0", (SELECT Id_Retencion FROM Retencion WHERE Id_Plan_Cuenta = P.Id_Plan_Cuenta_Retefuente))) AS Id_Retencion_Fte,
    P.Tipo_Reteica,
    P.Id_Plan_Cuenta_Reteica,
    (IF(P.Id_Plan_Cuenta_Reteica IS NULL OR P.Id_Plan_Cuenta_Reteica = 0, "", (SELECT Nombre FROM Plan_Cuentas WHERE Id_Plan_Cuentas = P.Id_Plan_Cuenta_Reteica))) AS Nombre_Reteica,
    (IF(P.Id_Plan_Cuenta_Reteica IS NULL OR P.Id_Plan_Cuenta_Reteica = 0, "0", (SELECT Porcentaje FROM Retencion WHERE Id_Plan_Cuenta = P.Id_Plan_Cuenta_Reteica))) AS Porcentaje_Reteica,
    (IF(P.Id_Plan_Cuenta_Retefuente IS NULL OR P.Id_Plan_Cuenta_Retefuente = 0, "0", (SELECT Id_Retencion FROM Retencion WHERE Id_Plan_Cuenta = P.Id_Plan_Cuenta_Reteica))) AS Id_Retencion_Ica,
    P.Contribuyente,
    P.Id_Plan_Cuenta_Reteiva,
    (IF(P.Id_Plan_Cuenta_Reteiva IS NULL OR P.Id_Plan_Cuenta_Reteiva = 0, "", (SELECT Nombre FROM Plan_Cuentas WHERE Id_Plan_Cuentas = P.Id_Plan_Cuenta_Reteiva))) AS Nombre_Reteiva,
    (IF(P.Id_Plan_Cuenta_Reteiva IS NULL OR P.Id_Plan_Cuenta_Reteiva = 0, "0", (SELECT Porcentaje FROM Retencion WHERE Id_Plan_Cuenta = P.Id_Plan_Cuenta_Reteiva))) AS Porcentaje_Reteiva,
    (IF(P.Id_Plan_Cuenta_Retefuente IS NULL OR P.Id_Plan_Cuenta_Retefuente = 0, "0", (SELECT Id_Retencion FROM Retencion WHERE Id_Plan_Cuenta = P.Id_Plan_Cuenta_Reteiva))) AS Id_Retencion_Iva,
    Regimen
  FROM Proveedor P
  WHERE
    Id_Proveedor = '.$res1['Id_Proveedor'];

$oCon= new consulta();
$oCon->setQuery($query_retenciones);
$retenciones_proveedor = $oCon->getData();
unset($oCon);

$resultado['Data_Retenciones']=$retenciones_proveedor;

$query_configuracion = '
  SELECT
    Valor_Unidad_Tributaria,
    Base_Retencion_Compras_Reg_Comun,
    Base_Retencion_Compras_Reg_Simpl,
    Base_Retencion_Compras_Ica,
    Base_Retencion_Iva_Reg_Comun
  FROM Configuracion
  WHERE
      Id_Configuracion = 1';

$oCon= new consulta();
$oCon->setQuery($query_configuracion);
$valores_retenciones = $oCon->getData();
unset($oCon);

$resultado['Valores_Base_Retenciones']=$valores_retenciones;

echo json_encode($resultado);
          
?>
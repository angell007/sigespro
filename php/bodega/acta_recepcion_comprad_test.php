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

switch($tipoCompra){
    
    case "Nacional":{
        $query = 'SELECT  COUNT(*) as Total_Items
           FROM Producto_Orden_Compra_Nacional POCN 
               INNER JOIN Producto P 
                ON P.Id_Producto = POCN.Id_Producto 
               INNER JOIN Orden_Compra_Nacional OCN
                ON OCN.Id_Orden_Compra_Nacional = POCN.Id_Orden_Compra_Nacional
           WHERE OCN.Codigo ="'.$codigo.'"' ;
           
           $query1 ="SELECT 'Nacional' AS Tipo, Id_Orden_Compra_Nacional AS Id_Orden_Compra, Codigo, Identificacion_Funcionario, Id_Bodega, (SELECT P.Nombre FROM Proveedor P WHERE P.Id_Proveedor=OCN.Id_Proveedor) AS Proveedor, OCN.Id_Proveedor FROM Orden_Compra_Nacional OCN WHERE Codigo = '".$codigo."'";


            $query2=' SELECT GROUP_CONCAT(POCN.Id_Producto) as Id_Producto,  GROUP_CONCAT(SUBSTRING_INDEX(P.Codigo_Cum,"-",1)) as Cum,POCN.Id_Orden_Compra_Nacional as Id_Orden_Nacional
            FROM Producto_Orden_Compra_Nacional POCN 
                INNER JOIN Producto P 
                 ON P.Id_Producto = POCN.Id_Producto 
                INNER JOIN Orden_Compra_Nacional OCN
                 ON OCN.Id_Orden_Compra_Nacional = POCN.Id_Orden_Compra_Nacional
            WHERE OCN.Codigo ="'.$codigo.'"';
           
        break;
    }
    case "Internacional":{
        $query = 'SELECT COUNT(*) as Total_Items
           FROM Producto_Orden_Compra_Internacional POCN 
               INNER JOIN Producto P 
                ON P.Id_Producto = POCN.Id_Producto 
               INNER JOIN Orden_Compra_Internacional OCN
                ON OCN.Id_Orden_Compra_Internacional = POCN.Id_Orden_Compra_Internacional
           WHERE OCN.Codigo ="'.$codigo.'" GROUP BY POCN.Id_Producto' ;
        
        $query1 ="SELECT 'Internacional' AS Tipo, Id_Orden_Compra_Internacional AS Id_Orden_Compra, Codigo, Identificacion_Funcionario, Id_Bodega, (SELECT P.Nombre FROM Proveedor P WHERE P.Id_Proveedor=OCI.Id_Proveedor) AS Proveedor, OCI.Id_Proveedor FROM  Orden_Compra_Internacional OCI WHERE Codigo = '".$codigo."'";

        $query2 = 'SELECT GROUP_CONCAT(POCN.Id_Producto) as Id_Producto, GROUP_CONCAT(P.Codigo_Cum) as Cum
        FROM Producto_Orden_Compra_Internacional POCN 
            INNER JOIN Producto P 
             ON P.Id_Producto = POCN.Id_Producto 
            INNER JOIN Orden_Compra_Internacional OCN
             ON OCN.Id_Orden_Compra_Internacional = POCN.Id_Orden_Compra_Internacional
        WHERE OCN.Codigo ="'.$codigo.'" GROUP BY POCN.Id_Producto' ;
        
        break;
    }
}

$oCon= new consulta();
$oCon->setQuery($query);
$res = $oCon->getData();
unset($oCon);


$oCon= new consulta();
$oCon->setQuery($query1);
$res1 = $oCon->getData();
unset($oCon);

$oCon= new consulta();
$oCon->setQuery($query2);
$id_productos = $oCon->getData();
unset($oCon);

$resultado['encabezado'] = $res1;
$resultado['Items'] = $res['Total_Items'];
$resultado['Productos']=$id_productos;

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
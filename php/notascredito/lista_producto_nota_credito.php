<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once '../../config/start.inc.php';
include_once '../../class/class.lista.php';
include_once '../../class/class.validacion_cufe.php';
include_once '../../class/class.complex.php';
include_once '../../class/class.consulta.php';

$id = (isset($_REQUEST['id']) ? $_REQUEST['id'] : '');

$response = [];
if ($id == '') {
    echo json_encode(array());
    return;
}

if (!validarExistenciaNotaGlobal()) {
  $aceptada=ValidarAceptacionCufe($id);
  if(!$aceptada){

  

    $productos_excluir = GetProductosNotaCreditoFactura($id);

    $condicion_productos_excluir = ' HAVING Cantidad > 0';

    $query2 = 'SELECT PFV.*,
      IFNULL(CONCAT(P.Nombre_Comercial, " - ",P.Principio_Activo, " ", P.Cantidad,"", P.Unidad_Medida, " " , P.Presentacion, "\n", P.Invima, " CUM:", P.Codigo_Cum),
      CONCAT(P.Nombre_Comercial, " LAB-", P.Laboratorio_Comercial)) as producto,
      P.Id_Producto,
      IF(P.Laboratorio_Generico IS NULL,P.Laboratorio_Comercial,P.Laboratorio_Generico) as Laboratorio,
      P.Presentacion,
      P.Codigo_Cum as Cum,
      PFV.Fecha_Vencimiento as Vencimiento,
      PFV.Lote as Lote,
      "true" as Disabled, 0 as Subtotal_Nota, 0 as Iva,
      (PFV.Cantidad - (SELECT IFNULL(SUM(PNC.Cantidad), 0) FROM Producto_Nota_Credito PNC INNER JOIN Nota_Credito NC ON PNC.Id_Nota_Credito = NC.Id_Nota_Credito WHERE NC.Id_Factura = PFV.Id_Factura_Venta AND PNC.Id_Producto = PFV.Id_Producto AND PNC.Lote = PFV.Lote AND NC.Estado!="Anulada")) AS Cantidad
      FROM Producto_Factura_Venta PFV

      LEFT JOIN Producto P ON P.Id_Producto = PFV.Id_Producto
      WHERE PFV.Id_Factura_Venta =' . $id . $condicion_productos_excluir;

    $oCon = new consulta();
    $oCon->setQuery($query2);
    $oCon->setTipo('Multiple');
    $productos = $oCon->getData();
    unset($oCon);

    if (count($productos) == 0) {
        $query22 = 'SELECT PFV.*,
        IFNULL(CONCAT(P.Principio_Activo, " ", P.Cantidad,"", P.Unidad_Medida, " " , P.Presentacion, "\n", P.Invima, " CUM:", P.Codigo_Cum), CONCAT(P.Nombre_Comercial, " LAB-", P.Laboratorio_Comercial)) as producto,
        P.Id_Producto,
        IF(P.Laboratorio_Generico IS NULL,P.Laboratorio_Comercial,P.Laboratorio_Generico) as Laboratorio,
        P.Presentacion,
        P.Codigo_Cum as Cum,
        PFV.Fecha_Vencimiento as Vencimiento,
        PFV.Lote as Lote,
        PFV.Id_Inventario_Nuevo as Id_Inventario_Nuevo,
        PFV.Precio_Venta as Costo_unitario,
        PFV.Cantidad as Cantidad,
        PFV.Precio_Venta as PrecioVenta,
        PFV.Subtotal as Subtotal,
        PFV.Id_Producto_Factura_Venta as idPFV,"true" as Disabled, 0 as Subtotal_Nota, 0 as Iva,
        (PFV.Cantidad - (SELECT IFNULL(SUM(PNC.Cantidad), 0) FROM Producto_Nota_Credito PNC INNER JOIN Nota_Credito NC ON PNC.Id_Nota_Credito = NC.Id_Nota_Credito WHERE NC.Id_Factura = PFV.Id_Factura_Venta AND PNC.Id_Producto = PFV.Id_Producto AND PNC.Lote = PFV.Lote)) AS Cantidad
        FROM Producto_Factura_Venta PFV
        LEFT JOIN Producto P ON PFV.Id_Producto = P.Id_Producto
        WHERE PFV.Id_Factura_Venta =' . $id . $condicion_productos_excluir;

        $oCon = new consulta();
        $oCon->setQuery($query22);
        $oCon->setTipo('Multiple');
        $productos = $oCon->getData();
        unset($oCon);
    }

    $response['type'] = 'success';
    $response['data'] = $productos;
  }
  else{
    $response['title'] = 'error';
    $response['type'] = 'error';
    $response['message'] = $aceptada;

  }

}

echo json_encode($response);

function GetProductosNotaCreditoFactura($idFactura)
{
    $query = '
			SELECT
				IFNULL(GROUP_CONCAT(Id_Producto), 0) AS Excluir_Productos
			FROM Nota_Credito NC
			INNER JOIN Producto_Nota_Credito PNC ON NC.Id_Nota_Credito = PNC.Id_Nota_Credito
			WHERE
				NC.Id_Factura = ' . $idFactura . ' AND NC.Estado!="Anulada" ';

    $oCon = new consulta();
    $oCon->setQuery($query);
    $oCon->setTipo('simple');
    $productos_nota_credito = $oCon->getData();
    unset($oCon);

    return $productos_nota_credito['Excluir_Productos'];
}

function validarExistenciaNotaGlobal()
{

    global $id, $response;

    $oItem = new complex('Factura_Venta', 'Id_Factura_Venta', $id);
    $factura = $oItem->getData();
    unset($oItem);

    $query = ' SELECT GROUP_CONCAT( Codigo )   AS Codigos
              FROM Nota_Credito_Global
             WHERE Codigo_Factura = "' . $factura['Codigo'] . '"
             GROUP BY Codigo_Factura';
    $oCon = new consulta();
    $oCon->setQuery($query);
    $notas_globales = $oCon->getData();

    if (!$notas_globales) {
        # code...
        return false;
    }
    $response['type'] = 'error';
    $response['title'] = 'OOPS! Existe Nota Crédito creada para esta factura';
    $response['message'] = 'Se ha realizado nota credito tipo precio (NO AFECTA INVENTARIO) con anterioridad : ' . $notas_globales['Codigos'];

    return true;

}
function ValidarAceptacionCufe($id_factura)
{
    $oItem = new complex('Factura_Venta', 'Id_Factura_Venta', $id_factura);
    $cufe = $oItem->getData()['Cufe'];

    $validarCufe = new ValidarCufe($cufe);
    $dataFacturaDian = $validarCufe->getEstructura();
    // echo json_encode($dataFacturaDian); exit;
    if($dataFacturaDian=="error"){
        return "Hubo un error al intentar procesar el cufe de la factura";
    }
    if ($dataFacturaDian) {
        foreach ($dataFacturaDian['Eventos'] as $evento) {
            if (strpos($evento['Description'], 'Aceptación')!==false) {
                return "Factura cuenta con Aceptacion ante la DIAN, no se permite hacer nota Crédito";
            }
        }
        return false;
    }
    return "Hubo un error al intentar procesar el cufe de la factura";
}

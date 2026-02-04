<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once '../../config/start.inc.php';
include_once '../../class/class.lista.php';
include_once '../../class/class.complex.php';
include_once '../../class/class.consulta.php';
include_once '../../class/class.validacion_cufe.php';

$id = (isset($_REQUEST['id']) ? $_REQUEST['id'] : '');
$modelo = (isset($_REQUEST['modelo']) ? $_REQUEST['modelo'] : '');
$codigo = (isset($_REQUEST['codigo']) ? $_REQUEST['codigo'] : '');
$tipoCliente = (isset($_REQUEST['tipoCliente']) ? $_REQUEST['tipoCliente'] : '');
$tipoTercero = $modelo == 'Documento_No_Obligados' ? 'Proveedor' : 'Cliente';

$joins = joins_db($modelo);
$where = condicion_db($modelo, $id, $codigo);
$selects = selects_db($modelo);
$query = "$selects
      FROM $modelo F
      $joins
      $where";
      

$oCon = new consulta();
$oCon->setQuery($query);

$factura = $oCon->getData();
unset($oCon);

if ($factura) {

    $aceptada = ValidarAceptacionCufe($factura['Id_Factura'], $modelo);
    if (!$aceptada) {

        if (!validarExistenciaNota($factura['Id_Factura'])) {
            $valor_nota = factura_nota_credito($factura['Id_Factura'], $modelo);

            $valor_factura = select_db_productos($factura['Id_Factura']);

            if ($valor_factura > $valor_nota) {
                $resultado['tipo'] = 'success';
                $resultado['Factura'] = $factura;
            } else {

                $resultado['tipo'] = 'error';
                $resultado['title'] = 'Factura con nota crédito';
                $resultado['mensaje'] = 'A esta factura ya se le realizó una nota por el valor total de la factura';
            }
        } else {

            $resultado['tipo'] = 'error';
            $resultado['title'] = 'Factura con nota crédito';
            $resultado['mensaje'] = 'A esta factura ya se le realizó una nota Credito de Otro tipo';
        }
    } else {
        $resultado['title'] = 'error';
        $resultado['tipo'] = 'error';
        $resultado['mensaje'] = $aceptada;

    }
} else {
    $resultado['tipo'] = 'error';
    $resultado['title'] = 'Factura no encotrada';
    $resultado['mensaje'] = 'No se ha encontrada factura asociada a ese código';
}

echo json_encode($resultado);

//funciones generales para los modelos de factura
function selects_db($modelo)
{
    $selects = 'SELECT
      F.Id_' . $modelo . '  AS Id_Factura, F.Codigo as Codigo, F.Nota_Credito ';

    if ($modelo == 'Factura') {
        $selects .= ' , F.Id_Dispensacion';
    }
    return $selects;
}

function joins_db($modelo)
{
    global $tipoCliente;
    $joins = '';
/*       if ($modelo=='Factura_Administrativa') {
INNER JOIN '.$tipoCliente.' C ';
if ($tipoCliente=='Funcionario') {
$joins.='ON F.Id_Cliente=C.Identificacion_Funcionario';
}
if ($tipoCliente=='Cliente') {
$joins.='ON F.Id_Cliente=C.Id_Cliente';
}
if ($tipoCliente=='Proveedor') {
$joins.='ON F.Id_Cliente=C.Id_Proveedor';
}

}else{
$joins ='
INNER JOIN Cliente C
ON F.Id_Cliente=C.Id_Cliente
';
} */

    if ($modelo == 'Factura_Capita' || $modelo == 'Factura_Administrativa' || $modelo == 'Documento_No_Obligados') {
        $joins .= '
            INNER JOIN  Descripcion_' . $modelo . ' PF
            ON PF.Id_' . $modelo . '=F.Id_' . $modelo . ' ';
    } else {
        $joins .= '
            INNER JOIN Producto_' . $modelo . ' PF
            ON PF.Id_' . $modelo . ' = F.Id_' . $modelo;
    }
    return $joins;
}

function condicion_db($modelo, $id, $codigo)
{
    global $tipoCliente, $tipoTercero;
    $condicion = "
      WHERE F.Id_$tipoTercero = $id";
    if ($modelo == 'Documento_No_Obligados') {
        $condicion .= ' AND F.Tipo_Proveedor ="' . $tipoCliente . '" ';
    } else {

        if ($modelo == 'Factura_Venta') {
            $condicion .= ' AND F.Estado <> "Anulada" AND F.Estado <> "Pagada" ';
        } else {
            $condicion .= ' AND F.Estado_Factura <> "Anulada" AND F.Estado_Factura <> "Pagada" ';
        }
        if ($modelo == 'Factura_Administrativa') {
            $condicion .= 'AND F.Tipo_Cliente ="' . $tipoCliente . '" ';
        }
    }
    $condicion .= ' AND F.Codigo = "' . $codigo . '"';
    return $condicion;
}

function factura_nota_credito($id_factura, $modelo)
{
    $query = 'SELECT Id_Nota_Credito_Global, Codigo FROM Nota_Credito_Global WHERE Tipo_Factura = "' . $modelo . '" AND Id_Factura = ' . $id_factura;
    $oCon = new consulta();
    $oCon->setQuery($query);
    $oCon->setTipo('Multiple');
    $notas_creditos = $oCon->getData();
    unset($oCon);
    $total_de_notas = 0;
    if ($notas_creditos) {
        # code...
        foreach ($notas_creditos as $nota) {
            # code...

            $query = 'SELECT SUM(Valor_Nota_Credito) AS Total_Nota
                  FROM Producto_Nota_Credito_Global
                  WHERE Id_Nota_Credito_Global = ' . $nota['Id_Nota_Credito_Global'] . '
                  GROUP BY Id_Nota_Credito_Global';

            $oCon = new consulta();

            $oCon->setQuery($query);
            $valor = $oCon->getData();
            $total_de_notas += $valor['Total_Nota'];

        }
    }
    return $total_de_notas;

}

function select_db_productos($id_factura)
{
    global $modelo;

    $modelo_producto = '';

    if ($modelo == 'Factura_Capita' || $modelo == 'Factura_Administrativa' || $modelo == 'Documento_No_Obligados') {
        $modelo_producto = 'Descripcion_' . $modelo;
    } else {
        $modelo_producto = 'Producto_' . $modelo;
    }
    //GENERALES
    $query = 'SELECT  PF.Cantidad, PF.Descuento, PF.Impuesto,';

    //productos y ids modelo producto

    // seleccionar precio
    if ($modelo_producto == 'Producto_Factura_Venta') {
        # code...
        $query .= 'PF.Precio_Venta AS Precio ';
    } else {
        $query .= 'PF.Precio';
    }
    $query .= ' FROM ' . $modelo_producto . ' PF WHERE Id_' . $modelo . '=' . $id_factura;

    $oCon = new consulta();

    $oCon->setQuery($query);
    $oCon->setTipo('Multiple');
    $productos = $oCon->getData();

    $acumulador = 0;
    foreach ($productos as $producto) {
        $acumulador += calcularSubtotal($producto);
    }
    /*  var_dump($acumulador); */
    return $acumulador;
}

function calcularSubtotal($Item)
{
    global $modelo;
    // if ($modelo == "Factura_Venta") {
    //     $descuento = ($Item["Cantidad"] * $Item["Precio"]) * $Item["Descuento"] / 100;
    // } else {
    if ($modelo == "Factura" || $modelo == "Factura_Capita") {
        $descuento = $Item["Descuento"];
    } else {
        $descuento = $Item["Descuento"] * $Item["Precio"] / 100;
    }
    $descuento = $Item["Cantidad"] * $descuento;

    // }

    $valor_iva = ((float) ($Item['Impuesto']) / 100) * (((float) ($Item['Cantidad']) * (float) ($Item['Precio'])) - ((float) ($descuento)));
    $subtotal = ((float) ($Item['Cantidad']) * (float) ($Item['Precio'])) - ((float) ($descuento));
    $resultado = $subtotal + $valor_iva;

    return $resultado;
}
function validarExistenciaNota($id_factura)
{
    global $modelo;
    if ($modelo == 'Factura_Venta') {
        $query = "SELECT *
                  FROM Nota_Credito
                  Where Id_Factura=$id_factura
                  ";
        $oCon = new consulta();
        $oCon->setQuery($query);
        $factura = $oCon->getData();
        unset($oCon);
        if ($factura) {
            return true;
        } else {
            return false;
        }
    }
    return false;
}

function ValidarAceptacionCufe($id_factura, $modelo)
{
    $oItem = new complex($modelo, "Id_$modelo", $id_factura);
    $cufe = $oItem->getData();
    $cufe = $cufe['Cuds'] ? $cufe['Cuds'] : $cufe['Cufe'];
    $validarCufe = new ValidarCufe($cufe);
    $dataFacturaDian = $validarCufe->getEstructura();
    // echo json_encode($dataFacturaDian); exit;
    if ($dataFacturaDian) {
        foreach ($dataFacturaDian['Eventos'] as $evento) {
            if (strpos($evento['Description'], 'Aceptación') !== false) {
                return "Factura cuenta con Aceptacion ante la DIAN, no se permite hacer nota Crédito";
            }
        }
        return false;
    }
    return "Hubo un error al intentar procesar el cufe de la factura";
}

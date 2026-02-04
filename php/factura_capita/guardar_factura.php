<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

/* Inicio llamado funciones basicas sistema */
require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');
require_once('../../class/class.configuracion.php');
include_once('../../class/class.contabilizar.php');
require_once('../../class/class.qr.php');  /* AGREGAR ESTA CLASE PARA GENERAR QR */
include_once('../../class/class.facturacion_electronica.php');

/* Fin llamado funciones basicas sistema */

/**se inicializan variables */
$contabilizar = new Contabilizar(); //clase contabilizar
$configuracion = new Configuracion();//clase configuracion
$mod = ( isset( $_REQUEST['modulo'] ) ? $_REQUEST['modulo'] : '' ); // datos de los modulo que se esta ejecutando
$datos = ( isset( $_REQUEST['datos'] ) ? $_REQUEST['datos'] : '' ); // datos del formulario
$descripcion = ( isset( $_REQUEST['productos'] ) ? $_REQUEST['productos'] : '' ); // productos de la factura traidos del formulario
$punto = ''; // variable que contiene el punto de dispensaci��n
$id_factura_capita = null; // inicializa para evitar notices cuando la creacion falla
/**fin de la inicializacion  de variables */

$datos = (array) json_decode($datos); // convierte los datos del formulario en un array
$descripciones = (array) json_decode($descripcion , true); // convierte los productos de la factura en un array
// Se añade validación inicial para asegurar que existan datos mínimos.
// Sin datos no se puede continuar el flujo de creación de factura.
$resultado = array();

if (empty($datos)) {
    $resultado['mensaje'] = 'No se recibieron datos para procesar la factura.';
    $resultado['tipo'] = 'error';
    echo json_encode($resultado);
    exit;
}

// Se añade validación del departamento porque es obligatorio para
// consultar la resolución y definir consecutivos. Sin este dato todo el
// proceso quedaría inconsistente.
if (!isset($datos['Id_Departamento']) || $datos['Id_Departamento'] === '') {
    $resultado['mensaje'] = 'Debe indicar el departamento al que corresponde la factura.';
    $resultado['tipo'] = 'error';
    echo json_encode($resultado);
    exit;
}

$datos['Id_Departamento'] = (int) $datos['Id_Departamento'];

/* Se añade control de Condición de Pago para evitar errores cuando
 el valor viene vacío o no numérico. La plataforma requiere un valor
 válido, así que se asigna un default seguro (1). */
if (!isset($datos['Condicion_Pago']) || $datos['Condicion_Pago'] === '' || !is_numeric($datos['Condicion_Pago'])) {
    $datos['Condicion_Pago'] = 1;
} else {
    $datos['Condicion_Pago'] = (int) $datos['Condicion_Pago'];
}

/* Se añade cálculo automático de Fecha_Pago para evitar facturas con
 fecha vacía. Si no viene definida, se toma el último día del mes
 indicado; si no hay mes válido, se usa la fecha actual. Esto garantiza
 que siempre exista una fecha correcta sin romper el proceso.*/
if (empty($datos['Fecha_Pago'])) {
    $fechaPago = null;

    if (!empty($datos['Mes'])) {
        try {
            $fechaPago = new DateTime($datos['Mes'] . '-01');
            $fechaPago->modify('last day of this month');
        } catch (Exception $e) {
            $fechaPago = null;
        }
    }

    if (!$fechaPago) {
        $fechaPago = new DateTime();
    }

    $datos['Fecha_Pago'] = $fechaPago->format('Y-m-d');
}


/* $resultado['mensaje'] = "Estamos en mantenimiento, haciendole mejoras al proceso de facturaci��n.";
$resultado['tipo'] = "info";
echo json_encode($resultado);
exit; */

/*$oItem = new complex('Resolucion','Id_Departamento',$datos['Id_Departamento']);
$row = $oItem->getData();*/



/**se obtiene la resolucion vigente */
$query='SELECT * FROM Resolucion WHERE (Id_Departamento='.$datos['Id_Departamento'].' OR Id_Departamento=0) AND Modulo="Capita" AND Fecha_Fin>=CURDATE() AND Estado = "Activo" AND Consecutivo<=Numero_Final ORDER BY Fecha_Fin ASC LIMIT 1';
//este query trae la resolucion vigente por el departamento.
$oCon= new consulta();
$oCon->setQuery($query);
$row = $oCon->getData();

unset($oCon); 
/**fin se obtiene la resolucion vigente */

/* se obtiene el consecutivo de la factura */
if ($row) {

    /* se obtiene el consecutivo de la factura */

    $cod = getConsecutivo($row); // se obtiene el consecutivo de la factura

    /* se asigna el consecutivo a la factura */
    $datos['Codigo']=$cod; // se  guarda el consecutivo de la factura 
    $datos['Id_Resolucion']=$row['Id_Resolucion']; // se asigna la resolucion a la factura

    /* se obtiene el punto de dispensaci��n */
    if (isset($datos['Id_Punto_Dispensacion']) && $datos['Id_Punto_Dispensacion'] != "0") { // Si existe la variable que contiene el punto de dispensaci��n y el valor sea diferente a "TODOS".
        $punto = $datos['Id_Punto_Dispensacion']; // asigna el punto de dispensaci��n a la variable.
    }

    /* se guarda la factura*/
    /** la variable mod contiene el nombre del modulo "Factura_Capita" */

    /**
     * La clase Complex act��a como un modelo gen��rico para interactuar con la base de datos.
     *
     * Al instanciarla, se le asigna el nombre de la tabla y un identificador (ID).
     * Esto permite acceder a un registro espec��fico, consultar sus datos y 
     * modificarlos sin necesidad de escribir consultas SQL manuales.
     *
     * En resumen, centraliza la l��gica de comunicaci��n con la base de datos
     * y facilita la lectura y modificaci��n de registros.
     */

    $oItem = new complex($mod,"Id_".$mod); // se instancia la clase complex para modificar o insertar la factura

    // Se añade este bloque para manejar correctamente el ID del cliente, ya que
// en algunos formularios el cliente llega como objeto, en otros como array
// y en otros como Id_Cliente directo. Sin este control, el sistema intentaba
// guardar el objeto completo dentro de la factura, generando errores en la BD.
$idCliente = null;

if (isset($datos['Cliente'])) {
    $cliente = $datos['Cliente'];

    // Se detecta si viene como objeto o arreglo para extraer únicamente el ID.
    if (is_object($cliente) && isset($cliente->Id_Cliente)) {
        $idCliente = $cliente->Id_Cliente;

    } elseif (is_array($cliente) && isset($cliente['Id_Cliente'])) {
        $idCliente = $cliente['Id_Cliente'];
    }

    // Se elimina el campo para evitar que Complex intente mapearlo
    // y cause errores al guardar la factura.
    unset($datos['Cliente']);
}

// Se añade fallback porque algunos módulos envían directamente Id_Cliente
// suelto en lugar del objeto/array. Esto mantiene compatibilidad.
if (!$idCliente && !empty($datos['Id_Cliente'])) {
    $idCliente = $datos['Id_Cliente'];
}

// Si después de todas las comprobaciones no se logra identificar el cliente,
// se detiene el proceso para evitar crear facturas huérfanas o inválidas.
if (!$idCliente) {
    $resultado['mensaje'] = 'No fue posible identificar el cliente asociado a la factura.';
    $resultado['tipo'] = 'error';
    echo json_encode($resultado);
    exit;
}

    $datos['Id_Cliente'] = $idCliente; // Se agrega codigo para que tome el id del cliente 24/09/2025

    // Se recorren todos los pares ��ndice => valor del arreglo $datos.
    // Por cada elemento, se asigna din��micamente la propiedad al objeto $oItem
    // (instancia de Complex). De esta forma, las claves de $datos se convierten
    // en nombres de atributos y los valores en su respectivo contenido.
    foreach($datos as $index=>$value) { 
        $oItem->$index=$value;
    }
    $oItem->save(); // se gaurda el registro en la base de datos 
    $id_factura_capita = $oItem->getId(); // se obtiene el id de la factura
    $qr = generarqr('facturacapita',$id_factura_capita,'IMAGENES/QR/'); // se genera el qr
    $oItem->Codigo_Qr=$qr; // se guarda la ruta del qr
    $oItem->save(); // se gaurda el registro en la base de datos
    unset($oItem); // se libera la memoria

    //unset($oItem); // se libera la memoria

    /* AQUI GENERA QR */
    //$oItem = new complex("Factura_Capita","Id_Factura_Capita",$id_factura_capita); //
    /* HASTA AQUI GENERA QR */
    if (!empty($descripciones)) {
        unset($descripciones[count($descripciones)-1]); // se elimina el ultimo elemento del array
    }


    /**
     * se recorre los productos de la factura y se guardan en la base de datos en la tabla Descripcion_Factura_Capita
     * para esto se instancia la clase complex
     * La clase Complex act��a como un modelo gen��rico para interactuar con la base de datos.
     * se le pasa el nombre de la tabla y un identificador (ID).
     * seguardan los productos de la factura
     */

    foreach($descripciones as $descripcion){ //
        $oItem = new complex('Descripcion_'.$mod,"Id_Descripcion_".$mod); //se instancia la clase complex
        $oItem->Id_Factura_Capita=$id_factura_capita; // se asigna el id de la factura
        $oItem->Descripcion = $descripcion["Descripcion"];// se asigna la descripcion
        $oItem->Cantidad=number_format($descripcion["Cantidad"],0,"","");// se asigna la cantidad
        $oItem->Precio=number_format($descripcion["Precio"],2,".",""); // se asigna el precio
        $oItem->Descuento=number_format($descripcion["Descuento"],0,"","");// se asigna el descuento
        $oItem->Impuesto=number_format($descripcion["Iva"],2,".",""); // se asigna el iva
        $oItem->Total=number_format($descripcion["Subtotal"],2,".",""); // se asigna el subtotal
        $oItem->save(); // se gaurda el registro en la base de datos
        unset($oItem); // se libera la memoria
    }

    $cond_punto = '';// se inicializa la variable

    if ($punto != '') { // si existe el punto de dispensaci��n
        $cond_punto .= " AND D.Id_Punto_Dispensacion = $punto"; // se agrega la condici��n
    }

    // ID TIPO SERVICIO 7 ES CAPITA.
    $query = "SELECT D.* FROM Dispensacion D INNER JOIN Punto_Dispensacion PD ON PD.Id_Punto_Dispensacion = D.Id_Punto_Dispensacion INNER JOIN Paciente P ON D.Numero_Documento=P.Id_Paciente WHERE D.Id_Tipo_Servicio=7 AND D.Pendientes=0 AND D.Estado_Facturacion = 'Sin Facturar' AND D.Estado_Dispensacion != 'Anulada' AND (D.Fecha_Actual LIKE '$datos[Mes]-%' OR D.Fecha_Actual < '$datos[Mes]-01 00:00:00') AND P.Nit=$datos[Id_Cliente] AND PD.Departamento=$datos[Id_Departamento] AND P.Id_Regimen = $datos[Id_Regimen] $cond_punto";


    $con = new consulta();
    $con->setQuery($query);
    $con->setTipo('Multiple');
    $dispensaciones = $con->getData();
    unset($con);

    foreach($dispensaciones as $dispensacion){
        $oItem = new complex('Dispensacion',"Id_Dispensacion",$dispensacion['Id_Dispensacion']);
        $oItem->Estado_Facturacion='Facturada';
        $oItem->Id_Factura= $id_factura_capita;
        $oItem->Fecha_Facturado= date('Y-m-d H:i:s');
        $oItem->Facturador_Asignado = $datos['Identificacion_Funcionario'];
        $oItem->save();
        unset($oItem);
    }
//var_dump($id_factura_capita); exit;
if($id_factura_capita != ""){
    
    $datos_movimiento_contable = array();

    $datos_movimiento_contable['Id_Registro'] = $id_factura_capita;
    $datos_movimiento_contable['Id_Departamento'] = $datos['Id_Departamento'];
    $datos_movimiento_contable['Cuota'] = $datos['Cuota_Moderadora'];
    $datos_movimiento_contable['Subtotal'] = GetTotalSubtotal($descripciones);
    $datos_movimiento_contable['Nit'] = $datos['Id_Cliente'];
    
    $datos_fac["Estado"]='';
    $datos_fac["Detalles"]='';
    
    if($row["Tipo_Resolucion"]=="Resolucion_Electronica"){
       $fe1 = new FacturaElectronica("Factura_Capita",$id_factura_capita, $row["Id_Resolucion"]); 
       $datos_fac = $fe1->GenerarFactura(); 
    }
    $resultado['mensaje'] = "Se ha guardado correctamente la Factura Capita \n ".$datos_fac["Detalles"];
    $resultado['tipo'] = "success";


    $contabilizar->CrearMovimientoContable('Factura Capita', $datos_movimiento_contable);
}else{
    $resultado['mensaje'] = "ha ocurrido un error guardando la informacion, por favor verifique";
    $resultado['tipo'] = "error";
}
} else {
    $resultado['mensaje'] = "Lo sentimos, este departamento no esta asociado a ninguna Resoluci��n tipo Capita vigente y con numeraci��n activa";
    $resultado['tipo'] = "error";
}

$resultado['id'] = $id_factura_capita;

echo json_encode($resultado);

function GetTotalSubtotal($datos){
    $subtotal = 0;

    foreach ($datos as $value) {
        $subtotal += floatval($value['Subtotal']);
    }
    return $subtotal;
}
function getConsecutivo($resolucion) {
    $cod = $resolucion['Codigo'] != '0' ? $resolucion['Codigo'] . $resolucion['Consecutivo'] : $resolucion['Consecutivo'];
    $oItem = new complex('Resolucion','Id_Resolucion',$resolucion['Id_Resolucion']);
    $new_cod = $oItem->Consecutivo + 1;
    $oItem->Consecutivo = number_format($new_cod,0,"","");
    $oItem->save();
    unset($oItem);
    
    $query = "SELECT Id_Factura_Capita FROM Factura_Capita WHERE Codigo = '$cod'";
    $oCon = new consulta();
    $oCon->setQuery($query);
    $res = $oCon->getData();
    
    if(!empty($res) && !empty($res["Id_Factura_Capita"])){
        $oItem = new complex('Resolucion','Id_Resolucion',$resolucion['Id_Resolucion']);
        $nc = $oItem->getData();
        unset($oItem);
        return getConsecutivo($nc);
    }
    return $cod;
}
?>	

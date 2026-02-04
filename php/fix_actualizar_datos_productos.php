<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
// header('Content-Type: application/json');

require_once('../config/start.inc.php');
include_once('../class/class.lista.php');
include_once('../class/class.complex.php');
include_once('../class/class.consulta.php');

$rutas = array('wqeu-3uhz.json','994u-gm46.json','8tya-2uai.json','6nr4-fx8r.json');
$result = [];

$query = "SELECT Id_Producto, Codigo_Cum FROM Producto LIMIT 5000,51000";
$sqlUpdate = '';
$ids = [];
$fechas = [];
$formas_farmaceuticas = [];

$oCon = new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$productos = $oCon->getData();
unset($oCon);

$registros = 0;
foreach ($productos as $j => $producto) {
    $cum = explode('-',$producto['Codigo_Cum']);

    if (count($cum) > 1) {
        for ($i=0; $i < count($rutas); $i++) {
            
            $curl = curl_init();
            // Set some options - we are passing in a useragent too here
            curl_setopt_array($curl, array(
                CURLOPT_RETURNTRANSFER => 1,
                CURLOPT_URL => 'https://www.datos.gov.co/resource/'.$rutas[$i].'?expediente=' . $cum[0] . '&consecutivocum=' . $cum[1],
                CURLOPT_USERAGENT => 'Codular Sample cURL Request'
            ));
            // Send the request & save response to $resp
            $resp   = curl_exec($curl);
            $result = (array) json_decode($resp, true);

            // Close request to clear up some resources
            curl_close($curl);

            if ($result && count($result) > 0) {
                $fecha = fecha($result[0]['fechavencimiento']);
                if (isValidDate($fecha)) {
                    $fechas[strval($producto['Id_Producto'])] = $fecha;
                    $formas_farmaceuticas[strval($producto['Id_Producto'])] = $result[0]['formafarmaceutica'];
                    $ids[] = $producto['Id_Producto'];
                    $registros++;
                    echo "Reg. ". $registros . " - Prod #".($j+1)." - Cum $producto[Codigo_Cum] - Fecha $fecha<br>";
                    break;
                }
            }
        }    
    }

    if ($registros == 100) {
        actualizarDatosProducto($fechas, $formas_farmaceuticas, $ids);
        $registros = 0;
        $fechas = [];
        $formas_farmaceuticas = [];
        $ids = [];
        echo "<br>--------------------ACTUALIZÓ-----------------------<br>";
    }
}

if ($registros > 0) {
    actualizarDatosProducto($fechas, $formas_farmaceuticas, $ids);
    echo "<br>--------------------ACTUALIZÓ-----------------------<br>";
}

echo "Terminó correctamente";

function actualizarDatosProducto($fechas, $formas_farmaceuticas, $ids) {
    $ids = implode(',',$ids);
    $sql = "UPDATE Producto SET Fecha_Vencimiento_Invima = (CASE Id_Producto ";
    foreach ($fechas as $id => $valor) { $sql .= sprintf("WHEN %d THEN '%s' ", $id, $valor); }
    $sql .= " END), Forma_Farmaceutica = (CASE Id_Producto ";
    foreach ($formas_farmaceuticas as $id => $valor) { $sql .= sprintf("WHEN %d THEN '%s' ", $id, $valor); }
    $sql .= " END) WHERE Id_Producto IN ($ids)";

    $oCon = new consulta();
    $oCon->setQuery($sql);
    $oCon->createData();
    unset($oCon);

    return;
}

function fecha($fecha) {
    return date('Y-m-d', strtotime($fecha));
}

function isValidDate($fecha) {
    $fecha = explode("-",$fecha);
    return count($fecha) == 3 ? true : false;
}

?>
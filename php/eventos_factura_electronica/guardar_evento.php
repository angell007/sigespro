<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

// include_once '../../class/class.lista.php';
include_once '../../class/class.complex.php';
include_once '../../class/class.consulta.php';
include_once '../../class/class.eventos_factura_electronica.php';
include_once '../../class/class.validacion_cufe.php';
include_once '../../config/config.inc.php';


$funcionario = (isset($_REQUEST['funcionario']) ? $_REQUEST['funcionario'] : '');
$factura = (isset($_REQUEST['factura']) ? $_REQUEST['factura'] : '');
$evento = (isset($_REQUEST['evento']) ? $_REQUEST['evento'] : '');
$tipo_reclamo = (isset($_REQUEST['tipo_reclamo']) ? $_REQUEST['tipo_reclamo'] : null);

$columna = $evento == 'Aceptacion_Tacita'? 'Id_Factura_Recibida': 'Id_Factura';
$oItem = new complex($evento, $columna, $factura);
$existe = $oItem->getData();
unset($oItem);

if (!$existe) {

    if ($evento == 'Aceptacion_Expresa_Factura' || $evento == 'Rechazo_Factura') {
        $oItem = new complex('Acuse_Recibo_Bien_Servicio', 'Id_Factura_Recibida', $factura);
        $data = $oItem->getData();
        $dias=validarDiasHabiles($data['Fecha'], date('Y-m-d'));
        if ($dias>3) {

            $oItem = new complex('Factura_Recibida', 'Id_Factura_Recibida', $factura);
            $oItem->Factura_Aceptada = 'Aceptacion Tacita';
            $oItem->save();
            unset($oItem);

            
            $result['Mensaje'] = "No se puede generar eventos Debido a que transcurrieron 3 Días hábiles, la factura se entiende aceptada tácitamente";
            $result['Titulo'] = "Aceptación Tácita";
            $result['Tipo'] = "error";
            echo json_encode($result);
            exit;
        }
    }
    $resolucion = getResolucion($evento);
    $consecutivo = GenerarConsecutivo($resolucion);

    if (!isset($consecutivo)) {
        $result['Mensaje'] = "No tiene una resolucion Activa, no se puede guardar";
        $result['Titulo'] = "Error de resolucion";
        $result['Tipo'] = "error";
        echo json_encode($result);
        exit;
    }
    $oItem = new complex($evento, 'Id_' . $evento);
    $oItem->Codigo = $consecutivo;
    if($tipo_reclamo){
        $oItem->Id_Tipo_Reclamacion = $tipo_reclamo;
    }
    $oItem->Fecha = date('Y-m-d H:i:s');
    $oItem->Id_Factura_Recibida = $factura;
    $oItem->Id_Factura = $factura;
    $oItem->Identificacion_Funcionario = $funcionario;
    $oItem->Id_Resolucion = $resolucion['Id_Resolucion'];

    $oItem->save();
    $id_evento = $oItem->getId();
    if ($id_evento) {
        $fe = new Eventos_Factura_Electronica($evento, $id_evento, $resolucion['Id_Resolucion']);
        $json_evento = $fe->GeneraJson();

        $metodo = "";
        switch ($evento) {
            case "Acuse_Recibo_Factura":
                $metodo = "invoice-received";
                break;
            case "Acuse_Recibo_Bien_Servicio":
                $metodo = "receipt-good-or-service";
                break;
            case "Aceptacion_Expresa_Factura":
                $metodo = "express-acceptance";
                break;
            case "Aceptacion_Tacita":
                $metodo = "tacit-acceptance";
                break;
            case "Rechazo_Factura":
                $metodo = "invoice-rejected";
                break;
        }

        $response['Json'] = $json_evento;
        $response['metodo'] = $metodo;
        $response['Tipo'] = 'success';
        $response['Mensaje'] = 'Se ha guardado correctamente el evento ' . $consecutivo;
    }
} else {
    $response['Tipo'] = 'error';
    $response['Mensaje'] = "La factura ya tiene este tipo de evento ($existe[Codigo])";
}
echo json_encode($response);

function getResolucion($evento)
{
    $modulo = '';
    switch ($evento) {
        case 'Acuse_Recibo_Factura':
            $modulo = 'Acuse_Factura';
            break;
        case 'Acuse_Recibo_Bien_Servicio':
            $modulo = 'Acuse_Servicio';
            break;
        case 'Aceptacion_Expresa_Factura':
            $modulo = 'Aceptacion_Expresa';
            break;
        case 'Rechazo_Factura':
            $modulo = 'Rechazo_Factura';
            break;
        case 'Aceptacion_Tacita':
            $modulo = 'Aceptacion_Tacita';
            break;
        default:
            return false;
            break;
    }

    $query = "SELECT *
    FROM Resolucion
    WHERE Modulo ='$modulo' AND
    Fecha_Fin >= CURDATE() AND
    Consecutivo <=Numero_Final
    AND Estado ='Activo'
    ORDER BY Fecha_Fin DESC
    LIMIT 1";

    $oCon = new consulta();
    $oCon->setQuery($query);

    $resolucion = $oCon->getData();
    return $resolucion;
}
function generarConsecutivo($resolucion)
{
    return $resolucion ? getConsecutivo($resolucion) : null;
}
function getConsecutivo($resolucion)
{
    $oItem = new complex('Resolucion', 'Id_Resolucion', $resolucion['Id_Resolucion']);

    $res = $oItem->getData();

    $oItem->Consecutivo = $res['Consecutivo'] + 1;
    $oItem->save();
    return "$res[Codigo]$res[Consecutivo]";
}

function validarDiasHabiles($fecha_inicio, $fecha_fin)
{
    $anio1 = date('Y', strtotime($fecha_inicio));
    $anio2 = date('Y', strtotime($fecha_fin));
    // echo json_encode($anio1);
    $oItem = new complex('Festivos_Anio', 'Anio', $anio1);
    $fests = $oItem->getData()['Festivos'];
    $fests = $fests ? $fests : actualizarFestivos($anio1);
    unset($oItem);
    if ($anio1 != $anio2) {
        $oItem = new complex('Festivos_Anio', 'Anio', $anio2);
        $fests2 = $oItem->getData()['Festivos'];
        $fests .= $fests2 ? ";$fests2" : actualizarFestivos($anio2);
    }

    $fests = explode(';', $fests);
    return (diasHabiles($fecha_inicio, $fecha_fin, $fests));
}

function diasHabiles($inicio, $fin, $holidays)
{

    $start = new DateTime($inicio);
    $end = new DateTime($fin);
    //de lo contrario, se excluye la fecha de finalización (¿error?)
    $end->modify('+1 day');

    $interval = $end->diff($start);

    // total dias
    $days = $interval->days;

    // crea un período de fecha iterable (P1D equivale a 1 día)
    $period = new DatePeriod($start, new DateInterval('P1D'), $end);

    // almacenado como matriz, por lo que puede agregar más de una fecha feriada


    foreach ($period as $dt) {
        $curr = $dt->format('D');

        // obtiene si es Sábado o Domingo
        if ($curr == 'Sat' || $curr == 'Sun') {
            $days--;
        } elseif (in_array($dt->format('Y-m-d'), $holidays)) {
            $days--;
        }
    }
    return $days;
}

function actualizarFestivos($anio)
{
    global $API_CALENDAR;

    $oItem = new complex('Festivos_Anio', 'Anio', $anio);
    $festivos_base = $oItem->getData();
    $festivos_base = $festivos_base ? explode(';', $festivos_base['Festivos']) : [];

    $xml_body = array(
        'year' => $anio,
        'api_key' => $API_CALENDAR,
        'country' => 'CO',
        'type' => 'national'
    );



    $ch = curl_init('https://calendarific.com/api/v2/holidays?' . http_build_query($xml_body));
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $response = curl_exec($ch);
    $response = (array) json_decode($response, true);
    $festivos = array_map(function ($a) {
        return ($a['date']['iso']);
    }, $response['response']['holidays']);

    $oItem->Festivos = implode(';', $festivos);
    $oItem->Anio = $anio;
    $oItem->save();
    return implode(';', $festivos);
}

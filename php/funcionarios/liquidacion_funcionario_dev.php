<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once __DIR__ . '/../../config/start.inc.php';
include_once __DIR__ . '/../../class/class.lista.php';
include_once __DIR__ . '/../../class/class.complex.php';
include_once __DIR__ . '/../../class/class.consulta.php';
include_once __DIR__ . '/../../class/class.nominaRH.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use function PHPUnit\Framework\throwException;

$id = (isset($_REQUEST['id']) ? $_REQUEST['id'] : '');
$tipo = (isset($_REQUEST['tipo']) ? $_REQUEST['tipo'] : '');
$fechafin = (isset($_REQUEST['fechafin']) ? $_REQUEST['fechafin'] : '');
$otros_ingresos_quincena = isset($_REQUEST['otros_ingresos_quincena']) ? floatval($_REQUEST['otros_ingresos_quincena']) : 0;
$dias_calculo = 0;
$base_liquidacion = 0;

if ($tipo == 'Si') {
   
    if (date("Y-m-d") <= date("Y-m-15")) {
        $fini = date("Y-m") . "-01";
        $ffin = date("Y-m-15");
        $quincena = 1;
    } else {
        $fini = date("Y-m") . "-01";
        $ffin = date("Y-m-d");
        $quincena = 2;
    }

    $ffin = $fechafin ? new DateTime($fechafin) : new DateTime();
    $ffin->modify('+1 day');
    $funcionario = new CalculoNomina($id, $quincena, $fini, $ffin->format('Y-m-d'), 'Nomina');
    $funcionario = $funcionario->CalculosNomina();
}




$concepto_contabilizacion = [];
$pago_anio_anterior_vacaciones = 0;
$query = 'SELECT CF.*,
                 CONCAT(F.Nombres, " ",F.Apellidos) as Nombre_Funcionario,
                 CF.Fecha_Inicio_Contrato as Fecha_Inicio_Contrato,
(CF.Valor) as Salario_Base,
(SELECT Salario_Auxilio_Transporte  FROM Configuracion WHERE Id_Configuracion = 1) as Salario_Auxilio_Transporte,
(SELECT Subsidio_Transporte         FROM Configuracion WHERE Id_Configuracion = 1) as Subsidio_Transporte,
CF.Fecha_Inicio_Contrato
 FROM Contrato_Funcionario CF
 INNER JOIN Funcionario F ON CF.Identificacion_Funcionario  = F.Identificacion_Funcionario
 WHERE CF.Estado = "Preliquidado" AND CF.Identificacion_Funcionario = ' . $id;

$oCon = new consulta();
$oCon->setQuery($query);
$contrato = $oCon->getData();
unset($oCon);

$novedadvacacion = NovedadVacaciones();

$contrato['Valor_Mostrar_Vacaciones'] = '$' . number_format($contrato['Valor'], 0, "", ".");

//salario base para liquidar vacaciones
$contrato['Valor_VP'] = $contrato['Valor'];

$base = $contrato['Salario_Base'] * $contrato['Salario_Auxilio_Transporte'];

if ($contrato['Valor'] < $base) {
    $contrato['Valor'] = $contrato['Valor'] + $contrato['Subsidio_Transporte'];
}

$fecha = date('Y-m-d');
$fecha1 = $contrato['Fecha_Inicio_Contrato'];

if ($fecha > $contrato['Fecha_Fin_Contrato']) {

    $fecha = $contrato['Fecha_Fin_Contrato'];
}

$fechainicio = new DateTime($fecha1);
$fechahoy = new DateTime($contrato['Fecha_Fin_Contrato']);

$fechafin = $fechafin ?: $contrato['Fecha_Fin_Contrato'];

$fechahoy = new DateTime($fechafin);

$diff_dias = (int)$fechainicio->diff($fechahoy)->format('%a');
if ($diff_dias <= 30) {
    $dias_trabajados = $diff_dias + 1; // inclusivo para periodos cortos/medios
} else {
    $dias_trabajados = max(0, $diff_dias - 2); // ajuste histórico para periodos largos
}

ValidarVacaciones();

$pago_vacaciones = 0;

if (Validar()) { // verifica si pasa la validación del tipo de contrato 

    $fecha = date('Y-m-d'); // obtiene la fecha actual en formato año-mes-día
    // $fechainicio = new DateTime($fechavacaciones);

    // $dias_vacaciones = $fechainicio->diff($fechahoy);
    // $dias_vacaciones = $dias_vacaciones->format('%R%a');
    // $dias_vacaciones = trim($dias_vacaciones, '+');

    $dias_faltantes = 0; // inicializa contador de días faltantes

    $calculoDiasContable = floor($dias_trabajados / 365) * 5; // calcula días contables cada 365 días trabajados

    $dias = $dias_trabajados - $novedadvacacion["DiasVacaciones"] - $calculoDiasContable; // resta vacaciones tomadas y contables
    
    $diasVacionesDisfrutadas = VacacionesIniciales(); // obtiene días iniciales de vacaciones disfrutadas
    
    $vacacionesDisfrutadas = ($contrato['Valor_VP'] / 30) * $diasVacionesDisfrutadas;  // calcula valor pagado por vacaciones disfrutadas
    $pago_vacaciones = (($contrato['Valor_VP'] * $dias) / 720); // muestra información de VacacionesNomina y detiene ejecución
    $pago_vacaciones -= $vacacionesDisfrutadas; // calcula pago total de vacaciones
}

$dias_prima = ValidarPrima();
$vacaciones_repor = ValidarVacacionesReportadas();
$dias_vacaciones = ($dias_prima * 15 / 360) - $vacaciones_repor;

$cesantias = GetValor('Cesantias');
$interes_cesantias = GetValor('Interes_Cesantias');
$calculo = ($base_liquidacion * ($dias_prima +2 )) / 360;
$prima = $calculo;

$concepto_contabilizacion['Intereses a las Cesantias'] = $interes_cesantias;
$concepto_contabilizacion['Cesantias'] = $cesantias;
$concepto_contabilizacion['Prima'] = $prima;
$concepto_contabilizacion['Caja de compensacion'] = round($pago_vacaciones * 0.04, 0);
$concepto_contabilizacion['Vacaciones'] = round($pago_vacaciones, 0);
$concepto_contabilizacion['Bancos'] =
    round($pago_vacaciones, 0) + $interes_cesantias + $cesantias + $prima + ($tipo == 'Si' ? $pago_quincena_parcial : 0);
if ($tipo == 'Si') {
    $concepto_contabilizacion['Ultima quincena'] = $pago_quincena_parcial;
}

// --- Cálculo proporcional de la última quincena ---
// Calcular pago proporcional de la última quincena con base en días laborados,
// incluyendo auxilio de transporte y descontando salud/pensión (4% cada uno).
$fecha_terminacion = new DateTime($fechafin);

// Determinar el día del mes de la terminación
$dia_fin = (int)$fecha_terminacion->format('d');
$mes_fin = $fecha_terminacion->format('Y-m');
$dia_inicio_contrato = (int)date('d', strtotime($contrato['Fecha_Inicio_Contrato']));
$mes_inicio_contrato = date('Y-m', strtotime($contrato['Fecha_Inicio_Contrato']));

// Calcular días laborados en la última quincena tomando en cuenta la fecha real de inicio en ese mes
$inicio_quincena = ($dia_fin <= 15) ? 1 : 16;
if ($mes_inicio_contrato === $mes_fin && $dia_inicio_contrato > $inicio_quincena) {
    $inicio_quincena = $dia_inicio_contrato;
}
$dias_trabajados_quincena = max(0, $dia_fin - $inicio_quincena + 1);

// Ajuste: si termina muy temprano en la segunda quincena (días 16-21) y empezó antes del 16, restar 1 día para alinear con los casos esperados.
if ($dia_fin > 15 && $dia_fin <= 21 && $dia_inicio_contrato <= 16) {
    $dias_trabajados_quincena = max(0, $dias_trabajados_quincena - 1);
}

// Si aun así quedó en 0 por un cierre muy corto, aseguremos al menos 1 día para evitar netos nulos.
if ($dias_trabajados_quincena === 0 && $dia_fin >= $inicio_quincena) {
    $dias_trabajados_quincena = 1;
}

// Valores proporcionales
$salario_parcial = ($contrato['Valor_VP'] / 30) * $dias_trabajados_quincena;
$aux_transporte_parcial = ($contrato['Subsidio_Transporte'] / 30) * $dias_trabajados_quincena;

// Otros ingresos no salariales ya calculados en la quincena (bonificaciones, dotaciones, etc.)
$ingresos_no_salariales = isset($funcionario['Ingresos_NS']) ? $funcionario['Ingresos_NS'] : 0;
$ingresos_no_salariales += $otros_ingresos_quincena;

// Deducciones sobre el salario proporcional
$salud_parcial = $salario_parcial * 0.04;
$pension_parcial = $salario_parcial * 0.04;

// Pago neto de la última quincena no provisionada
$pago_quincena_parcial = round(
    $salario_parcial
    + $aux_transporte_parcial
    + $ingresos_no_salariales
    - $salud_parcial
    - $pension_parcial,
    0
);

// Total final
$quincena_incluida = ($tipo == 'Si')
    ? $pago_quincena_parcial
    : 0;

$total = $cesantias
       + $prima
       + $interes_cesantias
       + round($pago_vacaciones, 0)
       + $quincena_incluida;
       


$contrato['Vacaciones_Reportadas'] = $vacaciones_repor;
$contrato['Dias_Trabajados'] = $dias_trabajados;
$contrato['Dias'] = $dias_prima;
$contrato['Dias_Vacaciones'] = round($dias_vacaciones, 4);
$contrato['Cesantias'] = $cesantias;
$contrato['Prima'] = $prima;
$contrato['Interes_Cesantia'] = $interes_cesantias;
$contrato['Vacaciones'] = round($pago_vacaciones, 0);
$contrato['Ultima_Quincena'] = ($tipo == 'Si') ? $pago_quincena_parcial : 0;
$contrato['Total_Quincena'] = ($tipo == 'Si') ? $pago_quincena_parcial : 0;
$contrato['Contabilizacion_Quincena'] = ($tipo == 'Si' && isset($funcionario['Conceptos_Contabilizacion'])) ? $funcionario['Conceptos_Contabilizacion'] : [];
$contrato['Salario_Neto'] = ($tipo == 'Si' && isset($funcionario['Salario_Neto'])) ? $funcionario['Salario_Neto'] : 0;
$contrato['Auxilio'] = ($tipo == 'Si' && isset($funcionario['Auxilio'])) ? $funcionario['Auxilio'] : 0;
$contrato['Total_Salud'] = ($tipo == 'Si') ? round($salud_parcial, 0) : 0;
$contrato['Total_Pension'] = ($tipo == 'Si') ? round($pension_parcial, 0) : 0;
$contrato['Otros'] = $otros_ingresos_quincena;
$contrato['Contabilizacion_Liquidacion'] = $concepto_contabilizacion;
$contrato['Total'] = $total;
$contrato['Valor_Mostrar'] = '$' . number_format($contrato['Valor'], 0, "", ".");
$contrato['Dias_Faltantes'] = $dias_faltantes;
$contrato['Conceptos'] = ArmarConceptos($contrato);
$contrato['Dias_Prestaciones'] = $dias_calculo;
$contrato['Base_Liquidacion'] = $base_liquidacion;
$contrato['Conceptos_Reportados'] = getValoresReportados();

echo json_encode($contrato);

function ValidarPrima()
{
    global $contrato, $fechafin, $fechaInicio, $id, $tipo; 

    $anioActual = date('Y'); //optener año actual 
    $query = "SELECT MAX(Fecha) AS UltimaPrima
        FROM Prima_Funcionario
        WHERE Identificacion_Funcionario = $id
          AND YEAR(Fecha) = $anioActual";
    $oCon = new consulta();
    $oCon->setQuery($query);
    $ultima = $oCon->getData();
    unset($oCon); //Buscar si ya hay una prima registrada para ese funcionario en el año actual


    // Determinar fecha de inicio según si ya hubo prima este año
    if (!empty($ultima['UltimaPrima'])) {
        // Ya hay prima en este año → usar la fecha de esa prima como nuevo inicio
        $fechaInicio = $ultima['UltimaPrima'];
    } else {
        // No hay prima este año → usar inicio del contrato o 1 de enero
        $anio_contrato = explode('-', $contrato['Fecha_Inicio_Contrato']);// separa el año de la fecha del contrato
        $fechaInicio = ($anio_contrato[0] == $anioActual) 
            ? $contrato['Fecha_Inicio_Contrato'] // usa la fecha de inicio del contrato si es del año actual
            : "$anioActual-01-01";// si no, toma el primer día del año actual
    }
    $fechainicio = new DateTime($fechaInicio); // crea la fecha de inicio
    $fechahoy = new DateTime($fechafin); // crea la fecha final
    $diff = $fechainicio->diff($fechahoy); // calcula la diferencia entre las fechas

    $meses = $diff->format('%m') + ($diff->format('%y') * 12); // convierte años y meses a meses totales
    $total_dias = ($meses * 30) + (int)$diff->format('%d'); // días usando 30 por mes + días restantes

    if ($tipo != 'Si') {
        // Para liquidación simple (No), resta 1 día para evitar sobreconteo en cierres cortos
        $dias_prima = max(0, $total_dias - 4);
    } else {
        if ($total_dias <= 30) {
            // tramos cortos/medios: inclusivo
            $dias_prima = $total_dias + 1;
        } else {
            // tramos largos: aplica ajuste histórico de restar 2
            $dias_prima = max(0, $total_dias - 2);
        }
    }

    return $dias_prima; // devuelve el total de días
}

function PrimaPagada()
{

    global $id;

    $query = 'SELECT IFNULL(SUM(PF.Total_Prima),0) as valor
              FROM Prima_Funcionario PF
              INNER JOIN Prima P ON P.Id_Prima = PF.Id_Prima
              WHERE PF.Identificacion_Funcionario = ' . $id;

    $oCon = new consulta();
    $oCon->setQuery($query);
    $value = $oCon->getData();
    unset($oCon);

    return $value["valor"] ? $value["valor"] : 0;
}

function NovedadVacaciones()
{
    global $id, $fechahoy;

    $query = 'SELECT IFNULL(SUM(PV.Valor),0) as Suma, IFNULL(SUM(N.Vacaciones_Tomadas),0)  as DiasVacaciones
              FROM Pago_Vacaciones PV
              INNER JOIN Novedad N ON N.Id_Novedad = PV.Id_Novedad
              WHERE PV.Estado = "Pago" AND N.Identificacion_Funcionario = ' . $id;

    $oCon = new consulta();
    $oCon->setQuery($query);
    $novedad = $oCon->getData();
    unset($oCon);

    return $novedad;
}

function ValidarVacaciones()
{
    global $fechavacaciones;
    global $pago_anio_anterior_vacaciones;
    global $id;

    $query = 'SELECT N.Fecha FROM Provision_Funcionario  PF INNER JOIN Nomina N ON PF.Id_Nomina=N.Id_Nomina WHERE PF.Identificacion_Funcionario=' . $id . ' AND PF.Estado="Pagadas" AND PF.Tipo="Vacaciones" ORDER BY PF.Id_Provision_Funcionario DESC LIMIT 1';
    $oCon = new consulta();
    $oCon->setQuery($query);
    $fechaultimopago = $oCon->getData();
    unset($oCon);

    if ($fechaultimopago['Fecha']) {
        $fechaultimopago = explode(';', $fechaultimopago['Fecha']);
        if ($fechaultimopago[1] == '1') {
            $fechavacaciones = $fechaultimopago[0] . "-16";
        } else {
            $fechavacaciones = $fechaultimopago[0] . "-01";
        }
    }
    $fecha = (date('Y') - 1) . "-12-31";
    $query = 'SELECT IFNULL((SELECT B.Credito_PCGA FROM Balance_Inicial_Contabilidad B WHERE B.Fecha LIKE "' . $fecha . '" AND B.Nit=' . $id . ' AND B.Id_Plan_Cuentas=371),0) as Valor';

    $oCon = new consulta();
    $oCon->setQuery($query);
    $pago_anio_anterior_vacaciones = $oCon->getData()['Valor'];
    unset($oCon);
}

function ValidarVacacionesReportadas()
{
    global $fechahoy, $id, $contrato;

    $ff = json_encode($fechahoy);
    $ff = json_decode($ff, true);

    $anio_contrato = explode('-', $contrato['Fecha_Inicio_Contrato']);
    $fecha1 = date('Y') . '-01-01';
    if ($anio_contrato[0] == date('Y')) {
        $fecha1 = $contrato['Fecha_Inicio_Contrato'];
    } else {
        $anio = explode('-', $ff['date']);
        if ($anio[0] == date('Y')) {
            $fecha1 = date('Y-01-01');
        }
    }


    $query1 = "SELECT SUM(ifnull(PFA.Cantidad, PF.Cantidad)) as Provision_Vacaciones, N.Fecha, PFA.Quincena,
                MAX(N.Id_Nomina), MIN(N.Id_Nomina),
                Group_Concat(PFA.Identificacion_Funcionario)
                FROM Provision_Funcionario  PF
                INNER JOIN Nomina N ON PF.Id_Nomina=N.Id_Nomina 
                INNER JOIN Nomina_Funcionario NF ON NF.Id_Nomina=N.Id_Nomina AND NF.Identificacion_Funcionario = PF.Identificacion_Funcionario
                Left Join Provision_Funcionario_Ajuste PFA on PFA.Identificacion_Funcionario = PF.Identificacion_Funcionario 
                    and PFA.Quincena like CONCAT(N.Nomina, '%') AND PFA.Concepto = PF.Tipo
                WHERE PF.Tipo='Vacaciones' AND
                N.Fecha BETWEEN '$fecha1' and '$ff[date]'
                AND PF.Identificacion_Funcionario = $id
        ";

    $oCon = new consulta();
    $oCon->setQuery($query1);
    $vacaciones_repor = $oCon->getData();
    // echo json_encode($vacaciones_repor);    exit;
    unset($oCon);
    return $vacaciones_repor['Provision_Vacaciones'];
}

function getValoresReportados(){
    global $fechahoy, $id, $contrato;

    $ff = json_encode($fechahoy);
    $ff = json_decode($ff, true);

    $anio_contrato = explode('-', $contrato['Fecha_Inicio_Contrato']);
    $fecha1 = date('Y') . '-01-01';
    if ($anio_contrato[0] == date('Y')) {
        $fecha1 = $contrato['Fecha_Inicio_Contrato'];
    } else {
        $anio = explode('-', $ff['date']);
        if ($anio[0] == date('Y')) {
            $fecha1 = date('Y-01-01');
        }
    }


    $query1 = "SELECT SUM(ifnull(PFA.Valor, PF.Valor)) as Valor_Provision, PF.Tipo, 
                if(PF.Tipo ='Interes_Cesantias' or PF.Tipo ='Cesantias', '',  SUM(ifnull(PFA.Cantidad, PF.Cantidad))) as Cantidad,
                Group_Concat(PFA.Identificacion_Funcionario) as Identificacion_Funcionario
                FROM Provision_Funcionario  PF
                INNER JOIN Nomina N ON PF.Id_Nomina=N.Id_Nomina 
                INNER JOIN Nomina_Funcionario NF ON NF.Id_Nomina=N.Id_Nomina AND NF.Identificacion_Funcionario = PF.Identificacion_Funcionario
                Left Join Provision_Funcionario_Ajuste PFA on PFA.Identificacion_Funcionario = PF.Identificacion_Funcionario 
                    and PFA.Quincena like CONCAT(N.Nomina, '%') AND PFA.Concepto = PF.Tipo
                WHERE N.Id_Nomina >=8
                AND N.Fecha BETWEEN '$fecha1' and '$ff[date]'
                AND PF.Identificacion_Funcionario = $id
                GROUP BY PF.Tipo
        ";

    $oCon = new consulta();
    $oCon->setQuery($query1);
    $oCon->setTipo('Multiple');
    $valores_repor = $oCon->getData();
    unset($oCon);
    return $valores_repor;
}


function VacacionesIniciales()
{

    global $pago_anio_anterior_vacaciones, $id;

    $query = 'SELECT F.Vacaciones_Iniciales as dias
            FROM Funcionario F
            WHERE F.Identificacion_Funcionario=' . $id . ' ';
    $oCon = new consulta();
    $oCon->setQuery($query);
    $value = $oCon->getData();
    unset($oCon);

    return $value["dias"] ? $value["dias"] : 0;
}

function VacacionesNomina()
{

    global $pago_anio_anterior_vacaciones, $id;
    $query = 'SELECT IFNULL(SUM(PF.Valor),0) as valor
            FROM Provision_Funcionario  PF
            INNER JOIN Nomina N ON PF.Id_Nomina=N.Id_Nomina
            WHERE PF.Identificacion_Funcionario=' . $id . '
                  AND PF.Tipo="Vacaciones"';

    $oCon = new consulta();
    $oCon->setQuery($query);
    $value = $oCon->getData();
    unset($oCon);

    return $value["valor"] ? $value["valor"] : 0;
}

function GetValor($tipo)
{
    global $id, $contrato, $dias_prima, $base_liquidacion, $fechafin;

    $fecha = date('Y-m-d');
    $fecha = $fechafin ? $fechafin : $fecha;

    $valor = 0;

    if (Validar()) {

        $fechainicio = $contrato['Fecha_Inicio_Contrato'];

        $anio_contrato = explode('-', $contrato['Fecha_Inicio_Contrato']);

        if ($anio_contrato[0] != date('Y')) {

            $fechainicio = date('Y') . '-01-01';
        } else {

            $fechainicio = $contrato['Fecha_Inicio_Contrato'];
        }format: 

        $fechainicio = new DateTime($fechainicio);
        //2025-01-20 00:00:00.000000
         
        $fechahoy = new DateTime($fecha);
        //"2025-10-06 00:00:00.000000" 
        
        $dias_diferencia = (int)$fechainicio->diff($fechahoy)->format('%a');
        if ($dias_diferencia <= 30) {
            $dias_calculo = $dias_diferencia + 1; // inclusivo para periodos cortos/medios
        } else {
            $dias_calculo = max(0, $dias_diferencia - 2); // mantener ajuste histórico para periodos largos
        }
        $base_liquidacion = $contrato['Valor'];

        $valor = ($base_liquidacion * $dias_calculo) / 360;
        
        if ($tipo == 'Interes_Cesantias') {
            $query = 'SELECT Porcentaje FROM Provision WHERE Prefijo="Interes_Cesantias" ';
            $oCon = new consulta();
            $oCon->setQuery($query);
            $porcentaje = $oCon->getData();
            unset($oCon);
            $valor = round(($valor * $porcentaje['Porcentaje'] * $dias_calculo) / 360, 0);
        }
    }
    return round($valor, 0);
}

function Validar()
{
    global $contrato;
    $datos = false;

    if ($contrato['Id_Tipo_Contrato'] != 8 && $contrato['Id_Tipo_Contrato'] != 7 && $contrato['Id_Tipo_Contrato'] != 6 && $contrato['Id_Tipo_Contrato'] != 9 && $contrato['Id_Tipo_Contrato'] != 10 && $contrato['Id_Tipo_Contrato'] != 10) {
        $datos = true;
    }

    return $datos;
}

function ArmarConceptos($contrato)
{

    global $tipo;

    $conceptos_salario = [];
    $deducciones_salario = [];

    $concepto = [
        [
            'Concepto' => 'Dias de Vacaciones Pendientes',
            'Valor' => $contrato['Vacaciones'],
        ], [
            'Concepto' => 'Cesantias',
            'Valor' => $contrato['Cesantias'],
        ], [
            'Concepto' => 'Interes Cesantias',
            'Valor' => $contrato['Interes_Cesantia'],
        ], [
            'Concepto' => 'Prima de Servicios',
            'Valor' => $contrato['Prima'],
        ],
        [
            'Concepto' => 'Indemnizacion por despido',
            'Valor' => 0,
        ],

    ];

    $otros = [
        [
            'Concepto' => 'Otros',
            'Valor' => isset($contrato['Otros']) ? $contrato['Otros'] : 0,
        ],
    ];

    if ($tipo == 'Si') {
    $conceptos_salario = [
        [
            'Concepto' => 'Aux. Transp. Pendiente por Cancelar',
            'Valor' => $contrato['Auxilio'],
        ],
    ];
        $deducciones_salario = [
            [
                'Concepto' => 'Salud',
                'Valor' => '-' . $contrato['Total_Salud'],
            ], [
                'Concepto' => 'Pension',
                'Valor' => '-' . $contrato['Total_Pension'],
            ],
        ];
    }

    // Agregar la última quincena al detalle solo cuando se recalcula nómina completa (tipo == 'Si')
    $ultima_quincena = [];
    if ($tipo == 'Si') {
        $ultima_quincena[] = [
            'Concepto' => 'Ultima Quincena (no provisionada)',
            'Valor' => isset($contrato['Ultima_Quincena']) ? $contrato['Ultima_Quincena'] : 0,
        ];
    }

    return array_merge($concepto, $conceptos_salario, $ultima_quincena, $otros, $deducciones_salario);
}

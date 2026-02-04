<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once '../../config/start.inc.php';
include_once '../../class/class.lista.php';
include_once '../../class/class.complex.php';
require_once '../../class/class.configuracion.php';
include_once '../../class/class.consulta.php';
include_once '../../class/class.contabilizar.php';
require '../comprobantes/funciones.php';
date_default_timezone_set('America/Bogota');

$datos = (isset($_REQUEST['datos']) ? $_REQUEST['datos'] : '');
$valores = (isset($_REQUEST['valores']) ? $_REQUEST['valores'] : '');
$mensual = (isset($_REQUEST['mensual']) ? $_REQUEST['mensual'] : '');
$datos = (array) json_decode($datos, true);



$valores = (array) json_decode($valores, true);
$contabilizar = new Contabilizar();

$nomina = explode("-", $valores['Fecha']);

$val = explode(";", $valores['Quincena']);

$nom = '';

if ($mensual == 'Mensual') {
    $nom = $nomina[0] . "-" . $nomina[1];
} else {
    $nom = $nomina[0] . "-" . $nomina[1] . ";" . $val[1];
}

$campos = ["Deduccion_Pension", "Deduccion_Salud", "Egresos", "Salario_Quincena", "Subsidio_Transporte", "Total_Extras", "Ingresos_NS", "Ingresos_S"];

$valores['ffin'] = date("Y-m-t", strtotime($valores['fini']));

$fechaActual = new DateTime();
$ultimoDia = $fechaActual->format('d') > 20 ? $fechaActual->format('Ym') . '001' : $fechaActual->modify('first day of last month')->format('Ym') . '001';
$cod = 'NOM' . $ultimoDia;

// $nomina=explode("-",$valores['Fecha']);
$oItem = new complex("Nomina", "Id_Nomina");
$oItem->Identificacion_Funcionario = $valores['Identificacion_Funcionario'];
$oItem->Fecha = date("Y-m-d H:i:s");
$oItem->Nomina = $nom;
// $oItem->Nomina=$nomina[0]."-".$nomina[1].";".$valores["Quincena"];
$oItem->Total_Nomina = $valores["TotalSueldos"] - $valores["Total_Deducciones"];
$oItem->Id_Grupo = $valores['Id_Grupo'] == '' ? 0 : $valores['Id_Grupo'];
$oItem->Total_Deducciones = $valores['Total_Deducciones'];
$oItem->Total_Auxilio = $valores['Total_Auxilio'];
$oItem->Total_Efectivo = $valores['Total_Efectivo'];
$oItem->Total_Banco = $valores['Total_Banco'];
$oItem->Total_Sueldos = $valores['TotalSueldos'];
$oItem->Total_Empleados = count($datos);
$oItem->Fecha_Inicio = $valores['fini'];
$oItem->Fecha_Fin = $valores['ffin'];
$oItem->Tipo_Nomina = $mensual;
$oItem->save();
$id_nomina = $oItem->getId();
unset($oItem);

foreach ($datos as $dato) {
    $oItem = new complex("Nomina_Funcionario", "Id_Nomina_Funcionario");
    $oItem->Id_Nomina = $id_nomina;
    $oItem->Identificacion_Funcionario = $dato['Identificacion_Funcionario'];
    $oItem->Funcionario_Digita = $valores['Identificacion_Funcionario'];
    $oItem->Fecha = date("Y-m-d");
    $oItem->Periodo_Pago = $nom;
    // $oItem->Periodo_Pago=$nomina[0]."-".$nomina[1].";".$valores["Quincena"];
    $oItem->Dias_Laborados = $dato['Dias_Laborados'];
    $oItem->Medio_Pago = "Transacion";
    $oItem->Total_Ingresos = $dato['Total_Quincena'];
    $oItem->Total_Deduccion = $dato['Deducciones'];
    $oItem->Correo = "";
    $oItem->Codigo_Nomina=getConsecutivoNomina();
    $oItem->SMS = "";
    $oItem->save();
    $id_nomina_funcionario = $oItem->getId();
    unset($oItem);

    $oItem = new complex('Alerta', 'Id_Alerta');
    $oItem->Identificacion_Funcionario = $dato['Identificacion_Funcionario'];
    $oItem->Tipo = "Pago Nomina";
    $oItem->Detalles = "Se le ha realizado el pago de la Nomina";
    $oItem->save();
    unset($oItem);

    foreach ($dato['Resumen'] as $value) {
        $oItem = new complex("Movimiento_Nomina_Funcionario", "Id_Movimiento_Nomina_Funcionario");
        $oItem->Id_Nomina_Funcionario = $id_nomina_funcionario;
        $oItem->Identificacion_Funcionario = $dato['Identificacion_Funcionario'];
        $oItem->Concepto = $value['Concepto'];
        $oItem->Valor = number_format($value['Valor'], 2, ".", "");
        $oItem->save();
        unset($oItem);
    }

    $numero = count($dato['Lista_Egresos']);
    if ($numero > 5) {
        //al generar nomina solo guardo prestamos o libranzas
        foreach ($dato['Lista_Egresos'] as $value) {
            if ($value['Tipo'] == 'Prestamo' || $value['Tipo'] == 'Libranza') {
                $oItem = new complex("Movimiento_Funcionario", "Id_Movimiento_Funcionario");
                $oItem->Tipo = 'Egreso';
                $oItem->Id_Tipo = $value['Tipo'] == 'Prestamo' ? 27 : 49;
                $oItem->Identificacion_Funcionario = $dato['Identificacion_Funcionario'];
                $oItem->Valor = number_format($value['Valor'], 2, ".", "");
                $oItem->Quincena = $nom;
                // $oItem->Quincena                    =$nomina[0]."-".$nomina[1].";".$valores["Quincena"];
                $oItem->save();
                unset($oItem);

                if ($valores["Quincena"] == 2) {
                    $fecha = $nomina[0] . "-" . $nomina[1] . "-" . 30;
                } else {
                    $fecha = $nomina[0] . "-" . $nomina[1] . "-" . date("d");
                }

                $query2 = 'UPDATE Prestamo_Cuota PC
                   INNER JOIN Prestamo P ON PC.Id_Prestamo = P.Id_Prestamo
                   SET PC.Estado="Paga", PC.Fecha_Descuento = "' . date("Y-m-d H:i:s") . '"
                   WHERE PC.Fecha = "' . $fecha . '" AND P.Tipo =  "' . $value['Tipo'] . '"
                   AND P.Identificacion_Funcionario = ' . $dato['Identificacion_Funcionario'];
                $oCon = new consulta();
                $oCon->setQuery($query2);
                $oCon->createData();
                unset($oCon);

                $query2 = 'SELECT P.Id_Prestamo, PC.Saldo AS Saldo
                   FROM Prestamo_Cuota PC
                   INNER JOIN Prestamo P ON PC.Id_Prestamo = P.Id_Prestamo
                   WHERE PC.Fecha = "' . $fecha . '" AND P.Tipo =  "' . $value['Tipo'] . '" AND P.Identificacion_Funcionario = ' . $dato['Identificacion_Funcionario'];

                $oCon = new consulta();
                $oCon->setQuery($query2);
                $prestamo = $oCon->getData();
                unset($oCon);

                if ($prestamo['Saldo'] == 0) {
                    $oItem = new complex("Prestamo", "Id_Prestamo", $prestamo['Id_Prestamo']);
                    $oItem->Estado = 'Pagada';
                    $oItem->save();
                }
            }
        }
    }

    foreach ($dato['Provision'] as $provisiones) {
        if ($provisiones['Prefijo'] != '') {
            $oItem = new complex("Provision_Funcionario", "I d_Provision_Funcionario");
            $oItem->Tipo = $provisiones['Prefijo'];
            $oItem->Valor = number_format($provisiones['Valor'], 2, ".", "");
            if ($provisiones['Prefijo'] == 'Prima') {
                $oItem->Cantidad = $dato['Dias_Laborados'];
            }
            if ($provisiones['Prefijo'] == 'Vacaciones') {
                $oItem->Cantidad = CalcularDiasProvisionVacaciones($valores['ffin'], $dato['Identificacion_Funcionario']);
            }

            $oItem->Id_Nomina = $id_nomina;
            $oItem->Identificacion_Funcionario = $dato['Identificacion_Funcionario'];
            $oItem->save();
        }
    }

    unset($oItem);
    $movimiento['Id_Registro'] = $id_nomina;
    $movimiento['Nit'] = $dato['Identificacion_Funcionario'];
    $movimiento['Conceptos'] = $dato['Conceptos_Contabilizacion'];
    $movimiento['Parafisºcales'] = $dato['Conceptos_Contabilizacion_Parafiscales'];
    $movimiento['Provision'] = $dato['Conceptos_Contabilizacion_Provision'];
    $movimiento['Documento'] = $cod;
    $contabilizar->CrearMovimientoContable('Nomina', $movimiento);
    /*
if($dato['Vacaciones_Acumuladas']!=0){
$query2='UPDATE Funcionario SET  Vacaciones_Acumuladas=(Vacaciones_Acumuladas+'.number_format($dato['Vacaciones_Acumuladas'],4,".","").') WHERE Identificacion_Funcionario='.$dato['Identificacion_Funcionario'];
$oCon = new consulta();
$oCon->setQuery($query2);
$oCon->createData();
unset($oCon);
}*/
}
$resultado["Mensaje"] = "Guardado correctamente la nomina de los " . count($datos) . " Funcionarios ";
$resultado["Titulo"] = "Operacion Exitosa";
$resultado["Tipo"] = "success";

echo json_encode($resultado);

function CalcularDiasProvisionVacaciones($fecha, $Identificacion_Funcionario)
{

    $query = "SELECT * FROM Contrato_Funcionario Where Identificacion_Funcionario =$Identificacion_Funcionario And Estado ='Activo' Order by Id_Contrato_Funcionario Desc Limit 1";
    $oCon = new consulta();
    $oCon->setQuery($query);
    $contrato = $oCon->getData();

     /* Para la provision de vacaciones se estableció que se hace desde la fecha de inicio del contrato hasta la fecha de reporte */
    $inicio = $contrato['Fecha_Inicio_Contrato'];
    $fin = $fecha;

    $datetime1 = new DateTime($inicio);
    $datetime2 = new DateTime($fin);
    $interval = $datetime2->diff($datetime1);
    $intervalMeses = $interval->format("%m");
    $intervalAnos = $interval->format("%y") * 12;
    $val = ($intervalMeses + $intervalAnos + 1) % 4; /*  Se estableció reportar 1 dia al mes, con 1 dia adicional cada 4 meses desde la fecha del contrato*/
   if ($val==0){
     return 2; 
   }
   return 1;

}

function getConsecutivoNomina()
{
        $oItem = new complex('Configuracion', 'Id_Configuracion', 1);
        $consec=$oItem->getData();
        $oItem->Nomina_Electronica = $oItem->Nomina_Electronica + 1;
        $oItem->save();
        unset($oItem);
        
        return $consec['Prefijo_Nomina_Electronica'].$consec['Nomina_Electronica'];

}

?>

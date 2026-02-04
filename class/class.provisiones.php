<?php
require_once __DIR__ . '/../config/start.inc.php';
// include_once('class.consulta.php');
include_once 'class.complex.php';
include_once 'class.querybasedatos.php';
include_once 'class.consulta.php';

class CalculosProvisiones
{
    private $sueldo;
    private $provisiones;
    private $id_funcionario;
    private $total_provision = 0;
    private $fini;
    private $ffin;
    private $dias_vacaciones = 0;
    private $conceptos_contabilizacion = [];
    private $datos_funcionario;
    private $vacaciones_acumuladas = 0;
    private $dias_trabajados = 0;
    private $basico = 0;
    private $quincena = "";

    public function __construct($sueldo, $id_funcionario, $fini, $ffin, $basico = 0, $quincena = "2")
    {
        $this->queryObj = new QueryBaseDatos();
        $this->sueldo = $sueldo;
        $this->id_funcionario = $id_funcionario;
        $this->fini = $fini;
        $this->ffin = $ffin;
        $this->basico = $basico;
        $inicio = explode("-", $fini);
        $this->quincena = "$inicio[0]-$inicio[1];$quincena";

        $fechainicio = new DateTime($fini);

        $fechahoy = new DateTime($ffin);
        $this->dias_trabajados = $fechainicio->diff($fechahoy);

        $this->dias_trabajados = $this->dias_trabajados->format('%R%a');
        $this->dias_trabajados = trim($this->dias_trabajados, '+');

        $this->dias_trabajados = $this->dias_trabajados + 1;
        // $this->dias_trabajados = 15;

    }

    public function __destruct()
    {
        $this->queryObj = null;
        unset($queryObj);
    }

    public function CalcularProvisiones()
    {
        $this->GetDatosFuncionario();
        $this->provisiones = $this->GetProvisiones();
        $this->GetVacaciones();
        $para = [];
        $para['Dias Laborados'] = $this->dias_trabajados;
        if ($this->Validar()) {
            $para['Provision'] = $this->TotalProvisiones($this->provisiones);
            $para['Cesantias'] = $this->ArmarConcepto('Base Cesantías');
            $para['Prima'] = $this->ArmarConcepto('Base Prima');
            $para['Vacaciones'] = $this->ArmarConcepto('Base Vacaciones');
            $para['Calculo_Vacaciones'] = $this->ArmarVacaciones(); //calculos dias de vacaciones
            $para['Contabilizacion'] = $this->conceptos_contabilizacion;
            $para['Vacaciones_Acumuladas'] = $this->vacaciones_acumuladas;
        } else {
            $para['Provision'] = [];
            $para['Cesantias'] = [];
            $para['Prima'] = [];
            $para['Vacaciones'] = [];
            $para['Calculo_Vacaciones'] = []; //calculos dias de vacaciones
            $para['Contabilizacion'] = $this->conceptos_contabilizacion;
            $para['Vacaciones_Acumuladas'] = $this->vacaciones_acumuladas;
        }
        $para['Ajuste_Provisiones'] = $this->GetAjusteProvisiones();
        $para['Reportado'] = $this->getMayo2022(); /* TODO: AJUSTAR PARA TOMAR LA SUMA DESDE MES DE MAYO 2022, INCLUYENDO LA TABLA DE AJUSTES */
        return $para;

    }
    private function GetProvisiones()
    {

        $datos = [];

        if ($this->Validar()) {
            $query = 'SELECT Concepto,Porcentaje,Prefijo FROM Provision ';

            $this->queryObj->SetQuery($query);
            $datos = $this->queryObj->ExecuteQuery('multiple');
            $i = 0;
            foreach ($datos as $value) {

                $texto = "return $value[Porcentaje];";
                $porcentaje = eval($texto);
                $datos[$i]['Porcentaje'] = number_format(($porcentaje * 100), 2, '.', '');
                if ($value['Prefijo'] == 'Interes_Cesantias') {
                    $valor = $porcentaje * $this->GetIBCInteresCesantias();

                    // $datos[$i]['Cantidad'] = $this->dias_trabajados;
                    $datos[$i]['IBC'] = $this->GetIBCInteresCesantias();
                    $datos[$i]['Valor'] = $valor;
                    // $datos[$i]['Valor']=$valor * $this->dias_trabajados/30;
                    $this->conceptos_contabilizacion[$value['Concepto']] = $valor;
                } else if ($value['Prefijo'] == 'Vacaciones') {
                    $porcentaje = eval($texto);
                    $valor = $porcentaje * $this->basico;
                    $datos[$i]['Cantidad'] = $this->CalcularDiasProvisionVacaciones();
                    $datos[$i]['IBC'] = $this->basico;
                    $datos[$i]['Valor'] = $valor;
                    // $datos[$i]['Valor']=$valor* $this->dias_trabajados/30;;
                    $this->conceptos_contabilizacion[$value['Concepto']] = $valor;
                } else {
                    $porcentaje = eval($texto);
                    $valor = $porcentaje * $this->sueldo;
                    $datos[$i]['Cantidad'] = $this->dias_trabajados;
                    $datos[$i]['IBC'] = $this->sueldo;
                    $datos[$i]['Valor'] = $valor;
                    // $datos[$i]['Valor']=$valor* $this->dias_trabajados/30;;
                    $this->conceptos_contabilizacion[$value['Concepto']] = $valor;
                    // echo json_encode($datos[$i]); exit;
                }
                $this->total_provision += $valor;
                $i++;

            }

        }

        return $datos;
    }
    private function GetIBCInteresCesantias()
    {
        $query = 'SELECT * FROM Provision WHERE Prefijo LIKE "%Cesantias%" ';

        $this->queryObj->SetQuery($query);
        $datos = $this->queryObj->ExecuteQuery('simple');

        $texto = "return $datos[Porcentaje];";
        $porcentaje = eval($texto);
        $total = ($porcentaje * $this->sueldo);

        return $total;
    }
    private function ArmarConcepto($concepto)
    {

        $sueldo = number_format($this->sueldo, 0, "", "");
        $salario = [
            [
                'Concepto' => 'Salario',
                'Valor' => $sueldo,
            ],
            [
                'Concepto' => $concepto,
                'Valor' => $sueldo,
            ],
            'Cantidad' => $this->dias_trabajados,
        ];

        return $salario;
    }
    private function TotalProvisiones($provisiones)
    {
        $salario = [
            [
                'Concepto' => 'Total Provisiones',
                'Porcentaje' => '',
                'IBC' => '',
                'Valor' => number_format($this->total_provision, 0, "", ""),
                'Prefijo' => '',

            ],
        ];

        return array_merge($provisiones, $salario);

    }
    private function ArmarVacaciones()
    {
        $valor = ((15 * $this->GetPorcentajeVacaciones())) - $this->dias_vacaciones;
        $this->vacaciones_acumuladas = $valor;
        $salario = [
            [
                'Base' => '15',
                'Porcentaje' => '4.167%',
                'Formula' => '(15 días * 4.167%) - ' . $this->dias_vacaciones . ' días',
                'Dias' => $this->dias_vacaciones,
                'Acumuladas' => number_format($valor, 3, ".", ""),
            ],
        ];

        return $salario;

    }
    private function GetPorcentajeVacaciones()
    {
        $query = 'SELECT Porcentaje FROM Provision WHERE Prefijo LIKE "%Vacaciones%" ';

        $this->queryObj->SetQuery($query);
        $datos = $this->queryObj->ExecuteQuery('simple');
        return $datos['Porcentaje'];
    }
    private function GetVacaciones()
    {
        $anio = date('Y');
        $query = 'SELECT * FROM Festivos_Anio WHERE Anio=' . $anio;

        $this->queryObj->SetQuery($query);
        $datos = $this->queryObj->ExecuteQuery('simple');

        if ($datos) {
            $festivos = $datos['Festivos'];
            if (!$festivos) {
                $festivos = $this->GetFestivos($anio);
            }

            $this->GetNovedades($festivos);

            $fecha = date('Y-m-d');
            $fecha1 = new DateTime(isset($datos['Fecha_Inicio_Contrato']));
            $fecha2 = new DateTime($fecha);
            $resultado = $fecha1->diff($fecha2);
            $resultado = $resultado->format('%R%a');
            $resultado = trim($resultado, '+');
        }

    }
    private function GetFestivos($anio)
    {
        $curl = curl_init();
        // Set some options - we are passing in a useragent too here
        curl_setopt_array($curl, array(
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_URL => 'https://calendarific.com/api/v2/holidays?&api_key=c24852651b305a7c29a23705764f966758d20074&country=CO&year=' . $anio . '&type=national',
            CURLOPT_USERAGENT => 'Codular Sample cURL Request',
        ));
        // Send the request & save response to $resp
        $resp = curl_exec($curl);
        $result = (array) json_decode($resp, true);
        $contenido = '';
        foreach ((array) $result['response']['holidays'] as $value) {
            $contenido .= $value['date']['iso'] . ';';
        }

        $contenido = trim($contenido, ';');

        $query = "INSERT INTO Festivos_Anio (`Id_Festivos_Anio`, `Anio`, `Festivos`) VALUES (NULL," . $anio . ",'" . $contenido . "') ";

        $this->queryObj->SetQuery($query);
        $this->queryObj->QueryUpdate();

        return $contenido;

    }
    private function GetNovedades($festivos)
    {
        $query = 'SELECT TN.*, N.*
                FROM Tipo_Novedad TN
                INNER JOIN Novedad N
                ON TN.Id_Tipo_Novedad = N.Id_Tipo_Novedad
                AND TN.Tipo_Novedad="Vacaciones"
                AND N.Identificacion_Funcionario=' . $this->id_funcionario . '
                AND ((N.Fecha_Inicio>="' . $this->fini . '" AND N.Fecha_Inicio<="' . $this->ffin . '") OR (N.Fecha_Fin>="' . $this->fini . '" AND N.Fecha_Fin<="' . $this->ffin . '") OR (N.Fecha_Inicio<="' . $this->fini . '" AND N.Fecha_Fin>="' . $this->ffin . '"))';

        $this->queryObj->SetQuery($query);
        $datos = $this->queryObj->ExecuteQuery('multiple');

        if ($datos) {
            //  echo "1";
            foreach ($datos as $nov) {
                if ($nov["Fecha_Inicio"] <= $this->fini) {
                    $ini_nov = $this->fini;
                } else {
                    $ini_nov = $nov["Fecha_Inicio"];
                }
                if ($nov["Fecha_Fin"] >= $this->ffin) {
                    $fin_nov = $this->ffin;
                } else {
                    $fin_nov = $nov["Fecha_Fin"];
                }

            }

            $fecha = $ini_nov;
            for ($i = 0; $fecha < $fin_nov; $i++) {
                $pos = strpos($festivos, date("Y-m-d"));
                $dia_hoy = date("w", strtotime($fecha));

                if ($dia_hoy != 0 && $pos === false) {
                    $this->dias_vacaciones++;
                }
                $fecha = strtotime("+1 days", strtotime($fecha));
                $fecha = date('Y-m-d', $fecha);
            }

        }
    }

    private function GetAjusteProvisiones()
    {
        $func = $this->id_funcionario;
        $quincena = $this->quincena;
        $query =
            "  SELECT CONCAT(
            '{\"Identificacion_Funcionario\":', Identificacion_Funcionario,
            ',\"Quincena\":\"', PF.Quincena,
			'\",\"Ajustes\":{'
            ,Group_Concat(CONCAT_WS('',
                '\"', PF.Concepto,
                        '\":{\"Id_Provision_Funcionario_Ajuste\":\"',PF.Id_Provision_Funcionario_Ajuste,
                        '\",\"Valor\":\"',PF.Valor,
                        '\",\"Cantidad\":\"',PF.Cantidad,
                        '\",\"Porcentaje\":\"',PF.Porcentaje,
                    '\"}')),
            '}}') AS Ajustes
        FROM Provision_Funcionario_Ajuste PF
        WHERE PF.Estado= 'Activo' AND PF.Quincena LIKE '$quincena'
        AND PF.Identificacion_Funcionario = $func
        GROUP BY PF.Identificacion_Funcionario, PF.Quincena";
        $this->queryObj->SetQuery($query);
        $ajustes = $this->queryObj->ExecuteQuery('simple');
        return json_decode($ajustes['Ajustes'], true);
    }

    private function getMayo2022() /* TODO: AJUSTAR PARA TOMAR LA SUMA DESDE MES DE MAYO 2022 (primer provision transmitida a Dian), INCLUYENDO LA TABLA DE AJUSTES */
    {
        $func = $this->id_funcionario;
        $query = " SELECT CONCAT(
            '{\"Identificacion_Funcionario\":', PF.Identificacion_Funcionario,
            ',\"Nomina\":\"', PF.Id_Nomina,
			'\",\"Reporte\":{'
            ,Group_Concat(CONCAT_WS('',
                '\"', PF.Tipo,
                        '\":{\"Id_Provision_Funcionario\":\"',PF.Id_Provision_Funcionario,
                        '\",\"Valor\":\"',PF.Valor,
                        '\",\"Cantidad\":\"',PF.Cantidad,
                         '\",\"Porcentaje\":\"',(P.Porcentaje*100),
                    '\"}')),
            '}}') AS Mayo
        FROM Provision_Funcionario PF
        LEFT JOIN Provision P ON P.Prefijo = PF.Tipo
        WHERE PF.Id_Nomina=8
        AND PF.Identificacion_Funcionario = $func
         GROUP BY PF.Identificacion_Funcionario, PF.Id_Nomina";

        $this->queryObj->SetQuery($query);
        $mayo = $this->queryObj->ExecuteQuery('simple');
        return json_decode($mayo['Mayo'], true);
    }

    private function GetDatosFuncionario()
    {

        $query = 'SELECT *,(SELECT Salario_Base FROM Configuracion WHERE Id_Configuracion=1 ) as Salario_Base, (SELECT Subsidio_Transporte FROM Configuracion WHERE Id_Configuracion=1 ) as Subsidio_Transporte, (SELECT Salario_Auxilio_Transporte FROM Configuracion WHERE Id_Configuracion=1 ) as Salario_Auxilio_Transporte FROM Contrato_Funcionario WHERE Estado="Activo" AND Identificacion_Funcionario=' . $this->id_funcionario;

        $this->queryObj->SetQuery($query);
        $this->datos_funcionario = $this->queryObj->ExecuteQuery('simple');
    }
    private function Validar()
    {
        $datos = false;

        if ($this->datos_funcionario['Id_Tipo_Contrato'] != 8 && $this->datos_funcionario['Id_Tipo_Contrato'] != 7 && $this->datos_funcionario['Id_Tipo_Contrato'] != 6 && $this->datos_funcionario['Id_Tipo_Contrato'] != 9 && $this->datos_funcionario['Id_Tipo_Contrato'] != 10 && $this->datos_funcionario['Id_Tipo_Contrato'] != 10) {
            $datos = true;
        }

        return $datos;
    }

    private function CalcularDiasProvisionVacaciones()
    {

        /* Para la provision de vacaciones se estableció que se hace desde la fecha de inicio del contrato hasta la fecha de reporte */
        $inicio = $this->datos_funcionario['Fecha_Inicio_Contrato'];
        $fin = $this->ffin;

        $datetime1 = new DateTime($inicio);
        $datetime2 = new DateTime($fin);
        $interval = $datetime2->diff($datetime1);
        $intervalMeses = $interval->format("%m");
        $intervalAnos = $interval->format("%y") * 12;
        $val = ($intervalMeses + $intervalAnos + 1) % 4; /*  Se estableció reportar 1 dia al mes, con 1 dia adicional cada 4 meses desde la fecha del contrato*/
        if ($val == 0) {
            return 2;
        }
        return 1;

    }}

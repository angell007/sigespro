<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

use PhpParser\Node\Stmt\Echo_;

require_once('../../vendor/autoload.php');

include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta_paginada.php');
include_once('../../helper/response.php');
include_once('../../helper/makeConditions.php');

new  GetDispensacion();

use Carbon\Carbon as Carbon;

class GetDispensacion
{
    public $funcionario;
    public $response;
    private $queryObj;
    public $conditions1;
    public $conditions2;
    public $conditions3;
    public $partialconditions;
    public $codigo1;
    public $codigo2;
    public $codigo3;
    public $origen1;
    public $origen2;
    public $origen3;
    public $destino1;
    public $destino2;
    public $destino3, $paciente1, $paciente2;

    public function __construct()
    {
        $this->funcionario =  (isset($_REQUEST['funcionario']) && $_REQUEST['funcionario'] != '') ? $_REQUEST['funcionario'] : false;

        $this->codigo1 =  (isset($_REQUEST['codigo1']) && $_REQUEST['codigo1'] != '') ? $_REQUEST['codigo1'] : '';
        $this->codigo2 =  (isset($_REQUEST['codigo2']) && $_REQUEST['codigo2'] != '') ? $_REQUEST['codigo2'] : '';
        $this->codigo3 =  (isset($_REQUEST['codigo3']) && $_REQUEST['codigo3'] != '') ? $_REQUEST['codigo3'] : '';

        $this->origen1 =  (isset($_REQUEST['origen1']) && $_REQUEST['origen1'] != '') ? $_REQUEST['origen1'] : '';
        $this->origen2 =  (isset($_REQUEST['origen2']) && $_REQUEST['origen2'] != '') ? $_REQUEST['origen2'] : '';
        $this->origen3 =  (isset($_REQUEST['origen3']) && $_REQUEST['origen3'] != '') ? $_REQUEST['origen3'] : '';

        $this->destino1 =  (isset($_REQUEST['destino1']) && $_REQUEST['destino1'] != '') ? $_REQUEST['destino1'] : '';
        $this->destino2 =  (isset($_REQUEST['destino2']) && $_REQUEST['destino2'] != '') ? $_REQUEST['destino2'] : '';
        $this->destino3 =  (isset($_REQUEST['destino3']) && $_REQUEST['destino3'] != '') ? $_REQUEST['destino3'] : '';


        $this->paciente1 =  (isset($_REQUEST['paci1']) && $_REQUEST['paci1'] != '') ? $_REQUEST['paci1'] : '';
        $this->paciente2 =  (isset($_REQUEST['paci2']) && $_REQUEST['paci2'] != '') ? $_REQUEST['paci2'] : '';

        $this->queryObj = new consulta();
        $this->setConditions($_REQUEST);
        $this->init();
    }

    public function init()
    {
        try {

            if (!$this->funcionario) {
                show(myerror('Funcionario no se encuentra'));
            }

            $this->DisWithoutError();
            $this->DisWithError();

            show($this->response);
        } catch (\Throwable $th) {
            show(myerror($th->getMessage() . http_response_code(500)));
        }
    }

    public function DisWithoutError()
    {

        $query = "SELECT AuD.Id_Auditoria, D.Codigo, D.Numero_Documento Doc_Paciente, D.Paciente, D.Fecha_Actual, D.Id_Dispensacion, PD.Id_Punto_Dispensacion,
                            CASE
                            WHEN DD.Tipo_Direccion = 'Paciente' THEN 
                            (SELECT CONCAT(DeP.Nombre, ' ', MuN.Nombre, ' ', DiD.Descripcion, ' ', PaD.Direccion2, ' ', PaD.Direccion3, ' ', DiDc.Descripcion, ' ', PaD.Direccion5 )
                            FROM Paciente_Direccion AS PaD
                            INNER JOIN Direccion_Dian AS DiD ON DiD.Codigo = PaD.Direccion1 
                            INNER JOIN Direccion_Dian AS DiDc ON DiDc.Codigo = PaD.Direccion4 
                            INNER JOIN Departamento AS DeP ON DeP.Id_Departamento = PaD.Id_Departamento 
                            INNER JOIN Municipio AS MuN ON MuN.Id_Municipio = PaD.Id_Municipio) 
                            WHEN DD.Tipo_Direccion = 'Punto_Dispensacion' THEN (SELECT Direccion FROM Punto_Dispensacion AS PuD WHERE PuD.Id_Punto_dispensacion = DD.ID_Direccion) 
                            WHEN DD.Tipo_Direccion = 'Bodega_Nuevo' THEN (SELECT Direccion FROM Bodega_Nuevo AS BoN WHERE BoN.Id_Bodega_Nuevo = DD.ID_Direccion) 
                            ELSE 'Sin Direccion'
                            END As Destino
                            FROM Dispensacion D 
                            INNER JOIN Punto_Dispensacion  AS PD ON PD.Id_Punto_Dispensacion = D.Id_Punto_Dispensacion 
                            INNER JOIN Paciente  AS P ON P.Id_Paciente = D.Numero_documento 
                            INNER JOIN Auditoria  AS AuD ON AuD.Id_Dispensacion = D.Id_Dispensacion 
                            LEFT JOIN  Dispensacion_Domicilio AS DD ON DD.Id_Dispensacion = D.Id_Dispensacion
                            WHERE D.Id_Dispensacion not in (SELECT Id_Dispensacion FROM Dispensacion_Domicilio DisD )
                           AND AuD.Estado = 'Aceptar' AND D.Tipo_Entrega = 'Domicilio' AND D.Estado_Dispensacion != 'Anulada'
                           AND  DATE( D.Fecha_Actual )  >='2021-08-21' " . $this->conditions1 . " ORDER BY D.Id_Dispensacion DESC LIMIT 20";
                           
        /*$query = "SELECT 
    DM.Id_Dispensacion_Mipres as PREDIS,
    P.Id_Paciente, 
    CONCAT_WS(' ', P.Primer_Nombre, P.Segundo_Nombre, P.Primer_Apellido, P.Segundo_Apellido) AS Nombre_Completo,
    M.Nombre AS Municipio,
    DM.EstadoLLamada,
    DM.Fecha_Direccionamiento, 
    DM.Fecha_Maxima_Entrega, 
    f.Identificacion_Funcionario,
    CONCAT_WS(' ', f.Nombres, f.Apellidos) AS Responsable
FROM 
    A_Dispensacion_Mipres DM
INNER JOIN 
    Paciente P ON DM.Id_Paciente = P.Id_Paciente
INNER JOIN 
    Municipio M ON M.Codigo = DM.Codigo_Municipio
INNER JOIN 
    Funcionario f ON DM.Id_AgenteCallcenter = f.Identificacion_Funcionario
WHERE 
    DM.EstadoLLamada = 'Pendiente' ORDER BY DM.Id_Dispensacion_Mipres DESC LIMIT 20;";*/

        $this->queryObj->SetQuery($query);
        $this->queryObj->setTipo('Multiple');
                // echo $query; exit;

        $this->response['DisWithoutError'] = $this->queryObj->getData();
        
    }

    public function DisWithError()
    {

        $query = "SELECT  AuD.Estado,D.Tipo_Entrega, AuD.Id_Auditoria, BN.Nombre As Origen, D.Codigo, D.Numero_Documento Doc_Paciente, D.Paciente, D.Fecha_Actual,
        IF(DD.Id_Dispensacion = '', 'warning', '' ) As TieneDomicilio,
        CASE
        WHEN DD.Tipo_Direccion = 'Paciente' THEN 
        (SELECT CONCAT(DeP.Nombre, ' ', MuN.Nombre, ' ', DiD.Descripcion, ' ', PaD.Direccion2, ' ', PaD.Direccion3, ' ', DiDc.Descripcion, ' ', PaD.Direccion5 )
        FROM Paciente_Direccion AS PaD
        INNER JOIN Direccion_Dian AS DiD ON DiD.Codigo = PaD.Direccion1 
        INNER JOIN Direccion_Dian AS DiDc ON DiDc.Codigo = PaD.Direccion4 
        INNER JOIN Departamento AS DeP ON DeP.Id_Departamento = PaD.Id_Departamento 
        INNER JOIN Municipio AS MuN ON MuN.Id_Municipio = PaD.Id_Municipio) 
        WHEN DD.Tipo_Direccion = 'Punto_Dispensacion' THEN PTO.Direccion 
        WHEN DD.Tipo_Direccion = 'Bodega_Nuevo' THEN BDN.Direccion 
        ELSE 'Sin Direccion'
        END As Destino
        FROM Dispensacion D 
        INNER JOIN Punto_Dispensacion  AS PD ON PD.Id_Punto_Dispensacion = D.Id_Punto_Dispensacion 
        left JOIN Bodega_Nuevo  AS BN ON BN.Id_Bodega_Nuevo = PD.Id_Bodega_Despacho 
        INNER JOIN Paciente  AS P ON P.Id_Paciente = D.Numero_documento 
        INNER JOIN Auditoria  AS AuD ON AuD.Id_Dispensacion = D.Id_Dispensacion 
        LEFT JOIN  Dispensacion_Domicilio AS DD ON DD.Id_Dispensacion = D.Id_Dispensacion
        Left Join Punto_Dispensacion PTO on PTO.Id_Punto_Dispensacion = DD.Id_Direccion and DD.Tipo_Direccion= 'Punto_Dispensacion'
        Left Join Bodega_Nuevo BDN on BDN.Id_Bodega_Nuevo = DD.Id_Direccion and DD.Tipo_Direccion='Bodega_Nuevo'
        WHERE D.Estado_Dispensacion != 'Anulada' AND  AuD.Estado = 'Rechazar'
        AND  DATE( D.Fecha_Actual)  >= '2021-08-27' " . $this->conditions2 . " ORDER BY D.Id_Dispensacion DESC LIMIT 20";
        
       /* $query = "SELECT 
    DM.Id_Dispensacion_Mipres as PREDIS,
    P.Id_Paciente, 
    CONCAT_WS(' ', P.Primer_Nombre, P.Segundo_Nombre, P.Primer_Apellido, P.Segundo_Apellido) AS Nombre_Completo,
    M.Nombre AS Municipio,
    DM.EstadoLLamada,
    DM.Fecha_Direccionamiento, 
    DM.Fecha_Maxima_Entrega, 
    f.Identificacion_Funcionario,
    CONCAT_WS(' ', f.Nombres, f.Apellidos) AS Responsable
FROM 
    A_Dispensacion_Mipres DM
INNER JOIN 
    Paciente P ON DM.Id_Paciente = P.Id_Paciente
INNER JOIN 
    Municipio M ON M.Codigo = DM.Codigo_Municipio
INNER JOIN 
    Funcionario f ON DM.Id_AgenteCallcenter = f.Identificacion_Funcionario
WHERE 
    DM.EstadoLLamada = 'Pendiente' ORDER BY DM.Id_Dispensacion_Mipres DESC LIMIT 20;";*/
        
        // echo $query; exit;

        $this->queryObj->SetQuery($query);
        $this->queryObj->setTipo('Multiple');
        $this->response['DisWithError'] = $this->queryObj->getData();
    }

    public function recoverIdPuntos()
    {
        $subqueryIdBodegas = "SELECT FBN.Id_Bodega_Nuevo FROM Funcionario  As F
                INNER JOIN Funcionario_Bodega_Nuevo As FBN ON F.Identificacion_Funcionario = FBN.Identificacion_Funcionario
                WHERE F.Identificacion_Funcionario = $this->funcionario";
        $this->queryObj->setTipo('Multiple');
        $this->queryObj->SetQuery($subqueryIdBodegas);
        $IDBodegas = $this->destructBodega($this->queryObj->getData());

        $this->queryObj = new consulta();
        $subqueryIdPuntos = "SELECT PD.Id_Punto_Dispensacion 
                FROM Punto_Dispensacion  As PD
                WHERE Id_Bodega_Despacho IN ($IDBodegas)";
        $this->queryObj->setTipo('Multiple');
        $this->queryObj->SetQuery($subqueryIdPuntos);

        return $this->destructDispensacion($this->queryObj->getData());
    }

    public function destructBodega($data)
    {

        $string = 0;

        if ($data['data'] != null) {
            foreach ($data['data'] as $ID) {
                $string .= ',' . $ID['Id_Bodega_Nuevo'];
            }
        }

        return $string;
    }
    public function destructDispensacion($data)
    {

        $string = 0;

        if ($data['data'] != null) {
            foreach ($data['data'] as $ID) {
                $string .= ',' . $ID['Id_Punto_Dispensacion'];
            }
        }

        return $string;
    }

    public function setConditions($request)
    {

        foreach ($request as $index => $params) {

            if ($params != '') {
                if ($index == 'fecha') {
                    $this->conditions3 = $this->makeConditionEqual($params, 'D.Fecha_Formula');
                }
                if ($index == 'origen1') {
                    $this->conditions1 =  $this->makeConditionEqual($params, 'BN.Nombre');
                }
                if ($index == 'origen2') {
                    $this->conditions2 = $this->makeConditionEqual($params, 'BN.Nombre');
                }
                if ($index == 'origen3') {
                    $this->conditions3 = $this->makeConditionEqual($params, 'BN.Nombre');
                }
                if ($index == 'destino1') {
                    $this->conditions1 = $this->makeConditionHaving($params, 'Destino');
                }
                if ($index == 'destino2') {
                    $this->conditions2 = $this->makeConditionHaving($params, 'Destino');
                }
                if ($index == 'destino3') {
                    $this->conditions3 = $this->makeConditionHaving($params, 'Destino');
                }
                if ($index == 'paci2') {
                    $this->conditions2 = $this->makeConditionEqual($params, 'P.Primer_Nombre');
                }


                if ($index == 'paci1') {
                    $this->conditions1 = $this->makeConditionEqual($params, 'P.Primer_Nombre');
                }
                if ($index == 'codigo1') {
                    $this->conditions1 = $this->makeConditionEqual($params, 'D.Codigo');
                }
                if ($index == 'codigo2') {
                    $this->conditions2 = $this->makeConditionEqual($params, 'D.Codigo');
                }
            }
        }
    }

    public function makeConditionEqual($dato, $columna)
    {
        try {
            $this->partialconditions .= "AND $columna LIKE '%$dato%' ";
            return $this->partialconditions;
        } catch (\Throwable $th) {
            show(myerror($th->getMessage()));
        }
    }
    public function makeConditionHaving($dato, $columna)
    {
        try {
            $this->partialconditions .= "HAVING $columna LIKE '%$dato%' ";
            return $this->partialconditions;
        } catch (\Throwable $th) {
            show(myerror($th->getMessage()));
        }
    }
}

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

new  GetPreDispensacion();

use Carbon\Carbon as Carbon;

class GetPreDispensacion
{
    public $funcionario;
    public $response;
    private $queryObj;
    
    public $conditionsPendientes;
    public $conditionsFallidas;
    public $conditionsContactadas;
    private $limit;
    private $page;
    private $offset;
    private $includeProductos;

    public $partialconditions;
    
    public $pacienteContactadas;
    public $codigoDispensacionContactadas;
    public $prescripcionContactadas;
    public $direccionamientoContactadas;
    
    public $pacienteFallidas;
    public $direccionamientoFallidas;
    public $prescripcionFallidas;
    
    public $pacientePendientes;
    public $direccionamientoPendientes;
    public $prescripcionPendientes;

    public function __construct()
    {
        //var_dump("esto es una prueba");
        $this->funcionario =  (isset($_REQUEST['funcionario']) && $_REQUEST['funcionario'] != '') ? $_REQUEST['funcionario'] : false;
        
        $this->pacienteContactadas =  (isset($_REQUEST['pacienteContactadas']) && $_REQUEST['pacienteContactadas'] != '') ? $_REQUEST['pacienteContactadas'] : '';
        $this->codigoDispensacionContactadas =  (isset($_REQUEST['codigoDispensacionContactadas']) && $_REQUEST['codigoDispensacionContactadas'] != '') ? $_REQUEST['codigoDispensacionContactadas'] : '';
        $this->prescripcionContactadas =  (isset($_REQUEST['prescripcionContactadas']) && $_REQUEST['prescripcionContactadas'] != '') ? $_REQUEST['prescripcionContactadas'] : '';
        $this->direccionamientoContactadas =  (isset($_REQUEST['direccionamientoContactadas']) && $_REQUEST['direccionamientoContactadas'] != '') ? $_REQUEST['direccionamientoContactadas'] : '';
        
        $this->pacientePendientes =  (isset($_REQUEST['pacienteFallidas']) && $_REQUEST['pacienteFallidas'] != '') ? $_REQUEST['pacienteFallidas'] : '';
        $this->direccionamientoFallidas =  (isset($_REQUEST['direccionamientoFallidas']) && $_REQUEST['direccionamientoFallidas'] != '') ? $_REQUEST['direccionamientoFallidas'] : '';
        $this->prescripcionFallidas =  (isset($_REQUEST['prescripcionFallidas']) && $_REQUEST['prescripcionFallidas'] != '') ? $_REQUEST['prescripcionFallidas'] : '';

        $this->pacientePendientes =  (isset($_REQUEST['pacientePendientes']) && $_REQUEST['pacientePendientes'] != '') ? $_REQUEST['pacientePendientes'] : '';
        $this->direccionamientoPendientes =  (isset($_REQUEST['direccionamientoPendientes']) && $_REQUEST['direccionamientoPendientes'] != '') ? $_REQUEST['direccionamientoPendientes'] : '';
        $this->prescripcionPendientes =  (isset($_REQUEST['prescripcionPendientes']) && $_REQUEST['prescripcionPendientes'] != '') ? $_REQUEST['prescripcionPendientes'] : '';

        $this->limit = (isset($_REQUEST['limit']) && is_numeric($_REQUEST['limit'])) ? (int) $_REQUEST['limit'] : 100;
        $this->page = (isset($_REQUEST['page']) && is_numeric($_REQUEST['page'])) ? (int) $_REQUEST['page'] : 1;
        $this->includeProductos = !(isset($_REQUEST['includeProductos']) && $_REQUEST['includeProductos'] === '0');
        if ($this->page < 1) {
            $this->page = 1;
        }
        if ($this->limit <= 0) {
            $this->limit = null;
        }
        $this->offset = ($this->limit !== null) ? ($this->page - 1) * $this->limit : 0;

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
            $this->preDisPendientes();
            $this->preDisContactoEfectivo();
            $this->preDisFallo();

            show($this->response);
        } catch (\Throwable $th) {
            show(myerror($th->getMessage() . http_response_code(500)));
        }
    }

    public function preDisContactoEfectivo()
    {
        $productosSelect = $this->includeProductos ? "(
            SELECT JSON_ARRAYAGG(
                    JSON_OBJECT(
                        'NoPrescripcion', PD.NoPrescripcion,
                        'IDDireccionamiento', PD.IDDireccionamiento
                    )
                    )
            FROM Producto_Dispensacion_Mipres PD
            WHERE PD.Id_Dispensacion_Mipres = DM.Id_Dispensacion_Mipres
            )" : "NULL";

        $query = "SELECT SQL_CALC_FOUND_ROWS 
            DM.Id_Dispensacion_Mipres AS PREDIS,
            D.Id_Dispensacion,
            P.EPS,
            P.Telefono,
            DM.Fecha,
            DM.Fecha_Contacto,
            D.Codigo,
            M.Nombre AS Municipio,
            CONCAT_WS(' ', P.Primer_Nombre, P.Segundo_Nombre, P.Primer_Apellido, P.Segundo_Apellido) AS Nombre,
            $productosSelect AS Productos
        FROM 
            Dispensacion_Mipres DM
        INNER JOIN 
            Paciente P ON P.Id_Paciente = DM.Id_Paciente
        INNER JOIN 
            Municipio M ON M.Codigo = DM.Codigo_Municipio
        LEFT JOIN 
            Dispensacion D ON D.Id_Dispensacion_Mipres = DM.Id_Dispensacion_Mipres
        WHERE DM.Fecha >= '2025-03-30 00:00:00' AND DM.Estado_Callcenter = 'Contactado' $this->conditionsContactadas
        ORDER BY DM.Fecha_Contacto DESC" . $this->getLimitClause() . ";";


        $this->queryObj->SetQuery($query);
        $this->queryObj->setTipo('Multiple');
        $data = $this->queryObj->getData();

        foreach ($data['data'] as &$row) {
            if (isset($row['Productos'])) {
                $row['Productos'] = json_decode($row['Productos'], true);
            }
        }

        $this->response['PreDisContactoEfectivo'] = $data;
    }

    public function preDisPendientes()
    {
        $productosSelect = $this->includeProductos ? "(
            SELECT JSON_ARRAYAGG(
                    JSON_OBJECT(
                        'NoPrescripcion', PD.NoPrescripcion,
                        'IDDireccionamiento', PD.IDDireccionamiento
                    )
                    )
            FROM Producto_Dispensacion_Mipres PD
            WHERE PD.Id_Dispensacion_Mipres = DM.Id_Dispensacion_Mipres
            )" : "NULL";

        $query = "SELECT SQL_CALC_FOUND_ROWS 
            DM.Id_Dispensacion_Mipres AS PREDIS,
            P.EPS,
            P.Telefono,
            DM.Fecha AS Fecha_Solicitud,
            DM.Fecha_Direccionamiento AS Fecha,
            DM.Fecha_Maxima_Entrega,
            M.Nombre AS Municipio,
            CONCAT_WS(' ', P.Primer_Nombre, P.Segundo_Nombre, P.Primer_Apellido, P.Segundo_Apellido) AS Nombre,
            $productosSelect AS Productos
        FROM 
            Dispensacion_Mipres DM
        INNER JOIN 
            Paciente P ON P.Id_Paciente = DM.Id_Paciente
        INNER JOIN 
            Municipio M ON M.Codigo = DM.Codigo_Municipio
        LEFT JOIN 
            Dispensacion D ON D.Id_Dispensacion_Mipres = DM.Id_Dispensacion_Mipres
        WHERE 
            DM.Fecha >= '2025-03-30 00:00:00' 
            AND DM.Estado_Callcenter = 'Pendiente' 
            AND (DM.Estado IS NULL OR DM.Estado <> 'Facturado')
            AND (D.Estado IS NULL OR D.Estado <> 'Entregado' OR D.Estado_Dispensacion = 'Anulada')
            AND (D.Estado_Facturacion IS NULL OR D.Estado_Facturacion <> 'Facturada' OR D.Estado_Dispensacion = 'Anulada')
            $this->conditionsPendientes
        ORDER BY DM.Fecha_Direccionamiento ASC" . $this->getLimitClause() . ";";

        $this->queryObj->SetQuery($query);
        $this->queryObj->setTipo('Multiple');
        $data = $this->queryObj->getData();

        foreach ($data['data'] as &$row) {
            if (isset($row['Productos'])) {
                $row['Productos'] = json_decode($row['Productos'], true);
            }
        }

        $this->response['PreDisPendientes'] = $data;
    }

    public function preDisFallo()
    {
        $productosSelect = $this->includeProductos ? "(
            SELECT JSON_ARRAYAGG(
                    JSON_OBJECT(
                        'NoPrescripcion', PD.NoPrescripcion,
                        'IDDireccionamiento', PD.IDDireccionamiento
                    )
                    )
            FROM Producto_Dispensacion_Mipres PD
            WHERE PD.Id_Dispensacion_Mipres = DM.Id_Dispensacion_Mipres
            )" : "NULL";

        $query = "SELECT SQL_CALC_FOUND_ROWS 
            DM.Id_Dispensacion_Mipres AS PREDIS,
            P.EPS,
            P.Telefono,
            DM.Fecha AS Fecha_Solicitud,
            DM.Fecha_Maxima_Entrega,
            M.Nombre AS Municipio,
            CONCAT_WS(' ', P.Primer_Nombre, P.Segundo_Nombre, P.Primer_Apellido, P.Segundo_Apellido) AS Nombre,
            $productosSelect AS Productos
        FROM 
            Dispensacion_Mipres DM
        INNER JOIN 
            Paciente P ON P.Id_Paciente = DM.Id_Paciente
        INNER JOIN 
            Municipio M ON M.Codigo = DM.Codigo_Municipio
        WHERE DM.Fecha >= '2025-03-30 00:00:00' 
            AND DM.Estado_Callcenter = 'No Contactado' 
            $this->conditionsFallidas
        ORDER BY DM.Fecha_Direccionamiento ASC" . $this->getLimitClause() . ";";

        $this->queryObj->SetQuery($query);
        $this->queryObj->setTipo('Multiple');
        $data = $this->queryObj->getData();

        foreach ($data['data'] as &$row) {
            if (isset($row['Productos'])) {
                $row['Productos'] = json_decode($row['Productos'], true);
            }
        }
        $this->response['PreDisFallo'] = $data;
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
                if ($index == 'pacientePendientes') {
                    $dato = $params;
                    $conditions = [];
                    $conditions[] = "P.Primer_Nombre LIKE '%$dato%'";
                    $conditions[] = "P.Segundo_Nombre LIKE '%$dato%'";
                    $conditions[] = "P.Primer_Apellido LIKE '%$dato%'";
                    $conditions[] = "P.Segundo_Apellido LIKE '%$dato%'";
                    $conditions[] = "REGEXP_REPLACE(
                        TRIM(
                            CONCAT_WS(' ', 
                                NULLIF(P.Primer_Nombre, ''), 
                                NULLIF(P.Segundo_Nombre, ''), 
                                NULLIF(P.Primer_Apellido, ''), 
                                NULLIF(P.Segundo_Apellido, '')
                            )
                        ), 
                        ' +', ' '
                    ) LIKE '%$dato%'";                    
                    
                    if (is_numeric($dato)) {
                        $conditions[] = "P.Id_Paciente = $dato";
                    }
                    $this->conditionsPendientes .= " AND (" . implode(' OR ', $conditions) . ")";
                }
                if ($index == 'direccionamientoPendientes') {
                    $dato = $params;
                    $this->conditionsPendientes .= " AND EXISTS (
                        SELECT 1 
                        FROM Producto_Dispensacion_Mipres PD 
                        WHERE 
                            PD.Id_Dispensacion_Mipres = DM.Id_Dispensacion_Mipres 
                            AND PD.IDDireccionamiento LIKE '%$dato%'
                    )";
                }
                if ($index == 'prescripcionPendientes') {
                    $dato = $params;
                    $this->conditionsPendientes .= " AND EXISTS (
                        SELECT 1 
                        FROM Producto_Dispensacion_Mipres PD 
                        WHERE 
                            PD.Id_Dispensacion_Mipres = DM.Id_Dispensacion_Mipres 
                            AND PD.NoPrescripcion LIKE '%$dato%'
                    )";
                }

                if ($index == 'pacienteFallidas') {
                    $dato = $params;
                    $conditions = [];
                    $conditions[] = "P.Primer_Nombre LIKE '%$dato%'";
                    $conditions[] = "P.Segundo_Nombre LIKE '%$dato%'";
                    $conditions[] = "P.Primer_Apellido LIKE '%$dato%'";
                    $conditions[] = "P.Segundo_Apellido LIKE '%$dato%'";
                    $conditions[] = "REGEXP_REPLACE(
                        TRIM(
                            CONCAT_WS(' ', 
                                NULLIF(P.Primer_Nombre, ''), 
                                NULLIF(P.Segundo_Nombre, ''), 
                                NULLIF(P.Primer_Apellido, ''), 
                                NULLIF(P.Segundo_Apellido, '')
                            )
                        ), 
                        ' +', ' '
                    ) LIKE '%$dato%'";                    
                    
                    if (is_numeric($dato)) {
                        $conditions[] = "P.Id_Paciente = $dato";
                    }
                    $this->conditionsFallidas .= " AND (" . implode(' OR ', $conditions) . ")";
                }

                if ($index == 'prescripcionFallidas') {
                    $dato = $params;
                    $this->conditionsFallidas .= " AND EXISTS (
                        SELECT 1 
                        FROM Producto_Dispensacion_Mipres PD 
                        WHERE 
                            PD.Id_Dispensacion_Mipres = DM.Id_Dispensacion_Mipres 
                            AND PD.NoPrescripcion LIKE '%$dato%'
                    )";
                }

                if ($index == 'direccionamientoFallidas') {
                    $dato = $params;
                    $this->conditionsFallidas .= " AND EXISTS (
                        SELECT 1 
                        FROM Producto_Dispensacion_Mipres PD 
                        WHERE 
                            PD.Id_Dispensacion_Mipres = DM.Id_Dispensacion_Mipres 
                            AND PD.IDDireccionamiento LIKE '%$dato%'
                    )";
                }

                if ($index == 'pacienteContactadas') {
                    $dato = $params;
                    $conditions = [];
                    $conditions[] = "P.Primer_Nombre LIKE '%$dato%'";
                    $conditions[] = "P.Segundo_Nombre LIKE '%$dato%'";
                    $conditions[] = "P.Primer_Apellido LIKE '%$dato%'";
                    $conditions[] = "P.Segundo_Apellido LIKE '%$dato%'";
                    $conditions[] = "REGEXP_REPLACE(
                        TRIM(
                            CONCAT_WS(' ', 
                                NULLIF(P.Primer_Nombre, ''), 
                                NULLIF(P.Segundo_Nombre, ''), 
                                NULLIF(P.Primer_Apellido, ''), 
                                NULLIF(P.Segundo_Apellido, '')
                            )
                        ), 
                        ' +', ' '
                    ) LIKE '%$dato%'";                    
                    
                    if (is_numeric($dato)) {
                        $conditions[] = "P.Id_Paciente = $dato";
                    }
                    $this->conditionsContactadas .= " AND (" . implode(' OR ', $conditions) . ")";
                }

                if ($index == 'codigoDispensacionContactadas') {
                    $dato = $params;
                    $this->conditionsContactadas .= " AND D.Codigo LIKE '%$dato%'";
                }
                if ($index == 'prescripcionContactadas') {
                    $dato = $params;
                    $this->conditionsContactadas .= " AND EXISTS (
                        SELECT 1 
                        FROM Producto_Dispensacion_Mipres PD 
                        WHERE 
                            PD.Id_Dispensacion_Mipres = DM.Id_Dispensacion_Mipres 
                            AND PD.NoPrescripcion LIKE '%$dato%'
                    )";
                }

                if ($index == 'direccionamientoContactadas') {
                    $dato = $params;
                    $this->conditionsContactadas .= " AND EXISTS (
                        SELECT 1 
                        FROM Producto_Dispensacion_Mipres PD 
                        WHERE 
                            PD.Id_Dispensacion_Mipres = DM.Id_Dispensacion_Mipres 
                            AND PD.IDDireccionamiento LIKE '%$dato%'
                    )";
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

    private function getLimitClause()
    {
        if ($this->limit === null) {
            return '';
        }
        return " LIMIT $this->offset, $this->limit";
    }
}

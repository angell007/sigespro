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
use PhpParser\Node\Stmt\Foreach_;

class GetDispensacion
{
    public $funcionario;
    public $punto;
    public $response;
    private $queryObj;
    public $conditions1;
    public $conditions2;
    public $conditions3;
    public $partialconditions;


    public function __construct()
    {
        $this->funcionario =  (isset($_REQUEST['funcionario']) && $_REQUEST['funcionario'] != '') ? $_REQUEST['funcionario'] : false;
        $this->punto =  (isset($_REQUEST['punto']) && $_REQUEST['punto'] != '') ? $_REQUEST['punto'] : false;

        $this->codigo1 =  (isset($_REQUEST['codigo1']) && $_REQUEST['codigo1'] != '') ? $_REQUEST['codigo1'] : '';
        $this->codigo2 =  (isset($_REQUEST['codigo2']) && $_REQUEST['codigo2'] != '') ? $_REQUEST['codigo2'] : '';
        $this->codigo3 =  (isset($_REQUEST['codigo3']) && $_REQUEST['codigo3'] != '') ? $_REQUEST['codigo3'] : '';

        $this->origen1 =  (isset($_REQUEST['origen1']) && $_REQUEST['origen1'] != '') ? $_REQUEST['origen1'] : '';
        $this->origen2 =  (isset($_REQUEST['origen2']) && $_REQUEST['origen2'] != '') ? $_REQUEST['origen2'] : '';
        $this->origen3 =  (isset($_REQUEST['origen3']) && $_REQUEST['origen3'] != '') ? $_REQUEST['origen3'] : '';

        $this->destino1 =  (isset($_REQUEST['destino1']) && $_REQUEST['destino1'] != '') ? $_REQUEST['destino1'] : '';
        $this->destino2 =  (isset($_REQUEST['destino2']) && $_REQUEST['destino2'] != '') ? $_REQUEST['destino2'] : '';
        $this->destino3 =  (isset($_REQUEST['destino3']) && $_REQUEST['destino3'] != '') ? $_REQUEST['destino3'] : '';

        $this->queryObj = new consulta();
        $this->setConditions($_REQUEST);
        $this->init();
    }

    public function getPoints() {}

    public function init()
    {
        try {
            if (!$this->funcionario) {
                show(myerror('Funcionario no se encuentra'));
            }
            $this->NoAlistadas();
            //$this->disSinProductos();
            $this->Alistadas();
            $this->Preparadas();
            show($this->response);
        } catch (\Throwable $th) {
            show(myerror($th->getMessage()));
        }
    }

    public function NoAlistadas()
    {

        $query = 'SELECT D.*, BN.Nombre As Origen, D.Id_Punto_Dispensacion ,BN.Id_Bodega_Nuevo,
            CASE
                WHEN DD.Tipo_Direccion = "Paciente" THEN 
                (SELECT TRIM(CONCAT_WS(" ", DeP.Nombre, MuN.Nombre, DiD.Descripcion, PaD.Direccion2, PaD.Direccion3, DiDc.Descripcion, PaD.Direccion5))
                FROM Paciente_Direccion AS PaD
                INNER JOIN Direccion_Dian AS DiD ON DiD.Codigo = PaD.Direccion1 
                INNER JOIN Direccion_Dian AS DiDc ON DiDc.Codigo = PaD.Direccion4 
                INNER JOIN Departamento AS DeP ON DeP.Id_Departamento = PaD.Id_Departamento 
                INNER JOIN Municipio AS MuN ON MuN.Id_Municipio = PaD.Id_Municipio
                WHERE PaD.Id_Paciente = P.Id_Paciente
                LIMIT 1) 
                WHEN DD.Tipo_Direccion = "Punto_Dispensacion" THEN (SELECT Direccion FROM Punto_Dispensacion AS PuD WHERE PuD.Id_Punto_dispensacion = DD.ID_Direccion) 
                WHEN DD.Tipo_Direccion = "Bodega_Nuevo" THEN (SELECT Direccion FROM Bodega_Nuevo AS BoN WHERE BoN.Id_Bodega_Nuevo = DD.ID_Direccion) 
                ELSE "Sin Direccion"
                END As Destino
         FROM CustomDis As D
                INNER JOIN Punto_Dispensacion  AS PD ON PD.Id_Punto_Dispensacion = D.Id_Punto_Dispensacion 
                INNER JOIN Bodega_Nuevo  AS BN ON BN.Id_Bodega_Nuevo = PD.Id_Bodega_Despacho 
                INNER JOIN Paciente  AS P ON P.Id_Paciente = D.Numero_documento 
                LEFT JOIN  Dispensacion_Domicilio AS DD ON DD.Id_Dispensacion = D.Id_Dispensacion
                WHERE Date( D.Fecha_Actual)>= "2025-01-01" and D.Id_Punto_Dispensacion IN (' . $this->recoverIdPuntos() . ') 
                AND D.Codigo LIKE "%' . $this->codigo1 . '%"
                ' . $this->conditions1 . '  ';

        $this->queryObj->SetQuery($query);
        $this->queryObj->setTipo('Multiple');
        $this->validarProductos($this->queryObj->getData());
    }

    public function validarProductos($datos)
    {


        $NoAlistadas = [];
        $disSinProductos = [];
        $productos = [];
        $p = [];
        $i = -1;
        foreach ($datos['data'] as $value) {
            $i++;
            $query = 'SELECT * 
                     FROM Producto_Dispensacion PD 
                     WHERE PD.Id_Dispensacion = ' . $value['Id_Dispensacion'];

            $this->queryObj->SetQuery($query);
            $this->queryObj->setTipo('Multiple');

            $productos = $this->queryObj->getData();
            $bandera = true;
            foreach ($productos['data'] as $v) {

                $query = 'SELECT SUM(INU.Cantidad) as Cant
                         FROM Producto P
                         INNER JOIN Inventario_Nuevo INU ON P.Id_Producto = INU.Id_Producto          
                         WHERE P.Id_Producto = ' . $v['Id_Producto'] . " GROUP BY INU.Id_Producto";

                $this->queryObj->SetQuery($query);
                $this->queryObj->setTipo('Multiple');

                $p = $this->queryObj->getData();

                if ($p['data'][$i]['Cant'] < $productos['data'][$i]['Cantidad_Formulada']) {

                    $bandera = false;
                    break;
                }
            }

            if ($bandera) {
                $NoAlistadas[] = $value;
            } else {
                $disSinProductos[] = $value;
            }
        }

        $this->response['NoAlistadas']['data'] = $NoAlistadas;
        $this->response['disSinProductos']['data']  = $disSinProductos;
    }



    public function Alistadas()
    {

        $query = 'SELECT D.*, BN.Nombre As Origen, 
          CASE
                WHEN DD.Tipo_Direccion = "Paciente" THEN 
                (SELECT TRIM(CONCAT_WS(" ", DeP.Nombre, MuN.Nombre, DiD.Descripcion, PaD.Direccion2, PaD.Direccion3, DiDc.Descripcion, PaD.Direccion5))
                FROM Paciente_Direccion AS PaD
                INNER JOIN Direccion_Dian AS DiD ON DiD.Codigo = PaD.Direccion1 
                INNER JOIN Direccion_Dian AS DiDc ON DiDc.Codigo = PaD.Direccion4 
                INNER JOIN Departamento AS DeP ON DeP.Id_Departamento = PaD.Id_Departamento 
                INNER JOIN Municipio AS MuN ON MuN.Id_Municipio = PaD.Id_Municipio
                WHERE PaD.Id_Paciente = P.Id_Paciente
                LIMIT 1) 
                WHEN DD.Tipo_Direccion = "Punto_Dispensacion" THEN (SELECT Direccion FROM Punto_Dispensacion AS PuD WHERE PuD.Id_Punto_dispensacion = DD.ID_Direccion) 
                WHEN DD.Tipo_Direccion = "Bodega_Nuevo" THEN (SELECT Direccion FROM Bodega_Nuevo AS BoN WHERE BoN.Id_Bodega_Nuevo = DD.ID_Direccion) 
                ELSE "Sin Direccion"
                END As Destino
        FROM CustomDisAlistada As D
        INNER JOIN Punto_Dispensacion  AS PD ON PD.Id_Punto_Dispensacion = D.Id_Punto_Dispensacion 
        INNER JOIN Bodega_Nuevo  AS BN ON BN.Id_Bodega_Nuevo = PD.Id_Bodega_Despacho 
        INNER JOIN Paciente  AS P ON P.Id_Paciente = D.Numero_documento
        LEFT JOIN Dispensacion_Domicilio AS DD ON DD.Id_Dispensacion = D.Id_Dispensacion
        WHERE  D.Id_Punto_Dispensacion IN (' . $this->recoverIdPuntos() . ') AND D.Codigo LIKE "%' . $this->codigo2 . '%"
         ' . $this->conditions2 . ' ';
        // echo $query;exit;
        $this->queryObj->SetQuery($query);
        $this->queryObj->setTipo('Multiple');

        $this->response['Alistadas'] = $this->queryObj->getData();
    }

    public function Preparadas()
    {
        $query = 'SELECT D.*, BN.Nombre As Origen, IFNULL(DD.Estado, "Sin estado")  As EstadoDomicilio,
          (CASE
                WHEN DD.Tipo_Direccion = "Paciente" THEN 
                (SELECT TRIM(CONCAT_WS(" ", DeP.Nombre, MuN.Nombre, DiD.Descripcion, PaD.Direccion2, PaD.Direccion3, DiDc.Descripcion, PaD.Direccion5))
                FROM Paciente_Direccion AS PaD
                INNER JOIN Direccion_Dian AS DiD ON DiD.Codigo = PaD.Direccion1 
                INNER JOIN Direccion_Dian AS DiDc ON DiDc.Codigo = PaD.Direccion4 
                INNER JOIN Departamento AS DeP ON DeP.Id_Departamento = PaD.Id_Departamento 
                INNER JOIN Municipio AS MuN ON MuN.Id_Municipio = PaD.Id_Municipio
                WHERE PaD.Id_Paciente = P.Id_Paciente
                LIMIT 1) 
                WHEN DD.Tipo_Direccion = "Punto_Dispensacion" THEN (SELECT Direccion FROM Punto_Dispensacion AS PuD WHERE PuD.Id_Punto_dispensacion = DD.ID_Direccion) 
                WHEN DD.Tipo_Direccion = "Bodega_Nuevo" THEN (SELECT Direccion FROM Bodega_Nuevo AS BoN WHERE BoN.Id_Bodega_Nuevo = DD.ID_Direccion) 
                ELSE "Sin Direccion"
                END) As Destino,
        (SELECT COUNT(*) FROM Producto_Dispensacion PrD WHERE PrD.Id_Producto_Dispensacion = D.Id_Dispensacion) as Items
        FROM CustomDisPreparadas As D
        INNER JOIN Punto_Dispensacion  AS PD ON PD.Id_Punto_Dispensacion = D.Id_Punto_Dispensacion 
        INNER JOIN Bodega_Nuevo  AS BN ON BN.Id_Bodega_Nuevo = PD.Id_Bodega_Despacho 
        INNER JOIN Paciente AS P ON P.Id_Paciente = D.Numero_documento
        LEFT JOIN Dispensacion_Domicilio AS DD ON DD.Id_Dispensacion = D.Id_Dispensacion
        WHERE  D.Id_Punto_Dispensacion IN (' . $this->recoverIdPuntos() . ') AND D.Codigo LIKE "%' . $this->codigo3 . '%"
        ' . $this->conditions3 . ' ';
        // echo $query;
        $this->queryObj->SetQuery($query);
        $this->queryObj->setTipo('Multiple');
        $this->response['Preparadas'] = $this->queryObj->getData();
    }






    public function recoverIdPuntos()
    {
        $subqueryIdBodegas = "SELECT FBN.Id_Bodega_Nuevo FROM Funcionario  As F
                INNER JOIN Funcionario_Bodega_Nuevo As FBN ON F.Identificacion_Funcionario = FBN.Identificacion_Funcionario
                WHERE F.Identificacion_Funcionario = $this->funcionario";
        //echo $subqueryIdBodegas; exit;
        $this->queryObj->setTipo('Multiple');
        $this->queryObj->SetQuery($subqueryIdBodegas);
        // var_dump( $subqueryIdBodegas ); 
        $IDBodegas = $this->destructBodega($this->queryObj->getData());
        $IDBodegas = ltrim($IDBodegas, ',');
        $subqueryIdPuntos = "SELECT PD.Id_Punto_Dispensacion 
                FROM Punto_Dispensacion  As PD
                WHERE Id_Bodega_Despacho IN ($IDBodegas)";
        $this->queryObj->setTipo('Multiple');
        $this->queryObj->SetQuery($subqueryIdPuntos);
        //var_dump( $this->destructDispensacion($this->queryObj->getData()));

        return $this->destructDispensacion($this->queryObj->getData());
    }

    public function destructBodega($data)
    {

        $string = '';

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

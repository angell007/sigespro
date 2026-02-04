<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../vendor/autoload.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta_paginada.php');
include_once('../../helper/response.php');


new  GetDispensacion();

class GetDispensacion
{
    public $dispensacion;
    public $response;
    private $queryObj;

    public function __construct()
    {
        $this->dispensacion =  (isset($_REQUEST['id']) && $_REQUEST['id'] != '') ? json_decode($_REQUEST['id']) : false;
        $this->queryObj = new consulta();
        $this->init();
    }

    public function init()
    {
        try {

            $this->getDispensacion();
        } catch (\Throwable $th) {
            echo json_encode(myerror($th->getMessage()));
        }
    }


    public function getDispensacion()
    {

        $query = 'SELECT D.*, BN.Nombre As Origen, P.Direccion  As Destino FROM CustomDis As D
         INNER JOIN Punto_Dispensacion  AS PD ON PD.Id_Punto_Dispensacion = D.Id_Punto_Dispensacion 
        INNER JOIN Bodega_Nuevo  AS BN ON BN.Id_Bodega_Nuevo = PD.Id_Bodega_Despacho 
        INNER JOIN Paciente  AS P ON P.Id_Paciente = D.Numero_documento
        WHERE  D.Id_Dispensacion =  ' . $this->dispensacion . '
        Limit 10';
        $this->queryObj->SetQuery($query);
        $this->queryObj->setTipo('Multiple');
        echo json_encode($this->queryObj->getData());
    }


    public function destructBodega($data)
    {

        // $string = 0;

        // if ($data['data'] != null ) {
        //             foreach ($data['data'] as $ID) {
        //                 $string .= ',' . $ID['Id_Bodega_Nuevo']  ;
        //             }
        // }

        // return $string;

    }
    public function destructDispensacion($data)
    {

        // $string = 0;

        // if ($data['data'] != null ) {
        //             foreach ($data['data'] as $ID) {
        //                 $string .= ',' . $ID['Id_Punto_Dispensacion']  ;
        //             }
        // }

        // return $string;

    }
}

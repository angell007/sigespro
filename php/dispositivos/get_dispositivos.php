<?php

use PhpParser\Node\Stmt\Echo_;

require_once('../../vendor/autoload.php');

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

include_once('../../class/class.consulta_paginada.php');
require_once('../../helper/response.php');

new  Dispositivo();

use Carbon\Carbon as Carbon;

class Dispositivo
{
    public $request;

    public function __construct()
    {

        $this->request  = (isset($_REQUEST['datos']) && ($_REQUEST['datos'] != '0') ? json_decode($_REQUEST['datos'], true) : '');
        $this->init();

    }

    public function init()
    {
        try {

            $query="SELECT  SQL_CALC_FOUND_ROWS Dispositivo.*, PD.Nombre AS Punto  FROM Dispositivo_Radicacion As Dispositivo
            LEFT JOIN Punto_Dispensacion As PD ON Dispositivo.Id_Punto_Dispensacion = PD.Id_Punto_Dispensacion";
            $oCon=new consulta();
            $oCon->setQuery($query);
            $oCon->setTipo('Multiple');
            $data = $oCon->getData();
            unset($oCon);

            show($data);

        } catch (\Throwable $th) {
            show(myerror($th->getMessage()));
        }

    }
}

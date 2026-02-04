<?php

use PhpParser\Node\Stmt\Echo_;

require_once('../../vendor/autoload.php');


include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');
include_once('../../helper/response.php');
include_once('../../class/class.querybasedatos.php');

new  Dispositivo();

use Carbon\Carbon as Carbon;

class Dispositivo
{
    public $request;
    private $queryObj;

    public function __construct()
    {

        $this->request  = (isset($_REQUEST['datos']) && ($_REQUEST['datos'] != '0') ? json_decode($_REQUEST['datos'], true) : '');
        $this->queryObj = new QueryBaseDatos();
        $this->init();

    }

    public function init()
    {
        $this->saveDispositivo();
    }

        public function saveDispositivo()
        {
            try {

                $oItem = new complex('Dispositivo_Radicacion', 'Id_Dispositivo_Radicacion', isset($this->request['id']) ? $this->request['id'] : 0 );
                $oItem->Nombre = $this->request['Nombre'];
                $oItem->Id_Punto_Dispensacion = $this->request['Id_Punto_Dispensacion'];
                $oItem->Ref_Pantalla = $this->request['Ref_Pantalla'];
                $oItem->Ref_Scanner = $this->request['Ref_Scanner'];
                $oItem->Ref_Raspberry = $this->request['Ref_Raspberry'];
                $oItem->Ref_Impresora = $this->request['Ref_Impresora'];
                $oItem->Ref_Camara = $this->request['Ref_Camara'];
                $oItem->Ref_Parlantes = $this->request['Ref_Parlantes'];
                $oItem->save();
                $lastId =  $oItem->getId();
                unset($oItem);
                show([200, $lastId]);

        } catch (\Throwable $th) {
            show(myerror($th->getMessage()));
        }
     }
}

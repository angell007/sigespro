<?php

use PhpParser\Node\Stmt\Echo_;

require_once('../../vendor/autoload.php');

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

include_once('../../class/class.consulta_paginada.php');
require_once('../../helper/response.php');
require_once('../../helper\makeConditions.php');
require_once('../../helper\customUcWord.php');

new  Search;

use Carbon\Carbon as Carbon;

class Search
{
    
    public $request;
    public $conditions;
    public $makeConditionClass;
    
    
    public function __construct()
    {
        $this->makeConditionClass = new makeConditions();
        $this->modulo  = 'Dispositivo_Radicacion';
        $this->pag  = (isset($_REQUEST['pag']) && ($_REQUEST['pag'] != '0') ? $_REQUEST['pag'] : 1);
        $this->request  = $_REQUEST;
        $this->conditions ='';
        $this->init();
    }

    public function init()
    {
        try {
            $this->nomalizeRequest();
            $this->SetConditions();
            return $this->executeQuery();
        } catch (\Throwable $th) {
            show(myerror($th->getMessage()));
        }
    }

    public function nomalizeRequest()
    {
        try{
            unset($this->request['pag']);
        } catch (\Throwable $th) {
            show(myerror($th->getMessage()));
        }
    }

    public function SetConditions()
    {
        try {
            foreach ($this->request as $key => $param) {
                if ($key != 'estado' && $key != 'punto') {
                    $this->conditions = $this->makeConditionClass->makeConditionLike($param,  $this->modulo . '.' . customUcWord ($key) );
                }
                if ($key == 'estado') {
                    $this->conditions = $this->makeConditionClass->makeConditionEqual($param,  $this->modulo . '.' . customUcWord ($key) );
                }
                if ($key == 'punto') {
                    $this->conditions = $this->makeConditionClass->makeConditionLike($param,  'PD' . '.' . 'Nombre' );
                }
            }
        } catch (\Throwable $th) {
            show(myerror($th->getMessage()));
        }
    }

  

    public function executeQuery()
    {
        
        try {

            $query="SELECT  SQL_CALC_FOUND_ROWS $this->modulo.*, PD.Nombre AS Punto  FROM $this->modulo 
            LEFT JOIN Punto_Dispensacion As PD ON $this->modulo.Id_Punto_Dispensacion = PD.Id_Punto_Dispensacion $this->conditions ";

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

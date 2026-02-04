<?php

use PhpParser\Node\Stmt\Echo_;

require_once('../../vendor/autoload.php');


include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');
include_once('../../class/class.http_response.php');
include_once('../../helper/response.php');
include_once('../../class/class.querybasedatos.php');


new  Document();

use Carbon\Carbon as Carbon;

class Document
{
    public $funcionarioCuenta;
    public $funcionarioDigita;
    public $IdBodega;
    private $queryObj;

    public function __construct()
    {

        $this->funcionarioCuenta  = (isset($_REQUEST['contador']) && ($_REQUEST['contador'] != '0') ? $_REQUEST['contador'] : '');
        $this->funcionarioDigita =  (isset($_REQUEST['digitador']) && ($_REQUEST['digitador'] != '0') ? $_REQUEST['digitador'] : '');
        $this->IdBodega =  (isset($_REQUEST['bodega']) && ($_REQUEST['bodega'] != '0') ? $_REQUEST['bodega'] : '');
        $this->queryObj = new QueryBaseDatos();
        $this->init();

    }

    public function init()
    {
        try {
            if ($this->validateFuncionarioCuenta() && $this->validateFuncionarioDigita() ) {
                $oItem = new complex('Doc_Inventario_Auditable', 'Doc_Inventario_Auditable_Id');
                $oItem->Id_Bodega = $this->IdBodega;
                $oItem->Fecha_Inicio = Carbon::now()->format('Y-m-d H:m');
                $oItem->Estado = 'Haciendo Primer Conteo';
                $oItem->Funcionario_Cuenta = $this->funcionarioCuenta;
                $oItem->Funcionario_Digita = $this->funcionarioDigita;
                $oItem->save();
                $lastId =  $oItem->getId();
                unset($oItem);
                $this->changeStatus();
                show([200, $lastId]);
            }
            show([400]);
        } catch (\Throwable $th) {
            show(myerror($th->getMessage()));
        }

    }

    public function validateFuncionarioCuenta()
    {
        try {
           
        $query = "SELECT F.Identificacion_Funcionario
        FROM Funcionario F
        WHERE F.Identificacion_Funcionario = $this->funcionarioCuenta";
        $this->queryObj->SetQuery($query);
        return  $this->queryObj->ExecuteQuery('simple');

        } catch (\Throwable $th) {
            show(myerror($th->getMessage()));
        }

    }
    public function validateFuncionarioDigita ()
    {
        try {
           
            $query = "SELECT F.Identificacion_Funcionario
            FROM Funcionario F
            WHERE F.Identificacion_Funcionario = $this->funcionarioDigita";
            $this->queryObj->SetQuery($query);
            return  $this->queryObj->ExecuteQuery('simple');

        } catch (\Throwable $th) {
            show(myerror($th->getMessage()));
        }

    }
    public function changeStatus()
    {
        try {


            
            $query = "SELECT  E.Id_Estiba FROM Bodega_Nuevo As BN
            INNER JOIN Estiba As E ON E.Id_Bodega_Nuevo = BN.Id_Bodega_Nuevo
            WHERE  BN.Id_Bodega_Nuevo = $this->IdBodega AND  E.Estado = 'Disponible'";
            $this->queryObj->SetQuery($query);

            foreach ($this->queryObj->ExecuteQuery('Multiple') as $myEstiba) {

            $query = "UPDATE  Estiba As Es  SET Es.Estado = 'Inventario' WHERE  Es.Id_Estiba = $myEstiba[Id_Estiba] ";
            $this->queryObj->SetQuery($query);
            $this->queryObj->QueryUpdate();

            }

            return ;

            } catch (\Throwable $th) {
                show(myerror($th->getMessage()));
            }
    }
}

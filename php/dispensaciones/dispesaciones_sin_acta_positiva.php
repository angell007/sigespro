<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

include_once('../../class/class.http_response.php');
include_once('../../class/class.utility.php');
include_once('../../helper/response.php');
include_once('../../class/class.consulta_paginada.php');

new DispensacionActa();

class DispensacionActa
{  
    public $dispensaciones;
    public $response;
    private $queryObj;
    public $cond;
    public $condicion;
    public $condiciones;
    public $pag;
    public $tam;
    public $condiconflag;

    public function __construct()
    {    
        $this->cond =  (isset($_REQUEST['cond']) && $_REQUEST['cond'] != '') ? $_REQUEST['cond'] : '';
        $this->condicion =  $this->cond ? 'and not exists' : 'and exists';
        $this->pag = ( isset( $_REQUEST['pag'] ) ? $_REQUEST['pag'] : '' );
        $this->tam = ( isset( $_REQUEST['tam'] ) ? $_REQUEST['tam'] : '' ); 

        $this->condiconflag = ( isset( $_REQUEST['flag'] ) ? 'AND (D.Firma_Reclamante IS NOT NULL OR D.Acta_Entrega IS NOT NULL)' : 'AND D.Firma_Reclamante IS NULL AND D.Acta_Entrega IS NULL' );  
        
/*         $this->queryObj = new consulta(); */
         $this->condiciones = $this->SetCondiciones($_REQUEST);
        if (!isset($_REQUEST['pag']) || $_REQUEST['pag'] == '') { 
            $this->pag  = 0; 
            $this->tam = 10; 
        } else { 
            $this->pag  = $_REQUEST['pag']; 
            $this->pag = $this->pag -1;
        } 
        $this->init();

        // show($this->response);
    }
    
    function init()
    {
        try {
            
            $this->GetDis();
        
        } catch (\Throwable $th) {
            //throw $th;
        }
    }

    function GetDis(){
        
        try {
            
            $query='SELECT SQL_CALC_FOUND_ROWS  D.Estado_Dispensacion, 
                           D.Id_Dispensacion,       
                           D.Codigo,
                           A.Id_Auditoria,
                           DATE_FORMAT(D.Fecha_Actual, "%d/%m/%Y") AS Fecha_Dis,
                           D.Estado_Dispensacion AS Estado,
                           D.Estado_Facturacion,
                           D.Numero_Documento,
                           D.Estado_Auditoria,
                           D.Pendientes,
                           CONCAT_WS(" ",Primer_Nombre, Segundo_Nombre, Primer_Apellido,Segundo_Apellido) AS Paciente,
                           P.Nombre AS Punto_Dispensacion,
                           L.Nombre AS Departamento
                    FROM Dispensacion D
                    INNER JOIN Auditoria A on D.Id_Dispensacion = A.Id_Dispensacion
                    INNER JOIN Paciente PC on D.Numero_Documento=PC.Id_Paciente
                    INNER JOIN Punto_Dispensacion P on D.Id_Punto_Dispensacion=P.Id_Punto_Dispensacion
                    INNER JOIN Departamento L ON P.Departamento = L.Id_Departamento
                    WHERE D.EPS = "Positiva"  '. $this->condiconflag .'
                    '. $this->condiciones .'
                    ORDER BY Id_Dispensacion DESC
                     LIMIT '.$this->pag.','.$this->tam;

                    //  echo $query; exit;
            $oCon= new consulta();
            $oCon->setQuery($query);
            $oCon->setTipo('Multiple');
            $res= $oCon->getData();
            unset($oCon);
                
            $resultado["Dispensaciones"] = $res['data'];
            $resultado["codigo"] = 'success';
            $resultado['numReg'] = $res['total'];

            echo json_encode($resultado);

        }catch (\Throwable $th) {
            // throw $th;
            $status = 400;
            $this->response = [
                'message' => 'Ha ocurrido un error' . $th->getMessage() .
                ' Line  ' .  $th->getLine() .
                ' file  ' . $th->getFile(),

                "success" => false,
                "httpResponseCode" => 400,
            ];
        }   
    }

    public function SetCondiciones($req){     
        
        global $util;
        $condicion1 = '';

        if (isset($req['cod']) && $req['cod']) {
            if ($condicion1 == "") {
                $condicion1 .= " AND D.Codigo LIKE '%".$req['cod']."%'";
            } else {
                $condicion1 = '';
            }
        }  
    
        if (isset($req['pers']) && $req['pers']!='') {
            if ($condicion1 == '') {
               $condicion1 .= " AND P.Primer_Nombre LIKE '%".$req['pers']."%'";
            }else{
                $condicion1 = '';
            }           
        }

        if (isset($req['punto']) && $req['punto']) {
            if ($condicion1 == "") {
                $condicion1 .= " AND P.Nombre LIKE '%".$req['punto']."%'";
            } else {
                $condicion1 = '';
            }
        } 
        if (isset($req['dep']) && $req['dep']) {
            if ($condicion1 == "") {
                $condicion1 .= " AND L.Id_Departamento = ".$req['dep'];
            } else {
                $condicion1 = '';
            }
        }   
        if (isset($_REQUEST['fecha']) && $_REQUEST['fecha'] != "") {
            $fecha_inicio = trim(explode(' - ', $_REQUEST['fecha'])[0]);
            $fecha_fin = trim(explode(' - ', $_REQUEST['fecha'])[1]);
            
            $condicion1 .= "AND  DATE_FORMAT(D.Fecha_Actual,'%Y-%m-%d') BETWEEN '$fecha_inicio' AND '$fecha_fin'";
        }else{
            $fecha_fin=date('Y-m-d');
            $fecha_inicio=date('Y-m-d');
        }


        return $condicion1;
    }


}




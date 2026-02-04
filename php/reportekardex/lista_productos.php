<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');
include_once('../../helper/response.php');
include_once('../../helper/replaceInventario.php');

new InventarioByKardex;

class InventarioByKardex {
    
    public $nombre;
    public $labComercial;
    public $codigoCum;
    public $labGenerico;
    public $conditions;
    public $tipo;
    
    
    function __construct() {
        
        $this->nombre =  $_REQUEST['nom'];
        $this->labComercial =  $_REQUEST['lab_com'];
        $this->labGenerico =  $_REQUEST['lab_gen'];
        $this->codigoCum =  $_REQUEST['cum'];
        $this->tipo = $_REQUEST['tipo'];
        $this->params = ['nom','lab_com','lab_gen','cum'];
        
            $this->init();
    }

    public function init()
    {
       $this->setConditions();
       $this->executeQuery();
    }

    public function setConditions()
    {
        try {
                    foreach ($this->params as $key => $param) {

                        if ($key == 'nom') {
                                $this->conditions .= "WHERE (P.Principio_Activo LIKE '%$this->nombre%' OR P.Presentacion LIKE 
                                '%$this->nombre%' OR P.Concentracion LIKE '%$this->nombre%' OR P.Nombre_Comercial LIKE
                                '%$this->nombre%' OR P.Cantidad LIKE '%$this->nombre%' OR P.Unidad_Medida LIKE 
                                '%$this->nombre%')";
                        }

                        if ( $key == '' && isset($this->labComercial) && $this->labComercial) {
                            $this->conditions .= " AND P.Laboratorio_Comercial LIKE '%$this->labComercial%'";
                        }
                        if ( $key == '' && isset($this->labGenerico) && $this->labGenerico) {
                            $this->conditions .= " AND P.Laboratorio_Generico LIKE '%$this->labGenerico%'";
                        }
                        if ( $key == '' && isset($this->codigoCum) && $this->codigoCum) {
                            $this->conditions .= " AND P.Codigo_Cum LIKE '%$this->codigoCum%'";
                        }

                    }

                } catch (\Throwable $th) {
                    show(myerror($th->getMessage()));
                }

    }
    public function executeQuery()
    {
        try {


            $query = "SELECT P.Id_Producto, P.Nombre_Comercial, P.Codigo_Cum,

            CONCAT( P.Principio_Activo, ' ',
                    P.Presentacion, ' ',
                    P.Concentracion,
                    P.Cantidad,' ',
                    P.Unidad_Medida) as Nombre_Producto,
                    P.Laboratorio_Comercial,
                    P.Laboratorio_Generico,
                    P.Embalaje,
                    InN.Lote, 
                    InN.Fecha_Vencimiento, 
                    InN.Cantidad AS Cantidad_Disponible
                    FROM  Inventario_Nuevo As  InN
                    INNER JOIN Estiba As E ON E.Id_Estiba =  InN.Id_Estiba
                    INNER JOIN Producto P 
                    ON P.Id_Producto=InN.Id_Producto $this->conditions  GROUP BY InN.Id_Producto ORDER BY P.Nombre_Comercial ASC";
    
                    $oCon= new consulta();
                    $oCon->setQuery($query);
                    $oCon->setTipo('Multiple');
                    $resultados = $oCon->getData();
                    unset($oCon);
                
                    show($resultados);
                    

                } catch (\Throwable $th) {
                    show(myerror($th->getMessage()));
                }

    }

}



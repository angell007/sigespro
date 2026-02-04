<?php

use PhpParser\Node\Stmt\Echo_;

require_once('../../vendor/autoload.php');


include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');
include_once('../../class/class.http_response.php');
include_once('../../helper/response.php');

new  ConteoUno();

use Carbon\Carbon as Carbon;

class ConteoUno
{
    public $datos;

    public function __construct()
    {
        $this->documento  = (isset($_REQUEST['inv']) && ($_REQUEST['inv'] != '') ? json_decode($_REQUEST['inv']) : '');
        $this->init();
    }

    public function init()
    {
        try {

            $resultado['tipo']="success";
            $resultado['Productos']=$this->getProductosDif();
            $resultado['Productos_Sin_Diferencia']=$this->getProductosIquals();
            $resultado['Inventarios']= $this->documento;
            $resultado['Estado']= 'Primer Conteo';

            show($resultado);

        } catch (\Throwable $th) {
            show(myerror($th->getMessage()));
        }
    }

    

    public function getProductosIquals()
    {

        try {
            $query = "SELECT PDA.Lote,  PDA.Fecha_Vencimiento, PDA.Cantidad_Inventario, PDA.Primer_Conteo As Cantidad_Encontrada, P.Nombre_Comercial, E.Nombre As Estiba,
            
            CONCAT(
            IFNULL(P.Principio_Activo, '  '), ' ',
            P.Presentacion,' ',
            IFNULL(P.Concentracion, '  '), ' ',
            P.Cantidad,' ', 
            P.Unidad_Medida,  ' LAB: ', 
            P.Laboratorio_Comercial
            ) AS Nombre_Producto

            FROM Producto_Doc_Inventario_Auditable  As  PDA
            INNER JOIN Producto As P ON  P.Id_Producto = PDA.Id_Producto
            INNER JOIN Estiba As E ON  E.Id_Estiba = PDA.Id_Estiba
            WHERE Id_Doc_Inventario_Auditable = $this->documento AND Primer_Conteo = Cantidad_Inventario ORDER BY Estiba ASC, P.Nombre_Comercial ASC ";
            $oCon= new consulta();
            $oCon->setTipo('Multiple');
            $oCon->setQuery($query);
            $productos_inventario = $oCon->getData();
            unset($oCon);
        
            return $productos_inventario;

        } catch (\Throwable $th) {
            show(myerror($th->getMessage()));
        }
    }

    public function getProductosDif()
    {
        try {
            $query = "SELECT PDA.Lote,  
            PDA.Fecha_Vencimiento, PDA.Cantidad_Inventario, PDA.Primer_Conteo As Cantidad_Encontrada, PDA.Id_Producto,
            PDA.Id_Producto_Doc_Inventario_Auditable,
             P.Nombre_Comercial, E.Nombre As Estiba,  

             CONCAT(
            IFNULL(P.Principio_Activo, '  '), ' ',
            P.Presentacion,' ',
            IFNULL(P.Concentracion, '  '), ' ',
            P.Cantidad,' ', 
            P.Unidad_Medida,  ' LAB: ', 
            P.Laboratorio_Comercial
            ) AS Nombre_Producto,

            (CASE WHEN (PDA.Primer_Conteo) < (PDA.Cantidad_Inventario) 
            THEN CONCAT('', PDA.Primer_Conteo - PDA.Cantidad_Inventario)
            WHEN (PDA.Primer_Conteo) > (PDA.Cantidad_Inventario) 
            THEN CONCAT('+', PDA.Primer_Conteo - PDA.Cantidad_Inventario) 
            END )

            AS Cantidad_Diferencial

            FROM Producto_Doc_Inventario_Auditable  As  PDA
            INNER JOIN Producto As P ON  P.Id_Producto = PDA.Id_Producto
            INNER JOIN Estiba As E ON  E.Id_Estiba = PDA.Id_Estiba
            WHERE Id_Doc_Inventario_Auditable = $this->documento AND Primer_Conteo <> Cantidad_Inventario ORDER BY Estiba ASC, P.Nombre_Comercial ASC";
            $oCon= new consulta();
            $oCon->setTipo('Multiple');
            $oCon->setQuery($query);
            $productos_inventario = $oCon->getData();
            unset($oCon);
        
            return $productos_inventario;

        } catch (\Throwable $th) {
            show(myerror($th->getMessage()));
        }
    }

    
}

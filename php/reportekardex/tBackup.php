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

new ConsultaKardex;

class ConsultaKardex {

    public $condicion = '';
    public $condicion2=''; 
    public $condicion3=''; 
    public $condicion4=''; 
    public $condicion5=''; 
    public $condicion6=''; 
    public $tipo = '';
    public $idTipo = '';
    public $producto = '';
    public $ruta = '';
    public $tabla = '';
    public $tablaDest = '';
    public $attrFecha = '';
    public $query_dispensaciones = '';
    public $query_notas_creditos = '';
    public $query_devoluciones_compras = '';
    public $query_actas_internacionales = '';
    public $fecha_inicio = '';
    public $fecha_fin = '';
    public $sql_acta_recepcion_bodegas = '';
    public  $acum = '';
    public $total = '';

    public $sqlActaRecepcionBodegas = '' ;
    public $queryNotasCreditos = '';
    public $queryDevolucionesCompras ='';
    public $queryActasInternacionales = '';


    function __construct() {
        
    $this->tipo = $_REQUEST['tipo'];
    $this->idTipo = $_REQUEST['idtipo'];
    $this->producto = $_REQUEST['producto'];
    $this->fecha_inicio = $_REQUEST['producto'];

    if (isset($_REQUEST['fecha_inicio']) && $_REQUEST['fecha_inicio'] != "") {
            $this->fecha_inicio = $_REQUEST['fecha_inicio']."-01"; 
    }
    if (isset($_REQUEST['fecha_fin']) && $_REQUEST['fecha_fin'] != "") {
            $this->fecha_fin = $_REQUEST['fecha_fin'];
    }

        $this->replace = replaceInventario();
        $this->init();

    }

    public function init()
    {
        if ($this->tipo ==  'Bodega') {
                show($this->createStatementByBodega());
            }else{
                show($this->createStatementByOther());
        }
    }

public function createStatementByBodega()
{
try {

            $condicion = "AND AR.Id_Bodega_Nuevo=  $this->idTipo";
            $this->sqlActaRecepcionBodegas =  sqlActaRecepcionBodegas($this->tipo, 'Bodega_Nuevo', $this->idTipo,$this->producto, $condicion, $this->fecha_inicio, $this->fecha_fin );
            $this->queryNotasCreditos = queryNotasCreditos($this->idTipo,$this->producto, $this->fecha_inicio, $this->fecha_fin );

            $condicion = "AND Id_Bodega_Nuevo=$this->idTipo";
            $this->queryDevolucionesCompras = queryDevolucionesCompras($condicion, $this->producto, $condicion, $this->fecha_inicio, $this->fecha_fin );
            $this->queryActasInternacionales = queryActasInternacionales($condicion, $this->producto, $condicion, $this->fecha_inicio, $this->fecha_fin );

        } catch (\Throwable $th) {
            show(myerror($th->getMessage()));
        }
}

public function createStatementByOther()
{
    try {
    
            $this->condicion .= " AND R.Id_Origen=$this->idTipo AND R.Tipo_Origen='Punto_Dispensacion'";
            $this->condicion3 .= " AND AI.Id_Origen_Destino=$this->idTipo AND AI.Origen_Destino='Punto'";
            $this->condicion2 .= " AND AR.Id_Punto_Dispensacion=$this->idTipo";
            $this->condicion4 .= " AND INF.Bodega=''";
            $this->condicion5 .= " AND Id_Punto_Dispensacion=$this->idTipo";

            $ruta = 'actarecepcionremisionver';
            $tabla = 'Acta_Recepcion_Remision';
            $tablaDest = 'Punto_Dispensacion'; 
            $attrFecha = 'Fecha';

            $this->recoverIdProductsInv($this->checkPIF());

            $condicion = "AND AR.Id_Bodega_Nuevo=  $this->idTipo";

            $this->sqlActaRecepcionBodegas = sqlActaRecepcionBodegas(null, 'Bodega_Nuevo', $this->idTipo,$this->producto, $condicion, $this->fecha_inicio, $this->fecha_fin );
            
            $this->condicion .= " AND R.Fecha BETWEEN ' $this->fecha_inicio 00:00:00' AND ' $this->fecha_fin 23:59:59'";
            $this->condicion2.= " AND AR.$attrFecha BETWEEN ' $this->fecha_inicio 00:00:00' AND ' $this->fecha_fin 23:59:59'";
            
           $this->acumulado();

           return  $this->recoverFinal($this->sqlActaRecepcionBodegas);

            } catch (\Throwable $th) {
                show(myerror($th->getMessage()));
            }
   }


   public function recoverIdProductsInv($productoInventarioFisico)
   {
       try {
           $ids_inv = '';
           if (count($productoInventarioFisico) > 0) {
               foreach ($productoInventarioFisico as $value) {
                 $query_invs = 'SELECT 
                               GROUP_CONCAT(INF.Id_Inventario_Fisico_Punto) AS Ids
                               FROM Inventario_Fisico_Punto INF
                               WHERE 
                               INF.Estado="Terminado"
                               AND INF.Id_Punto_Dispensacion = ' . $this->idTipo 
                               . ' AND INF.Fecha_Fin = "'.$value["Fecha_Fin"].'"';
         
                               $oCon= new consulta();
                               $oCon->setQuery($query_invs);
                               $result = $oCon->getData(); 
                               $ids_inv .= $result['Ids'].",";
                                unset($oCon);
               }       
         
            $ids_inv = trim($ids_inv, ",");

             }else{
               $ids_inv = '0';
             }
                return  $ids_inv;
               } catch (\Throwable $th) {
                   show(myerror($th->getMessage()));
               }
      }

      public function acumulado ()
      {

       $ultimo_dia_mes = date("Y-m-d",(mktime(0,0,0,date("m",strtotime($this->fecha_inicio)),1,date("Y",strtotime($this->fecha_inicio)))-1));
       $query_inicial = 'SELECT SUM(Cantidad) as Total
       FROM Saldo_Inicial_Kardex 
       WHERE Id_Producto = '.$this->producto.' AND Fecha="'.$ultimo_dia_mes.'" '.$this->condicion5.' GROUP BY Id_Producto';
       $oCon= new consulta();
       $oCon->setQuery($query_inicial);
       $res = $oCon->getData();
       unset($oCon);
       $this->acum=$this->total=(INT)$res["Total"];

      }

      
      public function recoverFinal($sqlActaRecepcionBodegas)
      {

               $ids_inv=  $this->recoverIdProductsInv($this->checkPIF());

               $condicion   = " AND R.Fecha BETWEEN '$this->fecha_inicio 00:00:00' AND '$this->fecha_fin 23:59:59'";
               $condicion3 = " AND AI.Id_Origen_Destino=$this->idTipo AND AI.Origen_Destino='Punto'";
               $condicion2 = " AND AR.Id_Punto_Dispensacion=$this->idTipo";
               $condicion4 = " AND INF.Bodega=''";
               $query_dispensaciones = sqlDispensaciones('', '', $this->idTipo,  $ids_inv,  $this->producto, $this->fecha_inicio,  $this->fecha_fin );

               $this->queryNotasCreditos = queryNotasCreditos($this->idTipo, $this->producto, $this->fecha_inicio,  $this->fecha_fin );
               $this->queryDevolucionesCompras =  queryDevolucionesCompras($condicion, $this->producto,$this->fecha_inicio, $this->fecha_fin );
               $this->queryActasInternacionales =  queryActasInternacionales($condicion, $this->producto, $this->fecha_inicio, $this->fecha_fin );

               $ruta = 'actarecepcionremisionver';
               $tabla = 'Acta_Recepcion_Remision';
               $this->tablaDest = 'Punto_Dispensacion'; 
               $attrFecha = 'Fecha';

               $params = [

               'condicion' => $condicion,  'producto' => $this->producto,  'tablaDest' => $this->tablaDest,  'fecha_inicio' => $this->fecha_inicio,  'idTipo' => $this->idTipo,
               'fecha_fin' => $this->fecha_fin,  'condicion3' => $condicion3,  'condicion2' => $condicion2,  'tabla' =>$tabla,  'sqlActaRecepcionBodegas' => $sqlActaRecepcionBodegas,  'ruta' => $ruta,   'attrFecha' =>$attrFecha, 
               'query_dispensaciones' => $query_dispensaciones,  'query_notas_creditos' =>  $this->queryNotasCreditos ,  'query_devoluciones_compras' => $this->queryDevolucionesCompras,  'query_actas_internacionales' => $this->queryActasInternacionales,
               $condicion4

               ];


       $oCon= new consulta();
       $oCon->setQuery( resultData($params));
       $oCon->setTipo('Multiple');
       $resultados = $oCon->getData();
       unset($oCon);
       
       $saldo_actual = getSaldoActualProducto($this->tipo);
       
       $i=-1;

       foreach($resultados as $res){ $i++;

           if($res["Tipo"]=='Entrada'){

               $this->acum+=$res["Cantidad"];

           }elseif ($res["Tipo"]=='Salida'){

               $this->acum-=$res["Cantidad"];

           } elseif ($res["Tipo"]=='Inventario') {

               $fecha_ant = date('Y-m-d',strtotime($resultados[$i-1]['Fecha']));

               $fecha_act = date('Y-m-d',strtotime($res['Fecha']));

               if ($resultados[$i-1]["Tipo"] != "Inventario" || ($resultados[$i-1]["Tipo"] == "Inventario" && $fecha_ant != $fecha_act)) {
                   $this->acum = $res["Cantidad"];
               } else {
                   $this->acum = $this->acum + $res["Cantidad"];
               }
               
           }

           $resultados[$i]["Saldo"]=$this->acum;

       }
       
       $final["Productos"]=$resultados;
       $final["Inicial"]=$this->total;
       $final["Saldo_Actual"] = $saldo_actual;
       
       show($final);

      }
}


<?php
include_once('class.consulta.php');

#LA CLASE CALCULA AUTOMATICAMENTE TODO EL COSTO PROMEDIO, SI SE QUIERE ACTUALIZAR EN LA BASE DE DATOS
# USAR LA FUNCION  "actualizarCostoPromedio"
class Costo_Promedio {
    public $existencia_actual; //ok
    private $existencia_nueva; //OK
    private $costo_actual;//OK
    private $valor_nueva_entrada;   //OK
    private $id_producto; //OK
    private $valor_inventario; // Valor total actual de mi producto (Costo Actual x Existencia Actual)
    private $costo_promedio;
    private $costo_ingresado;
    private $bodega_activa_cache = [];
    private $punto_activo_cache = [];
    private $estiba_cache = [];

    function __construct($id_producto,$nueva_cantidad,$nuevo_costo)
    {
        $this->costo_ingresado = $nuevo_costo;
        $this->id_producto = $id_producto;
        $this->existencia_nueva = (int)$nueva_cantidad;
        $this->valor_nueva_entrada = ((float)$nuevo_costo * (int)$nueva_cantidad);
        $this->existencia_actual = $this->buscarExistenciaActual();
        $this->costo_actual = $this->buscarCostoActual();

        $this->valor_inventario = $this->valorEnInventario();

        $this->costo_promedio = $this->calcularCostoPromedio();

    }
    private static function obtenerCostoActa2025($id_producto){
        $oCon=new consulta();
        $query='SELECT PAR.Precio
            FROM Producto_Acta_Recepcion PAR
            INNER JOIN Acta_Recepcion AR ON AR.Id_Acta_Recepcion = PAR.Id_Acta_Recepcion
            LEFT JOIN Punto_Dispensacion PD ON PD.Id_Punto_Dispensacion = AR.Id_Punto_Dispensacion
            WHERE PAR.Id_Producto = '.$id_producto.'
            AND PAR.Precio > 0
            AND YEAR(AR.Fecha_Creacion) = 2025
            AND (AR.Id_Punto_Dispensacion IS NULL OR AR.Id_Punto_Dispensacion = 0 OR PD.Estado <> "Inactivo")
            ORDER BY AR.Fecha_Creacion DESC, AR.Id_Acta_Recepcion DESC
            LIMIT 1';

        $oCon->setQuery($query);
        $data=$oCon->getData();
        unset($oCon);

        if (!$data || !isset($data['Precio'])) {
            return null;
        }

        return (float)$data['Precio'];
    }

    private static function actualizarCostoInventarioNuevo($id_producto, $costo){
        $oCon=new consulta();
        $query='UPDATE Inventario_Nuevo SET Costo = '.number_format($costo, 2, '.', '').' WHERE Id_Producto = '.$id_producto;
        $oCon->setQuery($query);
        $oCon->createData();
        unset($oCon);
    }

    public static function actualizarCostoPromedioDesdeActa2025($id_producto, $cantidad_nueva, $existencia_actual, $costo_actual, $fecha_actualizacion = null){
        $costo_acta = self::obtenerCostoActa2025($id_producto);
        if ($costo_acta === null) {
            return false;
        }

        $existencia_actual = (int)$existencia_actual;
        $cantidad_nueva = (int)$cantidad_nueva;
        $costo_actual_num = $costo_actual !== null ? (float)$costo_actual : 0.0;

        if ($costo_actual === null || $costo_actual_num < 1 || $existencia_actual <= 0) {
            $costo_promedio = $costo_acta;
        } else {
            $valor_inventario = $costo_actual_num * $existencia_actual;
            $valor_nueva_entrada = $costo_acta * $cantidad_nueva;
            $total_unidades = $existencia_actual + $cantidad_nueva;
            $costo_promedio = $total_unidades > 0 ? ($valor_inventario + $valor_nueva_entrada) / $total_unidades : $costo_acta;
        }

        $fecha_sql = $fecha_actualizacion ? "'".$fecha_actualizacion."'" : 'NOW()';
        $costo_sql = number_format($costo_promedio, 2, '.', '');

        if ($costo_actual === null) {
            $oCon=new consulta();
            $query='INSERT INTO Costo_Promedio (Id_Producto, Costo_Promedio, Costo_Anterior, Ultima_Actualizacion)
                VALUES ('.$id_producto.', '.$costo_sql.', 0, '.$fecha_sql.')';
            $oCon->setQuery($query);
            $oCon->createData();
            unset($oCon);
        } else {
            $oCon=new consulta();
            $query='UPDATE Costo_Promedio SET Costo_Anterior = Costo_Promedio, Ultima_Actualizacion = '.$fecha_sql.', Costo_Promedio = '.$costo_sql.'
                WHERE Id_Producto = '.$id_producto;
            $oCon->setQuery($query);
            $oCon->createData();
            unset($oCon);
        }

        self::actualizarCostoInventarioNuevo($id_producto, $costo_promedio);
        return true;
    }
    private function calcularCostoPromedio(){
      
        $costo = ($this->valor_inventario + $this->valor_nueva_entrada) / ($this->existencia_actual + $this->existencia_nueva);
       return $costo;

    }

    function valorEnInventario(){
        return (float)$this->costo_actual * $this->existencia_actual;

    }
    private function buscarExistenciaActual(){
        $oCon=new consulta();
        $query='SELECT Id_Bodega, Id_Punto_Dispensacion, Id_Estiba, Cantidad, Cantidad_Apartada, Cantidad_Seleccionada
            FROM Inventario_Nuevo WHERE Id_Producto = '.$this->id_producto;
        $oCon->setQuery($query);
        $oCon->setTipo('Multiple');
        $inventario = $oCon->getData();
        unset($oCon);

        if (!is_array($inventario) || count($inventario) === 0) {
            return 0;
        }

        $cantidad_total = 0;
        foreach ($inventario as $row) {
            $cantidad_disponible = (int)$row['Cantidad'] - ((int)$row['Cantidad_Apartada'] + (int)$row['Cantidad_Seleccionada']);
            if ($cantidad_disponible <= 0) {
                continue;
            }
            if ($this->inventarioTieneUbicacionActiva($row)) {
                $cantidad_total += $cantidad_disponible;
            }
        }

        return (int)$cantidad_total;
    }

    private function inventarioTieneUbicacionActiva($row){
        $id_bodega = isset($row['Id_Bodega']) ? (int)$row['Id_Bodega'] : 0;
        if ($id_bodega > 0) {
            return $this->isBodegaActiva($id_bodega);
        }

        $id_punto = isset($row['Id_Punto_Dispensacion']) ? (int)$row['Id_Punto_Dispensacion'] : 0;
        if ($id_punto > 0) {
            return $this->isPuntoActivo($id_punto);
        }

        $id_estiba = isset($row['Id_Estiba']) ? (int)$row['Id_Estiba'] : 0;
        if ($id_estiba <= 0) {
            return false;
        }

        $estiba = $this->getEstibaInfo($id_estiba);
        if (!$estiba) {
            return false;
        }

        $id_bodega_nuevo = isset($estiba['Id_Bodega_Nuevo']) ? (int)$estiba['Id_Bodega_Nuevo'] : 0;
        if ($id_bodega_nuevo > 0) {
            return $this->isBodegaActiva($id_bodega_nuevo);
        }

        $id_punto_estiba = isset($estiba['Id_Punto_Dispensacion']) ? (int)$estiba['Id_Punto_Dispensacion'] : 0;
        if ($id_punto_estiba > 0) {
            return $this->isPuntoActivo($id_punto_estiba);
        }

        return false;
    }

    private function isBodegaActiva($id_bodega){
        $id_bodega = (int)$id_bodega;
        if ($id_bodega <= 0) {
            return false;
        }
        if (array_key_exists($id_bodega, $this->bodega_activa_cache)) {
            return $this->bodega_activa_cache[$id_bodega];
        }

        $oCon = new consulta();
        $query = 'SELECT Estado FROM Bodega_Nuevo WHERE Id_Bodega_Nuevo = '.$id_bodega;
        $oCon->setQuery($query);
        $data = $oCon->getData();
        unset($oCon);

        $estado = isset($data['Estado']) ? strtolower(trim($data['Estado'])) : '';
        $activo = ($estado === 'activo');
        $this->bodega_activa_cache[$id_bodega] = $activo;
        return $activo;
    }

    private function isPuntoActivo($id_punto){
        $id_punto = (int)$id_punto;
        if ($id_punto <= 0) {
            return false;
        }
        if (array_key_exists($id_punto, $this->punto_activo_cache)) {
            return $this->punto_activo_cache[$id_punto];
        }

        $oCon = new consulta();
        $query = 'SELECT Estado FROM Punto_Dispensacion WHERE Id_Punto_Dispensacion = '.$id_punto;
        $oCon->setQuery($query);
        $data = $oCon->getData();
        unset($oCon);

        $estado = isset($data['Estado']) ? strtolower(trim($data['Estado'])) : '';
        $activo = ($estado === 'activo');
        $this->punto_activo_cache[$id_punto] = $activo;
        return $activo;
    }

    private function getEstibaInfo($id_estiba){
        $id_estiba = (int)$id_estiba;
        if ($id_estiba <= 0) {
            return null;
        }
        if (array_key_exists($id_estiba, $this->estiba_cache)) {
            return $this->estiba_cache[$id_estiba];
        }

        $oCon = new consulta();
        $query = 'SELECT Id_Estiba, Id_Bodega_Nuevo, Id_Punto_Dispensacion FROM Estiba WHERE Id_Estiba = '.$id_estiba;
        $oCon->setQuery($query);
        $data = $oCon->getData();
        unset($oCon);

        $estiba = is_array($data) && !empty($data) ? $data : null;
        $this->estiba_cache[$id_estiba] = $estiba;
        return $estiba;
    }

    private function buscarCostoActual(){
        $oCon=new consulta();
        $query="SELECT Costo_Promedio FROM Costo_Promedio WHERE Id_Producto = ".$this->id_producto;
        $oCon->setQuery($query);
       
        $costo_actual=$oCon->getData();
        
       
        unset($oCon);
        return $costo_actual['Costo_Promedio'];
    }

    function getCostoPromedio(){

        return $this->costo_promedio;
    }

    function actualizarCostoPromedio(){
        #actualiza el costo en la base de datos
        $costo_acta = self::obtenerCostoActa2025($this->id_producto);
        if ($costo_acta !== null) {
            self::actualizarCostoPromedioDesdeActa2025(
                $this->id_producto,
                $this->existencia_nueva,
                $this->existencia_actual,
                $this->costo_actual
            );
            return;
        }

        if($this->costo_actual == null){
           $oCon=new consulta();
            $query='INSERT INTO  Costo_Promedio (Id_Producto, Costo_Promedio) VALUES ('.$this->id_producto.', '. number_format($this->costo_ingresado, 2, '.', '') . ')';
             
            $oCon->setQuery($query);
            $oCon->createData();
            unset($oCon);
        }else{
            $costo = 0;
            if((float)$this->costo_actual >= 1){
                $costo = $this->costo_promedio;
            }else{
                 $costo = $this->costo_ingresado;
            }
            
            $oCon=new consulta();
            $query='UPDATE Costo_Promedio SET  Costo_Anterior = Costo_Promedio, Ultima_Actualizacion = NOW(), Costo_Promedio = '. number_format($costo, 2, '.', '') .'
             WHERE Id_Producto = '.$this->id_producto;
             
            $oCon->setQuery($query);
            $oCon->createData();
            unset($oCon);
        }
    }
}
/* 
$costo = new Costo_Promedio(56227,2,500);
var_dump($costo->existencia_actual); */

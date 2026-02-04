<?php 

require_once __DIR__."/../class.contabilizar.php";

class ContabilidadPrestamo extends Contabilizar {
    
    public function __construct($save_fecha = false)
    {
        parent::__construct($save_fecha);
    }

    public function CrearMovimientoContable($tipo, $datos){
        
        switch ($tipo) {
            case 'Prestamo':
                $this->GetidModulo($tipo);
                $this->id_registro_modulo = $datos['Id_Registro'];
                $this->nit = $datos['Nit'];
                $this->tipo_nit = 'Funcionario';
                $this->CrearMovimientosgetPrestamo($datos);

            break;
            default:
            # code...
            break;
        }
    }

    private function CrearMovimientosgetPrestamo($datos){
     
        $this->GuardarMovimientosgetPrestamo($datos);
    }
    private function GuardarMovimientosgetPrestamo($datos){
        try {
            //origen
            if(!$datos['Id_Plan_Cuenta_Banco']) 
                throw new Exception("es necesario el plan de cuenta del banco", 1);
        
            //origen
            $prestamo = $this->GetPestamo();
            $cuenta = $this->BuscarInformacionParaMovimiento('Prestamo');
            $oItem = new complex("Movimiento_Contable","Id_Movimiento_Contable");
            $oItem->Id_Plan_Cuenta = $cuenta['Id_Plan_Cuenta'];
            $oItem->Id_Modulo = $this->id_modulo;
            $oItem->Id_Registro_Modulo = $this->id_registro_modulo;
            $oItem->Debe = round(floatval($prestamo['Valor_Prestamo']));
            $oItem->Debe_Niif = round(floatval($prestamo['Valor_Prestamo']));
            $oItem->Haber = "0";
            $oItem->Haber_Niif = "0";
            $oItem->Nit = $this->nit;
            $oItem->Tipo_Nit = $this->tipo_nit;
            $oItem->Documento = $prestamo['Codigo'];
            $oItem->Numero_Comprobante = $prestamo['Codigo'];
            $oItem->save();
            unset($oItem);

            //Banco
            $prestamo = $this->GetPestamo();
            $cuenta = $this->BuscarInformacionParaMovimiento('Prestamo');
            $oItem = new complex("Movimiento_Contable","Id_Movimiento_Contable");
            $oItem->Id_Plan_Cuenta = $datos['Id_Plan_Cuenta_Banco'];
            $oItem->Id_Modulo = $this->id_modulo;
            $oItem->Id_Registro_Modulo = $this->id_registro_modulo;
            $oItem->Debe = "0";
            $oItem->Debe_Niif = "0";
            $oItem->Haber = round(floatval($prestamo['Valor_Prestamo']));
            $oItem->Haber_Niif = round(floatval($prestamo['Valor_Prestamo']));
            $oItem->Nit = $this->nit;
            $oItem->Tipo_Nit = $this->tipo_nit;
            $oItem->Documento = $prestamo['Codigo'];
            $oItem->Numero_Comprobante = $prestamo['Codigo'];
            
            $oItem->save();
            unset($oItem);

        } catch (\Throwable $th) {
            //throw $th;
            echo $th->getMessage();
        }
    }

    private function GetPestamo(){
        $query = '
            SELECT
                *
            FROM Prestamo
            WHERE
                Id_Prestamo = '.$this->id_registro_modulo;
        $this->queryObj->SetQuery($query);
        return $this->queryObj->ExecuteQuery('simple');

    }
}

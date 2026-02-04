<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');
include_once('../../class/class.contabilizar.php');

require('utilidades/querys.php');
require('utilidades/funciones.php');
require('../comprobantes/funciones.php');

$datos = isset($_REQUEST['datos']) ? $_REQUEST['datos'] : false;
$insert = [];
$cuentas_depreciaciacion = [
    "Debito" => [],
    "Credito" => []
];

if ($datos) {

    $contabilizar  = new contabilizar();
    
    $datos = (array) json_decode($datos, true);

    if (validarDepreciacion($datos['Mes'],$datos["Year"])) {
        $resultado['tipo'] = "info";
        $resultado['mensaje'] = "Ya ha sido depreciado el mes seleccionado.";
        $resultado['titulo'] = "Ooops!";
    } else {

        $tipo = $datos['Tipo'];

        $mes = mesFormat($datos['Mes']);
        $year = $datos['Year'];
    
        $cod = generarConsecutivo('Depreciacion', $mes, $year);
        $datos['Codigo']=$cod;
        //$datos['Anio'] = date('Y');
        $datos['Anio'] = $datos['Year'];
    
        $oItem = new complex('Depreciacion','Id_Depreciacion');
        foreach ($datos as $index => $value) {
            $oItem->$index = $value;
        }
       $oItem->save();
        $id_depreciacion = $oItem->getId();
        unset($oItem);
    
        
        $activos = [];
        $tipos_activos = getDatosTiposActivos('PCGA', $datos['Mes'], $datos['Year'],true);
    
        foreach ($tipos_activos as $i => $tipo_act) {
    
            foreach ($tipo_act['activos_fijos'] as $j => $activo) {
                $costo_pcga = $activo['Costo_PCGA'];
                //$anio_compra = date('Y', strtotime($activo['Fecha']));
                $anio_compra = $year;
    
                //$vida_util_pcga = $activo['Tipo_Depreciacion'] == 1 && $anio_compra == date('Y') ? 1 : $tipo_act['Vida_Util_PCGA']; // Si el tipo de depreciacion es 0, se deprecia de manera normal, de lo contrario, solo se depreciará a 1 mes.

                $vida_util_pcga = $activo['Tipo_Depreciacion'] == 1 && $anio_compra == $datos['Year'] ? 1 : $tipo_act['Vida_Util_PCGA'];

                $valor_depreciacion_pcga = calcularDepreciacionMes($datos['Mes'],$datos["Year"], $activo['ID'], $datos['Mes'], $tipo_act['Porcentaje_PCGA'], 
                                            $costo_pcga, $vida_util_pcga,$activo['Vida_Util_Acum'],$activo['Fecha'],$activo['Depreciacion_Acum_PCGA'],'PCGA');
               
                $activos[strval($activo['ID'])] = ["Pcga" => $valor_depreciacion_pcga];
    
                $plan_deb = strval($tipo_act['Id_Plan_Cuenta_Depreciacion']);
                $plan_cred = strval($tipo_act['Id_Plan_Cuenta_Credito_Depreciacion']);
                
                if (!array_key_exists($plan_deb,$cuentas_depreciaciacion["Debito"])) {
                    $cuentas_depreciaciacion["Debito"][$plan_deb]['Pcga'] = floatval(number_format($valor_depreciacion_pcga,2,".",""));
              
                    
                } else {
                    $cuentas_depreciaciacion["Debito"][$plan_deb]['Pcga'] += floatval(number_format($valor_depreciacion_pcga,2,".",""));
             
                }
    
                if (!array_key_exists($plan_cred,$cuentas_depreciaciacion["Credito"])) {
                    $cuentas_depreciaciacion["Credito"][$plan_cred]['Pcga'] = floatval(number_format($valor_depreciacion_pcga,2,".",""));
                  
                } else {
                    $cuentas_depreciaciacion["Credito"][$plan_cred]['Pcga'] += floatval(number_format($valor_depreciacion_pcga,2,".",""));
                   
                }
                
               
            }
            
        }

        $tipos_activos = getDatosTiposActivos('NIIF', $datos['Mes'], $datos['Year']);
    
        foreach ($tipos_activos as $i => $tipo_act) {
    
            foreach ($tipo_act['activos_fijos'] as $j => $activo) {
                $costo_niif = $activo['Costo_NIIF'];
                //$anio_compra = date('Y', strtotime($activo['Fecha']));
                $anio_compra = $datos['Year'];


                //$vida_util_niif = $activo['Tipo_Depreciacion'] == 1 && $anio_compra == date('Y') ? 1 : $tipo_act['Vida_Util_NIIF']; // Si el tipo de depreciacion es 0, se deprecia de manera normal, de lo contrario, solo se depreciará a 1 mes.
                $vida_util_niif = $activo['Tipo_Depreciacion'] == 1 && $anio_compra == $datos['Year'] ? 1 : $tipo_act['Vida_Util_NIIF'];
    
                $valor_depreciacion_niif= null;
                if( $tipo_act['Sin_Depreciacion_Niff'] == 1){
                    $valor_depreciacion_niif = 0;
                }
                else{
                    
                    $valor_depreciacion_niif = calcularDepreciacionMes($datos['Mes'], $datos["Year"], $activo['ID'], $datos['Mes'], $tipo_act['Porcentaje_NIIF'],
                    $costo_niif, $vida_util_niif,$activo['Vida_Util_Acum'],$activo['Fecha'],$activo['Depreciacion_Acum_NIIF'],'NIIF'); 
                }
                

                $tipo_act['activos_fijos'][$j]['depre'] = $valor_depreciacion_niif;
                if (!array_key_exists(strval($activo['ID']),$activos)) {
                    $activos[strval($activo['ID'])] = ["Pcga" => 0, "Niif" => $valor_depreciacion_niif];
                } else {
                    $activos[strval($activo['ID'])]['Niif'] = $valor_depreciacion_niif;
                }
    
                $plan_deb = strval($tipo_act['Id_Plan_Cuenta_Depreciacion']);
                $plan_cred = strval($tipo_act['Id_Plan_Cuenta_Credito_Depreciacion']);
                
                if (!array_key_exists($plan_deb,$cuentas_depreciaciacion["Debito"])) {
                    $cuentas_depreciaciacion["Debito"][$plan_deb]['Niif'] = floatval(number_format($valor_depreciacion_niif,2,".",""));
                } else {
                    $cuentas_depreciaciacion["Debito"][$plan_deb]['Niif'] += floatval(number_format($valor_depreciacion_niif,2,".",""));
                }
    
                if (!array_key_exists($plan_cred,$cuentas_depreciaciacion["Credito"])) {
                    $cuentas_depreciaciacion["Credito"][$plan_cred]['Niif'] = floatval(number_format($valor_depreciacion_niif,2,".",""));
                } else {
                    $cuentas_depreciaciacion["Credito"][$plan_cred]['Niif'] += floatval(number_format($valor_depreciacion_niif,2,".",""));
                }
               
               
            }
            
         
            
          
           
        }

        foreach ($activos as $id => $value) {
            if ($value['Pcga'] > 0 || $value['Niif'] > 0) {
                $insert[] = "($id_depreciacion,$id,".number_format($value['Pcga'],2,".","").",".number_format($value['Niif'],2,".","").")"; // Armo los VALUES del insert masivamente.
            }
        }

        $query = "INSERT INTO Activo_Fijo_Depreciacion (Id_Depreciacion,Id_Activo_Fijo,Valor_PCGA,Valor_NIIF) VALUES " . implode(',',$insert);
    
        $oCon = new consulta();
        $oCon->setQuery($query);
        $oCon->createData();
        unset($oCon);
        
        
        $datos_contabilizacion['Id_Registro'] = $id_depreciacion;
        $datos_contabilizacion['Datos'] = $datos;
        $datos_contabilizacion['Contabilizacion'] = $cuentas_depreciaciacion;
       
       
     $contabilizar->CrearMovimientoContable('Depreciacion',$datos_contabilizacion);
        
        if ($id_depreciacion) {
            $resultado['tipo'] = "success";
            $resultado['mensaje'] = "Se ha contabilizado correctamente la depreciación de los activos fijos con el código: ".$datos['Codigo'];
            $resultado['titulo'] = "Exito!";
            $resultado['Id'] = $id_depreciacion;
        } else {
            $resultado['tipo'] = "error";
            $resultado['mensaje'] = "Ha ocurrido un error en el proceso. Por favor vuelve a intentarlo.";
            $resultado['titulo'] = "Ooops!";
        }
    }


    echo json_encode($resultado);
    
}

function validarDepreciacion($mes,$year) {
    //$query = "SELECT Id_Depreciacion FROM Depreciacion WHERE Mes = $mes AND Anio = YEAR(CURDATE()) AND Estado = 'Activo'";
    $query = "SELECT Id_Depreciacion FROM Depreciacion WHERE Mes = $mes AND Anio = $year AND Estado = 'Activo'";

    $oCon = new consulta();
    $oCon->setQuery($query);
    $res = $oCon->getData();
    unset($oCon);

    if ($res) {
        return true;
    }

    return false;
}

?>
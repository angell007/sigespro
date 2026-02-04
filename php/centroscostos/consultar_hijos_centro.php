<?php
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
    header('Content-Type: application/json');

    require_once('../../config/start.inc.php');
    include_once('../../class/class.lista.php');
    include_once('../../class/class.complex.php');
    include_once('../../class/class.consulta.php');

    $query = '
        SELECT
            *
        FROM Centro_Costo
        WHERE
            Id_Centro_Padre = 0';

    $oCon= new consulta();
    $oCon->setTipo('Multiple');
    $oCon->setQuery($query);
    $centros_padre_raiz = $oCon->getData();
    unset($oCon);

    echo json_encode(ArmarArbolCentros($centros_padre_raiz));

    function ArmarArbolCentros($centros_raiz, $centros_hijos = null){
        $count = count($centros_raiz);
        $centro_raiz_actual = array('id' => 0, 'nombre' => '');
        $array_final = array();


        if ($count > 0) {
            
            $c = 0;

            foreach ($centros_raiz as $k) { 
                //$centros_raiz[$c]['hijos'] = CicloArbol($k['Id_Centro_Costo']);
                $array_final[$c]['value'] = $k['Id_Centro_Costo'];
                $array_final[$c]['text'] = $k['Nombre'];
                $array_final[$c]['children'] = CicloArbol($k['Id_Centro_Costo']);
                $c++;
            }
        }

        return $array_final; 
    }

    function CicloArbol($idPadre){

        $h = ConsultarCentrosPorPadre($idPadre);
        $arr = array();
        $c2 = 0;
        if (count($h) > 0) {
                    
            foreach ($h as $hijo) {
                //$h[$c2]['hijos'] = CicloArbol($hijo['Id_Centro_Costo']);
                $arr[$c2]['text'] = $hijo[$c2]['Nombre'];
                $arr[$c2]['value'] = $hijo[$c2]['Id_Centro_Costo'];
                $arr[$c2]['children'] = CicloArbol($hijo['Id_Centro_Costo']);
                $c2++;
            }

            return $arr;
        }else{

            return array();
        }
    }

    function ConsultarCentrosPorPadre($idPadre){

        $query = '
        SELECT
            *
        FROM Centro_Costo
        WHERE
            Id_Centro_Padre = '.$idPadre;

        $oCon= new consulta();
        $oCon->setTipo('Multiple');
        $oCon->setQuery($query);
        $centros_hijo = $oCon->getData();
        unset($oCon); 

        return $centros_hijo; 
    }
?>   
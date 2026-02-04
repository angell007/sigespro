<?php
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
    // header('Content-Type: application/json');

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

    //var_dump(ArmarArbolCentros($centros_padre_raiz));

    echo json_encode(ArmarArbolCentros($centros_padre_raiz));

    function ArmarArbolCentros($centros_raiz, $centros_hijos = null){
        $count = count($centros_raiz);
        $centro_raiz_actual = array('id' => 0, 'nombre' => '');
        $hijos_actuales = array();
        $arboles_centros = array();


        if ($count > 0) {
            
            $centro_raiz_actual['id'] = $centros_raiz[0]['Id_Centro_Costo'];
            $centro_raiz_actual['nombre'] = $centros_raiz[0]['Nombre'];

            $c = 0;
            //$nivel = $c + 1;
            $nivel = "";

            foreach ($centros_raiz as $k) { 
                $centros_raiz[$c]['nivel'] = $nivel;
                $centros_raiz[$c]['hijos'] = CicloArbol($k['Id_Centro_Costo'], $nivel);
                $c++;
            }
        }

        return $centros_raiz;
    }

    function CicloArbol($idPadre, $ciclo){

        $h = ConsultarCentrosPorPadre($idPadre);
        //var_dump($idPadre);
        $c2 = 0;
        //$nivel2 = $ciclo. ".".($c2 + 1);
        if (count($h) > 0) {
                    
            foreach ($h as $hijo) {

                //$nivel2 = "<span style='color:transparent;'>&nbsp&nbsp</span>".$ciclo. ".".($c2 + 1);
                $nivel2 = "";

                $h[$c2]['nivel'] = $nivel2;
                $h[$c2]['hijos'] = CicloArbol($hijo['Id_Centro_Costo'], $nivel2);
                $c2++;
            }

            return $h;
        }else{

            return array();
        }
    }

    function ConsultarCentrosPorPadre($idPadre){

        $query = '
            SELECT
                CC.*,
                (IF(CC.Id_Centro_Padre != 0, (SELECT Nombre FROM Centro_Costo WHERE Id_Centro_Costo = CC.Id_Centro_Padre), "Sin Padre")) AS PadreCentro,
                (IF(CC.Id_Tipo_Centro != 0, (SELECT Nombre FROM Tipo_Centro WHERE Id_Tipo_Centro = CC.Id_Tipo_Centro), "")) AS TipoCentro,
                (IF(CC.Id_Tipo_Centro != 0, (CASE
                                                WHEN CC.Id_Tipo_Centro = 1 THEN (SELECT Nombre FROM Cliente WHERE Id_Cliente = CC.Valor_Tipo_Centro)
                                                WHEN CC.Id_Tipo_Centro = 2 THEN (SELECT Nombre FROM Departamento WHERE Id_Departamento = CC.Valor_Tipo_Centro)
                                                WHEN CC.Id_Tipo_Centro = 3 THEN (SELECT Nombre FROM Punto_Dispensacion WHERE Id_Punto_Dispensacion = CC.Valor_Tipo_Centro)
                                                WHEN CC.Id_Tipo_Centro = 4 THEN (SELECT Nombre FROM Municipio WHERE Id_Municipio = CC.Valor_Tipo_Centro)
                                            END), "")) AS ValorTipoCentro
            FROM Centro_Costo CC
            WHERE
                Id_Centro_Padre = '.$idPadre;

        $oCon= new consulta();
        $oCon->setTipo('Multiple');
        $oCon->setQuery($query);
        $centros_hijo = $oCon->getData();
        unset($oCon); 

         /*if (($centros_hijo) > 0) {
            
            $i = 0;
            foreach ($centros_hijo as $key => $value) {
                
                switch ($value['TipoCentro']) {
                    case 'Tercero':
                        
                        $query = '
                            SELECT
                                Nombre
                            FROM Cliente
                            WHERE
                                Id_Cliente = '.$value['Valor_Tipo_Centro'];

                        $oCon= new consulta();
                        $oCon->setQuery($query);
                        $nombre_tercero = $oCon->getData();
                        unset($oCon);

                        $centros_hijo[$i]['ValorTipoCentro'] = $nombre_tercero['Nombre'];
                        break;

                    case 'Departamento':
                        
                        $query = '
                            SELECT
                                Nombre
                            FROM Departamento
                            WHERE
                                Id_Departamento = '.$value['Valor_Tipo_Centro'];

                        $oCon= new consulta();
                        $oCon->setQuery($query);
                        $nombre_departamento = $oCon->getData();
                        unset($oCon);

                        $centros_hijo[$i]['ValorTipoCentro'] = $nombre_departamento['Nombre'];
                        break;

                    case 'Punto de Dispensacion':
                        
                        $query = '
                            SELECT
                                Nombre
                            FROM Punto_Dispensacion
                            WHERE
                                Id_Punto_Dispensacion = '.$value['Valor_Tipo_Centro'];

                        $oCon= new consulta();
                        $oCon->setQuery($query);
                        $nombre_punto = $oCon->getData();
                        unset($oCon);

                        $centros_hijo[$i]['ValorTipoCentro'] = $nombre_punto['Nombre'];
                        break;
                    
                    default:
                        $centros_hijo[$i]['ValorTipoCentro'] = '';
                        break;
                }

                $i++;
            }
        } */

        return $centros_hijo; 
    }

    function Separar($valor){
        $separado = explode(".", $valor);
        if (count($separado) > 0) {
            $acumulador = 0;

            foreach ($separado as $key => $value) {
                $acumulador += $value;
            }
        }else{
            return 0;
        }
        
        return ($acumulador * 5);
    }
?>   
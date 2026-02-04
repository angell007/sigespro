<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../../config/start.inc.php');
include_once('../../../class/class.lista.php');
include_once('../../../class/class.complex.php');
include_once('../../../class/class.consulta.php');

$oItem = new Lista('Bodega_Inicial');
$oItem->setRestrict('Peso_Minimo', "=", "M");
$bodega = $oItem->getList();
unset($oItem);
var_dump($bodega);
exit;

$guardados     = array();
$actualizados  = array();
$noEncontrados = array();
$mantis        = array();

$rutas = array('wqeu-3uhz.json','994u-gm46.json','8tya-2uai.json','6nr4-fx8r.json','7c5e-muu4.json'); //vigentes ,  renovacion , otros estados, vencidos  

function limpiar($String)
{
$String = str_replace("  "," ",$String);
$String = str_replace("á","a",$String);
$String = str_replace("Á","A",$String);
$String = str_replace("Í","I",$String);
$String = str_replace("í","i",$String);
$String = str_replace("é","e",$String);
$String = str_replace("É","E",$String);
$String = str_replace("ó","o",$String);
$String = str_replace("Ó","O",$String);
$String = str_replace("ú","u",$String);
$String = str_replace("Ú","U",$String);
$String = str_replace("ç","c",$String);
$String = str_replace("Ç","C",$String);
$String = str_replace("ñ","n",$String);
$String = str_replace("Ñ","N",$String);
$String = str_replace("Ý","Y",$String);
$String = str_replace("ý","y",$String);
$String = str_replace("'","",$String);
return $String;
}

$i = -1;
foreach ($bodega as $data) {
    //recorro los productos que tengan el CUM de bodega_inicial
    $oData = new Complex("Producto", 'Codigo_Cum', $data['Cum']);
    $prod  = $oData->getData();
    unset($oData);
    
    if (isset($prod['Id_Producto'])) {
        //actualizo los campos
        
        $oData2                = new Complex("Producto", 'Id_Producto', $prod['Id_Producto']);
        $oData2->Mantis        = $data['Cod_Mantis'];
        $oData2->Imagen        = $data['Foto'];
        $oData2->Codigo_Barras = $data['Codigo_Barras'];
        $oData2->Cantidad_Presentacion  = (int)$data['Presentacion'];
        
        
        if (strpos($data['Peso_Minimo'], 'No') !== false) {
            $oData2->Peso_Presentacion_Minima = 0;
        } else {
            if (strpos($data['Peso_Minimo'], 'NO') !== false) {
                $oData2->Peso_Presentacion_Minima = 0;
            } else {
                if (strpos($data['Peso_Minimo'], 'no') !== false) {
                    $oData2->Peso_Presentacion_Minima = 0;
                } else {
                    if (strpos($data['Peso_Minimo'], 'G') !== false) {
                        $peso1                            = explode("G", $data['Peso_Minimo']);
                        $oData2->Peso_Presentacion_Minima = $peso1[0];
                    } else {
                        if (strpos($data['Peso_Minimo'], 'g') !== false) {
                            $peso1                            = explode("g", $data['Peso_Minimo']);
                            $oData2->Peso_Presentacion_Minima = $peso1[0];
                        } else {
                            if (strpos($data['Peso_Minimo'], 'M') === true) {
                                $oData2->Peso_Presentacion_Minima = $data['Peso_Minimo'];
                            }
                        }
                    }
                }
            }
        }
        
        
        if (strpos($data['Peso_Regular'], 'No') !== false) {
            $oData2->Peso_Presentacion_Regular = 0;
        } else {
            if (strpos($data['Peso_Regular'], 'NO') !== false) {
                $oData2->Peso_Presentacion_Regular = 0;
            } else {
                if (strpos($data['Peso_Regular'], 'no') !== false) {
                    $oData2->Peso_Presentacion_Regular = 0;
                } else {
                    if (strpos($data['Peso_Regular'], 'G') !== false) {
                        $peso2                             = explode("G", $data['Peso_Regular']);
                        echo $data["Cum"].": ".$peso2[0].'\n';
                        $oData2->Peso_Presentacion_Regular = $peso2[0];
                    } else {
                        if (strpos($data['Peso_Regular'], 'g') !== false) {
                            $peso2                             = explode("g", $data['Peso_Regular']);
                            $oData2->Peso_Presentacion_Regular = $peso2[0];
                        } else {
                            if (strpos($data['Peso_Regular'], 'M') === true) {
                                $oData2->Peso_Presentacion_Regular = $data['Peso_Regular'];
                            }
                        }
                    }
                }
            }
        }
        
        
        if (strpos($data['Peso_Maximo'], 'No') !== false) {
            $oData2->Peso_Presentacion_Maxima = 0;
        } else {
            if (strpos($data['Peso_Maximo'], 'NO') !== false) {
                $oData2->Peso_Presentacion_Maxima = 0;
            } else {
                if (strpos($data['Peso_Maximo'], 'no') !== false) {
                    $oData2->Peso_Presentacion_Maxima = 0;
                } else {
                    if (strpos($data['Peso_Maximo'], 'G') !== false) {
                        $peso3                            = explode("G", $data['Peso_Maximo']);
                        $oData2->Peso_Presentacion_Maxima = $peso3[0];
                    } else {
                        if (strpos($data['Peso_Maximo'], 'g') !== false) {
                            $peso3                            = explode("g", $data['Peso_Maximo']);
                            $oData2->Peso_Presentacion_Maxima = $peso3[0];
                        } else {
                            if (strpos($data['Peso_Maximo'], 'M') === true) {
                                $oData2->Peso_Presentacion_Maxima = $data['Peso_Maximo'];
                            }
                        }
                    }
                }
            }
        }
        
        
        // echo "Se actualizo el cum " .$data['Cum']. "  en la DB\n"; 
        $oData2->save();
        array_push($actualizados, $data['Cum']);
        unset($oData2);
        
    } else {
        
        // imprimo que no existe
        if (strpos($data['Cum'], '-') !== false) {
            $cum = explode("-", $data['Cum']);
            
            if(is_numeric($cum[0])&&is_numeric($cum[1])){
            // Get cURL resource
            
            $x=-1;
            $result=[];
            while (count($result) == 0 && $x < 3) {$x++;
            
                $curl = curl_init();
                // Set some options - we are passing in a useragent too here
                curl_setopt_array($curl, array(
                    CURLOPT_RETURNTRANSFER => 1,
                    CURLOPT_URL => 'https://www.datos.gov.co/resource/'.$rutas[$x].'?expediente=' . $cum[0] . '&consecutivocum=' . $cum[1],
                    CURLOPT_USERAGENT => 'Codular Sample cURL Request'
                ));
                // Send the request & save response to $resp
                $resp   = curl_exec($curl);
                $result = (array) json_decode($resp, true);
                // Close request to clear up some resources
                curl_close($curl);
                
                if (count($result) > 0) {
                    $oData3                        = new Complex("Producto", 'Id_Producto');
                    $oData3->Codigo_Cum            = $data['Cum'];
                    $oData3->Principio_Activo      = limpiar(utf8_decode($result[0]['principioactivo']));
                    $oData3->Presentacion          = limpiar(utf8_decode($result[0]['unidadreferencia']));
                    $oData3->Concentracion         = limpiar(utf8_decode($result[0]['concentracion']));
                    $oData3->Nombre_Comercial      = limpiar(utf8_decode($result[0]['producto']));
                    $oData3->Embalaje              = limpiar(utf8_decode($result[0]['descripcioncomercial']));
                    $oData3->Laboratorio_Generico  = limpiar(utf8_decode($result[0]['titular']));
                    $oData3->Laboratorio_Comercial = limpiar(utf8_decode($result[0]['nombrerol']));
                    $oData3->ATC                   = limpiar(utf8_decode($result[0]['atc']));
                    $oData3->Descripcion_ATC       = limpiar(utf8_decode($result[0]['descripcionatc']));
                    $oData3->Invima                = limpiar(utf8_decode($result[0]['registrosanitario']));
                    $oData3->Via_Administracion    = limpiar(utf8_decode($result[0]['viaadministracion']));
                    $oData3->Unidad_Medida         = limpiar(utf8_decode($result[0]['unidadmedida']));
                    $oData3->Cantidad              = limpiar(utf8_decode($result[0]['cantidad']));
                    
                    $oData3->Mantis        = $data['Cod_Mantis'];
                    $oData3->Imagen        = $data['Foto'];
                    $oData3->Codigo_Barras = $data['Codigo_Barras'];
                    $oData3->Presentacion  = $data['Cantidad_Presentacion'];
                    
                    
                    if (strpos($data['Peso_Minimo'], 'No') !== false) {
                        $oData3->Peso_Presentacion_Minima = 0;
                    } else {
                        if (strpos($data['Peso_Minimo'], 'NO') !== false) {
                            $oData3->Peso_Presentacion_Minima = 0;
                        } else {
                            if (strpos($data['Peso_Minimo'], 'no') !== false) {
                                $oData3->Peso_Presentacion_Minima = 0;
                            } else {
                                if (strpos($data['Peso_Minimo'], 'G') !== false) {
                                    $peso1                            = explode("G", $data['Peso_Minimo']);
                                    $oData3->Peso_Presentacion_Minima = $peso1[0];
                                } else {
                                    if (strpos($data['Peso_Minimo'], 'g') !== false) {
                                        $peso1                            = explode("g", $data['Peso_Minimo']);
                                        $oData3->Peso_Presentacion_Minima = $peso1[0];
                                    } else {
                                        if (strpos($data['Peso_Minimo'], 'M') === true) {
                                            $oData3->Peso_Presentacion_Minima = 0;
                                        }
                                    }
                                }
                            }
                        }
                    }
                    
                    
                    if (strpos($data['Peso_Regular'], 'No') !== false) {
                        $oData3->Peso_Presentacion_Regular = 0;
                    } else {
                        if (strpos($data['Peso_Regular'], 'NO') !== false) {
                            $oData3->Peso_Presentacion_Regular = 0;
                        } else {
                            if (strpos($data['Peso_Regular'], 'no') !== false) {
                                $oData3->Peso_Presentacion_Regular = 0;
                            } else {
                                if (strpos($data['Peso_Regular'], 'G') !== false) {
                                    $peso2                             = explode("G", $data['Peso_Regular']);
                                    echo $data["Cum"].": ".$peso2[0].'\n';
                                    $oData3->Peso_Presentacion_Regular = $peso2[0];
                                } else {
                                    if (strpos($data['Peso_Regular'], 'g') !== false) {
                                        $peso2                             = explode("g", $data['Peso_Regular']);
                                        $oData3->Peso_Presentacion_Regular = $peso2[0];
                                    } else {
                                        if (strpos($data['Peso_Regular'], 'M') === true) {
                                            $oData3->Peso_Presentacion_Regular = 0;
                                        }
                                    }
                                }
                            }
                        }
                    }
                    
                    
                    if (strpos($data['Peso_Maximo'], 'No') !== false) {
                        $oData3->Peso_Presentacion_Maxima = 0;
                    } else {
                        if (strpos($data['Peso_Maximo'], 'NO') !== false) {
                            $oData3->Peso_Presentacion_Maxima = 0;
                        } else {
                            if (strpos($data['Peso_Maximo'], 'no') !== false) {
                                $oData3->Peso_Presentacion_Maxima = 0;
                            } else {
                                if (strpos($data['Peso_Maximo'], 'G') !== false) {
                                    $peso3                            = explode("G", $data['Peso_Maximo']);
                                    $oData3->Peso_Presentacion_Maxima = $peso3[0];
                                } else {
                                    if (strpos($data['Peso_Maximo'], 'g') !== false) {
                                        $peso3                            = explode("g", $data['Peso_Maximo']);
                                        $oData3->Peso_Presentacion_Maxima = $peso3[0];
                                    } else {
                                        if (strpos($data['Peso_Maximo'], 'M') === true) {
                                            $oData3->Peso_Presentacion_Maxima = 0;
                                        }
                                    }
                                }
                            }
                        }
                    }
                    $oData3->save();
                    array_push($guardados, $data['Cum']);
                    // echo "Se creo el cum " .$data['Cum']. "  en la DB\n"; 
                    unset($oData3);
                }
                
                
                
                
            }
          }else{
              array_push($noEncontrados, $data['Cum']);
                array_push($mantis, $data['Cod_Mantis']);
          }
            
        } else {
            //echo "no encuentro el cum " .$data['Cum']. " \n"; 
           
            if(is_numeric($data['Cum'])){ 
            // Get cURL resource
           // echo $data["Cum"]."\n";
            $x=-1;
            $result=[];
            while (count($result) == 0 && $x < 4) {$x++;
                $curl = curl_init();
                // Set some options - we are passing in a useragent too here
                curl_setopt_array($curl, array(
                    CURLOPT_RETURNTRANSFER => 1,
                    CURLOPT_URL => 'https://www.datos.gov.co/resource/'.$rutas[$x].'?expediente=' . $data["Cum"],
                    CURLOPT_USERAGENT => 'Codular Sample cURL Request'
                ));
                // Send the request & save response to $resp
                $resp   = curl_exec($curl);
                $result = (array) json_decode($resp, true);
                // Close request to clear up some resources
                curl_close($curl);
                if (count($result) > 0) {
                    if($x!=4){
                        $oData3                        = new Complex("Producto", 'Id_Producto');
                        $oData3->Codigo_Cum            = $data['Cum'];
                        $oData3->Principio_Activo      = limpiar(utf8_decode($result[0]['principioactivo']));
                        $oData3->Presentacion          = limpiar(utf8_decode($result[0]['unidadreferencia']));
                        $oData3->Concentracion         = limpiar(utf8_decode($result[0]['concentracion']));
                        $oData3->Nombre_Comercial      = limpiar(utf8_decode($result[0]['producto']));
                        $oData3->Embalaje              = limpiar(utf8_decode($result[0]['descripcioncomercial']));
                        $oData3->Laboratorio_Generico  = limpiar(utf8_decode($result[0]['titular']));
                        $oData3->Laboratorio_Comercial = limpiar(utf8_decode($result[0]['nombrerol']));
                        $oData3->ATC                   = limpiar(utf8_decode($result[0]['atc']));
                        $oData3->Descripcion_ATC       = limpiar(utf8_decode($result[0]['descripcionatc']));
                        $oData3->Invima                = limpiar(utf8_decode($result[0]['registrosanitario']));
                        $oData3->Via_Administracion    = limpiar(utf8_decode($result[0]['viaadministracion']));
                        $oData3->Unidad_Medida         = limpiar(utf8_decode($result[0]['unidadmedida']));
                        $oData3->Cantidad              = limpiar(utf8_decode($result[0]['cantidad']));
                    }else{
                        $oData3                        = new Complex("Producto", 'Id_Producto');
                        $oData3->Codigo_Cum            = $data['Cum'];
                        //$oData3->Principio_Activo      = limpiar(utf8_decode($result[0]['principioactivo']));
                        //$oData3->Presentacion          = limpiar(utf8_decode($result[0]['unidadreferencia']));
                        //$oData3->Concentracion         = limpiar(utf8_decode($result[0]['concentracion']));
                        $oData3->Nombre_Comercial      = limpiar(utf8_decode($result[0]['producto']));
                        //$oData3->Embalaje              = limpiar(utf8_decode($result[0]['descripcioncomercial']));
                        //$oData3->Laboratorio_Generico  = limpiar(utf8_decode($result[0]['titular']));
                        $oData3->Laboratorio_Comercial = limpiar(utf8_decode($result[0]['titular']));
                        //$oData3->ATC                   = limpiar(utf8_decode($result[0]['atc']));
                        //$oData3->Descripcion_ATC       = limpiar(utf8_decode($result[0]['descripcionatc']));
                        $oData3->Invima                = limpiar(utf8_decode($result[0]['rsynso']));
                        //$oData3->Via_Administracion    = limpiar(utf8_decode($result[0]['viaadministracion']));
                        // $oData3->Unidad_Medida         = limpiar(utf8_decode($result[0]['unidadmedida']));
                        $oData3->Cantidad              = 0;
                    }
                    $oData3->Mantis        = $data['Cod_Mantis'];
                    $oData3->Imagen        = $data['Foto'];
                    $oData3->Codigo_Barras = $data['Codigo_Barras'];
                    $oData3->Presentacion  = $data['Cantidad_Presentacion'];
                    
                    
                    if (strpos($data['Peso_Minimo'], 'No') !== false) {
                        $oData3->Peso_Presentacion_Minima = 0;
                    } else {
                        if (strpos($data['Peso_Minimo'], 'NO') !== false) {
                            $oData3->Peso_Presentacion_Minima = 0;
                        } else {
                            if (strpos($data['Peso_Minimo'], 'no') !== false) {
                                $oData3->Peso_Presentacion_Minima = 0;
                            } else {
                                if (strpos($data['Peso_Minimo'], 'G') !== false) {
                                    $peso1                            = explode("G", $data['Peso_Minimo']);
                                    $oData3->Peso_Presentacion_Minima = $peso1[0];
                                } else {
                                    if (strpos($data['Peso_Minimo'], 'g') !== false) {
                                        $peso1                            = explode("g", $data['Peso_Minimo']);
                                        $oData3->Peso_Presentacion_Minima = $peso1[0];
                                    } else {
                                        if (strpos($data['Peso_Minimo'], 'M') === true) {
                                            $oData3->Peso_Presentacion_Minima = 0;
                                        }
                                    }
                                }
                            }
                        }
                    }
                    
                    
                    if (strpos($data['Peso_Regular'], 'No') !== false) {
                        $oData3->Peso_Presentacion_Regular = 0;
                    } else {
                        if (strpos($data['Peso_Regular'], 'NO') !== false) {
                            $oData3->Peso_Presentacion_Regular = 0;
                        } else {
                            if (strpos($data['Peso_Regular'], 'no') !== false) {
                                $oData3->Peso_Presentacion_Regular = 0;
                            } else {
                                if (strpos($data['Peso_Regular'], 'G') !== false) {
                                    $peso2                             = explode("G", $data['Peso_Regular']);
                                    $oData3->Peso_Presentacion_Regular = $peso2[0];
                                } else {
                                    if (strpos($data['Peso_Regular'], 'g') !== false) {
                                        $peso2                             = explode("g", $data['Peso_Regular']);
                                        $oData3->Peso_Presentacion_Regular = $peso2[0];
                                    } else {
                                        if (strpos($data['Peso_Regular'], 'M') === true) {
                                            $oData3->Peso_Presentacion_Regular = 0;
                                        }
                                    }
                                }
                            }
                        }
                    }
                    
                    
                    if (strpos($data['Peso_Maximo'], 'No') !== false) {
                        $oData3->Peso_Presentacion_Maxima = 0;
                    } else {
                        if (strpos($data['Peso_Maximo'], 'NO') !== false) {
                            $oData3->Peso_Presentacion_Maxima = 0;
                        } else {
                            if (strpos($data['Peso_Maximo'], 'no') !== false) {
                                $oData3->Peso_Presentacion_Maxima = 0;
                            } else {
                                if (strpos($data['Peso_Maximo'], 'G') !== false) {
                                    $peso3                            = explode("G", $data['Peso_Maximo']);
                                    $oData3->Peso_Presentacion_Maxima = $peso3[0];
                                } else {
                                    if (strpos($data['Peso_Maximo'], 'g') !== false) {
                                        $peso3                            = explode("g", $data['Peso_Maximo']);
                                        $oData3->Peso_Presentacion_Maxima = $peso3[0];
                                    } else {
                                        if (strpos($data['Peso_Maximo'], 'M') === true) {
                                            $oData3->Peso_Presentacion_Maxima = 0;
                                        }
                                    }
                                }
                            }
                        }
                    }
                    $oData3->save();
                    array_push($guardados, $data['Cum']);
                    // echo "Se creo el cum " .$data['Cum']. "  en la DB\n"; 
                    unset($oData3);
                }elseif(count($result)==0 & $x==4){
                    array_push($noEncontrados, $data['Cum']);
                    array_push($mantis, $data['Cod_Mantis']);
                    
                }
                
            }
            
         }else{
            array_push($noEncontrados, $data['Cum']);
            array_push($mantis, $data['Cod_Mantis']);
         }
            
        }
        
        
        
        
    }
}

echo "Se han guardado " . count($guardados) . " Cum (s) \nSe han actualizado " . count($actualizados) . " Cum (s) \nNo se han podido agregar " . count($noEncontrados) . " Cum(s)\n\n";
echo "No se agregaron los siguientes Cum(s): \n";

for ($i = 0; $i < count($noEncontrados); $i++) {
    echo "Cum: " . $noEncontrados[$i] . " Mantis: " . $mantis[$i] . " \n";
}

?>
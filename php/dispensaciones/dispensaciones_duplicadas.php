<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$query = "SELECT GROUP_CONCAT(Id_Dispensacion) AS Dispensaciones, Numero_Documento, COUNT(Numero_Documento) AS Repetidos FROM Dispensacion WHERE Estado_Dispensacion<>'Anulada' GROUP BY Numero_Documento HAVING Repetidos>1";

$con = new consulta(); // Obtenemos las dispensaciones repetidas
$con->setQuery($query);
$con->setTipo("Multiple");
$dis_repetidas = $con->getData();
unset($con);

$count_firstProdDisp = 0;
$first_disp = '';
$duplicados = 0;

$i = -1;
$j = -1;
foreach ($dis_repetidas as $dis_rep) {$i++;
  $dispensaciones = explode(",",$dis_rep['Dispensaciones']); // Explodeamos las dispensaciones agrupadas
  $count_otherProdDisp = -1;
  foreach ($dispensaciones as $dis) {$j++; // Iteramos las dispensaciones explodeadas

    if ($j == 0) { // Si es la priemra iteracion, obtenemos la cantidad de productos de la dispensacion de la primera iteración.
      $query = "SELECT COUNT(*) AS Total FROM Producto_Dispensacion WHERE Id_Dispensacion=$dis\n\n";
      $con = new consulta();
      $con->setQuery($query);
      $rest = $con->getData();
      unset($con);
      $first_disp = $dis;
      $count_firstProdDisp = $rest['Total'];
      echo "La cantidad de productos en la primera iteración en la dispensacion $dis es de $count_firstProdDisp\n\n";
    } else {
      $query = "SELECT COUNT(*) AS Total FROM Producto_Dispensacion WHERE Id_Dispensacion=$dis";
      $con = new consulta();
      $con->setQuery($query);
      $rest = $con->getData();
      unset($con);
      $count_otherProdDisp = $rest['Total']; // Obtenemos la cantidad de productos de las otras dispensaciones para poder comparar y saber si están duplicadas.
      echo "La cantidad de productos en la otra iteración en la dispensacion $dis es de $count_otherProdDisp\n\n";
    }

    if ($count_firstProdDisp == $count_otherProdDisp) { // Si la cantidad de productos de la primera iteración es igual con las demás empezamos a comparar si se trata de la misma dispensación duplicada.
      $query = "SELECT * FROM Producto_Dispensacion WHERE Id_Dispensacion=$first_disp";
      $con = new consulta(); 
      $con->setQuery($query); // Productos de la primera iteración.
      $con->setTipo("Multiple");
      $prod1 = $con->getData();
      unset($con);
      
      $query = "SELECT * FROM Producto_Dispensacion WHERE Id_Dispensacion=$dis";
      $con = new consulta(); 
      $con->setQuery($query); // Productos de las otras iteraciones
      $con->setTipo("Multiple");
      $prod2 = $con->getData();
      unset($con);

      for ($index=0; $index < count($prod1); $index++) { 
        if ($prod1[$index]['Id_Producto'] == $prod2[$index]['Id_Producto'] && $prod1[$index]['Id_Inventario'] == $prod2[$index]['Id_Inventario'] && $prod1[$index]['Cum'] == $prod2[$index]['Cum'] && $prod1[$index]['Lote'] == $prod2[$index]['Lote'] && $prod1[$index]['Cantidad_Formulada'] == $prod2[$index]['Cantidad_Formulada'] && $prod1[$index]['Cantidad_Entregada'] == $prod2[$index]['Cantidad_Entregada']) {
          /* if (!in_array($dis, $duplicados)) {
            echo "--- Dis repetida $dis\n\n";
            array_push($duplicados, $dis); // Los meto en un array para el final del proceso saber cuales están duplicados.
          } */
          
          $id  = $prod2[$index]['Id_Dispensacion'];
          $func = 12345;

          // var_dump($id);

          if ($index == 0) {
            if ($id != null) {
              $oItem = new complex('Dispensacion','Id_Dispensacion',$id);
              $oItem->Estado_Dispensacion = "Anulada";
              
              // $oItem->save();
              unset($oItem);
              $duplicados++;
              echo "*** Dis $id cambiada a Anulada\n\n";
            }
          }

          if ($id != null) {
            if ($prod2[$index]['Id_Inventario'] != 0) {
              $oItem2 = new complex('Inventario','Id_Inventario',$prod2[$index]['Id_Inventario']);
            
              $cantidad = number_format($prod2[$index]['Cantidad_Entregada'],0,"","");
              $cantidad_final = $oItem2->Cantidad + $cantidad;
              echo "++++ Id_Inventario a actualizar " . $prod2[$index]['Id_Inventario'] . " con la cantidad entrante " . $prod2[$index]['Cantidad_Entregada'] . " y la inicial ". $oItem2->Cantidad ." y la final será $cantidad_final\n\n";
              $oItem2->Cantidad = number_format($cantidad_final,0,"","");
              
              // $oItem2->save();
              unset($oItem2);
              
            }
          }
          
          if ($index == 0) {
            $ActividadDis["Identificacion_Funcionario"]=$func;
            $ActividadDis["Id_Dispensacion"] = $id;
            $ActividadDis['Fecha'] = date("Y-m-d H:i:s");
            $ActividadDis["Detalle"] = "Esta dispensacion fue anulada porque se encuentra duplicada y por petición del departamento del sistema de PROH se procedió a hacer dicha operación";
            $ActividadDis["Estado"] = "Anulada";
            echo "+*+* Actividad agregada de la Dis $id\n\n";
            $oItem3 = new complex("Actividades_Dispensacion","Id_Actividades_Dispensacion");
            foreach($ActividadDis as $index2=>$value) {
              
                $oItem3->$index2=$value;
            }
            // $oItem3->save();
            unset($oItem3);
          }
        }
      }
    }
    if ($j == (count($dispensaciones)-1)) {
      $j = -1;
      echo "Fin de la iteracion de dispensaciones\n\n################################################################################\n\n";
    }
  }
}

echo "Total de dispensaciones duplicadas: " . $duplicados . "\n\n";
?>
<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');
require_once('../../class/html2pdf.class.php');
include_once('../../class/NumeroALetra.php');


$id = ( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : '' );

/* FUNCIONES BASICAS */
function fecha($str)
{
    $parts = explode(" ",$str);
    $date = explode("-",$parts[0]);
    return $date[2] . "/". $date[1] ."/". $date[0];
}

$meses = ["Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre"];
/* FIN FUNCIONES BASICAS*/

/* DATOS GENERALES DE CABECERAS Y CONFIGURACION */
$oItem = new complex('Configuracion',"Id_Configuracion",1);
$config = $oItem->getData();
unset($oItem);
/* FIN DATOS GENERALES DE CABECERAS Y CONFIGURACION */

/* DATOS DEL ARCHIVO A MOSTRAR */
/*$oItem = new complex($tipo,"Id_".$tipo,$id);
$data = $oItem->getData();
unset($oItem);
/* FIN DATOS DEL ARCHIVO A MOSTRAR */

ob_start(); // Se Inicializa el gestor de PDF

/* HOJA DE ESTILO PARA PDF*/
$style='<style>
.page-content{
width:750px;
}
.row{
display:inlinie-block;
width:750px;
}
.td-header{
    font-size:15px;
    line-height: 20px;
}
</style>';
/* FIN HOJA DE ESTILO PARA PDF*/
//clientes
//proveedores
//comprobantes
//factura_comprobante
//cuenta contable comprobante
//retenciones_comprobante
/* HAGO UN SWITCH PARA TODOS LOS MODULOS QUE PUEDEN GENERAR PDF */
$query = 'SELECT L.*, DATE(L.Fecha) as Fecha,  CONCAT(F.Nombres," ",F.Apellidos) as Funcionario, CF.*, (SELECT C.Nombre FROM Cargo C WHERE C.Id_Cargo=F.Id_Cargo) as Cargo,(SELECT Valor FROM Concepto_Liquidacion_Funcionario CL WHERE CL.Concepto LIKE "Aux. Transp. Pendiente por Cancelar%" AND CL.Id_Liquidacion_Funcionario=L.Id_Liquidacion_Funcionario 
) as Auxilio

 FROM Liquidacion_Funcionario L  INNER JOIN Funcionario F ON L.Identificacion_Funcionario=F.Identificacion_Funcionario INNER JOIN Contrato_Funcionario CF ON L.Id_Contrato_Funcionario=CF.Id_Contrato_Funcionario WHERE L.Id_Liquidacion_Funcionario='.$id;

$oCon= new consulta();
$oCon->setQuery($query);
$liquidacion = $oCon->getData();
unset($oCon);

$fecha=date('Y')."-01-01";

if($fecha<$liquidacion['Fecha_Inicio_Contrato']){
    $fecha=$liquidacion['Fecha_Inicio_Contrato'];
}

$query = 'SELECT * FROM Concepto_Liquidacion_Funcionario WHERE Id_Liquidacion_Funcionario='.$id.' HAVING Valor>0';

$oCon= new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$conceptos = $oCon->getData();
unset($oCon);


$query = 'SELECT * FROM Concepto_Liquidacion_Funcionario WHERE Id_Liquidacion_Funcionario='.$id.' HAVING Valor<0';

$oCon= new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$deducciones = $oCon->getData();
unset($oCon);

$query = 'SELECT SUM(Valor) as Valor FROM Concepto_Liquidacion_Funcionario WHERE Id_Liquidacion_Funcionario='.$id;

$oCon= new consulta();
$oCon->setQuery($query);
$total = $oCon->getData();
unset($oCon);
$numero = number_format($total['Valor'], 0, '.','');
$letras = NumeroALetras::convertir($numero);

$query="SELECT DATEDIFF(N.Fecha_Fin,N.Fecha_Inicio) as  Dias FROM Novedad N WHERE N.Id_Tipo_Novedad=1 AND N.Identificacion_Funcionario=".$liquidacion['Identificacion_Funcionario'];
$oCon= new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$vacaciones = $oCon->getData();
unset($oCon);

$dias_tomados=0;
foreach ($vacaciones as  $value) {
   $dias_tomados=$dias_tomados+$value['Dias']+1;
}

$query = 'SELECT SUM(Valor) as Valor FROM Concepto_Liquidacion_Funcionario WHERE Valor>0 AND Id_Liquidacion_Funcionario='.$id;

$oCon= new consulta();
$oCon->setQuery($query);
$subtotal = $oCon->getData();
unset($oCon);

$query = 'SELECT SUM(Valor) as Valor FROM Concepto_Liquidacion_Funcionario WHERE Valor<0 AND Id_Liquidacion_Funcionario='.$id;

$oCon= new consulta();
$oCon->setQuery($query);
$subtotal_deduccion = $oCon->getData();
unset($oCon);

$query = 'SELECT N.*, DATE(N.Fecha_Fin) as Fecha_Corte FROM Novedad N WHERE N.Id_Tipo_Novedad=1 AND N.Identificacion_Funcionario='.$id.' ORDER BY N.Id_Novedad DESC LIMIT 1';

$oCon= new consulta();
$oCon->setQuery($query);
$fecha_corte_vacaciones = $oCon->getData();
unset($oCon);

if($fecha_corte_vacaciones['Fecha_Corte']){
    $fecha_corte_vacaciones=$fecha_corte_vacaciones['Fecha_Corte'];
}else{
    $fecha_corte_vacaciones=$liquidacion['Fecha_Inicio_Contrato'];
}

function MesString($mes_index){
    global $meses;

    return  $meses[($mes_index-1)];
}
   $codigos ='
                      <h4 style="margin:5px 0 0 0;font-size:14px;line-height:14px;width:120px;">Liquidacion Funcionario</h4>
                      <h4 style="margin:5px 0 0 0;font-size:14px;line-height:14px;width:120px;">'.$liquidacion['Codigo'].'</h4>
        ';
        $contenido = '<table style="border:1px solid #cccccc;"  cellpadding="0" cellspacing="0">
            <tr style="width:590px;" >
                            <td  style="width:100px;font-size:10px;font-weight:bold;text-align:left; background:#ededed;border:1px solid #cccccc;padding:4px;padding-right:0;">Funcionario </td>
                            <td style="width:213px;font-size:10px;text-align:left;border:1px solid #cccccc;padding:4px;padding-right:0;">'.$liquidacion['Funcionario'].'</td>
                            <td  style="width:100px;font-size:10px;text-align:left;border:1px solid #cccccc;padding:4px;padding-right:0;">C.C.'.$liquidacion['Identificacion_Funcionario'].'</td>
                            <td   style="width:100px;font-size:10px;text-align:left;border:1px solid #cccccc;padding:4px;padding-right:0;background:#ededed;font-weight:bold;">Cargo</td>
                            <td   style="width:180px;font-size:10px;text-align:center;border:1px solid #cccccc;padding:4px;padding-right:2;">'.$liquidacion['Cargo'].' </td>
            
            </tr>
            <tr style="width:590px; " >
                            <td style="width:100px;font-size:10px;font-weight:bold;text-align:left;background:#ededed;border:1px solid #cccccc;padding:4px;padding-right:0;vertical-align:middle">Causa de Liquidación</td>
                            <td  colspan="4"  style="width:213px;font-size:10px;text-align:left;border:1px solid #cccccc;padding:4px;padding-right:0;vertical-align:middle">'.$liquidacion['Motivo'].'</td>
                            
            </tr>           
           
         
         
        </table>

        <table  style="font-size:10px;margin-top:10px;" cellpadding="0" cellspacing="0">
            <tr style="width:590px;" >
                            <td colspan="2"  style="width:150px;font-size:10px;font-weight:bold;text-align:center; background:#ededed;border:1px solid #cccccc;padding:4px;padding-right:0;">Periodo de Liquidación </td>
                            <td colspan="2"  style="width:150px;font-size:10px;font-weight:bold;text-align:center; background:#ededed;border:1px solid #cccccc;padding:4px;padding-right:0;">Salario Base de Liquidación </td>
            
            </tr>
            <tr style="width:590px; " >
                            <td  style="width:150px;font-size:10px;font-weight:bold;text-align:left;background:#ededed;border:1px solid #cccccc;padding:4px;padding-right:0;">Fecha Terminación Contrato</td>
                            <td   style="width:213px;font-size:10px;text-align:center;border:1px solid #cccccc;padding:4px;padding-right:0;">'.$liquidacion['Fecha_Fin_Contrato'].'</td>
                            <td  style=" vertical-align:middle; width:150px;font-size:10px;font-weight:bold;text-align:left;border:1px solid #cccccc;background:#ededed;padding:4px;padding-right:0;">Sueldo Basico</td>
                            <td style="width:188px;font-size:10px;text-align:right;border:1px solid #cccccc;padding:4px;padding-right:5;">$ '.number_format($liquidacion['Valor'],2,",",".").'  </td>       
            
            </tr>   
             <tr style="width:590px; " >
                            <td  style="width:150px;font-size:10px;font-weight:bold;text-align:left;background:#ededed;border:1px solid #cccccc;padding:4px;padding-right:0;">Fecha Inicio Contrato</td>
                            <td   style="width:213px;font-size:10px;text-align:center;border:1px solid #cccccc;padding:4px;padding-right:0;">'.$liquidacion['Fecha_Inicio_Contrato'].'</td>
                            <td  style=" vertical-align:middle; width:150px;font-size:10px;font-weight:bold;text-align:left;border:1px solid #cccccc;background:#ededed;padding:4px;padding-right:0;">Auxilio Transporte</td>
                            <td style="width:188px;font-size:10px;text-align:right;border:1px solid #cccccc;padding:4px;padding-right:5;"> $'.number_format($liquidacion['Auxilio'],2,",",".").'  </td>       
            
            </tr>             
            <tr style="width:590px; " >
            <td  style="width:150px;font-size:10px;font-weight:bold;text-align:left;background:#ededed;border:1px solid #cccccc;padding:4px;padding-right:0;">Dias Duración Contrato</td>
            <td   style="width:213px;font-size:10px;text-align:center;border:1px solid #cccccc;padding:4px;padding-right:0;">'.$liquidacion['Dias'].' Dias </td>
            <td  style=" vertical-align:middle; width:150px;font-size:10px;font-weight:bold;text-align:left;border:1px solid #cccccc;background:#ededed;padding:4px;padding-right:0;">Promedio Salario Variable</td>
            <td style="width:188px;font-size:10px;text-align:right;border:1px solid #cccccc;padding:4px;padding-right:5;">$ '.number_format(0,2,",","").' </td>       

            </tr>     
            <tr style="width:590px; " >
            <td  style="width:150px;font-size:10px;font-weight:bold;text-align:left;background:#ededed;border:1px solid #cccccc;padding:4px;padding-right:0;">Licencias no Remuneradas y Sanciones</td>
            <td   style="width:213px;font-size:10px;text-align:center;border:1px solid #cccccc;padding:4px;padding-right:0;"> 0 Dias </td>
            <td  style=" vertical-align:middle; width:150px;font-size:10px;font-weight:bold;text-align:left;border:1px solid #cccccc;background:#ededed;padding:4px;padding-right:0;">Total Base Liquidación </td>
            <td style="width:188px;font-size:10px;text-align:right;border:1px solid #cccccc;padding:4px;padding-right:5;">$ '.number_format($liquidacion['Base_Liquidacion'],2,",",".").' </td>       

            </tr>  
            <tr>
            <td  style="width:150px;font-size:10px;font-weight:bold;text-align:left;background:#ededed;border:1px solid #cccccc;padding:4px;padding-right:0;">Tiempo Total Laborado</td>
            <td   style="width:213px;font-size:10px;text-align:center;border:1px solid #cccccc;padding:4px;padding-right:0;">'.($liquidacion['Dias']).' Dias </td>
            </tr>   
            <tr>
            <td  style="width:150px;font-size:10px;font-weight:bold;text-align:left;background:#ededed;border:1px solid #cccccc;padding:4px;padding-right:0;">Fecha Inicio Liquidacion Prestaciones Sociales </td>
            <td   style="width:213px;font-size:10px;text-align:center;border:1px solid #cccccc;padding:4px;padding-right:0;vertical-align:middle">'.$liquidacion['Fecha_Inicio_Contrato'].' </td>
            </tr>   
         
         
        </table>


        <table style="font-size:10px;margin-top:10px;" cellpadding="0" cellspacing="0">
            <tr style="width:590px;" >
                            <td colspan="2"  style="width:100px;font-size:10px;font-weight:bold;text-align:center; background:#ededed;border:1px solid #cccccc;padding:4px;padding-right:0;">Prima </td>
                            <td colspan="2"  style="width:100px;font-size:10px;font-weight:bold;text-align:center; background:#ededed;border:1px solid #cccccc;padding:4px;padding-right:0;">Cesantias </td>
          
            
            </tr>   
            <tr style="width:590px; " >
            <td  style="width:150px;font-size:10px;font-weight:bold;text-align:left;background:#ededed;border:1px solid #cccccc;padding:4px;padding-right:0;">Fecha Liquidacion Prima</td>
            <td   style="width:213px;font-size:10px;text-align:center;border:1px solid #cccccc;padding:4px;padding-right:0;">'.$fecha.'  </td>
            <td  style=" vertical-align:middle; width:150px;font-size:10px;font-weight:bold;text-align:left;border:1px solid #cccccc;background:#ededed;padding:4px;padding-right:0;">Fecha Liquidacion Cesantias</td>
            <td style="width:198px;font-size:10px;;text-align:center;border:1px solid #cccccc;padding:4px;padding-right:0;">'.$fecha.'  </td>       

            </tr>      
            <tr style="width:590px; " >
            <td  style="width:150px;font-size:10px;font-weight:bold;text-align:left;background:#ededed;border:1px solid #cccccc;padding:4px;padding-right:0;"> Fecha de Corte Prima</td>
            <td   style="width:213px;font-size:10px;text-align:center;border:1px solid #cccccc;padding:4px;padding-right:0;">'.$liquidacion['Fecha'].'</td>
            <td  style=" vertical-align:middle; width:150px;font-size:10px;font-weight:bold;text-align:left;border:1px solid #cccccc;background:#ededed;padding:4px;padding-right:0;">Fecha de Corte de Cesantias  </td>
            <td style="width:198px;font-size:10px;;text-align:center;border:1px solid #cccccc;padding:4px;padding-right:0;">'.$liquidacion['Fecha'].'  </td>       

            </tr>    
            <tr style="width:590px; " >
            <td  style="width:150px;font-size:10px;font-weight:bold;text-align:left;background:#ededed;border:1px solid #cccccc;padding:4px;padding-right:0;"> Dias Prima</td>
            <td   style="width:213px;font-size:10px;text-align:center;border:1px solid #cccccc;padding:4px;padding-right:0;">'.$liquidacion['Dias_Prestaciones'].' Dias</td>
            <td  style=" vertical-align:middle; width:150px;font-size:10px;font-weight:bold;text-align:left;border:1px solid #cccccc;background:#ededed;padding:4px;padding-right:0;">Dias Cesantias  </td>
            <td style="width:198px;font-size:10px;;text-align:center;border:1px solid #cccccc;padding:4px;padding-right:0;">'.$liquidacion['Dias_Prestaciones'].' Dias </td>       

            </tr>        


            <tr style="width:590px;" >
                            <td colspan="2"  style="width:100px;font-size:10px;font-weight:bold;text-align:center; background:#ededed;border:1px solid #cccccc;padding:4px;padding-right:0;vertical-align:middle;">Vacaciones </td>
                            <td colspan="2"  style="width:100px;font-size:10px;font-weight:bold;text-align:center; background:#ededed;border:1px solid #cccccc;padding:4px;padding-right:0;vertical-align:middle;">Interes a las Cesantias </td>
          
            
            </tr>   
            <tr style="width:590px; " >
            <td  style="width:150px;font-size:10px;font-weight:bold;text-align:left;background:#ededed;border:1px solid #cccccc;padding:4px;padding-right:0;">Fecha Liquidacion Vacaciones</td>
            <td   style="width:213px;font-size:10px;text-align:center;border:1px solid #cccccc;padding:4px;padding-right:0;">'.$fecha_corte_vacaciones.'  </td>
            <td  style=" vertical-align:middle; width:150px;font-size:10px;font-weight:bold;text-align:left;border:1px solid #cccccc;background:#ededed;padding:4px;padding-right:0;">Fecha Liquidacion Intereses</td>
            <td style="width:198px;font-size:10px;;text-align:center;border:1px solid #cccccc;padding:4px;padding-right:0;">'.$fecha.'  </td>       

            </tr>      
            <tr style="width:590px; " >
            <td  style="width:150px;font-size:10px;font-weight:bold;text-align:left;background:#ededed;border:1px solid #cccccc;padding:4px;padding-right:0;"> Fecha de Corte Vacaciones</td>
            <td   style="width:213px;font-size:10px;text-align:center;border:1px solid #cccccc;padding:4px;padding-right:0;">'.$liquidacion['Fecha'].' </td>
            <td  style=" vertical-align:middle; width:150px;font-size:10px;font-weight:bold;text-align:left;border:1px solid #cccccc;background:#ededed;padding:4px;padding-right:0;">Fecha de Corte Intereses  </td>
            <td style="width:198px;font-size:10px;;text-align:center;border:1px solid #cccccc;padding:4px;padding-right:0;">'.$liquidacion['Fecha'].'  </td>       

            </tr>    
            <tr style="width:590px; " >
            <td  style="width:150px;font-size:10px;font-weight:bold;text-align:left;background:#ededed;border:1px solid #cccccc;padding:4px;padding-right:0;"> Total Dias Vacaciones </td>
            <td   style="width:213px;font-size:10px;text-align:center;border:1px solid #cccccc;padding:4px;padding-right:0;">'.$liquidacion['Vacaciones_Acumuladas'].' Dias</td>
            <td  style=" vertical-align:middle; width:150px;font-size:10px;font-weight:bold;text-align:left;border:1px solid #cccccc;background:#ededed;padding:4px;padding-right:0;">Dias Intereses  </td>
            <td style="width:198px;font-size:10px;;text-align:center;border:1px solid #cccccc;padding:4px;padding-right:0;">'.$liquidacion['Dias_Prestaciones'].' Dias </td>       

            </tr> 
            <tr style="width:590px;> 
            <td  style="width:150px;font-size:10px;font-weight:bold;text-align:left;background:#ededed;border:1px solid #cccccc;padding:4px;padding-right:0;">Dias Tomados de Vacaciones </td>
            <td   style="width:213px;font-size:10px;text-align:center;border:1px solid #cccccc;padding:4px;padding-right:0;">'.$dias_tomados.' Dias</td>
            </tr>      
            <tr style="width:590px;> 
            <td  style="width:150px;font-size:10px;font-weight:bold;text-align:left;background:#ededed;border:1px solid #cccccc;padding:4px;padding-right:0;"> Dias Pendientes </td>
            <td   style="width:213px;font-size:10px;text-align:center;border:1px solid #cccccc;padding:4px;padding-right:0;">'.$liquidacion['Dias_Prestaciones'].' Dias</td>
            </tr>      
    
    

           
         
         
        </table>
        
   
        <table style="font-size:10px;margin-top:15px;" cellpadding="0" cellspacing="0">
            <tr>
        
                <td colspan="2" style="text-align:center;width:580px;max-width:400px;font-weight:bold;border-top:1px solid #cccccc;border-left:1px solid #cccccc;border-right:1px solid #cccccc;background:#ededed;padding:4px 0;">
                    Resumen Liquidación
                </td>
               
                
            </tr>
            <tr>

                <td  style="text-align:center;width:580px;max-width:400px;font-weight:bold;border:1px solid #cccccc;background:#ededed;padding:4px 0;">
                    Concepto
                </td>
                    <td style="text-align:center;width:140px;max-width:100px;font-weight:bold;border:1px solid #cccccc;background:#ededed;padding:4px 0;">
                    Valor
                </td>
            </tr>
          ';
foreach ($conceptos as  $value) {
    $contenido.='   <tr >
                        <td style="width:580px;max-width:400px;border:1px solid #cccccc;padding:4px;" >
                       '.$value['Concepto'].'
                        </td>
                        <td style="text-align:right;width:140px;max-width:100px;border:1px solid #cccccc;padding:4px;">$ '.number_format($value['Valor'],2,".",",").'
                        </td>
                    </tr>';
}
 
$contenido.=' 
        <tr >
            <td style="width:580px;max-width:400px;border:1px solid #cccccc;padding:4px;background:#ededed;" >
            <b>Total Devengos</b>
            </td>
            <td style="text-align:right;width:140px;max-width:100px;border:1px solid #cccccc;padding:4px;background:#ededed;"><b>$ '.number_format($subtotal['Valor'],2,".",",").'</b>
            </td>
        </tr>';
        foreach ($deducciones as  $value) {
            $contenido.='   <tr >
                                <td style="width:580px;max-width:400px;border:1px solid #cccccc;padding:4px;" >
                               '.$value['Concepto'].'
                                </td>
                                <td style="text-align:right;width:140px;max-width:100px;border:1px solid #cccccc;padding:4px;">$ '.number_format($value['Valor'],2,".",",").'
                                </td>
                            </tr>';
        }
        
        $contenido.='
        <tr >
            <td style="width:580px;max-width:400px;border:1px solid #cccccc;padding:4px;" >
            Prestamos o Anticipos
            </td>
            <td style="text-align:right;width:140px;max-width:100px;border:1px solid #cccccc;padding:4px;">$ '.number_format(0,2,".",",").'
            </td>
        </tr>
        <tr >
            <td style="width:580px;max-width:400px;border:1px solid #cccccc;padding:4px;background:#ededed;" >
            <b>Total Deducciones</b>
            </td>
            <td style="text-align:right;width:140px;max-width:100px;border:1px solid #cccccc;padding:4px;background:#ededed;"><b>$ '.number_format($subtotal_deduccion['Valor'],2,".",",").'</b>
            </td>
        </tr>
        <tr>
        <td colspan="2" style="width:580px;max-width:400px;border:1px solid #cccccc;padding:4px;">

        </td>
        </tr>
        <tr >
            <td style="width:580px;max-width:400px;border:1px solid #cccccc;padding:4px; background:#ededed;" >
            <b>Valor Liquidacion </b>
            </td>
            <td style="text-align:right;width:140px;max-width:100px;border:1px solid #cccccc;padding:4px; background:#ededed;"><b>$ '.number_format($total['Valor'],2,".",",").'</b>
            </td>
        </tr>
        <tr>
            <td colspan="2" style="width:580px;max-width:400px;border:1px solid #cccccc;padding:4px; background:#ededed;" >
            <b> SE HACE EL PAGO POR LA SUMA DE  '.$letras.' PESOS M/CTE.  </b>
            </td>
        </tr>
        <tr>
            <td colspan="2" style="width:580px;max-width:400px;border:1px solid #cccccc;padding:4px; " >
            <b> SE HACE CONSTAR </b>
            </td>
        </tr>
        <tr>
            <td colspan="2" style="width:580px;max-width:400px;border:1px solid #cccccc;padding:4px; font-size:9px; " >
            1. Que el patrono ha incorporado en la presente liquidación los importes correspondientes a salarios, horas extras, descansos compensatorios, cesantías, vacaciones, prima de servicios, auxilio de transporte, y en sí, todo concepto   relacionado con salarios, prestaciones o indemnizaciones causadas al quedar extinguido el contrato de trabajo.
              </td>
        </tr>
      
        <tr>
            <td colspan="2" style="width:580px;max-width:400px;border:1px solid #cccccc;padding:4px; font-size:9px; " >
            2. Que con el pago del dinero anotado en la presente liquidación, queda transada cualquier diferencia relativa al contrato de trabajo extinguido, o a cualquier diferencia anterior. Por lo tanto, esta transacción tiene como efecto la terminaciónde las obligaciones provenientes de la relación laboral que existió entre PROH S.A y el trabajador, quienes declaran estar a paz y salvo por todo concepto.
        </td>
        </tr>

        </table>
        
        ';

        $contenido .='<table style="margin-top:10px;font-size:10px; cellpadding="0" cellspacing="0"">
        <tr>
        <td style="width:365px;border:1px solid #cccccc;padding-left:5px;">
        <br><br><br>  _______________________________________<br><br>
            '.$liquidacion["Funcionario"].'
        </td> 
        <td style="width:360px;border:1px solid #cccccc;padding-left:5px;">      
        <br><br><br> _____________________________________<br><br>
        Representante Legal
        
        </td>
        
        </tr>
        </table>';
/* FIN SWITCH*/

/* CABECERA GENERAL DE TODOS LOS ARCHIVOS PDF*/
$cabecera='<table style="" >
              <tbody>
                <tr>
                  <td style="width:70px;">
                    <img src="'.$_SERVER["DOCUMENT_ROOT"].'/IMAGENES/LOGOS/LogoProh.jpg" style="width:60px;" alt="Pro-H Software" />
                  </td>
                  <td class="td-header" style="width:510px;font-weight:thin;font-size:14px;line-height:20px;">
                    '.$config["Nombre_Empresa"].'<br> 
                    N.I.T.: '.$config["NIT"].'<br> 
                    '.$config["Direccion"].'<br> 
                    TEL: '.$config["Telefono"].'
                  </td>
                  <td style="width:150px;text-align:right">
                        '.$codigos.'
                  </td>
                  
                </tr>
              </tbody>
            </table><hr style="border:1px dotted #ccc;width:730px;">';
/* FIN CABECERA GENERAL DE TODOS LOS ARCHIVOS PDF*/

/* CONTENIDO GENERAL DEL ARCHIVO MEZCLANDO TODA LA INFORMACION*/
$content = '<page backtop="0mm" backbottom="0mm">
                <div class="page-content" >'.
                    $cabecera.
                    $contenido.'
                </div>
            </page>';
/* FIN CONTENIDO GENERAL DEL ARCHIVO MEZCLANDO TODA LA INFORMACION*/

try{
    /* CREO UN PDF CON LA INFORMACION COMPLETA PARA DESCARGAR*/
    $html2pdf = new HTML2PDF('P', 'A4', 'Es', true, 'UTF-8', array(5, 5, 5, 5));
    $html2pdf->writeHTML($content);
    $direc = $id.'.pdf'; // NOMBRE DEL ARCHIVO ES EL CODIGO DEL DOCUMENTO
    $html2pdf->Output($direc,''); // LA D SIGNIFICA DESCARGAR, 'F' SE PODRIA HACER PARA DEJAR EL ARCHIVO EN UNA CARPETA ESPECIFICA
}catch(HTML2PDF_exception $e) {
    echo $e;
    exit;
}

?>
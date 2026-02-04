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
$funcionario = ( isset( $_REQUEST['funcionario'] ) ? $_REQUEST['funcionario'] : '' );
$tipo = ( isset( $_REQUEST['tipo'] ) ? $_REQUEST['tipo'] : 'vista' );

/* FUNCIONES BASICAS */
function fecha($str)
{
	$parts = explode(" ",$str);
	$date = explode("-",$parts[0]);
	return $date[2] . "/". $date[1] ."/". $date[0];
}
/* FIN FUNCIONES BASICAS*/

ob_start(); // Se Inicializa el gestor de PDF

/* HOJA DE ESTILO PARA PDF*/
$style='<style>
.page-content{
width:750px;
text-align:justify;
word-wrap:break-word;
}
.row{
display:inlinie-block;
width:750px;
}
.td-header{
    font-size:15px;
    line-height: 20px;
}
.titular{
    font-size: 11px;
    text-transform: uppercase;
    margin-bottom: 0;
  }
</style>';
/* FIN HOJA DE ESTILO PARA PDF*/

// consulta para traer los datos del funcionario

$query='SELECT F.*, C.Nombre as Cargo, C.Funciones,
            --    (SELECT C.Nombre FROM Cargo C WHERE C.Id_Cargo=F.Id_Cargo) as Cargo, 
               CF.*, 
               CONCAT(F.Nombres," ",F.Apellidos) as Funcionario  FROM Funcionario F
        INNER JOIN Contrato_Funcionario CF ON F.Identificacion_Funcionario=CF.Identificacion_Funcionario 
        INNER JOIN Cargo C  ON C.Id_Cargo=F.Id_Cargo
        WHERE F.Identificacion_Funcionario='.$id.$funcionario;
$oCon= new consulta();
$oCon->setQuery($query);
$funcionario = $oCon->getData();
unset($oCon);

$numero = number_format($funcionario['Salario'], 0, '.','');
$letras = NumeroALetras::convertir($numero);




function obtenerFechaEnLetra($fecha){
   
    $num = date("j", strtotime($fecha));
    $anno = date("Y", strtotime($fecha));
    $mes = array('Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre');
    $mes = $mes[(date('m', strtotime($fecha))*1)-1];
    return $num.' de '.$mes.' de '.$anno;
}
 



/* HAGO UN SWITCH PARA TODOS LOS MODULOS QUE PUEDEN GENERAR PDF */

        

    $contenido = '<p style="margin-bottom:5px;font-weight:bold;text-align:center">CONTRATO DE TRABAJO A TÉRMINO FIJO INFERIOR A UN AÑO </p> <p style="margin-top:0;font-weight:bold;text-align:center">INFORMACIÓN DEL CONTRATO</p>';

    $contenido .= '<table>
    
    <tr>
        <td style="width:400px;">
        <table>
    
        <tr>
            <td style="width:300px;font-weight:bold">Nombre del Empleador:</td>
            <td style="width:300px;font-weight:bold">PRODUCTOS HOSPITALARIOS S.A
            </td>    
        </tr>
        <tr>
            <td style="width:300px">NIT.</td>
            <td style="width:300px">804.016.084-5</td>    
        </tr>
        <tr>
            <td style="width:300px">Domicilio del EMPLEADOR:</td>
            <td style="width:300px">CALLE 22 4 24-54 BARRIO ALARCON</td>    
        </tr>
        <tr>
            <td style="width:300px">Representante Legal:</td>
            <td style="width:300px">MARIELA RODRIGUEZ DE ARCINIEGAS</td>    
        </tr>
        <tr>
            <td style="width:300px">Documento de identidad:</td>
            <td style="width:300px">63.275.342 DE BUCARAMANGA</td>    
        </tr>
        
        </table>
        </td>    
    </tr>
    
    
    </table>
    
    <table style="margin-top:10px">
    
    <tr>
        <td style="width:400px;">
        <table>
    
        <tr>
            <td style="width:300px;font-weight:bold">Nombre del (la) Trabajador(a):</td>
            <td style="width:300px;font-weight:bold">'.$funcionario['Nombres'].' '.$funcionario['Apellidos'].'
            </td>    
        </tr>
        <tr>
            <td style="width:300px">Cedula de Ciudadanía:</td>
            <td style="width:300px">'.number_format($funcionario['Identificacion_Funcionario'],0,"",".").' </td>    
        </tr>
        <tr>
            <td style="width:300px">Dirección del (la) Trabajador(a):</td>
            <td style="width:300px">'.$funcionario['Direccion_Residencia'].'</td>    
        </tr>
        <tr>
            <td style="width:300px">Teléfono:</td>
            <td style="width:300px">'.$funcionario['Celular'].'</td>    
        </tr>
        <tr>
            <td style="width:300px">Lugar y fecha de Nacimiento:</td>
            <td style="width:300px">'.$funcionario['Lugar_Nacimiento'].', '.obtenerFechaEnLetra($funcionario['Fecha_Nacimiento']).'</td>    
        </tr>
        <tr>
            <td style="width:300px">Fecha de Iniciación del Contrato:</td>
            <td style="width:300px">'.$funcionario['Fecha_Inicio_Contrato'].'</td>    
        </tr>
        <tr>
            <td style="width:300px">Fecha terminación del Contrato:</td>
            <td style="width:300px">'.$funcionario['Fecha_Fin_Contrato'].'</td>    
        </tr>
        <tr>
            <td style="width:300px">Cargo del (la) Trabajador(a):</td>
            <td style="width:300px">'.$funcionario['Cargo'].'</td>    
        </tr>
        <tr>
            <td style="width:300px">Lugar donde desempaña el cargo:</td>
            <td style="width:300px">BUCARAMANGA</td>    
        </tr>
        <tr>
            <td style="width:300px"></td>
            <td style="width:300px"></td>    
        </tr>
        <tr>
            <td style="width:300px"></td>
            <td style="width:300px"></td>    
        </tr>
        <tr>
            <td style="width:300px;font-weight:bold">Modalidad:</td>
            <td style="width:300px">CONTRATO DE TRABAJO A TERMINO FIJO.</td>    
        </tr>
        
        </table>
        </td>    
    </tr>
    
    
    </table>
    ';
    $contenido2 = '
    
    <p>Las partes, previa y debidamente identificadas, de las calidades y condiciones descritas, acordamos la celebración del presente contrato de trabajo, conforme las siguientes,</p>

    <h5 style="text-align:center">FUNCIONES DEL CARGO</h5>
    <p>'.$funcionario['Funciones'].'.</p>

    <h5 style="text-align:center">CLAUSULAS</h5>

    <p><b>PRIMERA. OBJETO.</b> EL TRABAJADOR prestará en forma exclusiva sus servicios personales bajo la continuada dependencia y subordinación AL EMPLEADOR inicialmente en el cargo de <b>'.$funcionario['Cargo'].'</b> o enlos oficios que por razón de su formación, competencias, capacitación y necesidades del proceso o procedimientos sean necesarios realizar, sin que dichos cambios de oficios o cargos implique una desmejora o cambio de sus condiciones y obligaciones laborales.</p>

    <p><b>EL TRABAJADOR</b> se compromete a no prestar directa ni indirectamente servicios laborales a otros EMPLEADORES, ni a trabajar por cuenta propia en el mismo oficio, en las instalaciones de la EMPRESA y horarios laborales, durante la vigencia de este contrato.</p>

    <p><b>SEGUNDA. LUGAR DE TRABAJO.</b> El TRABAJADOR prestará sus servicios personales en la ciudad de <b>BUCARAMANGA</b> pero acepta realizar las labores, actividades o tareas que le señale EL EMPLEADOR, en otro lugar donde sea promovido o trasladado, en cualquier momento, reservándose además EL EMPLEADOR, la facultad de modificar turnos, horarios de trabajo, áreas, secciones o actividad a realizar cuando lo estime conveniente, sin desmejorarlo en su remuneración, y demás condiciones laborales, basados enel principio del (IUS VARIANDI). Cualquier modificación del lugar de trabajo, que signifique cambio de ciudad, se hará conforme al Código Sustantivo de Trabajo.</p>

    <p><b>TERCERA.ELEMENTOS	DE TRABAJO.</b>  EMPLEADOR:	Corresponde al empleador suministrar los elementos necesarios para el normal desempeño de las funciones del cargo contratado. TRABAJADOR: tiene el deber de proteger, cuidar y devolver en buen estado los elementos detrabajo, y utilizar dichos elementos en un uso exclusivo para el desempeño de las funciones del cargo contratado.</p>

    <p><b>CUARTA: REMUNERACION</b> Como contraprestación directa por los servicios que se obliga a prestar EL TRABAJADOR, recibirá una remuneración que equivaldrá inicialmente a la suma de '.$letras.' PESOS MCTE ($'.$funcionario['Salario'].') pagaderos quincenalmente, previa entrega del informe de actividades por parte del TRABAJADOR.	Así mismo, se entiende que en el salario convenido está incluido el valor del descanso dominical o festivo que tenga derecho EL TRABAJADOR.</p>

    <p><b>QUINTA. PAGOS NO PRESTACIONALES.</b> En concordancia conel artículo 128 del Código Sustantivo del Trabajo, subrogado porel artículo 15 de la Ley 50 de 1990, EL EMPLEADOR y EL   </p>

    ';
// echo $contenido2;exit;

    $contenido3 = '
    <p> TRABAJADOR acuerdan expresamente que no constituyen salario,  ni factor salarial para ningún efecto, en dinero o en especie, pagos o reconocimientos que se realizan por los conceptos que a continuación se enuncian, ya que se entiende que dichos pagos constituyen un medio para facilitar la prestación del servicio y para desempeñar a cabalidad las funciones y no tienen el carácter de retribución del trabajo.</p>

    <ol>
        <li>Las sumas que ocasionalmente y por mera liberalidad se le cancelen a EL TRABAJADOR como primas gratificaciones, o bonificaciones ocasionales, las propinas, los elementos de trabajo, la indemnización por terminación del contrato de trabajo.</li>
        <li>Lo que recibe EL TRABAJADOR en dinero o en especie no para su beneficio o enriquecimiento personal sino para desempeñar a cabalidad sus funciones, como gastos de representación, auxilio de medio de transporte, auxilio por telecomunicaciones y celulares, vivienda, auxilios por elementos
        de trabajo, auxilio por internet u otros semejantes.</li>
        <li>Los viáticos ocasionales o accidentales. Los viáticos permanentes no son salario en la parte destinada a pagar los gastos de transporte y gastos de representación.</li>
        <li>Los beneficios o auxilios habituales u ocasionales otorgados en forma extralegal por LA EMPLEADOR, tales como las primas extralegales, de vacaciones, de servicios, de antiguedad o de navidad etc.</li>
        <li>Los pagos o suministros en especie como alimentación, alojamiento, vestuario, gastos de gasolina, gastos odontológicos o médicos.</li>
    </ol>

    <p><b>SEXTA. COMPETENCIA. EL TRABAJADOR</b> declara que reúne las condiciones de aptitud necesarias para el desempeño de su oficio, que las informaciones consignadas en su solicitud de empleo son verídicas y que conoce el Reglamento Interno de Trabajo y el de Higiene y Seguridad Industrial, cuyas normas se entienden incorporadas en el presente contrato y que el trabajador se compromete a lo siguiente: <b>1.</b> Asistir a las capacitaciones en relación con la seguridad y salud en el trabajo programadas por la empresa. <b>2.</b> Usar los elementos de protección personal que le ha sido suministrado para la ejecución segura de su trabajo. <b>3.</b> Reportar los incidentes y accidentes de trabajo que se presenten. <b>4.</b> Informar a sujefe inmediato, encargado de salud ocupacional o representante del COPASST acerca de condiciones, prácticas y comportamientos que puedan generar riesgo de accidente y/o enfermedad laboral. <b>5.</b> Suministrar información clara, veraz y completa sobre su estado de salud. 6. Procurar el cuidado integral de su salud. <b>6.</b> Participar en la prevención de riesgos profesionales a través de los Comités Paritarios de Salud Ocupacional o Vigías Ocupacionales. <b>7.</b> Cumplir las normas, reglamentos e instrucciones del Sistema de Gestión de la Seguridad y Salud en el Trabajo SG-SST de la empresa y asistir periódicamente a los programas de promoción y prevención adelantados por las Administradoras de Riesgos Laborales. <b>8.</b> Colaborar y velar por el cumplimiento de las obligaciones contraídas por la empresa en relación con la Seguridad y Salud en el Trabajo. <b>9.</b> Participar en la mejora del sistema de gestión en seguridad y salud en el trabajo de la empresa.</p>

    <p><b>SEPTIMA. JORNADA DE TRABAJO.</b> El trabajador se obliga a laborar la jornada ordinaria en los turnos “ y dentro de las horas señaladas por el empleador, pudiendo hacer éste ajustes o cambios de horario cuando lo estime conveniente. Por el acuerdo expreso o tácito de las partes, podrán repartirse las horas jornada ordinaria de la forma prevista en el artículo 164 del Código Sustantivo del Trabajo, modificado por el artículo 23 de la Ley 50 de 1990, teniendo en cuenta que los tiempos de descanso entre las secciones de la jornada no se computan dentro de la misma, según el artículo 167 ibídem.</p>

    <p><b>OCTAVA: TRABAJO SUPLEMENTARIO.</b> El trabajo suplementario o en horas extras, así como todo trabajo en domingo o festivo en los que deba concederse descanso, será remunerado conformea la Ley, al igual que los respectivos recargos nocturnos. Es de advertir que dicho trabajo debe ser autorizado por el empleador o sus representantes, para efecto de su reconocimiento y pago.</p>

     <p><b>NOVENA. DURACIÓN DEL CONTRATO.</b> El presente contrato tendrá un término de duración de TRES meses, el cual iniciará su vigencia a partir del día '.obtenerFechaEnLetra($funcionario['Fecha_Inicio_Contrato']).', pero podrá darse por terminado por cualquiera de las partes, cumpliendo con las exigencias legales al respecto. <b>PARAGRAFO:</b> Si la duración del contrato fuere superior a treinta días e inferior a un año, se entenderá por renovado por un término inicial al pactado, si antes de la fecha del vencimiento ninguna de las partes avisare por escrito la terminación de no prorrogarlo, con una antelación no inferior a treinta días.<b>PARAGRAFO SEGUNDO:</b> De cualquier manera, el tiempo de ejecución del presente contrato estará sujeto a la vigencia del contrato suscrito entre Productos Hospitalarios S.A. y MEDIMAS; por lo quesi llegare a darse terminación unilateral anticipada del contrato principal en mención, el contrato de trabajo a término fijo también terminara sin tener en cuenta la fecha inicialmente pactada entre trabajador y empleador, toda vez que este contrato nace única y exclusivamente de las necesidades derivadas del contrato suscrito con MEDIMAS.</p>

    <p><b>DECIMA. PERIODO DE PRUEBA.</b> Las partes acuerdan un periodo de dieciocho (18) días, que no es superior a la quinta parte del término inicial de este contrato ni excede dos meses. En caso de prorrogas o nuevo contrato entre las partes se entenderá que no hay nuevo periodo de prueba. Durante este
    periodo tanto el empleador como el trabajador, podrán terminar el contrato en cualquier momento en forma unilateral, de conformidad con el artículo 78 del Código Sustantivo del Trabajo, modificado porel artículo 7" de la ley 50 de 1990.</p>    
    ';

    $contenido4 = '
    
    <p><b>DECIMA PRIMERA. INVENCIONES:</b> Las invenciones o descubrimientos realizados por el trabajador contratado para investigar pertenecen al empleador, de conformidad conel artículo 539 del Código de Comercio, así como el artículo 20 y concordantes de la ley 23 de 1982 sobre derechos de autor. En cualquier otro caso el invento pertenece al trabajador, salvo cuando éste no haya sido contratado para investigar y realice la invención mediante datos o medios conocidos o utilizados en razón de la labor desempeñada, evento en el cual el trabajador, tendrá derecho a una compensación que sefijará dé acuerdo con el monto del salario, la importancia del invento o descubrimiento, el beneficio que reporte al empleador u otros factores similares.</p>

    <p><b>DECIMA SEGUNDA. OBLIGACIONES GENERALES Y PROHIBICIONES ESPECIALES DEL
    TRABAJADOR.</b> El contrato de trabajo que se suscribe supone siempre del TRABAJADOR frente AL EMPLEADOR un compromiso de respeto, obediencia y fidelidad hacia él, basado dicho compromiso en la buena fe durante la ejecución de las labores. Las obligaciones generales están consagradas en los artículos 58 y 60 del Código Sustantivo del Trabajo además de las disposiciones contenidas en el Reglamento Interno de Trabajo, en el reglamento de Higiene y Seguridad industrial, en las normas y procedimientos establecidos por EL EMPLEADOR y en las demás regulaciones especiales vigentes en LA EMPRESA.</p>

    <p>Las obligaciones especiales para EL TRABAJADOR son las siguientes:</p>

    <ol>
        <li>Incorporar su capacidad normal de trabajo en forma exclusiva, en el desempeño de sus funciones y actividades propias del oficio de '.$funcionario['Cargo'].' y en las labores anexas, similares o complementarias del mismo, de acuerdo con los reglamentos, órdenes e instrucciones que reciba de alguno de los representantes del EMPLEADOR, observando la diligencia, el cuidado y responsabilidad necesarias en la ejecución de las actividades asignadas.</li>
        <li>Presentarse a los itinerarios indicados e instrucciones que de modo particular le imparta el patrono para el desempeño de sus funciones.</li>
        <li>Cumplir los requisitos del EMPLEADOR para la elaboración de los pedidos a los clientes.</li>
        <li>Laborar la jornada ordinaria en los turnos continuos o discontinuos y dentro de las horas que le asigne EL EMPLEADOR, pudiendo este ordenar los cambios o ajustes que sean necesarios para el adecuado funcionamiento de las actividades y labores, de cara a las necesidades de los clientes.</li>
        <li>Terminada la relación de trabajo, deberá efectuar la entrega del puesto de trabajo, los elementos suministrados por EL EMPLEADOR, los informes relacionados con el desempeño de sus funciones, el avance y estado de los procesos bajo su responsabilidad.</li>
    </ol>

    <p><b>PARAGRAFO:</b> El incumplimiento de las obligaciones especiales para EL TRABAJADOR es justa causa de terminación del contrato que las partes califican como graves.</p>

    <p style="font-weight:bold;text-decoration:underline">PROHIBICIONES DEL TRABAJADOR:</p>

    <ol>
        <li>Ejecutar actividades diferentes a las propias de su oficio en horas de trabajo, para terceros ya fueren remuneradas o no, o para su provecho personal.</li>
        <li>Pedir o recibir dinero de los clientes del EMPLEADOR y darles un destino diferente a ellos o no entregarlo en su debida oportunidad enla oficina del EMPLEADOR.</li>
        <li>Todo acto de violencia, deslealtad, injuria, malos tratos o grave indisciplina en que incurra EL
        TRABAJADOR en sus labores contra el EMPLEADOR, el personal directivo, sus compañeros de trabajo o sus superiores, clientes o proveedores.</li>
        <li>Losretrasos reiterados en la iniciación de la jornada de trabajo sin una justa causa que lo amerite. La inasistencia a laborar sin una excusa suficiente quelo justifique.</li>
        <li>No desempeñar labor alguna ni ejercer otra actividad fuera de las horas de trabajo al servicio de otro empleador, que pueda afectar o poner en peligro su seguridad, su salud o su descanso, e igualmente a no trabajar por cuenta propia en el mismo oficio.</li>
    </ol>

    <p><b>PARAGRAFO.</b> La violación de las prohibiciones constituye una justa causa de terminación del contrato quelas partes califican como graves.</p>

    <p><b>DECIMA TERCERA. TERMINACION DEL CONTRATO POR JUSTA CAUSA.</b> Además de las previstas en el Código Sustantivo de Trabajo o en las disposiciones que sobre la materia se dicten en el futuro, o en el Reglamento Interno de Trabajo, o en el Reglamento de Higiene y Seguridad Industrial del EMPLEADOR, constituyen justas causas para dar por terminado el contrato de trabajo por parte del EMPLEADOR, las cuales las partes califican como graves: a) La violación por parte del TRABAJADOR de cualquiera de sus obligaciones legales, contractuales o reglamentarias previstas en el Reglamento Interno de Trabajo, así mismo aquellas que se encuentran en el cargo a desempeñar;	b) La no asistencia puntual al trabajo, sin excusa suficiente a juicio del EMPLEADOR; c) La ejecución por parte del TRABAJADOR de labores remuneradas	al servicio de terceros sin autorización del EMPLEADOR; d) La revelación de secretos y datos reservados de EL EMPLEADOR; e) Presentarse a laborar en estado de embriaguez o bajo la influencia de narcóticos o sustancias psicoactivas, o el consumo delos mismos en el sitio de trabajo, aun porla primera vez; f) El hecho que el TRABAJADOR abandone el sitio de trabajo sin permiso de sus superiores; g) La no asistencia a una sesión completa de la jornada de trabajo, o más, sin excusa suficiente a juicio del EMPLEADOR, salvo fuerza mayor o caso fortuito; h) El negarse a desempeñar una labor inherente, conexa o complementaria de sus funciones habituales; i) Incumplir con las políticas contenidas en el Código de Conducta y Políticas Corporativas del EMPLEADOR, o documentos que lo adicionen, sustituyan o complementen; j) Exigir o insinuar a los clientes, proveedores o compradores de EL EMPLEADOR la entrega de cualquier clase de dádivas, valores o especies para el Trabajador o para terceros; k) Aprovecharse en beneficio propio o de terceros de dineros, valores, recursos o insumos del EMPLEADOR; |!) Incurrir en alguna conducta de las previstas en el artículo 7 de la Ley 1010 de 2006, catalogadas de acoso laboral; m) Omitir cualquier información de la que tenga conocimiento frente a lesiones e incidentes incurridos en el desarrollo de la labor; n) Ser accionista, socio, asociado o titular de cualquier forma de partes de capital de sociedades que puedan eventualmente ser competidoras del EMPLEADOR, situación que deberá informar de manera inmediata al EMPLEADOR;  o) Incumplir cualquiera de las obligaciones mencionadas enla cláusula primera del presente contrato.</p>

    <p><b>DECIMA CUARTA. CAUSALES DE TERMINACIÓN DE LA RELACIÓN LABORAL POR PARTE DEL TRABAJADOR.</b> Sin perjuicio de lo establecido en la ley, en el Reglamento Interno de Trabajo, EL TRABAJADOR puede dar por terminado por justa causa la relación contractual con su empleador por las causas previstas en el artículo 7 del Decreto 2351 de 1965 ordinal B. Así mismo podrá darlo por terminado en forma unilateral de acuerdo a lo establecido en la ley 789 de 2002 articulo 28 y en la ley 50 de 1990 articulo 5 numeral 1 ordinal c y cuando así lo estime conveniente EL TRABAJADOR por renuncia presentada en cualquier momento, sin que hubiere lugar a alguna indemnización a menos que EL TRABAJADOR haya sido enviado a estudiar o a capacitarse por cuenta DEL EMPLEADOR o con el permiso o de él y después de terminado sus estudios o capacitaciones presente renuncia. En este caso deberá pagar la indemnización que determine EL EMPLEADOR con la cual se resarcirán los perjuicios que dicho acto le ocasiono al EMPLEADOR. <b>PARAGRAFO.</b> Sí EL TRABAJADOR presenta su renuncia al cargo, deberá prever y radicar el correspondiente documento enviado por el medio más expedido a Gerencia o Coordinación de Recursos, en un término no inferior a cinco (5) días hábiles, de tal forma que EL EMPLEADOR disponga de un funcionario para realizar recibido del puesto de trabajo y realizar el empalme que contenga la entrega o devolución de elementos suministrados, equipos y demás; así como, de los informes y estado actual de todos los procesos bajo su responsabilidad.</p>

    <p><b>DECIMA QUINTA. CONFIDENCIALIDAD.	EL TRABAJADOR.</b> se obliga a desempeñar el cargo con lealtad, buena fe y fidelidad a favor del EMPLEADOR y por tanto adquiere la obligación de que todos los asuntos que conozca directa o indirectamente por razón de su cargo o por razón de estar vinculado a LA EMPRESA, serán manejados como reservados y por tal motivo, no podrán ser divulgados o revelados, por ningún medio ni a terceros ni a personal vinculado a LA EMPRESA. Tiene especial carácter de reservado todo lo que tenga que ver conla situación financiera del EMPLEADOR, los inventos	y descubrimientos, su producción, procesos operacionales, industriales,	financieros, administrativos, técnicos y comerciales. La violación de esta obligación se califica como falta grave y constituye por consiguiente justa causa para dar por terminado en forma unilateral el contrato de trabajo por parte del EMPLEADOR, sin perjuicio de las demás acciones legales a que haya lugar. Si terminado el contrato EL TRABAJADOR hiciere uso de esta información podrá dar lugar a que EL EMPLEADOR inicie las acciones legales pertinentes.</p>

    <p><b>DECIMA SEXTA. MOVILIDAD.</b> Las partes podrán convenir que el trabajo se preste en lugar distinto al inicialmente contratado, siempre que tales traslados no desmejoren las condiciones laborales o de remuneración del trabajador, o impliquen perjuicios para él. Los gastos que se originen con el traslado serán cubiertos por el empleador de conformidad con el numeral 8* del artículo 57 del Código Sustantivo del Trabajo. El trabajador se obliga a aceptar los cambios de oficio que decida el empleador dentro de su poder subordinante, siempre que se respeten las condiciones laborales del trabajador y no se le
    causen perjuicios. Todo ello sin que se afecte el honor, la dignidad y los derechos mínimos del trabajador, de conformidad con el artículo 23 del Código Sustantivo del Trabajo, modificado por el artículo 1” de la Ley 50 de 1990.</p>

    <p><b>DECIMA SEPTIMA. AUTORIZACIÓN	PARA EL TRATAMIENTO	DE DATOS PERSONALES.</b> El Trabajador autoriza de manera voluntaria el tratamiento de sus datos personales a EL EMPLEADOR para las siguientes finalidades:	control de horario, gestión de nómina y de personal, pago de prestaciones sociales, prevención de riesgos laborales, promoción de bienestar laboral, desarrollo de capacitaciones, registro histórico laboral, afiliación a la seguridad social integral, ejecución de auditorías, reporte de cumplimiento de obligaciones laborales, administrativas, tributarias y en general, para el tratamiento de todas las actividades derivadas de la relación laboral. Así mismo, EL TRABAJADOR autoriza de manera voluntaria el tratamiento de sus datos personales sensibles, como la huella dactilar, la firma, su imagen en formato de fotografía y video, para finalidades relativas a la seguridad de los bienes y de las personas al interior de las instalaciones y para la ejecución de todas las obligaciones y actividades de EL EMPLEADOR. EL TRABAJADOR tiene derecho a conocer, actualizar y rectificar la información que EL EMPLEADOR tiene sobre él, así como realizar consultas o reclamos relativos al manejo de información, de acuerdo al procedimiento establecido en la política de tratamiento de datos
    personales, previamente conocidos por EL TRABAJADOR y disponibles en WWW.PROHSA.COM <b>PÁRAGRAFO.</b> El Trabajador autoriza el tratamiento de datos personales de sus beneficiarios, ya sean mayores o menores de edad, conla finalidad de afiliación al sistema de seguridad social integral, así como para todos y cada uno de los beneficios que están establecidos y que se establezcan por EL EMPLEADOR.</p>

    <p><b>DECIMA OCTAVA. CIRCULACIÓN DE LOS DATOS PERSONALES.</b> El Trabajador de acuerdo a la facultad que le concede el literal a) del artículo 26 de la Ley 1581 de 2012, autoriza de manera expresa e inequívoca a El Empleador para transmitir sus datos personales a los siguientes terceros, qué en tal virtud, asumen la calidad de Encargados: Administradora de Riesgos Laborales, Entidades Promotoras de Salud, Cajas de Compensación, DIAN, Secretaría de Hacienda, Entidades Financieras, y/o con cualquier autoridad administrativa y/o judicial legalmente facultada, con la finalidad de cumplir obligaciones laborales de El Empleador para con El Empleado.</p>

    <p><b>DECIMA NOVENA. CUMPLIMIENTO DE POLÍTICAS Y MANUALES RELATIVOS A LA PROTECCIÓN DE DATOS PERSONALES.</b> El Trabajador deberá observar y cumplir la Política de Tratamiento de Datos Personales, el Manual Interno de Políticas y Procedimientos vigentes en la EMPLEADOR, y los demás documentos que los complementen o modifiquen; sus actuaciones serán conforme a dichos instrumentos. Las partes declaran y aceptan que el incumplimiento de la Política de Tratamiento de Datos Personales, es calificada como una falta grave en los términos del numeral 6 del literal a) del artículo 7 del Decreto 2351 de 1965, norma que subrogó el artículo 62 del C. S del T.</p>

    <p><b>VIGESIMA. EFECTOS.</b> El presente contrato reemplaza en su integridad y deja sin efecto alguno cualquier otro contrato verbal o escrito celebrado Entre las partes con anterioridad. Las modificaciones que se acuerden al presente contrato se anotaran a continuación de su texto o, en documeto adicional que hará parte integral de éste.</p>

    <p>Para constancia se firma el presente contrato en dos ejemplares, uno para EL EMPLEADOR y otro para
    EL TRABAJADOR, en la ciudad de <b>BUCARAMANGA</b>, a los 14 de diciembre de 2018</p>

    ';

    $contenido5 = '
    
    <table>
    
    <tr>
        <td style="width:400px;padding-left:10px">
        <table>
    
        <tr>
            <td style="width:300px;font-weight:bold">Empleador</td>
            <td style="width:300px;font-weight:bold">Trabajador</td>    
        </tr>
        
        </table>
        </td>    
    </tr>
    
    
    </table>
    
    <table style="margin-top:50px;margin-bottom:0px">
    
    <tr>
        <td style="width:400px;padding-left:10px">
        <table>
        <tr>
            <td style="width:300px;font-weight:bold">MARIELA RODRIGUEZ DE ARCINIEGAS</td>
            <td style="width:300px;font-weight:bold">'.$funcionario['Funcionario'].'</td>    
        </tr>
        <tr>
            <td style="width:300px;font-weight:bold">C.C 63.275.342</td>
            <td style="width:300px;font-weight:bold">C.C '.number_format($funcionario['Identificacion_Funcionario'],0,"",".").' </td>    
        </tr>
        
        </table>
        </td>    
    </tr>
    
    
    </table>
    ';

/* CONTENIDO GENERAL DEL ARCHIVO MEZCLANDO TODA LA INFORMACION*/
$content = '<page backtop="0mm" backbottom="0mm">
                <div class="page-content" style="text-align:justify;  word-wrap:break-word;">'.
                    $contenido.$contenido2.$contenido3.$contenido4.$contenido5.'
                </div>
            </page>';
/* FIN CONTENIDO GENERAL DEL ARCHIVO MEZCLANDO TODA LA INFORMACION*/

try{
    /* CREO UN PDF CON LA INFORMACION COMPLETA PARA DESCARGAR*/
    $html2pdf = new HTML2PDF('P', 'A4', 'Es', true, 'UTF-8', array(20, 20, 20, 20));
    $html2pdf->writeHTML($content);
    $direc = 'Contrato_Laboral.pdf'; // NOMBRE DEL ARCHIVO ES EL CODIGO DEL DOCUMENTO
    $html2pdf->Output($direc); // LA D SIGNIFICA DESCARGAR, 'F' SE PODRIA HACER PARA DEJAR EL ARCHIVO EN UNA CARPETA ESPECIFICA
    
}catch(HTML2PDF_exception $e) {
    echo $e;
    exit;
}

?>
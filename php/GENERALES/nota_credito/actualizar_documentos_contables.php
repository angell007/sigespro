<?php 
// SCRIPT PARA UNIR CÓDIGOS POR FECHAS PARA AMBOS TIPOS DE NOTAS


include_once('../../../class/class.complex.php');
include_once('../../../class/class.consulta.php');

$cods = getCods();



foreach ($cods as $cod){

    $query = 'Select   D.Codigo AS Documento, C.Id_Cuenta_Documento_Contable, C.Documento as CodigoActual 
        FROM Cuenta_Documento_Contable C
        INNER JOIN Documento_Contable D ON D.Id_Documento_Contable = C.Id_Documento_Contable 
    WHERE C.Documento = "'.$cod['Actual'].'" AND Date(D.Fecha_Registro) <= "2020-12-31"  
           
    ';
    $oCon = new consulta();
    $oCon->setQuery($query);
    $oCon->setTipo('Multiple');
    $docs = $oCon->getData();
    unset($oCon);

    foreach ( $docs as $doc){
        echo '<br> '.$doc['Documento'].' '.$doc['CodigoActual'].' '.$cod['Nuevo'].'<br>';
        $query = 'UPDATE Cuenta_Documento_Contable SET Documento = "'.$cod['Nuevo'] .'"
                    WHERE Id_Cuenta_Documento_Contable ='.$doc['Id_Cuenta_Documento_Contable'];

        echo '<br>'.$query;
        $oCon = new consulta();
        $oCon->setQuery($query);
        $data = $oCon->createData();
        unset($oCon);

        $query = 'UPDATE Movimiento_Contable SET Documento  = "'.$cod['Nuevo'].'"
                    WHERE Numero_Comprobante = "'.$doc['Documento'].'"
                    AND Documento = "'.$doc['CodigoActual'].'"';
        echo '<br>'.$query;
        $oCon = new consulta();
        $oCon->setQuery($query);
        $data = $oCon->createData();
        unset($oCon);
    }
    
}

exit;




function getCods (){
    return [ 
        ['Actual' => "NCDFE1335",
        'Nuevo' => "NC394"],
    
        ['Actual' => "NC394",
        'Nuevo' => "NC395"],
    
        ['Actual' => "NC395",
        'Nuevo' => "NC396"],
    
        ['Actual' => "NCDFE1336",
        'Nuevo' => "NC397"],
    
        ['Actual' => "NC396",
        'Nuevo' => "NC398"],
    
        ['Actual' => "NC397",
        'Nuevo' => "NC399"],
    
        ['Actual' => "NC398",
        'Nuevo' => "NC400"],
    
        ['Actual' => "NC399",
        'Nuevo' => "NC401"],
    
        ['Actual' => "NC400",
        'Nuevo' => "NC402"],
    
        ['Actual' => "NC401",
        'Nuevo' => "NC403"],
    
        ['Actual' => "NC402",
        'Nuevo' => "NC404"],
    
        ['Actual' => "NC403",
        'Nuevo' => "NC405"],
    
        ['Actual' => "NC404",
        'Nuevo' => "NC406"],
    
        ['Actual' => "NC405",
        'Nuevo' => "NC407"],
    
        ['Actual' => "NC406",
        'Nuevo' => "NC408"],
    
        ['Actual' => "NCDFE1",
        'Nuevo' => "NC409"],
    
        ['Actual' => "NCDFE2",
        'Nuevo' => "NC410"],
    
        ['Actual' => "NCDFE3",
        'Nuevo' => "NC411"],
    
        ['Actual' => "NCDFE4",
        'Nuevo' => "NC412"],
    
        ['Actual' => "NCDFE5",
        'Nuevo' => "NC413"],
    
        ['Actual' => "NCDFE6",
        'Nuevo' => "NC414"],
    
        ['Actual' => "NCDFE7",
        'Nuevo' => "NC415"],
    
        ['Actual' => "NCDFE8",
        'Nuevo' => "NC416"],
    
        ['Actual' => "NCDFE9",
        'Nuevo' => "NC417"],
    
        ['Actual' => "NCDFE10",
        'Nuevo' => "NC418"],
    
        ['Actual' => "NCDFE11",
        'Nuevo' => "NC419"],
    
        ['Actual' => "NCDFE12",
        'Nuevo' => "NC420"],
    
        ['Actual' => "NCDFE13",
        'Nuevo' => "NC421"],
    
        ['Actual' => "NCDFE14",
        'Nuevo' => "NC422"],
    
        ['Actual' => "NCDFE15",
        'Nuevo' => "NC423"],
    
        ['Actual' => "NCDFE16",
        'Nuevo' => "NC424"],
    
        ['Actual' => "NCDFE17",
        'Nuevo' => "NC425"],
    
        ['Actual' => "NCDFE18",
        'Nuevo' => "NC426"],
    
        ['Actual' => "NCDFE19",
        'Nuevo' => "NC427"],
    
        ['Actual' => "NCDFE20",
        'Nuevo' => "NC428"],
    
        ['Actual' => "NCDFE21",
        'Nuevo' => "NC429"],
    
        ['Actual' => "NCDFE22",
        'Nuevo' => "NC430"],
    
        ['Actual' => "NCDFE23",
        'Nuevo' => "NC431"],
    
        ['Actual' => "NCDFE24",
        'Nuevo' => "NC432"],
    
        ['Actual' => "NCDFE25",
        'Nuevo' => "NC433"],
    
        ['Actual' => "NCDFE26",
        'Nuevo' => "NC434"],
    
        ['Actual' => "NCDFE27",
        'Nuevo' => "NC435"],
    
        ['Actual' => "NCDFE28",
        'Nuevo' => "NC436"],
    
        ['Actual' => "NCDFE29",
        'Nuevo' => "NC437"],
    
        ['Actual' => "NCDFE30",
        'Nuevo' => "NC438"],
    
        ['Actual' => "NCDFE31",
        'Nuevo' => "NC439"],
    
        ['Actual' => "NCDFE32",
        'Nuevo' => "NC440"],
    
        ['Actual' => "NCDFE33",
        'Nuevo' => "NC441"],
    
        ['Actual' => "NCDFE34",
        'Nuevo' => "NC442"],
    
        ['Actual' => "NCDFE35",
        'Nuevo' => "NC443"],
    
        ['Actual' => "NCDFE36",
        'Nuevo' => "NC444"],
    
        ['Actual' => "NCDFE37",
        'Nuevo' => "NC445"],
    
        ['Actual' => "NCDFE38",
        'Nuevo' => "NC446"],
    
        ['Actual' => "NCDFE39",
        'Nuevo' => "NC447"],
    
        ['Actual' => "NCDFE40",
        'Nuevo' => "NC448"],
    
        ['Actual' => "NCDFE41",
        'Nuevo' => "NC449"],
    
        ['Actual' => "NCDFE42",
        'Nuevo' => "NC450"],
    
        ['Actual' => "NCDFE43",
        'Nuevo' => "NC451"],
    
        ['Actual' => "NCDFE44",
        'Nuevo' => "NC452"],
    
        ['Actual' => "NCDFE45",
        'Nuevo' => "NC453"],
    
        ['Actual' => "NCDFE46",
        'Nuevo' => "NC454"],
    
        ['Actual' => "NCDFE47",
        'Nuevo' => "NC455"],
    
        ['Actual' => "NCDFE48",
        'Nuevo' => "NC456"],
    
        ['Actual' => "NCDFE49",
        'Nuevo' => "NC457"],
    
        ['Actual' => "NCDFE50",
        'Nuevo' => "NC458"],
    
        ['Actual' => "NCDFE51",
        'Nuevo' => "NC459"],
    
        ['Actual' => "NCDFE52",
        'Nuevo' => "NC460"],
    
        ['Actual' => "NCDFE53",
        'Nuevo' => "NC461"],
    
        ['Actual' => "NCDFE54",
        'Nuevo' => "NC462"],
    
        ['Actual' => "NCDFE55",
        'Nuevo' => "NC463"],
    
        ['Actual' => "NCDFE56",
        'Nuevo' => "NC464"],
    
        ['Actual' => "NCDFE57",
        'Nuevo' => "NC465"],
    
        ['Actual' => "NCDFE58",
        'Nuevo' => "NC466"],
    
        ['Actual' => "NCDFE59",
        'Nuevo' => "NC467"],
    
        ['Actual' => "NCDFE60",
        'Nuevo' => "NC468"],
    
        ['Actual' => "NCDFE61",
        'Nuevo' => "NC469"],
    
        ['Actual' => "NCDFE62",
        'Nuevo' => "NC470"],
    
        ['Actual' => "NCDFE63",
        'Nuevo' => "NC471"],
    
        ['Actual' => "NCDFE64",
        'Nuevo' => "NC472"],
    
        ['Actual' => "NCDFE65",
        'Nuevo' => "NC473"],
    
        ['Actual' => "NCDFE66",
        'Nuevo' => "NC474"],
    
        ['Actual' => "NCDFE67",
        'Nuevo' => "NC475"],
    
        ['Actual' => "NCDFE68",
        'Nuevo' => "NC476"],
    
        ['Actual' => "NCDFE69",
        'Nuevo' => "NC477"],
    
        ['Actual' => "NCDFE70",
        'Nuevo' => "NC478"],
    
        ['Actual' => "NCDFE71",
        'Nuevo' => "NC479"],
    
        ['Actual' => "NCDFE72",
        'Nuevo' => "NC480"],
    
        ['Actual' => "NCDFE73",
        'Nuevo' => "NC481"],
    
        ['Actual' => "NCDFE74",
        'Nuevo' => "NC482"],
    
        ['Actual' => "NCDFE75",
        'Nuevo' => "NC483"],
    
        ['Actual' => "NCDFE76",
        'Nuevo' => "NC484"],
    
        ['Actual' => "NCDFE77",
        'Nuevo' => "NC485"],
    
        ['Actual' => "NCDFE78",
        'Nuevo' => "NC486"],
    
        ['Actual' => "NCDFE79",
        'Nuevo' => "NC487"],
    
        ['Actual' => "NCDFE80",
        'Nuevo' => "NC488"],
    
        ['Actual' => "NCDFE81",
        'Nuevo' => "NC489"],
    
        ['Actual' => "NCDFE82",
        'Nuevo' => "NC490"],
    
        ['Actual' => "NCDFE83",
        'Nuevo' => "NC491"],
    
        ['Actual' => "NCDFE84",
        'Nuevo' => "NC492"],
    
        ['Actual' => "NCDFE85",
        'Nuevo' => "NC493"],
    
        ['Actual' => "NCDFE86",
        'Nuevo' => "NC494"],
    
        ['Actual' => "NCDFE87",
        'Nuevo' => "NC495"],
    
        ['Actual' => "NCDFE88",
        'Nuevo' => "NC496"],
    
        ['Actual' => "NCDFE89",
        'Nuevo' => "NC497"],
    
        ['Actual' => "NCDFE90",
        'Nuevo' => "NC498"],
    
        ['Actual' => "NCDFE91",
        'Nuevo' => "NC499"],
    
        ['Actual' => "NCDFE92",
        'Nuevo' => "NC500"],
    
        ['Actual' => "NCDFE93",
        'Nuevo' => "NC501"],
    
        ['Actual' => "NCDFE94",
        'Nuevo' => "NC502"],
    
        ['Actual' => "NCDFE95",
        'Nuevo' => "NC503"],
    
        ['Actual' => "NCDFE96",
        'Nuevo' => "NC504"],
    
        ['Actual' => "NCDFE97",
        'Nuevo' => "NC505"],
    
        ['Actual' => "NCDFE98",
        'Nuevo' => "NC506"],
    
        ['Actual' => "NCDFE99",
        'Nuevo' => "NC507"],
    
        ['Actual' => "NCDFE100",
        'Nuevo' => "NC508"],
    
        ['Actual' => "NCDFE101",
        'Nuevo' => "NC509"],
    
        ['Actual' => "NCDFE102",
        'Nuevo' => "NC510"],
    
        ['Actual' => "NCDFE103",
        'Nuevo' => "NC511"],
    
        ['Actual' => "NCDFE104",
        'Nuevo' => "NC512"],
    
        ['Actual' => "NCDFE105",
        'Nuevo' => "NC513"],
    
        ['Actual' => "NCDFE106",
        'Nuevo' => "NC514"],
    
        ['Actual' => "NCDFE107",
        'Nuevo' => "NC515"],
    
        ['Actual' => "NCDFE108",
        'Nuevo' => "NC516"],
    
        ['Actual' => "NCDFE109",
        'Nuevo' => "NC517"],
    
        ['Actual' => "NCDFE110",
        'Nuevo' => "NC518"],
    
        ['Actual' => "NCDFE111",
        'Nuevo' => "NC519"],
    
        ['Actual' => "NCDFE112",
        'Nuevo' => "NC520"],
    
        ['Actual' => "NCDFE113",
        'Nuevo' => "NC521"],
    
        ['Actual' => "NCDFE114",
        'Nuevo' => "NC522"],
    
        ['Actual' => "NCDFE115",
        'Nuevo' => "NC523"],
    
        ['Actual' => "NCDFE116",
        'Nuevo' => "NC524"],
    
        ['Actual' => "NCDFE117",
        'Nuevo' => "NC525"],
    
        ['Actual' => "NCDFE118",
        'Nuevo' => "NC526"],
    
        ['Actual' => "NCDFE119",
        'Nuevo' => "NC527"],
    
        ['Actual' => "NCDFE120",
        'Nuevo' => "NC528"],
    
        ['Actual' => "NCDFE121",
        'Nuevo' => "NC529"],
    
        ['Actual' => "NCDFE122",
        'Nuevo' => "NC530"],
    
        ['Actual' => "NCDFE123",
        'Nuevo' => "NC531"],
    
        ['Actual' => "NCDFE124",
        'Nuevo' => "NC532"],
    
        ['Actual' => "NCDFE125",
        'Nuevo' => "NC533"],
    
        ['Actual' => "NCDFE126",
        'Nuevo' => "NC534"],
    
        ['Actual' => "NCDFE127",
        'Nuevo' => "NC535"],
    
        ['Actual' => "NCDFE128",
        'Nuevo' => "NC536"],
    
        ['Actual' => "NCDFE129",
        'Nuevo' => "NC537"],
    
        ['Actual' => "NCDFE130",
        'Nuevo' => "NC538"],
    
        ['Actual' => "NCDFE131",
        'Nuevo' => "NC539"],
    
        ['Actual' => "NCDFE132",
        'Nuevo' => "NC540"],
    
        ['Actual' => "NCDFE133",
        'Nuevo' => "NC541"],
    
        ['Actual' => "NCDFE134",
        'Nuevo' => "NC542"],
    
        ['Actual' => "NCDFE135",
        'Nuevo' => "NC543"],
    
        ['Actual' => "NCDFE136",
        'Nuevo' => "NC544"],
    
        ['Actual' => "NCDFE137",
        'Nuevo' => "NC545"],
    
        ['Actual' => "NCDFE138",
        'Nuevo' => "NC546"],
    
        ['Actual' => "NCDFE139",
        'Nuevo' => "NC547"],
    
        ['Actual' => "NCDFE140",
        'Nuevo' => "NC548"],
    
        ['Actual' => "NCDFE141",
        'Nuevo' => "NC549"],
    
        ['Actual' => "NCDFE142",
        'Nuevo' => "NC550"],
    
        ['Actual' => "NCDFE143",
        'Nuevo' => "NC551"],
    
        ['Actual' => "NCDFE144",
        'Nuevo' => "NC552"],
    
        ['Actual' => "NCDFE145",
        'Nuevo' => "NC553"],
    
        ['Actual' => "NCDFE146",
        'Nuevo' => "NC554"],
    
        ['Actual' => "NCDFE147",
        'Nuevo' => "NC555"],
    
        ['Actual' => "NCDFE148",
        'Nuevo' => "NC556"],
    
        ['Actual' => "NCDFE149",
        'Nuevo' => "NC557"],
    
        ['Actual' => "NCDFE150",
        'Nuevo' => "NC558"],
    
        ['Actual' => "NCDFE151",
        'Nuevo' => "NC559"],
    
        ['Actual' => "NCDFE152",
        'Nuevo' => "NC560"],
    
        ['Actual' => "NCDFE153",
        'Nuevo' => "NC561"],
    
        ['Actual' => "NCDFE154",
        'Nuevo' => "NC562"],
    
        ['Actual' => "NCDFE155",
        'Nuevo' => "NC563"],
    
        ['Actual' => "NCDFE156",
        'Nuevo' => "NC564"],
    
        ['Actual' => "NCDFE157",
        'Nuevo' => "NC565"],
    
        ['Actual' => "NCDFE158",
        'Nuevo' => "NC566"],
    
        ['Actual' => "NCDFE159",
        'Nuevo' => "NC567"],
    
        ['Actual' => "NCDFE160",
        'Nuevo' => "NC568"],
    
        ['Actual' => "NCDFE161",
        'Nuevo' => "NC569"],
    
        ['Actual' => "NCDFE162",
        'Nuevo' => "NC570"],
    
        ['Actual' => "NCDFE163",
        'Nuevo' => "NC571"],
    
        ['Actual' => "NCDFE164",
        'Nuevo' => "NC572"],
    
        ['Actual' => "NCDFE165",
        'Nuevo' => "NC573"],
    
        ['Actual' => "NCDFE166",
        'Nuevo' => "NC574"],
    
        ['Actual' => "NCDFE167",
        'Nuevo' => "NC575"],
    
        ['Actual' => "NCDFE168",
        'Nuevo' => "NC576"],
    
        ['Actual' => "NCDFE169",
        'Nuevo' => "NC577"],
    
        ['Actual' => "NCDFE170",
        'Nuevo' => "NC578"],
    
        ['Actual' => "NCDFE171",
        'Nuevo' => "NC579"],
    
        ['Actual' => "NCDFE172",
        'Nuevo' => "NC580"],
    
        ['Actual' => "NCDFE173",
        'Nuevo' => "NC581"],
    
        ['Actual' => "NCDFE174",
        'Nuevo' => "NC582"],
    
        ['Actual' => "NCDFE175",
        'Nuevo' => "NC583"],
    
        ['Actual' => "NCDFE176",
        'Nuevo' => "NC584"],
    
        ['Actual' => "NCDFE177",
        'Nuevo' => "NC585"],
    
        ['Actual' => "NCDFE178",
        'Nuevo' => "NC586"],
    
        ['Actual' => "NCDFE179",
        'Nuevo' => "NC587"],
    
        ['Actual' => "NCDFE180",
        'Nuevo' => "NC588"],
    
        ['Actual' => "NCDFE181",
        'Nuevo' => "NC589"],
    
        ['Actual' => "NCDFE182",
        'Nuevo' => "NC590"],
    
        ['Actual' => "NCDFE183",
        'Nuevo' => "NC591"],
    
        ['Actual' => "NCDFE184",
        'Nuevo' => "NC592"],
    
        ['Actual' => "NCDFE185",
        'Nuevo' => "NC593"],
    
        ['Actual' => "NCDFE186",
        'Nuevo' => "NC594"],
    
        ['Actual' => "NCDFE187",
        'Nuevo' => "NC595"],
    
        ['Actual' => "NCDFE188",
        'Nuevo' => "NC596"],
    
        ['Actual' => "NCDFE189",
        'Nuevo' => "NC597"],
    
        ['Actual' => "NCDFE190",
        'Nuevo' => "NC598"],
    
        ['Actual' => "NCDFE191",
        'Nuevo' => "NC599"],
    
        ['Actual' => "NCDFE192",
        'Nuevo' => "NC600"],
    
        ['Actual' => "NCDFE193",
        'Nuevo' => "NC601"],
    
        ['Actual' => "NCDFE194",
        'Nuevo' => "NC602"],
    
        ['Actual' => "NCDFE195",
        'Nuevo' => "NC603"],
    
        ['Actual' => "NCDFE196",
        'Nuevo' => "NC604"],
    
        ['Actual' => "NCDFE197",
        'Nuevo' => "NC605"],
    
        ['Actual' => "NCDFE198",
        'Nuevo' => "NC606"],
    
        ['Actual' => "NCDFE199",
        'Nuevo' => "NC607"],
    
        ['Actual' => "NCDFE200",
        'Nuevo' => "NC608"],
    
        ['Actual' => "NCDFE201",
        'Nuevo' => "NC609"],
    
        ['Actual' => "NCDFE202",
        'Nuevo' => "NC610"],
    
        ['Actual' => "NCDFE203",
        'Nuevo' => "NC611"],
    
        ['Actual' => "NCDFE204",
        'Nuevo' => "NC612"],
    
        ['Actual' => "NCDFE205",
        'Nuevo' => "NC613"],
    
        ['Actual' => "NCDFE206",
        'Nuevo' => "NC614"],
    
        ['Actual' => "NCDFE207",
        'Nuevo' => "NC615"],
    
        ['Actual' => "NCDFE208",
        'Nuevo' => "NC616"],
    
        ['Actual' => "NCDFE209",
        'Nuevo' => "NC617"],
    
        ['Actual' => "NCDFE210",
        'Nuevo' => "NC618"],
    
        ['Actual' => "NCDFE211",
        'Nuevo' => "NC619"],
    
        ['Actual' => "NCDFE212",
        'Nuevo' => "NC620"],
    
        ['Actual' => "NCDFE213",
        'Nuevo' => "NC621"],
    
        ['Actual' => "NCDFE214",
        'Nuevo' => "NC622"],
    
        ['Actual' => "NCDFE215",
        'Nuevo' => "NC623"],
    
        ['Actual' => "NCDFE216",
        'Nuevo' => "NC624"],
    
        ['Actual' => "NCDFE217",
        'Nuevo' => "NC625"],
    
        ['Actual' => "NCDFE218",
        'Nuevo' => "NC626"],
    
        ['Actual' => "NCDFE219",
        'Nuevo' => "NC627"],
    
        ['Actual' => "NCDFE220",
        'Nuevo' => "NC628"],
    
        ['Actual' => "NCDFE221",
        'Nuevo' => "NC629"],
    
        ['Actual' => "NCDFE222",
        'Nuevo' => "NC630"],
    
        ['Actual' => "NCDFE223",
        'Nuevo' => "NC631"],
    
        ['Actual' => "NCDFE224",
        'Nuevo' => "NC632"],
    
        ['Actual' => "NCDFE225",
        'Nuevo' => "NC633"],
    
        ['Actual' => "NCDFE226",
        'Nuevo' => "NC634"],
    
        ['Actual' => "NCDFE227",
        'Nuevo' => "NC635"],
    
        ['Actual' => "NCDFE228",
        'Nuevo' => "NC636"],
    
        ['Actual' => "NCDFE229",
        'Nuevo' => "NC637"],
    
        ['Actual' => "NCDFE230",
        'Nuevo' => "NC638"],
    
        ['Actual' => "NCDFE231",
        'Nuevo' => "NC639"],
    
        ['Actual' => "NCDFE232",
        'Nuevo' => "NC640"],
    
        ['Actual' => "NCDFE233",
        'Nuevo' => "NC641"],
    
        ['Actual' => "NCDFE234",
        'Nuevo' => "NC642"],
    
        ['Actual' => "NCDFE235",
        'Nuevo' => "NC643"],
    
        ['Actual' => "NCDFE236",
        'Nuevo' => "NC644"],
    
        ['Actual' => "NCDFE237",
        'Nuevo' => "NC645"],
    
        ['Actual' => "NCDFE238",
        'Nuevo' => "NC646"],
    
        ['Actual' => "NCDFE239",
        'Nuevo' => "NC647"],
    
        ['Actual' => "NCDFE240",
        'Nuevo' => "NC648"],
    
        ['Actual' => "NCDFE241",
        'Nuevo' => "NC649"],
    
        ['Actual' => "NCDFE242",
        'Nuevo' => "NC650"],
    
        ['Actual' => "NCDFE243",
        'Nuevo' => "NC651"],
    
        ['Actual' => "NCDFE244",
        'Nuevo' => "NC652"],
    
        ['Actual' => "NCDFE245",
        'Nuevo' => "NC653"],
    
        ['Actual' => "NCDFE246",
        'Nuevo' => "NC654"],
    
        ['Actual' => "NCDFE247",
        'Nuevo' => "NC655"],
    
        ['Actual' => "NCDFE248",
        'Nuevo' => "NC656"],
    
        ['Actual' => "NCDFE249",
        'Nuevo' => "NC657"],
    
        ['Actual' => "NCDFE250",
        'Nuevo' => "NC658"],
    
        ['Actual' => "NCDFE251",
        'Nuevo' => "NC659"],
    
        ['Actual' => "NCDFE252",
        'Nuevo' => "NC660"],
    
        ['Actual' => "NCDFE253",
        'Nuevo' => "NC661"],
    
        ['Actual' => "NCDFE254",
        'Nuevo' => "NC662"],
    
        ['Actual' => "NCDFE255",
        'Nuevo' => "NC663"],
    
        ['Actual' => "NCDFE256",
        'Nuevo' => "NC664"],
    
        ['Actual' => "NCDFE257",
        'Nuevo' => "NC665"],
    
        ['Actual' => "NCDFE258",
        'Nuevo' => "NC666"],
    
        ['Actual' => "NCDFE259",
        'Nuevo' => "NC667"],
    
        ['Actual' => "NCDFE260",
        'Nuevo' => "NC668"],
    
        ['Actual' => "NCDFE261",
        'Nuevo' => "NC669"],
    
        ['Actual' => "NCDFE262",
        'Nuevo' => "NC670"],
    
        ['Actual' => "NCDFE263",
        'Nuevo' => "NC671"],
    
        ['Actual' => "NCDFE264",
        'Nuevo' => "NC672"],
    
        ['Actual' => "NCDFE265",
        'Nuevo' => "NC673"],
    
        ['Actual' => "NCDFE266",
        'Nuevo' => "NC674"],
    
        ['Actual' => "NCDFE267",
        'Nuevo' => "NC675"],
    
        ['Actual' => "NCDFE268",
        'Nuevo' => "NC676"],
    
        ['Actual' => "NCDFE269",
        'Nuevo' => "NC677"],
    
        ['Actual' => "NCDFE270",
        'Nuevo' => "NC678"],
    
        ['Actual' => "NCDFE271",
        'Nuevo' => "NC679"],
    
        ['Actual' => "NCDFE272",
        'Nuevo' => "NC680"],
    
        ['Actual' => "NCDFE273",
        'Nuevo' => "NC681"],
    
        ['Actual' => "NCDFE274",
        'Nuevo' => "NC682"],
    
        ['Actual' => "NCDFE275",
        'Nuevo' => "NC683"],
    
        ['Actual' => "NCDFE276",
        'Nuevo' => "NC684"],
    
        ['Actual' => "NCDFE277",
        'Nuevo' => "NC685"],
    
        ['Actual' => "NCDFE278",
        'Nuevo' => "NC686"],
    
        ['Actual' => "NCDFE279",
        'Nuevo' => "NC687"],
    
        ['Actual' => "NCDFE280",
        'Nuevo' => "NC688"],
    
        ['Actual' => "NCDFE281",
        'Nuevo' => "NC689"],
    
        ['Actual' => "NCDFE282",
        'Nuevo' => "NC690"],
    
        ['Actual' => "NCDFE283",
        'Nuevo' => "NC691"],
    
        ['Actual' => "NCDFE284",
        'Nuevo' => "NC692"],
    
        ['Actual' => "NCDFE285",
        'Nuevo' => "NC693"],
    
        ['Actual' => "NCDFE286",
        'Nuevo' => "NC694"],
    
        ['Actual' => "NCDFE287",
        'Nuevo' => "NC695"],
    
        ['Actual' => "NCDFE288",
        'Nuevo' => "NC696"],
    
        ['Actual' => "NCDFE289",
        'Nuevo' => "NC697"],
    
        ['Actual' => "NCDFE290",
        'Nuevo' => "NC698"],
    
        ['Actual' => "NCDFE291",
        'Nuevo' => "NC699"],
    
        ['Actual' => "NCDFE292",
        'Nuevo' => "NC700"],
    
        ['Actual' => "NCDFE293",
        'Nuevo' => "NC701"],
    
        ['Actual' => "NCDFE294",
        'Nuevo' => "NC702"],
    
        ['Actual' => "NCDFE295",
        'Nuevo' => "NC703"],
    
        ['Actual' => "NCDFE296",
        'Nuevo' => "NC704"],
    
        ['Actual' => "NCDFE297",
        'Nuevo' => "NC705"],
    
        ['Actual' => "NCDFE298",
        'Nuevo' => "NC706"],
    
        ['Actual' => "NCDFE299",
        'Nuevo' => "NC707"],
    
        ['Actual' => "NCDFE300",
        'Nuevo' => "NC708"],
    
        ['Actual' => "NCDFE301",
        'Nuevo' => "NC709"],
    
        ['Actual' => "NCDFE302",
        'Nuevo' => "NC710"],
    
        ['Actual' => "NCDFE303",
        'Nuevo' => "NC711"],
    
        ['Actual' => "NCDFE304",
        'Nuevo' => "NC712"],
    
        ['Actual' => "NCDFE305",
        'Nuevo' => "NC713"],
    
        ['Actual' => "NCDFE306",
        'Nuevo' => "NC714"],
    
        ['Actual' => "NCDFE307",
        'Nuevo' => "NC715"],
    
        ['Actual' => "NCDFE308",
        'Nuevo' => "NC716"],
    
        ['Actual' => "NCDFE309",
        'Nuevo' => "NC717"],
    
        ['Actual' => "NCDFE310",
        'Nuevo' => "NC718"],
    
        ['Actual' => "NCDFE311",
        'Nuevo' => "NC719"],
    
        ['Actual' => "NCDFE312",
        'Nuevo' => "NC720"],
    
        ['Actual' => "NCDFE313",
        'Nuevo' => "NC721"],
    
        ['Actual' => "NCDFE314",
        'Nuevo' => "NC722"],
    
        ['Actual' => "NCDFE315",
        'Nuevo' => "NC723"],
    
        ['Actual' => "NCDFE316",
        'Nuevo' => "NC724"],
    
        ['Actual' => "NCDFE317",
        'Nuevo' => "NC725"],
    
        ['Actual' => "NCDFE318",
        'Nuevo' => "NC726"],
    
        ['Actual' => "NCDFE319",
        'Nuevo' => "NC727"],
    
        ['Actual' => "NCDFE320",
        'Nuevo' => "NC728"],
    
        ['Actual' => "NCDFE321",
        'Nuevo' => "NC729"],
    
        ['Actual' => "NCDFE322",
        'Nuevo' => "NC730"],
    
        ['Actual' => "NCDFE323",
        'Nuevo' => "NC731"],
    
        ['Actual' => "NCDFE324",
        'Nuevo' => "NC732"],
    
        ['Actual' => "NCDFE325",
        'Nuevo' => "NC733"],
    
        ['Actual' => "NCDFE326",
        'Nuevo' => "NC734"],
    
        ['Actual' => "NCDFE327",
        'Nuevo' => "NC735"],
    
        ['Actual' => "NCDFE328",
        'Nuevo' => "NC736"],
    
        ['Actual' => "NCDFE329",
        'Nuevo' => "NC737"],
    
        ['Actual' => "NCDFE330",
        'Nuevo' => "NC738"],
    
        ['Actual' => "NCDFE331",
        'Nuevo' => "NC739"],
    
        ['Actual' => "NCDFE332",
        'Nuevo' => "NC740"],
    
        ['Actual' => "NCDFE333",
        'Nuevo' => "NC741"],
    
        ['Actual' => "NCDFE334",
        'Nuevo' => "NC742"],
    
        ['Actual' => "NCDFE335",
        'Nuevo' => "NC743"],
    
        ['Actual' => "NCDFE336",
        'Nuevo' => "NC744"],
    
        ['Actual' => "NCDFE337",
        'Nuevo' => "NC745"],
    
        ['Actual' => "NCDFE338",
        'Nuevo' => "NC746"],
    
        ['Actual' => "NCDFE339",
        'Nuevo' => "NC747"],
    
        ['Actual' => "NCDFE340",
        'Nuevo' => "NC748"],
    
        ['Actual' => "NCDFE341",
        'Nuevo' => "NC749"],
    
        ['Actual' => "NCDFE342",
        'Nuevo' => "NC750"],
    
        ['Actual' => "NCDFE343",
        'Nuevo' => "NC751"],
    
        ['Actual' => "NCDFE344",
        'Nuevo' => "NC752"],
    
        ['Actual' => "NCDFE345",
        'Nuevo' => "NC753"],
    
        ['Actual' => "NCDFE346",
        'Nuevo' => "NC754"],
    
        ['Actual' => "NCDFE347",
        'Nuevo' => "NC755"],
    
        ['Actual' => "NCDFE348",
        'Nuevo' => "NC756"],
    
        ['Actual' => "NCDFE349",
        'Nuevo' => "NC757"],
    
        ['Actual' => "NCDFE350",
        'Nuevo' => "NC758"],
    
        ['Actual' => "NCDFE351",
        'Nuevo' => "NC759"],
    
        ['Actual' => "NCDFE352",
        'Nuevo' => "NC760"],
    
        ['Actual' => "NCDFE353",
        'Nuevo' => "NC761"],
    
        ['Actual' => "NCDFE354",
        'Nuevo' => "NC762"],
    
        ['Actual' => "NCDFE355",
        'Nuevo' => "NC763"],
    
        ['Actual' => "NCDFE356",
        'Nuevo' => "NC764"],
    
        ['Actual' => "NCDFE357",
        'Nuevo' => "NC765"],
    
        ['Actual' => "NCDFE358",
        'Nuevo' => "NC766"],
    
        ['Actual' => "NCDFE359",
        'Nuevo' => "NC767"],
    
        ['Actual' => "NCDFE360",
        'Nuevo' => "NC768"],
    
        ['Actual' => "NCDFE361",
        'Nuevo' => "NC769"],
    
        ['Actual' => "NCDFE362",
        'Nuevo' => "NC770"],
    
        ['Actual' => "NCDFE363",
        'Nuevo' => "NC771"],
    
        ['Actual' => "NCDFE364",
        'Nuevo' => "NC772"],
    
        ['Actual' => "NCDFE365",
        'Nuevo' => "NC773"],
    
        ['Actual' => "NCDFE366",
        'Nuevo' => "NC774"],
    
        ['Actual' => "NCDFE367",
        'Nuevo' => "NC775"],
    
        ['Actual' => "NCDFE368",
        'Nuevo' => "NC776"],
    
        ['Actual' => "NCDFE369",
        'Nuevo' => "NC777"],
    
        ['Actual' => "NCDFE370",
        'Nuevo' => "NC778"],
    
        ['Actual' => "NCDFE371",
        'Nuevo' => "NC779"],
    
        ['Actual' => "NCDFE372",
        'Nuevo' => "NC780"],
    
        ['Actual' => "NCDFE373",
        'Nuevo' => "NC781"],
    
        ['Actual' => "NCDFE374",
        'Nuevo' => "NC782"],
    
        ['Actual' => "NCDFE375",
        'Nuevo' => "NC783"],
    
        ['Actual' => "NCDFE376",
        'Nuevo' => "NC784"],
    
        ['Actual' => "NCDFE377",
        'Nuevo' => "NC785"],
    
        ['Actual' => "NCDFE378",
        'Nuevo' => "NC786"],
    
        ['Actual' => "NCDFE379",
        'Nuevo' => "NC787"],
    
        ['Actual' => "NCDFE380",
        'Nuevo' => "NC788"],
    
        ['Actual' => "NCDFE381",
        'Nuevo' => "NC789"],
    
        ['Actual' => "NCDFE382",
        'Nuevo' => "NC790"],
    
        ['Actual' => "NCDFE383",
        'Nuevo' => "NC791"],
    
        ['Actual' => "NCDFE384",
        'Nuevo' => "NC792"],
    
        ['Actual' => "NCDFE385",
        'Nuevo' => "NC793"],
    
        ['Actual' => "NCDFE386",
        'Nuevo' => "NC794"],
    
        ['Actual' => "NCDFE387",
        'Nuevo' => "NC795"],
    
        ['Actual' => "NCDFE388",
        'Nuevo' => "NC796"],
    
        ['Actual' => "NCDFE389",
        'Nuevo' => "NC797"],
    
        ['Actual' => "NCDFE390",
        'Nuevo' => "NC798"],
    
        ['Actual' => "NCDFE391",
        'Nuevo' => "NC799"],
    
        ['Actual' => "NCDFE392",
        'Nuevo' => "NC800"],
    
        ['Actual' => "NCDFE393",
        'Nuevo' => "NC801"],
    
        ['Actual' => "NCDFE394",
        'Nuevo' => "NC802"],
    
        ['Actual' => "NCDFE395",
        'Nuevo' => "NC803"],
    
        ['Actual' => "NCDFE396",
        'Nuevo' => "NC804"],
    
        ['Actual' => "NCDFE397",
        'Nuevo' => "NC805"],
    
        ['Actual' => "NCDFE398",
        'Nuevo' => "NC806"],
    
        ['Actual' => "NCDFE399",
        'Nuevo' => "NC807"],
    
        ['Actual' => "NCDFE400",
        'Nuevo' => "NC808"],
    
        ['Actual' => "NCDFE401",
        'Nuevo' => "NC809"],
    
        ['Actual' => "NCDFE402",
        'Nuevo' => "NC810"],
    
        ['Actual' => "NCDFE403",
        'Nuevo' => "NC811"],
    
        ['Actual' => "NCDFE404",
        'Nuevo' => "NC812"],
    
        ['Actual' => "NCDFE405",
        'Nuevo' => "NC813"],
    
        ['Actual' => "NCDFE406",
        'Nuevo' => "NC814"],
    
        ['Actual' => "NCDFE407",
        'Nuevo' => "NC815"],
    
        ['Actual' => "NCDFE408",
        'Nuevo' => "NC816"],
    
        ['Actual' => "NCDFE409",
        'Nuevo' => "NC817"],
    
        ['Actual' => "NCDFE410",
        'Nuevo' => "NC818"],
    
        ['Actual' => "NCDFE411",
        'Nuevo' => "NC819"],
    
        ['Actual' => "NCDFE412",
        'Nuevo' => "NC820"],
    
        ['Actual' => "NCDFE413",
        'Nuevo' => "NC821"],
    
        ['Actual' => "NCDFE414",
        'Nuevo' => "NC822"],
    
        ['Actual' => "NCDFE415",
        'Nuevo' => "NC823"],
    
        ['Actual' => "NCDFE416",
        'Nuevo' => "NC824"],
    
        ['Actual' => "NCDFE417",
        'Nuevo' => "NC825"],
    
        ['Actual' => "NCDFE418",
        'Nuevo' => "NC826"],
    
        ['Actual' => "NCDFE419",
        'Nuevo' => "NC827"],
    
        ['Actual' => "NCDFE420",
        'Nuevo' => "NC828"],
    
        ['Actual' => "NCDFE421",
        'Nuevo' => "NC829"],
    
        ['Actual' => "NCDFE422",
        'Nuevo' => "NC830"],
    
        ['Actual' => "NCDFE423",
        'Nuevo' => "NC831"],
    
        ['Actual' => "NCDFE424",
        'Nuevo' => "NC832"],
    
        ['Actual' => "NCDFE425",
        'Nuevo' => "NC833"],
    
        ['Actual' => "NCDFE426",
        'Nuevo' => "NC834"],
    
        ['Actual' => "NCDFE427",
        'Nuevo' => "NC835"],
    
        ['Actual' => "NCDFE428",
        'Nuevo' => "NC836"],
    
        ['Actual' => "NCDFE429",
        'Nuevo' => "NC837"],
    
        ['Actual' => "NCDFE430",
        'Nuevo' => "NC838"],
    
        ['Actual' => "NCDFE431",
        'Nuevo' => "NC839"],
    
        ['Actual' => "NCDFE432",
        'Nuevo' => "NC840"],
    
        ['Actual' => "NCDFE433",
        'Nuevo' => "NC841"],
    
        ['Actual' => "NCDFE434",
        'Nuevo' => "NC842"],
    
        ['Actual' => "NCDFE435",
        'Nuevo' => "NC843"],
    
        ['Actual' => "NCDFE436",
        'Nuevo' => "NC844"],
    
        ['Actual' => "NCDFE437",
        'Nuevo' => "NC845"],
    
        ['Actual' => "NCDFE438",
        'Nuevo' => "NC846"],
    
        ['Actual' => "NCDFE439",
        'Nuevo' => "NC847"],
    
        ['Actual' => "NCDFE440",
        'Nuevo' => "NC848"],
    
        ['Actual' => "NCDFE441",
        'Nuevo' => "NC849"],
    
        ['Actual' => "NCDFE442",
        'Nuevo' => "NC850"],
    
        ['Actual' => "NCDFE443",
        'Nuevo' => "NC851"],
    
        ['Actual' => "NCDFE444",
        'Nuevo' => "NC852"],
    
        ['Actual' => "NCDFE445",
        'Nuevo' => "NC853"],
    
        ['Actual' => "NCDFE446",
        'Nuevo' => "NC854"],
    
        ['Actual' => "NCDFE447",
        'Nuevo' => "NC855"],
    
        ['Actual' => "NCDFE448",
        'Nuevo' => "NC856"],
    
        ['Actual' => "NCDFE449",
        'Nuevo' => "NC857"],
    
        ['Actual' => "NCDFE450",
        'Nuevo' => "NC858"],
    
        ['Actual' => "NCDFE451",
        'Nuevo' => "NC859"],
    
        ['Actual' => "NCDFE452",
        'Nuevo' => "NC860"],
    
        ['Actual' => "NCDFE453",
        'Nuevo' => "NC861"],
    
        ['Actual' => "NCDFE454",
        'Nuevo' => "NC862"],
    
        ['Actual' => "NCDFE455",
        'Nuevo' => "NC863"],
    
        ['Actual' => "NCDFE456",
        'Nuevo' => "NC864"],
    
        ['Actual' => "NCDFE457",
        'Nuevo' => "NC865"],
    
        ['Actual' => "NCDFE458",
        'Nuevo' => "NC866"],
    
        ['Actual' => "NCDFE459",
        'Nuevo' => "NC867"],
    
        ['Actual' => "NCDFE460",
        'Nuevo' => "NC868"],
    
        ['Actual' => "NCDFE461",
        'Nuevo' => "NC869"],
    
        ['Actual' => "NCDFE462",
        'Nuevo' => "NC870"],
    
        ['Actual' => "NCDFE463",
        'Nuevo' => "NC871"],
    
        ['Actual' => "NCDFE464",
        'Nuevo' => "NC872"],
    
        ['Actual' => "NCDFE465",
        'Nuevo' => "NC873"],
    
        ['Actual' => "NCDFE466",
        'Nuevo' => "NC874"],
    
        ['Actual' => "NCDFE467",
        'Nuevo' => "NC875"],
    
        ['Actual' => "NCDFE468",
        'Nuevo' => "NC876"],
    
        ['Actual' => "NCDFE469",
        'Nuevo' => "NC877"],
    
        ['Actual' => "NCDFE470",
        'Nuevo' => "NC878"],
    
        ['Actual' => "NCDFE471",
        'Nuevo' => "NC879"],
    
        ['Actual' => "NCDFE472",
        'Nuevo' => "NC880"],
    
        ['Actual' => "NCDFE473",
        'Nuevo' => "NC881"],
    
        ['Actual' => "NCDFE474",
        'Nuevo' => "NC882"],
    
        ['Actual' => "NCDFE475",
        'Nuevo' => "NC883"],
    
        ['Actual' => "NCDFE476",
        'Nuevo' => "NC884"],
    
        ['Actual' => "NCDFE477",
        'Nuevo' => "NC885"],
    
        ['Actual' => "NCDFE478",
        'Nuevo' => "NC886"],
    
        ['Actual' => "NCDFE479",
        'Nuevo' => "NC887"],
    
        ['Actual' => "NCDFE480",
        'Nuevo' => "NC888"],
    
        ['Actual' => "NCDFE481",
        'Nuevo' => "NC889"],
    
        ['Actual' => "NCDFE482",
        'Nuevo' => "NC890"],
    
        ['Actual' => "NCDFE483",
        'Nuevo' => "NC891"],
    
        ['Actual' => "NCDFE484",
        'Nuevo' => "NC892"],
    
        ['Actual' => "NCDFE485",
        'Nuevo' => "NC893"],
    
        ['Actual' => "NCDFE486",
        'Nuevo' => "NC894"],
    
        ['Actual' => "NCDFE487",
        'Nuevo' => "NC895"],
    
        ['Actual' => "NCDFE488",
        'Nuevo' => "NC896"],
    
        ['Actual' => "NCDFE489",
        'Nuevo' => "NC897"],
    
        ['Actual' => "NCDFE490",
        'Nuevo' => "NC898"],
    
        ['Actual' => "NCDFE491",
        'Nuevo' => "NC899"],
    
        ['Actual' => "NCDFE492",
        'Nuevo' => "NC900"],
    
        ['Actual' => "NCDFE493",
        'Nuevo' => "NC901"],
    
        ['Actual' => "NCDFE494",
        'Nuevo' => "NC902"],
    
        ['Actual' => "NCDFE495",
        'Nuevo' => "NC903"],
    
        ['Actual' => "NCDFE496",
        'Nuevo' => "NC904"],
    
        ['Actual' => "NCDFE497",
        'Nuevo' => "NC905"],
    
        ['Actual' => "NCDFE498",
        'Nuevo' => "NC906"],
    
        ['Actual' => "NCDFE499",
        'Nuevo' => "NC907"],
    
        ['Actual' => "NCDFE500",
        'Nuevo' => "NC908"],
    
        ['Actual' => "NCDFE501",
        'Nuevo' => "NC909"],
    
        ['Actual' => "NCDFE502",
        'Nuevo' => "NC910"],
    
        ['Actual' => "NCDFE503",
        'Nuevo' => "NC911"],
    
        ['Actual' => "NCDFE504",
        'Nuevo' => "NC912"],
    
        ['Actual' => "NCDFE505",
        'Nuevo' => "NC913"],
    
        ['Actual' => "NCDFE506",
        'Nuevo' => "NC914"],
    
        ['Actual' => "NCDFE507",
        'Nuevo' => "NC915"],
    
        ['Actual' => "NCDFE508",
        'Nuevo' => "NC916"],
    
        ['Actual' => "NCDFE509",
        'Nuevo' => "NC917"],
    
        ['Actual' => "NCDFE510",
        'Nuevo' => "NC918"],
    
        ['Actual' => "NCDFE511",
        'Nuevo' => "NC919"],
    
        ['Actual' => "NCDFE512",
        'Nuevo' => "NC920"],
    
        ['Actual' => "NCDFE513",
        'Nuevo' => "NC921"],
    
        ['Actual' => "NCDFE514",
        'Nuevo' => "NC922"],
    
        ['Actual' => "NCDFE515",
        'Nuevo' => "NC923"],
    
        ['Actual' => "NCDFE516",
        'Nuevo' => "NC924"],
    
        ['Actual' => "NCDFE517",
        'Nuevo' => "NC925"],
    
        ['Actual' => "NCDFE518",
        'Nuevo' => "NC926"],
    
        ['Actual' => "NCDFE519",
        'Nuevo' => "NC927"],
    
        ['Actual' => "NCDFE520",
        'Nuevo' => "NC928"],
    
        ['Actual' => "NCDFE521",
        'Nuevo' => "NC929"],
    
        ['Actual' => "NCDFE522",
        'Nuevo' => "NC930"],
    
        ['Actual' => "NCDFE523",
        'Nuevo' => "NC931"],
    
        ['Actual' => "NCDFE524",
        'Nuevo' => "NC932"],
    
        ['Actual' => "NCDFE525",
        'Nuevo' => "NC933"],
    
        ['Actual' => "NCDFE526",
        'Nuevo' => "NC934"],
    
        ['Actual' => "NCDFE527",
        'Nuevo' => "NC935"],
    
        ['Actual' => "NCDFE528",
        'Nuevo' => "NC936"],
    
        ['Actual' => "NCDFE529",
        'Nuevo' => "NC937"],
    
        ['Actual' => "NCDFE530",
        'Nuevo' => "NC938"],
    
        ['Actual' => "NCDFE531",
        'Nuevo' => "NC939"],
    
        ['Actual' => "NCDFE532",
        'Nuevo' => "NC940"],
    
        ['Actual' => "NCDFE533",
        'Nuevo' => "NC941"],
    
        ['Actual' => "NCDFE534",
        'Nuevo' => "NC942"],
    
        ['Actual' => "NCDFE535",
        'Nuevo' => "NC943"],
    
        ['Actual' => "NCDFE536",
        'Nuevo' => "NC944"],
    
        ['Actual' => "NCDFE537",
        'Nuevo' => "NC945"],
    
        ['Actual' => "NCDFE538",
        'Nuevo' => "NC946"],
    
        ['Actual' => "NCDFE539",
        'Nuevo' => "NC947"],
    
        ['Actual' => "NCDFE540",
        'Nuevo' => "NC948"],
    
        ['Actual' => "NCDFE541",
        'Nuevo' => "NC949"],
    
        ['Actual' => "NCDFE542",
        'Nuevo' => "NC950"],
    
        ['Actual' => "NCDFE543",
        'Nuevo' => "NC951"],
    
        ['Actual' => "NCDFE544",
        'Nuevo' => "NC952"],
    
        ['Actual' => "NCDFE545",
        'Nuevo' => "NC953"],
    
        ['Actual' => "NCDFE546",
        'Nuevo' => "NC954"],
    
        ['Actual' => "NCDFE547",
        'Nuevo' => "NC955"],
    
        ['Actual' => "NCDFE548",
        'Nuevo' => "NC956"],
    
        ['Actual' => "NCDFE549",
        'Nuevo' => "NC957"],
    
        ['Actual' => "NCDFE550",
        'Nuevo' => "NC958"],
    
        ['Actual' => "NCDFE551",
        'Nuevo' => "NC959"],
    
        ['Actual' => "NCDFE552",
        'Nuevo' => "NC960"],
    
        ['Actual' => "NCDFE553",
        'Nuevo' => "NC961"],
    
        ['Actual' => "NCDFE554",
        'Nuevo' => "NC962"],
    
        ['Actual' => "NCDFE555",
        'Nuevo' => "NC963"],
    
        ['Actual' => "NCDFE556",
        'Nuevo' => "NC964"],
    
        ['Actual' => "NCDFE557",
        'Nuevo' => "NC965"],
    
        ['Actual' => "NCDFE558",
        'Nuevo' => "NC966"],
    
        ['Actual' => "NCDFE559",
        'Nuevo' => "NC967"],
    
        ['Actual' => "NCDFE560",
        'Nuevo' => "NC968"],
    
        ['Actual' => "NCDFE561",
        'Nuevo' => "NC969"],
    
        ['Actual' => "NCDFE562",
        'Nuevo' => "NC970"],
    
        ['Actual' => "NCDFE563",
        'Nuevo' => "NC971"],
    
        ['Actual' => "NCDFE564",
        'Nuevo' => "NC972"],
    
        ['Actual' => "NCDFE565",
        'Nuevo' => "NC973"],
    
        ['Actual' => "NCDFE566",
        'Nuevo' => "NC974"],
    
        ['Actual' => "NCDFE567",
        'Nuevo' => "NC975"],
    
        ['Actual' => "NCDFE568",
        'Nuevo' => "NC976"],
    
        ['Actual' => "NCDFE569",
        'Nuevo' => "NC977"],
    
        ['Actual' => "NCDFE570",
        'Nuevo' => "NC978"],
    
        ['Actual' => "NCDFE571",
        'Nuevo' => "NC979"],
    
        ['Actual' => "NCDFE572",
        'Nuevo' => "NC980"],
    
        ['Actual' => "NCDFE573",
        'Nuevo' => "NC981"],
    
        ['Actual' => "NCDFE574",
        'Nuevo' => "NC982"],
    
        ['Actual' => "NCDFE575",
        'Nuevo' => "NC983"],
    
        ['Actual' => "NCDFE576",
        'Nuevo' => "NC984"],
    
        ['Actual' => "NCDFE577",
        'Nuevo' => "NC985"],
    
        ['Actual' => "NCDFE578",
        'Nuevo' => "NC986"],
    
        ['Actual' => "NCDFE579",
        'Nuevo' => "NC987"],
    
        ['Actual' => "NCDFE580",
        'Nuevo' => "NC988"],
    
        ['Actual' => "NCDFE581",
        'Nuevo' => "NC989"],
    
        ['Actual' => "NCDFE582",
        'Nuevo' => "NC990"],
    
        ['Actual' => "NCDFE583",
        'Nuevo' => "NC991"],
    
        ['Actual' => "NCDFE584",
        'Nuevo' => "NC992"],
    
        ['Actual' => "NCDFE585",
        'Nuevo' => "NC993"],
    
        ['Actual' => "NCDFE586",
        'Nuevo' => "NC994"],
    
        ['Actual' => "NCDFE587",
        'Nuevo' => "NC995"],
    
        ['Actual' => "NCDFE588",
        'Nuevo' => "NC996"],
    
        ['Actual' => "NCDFE589",
        'Nuevo' => "NC997"],
    
        ['Actual' => "NCDFE590",
        'Nuevo' => "NC998"],
    
        ['Actual' => "NCDFE591",
        'Nuevo' => "NC999"],
    
        ['Actual' => "NCDFE592",
        'Nuevo' => "NC1000"],
    
        ['Actual' => "NCDFE593",
        'Nuevo' => "NC1001"],
    
        ['Actual' => "NCDFE594",
        'Nuevo' => "NC1002"],
    
        ['Actual' => "NCDFE595",
        'Nuevo' => "NC1003"],
    
        ['Actual' => "NCDFE596",
        'Nuevo' => "NC1004"],
    
        ['Actual' => "NCDFE597",
        'Nuevo' => "NC1005"],
    
        ['Actual' => "NCDFE598",
        'Nuevo' => "NC1006"],
    
        ['Actual' => "NCDFE599",
        'Nuevo' => "NC1007"],
    
        ['Actual' => "NCDFE600",
        'Nuevo' => "NC1008"],
    
        ['Actual' => "NCDFE601",
        'Nuevo' => "NC1009"],
    
        ['Actual' => "NCDFE602",
        'Nuevo' => "NC1010"],
    
        ['Actual' => "NCDFE603",
        'Nuevo' => "NC1011"],
    
        ['Actual' => "NCDFE604",
        'Nuevo' => "NC1012"],
    
        ['Actual' => "NCDFE605",
        'Nuevo' => "NC1013"],
    
        ['Actual' => "NCDFE606",
        'Nuevo' => "NC1014"],
    
        ['Actual' => "NCDFE607",
        'Nuevo' => "NC1015"],
    
        ['Actual' => "NCDFE608",
        'Nuevo' => "NC1016"],
    
        ['Actual' => "NCDFE609",
        'Nuevo' => "NC1017"],
    
        ['Actual' => "NCDFE610",
        'Nuevo' => "NC1018"],
    
        ['Actual' => "NCDFE611",
        'Nuevo' => "NC1019"],
    
        ['Actual' => "NCDFE612",
        'Nuevo' => "NC1020"],
    
        ['Actual' => "NCDFE613",
        'Nuevo' => "NC1021"],
    
        ['Actual' => "NCDFE614",
        'Nuevo' => "NC1022"],
    
        ['Actual' => "NCDFE615",
        'Nuevo' => "NC1023"],
    
        ['Actual' => "NCDFE616",
        'Nuevo' => "NC1024"],
    
        ['Actual' => "NCDFE617",
        'Nuevo' => "NC1025"],
    
        ['Actual' => "NCDFE618",
        'Nuevo' => "NC1026"],
    
        ['Actual' => "NCDFE619",
        'Nuevo' => "NC1027"],
    
        ['Actual' => "NCDFE620",
        'Nuevo' => "NC1028"],
    
        ['Actual' => "NCDFE621",
        'Nuevo' => "NC1029"],
    
        ['Actual' => "NCDFE622",
        'Nuevo' => "NC1030"],
    
        ['Actual' => "NCDFE623",
        'Nuevo' => "NC1031"],
    
        ['Actual' => "NCDFE624",
        'Nuevo' => "NC1032"],
    
        ['Actual' => "NCDFE625",
        'Nuevo' => "NC1033"],
    
        ['Actual' => "NCDFE626",
        'Nuevo' => "NC1034"],
    
        ['Actual' => "NCDFE627",
        'Nuevo' => "NC1035"],
    
        ['Actual' => "NCDFE628",
        'Nuevo' => "NC1036"],
    
        ['Actual' => "NCDFE629",
        'Nuevo' => "NC1037"],
    
        ['Actual' => "NCDFE630",
        'Nuevo' => "NC1038"],
    
        ['Actual' => "NCDFE631",
        'Nuevo' => "NC1039"],
    
        ['Actual' => "NCDFE632",
        'Nuevo' => "NC1040"],
    
        ['Actual' => "NCDFE633",
        'Nuevo' => "NC1041"],
    
        ['Actual' => "NCDFE634",
        'Nuevo' => "NC1042"],
    
        ['Actual' => "NCDFE635",
        'Nuevo' => "NC1043"],
    
        ['Actual' => "NCDFE636",
        'Nuevo' => "NC1044"],
    
        ['Actual' => "NCDFE637",
        'Nuevo' => "NC1045"],
    
        ['Actual' => "NCDFE638",
        'Nuevo' => "NC1046"],
    
        ['Actual' => "NCDFE639",
        'Nuevo' => "NC1047"],
    
        ['Actual' => "NCDFE640",
        'Nuevo' => "NC1048"],
    
        ['Actual' => "NCDFE641",
        'Nuevo' => "NC1049"],
    
        ['Actual' => "NCDFE642",
        'Nuevo' => "NC1050"],
    
        ['Actual' => "NCDFE643",
        'Nuevo' => "NC1051"],
    
        ['Actual' => "NCDFE644",
        'Nuevo' => "NC1052"],
    
        ['Actual' => "NCDFE645",
        'Nuevo' => "NC1053"],
    
        ['Actual' => "NCDFE646",
        'Nuevo' => "NC1054"],
    
        ['Actual' => "NCDFE647",
        'Nuevo' => "NC1055"],
    
        ['Actual' => "NCDFE648",
        'Nuevo' => "NC1056"],
    
        ['Actual' => "NCDFE649",
        'Nuevo' => "NC1057"],
    
        ['Actual' => "NCDFE650",
        'Nuevo' => "NC1058"],
    
        ['Actual' => "NCDFE651",
        'Nuevo' => "NC1059"],
    
        ['Actual' => "NCDFE652",
        'Nuevo' => "NC1060"],
    
        ['Actual' => "NCDFE653",
        'Nuevo' => "NC1061"],
    
        ['Actual' => "NCDFE654",
        'Nuevo' => "NC1062"],
    
        ['Actual' => "NCDFE655",
        'Nuevo' => "NC1063"],
    
        ['Actual' => "NCDFE656",
        'Nuevo' => "NC1064"],
    
        ['Actual' => "NCDFE657",
        'Nuevo' => "NC1065"],
    
        ['Actual' => "NCDFE658",
        'Nuevo' => "NC1066"],
    
        ['Actual' => "NCDFE659",
        'Nuevo' => "NC1067"],
    
        ['Actual' => "NCDFE660",
        'Nuevo' => "NC1068"],
    
        ['Actual' => "NCDFE661",
        'Nuevo' => "NC1069"],
    
        ['Actual' => "NCDFE662",
        'Nuevo' => "NC1070"],
    
        ['Actual' => "NCDFE663",
        'Nuevo' => "NC1071"],
    
        ['Actual' => "NCDFE664",
        'Nuevo' => "NC1072"],
    
        ['Actual' => "NCDFE665",
        'Nuevo' => "NC1073"],
    
        ['Actual' => "NCDFE666",
        'Nuevo' => "NC1074"],
    
        ['Actual' => "NCDFE667",
        'Nuevo' => "NC1075"],
    
        ['Actual' => "NCDFE668",
        'Nuevo' => "NC1076"],
    
        ['Actual' => "NCDFE669",
        'Nuevo' => "NC1077"],
    
        ['Actual' => "NCDFE670",
        'Nuevo' => "NC1078"],
    
        ['Actual' => "NCDFE671",
        'Nuevo' => "NC1079"],
    
        ['Actual' => "NCDFE672",
        'Nuevo' => "NC1080"],
    
        ['Actual' => "NCDFE673",
        'Nuevo' => "NC1081"],
    
        ['Actual' => "NCDFE674",
        'Nuevo' => "NC1082"],
    
        ['Actual' => "NCDFE675",
        'Nuevo' => "NC1083"],
    
        ['Actual' => "NCDFE676",
        'Nuevo' => "NC1084"],
    
        ['Actual' => "NCDFE677",
        'Nuevo' => "NC1085"],
    
        ['Actual' => "NCDFE678",
        'Nuevo' => "NC1086"],
    
        ['Actual' => "NCDFE679",
        'Nuevo' => "NC1087"],
    
        ['Actual' => "NCDFE680",
        'Nuevo' => "NC1088"],
    
        ['Actual' => "NCDFE681",
        'Nuevo' => "NC1089"],
    
        ['Actual' => "NCDFE682",
        'Nuevo' => "NC1090"],
    
        ['Actual' => "NCDFE683",
        'Nuevo' => "NC1091"],
    
        ['Actual' => "NCDFE684",
        'Nuevo' => "NC1092"],
    
        ['Actual' => "NCDFE685",
        'Nuevo' => "NC1093"],
    
        ['Actual' => "NCDFE686",
        'Nuevo' => "NC1094"],
    
        ['Actual' => "NCDFE687",
        'Nuevo' => "NC1095"],
    
        ['Actual' => "NCDFE688",
        'Nuevo' => "NC1096"],
    
        ['Actual' => "NCDFE689",
        'Nuevo' => "NC1097"],
    
        ['Actual' => "NCDFE690",
        'Nuevo' => "NC1098"],
    
        ['Actual' => "NCDFE691",
        'Nuevo' => "NC1099"],
    
        ['Actual' => "NCDFE692",
        'Nuevo' => "NC1100"],
    
        ['Actual' => "NCDFE693",
        'Nuevo' => "NC1101"],
    
        ['Actual' => "NCDFE694",
        'Nuevo' => "NC1102"],
    
        ['Actual' => "NCDFE695",
        'Nuevo' => "NC1103"],
    
        ['Actual' => "NCDFE696",
        'Nuevo' => "NC1104"],
    
        ['Actual' => "NCDFE697",
        'Nuevo' => "NC1105"],
    
        ['Actual' => "NCDFE698",
        'Nuevo' => "NC1106"],
    
        ['Actual' => "NCDFE699",
        'Nuevo' => "NC1107"],
    
        ['Actual' => "NCDFE700",
        'Nuevo' => "NC1108"],
    
        ['Actual' => "NCDFE701",
        'Nuevo' => "NC1109"],
    
        ['Actual' => "NCDFE702",
        'Nuevo' => "NC1110"],
    
        ['Actual' => "NCDFE703",
        'Nuevo' => "NC1111"],
    
        ['Actual' => "NCDFE704",
        'Nuevo' => "NC1112"],
    
        ['Actual' => "NCDFE705",
        'Nuevo' => "NC1113"],
    
        ['Actual' => "NCDFE706",
        'Nuevo' => "NC1114"],
    
        ['Actual' => "NCDFE707",
        'Nuevo' => "NC1115"],
    
        ['Actual' => "NCDFE708",
        'Nuevo' => "NC1116"],
    
        ['Actual' => "NCDFE709",
        'Nuevo' => "NC1117"],
    
        ['Actual' => "NCDFE710",
        'Nuevo' => "NC1118"],
    
        ['Actual' => "NCDFE711",
        'Nuevo' => "NC1119"],
    
        ['Actual' => "NCDFE712",
        'Nuevo' => "NC1120"],
    
        ['Actual' => "NCDFE713",
        'Nuevo' => "NC1121"],
    
        ['Actual' => "NCDFE714",
        'Nuevo' => "NC1122"],
    
        ['Actual' => "NCDFE715",
        'Nuevo' => "NC1123"],
    
        ['Actual' => "NCDFE716",
        'Nuevo' => "NC1124"],
    
        ['Actual' => "NCDFE717",
        'Nuevo' => "NC1125"],
    
        ['Actual' => "NCDFE718",
        'Nuevo' => "NC1126"],
    
        ['Actual' => "NCDFE719",
        'Nuevo' => "NC1127"],
    
        ['Actual' => "NCDFE720",
        'Nuevo' => "NC1128"],
    
        ['Actual' => "NCDFE721",
        'Nuevo' => "NC1129"],
    
        ['Actual' => "NCDFE722",
        'Nuevo' => "NC1130"],
    
        ['Actual' => "NCDFE723",
        'Nuevo' => "NC1131"],
    
        ['Actual' => "NCDFE724",
        'Nuevo' => "NC1132"],
    
        ['Actual' => "NCDFE725",
        'Nuevo' => "NC1133"],
    
        ['Actual' => "NCDFE726",
        'Nuevo' => "NC1134"],
    
        ['Actual' => "NCDFE727",
        'Nuevo' => "NC1135"],
    
        ['Actual' => "NCDFE728",
        'Nuevo' => "NC1136"],
    
        ['Actual' => "NCDFE729",
        'Nuevo' => "NC1137"],
    
        ['Actual' => "NCDFE730",
        'Nuevo' => "NC1138"],
    
        ['Actual' => "NCDFE731",
        'Nuevo' => "NC1139"],
    
        ['Actual' => "NCDFE732",
        'Nuevo' => "NC1140"],
    
        ['Actual' => "NCDFE733",
        'Nuevo' => "NC1141"],
    
        ['Actual' => "NCDFE734",
        'Nuevo' => "NC1142"],
    
        ['Actual' => "NCDFE735",
        'Nuevo' => "NC1143"],
    
        ['Actual' => "NCDFE736",
        'Nuevo' => "NC1144"],
    
        ['Actual' => "NCDFE737",
        'Nuevo' => "NC1145"],
    
        ['Actual' => "NCDFE738",
        'Nuevo' => "NC1146"],
    
        ['Actual' => "NCDFE739",
        'Nuevo' => "NC1147"],
    
        ['Actual' => "NCDFE740",
        'Nuevo' => "NC1148"],
    
        ['Actual' => "NCDFE741",
        'Nuevo' => "NC1149"],
    
        ['Actual' => "NCDFE742",
        'Nuevo' => "NC1150"],
    
        ['Actual' => "NCDFE743",
        'Nuevo' => "NC1151"],
    
        ['Actual' => "NCDFE744",
        'Nuevo' => "NC1152"],
    
        ['Actual' => "NCDFE745",
        'Nuevo' => "NC1153"],
    
        ['Actual' => "NCDFE746",
        'Nuevo' => "NC1154"],
    
        ['Actual' => "NCDFE747",
        'Nuevo' => "NC1155"],
    
        ['Actual' => "NCDFE748",
        'Nuevo' => "NC1156"],
    
        ['Actual' => "NCDFE749",
        'Nuevo' => "NC1157"],
    
        ['Actual' => "NCDFE750",
        'Nuevo' => "NC1158"],
    
        ['Actual' => "NCDFE751",
        'Nuevo' => "NC1159"],
    
        ['Actual' => "NCDFE752",
        'Nuevo' => "NC1160"],
    
        ['Actual' => "NCDFE753",
        'Nuevo' => "NC1161"],
    
        ['Actual' => "NCDFE754",
        'Nuevo' => "NC1162"],
    
        ['Actual' => "NCDFE755",
        'Nuevo' => "NC1163"],
    
        ['Actual' => "NCDFE756",
        'Nuevo' => "NC1164"],
    
        ['Actual' => "NCDFE757",
        'Nuevo' => "NC1165"],
    
        ['Actual' => "NCDFE758",
        'Nuevo' => "NC1166"],
    
        ['Actual' => "NCDFE759",
        'Nuevo' => "NC1167"],
    
        ['Actual' => "NCDFE760",
        'Nuevo' => "NC1168"],
    
        ['Actual' => "NCDFE761",
        'Nuevo' => "NC1169"],
    
        ['Actual' => "NCDFE762",
        'Nuevo' => "NC1170"],
    
        ['Actual' => "NCDFE763",
        'Nuevo' => "NC1171"],
    
        ['Actual' => "NCDFE764",
        'Nuevo' => "NC1172"],
    
        ['Actual' => "NCDFE765",
        'Nuevo' => "NC1173"],
    
        ['Actual' => "NCDFE766",
        'Nuevo' => "NC1174"],
    
        ['Actual' => "NCDFE767",
        'Nuevo' => "NC1175"],
    
        ['Actual' => "NCDFE768",
        'Nuevo' => "NC1176"],
    
        ['Actual' => "NCDFE769",
        'Nuevo' => "NC1177"],
    
        ['Actual' => "NCDFE770",
        'Nuevo' => "NC1178"],
    
        ['Actual' => "NCDFE771",
        'Nuevo' => "NC1179"],
    
        ['Actual' => "NCDFE772",
        'Nuevo' => "NC1180"],
    
        ['Actual' => "NCDFE773",
        'Nuevo' => "NC1181"],
    
        ['Actual' => "NCDFE774",
        'Nuevo' => "NC1182"],
    
        ['Actual' => "NCDFE775",
        'Nuevo' => "NC1183"],
    
        ['Actual' => "NCDFE776",
        'Nuevo' => "NC1184"],
    
        ['Actual' => "NCDFE777",
        'Nuevo' => "NC1185"],
    
        ['Actual' => "NCDFE778",
        'Nuevo' => "NC1186"],
    
        ['Actual' => "NCDFE779",
        'Nuevo' => "NC1187"],
    
        ['Actual' => "NCDFE780",
        'Nuevo' => "NC1188"],
    
        ['Actual' => "NCDFE781",
        'Nuevo' => "NC1189"],
    
        ['Actual' => "NCDFE782",
        'Nuevo' => "NC1190"],
    
        ['Actual' => "NCDFE783",
        'Nuevo' => "NC1191"],
    
        ['Actual' => "NCDFE784",
        'Nuevo' => "NC1192"],
    
        ['Actual' => "NCDFE785",
        'Nuevo' => "NC1193"],
    
        ['Actual' => "NCDFE786",
        'Nuevo' => "NC1194"],
    
        ['Actual' => "NCDFE787",
        'Nuevo' => "NC1195"],
    
        ['Actual' => "NCDFE788",
        'Nuevo' => "NC1196"],
    
        ['Actual' => "NCDFE789",
        'Nuevo' => "NC1197"],
    
        ['Actual' => "NCDFE790",
        'Nuevo' => "NC1198"],
    
        ['Actual' => "NCDFE791",
        'Nuevo' => "NC1199"],
    
        ['Actual' => "NCDFE792",
        'Nuevo' => "NC1200"],
    
        ['Actual' => "NCDFE793",
        'Nuevo' => "NC1201"],
    
        ['Actual' => "NCDFE794",
        'Nuevo' => "NC1202"],
    
        ['Actual' => "NCDFE795",
        'Nuevo' => "NC1203"],
    
        ['Actual' => "NCDFE796",
        'Nuevo' => "NC1204"],
    
        ['Actual' => "NCDFE797",
        'Nuevo' => "NC1205"],
    
        ['Actual' => "NCDFE798",
        'Nuevo' => "NC1206"],
    
        ['Actual' => "NCDFE799",
        'Nuevo' => "NC1207"],
    
        ['Actual' => "NCDFE800",
        'Nuevo' => "NC1208"],
    
        ['Actual' => "NCDFE801",
        'Nuevo' => "NC1209"],
    
        ['Actual' => "NCDFE802",
        'Nuevo' => "NC1210"],
    
        ['Actual' => "NCDFE803",
        'Nuevo' => "NC1211"],
    
        ['Actual' => "NCDFE804",
        'Nuevo' => "NC1212"],
    
        ['Actual' => "NCDFE805",
        'Nuevo' => "NC1213"],
    
        ['Actual' => "NCDFE806",
        'Nuevo' => "NC1214"],
    
        ['Actual' => "NCDFE807",
        'Nuevo' => "NC1215"],
    
        ['Actual' => "NCDFE808",
        'Nuevo' => "NC1216"],
    
        ['Actual' => "NCDFE809",
        'Nuevo' => "NC1217"],
    
        ['Actual' => "NCDFE810",
        'Nuevo' => "NC1218"],
    
        ['Actual' => "NCDFE811",
        'Nuevo' => "NC1219"],
    
        ['Actual' => "NCDFE812",
        'Nuevo' => "NC1220"],
    
        ['Actual' => "NCDFE813",
        'Nuevo' => "NC1221"],
    
        ['Actual' => "NCDFE814",
        'Nuevo' => "NC1222"],
    
        ['Actual' => "NCDFE815",
        'Nuevo' => "NC1223"],
    
        ['Actual' => "NCDFE816",
        'Nuevo' => "NC1224"],
    
        ['Actual' => "NCDFE817",
        'Nuevo' => "NC1225"],
    
        ['Actual' => "NCDFE818",
        'Nuevo' => "NC1226"],
    
        ['Actual' => "NCDFE819",
        'Nuevo' => "NC1227"],
    
        ['Actual' => "NCDFE820",
        'Nuevo' => "NC1228"],
    
        ['Actual' => "NCDFE821",
        'Nuevo' => "NC1229"],
    
        ['Actual' => "NCDFE822",
        'Nuevo' => "NC1230"],
    
        ['Actual' => "NCDFE823",
        'Nuevo' => "NC1231"],
    
        ['Actual' => "NCDFE824",
        'Nuevo' => "NC1232"],
    
        ['Actual' => "NCDFE825",
        'Nuevo' => "NC1233"],
    
        ['Actual' => "NCDFE826",
        'Nuevo' => "NC1234"],
    
        ['Actual' => "NCDFE827",
        'Nuevo' => "NC1235"],
    
        ['Actual' => "NCDFE828",
        'Nuevo' => "NC1236"],
    
        ['Actual' => "NCDFE829",
        'Nuevo' => "NC1237"],
    
        ['Actual' => "NCDFE830",
        'Nuevo' => "NC1238"],
    
        ['Actual' => "NCDFE831",
        'Nuevo' => "NC1239"],
    
        ['Actual' => "NCDFE832",
        'Nuevo' => "NC1240"],
    
        ['Actual' => "NCDFE833",
        'Nuevo' => "NC1241"],
    
        ['Actual' => "NCDFE834",
        'Nuevo' => "NC1242"],
    
        ['Actual' => "NCDFE835",
        'Nuevo' => "NC1243"],
    
        ['Actual' => "NCDFE836",
        'Nuevo' => "NC1244"],
    
        ['Actual' => "NCDFE837",
        'Nuevo' => "NC1245"],
    
        ['Actual' => "NCDFE838",
        'Nuevo' => "NC1246"],
    
        ['Actual' => "NCDFE839",
        'Nuevo' => "NC1247"],
    
        ['Actual' => "NCDFE840",
        'Nuevo' => "NC1248"],
    
        ['Actual' => "NCDFE841",
        'Nuevo' => "NC1249"],
    
        ['Actual' => "NCDFE842",
        'Nuevo' => "NC1250"],
    
        ['Actual' => "NCDFE843",
        'Nuevo' => "NC1251"],
    
        ['Actual' => "NCDFE844",
        'Nuevo' => "NC1252"],
    
        ['Actual' => "NCDFE845",
        'Nuevo' => "NC1253"],
    
        ['Actual' => "NCDFE846",
        'Nuevo' => "NC1254"],
    
        ['Actual' => "NCDFE847",
        'Nuevo' => "NC1255"],
    
        ['Actual' => "NCDFE848",
        'Nuevo' => "NC1256"],
    
        ['Actual' => "NCDFE849",
        'Nuevo' => "NC1257"],
    
        ['Actual' => "NCDFE850",
        'Nuevo' => "NC1258"],
    
        ['Actual' => "NCDFE851",
        'Nuevo' => "NC1259"],
    
        ['Actual' => "NCDFE852",
        'Nuevo' => "NC1260"],
    
        ['Actual' => "NCDFE853",
        'Nuevo' => "NC1261"],
    
        ['Actual' => "NCDFE854",
        'Nuevo' => "NC1262"],
    
        ['Actual' => "NCDFE855",
        'Nuevo' => "NC1263"],
    
        ['Actual' => "NCDFE856",
        'Nuevo' => "NC1264"],
    
        ['Actual' => "NCDFE857",
        'Nuevo' => "NC1265"],
    
        ['Actual' => "NCDFE858",
        'Nuevo' => "NC1266"],
    
        ['Actual' => "NCDFE859",
        'Nuevo' => "NC1267"],
    
        ['Actual' => "NCDFE860",
        'Nuevo' => "NC1268"],
    
        ['Actual' => "NCDFE861",
        'Nuevo' => "NC1269"],
    
        ['Actual' => "NCDFE862",
        'Nuevo' => "NC1270"],
    
        ['Actual' => "NCDFE863",
        'Nuevo' => "NC1271"],
    
        ['Actual' => "NCDFE864",
        'Nuevo' => "NC1272"],
    
        ['Actual' => "NCDFE865",
        'Nuevo' => "NC1273"],
    
        ['Actual' => "NCDFE866",
        'Nuevo' => "NC1274"],
    
        ['Actual' => "NCDFE867",
        'Nuevo' => "NC1275"],
    
        ['Actual' => "NCDFE868",
        'Nuevo' => "NC1276"],
    
        ['Actual' => "NCDFE869",
        'Nuevo' => "NC1277"],
    
        ['Actual' => "NCDFE870",
        'Nuevo' => "NC1278"],
    
        ['Actual' => "NCDFE871",
        'Nuevo' => "NC1279"],
    
        ['Actual' => "NCDFE872",
        'Nuevo' => "NC1280"],
    
        ['Actual' => "NCDFE873",
        'Nuevo' => "NC1281"],
    
        ['Actual' => "NCDFE874",
        'Nuevo' => "NC1282"],
    
        ['Actual' => "NCDFE875",
        'Nuevo' => "NC1283"],
    
        ['Actual' => "NCDFE876",
        'Nuevo' => "NC1284"],
    
        ['Actual' => "NCDFE877",
        'Nuevo' => "NC1285"],
    
        ['Actual' => "NCDFE878",
        'Nuevo' => "NC1286"],
    
        ['Actual' => "NCDFE879",
        'Nuevo' => "NC1287"],
    
        ['Actual' => "NCDFE880",
        'Nuevo' => "NC1288"],
    
        ['Actual' => "NCDFE881",
        'Nuevo' => "NC1289"],
    
        ['Actual' => "NCDFE882",
        'Nuevo' => "NC1290"],
    
        ['Actual' => "NCDFE883",
        'Nuevo' => "NC1291"],
    
        ['Actual' => "NCDFE884",
        'Nuevo' => "NC1292"],
    
        ['Actual' => "NCDFE885",
        'Nuevo' => "NC1293"],
    
        ['Actual' => "NCDFE886",
        'Nuevo' => "NC1294"],
    
        ['Actual' => "NCDFE887",
        'Nuevo' => "NC1295"],
    
        ['Actual' => "NCDFE888",
        'Nuevo' => "NC1296"],
    
        ['Actual' => "NCDFE889",
        'Nuevo' => "NC1297"],
    
        ['Actual' => "NCDFE890",
        'Nuevo' => "NC1298"],
    
        ['Actual' => "NCDFE891",
        'Nuevo' => "NC1299"],
    
        ['Actual' => "NCDFE892",
        'Nuevo' => "NC1300"],
    
        ['Actual' => "NCDFE893",
        'Nuevo' => "NC1301"],
    
        ['Actual' => "NCDFE894",
        'Nuevo' => "NC1302"],
    
        ['Actual' => "NCDFE895",
        'Nuevo' => "NC1303"],
    
        ['Actual' => "NCDFE896",
        'Nuevo' => "NC1304"],
    
        ['Actual' => "NCDFE897",
        'Nuevo' => "NC1305"],
    
        ['Actual' => "NCDFE898",
        'Nuevo' => "NC1306"],
    
        ['Actual' => "NCDFE899",
        'Nuevo' => "NC1307"],
    
        ['Actual' => "NCDFE900",
        'Nuevo' => "NC1308"],
    
        ['Actual' => "NCDFE901",
        'Nuevo' => "NC1309"],
    
        ['Actual' => "NCDFE902",
        'Nuevo' => "NC1310"],
    
        ['Actual' => "NCDFE903",
        'Nuevo' => "NC1311"],
    
        ['Actual' => "NCDFE904",
        'Nuevo' => "NC1312"],
    
        ['Actual' => "NCDFE905",
        'Nuevo' => "NC1313"],
    
        ['Actual' => "NCDFE906",
        'Nuevo' => "NC1314"],
    
        ['Actual' => "NCDFE907",
        'Nuevo' => "NC1315"],
    
        ['Actual' => "NCDFE908",
        'Nuevo' => "NC1316"],
    
        ['Actual' => "NCDFE909",
        'Nuevo' => "NC1317"],
    
        ['Actual' => "NCDFE910",
        'Nuevo' => "NC1318"],
    
        ['Actual' => "NCDFE911",
        'Nuevo' => "NC1319"],
    
        ['Actual' => "NCDFE912",
        'Nuevo' => "NC1320"],
    
        ['Actual' => "NCDFE913",
        'Nuevo' => "NC1321"],
    
        ['Actual' => "NCDFE914",
        'Nuevo' => "NC1322"],
    
        ['Actual' => "NCDFE915",
        'Nuevo' => "NC1323"],
    
        ['Actual' => "NCDFE916",
        'Nuevo' => "NC1324"],
    
        ['Actual' => "NCDFE917",
        'Nuevo' => "NC1325"],
    
        ['Actual' => "NCDFE918",
        'Nuevo' => "NC1326"],
    
        ['Actual' => "NCDFE919",
        'Nuevo' => "NC1327"],
    
        ['Actual' => "NCDFE920",
        'Nuevo' => "NC1328"],
    
        ['Actual' => "NCDFE921",
        'Nuevo' => "NC1329"],
    
        ['Actual' => "NCDFE922",
        'Nuevo' => "NC1330"],
    
        ['Actual' => "NCDFE923",
        'Nuevo' => "NC1331"],
    
        ['Actual' => "NCDFE924",
        'Nuevo' => "NC1332"],
    
        ['Actual' => "NCDFE925",
        'Nuevo' => "NC1333"],
    
        ['Actual' => "NCDFE926",
        'Nuevo' => "NC1334"],
    
        ['Actual' => "NCDFE927",
        'Nuevo' => "NC1335"],
    
        ['Actual' => "NCDFE928",
        'Nuevo' => "NC1336"],
    
        ['Actual' => "NCDFE929",
        'Nuevo' => "NC1337"],
    
        ['Actual' => "NCDFE930",
        'Nuevo' => "NC1338"],
    
        ['Actual' => "NCDFE931",
        'Nuevo' => "NC1339"],
    
        ['Actual' => "NCDFE932",
        'Nuevo' => "NC1340"],
    
        ['Actual' => "NCDFE933",
        'Nuevo' => "NC1341"],
    
        ['Actual' => "NCDFE934",
        'Nuevo' => "NC1342"],
    
        ['Actual' => "NCDFE935",
        'Nuevo' => "NC1343"],
    
        ['Actual' => "NCDFE936",
        'Nuevo' => "NC1344"],
    
        ['Actual' => "NCDFE937",
        'Nuevo' => "NC1345"],
    
        ['Actual' => "NCDFE938",
        'Nuevo' => "NC1346"],
    
        ['Actual' => "NCDFE939",
        'Nuevo' => "NC1347"],
    
        ['Actual' => "NCDFE940",
        'Nuevo' => "NC1348"],
    
        ['Actual' => "NCDFE941",
        'Nuevo' => "NC1349"],
    
        ['Actual' => "NCDFE942",
        'Nuevo' => "NC1350"],
    
        ['Actual' => "NCDFE943",
        'Nuevo' => "NC1351"],
    
        ['Actual' => "NCDFE944",
        'Nuevo' => "NC1352"],
    
        ['Actual' => "NCDFE945",
        'Nuevo' => "NC1353"],
    
        ['Actual' => "NCDFE946",
        'Nuevo' => "NC1354"],
    
        ['Actual' => "NCDFE947",
        'Nuevo' => "NC1355"],
    
        ['Actual' => "NCDFE948",
        'Nuevo' => "NC1356"],
    
        ['Actual' => "NCDFE949",
        'Nuevo' => "NC1357"],
    
        ['Actual' => "NCDFE950",
        'Nuevo' => "NC1358"],
    
        ['Actual' => "NCDFE951",
        'Nuevo' => "NC1359"],
    
        ['Actual' => "NCDFE952",
        'Nuevo' => "NC1360"],
    
        ['Actual' => "NCDFE953",
        'Nuevo' => "NC1361"],
    
        ['Actual' => "NCDFE954",
        'Nuevo' => "NC1362"],
    
        ['Actual' => "NCDFE955",
        'Nuevo' => "NC1363"],
    
        ['Actual' => "NCDFE956",
        'Nuevo' => "NC1364"],
    
        ['Actual' => "NCDFE957",
        'Nuevo' => "NC1365"],
    
        ['Actual' => "NCDFE958",
        'Nuevo' => "NC1366"],
    
        ['Actual' => "NCDFE959",
        'Nuevo' => "NC1367"],
    
        ['Actual' => "NCDFE960",
        'Nuevo' => "NC1368"],
    
        ['Actual' => "NCDFE961",
        'Nuevo' => "NC1369"],
    
        ['Actual' => "NCDFE962",
        'Nuevo' => "NC1370"],
    
        ['Actual' => "NCDFE963",
        'Nuevo' => "NC1371"],
    
        ['Actual' => "NCDFE964",
        'Nuevo' => "NC1372"],
    
        ['Actual' => "NCDFE965",
        'Nuevo' => "NC1373"],
    
        ['Actual' => "NCDFE966",
        'Nuevo' => "NC1374"],
    
        ['Actual' => "NCDFE967",
        'Nuevo' => "NC1375"],
    
        ['Actual' => "NCDFE968",
        'Nuevo' => "NC1376"],
    
        ['Actual' => "NCDFE969",
        'Nuevo' => "NC1377"],
    
        ['Actual' => "NCDFE970",
        'Nuevo' => "NC1378"],
    
        ['Actual' => "NCDFE971",
        'Nuevo' => "NC1379"],
    
        ['Actual' => "NCDFE972",
        'Nuevo' => "NC1380"],
    
        ['Actual' => "NCDFE973",
        'Nuevo' => "NC1381"],
    
        ['Actual' => "NCDFE974",
        'Nuevo' => "NC1382"],
    
        ['Actual' => "NCDFE975",
        'Nuevo' => "NC1383"],
    
        ['Actual' => "NCDFE976",
        'Nuevo' => "NC1384"],
    
        ['Actual' => "NCDFE977",
        'Nuevo' => "NC1385"],
    
        ['Actual' => "NCDFE978",
        'Nuevo' => "NC1386"],
    
        ['Actual' => "NCDFE979",
        'Nuevo' => "NC1387"],
    
        ['Actual' => "NCDFE980",
        'Nuevo' => "NC1388"],
    
        ['Actual' => "NCDFE981",
        'Nuevo' => "NC1389"],
    
        ['Actual' => "NCDFE982",
        'Nuevo' => "NC1390"],
    
        ['Actual' => "NCDFE983",
        'Nuevo' => "NC1391"],
    
        ['Actual' => "NCDFE984",
        'Nuevo' => "NC1392"],
    
        ['Actual' => "NCDFE985",
        'Nuevo' => "NC1393"],
    
        ['Actual' => "NCDFE986",
        'Nuevo' => "NC1394"],
    
        ['Actual' => "NCDFE987",
        'Nuevo' => "NC1395"],
    
        ['Actual' => "NCDFE988",
        'Nuevo' => "NC1396"],
    
        ['Actual' => "NCDFE989",
        'Nuevo' => "NC1397"],
    
        ['Actual' => "NCDFE990",
        'Nuevo' => "NC1398"],
    
        ['Actual' => "NCDFE991",
        'Nuevo' => "NC1399"],
    
        ['Actual' => "NCDFE992",
        'Nuevo' => "NC1400"],
    
        ['Actual' => "NCDFE993",
        'Nuevo' => "NC1401"],
    
        ['Actual' => "NCDFE994",
        'Nuevo' => "NC1402"],
    
        ['Actual' => "NCDFE995",
        'Nuevo' => "NC1403"],
    
        ['Actual' => "NCDFE996",
        'Nuevo' => "NC1404"],
    
        ['Actual' => "NCDFE997",
        'Nuevo' => "NC1405"],
    
        ['Actual' => "NCDFE998",
        'Nuevo' => "NC1406"],
    
        ['Actual' => "NCDFE999",
        'Nuevo' => "NC1407"],
    
        ['Actual' => "NCDFE1000",
        'Nuevo' => "NC1408"],
    
        ['Actual' => "NCDFE1001",
        'Nuevo' => "NC1409"],
    
        ['Actual' => "NCDFE1002",
        'Nuevo' => "NC1410"],
    
        ['Actual' => "NCDFE1003",
        'Nuevo' => "NC1411"],
    
        ['Actual' => "NCDFE1004",
        'Nuevo' => "NC1412"],
    
        ['Actual' => "NCDFE1005",
        'Nuevo' => "NC1413"],
    
        ['Actual' => "NCDFE1006",
        'Nuevo' => "NC1414"],
    
        ['Actual' => "NCDFE1007",
        'Nuevo' => "NC1415"],
    
        ['Actual' => "NCDFE1008",
        'Nuevo' => "NC1416"],
    
        ['Actual' => "NCDFE1009",
        'Nuevo' => "NC1417"],
    
        ['Actual' => "NCDFE1010",
        'Nuevo' => "NC1418"],
    
        ['Actual' => "NCDFE1011",
        'Nuevo' => "NC1419"],
    
        ['Actual' => "NCDFE1012",
        'Nuevo' => "NC1420"],
    
        ['Actual' => "NCDFE1013",
        'Nuevo' => "NC1421"],
    
        ['Actual' => "NCDFE1014",
        'Nuevo' => "NC1422"],
    
        ['Actual' => "NCDFE1015",
        'Nuevo' => "NC1423"],
    
        ['Actual' => "NCDFE1016",
        'Nuevo' => "NC1424"],
    
        ['Actual' => "NCDFE1017",
        'Nuevo' => "NC1425"],
    
        ['Actual' => "NCDFE1018",
        'Nuevo' => "NC1426"],
    
        ['Actual' => "NCDFE1019",
        'Nuevo' => "NC1427"],
    
        ['Actual' => "NCDFE1020",
        'Nuevo' => "NC1428"],
    
        ['Actual' => "NCDFE1021",
        'Nuevo' => "NC1429"],
    
        ['Actual' => "NCDFE1022",
        'Nuevo' => "NC1430"],
    
        ['Actual' => "NCDFE1023",
        'Nuevo' => "NC1431"],
    
        ['Actual' => "NCDFE1024",
        'Nuevo' => "NC1432"],
    
        ['Actual' => "NCDFE1025",
        'Nuevo' => "NC1433"],
    
        ['Actual' => "NCDFE1026",
        'Nuevo' => "NC1434"],
    
        ['Actual' => "NCDFE1027",
        'Nuevo' => "NC1435"],
    
        ['Actual' => "NCDFE1028",
        'Nuevo' => "NC1436"],
    
        ['Actual' => "NCDFE1029",
        'Nuevo' => "NC1437"],
    
        ['Actual' => "NCDFE1030",
        'Nuevo' => "NC1438"],
    
        ['Actual' => "NCDFE1031",
        'Nuevo' => "NC1439"],
    
        ['Actual' => "NCDFE1032",
        'Nuevo' => "NC1440"],
    
        ['Actual' => "NCDFE1033",
        'Nuevo' => "NC1441"],
    
        ['Actual' => "NCDFE1034",
        'Nuevo' => "NC1442"],
    
        ['Actual' => "NCDFE1035",
        'Nuevo' => "NC1443"],
    
        ['Actual' => "NCDFE1036",
        'Nuevo' => "NC1444"],
    
        ['Actual' => "NCDFE1037",
        'Nuevo' => "NC1445"],
    
        ['Actual' => "NCDFE1038",
        'Nuevo' => "NC1446"],
    
        ['Actual' => "NCDFE1039",
        'Nuevo' => "NC1447"],
    
        ['Actual' => "NCDFE1040",
        'Nuevo' => "NC1448"],
    
        ['Actual' => "NCDFE1041",
        'Nuevo' => "NC1449"],
    
        ['Actual' => "NCDFE1042",
        'Nuevo' => "NC1450"],
    
        ['Actual' => "NCDFE1043",
        'Nuevo' => "NC1451"],
    
        ['Actual' => "NCDFE1044",
        'Nuevo' => "NC1452"],
    
        ['Actual' => "NCDFE1045",
        'Nuevo' => "NC1453"],
    
        ['Actual' => "NCDFE1046",
        'Nuevo' => "NC1454"],
    
        ['Actual' => "NCDFE1047",
        'Nuevo' => "NC1455"],
    
        ['Actual' => "NCDFE1048",
        'Nuevo' => "NC1456"],
    
        ['Actual' => "NCDFE1049",
        'Nuevo' => "NC1457"],
    
        ['Actual' => "NCDFE1050",
        'Nuevo' => "NC1458"],
    
        ['Actual' => "NCDFE1051",
        'Nuevo' => "NC1459"],
    
        ['Actual' => "NCDFE1052",
        'Nuevo' => "NC1460"],
    
        ['Actual' => "NCDFE1053",
        'Nuevo' => "NC1461"],
    
        ['Actual' => "NCDFE1054",
        'Nuevo' => "NC1462"],
    
        ['Actual' => "NCDFE1055",
        'Nuevo' => "NC1463"],
    
        ['Actual' => "NCDFE1056",
        'Nuevo' => "NC1464"],
    
        ['Actual' => "NCDFE1057",
        'Nuevo' => "NC1465"],
    
        ['Actual' => "NCDFE1058",
        'Nuevo' => "NC1466"],
    
        ['Actual' => "NCDFE1059",
        'Nuevo' => "NC1467"],
    
        ['Actual' => "NCDFE1060",
        'Nuevo' => "NC1468"],
    
        ['Actual' => "NCDFE1061",
        'Nuevo' => "NC1469"],
    
        ['Actual' => "NCDFE1062",
        'Nuevo' => "NC1470"],
    
        ['Actual' => "NCDFE1063",
        'Nuevo' => "NC1471"],
    
        ['Actual' => "NCDFE1064",
        'Nuevo' => "NC1472"],
    
        ['Actual' => "NCDFE1065",
        'Nuevo' => "NC1473"],
    
        ['Actual' => "NCDFE1066",
        'Nuevo' => "NC1474"],
    
        ['Actual' => "NCDFE1067",
        'Nuevo' => "NC1475"],
    
        ['Actual' => "NCDFE1068",
        'Nuevo' => "NC1476"],
    
        ['Actual' => "NCDFE1069",
        'Nuevo' => "NC1477"],
    
        ['Actual' => "NCDFE1070",
        'Nuevo' => "NC1478"],
    
        ['Actual' => "NCDFE1071",
        'Nuevo' => "NC1479"],
    
        ['Actual' => "NCDFE1072",
        'Nuevo' => "NC1480"],
    
        ['Actual' => "NCDFE1073",
        'Nuevo' => "NC1481"],
    
        ['Actual' => "NCDFE1074",
        'Nuevo' => "NC1482"],
    
        ['Actual' => "NCDFE1075",
        'Nuevo' => "NC1483"],
    
        ['Actual' => "NCDFE1076",
        'Nuevo' => "NC1484"],
    
        ['Actual' => "NCDFE1077",
        'Nuevo' => "NC1485"],
    
        ['Actual' => "NCDFE1078",
        'Nuevo' => "NC1486"],
    
        ['Actual' => "NCDFE1079",
        'Nuevo' => "NC1487"],
    
        ['Actual' => "NCDFE1080",
        'Nuevo' => "NC1488"],
    
        ['Actual' => "NCDFE1081",
        'Nuevo' => "NC1489"],
    
        ['Actual' => "NCDFE1082",
        'Nuevo' => "NC1490"],
    
        ['Actual' => "NCDFE1083",
        'Nuevo' => "NC1491"],
    
        ['Actual' => "NCDFE1084",
        'Nuevo' => "NC1492"],
    
        ['Actual' => "NCDFE1085",
        'Nuevo' => "NC1493"],
    
        ['Actual' => "NCDFE1086",
        'Nuevo' => "NC1494"],
    
        ['Actual' => "NCDFE1087",
        'Nuevo' => "NC1495"],
    
        ['Actual' => "NCDFE1088",
        'Nuevo' => "NC1496"],
    
        ['Actual' => "NCDFE1089",
        'Nuevo' => "NC1497"],
    
        ['Actual' => "NCDFE1090",
        'Nuevo' => "NC1498"],
    
        ['Actual' => "NCDFE1091",
        'Nuevo' => "NC1499"],
    
        ['Actual' => "NCDFE1092",
        'Nuevo' => "NC1500"],
    
        ['Actual' => "NCDFE1093",
        'Nuevo' => "NC1501"],
    
        ['Actual' => "NCDFE1094",
        'Nuevo' => "NC1502"],
    
        ['Actual' => "NCDFE1095",
        'Nuevo' => "NC1503"],
    
        ['Actual' => "NCDFE1096",
        'Nuevo' => "NC1504"],
    
        ['Actual' => "NCDFE1097",
        'Nuevo' => "NC1505"],
    
        ['Actual' => "NCDFE1098",
        'Nuevo' => "NC1506"],
    
        ['Actual' => "NCDFE1099",
        'Nuevo' => "NC1507"],
    
        ['Actual' => "NCDFE1100",
        'Nuevo' => "NC1508"],
    
        ['Actual' => "NCDFE1101",
        'Nuevo' => "NC1509"],
    
        ['Actual' => "NCDFE1102",
        'Nuevo' => "NC1510"],
    
        ['Actual' => "NCDFE1103",
        'Nuevo' => "NC1511"],
    
        ['Actual' => "NCDFE1104",
        'Nuevo' => "NC1512"],
    
        ['Actual' => "NCDFE1105",
        'Nuevo' => "NC1513"],
    
        ['Actual' => "NCDFE1106",
        'Nuevo' => "NC1514"],
    
        ['Actual' => "NCDFE1107",
        'Nuevo' => "NC1515"],
    
        ['Actual' => "NCDFE1108",
        'Nuevo' => "NC1516"],
    
        ['Actual' => "NCDFE1109",
        'Nuevo' => "NC1517"],
    
        ['Actual' => "NCDFE1110",
        'Nuevo' => "NC1518"],
    
        ['Actual' => "NCDFE1111",
        'Nuevo' => "NC1519"],
    
        ['Actual' => "NCDFE1112",
        'Nuevo' => "NC1520"],
    
        ['Actual' => "NCDFE1113",
        'Nuevo' => "NC1521"],
    
        ['Actual' => "NCDFE1114",
        'Nuevo' => "NC1522"],
    
        ['Actual' => "NCDFE1115",
        'Nuevo' => "NC1523"],
    
        ['Actual' => "NCDFE1116",
        'Nuevo' => "NC1524"],
    
        ['Actual' => "NCDFE1117",
        'Nuevo' => "NC1525"],
    
        ['Actual' => "NCDFE1118",
        'Nuevo' => "NC1526"],
    
        ['Actual' => "NCDFE1119",
        'Nuevo' => "NC1527"],
    
        ['Actual' => "NCDFE1120",
        'Nuevo' => "NC1528"],
    
        ['Actual' => "NCDFE1121",
        'Nuevo' => "NC1529"],
    
        ['Actual' => "NCDFE1122",
        'Nuevo' => "NC1530"],
    
        ['Actual' => "NCDFE1123",
        'Nuevo' => "NC1531"],
    
        ['Actual' => "NCDFE1124",
        'Nuevo' => "NC1532"],
    
        ['Actual' => "NCDFE1125",
        'Nuevo' => "NC1533"],
    
        ['Actual' => "NCDFE1126",
        'Nuevo' => "NC1534"],
    
        ['Actual' => "NCDFE1127",
        'Nuevo' => "NC1535"],
    
        ['Actual' => "NCDFE1128",
        'Nuevo' => "NC1536"],
    
        ['Actual' => "NCDFE1129",
        'Nuevo' => "NC1537"],
    
        ['Actual' => "NCDFE1130",
        'Nuevo' => "NC1538"],
    
        ['Actual' => "NCDFE1131",
        'Nuevo' => "NC1539"],
    
        ['Actual' => "NCDFE1132",
        'Nuevo' => "NC1540"],
    
        ['Actual' => "NCDFE1133",
        'Nuevo' => "NC1541"],
    
        ['Actual' => "NCDFE1134",
        'Nuevo' => "NC1542"],
    
        ['Actual' => "NCDFE1135",
        'Nuevo' => "NC1543"],
    
        ['Actual' => "NCDFE1136",
        'Nuevo' => "NC1544"],
    
        ['Actual' => "NCDFE1137",
        'Nuevo' => "NC1545"],
    
        ['Actual' => "NCDFE1138",
        'Nuevo' => "NC1546"],
    
        ['Actual' => "NCDFE1139",
        'Nuevo' => "NC1547"],
    
        ['Actual' => "NCDFE1140",
        'Nuevo' => "NC1548"],
    
        ['Actual' => "NCDFE1141",
        'Nuevo' => "NC1549"],
    
        ['Actual' => "NCDFE1142",
        'Nuevo' => "NC1550"],
    
        ['Actual' => "NCDFE1143",
        'Nuevo' => "NC1551"],
    
        ['Actual' => "NCDFE1144",
        'Nuevo' => "NC1552"],
    
        ['Actual' => "NCDFE1145",
        'Nuevo' => "NC1553"],
    
        ['Actual' => "NCDFE1146",
        'Nuevo' => "NC1554"],
    
        ['Actual' => "NCDFE1147",
        'Nuevo' => "NC1555"],
    
        ['Actual' => "NCDFE1148",
        'Nuevo' => "NC1556"],
    
        ['Actual' => "NCDFE1149",
        'Nuevo' => "NC1557"],
    
        ['Actual' => "NCDFE1150",
        'Nuevo' => "NC1558"],
    
        ['Actual' => "NCDFE1151",
        'Nuevo' => "NC1559"],
    
        ['Actual' => "NCDFE1152",
        'Nuevo' => "NC1560"],
    
        ['Actual' => "NCDFE1153",
        'Nuevo' => "NC1561"],
    
        ['Actual' => "NCDFE1154",
        'Nuevo' => "NC1562"],
    
        ['Actual' => "NCDFE1155",
        'Nuevo' => "NC1563"],
    
        ['Actual' => "NCDFE1156",
        'Nuevo' => "NC1564"],
    
        ['Actual' => "NCDFE1157",
        'Nuevo' => "NC1565"],
    
        ['Actual' => "NCDFE1158",
        'Nuevo' => "NC1566"],
    
        ['Actual' => "NCDFE1159",
        'Nuevo' => "NC1567"],
    
        ['Actual' => "NCDFE1160",
        'Nuevo' => "NC1568"],
    
        ['Actual' => "NCDFE1161",
        'Nuevo' => "NC1569"],
    
        ['Actual' => "NCDFE1162",
        'Nuevo' => "NC1570"],
    
        ['Actual' => "NCDFE1163",
        'Nuevo' => "NC1571"],
    
        ['Actual' => "NCDFE1164",
        'Nuevo' => "NC1572"],
    
        ['Actual' => "NCDFE1165",
        'Nuevo' => "NC1573"],
    
        ['Actual' => "NCDFE1166",
        'Nuevo' => "NC1574"],
    
        ['Actual' => "NCDFE1167",
        'Nuevo' => "NC1575"],
    
        ['Actual' => "NCDFE1168",
        'Nuevo' => "NC1576"],
    
        ['Actual' => "NCDFE1169",
        'Nuevo' => "NC1577"],
    
        ['Actual' => "NCDFE1170",
        'Nuevo' => "NC1578"],
    
        ['Actual' => "NCDFE1171",
        'Nuevo' => "NC1579"],
    
        ['Actual' => "NCDFE1172",
        'Nuevo' => "NC1580"],
    
        ['Actual' => "NCDFE1173",
        'Nuevo' => "NC1581"],
    
        ['Actual' => "NCDFE1174",
        'Nuevo' => "NC1582"],
    
        ['Actual' => "NCDFE1175",
        'Nuevo' => "NC1583"],
    
        ['Actual' => "NCDFE1176",
        'Nuevo' => "NC1584"],
    
        ['Actual' => "NCDFE1177",
        'Nuevo' => "NC1585"],
    
        ['Actual' => "NCDFE1178",
        'Nuevo' => "NC1586"],
    
        ['Actual' => "NCDFE1179",
        'Nuevo' => "NC1587"],
    
        ['Actual' => "NCDFE1180",
        'Nuevo' => "NC1588"],
    
        ['Actual' => "NCDFE1181",
        'Nuevo' => "NC1589"],
    
        ['Actual' => "NCDFE1182",
        'Nuevo' => "NC1590"],
    
        ['Actual' => "NCDFE1183",
        'Nuevo' => "NC1591"],
    
        ['Actual' => "NCDFE1184",
        'Nuevo' => "NC1592"],
    
        ['Actual' => "NCDFE1185",
        'Nuevo' => "NC1593"],
    
        ['Actual' => "NCDFE1186",
        'Nuevo' => "NC1594"],
    
        ['Actual' => "NCDFE1187",
        'Nuevo' => "NC1595"],
    
        ['Actual' => "NCDFE1188",
        'Nuevo' => "NC1596"],
    
        ['Actual' => "NCDFE1189",
        'Nuevo' => "NC1597"],
    
        ['Actual' => "NCDFE1190",
        'Nuevo' => "NC1598"],
    
        ['Actual' => "NCDFE1191",
        'Nuevo' => "NC1599"],
    
        ['Actual' => "NCDFE1192",
        'Nuevo' => "NC1600"],
    
        ['Actual' => "NCDFE1193",
        'Nuevo' => "NC1601"],
    
        ['Actual' => "NCDFE1194",
        'Nuevo' => "NC1602"],
    
        ['Actual' => "NCDFE1195",
        'Nuevo' => "NC1603"],
    
        ['Actual' => "NCDFE1196",
        'Nuevo' => "NC1604"],
    
        ['Actual' => "NCDFE1197",
        'Nuevo' => "NC1605"],
    
        ['Actual' => "NCDFE1198",
        'Nuevo' => "NC1606"],
    
        ['Actual' => "NCDFE1199",
        'Nuevo' => "NC1607"],
    
        ['Actual' => "NCDFE1200",
        'Nuevo' => "NC1608"],
    
        ['Actual' => "NCDFE1201",
        'Nuevo' => "NC1609"],
    
        ['Actual' => "NCDFE1202",
        'Nuevo' => "NC1610"],
    
        ['Actual' => "NCDFE1203",
        'Nuevo' => "NC1611"],
    
        ['Actual' => "NCDFE1204",
        'Nuevo' => "NC1612"],
    
        ['Actual' => "NCDFE1205",
        'Nuevo' => "NC1613"],
    
        ['Actual' => "NCDFE1206",
        'Nuevo' => "NC1614"],
    
        ['Actual' => "NCDFE1207",
        'Nuevo' => "NC1615"],
    
        ['Actual' => "NCDFE1208",
        'Nuevo' => "NC1616"],
    
        ['Actual' => "NCDFE1209",
        'Nuevo' => "NC1617"],
    
        ['Actual' => "NCDFE1210",
        'Nuevo' => "NC1618"],
    
        ['Actual' => "NCDFE1211",
        'Nuevo' => "NC1619"],
    
        ['Actual' => "NCDFE1212",
        'Nuevo' => "NC1620"],
    
        ['Actual' => "NCDFE1213",
        'Nuevo' => "NC1621"],
    
        ['Actual' => "NCDFE1214",
        'Nuevo' => "NC1622"],
    
        ['Actual' => "NCDFE1215",
        'Nuevo' => "NC1623"],
    
        ['Actual' => "NCDFE1216",
        'Nuevo' => "NC1624"],
    
        ['Actual' => "NCDFE1217",
        'Nuevo' => "NC1625"],
    
        ['Actual' => "NCDFE1218",
        'Nuevo' => "NC1626"],
    
        ['Actual' => "NCDFE1219",
        'Nuevo' => "NC1627"],
    
        ['Actual' => "NCDFE1220",
        'Nuevo' => "NC1628"],
    
        ['Actual' => "NCDFE1221",
        'Nuevo' => "NC1629"],
    
        ['Actual' => "NCDFE1222",
        'Nuevo' => "NC1630"],
    
        ['Actual' => "NCDFE1223",
        'Nuevo' => "NC1631"],
    
        ['Actual' => "NCDFE1224",
        'Nuevo' => "NC1632"],
    
        ['Actual' => "NCDFE1225",
        'Nuevo' => "NC1633"],
    
        ['Actual' => "NCDFE1226",
        'Nuevo' => "NC1634"],
    
        ['Actual' => "NCDFE1227",
        'Nuevo' => "NC1635"],
    
        ['Actual' => "NCDFE1228",
        'Nuevo' => "NC1636"],
    
        ['Actual' => "NCDFE1229",
        'Nuevo' => "NC1637"],
    
        ['Actual' => "NCDFE1230",
        'Nuevo' => "NC1638"],
    
        ['Actual' => "NCDFE1231",
        'Nuevo' => "NC1639"],
    
        ['Actual' => "NCDFE1232",
        'Nuevo' => "NC1640"],
    
        ['Actual' => "NCDFE1233",
        'Nuevo' => "NC1641"],
    
        ['Actual' => "NCDFE1234",
        'Nuevo' => "NC1642"],
    
        ['Actual' => "NCDFE1235",
        'Nuevo' => "NC1643"],
    
        ['Actual' => "NCDFE1236",
        'Nuevo' => "NC1644"],
    
        ['Actual' => "NCDFE1237",
        'Nuevo' => "NC1645"],
    
        ['Actual' => "NCDFE1238",
        'Nuevo' => "NC1646"],
    
        ['Actual' => "NCDFE1239",
        'Nuevo' => "NC1647"],
    
        ['Actual' => "NCDFE1240",
        'Nuevo' => "NC1648"],
    
        ['Actual' => "NCDFE1241",
        'Nuevo' => "NC1649"],
    
        ['Actual' => "NCDFE1242",
        'Nuevo' => "NC1650"],
    
        ['Actual' => "NCDFE1243",
        'Nuevo' => "NC1651"],
    
        ['Actual' => "NCDFE1244",
        'Nuevo' => "NC1652"],
    
        ['Actual' => "NCDFE1245",
        'Nuevo' => "NC1653"],
    
        ['Actual' => "NCDFE1246",
        'Nuevo' => "NC1654"],
    
        ['Actual' => "NCDFE1247",
        'Nuevo' => "NC1655"],
    
        ['Actual' => "NCDFE1248",
        'Nuevo' => "NC1656"],
    
        ['Actual' => "NCDFE1249",
        'Nuevo' => "NC1657"],
    
        ['Actual' => "NCDFE1250",
        'Nuevo' => "NC1658"],
    
        ['Actual' => "NCDFE1251",
        'Nuevo' => "NC1659"],
    
        ['Actual' => "NCDFE1252",
        'Nuevo' => "NC1660"],
    
        ['Actual' => "NCDFE1253",
        'Nuevo' => "NC1661"],
    
        ['Actual' => "NCDFE1254",
        'Nuevo' => "NC1662"],
    
        ['Actual' => "NCDFE1255",
        'Nuevo' => "NC1663"],
    
        ['Actual' => "NCDFE1256",
        'Nuevo' => "NC1664"],
    
        ['Actual' => "NCDFE1257",
        'Nuevo' => "NC1665"],
    
        ['Actual' => "NCDFE1258",
        'Nuevo' => "NC1666"],
    
        ['Actual' => "NCDFE1259",
        'Nuevo' => "NC1667"],
    
        ['Actual' => "NCDFE1260",
        'Nuevo' => "NC1668"],
    
        ['Actual' => "NCDFE1261",
        'Nuevo' => "NC1669"],
    
        ['Actual' => "NCDFE1262",
        'Nuevo' => "NC1670"],
    
        ['Actual' => "NCDFE1263",
        'Nuevo' => "NC1671"],
    
        ['Actual' => "NCDFE1264",
        'Nuevo' => "NC1672"],
    
        ['Actual' => "NCDFE1265",
        'Nuevo' => "NC1673"],
    
        ['Actual' => "NCDFE1266",
        'Nuevo' => "NC1674"],
    
        ['Actual' => "NCDFE1267",
        'Nuevo' => "NC1675"],
    
        ['Actual' => "NCDFE1268",
        'Nuevo' => "NC1676"],
    
        ['Actual' => "NCDFE1269",
        'Nuevo' => "NC1677"],
    
        ['Actual' => "NCDFE1270",
        'Nuevo' => "NC1678"],
    
        ['Actual' => "NCDFE1271",
        'Nuevo' => "NC1679"],
    
        ['Actual' => "NCDFE1272",
        'Nuevo' => "NC1680"],
    
        ['Actual' => "NCDFE1273",
        'Nuevo' => "NC1681"],
    
        ['Actual' => "NCDFE1274",
        'Nuevo' => "NC1682"],
    
        ['Actual' => "NCDFE1275",
        'Nuevo' => "NC1683"],
    
        ['Actual' => "NCDFE1276",
        'Nuevo' => "NC1684"],
    
        ['Actual' => "NCDFE1277",
        'Nuevo' => "NC1685"],
    
        ['Actual' => "NCDFE1278",
        'Nuevo' => "NC1686"],
    
        ['Actual' => "NCDFE1279",
        'Nuevo' => "NC1687"],
    
        ['Actual' => "NCDFE1280",
        'Nuevo' => "NC1688"],
    
        ['Actual' => "NCDFE1281",
        'Nuevo' => "NC1689"],
    
        ['Actual' => "NCDFE1282",
        'Nuevo' => "NC1690"],
    
        ['Actual' => "NCDFE1283",
        'Nuevo' => "NC1691"],
    
        ['Actual' => "NCDFE1284",
        'Nuevo' => "NC1692"],
    
        ['Actual' => "NCDFE1285",
        'Nuevo' => "NC1693"],
    
        ['Actual' => "NCDFE1286",
        'Nuevo' => "NC1694"],
    
        ['Actual' => "NCDFE1287",
        'Nuevo' => "NC1695"],
    
        ['Actual' => "NCDFE1288",
        'Nuevo' => "NC1696"],
    
        ['Actual' => "NCDFE1289",
        'Nuevo' => "NC1697"],
    
        ['Actual' => "NCDFE1290",
        'Nuevo' => "NC1698"],
    
        ['Actual' => "NCDFE1291",
        'Nuevo' => "NC1699"],
    
        ['Actual' => "NCDFE1292",
        'Nuevo' => "NC1700"],
    
        ['Actual' => "NCDFE1293",
        'Nuevo' => "NC1701"],
    
        ['Actual' => "NCDFE1294",
        'Nuevo' => "NC1702"],
    
        ['Actual' => "NCDFE1295",
        'Nuevo' => "NC1703"],
    
        ['Actual' => "NCDFE1296",
        'Nuevo' => "NC1704"],
    
        ['Actual' => "NCDFE1297",
        'Nuevo' => "NC1705"],
    
        ['Actual' => "NCDFE1298",
        'Nuevo' => "NC1706"],
    
        ['Actual' => "NCDFE1299",
        'Nuevo' => "NC1707"],
    
        ['Actual' => "NCDFE1300",
        'Nuevo' => "NC1708"],
    
        ['Actual' => "NCDFE1301",
        'Nuevo' => "NC1709"],
    
        ['Actual' => "NCDFE1302",
        'Nuevo' => "NC1710"],
    
        ['Actual' => "NCDFE1303",
        'Nuevo' => "NC1711"],
    
        ['Actual' => "NCDFE1304",
        'Nuevo' => "NC1712"],
    
        ['Actual' => "NCDFE1305",
        'Nuevo' => "NC1713"],
    
        ['Actual' => "NCDFE1306",
        'Nuevo' => "NC1714"],
    
        ['Actual' => "NCDFE1307",
        'Nuevo' => "NC1715"],
    
        ['Actual' => "NCDFE1308",
        'Nuevo' => "NC1716"],
    
        ['Actual' => "NCDFE1309",
        'Nuevo' => "NC1717"],
    
        ['Actual' => "NCDFE1310",
        'Nuevo' => "NC1718"],
    
        ['Actual' => "NCDFE1311",
        'Nuevo' => "NC1719"],
    
        ['Actual' => "NCDFE1312",
        'Nuevo' => "NC1720"],
    
        ['Actual' => "NCDFE1313",
        'Nuevo' => "NC1721"],
    
        ['Actual' => "NCDFE1314",
        'Nuevo' => "NC1722"],
    
        ['Actual' => "NCDFE1315",
        'Nuevo' => "NC1723"],
    
        ['Actual' => "NCDFE1316",
        'Nuevo' => "NC1724"],
    
        ['Actual' => "NCDFE1317",
        'Nuevo' => "NC1725"],
    
        ['Actual' => "NCDFE1318",
        'Nuevo' => "NC1726"],
    
        ['Actual' => "NCDFE1319",
        'Nuevo' => "NC1727"],
    
        ['Actual' => "NCDFE1320",
        'Nuevo' => "NC1728"],
    
        ['Actual' => "NCDFE1321",
        'Nuevo' => "NC1729"],
    
        ['Actual' => "NCDFE1322",
        'Nuevo' => "NC1730"],
    
        ['Actual' => "NCDFE1323",
        'Nuevo' => "NC1731"],
    
        ['Actual' => "NCDFE1324",
        'Nuevo' => "NC1732"],
    
        ['Actual' => "NCDFE1325",
        'Nuevo' => "NC1733"],
    
        ['Actual' => "NCDFE1326",
        'Nuevo' => "NC1734"],
    
        ['Actual' => "NCDFE1327",
        'Nuevo' => "NC1735"],
    
        ['Actual' => "NCDFE1328",
        'Nuevo' => "NC1736"],
    
        ['Actual' => "NCDFE1329",
        'Nuevo' => "NC1737"],
    
        ['Actual' => "NCDFE1330",
        'Nuevo' => "NC1738"],
    
        ['Actual' => "NCDFE1331",
        'Nuevo' => "NC1739"],
    
        ['Actual' => "NCDFE1332",
        'Nuevo' => "NC1740"],
    
        ['Actual' => "NCDFE1333",
        'Nuevo' => "NC1741"],
    
        ['Actual' => "NCDFE1334",
        'Nuevo' => "NC1742"],
    
        ['Actual' => "NC407",
        'Nuevo' => "NC1743"],
    
        ['Actual' => "NC408",
        'Nuevo' => "NC1744"],
    
        ['Actual' => "NC409",
        'Nuevo' => "NC1745"],
    
        ['Actual' => "NC410",
        'Nuevo' => "NC1746"],
    
        ['Actual' => "NC411",
        'Nuevo' => "NC1747"],
    
        ['Actual' => "NC412",
        'Nuevo' => "NC1748"],
    
        ['Actual' => "NC413",
        'Nuevo' => "NC1749"],
    
        ['Actual' => "NC414",
        'Nuevo' => "NC1750"],
    
        ['Actual' => "NC415",
        'Nuevo' => "NC1751"],
    
        ['Actual' => "NC416",
        'Nuevo' => "NC1752"],
    
        ['Actual' => "NC417",
        'Nuevo' => "NC1753"],
    
        ['Actual' => "NC418",
        'Nuevo' => "NC1754"],
    
        ['Actual' => "NC419",
        'Nuevo' => "NC1755"],
    
        ['Actual' => "NC420",
        'Nuevo' => "NC1756"],
    
        ['Actual' => "NC421",
        'Nuevo' => "NC1757"],
    
        ['Actual' => "NC422",
        'Nuevo' => "NC1758"],
    
        ['Actual' => "NC423",
        'Nuevo' => "NC1759"],
    
        ['Actual' => "NCDFE1337",
        'Nuevo' => "NC1760"],
    
        ['Actual' => "NCDFE1338",
        'Nuevo' => "NC1761"],
    
        ['Actual' => "NCDFE1339",
        'Nuevo' => "NC1762"],
    
        ['Actual' => "NCDFE1340",
        'Nuevo' => "NC1763"],
    
        ['Actual' => "NCDFE1341",
        'Nuevo' => "NC1764"],
    
        ['Actual' => "NCDFE1342",
        'Nuevo' => "NC1765"],
    
        ['Actual' => "NCDFE1343",
        'Nuevo' => "NC1766"],
    
        ['Actual' => "NCDFE1344",
        'Nuevo' => "NC1767"],
    
        ['Actual' => "NCDFE1345",
        'Nuevo' => "NC1768"],
    
        ['Actual' => "NCDFE1346",
        'Nuevo' => "NC1769"],
    
        ['Actual' => "NCDFE1347",
        'Nuevo' => "NC1770"],
    
        ['Actual' => "NCDFE1348",
        'Nuevo' => "NC1771"],
    
        ['Actual' => "NCDFE1349",
        'Nuevo' => "NC1772"],
    
        ['Actual' => "NCDFE1350",
        'Nuevo' => "NC1773"],
    
        ['Actual' => "NCDFE1351",
        'Nuevo' => "NC1774"],
    
        ['Actual' => "NCDFE1352",
        'Nuevo' => "NC1775"],
    
        ['Actual' => "NCDFE1353",
        'Nuevo' => "NC1776"],
    
        ['Actual' => "NCDFE1354",
        'Nuevo' => "NC1777"],
    
        ['Actual' => "NCDFE1355",
        'Nuevo' => "NC1778"],
    
        ['Actual' => "NCDFE1356",
        'Nuevo' => "NC1779"],
    
        ['Actual' => "NCDFE1357",
        'Nuevo' => "NC1780"],
    
        ['Actual' => "NCDFE1358",
        'Nuevo' => "NC1781"],
    
        ['Actual' => "NCDFE1359",
        'Nuevo' => "NC1782"],
    
        ['Actual' => "NCDFE1360",
        'Nuevo' => "NC1783"],
    
        ['Actual' => "NCDFE1361",
        'Nuevo' => "NC1784"],
    
        ['Actual' => "NCDFE1362",
        'Nuevo' => "NC1785"],
    
        ['Actual' => "NCDFE1363",
        'Nuevo' => "NC1786"],
    
        ['Actual' => "NCDFE1364",
        'Nuevo' => "NC1787"],
    
        ['Actual' => "NCDFE1365",
        'Nuevo' => "NC1788"],
    
        ['Actual' => "NCDFE1366",
        'Nuevo' => "NC1789"],
    
        ['Actual' => "NCDFE1367",
        'Nuevo' => "NC1790"],
    
        ['Actual' => "NCDFE1368",
        'Nuevo' => "NC1791"],
    
        ['Actual' => "NCDFE1369",
        'Nuevo' => "NC1792"],
    
        ['Actual' => "NCDFE1370",
        'Nuevo' => "NC1793"],
    
        ['Actual' => "NCDFE1371",
        'Nuevo' => "NC1794"],
    
        ['Actual' => "NCDFE1372",
        'Nuevo' => "NC1795"],
    
        ['Actual' => "NCDFE1373",
        'Nuevo' => "NC1796"],
    
        ['Actual' => "NCDFE1374",
        'Nuevo' => "NC1797"],
    
        ['Actual' => "NCDFE1375",
        'Nuevo' => "NC1798"],
    
        ['Actual' => "NCDFE1376",
        'Nuevo' => "NC1799"],
    
        ['Actual' => "NCDFE1377",
        'Nuevo' => "NC1800"],
    
        ['Actual' => "NCDFE1378",
        'Nuevo' => "NC1801"],
    
        ['Actual' => "NCDFE1379",
        'Nuevo' => "NC1802"],
    
        ['Actual' => "NCDFE1380",
        'Nuevo' => "NC1803"],
    
        ['Actual' => "NCDFE1381",
        'Nuevo' => "NC1804"],
    
        ['Actual' => "NCDFE1382",
        'Nuevo' => "NC1805"],
    
        ['Actual' => "NCDFE1383",
        'Nuevo' => "NC1806"],
    
        ['Actual' => "NCDFE1384",
        'Nuevo' => "NC1807"],
    
        ['Actual' => "NCDFE1385",
        'Nuevo' => "NC1808"],
    
        ['Actual' => "NCDFE1386",
        'Nuevo' => "NC1809"],
    
        ['Actual' => "NCDFE1387",
        'Nuevo' => "NC1810"],
    
        ['Actual' => "NCDFE1388",
        'Nuevo' => "NC1811"],
    
        ['Actual' => "NCDFE1389",
        'Nuevo' => "NC1812"],
    
        ['Actual' => "NCDFE1390",
        'Nuevo' => "NC1813"],
    
        ['Actual' => "NCDFE1391",
        'Nuevo' => "NC1814"],
    
        ['Actual' => "NCDFE1392",
        'Nuevo' => "NC1815"],
    
        ['Actual' => "NCDFE1393",
        'Nuevo' => "NC1816"],
    
        ['Actual' => "NCDFE1394",
        'Nuevo' => "NC1817"],
    
        ['Actual' => "NCDFE1395",
        'Nuevo' => "NC1818"],
    
        ['Actual' => "NCDFE1396",
        'Nuevo' => "NC1819"],
    
        ['Actual' => "NCDFE1397",
        'Nuevo' => "NC1820"],
    
        ['Actual' => "NCDFE1398",
        'Nuevo' => "NC1821"],
    
        ['Actual' => "NCDFE1399",
        'Nuevo' => "NC1822"],
    
        ['Actual' => "NCDFE1400",
        'Nuevo' => "NC1823"],
    
        ['Actual' => "NCDFE1401",
        'Nuevo' => "NC1824"],
    
        ['Actual' => "NCDFE1402",
        'Nuevo' => "NC1825"],
    
        ['Actual' => "NCDFE1403",
        'Nuevo' => "NC1826"],
    
        ['Actual' => "NCDFE1404",
        'Nuevo' => "NC1827"],
    
        ['Actual' => "NCDFE1405",
        'Nuevo' => "NC1828"],
    
        ['Actual' => "NCDFE1406",
        'Nuevo' => "NC1829"],
    
        ['Actual' => "NCDFE1407",
        'Nuevo' => "NC1830"],
    
        ['Actual' => "NCDFE1408",
        'Nuevo' => "NC1831"],
    
        ['Actual' => "NCDFE1409",
        'Nuevo' => "NC1832"],
    
        ['Actual' => "NCDFE1410",
        'Nuevo' => "NC1833"],
    
        ['Actual' => "NCDFE1411",
        'Nuevo' => "NC1834"],
    
        ['Actual' => "NCDFE1412",
        'Nuevo' => "NC1835"],
    
        ['Actual' => "NCDFE1413",
        'Nuevo' => "NC1836"],
    
        ['Actual' => "NCDFE1414",
        'Nuevo' => "NC1837"],
    
        ['Actual' => "NCDFE1415",
        'Nuevo' => "NC1838"],
    
        ['Actual' => "NCDFE1416",
        'Nuevo' => "NC1839"],
    
        ['Actual' => "NCDFE1417",
        'Nuevo' => "NC1840"],
    
        ['Actual' => "NCDFE1418",
        'Nuevo' => "NC1841"],
    
        ['Actual' => "NCDFE1419",
        'Nuevo' => "NC1842"],
    
        ['Actual' => "NCDFE1420",
        'Nuevo' => "NC1843"],
    
        ['Actual' => "NCDFE1421",
        'Nuevo' => "NC1844"],
    
        ['Actual' => "NCDFE1422",
        'Nuevo' => "NC1845"],
    
        ['Actual' => "NCDFE1423",
        'Nuevo' => "NC1846"],
    
        ['Actual' => "NCDFE1424",
        'Nuevo' => "NC1847"],
    
        ['Actual' => "NCDFE1425",
        'Nuevo' => "NC1848"],
    
        ['Actual' => "NCDFE1426",
        'Nuevo' => "NC1849"],
    
        ['Actual' => "NCDFE1427",
        'Nuevo' => "NC1850"],
    
        ['Actual' => "NCDFE1428",
        'Nuevo' => "NC1851"],
    
        ['Actual' => "NCDFE1429",
        'Nuevo' => "NC1852"],
    
        ['Actual' => "NCDFE1430",
        'Nuevo' => "NC1853"],
    
        ['Actual' => "NCDFE1431",
        'Nuevo' => "NC1854"],
    
        ['Actual' => "NCDFE1432",
        'Nuevo' => "NC1855"],
    
        ['Actual' => "NCDFE1433",
        'Nuevo' => "NC1856"],
    
        ['Actual' => "NCDFE1434",
        'Nuevo' => "NC1857"],
    
        ['Actual' => "NCDFE1435",
        'Nuevo' => "NC1858"],
    
        ['Actual' => "NCDFE1436",
        'Nuevo' => "NC1859"],
    
        ['Actual' => "NCDFE1437",
        'Nuevo' => "NC1860"],
    
        ['Actual' => "NCDFE1438",
        'Nuevo' => "NC1861"],
    
        ['Actual' => "NCDFE1439",
        'Nuevo' => "NC1862"],
    
        ['Actual' => "NCDFE1440",
        'Nuevo' => "NC1863"],
    
        ['Actual' => "NCDFE1441",
        'Nuevo' => "NC1864"],
    
        ['Actual' => "NCDFE1442",
        'Nuevo' => "NC1865"],
    
        ['Actual' => "NCDFE1443",
        'Nuevo' => "NC1866"],
    
        ['Actual' => "NCDFE1444",
        'Nuevo' => "NC1867"],
    
        ['Actual' => "NCDFE1445",
        'Nuevo' => "NC1868"],
    
        ['Actual' => "NCDFE1446",
        'Nuevo' => "NC1869"],
    
        ['Actual' => "NCDFE1447",
        'Nuevo' => "NC1870"],
    
        ['Actual' => "NCDFE1448",
        'Nuevo' => "NC1871"],
    
        ['Actual' => "NCDFE1449",
        'Nuevo' => "NC1872"],
    
        ['Actual' => "NCDFE1450",
        'Nuevo' => "NC1873"],
    
        ['Actual' => "NCDFE1451",
        'Nuevo' => "NC1874"],
    
        ['Actual' => "NCDFE1452",
        'Nuevo' => "NC1875"],
    
        ['Actual' => "NCDFE1453",
        'Nuevo' => "NC1876"],
    
        ['Actual' => "NCDFE1454",
        'Nuevo' => "NC1877"],
    
        ['Actual' => "NCDFE1455",
        'Nuevo' => "NC1878"],
    
        ['Actual' => "NCDFE1456",
        'Nuevo' => "NC1879"],
    
        ['Actual' => "NCDFE1457",
        'Nuevo' => "NC1880"],
    
        ['Actual' => "NCDFE1458",
        'Nuevo' => "NC1881"],
    
        ['Actual' => "NCDFE1459",
        'Nuevo' => "NC1882"],
    
        ['Actual' => "NCDFE1460",
        'Nuevo' => "NC1883"],
    
        ['Actual' => "NCDFE1461",
        'Nuevo' => "NC1884"],
    
        ['Actual' => "NCDFE1462",
        'Nuevo' => "NC1885"],
    
        ['Actual' => "NCDFE1463",
        'Nuevo' => "NC1886"],
    
        ['Actual' => "NCDFE1464",
        'Nuevo' => "NC1887"],
    
        ['Actual' => "NCDFE1465",
        'Nuevo' => "NC1888"],
    
        ['Actual' => "NCDFE1466",
        'Nuevo' => "NC1889"],
    
        ['Actual' => "NCDFE1467",
        'Nuevo' => "NC1890"],
    
        ['Actual' => "NCDFE1468",
        'Nuevo' => "NC1891"],
    
        ['Actual' => "NCDFE1469",
        'Nuevo' => "NC1892"],
    
        ['Actual' => "NCDFE1470",
        'Nuevo' => "NC1893"],
    
        ['Actual' => "NCDFE1471",
        'Nuevo' => "NC1894"],
    
        ['Actual' => "NCDFE1472",
        'Nuevo' => "NC1895"],
    
        ['Actual' => "NCDFE1473",
        'Nuevo' => "NC1896"],
    
        ['Actual' => "NCDFE1474",
        'Nuevo' => "NC1897"],
    
        ['Actual' => "NCDFE1475",
        'Nuevo' => "NC1898"],
    
        ['Actual' => "NCDFE1476",
        'Nuevo' => "NC1899"],
    
        ['Actual' => "NCDFE1477",
        'Nuevo' => "NC1900"],
    
        ['Actual' => "NCDFE1478",
        'Nuevo' => "NC1901"],
    
        ['Actual' => "NCDFE1479",
        'Nuevo' => "NC1902"],
    
        ['Actual' => "NCDFE1480",
        'Nuevo' => "NC1903"],
    
        ['Actual' => "NCDFE1481",
        'Nuevo' => "NC1904"],
    
        ['Actual' => "NCDFE1482",
        'Nuevo' => "NC1905"],
    
        ['Actual' => "NCDFE1483",
        'Nuevo' => "NC1906"],
    
        ['Actual' => "NCDFE1484",
        'Nuevo' => "NC1907"],
    
        ['Actual' => "NCDFE1485",
        'Nuevo' => "NC1908"],
    
        ['Actual' => "NCDFE1486",
        'Nuevo' => "NC1909"],
    
        ['Actual' => "NCDFE1487",
        'Nuevo' => "NC1910"],
    
        ['Actual' => "NCDFE1488",
        'Nuevo' => "NC1911"],
    
        ['Actual' => "NCDFE1489",
        'Nuevo' => "NC1912"],
    
        ['Actual' => "NCDFE1490",
        'Nuevo' => "NC1913"],
    
        ['Actual' => "NCDFE1491",
        'Nuevo' => "NC1914"],
    
        ['Actual' => "NCDFE1492",
        'Nuevo' => "NC1915"],
    
        ['Actual' => "NCDFE1493",
        'Nuevo' => "NC1916"],
    
        ['Actual' => "NCDFE1494",
        'Nuevo' => "NC1917"],
    
        ['Actual' => "NCDFE1495",
        'Nuevo' => "NC1918"],
    
        ['Actual' => "NCDFE1496",
        'Nuevo' => "NC1919"],
    
        ['Actual' => "NCDFE1497",
        'Nuevo' => "NC1920"],
    
        ['Actual' => "NCDFE1498",
        'Nuevo' => "NC1921"],
    
        ['Actual' => "NCDFE1499",
        'Nuevo' => "NC1922"],
    
        ['Actual' => "NCDFE1500",
        'Nuevo' => "NC1923"],
    
        ['Actual' => "NCDFE1501",
        'Nuevo' => "NC1924"],
    
        ['Actual' => "NCDFE1502",
        'Nuevo' => "NC1925"],
    
        ['Actual' => "NCDFE1503",
        'Nuevo' => "NC1926"],
    
        ['Actual' => "NCDFE1504",
        'Nuevo' => "NC1927"],
    
        ['Actual' => "NCDFE1505",
        'Nuevo' => "NC1928"],
    
        ['Actual' => "NCDFE1506",
        'Nuevo' => "NC1929"],
    
        ['Actual' => "NCDFE1507",
        'Nuevo' => "NC1930"],
    
        ['Actual' => "NCDFE1508",
        'Nuevo' => "NC1931"],
    
        ['Actual' => "NCDFE1509",
        'Nuevo' => "NC1932"],
    
        ['Actual' => "NCDFE1510",
        'Nuevo' => "NC1933"],
    
        ['Actual' => "NCDFE1511",
        'Nuevo' => "NC1934"],
    
        ['Actual' => "NCDFE1512",
        'Nuevo' => "NC1935"],
    
        ['Actual' => "NCDFE1513",
        'Nuevo' => "NC1936"],
    
        ['Actual' => "NCDFE1514",
        'Nuevo' => "NC1937"],
    
        ['Actual' => "NCDFE1515",
        'Nuevo' => "NC1938"],
    
        ['Actual' => "NCDFE1516",
        'Nuevo' => "NC1939"],
    
        ['Actual' => "NCDFE1517",
        'Nuevo' => "NC1940"],
    
        ['Actual' => "NCDFE1518",
        'Nuevo' => "NC1941"],
    
        ['Actual' => "NCDFE1519",
        'Nuevo' => "NC1942"],
    
        ['Actual' => "NCDFE1520",
        'Nuevo' => "NC1943"],
    
        ['Actual' => "NCDFE1521",
        'Nuevo' => "NC1944"],
    
        ['Actual' => "NCDFE1522",
        'Nuevo' => "NC1945"],
    
        ['Actual' => "NCDFE1523",
        'Nuevo' => "NC1946"],
    
        ['Actual' => "NCDFE1524",
        'Nuevo' => "NC1947"],
    
        ['Actual' => "NCDFE1525",
        'Nuevo' => "NC1948"],
    
        ['Actual' => "NCDFE1526",
        'Nuevo' => "NC1949"],
    
        ['Actual' => "NCDFE1527",
        'Nuevo' => "NC1950"],
    
        ['Actual' => "NCDFE1528",
        'Nuevo' => "NC1951"],
    
        ['Actual' => "NCDFE1529",
        'Nuevo' => "NC1952"],
    
        ['Actual' => "NCDFE1530",
        'Nuevo' => "NC1953"],
    
        ['Actual' => "NCDFE1531",
        'Nuevo' => "NC1954"],
    
        ['Actual' => "NCDFE1532",
        'Nuevo' => "NC1955"],
    
        ['Actual' => "NCDFE1533",
        'Nuevo' => "NC1956"],
    
        ['Actual' => "NCDFE1534",
        'Nuevo' => "NC1957"],
    
        ['Actual' => "NCDFE1535",
        'Nuevo' => "NC1958"],
    
        ['Actual' => "NCDFE1536",
        'Nuevo' => "NC1959"],
    
        ['Actual' => "NCDFE1537",
        'Nuevo' => "NC1960"],
    
        ['Actual' => "NCDFE1538",
        'Nuevo' => "NC1961"],
    
        ['Actual' => "NCDFE1539",
        'Nuevo' => "NC1962"],
    
        ['Actual' => "NCDFE1540",
        'Nuevo' => "NC1963"],
    
        ['Actual' => "NCDFE1541",
        'Nuevo' => "NC1964"],
    
        ['Actual' => "NCDFE1542",
        'Nuevo' => "NC1965"],
    
        ['Actual' => "NCDFE1543",
        'Nuevo' => "NC1966"],
    
        ['Actual' => "NCDFE1544",
        'Nuevo' => "NC1967"],
    
        ['Actual' => "NCDFE1545",
        'Nuevo' => "NC1968"],
    
        ['Actual' => "NCDFE1546",
        'Nuevo' => "NC1969"],
    
        ['Actual' => "NCDFE1547",
        'Nuevo' => "NC1970"],
    
        ['Actual' => "NCDFE1548",
        'Nuevo' => "NC1971"],
    
        ['Actual' => "NCDFE1549",
        'Nuevo' => "NC1972"],
    
        ['Actual' => "NCDFE1550",
        'Nuevo' => "NC1973"],
    
        ['Actual' => "NCDFE1551",
        'Nuevo' => "NC1974"],
    
        ['Actual' => "NCDFE1552",
        'Nuevo' => "NC1975"],
    
        ['Actual' => "NCDFE1553",
        'Nuevo' => "NC1976"],
    
        ['Actual' => "NCDFE1554",
        'Nuevo' => "NC1977"],
    
        ['Actual' => "NCDFE1555",
        'Nuevo' => "NC1978"],
    
        ['Actual' => "NCDFE1556",
        'Nuevo' => "NC1979"],
    
        ['Actual' => "NCDFE1557",
        'Nuevo' => "NC1980"],
    
        ['Actual' => "NCDFE1558",
        'Nuevo' => "NC1981"],
    
        ['Actual' => "NCDFE1559",
        'Nuevo' => "NC1982"],
    
        ['Actual' => "NCDFE1560",
        'Nuevo' => "NC1983"],
    
        ['Actual' => "NCDFE1561",
        'Nuevo' => "NC1984"],
    
        ['Actual' => "NCDFE1562",
        'Nuevo' => "NC1985"],
    
        ['Actual' => "NCDFE1563",
        'Nuevo' => "NC1986"],
    
        ['Actual' => "NCDFE1564",
        'Nuevo' => "NC1987"],
    
        ['Actual' => "NCDFE1565",
        'Nuevo' => "NC1988"],
    
        ['Actual' => "NCDFE1566",
        'Nuevo' => "NC1989"],
    
        ['Actual' => "NCDFE1567",
        'Nuevo' => "NC1990"],
    
        ['Actual' => "NCDFE1568",
        'Nuevo' => "NC1991"],
    
        ['Actual' => "NCDFE1569",
        'Nuevo' => "NC1992"],
    
        ['Actual' => "NCDFE1570",
        'Nuevo' => "NC1993"],
    
        ['Actual' => "NCDFE1571",
        'Nuevo' => "NC1994"],
    
        ['Actual' => "NCDFE1572",
        'Nuevo' => "NC1995"],
    
        ['Actual' => "NCDFE1573",
        'Nuevo' => "NC1996"],
    
        ['Actual' => "NCDFE1574",
        'Nuevo' => "NC1997"],
    
        ['Actual' => "NCDFE1575",
        'Nuevo' => "NC1998"],
    
        ['Actual' => "NCDFE1576",
        'Nuevo' => "NC1999"],
    
        ['Actual' => "NCDFE1577",
        'Nuevo' => "NC2000"],
    
        ['Actual' => "NCDFE1578",
        'Nuevo' => "NC2001"],
    
        ['Actual' => "NCDFE1579",
        'Nuevo' => "NC2002"],
    
        ['Actual' => "NCDFE1580",
        'Nuevo' => "NC2003"],
    
        ['Actual' => "NCDFE1581",
        'Nuevo' => "NC2004"],
    
        ['Actual' => "NCDFE1582",
        'Nuevo' => "NC2005"],
    
        ['Actual' => "NCDFE1583",
        'Nuevo' => "NC2006"],
    
        ['Actual' => "NCDFE1584",
        'Nuevo' => "NC2007"],
    
        ['Actual' => "NCDFE1585",
        'Nuevo' => "NC2008"],
    
        ['Actual' => "NCDFE1586",
        'Nuevo' => "NC2009"],
    
        ['Actual' => "NCDFE1587",
        'Nuevo' => "NC2010"],
    
        ['Actual' => "NCDFE1588",
        'Nuevo' => "NC2011"],
    
        ['Actual' => "NCDFE1589",
        'Nuevo' => "NC2012"],
    
        ['Actual' => "NCDFE1590",
        'Nuevo' => "NC2013"],
    
        ['Actual' => "NCDFE1591",
        'Nuevo' => "NC2014"],
    
        ['Actual' => "NCDFE1592",
        'Nuevo' => "NC2015"],
    
        ['Actual' => "NCDFE1593",
        'Nuevo' => "NC2016"],
    
        ['Actual' => "NCDFE1594",
        'Nuevo' => "NC2017"],
    
        ['Actual' => "NCDFE1595",
        'Nuevo' => "NC2018"],
    
        ['Actual' => "NCDFE1596",
        'Nuevo' => "NC2019"],
    
        ['Actual' => "NCDFE1597",
        'Nuevo' => "NC2020"],
    
        ['Actual' => "NCDFE1598",
        'Nuevo' => "NC2021"],
    
        ['Actual' => "NCDFE1599",
        'Nuevo' => "NC2022"],
    
        ['Actual' => "NCDFE1600",
        'Nuevo' => "NC2023"],
    
        ['Actual' => "NCDFE1601",
        'Nuevo' => "NC2024"],
    
        ['Actual' => "NCDFE1602",
        'Nuevo' => "NC2025"],
    
        ['Actual' => "NCDFE1603",
        'Nuevo' => "NC2026"],
    
        ['Actual' => "NCDFE1604",
        'Nuevo' => "NC2027"],
    
        ['Actual' => "NCDFE1605",
        'Nuevo' => "NC2028"],
    
        ['Actual' => "NCDFE1606",
        'Nuevo' => "NC2029"],
    
        ['Actual' => "NCDFE1607",
        'Nuevo' => "NC2030"],
    
        ['Actual' => "NCDFE1608",
        'Nuevo' => "NC2031"],
    
        ['Actual' => "NCDFE1609",
        'Nuevo' => "NC2032"],
    
        ['Actual' => "NCDFE1610",
        'Nuevo' => "NC2033"],
    
        ['Actual' => "NCDFE1611",
        'Nuevo' => "NC2034"],
    
        ['Actual' => "NCDFE1612",
        'Nuevo' => "NC2035"],
    
        ['Actual' => "NCDFE1613",
        'Nuevo' => "NC2036"],
    
        ['Actual' => "NCDFE1614",
        'Nuevo' => "NC2037"],
    
        ['Actual' => "NCDFE1615",
        'Nuevo' => "NC2038"],
    
        ['Actual' => "NCDFE1616",
        'Nuevo' => "NC2039"],
    
        ['Actual' => "NCDFE1617",
        'Nuevo' => "NC2040"],
    
        ['Actual' => "NCDFE1618",
        'Nuevo' => "NC2041"],
    
        ['Actual' => "NCDFE1619",
        'Nuevo' => "NC2042"],
    
        ['Actual' => "NCDFE1620",
        'Nuevo' => "NC2043"],
    
        ['Actual' => "NCDFE1621",
        'Nuevo' => "NC2044"],
    
        ['Actual' => "NCDFE1622",
        'Nuevo' => "NC2045"],
    
        ['Actual' => "NCDFE1623",
        'Nuevo' => "NC2046"],
    
        ['Actual' => "NCDFE1624",
        'Nuevo' => "NC2047"],
    
        ['Actual' => "NCDFE1625",
        'Nuevo' => "NC2048"],
    
        ['Actual' => "NCDFE1626",
        'Nuevo' => "NC2049"],
    
        ['Actual' => "NCDFE1627",
        'Nuevo' => "NC2050"],
    
        ['Actual' => "NCDFE1628",
        'Nuevo' => "NC2051"],
    
        ['Actual' => "NCDFE1629",
        'Nuevo' => "NC2052"],
    
        ['Actual' => "NCDFE1630",
        'Nuevo' => "NC2053"],
    
        ['Actual' => "NCDFE1631",
        'Nuevo' => "NC2054"],
    
        ['Actual' => "NCDFE1632",
        'Nuevo' => "NC2055"],
    
        ['Actual' => "NCDFE1633",
        'Nuevo' => "NC2056"],
    
        ['Actual' => "NCDFE1634",
        'Nuevo' => "NC2057"],
    
        ['Actual' => "NCDFE1635",
        'Nuevo' => "NC2058"],
    
        ['Actual' => "NCDFE1636",
        'Nuevo' => "NC2059"],
    
        ['Actual' => "NCDFE1637",
        'Nuevo' => "NC2060"],
    
        ['Actual' => "NCDFE1638",
        'Nuevo' => "NC2061"],
    
        ['Actual' => "NCDFE1639",
        'Nuevo' => "NC2062"],
    
        ['Actual' => "NCDFE1640",
        'Nuevo' => "NC2063"],
    
        ['Actual' => "NCDFE1641",
        'Nuevo' => "NC2064"],
    
        ['Actual' => "NCDFE1642",
        'Nuevo' => "NC2065"],
    
        ['Actual' => "NCDFE1643",
        'Nuevo' => "NC2066"],
    
        ['Actual' => "NCDFE1644",
        'Nuevo' => "NC2067"],
    
        ['Actual' => "NCDFE1645",
        'Nuevo' => "NC2068"],
    
        ['Actual' => "NCDFE1646",
        'Nuevo' => "NC2069"],
    
        ['Actual' => "NCDFE1647",
        'Nuevo' => "NC2070"],
    
        ['Actual' => "NCDFE1648",
        'Nuevo' => "NC2071"],
    
        ['Actual' => "NCDFE1649",
        'Nuevo' => "NC2072"],
    
        ['Actual' => "NCDFE1650",
        'Nuevo' => "NC2073"],
    
        ['Actual' => "NCDFE1651",
        'Nuevo' => "NC2074"],
    
        ['Actual' => "NCDFE1652",
        'Nuevo' => "NC2075"],
    
        ['Actual' => "NCDFE1653",
        'Nuevo' => "NC2076"],
    
        ['Actual' => "NCDFE1654",
        'Nuevo' => "NC2077"],
    
        ['Actual' => "NCDFE1655",
        'Nuevo' => "NC2078"],
    
        ['Actual' => "NCDFE1656",
        'Nuevo' => "NC2079"],
    
        ['Actual' => "NCDFE1657",
        'Nuevo' => "NC2080"],
    
        ['Actual' => "NCDFE1658",
        'Nuevo' => "NC2081"],
    
        ['Actual' => "NCDFE1659",
        'Nuevo' => "NC2082"],
    
        ['Actual' => "NCDFE1660",
        'Nuevo' => "NC2083"],
    
        ['Actual' => "NCDFE1661",
        'Nuevo' => "NC2084"],
    
        ['Actual' => "NCDFE1662",
        'Nuevo' => "NC2085"],
    
        ['Actual' => "NCDFE1663",
        'Nuevo' => "NC2086"],
    
        ['Actual' => "NCDFE1664",
        'Nuevo' => "NC2087"],
    
        ['Actual' => "NCDFE1665",
        'Nuevo' => "NC2088"],
    
        ['Actual' => "NCDFE1666",
        'Nuevo' => "NC2089"],
    
        ['Actual' => "NCDFE1667",
        'Nuevo' => "NC2090"],
    
        ['Actual' => "NCDFE1668",
        'Nuevo' => "NC2091"],
    
        ['Actual' => "NCDFE1669",
        'Nuevo' => "NC2092"],
    
        ['Actual' => "NCDFE1670",
        'Nuevo' => "NC2093"],
    
        ['Actual' => "NCDFE1671",
        'Nuevo' => "NC2094"],
    
        ['Actual' => "NCDFE1672",
        'Nuevo' => "NC2095"],
    
        ['Actual' => "NCDFE1673",
        'Nuevo' => "NC2096"],
    
        ['Actual' => "NCDFE1674",
        'Nuevo' => "NC2097"],
    
        ['Actual' => "NCDFE1675",
        'Nuevo' => "NC2098"],
    
        ['Actual' => "NCDFE1676",
        'Nuevo' => "NC2099"],
    
        ['Actual' => "NCDFE1677",
        'Nuevo' => "NC2100"],
    
        ['Actual' => "NCDFE1678",
        'Nuevo' => "NC2101"],
    
        ['Actual' => "NCDFE1679",
        'Nuevo' => "NC2102"],
    
        ['Actual' => "NCDFE1680",
        'Nuevo' => "NC2103"],
    
        ['Actual' => "NCDFE1681",
        'Nuevo' => "NC2104"],
    
        ['Actual' => "NCDFE1682",
        'Nuevo' => "NC2105"],
    
        ['Actual' => "NCDFE1683",
        'Nuevo' => "NC2106"],
    
        ['Actual' => "NCDFE1684",
        'Nuevo' => "NC2107"],
    
        ['Actual' => "NCDFE1685",
        'Nuevo' => "NC2108"],
    
        ['Actual' => "NCDFE1686",
        'Nuevo' => "NC2109"],
    
        ['Actual' => "NCDFE1687",
        'Nuevo' => "NC2110"],
    
        ['Actual' => "NCDFE1688",
        'Nuevo' => "NC2111"],
    
        ['Actual' => "NCDFE1689",
        'Nuevo' => "NC2112"],
    
        ['Actual' => "NCDFE1690",
        'Nuevo' => "NC2113"],
    
        ['Actual' => "NCDFE1691",
        'Nuevo' => "NC2114"],
    
        ['Actual' => "NCDFE1692",
        'Nuevo' => "NC2115"],
    
        ['Actual' => "NCDFE1693",
        'Nuevo' => "NC2116"],
    
        ['Actual' => "NCDFE1694",
        'Nuevo' => "NC2117"],
    
        ['Actual' => "NCDFE1695",
        'Nuevo' => "NC2118"],
    
        ['Actual' => "NCDFE1696",
        'Nuevo' => "NC2119"],
    
        ['Actual' => "NCDFE1697",
        'Nuevo' => "NC2120"],
    
        ['Actual' => "NCDFE1698",
        'Nuevo' => "NC2121"],
    
        ['Actual' => "NCDFE1699",
        'Nuevo' => "NC2122"],
    
        ['Actual' => "NCDFE1700",
        'Nuevo' => "NC2123"],
    
        ['Actual' => "NCDFE1701",
        'Nuevo' => "NC2124"],
    
        ['Actual' => "NCDFE1702",
        'Nuevo' => "NC2125"],
    
        ['Actual' => "NCDFE1703",
        'Nuevo' => "NC2126"],
    
        ['Actual' => "NCDFE1704",
        'Nuevo' => "NC2127"],
    
        ['Actual' => "NCDFE1705",
        'Nuevo' => "NC2128"],
    
        ['Actual' => "NCDFE1706",
        'Nuevo' => "NC2129"],
    
        ['Actual' => "NCDFE1707",
        'Nuevo' => "NC2130"],
    
        ['Actual' => "NCDFE1708",
        'Nuevo' => "NC2131"],
    
        ['Actual' => "NCDFE1709",
        'Nuevo' => "NC2132"],
    
        ['Actual' => "NCDFE1710",
        'Nuevo' => "NC2133"],
    
        ['Actual' => "NCDFE1711",
        'Nuevo' => "NC2134"],
    
        ['Actual' => "NCDFE1712",
        'Nuevo' => "NC2135"],
    
        ['Actual' => "NCDFE1713",
        'Nuevo' => "NC2136"],
    
        ['Actual' => "NCDFE1714",
        'Nuevo' => "NC2137"],
    
        ['Actual' => "NCDFE1715",
        'Nuevo' => "NC2138"],
    
        ['Actual' => "NCDFE1716",
        'Nuevo' => "NC2139"],
    
        ['Actual' => "NCDFE1717",
        'Nuevo' => "NC2140"],
    
        ['Actual' => "NCDFE1718",
        'Nuevo' => "NC2141"],
    
        ['Actual' => "NCDFE1719",
        'Nuevo' => "NC2142"],
    
        ['Actual' => "NCDFE1720",
        'Nuevo' => "NC2143"],
    
        ['Actual' => "NCDFE1721",
        'Nuevo' => "NC2144"],
    
        ['Actual' => "NCDFE1722",
        'Nuevo' => "NC2145"],
    
        ['Actual' => "NCDFE1723",
        'Nuevo' => "NC2146"],
    
        ['Actual' => "NCDFE1724",
        'Nuevo' => "NC2147"],
    
        ['Actual' => "NCDFE1725",
        'Nuevo' => "NC2148"],
    
        ['Actual' => "NCDFE1726",
        'Nuevo' => "NC2149"],
    
        ['Actual' => "NCDFE1727",
        'Nuevo' => "NC2150"],
    
        ['Actual' => "NCDFE1728",
        'Nuevo' => "NC2151"],
    
        ['Actual' => "NCDFE1729",
        'Nuevo' => "NC2152"],
    
        ['Actual' => "NCDFE1730",
        'Nuevo' => "NC2153"],
    
        ['Actual' => "NCDFE1731",
        'Nuevo' => "NC2154"],
    
        ['Actual' => "NCDFE1732",
        'Nuevo' => "NC2155"],
    
        ['Actual' => "NCDFE1733",
        'Nuevo' => "NC2156"],
    
        ['Actual' => "NCDFE1734",
        'Nuevo' => "NC2157"],
    
        ['Actual' => "NCDFE1735",
        'Nuevo' => "NC2158"],
    
        ['Actual' => "NCDFE1736",
        'Nuevo' => "NC2159"],
    
        ['Actual' => "NCDFE1737",
        'Nuevo' => "NC2160"],
    
        ['Actual' => "NCDFE1738",
        'Nuevo' => "NC2161"],
    
        ['Actual' => "NCDFE1739",
        'Nuevo' => "NC2162"],
    
        ['Actual' => "NCDFE1740",
        'Nuevo' => "NC2163"],
    
        ['Actual' => "NCDFE1741",
        'Nuevo' => "NC2164"],
    
        ['Actual' => "NCDFE1742",
        'Nuevo' => "NC2165"],
    
        ['Actual' => "NCDFE1743",
        'Nuevo' => "NC2166"],
    
        ['Actual' => "NCDFE1744",
        'Nuevo' => "NC2167"],
    
        ['Actual' => "NCDFE1745",
        'Nuevo' => "NC2168"],
    
        ['Actual' => "NCDFE1746",
        'Nuevo' => "NC2169"],
    
        ['Actual' => "NCDFE1747",
        'Nuevo' => "NC2170"],
    
        ['Actual' => "NCDFE1748",
        'Nuevo' => "NC2171"],
    
        ['Actual' => "NC424",
        'Nuevo' => "NC2172"],
    
        ['Actual' => "NC425",
        'Nuevo' => "NC2173"],
    
        ['Actual' => "NC426",
        'Nuevo' => "NC2174"],
    
        ['Actual' => "NC427",
        'Nuevo' => "NC2175"],
    
        ['Actual' => "NC428",
        'Nuevo' => "NC2176"],
    
        ['Actual' => "NC429",
        'Nuevo' => "NC2177"],
    
        ['Actual' => "NCDFE1749",
        'Nuevo' => "NC2178"],
    
        ['Actual' => "NCDFE1750",
        'Nuevo' => "NC2179"],
    
        ['Actual' => "NC430",
        'Nuevo' => "NC2180"],
    
        ['Actual' => "NC431",
        'Nuevo' => "NC2181"],
    
        ['Actual' => "NCDFE1751",
        'Nuevo' => "NC2182"],
    
        ['Actual' => "NCDFE1752",
        'Nuevo' => "NC2183"],
    
        ['Actual' => "NC432",
        'Nuevo' => "NC2184"],
    
        ['Actual' => "NCDFE1753",
        'Nuevo' => "NC2185"],
    
        ['Actual' => "NCDFE1754",
        'Nuevo' => "NC2186"],
    
        ['Actual' => "NCDFE1755",
        'Nuevo' => "NC2187"],
    
        ['Actual' => "NCDFE1756",
        'Nuevo' => "NC2188"],
    
        ['Actual' => "NC433",
        'Nuevo' => "NC2189"],
    
        ['Actual' => "NC434",
        'Nuevo' => "NC2190"],
    
        ['Actual' => "NC435",
        'Nuevo' => "NC2191"],
    
        ['Actual' => "NC436",
        'Nuevo' => "NC2192"],
    
        ['Actual' => "NC437",
        'Nuevo' => "NC2193"],
    
        ['Actual' => "NC438",
        'Nuevo' => "NC2194"],
    
        ['Actual' => "NC439",
        'Nuevo' => "NC2195"],
    
        ['Actual' => "NC440",
        'Nuevo' => "NC2196"],
    
        ['Actual' => "NC441",
        'Nuevo' => "NC2197"],
    
        ['Actual' => "NC442",
        'Nuevo' => "NC2198"],
    
        ['Actual' => "NC443",
        'Nuevo' => "NC2199"],
    
        ['Actual' => "NC444",
        'Nuevo' => "NC2200"],
    
        ['Actual' => "NC445",
        'Nuevo' => "NC2201"],
    
        ['Actual' => "NC446",
        'Nuevo' => "NC2202"],
    
        ['Actual' => "NCDFE1757",
        'Nuevo' => "NC2203"],
    
        ['Actual' => "NCDFE1758",
        'Nuevo' => "NC2204"],
    
        ['Actual' => "NCDFE1759",
        'Nuevo' => "NC2205"],
    
        ['Actual' => "NCDFE1760",
        'Nuevo' => "NC2206"],
    
        ['Actual' => "NCDFE1761",
        'Nuevo' => "NC2207"],
    
        ['Actual' => "NCDFE1762",
        'Nuevo' => "NC2208"],
    
        ['Actual' => "NCDFE1763",
        'Nuevo' => "NC2209"],
    
        ['Actual' => "NCDFE1764",
        'Nuevo' => "NC2210"],
    
        ['Actual' => "NCDFE1765",
        'Nuevo' => "NC2211"],
    
        ['Actual' => "NCDFE1766",
        'Nuevo' => "NC2212"],
    
        ['Actual' => "NCDFE1767",
        'Nuevo' => "NC2213"],
    
        ['Actual' => "NCDFE1768",
        'Nuevo' => "NC2214"],
    
        ['Actual' => "NC447",
        'Nuevo' => "NC2215"],
    
        ['Actual' => "NC448",
        'Nuevo' => "NC2216"],
    
        ['Actual' => "NC449",
        'Nuevo' => "NC2217"],
    
        ['Actual' => "NC450",
        'Nuevo' => "NC2218"],
    
        ['Actual' => "NC451",
        'Nuevo' => "NC2219"],
    
        ['Actual' => "NCDFE1769",
        'Nuevo' => "NC2220"],
    
        ['Actual' => "NCDFE1770",
        'Nuevo' => "NC2221"],
    
        ['Actual' => "NCDFE1771",
        'Nuevo' => "NC2222"],
    
        ['Actual' => "NCDFE1772",
        'Nuevo' => "NC2223"],
    
        ['Actual' => "NCDFE1773",
        'Nuevo' => "NC2224"],
    
        ['Actual' => "NCDFE1774",
        'Nuevo' => "NC2225"],
    
        ['Actual' => "NC452",
        'Nuevo' => "NC2226"],
    
        ['Actual' => "NC453",
        'Nuevo' => "NC2227"],
    
        ['Actual' => "NCDFE1775",
        'Nuevo' => "NC2228"],
    
        ['Actual' => "NC454",
        'Nuevo' => "NC2229"],
    
        ['Actual' => "NCDFE1787",
        'Nuevo' => "NC2230"],
    
        ['Actual' => "NCDFE1788",
        'Nuevo' => "NC2231"],
    
        ['Actual' => "NCDFE1776",
        'Nuevo' => "NC2232"],
    
        ['Actual' => "NCDFE1777",
        'Nuevo' => "NC2233"],
    
        ['Actual' => "NCDFE1778",
        'Nuevo' => "NC2234"],
    
        ['Actual' => "NCDFE1779",
        'Nuevo' => "NC2235"],
    
        ['Actual' => "NCDFE1780",
        'Nuevo' => "NC2236"],
    
        ['Actual' => "NCDFE1781",
        'Nuevo' => "NC2237"],
    
        ['Actual' => "NCDFE1782",
        'Nuevo' => "NC2238"],
    
        ['Actual' => "NCDFE1783",
        'Nuevo' => "NC2239"],
    
        ['Actual' => "NCDFE1784",
        'Nuevo' => "NC2240"],
    
        ['Actual' => "NCDFE1785",
        'Nuevo' => "NC2241"],
    
        ['Actual' => "NCDFE1786",
        'Nuevo' => "NC2242"],
    
        ['Actual' => "NCDFE1789",
        'Nuevo' => "NC2243"],
    
        ['Actual' => "NCDFE1790",
        'Nuevo' => "NC2244"],
    
        ['Actual' => "NCDFE1791",
        'Nuevo' => "NC2245"],
    
        ['Actual' => "NCDFE1792",
        'Nuevo' => "NC2246"],
    
        ['Actual' => "NC455",
        'Nuevo' => "NC2247"],
    
        ['Actual' => "NC456",
        'Nuevo' => "NC2248"],
    
        ['Actual' => "NC457",
        'Nuevo' => "NC2249"],
    
        ['Actual' => "NC458",
        'Nuevo' => "NC2250"],
    
        ['Actual' => "NC459",
        'Nuevo' => "NC2251"],
    
        ['Actual' => "NCDFE1793",
        'Nuevo' => "NC2252"],
    
        ['Actual' => "NCDFE1794",
        'Nuevo' => "NC2253"],
    
        ['Actual' => "NCDFE1795",
        'Nuevo' => "NC2254"],
    
        ['Actual' => "NC460",
        'Nuevo' => "NC2255"],
    
        ['Actual' => "NC461",
        'Nuevo' => "NC2256"],
    
        ['Actual' => "NC462",
        'Nuevo' => "NC2257"],
    
        ['Actual' => "NCDFE1796",
        'Nuevo' => "NC2258"],
    
        ['Actual' => "NCDFE1797",
        'Nuevo' => "NC2259"],
    
        ['Actual' => "NCDFE1798",
        'Nuevo' => "NC2260"],
    
        ['Actual' => "NCDFE1799",
        'Nuevo' => "NC2261"],
    
        ['Actual' => "NCDFE1800",
        'Nuevo' => "NC2262"],
    
        ['Actual' => "NCDFE1801",
        'Nuevo' => "NC2263"],
    
        ['Actual' => "NCDFE1802",
        'Nuevo' => "NC2264"],
    
        ['Actual' => "NCDFE1803",
        'Nuevo' => "NC2265"],
    
        ['Actual' => "NC463",
        'Nuevo' => "NC2266"],
    
        ['Actual' => "NC464",
        'Nuevo' => "NC2267"],
    
        ['Actual' => "NCDFE1804",
        'Nuevo' => "NC2268"],
    
        ['Actual' => "NCDFE1805",
        'Nuevo' => "NC2269"],
    
        ['Actual' => "NCDFE1806",
        'Nuevo' => "NC2270"],
    
        ['Actual' => "NCDFE1807",
        'Nuevo' => "NC2271"],
    
        ['Actual' => "NCDFE1808",
        'Nuevo' => "NC2272"],
    
        ['Actual' => "NCDFE1809",
        'Nuevo' => "NC2273"],
    
        ['Actual' => "NCDFE1810",
        'Nuevo' => "NC2274"],
    
        ['Actual' => "NCDFE1811",
        'Nuevo' => "NC2275"],
    
        ['Actual' => "NCDFE1812",
        'Nuevo' => "NC2276"],
    
        ['Actual' => "NCDFE1813",
        'Nuevo' => "NC2277"],
    
        ['Actual' => "NCDFE1814",
        'Nuevo' => "NC2278"],
    
        ['Actual' => "NC465",
        'Nuevo' => "NC2279"],
    
        ['Actual' => "NCDFE1815",
        'Nuevo' => "NC2280"],
    
        ['Actual' => "NC466",
        'Nuevo' => "NC2281"],
    
        ['Actual' => "NCDFE1816",
        'Nuevo' => "NC2282"],
    
        ['Actual' => "NCDFE1817",
        'Nuevo' => "NC2283"],
    
        ['Actual' => "NCDFE1818",
        'Nuevo' => "NC2284"],
    
        ['Actual' => "NCDFE1819",
        'Nuevo' => "NC2285"],
    
        ['Actual' => "NCDFE1820",
        'Nuevo' => "NC2286"],
    
        ['Actual' => "NCDFE1821",
        'Nuevo' => "NC2287"],
    
        ['Actual' => "NCDFE1822",
        'Nuevo' => "NC2288"],
    
        ['Actual' => "NCDFE1823",
        'Nuevo' => "NC2289"],
    
        ['Actual' => "NCDFE1824",
        'Nuevo' => "NC2290"],
    
        ['Actual' => "NCDFE1825",
        'Nuevo' => "NC2291"],
    
        ['Actual' => "NCDFE1826",
        'Nuevo' => "NC2292"],
    
        ['Actual' => "NCDFE1827",
        'Nuevo' => "NC2293"],
    
        ['Actual' => "NCDFE1828",
        'Nuevo' => "NC2294"],
    
        ['Actual' => "NCDFE1829",
        'Nuevo' => "NC2295"],
    
        ['Actual' => "NCDFE1830",
        'Nuevo' => "NC2296"],
    
        ['Actual' => "NCDFE1831",
        'Nuevo' => "NC2297"],
    
        ['Actual' => "NCDFE1832",
        'Nuevo' => "NC2298"],
    
        ['Actual' => "NCDFE1833",
        'Nuevo' => "NC2299"],
    
        ['Actual' => "NCDFE1834",
        'Nuevo' => "NC2300"],
    
        ['Actual' => "NCDFE1835",
        'Nuevo' => "NC2301"],
    
        ['Actual' => "NCDFE1836",
        'Nuevo' => "NC2302"],
    
        ['Actual' => "NCDFE1837",
        'Nuevo' => "NC2303"],
    
        ['Actual' => "NCDFE1838",
        'Nuevo' => "NC2304"],
    
        ['Actual' => "NCDFE1839",
        'Nuevo' => "NC2305"],
    
        ['Actual' => "NCDFE1840",
        'Nuevo' => "NC2306"],
    
        ['Actual' => "NCDFE1841",
        'Nuevo' => "NC2307"],
    
        ['Actual' => "NCDFE1842",
        'Nuevo' => "NC2308"],
    
        ['Actual' => "NCDFE1843",
        'Nuevo' => "NC2309"],
    
        ['Actual' => "NCDFE1844",
        'Nuevo' => "NC2310"],
    
        ['Actual' => "NCDFE1845",
        'Nuevo' => "NC2311"],
    
        ['Actual' => "NCDFE1846",
        'Nuevo' => "NC2312"],
    
        ['Actual' => "NC467",
        'Nuevo' => "NC2313"],
    
        ['Actual' => "NC468",
        'Nuevo' => "NC2314"],
    
        ['Actual' => "NC469",
        'Nuevo' => "NC2315"],
    
        ['Actual' => "NCDFE1847",
        'Nuevo' => "NC2316"],
    
        ['Actual' => "NCDFE1848",
        'Nuevo' => "NC2317"],
    
        ['Actual' => "NC470",
        'Nuevo' => "NC2318"],
    
        ['Actual' => "NC471",
        'Nuevo' => "NC2319"],
    
        ['Actual' => "NCDFE1849",
        'Nuevo' => "NC2320"],
    
        ['Actual' => "NCDFE1850",
        'Nuevo' => "NC2321"],
    
        ['Actual' => "NCDFE1851",
        'Nuevo' => "NC2322"],
    
        ['Actual' => "NCDFE1852",
        'Nuevo' => "NC2323"],
    
        ['Actual' => "NCDFE1853",
        'Nuevo' => "NC2324"],
    
        ['Actual' => "NCDFE1854",
        'Nuevo' => "NC2325"],
    
        ['Actual' => "NCDFE1855",
        'Nuevo' => "NC2326"],
    
        ['Actual' => "NCDFE1856",
        'Nuevo' => "NC2327"],
    
        ['Actual' => "NCDFE1857",
        'Nuevo' => "NC2328"],
    
        ['Actual' => "NCDFE1858",
        'Nuevo' => "NC2329"],
    
        ['Actual' => "NCDFE1859",
        'Nuevo' => "NC2330"],
    
        ['Actual' => "NCDFE1860",
        'Nuevo' => "NC2331"],
    
        ['Actual' => "NCDFE1861",
        'Nuevo' => "NC2332"],
    
        ['Actual' => "NCDFE1862",
        'Nuevo' => "NC2333"],
    
        ['Actual' => "NCDFE1863",
        'Nuevo' => "NC2334"],
    
        ['Actual' => "NCDFE1864",
        'Nuevo' => "NC2335"],
    
        ['Actual' => "NCDFE1865",
        'Nuevo' => "NC2336"],
    
        ['Actual' => "NCDFE1866",
        'Nuevo' => "NC2337"],
    
        ['Actual' => "NCDFE1867",
        'Nuevo' => "NC2338"],
    
        ['Actual' => "NCDFE1868",
        'Nuevo' => "NC2339"],
    
        ['Actual' => "NCDFE1869",
        'Nuevo' => "NC2340"],
    
        ['Actual' => "NCDFE1870",
        'Nuevo' => "NC2341"],
    
        ['Actual' => "NCDFE1871",
        'Nuevo' => "NC2342"],
    
        ['Actual' => "NCDFE1872",
        'Nuevo' => "NC2343"],
    
        ['Actual' => "NCDFE1873",
        'Nuevo' => "NC2344"],
    
        ['Actual' => "NCDFE1874",
        'Nuevo' => "NC2345"],
    
        ['Actual' => "NCDFE1875",
        'Nuevo' => "NC2346"],
    
        ['Actual' => "NCDFE1876",
        'Nuevo' => "NC2347"],
    
        ['Actual' => "NCDFE1877",
        'Nuevo' => "NC2348"],
    
        ['Actual' => "NCDFE1878",
        'Nuevo' => "NC2349"],
    
        ['Actual' => "NCDFE1879",
        'Nuevo' => "NC2350"],
    
        ['Actual' => "NCDFE1880",
        'Nuevo' => "NC2351"],
    
        ['Actual' => "NCDFE1881",
        'Nuevo' => "NC2352"],
    
        ['Actual' => "NCDFE1882",
        'Nuevo' => "NC2353"],
    
        ['Actual' => "NCDFE1883",
        'Nuevo' => "NC2354"],
    
        ['Actual' => "NCDFE1884",
        'Nuevo' => "NC2355"],
    
        ['Actual' => "NCDFE1885",
        'Nuevo' => "NC2356"],
    
        ['Actual' => "NCDFE1886",
        'Nuevo' => "NC2357"],
    
        ['Actual' => "NCDFE1887",
        'Nuevo' => "NC2358"],
    
        ['Actual' => "NCDFE1888",
        'Nuevo' => "NC2359"],
    
        ['Actual' => "NCDFE1889",
        'Nuevo' => "NC2360"],
    
        ['Actual' => "NCDFE1890",
        'Nuevo' => "NC2361"],
    
        ['Actual' => "NCDFE1891",
        'Nuevo' => "NC2362"],
    
        ['Actual' => "NCDFE1892",
        'Nuevo' => "NC2363"],
    
        ['Actual' => "NCDFE1893",
        'Nuevo' => "NC2364"],
    
        ['Actual' => "NCDFE1894",
        'Nuevo' => "NC2365"],
    
        ['Actual' => "NCDFE1895",
        'Nuevo' => "NC2366"],
    
        ['Actual' => "NCDFE1896",
        'Nuevo' => "NC2367"],
    
        ['Actual' => "NCDFE1897",
        'Nuevo' => "NC2368"],
    
        ['Actual' => "NC472",
        'Nuevo' => "NC2369"],
    
        ['Actual' => "NC473",
        'Nuevo' => "NC2370"],
    
        ['Actual' => "NCDFE1898",
        'Nuevo' => "NC2371"],
    
        ['Actual' => "NCDFE1899",
        'Nuevo' => "NC2372"],
    
        ['Actual' => "NCDFE1900",
        'Nuevo' => "NC2373"],
    
        ['Actual' => "NCDFE1901",
        'Nuevo' => "NC2374"],
    
        ['Actual' => "NC474",
        'Nuevo' => "NC2375"],
    
        ['Actual' => "NCDFE1902",
        'Nuevo' => "NC2376"],
    
        ['Actual' => "NC475",
        'Nuevo' => "NC2377"],
    
        ['Actual' => "NC476",
        'Nuevo' => "NC2378"],
    
        ['Actual' => "NCDFE1903",
        'Nuevo' => "NC2379"],
    
        ['Actual' => "NCDFE1904",
        'Nuevo' => "NC2380"],
    
        ['Actual' => "NCDFE1905",
        'Nuevo' => "NC2381"],
    
        ['Actual' => "NCDFE1906",
        'Nuevo' => "NC2382"],
    
        ['Actual' => "NCDFE1907",
        'Nuevo' => "NC2383"],
    
        ['Actual' => "NC477",
        'Nuevo' => "NC2384"],
    
        ['Actual' => "NCDFE1908",
        'Nuevo' => "NC2385"],
    
        ['Actual' => "NCDFE1909",
        'Nuevo' => "NC2386"],
    
        ['Actual' => "NCDFE1910",
        'Nuevo' => "NC2387"],
    
        ['Actual' => "NCDFE1911",
        'Nuevo' => "NC2388"],
    
        ['Actual' => "NC478",
        'Nuevo' => "NC2389"],
    
        ['Actual' => "NC479",
        'Nuevo' => "NC2390"],
    
        ['Actual' => "NC480",
        'Nuevo' => "NC2391"],
    
        ['Actual' => "NC481",
        'Nuevo' => "NC2392"],
    
        ['Actual' => "NC482",
        'Nuevo' => "NC2393"],
    
        ['Actual' => "NC483",
        'Nuevo' => "NC2394"],
    
        ['Actual' => "NC484",
        'Nuevo' => "NC2395"],
    
        ['Actual' => "NC485",
        'Nuevo' => "NC2396"],
    
        ['Actual' => "NC486",
        'Nuevo' => "NC2397"],
    
        ['Actual' => "NC487",
        'Nuevo' => "NC2398"],
    
        ['Actual' => "NC488",
        'Nuevo' => "NC2399"],
    
        ['Actual' => "NC489",
        'Nuevo' => "NC2400"],
    
        ['Actual' => "NC490",
        'Nuevo' => "NC2401"],
    
        ['Actual' => "NC491",
        'Nuevo' => "NC2402"],
    
        ['Actual' => "NC492",
        'Nuevo' => "NC2403"],
    
        ['Actual' => "NC493",
        'Nuevo' => "NC2404"],
    
        ['Actual' => "NC494",
        'Nuevo' => "NC2405"],
    
        ['Actual' => "NC495",
        'Nuevo' => "NC2406"],
    
        ['Actual' => "NC496",
        'Nuevo' => "NC2407"],
    
        ['Actual' => "NC497",
        'Nuevo' => "NC2408"],
    
        ['Actual' => "NCDFE1912",
        'Nuevo' => "NC2409"],
    
        ['Actual' => "NCDFE1913",
        'Nuevo' => "NC2410"],
    
        ['Actual' => "NC498",
        'Nuevo' => "NC2411"],
    
        ['Actual' => "NCDFE1914",
        'Nuevo' => "NC2412"],
    
        ['Actual' => "NCDFE1915",
        'Nuevo' => "NC2413"],
    
        ['Actual' => "NCDFE1916",
        'Nuevo' => "NC2414"],
    
        ['Actual' => "NCDFE1917",
        'Nuevo' => "NC2415"],
    
        ['Actual' => "NCDFE1918",
        'Nuevo' => "NC2416"],
    
        ['Actual' => "NCDFE1919",
        'Nuevo' => "NC2417"],
    
        ['Actual' => "NCDFE1920",
        'Nuevo' => "NC2418"],
    
        ['Actual' => "NCDFE1921",
        'Nuevo' => "NC2419"],
    
        ['Actual' => "NCDFE1922",
        'Nuevo' => "NC2420"],
    
        ['Actual' => "NC499",
        'Nuevo' => "NC2421"],
    
        ['Actual' => "NCDFE1923",
        'Nuevo' => "NC2422"],
    
        ['Actual' => "NCDFE1924",
        'Nuevo' => "NC2423"],
    
        ['Actual' => "NCDFE1925",
        'Nuevo' => "NC2424"],
    
        ['Actual' => "NC500",
        'Nuevo' => "NC2425"],
    
        ['Actual' => "NCDFE1926",
        'Nuevo' => "NC2426"],
    
        ['Actual' => "NC501",
        'Nuevo' => "NC2427"],
    
        ['Actual' => "NCDFE1927",
        'Nuevo' => "NC2428"],
    
        ['Actual' => "NC502",
        'Nuevo' => "NC2429"],
    
        ['Actual' => "NC503",
        'Nuevo' => "NC2430"],
    
        ['Actual' => "NC504",
        'Nuevo' => "NC2431"],
    
        ['Actual' => "NCDFE1928",
        'Nuevo' => "NC2432"],
    
        ['Actual' => "NCDFE1929",
        'Nuevo' => "NC2433"],
    
        ['Actual' => "NCDFE1930",
        'Nuevo' => "NC2434"],
    
        ['Actual' => "NCDFE1931",
        'Nuevo' => "NC2435"],
    
        ['Actual' => "NC505",
        'Nuevo' => "NC2436"],
    
        ['Actual' => "NC506",
        'Nuevo' => "NC2437"],
    
        ['Actual' => "NC507",
        'Nuevo' => "NC2438"],
    
        ['Actual' => "NCDFE1932",
        'Nuevo' => "NC2439"],
    
        ['Actual' => "NC508",
        'Nuevo' => "NC2440"],
    
        ['Actual' => "NC509",
        'Nuevo' => "NC2441"],
    
        ['Actual' => "NC510",
        'Nuevo' => "NC2442"]
    
    ];
    
}
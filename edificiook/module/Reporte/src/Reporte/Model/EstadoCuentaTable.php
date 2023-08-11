<?php
/**
 * edificioOk (https://www.edificiook.com)
 * creado por: Meler Carranza, 23/03/2016.
 * ultima modificacion por: Meler Carranza
 * Fecha Modificacion: 23/03/2016.
 * Descripcion: Script para guardar datos de la generación de cuota de mantenimiento
 *
 * @link      https://www.edificiook.com
 * @copyright Copyright (c) 2011-2016 KND S.A.C (http://www.knd.pe)
 * @license   http://www.edificiook.com/license/comercial Software Comercial */

namespace Reporte\Model;

use Zend\Db\Sql\Sql;
use Zend\Db\Sql\Select;
use Zend\Db\TableGateway\AbstractTableGateway;
use Zend\Db\Adapter\Adapter;
use Zend\Db\ResultSet\ResultSet;
use Zend\Db\Sql\Expression;

use Zend\Mail\Message;
use Zend\Mail\Transport\Smtp as SmtpTransport;
use Zend\Mail\Transport\SmtpOptions;

// use Zend\Mail\Message;
use Zend\Mime\Message as MimeMessage;
use Zend\Mime\Part as MimePart;
use Zend\Mime\Mime;
// use Zend\Mail\Transport\Sendmail;

class EstadoCuentaTable extends AbstractTableGateway{

	public function __construct(Adapter $adapter)
	{
		$this->adapter=$adapter;
	}

	public function listarEstado($params,$idedificio,$idusuario)
	{
		$adapter=$this->getAdapter();
        $sql=new Sql($adapter);

		/* Recuperando los parametros. */
		$cod_ent=$idedificio;
		$cod_us =$idusuario;

		$page = $params['page'];
		$limit = $params['rows'];
		$sidx = $params['sidx'];
		$sord = $params['sord'];
		if(!$sidx) $sidx = 1;

		$sidx = 'uni_in_cod';

		/* En caso estemos realizando una busqueda o un filtro. */
		$filter = "";
		$search_ind = $params['_search'];
		if($search_ind == 'true'){
		    $filter_cad = json_decode(stripslashes($params['filters']));
		    $filter_opc = $filter_cad->{'groupOp'};
		    $filter_rul = $filter_cad->{'rules'};
		    $cont = 0;
		    foreach($filter_rul as $key => $value){
		        $fie = $filter_rul[$key]->{'field'};
		        $opt = $this->search($filter_rul[$key]->{'op'});
		        $dat = trim($filter_rul[$key]->{'data'});
		        if($fie=='unidad')
		            $fie="CONCAT(ui.uni_vc_tip,' ',ui.uni_vc_nom)";

		        if($fie=='propietario')
		            $fie="(SELECT CASE WHEN usu_ch_tip='PN' THEN CONCAT(usu_vc_ape,' ',usu_vc_nom) WHEN usu_ch_tip='PJ' THEN usu_vc_ape END AS nombre FROM usuario WHERE usu_in_cod=ui.uni_in_pro)";

		        if($fie=='poseedor')
		            $fie="(SELECT CASE WHEN usu_ch_tip='PN' THEN CONCAT(usu_vc_ape,' ',usu_vc_nom) WHEN usu_ch_tip='PJ' THEN usu_vc_ape END AS nombre FROM usuario WHERE usu_in_cod=ui.uni_in_pos)";

		        if($fie=='deuda')
		             $fie="uni_do_deu";

		        if($cont==0){
		           if($opt=="REGEXP2"){
		                $opt = "LIKE";
		                if($fie=='periodo'){
		                    $fie="MONTH(ci.coi_da_fecven)"; //pendiente
		                    $opt="=";
		                    $filter .= " AND ($fie $opt '%$dat%'";
		                }else{
		                    $filter .= " AND ($fie $opt '%$dat%'";
		                }
		            }else{
		                if($opt=="REGEXP3"){
		                    $opt = "LIKE";
		                    $filter .= " AND ($fie $opt '%$dat%'";
		                }else{
		                    if($opt=="NOT REGEXP2"){
		                        $opt = "NOT LIKE";
		                        $filter .= " AND ($fie $opt '%$dat%'";
		                    }else{
		                        if($opt=="NOT REGEXP3"){
		                            $opt = "NOT LIKE";
		                            $filter .= " AND ($fie $opt '%$dat%'";
		                        }else{
		                            if($opt=='LIKE' || $opt=='NOT LIKE'){
		                                $filter .= " AND ($fie $opt '%$dat%'";
		                            }else{
		                                $filter .= " AND ($fie $opt '%$dat%'";
		                            }
		                        }
		                    }
		                }
		            }
		            $cont++;
		        }else{
		            if($opt=="REGEXP2"){
		                $opt = "LIKE";
		                if($fie=='periodo'){
		                    $fie="MONTH(ci.coi_da_fecemi)"; //pendiente
		                    $opt="=";
		                    $filter .= " $filter_opc $fie $opt '%$dat%'";
		                }else{
		                    $filter .= " $filter_opc $fie $opt '%$dat%'";
		                }
		            }else{
		                if($opt=="REGEXP3"){
		                    $opt = "LIKE";
		                    $filter .= " $filter_opc $fie $opt '%$dat%'";
		                }else{
		                    if($opt=="NOT REGEXP2"){
		                        $opt = "NOT LIKE";
		                        $filter .= " $filter_opc $fie $opt '%$dat%'";
		                    }else{
		                        if($opt=="NOT REGEXP3"){
		                            $opt = "NOT LIKE";
		                            $filter .= " $filter_opc $fie $opt '%$dat%'";
		                        }else{
		                            if($opt=='LIKE' || $opt=='NOT LIKE'){
		                                $filter .= " $filter_opc $fie $opt '%$dat%'";
		                            }else{
		                                $filter .= " $filter_opc $fie $opt '%$dat%'";
		                            }
		                        }
		                    }
		                }
		            }
		        }
		    }
		    $filter .= ")";
		}

		/* Query que devuelve la cantidad de filas para realizar la paginación. */
		$queryUnidadUsuario = "SELECT COUNT(*) AS count FROM unidad ui, usuario u WHERE u.usu_in_cod=ui.uni_in_pro AND ui.edi_in_cod='$cod_ent' AND uni_in_pad IS NULL $filter";
        $row = $adapter->query($queryUnidadUsuario,$adapter::QUERY_MODE_EXECUTE)->current();
        $count = $row['count'];

		if($count > 0 && $limit > 0){
		    $total_pages = ceil($count / $limit);
		}else{
		    $total_pages = 0;
		}
		if ($page > $total_pages) $page = $total_pages;
		$start = $limit * $page - $limit;
		if($start <0) $start = 0;

		/* Query que muestra los datos que apareceran en el jqGrid. */
		//convert(uni_vc10_num,UNSIGNED INTEGER) = uni_vc_nom 
		if($cod_ent==60){
		     $queryDataGrid="SELECT ui.uni_in_cod, CONCAT(ui.uni_vc_tip,' ',ui.uni_vc_nom) AS unidad, (SELECT CASE WHEN usu_ch_tip='PN' THEN CONCAT(usu_vc_ape,' ',usu_vc_nom) WHEN usu_ch_tip='PJ' THEN usu_vc_ape END AS nombre FROM usuario WHERE usu_in_cod=ui.uni_in_pro) AS propietario, (SELECT CASE WHEN usu_ch_tip='PN' THEN CONCAT(usu_vc_ape,' ',usu_vc_nom) WHEN usu_ch_tip='PJ' THEN usu_vc_ape END AS nombre FROM usuario WHERE usu_in_cod=ui.uni_in_pos) AS poseedor , ui.uni_do_deu AS deuda,SUBSTRING(uni_vc_nom,1,1) as letra,convert(uni_vc_nom,UNSIGNED INTEGER) as numero FROM unidad ui, usuario u WHERE u.usu_in_cod=ui.uni_in_pro AND ui.uni_in_est='1' AND ui.edi_in_cod='$cod_ent' AND uni_in_pad IS NULL $filter  ORDER BY letra asc,numero asc LIMIT $start , $limit";
		}else{
		    $queryDataGrid="SELECT ui.uni_in_cod, CONCAT(ui.uni_vc_tip,' ',ui.uni_vc_nom) AS unidad, (SELECT CASE WHEN usu_ch_tip='PN' THEN CONCAT(usu_vc_ape,' ',usu_vc_nom) WHEN usu_ch_tip='PJ' THEN usu_vc_ape END AS nombre FROM usuario WHERE usu_in_cod=ui.uni_in_pro) AS propietario, (SELECT CASE WHEN usu_ch_tip='PN' THEN CONCAT(usu_vc_ape,' ',usu_vc_nom) WHEN usu_ch_tip='PJ' THEN usu_vc_ape END AS nombre FROM usuario WHERE usu_in_cod=ui.uni_in_pos) AS poseedor , ui.uni_do_deu AS deuda FROM unidad ui, usuario u WHERE u.usu_in_cod=ui.uni_in_pro AND ui.uni_in_est='1' AND ui.edi_in_cod='$cod_ent' AND uni_in_pad IS NULL $filter  ORDER BY $sidx $sord LIMIT $start , $limit";
		}

		$result = $adapter->query($queryDataGrid,$adapter::QUERY_MODE_EXECUTE)->toArray();

		$response=array();
        $response['page'] = $page;
        $response['total'] = $total_pages;
        $response['records'] = $count;
        $i=0;

		foreach($result as $row){
			$CodUniAcu="'".$row['uni_in_cod']."'";
		    $queryUnidad = "SELECT * FROM unidad WHERE uni_in_pad='".$row['uni_in_cod']."' ";
		    $ResUniSec = $adapter->query($queryUnidad,$adapter::QUERY_MODE_EXECUTE)->toArray();
		    foreach($ResUniSec as $RowUniSec){
		    	$CodUniAcu.=",'".$RowUniSec['uni_in_cod']."'";
		    }
		    $response['rows'][$i]['id'] = $row['uni_in_cod'];
		    $response['rows'][$i]['cell'] = array(
		    	$row['unidad'],
		    	$row['propietario'],
		    	$row['poseedor'],
		    	$row['deuda'],
		    	$filter
		    );
		    $i++;
		}

		return $response;

	}

	public function search($oper){
		switch($oper){
			case "eq" : $oper = "="; break;
			case "ne" : $oper = "!="; break;
			case "lt" : $oper = "<"; break;
			case "le" : $oper = "<="; break;
			case "gt" : $oper = ">"; break;
			case "ge" : $oper = ">="; break;
			case "bw" : $oper = "REGEXP2"; break;
			case "bn" : $oper = "NOT REGEXP"; break;
			case "in" : $oper = "IN"; break;
			case "ni" : $oper = "NOT IN"; break;
			case "ew" : $oper = "REGEXP3"; break;
			case "en" : $oper = "NOT REGEXP"; break;
			case "cn" : $oper = "LIKE"; break;
			case "nc" : $oper = "NOT LIKE"; break;
		}
		return $oper;
	}

	public function getReciboDetallado($codEnt,$mesx,$aniox,$tipo,$ids,$filterx,$admin,$baseUrl)
	{

    $adapter=$this->getAdapter();
	    
    //consultamos el tipo de empresa
    $queryEdificio = "SELECT `emp_in_cod` FROM edificio WHERE `edi_in_cod` = '$codEnt' ";
	$rowEmpresa = $adapter->query($queryEdificio,$adapter::QUERY_MODE_EXECUTE)->current();
   
    //consultamos el tipo de empresa
    //$db = new MySQL();

    if($admin=='4'){
        if($rowEmpresa['emp_in_cod']==8){
            $admin='1';
        }else{
            $admin='2';
        }
    }
    

    $cont = 1;
    $fechaEmision = '';
    $fechaEmisionSql = '';
    $fechaVencimiento = '';
    $fechaVencimientoSql = '';
    $queryUnidadEC = '';
    $unidadCodigoAcumulado = '';
    $propietarioNombre = '';
    $propietarioDniRuc = '';
    $propietarioDireccion = '';
    $periodo = '';
    
    if($admin=='2'){
        $bg = '#9A437A';
        $color2 = 'color:#fff;';
        $border = 'border: solid 0.2px black;';
        $border2 = 'border: solid 1px black;';
        $borderFpago = 'border: solid 1px black;';
    }else if($admin=='1'){
        $bg = '#2273B6';
        $color2 = 'color:#fff;';
        $border = 'border: solid 0.2px black;';
        $border2 = 'border: solid 1px black;';
        $borderFpago = 'border: solid 1px black;';

    }else if($admin=='0'){
        $bg = '#fff';
        $color2 = 'color:#fff;';
        $border = 'border: solid 0.2px white;';
        $border2 = 'border: solid 1px white;';
        $borderFpago = 'border: solid 1px black;';
    }else{
        $bg = '#fff';
        $color2 = 'color:#fff;';
        $border = 'border: solid 0.2px white;';
        $border2 = 'border: solid 1px white;';
        $borderFpago ='border: solid 1px white;';
    }
    
    /*[OBTENER MES]*/
    /* Sacando los datos de fecha segun el formato del GMT */
    if (isset($mesx)) {
        $mes = $mesx;
        $mesNumero = $mesx;
        $mesNumeroAnterior = $mesx - 1;
    }else {
        $mes = date('m');
        $mesNumero = date('m');
        $mesNumeroAnterior = $mes - 1;
    }

    if (isset($aniox)) {
        $anioAnterior = $aniox;
        $anioMant = $aniox;
        $anio = $aniox;
        $anio_consula = $aniox;
    } else {
        $anioAnterior = date('Y');
        $anioMant = date('Y');
        $anio = date('Y');
        $anio_consula = date('Y');
    }

    $mesNombre = $this->getMonthNameString($mes);

    if (strlen($mesNumero) == 1) {
        $mesNumero = "0" . $mesNumero;
    }

    if ($mesNumeroAnterior == 0) {
        $mesNumeroAnterior = "12";
        $anioAnterior--;
    } else if (strlen($mesNumeroAnterior) == 1) {
        $mesNumeroAnterior = "0" . $mesNumeroAnterior;
    }
    $fechaActual = date("d/m/Y");
    
     if (isset($codEnt)) {

        /* Recuperando el nombre del propietario de la entidad y su RUC */
        $queryEdificioDistrito = "SELECT e.*, d.dis_vc_nom FROM edificio e, distrito d WHERE d.dis_in_cod = e.dis_in_cod AND e.edi_in_cod = '$codEnt' ";
		$rowEntidad = $adapter->query($queryEdificioDistrito,$adapter::QUERY_MODE_EXECUTE)->current();

		$entidadNombre = utf8_decode($rowEntidad['edi_vc_nompro']); 

        if(strlen($entidadNombre)>30){
             $entidadNombre = substr(strip_tags($entidadNombre), 0, 30);
             $entidadNombre.='...';
        }

        $entidadRuc = 'RUC. ' . $rowEntidad['edi_ch_rucpro'];
        $entidadTipoPresupuesto = $rowEntidad['edi_vc_tpre'];
        $entidadDireccion = utf8_decode($rowEntidad['edi_vc_dir']);
        $entidadUrbanizacion = 'URB. ' . utf8_decode($rowEntidad['edi_vc_urb']);
        $entidadMensaje = substr(utf8_decode($rowEntidad['edi_te_men']), 0, 500);
        $entidadDistrito = utf8_decode($rowEntidad['dis_vc_nom']);

        //Calcula la fecha de proceso o emision
	        if (strlen($rowEntidad['edi_in_diapro']) < 2) {
	            $fechaEmision = "0" . $rowEntidad['edi_in_diapro'] . "/" . $mesNumero . "/" . $anio;
	            $fechaEmisionSql = $anio . '-' . $mesNumero . '-0' . $rowEntidad['edi_in_diapro'];
	        } else {
	            $fechaEmision = $rowEntidad['edi_in_diapro'] . "/" . $mesNumero . "/" . $anio;
	            $fechaEmisionSql = $anio . '-' . $mesNumero . '-' . $rowEntidad['edi_in_diapro'];
	        }

	        $mesNumeroEmi=$mesNumero;

	        //Calcula la fecha de vencimiento
	        $strmen = '0';
	        $strdia = '0';
	        (strlen($rowEntidad['edi_in_diapag']) < 2) ? $strdia = '0' : $strdia = '';
	        if ($rowEntidad['edi_in_diapro'] < $rowEntidad['edi_in_diapag']) {
	            (strlen($mesNumero) < 2) ? $strmen = '0' : $strmen = '';
	            $fechaVencimiento = $strdia . $rowEntidad['edi_in_diapag'] . "/" . $strmen . $mesNumero . "/" . $anio;
	            $fechaVencimientoSql = $anio . '-' . $strmen . $mesNumero . '-' . $strdia . $rowEntidad['edi_in_diapag'];
	        } else {
	            if ($mesNumero < 12) {
	                $mesNumero++;
	            } else {
	                $mesNumero = 1;
	            }
	            (strlen($mesNumero) < 2) ? $strmen = '0' : $strmen = '';
	            $fechaVencimiento = $strdia . $rowEntidad['edi_in_diapag'] . "/" . $strmen . $mesNumero . "/" . $anio;
	            $fechaVencimientoSql = $anio . '-' . $strmen . $mesNumero . '-' . $strdia . $rowEntidad['edi_in_diapag'];
	        }
	        
	       $periodo = $this->getFormatMes($strmen.$mesNumeroEmi);

	        if ($rowEntidad['edi_in_numser'] != 0 && $rowEntidad['edi_in_numser'] != null) {
	            $entidadNumeroSerie = $rowEntidad['edi_in_numser'];
	        } else {
	            $entidadNumeroSerie = 1;
	        }
	        $entidadNumeroDocumento = $rowEntidad['edi_in_numdoc'];

	        /* Listando las unidades inmobiliarias padres las cuales van a ser encabezado de las unidades secundarias que tenga esa unidad */
	        $filArr = explode('CORTE', stripslashes($filterx));
	        $filter = '';

	        for ($i = 0; $i < (count($filArr)); $i++){
	            $filter .= $filArr[$i];
	        }

	        if (isset($ids)) {

	            $codsUni = stripslashes($ids);
	            $codPar = explode(",", $codsUni);
	            $codNuevo = "";
	            for ($xyz = 0; $xyz < count($codPar); $xyz++) {
	                if ($xyz == 0) {
	                    $codNuevo .= "'" . $codPar[$xyz] . "'";
	                } else {
	                    $codNuevo .= ",'" . $codPar[$xyz] . "'";
	                }
	            }



            if($codEnt != '46'){ 

	            $selectUnidad = "SELECT ui.* FROM unidad ui WHERE ui.uni_in_pad IS NULL AND ui.edi_in_cod='$codEnt' AND ui.uni_in_est='1' AND (ui.uni_do_cm2 != '0.00' OR (SELECT SUM(uni_do_cm2) FROM unidad uii WHERE uni_in_pad IN (uii.uni_in_cod)) > 0) AND ui.uni_in_cod IN ($codNuevo) $filter";
				$queryUnidadEC = $adapter->query($selectUnidad,$adapter::QUERY_MODE_EXECUTE)->toArray();

	            } else {

	            $selectUnidad = "SELECT ui.* FROM unidad ui WHERE ui.uni_in_pad IS NULL AND ui.edi_in_cod='$codEnt' AND ui.uni_in_est='1' AND ui.uni_in_cod IN ($codNuevo) $filter";
				$queryUnidadEC = $adapter->query($selectUnidad,$adapter::QUERY_MODE_EXECUTE)->toArray();

	            }

	            
	        } else {
	              
	                if($codEnt != '46'){ 

	                	$selectUnidad = "SELECT ui.* FROM unidad ui WHERE ui.uni_in_pad IS NULL AND ui.edi_in_cod='$codEnt' AND ui.uni_in_est='1' AND (ui.uni_do_cm2 != '0.00' OR (SELECT SUM(uni_do_cm2) FROM unidad uii WHERE uni_in_pad IN (uii.uni_in_cod)) > 0) $filter";
						$queryUnidadEC = $adapter->query($selectUnidad,$adapter::QUERY_MODE_EXECUTE)->toArray();

	                } else {

	                	$selectUnidad = "SELECT ui.* FROM unidad ui WHERE ui.uni_in_pad IS NULL AND ui.edi_in_cod='$codEnt' AND ui.uni_in_est='1' $filter";
						$queryUnidadEC = $adapter->query($selectUnidad,$adapter::QUERY_MODE_EXECUTE)->toArray();

	                }


	        }

	        $cantidadCopias = 0;


         /* Listando por unidad inmobiliaria padre para que aparezca un solo estado de cuenta por pagina. */
        foreach($queryUnidadEC as $rowUnidadEC){

                $total = 0; $totalCm2 = 0; $totalM2 = 0;
                $unidadCodigoDepartamento = strtoupper(utf8_decode($rowUnidadEC['uni_vc_nom']));
                $unidadPorcentajeCuotaUnidad = $rowUnidadEC['uni_do_cm2'];
                $unidadAreaOcupada = $rowUnidadEC['uni_do_aocu'];
                $unidadTipo = strtoupper(utf8_decode($rowUnidadEC['uni_vc_tip']));
               
                /* Se está concatenando los códigos de las unidades inmobiliarias. */
                $unidadCodigoAcumulado .= "'" . $rowUnidadEC['uni_in_cod'] . "'";

                $selectUsuario = "SELECT usu_ch_tip, usu_vc_nom, usu_vc_ape, usu_ch_dni, usu_ch_ruc, usu_vc_dir FROM usuario WHERE usu_in_cod = '" . $rowUnidadEC['uni_in_pro'] . "' ";
				$rowPropietario = $adapter->query($selectUsuario,$adapter::QUERY_MODE_EXECUTE)->current();


                if ($rowPropietario['usu_ch_tip'] == 'PN') {
                    $propietarioNombre = strtoupper(utf8_decode($rowPropietario['usu_vc_ape'] . " " . $rowPropietario['usu_vc_nom']));
                    $propietarioDniRuc = strtoupper(utf8_decode($rowPropietario['usu_ch_dni']));
                } else {
                    $propietarioNombre = strtoupper(utf8_decode($rowPropietario['usu_vc_ape']));
                    $propietarioDniRuc = strtoupper(utf8_decode($rowPropietario['usu_ch_ruc']));
                }

                $propietarioDireccion = strtoupper(utf8_decode($rowPropietario['usu_vc_dir']));


                $selectUsuario="SELECT usu_ch_tip, usu_vc_nom, usu_vc_ape, usu_ch_dni, usu_ch_ruc, usu_vc_dir FROM usuario WHERE usu_in_cod = '" . $rowUnidadEC['uni_in_pos'] . "' ";
				$rowPoseedor = $adapter->query($selectUsuario,$adapter::QUERY_MODE_EXECUTE)->current();

                if ($rowPoseedor['usu_ch_tip'] == 'PN') {
                    $poseedorNombre = ucwords(strtoupper(utf8_decode(substr($rowPoseedor['usu_vc_ape'] . " " . $rowPoseedor['usu_vc_nom'],0,30))));
                    $poseedorDniRuc = ucwords(strtoupper(utf8_decode($rowPoseedor['usu_ch_dni'])));
                } else {
                    $poseedorNombre = ucwords(strtoupper(utf8_decode(substr($rowPoseedor['usu_vc_ape'],0,30))));
                    $poseedorDniRuc = ucwords(strtoupper(utf8_decode($rowPoseedor['usu_ch_ruc'])));
                }
                $poseedorDireccion = ucwords(strtoupper(utf8_decode(substr($rowPoseedor['usu_vc_dir'],0,35))));
            
            
            if($cont=='1'){
                echo '<page orientation="portrait"" backcolor="#FEFEFE" backtop="0" backbottom="3mm" style="font-size: 12pt">';
            }else{
                echo '<page orientation="portrait"" >';
            }
            
             /* ===== SECCION: RECIBO DE MANTENIMIENTO ===== */
            $selectIngresoUsuarioUnidad = "SELECT i.ing_in_cod, i.uni_in_cod, i.ing_in_est,CASE WHEN u.usu_ch_tip = 'PN' THEN CONCAT(u.usu_vc_ape,' ',u.usu_vc_nom) WHEN u.usu_ch_tip = 'PJ' THEN u.usu_vc_ape END AS poseedor, u.usu_ch_dni, u.usu_ch_ruc, u.usu_vc_dir, ui.uni_vc_nom, u.usu_ch_tip, i.ing_in_nroser, i.ing_in_nrodoc, i.ing_da_fecemi, i.ing_da_fecven FROM ingreso i, usuario u, unidad ui WHERE ui.uni_in_cod = i.uni_in_cod AND u.usu_in_cod = i.ing_in_resi AND ui.edi_in_cod = '$codEnt' AND MONTH(i.ing_da_fecemi) = '$mes' AND YEAR(i.ing_da_fecemi) = '$anio_consula' AND ui.uni_in_cod = '" . $rowUnidadEC['uni_in_cod'] . "' ";
			$rowIngresoRM = $adapter->query($selectIngresoUsuarioUnidad,$adapter::QUERY_MODE_EXECUTE)->current();

			$idIngresoView=$rowIngresoRM['ing_in_cod'];

                //$poseedorRM = strtoupper(utf8_decode($rowIngresoRM['poseedor']));
                $rucRM = utf8_decode($rowIngresoRM['usu_ch_ruc']);
                //$direccionRM = strtoupper(utf8_decode($rowIngresoRM['usu_vc_dir']));
                //$departamentoRM = strtoupper(utf8_decode($rowIngresoRM['uni_vc_nom']));

                if ($rowIngresoRM['usu_ch_tip'] == 'PN') {
                    $rucRM = utf8_decode($rowIngresoRM['usu_ch_dni']);
                }

                /* RECIBO DE MANTENIMIENTO: CALCULANDO NUMERO DE SERIE */
                $entidadNumeroSerie = $rowIngresoRM['ing_in_nroser'];
                $entidadNumeroDocumento = $rowIngresoRM['ing_in_nrodoc'];
                $entidadNumeroDocumento = substr('00000' . $entidadNumeroDocumento, strlen('00000' . $entidadNumeroDocumento) - 6, 6);
                $entidadNumeroSerie = substr('00000' . $entidadNumeroSerie, strlen('00000' . $entidadNumeroSerie) - 3, 6);

                $nroSerieRM = utf8_decode($entidadNumeroSerie . ' - ' . $entidadNumeroDocumento);


                $selectConceptoIngreso = "SELECT c.con_in_cod, c.con_vc_des,ci.coi_te_com, ci.coi_do_subtot, ci.coi_da_fecemi, ci.coi_da_fecven  FROM concepto_ingreso ci, concepto c WHERE ci.con_in_cod = c.con_in_cod AND ci.ing_in_cod ='" . $rowIngresoRM['ing_in_cod'] . "' ORDER BY 1 DESC";
				$queryConcepto = $adapter->query($selectConceptoIngreso,$adapter::QUERY_MODE_EXECUTE)->toArray();


echo '
    <style type="text/css">
    <!--
    table { vertical-align: top; }
    tr    { vertical-align: top; }
    td    { vertical-align: top; }
    -->
    </style>

    <bookmark title="Lettre" level="0" ></bookmark>
    <table cellspacing="0" style="width: 100%; text-align: left; font-size: 11pt;">
    <h1 style="display: none;"></h1>
         <tr>
            <td style="width: 100%; text-align: left;">
                <table cellspacing="0" style="width: 100%; text-align: center; font-size: 10pt;">';
                     
                    ob_start(); 
                     
                    echo '<tr><td style="width:25%;">';
                            
						if($rowEntidad['edi_vc_logo']!=''){ 
							echo '<img src="'.$baseUrl.'/file/logo-edificio/'.$rowEntidad['edi_vc_logo'].'?t='.time().'"  style="width: 120px; margin-top: 25px;" />';
						}else{
							echo '&nbsp;';   
						} 

                       echo  '</td>
                        <td style="width:35%; line-height: 1;">
                                <h4 style="margin: 13px;">
                                <b>'.$entidadNombre.'</b>
                                </h4>
                                <h6 style="margin-top:-2px; margin-bottom: 0; font-size: 10px; font-weight: 300;">';
                                    echo $entidadDireccion."<br>";
                                    echo $entidadUrbanizacion."<br>";
                                    echo $entidadDistrito;
                                	echo '</h6>
                        </td>
                        <td style="width:40%;">
                            <table cellspacing="0" style="width: 100%; '.$border2.' text-align: center; font-size: 10pt;">
                                <tr>
                                    <td style="width:100%;">
                                        <!-- RUC -->
                                        <h5 style="margin-top:5px; margin-bottom: 5px;">'.$entidadRuc.'</h5>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="width:100%; background: '.$bg.';">
                                        <h4 style="'.$color2.' font-size:18px; margin-top: 10px; margin-bottom: 10px;">RECIBO DE MANTENIMIENTO</h4>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="width:100%;">
                                        <h5 style="margin-top:5px; margin-bottom: 5px;">Nº '.$nroSerieRM.'</h5>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2" style="width:60%;">
                            <table cellspacing="0" style="width:100%; '.$border2.'  text-align: center; font-size: 10pt;">
                                <tr style="background: '.$bg.';">
                                    <td colspan="2">
                                        <h4 style="'.$color2.' margin-top:2px; margin-bottom: 2px; font-size:14px;">
                                            RECIBO DE MANTENIMIENTO
                                        </h4>
                                    </td> 
                                </tr>
                                <tr>
                                    <td style="padding-left: 5px;  width:43%;">
                                        <h4 style="margin-top: 1px; margin-bottom: 1px; font-size:12px; text-align: left;">
                                            RAZÓN SOCIAL: 
                                        </h4>
                                    </td>
                                    <td style="width:57%;">
                                        <h6 style="margin-top: 1px; margin-bottom: 1px; text-align: left; font-size: 10px; font-weight: 300;">
                                            '.ucwords(strtolower(utf8_encode($poseedorNombre))).'
                                        </h6>
                                    </td>  
                                </tr>
                                <tr>
                                    <td style="padding-left: 5px; width:43%;">
                                        <h4 style="margin-top: 1px; margin-bottom: 1px; font-size:12px; text-align: left;">
                                            DNI / RUC: 
                                        </h4>
                                    </td>
                                    <td style="width:57%;">
                                        <h6 style="margin-top: 1px; margin-bottom: 1px; text-align: left; font-size: 10px; font-weight: 300;">
                                           '.ucwords(strtolower($poseedorDniRuc)).'
                                        </h6>
                                    </td>   
                                </tr>
                                <tr>
                                    <td style="width:43%; padding-left: 5px; ">
                                        <h4 style="margin-top: 1px; margin-bottom: 1px; font-size:12px; text-align: left;">
                                            DOMICILIO FISCAL: 
                                        </h4>
                                    </td>
                                    <td style="width:57%;">
                                        <h6 style="margin-top: 1px; margin-bottom: 1px; text-align: left; font-size: 10px; font-weight: 300;">
                                            '.ucwords(strtolower($poseedorDireccion)).'
                                        </h6>
                                    </td>  
                                </tr>
                            </table>
                        </td>
                        <td style="width: 40%;">
                            <table cellspacing="0" style="width:100%; '.$border2.' text-align: center; font-size: 10pt;">
                                <tr>
                                    <td style="padding-left: 5px; width:70%;  background: '.$bg.'; '.$border.'">
                                        <h4 style="'.$color2.' margin-top:1px; margin-bottom: 1.5px; font-size:12px; text-align: left;">
                                            PERIODO: 
                                        </h4>
                                    </td>
                                    <td style="width:30%; '.$border.'">
                                        <h6 style="margin-top:2px; margin-bottom: 0; text-align: center; font-size: 10px; font-weight: 300;">
                                            '.ucwords(strtolower($periodo)).'
                                        </h6>
                                    </td>  
                                </tr>
                                <tr>
                                    <td style="padding-left: 5px; width:70%;  background: '.$bg.'; '.$border.'">
                                        <h4 style="'.$color2.' margin-top:1px; margin-bottom: 1.5px; font-size:12px; text-align: left;">
                                            CÓDIGO DEPÓSITO:
                                        </h4>
                                    </td>
                                    <td style="width:30%; '.$border.'">
                                        <h6 style="margin-top:2px; margin-bottom: 0; text-align: center; font-size: 10px; font-weight: 300;">
                                            '.$rowUnidadEC['uni_vc_nom'].'
                                        </h6>
                                    </td>  
                                </tr>
                                <tr>
                                    <td style="padding-left: 5px; width:70%;  background: '.$bg.'; '.$border.'">
                                        <h4 style="'.$color2.' margin-top:1px; margin-bottom: 1.5px; margin-bottom: 2px; font-size:12px; text-align: left;">
                                            FECHA EMISIÓN: 
                                        </h4>
                                    </td>
                                    <td style="width:30%; '.$border.'">
                                        <h6 style="margin-top:2px; margin-bottom: 0; text-align: center; font-size: 10px; font-weight: 300;">
                                            '.$fechaEmision.'
                                        </h6>
                                    </td>   
                                </tr>
                                <tr>
                                    <td style="padding-left: 5px; width:70%;  background: '.$bg.'; '.$border.'">
                                        <h4 style="'.$color2.' margin-top:1px; margin-bottom: 1.5px; font-size:12px; text-align: left;">
                                            FECHA VENCIMIENTO: 
                                        </h4>
                                    </td>
                                    <td style="width:30%; '.$border.'">
                                        <h6 style="margin-top: 2px; margin-bottom: 0; text-align: center; font-size: 10px; font-weight: 300;">
                                            '.$fechaVencimiento.'
                                        </h6>
                                    </td>  
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="3" style="width: 100%;">
                            <table cellspacing="0" style="width:100%; '.$border2.' text-align: center; font-size: 10pt;">
                                 <tr style="'.$border2.'">
                                    <td colspan="2" style="width:100%; background:'.$bg.'; '.$border.'">
                                        <h4 style="'.$color2.' margin-top:2px; margin-bottom: 2px; font-size:14px; text-align: center;">
                                            RESUMEN DEL RECIBO 
                                        </h4>
                                    </td>
                                 </tr>
                                 <tr style="'.$border2.'">
                                    <td style="'.$border.'">DESCRIPCIÓN</td>
                                    <td style="'.$border.'">Importe en S/</td>
                                 </tr>
                                 <tr style="'.$border2.' height:60px;">
                                    <td style="width:86%; font-size: 10px; text-align: left; padding-left: 5px; padding-top:5px">';
                                        
                                            $contadorConcepto = 0;
                                            $strConcepto = '';
                                            $rowCountConceptos = count($queryConcepto);
                                            $rowCountConceptos -= 1;
                                            $marginButton='4px';
                                            $marginTop='2px';
                                            foreach ($queryConcepto as $rowConcepto) {
                                                $arrFecEmi = explode('-', $rowConcepto['coi_da_fecemi']);
                                                $arrFecVen = explode('-', $rowConcepto['coi_da_fecven']);
                                                $mesAgua = "";
                                                $mesMant = "";
                                                
                                                if (($arrFecVen[1] > $arrFecEmi[1])) {
                                                    $mesAgua = $arrFecEmi[1];
                                                    $mesMant = $arrFecVen[1];
                                                } else {
                                                    $mesAgua = $mesNumeroAnterior;
                                                    $mesMant = $arrFecVen[1];
                                                }

                                                /* RECIBO DE MANTENIMIENTO: DESCRIPCION DE LOS CONCEPTOS Y TOTALES */
                                                $mesRM = $mesNombre;

                                                if ($rowConcepto['con_in_cod'] == 17) {//Cuota de mantenimiento
                                                    $mesRM = $this->getMonthNameInt($mesMant);
                                                }
                                                 
                                                 if ($rowConcepto['con_in_cod'] != 23 && $rowConcepto['con_in_cod'] != 109 && $rowConcepto['con_in_cod'] != 114) { //agua y rec. mant
                                                    if ($rowConcepto['con_in_cod'] == 17) { //agua y rec. mant
                                                        if ($contadorConcepto >= $rowCountConceptos) {
                                                            $strConcepto .= strtoupper(utf8_decode($rowConcepto['con_vc_des'] . ' - ' . $mesRM . ' ' . $anioMant)).'<br>';
                                                        }
                                                    } else {
                                                        if ($contadorConcepto >= $rowCountConceptos) {
                                                            $strConcepto .=  strtoupper(utf8_decode($rowConcepto['con_vc_des'].' '.$rowConcepto['coi_te_com'])).'<br>';
                                                        }
                                                    }
                                                 }
                                                 
                                                 $contadorConcepto++;
                                            }

                                            echo $strConcepto;
                                       
                                    echo '</td><td style="width:14%; font-size: 9px; text-align: center; padding-right:10px; font-weight: bold; padding-top:5px">';

$selectConceptoIngreso1 = "SELECT c.con_in_cod, c.con_vc_des,ci.coi_te_com, ci.coi_do_subtot, ci.coi_da_fecemi, ci.coi_da_fecven FROM concepto_ingreso ci, concepto c WHERE ci.con_in_cod = c.con_in_cod AND ci.ing_in_cod ='".$rowIngresoRM['ing_in_cod']."' ORDER BY 1 DESC";
$queryConcepto1 = $adapter->query($selectConceptoIngreso1,$adapter::QUERY_MODE_EXECUTE)->toArray();

                                                $montoConceptosAnteriores = 0.00;
                                                $contadorConcepto1 = 0;
                                                $strConcepto1 = '';
                                                $rowCountConceptos1 = count($queryConcepto1);
                                                $rowCountConceptos1 -= 1;
                                            
                                                foreach($queryConcepto1 as $rowConcepto1) {
                                                    
                                                    if ($rowConcepto1['con_in_cod'] != 23 && $rowConcepto1['con_in_cod'] != 109 && $rowConcepto1['con_in_cod'] != 114) { //agua y rec. mant
                                                        if ($contadorConcepto1 >= $rowCountConceptos1) {
                                                            $strConcepto1 .= utf8_decode('S/ ' . number_format($rowConcepto1['coi_do_subtot'], 2, ".", "")).'<br>';
                                                        }else{
                                                            $montoConceptosAnteriores += $rowConcepto1['coi_do_subtot'];
                                                        }
                                                    }
                                                    
                                                    $total += $rowConcepto1['coi_do_subtot'];
                                                    $contadorConcepto1++;
                                                }
                                                
                                                echo $strConcepto1;
                                           
                                    echo '</td></tr>';


$selectConceptoIngreso1 = "SELECT c.con_in_cod, c.con_vc_des,ci.coi_te_com, ci.coi_do_subtot, ci.coi_da_fecemi, ci.coi_da_fecven  FROM concepto_ingreso ci, concepto c WHERE ci.con_in_cod = c.con_in_cod AND ci.ing_in_cod ='" . $rowIngresoRM['ing_in_cod'] . "'";
$queryConcepto2 = $adapter->query($selectConceptoIngreso1,$adapter::QUERY_MODE_EXECUTE)->toArray();                                   
                                                
                                                $contentConsumoAgua='';

                                                 foreach ($queryConcepto2 as $rowConcepto2) {
                                                        $arrFecEmi = explode('-', $rowConcepto2['coi_da_fecemi']);
                                                        $arrFecVen = explode('-', $rowConcepto2['coi_da_fecven']);
                                                        $mesAgua = "";
                                                        $mesMant = "";

                                                        if (($arrFecVen[1] > $arrFecEmi[1])) {
                                                            $mesAgua = $arrFecEmi[1];
                                                            $mesMant = $arrFecVen[1];
                                                        } else {
                                                            $mesAgua = $mesNumeroAnterior;
                                                            $mesMant = $arrFecVen[1];
                                                        }
                                                        
                                                        /* ESTADO CUENTA: CONSUMOS INDIVIDUALES DETALLE */

                                                        $mesAnteriorCI = $mesNumeroAnterior - 1;
                                                        $anioAnteriorCI = $anioAnterior;
                                                        if ($mesAnteriorCI == 0) {
                                                            $mesAnteriorCI = 12;
                                                            $anioAnteriorCI--;
                                                        } else {
                                                            if (strlen($mesAnteriorCI) == 1) {
                                                                $mesAnteriorCI = "0" . $mesAnteriorCI;
                                                            }
                                                        }
                                                       
                                                        $queryLecturaAnterior = "SELECT cns_do_lec, DATE_FORMAT(cns_da_fec, '%d/%m/%Y') AS fecLecAnt, cns_vc_tip, cns_vc_tipver FROM consumo WHERE MONTH(cns_da_fec) = '$mesAnteriorCI' AND YEAR(cns_da_fec) = '$anioAnteriorCI' AND cns_vc_ser = 'AGUA' AND uni_in_cod = '" . $rowUnidadEC['uni_in_cod'] . "' ";
									$rowLecturaAnterior = $adapter->query($queryLecturaAnterior,$adapter::QUERY_MODE_EXECUTE)->current();

									$queryLecturaActual = "SELECT cns_do_lec, DATE_FORMAT(cns_da_fec, '%d/%m/%Y') AS fecLecAct, cns_vc_tip, cns_vc_tipver, cns_do_conl, cns_do_conm3, cns_do_congal FROM consumo WHERE MONTH(cns_da_fec) = '$mesNumeroAnterior' AND YEAR(cns_da_fec) = '$anioAnterior' AND cns_vc_ser = 'AGUA' AND uni_in_cod = '" . $rowUnidadEC['uni_in_cod'] . "' ";
									$rowLecturaActual = $adapter->query($queryLecturaActual,$adapter::QUERY_MODE_EXECUTE)->current();

                                    $consumoIndividualEC = 0;
                                    if ($rowLecturaActual['cns_vc_tipver'] == 'LITRO') {
                                        $consumoIndividualEC = $rowLecturaActual['cns_do_conl'];
                                    } elseif ($rowLecturaActual['cns_vc_tipver'] == 'M3') {
                                        $consumoIndividualEC = $rowLecturaActual['cns_do_conm3'];
                                    } else {
                                        $consumoIndividualEC = $rowLecturaActual['cns_do_congal'];
                                    }

                                    if($rowLecturaAnterior['fecLecAnt']==''){
                                        $lecAnt = $this->getEspacio(10);
                                    }else{
                                        $lecAnt = $rowLecturaAnterior['fecLecAnt'];
                                    }

                                    if($rowLecturaActual['fecLecAct']==''){
                                        $lecAct = $this->getEspacio(18);
                                    }else{
                                        $lecAct = $rowLecturaActual['fecLecAct'];
                                    }
                                                        
                                                        /* RECIBO DE MANTENIMIENTO: DESCRIPCION DE LOS CONCEPTOS DE AGUA */
                                                        $mesRM = $mesNombre;

                                                        if ($rowConcepto2['con_in_cod'] == 23  || $rowConcepto2['con_in_cod'] == 109 || $rowConcepto2['con_in_cod'] == 114) { //agua y rec. mant
                                                            $mesRM = $this->getMonthNameInt($mesAgua);
                                                        }

                                                        
                                                        if ($rowConcepto2['con_in_cod'] == 23  || $rowConcepto2['con_in_cod'] == 109 || $rowConcepto2['con_in_cod'] == 114) { //agua y rec. mant
                                                           $contentConsumoAgua= '<tr style="'.$border2.'">
                                                                      <td style="width:86%; font-size: 8.5px; text-align: left; padding-left: 5px;">
                                                                          <h6 style="margin-top: '.$marginTop.'; margin-bottom:'.$marginButton.'; font-size:10px;">
                                                                              '.strtoupper(utf8_decode($rowConcepto2['con_vc_des'] . ' - ' . $mesRM . ' ' . $anioAnterior)).'
                                                                          </h6>
                                                                         FECHA LECTURA ANTERIOR'.$this->getEspacio(2).':'.$this->getEspacio(6).$lecAnt.$this->getEspacio(6).'LECTURA ANTERIOR'.$this->getEspacio(3).':'.$this->getEspacio(5).number_format($rowLecturaAnterior['cns_do_lec'], 2, '.', ',').'<br>
                                                                         FECHA LECTURA ACTUAL'.$this->getEspacio(6).':'.$this->getEspacio(6).$lecAct.$this->getEspacio(6).'LECTURA ACTUAL'.$this->getEspacio(8).':'.$this->getEspacio(7).number_format($rowLecturaActual['cns_do_lec'], 2, '.', ',').'
                                                                      </td>
                                                                      <td style="width:14%; font-size: 9px; text-align: center; padding-right: 10px; font-weight: bold;">
                                                                            <h6 style="margin-top:'.$marginTop.'; margin-bottom:'.$marginButton.'; font-size: 9px;">
                                                                            '.utf8_decode('S/ ' . number_format($rowConcepto2['coi_do_subtot'], 2, ".", "")).'<br>'.$this->getEspacio(1).'
                                                                            </h6>
                                                                            <b>'.$this->getEspacio(1).'</b>
                                                                      </td>
                                                                 </tr>';
                                                            echo $contentConsumoAgua;

                                                        }
                                                }

                                                if($contentConsumoAgua==''){
                                                    echo    '<tr style="'.$border2.'">
                                                                      <td style="width:86%; font-size: 8.5px; text-align: left; padding-left: 5px;">
                                                                          <h6 style="margin-top: '.$marginTop.'; margin-bottom:'.$marginButton.';">
                                                                             &nbsp;
                                                                          </h6>
                                                                         &nbsp;<br>
                                                                         &nbsp;<br>
                                                                      </td>
                                                                      <td style="width:14%; font-size: 9px; text-align: right; padding-right: 10px; font-weight: bold;">
                                                                            <h6 style="margin-top: '.$marginTop.'; margin-bottom: '.$marginButton.';">
                                                                                &nbsp;
                                                                            </h6>
                                                                            <b>'.$this->getEspacio(1).'</b>
                                                                      </td>
                                                                 </tr>';
                                                }

                                             
                                
                               echo ' <tr style="'.$border2.'">
                                    <td colspan="2" style="width:100%;">
                                        '.$this->getEspacio(1).'
                                    </td>
                                 </tr>

                                 <tr style="'.$border2.'">
                                    <td style="width:86%; font-size: 12px; text-align: left; padding-left: 5px; font-weight: bold;">'.utf8_decode('SON:' . $this->covertirNumLetras(number_format($total, 2, ".", "")) . '   NUEVOS SOLES').'<br><br>';
                                            
                                             /* RECIBO DE MANTENIMIENTO: FECHA CANCELADO */
                                            if ($rowIngresoRM['ing_in_est'] == 0) {
$selectIngresoParcial = "SELECT ipa_da_fecpag FROM ingreso_parcial WHERE ing_in_cod = '" . $rowIngresoRM['ing_in_cod'] . "' ORDER BY ipa_da_fecpag DESC LIMIT 0, 1 ";
$rowCancelado = $adapter->query($selectIngresoParcial,$adapter::QUERY_MODE_EXECUTE)->current();        

                                                if ($rowCancelado['ipa_da_fecpag'] != '' && $rowCancelado['ipa_da_fecpag'] != null) {
                                                    $fechaPagoRM = explode('-', $rowCancelado['ipa_da_fecpag']);
                                                } else {
                                                    $fechaPagoRM = '   ';
                                                }
                                            } else {
                                                $fechaPagoRM = '   ';
                                            }
                                            
                                            echo '<table cellspacing="0" style="margin-top: -8px;">
                                                 <tr>
                                                     <td style="width: 80px;">CANCELADO:</td>
                                                     <td style="width: 50px;  text-align: center; '.$borderFpago.'">'.$fechaPagoRM[2].'</td>
                                                     <td style="width: 50px;  text-align: center; '.$borderFpago.'">'.$fechaPagoRM[1].'</td>
                                                     <td style="width: 50px;  text-align: center; '.$borderFpago.'">'.$fechaPagoRM[0].'</td>
                                                     <td style="width: 240px; text-align: right; font-weight: bold;">TOTAL</td>
                                                 </tr>
                                             </table>
                                    </td>
                                    <td style="width:14%; font-size: 9px; text-align: center;">
                                        <h6 style="margin-top: 19px; margin-bottom:16px; font-size: 12px;">
                                            '.utf8_decode('S/ ' . number_format($total, 2, ".", "")).'
                                        </h6>
                                    </td>
                                 </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="3" style="width: 100%;">
                            <br/>
                            
                        </td>
                    </tr>
                    <tr>
                        <td colspan="3" style="width: 100%;">
                            
                        </td>
                    </tr>
                    <tr>
                        <td colspan="3" style="width: 100%;">
                           
                        </td>
                    </tr>
                    <tr>
                        <td colspan="3" style="width: 100%;">
                           
                        </td>
                     </tr>';
                    
					$contenidoRecibo = ob_get_clean();
					echo  $contenidoRecibo; 
                  
                    echo '<tr><td colspan="3" style="width: 100%;">
			                            <h4 style="margin-top:7px; margin-bottom: 1px; font-size:12px; text-align: center;">';
			                               	if($admin!=0){echo "ORIGINAL";}
			                            echo '</h4>
			                        </td>
			                     </tr>
			                </table>
			            </td>
			         </tr>
			    </table>';
 
            echo '</page>';
            $cont++;
        }

    } else {
        header('Location:index.php');
    }
 



	}





public function getReciboEstandar($codEnt,$mesx,$aniox,$tipo,$ids,$filterx,$admin,$validar,$baseUrl)
{
	//echo $codEnt.'::::'.$mesx.'::::'.$aniox.'::::'.$tipo.'::::'.$ids.'::::'.$filterx.'::::'.$admin.'::::'.$validar.'::::'.$baseUrl;
	$adapter=$this->getAdapter();
	    
	//consultamos el tipo de empresa
	$queryEdificio = "SELECT emp_in_cod FROM edificio WHERE edi_in_cod = '$codEnt' ";
	$rowEmpresa = $adapter->query($queryEdificio,$adapter::QUERY_MODE_EXECUTE)->current();
	//print_r($rowEmpresa);

	if($admin=='4' || $admin=='1' || $admin=='2')
	{
		if($rowEmpresa['emp_in_cod']==8)
		{
			$admin='1';
		} else
		{
			$admin='2';
		}
		} else if($admin=='0')
		{
			$admin='0';
		} else
		{
			$admin='2';
	    }

		$cont = 1;
		$fechaEmision = '';
		$fechaEmisionSql = '';
		$fechaVencimiento = '';
		$fechaVencimientoSql = '';
		$queryUnidadEC = '';
		$unidadCodigoAcumulado = '';
		$propietarioNombre = '';
		$propietarioDniRuc = '';
		$propietarioDireccion = '';
		$periodo = '';
		$errorPdf = array();
	    
	    if($admin=='2')
	    {
			$bg = '#9A437A';
			$color2 = 'color:#fff;';
			$border = 'border: solid 0.2px black;';
			$border2 = 'border: solid 0px black;'; 
			$borderFpago = 'border: solid 0px black;';
		} else if($admin=='1')
		{
			$bg = '#2273B6';
			$color2 = 'color:#fff;';
			$border = 'border: solid 0.2px black;';
			$border2 = 'border: solid 0px black;';
			$borderFpago = 'border: solid 0px black;';
		}else if($admin=='0')
		{
			$bg = '#FFFFFF';
			$color2 = 'color:#fff;';
			$border = 'border: solid 0.2px white;';
			$border2 = 'border: solid 0px white;';
			$borderFpago = 'border: solid 0px black;';
		}else
		{
			$bg = '#fff';
			$color2 = 'color:#fff;';
			$border = 'border: solid 0.2px white;';
			$border2 = 'border: solid 0px white;';
			$borderFpago ='border: solid 0px white;';
		}

		/*[OBTENER MES]*/
		/* Sacando los datos de fecha segun el formato del GMT */
		if (isset($mesx))
		{
			$mes = $mesx;
			$mesNumero = $mesx;
			$mesNumeroAnterior = $mesx - 1;
		} else
		{
			$mes = date('m');
			$mesNumero = date('m');
			$mesNumeroAnterior = $mes - 1;
		}

		if (isset($aniox)) {
			$anioAnterior = $aniox;
			$anioMant = $aniox;
			$anio = $aniox;
			$anio_consula = $aniox;
		} else {
			$anioAnterior = date('Y');
			$anioMant = date('Y');
			$anio = date('Y');
			$anio_consula = date('Y');
		}

		$mesNombre = $this->getMonthNameString($mes);

		if (strlen($mesNumero) == 1)
		{
			$mesNumero = "0" . $mesNumero;
		}

		if ($mesNumeroAnterior == 0)
		{
			$mesNumeroAnterior = "12";
			$anioAnterior--;
		} else if (strlen($mesNumeroAnterior) == 1)
		{
			$mesNumeroAnterior = "0" . $mesNumeroAnterior;
		}
	    
	    $fechaActual = date("d/m/Y");
	    
		if (isset($codEnt))
		{
			/* Recuperando el nombre del propietario de la entidad y su RUC */
			$queryEdificioDistrito = "SELECT e.*, d.dis_vc_nom FROM edificio e, distrito d WHERE d.dis_in_cod = e.dis_in_cod AND e.edi_in_cod = '$codEnt' ";
			$rowEntidad = $adapter->query($queryEdificioDistrito,$adapter::QUERY_MODE_EXECUTE)->current();

			$entidadNombre = utf8_decode($rowEntidad['edi_vc_nompro']);        

			if($codEnt!=60)
			{
				if(strlen($entidadNombre)>30)
				{
					$entidadNombre = substr(strip_tags($entidadNombre), 0, 30);
					$entidadNombre.='...';
				}
			}

			$entidadRuc = 'RUC. ' . $rowEntidad['edi_ch_rucpro'];
			$entidadTipoPresupuesto = $rowEntidad['edi_vc_tpre'];
			$entidadDireccion = utf8_decode($rowEntidad['edi_vc_dir']);
			$entidadUrbanizacion = 'URB. '.$rowEntidad['edi_vc_urb'];
			$entidadMensaje = substr(utf8_decode($rowEntidad['edi_te_men']), 0, 500);
			$entidadDistrito = $rowEntidad['dis_vc_nom'];

			//Calcula la fecha de proceso o emision
			if (strlen($rowEntidad['edi_in_diapro']) < 2)
			{
				$fechaEmision = "0" . $rowEntidad['edi_in_diapro'] . "/" . $mesNumero . "/" . $anio;
				$fechaEmisionSql = $anio . '-' . $mesNumero . '-0' . $rowEntidad['edi_in_diapro'];
			} else
			{
				$fechaEmision = $rowEntidad['edi_in_diapro'] . "/" . $mesNumero . "/" . $anio;
				$fechaEmisionSql = $anio . '-' . $mesNumero . '-' . $rowEntidad['edi_in_diapro'];
			}

			$mesNumeroEmi=$mesNumero;

			//Calcula la fecha de vencimiento
			$strmen = '0';
			$strdia = '0';
			(strlen($rowEntidad['edi_in_diapag']) < 2) ? $strdia = '0' : $strdia = '';
			
			if ($rowEntidad['edi_in_diapro'] < $rowEntidad['edi_in_diapag'])
			{
				(strlen($mesNumero) < 2) ? $strmen = '0' : $strmen = '';
				$fechaVencimiento = $strdia . $rowEntidad['edi_in_diapag'] . "/" . $strmen . $mesNumero . "/" . $anio;
				$fechaVencimientoSql = $anio . '-' . $strmen . $mesNumero . '-' . $strdia . $rowEntidad['edi_in_diapag'];
			} else
			{
				if ($mesNumero < 12)
				{
					$mesNumero++;
				} else
				{
					$mesNumero = 1;
				}
				
				(strlen($mesNumero) < 2) ? $strmen = '0' : $strmen = '';
				$fechaVencimiento = $strdia . $rowEntidad['edi_in_diapag'] . "/" . $strmen . $mesNumero . "/" . $anio;
				$fechaVencimientoSql = $anio . '-' . $strmen . $mesNumero . '-' . $strdia . $rowEntidad['edi_in_diapag'];
			}

			$periodo = $this->getFormatMes($strmen.$mesNumeroEmi);

			if ($rowEntidad['edi_in_numser'] != 0 && $rowEntidad['edi_in_numser'] != null)
			{
				$entidadNumeroSerie = $rowEntidad['edi_in_numser'];
			} else
			{
				$entidadNumeroSerie = 1;
			}
			
			$entidadNumeroDocumento = $rowEntidad['edi_in_numdoc'];

			/* Listando las unidades inmobiliarias padres las cuales van a ser encabezado de las unidades secundarias que tenga esa unidad */
			$filArr = explode('CORTE', stripslashes($filterx));
			$filter = '';

			for ($i = 0; $i < (count($filArr)); $i++)
			{
				$filter .= $filArr[$i];
			}

			if (isset($ids))
			{
				$codsUni = stripslashes($ids);
				$codPar = explode(",", $codsUni);
				$codNuevo = "";
				
				for ($xyz = 0; $xyz < count($codPar); $xyz++)
				{
					if ($xyz == 0)
					{
						$codNuevo .= "'" . $codPar[$xyz] . "'";
					} else
					{
						$codNuevo .= ",'" . $codPar[$xyz] . "'";
					}
				}
			
				//echo $codNuevo;
				if($codEnt != '46')
				{
					$selectUnidad = "SELECT ui.* FROM unidad ui WHERE ui.uni_in_pad IS NULL AND ui.edi_in_cod='$codEnt' AND ui.uni_in_est='1' AND (ui.uni_do_cm2 != '0.00' OR (SELECT SUM(uni_do_cm2) FROM unidad uii WHERE uni_in_pad IN (uii.uni_in_cod)) > 0) AND ui.uni_in_cod IN ($codNuevo) $filter";
					$queryUnidadEC = $adapter->query($selectUnidad,$adapter::QUERY_MODE_EXECUTE)->toArray();

				} else
				{
					$selectUnidad = "SELECT ui.* FROM unidad ui WHERE ui.uni_in_pad IS NULL AND ui.edi_in_cod='$codEnt' AND ui.uni_in_est='1' AND ui.uni_in_cod IN ($codNuevo) $filter";
					$queryUnidadEC = $adapter->query($selectUnidad,$adapter::QUERY_MODE_EXECUTE)->toArray();

				}
			} else
			{
				if($codEnt != '46')
				{
					$selectUnidad = "SELECT ui.* FROM unidad ui WHERE ui.uni_in_pad IS NULL AND ui.edi_in_cod='$codEnt' AND ui.uni_in_est='1' AND (ui.uni_do_cm2 != '0.00' OR (SELECT SUM(uni_do_cm2) FROM unidad uii WHERE uni_in_pad IN (uii.uni_in_cod)) > 0) $filter";
					$queryUnidadEC = $adapter->query($selectUnidad,$adapter::QUERY_MODE_EXECUTE)->toArray();
				} else
				{
					$selectUnidad = "SELECT ui.* FROM unidad ui WHERE ui.uni_in_pad IS NULL AND ui.edi_in_cod='$codEnt' AND ui.uni_in_est='1' $filter";
					$queryUnidadEC = $adapter->query($selectUnidad,$adapter::QUERY_MODE_EXECUTE)->toArray();
				}
			}

			$cantidadCopias = 0;
			//print_r($queryUnidadEC);
			/* Listando por unidad inmobiliaria padre para que aparezca un solo estado de cuenta por pagina. */
			foreach($queryUnidadEC as $rowUnidadEC)
			{
				$selectIngresoUsuarioUnidad = "SELECT i.ing_in_cod, i.uni_in_cod, i.ing_in_est,CASE WHEN u.usu_ch_tip = 'PN' THEN CONCAT(u.usu_vc_ape,' ',u.usu_vc_nom) WHEN u.usu_ch_tip = 'PJ' THEN u.usu_vc_ape END AS poseedor, u.usu_ch_dni, u.usu_ch_ruc, u.usu_vc_dir, ui.uni_vc_nom, u.usu_ch_tip, i.ing_in_nroser, i.ing_in_nrodoc, i.ing_da_fecemi, i.ing_da_fecven FROM ingreso i, usuario u, unidad ui WHERE ui.uni_in_cod = i.uni_in_cod AND u.usu_in_cod = i.ing_in_resi AND ui.edi_in_cod = '$codEnt' AND MONTH(i.ing_da_fecemi) = '$mes' AND YEAR(i.ing_da_fecemi) = '$anio_consula' AND ui.uni_in_cod = '" . $rowUnidadEC['uni_in_cod'] . "' ";
				$rowIngresoRM = $adapter->query($selectIngresoUsuarioUnidad,$adapter::QUERY_MODE_EXECUTE)->count();
				
				if(isset($validar))
				{
					if($rowIngresoRM != 1)
					{
						if($validar !='RETURN') $errorPdf[] = $rowUnidadEC['uni_in_cod'];
						continue;
					}
				}
	
				$total = 0; $totalCm2 = 0; $totalM2 = 0; $totalParticipacion = 0;
				$unidadCodigoDepartamento = strtoupper(utf8_decode($rowUnidadEC['uni_vc_nom']));
				$unidadPorcentajeCuotaUnidad = $rowUnidadEC['uni_do_cm2'];
				$unidadAreaOcupada = $rowUnidadEC['uni_do_aocu'];
				$unidadParticipacion=$rowUnidadEC['uni_do_pct'];
				$unidadTipo = strtoupper(utf8_decode($rowUnidadEC['uni_vc_tip']));

				/* Se está concatenando los códigos de las unidades inmobiliarias. */
				$unidadCodigoAcumulado .= "'" . $rowUnidadEC['uni_in_cod'] . "'";

				$selectUsuario = "SELECT usu_ch_tip, usu_vc_nom, usu_vc_ape, usu_ch_dni, usu_ch_ruc, usu_vc_dir FROM usuario WHERE usu_in_cod = '" . $rowUnidadEC['uni_in_pro'] . "' ";
				$rowPropietario = $adapter->query($selectUsuario,$adapter::QUERY_MODE_EXECUTE)->current();

				if ($rowPropietario['usu_ch_tip'] == 'PN')
				{
					/*$propietarioNombre = strtoupper(utf8_decode($rowPropietario['usu_vc_ape'] . " " . $rowPropietario['usu_vc_nom']));
					$propietarioDniRuc = strtoupper(utf8_decode($rowPropietario['usu_ch_dni']));*/
					$propietarioNombre = strtoupper(substr($rowPropietario['usu_vc_ape'] . " " . $rowPropietario['usu_vc_nom'],0,60));
					$propietarioDniRuc = ucwords(strtoupper(utf8_decode($rowPropietario['usu_ch_dni'])));
				} else
				{
					/*$propietarioNombre = strtoupper(utf8_decode($rowPropietario['usu_vc_ape']));
					$propietarioDniRuc = strtoupper(utf8_decode($rowPropietario['usu_ch_ruc']));*/
					$propietarioNombre = strtoupper(substr($rowPropietario['usu_vc_ape'],0,60));
					$propietarioDniRuc = ucwords(strtoupper(utf8_decode($rowPropietario['usu_ch_ruc'])));
				}
				
				//$propietarioDireccion = strtoupper(utf8_decode($rowPropietario['usu_vc_dir']));
				$propietarioDireccion = strtoupper(substr($rowPropietario['usu_vc_dir'],0,30));
				$selectUsuario = "SELECT usu_ch_tip, usu_vc_nom, usu_vc_ape, usu_ch_dni, usu_ch_ruc, usu_vc_dir FROM usuario WHERE usu_in_cod = '" . $rowUnidadEC['uni_in_pos'] . "' ";
				$rowPoseedor = $adapter->query($selectUsuario,$adapter::QUERY_MODE_EXECUTE)->current();

				if ($rowPoseedor['usu_ch_tip'] == 'PN')
				{
					/*$poseedorNombre = ucwords(strtoupper(utf8_decode(substr($rowPoseedor['usu_vc_ape'] . " " . $rowPoseedor['usu_vc_nom'],0,30))));
					$poseedorNombre=ucwords(strtolower(utf8_encode($poseedorNombre)));
					$poseedorDniRuc = ucwords(strtoupper(utf8_decode($rowPoseedor['usu_ch_dni'])));*/
					$poseedorNombre = strtoupper(substr($rowPoseedor['usu_vc_ape'] . " " . $rowPoseedor['usu_vc_nom'],0,60));
					$poseedorDniRuc = ucwords(strtoupper(utf8_decode($rowPoseedor['usu_ch_dni'])));
				} else
				{
					//$poseedorNombre = ucwords(strtoupper(utf8_decode(substr($rowPoseedor['usu_vc_ape'],0,30))));
					/*$poseedorNombre = strtoupper(utf8_decode(substr($rowPoseedor['usu_vc_ape'],0,26)));
					$poseedorDniRuc = ucwords(strtoupper(utf8_decode($rowPoseedor['usu_ch_ruc'])));*/
					$poseedorNombre = strtoupper(substr($rowPoseedor['usu_vc_ape'],0,60));
					$poseedorDniRuc = ucwords(strtoupper(utf8_decode($rowPoseedor['usu_ch_ruc'])));
				}

				$poseedorDireccion = strtoupper(substr($rowPoseedor['usu_vc_dir'],0,30));

				/*
				if($cont=='1')
				{
				echo '<page orientation="paysage">';
				} else
				{
				echo '<page orientation="paysage" >';
				}
				*/
				/* ===== SECCION: RECIBO DE MANTENIMIENTO ===== */
				//echo "entidad:".$codEnt."mes:".$mes."anio:".$anio_consula."unidad:".$rowUnidadEC['uni_in_cod'];
				$selectIngresoUsuarioUnidad = "SELECT i.ing_in_cod, i.uni_in_cod, i.ing_in_est,CASE WHEN u.usu_ch_tip = 'PN' THEN CONCAT(u.usu_vc_ape,' ',u.usu_vc_nom) WHEN u.usu_ch_tip = 'PJ' THEN u.usu_vc_ape END AS poseedor, u.usu_ch_dni, u.usu_ch_ruc, u.usu_vc_dir, ui.uni_vc_nom, u.usu_ch_tip, i.ing_in_nroser, i.ing_in_nrodoc, i.ing_da_fecemi, i.ing_da_fecven FROM ingreso i, usuario u, unidad ui WHERE ui.uni_in_cod = i.uni_in_cod AND u.usu_in_cod = i.ing_in_resi AND ui.edi_in_cod = '$codEnt' AND MONTH(i.ing_da_fecemi) = '$mes' AND YEAR(i.ing_da_fecemi) = '$anio_consula' AND ui.uni_in_cod = '" . $rowUnidadEC['uni_in_cod'] . "' ";
				$rowIngresoRM = $adapter->query($selectIngresoUsuarioUnidad,$adapter::QUERY_MODE_EXECUTE)->current();

				$idIngresoView=$rowIngresoRM['ing_in_cod'];

				//$poseedorRM = strtoupper(utf8_decode($rowIngresoRM['poseedor']));
				$rucRM = utf8_decode($rowIngresoRM['usu_ch_ruc']);
				//$direccionRM = strtoupper(utf8_decode($rowIngresoRM['usu_vc_dir']));
				//$departamentoRM = strtoupper(utf8_decode($rowIngresoRM['uni_vc_nom']));

				if ($rowIngresoRM['usu_ch_tip'] == 'PN')
				{
					$rucRM = utf8_decode($rowIngresoRM['usu_ch_dni']);
				}

				/* RECIBO DE MANTENIMIENTO: CALCULANDO NUMERO DE SERIE */
				$entidadNumeroSerie = $rowIngresoRM['ing_in_nroser'];
				$entidadNumeroDocumento = $rowIngresoRM['ing_in_nrodoc'];
				$entidadNumeroDocumento = substr('00000' . $entidadNumeroDocumento, strlen('00000' . $entidadNumeroDocumento) - 6, 6);
				$entidadNumeroSerie = substr('00000' . $entidadNumeroSerie, strlen('00000' . $entidadNumeroSerie) - 3, 6);

				$nroSerieRM = utf8_decode($entidadNumeroSerie . ' - ' . $entidadNumeroDocumento);

				echo '
				<style type="text/css">
					td, th {
						padding: 1px!important;
					}
				</style>

				<table cellspacing="0" style="width: 100%; text-align: left; font-size: 11pt; page-break-inside: avoid;" idcolor="'.$bg.'">
					<tr>
						<td style="width: 49%; text-align: left;">
							<table cellspacing="0" style="width: 100%; text-align: center; font-size: 10pt;">';
							ob_start();
						echo '
								<tr>
									<td style="width:25%;"> ';
									if($rowEntidad['edi_vc_logo']!='')
									{
									echo '
										<img class="logo" src="'.$baseUrl.'/file/logo-edificio/'.$rowEntidad['edi_vc_logo'].'?t='.time().'"  style="width: 120px; margin-top: 25px;float: left;" />';
									} else
									{
									echo '&nbsp;';
									}
									echo '
									</td>
									<td style="width:35%; line-height: 1;">';
									if($codEnt!=60)
									{
									echo '
										<h5 style="font-size: 16px;margin: 13px 13px 13px 13px;text-align:center;"><b>'.$entidadNombre.'</b></h5>';
									}else
									{
									echo '
										<h5 style="font-size: 16px;margin: 13px 13px 13px 13px;text-align:center;"><b>'.$entidadNombre.' </b></h5>';
									}
									echo '
										<h6 style="margin-top:-2px; margin-bottom: 0; text-align:center; font-size: 9px;">';
											echo $entidadDireccion."<br>";
											echo $entidadUrbanizacion."<br>";
											echo $entidadDistrito;
									echo '
										</h6>
									</td>
									<td style="width:40%;">
										<table cellspacing="0" style="width: 100%; '.$border2.' text-align: center; font-size: 10pt;">
											<tr>
												<td style="width:100%;">
													<h6 style="margin-top:5px; margin-bottom: 5px;"><b>'.$entidadRuc.'</b></h6>
												</td>
											</tr>
											<tr>
												<td style="width:100%; background:'.$bg.';" class="view">
													<h4 style="color:#fff; font-size:18px; margin-top: 10px; margin-bottom: 10px;"><b>RECIBO DE MANTENIMIENTO</b></h4>
												</td>
											</tr>
											<tr>
												<td style="width:100%;">
													<h6 style="margin-top:5px; margin-bottom: 5px;"><b>Nº '.$nroSerieRM.'</b></h6>
												</td>
											</tr>
										</table>
									</td>
								</tr>
								<tr>
									<td colspan="2" style="width:60%;">
										<table cellspacing="0" style="width:100%; '.$border2.'  text-align: center; font-size: 10pt;height: 73px;">
											<tr style="background:'.$bg.';height: 22px;" class="view">
												<td colspan="2">
													<h4 style="color:#fff; margin-top:3px; margin-bottom: 2px; font-size:14px;">
														<b>RECIBO DE MANTENIMIENTO</b>
													</h4>
												</td> 
											</tr>
											<tr>
												<td style="padding-left: 5px;  width:35%;">
													<h4 style="margin-top: 2px; margin-bottom: 1px; font-size:9px; text-align: left;margin-left:8px;">
														<b>RAZÓN SOCIAL: </b>
													</h4>
												</td>
												<td style="width:65%;">
													<h6 style="margin-top: 2px; margin-bottom: 1px; text-align: left; font-size: 9px; font-weight: 300;line-height: 11px;width: 170px; overflow: hidden; height: 20px;margin-left:0px;position: absolute;">'.$propietarioNombre.'</h6>
													<div style="height: 20px;"></div>
												</td>
											</tr>
											<tr>
												<td style="padding-left: 5px; width:35%;">
													<h4 style="margin-top: 0px; margin-bottom: 0px; font-size:9px; text-align: left;margin-left: 8px;">
														<b>DNI / RUC: </b>
													</h4>
												</td>
												<td style="width:65%;">
													<h6 style="margin-top: 0px; margin-bottom: 0px; text-align: left; font-size: 9px; font-weight: 300;">
														'.ucwords(strtoupper($propietarioDniRuc)).'
													</h6>
												</td>
											</tr>
											<tr>
												<td style="width:35%; padding-left: 5px; ">
													<h4 style="margin-top: 1px; margin-bottom: 1px; font-size:9px; text-align: left;margin-left: 8px;">
														<b>DOMICILIO FISCAL: </b>
													</h4>
												</td>
												<td style="width:65%;">
													<h6 style="margin-top: 1px; margin-bottom: 1px; text-align: left; font-size: 9px; font-weight: 300;">'.$propietarioDireccion.'</h6>
												</td>
											</tr>
										</table>
									</td>
									<td style="width: 40%;">
										<table cellspacing="0" style="width:100%; '.$border2.' text-align: center; font-size: 10pt;">
											<tr>
												<td style="padding-left: 5px; width:70%;  background: '.$bg.'; '.$border2.'" class="view">
													<h4 style="color:#fff; margin-top:2px; margin-bottom: 1px; font-size:12px; text-align: left;margin-left: 8px;">
														<b>PERIODO: </b>
													</h4>
												</td>
												<td style="width:30%; '.$border2.'">
													<h6 style="margin-top:4px; margin-bottom: 0; text-align: center; font-size: 9px; font-weight: 300;">
														<b>'.ucwords(strtolower($periodo)).'</b>
													</h6>
												</td>
											</tr>
											<tr>
												<td style="padding-left: 5px; width:70%;  background: '.$bg.'; '.$border2.'" class="view">
													<h4 style="color:#fff; margin-top:2px; margin-bottom: 1px; font-size:12px; text-align: left;margin-left: 8px;">
														<b>CÓDIGO DEPÓSITO:</b>
													</h4>
												</td>
												<td style="width:30%; '.$border2.'">
													<h6 style="margin-top:2px; margin-bottom: 0; text-align: center; font-size: 10px; font-weight: 300;">
														<b>'.$rowUnidadEC['uni_vc_codpag'].'</b>
													</h6>
												</td>
											</tr>
											<tr>
												<td style="padding-left: 5px; width:70%;  background: '.$bg.'; '.$border2.'" class="view">
													<h4 style="color:#fff; margin-top:2px; margin-bottom: 1px; font-size:12px; text-align: left;margin-left: 8px;">
														<b>FECHA EMISIÓN: </b>
													</h4>
												</td>
												<td style="width:30%; '.$border2.'">
													<h6 style="margin-top:4px; margin-bottom: 0; text-align: center; font-size: 9px; font-weight: 300;">
														<b>'.$fechaEmision.'</b>
													</h6>
												</td>
											</tr>
											<tr>
												<td style="padding-left: 5px; width:70%;  background: '.$bg.'; '.$border2.'" class="view">
													<h4 style="color:#fff; margin-top:2px; margin-bottom: 2px; font-size:12px; text-align: left;margin-left: 8px;">
														<b>FECHA VENCIMIENTO: </b>
													</h4>
												</td>
												<td style="width:30%; '.$border2.'">
													<h6 style="margin-top: 4px; margin-bottom: 0; text-align: center; font-size: 9px; font-weight: 300;">
														<b>'.$fechaVencimiento.'</b>
													</h6>
												</td>
											</tr>
										</table>
									</td>
								</tr>
								<tr>
									<td colspan="3" style="width: 100%;">
										<table cellspacing="0" style="width:100%; '.$border2.' text-align: center; font-size: 10pt;margin-bottom: -1px;height: 149px;">
											<tr>
												<td colspan="2" style="width:100%; background: '.$bg.'; '.$border2.'" class="view">
													<h4 style="color:#fff; margin-top:2px; margin-bottom: 2px; font-size:14px; text-align: center;">
														<b>DETALLE</b>
													</h4>
												</td>
											</tr>';
											$maxNumberRowsView=7;
											$totalRowsView=0;
											$textNombreConcetos="";
											$textPrecioConceptos="";

											if($this->existeCuotaDeMantenimiento($idIngresoView)==true)
											{
												$rowCuotaMantenimiento=$this->getRowConceptoCuotaMto($idIngresoView);
												$fechaEmisionConcepto=$rowCuotaMantenimiento['coi_da_fecemi'];
												$arrayFechaEmiConcepto=explode("-", $fechaEmisionConcepto);
												$mesEmitidoConcepto=$arrayFechaEmiConcepto[1];
												$nombreMesCuota=$this->getFormatMes($mesEmitidoConcepto);
												$textNombreConcetos.='<span style="margin-left: 8px;">'.strtoupper(utf8_decode($rowCuotaMantenimiento['con_vc_des'] . ' - ' . $nombreMesCuota . ' ' . $anioMant)).'</span><br>';
												$textPrecioConceptos.=number_format($rowCuotaMantenimiento['coi_do_subtot'],2)."<br>";
												$totalRowsView++;
											}

											$marginButtonSiHayConceptoAgua="0px;";
											
											if($this->existeConsumoDeAgua($idIngresoView)==true)
											{
												$totalRowsView+=3;
											} else
											{
												$marginButtonSiHayConceptoAgua='2px;';   
											}

											$numberRowsDisponibles=$maxNumberRowsView-$totalRowsView;

											$countAllConceptosDeIngreso=$this->getCountConceptos($idIngresoView);
											$contadorConceptos=0;
											$existeOtros=0;
											$sumaTotalOtros=0;

											$getRowsConceptosAdicionales=$this->getRowsConceptosAdicionales($idIngresoView);

											foreach ($getRowsConceptosAdicionales as $key => $value)
											{
												$comentario=strtolower($value['coi_te_com']);
												if(strlen($comentario)>0)
												{
													$comentario=" - ".$comentario;
												} else
												{
													$comentario="";
												}

												if(count($getRowsConceptosAdicionales)==$numberRowsDisponibles)
												{
													$textNombreConcetos.='<span style="margin-left: 8px;">'.strtoupper(utf8_decode($value['con_vc_des'])).'--'.$comentario.'</span><br>';
													$textPrecioConceptos.=number_format($value['coi_do_subtot'],2)."<br>";
												}

												if(count($getRowsConceptosAdicionales)>$numberRowsDisponibles)
												{
													if($contadorConceptos<$numberRowsDisponibles)
													{
														$textNombreConcetos.='<span style="margin-left: 8px;">'.strtoupper(utf8_decode($value['con_vc_des'])).$comentario. '</span><br>';
														$textPrecioConceptos.=number_format($value['coi_do_subtot'],2)."<br>";
													} else
													{
														if($existeOtros==1)
														{
															$sumaTotalOtros+=number_format($value['coi_do_subtot'],2);
														} else
														{
															$sumaTotalOtros+=number_format($value['coi_do_subtot'],2);
															$existeOtros=1;
														}
													}
												} else
												{
													$textNombreConcetos.='<span style="margin-left: 8px;">'.strtoupper(utf8_decode($value['con_vc_des'])).$comentario.'</span><br>';
													$textPrecioConceptos.=number_format($value['coi_do_subtot'],2)."<br>";                                                 
												}
												
												$contadorConceptos++; 
											}

											if($existeOtros==1)
											{
												$textNombreConcetos.='<span style="margin-left: 8px;">OTROS CONCEPTOS</span>';
												$textPrecioConceptos.=number_format($sumaTotalOtros,2)."<br>";
											} else
											{
												for($i=0;$i<=($numberRowsDisponibles-$contadorConceptos);$i++)
												{
													$textNombreConcetos.= "<br>";
													//$textNombreConcetos.= '<span style="margin-left: 8px;">CUOTA DE MANTENIMIENTO - ENERO 2016</span><br>';
												}
											}

										echo '
											<tr>
												<td style="width:86%; font-size: 9px; text-align: left; padding-left: 5px; padding-top:4px;padding-bottom:0px;">
													<div style="line-height: 10px;">'.$textNombreConcetos.'</div>
												</td>
												<td style="width:14%; font-size: 9px; text-align: center; padding-right:10px; font-weight: bold; padding-top:3px;height: 33px;">';
													echo $textPrecioConceptos;

													if($existeOtros==1)
													{
														$textNombreConcetos.=$sumaTotalOtros;
													} else
													{
														for($i=1;$i<=($numberRowsDisponibles-$contadorConceptos);$i++)
														{
															echo "";
														}
													}

											echo '
												</td>
											</tr>';

											if($this->existeConsumoDeAgua($idIngresoView)==true)
											{
												$rowConceptoConsumoAgua=$this->getRowConceptoConsumoAgua($idIngresoView);
												$arrFecEmi = explode('-', $rowConceptoConsumoAgua['coi_da_fecemi']);
												$arrFecVen = explode('-', $rowConceptoConsumoAgua['coi_da_fecven']);
												$mesAgua = "";
												$mesMant = "";

												if (($arrFecVen[1] > $arrFecEmi[1]))
												{
													$mesAgua = $arrFecEmi[1];
													$mesMant = $arrFecVen[1];
												} else
												{
													$mesAgua = $mesNumeroAnterior;
													$mesMant = $arrFecVen[1];
												}

												$mesAnteriorCI = $mesNumeroAnterior - 1;
												$anioAnteriorCI = $anioAnterior;
												
												if ($mesAnteriorCI == 0)
												{
													$mesAnteriorCI = 12;
													$anioAnteriorCI--;
												} else
												{
													if (strlen($mesAnteriorCI) == 1)
													{
														$mesAnteriorCI = "0". $mesAnteriorCI;
													}
												}

												$queryLecturaAnterior = "SELECT cns_do_lec, DATE_FORMAT(cns_da_fec, '%d/%m/%Y') AS fecLecAnt, cns_vc_tip, cns_vc_tipver FROM consumo WHERE MONTH(cns_da_fec) = '$mesAnteriorCI' AND YEAR(cns_da_fec) = '$anioAnteriorCI' AND cns_vc_ser = 'AGUA' AND uni_in_cod = '" . $rowUnidadEC['uni_in_cod'] . "' ";
												$rowLecturaAnterior = $adapter->query($queryLecturaAnterior,$adapter::QUERY_MODE_EXECUTE)->current();

												$queryLecturaActual = "SELECT cns_do_lec, DATE_FORMAT(cns_da_fec, '%d/%m/%Y') AS fecLecAct, cns_vc_tip, cns_vc_tipver, cns_do_conl, cns_do_conm3, cns_do_congal FROM consumo WHERE MONTH(cns_da_fec) = '$mesNumeroAnterior' AND YEAR(cns_da_fec) = '$anioAnterior' AND cns_vc_ser = 'AGUA' AND uni_in_cod = '" . $rowUnidadEC['uni_in_cod'] . "' ";
												$rowLecturaActual = $adapter->query($queryLecturaActual,$adapter::QUERY_MODE_EXECUTE)->current();

												$consumoIndividualEC = 0;

												if ($rowLecturaActual['cns_vc_tipver'] == 'LITRO')
												{
													$consumoIndividualEC = $rowLecturaActual['cns_do_conl'];
												} elseif ($rowLecturaActual['cns_vc_tipver'] == 'M3')
												{
													$consumoIndividualEC = $rowLecturaActual['cns_do_conm3'];
												} else
												{
													$consumoIndividualEC = $rowLecturaActual['cns_do_congal'];
												}

												if($rowLecturaAnterior['fecLecAnt']=='')
												{
													$lecAnt = $this->getEspacio(10);
												} else
												{
													$lecAnt = $rowLecturaAnterior['fecLecAnt'];
												}

												if($rowLecturaActual['fecLecAct']=='')
												{
													$lecAct = $this->getEspacio(18);
												} else
												{
													$lecAct = $rowLecturaActual['fecLecAct'];
												}

												$lecturaAnterior = ($rowLecturaAnterior['cns_vc_tipver']!='')?$rowLecturaAnterior['cns_vc_tipver']:'m3';
												$lecturaActual = ($rowLecturaActual['cns_vc_tipver']!='')?$rowLecturaActual['cns_vc_tipver']:'m3';

												//RECIBO DE MANTENIMIENTO: DESCRIPCION DE LOS CONCEPTOS DE AGUA
												$mesRM = $mesNombre;
												$mesRM = $this->getMonthNameInt($mesAgua);

												$marginButton='0px';
												$marginTop='0px';

												$contentConsumoAgua= '<tr>
													<td style="width:86%; font-size: 7.5px; text-align: left; padding-left: 5px;">
														<h6 style="margin-left: 8px;margin-bottom:0px;font-size:10px;margin-top: 0px;">
															<b>'.strtoupper(utf8_decode($rowConceptoConsumoAgua['con_vc_des'] . ' - ' . $mesRM . ' ' . $anioAnterior)).'</b>
														</h6>
														<div style="line-height: 9px;font-size: 8px;">
															&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
															FECHA LECTURA ANTERIOR'.$this->getEspacio(2).':'.$this->getEspacio(6).$lecAnt.$this->getEspacio(6).'LECTURA ANTERIOR'.$this->getEspacio(3).':'.$this->getEspacio(5).number_format($rowLecturaAnterior['cns_do_lec'], 2).' ('.strtolower($lecturaAnterior).')<br>
															&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
															FECHA LECTURA ACTUAL'.$this->getEspacio(6).':'.$this->getEspacio(6).$lecAct.$this->getEspacio(6).'LECTURA ACTUAL'.$this->getEspacio(8).':'.$this->getEspacio(7).number_format($rowLecturaActual['cns_do_lec'], 2).' ('.strtolower($lecturaActual).')
														</div>
													</td>
													<td style="width:14%; font-size: 14px; text-align: center; padding-right: 10px; font-weight: bold;">
														<h6 style="margin-top:0px;font-size: 9px; margin-bottom:0px;">
															<b>'.utf8_decode('S/ ' . number_format($rowConceptoConsumoAgua['coi_do_subtot'], 2)).'<br>'.$this->getEspacio(1).'</b>
														</h6>
														<b>'.$this->getEspacio(1).'</b>
													</td>
												</tr>';
												
												echo $contentConsumoAgua;
												}
										echo '
											<tr>
												<td style="width:86%; font-size: 10px; text-align: left; padding-left: 5px; font-weight: bold;">
													<h6 style="margin-left: 8px;margin-bottom:0px;font-size:10px;margin-top: 0px;">
														<b>';
															$sumaTotalConceptosMes=$this->getSumConceptos($idIngresoView);
															echo utf8_decode('SON:' . $this->covertirNumLetras(number_format($sumaTotalConceptosMes, 2)) . '   SOLES').'
														</b>
													</h6>
													<br>';  
													/* RECIBO DE MANTENIMIENTO: FECHA CANCELADO */
													if ($rowIngresoRM['ing_in_est'] == 0)
													{
														$selectIngresoParcial = "SELECT ipa_da_fecpag FROM ingreso_parcial WHERE ing_in_cod = '" . $rowIngresoRM['ing_in_cod'] . "' ORDER BY ipa_da_fecpag DESC LIMIT 0, 1 ";
														$rowCancelado = $adapter->query($selectIngresoParcial,$adapter::QUERY_MODE_EXECUTE)->current();
														
														if ($rowCancelado['ipa_da_fecpag'] != '' && $rowCancelado['ipa_da_fecpag'] != null)
														{
															$fechaPagoRM = explode('-', $rowCancelado['ipa_da_fecpag']);
														} else
														{
															$fechaPagoRM = '   ';
														}
														} else
														{
															$fechaPagoRM = '   ';
														}
												echo '
													<table cellspacing="0" style="margin-top: -12px;">
														<tr>
															<td style="width: 80px;font-size: 10px;"><span style="margin-left: 8px;margin-right: 13px;">CANCELADO:</span></td>
															<td style="width: 35px;  text-align: center; '.$borderFpago.'font-size: 9px;"><b>'.$fechaPagoRM[2].'</b></td>
															<td style="width: 35px;  text-align: center; '.$borderFpago.'font-size: 9px;"><b>'.$fechaPagoRM[1].'</b></td>
															<td style="width: 35px;  text-align: center; '.$borderFpago.'font-size: 9px;"><b>'.$fechaPagoRM[0].'</b></td>
															<td style="width: 240px; text-align: right; font-weight: bold;font-size: 9px;"><b>TOTAL</b></td>
														</tr>
													</table>
												</td>
												<td style="width:14%; font-size: 9px; text-align: center;">
													<h6 style="margin-top: 14px; margin-bottom:10px; font-size: 11px;">
														<b>';
															echo utf8_decode('S/ ' . number_format($sumaTotalConceptosMes, 2));
															echo '
														</b>
													</h6>
												</td>
											</tr>
										</table>
									</td>
								</tr>
								<tr>
									<td colspan="3" style="width: 100%;">
									<br>
										<table cellspacing="0" style="width:100%; '.$border2.' text-align: center; font-size: 10pt;margin-bottom: 1px;height: 100px;">
											<tr style="height:20px;">
												<td colspan="3" style="width:100%; background: '.$bg.'; '.$border2.'" class="view">
													<h4 style="color:#fff; margin-top:2px; margin-bottom: 1px; font-size:14px; text-align: center;">
														<b>ESTADO DE CUENTA </b>
													</h4>
												</td>
											</tr>
											<tr style="height:18px;">
												<td style="padding-left: 5px;  width:60%; background: '.$bg.'; '.$border2.'" class="view">
													<h4 style="color: #fff; margin-top: 2px; margin-bottom: 1px; font-size:12px; text-align: center;">
														<b>ARRENDATARIO</b>
													</h4>
												</td>
												<td rowspan="2" style="width:20%; background: '.$bg.'; '.$border2.'" class="view">
													<h4 style="color: #fff;  margin-top: 10px; margin-bottom: 1px; font-size:12px; text-align: center;">
														<b>PERIODO </b>
													</h4>
												</td>
												<td rowspan="2" style="width:20%; '.$border2.'">
													<h6 style="font-size:10px; margin-top: 10px; margin-bottom: 1px;">
														<b>'.$periodo.'</b>
													</h6>
												</td> 
											</tr>
											<tr>
												<td style="padding-left: 5px;  width:60%; text-align: left;">
													<table cellspacing="0" style="width:100%;text-align: center; font-size: 10pt;">
														<tbody>
															<tr>
																<td style="padding-left: 5px;  width:35%;">
																	<h4 style="margin-top: 2px; margin-bottom: 1px; font-size:9px; text-align: left;margin-left:8px;">
																		<b>RAZÓN SOCIAL: </b>
																	</h4>
																</td>
																<td style="width:65%;">
																	<h6 style="margin-top: 2px; margin-bottom: 1px; text-align: left; font-size: 9px; font-weight: 300;line-height: 11px; width: 170px; overflow: hidden; height: 20px;position:absolute;">
																	'.$poseedorNombre.'
																	</h6>
																	<div style="height: 20px;"></div>
																</td>  
															</tr>
														</tbody>
													</table>
												</td>	
											</tr>
											<tr>
												<td style="padding-left: 5px;  width:60%; text-align: left;">
													<table cellspacing="0" style="width:100%;text-align: center; font-size: 10pt;">
														<tbody>
															<tr>
																<td style="padding-left: 5px; width:35%;">
																	<h4 style="margin-top: 0px; margin-bottom: 0px; font-size:9px; text-align: left;margin-left: 8px;">
																		<b>DNI / RUC: </b>
																	</h4>
																</td>
																<td style="width:65%;">
																	<h6 style="margin-top: 0px; margin-bottom: 0px; text-align: left; font-size: 9px; font-weight: 300;">
																		'.ucwords(strtolower($poseedorDniRuc)).'
																	</h6>
																</td>
															</tr>
														</tbody>
													</table>
												</td>
												<td rowspan="2" style="width:20%; background: '.$bg.'; '.$border2.'" class="view">
													<h4 style="color: #fff;  margin-top: 2px; margin-bottom: 1px; font-size:12px; text-align: center;">
														<b>CODIGO DEPÓSITO</b>
													</h4>
												</td>
												<td rowspan="2" style="width:20%; '.$border2.'">
													<h6 style="font-size:10px; margin-top: 10px; margin-bottom: 8px;">
														<b>';
															echo ''.$rowUnidadEC['uni_vc_codpag'];
															echo '
														</b>
													</h6>
												</td> 
											</tr>
											<tr>
												<td style="padding-left: 5px;  width:60%; text-align: left;">
													<table cellspacing="0" style="width:100%;text-align: center; font-size: 10pt;">
														<tbody>
															<tr>
																<td style="width:35%; padding-left: 5px; ">
																	<h4 style="margin-top: 1px; margin-bottom: 1px; font-size:9px; text-align: left;margin-left: 8px;">
																		<b>DOMICILIO FISCAL: </b>
																	</h4>
																</td>
																<td style="width:65%;">
																	<h6 style="margin-top: 1px; margin-bottom: 1px; text-align: left; font-size: 9px; font-weight: 300;">
																		'.$poseedorDireccion.'
																	</h6>
																</td>  
															</tr>
														</tbody>
													</table>
												</td>
											</tr>
										</table>
									</td>
								</tr>
								<tr>
									<td colspan="3" style="width: 100%;">
										<table cellspacing="0" style="width:100%; '.$border2.' text-align: center; font-size: 10pt;margin-bottom: 1px;">
											<tr>
												<td colspan="2" style="width:33%; background: '.$bg.'; '.$border2.'" class="view">
													<h4 style="color:#fff; margin-top:2px; margin-bottom: 1px; font-size:12px; text-align: center;">
														<b>DEUDA PENDIENTE </b>
													</h4>
												</td>
												<td colspan="3" style="width:34%; background: '.$bg.'; '.$border2.'" class="view">
													<h4 style="color:#fff; margin-top:2px; margin-bottom: 1px; font-size:12px; text-align: center;">
														<b>UNIDADES INMOBILIARIAS</b>
													</h4>
												</td>
											</tr>
											<tr>
												<td style="width:34%; background: '.$bg.'; '.$border2.'" class="view">
													<h4 style="color:#fff; margin-top:1px; margin-bottom: 1px; font-size:12px; text-align: center;">
														<b>Mes </b>
													</h4>
												</td>
												<td style="width:16%; background: '.$bg.'; '.$border2.'" class="view">
													<h4 style="color:#fff; margin-top:1px; margin-bottom: 1px; font-size:12px; text-align: center;">
														<b>Monto</b>
													</h4>
												</td>
												<td style="width:18%; background: '.$bg.'; '.$border2.'" class="view">
													<h4 style="color:#fff; margin-top:1px; margin-bottom: 1px; font-size:12px; text-align: center;">
														<b>Tipo</b>
													</h4>
												</td>
												<td style="width:16%; background: '.$bg.'; '.$border2.'" class="view">
													<h4 style="color:#fff; margin-top:1px; margin-bottom: 1px; font-size:12px; text-align: center;">
														<b>Número</b>
													</h4>
												</td>
												<td style="width:16%; background: '.$bg.'; '.$border2.'" class="view">
													<h4 style="color:#fff; margin-top:1px; margin-bottom: 1px; font-size:12px; text-align: center;">
														<b>M2</b>
													</h4>
												</td>
											</tr>
											<tr>
												<td style="text-align: left; width:34%; height: 50px; '.$border2.'">
													<h6 style="font-weight: 300; margin-top: 1px; margin-bottom: 1px; line-height: 1.2; font-size: 8px;">';
														$mes2 = date('m');
														$contadorDeudaPendiente = 0;
														$IngFecEmi = $rowIngresoRM['ing_da_fecemi'];
														$deuPenDesc = '';
														$selectConceptoIngreso = "SELECT i.ing_in_cod, MONTH(ci.coi_da_fecemi) AS mes, YEAR(ci.coi_da_fecemi) AS anio,SUM(ci.coi_do_imp) AS deuda FROM concepto_ingreso ci, ingreso i, concepto c WHERE i.ing_in_cod = ci.ing_in_cod AND c.con_in_cod = ci.con_in_cod AND uni_in_cod IN ('" . $rowUnidadEC['uni_in_cod'] . "') AND ing_in_est != '0' AND c.con_vc_tip = 'INGRESO' AND ci.coi_da_fecemi < '$IngFecEmi' GROUP BY i.ing_in_cod ORDER BY ci.coi_da_fecven ASC";
														$queryDeudaPendiente1 = $adapter->query($selectConceptoIngreso,$adapter::QUERY_MODE_EXECUTE)->toArray();
														$rowCountDeudaPendiente1 = count($queryDeudaPendiente1);
														$rowCountDeudaPendiente1 -= 2;
														foreach ($queryDeudaPendiente1 as $rowDeudaPendiente1)
														{
															if ($contadorDeudaPendiente >= $rowCountDeudaPendiente1)
															{
																$deuPenDesc .= ucwords(strtolower($this->getMonthNameString($rowDeudaPendiente1['mes']))).'<br>';
															}
															$contadorDeudaPendiente++;
														}
														if ($contadorDeudaPendiente > 2)
														{
															echo 'Meses Anteriores<br>';
														}
														
														echo $deuPenDesc;
												echo '
													</h6>
												</td>
												<td style="text-align: left; width:16%; height: 50px; '.$border2.' text-align: center;">
													<h6 style="font-weight: 300; margin-top: 1px; margin-bottom: 1px; line-height: 1.2; font-size: 8px;">';
														$mes2 = date('m');
														$contadorDeudaPendiente = 0;
														$montoMesesAnteriores = 0;
														$montoDeudaPendiente = 0;
														$IngFecEmi = $rowIngresoRM['ing_da_fecemi'];
														$deuPen = '';
														$selectDeudaPendiente = "SELECT i.ing_in_cod, MONTH(ci.coi_da_fecven) AS mes, YEAR(ci.coi_da_fecven) AS anio, SUM(ci.coi_do_imp) AS deuda FROM concepto_ingreso ci, ingreso i, concepto c WHERE i.ing_in_cod = ci.ing_in_cod AND c.con_in_cod = ci.con_in_cod AND uni_in_cod IN ('" . $rowUnidadEC['uni_in_cod'] . "') AND ing_in_est != '0' AND c.con_vc_tip = 'INGRESO' AND ci.coi_da_fecemi < '$IngFecEmi' GROUP BY i.ing_in_cod ORDER BY ci.coi_da_fecven ASC ";
														$queryDeudaPendiente = $adapter->query($selectDeudaPendiente,$adapter::QUERY_MODE_EXECUTE)->toArray();
														$rowCountDeudaPendiente = count($queryDeudaPendiente);
														$rowCountDeudaPendiente -= 2;

														foreach($queryDeudaPendiente as $rowDeudaPendiente)
														{
															$selectIngresoParcial = "SELECT SUM(ipa_do_imp) AS monto FROM ingreso_parcial WHERE ing_in_cod = '" . $rowDeudaPendiente['ing_in_cod'] . "' ";
															$rowPagoParcialDP = $adapter->query($selectIngresoParcial,$adapter::QUERY_MODE_EXECUTE)->current();
															$deudaPendiente = $rowDeudaPendiente['deuda'] - $rowPagoParcialDP['monto'];
															if ($contadorDeudaPendiente >= $rowCountDeudaPendiente)
															{
																$deuPen = $deuPen . 'S/ ' . number_format($deudaPendiente, 2).'&nbsp;<br>';
															} else
															{
																$montoMesesAnteriores += $deudaPendiente;
															}

															$montoDeudaPendiente += $deudaPendiente;
															$contadorDeudaPendiente++;
														}

														if ($contadorDeudaPendiente > 2)
														{
															echo 'S/ ' . number_format($montoMesesAnteriores, 2).'&nbsp;<br>';
														}
														
														echo $deuPen;
												echo '
													</h6>
												</td>';
											echo '
												<td style="width:16%; height: 50px; '.$border2.' text-align: left;">
													<h6 style="font-weight: 300; margin-top: 1px; margin-bottom: 1px; line-height: 1.2; font-size: 8px;">
														'.utf8_decode(ucwords(strtolower($unidadTipo))).'<br>';
														$totalM2 = $totalM2 + $unidadAreaOcupada;
														$totalParticipacion = $totalParticipacion + $unidadParticipacion;
														$selectUnidad = "SELECT * FROM unidad WHERE uni_in_pad='" . $rowUnidadEC['uni_in_cod'] . "' AND uni_in_est != 0";
														$queryUnidadSecundaria = $adapter->query($selectUnidad,$adapter::QUERY_MODE_EXECUTE)->toArray();
														$cont=1;
														foreach ($queryUnidadSecundaria as $rowUnidadSecundaria)
														{
															if($cont<=3)
															{
																echo utf8_decode(ucwords(strtolower($rowUnidadSecundaria['uni_vc_tip']))).'<br>';
															}
															$cont++;
															$totalM2 = $totalM2 + $rowUnidadSecundaria['uni_do_aocu'];
															$totalParticipacion = $totalParticipacion + $rowUnidadSecundaria['uni_do_pct'];
														}
												echo '
													</h6>
												</td>
												<td style="width:16%; height: 50px; '.$border2.'">
													<h6 style="font-weight: 300; margin-top: 1px; margin-bottom: 1px; line-height: 1.2; font-size: 8px;">';
														echo utf8_decode(substr($unidadCodigoDepartamento, 0, 8))."<br>";
														$selectUnidad = "SELECT uni_vc_nom FROM unidad WHERE uni_in_pad='" . $rowUnidadEC['uni_in_cod'] . "' AND uni_in_est != 0";
														$queryUnidadSecundaria1 = $adapter->query($selectUnidad,$adapter::QUERY_MODE_EXECUTE)->toArray();
														$cont=1;
														foreach ($queryUnidadSecundaria1 as $rowUnidadSecundaria1)
														{
															if($cont<=3)
															{
																echo utf8_decode(ucwords(strtolower($rowUnidadSecundaria1['uni_vc_nom']))).'<br>';
															}
															$cont++;
														}
												echo '
													</h6>
												</td>
												<td style="width:16%; height: 50px; '.$border2.' text-align: center;">
													<h6 style="font-weight: 300; margin-top: 1px; margin-bottom: 1px; line-height: 1.2; font-size: 8px;text-align:right;">';
														echo utf8_decode($unidadAreaOcupada)." m2 / ".$unidadParticipacion." %<br>";
														$selectUnidad = "SELECT uni_do_aocu, uni_do_pct FROM unidad WHERE uni_in_pad='" . $rowUnidadEC['uni_in_cod'] . "' AND uni_in_est != 0";
														$queryUnidadSecundaria2 = $adapter->query($selectUnidad,$adapter::QUERY_MODE_EXECUTE)->toArray();
														$cont=1;
														foreach ($queryUnidadSecundaria2 as $rowUnidadSecundaria2)
														{
															if($cont<=3)
															{
																echo $rowUnidadSecundaria2['uni_do_aocu']." m2 / ".$rowUnidadSecundaria2['uni_do_pct']." %<br>";
															}
															$cont++;
														}
												echo '
													</h6>
												</td>
											</tr>
											<tr>
												<td style="width:34%; background: '.$bg.'; '.$border2.'" class="view">
													<h4 style="color:#fff; margin-top:3px; margin-bottom: 1px; font-size:12px; text-align: center;">
														<b>Total Deuda S/</b>
													</h4>
												</td>
												<td style="width:16%; '.$border2.'">
													<h6 style="font-size: 12px; line-height: 1; margin-top: 3px; margin-bottom: 1px;"><b>';
														echo 'S/ ' . number_format($montoDeudaPendiente, 2);
														echo '</b>
													</h6>
												</td>
												<td colspan="2" style="width:32%; background: '.$bg.'; '.$border2.'" class="view">
													<h4 style="color:#fff; margin-top:3px; margin-bottom: 1px; font-size:12px; text-align: center;">
														<b>Total M2</b>
													</h4>
												</td>
												<td style="width:16%; '.$border2.' text-align: center;">
													<h6 style="font-size: 8px; line-height: 1; margin-top: 3px; margin-bottom: 1px;">
														<b>';
															echo number_format($totalM2, 2)." m2 / ".number_format($totalParticipacion, 2)." %";
												echo '
														</b>
													</h6>
												</td>
											</tr>
										</table>
									</td>
								</tr>
								<tr>
									<td colspan="3" style="width: 100%;">
										<table cellspacing="0" style="width:100%; '.$border2.' text-align: center; font-size: 10pt;margin-bottom: 1px;">
											<tr>
												<td style="width:18%; background: '.$bg.'; '.$border2.'" class="view">
													<h4 style="color:#fff; margin-top:1px; margin-bottom: 1px; font-size:11.5px; text-align: center;">
														<b>Presupuesto Mensual</b>
													</h4>
												</td>
												<td style="width:16%;  '.$border2.' text-align: center;">
													<h6 style="font-size: 11.5px; line-height: 1; margin-top: 7px; margin-bottom: 1px;"><b>';
														$selectPresupuestoConcepto = "SELECT SUM(pre_do_mon) AS total FROM presupuesto p, concepto c WHERE p.con_in_cod=c.con_in_cod AND c.con_vc_tip='INGRESO' AND p.edi_in_cod='$codEnt' AND CAST(`pre_ch_periodo` AS SIGNED )='$mes' AND pre_ch_anio='$anio_consula' ";
														$rowPresupuesto = $adapter->query($selectPresupuestoConcepto,$adapter::QUERY_MODE_EXECUTE)->current();
														if ($entidadTipoPresupuesto != 'A TODO COSTO')
														{
															echo 'S/'. number_format($rowPresupuesto['total'], 2).'&nbsp;';
														}
												echo '</b>
													</h6>
												</td>
												<td style="width:16%; background: '.$bg.'; '.$border2.'" class="view">
													<h4 style="color:#fff; margin-top:1px; margin-bottom: 1px; font-size:11.5px; text-align: center;">
														<b>Cuota del <br> Mes<b>
													</h4>
												</td>
												<td style="width:16%; '.$border2.' text-align: center;">
													<h6 style="font-size: 11.5px; line-height: 1; margin-top: 7px; margin-bottom: 1px;"><b>';
														echo utf8_decode('S/ ' . number_format($sumaTotalConceptosMes, 2));
													echo '
														</b>
													</h6>
												</td>';

												$totalPagadoMesActual=$this->getTotalPagadoMesActual($idIngresoView);
												$deudaMesActual=$sumaTotalConceptosMes - $totalPagadoMesActual;
												if($codEnt!='48')
												{
													$totalAcumulado = $deudaMesActual + $montoDeudaPendiente;
												} else
												{
													$totalAcumulado = $deudaMesActual + $montoDeudaPendiente;
												}
											echo '
												<td style="width:18%; background: '.$bg.'; '.$border2.'" class="view">
													<h4 style="color:#fff; margin-top:1px; margin-bottom: 1px; font-size:11.5px; text-align: center;">
														<b>Total Acumulado</b>
													</h4>
												</td>
												<td style="width:16%; '.$border2.' text-align: center;">
													<h6 style="font-size: 11.5px; line-height: 1; margin-top: 7px; margin-bottom: 1px;"><b>';
														echo utf8_decode('S/ ' . number_format($totalAcumulado, 2));
														echo '</b>
													</h6>
												</td>
											</tr>
										</table>
									</td>
								</tr>
								<tr>
									<td colspan="3" style="width: 100%;">
										<table cellspacing="0" style="width:100%; '.$border2.' text-align: center; font-size: 10pt;height: 94px;">
											<tr>
												<td style="width:80%; background: '.$bg.'; '.$border2.'" class="view">
													<p style="color:#fff; margin-top:7px; margin-bottom: 0px; font-size:12px; text-align: center;">
														MENSAJE
													</p>
												</td>
												<td style="width:20%; background: '.$bg.'; '.$border2.'" class="view">
													<p style="line-height: 14px;color:#fff; margin-top:0px; margin-bottom: 0px; font-size:12px; text-align: center;">
														CÓDIGO DE<br> REFERENCIA
													</p>
												</td>
											</tr>
											<tr>
												<td style="width:80%; '.$border2.' height: 63px;">
													<p style="line-height: 9px;font-family: inherit;font-size:8.3px; margin-top: 1px; margin-bottom: 1px; text-align: left;margin-left: 3px;">';
														echo utf8_encode($entidadMensaje);
												echo '
													</p>
												</td>
												<td style="width:20%; '.$border2.' height: 63px;">
													<p style="margin-top:25px; margin-bottom: 1px; font-size:12px; text-align: center;">';
														echo ''.$rowUnidadEC['uni_vc_codpag'];
												echo '
													</p>
												</td>
											</tr>
										</table>
									</td>
								</tr>';
								$contenidoRecibo = ob_get_clean();
								echo  $contenidoRecibo; 
							echo '
								<tr>
									<td colspan="3" style="width: 100%;">
										<h4 style="margin-top:7px; margin-bottom: 20px; font-size:12px; text-align: center;height:12px;">';
											if($admin!=0)
											{
												echo '<b>ORIGINAL</b>';
											}
									echo '
										</h4>
									</td>
								</tr>
							</table>
						</td>
						<td style="width: 2%; text-align: right;">
							&nbsp;
						</td>
						<td style="width: 49%; text-align: left;">
							<table cellspacing="0" style="width: 100%; text-align: center; font-size: 10pt;">';
								echo  $contenidoRecibo; 
							echo '
								<tr>
									<td colspan="3" style="width: 100%;">
										<h4 style="margin-top:7px; margin-bottom: 20px; font-size:12px; text-align: center;height:12px;">';
											if($admin!=0)
											{
												echo '<b>COPIA</b>';
											}
									echo '
										</h4>
									</td>
								</tr>
							</table>
						</td>
					</tr>
				</table>';
//echo '</page>';
			$cont++;
		}
	} else
	{
		header('Location:index.php');
	}
	
	if($validar != 'RETURN') return $errorPdf;
}











public function getReciboEstandar3($codEnt,$mesx,$aniox,$tipo,$ids,$filterx,$admin,$validar,$baseUrl)
	{
	    // echo $codEnt.'::::'.$mesx.'::::'.$aniox.'::::'.$tipo.'::::'.$ids.'::::'.$filterx.'::::'.$admin.'::::'.$validar.'::::'.$baseUrl;
	    // 77::::1::::2018::::html::::12285::::::::1::::ERROR::::http://edificiook.com/public
	 	$adapter=$this->getAdapter();
	    
	    //consultamos el tipo de empresa
	    $queryEdificio = "SELECT emp_in_cod FROM edificio WHERE edi_in_cod = '$codEnt' ";
		$rowEmpresa = $adapter->query($queryEdificio,$adapter::QUERY_MODE_EXECUTE)->current();
		//print_r($rowEmpresa);

	    if($admin=='4' || $admin=='1' || $admin=='2'){
	        if($rowEmpresa['emp_in_cod']==8 || $rowEmpresa['emp_in_cod']==13){
	            $admin='1';
	        }else{
	            $admin='2';
	        }
	    }else if($admin=='0'){
	        $admin='0';
	    }else{
	        $admin='2';
	    }

	    $cont = 1;
	    $fechaEmision = '';
	    $fechaEmisionSql = '';
	    $fechaVencimiento = '';
	    $fechaVencimientoSql = '';
	    $queryUnidadEC = '';
	    $unidadCodigoAcumulado = '';
	    $propietarioNombre = '';
	    $propietarioDniRuc = '';
	    $propietarioDireccion = '';
	    $periodo = '';
	    $errorPdf = array();
	    
	    //$admin=1;
            $Espacio = "padding-top: 4px; margin-top:4px;";
	    if($admin=='2'){
	        $bg = '#9A437A';
	        $color2 = 'color:#fff;';
	        $border = 'border: solid 0.2px black;';
	        $border2 = 'border: solid 0px black;'; 
	        $borderFpago = 'border: solid 0px black;';
	    }else if($admin=='1'){
	    	//$bg = '#2273B6'; Adinco
        	$bg='#1a52b2';
	        $color2 = 'color:#fff;';
	        $border = 'border: solid 0.2px #1a52b2;';
	        $border2 = 'border: solid 0px black;';
	        $borderFpago = 'border: solid 0px black;';

	    }else if($admin=='0'){
	        $bg = '#FFFFFF';
	        $color2 = 'color:#fff;';
	        $border = 'border: solid 0.2px white;';
	        $border2 = 'border: solid 0px white;';
	        $borderFpago = 'border: solid 0px black;';
	    }else{
	        $bg = '#fff';
	        $color2 = 'color:#fff;';
	        $border = 'border: solid 0.2px white;';
	        $border2 = 'border: solid 0px white;';
	        $borderFpago ='border: solid 0px white;';
	    }
	    
	    /*[OBTENER MES]*/
	    /* Sacando los datos de fecha segun el formato del GMT */
	    if (isset($mesx)) {
	        $mes = $mesx;
	        $mesNumero = $mesx;
	        $mesNumeroAnterior = $mesx - 1;
	    }else {
	        $mes = date('m');
	        $mesNumero = date('m');
	        $mesNumeroAnterior = $mes - 1;
	    }

	    if (isset($aniox)) {
	        $anioAnterior = $aniox;
	        $anioMant = $aniox;
	        $anio = $aniox;
	        $anio_consula = $aniox;
	    } else {
	        $anioAnterior = date('Y');
	        $anioMant = date('Y');
	        $anio = date('Y');
	        $anio_consula = date('Y');
	    }

	    $mesNombre = $this->getMonthNameString($mes);

	    if (strlen($mesNumero) == 1) {
	        $mesNumero = "0" . $mesNumero;
	    }

	    if ($mesNumeroAnterior == 0) {
	        $mesNumeroAnterior = "12";
	        $anioAnterior--;
	    } else if (strlen($mesNumeroAnterior) == 1) {
	        $mesNumeroAnterior = "0" . $mesNumeroAnterior;
	    }
	    $fechaActual = date("d/m/Y");
	    
	     if (isset($codEnt)) {

	        /* Recuperando el nombre del propietario de la entidad y su RUC */
	        $queryEdificioDistrito = "SELECT e.*, d.dis_vc_nom FROM edificio e LEFT JOIN distrito d on d.dis_in_cod = e.dis_in_cod WHERE e.edi_in_cod =$codEnt";
			$rowEntidad = $adapter->query($queryEdificioDistrito,$adapter::QUERY_MODE_EXECUTE)->toArray();
			if(isset($rowEntidad[0])){
				$rowEntidad=$rowEntidad[0];
			}



			$entidadNombre = $rowEntidad['edi_vc_nompro'];        

	        /*
	        if($codEnt!=60){
	            if(strlen($entidadNombre)>30){
	             $entidadNombre = substr(strip_tags($entidadNombre), 0, 30);
	             $entidadNombre.='...';
	            }
	        }
	        */
	        
	        $entidadRuc = 'RUC. ' . $rowEntidad['edi_ch_rucpro'];
	        $entidadTipoPresupuesto = $rowEntidad['edi_vc_tpre'];
	        $entidadDireccion = utf8_decode($rowEntidad['edi_vc_dir']);
	        $entidadUrbanizacion = 'URB. '.$rowEntidad['edi_vc_urb'];
	        $entidadMensaje = substr(utf8_decode($rowEntidad['edi_te_men']), 0, 600);
	        $entidadDistrito = $rowEntidad['dis_vc_nom'];
	        	
	        //Calcula la fecha de proceso o emision
	        if (strlen($rowEntidad['edi_in_diapro']) < 2) {
	            $fechaEmision = "0" . $rowEntidad['edi_in_diapro'] . "/" . $mesNumero . "/" . $anio;
	            $fechaEmisionSql = $anio . '-' . $mesNumero . '-0' . $rowEntidad['edi_in_diapro'];
	        } else {
	            $fechaEmision = $rowEntidad['edi_in_diapro'] . "/" . $mesNumero . "/" . $anio;
	            $fechaEmisionSql = $anio . '-' . $mesNumero . '-' . $rowEntidad['edi_in_diapro'];
	        }

	        $mesNumeroEmi=$mesNumero;

	        //Calcula la fecha de vencimiento
	        $strmen = '0';
	        $strdia = '0';
	        (strlen($rowEntidad['edi_in_diapag']) < 2) ? $strdia = '0' : $strdia = '';
	        if ($rowEntidad['edi_in_diapro'] < $rowEntidad['edi_in_diapag']) {
	            (strlen($mesNumero) < 2) ? $strmen = '0' : $strmen = '';
	            ($mesNumero == 12) ? $anio = intval($anio) : $anio;
	            $fechaVencimiento = $strdia . $rowEntidad['edi_in_diapag'] . "/" . $strmen . $mesNumero . "/" . $anio;
	            $fechaVencimientoSql = $anio . '-' . $strmen . $mesNumero . '-' . $strdia . $rowEntidad['edi_in_diapag'];
	        } else {
	            if ($mesNumero < 12) {
	                $mesNumero++;
	            } else {
	                $mesNumero = 1;
	            }
	            (strlen($mesNumero) < 2) ? $strmen = '0' : $strmen = '';
	            // var_dump($mesNumero);
	            // var_dump($anio);
	            ($mesNumero == 1) ? $anio = intval($anio)+1 : $anio;
	            $fechaVencimiento = $strdia . $rowEntidad['edi_in_diapag'] . "/" . $strmen . $mesNumero . "/" . $anio;
	            $fechaVencimientoSql = $anio . '-' . $strmen . $mesNumero . '-' . $strdia . $rowEntidad['edi_in_diapag'];
	        }
	        
	       $periodo = $this->getFormatMes($strmen.$mesNumeroEmi);

	        if ($rowEntidad['edi_in_numser'] != 0 && $rowEntidad['edi_in_numser'] != null) {
	            $entidadNumeroSerie = $rowEntidad['edi_in_numser'];
	        } else {
	            $entidadNumeroSerie = 1;
	        }
	        $entidadNumeroDocumento = $rowEntidad['edi_in_numdoc'];

	        /* Listando las unidades inmobiliarias padres las cuales van a ser encabezado de las unidades secundarias que tenga esa unidad */
	        $filArr = explode('CORTE', stripslashes($filterx));
	        $filter = '';

	        for ($i = 0; $i < (count($filArr)); $i++){
	            $filter .= $filArr[$i];
	        }

	        if (isset($ids)) {

	            $codsUni = stripslashes($ids);
	            $codPar = explode(",", $codsUni);
	            $codNuevo = "";
	            for ($xyz = 0; $xyz < count($codPar); $xyz++) {
	                if ($xyz == 0) {
	                    $codNuevo .= "'" . $codPar[$xyz] . "'";
	                } else {
	                    $codNuevo .= ",'" . $codPar[$xyz] . "'";
	                }
	            }
	          	//echo $codNuevo;
	            if($codEnt != '46'){ 

	            $selectUnidad = "SELECT ui.* FROM unidad ui WHERE ui.uni_in_pad IS NULL AND ui.edi_in_cod='$codEnt' AND ui.uni_in_est='1' AND (ui.uni_do_cm2 != '0.00' OR (SELECT SUM(uni_do_cm2) FROM unidad uii WHERE uni_in_pad IN (uii.uni_in_cod)) > 0) AND ui.uni_in_cod IN ($codNuevo) $filter";
				$queryUnidadEC = $adapter->query($selectUnidad,$adapter::QUERY_MODE_EXECUTE)->toArray();

	            } else {

	            $selectUnidad = "SELECT ui.* FROM unidad ui WHERE ui.uni_in_pad IS NULL AND ui.edi_in_cod='$codEnt' AND ui.uni_in_est='1' AND ui.uni_in_cod IN ($codNuevo) $filter";
				$queryUnidadEC = $adapter->query($selectUnidad,$adapter::QUERY_MODE_EXECUTE)->toArray();

	            }

	            
	        } else {
	              
	                if($codEnt != '46'){ 

	                	$selectUnidad = "SELECT ui.* FROM unidad ui WHERE ui.uni_in_pad IS NULL AND ui.edi_in_cod='$codEnt' AND ui.uni_in_est='1' AND (ui.uni_do_cm2 != '0.00' OR (SELECT SUM(uni_do_cm2) FROM unidad uii WHERE uni_in_pad IN (uii.uni_in_cod)) > 0) $filter";
						$queryUnidadEC = $adapter->query($selectUnidad,$adapter::QUERY_MODE_EXECUTE)->toArray();

	                } else {

	                	$selectUnidad = "SELECT ui.* FROM unidad ui WHERE ui.uni_in_pad IS NULL AND ui.edi_in_cod='$codEnt' AND ui.uni_in_est='1' $filter";
						$queryUnidadEC = $adapter->query($selectUnidad,$adapter::QUERY_MODE_EXECUTE)->toArray();

	                }


	        }

	        $cantidadCopias = 0;
	        //print_r($queryUnidadEC);
	        /* Listando por unidad inmobiliaria padre para que aparezca un solo estado de cuenta por pagina. */
        foreach($queryUnidadEC as $rowUnidadEC){

        		$selectIngresoUsuarioUnidad = "SELECT i.ing_in_cod, i.uni_in_cod, i.ing_in_est,CASE WHEN u.usu_ch_tip = 'PN' THEN CONCAT(u.usu_vc_ape,' ',u.usu_vc_nom) WHEN u.usu_ch_tip = 'PJ' THEN u.usu_vc_ape END AS poseedor, u.usu_ch_dni, u.usu_ch_ruc, u.usu_vc_dir, ui.uni_vc_nom, u.usu_ch_tip, i.ing_in_nroser, i.ing_in_nrodoc, i.ing_da_fecemi, i.ing_da_fecven FROM ingreso i, usuario u, unidad ui WHERE ui.uni_in_cod = i.uni_in_cod AND u.usu_in_cod = i.ing_in_resi AND ui.edi_in_cod = '$codEnt' AND MONTH(i.ing_da_fecemi) = '$mes' AND YEAR(i.ing_da_fecemi) = '$anio_consula' AND ui.uni_in_cod = '" . $rowUnidadEC['uni_in_cod'] . "' ";
				$rowIngresoRM = $adapter->query($selectIngresoUsuarioUnidad,$adapter::QUERY_MODE_EXECUTE)->count();
				if(isset($validar)){
					if($rowIngresoRM != 1){
						if($validar !='RETURN') $errorPdf[] = $rowUnidadEC['uni_in_cod'];
						continue;
					}
				}
					

                $total = 0; $totalCm2 = 0; $totalM2 = 0; $totalParticipacion = 0;
                $unidadCodigoDepartamento = strtoupper(utf8_decode($rowUnidadEC['uni_vc_nom']));
                $unidadPorcentajeCuotaUnidad = $rowUnidadEC['uni_do_cm2'];
                $unidadAreaOcupada = $rowUnidadEC['uni_do_aocu'];
                $unidadParticipacion=$rowUnidadEC['uni_do_pct'];
                $unidadTipo = strtoupper(utf8_decode($rowUnidadEC['uni_vc_tip']));
               
                /* Se está concatenando los códigos de las unidades inmobiliarias. */
                $unidadCodigoAcumulado .= "'" . $rowUnidadEC['uni_in_cod'] . "'";

                $selectUsuario = "SELECT usu_ch_tip, usu_vc_nom, usu_vc_ape, usu_ch_dni, usu_ch_ruc, usu_vc_dir FROM usuario WHERE usu_in_cod = '" . $rowUnidadEC['uni_in_pro'] . "' ";
				$rowPropietario = $adapter->query($selectUsuario,$adapter::QUERY_MODE_EXECUTE)->current();

                if ($rowPropietario['usu_ch_tip'] == 'PN') {
                    /*$propietarioNombre = strtoupper(utf8_decode($rowPropietario['usu_vc_ape'] . " " . $rowPropietario['usu_vc_nom']));
                    $propietarioDniRuc = strtoupper(utf8_decode($rowPropietario['usu_ch_dni']));*/
                    
                    $propietarioNombre = strtoupper(substr($rowPropietario['usu_vc_ape'] . " " . $rowPropietario['usu_vc_nom'],0,60));

                    $propietarioDniRuc = ucwords(strtoupper(utf8_decode($rowPropietario['usu_ch_dni'])));
                } else {
                    /*$propietarioNombre = strtoupper(utf8_decode($rowPropietario['usu_vc_ape']));
                    $propietarioDniRuc = strtoupper(utf8_decode($rowPropietario['usu_ch_ruc']));*/
                    $propietarioNombre = strtoupper(substr($rowPropietario['usu_vc_ape'],0,60));
                    $propietarioDniRuc = ucwords(strtoupper(utf8_decode($rowPropietario['usu_ch_ruc'])));
                }
                //$propietarioDireccion = strtoupper(utf8_decode($rowPropietario['usu_vc_dir']));
                $propietarioDireccion = strtoupper(substr($rowPropietario['usu_vc_dir'],0,60));


                $selectUsuario = "SELECT usu_ch_tip, usu_vc_nom, usu_vc_ape, usu_ch_dni, usu_ch_ruc, usu_vc_dir FROM usuario WHERE usu_in_cod = '" . $rowUnidadEC['uni_in_pos'] . "' ";
				$rowPoseedor = $adapter->query($selectUsuario,$adapter::QUERY_MODE_EXECUTE)->current();

                if ($rowPoseedor['usu_ch_tip'] == 'PN') {
                    /*$poseedorNombre = ucwords(strtoupper(utf8_decode(substr($rowPoseedor['usu_vc_ape'] . " " . $rowPoseedor['usu_vc_nom'],0,30))));
                    $poseedorNombre=ucwords(strtolower(utf8_encode($poseedorNombre)));
                    $poseedorDniRuc = ucwords(strtoupper(utf8_decode($rowPoseedor['usu_ch_dni'])));*/
                    $poseedorNombre = strtoupper(substr($rowPoseedor['usu_vc_ape'] . " " . $rowPoseedor['usu_vc_nom'],0,60));
                    $poseedorDniRuc = ucwords(strtoupper(utf8_decode($rowPoseedor['usu_ch_dni'])));
                } else {
                    //$poseedorNombre = ucwords(strtoupper(utf8_decode(substr($rowPoseedor['usu_vc_ape'],0,30))));
                    /*$poseedorNombre = strtoupper(utf8_decode(substr($rowPoseedor['usu_vc_ape'],0,26)));
                    $poseedorDniRuc = ucwords(strtoupper(utf8_decode($rowPoseedor['usu_ch_ruc'])));*/
                    $poseedorNombre = strtoupper(substr($rowPoseedor['usu_vc_ape'],0,60));
                    $poseedorDniRuc = ucwords(strtoupper(utf8_decode($rowPoseedor['usu_ch_ruc'])));
                }

                $poseedorDireccion = strtoupper(substr($rowPoseedor['usu_vc_dir'],0,60));

			/*if($cont=='1'){
				echo '<page orientation="paysage">';
			}else{
				echo '<page orientation="paysage" >';
			}*/
             /* ===== SECCION: RECIBO DE MANTENIMIENTO ===== */
            //echo "entidad:".$codEnt."mes:".$mes."anio:".$anio_consula."unidad:".$rowUnidadEC['uni_in_cod'];
            $selectIngresoUsuarioUnidad = "SELECT i.ing_in_cod, i.uni_in_cod, i.ing_in_est,CASE WHEN u.usu_ch_tip = 'PN' THEN CONCAT(u.usu_vc_ape,' ',u.usu_vc_nom) WHEN u.usu_ch_tip = 'PJ' THEN u.usu_vc_ape END AS poseedor, u.usu_ch_dni, u.usu_ch_ruc, u.usu_vc_dir, ui.uni_vc_nom, u.usu_ch_tip, i.ing_in_nroser, i.ing_in_nrodoc, i.ing_da_fecemi, i.ing_da_fecven FROM ingreso i, usuario u, unidad ui WHERE ui.uni_in_cod = i.uni_in_cod AND u.usu_in_cod = i.ing_in_resi AND ui.edi_in_cod = '$codEnt' AND MONTH(i.ing_da_fecemi) = '$mes' AND YEAR(i.ing_da_fecemi) = '$anio_consula' AND ui.uni_in_cod = '" . $rowUnidadEC['uni_in_cod'] . "' ";
			$rowIngresoRM = $adapter->query($selectIngresoUsuarioUnidad,$adapter::QUERY_MODE_EXECUTE)->current();

			$idIngresoView=$rowIngresoRM['ing_in_cod'];

                //$poseedorRM = strtoupper(utf8_decode($rowIngresoRM['poseedor']));
                $rucRM = utf8_decode($rowIngresoRM['usu_ch_ruc']);
                //$direccionRM = strtoupper(utf8_decode($rowIngresoRM['usu_vc_dir']));
                //$departamentoRM = strtoupper(utf8_decode($rowIngresoRM['uni_vc_nom']));

                if ($rowIngresoRM['usu_ch_tip'] == 'PN') {
                    $rucRM = utf8_decode($rowIngresoRM['usu_ch_dni']);
                }

                /* RECIBO DE MANTENIMIENTO: CALCULANDO NUMERO DE SERIE */
                $entidadNumeroSerie = $rowIngresoRM['ing_in_nroser'];
                $entidadNumeroDocumento = $rowIngresoRM['ing_in_nrodoc'];
                $entidadNumeroDocumento = substr('00000' . $entidadNumeroDocumento, strlen('00000' . $entidadNumeroDocumento) - 6, 6);
                $entidadNumeroSerie = substr('00000' . $entidadNumeroSerie, strlen('00000' . $entidadNumeroSerie) - 3, 6);

                $nroSerieRM = utf8_decode($entidadNumeroSerie . ' - ' . $entidadNumeroDocumento);

    			$textColorBreak=($admin!=0) ? '#000' : '#fff';

 	echo '



	<style type="text/css">
            table {
                width: 100%;
            }
            td, th {
                    padding: 1px!important;
                }
                
            #tb_01{
            
}
        </style>
    
        <table  cellspacing="0" style="width: 100%; text-align: left; font-size: 11pt; page-break-inside: avoid;" idcolor="'.$bg.'">
            <tbody>
                <tr>
                    <td style="width: 49%; text-align: left;">';
                    ob_start();
                    echo ' 
                        <table>
                            <tbody>
                                <tr>
                                    <td style="width: 100%; text-align: left;">
                                        <table>
                                            <tbody>
                                                <tr>
                                                    <td style="width:25%;vertical-align: top;">';
                    
                                                        if($rowEntidad['edi_vc_logo']!=''){  
                                                            echo '<img class="logo" src="'.$baseUrl.'/file/logo-edificio/'.$rowEntidad['edi_vc_logo'].'?t='.time().'"  style="width: 160px; margin-top: 20px;float: left;vertical-align: top;" />';
                                                        }else{
                                                            echo '&nbsp;';   
                            				}
                                                    
                                                        echo ' 
                                                        </td>
                                                        <td style="width:75%; line-height: 1; text-align: center;">';
                                                            if($codEnt!=60){
                                                                echo '<h5 style="font-size: 16px;margin: 6px 6px;text-align:center;"><b>'.$entidadNombre.'</b></h5>';
                                                            }else{
                                                                echo ' <h5 style="font-size: 16px;margin: 6px 6px;text-align:center;"><b>'.$entidadNombre.' </b></h5>'; 
                                                            }

                                                        echo '<h6 style="margin-top:-2px; margin-bottom: 2; text-align:center; font-size: 8px;">';
                                                        echo $entidadDireccion." - ";
                                                        echo $entidadUrbanizacion."</h6>". '<h6 style="margin-top:0px; margin-bottom: 0; text-align:center; font-size: 9px;">';;
                                                        echo $entidadDistrito;
                                                     	echo "<br><b>".$entidadRuc."</b>";
                                                        echo '</h6>
                                                    </td>
						</tr>
                                                
                                            </tbody>
					</table>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="width: 100%; text-align: left; vertical-align: top; ">
                                        <table style="width: 100%; text-align: left; vertical-align: top;">
                                            <tbody>
                                                <tr>
                                                    <td style="width: 39%; text-align: left; vertical-align: top;">
                                                        <table cellspacing="0" style="'.$border.' width: 100%; text-align: center; vertical-align: top;">
                                                            <tbody>
                                                                <tr>
                                                                    <td style="width: 100%; background:'.$bg.';" class="view">
                                                                        <h4 style="color:#fff; font-size:9px; margin-top: 4px; margin-bottom: 4px;"><b>RECIBO DE MANTENIMIENTO</b></h4>
                                                                    </td>
								</tr>
								<tr>
                                                                    <td style="width:100%;">
                                                                        <h5 style="margin-top:4px; margin-bottom: 4px; font-size:10px;"><b>Nº '.$nroSerieRM.'</b></h5>
                                                                    </td>
								</tr>
                                                            </tbody>
							</table>
                                                    </td>
                                                    <td style="width: 41%; text-align: left; vertical-align: top;">
                                                        <table cellspacing="0" style="'.$border.' width: 100%; text-align: center; vertical-align: top;">
                                                            <tbody>
                                                                <tr>
                                                                    <td>
                                                                        <table cellspacing="0" style="width: 100%;text-align: center; vertical-align: top;">
                                                                            <tbody>
                                                                                <tr>
                                                                                    <td style="padding-left: 5px; width: 70%; background:'.$bg.';" class="view">
                                                                                        <h4 style="color:#fff; margin-top: 4px; margin-bottom: 4px; font-size:9px; text-align: left;margin-left: 8px;">
                                                                                            <b>FECHA&nbsp;EMISIÓN: </b>
                                                                                        </h4>
                                                                                    </td>
                                                                                    <td style="width:30%;">
                                                                                        <h6 style="margin-top:4px; margin-bottom: 0; text-align: center; font-size: 10px; font-weight: 300;">
                                                                                            <b>'.$fechaEmision.'</b>
											</h6>
                                                                                    </td>   
										</tr>
                                                                            </tbody>
									</table>
                                                                    </td>
								</tr>
								<tr>
                                                                    <td style="width:100%;">
                                                                        <table cellspacing="0" style="width: 100%; text-align: center; vertical-align: top;">
                                                                            <tbody>
                                                                                <tr>
                                                                                    <td style="padding-left: 5px; width: 70%; background:'.$bg.'; padding: 0px!important; " class="view">
                                                                                        <h4 style="color:#fff; margin-top: 4px; margin-bottom: 4px; font-size:9px; text-align: left;margin-left: 8px;">
                                                                                            <b>FECHA&nbsp;VENCIMIENTO:&nbsp;</b>
											</h4>
                                                                                    </td>
                                                                                    <td style="width:30%;">
                                                                                        <h6 style="margin-top: 4px; margin-bottom: 0; text-align: center; font-size: 10px; font-weight: 300;">
                                                                                            <b>'.$fechaVencimiento.'</b>
											</h6>
                                                                                    </td>  
										</tr>
                                                                            </tbody>
									</table>
                                                                    </td>
								</tr>
                                                            </tbody>
                                                        </table>
                                                    </td>
                                                    <td style="width: 20%; text-align: left; vertical-align: top;">
                                                        <table cellspacing="0" style="'.$border.'width: 100%; text-align: center; vertical-align: top;">
                                                            <tbody>
                                                                <tr>
                                                                    <td style="width: 100%;background:'.$bg.';" class="view">
                                                                        <h4 style="color:#fff; font-size:9px; margin-top: 4px; margin-bottom: 4px;"><b>CÓDIGO&nbsp;DEPÓSITO</b></h4>
                                                                    </td>
                                                                </tr>
                                                                <tr>
                                                                    <td style="width:100%;">
                                                                        <h5 style="margin-top:4px; margin-bottom: 4px; font-size:10px;"><b>'.$rowUnidadEC['uni_vc_codpag'].'</b></h5>
                                                                    </td>
                                                                </tr>
                                                            </tbody>
                                                        </table>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table> 
                                    </td>
                                    
                                </tr>
                                
                                <tr style="'.$Espacio.'">
                                    <td style="width: 100%; text-align: left; vertical-align: top;padding-left:4px;padding-right:4px;">
                                        <table cellspacing="0" style="width: 100%; '.$border.' text-align: center; vertical-align: top;">
                                            <tr>
                                                <td style="width: 25%; margin-bottom:0px; margin-top:0px; margin-left:0px;margin-right:0px; padding-bottom:0px; padding-top:0px; padding-left:0px;padding-right:0px;">
                                                    <table cellspacing="0" style="width: 100%;text-align: left;vertical-align: top;">
                                                        <tbody>
                                                            <tr>
                                                                <td style="text-align: left;width: 100%;background:'.$bg.';margin-bottom:0px; margin-top:0px;padding-bottom:0px; padding-top:0px; height:26px!important;" class="view">
                                                                    <h5 style="color:#fff; font-size:10px; margin-top: 5px; margin-bottom: 5px; margin-left:0px; margin-right:0px;padding-left:0px;padding-right:0px;"><b>PROPIETARIO</b></h5>
								</td>
                                                            </tr>
							</tbody>
                                                    </table>
						</td>
						<td style="width: 55%; margin-bottom:0px; margin-top:0px; margin-left:0px;margin-right:0px; padding-bottom:0px; padding-top:0px; padding-left:0px;padding-right:0px;">
                                                    <table cellspacing="0" style="width: 100%;text-align: left;vertical-align: top;">
                                                        <tbody>
                                                            <tr style=" ">
                                                                <td style="text-align: left;width: 100%;margin-bottom:0px; margin-top:0px;padding-bottom:0px; padding-top:0px;">
                                                                    <h5 style="color:#000; font-size:10px; margin-top: 5px; margin-bottom: 5px; margin-left:0px; margin-right:0px;padding-left:0px;padding-right:0px;"><b>'.substr($propietarioNombre, 0, 40).'</b></h5>
								</td>
                                                            </tr>
							</tbody>
                                                    </table>
						</td>
						<td style="width: 5%; margin-bottom:0px; margin-top:0px; margin-left:0px;margin-right:0px; padding-bottom:0px; padding-top:0px; padding-left:0px;padding-right:0px;">
                                                    <table cellspacing="0" style="width: 100%;text-align: center; vertical-align: top;">
                                                        <tbody>
                                                            <tr>
                                                                <td style="text-align: center;width: 100%; background:'.$bg.';margin-bottom:0px; margin-top:0px;padding-bottom:0px; padding-top:0px;height:26px!important;" class="view">
                                                                    <h5 style="color:#fff; font-size:10px; margin-top: 5px; margin-bottom: 5px; margin-left:0px; margin-right:0px;padding-left:0px;padding-right:0px;"><b>RUC</b></h5>
								</td>
                                                            </tr>
							</tbody>
                                                    </table>
						</td>
						<td style="width: 15%; margin-bottom:0px; margin-top:0px; margin-left:0px;margin-right:0px; padding-bottom:0px; padding-top:0px; padding-left:0px;padding-right:0px;">
                                                    <table cellspacing="0" style="width: 100%;text-align: left; vertical-align: top;">
                                                        <tbody>
                                                            <tr>
                                                                <td style="text-align: left;width: 100%;margin-bottom:0px; margin-top:0px;padding-bottom:0px; padding-top:0px;height:26px!important;" >
                                                                    <h5 style="color:#000; font-size:10px; margin-top: 5px; margin-bottom: 5px; margin-left:0px; margin-right:0px;padding-left:0px;padding-right:0px;font-weight: 300;"><b>'.ucwords(strtoupper($propietarioDniRuc)).'</b></h5>
								</td>
                                                            </tr>
							</tbody>
                                                    </table>
						</td>
                                            </tr>
                                            <tr>
                                                <td style="width: 25%; margin-bottom:0px; margin-top:0px; margin-left:0px;margin-right:0px; padding-bottom:0px; padding-top:0px; padding-left:0px;padding-right:0px;">
                                                    <table cellspacing="0" style="width: 100%;text-align: left; vertical-align: top;">
                                                        <tbody>
                                                            <tr>
                                                                <td style="text-align: left;width: 100%; background:'.$bg.';margin-bottom:0px; margin-top:0px;padding-bottom:0px; padding-top:0px; height:26px!important;" class="view">
                                                                    <h5 style="color:#fff; font-size:10px; margin-top: 5px; margin-bottom: 5px; margin-left:0px; margin-right:0px;padding-left:0px;padding-right:0px;"><b>RESIDENTE</b></h5>
								</td>
                                                            </tr>
							</tbody>
                                                    </table>
						</td>
						<td style="width: 55%; margin-bottom:0px; margin-top:0px; margin-left:0px;margin-right:0px; padding-bottom:0px; padding-top:0px; padding-left:0px;padding-right:0px;">
                                                    <table cellspacing="0" style="width: 100%;text-align: left; vertical-align: top;">
                                                        <tbody>
                                                            <tr>
                                                                <td style="text-align: left;width: 100%;margin-bottom:0px; margin-top:0px;padding-bottom:0px; padding-top:0px;height:26px!important;">
                                                                    <h5 style="color:#000; font-size:10px; margin-top: 5px; margin-bottom: 5px; margin-left:0px; margin-right:0px;padding-left:0px;padding-right:0px;text-transform: uppercase;"><b>'.substr($poseedorNombre, 0, 40).'</b></h5>
								</td>
                                                            </tr>
							</tbody>
                                                    </table>
						</td>
						<td style="width: 5%; margin-bottom:0px; margin-top:0px; margin-left:0px;margin-right:0px; padding-bottom:0px; padding-top:0px; padding-left:0px;padding-right:0px;">
                                                    <table cellspacing="0" style="width: 100%;text-align: center; vertical-align: top;">
                                                        <tbody>
                                                            <tr>
                                                                <td style="text-align: center;width: 100%; background:'.$bg.';margin-bottom:0px; margin-top:0px;padding-bottom:0px; padding-top:0px; height:26px!important;" class="view">
                                                                    <h5 style="color:#fff; font-size:10px; margin-top: 5px; margin-bottom: 5px; margin-left:0px; margin-right:0px;padding-left:0px;padding-right:0px;"><b>RUC</b></h5>
								</td>
                                                            </tr>
							</tbody>
                                                    </table>
						</td>
						<td style="width: 15%; margin-bottom:0px; margin-top:0px; margin-left:0px;margin-right:0px; padding-bottom:0px; padding-top:0px; padding-left:0px;padding-right:0px;">
                                                    <table cellspacing="0" style="width: 100%; text-align: center; vertical-align: top;">
                                                        <tbody>
                                                            <tr>
                                                                <td style="text-align: left;width: 100%;margin-bottom:0px; margin-top:0px;padding-bottom:0px; padding-top:0px; height:26px!important;">
                                                                    <h5 style="color:#000; font-size:10px; margin-top: 5px; margin-bottom: 5px; margin-left:0px; margin-right:0px;padding-left:0px;padding-right:0px;"><b>'.ucwords(strtolower($poseedorDniRuc)).'</b></h5>
                                                            	</td>
                                                            </tr>
							</tbody>
                                                    </table>
						</td>
                                            </tr>
                                            <tr>
                                                <td style="width: 25%; margin-bottom:0px; margin-top:0px; margin-left:0px;margin-right:0px; padding-bottom:0px; padding-top:0px; padding-left:0px;padding-right:0px;">
                                                    <table cellspacing="0" style="width: 100%;text-align: left;vertical-align: top;">
                                                        <tbody>
                                                            <tr>
                                                                <td style="text-align: left;width: 100%; background:'.$bg.';margin-bottom:0px; margin-top:0px;padding-bottom:0px; padding-top:0px; height:26px!important;" class="view">
                                                                    <h5 style="color:#fff; font-size:10px; margin-top: 5px; margin-bottom: 5px; margin-left:0px; margin-right:0px;padding-left:0px;padding-right:0px;"><b>DOMICILIO FISCAL</b></h5>
								</td>
                                                            </tr>
							</tbody>
                                                    </table>
						</td>
						<td style="width: 55%; margin-bottom:0px; margin-top:0px; margin-left:0px;margin-right:0px; padding-bottom:0px; padding-top:0px; padding-left:0px;padding-right:0px;">
                                                    <table cellspacing="0" style="width: 100%;text-align: left; vertical-align: top;">
                                                        <tbody>
                                                            <tr>
                                                                <td style="text-align: left;width: 100%;margin-bottom:0px; margin-top:0px;padding-bottom:0px; padding-top:0px; height:26px!important;">
                                                                    <h5 style="color:#000; font-size:10px; margin-top: 5px; margin-bottom: 5px; margin-left:0px; margin-right:0px;padding-left:0px;padding-right:0px;"><b> '.$propietarioDireccion.'</b></h5>
								</td>
                                                            </tr>
							</tbody>
                                                    </table>
						</td>
						<td style="width: 5%; margin-bottom:0px; margin-top:0px; margin-left:0px;margin-right:0px; padding-bottom:0px; padding-top:0px; padding-left:0px;padding-right:0px;">
                                                    <table cellspacing="0" style="width: 100%;text-align: center; vertical-align: top;">
                                                        <tbody>
                                                            <tr>
                                                                <td style="width: 100%; margin-bottom:0px; margin-top:0px;padding-bottom:0px; padding-top:0px; height:26px!important;">
                                                                    <h5 style="color:#fff; font-size:10px; margin-top: 5px; margin-bottom: 5px; margin-left:0px; margin-right:0px;padding-left:0px;padding-right:0px;"><b></b></h5>
								</td>
                                                            </tr>
							</tbody>
                                                    </table>
						</td>
						<td style="width: 15%; margin-bottom:0px; margin-top:0px; margin-left:0px;margin-right:0px; padding-bottom:0px; padding-top:0px; padding-left:0px;padding-right:0px;">
                                                    <table cellspacing="0" style="width: 100%;text-align: center; vertical-align: top;">
                                                        <tbody>
                                                            <tr>
                                                                <td style="width: 100%;margin-bottom:0px; margin-top:0px;padding-bottom:0px; padding-top:0px; height:24x;">
                                                                    <h5 style="color:#000; font-size:10px; margin-top: 5px; margin-bottom: 5px; margin-left:0px; margin-right:0px;padding-left:0px;padding-right:0px;"><b></b></h5>
								</td>
                                                            </tr>
							</tbody>
                                                    </table>
						</td>
                                            </tr>
					</table>
                                    </td>
                                </tr>
                                <tr style="'.$Espacio.'">';
                                                        
                                $maxNumberRowsView=7;
                                $totalRowsView=0;
                                $textNombreConcetos="";
                                $textPrecioConceptos="";


                                if($this->existeCuotaDeMantenimiento($idIngresoView)==true){
                                    $rowCuotaMantenimiento=$this->getRowConceptoCuotaMto($idIngresoView);
                                    $fechaEmisionConcepto=$rowCuotaMantenimiento['coi_da_fecemi'];
                                    $arrayFechaEmiConcepto=explode("-", $fechaEmisionConcepto);
                                    $mesEmitidoConcepto=$arrayFechaEmiConcepto[1];
                                    $nombreMesCuota=$this->getFormatMes($mesEmitidoConcepto);
                                    $textNombreConcetos.='<span style="margin-left: 8px;">'.strtoupper(utf8_decode($rowCuotaMantenimiento['con_vc_des'] . ' - ' . $nombreMesCuota . ' ' . $anioMant)).'</span><br>';
                                    $textPrecioConceptos.=number_format($rowCuotaMantenimiento['coi_do_subtot'],2)."<br>";
                                    $totalRowsView++;
                                }

                                $marginButtonSiHayConceptoAgua="0px;";
                                if($this->existeConsumoDeAgua($idIngresoView)==true){
                                    $totalRowsView+=3;
                                }else{
                                    $marginButtonSiHayConceptoAgua='2px;';   
                                }
                                
                                $numberRowsDisponibles=$maxNumberRowsView-$totalRowsView;

                                $countAllConceptosDeIngreso=$this->getCountConceptos($idIngresoView);
                                $contadorConceptos=0;
                                $existeOtros=0;
                                $sumaTotalOtros=0;

                                $getRowsConceptosAdicionales=$this->getRowsConceptosAdicionales($idIngresoView);
                                
                                foreach ($getRowsConceptosAdicionales as $key => $value){
                                    $comentario=strtolower($value['coi_te_com']);
                                    if(strlen($comentario)>0){
                                        $comentario=" - ".$comentario;
                                    }else{
                                        $comentario="";
                                    }

                                    if(count($getRowsConceptosAdicionales)==$numberRowsDisponibles){
                                        $textNombreConcetos.='<span style="margin-left: 8px;">'.strtoupper(utf8_decode($value['con_vc_des'])).'--'.$comentario.'</span><br>';
                                        $textPrecioConceptos.= number_format($value['coi_do_subtot'],2)."<br>";
                                    }else if(count($getRowsConceptosAdicionales)>$numberRowsDisponibles){
                                        if($contadorConceptos<$numberRowsDisponibles){
                                                $textNombreConcetos.='<span style="margin-left: 8px;">'.strtoupper(utf8_decode($value['con_vc_des'])).$comentario. '</span><br>';
                                                $textPrecioConceptos.=number_format($value['coi_do_subtot'],2)."<br>";
                                               
                                        }else{
                                            if($existeOtros==1){
                                                $sumaTotalOtros+=number_format($value['coi_do_subtot'],2);
                                            }else{
                                                    $sumaTotalOtros+=number_format($value['coi_do_subtot'],2);
                                                    $existeOtros=1;
                                            }
                                        }  
                                    }else{
                                        $textNombreConcetos.='<span style="margin-left: 8px;">'.strtoupper(utf8_decode($value['con_vc_des'])).$comentario.'</span><br>';
                                        $textPrecioConceptos.=number_format($value['coi_do_subtot'],2)."<br>";                                                 
                                    }
                                    $contadorConceptos++; 
                                }

                               
                                if($existeOtros==1){
                                     $textNombreConcetos.='<span style="margin-left: 8px;">OTROS CONCEPTOS</span>';
                                     $textPrecioConceptos.=number_format($sumaTotalOtros,2)."<br>";
                                }else{
                                     for($i=0;$i<=($numberRowsDisponibles-$contadorConceptos);$i++){
                                      $textNombreConcetos.= "<br>";
                                     	//$textNombreConcetos.= '<span style="margin-left: 8px;">CUOTA DE MANTENIMIENTO - ENERO 2016</span><br>';
                                     }
                                }

                        		

                                echo '<td style="width: 100%; text-align: left; vertical-align: top;padding-left:4px;padding-right:4px;">
                                        <table cellspacing="0" style="width: 100%; '.$border.' text-align: center; vertical-align: top;">
                                            <tr>
                                                <td style="width: 75%; margin-bottom:0px; margin-top:0px; margin-left:0px;margin-right:0px; padding-bottom:0px; padding-top:0px; padding-left:0px;padding-right:0px;">
                                                    <table cellspacing="0" style="width: 100%; text-align: center; vertical-align: top;">
                                                        <tbody>
                                                            <tr>
                                                                <td style="width: 100%;margin-bottom:0px; margin-top:0px;padding-bottom:0px; padding-top:0px;">
                                                                    <h5 style="color:'.$textColorBreak.'; font-size:12px; margin-top: 2px; margin-bottom: 2px; margin-left:0px; margin-right:0px;padding-left:0px;padding-right:0px; text-align:left;"><b>PRESUPUESTO DEL MES</b></h5>
								</td>
                                                            </tr>
							</tbody>
                                                    </table>
						</td>';
                                                    $PresupuestoMes = 0;
                                                    $selectPresupuestoConcepto = "SELECT SUM(pre_do_mon) AS total FROM presupuesto p, concepto c WHERE p.con_in_cod=c.con_in_cod AND c.con_vc_tip='INGRESO' AND p.edi_in_cod='$codEnt' AND CAST(`pre_ch_periodo` AS SIGNED )='$mes' AND pre_ch_anio='$anio_consula' ";
										$rowPresupuesto = $adapter->query($selectPresupuestoConcepto,$adapter::QUERY_MODE_EXECUTE)->current();
                                                                                $textPrecioConceptos_ = '';
										if ($entidadTipoPresupuesto != 'A TODO COSTO') {
											$textPrecioConceptos_ = 'S/ '. number_format($rowPresupuesto['total'], 2, '.', ',').'&nbsp;';
                                                                                        $PresupuestoMes = number_format($rowPresupuesto['total']);
										}
                                                                                
                                                echo '<td style="width: 45%; margin-bottom:0px; margin-top:0px; margin-left:0px;margin-right:0px; padding-bottom:0px; padding-top:0px; padding-left:0px;padding-right:0px;">
                                                    <table cellspacing="0" style="width: 100%;text-align: center; vertical-align: top;">
                                                        <tbody>
                                                            <tr>
                                                                <td style="width: 100%;margin-bottom:0px; margin-top:0px;padding-bottom:0px; padding-top:0px;">
                                                                    <h5 style="color:#000; font-size:12px; margin-top: 2px; margin-bottom: 2px; margin-left:0px; margin-right:0px;padding-left:0px;padding-right:0px;text-align:right;"><b>'. $textPrecioConceptos_.'</b></h5>
								</td>
                                                            </tr>
							</tbody>
                                                    </table>
						</td>
                                            </tr>										 
					</table>
                                    </td>
				</tr>
                                
                                    <td style="width: 100%; text-align: left; vertical-align: top;padding-left:4px;padding-right:4px;">
                                        <table  cellspacing="0" style="width: 100%; '.$border.' text-align: center; vertical-align: top;  height:150px; ">
                                            <tbody>
                                                <tr style="margin-bottom:0px; margin-top:0px; margin-left:0px;margin-right:0px; padding-bottom:0px; padding-top:0px; padding-left:0px;padding-right:0px; height: 20px;">
                                                    <td style="width: 100%; text-align: left; vertical-align: top; margin-bottom:0px; margin-top:0px; margin-left:0px;margin-right:0px; padding-bottom:0px; padding-top:0px; padding-left:0px;padding-right:0px;">
                                                        <table cellspacing="0" style="width: 100%;text-align: left; vertical-align: top;">
                                                            <tbody>
                                                                <tr>
                                                                    <td style="width: 90%;  background:'.$bg.';" class="view">
                                                                        <h4 style="color:#FFF; font-size:12px; font-weight: bold; margin-top: 4px; margin-bottom: 4px;"><b>CONCEPTOS</b></h4>
                                                                    </td>
                                                                    <td style="width: 10%;background:'.$bg.';" class="view">
                                                                    </td>
								</tr>
                                                            </tbody>
							</table>
                                                    </td>
                                                </tr>
						<tr style="margin-bottom:0px; margin-top:0px;padding-bottom:0px; padding-top:0px;">
                                                    <td style="width: 100%; text-align: left; vertical-align: top; margin-bottom:0px; margin-top:0px;padding-bottom:0px; padding-top:0px;height:150px;">
                                                        <table cellspacing="0" style="width: 100%; text-align: left; vertical-align: top;">
                                                            <tbody>
                                                                <tr>
                                                                    <td style="width:86%; font-size: 9px; text-align: left; padding-left: 5px; padding-top:4px;padding-bottom:0px; vertical-align: top;">
                                                                        <div>'.$textNombreConcetos.'</div>
                                                                    </td>
                                                                    <td style="width:14%; font-size: 9px; text-align: right; padding-right:10px; font-weight: bold; padding-top:3px; vertical-align: top;">
                                                                        '.$textPrecioConceptos.'<br>
                                                                    </td>
								</tr>';
                                
                                                                
                                                               if($this->existeConsumoDeAgua($idIngresoView)==true){
                                                                        $rowConceptoConsumoAgua=$this->getRowConceptoConsumoAgua($idIngresoView);

                                                                        $arrFecEmi = explode('-', $rowConceptoConsumoAgua['coi_da_fecemi']);
                                                                        $arrFecVen = explode('-', $rowConceptoConsumoAgua['coi_da_fecven']);
                                                                        $mesAgua = "";
                                                                        $mesMant = "";

                                                                        if (($arrFecVen[1] > $arrFecEmi[1])) {
                                                                            $mesAgua = $arrFecEmi[1];
                                                                            $mesMant = $arrFecVen[1];
                                                                        } else {
                                                                            $mesAgua = $mesNumeroAnterior;
                                                                            $mesMant = $arrFecVen[1];
                                                                        }

                                                                        $mesAnteriorCI = $mesNumeroAnterior - 1;
                                                                        $anioAnteriorCI = $anioAnterior;
                                                                        if ($mesAnteriorCI == 0) {
                                                                            $mesAnteriorCI = 12;
                                                                            $anioAnteriorCI--;
                                                                        } else {
                                                                            if (strlen($mesAnteriorCI) == 1) {
                                                                                $mesAnteriorCI = "0". $mesAnteriorCI;
                                                                            }
                                                                        }

									$queryLecturaAnterior = "SELECT cns_do_lec, DATE_FORMAT(cns_da_fechalectura, '%d/%m/%Y') AS fecha, cns_vc_tip, cns_vc_tipver FROM consumo WHERE MONTH(cns_da_fec) = '$mesAnteriorCI' AND YEAR(cns_da_fec) = '$anioAnteriorCI' AND cns_vc_ser = 'AGUA' AND uni_in_cod = '" . $rowUnidadEC['uni_in_cod'] . "' ";
									$rowLecturaAnterior = $adapter->query($queryLecturaAnterior,$adapter::QUERY_MODE_EXECUTE)->current();



									$queryLecturaActual = "SELECT cns_do_lec, DATE_FORMAT(cns_da_fechalectura, '%d/%m/%Y') AS fecha, cns_vc_tip, cns_vc_tipver, cns_do_conl, cns_do_conm3, cns_do_congal FROM consumo WHERE MONTH(cns_da_fec) = '$mesNumeroAnterior' AND YEAR(cns_da_fec) = '$anioAnterior' AND cns_vc_ser = 'AGUA' AND uni_in_cod = '" . $rowUnidadEC['uni_in_cod'] . "' ";
									$rowLecturaActual = $adapter->query($queryLecturaActual,$adapter::QUERY_MODE_EXECUTE)->current();

                                                                        $consumoIndividualEC = 0;
                                                                        if ($rowLecturaActual['cns_vc_tipver'] == 'LITRO') {
                                                                            $consumoIndividualEC = $rowLecturaActual['cns_do_conl'];
                                                                        } elseif ($rowLecturaActual['cns_vc_tipver'] == 'M3') {
                                                                            $consumoIndividualEC = $rowLecturaActual['cns_do_conm3'];
                                                                        } else {
                                                                            $consumoIndividualEC = $rowLecturaActual['cns_do_congal'];
                                                                        }

                                                                        if($rowLecturaAnterior['fecha']==''){
                                                                            $lecAnt = $this->getEspacio(10);
                                                                        }else{
                                                                            $lecAnt = $rowLecturaAnterior['fecha'];
                                                                        }


                                                                        if($rowLecturaActual['fecha']==''){
                                                                            $lecAct = $this->getEspacio(18);
                                                                        }else{
                                                                            $lecAct = $rowLecturaActual['fecha'];
                                                                        }

                                                                        $lecturaAnterior = ($rowLecturaAnterior['cns_vc_tipver']!='')?$rowLecturaAnterior['cns_vc_tipver']:'m3';
                                                                        $lecturaActual = ($rowLecturaActual['cns_vc_tipver']!='')?$rowLecturaActual['cns_vc_tipver']:'m3';

                                                                        //RECIBO DE MANTENIMIENTO: DESCRIPCION DE LOS CONCEPTOS DE AGUA
                                                                        $mesRM = $mesNombre;
                                                                        $mesRM = $this->getMonthNameInt($mesAgua);

                                                                        $marginButton='0px';
                                                                        $marginTop='0px';
                                                                        //08/2017
                                                                        $contentConsumoAgua= '<tr>
                                                                        <td style="width:86%; font-size: 7.5px; text-align: left; padding-left: 5px;">
                                                                          <h6 style="margin-left: 8px;margin-bottom:0px;font-size:10px;margin-top: 0px;">
                                                                              <b>'.strtoupper(utf8_decode($rowConceptoConsumoAgua['con_vc_des'] . ' - ' . $mesRM . ' ' . $anioMant)).'</b>
                                                                          </h6>
                                                                          <div style="line-height: 9px;font-size: 8px;">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; FECHA LECTURA ANTERIOR'.$this->getEspacio(2).':'.$this->getEspacio(6).$lecAnt.$this->getEspacio(6).'LECTURA ANTERIOR'.$this->getEspacio(3).':'.$this->getEspacio(5).number_format($rowLecturaAnterior['cns_do_lec'], 2).' ('.strtolower($lecturaAnterior).')<br>
                                      &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; FECHA LECTURA ACTUAL'.$this->getEspacio(6).':'.$this->getEspacio(6).$lecAct.$this->getEspacio(6).'LECTURA ACTUAL'.$this->getEspacio(8).':'.$this->getEspacio(7).number_format($rowLecturaActual['cns_do_lec'], 2).' ('.strtolower($lecturaActual).')</div>
                                                                          
                                                                      </td>
                                                                      <td style="width:14%; font-size: 14px; text-align: center; padding-right: 10px; font-weight: bold;">
                                                                        <h6 style="margin-top:0px;font-size: 9px; margin-bottom:0px; text-align: right;">
                                                                            <b>'.utf8_decode(number_format($rowConceptoConsumoAgua['coi_do_subtot'], 2)).$this->getEspacio(1).'</b>
                                                                        </h6>
                                                                        
                                                                    </td>
                                                                </tr>';
                                                                echo $contentConsumoAgua;
                                                        }
                                                        
                                                                echo '<tr>
                                                                        <td style="width:86%; font-size: 10px; text-align: left; padding-left: 5px; font-weight: bold;">
                                                                             ';
                                                                            $sumaTotalConceptosMes=$this->getSumConceptos($idIngresoView);
                                                                            echo utf8_decode('SON:' . $this->covertirNumLetras(number_format($sumaTotalConceptosMes, 2)) . '   SOLES').'</b></h6><br>';
                        
                                                                            
                                                                            if ($rowIngresoRM['ing_in_est'] == 0) {
                                                                                $selectIngresoParcial = "SELECT ipa_da_fecpag FROM ingreso_parcial WHERE ing_in_cod = '" . $rowIngresoRM['ing_in_cod'] . "' ORDER BY ipa_da_fecpag DESC LIMIT 0, 1 ";
                                                                                $rowCancelado = $adapter->query($selectIngresoParcial,$adapter::QUERY_MODE_EXECUTE)->current();

                                                                                if ($rowCancelado['ipa_da_fecpag'] != '' && $rowCancelado['ipa_da_fecpag'] != null) {
                                                                                    $fechaPagoRM = explode('-', $rowCancelado['ipa_da_fecpag']);
                                                                                } 
                                                                                else {
                                                                                    $fechaPagoRM = '   ';
                                                                                }
                                                                            } 
                                                                            else 
                                                                            {
                                                                                $fechaPagoRM = '   ';
                                                                            }


										                                    echo '<table cellspacing="0" style="margin-top: 1px;">
										                                         <tr>											
																					<td style="width: 80px;font-size: 10px;"><span style="margin-left: 8px;margin-right: 13px;">CANCELADO:</span> <b>'.$fechaPagoRM[2] ." / ".$fechaPagoRM[1] ." / ".$fechaPagoRM[0].'</b> </td>
										                                         </tr>
										                                     </table>';
                                                                       echo ' 
									</td>
									<td style="width:14%; font-size: 9px; text-align: right;">
                                                                             
									</td>
                                                                     </tr>';
                                                       echo '
                                                            </tbody>
                                                        </table>
                                                    </td>
						</tr>
                                                
						 
                        
                                                
                                            </tbody>
					</table>
                                    </td>
    				</tr>
                                
<!-- Nueva Fila --><!-- COUTA DEL MES -->
                                 <tr style="'.$Espacio.'">';
                        

                                                echo '    <td style="width: 100%; text-align: left; vertical-align: top;">
                                                        <table style="'.$border.' width: 100%; text-align: left; vertical-align: top;">
                                                            <tbody>
                                                                <tr style="margin-bottom:0px; margin-top:0px;padding-bottom:0px; padding-top:0px;">
                                                                    <td style="width: 80%; text-align: left; vertical-align: top; margin-bottom:0px; margin-top:0px;padding-bottom:0px; padding-top:0px;">
                                                                        <table cellspacing="0" style="width: 100%;text-align: left; vertical-align: top;">
                                                                            <tbody>
                                                                                <tr>
                                                                                    <td style="width: 100%;">
                                                                                        <h4 style="color:'.$textColorBreak.'; font-size:12px; font-weight: bold; margin-top: 3px; margin-bottom: 3px;"><b>CUOTA DEL MES</b></h4>
                                                                                    </td>
										</tr>
                                                                            </tbody>
									</table>
                                                                    </td>';
                                                                    
                        
                                                                    echo '<td style="width: 20%; text-align: left; vertical-align: top; margin-bottom:0px; margin-top:0px;padding-bottom:0px; padding-top:0px;">
                                                                        <table cellspacing="0" style="width: 100%;text-align: right; vertical-align: top;">
                                                                            <tbody>
                                                                                <tr>
                                                                                    <td style="width: 100%; text-align:right;">
                                                                                        <h4 style="font-size:12px; font-weight: bold; margin-top: 3px; margin-bottom: 3px;text-align: right;"><b>';
                                                                                        echo utf8_decode('S/ ' . number_format($sumaTotalConceptosMes, 2)); 
                                                                                        echo '</b></h4>
                                                                                    </td>
										</tr>
                                                                            </tbody>
									</table>
                                                                    </td>
								</tr>
                                                            </tbody>
							</table>
                                                    </td>
						</tr>
						<tr style="'.$Espacio.'">
                                                    <td style="width: 100%; text-align: left; vertical-align: top; ">
                                                        <table style="width: 100%; text-align: left; vertical-align: top;margin-top: 3px;">
                                                            <tbody>
                                                                <tr>
                                                                    <td style="'.$border.'width: 55%; text-align: left; vertical-align: top; height:110px;">
                                                                        <table cellspacing="0" style="width: 100%; text-align: center; vertical-align: top;">
                                                                            <tbody>
                                                                                <tr>
                                                                                    <td style="width: 100%; background:'.$bg.'; margin-bottom:0px; margin-top:0px; margin-left:0px;margin-right:0px; padding-bottom:0px; padding-top:0px; padding-left:0px;padding-right:0px;" class="view">
                                                                                        <h4 style="color:#fff; font-size:10px; margin-top: 3px; margin-bottom: 3px;"><b>DETALLE DE UNIDADES</b></h4>
                                                                                    </td>
										</tr>
										<tr>
                                                                                    <td style="width:100%;text-align: left;">
                                                                                        <table style="width:100%;text-align: left;">
                                                                                             
                                                                                            <tr>
                                                                                                <td style="width:16%; height: 50px; text-align: left; vertical-align:top;">
                                                                                                    <h6 style="font-weight: 300; margin-top: 1px; margin-bottom: 1px; line-height: 1.2; font-size: 8px;">
                                                                                                       '.utf8_decode(ucwords(strtolower($unidadTipo)))." ".$rowUnidadEC['uni_vc_nom'].'<br>';

                                                                                                           $totalM2 = $totalM2 + $unidadAreaOcupada;
                                                                                                           $totalParticipacion = $totalParticipacion + $unidadParticipacion;

                                                                                                           $selectUnidad = "SELECT * FROM unidad WHERE uni_in_pad='" . $rowUnidadEC['uni_in_cod'] . "' AND uni_in_est != 0";
                                                                                                                                                           $queryUnidadSecundaria = $adapter->query($selectUnidad,$adapter::QUERY_MODE_EXECUTE)->toArray();

                                                                                                           $cont=1;
                                                                                                           foreach ($queryUnidadSecundaria as $rowUnidadSecundaria) {
                                                                                                              if($cont<=3){
                                                                                                                   echo utf8_decode(ucwords(strtolower($rowUnidadSecundaria['uni_vc_tip'])))." ".$rowUnidadSecundaria['uni_vc_nom'].'<br>';
                                                                                                              }
                                                                                                              $cont++;
                                                                                                              $totalM2 = $totalM2 + $rowUnidadSecundaria['uni_do_aocu'];
                                                                                                              $totalParticipacion = $totalParticipacion + $rowUnidadSecundaria['uni_do_pct'];
                                                                                                           }


                                                                                                    echo '</h6>
                                                                                                </td>
                                                                                                 
                                                                                                <td style="width:16%; height: 50px; text-align: center;vertical-align: top;">
                                                                                                    <h6 style="font-weight: 300; margin-top: 1px; margin-bottom: 1px; line-height: 1.2; font-size: 8px;text-align:right;">';

                                                                                                   echo utf8_decode($unidadAreaOcupada)." m2 / ".$unidadParticipacion." %<br>";

                                                                                                   $selectUnidad = "SELECT uni_do_aocu, uni_do_pct FROM unidad WHERE uni_in_pad='" . $rowUnidadEC['uni_in_cod'] . "' AND uni_in_est != 0";
                                                                                                                                           $queryUnidadSecundaria2 = $adapter->query($selectUnidad,$adapter::QUERY_MODE_EXECUTE)->toArray();

                                                                                                   $cont=1;
                                                                                                   foreach ($queryUnidadSecundaria2 as $rowUnidadSecundaria2) {
                                                                                                       if($cont<=3){
                                                                                                           //echo utf8_decode(ucwords(strtolower($rowUnidadSecundaria2['uni_do_aocu']))).'<br>';
                                                                                                           //$rowUnidadSecundaria2['uni_do_pct']='';
                                                                                                           echo $rowUnidadSecundaria2['uni_do_aocu']." m2 / ".$rowUnidadSecundaria2['uni_do_pct']." %<br>";
                                                                                                       }
                                                                                                       $cont++;
                                                                                                   }

                                                                                                    echo '</h6>
                                                                                                </td>
												
												 
                                                                                            </tr>
                                                                                            
											</table>
                                                                                    </td>
										</tr>
                                                                            </tbody>
									</table>
                                                                    </td>
                                                                    <td style="width:2%;"></td>
                                                                    <td style="'.$border.'width: 43%; text-align: left; vertical-align: top; height:110px;">
                                                                        <table cellspacing="0" style="width: 100%;text-align: center; vertical-align: top;">
                                                                            <tbody>
                                                                                <tr>
                                                                                    <td colspan="2" style="width: 100%; background:'.$bg.';" class="view">
                                                                                        <h4 style="color:#fff; font-size:10px; margin-top: 3px; margin-bottom: 3px;"><b>DEUDA ANTERIOR</b></h4>
                                                                                    </td>
										</tr>
										<tr>
                                                                                    <td style="text-align: left; width:34%; height: 50px; vertical-align: top;">
                                                                                                    <h6 style="font-weight: 300; margin-top: 1px; margin-bottom: 1px; line-height: 1.2; font-size: 8px;">';

                                                                                   $mes2 = date('m');

                                                                           $contadorDeudaPendiente = 0;
                                                                           $IngFecEmi = $rowIngresoRM['ing_da_fecemi'];
                                                                           $deuPenDesc = '';

                                                                           $selectConceptoIngreso = "SELECT i.ing_in_cod, MONTH(ci.coi_da_fecemi) AS mes, YEAR(ci.coi_da_fecemi) AS anio,
                                                                           SUM(ci.coi_do_imp) AS deuda FROM concepto_ingreso ci, ingreso i, concepto c WHERE i.ing_in_cod = ci.ing_in_cod
                                                                           AND c.con_in_cod = ci.con_in_cod AND uni_in_cod IN ('" . $rowUnidadEC['uni_in_cod'] . "') AND ing_in_est != '0'
                                                                           AND c.con_vc_tip = 'INGRESO' AND ci.coi_da_fecemi < '$IngFecEmi' GROUP BY i.ing_in_cod
                                                                           ORDER BY ci.coi_da_fecven ASC";
                                                                                           $queryDeudaPendiente1 = $adapter->query($selectConceptoIngreso,$adapter::QUERY_MODE_EXECUTE)->toArray();


                                                                           $rowCountDeudaPendiente1 = count($queryDeudaPendiente1);
                                                                           $rowCountDeudaPendiente1 -= 2;

                                                                           foreach ($queryDeudaPendiente1 as $rowDeudaPendiente1) {
                                                                               if ($contadorDeudaPendiente >= $rowCountDeudaPendiente1) {
                                                                                   $deuPenDesc .= ucwords(strtolower($this->getMonthNameString($rowDeudaPendiente1['mes']))).'<br>';
                                                                               }

                                                                               $contadorDeudaPendiente++;
                                                                           }

                                                                           if ($contadorDeudaPendiente > 2) {
                                                                               echo 'Meses Anteriores<br>';
                                                                           }
                                                                           echo $deuPenDesc;

                                                                                       echo '</h6>
                                                                                    </td>
                                                                                    <td style="text-align: left; width:16%; height: 50px; text-align: center; vertical-align: top;">
                                                                                       <h6 style="font-weight: 300; margin-top: 1px; margin-bottom: 1px; line-height: 1.2; font-size: 8px;text-align: right;">';

                                                                                           $mes2 = date('m');

                                                                                       $contadorDeudaPendiente = 0;
                                                                                       $montoMesesAnteriores = 0;
                                                                                       $montoDeudaPendiente = 0;
                                                                                       $IngFecEmi = $rowIngresoRM['ing_da_fecemi'];
                                                                                       $deuPen = '';

                                                                                       $selectDeudaPendiente = "SELECT i.ing_in_cod, MONTH(ci.coi_da_fecven) AS mes, YEAR(ci.coi_da_fecven) AS anio,
                                                                                       SUM(ci.coi_do_imp) AS deuda FROM concepto_ingreso ci, ingreso i, concepto c WHERE i.ing_in_cod = ci.ing_in_cod
                                                                                       AND c.con_in_cod = ci.con_in_cod AND uni_in_cod IN ('" . $rowUnidadEC['uni_in_cod'] . "') AND ing_in_est != '0' AND c.con_vc_tip = 'INGRESO' AND ci.coi_da_fecemi < '$IngFecEmi' GROUP BY i.ing_in_cod ORDER BY ci.coi_da_fecven ASC ";
                                                                                                                   $queryDeudaPendiente = $adapter->query($selectDeudaPendiente,$adapter::QUERY_MODE_EXECUTE)->toArray();

                                                                                       $rowCountDeudaPendiente = count($queryDeudaPendiente);
                                                                                       $rowCountDeudaPendiente -= 2;

                                                                                       foreach($queryDeudaPendiente as $rowDeudaPendiente) {

                                                                                                   $selectIngresoParcial = "SELECT SUM(ipa_do_imp) AS monto FROM ingreso_parcial WHERE ing_in_cod = '" . $rowDeudaPendiente['ing_in_cod'] . "' ";
                                                                                                                           $rowPagoParcialDP = $adapter->query($selectIngresoParcial,$adapter::QUERY_MODE_EXECUTE)->current();

                                                                                           $deudaPendiente = $rowDeudaPendiente['deuda'] - $rowPagoParcialDP['monto'];

                                                                                           if ($contadorDeudaPendiente >= $rowCountDeudaPendiente) {
                                                                                               $deuPen = $deuPen . 'S/ ' . number_format($deudaPendiente, 2).'&nbsp;<br>';
                                                                                           } else {
                                                                                               $montoMesesAnteriores += $deudaPendiente;
                                                                                           }

                                                                                           $montoDeudaPendiente += $deudaPendiente;
                                                                                           $contadorDeudaPendiente++;
                                                                                       }

                                                                                       if ($contadorDeudaPendiente > 2) {
                                                                                           echo 'S/ ' . number_format($montoMesesAnteriores, 2).'&nbsp;<br>';
                                                                                       }

                                                                                       echo $deuPen;

                                                                                       echo '</h6></td>
										</tr>
                                                                            </tbody>
                                                                        </table>
                                                                    </td>
                                                                </tr>
                                                            </tbody>
                                                        </table>
                                                    </td>
                                                </tr>
                                                
                                <tr style="'.$Espacio.'">';
                                                       $mes2 = date('m');

                                                        $contadorDeudaPendiente = 0;
                                                        $montoMesesAnteriores = 0;
                                                        $montoDeudaPendiente = 0;
                                                        $IngFecEmi = $rowIngresoRM['ing_da_fecemi'];
                                                        $deuPen = '';

                                                        $selectDeudaPendiente = "SELECT i.ing_in_cod, MONTH(ci.coi_da_fecven) AS mes, YEAR(ci.coi_da_fecven) AS anio,
                                                        SUM(ci.coi_do_imp) AS deuda FROM concepto_ingreso ci, ingreso i, concepto c WHERE i.ing_in_cod = ci.ing_in_cod
                                                        AND c.con_in_cod = ci.con_in_cod AND uni_in_cod IN ('" . $rowUnidadEC['uni_in_cod'] . "') AND ing_in_est != '0' AND c.con_vc_tip = 'INGRESO' AND ci.coi_da_fecemi < '$IngFecEmi' GROUP BY i.ing_in_cod ORDER BY ci.coi_da_fecven ASC ";
                                                                                    $queryDeudaPendiente = $adapter->query($selectDeudaPendiente,$adapter::QUERY_MODE_EXECUTE)->toArray();
                                                        $rowCountDeudaPendiente = count($queryDeudaPendiente);
                                                        $rowCountDeudaPendiente -= 2;
                                                        foreach($queryDeudaPendiente as $rowDeudaPendiente) {
                                                            $selectIngresoParcial = "SELECT SUM(ipa_do_imp) AS monto FROM ingreso_parcial WHERE ing_in_cod = '" . $rowDeudaPendiente['ing_in_cod'] . "' ";
                                                                                            $rowPagoParcialDP = $adapter->query($selectIngresoParcial,$adapter::QUERY_MODE_EXECUTE)->current();
                                                            $deudaPendiente = $rowDeudaPendiente['deuda'] - $rowPagoParcialDP['monto'];
                                                            if ($contadorDeudaPendiente >= $rowCountDeudaPendiente) {
                                                                $deuPen = $deuPen . 'S/ ' . number_format($deudaPendiente, 2, '.', ',').'&nbsp;<br>';
                                                            } else {
                                                                $montoMesesAnteriores += $deudaPendiente;
                                                            }
                                                            $montoDeudaPendiente += $deudaPendiente;
                                                            $contadorDeudaPendiente++;
                                                        }

                                                echo '    <td style="width: 100%; text-align: left; vertical-align: top;">
                                                        <table style="'.$border.' width: 100%; text-align: left; vertical-align: top;">
                                                            <tbody>
                                                                <tr style="margin-bottom:0px; margin-top:0px;padding-bottom:0px; padding-top:0px;">
                                                                    <td style="width: 80%; text-align: left; vertical-align: top; margin-bottom:0px; margin-top:0px;padding-bottom:0px; padding-top:0px;">
                                                                        <table cellspacing="0" style="width: 100%;text-align: left; vertical-align: top;">
                                                                            <tbody>
                                                                                <tr>
                                                                                    <td style="width: 100%;">
                                                                                        <h4 style="color:'.$textColorBreak.'; font-size:12px; font-weight: bold; margin-top: 3px; margin-bottom: 3px;"><b>TOTAL ACUMULADO A PAGAR</b></h4>
                                                                                    </td>
										</tr>
                                                                            </tbody>
									</table>
                                                                    </td>';
                                                                    
                                                                    $totalPagadoMesActual=$this->getTotalPagadoMesActual($idIngresoView);
                                                                    $deudaMesActual=$sumaTotalConceptosMes - $totalPagadoMesActual;

                                                                     if($codEnt!='48'){
                                                                           $totalAcumulado = $deudaMesActual + $montoDeudaPendiente;
                                                                       } else {
                                                                           //$totalAcumulado = $totalMesActual;
                                                                           $totalAcumulado = $deudaMesActual + $montoDeudaPendiente;
                                                                      }
                                                                    echo '<td style="width: 20%; text-align: left; vertical-align: top; margin-bottom:0px; margin-top:0px;padding-bottom:0px; padding-top:0px;">
                                                                        <table cellspacing="0" style="width: 100%;text-align: right; vertical-align: top;">
                                                                            <tbody>
                                                                                <tr>
                                                                                    <td style="width: 100%; text-align:right;">
                                                                                        <h5 style="margin-top:3px; margin-bottom: 3px;"><b>' . utf8_decode('S/ ' . number_format($totalAcumulado, 2)).'</b></h5>
                                                                                    </td>
										</tr>
                                                                            </tbody>
									</table>
                                                                    </td>
								</tr>
                                                            </tbody>
							</table>
                                                    </td>
						</tr>
                                    
                                                <!-- Nueva Fila -->
						<tr style="'.$Espacio.'">
                                                    <td style="width: 100%; text-align: left; vertical-align: top; ">
                                                        <table style="'.$border.'width: 100%; height:140px; text-align: left; vertical-align: top;">
                                                            <tbody>
                                                                <tr>
                                                                    <td style="width: 100%; text-align: left; vertical-align: top;">
                                                                        <table cellspacing="0" style="width: 100%; text-align: center; vertical-align: top;">
                                                                            <tbody>
                                                                                <tr>
                                                                                    <td style="width: 100%; background:'.$bg.';" class="view">
                                                                                        <h4 style="color:#fff; font-size:10px; margin-top: 4px; margin-bottom: 4px;"><b>MENSAJE</b></h4>
                                                                                    </td>
										</tr>
										<tr>
                                                                                    <td style="width:100%;text-align: left;">
                                                                                      <p style="line-height: 11px;font-family: inherit;font-size:8.3px; margin-top: 1px; margin-bottom: 1px; text-align: left;margin-left: 3px;">';
                                                                           		echo utf8_encode($entidadMensaje);
                                                                                        echo '</p>
                                                                                    </td>
                                                                                </tr>
                                                                            </tbody>
									</table>
                                                                    </td>
								</tr>
                                                            </tbody>
							</table>
                                                    </td>
						</tr>';
                                                                                        
                        $contenidoRecibo = ob_get_clean();
                        echo  $contenidoRecibo; 
						echo '<tr>
                        <td colspan="3" style="width: 100%;">
                            <h4 style="margin-top:4px; margin-bottom: 0px; font-size:12px; text-align: center;height:12px;">';
                                
                                if($admin!=0){echo '<b>ORIGINAL</b>';}

                            echo '</h4>
                        </td>
                     </tr>
                                                   
                        </table>
                    </td>
                    <td style="width: 2%; text-align: right;"></td>
                  <td style="width: 49%; text-align: left;">
                   	<table cellspacing="0" style="width: 100%; text-align: center; font-size: 10pt;">';
                        
		                echo  $contenidoRecibo; 

		                    echo '<tr>
		                        <td colspan="3" style="width: 100%;">
		                            <h4 style="margin-top:4px; margin-bottom: 0px; font-size:12px; text-align: center;height:12px;">';
		                               
		                            	if($admin!=0){echo '<b>COPIA</b>';}

		                            echo '</h4>
		                        </td>
		                     </tr>
		                </table>

                    </td>
                </tr>
            </tbody>
        </table>';


    // echo '</page>';
            $cont++;
        }

    } else {
        header('Location:index.php');
    }

    if($validar != 'RETURN') return $errorPdf;

}



















	public function getReciboEstandar2($codEnt,$mesx,$aniox,$tipo,$ids,$filterx,$admin,$validar,$baseUrl)
	{
	    //echo $codEnt.'::::'.$mesx.'::::'.$aniox.'::::'.$tipo.'::::'.$ids.'::::'.$filterx.'::::'.$admin.'::::'.$validar.'::::'.$baseUrl;
	 	$adapter=$this->getAdapter();
	    
	    //consultamos el tipo de empresa
	    $queryEdificio = "SELECT emp_in_cod FROM edificio WHERE edi_in_cod = '$codEnt' ";
		$rowEmpresa = $adapter->query($queryEdificio,$adapter::QUERY_MODE_EXECUTE)->current();
		//print_r($rowEmpresa);

	    if($admin=='4' || $admin=='1' || $admin=='2'){
	        if($rowEmpresa['emp_in_cod']==8){
	            $admin='1';
	        }else{
	            $admin='2';
	        }
	    }else if($admin=='0'){
	        $admin='0';
	    }else{
	        $admin='2';
	    }

	    $cont = 1;
	    $fechaEmision = '';
	    $fechaEmisionSql = '';
	    $fechaVencimiento = '';
	    $fechaVencimientoSql = '';
	    $queryUnidadEC = '';
	    $unidadCodigoAcumulado = '';
	    $propietarioNombre = '';
	    $propietarioDniRuc = '';
	    $propietarioDireccion = '';
	    $periodo = '';
	    $errorPdf = array();
	    
	    if($admin=='2'){
	        $bg = '#9A437A';
	        $color2 = 'color:#fff;';
	        $border = 'border: solid 0.2px black;';
	        $border2 = 'border: solid 1px black;';
	        $borderFpago = 'border: solid 1px black;';
	    }else if($admin=='1'){
	        $bg = '#2273B6';
	        $color2 = 'color:#fff;';
	        $border = 'border: solid 0.2px black;';
	        $border2 = 'border: solid 1px black;';
	        $borderFpago = 'border: solid 1px black;';

	    }else if($admin=='0'){
	        $bg = '#fff';
	        $color2 = 'color:#fff;';
	        $border = 'border: solid 0.2px white;';
	        $border2 = 'border: solid 1px white;';
	        $borderFpago = 'border: solid 1px black;';
	    }else{
	        $bg = '#fff';
	        $color2 = 'color:#fff;';
	        $border = 'border: solid 0.2px white;';
	        $border2 = 'border: solid 1px white;';
	        $borderFpago ='border: solid 1px white;';
	    }
	    
	    /*[OBTENER MES]*/
	    /* Sacando los datos de fecha segun el formato del GMT */
	    if (isset($mesx)) {
	        $mes = $mesx;
	        $mesNumero = $mesx;
	        $mesNumeroAnterior = $mesx - 1;
	    }else {
	        $mes = date('m');
	        $mesNumero = date('m');
	        $mesNumeroAnterior = $mes - 1;
	    }

	    if (isset($aniox)) {
	        $anioAnterior = $aniox;
	        $anioMant = $aniox;
	        $anio = $aniox;
	        $anio_consula = $aniox;
	    } else {
	        $anioAnterior = date('Y');
	        $anioMant = date('Y');
	        $anio = date('Y');
	        $anio_consula = date('Y');
	    }

	    $mesNombre = $this->getMonthNameString($mes);

	    if (strlen($mesNumero) == 1) {
	        $mesNumero = "0" . $mesNumero;
	    }

	    if ($mesNumeroAnterior == 0) {
	        $mesNumeroAnterior = "12";
	        $anioAnterior--;
	    } else if (strlen($mesNumeroAnterior) == 1) {
	        $mesNumeroAnterior = "0" . $mesNumeroAnterior;
	    }
	    $fechaActual = date("d/m/Y");
	    
	     if (isset($codEnt)) {

	        /* Recuperando el nombre del propietario de la entidad y su RUC */
	        $queryEdificioDistrito = "SELECT e.*, d.dis_vc_nom FROM edificio e, distrito d WHERE d.dis_in_cod = e.dis_in_cod AND e.edi_in_cod = '$codEnt' ";
			$rowEntidad = $adapter->query($queryEdificioDistrito,$adapter::QUERY_MODE_EXECUTE)->current();

			$entidadNombre = utf8_decode($rowEntidad['edi_vc_nompro']);        

	        if($codEnt!=60){
	            if(strlen($entidadNombre)>30){
	             $entidadNombre = substr(strip_tags($entidadNombre), 0, 30);
	             $entidadNombre.='...';
	            }
	        }
	        
	        $entidadRuc = 'RUC. ' . $rowEntidad['edi_ch_rucpro'];
	        $entidadTipoPresupuesto = $rowEntidad['edi_vc_tpre'];
	        $entidadDireccion = utf8_decode($rowEntidad['edi_vc_dir']);
	        $entidadUrbanizacion = 'URB. ' . utf8_decode($rowEntidad['edi_vc_urb']);
	        $entidadMensaje = substr(utf8_decode($rowEntidad['edi_te_men']), 0, 500);
	        $entidadDistrito = utf8_decode($rowEntidad['dis_vc_nom']);
	        	
	        //Calcula la fecha de proceso o emision
	        if (strlen($rowEntidad['edi_in_diapro']) < 2) {
	            $fechaEmision = "0" . $rowEntidad['edi_in_diapro'] . "/" . $mesNumero . "/" . $anio;
	            $fechaEmisionSql = $anio . '-' . $mesNumero . '-0' . $rowEntidad['edi_in_diapro'];
	        } else {
	            $fechaEmision = $rowEntidad['edi_in_diapro'] . "/" . $mesNumero . "/" . $anio;
	            $fechaEmisionSql = $anio . '-' . $mesNumero . '-' . $rowEntidad['edi_in_diapro'];
	        }

	        $mesNumeroEmi=$mesNumero;

	        //Calcula la fecha de vencimiento
	        $strmen = '0';
	        $strdia = '0';
	        (strlen($rowEntidad['edi_in_diapag']) < 2) ? $strdia = '0' : $strdia = '';
	        if ($rowEntidad['edi_in_diapro'] < $rowEntidad['edi_in_diapag']) {
	            (strlen($mesNumero) < 2) ? $strmen = '0' : $strmen = '';
	            $fechaVencimiento = $strdia . $rowEntidad['edi_in_diapag'] . "/" . $strmen . $mesNumero . "/" . $anio;
	            $fechaVencimientoSql = $anio . '-' . $strmen . $mesNumero . '-' . $strdia . $rowEntidad['edi_in_diapag'];
	        } else {
	            if ($mesNumero < 12) {
	                $mesNumero++;
	            } else {
	                $mesNumero = 1;
	            }
	            (strlen($mesNumero) < 2) ? $strmen = '0' : $strmen = '';
	            $fechaVencimiento = $strdia . $rowEntidad['edi_in_diapag'] . "/" . $strmen . $mesNumero . "/" . $anio;
	            $fechaVencimientoSql = $anio . '-' . $strmen . $mesNumero . '-' . $strdia . $rowEntidad['edi_in_diapag'];
	        }
	        
	       $periodo = $this->getFormatMes($strmen.$mesNumeroEmi);

	        if ($rowEntidad['edi_in_numser'] != 0 && $rowEntidad['edi_in_numser'] != null) {
	            $entidadNumeroSerie = $rowEntidad['edi_in_numser'];
	        } else {
	            $entidadNumeroSerie = 1;
	        }
	        $entidadNumeroDocumento = $rowEntidad['edi_in_numdoc'];

	        /* Listando las unidades inmobiliarias padres las cuales van a ser encabezado de las unidades secundarias que tenga esa unidad */
	        $filArr = explode('CORTE', stripslashes($filterx));
	        $filter = '';

	        for ($i = 0; $i < (count($filArr)); $i++){
	            $filter .= $filArr[$i];
	        }

	        if (isset($ids)) {

	            $codsUni = stripslashes($ids);
	            $codPar = explode(",", $codsUni);
	            $codNuevo = "";
	            for ($xyz = 0; $xyz < count($codPar); $xyz++) {
	                if ($xyz == 0) {
	                    $codNuevo .= "'" . $codPar[$xyz] . "'";
	                } else {
	                    $codNuevo .= ",'" . $codPar[$xyz] . "'";
	                }
	            }
	          	//echo $codNuevo;
	            if($codEnt != '46'){ 

	            $selectUnidad = "SELECT ui.* FROM unidad ui WHERE ui.uni_in_pad IS NULL AND ui.edi_in_cod='$codEnt' AND ui.uni_in_est='1' AND (ui.uni_do_cm2 != '0.00' OR (SELECT SUM(uni_do_cm2) FROM unidad uii WHERE uni_in_pad IN (uii.uni_in_cod)) > 0) AND ui.uni_in_cod IN ($codNuevo) $filter";
				$queryUnidadEC = $adapter->query($selectUnidad,$adapter::QUERY_MODE_EXECUTE)->toArray();

	            } else {

	            $selectUnidad = "SELECT ui.* FROM unidad ui WHERE ui.uni_in_pad IS NULL AND ui.edi_in_cod='$codEnt' AND ui.uni_in_est='1' AND ui.uni_in_cod IN ($codNuevo) $filter";
				$queryUnidadEC = $adapter->query($selectUnidad,$adapter::QUERY_MODE_EXECUTE)->toArray();

	            }

	            
	        } else {
	              
	                if($codEnt != '46'){ 

	                	$selectUnidad = "SELECT ui.* FROM unidad ui WHERE ui.uni_in_pad IS NULL AND ui.edi_in_cod='$codEnt' AND ui.uni_in_est='1' AND (ui.uni_do_cm2 != '0.00' OR (SELECT SUM(uni_do_cm2) FROM unidad uii WHERE uni_in_pad IN (uii.uni_in_cod)) > 0) $filter";
						$queryUnidadEC = $adapter->query($selectUnidad,$adapter::QUERY_MODE_EXECUTE)->toArray();

	                } else {

	                	$selectUnidad = "SELECT ui.* FROM unidad ui WHERE ui.uni_in_pad IS NULL AND ui.edi_in_cod='$codEnt' AND ui.uni_in_est='1' $filter";
						$queryUnidadEC = $adapter->query($selectUnidad,$adapter::QUERY_MODE_EXECUTE)->toArray();

	                }


	        }

	        $cantidadCopias = 0;
	        //print_r($queryUnidadEC);
	        /* Listando por unidad inmobiliaria padre para que aparezca un solo estado de cuenta por pagina. */
        foreach($queryUnidadEC as $rowUnidadEC){

        		$selectIngresoUsuarioUnidad = "SELECT i.ing_in_cod, i.uni_in_cod, i.ing_in_est,CASE WHEN u.usu_ch_tip = 'PN' THEN CONCAT(u.usu_vc_ape,' ',u.usu_vc_nom) WHEN u.usu_ch_tip = 'PJ' THEN u.usu_vc_ape END AS poseedor, u.usu_ch_dni, u.usu_ch_ruc, u.usu_vc_dir, ui.uni_vc_nom, u.usu_ch_tip, i.ing_in_nroser, i.ing_in_nrodoc, i.ing_da_fecemi, i.ing_da_fecven FROM ingreso i, usuario u, unidad ui WHERE ui.uni_in_cod = i.uni_in_cod AND u.usu_in_cod = i.ing_in_resi AND ui.edi_in_cod = '$codEnt' AND MONTH(i.ing_da_fecemi) = '$mes' AND YEAR(i.ing_da_fecemi) = '$anio_consula' AND ui.uni_in_cod = '" . $rowUnidadEC['uni_in_cod'] . "' ";
				$rowIngresoRM = $adapter->query($selectIngresoUsuarioUnidad,$adapter::QUERY_MODE_EXECUTE)->count();
				if(isset($validar)){
					if($rowIngresoRM != 1){
						if($validar !='RETURN') $errorPdf[] = $rowUnidadEC['uni_in_cod'];
						continue;
					}
				}
					

                $total = 0; $totalCm2 = 0; $totalM2 = 0;
                $unidadCodigoDepartamento = strtoupper(utf8_decode($rowUnidadEC['uni_vc_nom']));
                $unidadPorcentajeCuotaUnidad = $rowUnidadEC['uni_do_cm2'];
                $unidadAreaOcupada = $rowUnidadEC['uni_do_aocu'];
                $unidadTipo = strtoupper(utf8_decode($rowUnidadEC['uni_vc_tip']));
               
                /* Se está concatenando los códigos de las unidades inmobiliarias. */
                $unidadCodigoAcumulado .= "'" . $rowUnidadEC['uni_in_cod'] . "'";

                $selectUsuario = "SELECT usu_ch_tip, usu_vc_nom, usu_vc_ape, usu_ch_dni, usu_ch_ruc, usu_vc_dir FROM usuario WHERE usu_in_cod = '" . $rowUnidadEC['uni_in_pro'] . "' ";
				$rowPropietario = $adapter->query($selectUsuario,$adapter::QUERY_MODE_EXECUTE)->current();

                if ($rowPropietario['usu_ch_tip'] == 'PN') {
                    $propietarioNombre = strtoupper(utf8_decode($rowPropietario['usu_vc_ape'] . " " . $rowPropietario['usu_vc_nom']));
                    $propietarioDniRuc = strtoupper(utf8_decode($rowPropietario['usu_ch_dni']));
                } else {
                    $propietarioNombre = strtoupper(utf8_decode($rowPropietario['usu_vc_ape']));
                    $propietarioDniRuc = strtoupper(utf8_decode($rowPropietario['usu_ch_ruc']));
                }
                $propietarioDireccion = strtoupper(utf8_decode($rowPropietario['usu_vc_dir']));


                $selectUsuario = "SELECT usu_ch_tip, usu_vc_nom, usu_vc_ape, usu_ch_dni, usu_ch_ruc, usu_vc_dir FROM usuario WHERE usu_in_cod = '" . $rowUnidadEC['uni_in_pos'] . "' ";
				$rowPoseedor = $adapter->query($selectUsuario,$adapter::QUERY_MODE_EXECUTE)->current();

                if ($rowPoseedor['usu_ch_tip'] == 'PN') {
                    $poseedorNombre = ucwords(strtoupper(utf8_decode(substr($rowPoseedor['usu_vc_ape'] . " " . $rowPoseedor['usu_vc_nom'],0,30))));
                    $poseedorDniRuc = ucwords(strtoupper(utf8_decode($rowPoseedor['usu_ch_dni'])));
                } else {
                    $poseedorNombre = ucwords(strtoupper(utf8_decode(substr($rowPoseedor['usu_vc_ape'],0,30))));
                    $poseedorDniRuc = ucwords(strtoupper(utf8_decode($rowPoseedor['usu_ch_ruc'])));
                }
                $poseedorDireccion = ucwords(strtoupper(utf8_decode(substr($rowPoseedor['usu_vc_dir'],0,35))));

			if($cont=='1'){
				echo '<page orientation="paysage" backcolor="#FEFEFE" backtop="0" backbottom="1mm" style="font-size: 12pt">';
			}else{
				echo '<page orientation="paysage" >';
			}
             /* ===== SECCION: RECIBO DE MANTENIMIENTO ===== */
            //echo "entidad:".$codEnt."mes:".$mes."anio:".$anio_consula."unidad:".$rowUnidadEC['uni_in_cod'];
            $selectIngresoUsuarioUnidad = "SELECT i.ing_in_cod, i.uni_in_cod, i.ing_in_est,CASE WHEN u.usu_ch_tip = 'PN' THEN CONCAT(u.usu_vc_ape,' ',u.usu_vc_nom) WHEN u.usu_ch_tip = 'PJ' THEN u.usu_vc_ape END AS poseedor, u.usu_ch_dni, u.usu_ch_ruc, u.usu_vc_dir, ui.uni_vc_nom, u.usu_ch_tip, i.ing_in_nroser, i.ing_in_nrodoc, i.ing_da_fecemi, i.ing_da_fecven FROM ingreso i, usuario u, unidad ui WHERE ui.uni_in_cod = i.uni_in_cod AND u.usu_in_cod = i.ing_in_resi AND ui.edi_in_cod = '$codEnt' AND MONTH(i.ing_da_fecemi) = '$mes' AND YEAR(i.ing_da_fecemi) = '$anio_consula' AND ui.uni_in_cod = '" . $rowUnidadEC['uni_in_cod'] . "' ";
			$rowIngresoRM = $adapter->query($selectIngresoUsuarioUnidad,$adapter::QUERY_MODE_EXECUTE)->current();

			$idIngresoView=$rowIngresoRM['ing_in_cod'];

                //$poseedorRM = strtoupper(utf8_decode($rowIngresoRM['poseedor']));
                $rucRM = utf8_decode($rowIngresoRM['usu_ch_ruc']);
                //$direccionRM = strtoupper(utf8_decode($rowIngresoRM['usu_vc_dir']));
                //$departamentoRM = strtoupper(utf8_decode($rowIngresoRM['uni_vc_nom']));

                if ($rowIngresoRM['usu_ch_tip'] == 'PN') {
                    $rucRM = utf8_decode($rowIngresoRM['usu_ch_dni']);
                }

                /* RECIBO DE MANTENIMIENTO: CALCULANDO NUMERO DE SERIE */
                $entidadNumeroSerie = $rowIngresoRM['ing_in_nroser'];
                $entidadNumeroDocumento = $rowIngresoRM['ing_in_nrodoc'];
                $entidadNumeroDocumento = substr('00000' . $entidadNumeroDocumento, strlen('00000' . $entidadNumeroDocumento) - 6, 6);
                $entidadNumeroSerie = substr('00000' . $entidadNumeroSerie, strlen('00000' . $entidadNumeroSerie) - 3, 6);

                $nroSerieRM = utf8_decode($entidadNumeroSerie . ' - ' . $entidadNumeroDocumento);


 	echo '
	<style type="text/css">
    <!--
    body { font-family : "Helvetica Neue", Helvetica, Arial, sans-serif; }
    table { vertical-align: top; }
    tr    { vertical-align: top; }
    td    { vertical-align: top; }
    -->
    </style>

   
    <bookmark title="Lettre" level="0" ></bookmark>
    <table cellspacing="0" style="width: 100%; text-align: left; font-size: 11pt;">
         <h1 style="display: none;"></h1>
         <tr>
            <td style="width: 49%; text-align: left;">
                <table cellspacing="0" style="width: 100%; text-align: cqueryConceptoenter; font-size: 10pt;">';
                    
                    ob_start();
                    
                    echo '<tr><td style="width:25%;"> ';

                        if($rowEntidad['edi_vc_logo']!=''){  
							echo '<img class="logo" src="'.$baseUrl.'/file/logo-edificio/'.$rowEntidad['edi_vc_logo'].'?t='.time().'"  style="width: 120px; margin-top: 25px;" />';
						}else{
							echo '&nbsp;';   
						}

                        echo '</td><td style="width:35%; line-height: 1;">';

                                if($codEnt!=60){
									echo '<h4 style="margin: 13px;text-align:center;"><b>'.$entidadNombre.'</b></h4>';
								}else{
									echo ' <h5 style="margin: 13px;text-align:center;"><b>'.$entidadNombre.' </b></h5>'; 
								}

                                echo '<h6 style="margin-top:-2px; margin-bottom: 0; text-align:center; font-size: 10px; font-weight: 300;">';

                                echo $entidadDireccion."<br>";
                                echo utf8_encode(htmlspecialchars_decode($entidadUrbanizacion))."<br>";
                                echo utf8_encode(htmlspecialchars_decode($entidadDistrito));
                                
                                echo '</h6>
                        </td>
                        <td style="width:40%;">
                            <table cellspacing="0" style="width: 100%; '.$border2.' text-align: center; font-size: 10pt;">
                                <tr>
                                    <td style="width:100%;">
                                        <!-- RUC -->
                                        <h5 style="margin-top:5px; margin-bottom: 5px;">'.$entidadRuc.'</h5>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="width:100%; background: '.$bg.';">
                                        <h4 style="'.$color2.' font-size:18px; margin-top: 10px; margin-bottom: 10px;">RECIBO DE MANTENIMIENTO</h4>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="width:100%;">
                                        <h5 style="margin-top:5px; margin-bottom: 5px;">Nº '.$nroSerieRM.'</h5>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2" style="width:60%;">
                            <table cellspacing="0" style="width:100%; '.$border2.'  text-align: center; font-size: 10pt;">
                                <tr style="background: '.$bg.';">
                                    <td colspan="2">
                                        <h4 style="'.$color2.' margin-top:2px; margin-bottom: 2px; font-size:14px;">
                                            RECIBO DE MANTENIMIENTO
                                        </h4>
                                    </td> 
                                </tr>
                                <tr>
                                    <td style="padding-left: 5px;  width:43%;">
                                        <h4 style="margin-top: 1px; margin-bottom: 1px; font-size:12px; text-align: left;">
                                            RAZÓN SOCIAL: 
                                        </h4>
                                    </td>
                                    <td style="width:57%;">
                                        <h6 style="margin-top: 1px; margin-bottom: 1px; text-align: left; font-size: 10px; font-weight: 300;">
                                            '.ucwords(strtolower(utf8_encode($poseedorNombre))).'
                                        </h6>
                                    </td>  
                                </tr>
                                <tr>
                                    <td style="padding-left: 5px; width:43%;">
                                        <h4 style="margin-top: 1px; margin-bottom: 1px; font-size:12px; text-align: left;">
                                            DNI / RUC: 
                                        </h4>
                                    </td>
                                    <td style="width:57%;">
                                        <h6 style="margin-top: 1px; margin-bottom: 1px; text-align: left; font-size: 10px; font-weight: 300;">
                                            '.ucwords(strtolower($poseedorDniRuc)).'
                                        </h6>
                                    </td>   
                                </tr>
                                <tr>
                                    <td style="width:43%; padding-left: 5px; ">
                                        <h4 style="margin-top: 1px; margin-bottom: 1px; font-size:12px; text-align: left;">
                                            DOMICILIO FISCAL: 
                                        </h4>
                                    </td>
                                    <td style="width:57%;">
                                        <h6 style="margin-top: 1px; margin-bottom: 1px; text-align: left; font-size: 10px; font-weight: 300;">
                                            '.ucwords(strtolower($poseedorDireccion)).'
                                        </h6>
                                    </td>  
                                </tr>
                            </table>
                        </td>
                        <td style="width: 40%;">
                            <table cellspacing="0" style="width:100%; '.$border2.' text-align: center; font-size: 10pt;">
                                <tr>
                                    <td style="padding-left: 5px; width:70%;  background: '.$bg.'; '.$border.'">
                                        <h4 style="'.$color2.' margin-top:1px; margin-bottom: 1.5px; font-size:12px; text-align: left;">
                                            PERIODO: 
                                        </h4>
                                    </td>
                                    <td style="width:30%; '.$border.'">
                                        <h6 style="margin-top:2px; margin-bottom: 0; text-align: center; font-size: 10px; font-weight: 300;">
                                            '.ucwords(strtolower($periodo)).'
                                        </h6>
                                    </td>  
                                </tr>
                                <tr>
                                    <td style="padding-left: 5px; width:70%;  background: '.$bg.'; '.$border.'">
                                        <h4 style="'.$color2.' margin-top:1px; margin-bottom: 1.5px; font-size:12px; text-align: left;">
                                            CÓDIGO DEPÓSITO:
                                        </h4>
                                    </td>
                                    <td style="width:30%; '.$border.'">
                                        <h6 style="margin-top:2px; margin-bottom: 0; text-align: center; font-size: 10px; font-weight: 300;">
                                            '.$rowUnidadEC['uni_vc_nom'].'
                                        </h6>
                                    </td>  
                                </tr>
                                <tr>
                                    <td style="padding-left: 5px; width:70%;  background: '.$bg.'; '.$border.'">
                                        <h4 style="'.$color2.' margin-top:1px; margin-bottom: 1.5px; margin-bottom: 2px; font-size:12px; text-align: left;">
                                            FECHA EMISIÓN: 
                                        </h4>
                                    </td>
                                    <td style="width:30%; '.$border.'">
                                        <h6 style="margin-top:2px; margin-bottom: 0; text-align: center; font-size: 10px; font-weight: 300;">
                                            '.$fechaEmision.'
                                        </h6>
                                    </td>   
                                </tr>
                                <tr>
                                    <td style="padding-left: 5px; width:70%;  background: '.$bg.'; '.$border.'">
                                        <h4 style="'.$color2.' margin-top:1px; margin-bottom: 1.5px; font-size:12px; text-align: left;">
                                            FECHA VENCIMIENTO: 
                                        </h4>
                                    </td>
                                    <td style="width:30%; '.$border.'">
                                        <h6 style="margin-top: 2px; margin-bottom: 0; text-align: center; font-size: 10px; font-weight: 300;">
                                            '.$fechaVencimiento.'
                                        </h6>
                                    </td>  
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="3" style="width: 100%;">
                            <table cellspacing="0" style="width:100%; '.$border2.' text-align: center; font-size: 10pt;">
                                 <tr style="'.$border2.'">
                                    <td colspan="2" style="width:100%; background: '.$bg.'; '.$border.'">
                                        <h4 style="'.$color2.' margin-top:2px; margin-bottom: 2px; font-size:14px; text-align: center;">
                                            DETALLE 
                                        </h4>
                                    </td>
                                 </tr>
 									';



 								$maxNumberRowsView=7;
                                $totalRowsView=0;
                                $textNombreConcetos="";
                                $textPrecioConceptos="";


                                if($this->existeCuotaDeMantenimiento($idIngresoView)==true){
                                    $rowCuotaMantenimiento=$this->getRowConceptoCuotaMto($idIngresoView);
                                    $fechaEmisionConcepto=$rowCuotaMantenimiento['coi_da_fecemi'];
                                    $arrayFechaEmiConcepto=explode("-", $fechaEmisionConcepto);
                                    $mesEmitidoConcepto=$arrayFechaEmiConcepto[1];
                                    $nombreMesCuota=$this->getFormatMes($mesEmitidoConcepto);
                                    $textNombreConcetos.=strtoupper(utf8_decode($rowCuotaMantenimiento['con_vc_des'] . ' - ' . $nombreMesCuota . ' ' . $anioMant)).'<br>';
                                    $textPrecioConceptos.=number_format($rowCuotaMantenimiento['coi_do_subtot'],2, ".", "")."<br>";
                                    $totalRowsView++;
                                }

                                $marginButtonSiHayConceptoAgua="0px;";
                                if($this->existeConsumoDeAgua($idIngresoView)==true){
                                    $totalRowsView+=3;
                                }else{
                                    $marginButtonSiHayConceptoAgua='2px;';   
                                }
                                
                                $numberRowsDisponibles=$maxNumberRowsView-$totalRowsView;

                                $countAllConceptosDeIngreso=$this->getCountConceptos($idIngresoView);
                                $contadorConceptos=0;
                                $existeOtros=0;
                                $sumaTotalOtros=0;

                                $getRowsConceptosAdicionales=$this->getRowsConceptosAdicionales($idIngresoView);
                                
                                foreach ($getRowsConceptosAdicionales as $key => $value){
                                    $comentario=strtolower($value['coi_te_com']);
                                    if(strlen($comentario)>0){
                                        $comentario=" - ".$comentario;
                                    }else{
                                        $comentario="";
                                    }

                                    if(count($getRowsConceptosAdicionales)==$numberRowsDisponibles){
                                        $textNombreConcetos.=strtoupper(utf8_decode($value['con_vc_des'])).'--'.$comentario.'<br>';
                                        $textPrecioConceptos.=number_format($value['coi_do_subtot'],2, ".", "")."<br>";
                                    }

                                    if(count($getRowsConceptosAdicionales)>$numberRowsDisponibles){
                                        if($contadorConceptos<$numberRowsDisponibles){
                                                $textNombreConcetos.=strtoupper(utf8_decode($value['con_vc_des'])).$comentario. '<br>';
                                                $textPrecioConceptos.=number_format($value['coi_do_subtot'],2, ".", "")."<br>";
                                               
                                        }else{
                                            if($existeOtros==1){
                                                $sumaTotalOtros+=number_format($value['coi_do_subtot'],2, ".", "");
                                            }else{
                                                    $sumaTotalOtros+=number_format($value['coi_do_subtot'],2, ".", "");
                                                    $existeOtros=1;
                                            }
                                        }  
                                    }else{
                                        $textNombreConcetos.=strtoupper(utf8_decode($value['con_vc_des'])).$comentario.'<br>';
                                        $textPrecioConceptos.=number_format($value['coi_do_subtot'],2, ".", "")."<br>";                                                 
                                    }
                                    $contadorConceptos++; 
                                }

                               
                                if($existeOtros==1){
                                     $textNombreConcetos.='OTROS CONCEPTOS<br>';
                                     $textPrecioConceptos.=number_format($sumaTotalOtros,2, ".", "")."<br>";
                                }else{
                                     for($i=0;$i<=($numberRowsDisponibles-$contadorConceptos);$i++){
                                      $textNombreConcetos.= "<br>";
                                     }
                                }



                                echo '
										<tr style="'.$border2.'">
		                                    <td style="width:86%; font-size: 9px; text-align: left; padding-left: 5px; padding-top:4px;padding-bottom:'.$marginButtonSiHayConceptoAgua.'">
		                                            '.$textNombreConcetos.'
		                                    </td>
		                                    <td style="width:14%; font-size: 9px; text-align: center; padding-right:10px; font-weight: bold; padding-top:3px">
		                                           ';

		                                            echo $textPrecioConceptos;
		                                            
		                                            if($existeOtros==1){
		                                                 $textNombreConcetos.=$sumaTotalOtros;
		                                            }else{
		                                                for($i=1;$i<=($numberRowsDisponibles-$contadorConceptos);$i++){
		                                                  echo "<br>";
		                                                }
		                                            }

		                                    echo '</td>
		                                 </tr>';


								if($this->existeConsumoDeAgua($idIngresoView)==true){
                                    $rowConceptoConsumoAgua=$this->getRowConceptoConsumoAgua($idIngresoView);

                                    $arrFecEmi = explode('-', $rowConceptoConsumoAgua['coi_da_fecemi']);
                                    $arrFecVen = explode('-', $rowConceptoConsumoAgua['coi_da_fecven']);
                                    $mesAgua = "";
                                    $mesMant = "";

                                    if (($arrFecVen[1] > $arrFecEmi[1])) {
                                        $mesAgua = $arrFecEmi[1];
                                        $mesMant = $arrFecVen[1];
                                    } else {
                                        $mesAgua = $mesNumeroAnterior;
                                        $mesMant = $arrFecVen[1];
                                    }

                                    $mesAnteriorCI = $mesNumeroAnterior - 1;
                                    $anioAnteriorCI = $anioAnterior;
                                    if ($mesAnteriorCI == 0) {
                                        $mesAnteriorCI = 12;
                                        $anioAnteriorCI--;
                                    } else {
                                        if (strlen($mesAnteriorCI) == 1) {
                                            $mesAnteriorCI = "0". $mesAnteriorCI;
                                        }
                                    }

									$queryLecturaAnterior = "SELECT cns_do_lec, DATE_FORMAT(cns_da_fec, '%d/%m/%Y') AS fecLecAnt, cns_vc_tip, cns_vc_tipver FROM consumo WHERE MONTH(cns_da_fec) = '$mesAnteriorCI' AND YEAR(cns_da_fec) = '$anioAnteriorCI' AND cns_vc_ser = 'AGUA' AND uni_in_cod = '" . $rowUnidadEC['uni_in_cod'] . "' ";
									$rowLecturaAnterior = $adapter->query($queryLecturaAnterior,$adapter::QUERY_MODE_EXECUTE)->current();

									$queryLecturaActual = "SELECT cns_do_lec, DATE_FORMAT(cns_da_fec, '%d/%m/%Y') AS fecLecAct, cns_vc_tip, cns_vc_tipver, cns_do_conl, cns_do_conm3, cns_do_congal FROM consumo WHERE MONTH(cns_da_fec) = '$mesNumeroAnterior' AND YEAR(cns_da_fec) = '$anioAnterior' AND cns_vc_ser = 'AGUA' AND uni_in_cod = '" . $rowUnidadEC['uni_in_cod'] . "' ";
									$rowLecturaActual = $adapter->query($queryLecturaActual,$adapter::QUERY_MODE_EXECUTE)->current();

                                    $consumoIndividualEC = 0;
                                    if ($rowLecturaActual['cns_vc_tipver'] == 'LITRO') {
                                        $consumoIndividualEC = $rowLecturaActual['cns_do_conl'];
                                    } elseif ($rowLecturaActual['cns_vc_tipver'] == 'M3') {
                                        $consumoIndividualEC = $rowLecturaActual['cns_do_conm3'];
                                    } else {
                                        $consumoIndividualEC = $rowLecturaActual['cns_do_congal'];
                                    }

                                    if($rowLecturaAnterior['fecLecAnt']==''){
                                        $lecAnt = $this->getEspacio(10);
                                    }else{
                                        $lecAnt = $rowLecturaAnterior['fecLecAnt'];
                                    }

                                    if($rowLecturaActual['fecLecAct']==''){
                                        $lecAct = $this->getEspacio(18);
                                    }else{
                                        $lecAct = $rowLecturaActual['fecLecAct'];
                                    }

                                     //RECIBO DE MANTENIMIENTO: DESCRIPCION DE LOS CONCEPTOS DE AGUA
                                    $mesRM = $mesNombre;
                                    $mesRM = $this->getMonthNameInt($mesAgua);

                                    $marginButton='0px';
                                    $marginTop='0px';

                                    $contentConsumoAgua= '<tr style="'.$border2.'">
                                    <td style="width:86%; font-size: 8.5px; text-align: left; padding-left: 5px;">
                                      <h6 style="margin-top: '.$marginTop.'; margin-bottom:'.$marginButton.'; font-size:10px;">
                                          '.strtoupper(utf8_decode($rowConceptoConsumoAgua['con_vc_des'] . ' - ' . $mesRM . ' ' . $anioAnterior)).'
                                      </h6>
                                      &nbsp;&nbsp; FECHA LECTURA ANTERIOR'.$this->getEspacio(2).':'.$this->getEspacio(6).$lecAnt.$this->getEspacio(6).'LECTURA ANTERIOR'.$this->getEspacio(3).':'.$this->getEspacio(5).number_format($rowLecturaAnterior['cns_do_lec'], 2, '.', ',').'<br>
                                      &nbsp;&nbsp; FECHA LECTURA ACTUAL'.$this->getEspacio(6).':'.$this->getEspacio(6).$lecAct.$this->getEspacio(6).'LECTURA ACTUAL'.$this->getEspacio(8).':'.$this->getEspacio(7).number_format($rowLecturaActual['cns_do_lec'], 2, '.', ',').'
                                  </td>
                                  <td style="width:14%; font-size: 14px; text-align: center; padding-right: 10px; font-weight: bold;">
                                    <h6 style="margin-top:'.$marginTop.'; margin-bottom:'.$marginButton.';">
                                        '.utf8_decode('S/ ' . number_format($rowConceptoConsumoAgua['coi_do_subtot'], 2, ".", "")).'<br>'.$this->getEspacio(1).'
                                    </h6>
                                    <b>'.$this->getEspacio(1).'</b>
                                </td>
                            </tr>';
                            echo $contentConsumoAgua;

                        }




                        echo '

							<tr style="'.$border2.'">
                                    <td colspan="2" style="width:100%;">
                                       
                                    </td>
                                 </tr>

                                 <tr style="'.$border2.'">
                                    <td style="width:86%; font-size: 10px; text-align: left; padding-left: 5px; font-weight: bold;">';
                                             
                                    	 
								$sumaTotalConceptosMes=$this->getSumConceptos($idIngresoView);
								echo utf8_decode('SON:' . $this->covertirNumLetras(number_format($sumaTotalConceptosMes, 2, ".", "")) . '   NUEVOS SOLES').'<br><br>';  

								/* RECIBO DE MANTENIMIENTO: FECHA CANCELADO */
								
								if ($rowIngresoRM['ing_in_est'] == 0) {

									$selectIngresoParcial = "SELECT ipa_da_fecpag FROM ingreso_parcial WHERE ing_in_cod = '" . $rowIngresoRM['ing_in_cod'] . "' ORDER BY ipa_da_fecpag DESC LIMIT 0, 1 ";
									$rowCancelado = $adapter->query($selectIngresoParcial,$adapter::QUERY_MODE_EXECUTE)->current();

									if ($rowCancelado['ipa_da_fecpag'] != '' && $rowCancelado['ipa_da_fecpag'] != null) {
										$fechaPagoRM = explode('-', $rowCancelado['ipa_da_fecpag']);
									} else {
										$fechaPagoRM = '   ';
										}
									} else {
										$fechaPagoRM = '   ';
									}
                                             

                                    echo '<table cellspacing="0" style="margin-top: -8px;">
                                         <tr>
                                             <td style="width: 80px;font-weight:bolder;">CANCELADO:</td>
                                             <td style="width: 30px;  text-align: center; '.$borderFpago.'">'.$fechaPagoRM[2].'</td>
                                             <td style="width: 30px;  text-align: center; '.$borderFpago.'">'.$fechaPagoRM[1].'</td>
                                             <td style="width: 30px;  text-align: center; '.$borderFpago.'">'.$fechaPagoRM[0].'</td>
                                             <td style="width: 240px; text-align: right; font-weight: bold;">TOTAL</td>
                                         </tr>
                                     </table>
                                    </td>
                                    <td style="width:14%; font-size: 9px; text-align: center;">
                                        <h6 style="margin-top: 16px; margin-bottom:10px; font-size: 11px;">';
                                            
                                        	echo utf8_decode('S/ ' . number_format($sumaTotalConceptosMes, 2, ".", "")); 

                                        echo '</h6>
                                    </td>
                                 </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="3" style="width: 100%;">
                            <br/>
                            <table cellspacing="0" style="width:100%; '.$border2.' text-align: center; font-size: 10pt;">
                                 <tr style="'.$border2.'">
                                     <td colspan="3" style="width:100%; background: '.$bg.'; '.$border.'">
                                        <h4 style="'.$color2.' margin-top:2px; margin-bottom: 2px; font-size:14px; text-align: center;">
                                            ESTADO DE CUENTA 
                                        </h4>
                                    </td>
                                 </tr>
                                 <tr>
                                    <td style="padding-left: 5px;  width:60%; background: '.$bg.'; '.$border.'">
                                        <h4 style="color: #fff; margin-top: 1px; margin-bottom: 1px; font-size:12px; text-align: center;">
                                            ARRENDATARIO
                                        </h4>
                                    </td>
                                    <td rowspan="2" style="width:20%; background: '.$bg.'; '.$border.'">
                                        <h4 style="color: #fff;  margin-top: 10px; margin-bottom: 1px; font-size:12px; text-align: center;">
                                            PERIODO 
                                        </h4>
                                    </td>
                                    <td rowspan="2" style="width:20%; '.$border.'">
                                        <h6 style="font-size:10px; margin-top: 10px; margin-bottom: 1px;">
                                            '.$periodo.'
                                        </h6>
                                    </td> 
                                </tr>
                                <tr>
                                    <td style="padding-left: 5px;  width:60%; text-align: left;">
                                        <h4 style="margin-top: 5px; margin-bottom: 1px; font-size:12px; text-align: left;">
                                            RAZÓN SOCIAL:'.$this->getEspacio(11).'<span style="font-size: 10px; font-weight: 300;">'.ucwords(strtolower(utf8_encode($poseedorNombre))).'</span>
                                        </h4>
                                    </td>
                                   
                                </tr>
                                <tr>
                                    <td style="padding-left: 5px;  width:60%; text-align: left;">
                                        <h4 style="margin-top: 1px; margin-bottom: 1px; font-size:12px; text-align: left;">
                                            DNI / RUC:'.$this->getEspacio(21).'<span style="font-size: 10px; font-weight: 300;">'.ucwords(strtolower($poseedorDniRuc)).'</span>
                                        </h4>
                                    </td>
                                    <td rowspan="2" style="width:20%; background: '.$bg.'; '.$border.'">
                                        <h4 style="color: #fff;  margin-top: 2px; margin-bottom: 1px; font-size:12px; text-align: center;">
                                            CODIGO DEPÓSITO
                                        </h4>
                                    </td>
                                    <td rowspan="2" style="width:20%; '.$border.'">
                                        <h6 style="font-size:10px; margin-top: 10px; margin-bottom: 8px;">';
                                            	echo $rowUnidadEC['uni_vc_nom'];
                                        echo '</h6>
                                    </td> 
                                </tr>
                                <tr>
                                    <td style="padding-left: 5px;  width:60%; text-align: left;">
                                        <h4 style="margin-top: 1px; margin-bottom: 8px; font-size:12px; text-align: left;">
                                            DOMICILIO FISCAL:'.$this->getEspacio(5).'<span style="font-size: 10px; font-weight: 300;">'.ucwords(strtolower($poseedorDireccion)).'</span>
                                        </h4>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="3" style="width: 100%;">
                            <table cellspacing="0" style="width:100%; '.$border2.' text-align: center; font-size: 10pt;">
                                 <tr style="'.$border2.'">
                                     <td colspan="2" style="width:33%; background: '.$bg.'; '.$border.'">
                                        <h4 style="'.$color2.' margin-top:1px; margin-bottom: 1px; font-size:12px; text-align: center;">
                                            DEUDA PENDIENTE 
                                        </h4>
                                     </td>
                                     <td colspan="3" style="width:34%; background: '.$bg.'; '.$border.'">
                                        <h4 style="'.$color2.' margin-top:1px; margin-bottom: 1px; font-size:12px; text-align: center;">
                                            UNIDADES INMOBILIARIAS
                                        </h4>
                                     </td>
                                 </tr>
                                 <tr style="'.$border2.'">
                                     <td style="width:34%; background: '.$bg.'; '.$border.'">
                                        <h4 style="'.$color2.' margin-top:1px; margin-bottom: 1px; font-size:12px; text-align: center;">
                                            Mes 
                                        </h4>
                                     </td>
                                     <td style="width:16%; background: '.$bg.'; '.$border.'">
                                        <h4 style="'.$color2.' margin-top:1px; margin-bottom: 1px; font-size:12px; text-align: center;">
                                            Monto
                                        </h4>
                                     </td>
                                     <td style="width:18%; background: '.$bg.'; '.$border.'">
                                        <h4 style="'.$color2.' margin-top:1px; margin-bottom: 1px; font-size:12px; text-align: center;">
                                            Tipo
                                        </h4>
                                     </td>
                                     <td style="width:16%; background: '.$bg.'; '.$border.'">
                                        <h4 style="'.$color2.' margin-top:1px; margin-bottom: 1px; font-size:12px; text-align: center;">
                                            Número
                                        </h4>
                                     </td>
                                     <td style="width:16%; background: '.$bg.'; '.$border.'">
                                        <h4 style="'.$color2.' margin-top:1px; margin-bottom: 1px; font-size:12px; text-align: center;">
                                            M2
                                        </h4>
                                     </td>
                                 </tr>
                                 <tr style="'.$border2.'">
                                     <td style="text-align: left; width:34%; height: 50px; '.$border.'">
                                         <h6 style="font-weight: 300; margin-top: 1px; margin-bottom: 1px; line-height: 1.2; font-size: 10px;">';
                                            
         		$mes2 = date('m');

                $contadorDeudaPendiente = 0;
                $IngFecEmi = $rowIngresoRM['ing_da_fecemi'];
                $deuPenDesc = '';

                $selectConceptoIngreso = "SELECT i.ing_in_cod, MONTH(ci.coi_da_fecemi) AS mes, YEAR(ci.coi_da_fecemi) AS anio,
                SUM(ci.coi_do_imp) AS deuda FROM concepto_ingreso ci, ingreso i, concepto c WHERE i.ing_in_cod = ci.ing_in_cod
                AND c.con_in_cod = ci.con_in_cod AND uni_in_cod IN ('" . $rowUnidadEC['uni_in_cod'] . "') AND ing_in_est != '0'
                AND c.con_vc_tip = 'INGRESO' AND ci.coi_da_fecemi < '$IngFecEmi' GROUP BY i.ing_in_cod
                ORDER BY ci.coi_da_fecven ASC";
				$queryDeudaPendiente1 = $adapter->query($selectConceptoIngreso,$adapter::QUERY_MODE_EXECUTE)->toArray();
                

                $rowCountDeudaPendiente1 = count($queryDeudaPendiente1);
                $rowCountDeudaPendiente1 -= 2;
                
                foreach ($queryDeudaPendiente1 as $rowDeudaPendiente1) {
                    if ($contadorDeudaPendiente >= $rowCountDeudaPendiente1) {
                        $deuPenDesc .= ucwords(strtolower($this->getMonthNameString($rowDeudaPendiente1['mes']))).'<br>';
                    }
                    
                    $contadorDeudaPendiente++;
                }
                
                if ($contadorDeudaPendiente > 2) {
                    echo 'Meses Anteriores<br>';
                }
                echo $deuPenDesc;

                            echo '</h6>
                         </td>
                         <td style="text-align: left; width:16%; height: 50px; '.$border.' text-align: center;">
                            <h6 style="font-weight: 300; margin-top: 1px; margin-bottom: 1px; line-height: 1.2; font-size: 8px;">';

                    		$mes2 = date('m');

                            $contadorDeudaPendiente = 0;
                            $montoMesesAnteriores = 0;
                            $montoDeudaPendiente = 0;
                            $IngFecEmi = $rowIngresoRM['ing_da_fecemi'];
                            $deuPen = '';

                            $selectDeudaPendiente = "SELECT i.ing_in_cod, MONTH(ci.coi_da_fecven) AS mes, YEAR(ci.coi_da_fecven) AS anio,
                            SUM(ci.coi_do_imp) AS deuda FROM concepto_ingreso ci, ingreso i, concepto c WHERE i.ing_in_cod = ci.ing_in_cod
                            AND c.con_in_cod = ci.con_in_cod AND uni_in_cod IN ('" . $rowUnidadEC['uni_in_cod'] . "') AND ing_in_est != '0' AND c.con_vc_tip = 'INGRESO' AND ci.coi_da_fecemi < '$IngFecEmi' GROUP BY i.ing_in_cod ORDER BY ci.coi_da_fecven ASC ";
							$queryDeudaPendiente = $adapter->query($selectDeudaPendiente,$adapter::QUERY_MODE_EXECUTE)->toArray();
                            
                            $rowCountDeudaPendiente = count($queryDeudaPendiente);
                            $rowCountDeudaPendiente -= 2;
                            
                            foreach($queryDeudaPendiente as $rowDeudaPendiente) {

                    			$selectIngresoParcial = "SELECT SUM(ipa_do_imp) AS monto FROM ingreso_parcial WHERE ing_in_cod = '" . $rowDeudaPendiente['ing_in_cod'] . "' ";
								$rowPagoParcialDP = $adapter->query($selectIngresoParcial,$adapter::QUERY_MODE_EXECUTE)->current();

                                $deudaPendiente = $rowDeudaPendiente['deuda'] - $rowPagoParcialDP['monto'];

                                if ($contadorDeudaPendiente >= $rowCountDeudaPendiente) {
                                    $deuPen = $deuPen . 'S/ ' . number_format($deudaPendiente, 2, '.', ',').'&nbsp;<br>';
                                } else {
                                    $montoMesesAnteriores += $deudaPendiente;
                                }
                                
                                $montoDeudaPendiente += $deudaPendiente;
                                $contadorDeudaPendiente++;
                            }
                            
                            if ($contadorDeudaPendiente > 2) {
                                echo 'S/ ' . number_format($montoMesesAnteriores, 2, '.', ',').'&nbsp;<br>';
                            }
                            
                            echo $deuPen;

                            echo '</h6></td>';


                            echo '

	<td style="width:16%; height: 50px; '.$border.' text-align: left;">
                                         <h6 style="font-weight: 300; margin-top: 1px; margin-bottom: 1px; line-height: 1.2; font-size: 8px;">
                                            '.utf8_decode(ucwords(strtolower($unidadTipo))).'<br>';
                                            	
                                                $totalM2 = $totalM2 + $unidadAreaOcupada;

                                                $selectUnidad = "SELECT * FROM unidad WHERE uni_in_pad='" . $rowUnidadEC['uni_in_cod'] . "' AND uni_in_est != 0";
												$queryUnidadSecundaria = $adapter->query($selectUnidad,$adapter::QUERY_MODE_EXECUTE)->toArray();

                                                $cont=1;
                                                foreach ($queryUnidadSecundaria as $rowUnidadSecundaria) {
                                                   if($cont<=3){
                                                        echo utf8_decode(ucwords(strtolower($rowUnidadSecundaria['uni_vc_tip']))).'<br>';
                                                   }
                                                   $cont++;
                                                   $totalM2 = $totalM2 + $rowUnidadSecundaria['uni_do_aocu'];
                                                }
                                               
                                            
                                         echo '</h6>
                                     </td>
                                     <td style="width:16%; height: 50px; '.$border.'">
                                         <h6 style="font-weight: 300; margin-top: 1px; margin-bottom: 1px; line-height: 1.2; font-size: 8px;">';
                                            	
                                         	 echo utf8_decode(substr($unidadCodigoDepartamento, 0, 8))."<br>";
                                            
											$selectUnidad = "SELECT uni_vc_nom FROM unidad WHERE uni_in_pad='" . $rowUnidadEC['uni_in_cod'] . "' AND uni_in_est != 0";
											$queryUnidadSecundaria1 = $adapter->query($selectUnidad,$adapter::QUERY_MODE_EXECUTE)->toArray();
                                                $cont=1;
                                                foreach ($queryUnidadSecundaria1 as $rowUnidadSecundaria1) {
                                                   if($cont<=3){
                                                        echo utf8_decode(ucwords(strtolower($rowUnidadSecundaria1['uni_vc_nom']))).'<br>';
                                                   }
                                                   $cont++;
                                                }
                                               

                                         echo '</h6>
                                     </td>
                                     <td style="width:16%; height: 50px; '.$border.' text-align: center;">
                                         <h6 style="font-weight: 300; margin-top: 1px; margin-bottom: 1px; line-height: 1.2; font-size: 8px;">';
                                            	
                                        echo utf8_decode($unidadAreaOcupada)."<br>";
                                          
                                        $selectUnidad = "SELECT uni_do_aocu FROM unidad WHERE uni_in_pad='" . $rowUnidadEC['uni_in_cod'] . "' AND uni_in_est != 0";
										$queryUnidadSecundaria2 = $adapter->query($selectUnidad,$adapter::QUERY_MODE_EXECUTE)->toArray();

                                        $cont=1;
                                        foreach ($queryUnidadSecundaria2 as $rowUnidadSecundaria2) {
                                            if($cont<=3){
                                                echo utf8_decode(ucwords(strtolower($rowUnidadSecundaria2['uni_do_aocu']))).'<br>';
                                            }
                                            $cont++;
                                        }
                                               
                                         echo '</h6>
                                     </td>
                                 </tr>
                                 <tr style="'.$border2.'">
                                     <td style="width:34%; background: '.$bg.'; '.$border.'">
                                        <h4 style="'.$color2.' margin-top:3px; margin-bottom: 1px; font-size:12px; text-align: center;">
                                            Total Deuda S/
                                        </h4>
                                     </td>
                                     <td style="width:16%; '.$border.'">
                                         <h6 style="font-size: 12px; line-height: 1; margin-top: 3px; margin-bottom: 1px;">';
                                         
                                          	echo 'S/ ' . number_format($montoDeudaPendiente, 2, '.', ',');
                                         	
                                         echo '</h6>
                                     </td>
                                     <td colspan="2" style="width:32%; background: '.$bg.'; '.$border.'">
                                        <h4 style="'.$color2.' margin-top:3px; margin-bottom: 1px; font-size:12px; text-align: center;">
                                            Total M2
                                        </h4>
                                     </td>
                                     <td style="width:16%; '.$border.' text-align: center;">
                                         <h6 style="font-size: 12px; line-height: 1; margin-top: 3px; margin-bottom: 1px;">';
                                            
                                         echo  number_format($totalM2, 2, '.', ',');

                                         echo '</h6>
                                     </td>
                                 </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="3" style="width: 100%;">
                            <table cellspacing="0" style="width:100%; '.$border2.' text-align: center; font-size: 10pt;">
                                 <tr style="'.$border2.'">
                                     <td style="width:18%; background: '.$bg.'; '.$border.'">
                                        <h4 style="'.$color2.' margin-top:1px; margin-bottom: 1px; font-size:12px; text-align: center;">
                                           Presupuesto Mensual
                                        </h4>
                                     </td>
                                     <td style="width:16%;  '.$border.' text-align: center;">
                                        <h6 style="font-size: 12px; line-height: 1; margin-top: 7px; margin-bottom: 1px;">';
                                         

                                        $selectPresupuestoConcepto = "SELECT SUM(pre_do_mon) AS total FROM presupuesto p, concepto c WHERE p.con_in_cod=c.con_in_cod AND c.con_vc_tip='INGRESO' AND p.edi_in_cod='$codEnt' AND CAST(`pre_ch_periodo` AS SIGNED )='$mes' AND pre_ch_anio='$anio_consula' ";
										$rowPresupuesto = $adapter->query($selectPresupuestoConcepto,$adapter::QUERY_MODE_EXECUTE)->current();

										if ($entidadTipoPresupuesto != 'A TODO COSTO') {
											echo 'S/'. number_format($rowPresupuesto['total'], 2, '.', ',').'&nbsp;';
										}
                                        echo '</h6>
                                     </td>
                                     <td style="width:16%; background: '.$bg.'; '.$border.'">
                                        <h4 style="'.$color2.' margin-top:1px; margin-bottom: 1px; font-size:12px; text-align: center;">
                                            Cuota del <br> Mes
                                        </h4>
                                     </td>
                                     <td style="width:16%; '.$border.' text-align: center;">
                                        <h6 style="font-size: 12px; line-height: 1; margin-top: 7px; margin-bottom: 1px;">';
                                            
                                        	echo utf8_decode('S/ ' . number_format($sumaTotalConceptosMes, 2, ".", ""));

                                        echo '</h6>
                                     </td>';
                                    
                                     $totalPagadoMesActual=$this->getTotalPagadoMesActual($idIngresoView);
                                     $deudaMesActual=$sumaTotalConceptosMes - $totalPagadoMesActual;

                                      if($codEnt!='48'){
                                            $totalAcumulado = $deudaMesActual + $montoDeudaPendiente;
                                        } else {
                                            //$totalAcumulado = $totalMesActual;
                                            $totalAcumulado = $deudaMesActual + $montoDeudaPendiente;
                                       }

                                     echo '<td style="width:18%; background: '.$bg.'; '.$border.'">
                                        <h4 style="'.$color2.' margin-top:1px; margin-bottom: 1px; font-size:12px; text-align: center;">
                                            Total Acumulado
                                        </h4>
                                     </td>
                                     <td style="width:16%; '.$border.' text-align: center;">
                                        <h6 style="font-size: 12px; line-height: 1; margin-top: 7px; margin-bottom: 1px;">';
                                            
                                            echo utf8_decode('S/ ' . number_format($totalAcumulado, 2, ".", ","));

                                        echo '</h6>
                                     </td>
                                 </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="3" style="width: 100%;">
                            <table cellspacing="0" style="width:100%; '.$border2.' text-align: center; font-size: 10pt;">
                                 <tr style="'.$border2.'">
                                     <td style="width:80%; background: '.$bg.'; '.$border.'">
                                        <p style="'.$color2.' margin-top:7px; margin-bottom: 1px; font-size:12px; text-align: center;">
                                            MENSAJE
                                        </p>
                                     </td>
                                     <td style="width:20%; background: '.$bg.'; '.$border.'">
                                        <p style="'.$color2.' margin-top:1px; margin-bottom: 1px; font-size:12px; text-align: center;">
                                            CÓDIGO DE<br> REFERENCIA
                                        </p>
                                     </td>
                                 </tr>
                                 <tr style="'.$border2.'">
                                     <td style="width:80%; '.$border.' height: 60px;">
                                        <p style="font-size:8.5px; font-weight: 300; margin-top: 1px; margin-bottom: 1px; text-align: left;">';
                                           		
                                           		echo utf8_encode($entidadMensaje);

                                        echo '</p>
                                     </td>
                                     <td style="width:20%; '.$border.'">
                                        <p style="margin-top:25px; margin-bottom: 1px; font-size:12px; text-align: center;">';
                                            	
                                            	echo $rowUnidadEC['uni_vc_nom'];

                                        echo '</p>
                                     </td>
                                 </tr>
                            </table>
                        </td>
                     </tr>';
                     
					$contenidoRecibo = ob_get_clean();
					echo  $contenidoRecibo; 

                     echo '<tr>
                        <td colspan="3" style="width: 100%;">
                            <h4 style="margin-top:7px; margin-bottom: 1px; font-size:12px; text-align: center;">';
                                
                                if($admin!=0){echo 'ORIGINAL';}

                            echo '</h4>
                        </td>
                     </tr>
                </table>
            </td>
            <td style="width: 2%; text-align: right;">
                    &nbsp;
            </td>
            <td style="width: 49%; text-align: left;">
                <table cellspacing="0" style="width: 100%; text-align: center; font-size: 10pt;">';
                        
                echo  $contenidoRecibo; 

                    echo '<tr>
                        <td colspan="3" style="width: 100%;">
                            <h4 style="margin-top:7px; margin-bottom: 1px; font-size:12px; text-align: center;">';
                               
                            	if($admin!=0){echo 'COPIA';}

                            echo '</h4>
                        </td>
                     </tr>
                </table>
            </td>
         </tr>
    </table>';


    echo '</page>';
            $cont++;
        }

    } else {
        header('Location:index.php');
    }

    if($validar != 'RETURN') return $errorPdf;

}
	
	
	public function enviarCorreo()
	{
		return null;
	}

	private function getEspacio($cant){
		$result = '';

		for ($i = 1; $i <=$cant; $i++) {
		 $result .= '&nbsp;';
		}

		return $result;
	}

	private function getMonthNameInt($monthInt) {
        switch ($monthInt) {
            case 1: $monthInt = "ENERO";
                break;
            case 2: $monthInt = "FEBRERO";
                break;
            case 3: $monthInt = "MARZO";
                break;
            case 4: $monthInt = "ABRIL";
                break;
            case 5: $monthInt = "MAYO";
                break;
            case 6: $monthInt = "JUNIO";
                break;
            case 7: $monthInt = "JULIO";
                break;
            case 8: $monthInt = "AGOSTO";
                break;
            case 9: $monthInt = "SEPTIEMBRE";
                break;
            case 10: $monthInt = "OCTUBRE";
                break;
            case 11: $monthInt = "NOVIEMBRE";
                break;
            case 12: $monthInt = "DICIEMBRE";
                break;
        }
        return $monthInt;
    }

	private function existeCuotaDeMantenimiento($idIngreso){
		if($idIngreso==null or $idIngreso==0){
				return null;
		}

		$adapter=$this->getAdapter();
		$selectConceptoIngreso = "SELECT count(*) as cuota FROM concepto_ingreso WHERE ing_in_cod=$idIngreso AND con_in_cod=17 ";
		$rowCuotaMto = $adapter->query($selectConceptoIngreso,$adapter::QUERY_MODE_EXECUTE)->current();

		if($rowCuotaMto['cuota']>0){
			return true;
		}else{
			return false;
		}
	}

	private  function getTotalPagadoMesActual($idIngreso){
		$adapter=$this->getAdapter();
		$selectIngresoParcial = "SELECT sum(ipa_do_imp)as sumTotal from ingreso_parcial WHERE ing_in_cod=$idIngreso";
		$rowSum = $adapter->query($selectIngresoParcial,$adapter::QUERY_MODE_EXECUTE)->current();
		return $rowSum['sumTotal'];
	 }

	private function getRowsConceptosAdicionales($idIngreso){
		if($idIngreso==null or $idIngreso==0){
			return array();
		}

		$adapter=$this->getAdapter();
		$selectConceptoIngreso = "SELECT ci.*,con_vc_des from concepto_ingreso ci,concepto c WHERE ci.con_in_cod = c.con_in_cod AND ci.ing_in_cod=$idIngreso AND (ci.con_in_cod!=23 and ci.con_in_cod!=109 and ci.con_in_cod!=114 and ci.con_in_cod!=17)";
		$sqlConceptosAdicionales = $adapter->query($selectConceptoIngreso,$adapter::QUERY_MODE_EXECUTE)->toArray();

		$conceptosGroup=array();

		foreach($sqlConceptosAdicionales as $rowConcepto){
			array_push($conceptosGroup,$rowConcepto);
		}

		return $conceptosGroup;
	}

	private function getRowConceptoCuotaMto($idIngreso){
		if($idIngreso==null or $idIngreso==0){
		return null;
	}

	$adapter=$this->getAdapter();
	$selectConcepto = "SELECT ci.*,con_vc_des from concepto_ingreso ci,concepto c WHERE ci.con_in_cod = c.con_in_cod AND ci.ing_in_cod=$idIngreso AND ci.con_in_cod=17";
	return $adapter->query($selectConcepto,$adapter::QUERY_MODE_EXECUTE)->current();
	
	}

	private function getCountConceptos($idIngreso){
		if($idIngreso==null or $idIngreso==0){
			return null;
		}

		$adapter=$this->getAdapter();
		$selectConceptoIngreso = "SELECT count(*) as count from concepto_ingreso WHERE ing_in_cod=$idIngreso";
		$rowSum = $adapter->query($selectConceptoIngreso,$adapter::QUERY_MODE_EXECUTE)->current();
		return $rowSum['count'];
	}
	
	private function existeConsumoDeAgua($idIngreso){
		if($idIngreso==null or $idIngreso==0){
			return null;
		}

		$adapter=$this->getAdapter();
		$selectConceptoIngreso = "SELECT count(*) as consumo FROM concepto_ingreso WHERE ing_in_cod=$idIngreso AND (con_in_cod=23 OR con_in_cod=109 OR con_in_cod=114)";
		$rowConsumo = $adapter->query($selectConceptoIngreso,$adapter::QUERY_MODE_EXECUTE)->current();

		if($rowConsumo['consumo']>0){
			return true;
		}else{
			return false;
		}

		return false;
	}

	private function getSumConceptos($idIngreso){
		if($idIngreso==null or $idIngreso==0){
			return null;
		}

		$adapter=$this->getAdapter();
		$selectConceptoIngreso = "SELECT sum(coi_do_subtot)as sumTotal from concepto_ingreso WHERE ing_in_cod=$idIngreso";
		$rowSum = $adapter->query($selectConceptoIngreso,$adapter::QUERY_MODE_EXECUTE)->current();
		return $rowSum['sumTotal'];
	}

	private function getRowConceptoConsumoAgua($idIngreso){
		if($idIngreso==null or $idIngreso==0){
		return null;
		}

		$adapter=$this->getAdapter();
		$selectConceptoIngreso = "SELECT ci.*,con_vc_des FROM concepto_ingreso ci,concepto c WHERE ci.con_in_cod = c.con_in_cod AND ci.ing_in_cod=$idIngreso AND (ci.con_in_cod=23 or ci.con_in_cod=109 or ci.con_in_cod=114) LIMIT 1";
		$rows = $adapter->query($selectConceptoIngreso,$adapter::QUERY_MODE_EXECUTE)->current();
		return $rows;
	}

	private function mod($dividendo, $divisor) {
	    $resDiv = $dividendo / $divisor;
	    $parteEnt = floor($resDiv);            // Obtiene la parte Entera de resDiv
	    $parteFrac = $resDiv - $parteEnt;      // Obtiene la parte Fraccionaria de la divisi�n

	    $modulo = round($parteFrac * $divisor);  // Regresa la parte fraccionaria * la divisi�n (modulo)
	    return $modulo;
	}

	private function covertirNumLetras($number) {
		$number=str_replace(',', "", $number);
	    $thousands_final_string = ''; //new
	    //number = number_format (number, 2);
	    $number1 = $number;
	    $millions_final_string = '';
	    //settype (number, "integer");
	    $cent = explode('.', $number1);
	    $centavos = $cent[1];
	    $numerparchado = $cent[0];
	    if ($centavos == 0 || $centavos == NULL) {
	        $centavos = "00";
	    }

	    if ($number == 0 || $number == "") { // if amount = 0, then forget all about conversions,
	        $centenas_final_string = " cero "; // amount is zero (cero). handle it externally, to
	        // function breakdown
	    } else {
	        $millions = $this->ObtenerParteEntDiv($number, 1000000); // first, send the millions to the string
	        $number = $this->mod($numerparchado, 1000000);           // conversion function

	        if ($millions != 0) {
	            // This condition handles the plural case
	            if ($millions == 1) {              // if only 1, use 'millon' (million). if
	                $descriptor = " millon ";  // > than 1, use 'millones' (millions) as
	            } else {                           // a descriptor for this triad.
	                $descriptor = " millones ";
	            }
	        } else {
	            $descriptor = " ";                 // if 0 million then use no descriptor.
	        }

	        if ($this->string_literal_conversion($millions) == "cero") {
	            $millions_final_string = $descriptor;
	        } else {
	            $millions_final_string = $this->string_literal_conversion($millions) . $descriptor;
	        }


	        $thousands = $this->ObtenerParteEntDiv($number, 1000);  // now, send the thousands to the string
	        $number = $this->mod($number, 1000);            // conversion function.
	        //print "Th:".thousands;
	        if ($thousands != 1) {                   // This condition eliminates the descriptor
	            if ($this->string_literal_conversion($thousands) == "cero") {
	                $thousands_final_string = " mil ";
	            } else {
	                $thousands_final_string = $this->string_literal_conversion($thousands) . " mil ";
	            }
	            //  descriptor = " mil ";          // if there are no thousands on the amount
	        }
	        if ($thousands == 1) {
	            $thousands_final_string = " mil ";
	        }
	        if ($thousands < 1) {
	            $thousands_final_string = " ";
	        }

	        // this will handle numbers between 1 and 999 which
	        // need no descriptor whatsoever.

	        $centenas = $number;
	        $centenas_final_string = $this->string_literal_conversion($centenas);
	    } //end if (number ==0)

	    /* if (ereg("un",centenas_final_string))
	      {
	      centenas_final_string = ereg_replace("","o",centenas_final_string);
	      } */
	    //finally, print the output.

	    /* Concatena los millones, miles y cientos */
	    $cad = $millions_final_string . $thousands_final_string . $centenas_final_string;

	    /* Convierte la cadena a May�sculas */
	    $cad = strtoupper($cad);

	    if (strlen($centavos) > 2) {
	        if (substr($centavos, 2, 3) >= 5) {
	            $centavos = substr($centavos, 0, 1) . ((substr($centavos, 1, 2)) + 1);
	        } else {
	            $centavos = substr($centavos, 0, 2);
	        }
	    }

	    /* Concatena a los centavos la cadena "/100" */
	    if (strlen($centavos) == 1) {
	        $centavos = $centavos . "0";
	    }
	    $centavos = $centavos . "/100";


	    /* Asigna el tipo de moneda, para 1 = PESO, para distinto de 1 = PESOS */
	    /* if (number == 1)
	      {
	      moneda = " SOLES ";
	      }
	      else
	      {+moneda
	      moneda = " SOLES ";
	      } */

	    $cn = $cad . ' Y ' . $centavos;
	    return $cn;
	}

	private function string_literal_conversion($number) {
	    // first, divide your number in hundreds, tens and units, cascadig
	    // trough subsequent divisions, using the modulus of each division
	    // for the next.

	    $centenas = $this->ObtenerParteEntDiv($number, 100);

	    $number = $this->mod($number, 100);

	    $decenas = $this->ObtenerParteEntDiv($number, 10);
	    $number = $this->mod($number, 10);

	    $unidades = $this->ObtenerParteEntDiv($number, 1);
	    $number = $this->mod($number, 1);
	    $string_hundreds = "";
	    $string_tens = "";
	    $string_units = "";
	    // cascade trough hundreds. This will convert the hundreds part to
	    // their corresponding string in spanish.
	    if ($centenas == 1) {
	        $string_hundreds = "ciento ";
	    }


	    if ($centenas == 2) {
	        $string_hundreds = "doscientos ";
	    }

	    if ($centenas == 3) {
	        $string_hundreds = "trescientos ";
	    }

	    if ($centenas == 4) {
	        $string_hundreds = "cuatrocientos ";
	    }

	    if ($centenas == 5) {
	        $string_hundreds = "quinientos ";
	    }

	    if ($centenas == 6) {
	        $string_hundreds = "seiscientos ";
	    }

	    if ($centenas == 7) {
	        $string_hundreds = "setecientos ";
	    }

	    if ($centenas == 8) {
	        $string_hundreds = "ochocientos ";
	    }

	    if ($centenas == 9) {
	        $string_hundreds = "novecientos ";
	    }

	    // end switch hundreds
	    // casgade trough tens. This will convert the tens part to corresponding
	    // strings in spanish. Note, however that the strings between 11 and 19
	    // are all special cases. Also 21-29 is a special case in spanish.
	    if ($decenas == 1) {
	        //Special case, depends on units for each conversion
	        if ($unidades == 1) {
	            $string_tens = "once";
	        }

	        if ($unidades == 2) {
	            $string_tens = "doce";
	        }

	        if ($unidades == 3) {
	            $string_tens = "trece";
	        }

	        if ($unidades == 4) {
	            $string_tens = "catorce";
	        }

	        if ($unidades == 5) {
	            $string_tens = "quince";
	        }

	        if ($unidades == 6) {
	            $string_tens = "dieciseis";
	        }

	        if ($unidades == 7) {
	            $string_tens = "diecisiete";
	        }

	        if ($unidades == 8) {
	            $string_tens = "dieciocho";
	        }

	        if ($unidades == 9) {
	            $string_tens = "diecinueve";
	        }
	    }
	    //alert("STRING_TENS ="+string_tens);

	    if ($decenas == 2) {
	        $string_tens = "veinti";
	    }
	    if ($decenas == 3) {
	        $string_tens = "treinta";
	    }
	    if ($decenas == 4) {
	        $string_tens = "cuarenta";
	    }
	    if ($decenas == 5) {
	        $string_tens = "cincuenta";
	    }
	    if ($decenas == 6) {
	        $string_tens = "sesenta";
	    }
	    if ($decenas == 7) {
	        $string_tens = "setenta";
	    }
	    if ($decenas == 8) {
	        $string_tens = "ochenta";
	    }
	    if ($decenas == 9) {
	        $string_tens = "noventa";
	    }

	    // Fin de swicth decenas
	    // cascades trough units, This will convert the units part to corresponding
	    // strings in spanish. Note however that a check is being made to see wether
	    // the special cases 11-19 were used. In that case, the whole conversion of
	    // individual units is ignored since it was already made in the tens cascade.

	    if ($decenas == 1) {
	        $string_units = "";  // empties the units check, since it has alredy been handled on the tens switch
	    } else {
	        if ($unidades == 1) {
	            $string_units = "un";
	        }
	        if ($unidades == 2) {
	            $string_units = "dos";
	        }
	        if ($unidades == 3) {
	            $string_units = "tres";
	        }
	        if ($unidades == 4) {
	            $string_units = "cuatro";
	        }
	        if ($unidades == 5) {
	            $string_units = "cinco";
	        }
	        if ($unidades == 6) {
	            $string_units = "seis";
	        }
	        if ($unidades == 7) {
	            $string_units = "siete";
	        }
	        if ($unidades == 8) {
	            $string_units = "ocho";
	        }
	        if ($unidades == 9) {
	            $string_units = "nueve";
	        }
	        // end switch units
	    } // end if-then-else
	//final special cases. This conditions will handle the special cases which
	//are not as general as the ones in the cascades. Basically four:
	// when you've got 100, you dont' say 'ciento' you say 'cien'
	// 'ciento' is used only for [101 >= number > 199]
	    if ($centenas == 1 && $decenas == 0 && $unidades == 0) {
	        $string_hundreds = "cien ";
	    }

	// when you've got 10, you don't say any of the 11-19 special
	// cases.. just say 'diez'
	    if ($decenas == 1 && $unidades == 0) {
	        $string_tens = "diez ";
	    }

	// when you've got 20, you don't say 'veinti', which is used
	// only for [21 >= number > 29]
	    if ($decenas == 2 && $unidades == 0) {
	        $string_tens = "veinte ";
	    }

	// for numbers >= 30, you don't use a single word such as veintiuno
	// (twenty one), you must add 'y' (and), and use two words. v.gr 31
	// 'treinta y uno' (thirty and one)
	    if ($decenas >= 3 && $unidades >= 1) {
	        $string_tens = $string_tens . " y ";
	    }

	// this line gathers all the hundreds, tens and units into the final string
	// and returns it as the function value.

	    $final_string = $string_hundreds . $string_tens . $string_units;

	    if ($final_string == '') {
	        $final_string = "";
	        //$final_string = "cero";
	    }

	    return $final_string;
	}

	private function ObtenerParteEntDiv($dividendo, $divisor) {
	    $resDiv = $dividendo / $divisor;
	    $parteEntDiv = floor($resDiv);
	    return $parteEntDiv;
	}

	private function getFormatMes($mes){
	     $fmtMes = '';
	     
	     if($mes=='01'){
	         $fmtMes = 'ENERO';
	     }else if($mes=='02'){
	         $fmtMes = 'FEBRERO';
	     }else if($mes=='03'){
	         $fmtMes = 'MARZO';
	     }else if($mes=='04'){
	         $fmtMes = 'ABRIL';
	     }else if($mes=='05'){
	         $fmtMes = 'MAYO';
	     }else if($mes=='06'){
	         $fmtMes = 'JUNIO';
	     }else if($mes=='07'){
	         $fmtMes = 'JULIO';
	     }else if($mes=='08'){
	         $fmtMes = 'AGOSTO';
	     }else if($mes=='09'){
	         $fmtMes = 'SEPTIEMBRE';
	     }else if($mes=='10'){
	         $fmtMes = 'OCTUBRE';
	     }else if($mes=='11'){
	         $fmtMes = 'NOVIEMBRE';
	     }else if($mes=='12'){
	         $fmtMes = 'DICIEMBRE';
	     }
	     
	     return $fmtMes;
	 }


	private function getMonthNameString($monthString) {
	    switch ($monthString) {
	        case '01': $monthString = "ENERO";
	            break;
	        case '02': $monthString = "FEBRERO";
	            break;
	        case '03': $monthString = "MARZO";
	            break;
	        case '04': $monthString = "ABRIL";
	            break;
	        case '05': $monthString = "MAYO";
	            break;
	        case '06': $monthString = "JUNIO";
	            break;
	        case '07': $monthString = "JULIO";
	            break;
	        case '08': $monthString = "AGOSTO";
	            break;
	        case '09': $monthString = "SEPTIEMBRE";
	            break;
	        case '10': $monthString = "OCTUBRE";
	            break;
	        case '11': $monthString = "NOVIEMBRE";
	            break;
	        case '12': $monthString = "DICIEMBRE";
	            break;
	    }
	    return $monthString;
	}

	public function mail($params,$idUsuario)
	{	
		$adapter=$this->getAdapter();
		$tipo = $params['typeMail'];
		$fechaactual = date('d-m-Y');
		$dia = date("d");
		$mes = date("m")-1;
		// $mes = 2;
		$mes_a  = date("m");
		// $mes_a  = 2;
		$anio = date("Y");
		$fecha2 = $anio."-".$mes_a."-1";
		$ult_fech=date("Y-m-d", strtotime("$fecha2 - 1 day"));
		$fecfor = explode("-", $ult_fech);
		$fec_ult = $fecfor[2]."-".$fecfor[1]."-".$fecfor[0];
		//$mensaje = "";
		$mensaje2 = "";
		$mensajeNom = "";
		$adj1 = "";
		$adj2 = "";
		$fileBalance = (isset($params['fileBalance']))?$params['fileBalance']:'';

		$fecha=date("Y-m-d");
		$codent = $params['idEdificio'];

		$selectEdificioEmpresa = "SELECT edi_te_men, emp_vc_des,emp_vc_dom FROM edificio en, empresa e WHERE en.emp_in_cod=e.emp_in_cod AND en.edi_in_cod = '$codent'";
		$Resp = $adapter->query($selectEdificioEmpresa,$adapter::QUERY_MODE_EXECUTE)->current();

		$mensaje=$Resp['edi_te_men'];
		$nomEmp=$Resp['emp_vc_des'];
		$domiEmp=$Resp['emp_vc_dom'];

		$msg_tipo="";
		 
		if($tipo == 1){//Estado de Cuenta
		    $msg_tipo = '<p style="margin: 0 0 3px 0;"><strong>1.</strong> Estado de Cuenta de sus unidades inmobiliarias.</p>';
		}else if($tipo == 2){//Balance
		    $msg_tipo = '<p style="margin: 0 0 3px 0;"><strong>1.</strong> Balance de gestión del mes Anterior.</p>';
		}else{//ambos
		    $msg_tipo = '<p style="margin: 0 0 3px 0;"><strong>1.</strong> Estado de Cuenta de sus unidades inmobiliarias.</p>';
		    $msg_tipo.= '<p style="margin: 0 0 3px 0;"><strong>2.</strong> Balance de gestión del mes Anterior.</p>';
		}

		if(!isset($params['enviocorreo']))
		{
			$codent = $params['idEdificio'];
		    $varios =1;
		    $envio=0;
		    $contlleno = 0;
		    $contvacio = 0;
		    /* En caso se imprime todo o solo lo seleccionado*/
		    if(isset($params['idrow'])){
		    	$cod_par = implode(",", $params['idrow']);
		        $cod_par=explode(",",$cod_par);
		        $ids="";
		        for($xyz=0;$xyz<count($cod_par);$xyz++){
		                if($xyz==0){
		                        $ids.="'".$cod_par[$xyz]."'";
		                }else{
		                        $ids.=",'".$cod_par[$xyz]."'";
		                }
		        }

		        $SqlUni = "SELECT ui.uni_in_cod, ui.uni_in_pro, ui.uni_in_pos FROM unidad ui WHERE ui.uni_in_pad IS NULL AND ui.edi_in_cod='$codent' AND ui.uni_in_cod IN ($ids)";

		    }else{
		    	$SqlUni = "SELECT ui.uni_in_cod, ui.uni_in_pro, ui.uni_in_pos FROM unidad ui WHERE ui.uni_in_pad IS NULL AND ui.edi_in_cod='$codent' AND ui.uni_in_est != '0'";
		    }

			$ConsUni = $adapter->query($SqlUni,$adapter::QUERY_MODE_EXECUTE)->toArray();
			$ContUni = count($ConsUni);

		    if($ContUni>1){
		        $varios = 1;
		    }else{
		        $varios = 0;
		    }

		    foreach($ConsUni as $RespUni){
		        $coduni = $RespUni['uni_in_cod'];


		        if($RespUni['uni_in_pro']!=$RespUni['uni_in_pos'])
		        {
		        	$RespUs = $this->getMailUsuario($RespUni['uni_in_pro']);
		            if($RespUs['usu_vc_ema']==NULL || $RespUs['usu_vc_ema']==''){
		                $contvacio++;
		            }else{
		                $contlleno++;
		            }
		        }else{
		        	$RespUs2 = $this->getMailUsuario($RespUni['uni_in_pos']);
		            if($RespUs2['usu_vc_ema']==NULL || $RespUs2['usu_vc_ema']==''){
		                $contvacio++;
		            }else{
		                $contlleno++;
		            }
		        }

		    }

		    if($varios == 0){
		        if($contvacio > 0){
		            $resultMessage = "1:-:$coduni";
		            $envio =0;
		        }else{
		            $resultMessage = "Enviados: 1 , No enviados: 0 están sin correo";
		            $envio =1;
		        }
		    }else{
		        if($contlleno==0){
		            $resultMessage = "Enviados: $contlleno , No enviados: $contvacio están sin correo";
		            $envio = 0;
		        }else{
		            $resultMessage = "Enviados: $contlleno , No enviados: $contvacio están sin correo";
		            $envio=1;
		        }
		    }

		    /* Recorriendo los usuarios a los que se enviará los mensajes a su correo*/
		    if($envio ==1){
		        foreach($ConsUni as $RespUni){
		            $coduni = $RespUni['uni_in_cod'];
		            $destinatario=array();
		            if($RespUni['uni_in_pro']!=$RespUni['uni_in_pos']){
		                
		        		$RespUs = $this->getInfoUsuario($RespUni['uni_in_pro']);

		                if($RespUs['usu_ch_tip']=='PN'){
		                    $NomPro=$RespUs['usu_vc_nom']." ".$RespUs['usu_vc_ape'];
		                }else{
		                    $NomPro=$RespUs['usu_vc_ape'];
		                }
		                if($RespUs['usu_vc_ema']!='' && $NomPro!=''){
		                	$destinatario[]=array('nombre'=>$NomPro,"correo"=>$RespUs['usu_vc_ema']);
		                }



		        		$RespUs2 = $this->getInfoUsuario($RespUni['uni_in_pos']);

		                if($RespUs2['usu_ch_tip']=='PN'){
		                    $NomPos=$RespUs2['usu_vc_nom']." ".$RespUs2['usu_vc_ape'];
		                }else{
		                    $NomPos=$RespUs2['usu_vc_ape'];
		                }
		                if($RespUs2['usu_vc_ema']!='' && $NomPos!=''){
		                	$destinatario[]=array('nombre'=>$NomPos,"correo"=>$RespUs2['usu_vc_ema']);
		                }

		                $mensajeNombre = $NomPro.'/'.$NomPos;

		            }else{
		            	
		        		$RespUs = $this->getInfoUsuario($RespUni['uni_in_pro']);

		                if($RespUs['usu_ch_tip']=='PN'){
		                    $NomPro=$RespUs['usu_vc_nom']." ".$RespUs['usu_vc_ape'];
		                }else{
		                    $NomPro=$RespUs['usu_vc_ape'];
		                }

		                if($RespUs['usu_vc_ema']!='' && $NomPro!=''){
		                	$destinatario[]=array('nombre'=>$NomPro,"correo"=>$RespUs['usu_vc_ema']);
		                }

		                $mensajeNombre = $NomPro;
		            }

		            if(!empty($destinatario)){
		                /* Estructura para el envio del mensaje con el archivo de estado de cuenta adjunto */
		               
		                $mes_act = date("m");
		               	// $mes_act="2";

		                if($tipo == 1){
		                    $titulo="Estado de Cuenta ".$this->trans($mes_act);
		                    //bloque code soho+
		                    if($codent==65){
		                        $titulo="Mantenimiento edificio SOHO+ ".$this->trans($mes_act);
		                    }
		                }else if($tipo == 2){
		                    $titulo="Balance ".$this->trans($mes);
		                }else{
		                    $titulo="Estado de Cuenta ".$this->trans($mes_act)." y Balance ".$this->trans($mes);
		                    //bloque code soho+
		                    if($codent==65){
		                        $titulo="Mantenimiento edificio SOHO+ ".$this->trans($mes_act)." y Balance ".$this->trans($mes);
		                    }
		                }
		                
		                $responder="administrador@$domiEmp";
		                $remite=array('mail'=>"administrador@$domiEmp","nombre"=>"administrador");

		                //bloque de codigo para perzonalizar edificiook soho+
		                if($codent==65){
		                    $remite=array('mail'=>'administrador@edificiook.com','nombre'=>'ADMINISTRACION SOHO');
		                }


		                $msg = '
							<table style="width:100%; border:1px solid #ddd; background: #f8f8f8;">
								<tr>
									<td>
										<p style="margin:0 0 3px 0;">
											<strong>Estimado(a)</strong> '.$mensajeNombre.'
										</p>
									</td>
								</tr>
								<tr>
									<td>
										<p style="margin:0 0 3px 0;">
											En los archivos adjuntos encontrará:
										</p>
									</td>
								</tr>
								<tr>
									<td>'.$msg_tipo.'</td>
								</tr>
								<tr>
									<td><p style="margin: 0 0 3px 0;">'.$mensaje.'</p></td>
								</tr>
								<tr>
									<td><p style="margin: 0 0 3px 0;">Esta dirección de e-mail es utilizada solamente para los envíos de la información solicitada. Por favor no responda con consultas personales sobre sus cuentas ya que no es un medio seguro para la transmisión de información confidencial.</p></td>
								</tr>
								<tr>
									<td><p style="margin: 0 0 3px 0;">Atentamente</p></td>
								</tr>
								<tr>
									<td><p style="margin: 0 0 3px 0;"><strong>ADINCO</strong></p></td>
								</tr>
								
							</table>
						';

		                //$msg = $mensaje.$mensajeNom.$mensaje2;
		                $fecha = date("Y-m-d H:i:s");

		                //mail
		                
						// $this->sendMail($destinatario, $titulo, $msg, $remite, $fecha, $coduni, $mensajeNombre, $tipo, $fileBalance, $fec_ult, $idUsuario);
						$this->enviarcorreodeprueba($destinatario, $titulo, $msg, $remite, $fecha, $coduni, $mensajeNombre, $tipo, $fileBalance, $fec_ult, $idUsuario);

		            }
		            //unlink("../Reports_PDF/estado_cuenta/$coduni.pdf");            
		        }       
		    }else{
		        foreach($ConsUni as $RespUni){
		            $coduni = $RespUni['uni_in_cod'];
		            //unlink("../Reports_PDF/estado_cuenta/$coduni.pdf");            
		        }        
		    }    


		    return array(
		    	'message'=>'success',
		    	'result'=>$resultMessage
		    );



	}else{
		return array(
	    	'message'=>'error',
	    	'result'=>'No se encontro la variable'
	    );
	}
	}

public function enviarcorreodeprueba($destinatario, $titulo, $msg, $remite, $fecha, $coduni, $mensajeNombre, $tipo, $fileBalance, $fec_ult, $idUsuario) {

	$fechaActual=date("d-m-Y");
	$fechaActual = strftime("%B_del_%Y", strtotime($fechaActual));

	// HTML part
    $htmlPart           = new MimePart($msg);
    $htmlPart->encoding = Mime::ENCODING_QUOTEDPRINTABLE;
    $htmlPart->type     = "text/html; charset=UTF-8";

	$textContent = 'Prueba de correo con adjuntos';
    // Plain text part
    $textPart           = new MimePart($textContent);
    $textPart->encoding = Mime::ENCODING_QUOTEDPRINTABLE;
	$textPart->type     = "text/plain; charset=UTF-8";
	
	$body = new MimeMessage();
   	
	// $destinatario='cbravolozada@gmail.com';
	// $emisor='info@knd.pe';
	//Enviar email
	$message = new Message();
	// $message->addTo($destinatario);
	$message->addFrom($remite['mail'], $remite['nombre']);
	
	foreach($destinatario as $key=>$val)
	{
		$message->addTo($val['correo'], $val['nombre']);
    }

	// $message->setFrom($remite['mail'], $remite['nombre']);
        
	// $message->setEncoding("UTF-8");
	$message->setSubject($titulo);

	$content = new MimeMessage();
	$content->addPart($textPart);
	$content->addPart($htmlPart);

	$contentPart = new MimePart($content->generateMessage());
	$contentPart->type = "multipart/alternative;\n boundary=\"" . $content->getMime()->boundary() . '"';

	$body->addPart($contentPart);
	$messageType = 'multipart/related';

	if($tipo == 1){ //estado de cuenta
		$somefilePath = 'public/temp/recibo-eecc/'.$idUsuario.'-'.$coduni.'.pdf';
		$nameFile = 'EECC_de_'.$fechaActual.'.pdf';
	}else if($tipo == 2){ //balance
		$somefilePath = 'public/temp/recibo-eecc/'.$fileBalance;
		$nameFile = 'Balance_de_'.$fechaUltima.'.pdf';
	}else{ //estado de cuenta / balance
		$somefilePath = 'public/temp/recibo-eecc/'.$idUsuario.'-'.$coduni.'.pdf';
		$somefilePath2 = 'public/temp/recibo-eecc/'.$fileBalance;
		$nameFile = 'EECC_de_'.$fechaActual.'.pdf';
		$nameBalance = 'Balance_de_'.$fechaUltima.'.pdf';
	}

	//configuration
	$fileContent = fopen($somefilePath, 'r');
	
	$attachment = new MimePart($fileContent);
	$attachment->filename	 = $nameFile;
	$attachment->type        = Mime::TYPE_OCTETSTREAM;
	$attachment->encoding    = Mime::ENCODING_BASE64;
	$attachment->disposition = Mime::DISPOSITION_ATTACHMENT;

	$body->addPart($attachment);

	if(isset($somefilePath2)){
		$fileContent = fopen($somefilePath2, 'r');
		$attachment2 = new MimePart($fileContent);
		$attachment2->filename = $nameBalance;
		$attachment2->type    = Mime::TYPE_OCTETSTREAM;
		$attachment2->encoding    = Mime::ENCODING_BASE64;
		$attachment2->disposition = Mime::DISPOSITION_ATTACHMENT;
	}else{
		$attachment2 = $text;
	}

	
	// then add them to a MIME message
	// $mimeMessage = new Mime\Message();
	if($tipo!=1 && $tipo!=2){
		// $mimeMessage->setParts(array($html, $attachment, $attachment2));
		$body->addPart($attachment2);
	}

	// attach the body to the message and set the content-type
    $message->setBody($body);
    $message->getHeaders()->get('content-type')->setType($messageType);
    $message->setEncoding('UTF-8');
	
	// Utilizamos el smtp de gmail con nuestras credenciales
	$transport = new SmtpTransport();
	$options   = new SmtpOptions(array(
		'name'  => 'smtp.gmail.com',
		'host'  => 'smtp.gmail.com',
		'port'  => 465,
		'connection_class'  => 'login',
		'connection_config' => array(
			'username' => 'christian.bravo@knd.pe',
			'password' => 'CB46291407@1990',
			'ssl' => 'ssl',
		),
	));
	
	$transport->setOptions($options); //Establecemos la configuración
	$transport->send($message); //Enviamos el correo

	$this->saveLogCorreo($fecha,$coduni,$nombrePoseedor,$destinatario,$titulo);

	return 'success';
}


	private function sendMail($destinatario, $titulo, $msg, $remite, $fecha, $coduni, $mensajeNombre, $tipo, $fileBalance, $fechaUltima, $idUsuario)
	{
		setlocale(LC_ALL,"es_PE","es_PE","esp");
		$fechaActual=date("d-m-Y");
		$fechaActual = strftime("%B_del_%Y", strtotime($fechaActual));

		// first create the parts
        $text = new Mime\Part();
        $text->type = Mime\Mime::TYPE_TEXT;
        $text->charset = 'utf-8';
        
        // message html
        $html = new MimePart($msg);
        $html->type = "text/html";// message html

        // archive 

        if($tipo == 1){ //estado de cuenta
        	$somefilePath = 'public/temp/recibo-eecc/'.$idUsuario.'-'.$coduni.'.pdf';
        	$nameFile = 'EECC_de_'.$fechaActual.'.pdf';
        }else if($tipo == 2){ //balance
        	$somefilePath = 'public/temp/recibo-eecc/'.$fileBalance;
        	$nameFile = 'Balance_de_'.$fechaUltima.'.pdf';
        }else{ //estado de cuenta / balance
        	$somefilePath = 'public/temp/recibo-eecc/'.$idUsuario.'-'.$coduni.'.pdf';
        	$somefilePath2 = 'public/temp/recibo-eecc/'.$fileBalance;
        	$nameFile = 'EECC_de_'.$fechaActual.'.pdf';
        	$nameBalance = 'Balance_de_'.$fechaUltima.'.pdf';
        }

        //configuration
        $fileContent = fopen($somefilePath, 'r');
        //$data = base64_encode($fileContent);
        $attachment = new Mime\Part($fileContent);
        $attachment->type = 'application/pdf';
        $attachment->filename = $nameFile;
        $attachment->disposition = Mime\Mime::DISPOSITION_ATTACHMENT;
        // Setting the encoding is recommended for binary data
        $attachment->encoding = Mime\Mime::ENCODING_BASE64;

        if(isset($somefilePath2)){
        	//configuration
	        $fileContent = fopen($somefilePath2, 'r');
	        //$data = base64_encode($fileContent);
	        $attachment2 = new Mime\Part($fileContent);
	        $attachment2->type = 'application/pdf';
	        $attachment2->filename = $nameBalance;
	        $attachment2->disposition = Mime\Mime::DISPOSITION_ATTACHMENT;
	        // Setting the encoding is recommended for binary data
	        $attachment2->encoding = Mime\Mime::ENCODING_BASE64;
        }else{
        	$attachment2 = $text;
        }

        // then add them to a MIME message
        $mimeMessage = new Mime\Message();
        if($tipo==2){
        	$mimeMessage->setParts(array($html, $attachment, $attachment2));
        }else{
        	$mimeMessage->setParts(array($html, $attachment));
        }

        // and finally we create the actual email
        $message = new Mail\Message();
        /*$messagey->addHeader('X-MailGenerator', 'MyCoolApplication');
		$messagey->addHeader('X-greetingsTo', 'Mom', true); // multiple values
		$messagey->addHeader('X-greetingsTo', 'Dad', true);*/

        $message->setBody($mimeMessage);
        $message->setFrom($remite['mail'], $remite['nombre']);
        foreach($destinatario as $key=>$val){
        	$message->addTo($val['correo'], $val['nombre']);
        }
        $message->setSubject($titulo);
        $message->setEncoding("UTF-8");
        $message->getHeaders()->addHeaderLine('X-API-Key', 'FOO-BAR-BAZ-BAT');

        $transport = new Mail\Transport\Sendmail();
        $transport->send($message);

        // save log correo
        $this->saveLogCorreo($fecha,$coduni,$mensajeNombre,$destinatario,$titulo);

        // success
        return 'success';
	}

	private function saveLogCorreo($fecha,$coduni,$nombre,$destinatario,$titulo)
	{
		$adapter=$this->getAdapter();
		$sql=new Sql($adapter);
        $codigo = "";
        $queryUnidad = "SELECT * FROM unidad WHERE uni_in_cod='$coduni' ";
        $rowUni = $adapter->query($queryUnidad,$adapter::QUERY_MODE_EXECUTE)->current();
        $desUni = $rowUni['uni_vc_tip']." ".$rowUni['uni_vc_nom'];
        $codeni = $rowUni['edi_in_cod'];

        $logCorreo = '';
		$logNombre = '';
		foreach($destinatario as $key=>$val){
        	$logCorreo .= ",".$val['correo'];
        	$logNombre .= "/".$val['nombre'];
        }

        //insert log correo
        $insert = $sql->insert('log_correo');
        $data = array(
            'log_dt_freg'=>$fecha,
            'log_vc_unidad'=>$desUni,
            'log_vc_nombre'=>substr($logNombre, 1),
            'edi_in_cod'=>$codeni,
            'log_vc_email'=>substr($logCorreo, 1),
            'log_vc_asunto'=>$titulo
        );
        $insert->values($data);
        $sql->prepareStatementForSqlObject($insert)->execute();
	}

	private function getMailUsuario($id)
	{
		$adapter=$this->getAdapter();
		$queryUsuario = "SELECT usu_vc_ema FROM usuario WHERE usu_in_cod ='".$id."'";
		$data = $adapter->query($queryUsuario,$adapter::QUERY_MODE_EXECUTE)->current();
		return $data;
	}

	private function getInfoUsuario($id)
	{
		$adapter=$this->getAdapter();
		$queryUsuario = "SELECT usu_vc_ema,usu_vc_nom,usu_vc_ape,usu_ch_tip FROM usuario WHERE usu_in_cod ='".$id."'";
		$data = $adapter->query($queryUsuario,$adapter::QUERY_MODE_EXECUTE)->current();
		return $data;
	}

	private function trans($mes_act){
	    $mes_let = "";
	    switch($mes_act){
	        case 1: $mes_let="Enero"; break;
	        case 2: $mes_let="Febrero"; break;
	        case 3: $mes_let="Marzo"; break;
	        case 4: $mes_let="Abril"; break;
	        case 5: $mes_let="Mayo"; break;
	        case 6: $mes_let="Junio"; break;
	        case 7: $mes_let="Julio"; break;
	        case 8: $mes_let="Agosto"; break;
	        case 9: $mes_let="Septiembre"; break;
	        case 10: $mes_let="Octubre"; break;
	        case 11: $mes_let="Noviembre"; break;
	        case 12: $mes_let="Diciembre"; break;
	    }
	    return $mes_let;
	}


}
<?php
/**
 * edificioOk (https://www.edificiook.com)
 * creado por: Jhon Gomez, 13/05/2016.
 * ultima modificacion por: Meler Carranza
 * Fecha Modificacion: 13/05/2016.
 * Descripcion: 
 *
 * @autor     Fidel J. Thompson
 * @link      https://www.edificiook.com
 * @copyright Copyright (c) 2011-2016 KND S.A.C (http://www.knd.pe)
 * @license   http://www.edificiook.com/license/comercial Software Comercial
 */

namespace Reporte\Model;

use Zend\Db\Sql\Sql;
use Zend\Db\Sql\Select;
use Zend\Db\TableGateway\AbstractTableGateway;
use Zend\Db\Adapter\Adapter;
use Zend\Db\ResultSet\ResultSet;
use Zend\Db\Sql\Expression;

class BalanceTable extends AbstractTableGateway{

	public function __construct(Adapter $adapter)
	{
		$this->adapter=$adapter;
	}

	public function anioConceptoIngreso()
	{
		$adapter=$this->getAdapter();
        $sql=new Sql($adapter);
        $select = $sql->select()
	        	->from('concepto_ingreso')
	        	->columns(array('anio'=>new Expression('DISTINCT(YEAR(coi_da_fecven))')))
	        	->order('coi_da_fecven DESC');
        $queryConceptoIngreso=$sql->buildSqlString($select);
        $data = $adapter->query($queryConceptoIngreso,$adapter::QUERY_MODE_EXECUTE)->toArray();
        return $data;
	}

	public function generar($getMes,$getAnio,$getDia,$idEdificio)
	{

		if ($getMes!='null') {
		    $cbo_mes = $getMes;
		    $cbo_anio = $getAnio;
		    $cbo_entidad = $idEdificio;
		    $cbo_mes2 = $getMes + 1;
		}else {
		    $cbo_mes = date("m") - 1;
		    $cbo_anio = date("Y");
		    $cbo_entidad = $idEdificio;
		    $cbo_mes2 = date("m");
		}

		if ($cbo_mes == 0) {
		    $cbo_mes = "12";
		    $cbo_anio = date("Y") - 1;
		}

		$fecha = $cbo_anio . '-' . $cbo_mes . '-01';
		$fecha2 = $cbo_anio . '-' . $cbo_mes2 . '-01';

		//restamos 1 dia a la fecha transformada para obtener el ultimo dia del mes anterior
		$ult_fech = date("Y-m-d", strtotime("$fecha2 - 1 day")); // ultimo fecha q cada mes

		/* Consulta para hallar el ultima dia del ultimo mes */
		if ($cbo_mes2 >= 12) {
		    $cbo_mes2 = '12';
		    $ult_fech = $cbo_anio . '-' . date("m-d", strtotime("$fecha2 - 1 day")); // ultimo fecha q cada mes
		}

		$ult_dia = date("d", strtotime("$fecha2 - 1 day"));
		$fechaanterior = date("Y-m-d", strtotime("$fecha - 1 day"));
		$dia = "";

		if ($getDia!='null') {
		    $dia = $getDia;
		} else {
		    $dia = $ult_dia;
		}

		$mes_act = date("m");
		$anio_act = date("Y");

		$mes = "";
		$anio = "";
		if ($getMes!='null') {
		    $mes = $getMes;
		} else {
		    $mes = date("m");
		}

		if ($getAnio!='null') {
		    $anio = $getAnio;
		} else {
		    $anio = date("Y");
		}


		if ($mes == $mes_act && $anio == $anio_act) {
		    if ($getDia=='null') {
		        $ult_fech = date("Y-m-d", strtotime("$fecha2 - 1 day"));
		    } else {
		        $ult_fech = date("Y-m-" . $dia);
		    }
		    $ult_dia = $dia;
		}

		$total_egreso = 0;
		$total_cabe = 0;
		$total_deta = 0;
		$conceptoTotal = 0;
		$diferenciaConcepto = 0;
		$emiTotal = 0;
		$emiTotalCabe = 0;
		$cont = 0;

		//css
		$borderLine = 'border-top: 0.1px solid #000;border-bottom: 0.1px solid #000;';
		$borderBottom = 'border-bottom: 0.1px solid #000;';
		$borderTop = 'border-top: 0.1px solid #000;';
		$subTitleTotal = 'font-size:12px;font-weight:900;margin:0px;';
		$color2 = 'color:grey;';
		$saltoDePagina = 'page-break-before:always;';

		if (isset($idEdificio)) {
			$sql_anterior = $this->listarIngresosAnterior($fechaanterior, $cbo_entidad, $ult_fech);
		    $salIngAnte = $sql_anterior['total_ingreso'];
		    if ($sql_anterior['total_ingreso'] == 'NULL' || $sql_anterior['total_ingreso'] == '') {
		        $sql_anterior = $this->listarIngresosAnteriorSaldoEntidad($fechaanterior, $cbo_entidad, $ult_fech);
		        $salIngAnte = $sql_anterior['ingreso_anterior'];
		    } else {
		        /* sumar siempre el saldo anterior */
		        $sql_anterior = $this->listarIngresosAnteriorSaldoEntidad($fechaanterior, $cbo_entidad, $ult_fech);
		        $salIng = $sql_anterior['ingreso_anterior'];
		        $salIngAnte = $salIngAnte + $salIng;
		    }

		    $sql_ant_egr = $this->listarEgresosAnterior($fechaanterior, $cbo_entidad);
    		$saldo_anterior = ($salIngAnte - $sql_ant_egr['total_egreso']);

    		echo '
    			<meta charset="utf-8">
	    		<style>
					table {
					    border-collapse: collapse;
						border: 0px;
					}
					table, img, blockquote {page-break-inside: avoid;}
					body{margin:0px;border:0px;}
	    		</style>';

    		echo '
    			<body>
    			<div id="content">	
    			<table cellspacing="0" style="width: 100%;text-align:center;line-height:13px;margin-bottom:12px;">
					<tr>
						<td style="width:100%;"><h3 style="margin:0px;margin-bottom:10px;"><b>BALANCE</b></h3></td>
					</tr>
					<tr>
						<td style="width:100%;"><p style="font-size:12px;"><b>CORRESPONDIENTE AL '.$ult_dia.' DE '. strtoupper($this->traducirMes($sql_anterior['ACTUAL'])).' DE '.$cbo_anio.'</b></p></td>
					</tr>
					<tr>
						<td style="width:100%;"><p style="font-size:12px;">'.strtoupper($sql_anterior['edi_vc_nompro']).'</p></td>
					</tr>
					<tr>
						<td style="width:100%;color:grey;font-weight:900;"><p style="font-size:12px;"><b>(MONTOS EXPRESADOS EN SOLES)</b></p></td>
					</tr>
    			</table>
    		';
    			
			echo '
			<table cellspacing="0" style="width: 100%;margin-top:10px;">
				<tr>
					<td style="width:100%;"><p style="font-size:12px;"><b>'.utf8_decode('INGRESOS AL ' . $ult_dia . ' DE ' . strtoupper($this->traducirMes($sql_anterior['ACTUAL']))).' DE '. $cbo_anio.'</b></p></td>
				</tr>
			</table>';

			echo '
				<table cellspacing="0" style="width: 100%;">
					<tr style="line-height: 10px;">
						<td style="width:90%;"><p style="font-size:12px;">'.utf8_decode('SALDO DEL MES ANTERIOR ( ' . strtoupper($this->traducirMes($sql_anterior['ANTERIOR'])) . ' )').'</p></td>
						<td style="width:10%;"><p style="font-size:12px;text-align:right;">'.number_format(utf8_decode($saldo_anterior), 2, ".", ",").'</p></td>
					</tr>
				</table>
			';
					


			/* LISTADO DE CONCEPTOS */
		    $emiTotalCabe=0;
		    $sql_conceptoCabe = $this->listarConcepto($cbo_entidad, $cbo_anio, $cbo_mes, $dia);
		    foreach($sql_conceptoCabe as $RespconCabe) {
		        $conceptoTotal = 0;
		        $sql_conceptoDeta = $this->listarConceptoDetalle($cbo_entidad, $cbo_anio, $cbo_mes, $dia, $RespconCabe['con_in_cod']);
		        foreach ($sql_conceptoDeta as $RespconDeta) {
		            $emiTotalCabe+=$RespconDeta['monto'];
		        }
		    }


		    
    		/* MONTO INGRESOS DEL MES p. CUOTAS DEL MES  ACTUAL */
		    $sql_mante = $this->listarMantenimientoCuotas($cbo_mes, $cbo_anio, $cbo_entidad, $dia);

		    if (utf8_decode($sql_mante) == "") {
		        $sql_mante = "0.00";
		    } else {
		    	echo '
				<table cellspacing="0" style="width: 100%;">
		    		<tr style="line-height: 10px;">
						<td style="width:90%;"><p style="font-size:12px;">'.utf8_decode('INGRESOS DEL MES p. CUOTAS DEL MES DE ' . strtoupper($this->traducirMes($sql_anterior['ACTUAL']))).'</p></td>
						<td style="width:10%;"><p style="font-size:12px;text-align:right;">'.number_format(utf8_decode($sql_mante), 2, ".", ",").'<p></td>
					</tr>
				</table>';
		    }

		    $diferenciaConcepto = $sql_mante - $emiTotalCabe;

		    /* LISTADO DE CONCEPTOS */
		    $sql_conceptoCabe = $this->listarConcepto($cbo_entidad, $cbo_anio, $cbo_mes, $dia);
		    //echo '<table cellspacing="0" style="width: 100%;">';
		    foreach($sql_conceptoCabe as $RespconCabe) {
		        $conceptoTotal = 0;
		        $sql_conceptoDeta = $this->listarConceptoDetalle($cbo_entidad, $cbo_anio, $cbo_mes, $dia, $RespconCabe['con_in_cod']);
		        foreach ($sql_conceptoDeta as $RespconDeta) {
		            $conceptoTotal+=$RespconDeta['monto'];
		            $emiTotal+=$RespconDeta['monto'];
		        }

		        if ($RespconCabe['con_vc_des'] == 'CUOTA DE MANTENIMIENTO') {
		            $conceptoTotal +=$diferenciaConcepto;
		        }

		    //    echo '<tr style="line-height: 10px;"><td style="width:90%;"><p style="font-size:9px;'.$color2.'">'.$this->espacios(15).utf8_decode($RespconCabe['con_vc_des']).'</p></td>';

		        if ($conceptoTotal == 'NULL' || $conceptoTotal == '') {
		            $conceptoTotal = "0.00";
		        }

		    //    echo '<td style="width:10%;color:grey;"><p style="'.$color2.'font-size:9px;text-align:right;">'.number_format(utf8_decode($conceptoTotal), 2, ".", ",").'</p></td></tr>';
		    }

		    echo '</table>';

	    	echo '
	    	<table cellspacing="0" style="width: 100%;">
			    <tr>
					<td style="width:90%;"><p style="font-size:11px;"><b>'.$this->espacios(60).utf8_decode('TOTAL  INGRESOS AL ' . $ult_dia . ' DE ' . strtoupper($this->traducirMes($sql_anterior['ACTUAL'])) . ' DE ' . $cbo_anio).'</b></p></td>
					<td style="width:10%;'.$borderLine.'"><p style="font-size:11px;text-align:right;"><b>'.number_format(utf8_decode($sql_mante + $saldo_anterior), 2, ".", ",").'</b></p></td>
				</tr>
			</table>';

			echo '
			<table cellspacing="0" style="width: 100%;">
				<tr>
					<td style="width:100%;height:30px;"><p style="font-size:12px;"><b>'.utf8_decode('EGRESOS AL ' . $ult_dia . ' DE ' . strtoupper($this->traducirMes($sql_anterior['ACTUAL'])) . ' DE ' . $cbo_anio).'</b></p></td>
				</tr>
			</table>';

		    //recorre primero las familias de los conceptos

		    $adapter=$this->getAdapter();
	        $select = "SELECT cog_in_cod, cog_vc_des FROM concepto_grupo WHERE cog_in_est !=0 AND cog_in_cod IN(SELECT DISTINCT(cog_in_cod) AS cog_in_cod FROM concepto WHERE con_vc_tip = 'EGRESO')";
			$consGrup = $adapter->query($select,$adapter::QUERY_MODE_EXECUTE)->toArray();

			    foreach ($consGrup as $rowGrup) {
			        //valido solo con movimientos
			        $sql_valida = $this->listarEgresosTotales($cbo_mes, $cbo_anio, $cbo_entidad, $dia, $rowGrup['cog_in_cod']);

			        if (count($sql_valida) != 0) {
			            /* Muestra la familia de los conceptos */
			            echo '
			            <table>
			            	<tr>
								<td style="width:100%;height:20px;"><p style="font-size:12px;"><b>'.utf8_decode($rowGrup['cog_vc_des']).'</b></p></td>
							</tr>
						</table>';
			        }
			        
			        //Consulta que lista los totales de las cabeceras (Conceptos)
			        $sql_concepto = $this->listarEgresosTotales($cbo_mes, $cbo_anio, $cbo_entidad, $dia, $rowGrup['cog_in_cod']);

			        foreach ($sql_concepto as $Resp_con) {
			            /* Conceptos Pagados Cabeceras */
			            echo '
			           	<table cellspacing="0" style="width:100%;">
			           		<tr>
								<td style="width:90%;"><p style="font-size:10px;">'.$Resp_con['CONCEPTOS'].'</p></td>
								<td style="width:10%;"><p style="font-size:10px;text-align:right;">'.number_format(utf8_decode($Resp_con['TOTALES']), 2, ".", ",").'</p></td>
							</tr>
						</table>';

			            /* Conceptos Pagados Cabeceras Totales */
			            $total_egreso+=$Resp_con['TOTALES'];
			             
			            //Consulta que lista los totales  de las cabeceras (Conceptos)
			            $sql_prov = $this->listarDetalleProveedor($Resp_con['con_in_cod'], $cbo_mes, $cbo_anio, $cbo_entidad, $dia);
			            /* Lista los Proveedores Detalle */
			            foreach ($sql_prov as $Resp_prov) {
			                /* Conceptos */
			                echo '
		                	<table cellspacing="0" style="width:100%;">
		                		<tr style="line-height: 10px;">
									<td style="width:40%;'.$color2.'"><p style="font-size:9px;">'.$this->espacios(5).substr($Resp_prov['egr_te_obser'], 0, 34).'</p></td>
									<td style="width:20%;'.$color2.'"><p style="font-size:9px;">'.$Resp_prov['Numero'].'</p></td>
									<td style="width:30%;'.$color2.'"><p style="font-size:9px;">'.$Resp_prov['prv_vc_razsoc'].'</p></td>
									<td style="width:10%;'.$color2.''.$color2.'"><p style="font-size:9px;text-align:right;">'.number_format(utf8_decode($Resp_prov['mba_do_monto']), 2, ".", ",").'</p></td>
								</tr>
							</table>';
				        }

				        echo '
						<table cellspacing="0" style="width:100%;">
							<tr style="height:5px;">
								<td style="width:100%;"></td>
							</tr>
						</table>	
						';
			           
			        }

			    }


			    echo '
		    	<table cellspacing="0" style="width: 100%;">
				    <tr>
						<td style="width:90%;"><p style="font-size:11px;"><b>'.$this->espacios(60).utf8_decode('TOTAL  EGRESOS AL ' . $ult_dia . ' DE ' . strtoupper($this->traducirMes($sql_anterior['ACTUAL'])) . ' DE ' . $cbo_anio).'</b></p></td>
						<td style="width:10%;'.$borderLine.'"><p style="font-size:11px;text-align:right;"><b>'.number_format(utf8_decode($total_egreso), 2, ".", ",").'<b></p></td>
					</tr>
				</table>';

				echo '
				<table cellspacing="0" style="width: 100%;">
				    <tr>
						<td style="width:100%;">&nbsp;</td>
					</tr>
				</table>';

				$sql_tot_ingre = $this->listarIngresosTotales($cbo_mes, $cbo_anio, $cbo_entidad, $dia);
    			$sql_tot_ing2 = $sql_mante + $saldo_anterior;

				echo '
		    	<table cellspacing="0" style="width: 100%;">
				    <tr>
						<td style="width:90%;"><p style="font-size:12px;"><b>'.utf8_decode('SALDO DISPONIBLE EN BANCO AL ' . $ult_fech).'</p></td>
						<td style="width:10%;'.$borderLine.'"><p style="font-size:12px;text-align:right;"><b>'.number_format(utf8_decode($sql_tot_ing2 - $total_egreso), 2, ".", ",").'</p></td>
					</tr>
				</table>';


				echo '
		    	<table cellspacing="0" style="width: 100%;">
				    <tr>
						<td style="width:100%;'.$borderBottom.'">&nbsp;</td>
					</tr>
				</table>';

				echo '
				<table cellspacing="0" style="width: 100%;">
				    <tr>
						<td style="width:100%;">&nbsp;</td>
					</tr>
				</table>';

				echo '
		    	<table cellspacing="0" style="width: 100%;">
				    <tr>
						<td style="width:100%;"><p style="font-size:12px;"><b>'.utf8_decode('PENDIENTES POR COBRAR AL ' . $ult_dia . ' DE ' . strtoupper($this->traducirMes($sql_anterior['ACTUAL'])) . ' DE ' . $cbo_anio).'</b></p></td>
					</tr>
				</table>';

				//Lista los Clientes que tienen deuda Total Cabecera
			    $sql_deuda_Cabe = $this->listarPendientesCobrar($ult_fech, $cbo_entidad);
			    /* Lista los Clientes Con Deuda Pendiente */
			    /* recorrer los pendientes por corbrar  de los meses anteriores */
			    foreach ($sql_deuda_Cabe as $Resp_deuda_cabe) {
			        $totalConceptosIngresoByUnidad=$Resp_deuda_cabe['Total'];
			        $totalImportesByUnidad=$this->sumaImportesEnUnidadesConDeuda($Resp_deuda_cabe['uni_in_cod'], $ult_fech);
			        $pen_cob_total=$totalConceptosIngresoByUnidad - $totalImportesByUnidad;

			        if ($pen_cob_total > 0) {//validando los propietarios con deuda a favor
						echo '
				    	<table cellspacing="0" style="width: 100%;">
						    <tr style="line-height: 10px;">
								<td style="width:20%;'.$color2.'"><p style="font-size:9px;">'.$Resp_deuda_cabe['uni_vc_nom'].'</p></td>
								<td style="width:70%;'.$color2.'"><p style="font-size:9px;">'.$Resp_deuda_cabe['Nombres'].'</p></td>
								<td style="width:10%;'.$borderBottom.'"><p style="font-size:9px;text-align:right;">'.number_format(utf8_decode($pen_cob_total), 2, '.', ',').'</p></td>
							</tr>
						</table>';
						$total_deta+=$pen_cob_total;
			        }
			    }

			    echo '
		    	<table cellspacing="0" style="width: 100%;">
				    <tr>
						<td style="width:90%;"><p style="font-size:12px;">'.utf8_decode('TOTAL').'</p></td>
						<td style="width:10%;"><p style="font-size:12px;text-align:right;">'.number_format(utf8_decode($total_cabe + $total_deta), 2, ".", ",").'</p></td>
					</tr>
				</table>';

				echo '
		    	<table cellspacing="0" style="width: 100%;">
				    <tr>
						<td style="width:100%;'.$borderBottom.'">&nbsp;</td>
					</tr>
				</table>';

				echo '
				<table cellspacing="0" style="width: 100%;">
				    <tr>
						<td style="width:100%;">&nbsp;</td>
					</tr>
				</table>';


				echo '
		    	<table cellspacing="0" style="width: 100%;">
				    <tr>
						<td style="width:100%;"><p style="font-size:12px;"><b>'.utf8_decode('PENDIENTES POR PAGAR AL ' . $ult_dia . ' DE ' . strtoupper($this->traducirMes($sql_anterior['ACTUAL'])) . ' DE ' . $cbo_anio).'</b></p></td>
					</tr>
				</table>';

			    $total_pagar = 0;
				//familias de los coneptos
			    $adapter=$this->getAdapter();
		        $select = "SELECT cog_in_cod, cog_vc_des FROM concepto_grupo WHERE cog_in_est !=0 AND cog_in_cod IN(SELECT DISTINCT(cog_in_cod) AS cog_in_cod FROM concepto WHERE con_vc_tip = 'EGRESO')";
				$consGrup1 = $adapter->query($select,$adapter::QUERY_MODE_EXECUTE)->toArray();

			    $sql_pen_pag_valida = 0;
			    $valida_penPag = 0;
			    
			    foreach($consGrup1 as $rowGrup1) {
			        $sql_pen_pag_valida = $this->listarPendientesPagar($ult_fech, $cbo_entidad, $rowGrup1['cog_in_cod']);
			        if (count($sql_pen_pag_valida) != 0) {

			            echo '
			            <table>
			            	<tr>
								<td style="width:100%;height:20px;"><p style="font-size:12px;"><b>'.utf8_decode($rowGrup1['cog_vc_des']).'</b></p></td>
							</tr>
						</table>';
			        }
			        //Lista los pendientes por pagar
			        $sql_pen_pag = $this->listarPendientesPagar($ult_fech, $cbo_entidad, $rowGrup1['cog_in_cod']);
			        /* pendientes por pagar del mes actual */
			        foreach($sql_pen_pag as $Resp_pen_pag) {
			            $Pen_total_egr = $this->listarPendientesPagarTotalParcial($Resp_pen_pag['con_in_cod'], $ult_fech, $cbo_entidad);
			            $totales_egre = ($Resp_pen_pag['TOTALES'] - $Pen_total_egr);

			            echo '
			           	<table cellspacing="0" style="width:100%;">
			           		<tr>
								<td style="width:90%;"><p style="font-size:10px;">'.$Resp_pen_pag['CONCEPTOS'].'</p></td>
								<td style="width:10%;"><p style="font-size:10px;text-align:right;text-align:right;">'.number_format(utf8_decode($totales_egre), 2, '.', ',').'</p></td>
							</tr>
						</table>';

			            $total_pagar+=$totales_egre;
			            $sql_pen_pag_deta = $this->listarPendientesPagarDetalle($Resp_pen_pag['con_in_cod'], $cbo_entidad, $ult_fech);
			            /* pendientes por pagar del mes actual */
			            foreach ($sql_pen_pag_deta as $Resp_pen_pag_deta) {
			                $Pen_total_egreso = $this->listarPendientesPagarTotal($Resp_pen_pag_deta['egr_in_cod'], $ult_fech);
			                $TotEgr = $Resp_pen_pag_deta['egr_do_imp'] - $Pen_total_egreso; //bien
			                $razonSocial = $Resp_pen_pag_deta['prv_vc_razsoc'];

			                echo '
		                	<table cellspacing="0" style="width:100%;">
		                		<tr style="line-height: 10px;">
									<td style="width:40%;'.$color2.'"><p style="font-size:10px;">'.$this->espacios(5).substr($Resp_pen_pag_deta['egr_te_obser'], 0, 34).'</p></td>
									<td style="width:20%;'.$color2.'"><p style="font-size:10px;">'.$Resp_pen_pag_deta['Numero'].'</p></td>
									<td style="width:30%;'.$color2.'"><p style="font-size:10px;">'.$razonSocial.'</p></td>
									<td style="width:10%;'.$color2.''.$color2.'"><p style="font-size:10px;text-align:right;">'.number_format(utf8_decode($TotEgr), 2, ".", ",").'</p></td>
								</tr>
							</table>';

			            }

			        }
			    }

 				echo '
		    	<table cellspacing="0" style="width: 100%;">
				    <tr>
						<td style="width:90%;"><p style="font-size:12px;">'.utf8_decode('TOTAL').'</p></td>
						<td style="width:10%;'.$borderLine.'"><p style="font-size:12px;text-align:right;">'.number_format(utf8_decode($total_pagar), 2, ".", ",").'</p></td>
					</tr>
				</table>';
	

				/*echo '
		    	<table cellspacing="0" style="width: 100%;">
				    <tr>
						<td style="width:100%;'.$borderBottom.'">&nbsp;</td>
					</tr>
				</table>';*/

				

				echo '
		    	<table cellspacing="0" style="width: 100%;margin-bottom:10px;margin-top:10px;">
				    <tr>
						<td style="width:100%;text-align:center;"><p style="font-size:12px;"><b>'.utf8_decode('RESUMEN AL ' . $ult_dia . ' DE ' . strtoupper($this->traducirMes($sql_anterior['ACTUAL'])) . ' DE ' . $cbo_anio).'</b></p></td>
					</tr>
				</table>';


			    $sal_banco = $sql_tot_ing2 - $total_egreso;

			    echo '
					<table cellspacing="0" style="width: 100%;">
						<tr style="line-height:12px;">
							<td style="width:90%;"><p style="font-size:12px;">'.utf8_decode('SALDO DISPONIBLE EN BANCO AL ' . $ult_fech).'</p></td>
							<td style="width:10%;"><p style="font-size:12px;text-align:right;">'.number_format(utf8_decode($sal_banco), 2, ".", ",").'</p></td>
						</tr>
					</table>
				';

			     
			    /* TOTAL PENDIENTES POR COBRAR */
			    $pen_cob = $total_cabe + $total_deta;

			    echo '
					<table cellspacing="0" style="width: 100%;">
						<tr style="line-height:12px;">
							<td style="width:90%;"><p style="font-size:12px;">'.utf8_decode('TOTAL PENDIENTES POR COBRAR AL ' . $ult_dia . ' DE ' . strtoupper($this->traducirMes($sql_anterior['ACTUAL'])) . ' DE ' . $cbo_anio).'</p></td>
							<td style="width:10%;"><p style="font-size:12px;text-align:right;">'.number_format(utf8_decode($pen_cob), 2, ".", ",").'</p></td>
						</tr>
					</table>
				';

			    /* TOTAL PENDIENTES POR PAGAR */
			    echo '
					<table cellspacing="0" style="width: 100%;">
						<tr style="line-height:12px;">
							<td style="width:90%;"><p style="font-size:12px;">'.utf8_decode('TOTAL PENDIENTES POR PAGAR AL ' . $ult_dia . ' DE ' . strtoupper($this->traducirMes($sql_anterior['ACTUAL'])) . ' DE ' . $cbo_anio).'</p></td>
							<td style="width:10%;"><p style="font-size:12px;text-align:right;">('.number_format(utf8_decode($total_pagar), 2, ".", ",").')</p></td>
						</tr>
					</table>
				';

				


			    /* TOTAL PENDIENTES POR PAGAR */
			    $sal_cont = ($sal_banco + $pen_cob) - $total_pagar;

				echo '
		    	<table cellspacing="0" style="width: 100%;margin-top:10px;">
				    <tr style="line-height:12px;">
						<td style="width:90%;"><p style="font-size:12px;"><b>'.$this->espacios(60).utf8_decode('SALDO CONTABLE AL ' . $ult_dia . ' DE ' . strtoupper($this->traducirMes($sql_anterior['ACTUAL'])) . ' DE ' . $cbo_anio).'</b></p></td>
						<td style="width:10%;'.$borderLine.'"><p style="font-size:12px;text-align:right;">'.number_format(utf8_decode($sal_cont), 2, ".", ",").'</p></td>
					</tr>
				</table>';

				echo '
		    	<table cellspacing="0" style="width: 100%;">
				    <tr>
						<td style="width:100%;'.$borderBottom.'">&nbsp;</td>
					</tr>
				</table>';


				echo '
		    	<table cellspacing="0" style="width: 100%;margin-bottom:10px;margin-top:10px;">
				    <tr>
						<td style="width:100%;text-align:center;"><p style="font-size:12px;"><b>'.utf8_decode('DATOS DE INTERES ').'</b></p></td>
					</tr>
				</table>';


			    /* EMISIÓN DEL MES */
			    $resEmision = $this->listarEmisionMes($cbo_entidad, $cbo_anio, $cbo_mes, $dia);

			    /* EMISIÓN DEL MES */
			    if ($resEmision == 'NULL' || $resEmision == '') {
			        $resEmision = "0.00";
			    }

			    echo '
					<table cellspacing="0" style="width: 100%;">
						<tr style="line-height:12px;">
							<td style="width:90%;"><p style="font-size:12px;">EMISIÓN DEL MES</p></td>
							<td style="width:10%;"><p style="font-size:12px;text-align:right;">'.number_format($resEmision, 2, ".", ",").'</b></p></td>
						</tr>
					</table>
				';

			    $gastoProyec = $this->listarEgresoProyectado($cbo_entidad, $cbo_anio, $cbo_mes);

			    /* GASTOS PROYECTADO */
			    if ($gastoProyec == 'NULL' || $gastoProyec == '') {
			        $gastoProyec = "0.00";
			    }

			    echo '
					<table cellspacing="0" style="width: 100%;">
						<tr style="line-height:12px;">
							<td style="width:90%;"><p style="font-size:12px;">'.utf8_decode('GASTO PROYECTADO').'</p></td>
							<td style="width:10%;"><p style="font-size:12px;text-align:right;">'.number_format($gastoProyec, 2, ".", ",").'</p></td>
						</tr>
					</table>
				';

			    $sqlGastos = $this->listarTotalEgresos($cbo_entidad, $cbo_anio, $cbo_mes);
			    $gastoTotal = $sqlGastos['suma'];

			    /* GASTO EJECUTADO */
			    if ($gastoTotal == 'NULL' || $gastoTotal == '') {
			        $gastoTotal = "0.00";
			    }

			    echo '
					<table cellspacing="0" style="width: 100%;">
						<tr style="line-height:12px;">
							<td style="width:90%;"><p style="font-size:12px;">'.utf8_decode('GASTO EJECUTADO').'</p></td>
							<td style="width:10%;"><p style="font-size:12px;text-align:right;">'.number_format(utf8_decode($gastoTotal), 2, ".", ",").'</p></td>
						</tr>
					</table>
				';


			    /* FIRMA DEL ADMINISTRADOR */
			    $firmaAdmin = $this->listarAdministrador($cbo_entidad);

			    echo '
					<table cellspacing="0" style="width: 100%;text-align:center;margin-top:130px;">
						<tr>
							<td style="width:70%;">&nbsp;</td>
							<td style="width:30%;'.$borderTop.'"><p style="font-size:12px;">'.utf8_decode($firmaAdmin).'</p></td>
						</tr>
					</table>
				';

				echo '
					<table cellspacing="0" style="width: 100%;text-align:center;">
						<tr>
							<td style="width:70%;">&nbsp;</td>
							<td style="width:30%;"><p style="font-size:12px;">'.utf8_decode('Administrador (a)').'</p></td>
						</tr>
					</table>
					</div>
					</body>
				';

				$adapter=$this->getAdapter();
		        $selectDropView1 = "DROP VIEW IF EXISTS V_CuentasPorCobrar$cbo_entidad" . "_" . "$cbo_mes ";
				$Resp = $adapter->query($selectDropView1,$adapter::QUERY_MODE_EXECUTE);

				$selectDropView2 = "DROP VIEW IF EXISTS V_CuentasPorPagar$cbo_entidad" . "_" . "$cbo_mes ";
				$Resp = $adapter->query($selectDropView2,$adapter::QUERY_MODE_EXECUTE);

		}



	}



	private function traducirMes($month)
	{
		$result = '';
		switch (strtoupper($month)) {
			case 'JANUARY': $result = 'ENERO'; break;
			case 'FEBRUARY': $result = 'FEBRERO'; break;
			case 'MARCH': $result = 'MARZO'; break;
			case 'APRIL': $result = 'ABRIL'; break;
			case 'MAY': $result = 'MAYO'; break;
			case 'JUNE': $result = 'JUNIO'; break;
			case 'JULY': $result = 'JULIO'; break;
			case 'AUGUST': $result = 'AGOSTO'; break;
			case 'SEPTEMBER': $result = 'SEPTIEMBRE'; break;
			case 'OCTOBER': $result = 'OCTUBRE'; break;
			case 'NOVEMBER': $result = 'NOVIEMBRE'; break;
			case 'DECEMBER': $result = 'DICIEMBRE'; break;
		}
		return $result;
	}

	private function espacios($cantidad)
	{	
		$string = '';
		for($i=1;$i<=$cantidad;$i++){
			$string .= '&nbsp;';
		}
		return $string;
	}

    private function listarEmisionMes($cbo_entidad, $cbo_anio, $cbo_mes, $dia)
    {
    	$adapter=$this->getAdapter();
        $select = "SELECT SUM(coi_do_subtot) AS monto FROM concepto_ingreso ci, ingreso i, unidad ui WHERE ci.ing_in_cod=i.ing_in_cod AND ui.uni_in_cod=i.uni_in_cod AND MONTH(ci.coi_da_fecven)='$cbo_mes' AND YEAR(ci.coi_da_fecven)='$cbo_anio' AND ui.edi_in_cod='$cbo_entidad' AND  DAY(ci.coi_da_fecven)<='$dia'";
		$Resp = $adapter->query($select,$adapter::QUERY_MODE_EXECUTE)->current();
		return $Resp['monto'];
    }

    private function listarConcepto($cbo_entidad, $cbo_anio, $cbo_mes, $dia)
    {
    	$adapter=$this->getAdapter();
        $select = "SELECT ui.uni_in_cod, ci.con_in_cod, c.con_vc_des FROM ingreso i, concepto_ingreso ci, ingreso_parcial ip, unidad ui, concepto c WHERE c.con_in_cod = ci.con_in_cod AND ui.uni_in_cod = i.uni_in_cod AND i.ing_in_cod = ci.ing_in_cod AND i.ing_in_cod = ip.ing_in_cod AND i.ing_in_est = 0 AND ui.edi_in_cod = '$cbo_entidad' AND DAY(ip.ipa_da_fecpag) <= '$dia' AND MONTH(ip.ipa_da_fecpag) = '$cbo_mes' AND YEAR(ipa_da_fecpag) = '$cbo_anio' AND ui.uni_in_pad IS NULL GROUP BY ci.con_in_cod ";
		$Resp = $adapter->query($select,$adapter::QUERY_MODE_EXECUTE)->toArray();
	    return $Resp;
    }

    private function listarConceptoDetalle($cbo_entidad, $cbo_anio, $cbo_mes, $dia, $codConcepto) {
    	$adapter=$this->getAdapter();
        $select = "SELECT DISTINCT ip.ing_in_cod, ui.uni_in_cod, ui.uni_vc_nom, ci.con_in_cod, (SELECT SUM(coi_do_subtot) FROM concepto_ingreso cci WHERE cci.ing_in_cod = i.ing_in_cod AND cci.con_in_cod = '$codConcepto' ) AS monto, c.con_vc_des FROM ingreso i, concepto_ingreso ci, ingreso_parcial ip, unidad ui, concepto c WHERE c.con_in_cod = ci.con_in_cod AND ui.uni_in_cod = i.uni_in_cod AND i.ing_in_cod = ci.ing_in_cod AND i.ing_in_cod = ip.ing_in_cod AND ci.ing_in_cod = ip.ing_in_cod AND i.ing_in_est = 0 AND ui.edi_in_cod = '$cbo_entidad' AND DAY(ip.ipa_da_fecpag) <= '$dia' AND MONTH(ip.ipa_da_fecpag) = '$cbo_mes' AND YEAR(ipa_da_fecpag) = '$cbo_anio' AND ui.uni_in_pad IS NULL AND ci.con_in_cod = '$codConcepto' GROUP BY  i.ing_in_cod, ci.con_in_cod";
		$Resp = $adapter->query($select,$adapter::QUERY_MODE_EXECUTE)->toArray();
	    return $Resp;
    }

    private function listarEgresoProyectado($cbo_entidad, $cbo_anio, $cbo_mes)
    {
    	$adapter=$this->getAdapter();
        $select = "SELECT SUM(pre_do_mon) AS Total FROM presupuesto WHERE pre_ch_anio='$cbo_anio' AND pre_ch_periodo LIKE '$cbo_mes' AND edi_in_cod='$cbo_entidad' AND pre_in_est='1' AND pre_vc_mov='EGRESO'";
		$Resp = $adapter->query($select,$adapter::QUERY_MODE_EXECUTE)->current();
	    return $Resp['Total'];
    }

    private function listarTotalEgresos($cbo_entidad, $cbo_anio, $cbo_mes)
    {
    	$adapter=$this->getAdapter();
        $select = "SELECT SUM(e.egr_do_imp) AS suma FROM proveedor p, egreso e, concepto c WHERE p.prv_in_cod=e.prv_in_cod AND e.con_in_cod=c.con_in_cod AND MONTH(e.egr_da_fecemi)='$cbo_mes' AND YEAR(e.egr_da_fecemi)='$cbo_anio' AND e.edi_in_cod='$cbo_entidad'";
		$Resp = $adapter->query($select,$adapter::QUERY_MODE_EXECUTE)->current();
	    return $Resp;
    }

    private function listarAdministrador($cbo_entidad)
    {
    	$adapter=$this->getAdapter();
        $select = "SELECT usu_vc_ape AS apellidos,usu_vc_nom as nombres, usu_ch_tip as tipo FROM edificio ei, usuario u WHERE ei.edi_in_adm=u.usu_in_cod AND edi_in_cod=$cbo_entidad limit 1";
		$Resp = $adapter->query($select,$adapter::QUERY_MODE_EXECUTE)->current();

		$label="";
		if($Resp['tipo']=='PN'){
			$label=$Resp['nombres']." ".$Resp['apellidos'];
		}else{
			$label=$Resp['apellidos'];
		}

	    return $label;
    }

    private function listarEgresosTotales($cbo_mes, $cbo_anio, $cbo_entidad, $dia, $grupo) {
        $adapter=$this->getAdapter();
        $select = "SELECT c.con_in_cod, con_vc_des AS CONCEPTOS, SUM(epa_do_imp) AS TOTALES FROM egreso_parcial mb, egreso e, concepto c WHERE mb.egr_in_cod = e.egr_in_cod AND c.con_in_cod = e.con_in_cod AND e.edi_in_cod = '$cbo_entidad' AND MONTH(epa_da_fecpag) = '$cbo_mes' AND DAY(epa_da_fecpag) <= '$dia' AND YEAR(epa_da_fecpag) = '$cbo_anio' AND cog_in_cod = '$grupo' GROUP BY c.con_in_cod ";
		$Resp = $adapter->query($select,$adapter::QUERY_MODE_EXECUTE)->toArray();
	    return $Resp;
    }

    private function listarDetalleProveedor($conceptos, $cbo_mes, $cbo_anio, $cbo_entidad, $dia)
    {
    	$adapter=$this->getAdapter();
        $select ="SELECT p.prv_vc_razsoc, e.egr_te_obser, CONCAT(e.egr_vc_nroser,' - ',e.egr_vc_nrodoc) AS Numero, epa_do_imp AS mba_do_monto FROM egreso_parcial mb, egreso e, concepto c, proveedor p WHERE p.prv_in_cod = e.prv_in_cod AND mb.egr_in_cod = e.egr_in_cod AND c.con_in_cod = e.con_in_cod AND e.edi_in_cod = '$cbo_entidad' AND MONTH(epa_da_fecpag) = '$cbo_mes' AND YEAR(epa_da_fecpag) = '$cbo_anio' AND DAY(epa_da_fecpag) <= '$dia' AND c.con_in_cod='$conceptos' ";
		$Resp = $adapter->query($select,$adapter::QUERY_MODE_EXECUTE)->toArray();
	    return $Resp;
    }

    private function listarIngresosAnterior($fecha, $cbo_entidad, $ult_fech)
	{
		$adapter=$this->getAdapter();
        $select = "SELECT  SUM(ipa_do_impban) AS total_ingreso, MONTHNAME('$fecha') AS ANTERIOR, MONTHNAME('$ult_fech') AS ACTUAL, edi_vc_des, edi_vc_des, edi_vc_nompro FROM ingreso_parcial m, edificio e, ingreso i, unidad ui WHERE m.ing_in_cod = i.ing_in_cod AND ui.uni_in_cod = i.uni_in_cod AND ui.edi_in_cod = e.edi_in_cod AND ipa_da_fecpag <= '$fecha' AND ui.edi_in_cod = '$cbo_entidad' ";
		$Resp = $adapter->query($select,$adapter::QUERY_MODE_EXECUTE)->current();
	    return $Resp;
	}

    private function listarIngresosAnteriorSaldoEntidad($fecha, $cbo_entidad, $ult_fech)
    {
        $adapter=$this->getAdapter();
        $select = "SELECT (se.sae_do_mon) AS ingreso_anterior, MONTHNAME('$fecha') AS ANTERIOR, MONTHNAME('$ult_fech') AS ACTUAL, edi_vc_des, edi_vc_des, edi_vc_nompro FROM saldo_entidad se, edificio e WHERE se.edi_in_cod = e.edi_in_cod AND se.edi_in_cod = $cbo_entidad";
		$Resp = $adapter->query($select,$adapter::QUERY_MODE_EXECUTE)->current();
	    return $Resp;
    }

    private function listarEgresosAnterior($fecha, $cbo_entidad) {
    	$adapter=$this->getAdapter();
        $select = "SELECT SUM(epa_do_imp) AS total_egreso FROM egreso_parcial ep, egreso e WHERE ep.egr_in_cod = e.egr_in_cod AND epa_da_fecpag <= '$fecha' AND e.edi_in_cod = '$cbo_entidad' ";
		$Resp = $adapter->query($select,$adapter::QUERY_MODE_EXECUTE)->current();
	    return $Resp;
    }

    private function listarMantenimientoCuotas($cbo_mes, $cbo_anio, $cbo_entidad, $dia) {
        $adapter=$this->getAdapter();
        $select = "SELECT SUM(ipa_do_impban) AS monto FROM ingreso_parcial ip, ingreso i, unidad ui WHERE ui.uni_in_cod = i.uni_in_cod AND ip.ing_in_cod = i.ing_in_cod AND ui.edi_in_cod='$cbo_entidad' AND DAY(ipa_da_fecpag) <= '$dia' AND YEAR(ipa_da_fecpag) = '$cbo_anio' AND MONTH(ipa_da_fecpag)='$cbo_mes' ";
		$Resp = $adapter->query($select,$adapter::QUERY_MODE_EXECUTE)->current();
        return $Resp['monto'];
    }

    private function listarIngresosTotales($cbo_mes, $cbo_anio, $cbo_entidad, $dia) {
    	$adapter=$this->getAdapter();
        $select = "SELECT SUM(`ipa_do_imp`) AS TOTAL_INGRESO FROM ingreso_parcial m, ingreso i, unidad ui, edificio e WHERE i.ing_in_cod = m.ing_in_cod AND ui.uni_in_cod = i.uni_in_cod AND ui.`edi_in_cod` = e.`edi_in_cod` AND ui.`edi_in_cod` = e.`edi_in_cod` AND MONTH(ipa_da_fecpag) = '$cbo_mes' AND YEAR(ipa_da_fecpag) = '$cbo_anio' AND DAY(ipa_da_fecpag) <= '$dia' AND ui.edi_in_cod = '$cbo_entidad' ";
		$Resp = $adapter->query($select,$adapter::QUERY_MODE_EXECUTE)->current();
		return $Resp['TOTAL_INGRESO'];
    }

    private function listarPendientesCobrar($fecmax, $cbo_entidad) {
    	$adapter=$this->getAdapter();
        $select = "SELECT ing.ing_in_cod,uni_vc_nom,uni.uni_in_cod,sum(ci.coi_do_subtot) as Total,
                                    CASE WHEN (u.usu_ch_tip)='PN' THEN CONCAT(u.usu_vc_nom,' ',u.usu_vc_ape)
                                    WHEN (u.usu_ch_tip)='PJ' THEN (u.usu_vc_ape) END  AS Nombres
                                FROM ingreso ing
                                        INNER JOIN concepto_ingreso ci on ci.ing_in_cod=ing.ing_in_cod
                                        INNER JOIN unidad uni on uni.uni_in_cod=ing.uni_in_cod
                                        INNER JOIN usuario u on u.usu_in_cod=uni.uni_in_pos
                                WHERE ing.ing_da_fecven<='$fecmax' 
                                        AND ing_da_fecven <= '$fecmax'
                                        AND ing.uni_in_cod 
                                            IN (select i.uni_in_cod
                                                    from ingreso i, ingreso_parcial ip, unidad ui
                                                    where i.ing_in_cod=ip.ing_in_cod 
                                                        and i.uni_in_cod=ui.uni_in_cod
                                                        and i.ing_da_fecven <= '$fecmax' 
                                                        and ip.ipa_da_fecpag > '$fecmax' 
                                                        and ui.edi_in_cod='$cbo_entidad' 
                                                        and ui.uni_in_est = 1 
                                                        and ui.uni_in_pad IS NULL group by i.uni_in_cod
                                                union
                                                select i.uni_in_cod
                                                    from ingreso i, unidad ui
                                                    where i.uni_in_cod=ui.uni_in_cod
                                                        and i.ing_in_est!='0'
                                                        and i.ing_da_fecven <= '$fecmax'
                                                        and ui.edi_in_cod='$cbo_entidad' 
                                                        and ui.uni_in_est = 1 
                                                        and ui.uni_in_pad IS NULL group by i.uni_in_cod )
                                GROUP BY uni.uni_in_cod";
		$Resp = $adapter->query($select,$adapter::QUERY_MODE_EXECUTE)->toArray();
		return $Resp;
    }

	private function sumaImportesEnUnidadesConDeuda($cod_uni, $fecmax){
			$total=0;
			$adapter=$this->getAdapter();
	        $select = "SELECT SUM(`ipa_do_imp`) AS Total  FROM ingreso_parcial ipa WHERE ipa_da_fecpag<='$fecmax' AND ing_in_cod IN (select ing_in_cod from ingreso WHERE uni_in_cod ='$cod_uni' AND ing_da_fecven <= '$fecmax')";
			$Resp = $adapter->query($select,$adapter::QUERY_MODE_EXECUTE)->current();
			$total+=$Resp['Total'];
			return $total;
    }

    private function listarPendientesPagar($fecmax, $cbo_entidad, $grupo)
    {
    	$adapter=$this->getAdapter();
        $fecpar = explode("-", $fecmax);
        $select1 = "DROP VIEW IF EXISTS V_CuentasPorPagar$cbo_entidad" . "_" . "$fecpar[1]; ";
        $adapter->query($select1,$adapter::QUERY_MODE_EXECUTE);
        
        $select2 = "CREATE VIEW V_CuentasPorPagar$cbo_entidad" . "_" . "$fecpar[1] AS
        SELECT c.con_in_cod, e.egr_in_cod, e.egr_da_fecemi, CONCAT(e.egr_vc_nroser,' - ',e.egr_vc_nrodoc) AS Numero,con_vc_des,p.prv_vc_razsoc, e.egr_te_obser ,egr_do_imp
        FROM egreso e, concepto c, proveedor p
        WHERE p.prv_in_cod=e.prv_in_cod  AND c.con_in_cod=e.con_in_cod AND e.edi_in_cod='$cbo_entidad'
        AND `egr_in_est`!='0' AND egr_da_fecemi <= '$fecmax' AND cog_in_cod = '$grupo'
        UNION
        SELECT c.con_in_cod, e.egr_in_cod, e.egr_da_fecemi, CONCAT(e.egr_vc_nroser,' - ',e.egr_vc_nrodoc) AS Numero, con_vc_des, p.prv_vc_razsoc, e.egr_te_obser ,egr_do_imp
        FROM egreso_parcial ep, egreso e, proveedor p, concepto c
        WHERE c.con_in_cod=e.con_in_cod AND p.prv_in_cod=e.prv_in_cod AND ep.egr_in_cod=e.egr_in_cod AND e.edi_in_cod='$cbo_entidad'
        AND ep.epa_da_fecpag > '$fecmax' AND e.egr_da_fecemi <= '$fecmax' AND cog_in_cod = '$grupo'";
        $adapter->query($select2,$adapter::QUERY_MODE_EXECUTE);

        $select3 = "SELECT egr_in_cod, con_in_cod, con_vc_des AS CONCEPTOS, SUM(egr_do_imp) AS TOTALES FROM V_CuentasPorPagar$cbo_entidad" . "_" . "$fecpar[1] GROUP BY con_in_cod ";
		$Resp = $adapter->query($select3,$adapter::QUERY_MODE_EXECUTE)->toArray();
        return $Resp;
    }

    private function listarPendientesPagarTotal($cod_eng, $fecmax)
    {
    	$adapter=$this->getAdapter();
        $select = "SELECT SUM(epa_do_imp) AS Total FROM egreso_parcial WHERE egr_in_cod='$cod_eng' AND epa_da_fecpag <= '$fecmax'";
        $resp = $adapter->query($select,$adapter::QUERY_MODE_EXECUTE)->current();
        return $resp['Total'];
    }

    private function listarPendientesPagarTotalParcial($cod_con, $fecmax, $cod_ent)
    {
    	$adapter=$this->getAdapter();
        $select = "SELECT SUM(epa_do_imp) AS Total FROM egreso_parcial ep, egreso e WHERE ep.egr_in_cod=e.egr_in_cod AND e.con_in_cod='$cod_con' AND ep.epa_da_fecpag <= '$fecmax' AND e.edi_in_cod='$cod_ent' AND e.egr_in_est!=0 ";
        $resp = $adapter->query($select,$adapter::QUERY_MODE_EXECUTE)->current();
        return $resp['Total'];
    }

    private function listarPendientesPagarDetalle($concepto, $cbo_entidad, $fecmax)
    {
    	$fecpar = explode("-", $fecmax);
    	$adapter=$this->getAdapter();
        $select = "SELECT egr_in_cod, con_in_cod,Numero,con_vc_des,prv_vc_razsoc, egr_te_obser ,egr_do_imp FROM V_CuentasPorPagar$cbo_entidad" . "_" . "$fecpar[1] WHERE con_in_cod='$concepto' ";
        $resp = $adapter->query($select,$adapter::QUERY_MODE_EXECUTE)->toArray();
        return $resp;
    }



}
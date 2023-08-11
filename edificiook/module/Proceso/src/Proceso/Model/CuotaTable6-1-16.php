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
 * @license   http://www.edificiook.com/license/comercial Software Comercial
 */

namespace Proceso\Model;

use Zend\Db\Sql\Sql;
use Zend\Db\Sql\Select;
use Zend\Db\TableGateway\AbstractTableGateway;
use Zend\Db\Adapter\Adapter;
use Zend\Db\Sql\Expression;

class CuotaTable extends AbstractTableGateway{

	private $edificioId=null;
	private $usuarioId=null;

	private $tipoCalculoCuota=null; //proyectado - gasto
	private $tipoCuota=null;	//porcentual - uniforme

	private $cobroIndividualDeAgua=null; //true | false
	private $tipoCalculoAgua=null; // 0 | 1
	
	private $fechaEmision=null;
	private $fechaEmisionDb=null;
	private $diaEmision=null;
	private $mesEmision=null;
	private $yearEmision=null;

	private $mesGasto=null;
	private $yearGasto=null;

	private $fechaVence=null;
	private $fechaVenceDb=null;
	private $diaVence=null;
	private $mesVence=null;
	private $yearVence=null;

	private $tipoDocumento=null;
	private $nroSerie=null;
	private $nroDocumento=null;

	private $nroDecimales=2;

	private $nombreMes=array('Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre');


	public function __construct(Adapter $adapter)
	{
		$this->adapter=$adapter;
	}

	public function iniciarGeneracionDeCuota($params){
		$sql=new Sql($this->adapter);
		$this->edificioId=$params['edificioId'];
		$this->usuarioId=$params['usuarioId'];


		$fechaEmision=strtotime($params['fechaEmi']);
		$this->fechaEmision=date('d-m-Y',$fechaEmision);
		$this->fechaEmisionDb=date('Y-m-d',$fechaEmision);
		$this->diaEmision=date('d',$fechaEmision);
		$this->mesEmision=date('m',$fechaEmision);
		$this->yearEmision=date('Y',$fechaEmision);

		//la fecha de gasto viene hacer 1 mes antes a la fecha de emision.
		$fechaGasto=strtotime('-1 months',strtotime($params['fechaEmi']));
		$this->mesGasto=date('m',$fechaGasto);
		$this->yearGasto=date('Y',$fechaGasto);

		$fechaVence=strtotime($params['fechaVence']);
		$this->fechaVence=date('d-m-Y',$fechaVence);
		$this->fechaVenceDb=date('Y-m-d',$fechaVence);
		$this->diaVence=date('d',$fechaVence);
		$this->mesVence=date('m',$fechaVence);
		$this->yearVence=date('Y',$fechaVence);


		$edificioSelected=$this->getParametrosDeEdificio($this->edificioId);

		$this->tipoCalculoCuota=$edificioSelected['tCalculoCuota'];
		$this->tipoCuota=$edificioSelected['tCuota'];

		$this->cobroIndividualDeAgua=$edificioSelected['cobroAgua']==1 ? true:false;
		$this->tipoCalculoAgua=$edificioSelected['tCalculoAgua'];

		$this->tipoDocumento=$edificioSelected['tipoDoc'];

		$this->nroDecimales=$edificioSelected['nroDecimales'];

		
		$listaUnidadesPadres=$this->getAllUnidadesPadresPorEdificio($this->edificioId);
		

		if(!empty($listaUnidadesPadres)){

			$contadorUnidadesProvisionadas=0;

			foreach ($listaUnidadesPadres as $key => $value) {
				$ingresoId=null;
				$currentIdUnidad=$value['unidadId'];
				$currentRowUnidad=$this->getRowUnidadPorId($currentIdUnidad);

				$totalDeudaUnidad=(float)$currentRowUnidad['deuda'];

				/*
					* Si existe un ingreso previamente registrado, actualizamos si cumple las
					condiciones.

					*Si no existe un ingreso, insertamos un nuevo registro. 	
				*/
				$rowIngresoPrevioReg=$this->getRowIngresoPrevioRegistro($currentIdUnidad);
				if(!empty($rowIngresoPrevioReg)){
					/*
					*	Si existe un ingreso previamente registrado y el estado es [ 1 ] 
						podemos modificar los conceptos.

					*	Si el estado es [0,2] no podemos modificar conceptos, puesto que ya existe
						ingresos parciales(Asignamos null a la variable $ingresoId).
					*/
					if($rowIngresoPrevioReg['ing_in_est']==1){
						$ingresoId=$rowIngresoPrevioReg['ing_in_cod'];
						/*
							*Restamos a la deuda de unidad la sumatoria de todos los conceptos de 
							ingreso en modificación ( cuando concluya hacemos el mismo procedimiento
							y lo sumamos a la deuda de unidad). 
						*/
						$totalDeudaUnidad-=$this->getSumTotalConceptoIngreso($ingresoId);
						$this->deleteConceptoIngresoAutoReg($ingresoId);
					}else{
						$ingresoId=null; 
					}
				}else{
					$dataIngreso=array(	
						'uni_in_cod'=>$currentIdUnidad,
						'ing_in_usu'=>$currentRowUnidad['propietario'],
						'ing_in_resi'=>$currentRowUnidad['residente'],
						'ing_da_fecemi'=>$this->fechaEmisionDb,
						'ing_da_fecven'=>$this->fechaVenceDb,
						'ing_in_nroser'=>1,
						'ing_in_nrodoc'=>1,
						'ing_in_est'=>1,
					);
					$insertIngreso=$sql->insert('ingreso')->values($dataIngreso);
					$statementInsertIngreso=$sql->prepareStatementForSqlObject($insertIngreso);
					$ingresoId=$statementInsertIngreso->execute()->getGeneratedValue();
				}

				
				if($ingresoId!=null){
					$numeracionDoc=$this->numeracionDeDocumento();
					$this->nroSerie=$numeracionDoc['serie'];
					$this->nroDocumento=$numeracionDoc['nroDoc'];


					if(false==$this->existeConceptoIngreso(17,$ingresoId)){

						$importeCuotaMant=$this->getCalculoCuotaPorUnidad($currentIdUnidad);
						
						$dataConceptoCuotaMant=array(
								'con_in_cod'=>17,
								'ing_in_cod'=>$ingresoId,
								'coi_da_fecemi'=>$this->fechaEmisionDb,
								'coi_da_fecven'=>$this->fechaVenceDb,
								'coi_do_imp'=>$importeCuotaMant,
								'coi_do_subtot'=>$importeCuotaMant,
								'coi_in_nroser'=>$this->nroSerie,
								'coi_in_nrodoc'=>$this->nroDocumento
						);
						$insertConceptoCuota=$sql->insert('concepto_ingreso')->values($dataConceptoCuotaMant);
						$statementInsertConceptoCuota=$sql->prepareStatementForSqlObject($insertConceptoCuota);
						$statementInsertConceptoCuota->execute();
					}

					if($this->cobroIndividualDeAgua){
						
						if(false==$this->existeConceptoIngreso(23,$ingresoId)){
							$ImporteConceptoAgua=$this->getCalculoConsumoAgua($currentIdUnidad);
							$dataConceptoAgua=array(
								'con_in_cod'=>23,
								'ing_in_cod'=>$ingresoId,
								'coi_da_fecemi'=>$this->fechaEmisionDb,
								'coi_da_fecven'=>$this->fechaVenceDb,
								'coi_do_imp'=>$ImporteConceptoAgua,
								'coi_do_subtot'=>$ImporteConceptoAgua,
								'coi_in_nroser'=>$this->nroSerie,
								'coi_in_nrodoc'=>$this->nroDocumento
							);
							$insertConceptoAgua=$sql->insert('concepto_ingreso')->values($dataConceptoAgua);
							$statementInsertConceptoAgua=$sql->prepareStatementForSqlObject($insertConceptoAgua);
							$statementInsertConceptoAgua->execute();
						}
					}

					$sumTotalConceptoIngreso=$this->getSumTotalConceptoIngreso($ingresoId);
					$deudaIngreso=$sumTotalConceptoIngreso;

					/*
					*	Si la unidad tiene saldo a favor y la sumatoria de los conceptos es mayor a (0)
					*	registramos un ingreso parcial.
					*/
					if($totalDeudaUnidad<0 && $sumTotalConceptoIngreso>0){
						
						$rowUltimoIngresoParcial=$this->getRowIngresoParcial($currentRowUnidad['codigoUltimoIP']);
						if(!empty($rowUltimoIngresoParcial)){
							$totalDeudaUnidad=$totalDeudaUnidad + ($sumTotalConceptoIngreso * 1);
							$importe=($totalDeudaUnidad>=0) ? ($sumTotalConceptoIngreso - $totalDeudaUnidad) : $sumTotalConceptoIngreso;
							$deudaIngreso=$sumTotalConceptoIngreso - $importe;

							//Determina el estado de ingreso de acuerdo al a la suma de ingresos parciales vs concepto de ingreso.
							$estadoIngreso=$deudaIngreso<=0 ? 0 :($importe==0 ? 1:2);
						

							$dataInsertPago=array(
								'ing_in_cod'=>$ingresoId,
								'usu_in_cod'=>$this->usuarioId,
								'ipa_in_nroser'=>$this->nroSerie,
								'ipa_in_nrodoc'=>$this->nroDocumento,
								'ipa_vc_numope'=>$rowUltimoIngresoParcial['numOperacion'],
								'ipa_vc_tipdoc'=>$this->tipoDocumento,
								'ipa_vc_ban'=>$rowUltimoIngresoParcial['banco'],
								'ipa_da_fecemi'=>$this->fechaEmisionDb,
								'ipa_da_fecven'=>$this->fechaVenceDb,
								'ipa_da_fecpag'=>$rowUltimoIngresoParcial['fechaPago'],
								'ipa_do_int'=>'0.00',
								'ipa_te_obs'=>'',
								'ipa_do_tot'=>$importe,
								'ipa_do_imp'=>$importe,
								'ipa_do_sal'=>$deudaIngreso,
								'ipa_do_impban'=>0,
								'ipa_in_codimp'=>$rowUltimoIngresoParcial['importeCodigo'],
							);
							$insertPago=$sql->insert('ingreso_parcial')->values($dataInsertPago);
							$statementUpdateIngresoParcial=$sql->prepareStatementForSqlObject($insertPago);
							$lastIngresoParcial=$statementUpdateIngresoParcial->execute()->getGeneratedValue();
						}
						
					}else{
						$totalDeudaUnidad+=$sumTotalConceptoIngreso;
					}

					//Actualizamos total en ingreso
					$dataUpdateIngreso=array(	
						'ing_in_usu'=>$currentRowUnidad['propietario'],
						'ing_in_resi'=>$currentRowUnidad['residente'],
						'ing_da_fecemi'=>$this->fechaEmisionDb,
						'ing_da_fecven'=>$this->fechaVenceDb,
						'ing_in_nroser'=>$this->nroSerie,
						'ing_in_nrodoc'=>$this->nroDocumento,
						'ing_do_sal'=>$deudaIngreso,
					);

					if(!empty($estadoIngreso)){
						$dataUpdateIngreso['ing_in_est']=$estadoIngreso;
					}

					$updateIngreso=$sql->update()
						->table('ingreso')
						->set($dataUpdateIngreso)
						->where(array('ing_in_cod'=>$ingresoId));
					$statementUpdateIngreso=$sql->prepareStatementForSqlObject($updateIngreso);
					$statementUpdateIngreso->execute();

					//Actualizamos la deuda de unidad.
					$dataUpdateUnidad=array('uni_do_deu'=>$totalDeudaUnidad);
					if(isset($lastIngresoParcial)){
						$dataUpdateUnidad['uni_in_ultingpar']=$lastIngresoParcial;
					}
					$updateUnidad=$sql->update()
						->table('unidad')
						->set($dataUpdateUnidad)
						->where(array('uni_in_cod'=>$currentIdUnidad));
					$statementUpdateUnidad=$sql->prepareStatementForSqlObject($updateUnidad);
					$statementUpdateUnidad->execute();

					$contadorUnidadesProvisionadas++;
				}
			}//end foreach

			$indiceMes=(intval($this->mesEmision) -1);

			/////////save auditoria////////
            $params['accion']='Generación de cuota del mes de '.$this->nombreMes[$params['selMes'] - 1].' del '.$params['selYear'];
            $this->saveAuditoria($params);
            ///////////////////////////////

			return array(
				'tipo'=>'informativo',
				'mensaje'=>'Finalizo con éxito el proceso de generación de cuota '.$this->nombreMes[$indiceMes]." ".$this->yearEmision.". <b>".$contadorUnidadesProvisionadas."</b> Unidades fueron provisionadas"
			);
		}
	}


	public function getParametrosDeEdificio($edificioId){
		$sql=new Sql($this->adapter);
		$selectEdificio=$sql->select()
			->from(array('edi'=>'edificio'))
			->columns(array('tCalculoCuota'=>'edi_vc_tpre',
						'tCuota'=>'edi_vc_tcuo',
						'diaEmi'=>'edi_in_diapro',
						'diaVence'=>'edi_in_diapag',
						'cobroAgua'=>'edi_in_cobcon',
						'tCalculoAgua'=>'edi_in_tipcon',
						'tipoDoc'=>'edi_vc_tipdoc',
						'fechaSuscripcion'=>'edi_da_fecsus',
						'nroDecimales'=>'edi_in_round'
					))
			->where(array('edi_in_cod'=>$edificioId));

		$statement=$sql->prepareStatementForSqlObject($selectEdificio);
		$rsEdificio=$statement->execute()->current();
		return $rsEdificio;
	}

	private function getRowUnidadPorId($unidadId){
		$sql=new Sql($this->adapter);
		$selectUnidad=$sql->select()
			->from('unidad')
			->columns(array('propietario'=>'uni_in_pro','residente'=>'uni_in_pos','deuda'=>'uni_do_deu','codigoUltimoIP'=>'uni_in_ultingpar'))
			->where(array('uni_in_cod'=>$unidadId));
		$statement=$sql->prepareStatementForSqlObject($selectUnidad);
		return $statement->execute()->current();
	}

	private function getRowIngresoPrevioRegistro($unidadId){

		$sql=new Sql($this->adapter);
		$selectIngreso=$sql->select()
			->from(array('ing'=>'ingreso'))
			->columns(array('ing_in_cod','ing_in_est'))
			->where(array('uni_in_cod'=>$unidadId,
						"MONTH(ing_da_fecemi)=".$this->mesEmision,
						"YEAR(ing_da_fecemi)=".$this->yearEmision));

		$statement=$sql->prepareStatementForSqlObject($selectIngreso);
		$rsIngreso=$statement->execute()->current();
		return $rsIngreso;
	}

	private function getRowIngresoParcial($ingresoparcialId){
		$sql=new Sql($this->adapter);
		$selectIngesoParcial=$sql->select()
			->from(array('ipa'=>'ingreso_parcial'))
			->columns(array(
				'numOperacion'=>'ipa_vc_numope',
				'banco'=>'ipa_vc_ban',
				'fechaPago'=>'ipa_da_fecpag',
				'importeCodigo'=>'ipa_in_codimp'))
			->where(array('ipa_in_cod'=>$ingresoparcialId));
		$statement=$sql->prepareStatementForSqlObject($selectIngesoParcial);
		$rsIngreso=$statement->execute()->current();
		return $rsIngreso;
	}

	private function existeConceptoIngreso($conceptoId,$ingresoId){
		$sql=new sql($this->adapter);
		$selectConceptoIngreso=$sql->select()
			->from('concepto_ingreso')
			->columns(array('cantidad'=>new Expression('COUNT(*)')))
			->where(array('con_in_cod'=>$conceptoId,'ing_in_cod'=>$ingresoId));
		$statement=$sql->prepareStatementForSqlObject($selectConceptoIngreso);
		$rsConceptoIngreso=$statement->execute()->current();
		if($rsConceptoIngreso['cantidad']>0){
			return true;
		}
		return false;
	}

	//eliminar todo los conceptos de ingreso registrados automaticamente(producto de una generación de cuota)
	private function deleteConceptoIngresoAutoReg($ingresoId){
		$sql=new Sql($this->adapter);
		$delete=$sql->delete('concepto_ingreso')
			->where(array('ing_in_cod'=>$ingresoId,'coi_in_usureg'=>0));
		$statement=$sql->prepareStatementForSqlObject($delete);
		$statement->execute();
	}

	private function getSumTotalConceptoIngreso($ingresoId){
		$sql=new Sql($this->adapter);
		$selectSumCOI=$sql->select()
			->from('concepto_ingreso')
			->columns(array('total'=>new Expression('SUM(coi_do_subtot)')))
			->where(array('ing_in_cod'=>$ingresoId));
		$statement=$sql->prepareStatementForSqlObject($selectSumCOI);
		$rsSum=$statement->execute()->current();
		return $rsSum['total'];
	}

	private function getAllUnidadesPadresPorEdificio($edificioId){
		$adapter=$this->getAdapter();
		$sql=new Sql($adapter);
		$selectUnidadesPadres=$sql->select()
			->from('unidad')
			->columns(array('unidadId'=>'uni_in_cod'))
			->where(array('edi_in_cod'=>$edificioId,'uni_in_pad'=>NULL,'uni_in_est'=>1));
		$stringSql=$sql->buildSqlString($selectUnidadesPadres);
		$unidadesPadres=$adapter->query($stringSql,$adapter::QUERY_MODE_EXECUTE)->toArray();
		return $unidadesPadres;
	}


//Calculo cuota
	private function getCalculoCuotaPorUnidad($unidadId){

		$adapter=$this->getAdapter();
		$sql=new Sql($adapter);
		
		//sql para consultar el o los porcentaje(s) de cuota de una unidad y unidades hijas si las tiene.
		$selectSubUnidades=$sql->select()
			->from('unidad')
			->columns(array('porcentaje'=>'uni_do_cm2'));
		$selectSubUnidades->where
			->equalTo('uni_in_pad',$unidadId)
			->or
			->equalTo('uni_in_cod',$unidadId)
			->and
			->notEqualTo('uni_in_est',0);

        $stringSelectSubUnidades=$sql->buildSqlString($selectSubUnidades);
		$rsUnidades=$adapter->query($stringSelectSubUnidades,$adapter::QUERY_MODE_EXECUTE)->toArray();

		$montoCuota=0;
		
		if($this->tipoCalculoCuota=='PROYECTADO'){
			$presupuesto=$this->getPresupuestoDeCuota();
			if(!empty($rsUnidades)){
				foreach ($rsUnidades as $key => $value){
					$porcentajeCuota=$value['porcentaje'];
					$montoCuota+= (($porcentajeCuota / 100) * $presupuesto);
				}
			}
		}else if($this->tipoCalculoCuota=="GASTO"){
			$gastoRegistrado=$this->getAllSumGastoRegistrado();
			if(!empty($rsUnidades)){
				foreach ($rsUnidades as $key => $value){
					$porcentajeCuota=$value['porcentaje'];
					$montoCuota+= (($porcentajeCuota / 100) * $gastoRegistrado);
				}
			}
		}
		return $montoCuota;
	}

	private function getPresupuestoDeCuota(){
		$sql=new Sql($this->adapter);
		$selectPresupuesto=$sql->select()
			->from(array('pre'=>'presupuesto'))
			->columns(array("total"=>new Expression("SUM(pre.pre_do_mon)")))
			->join(array("con"=>"concepto"),'pre.con_in_cod=con.con_in_cod',array())
			->where(array("con.con_vc_des"=>'CUOTA DE MANTENIMIENTO',
					'con.emp_in_cod'=>0,
					'pre.edi_in_cod'=>$this->edificioId,
					'pre.pre_ch_periodo'=>$this->mesEmision,
					'pre.pre_ch_anio'=>$this->yearEmision));
		$statement=$sql->prepareStatementForSqlObject($selectPresupuesto);
		$rsPresupuesto=$statement->execute()->current();
		$presupuesto=0;
		if(!empty($rsPresupuesto)){
			$presupuesto=$rsPresupuesto['total'];
		}
		return $presupuesto;
	}

	private function getAllSumGastoRegistrado(){
		$sql=new Sql($this->adapter);
		$selectGasto=$sql->select()
			->from(array('egr'=>'egreso'))
			->columns(array("total"=>new Expression("SUM(egr.egr_do_imp)")))
			->where(array('egr.edi_in_cod'=>$this->edificioId,
					'MONTH(egr.egr_da_fecemi)='.$this->mesGasto,
					'YEAR(egr.egr_da_fecemi)='.$this->yearGasto,
					'egr.egr_in_est'=>1));

		$statement=$sql->prepareStatementForSqlObject($selectGasto);
		$rsGasto=$statement->execute()->current();
		$gasto=0;
		if(!empty($rsGasto)){
			$gasto=$rsGasto['total'];
		}
		return $gasto;
	}

//Calculo Agua

	private function getCalculoConsumoAgua($unidadId){
		$sql=new Sql($this->adapter);
		$selectConsumoAgua=$sql->select()
			->from('consumo')
			->columns(array('LITRO'=>'cns_do_conl','M3'=>'cns_do_conm3','GALON'=>'cns_do_congal'))
			->where(array('uni_in_cod'=>$unidadId,
							'MONTH(cns_da_fec)='.$this->mesGasto,
							'YEAR(cns_da_fec)='.$this->yearGasto));

		$statement=$sql->prepareStatementForSqlObject($selectConsumoAgua);
		$rsConsumoAgua=$statement->execute()->current();
		$consumoAguaEnM3=is_null($rsConsumoAgua['M3'])?0:$rsConsumoAgua['M3'];
	
		$importeConsumoAgua=0;
		//tipoCalculoAgua=1 (Tarifa Sedapal) || tipoCalculoAgua=0 de acuerdo al consumo del mes anterior
		if($this->tipoCalculoAgua==1){
			$importeConsumoAgua=$this->getImporteAguaSegunEPS($consumoAguaEnM3);
		}else{
			$importeConsumoAgua=$this->getImporteAguaSegunFactura($consumoAguaEnM3);
		}
		return $importeConsumoAgua;
	}

	//getImporteAguaSegunFactura() - retorna el precio por m3 de acuerdo al gasto registrado
	private function getImporteAguaSegunFactura($consumoAguaEnM3){
		$sql=new Sql($this->adapter);
		$selectGasto=$sql->select()
			->from(array('egr'=>'egreso'))
			->columns(array('cod'=>'egr_in_cod',"importe"=>'egr_do_imp','totalm3'=>'egr_do_totm3'))
			->where(array(
					'egr.edi_in_cod'=>$this->edificioId,
					'MONTH(egr.egr_da_fecemi)='.$this->mesGasto,
					'YEAR(egr.egr_da_fecemi)='.$this->yearGasto,
					"egr.con_in_cod"=>25
			));
		$selectGasto->where->notEqualTo('egr.egr_do_totm3',0);
		$statement=$sql->prepareStatementForSqlObject($selectGasto);
		$rsGasto=$statement->execute()->current();

		$importeConsumoAgua=0;
		$precioM3=0;
		if(!empty($rsGasto)){
			$importe=$rsGasto['importe'];
			$totalM3=$rsGasto['totalm3'];
			$precioM3=$importe / $totalM3;
		}
		$importeConsumoAgua=($consumoAguaEnM3 * $precioM3);

		return round($importeConsumoAgua,2);
	}

	private function getImporteAguaSegunEPS($consumoAguaEnM3){

		$adapter=$this->getAdapter();

		
		$precio=0;

		/*
		*
		*Esta seccion es temporal, la idea es hacerlo dinamico para otros edificios que trabajen con distintas tarifas.
		*/

		if($this->edificioId==55) {
			if($consumoAguaEnM3 >=0 && $consumoAguaEnM3<=8){
				$precio=(($consumoAguaEnM3 * 1.686) + ($consumoAguaEnM3 * 0.542)) * 1.18;
			}elseif($consumoAguaEnM3>8 && $consumoAguaEnM3<=25){
				$precio=(($consumoAguaEnM3 * 1.828) + ($consumoAguaEnM3 * 0.587)) * 1.18;
			}elseif($consumoAguaEnM3>25 && $consumoAguaEnM3<=100){
				$precio=(($consumoAguaEnM3 * 2.167) + ($consumoAguaEnM3 * 0.696)) * 1.18;
			}elseif($consumoAguaEnM3>100 && $consumoAguaEnM3<=1000){
				$precio=(($consumoAguaEnM3 * 2.801) + ($consumoAguaEnM3 * 0.899)) * 1.18;
			}

			return $precio;
		}


		if ($consumoAguaEnM3 <= 10) {
	        $stringSql = "SELECT tac_do_prc3 FROM `tarifa_consumo` WHERE `tac_in_cod` = '1' AND `tac_in_est` != '0'";
	        $rowTar=$adapter->query($stringSql,$adapter::QUERY_MODE_EXECUTE)->current();
	        $precio = (($consumoAguaEnM3 * $rowTar['tac_do_prc3']) * 1.18);

	    } elseif ($consumoAguaEnM3 > 10 && $consumoAguaEnM3 <= 25) {
	        $stringSql = "SELECT tac_do_prc3 FROM `tarifa_consumo` WHERE `tac_in_cod` = '1' AND `tac_in_est` != '0'";
	        $rowTar=$adapter->query($stringSql,$adapter::QUERY_MODE_EXECUTE)->current();

	        $stringSql ="SELECT tac_do_prc3 FROM `tarifa_consumo` WHERE `tac_in_cod` = '2' AND `tac_in_est` != '0'";
	        $rowTar2=$adapter->query($stringSql,$adapter::QUERY_MODE_EXECUTE)->current();

	        $precio = ((($rowTar['tac_do_prc3'] * 10) + ($consumoAguaEnM3 - 10) * $rowTar2['tac_do_prc3']) * 1.18);
	    } elseif ($consumoAguaEnM3 > 25 && $consumoAguaEnM3 <= 50) {
	        $stringSql ="SELECT tac_do_prc3 FROM `tarifa_consumo` WHERE `tac_in_cod` = '2' AND `tac_in_est` != '0'";
	        $rowTar2=$adapter->query($stringSql,$adapter::QUERY_MODE_EXECUTE)->current();

	        $stringSql ="SELECT tac_do_prc3 FROM `tarifa_consumo` WHERE `tac_in_cod` = '3' AND `tac_in_est` != '0'";
	        $rowTar3=$adapter->query($stringSql,$adapter::QUERY_MODE_EXECUTE)->current();

	        $precio = (((25 * $rowTar2['tac_do_prc3']) + ($consumoAguaEnM3 - 25) * $rowTar3['tac_do_prc3']) * 1.18);
	    } elseif ($consumoAguaEnM3 > 50) {
	        $stringSql ="SELECT tac_do_prc3 FROM `tarifa_consumo` WHERE `tac_in_cod` = '3' AND `tac_in_est` != '0'";
	        $rowTar3=$adapter->query($stringSql,$adapter::QUERY_MODE_EXECUTE)->current();

	        $stringSql ="SELECT tac_do_prc3 FROM `tarifa_consumo` WHERE `tac_in_cod` = '4' AND `tac_in_est` != '0'";
	        $rowTar4=$adapter->query($stringSql,$adapter::QUERY_MODE_EXECUTE)->current();

	        $precio = (((50 * $rowTar3['tac_do_prc3']) + ($consumoAguaEnM3 - 50) * $rowTar4['tac_do_prc3']) * 1.18);
	    }

	    return $precio;
	}


	private function numeracionDeDocumento(){
		$sql=new Sql($this->adapter);
		$selectEdificio=$sql->select()
			->from(array('edi'=>'edificio'))
			->columns(
				array(
					'serie'=>'edi_in_numser',
					'nroDoc'=>'edi_in_numdoc',
				)
			)
			->where(array('edi_in_cod'=>$this->edificioId));

		$statement=$sql->prepareStatementForSqlObject($selectEdificio);
		$rsEdificio=$statement->execute()->current();

		if(empty($rsEdificio['nroDoc'])){
			$rsEdificio['nroDoc']=1;
		}else{
			$rsEdificio['nroDoc']+=1;
		}

		if(empty($rsEdificio['serie'])){
			$rsEdificio['serie']=1;
		}else{
			if(strlen($rsEdificio['serie'])>4){
				$rsEdificio['serie']+=1;
				$rsEdificio['nroDoc']=1;
			}
		}

		//Actualizamos la numeracion en la tabla edificio la numeracion del documento generado.
		$dataNumeracion=array('edi_in_numser'=>$rsEdificio['serie'],'edi_in_numdoc'=>$rsEdificio['nroDoc']);
		$updateEdificio=$sql->update()->table('edificio')
			->set($dataNumeracion)->where(array('edi_in_cod'=>$this->edificioId));
		$sql->prepareStatementForSqlObject($updateEdificio)->execute();

		return $rsEdificio;
	}

	private function getMaxId($table,$id){
		$sql=new Sql($this->driver);
		$selectId=$sql->select()->from($table)->columns(array('id',new Expression('MAX('.$id.')')));
		$statement=$sql->prepareStatementForSqlObject($selectId);
		$row=$statement->execute()->current();
		return $row['id']++;
	}

	private function saveAuditoria($params)
    {   
        $adapter=$this->getAdapter();
        $sql=new Sql($adapter);
        $insert = $sql->insert('auditoria');
        $data = array(
            'usu_in_cod'=> $params['usuarioId'], //idusuario
            'edi_in_cod'=> $params['edificioId'], //idedificio
            'aud_opcion'=> 'Procesos > Generar Cuota', //ruta
            'aud_accion'=> $params['accion'], //accion
            'aud_fecha'=> date('Y-m-d H:i:s'), //fecha y hora
            'aud_ip'=> $_SERVER['REMOTE_ADDR'], //ip publica
            'aud_tabla'=> '', //tabla
            'aud_in_numra'=> '', //registro afectado
            'aud_info_adi'=> $_SERVER['HTTP_USER_AGENT'] //agente
        );
        $insert->values($data);
        $sql->prepareStatementForSqlObject($insert)->execute()->count();
    }
}
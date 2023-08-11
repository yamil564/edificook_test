<?php
/**
 * edificioOk (https://www.edificiook.com).
 * creado por: Meler Carranza, 11/04/2016.
 * ultima modificacion por: Meler Carranza.
 * Fecha Modificacion: 26/04/2016.
 * Descripcion: Script para guardar datos de ingreso.
 *
 * @author    Fidel Thompson Fonseca.
 * @link      https://www.edificiook.com.
 * @copyright Copyright (c) 2011-2016 KND S.A.C (http://www.knd.pe).
 * @license   http://www.edificiook.com/license/comercial Software Comercial.
 */

namespace Finanzas\Model;
use Zend\Db\Sql\Sql;
use Zend\Db\Sql\Select;
use Zend\Db\TableGateway\AbstractTableGateway;
use Zend\Db\Adapter\Adapter;
use Zend\Db\ResultSet\ResultSet;
use Zend\Db\Sql\Expression;
use Zend\Json\Expr;
use Zend\Session\Container;
use PclZip;

class IngresoTable extends AbstractTableGateway{
	private $edificioId=null;
    private $empresaId=null;
    private $yearSelected=null;
	private $tipoUnidad=null;
    private $nombreMes=array('Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre');
    private $nombreMesNumero=array(1=>'Enero',2=>'Febrero',3=>'Marzo',4=>'Abril',5=>'Mayo',6=>'Junio',7=>'Julio',8=>'Agosto',9=>'Septiembre',10=>'Octubre',11=>'Noviembre',12=>'Diciembre');
    public $table_file_recaudacion = 'file_recaudacion';
    private $idUsuario=null;
    private $idEmpresa=null;
    private $idEdificio=null;
    public $table = 'edificio';

	public function __construct(Adapter $adapter)
	{
		$this->adapter=$adapter;

		$session=new Container('User');
        $this->idUsuario=$session->offsetGet('userId');
        $this->idEmpresa=$session->offsetGet('empId');
        $this->idEdificio=$session->offsetGet('edificioId');
	}

	public function ingresosParaGrid($params){

		$this->edificioId=$params['edificioId'];
		$this->empresaId=$params['empresaId'];
		$this->yearSelected=isset($params['year'])?$params['year']:date('Y');
		$this->tipoUnidad=isset($params['tipo_unidad'])?$params['tipo_unidad']:'principales';

		$response=array();
		//row totales
		$rowTotalEmitido=$this->getGridRowTotales('TOTAL EMITIDO');
		$response['rows'][0]['id']='TOTAL_INGRESO';
		$response['rows'][0]['cell']=$rowTotalEmitido;

		$rowTotalCobrado=$this->getGridRowTotales('T. COBRADO');
		$response['rows'][1]['id']='TOTAL_COBRADO';
		$response['rows'][1]['cell']=$rowTotalCobrado;

		$rowPendienteDeCobro=$this->getGridRowTotalesPendienteDeCobro($rowTotalEmitido,$rowTotalCobrado);
		$response['rows'][2]['id']='TOTAL_PENDIENTE';
		$response['rows'][2]['cell']=$rowPendienteDeCobro;

		$rowPendienteDeCobro=$this->getGridRowTotales('INGRESO BANCARIO');
		$response['rows'][3]['id']='TOTAL_BANCO';
		$response['rows'][3]['cell']=$rowPendienteDeCobro;

		//row unidades
		$adapter=$this->getAdapter();
		$sql=new Sql($this->adapter);
		$selectUnidades=$sql->select()
			->from('unidad')
			->columns(
				array(
					'id'=>'uni_in_cod',
					'unidadNombre'=>new Expression("CONCAT(uni_vc_tip,' ',uni_vc_nom)")
				)
			)
			->where(array('edi_in_cod'=>$this->edificioId,'uni_in_est'=>1));
		if($this->tipoUnidad=='secundarias'){
			$selectUnidades->where(array('uni_in_pad IS NOT NULL'));
		}else{
			$selectUnidades->where(array('uni_in_pad IS NULL'));
		}

		$selectString=$sql->buildSqlString($selectUnidades);
		$rsUnidades=$adapter->query($selectString,$adapter::QUERY_MODE_EXECUTE)->toArray();

		if(!empty($rsUnidades)){
			$i=4;
			foreach ($rsUnidades as $key => $value){
				$currentUnidadId=$value['id'];
				$currentUnidadNombre=$value['unidadNombre'];
				$response['rows'][$i]['id']=$currentUnidadId;
				$response['rows'][$i]['cell']=$this->getGridRowEmisionesPorUnidad($currentUnidadId,$currentUnidadNombre);
				$i++;
			}
		}
		return $response;
	}


	public function updateGrupoUnidadConcepto($params)
	{
		$sql=new Sql($this->adapter);
		$adapter=$this->getAdapter();

		//code
		if($params['ingresoId']!=''){
    		if($this->existeIngresosParciales($params['ingresoId'])){
				return array('response'=>'error','message'=>'Imposible actualizar el concepto por que existe pagos registrados.','tipo'=>'advertencia');
			}
    	}

		$response=array();

		//update
		$updateCI=$sql->update()
		->table('grupo_unidad_concepto')
		->set(array('guc_vc_total'=>$params['textTotalConcepto']))
		->where(
			array(
				'grup_in_cod'=>$params['idGrupoUnidad'],
				'con_in_cod'=>$params['co_conceptoId']
			)
		);
		$statementUpdate=$sql->prepareStatementForSqlObject($updateCI);
		$rsUpdate=$statementUpdate->execute()->count();

		if($rsUpdate>0){
			//query get total
			$select=$sql->select()
				->from('grupo_unidad_concepto')
				->columns(array('total'=>new Expression('SUM(guc_vc_total)')))
				->where(array('grup_in_cod'=>$params['idGrupoUnidad']));
			$sqlString=$sql->buildSqlString($select);
			$rsSelect=$adapter->query($sqlString,$adapter::QUERY_MODE_EXECUTE)->current();

			//update total
			$updateCI=$sql->update()
			->table('grupo_unidad')
			->set(array('grup_vc_total'=>$rsSelect['total']))
			->where(
				array(
					'grup_in_cod'=>$params['idGrupoUnidad']
				)
			);
			$statementUpdate=$sql->prepareStatementForSqlObject($updateCI);
			$rsUpdate=$statementUpdate->execute()->count();

			if($rsUpdate>0){

				//get total grupo unidad
				//SELECT SUM(grup_vc_total) as total FROM grupo_unidad WHERE uni_in_pad = 10530 AND uni_in_cod = 3972 AND grup_in_month = 11 AND grup_in_year = 2016
				$select=$sql->select()
					->from('grupo_unidad')
					->columns(array('total'=>new Expression('SUM(grup_vc_total)')))
					->where(
						array(
							'uni_in_pad'=>$params['idUnidadPadre'],
							'grup_in_month'=>$params['month'],
							'grup_in_year'=>$params['year']
						)
					);
				$sqlString=$sql->buildSqlString($select);
				$rsSelect=$adapter->query($sqlString,$adapter::QUERY_MODE_EXECUTE)->current();

				//UPDATE ingreso SET ing_do_sal = '603.53' WHERE uni_in_cod = 10530 AND month(ing_da_fecemi)=12 AND year(ing_da_fecemi)=2016
				//update total ingreso
				$updateCI=$sql->update()
				->table('ingreso')
				->set(array('ing_do_sal'=>$rsSelect['total']))
				->where(
					array(
						'uni_in_cod'=>$params['idUnidadPadre'],
						'MONTH(ing_da_fecemi)='.$params['month'],
						'YEAR(ing_da_fecemi)='.$params['year']
					)
				);

				$statementUpdate=$sql->prepareStatementForSqlObject($updateCI);
				$rsUpdate=$statementUpdate->execute()->count();

				if($rsUpdate>0){
					//select * from grupo_unidad where uni_in_pad = 10530 and grup_in_month = 12 and grup_in_year = 2016
					$select=$sql->select()
						->from('grupo_unidad')
						->columns(array('idGrupoUnidad'=>'grup_in_cod'))
						->where(
							array(
								'uni_in_pad'=>$params['idUnidadPadre'],
								'grup_in_month'=>$params['month'],
								'grup_in_year'=>$params['year']
							)
						);
					$sqlString=$sql->buildSqlString($select);
					$rsSelect=$adapter->query($sqlString,$adapter::QUERY_MODE_EXECUTE)->toArray();

					//print_r($rsSelect);
					$totalConcepto=0;
					foreach($rsSelect as $grupoUnidadConcepto){
						$totalConcepto += $this->getTotalConceptoPorIdGrupoUnidad($grupoUnidadConcepto['idGrupoUnidad'],$params['co_conceptoId']);
					}

					//getIdIngresoPadre
					//SELECT * FROM ingreso WHERE uni_in_cod = 10530 AND month(ing_da_fecemi)=12 AND year(ing_da_fecemi)=2016
					$select=$sql->select()
						->from('ingreso')
						->columns(array('idIngresoGrupoUnidad'=>'ing_in_cod'))
						->where(
							array(
								'uni_in_cod'=>$params['idUnidadPadre'],
								'MONTH(ing_da_fecemi)='.$params['month'],
								'YEAR(ing_da_fecemi)='.$params['year']
							)
						);
					$queryIngreso=$sql->buildSqlString($select);
					$dataIngreso=$adapter->query($queryIngreso,$adapter::QUERY_MODE_EXECUTE)->current();

					//update concepto ingreso
					//UPDATE concepto_ingreso SET coi_do_imp = '', coi_do_subtot = '' WHERE con_in_cod = '' AND ing_in_cod = ''
					$updateCI=$sql->update()
					->table('concepto_ingreso')
					->set(
						array(
							'coi_do_imp'=>$totalConcepto,
							'coi_do_subtot'=>$totalConcepto
						)
					)
					->where(
						array(
							'con_in_cod'=>$params['co_conceptoId'],
							'ing_in_cod'=>$dataIngreso['idIngresoGrupoUnidad']
						)
					);
					$statementUpdate=$sql->prepareStatementForSqlObject($updateCI);
					$rsUpdate=$statementUpdate->execute()->count();

					if($rsUpdate>0){
						//update total grupo unidad
						$this->updateTotalGrupoUnidad($params['idUnidadPadre']);
						$response = array('response'=>'success','message'=>'Concepto actualizado con éxito.','tipo'=>'informativo');
					}
					
				}
			}
			
		}else{
			$response = array('response'=>'error','message'=>'Ocurrió un error desconocido en el servidor al intentar actualizar el registro.','tipo'=>'advertencia');
		}

		return $response;
	}

	private function getTotalConceptoPorIdGrupoUnidad($idGrupoUnidad, $idConcepto)
	{
		$sql=new Sql($this->adapter);
		$adapter=$this->getAdapter();
		$select=$sql->select()
			->from('grupo_unidad_concepto')
			->columns(
				array(
					'idGrupoUnidad'=>'grup_in_cod',
					'idConcepto'=>'con_in_cod',
					'total'=>'guc_vc_total'
				)
			)
			->where(
				array(
					'grup_in_cod'=>$idGrupoUnidad
				)
			);
		$sqlString=$sql->buildSqlString($select);
		$rsSelect=$adapter->query($sqlString,$adapter::QUERY_MODE_EXECUTE)->toArray();
		foreach($rsSelect as $grupoUnidadConcepto){
			if($grupoUnidadConcepto['idConcepto']==$idConcepto){
				return $grupoUnidadConcepto['total'];
			}
		}
	}

	private function getGridRowTotales($tipoFila){
		$adapter=$this->getAdapter();
		$sql=new Sql($adapter);
		$primeraColumna=null;
		if($tipoFila=='TOTAL EMITIDO'){
			$primeraColumna='TE';
			$select=$sql->select()
				->from(array('coi'=>'concepto_ingreso'))
				->columns(array(
							'mes'=>new Expression('MONTH(coi.coi_da_fecemi)'),
							'totalMes'=>new Expression('SUM(coi.coi_do_subtot)') 
				))
				->join(array('ing'=>'ingreso'),'coi.ing_in_cod=ing.ing_in_cod',array())
				->join(array('uni'=>'unidad'),'uni.uni_in_cod=ing.uni_in_cod',array())
				->where(array('uni.edi_in_cod'=>$this->edificioId,
							'YEAR(coi.coi_da_fecemi)='.$this->yearSelected))
				->group('mes');
		}else if($tipoFila=='T. COBRADO'){
			$select=$sql->select()
				->from(array('ipa'=>'ingreso_parcial'))
				->columns(array(
						'mes'=>new Expression('MONTH(ipa.ipa_da_fecemi)'),
						'totalMes'=>new Expression('SUM(ipa.ipa_do_imp)') 
				))
				->join(array('ing'=>'ingreso'),'ipa.ing_in_cod=ing.ing_in_cod',array())
				->join(array('uni'=>'unidad'),'uni.uni_in_cod=ing.uni_in_cod',array())
				->where(array('uni.edi_in_cod'=>$this->edificioId,
							'YEAR(ipa.ipa_da_fecemi)='.$this->yearSelected))
				->group('mes');
		}else if($tipoFila=='INGRESO BANCARIO'){
			$select=$sql->select()
				->from(array('ipa'=>'ingreso_parcial'))
				->columns(array(
						'mes'=>new Expression('MONTH(ipa.ipa_da_fecpag)'),
						'totalMes'=>new Expression('SUM(ipa.ipa_do_impban)') 
				))
				->join(array('ing'=>'ingreso'),'ipa.ing_in_cod=ing.ing_in_cod',array())
				->join(array('uni'=>'unidad'),'uni.uni_in_cod=ing.uni_in_cod',array())
				->where(array('uni.edi_in_cod'=>$this->edificioId,
							'YEAR(ipa.ipa_da_fecpag)='.$this->yearSelected))
				->group('mes');
		}

		$sqlString=$sql->buildSqlString($select);
		$rsSelect=$adapter->query($sqlString,$adapter::QUERY_MODE_EXECUTE)->toArray();

		$rowGrid=array($primeraColumna,$tipoFila);
		$totalAnual=0;
		$rsIndex=0;
		for($i=1;$i<=12;$i++){
			$presupuestoMes=isset($rsSelect[$rsIndex]['mes'])?$rsSelect[$rsIndex]['mes']:null;
			if($i==(int)$presupuestoMes){
				$rowGrid[]=number_format($rsSelect[$rsIndex]['totalMes'],2,'.',',');
				$totalAnual+=$rsSelect[$rsIndex]['totalMes'];
				$rsIndex++;
			}else{
				$rowGrid[]='';
			}	
		}
		$rowGrid[]=number_format($totalAnual,2,'.',',');
		return $rowGrid;
	}

	private function getGridRowTotalesPendienteDeCobro($rowTotalEmitido, $rowTotalCobrado){
		$totalAnualPendienteCobro=0;
		$rowGrid=array(null,'T. PENDIENTE');
		/*
		Iniciamos la variable $i en 2 ya que las 2 primeras columnas de la matriz no se tomaran en cuenta..
		*/
		for($i=2;$i<=13;$i++){
			if(!empty($rowTotalEmitido[$i])){
				$mesTotalEmitido=(float)str_replace(',', '', $rowTotalEmitido[$i]);
				$mesTotalCobrado=(float)str_replace(',','',$rowTotalCobrado[$i]);
				$mesTotalPendienteDeCobro=($mesTotalEmitido - $mesTotalCobrado);
				
				$rowGrid[]=number_format($mesTotalPendienteDeCobro,2,'.',',');
				$totalAnualPendienteCobro+=$mesTotalPendienteDeCobro;
			}else{
				$rowGrid[]='';
			}
		}
		$rowGrid[]=number_format($totalAnualPendienteCobro,2,'.',',');
		return $rowGrid;
	}


	private function getGridRowEmisionesPorUnidad($unidadId,$unidadNombre){
		$adapter=$this->getAdapter();
		$sql=new Sql($this->adapter);
		$select=$sql->select()
				->from(array('coi'=>'concepto_ingreso'))
				->columns(array(
							'mes'=>new Expression('MONTH(coi.coi_da_fecemi)'),
							'totalMes'=>new Expression('SUM(coi.coi_do_subtot)'),
				))
				->join(array('ing'=>'ingreso'),'coi.ing_in_cod=ing.ing_in_cod',array('id'=>'ing_in_cod','estado'=>'ing_in_est'))
				->where(array('ing.uni_in_cod'=>$unidadId,
							'YEAR(coi.coi_da_fecemi)='.$this->yearSelected))
				->group('mes');

		$sqlString=$sql->buildSqlString($select);
		
		$rsSelect=$adapter->query($sqlString,$adapter::QUERY_MODE_EXECUTE)->toArray();
		
		$rowGrid=array($unidadId,$unidadNombre);
		$totalAnual=0;
		$rsIndex=0;
		for($i=1;$i<=12;$i++){
			$presupuestoMes=isset($rsSelect[$rsIndex]['mes'])?$rsSelect[$rsIndex]['mes']:null;
			if($i==(int)$presupuestoMes){
				$rowGrid[]=$rsSelect[$rsIndex]['id'].'|'.number_format($rsSelect[$rsIndex]['totalMes'],2,'.',',').'|'.$rsSelect[$rsIndex]['estado'];
				$totalAnual+=$rsSelect[$rsIndex]['totalMes'];
				$rsIndex++;
			}else{
				$rowGrid[]='';
			}	
		}
		$rowGrid[]=number_format($totalAnual,2,'.',',');
		return $rowGrid;
	}

	public function detallesDeUnidad($unidadId){

		$sql=new Sql($this->adapter);
		$select=$sql->select()
			->from(array('uni'=>'unidad'))
			->columns(
				array(
					'propietarioId'=>'uni_in_pro',
					'residenteId'=>'uni_in_pos',
					'unidadNombre'=>new Expression("CONCAT(uni_vc_tip,' ',uni_vc_nom)"),
					'deudaTotalUnidad'=>'uni_do_deu',
					'codigoUltimoIP'=>'uni_in_ultingpar'
				)
			)
			->where(array('uni_in_cod'=>$unidadId));
		$statement=$sql->prepareStatementForSqlObject($select);
		$rowUnidad=$statement->execute()->current();
		$rowUnidad['propietario']=$this->getNombreUsuario($rowUnidad['propietarioId']);
		$rowUnidad['residente']=$this->getNombreUsuario($rowUnidad['residenteId']);
		return $rowUnidad;
	}

	public function detallesDeEmision($ingresoId){
		
		$rowIngreso=$this->getRowIngreso($ingresoId);

		//el número del mes funciona como indice en el vector $this->nombreMes[], esto para optener el nombre del mes.
		$indiceMes=(intval(date('m',strtotime($rowIngreso['fechaEmi']))) -1);
		$year=date('Y',strtotime($rowIngreso['fechaEmi']));
		$rowIngreso['mes']=$this->nombreMes[$indiceMes]." ".$year;
		$rowIngreso['fechaEmi']=date('d-m-Y',strtotime($rowIngreso['fechaEmi']));
		$rowIngreso['fechaVence']=date('d-m-Y',strtotime($rowIngreso['fechaVence']));
		$rowIngreso['propietario']=$this->getNombreUsuario($rowIngreso['propietarioId']);
		$rowIngreso['residente']=$this->getNombreUsuario($rowIngreso['residenteId']);

		$totalEmision=$this->getSumConceptosDeIngreso($ingresoId);
		$totalPagado=$this->getSumIngresoParcial($ingresoId);
		$debe=$totalEmision - $totalPagado;

		$rowIngreso['totalMesEmision']=number_format($totalEmision,2,'.',',');
		$rowIngreso['totalMesPagado']=number_format($totalPagado,2,'.',',');
		$rowIngreso['debeMes']=number_format($debe,2,'.',',');

		return $rowIngreso;
	}

	private function getRowIngreso($ingresoId){
		$sql=new Sql($this->adapter);
		$select=$sql->select()
			->from(array('ing'=>'ingreso'))
			->columns(
				array(
					'fechaEmi'=>'ing_da_fecemi',
					'fechaVence'=>'ing_da_fecven',
					'serie'=>'ing_in_nroser',
					'nroDoc'=>'ing_in_nrodoc',
					'estado'=>'ing_in_est',
				)
			)
			->join(
				array('uni'=>'unidad'),'uni.uni_in_cod=ing.uni_in_cod',
				array('unidadId'=>'uni_in_cod',
					'propietarioId'=>'uni_in_pro',
					'residenteId'=>'uni_in_pos',
					'unidadNombre'=>new Expression("CONCAT(uni_vc_tip,' ',uni_vc_nom)"),
					'deudaTotalUnidad'=>'uni_do_deu'
					)
			)
			->where(array('ing.ing_in_cod'=>$ingresoId));
		$statement=$sql->prepareStatementForSqlObject($select);
		$rowIngreso=$statement->execute()->current();
		return $rowIngreso;
	}


	private function getNombreUsuario($usuarioId){

		if(empty($usuarioId)){
			//return string vacío si no existe usuarioId.
			return '';
		}
		$sql=new Sql($this->adapter);
		$select=$sql->select()
			->from('usuario')
			->columns(array('nombre'=>'usu_vc_nom','apellido'=>'usu_vc_ape','tipoPersona'=>'usu_ch_tip'))
			->where(array('usu_in_cod'=>$usuarioId));
		$statement=$sql->prepareStatementForSqlObject($select);
		$rowUsuario=$statement->execute()->current();
		
		$nombre='';
		if($rowUsuario['tipoPersona']=='PN'){
			$nombre=$rowUsuario['nombre']." ".$rowUsuario['apellido'];
		}else{
			$nombre=$rowUsuario['apellido'];
		}
		return $nombre;
	}

	private function getSumConceptosDeIngreso($ingresoId){
		$sql=new Sql($this->adapter);

		$selectSumCI=$sql->select()
			->from(array('coi'=>'concepto_ingreso'))
			->columns(array('total'=>new Expression('SUM(coi.coi_do_subtot)')))
			->where(array('coi.ing_in_cod'=>$ingresoId));
		
		$statement=$sql->prepareStatementForSqlObject($selectSumCI);
		$rowSumConceptoIngreso=$statement->execute()->current();

		return $rowSumConceptoIngreso['total'];
	}

	public function conceptosDeIngreso($params){
		$ingresoId=$params['ingresoId'];

		$adapter=$this->getAdapter();
		$sql=new Sql($adapter);

		$select=$sql->select()
			->from(array('coi'=>'concepto_ingreso'))
			->columns(array('conceptoId'=>'con_in_cod','total'=>'coi_do_subtot','nota'=>'coi_te_com'))
			->join(array('con'=>'concepto'),'con.con_in_cod=coi.con_in_cod',array('concepto'=>'con_vc_des'))
			->where(array('coi.ing_in_cod'=>$ingresoId));
		$selectString=$sql->buildSqlString($select);
		$rsConceptos=$adapter->query($selectString,$adapter::QUERY_MODE_EXECUTE)->toArray();

		$totalPagado=$this->getSumConceptosDeIngreso($ingresoId);
		return array(
			"rows"=>$rsConceptos,
			'total'=>array(
				'label'=>'Total Emisión',
				'SumTotal'=>$totalPagado
			)
		);
	}


	private function getSumIngresoParcial($ingresoId){
		$sql=new Sql($this->adapter);
		$selectSumIP=$sql->select()
		->from('ingreso_parcial')
		->columns(array('totalPagado'=>new Expression('SUM(ipa_do_imp)')))
		->where(array('ing_in_cod'=>$ingresoId));

		$statement=$sql->prepareStatementForSqlObject($selectSumIP);
		$rowSumIngresoParcial=$statement->execute()->current();
		$totalPagado=$rowSumIngresoParcial['totalPagado'];
		return $totalPagado;
	}

	public function getAllIngresosParcialesByIngreso($params){
		$adapter=$this->getAdapter();
		$sql=new Sql($adapter);

		$ingresoId=$params['ingresoId'];

		$select=$sql->select()
			->from(array('ipa'=>'ingreso_parcial'))
			->columns(array(
				'id'=>'ipa_in_cod',
				'fechaPago'=>'ipa_da_fecpag',
				'tipoDoc'=>'ipa_vc_tipdoc',
				'nroOperacion'=>'ipa_vc_numope',
				'banco'=>'ipa_vc_ban',
				'importe'=>'ipa_do_imp',
				'obs'=>'ipa_te_obs'
				))
			->where(array('ing_in_cod'=>$ingresoId));
		$selectString=$sql->buildSqlString($select);
		$rs=$adapter->query($selectString,$adapter::QUERY_MODE_EXECUTE)->toArray();

		$totalPagado=$this->getSumIngresoParcial($ingresoId);

		return array(
			"rows"=>$rs,
			'total'=>array(
				'label'=>'Total Pagado',
				'sumImporte'=>$totalPagado
			)
		);
	}


	public function getAllIngresosParcialesByMes($params){
		$adapter=$this->getAdapter();
		$sql=new Sql($adapter);
		$edificioId=$params['edificioId'];
		$year=isset($params['year'])?$params['year']:'0';
		$mes=isset($params['mes'])?$params['mes']:'00';
		
		$select=$sql->select()
			->from(array('ipa'=>'ingreso_parcial'))
			->columns(array(
				'id'=>'ipa_in_cod',
				'fechaPago'=>'ipa_da_fecpag',
				'tipoDoc'=>'ipa_vc_tipdoc',
				'nroOperacion'=>'ipa_vc_numope',
				'banco'=>'ipa_vc_ban',
				'importe'=>'ipa_do_imp',
				'obs'=>'ipa_te_obs',
				))
			->join(array('ing'=>'ingreso'),'ing.ing_in_cod=ipa.ing_in_cod')
			->join(array('uni'=>'unidad'),'uni.uni_in_cod=ing.uni_in_cod',array('unidadNombre'=>new Expression("CONCAT(uni_vc_tip,' ',uni_vc_nom)")))
			->where(array('uni.edi_in_cod'=>$edificioId,
					'YEAR(ipa.ipa_da_fecpag)='.$year,
					'MONTH(ipa.ipa_da_fecpag)='.$mes))->order('fechaPago','asc');
		$selectString=$sql->buildSqlString($select);
		$rsIngresosParciales=$adapter->query($selectString,$adapter::QUERY_MODE_EXECUTE)->toArray();

		$response=array();
		$sumImporte=0;
		if(!empty($rsIngresosParciales)){
			$i=0;

			foreach ($rsIngresosParciales as $key => $value){
				$response['rows'][$i]['id']=$value['id'];
				$response['rows'][$i]['cell']=array(date('d-m',strtotime($value['fechaPago'])),$value['unidadNombre'],$value['importe'],$value['nroOperacion'],$value['banco'],$value['obs']);
				$sumImporte+=$value['importe'];
				$i++;
			}
		}
		$response['userdata']=array('Importe'=>$sumImporte);
		return $response;
	}



	public function updateIngresoParcial($params){
		$sql=new Sql($this->adapter);

		$dataUpdateIngresoParcial=array(
			'ipa_vc_numope'=>$params['textNroOperacion'],
			'ipa_da_fecpag'=>date('Y-m-d',strtotime($params['textFechaPago'])),
			'ipa_vc_tipdoc'=>$params['selTipoDoc'],
			'ipa_vc_ban'=>$params['selBanco'],
		);

		$update=$sql->update()
		->table('ingreso_parcial')->set($dataUpdateIngresoParcial)
		->where(array('ipa_in_cod'=>$params['id']));
		$statement=$sql->prepareStatementForSqlObject($update);
		$rs=$statement->execute()->count();

		if($rs>0){
			/////////save auditoria////////
			$params['tabla']='Ingreso Parcial';
			$params['numRegistro']=$params['id'];
			$params['accion']='Editar';
			$params['comNumRegistro']='';
			$this->saveAuditoria($params);
			///////////////////////////////
			return array(
				'tipo'=>'informativo',
				'mensaje'=>'Pago actualizado con éxito.'
			);
		}
		return array(
			'tipo'=>'advertencia',
			'mensaje'=>'El registro no sufrió modificaciones.'
		);
	}


	/*
	* 	Nombre de funcion: ingresoPendientePagoPorUnidad(unidadId);
	*
	*	Consulta ingresos por periodo de una unidad cuyo estado este pendiente de pago (1,2).
		Luego por cada ingreso calcula el total pagado y el total y el total a pagar.
	*	Retorna una array( filas= {Mes, totalEmision, totalPagado, Debe}, 
						 	totales{label,sumEmision,sumPagado,sumDebe}
						 );
	*/
	public function ingresoPendientePagoPorUnidad($unidadId){
		$adapter=$this->getAdapter();
		$sql=new Sql($adapter);

		$select=$sql->select()
			->from(array('coi'=>'concepto_ingreso'))
			->columns(
				array(
					'ingresoId'=>'ing_in_cod',
					'mes'=>new Expression('MONTH(ing.ing_da_fecemi)'),
					'year'=>new Expression('YEAR(ing.ing_da_fecemi)'),
					'totalEmision'=>new Expression('SUM(coi_do_subtot)')
				)
			)
			->join(array('ing'=>'ingreso'),'ing.ing_in_cod=coi.ing_in_cod',array())
			->where(array('ing.uni_in_cod'=>$unidadId,'ing.ing_in_est'=>array(1,2)))
			->group(array('mes','year'))
			->order(array('year asc','mes asc'));
		$selectString=$sql->buildSqlString($select);
		$rsIngresosPendientePago=$adapter->query($selectString,$adapter::QUERY_MODE_EXECUTE)->toArray();

		$items=array();
		$totales=array();

		if(!empty($rsIngresosPendientePago)){
			$sumEmision=0;
			$sumPagado=0;
			$sumDebe=0;
			foreach ($rsIngresosPendientePago as $key => $value){
				$totalEmision=$value['totalEmision'];
				$totalPagado=$this->getSumIngresoParcial($value['ingresoId']);
				$debe=$totalEmision - $totalPagado;

				$sumEmision+=$totalEmision;
				$sumPagado+=$totalPagado;
				$sumDebe+=$debe;

				$indiceMes=(intval($value['mes']) -1);
				$items[]=array(
					'ingresoId'=>$value['ingresoId'],
					'mes'=>$this->nombreMes[$indiceMes].' '.$value['year'],
					'totalEmision'=>number_format($totalEmision,2,'.',','),
					'totalPagado'=>number_format($totalPagado,2,'.',','),
					'debe'=>number_format($debe,2,'.',',')
				);
			}
			$totales=array(
				'ingresoId'=>'null',
				'label'=>'Totales',
				'sumEmision'=>number_format($sumEmision,2,'.',','),
				'sumPagado'=>number_format($sumPagado,2,'.',','),
				'sumDebe'=>number_format($sumDebe,2,'.',','),
			);
			$response=array('rows'=>$items,'total'=>$totales);
		}else{
			$response=array('rows'=>array(),'total'=>array('sumDebe'=>0));
		}
		
		return $response;
	}

	/*
	*	Realiza los pagos desde la deuda mas antigua hasta donde alcance el importe ingresado.
	*/
	public function addPagoEnDeudaPendiente($params){

		$sql=new Sql($this->adapter);

		$unidadId=(int)$params['unidadId'];
		$listIngresosPP=$this->ingresoPendientePagoPorUnidad($unidadId);

		$importe=$params['textImporte'];
		
		if(!empty($listIngresosPP['rows'])){
			$importeBanco=$importe;
			$connectionDb=null;
			try {

				$connectionDb=$this->getAdapter()->getDriver()->getConnection();
				$connectionDb->beginTransaction();

				$importeCodigo=0;
				foreach ($listIngresosPP['rows'] as $key => $value){
					
					if($importe<=0){
						break;
					}
					$ingresoId=(float)str_replace(',', '', $value['ingresoId']);
					$totalEmision=(float)str_replace(',', '', $value['totalEmision']);
					$totalPagado=(float)str_replace(',', '', $value['totalPagado']);
					$debe=str_replace(',','',$value['debe']);
					
					$rowDetalleIngreso=$this->detallesDeEmision($ingresoId);
					$saldoUnidad=(float)str_replace(',','',$rowDetalleIngreso['deudaTotalUnidad']);
					$nuevoSaldoUnidad=$saldoUnidad;

					$dataInsertPago=array(
						'ing_in_cod'=>$ingresoId,
						'usu_in_cod'=>$params['userId'],
						'ipa_in_nroser'=>$rowDetalleIngreso['serie'],
						'ipa_in_nrodoc'=>$rowDetalleIngreso['nroDoc'],
						'ipa_vc_numope'=>$params['textNroOperacion'],
						'ipa_vc_tipdoc'=>$params['selTipoDoc'],
						'ipa_vc_ban'=>$params['selBanco'],
						'ipa_da_fecemi'=>date('Y-m-d',strtotime($rowDetalleIngreso['fechaEmi'])),
						'ipa_da_fecven'=>date('Y-m-d',strtotime($rowDetalleIngreso['fechaVence'])),
						'ipa_da_fecpag'=>date('Y-m-d',strtotime($params['textFechaPago'])),
						'ipa_do_int'=>'0.00',
						'ipa_te_obs'=>$params['taObservacion'],
						'ipa_in_codimp'=>$importeCodigo,
					);
					if((string)$importe==$debe || (string)$importe>$debe){
						$importe=(float)str_replace(',', '', number_format($importe,2,'.',','));
						$importe=($importe - (float)$debe);
						
						$nuevoSaldoUnidad=$saldoUnidad-$debe;

						$dataInsertPago['ipa_do_tot']=$debe;
						$dataInsertPago['ipa_do_imp']=$debe;
						$dataInsertPago['ipa_do_sal']=0;
						$dataInsertPago['ipa_do_impban']=$importeBanco;

						$dataUpdateIngreso=array('ing_do_sal'=>0,'ing_in_est'=>0);
					}else{
						
						$pendientepago=$debe;
						$debe-=$importe;
						$nuevoSaldoUnidad=$saldoUnidad-$importe;

						$dataInsertPago['ipa_do_tot']=$pendientepago;
						$dataInsertPago['ipa_do_imp']=$importe;
						$dataInsertPago['ipa_do_sal']=$debe;
						$dataInsertPago['ipa_do_impban']=$importeBanco;
						$importe=0;

						$dataUpdateIngreso=array('ing_do_sal'=>$debe,'ing_in_est'=>2);
					}

					//1.- insertando ingreso parcial
						$insertPago=$sql->insert('ingreso_parcial')->values($dataInsertPago);
						$stringInsertPago=$sql->buildSqlString($insertPago);
						$connectionDb->execute($stringInsertPago);
						$lastIngresoParcial=$connectionDb->getLastGeneratedValue();

						//Actualizar codigo de importe.
						if($importeCodigo==0){
							$updateIngresoParcial=$sql->update()
							->table('ingreso_parcial')->set(array('ipa_in_codimp'=>$lastIngresoParcial))
							->where(array('ipa_in_cod'=>$lastIngresoParcial));
							$stringUpdateIP=$sql->buildSqlString($updateIngresoParcial);
							$connectionDb->execute($stringUpdateIP);
							$importeCodigo=$lastIngresoParcial;
						}


					//2.- actualizando ingreso
						$updateIngreso=$sql->update()
						->table('ingreso')->set($dataUpdateIngreso)
						->where(array('ing_in_cod'=>$ingresoId));
						$stringUpdateIngreso=$sql->buildSqlString($updateIngreso);
						$connectionDb->execute($stringUpdateIngreso);

					//3.- actualizando la unidad
						$dataUpdateUnidad=array('uni_do_deu'=>$nuevoSaldoUnidad,'uni_in_ultingpar'=>$lastIngresoParcial);
						$updateUnidad=$sql->update()
						->table('unidad')->set($dataUpdateUnidad)
						->where(array('uni_in_cod'=>$unidadId));
						$stringUpdateUnidad=$sql->buildSqlString($updateUnidad);
						$connectionDb->execute($stringUpdateUnidad);

					$importeBanco=0; //el importeBanco se ingresa una unica vez en el forech.
				}

				//actualizamos el saldo a favor si lo tiene.
				if($importe>0){
					$saldoUnidad=$importe * (-1);
					$updateUnidad=$sql->update()
					->table('unidad')->set(array('uni_do_deu'=>$saldoUnidad))
					->where(array('uni_in_cod'=>$unidadId));
					$stringUpdateUnidad=$sql->buildSqlString($updateUnidad);
					$connectionDb->execute($stringUpdateUnidad);
				}

				$connectionDb->commit();
				/////////save auditoria////////
				$params['tabla']='Ingreso Parcial';
				$params['numRegistro']=$lastIngresoParcial;
				$params['accion']='Registro de Pago - Total';
				$params['comNumRegistro']='';
				$this->saveAuditoria($params);
				///////////////////////////////
				return array(
					'tipo'=>'informativo',
					'mensaje'=>'Pago registrado con éxito'
				);
			} catch (\Exception $e) {
				$connectionDb->rollback();
				throw $e;
			}
		}
	}

	public function addPagoEnIngresoSeleccionado($params){

		$ingresoId=$params['ingresoId'];

		$importe=$params['textImporte'];
		if($importe<=0){
			return array('tipo'=>'error','mensaje'=>'Ingresar un importe mayor a S/ 0.00');
		}

		$rowDetalleIngreso=$this->detallesDeEmision($ingresoId);
		$unidadId=(int)$rowDetalleIngreso['unidadId'];
		$deudaTotalUnidad=(float)str_replace(',','',$rowDetalleIngreso['deudaTotalUnidad']);
		$deudaIngreso=(float)str_replace(',', '', $rowDetalleIngreso['debeMes']);
		
		if($deudaIngreso==0){
			return array('tipo'=>'error','mensaje'=>'No se permite registrar pagos en este ingreso');
		}

		if($importe<=$deudaIngreso){
			$deudaIngreso-=$importe;
			$estadoIngreso=($deudaIngreso==0)? 0:2;
			$deudaTotalUnidad-=$importe;

			$dataInsertPago=array(
				'ing_in_cod'=>$ingresoId,
				'usu_in_cod'=>$params['userId'],
				'ipa_in_nroser'=>$rowDetalleIngreso['serie'],
				'ipa_in_nrodoc'=>$rowDetalleIngreso['nroDoc'],
				'ipa_vc_numope'=>$params['textNroOperacion'],
				'ipa_vc_tipdoc'=>$params['selTipoDoc'],
				'ipa_vc_ban'=>$params['selBanco'],
				'ipa_da_fecemi'=>date('Y-m-d',strtotime($rowDetalleIngreso['fechaEmi'])),
				'ipa_da_fecven'=>date('Y-m-d',strtotime($rowDetalleIngreso['fechaVence'])),
				'ipa_da_fecpag'=>date('Y-m-d',strtotime($params['textFechaPago'])),
				'ipa_do_int'=>'0.00',
				'ipa_te_obs'=>$params['taObservacion'],
				'ipa_do_tot'=>$importe,
				'ipa_do_imp'=>$importe,
				'ipa_do_sal'=>$deudaIngreso,
				'ipa_do_impban'=>$importe,
				'ipa_in_codimp'=>0
			);

			$sql=new Sql($this->adapter);
			$connectionDb=$this->getAdapter()->getDriver()->getConnection();
			$connectionDb->beginTransaction();

			try {
				//1.- insertando ingreso parcial
					$insertPago=$sql->insert('ingreso_parcial')->values($dataInsertPago);
					$stringInsertPago=$sql->buildSqlString($insertPago);
					$connectionDb->execute($stringInsertPago);
					$lastIngresoParcial=$connectionDb->getLastGeneratedValue();

					//Actualizar codigo de importe.
						if($lastIngresoParcial>0){
							$updateIngreso=$sql->update()
							->table('ingreso_parcial')->set(array('ipa_in_codimp'=>$lastIngresoParcial))
							->where(array('ipa_in_cod'=>$lastIngresoParcial));
							$statementUpdateIP=$sql->prepareStatementForSqlObject($updateIngreso);
							$statementUpdateIP->execute();
						}

				//2.- actualizando ingreso
					$dataUpdateIngreso=array('ing_do_sal'=>$deudaIngreso,'ing_in_est'=>$estadoIngreso);
					$updateIngreso=$sql->update()
					->table('ingreso')->set($dataUpdateIngreso)
					->where(array('ing_in_cod'=>$ingresoId));
					$stringUpdateIngreso=$sql->buildSqlString($updateIngreso);
					$connectionDb->execute($stringUpdateIngreso);

				//3.- actualizando la unidad
					$dataUpdateUnidad=array('uni_do_deu'=>$deudaTotalUnidad,'uni_in_ultingpar'=>$lastIngresoParcial);
					$updateUnidad=$sql->update()
					->table('unidad')->set($dataUpdateUnidad)
					->where(array('uni_in_cod'=>$unidadId));
					$stringUpdateUnidad=$sql->buildSqlString($updateUnidad);
					$connectionDb->execute($stringUpdateUnidad);
				$connectionDb->commit();
				/////////save auditoria////////
				$params['tabla']='Ingreso Parcial';
				$params['numRegistro']=$lastIngresoParcial;
				$params['accion']='Registro de Pago - Mensual';
				$params['comNumRegistro']='';
				$this->saveAuditoria($params);
				///////////////////////////////
				return array(
					'tipo'=>'informativo',
					'mensaje'=>'Pago registrado con éxito'
				);
			 }catch (Exception $e) {
				$connectionDb->rollback();
				throw $e;
			}
		}else{
			return array('tipo'=>'error','mensaje'=>'No se permite registrar pagos adelantados en esta opción');
		}
	}

	/*	
	*	Función nombre : deletePagos()
	*	Eliminar ingresos parciales  por codigo de importe validando si tiene saldo a favor la unidad.
	*/
	public function deletePagos($params){
		
		$sql=new Sql($this->adapter);

		//Select row ingreso parcial
		$selectRowIP=$sql->select()
			->from(array('ipa'=>'ingreso_parcial'))
			->columns(array('ingresoId'=>'ing_in_cod','fechaEmi'=>'ipa_da_fecemi','importeCodigo'=>'ipa_in_codimp'))
			->join(array('ing'=>'ingreso'),'ing.ing_in_cod=ipa.ing_in_cod')
			->join(array('uni'=>'unidad'),'uni.uni_in_cod=ing.uni_in_cod',array('deudaTotalUnidad'=>'uni_do_deu'))
			->where(array('ipa_in_cod'=>$params['id']));
		$statementRowIP=$sql->prepareStatementForSqlObject($selectRowIP);
		$rowIngresoParcial=$statementRowIP->execute()->current();

		//Verificar si esta unidad tiene saldo a favor (-0) para aplicar la validación.
		$deudaUnidad=(float)$rowIngresoParcial['deudaTotalUnidad'];

		if($deudaUnidad<0){
			//Comprobar si existe un ingreso parcial registrado con codigo de importe diferente 
			//y mayor al que se pretende eliminar.
			$selectValidacion=$sql->select()
				->from(array('ipa'=>'ingreso_parcial'))
				->columns(array('count'=>new Expression('COUNT(*)')))
				->join(array('ing'=>'ingreso'),'ing.ing_in_cod=ipa.ing_in_cod',array())
				->where(array('ing.uni_in_cod'=>$params['unidadId'],
							"ipa.ipa_in_cod>".$params['id']));
			$selectValidacion->where->notEqualTo('ipa.ipa_in_codimp',$rowIngresoParcial['importeCodigo']);

			$statementValidacion=$sql->prepareStatementForSqlObject($selectValidacion);
			$countValid=$statementValidacion->execute()->current()['count'];

			if($countValid>0){
				return array(
					'tipo'=>'error',
					'mensaje'=>'No es posible eliminar el pago seleccionado por que la unidad cuenta con saldo a favor, eliminar el pago mas reciente.'
				);
			}
		}

		//Consulta ingresos parciales que seran eliminados.
		$selectIP=$sql->select()
			->from(array('ipa'=>'ingreso_parcial'))
			->columns(array('ipa_in_cod','ing_in_cod'))
			->where(array('ipa_in_codimp'=>$rowIngresoParcial['importeCodigo']));
		$statementSelectIP=$sql->prepareStatementForSqlObject($selectIP);
		$rsIngresoParcial=$statementSelectIP->execute();
		$resultSet=new ResultSet();
		$resultSet->initialize($rsIngresoParcial);

		$dataIngresosToEliminar=$resultSet->toArray();

		if(!empty($dataIngresosToEliminar)){			
			$connectionDb=$this->getAdapter()->getDriver()->getConnection();
			$connectionDb->beginTransaction();
			try {
				foreach ($dataIngresosToEliminar as $key => $value){
					$ingresoId=$value['ing_in_cod'];

					//Ejecutamos la eliminacion de la fila Ingreso parcial
					$deleteIP=$sql->delete('ingreso_parcial')
					->where(array('ipa_in_codimp'=>$value['ipa_in_cod']));
					$sqlStringDeleteIP=$sql->buildSqlString($deleteIP);
					$connectionDb->execute($sqlStringDeleteIP);

					$totalEmision=$this->getSumConceptosDeIngreso($ingresoId);
					$totalPagado=$this->getSumIngresoParcial($ingresoId);
					$debeEmision=$totalEmision - $totalPagado;

					//Determina el estado de ingreso de acuerdo al a la suma de ingresos parciales vs suma de conceptos de ingreso.
					$estadoIngreso=1; //Estado default cuando no tiene concepto_ingreso (CI).
					if($totalEmision>0){
						$estadoIngreso=$debeEmision<=0 ? 0 :($totalPagado==0 ? 1:2);
					}

					//Actualizando ingreso
					$dataUpdateIngreso=array('ing_do_sal'=>$debeEmision,'ing_in_est'=>$estadoIngreso);
					
					$updateIngreso=$sql->update()
					->table('ingreso')->set($dataUpdateIngreso)
					->where(array('ing_in_cod'=>$ingresoId));
					$stringUpdateIngreso=$sql->buildSqlString($updateIngreso);
					$connectionDb->execute($stringUpdateIngreso);
				}

				//optenemos la sumatoria de todos los ingresos pendientes de pago para actualizar como deuda pendiente de unidad.
				$deudaTotalUnidad=$this->ingresoPendientePagoPorUnidad($params['unidadId'])['total']['sumDebe'];
				$deudaTotalUnidad=str_replace(',', "", $deudaTotalUnidad);

				//3.- actualizando la unidad
					$dataUpdateUnidad=array('uni_do_deu'=>$deudaTotalUnidad,'uni_in_ultingpar'=>0);
					$updateUnidad=$sql->update()
					->table('unidad')->set($dataUpdateUnidad)
					->where(array('uni_in_cod'=>$params['unidadId']));
					$stringUpdateUnidad=$sql->buildSqlString($updateUnidad);
					$connectionDb->execute($stringUpdateUnidad);
					$connectionDb->commit();
					/////////save auditoria////////
					$params['tabla']='Ingreso Parcial';
					$params['numRegistro']=$params['id'];
					$params['accion']='Eliminar';
					$params['comNumRegistro']='';
					$this->saveAuditoria($params);
					///////////////////////////////
					return  array('tipo'=>'informativo','mensaje'=>'Pago eliminado con éxito.');
			} catch (\Exception $e) {
				$connectionDb->rollback();
				throw $e;
			}
		}
		return array('tipo'=>'error','mensaje'=>'Ocurrió un error desconocido en el servidor al intentar eliminar el pago seleccionado.');
	}


	public function getConceptos($params){
		$this->edificioId=$params['edificioId'];
		$this->empresaId=$params['empresaId'];
		$tipo=isset($params['tipo'])?$params['tipo']:'INGRESO'; // @parametro opcional

		$adapter=$this->getAdapter();
		$sql=new Sql($adapter);

		$select=$sql->select()
			->from('concepto')
			->columns(array('id'=>'con_in_cod','descripcion'=>'con_vc_des'))
			->where(
				array(
					'emp_in_cod'=>array(0,$this->empresaId),
					'con_vc_tip'=>$tipo,
					'con_in_est'=>1
				)
			);
		$sqlString=$sql->buildSqlString($select);
		$rsConceptos=$adapter->query($sqlString,$adapter::QUERY_MODE_EXECUTE)->toArray();
		return $rsConceptos;
	}

	public function getConceptoNombre($conceptoId){
		$adapter=$this->getAdapter();
		$sql=new Sql($adapter);
		$select=$sql->select()
			->from('concepto')
			->columns(array('descripcion'=>'con_vc_des'))
			->where(
				array(
					'con_in_cod'=>$conceptoId,
				)
			);
		$statement=$sql->prepareStatementForSqlObject($select);
		$rowConcepto=$statement->execute()->current();

		return $rowConcepto['descripcion'];

	}






	public function registrarIngreso($params){
		$sql=new Sql($this->adapter);

		$currentIdUnidad=$params['unidadId'];
		$fechaEmision=null;
		$fechaVence=null;
		$conceptoId=$params['selConcepto'];
		$totalImporteConcepto=$params['textTotalConceptoIngreso'];
		$usuarioId=$params['userId'];
		$nota=$params['ta_nota'];

		if($params['fechaPerzonalizada']=='false'){
			$fechaEmision=date('Y-m-d',strtotime($params['textFechaEmi_1']));
			$fechaVence=date('Y-m-d',strtotime($params['textFechaVence_1']));
		}else{
			$fechaEmision=date('Y-m-d',strtotime($params['textFechaEmi']));
			$fechaVence=date('Y-m-d',strtotime($params['textFechaVence']));
		}


		$currentRowUnidad=$this->detallesDeUnidad($currentIdUnidad);
		$totalDeudaUnidad=(float)$currentRowUnidad['deudaTotalUnidad'];

		$numeracionDocumento=$this->numeracionDeDocumento($params['edificioId']);

		$ingresoId=null;
		$rowIngresoPrevioReg=$this->getRowIngresoPrevioRegistro($currentIdUnidad,$fechaEmision);
		if(!empty($rowIngresoPrevioReg)){
			/*
			 * Solamente si existe un ingreso previamente registrado con estado [ 1 ]
               podemos añadir conceptos en esta opcion, siempre y cuado no tenga conceptos.
            */
			if($rowIngresoPrevioReg['ing_in_est']==1) {
				$ingresoId=$rowIngresoPrevioReg['ing_in_cod'];
				
				$selectCountCI=$sql->select()->from('concepto_ingreso')
					->columns(array('count'=>new Expression('COUNT(*)')))
					->where(array('ing_in_cod'=>$ingresoId));
				$statementCI=$sql->prepareStatementForSqlObject($selectCountCI);
				$rsCountConceptosDeIngreso=$statementCI->execute()->current()['count'];

				if($rsCountConceptosDeIngreso>0) {
					return array(
						'tipo' => 'error',
						'mensaje' => 'Imposible registrar, ya existe ingreso provisionado en este mes.'
					);
				}
			}else{
				return array(
					'tipo' => 'error',
					'mensaje' => 'Imposible registrar, ya existe un ingreso provisionado en este mes.'
				);
			}
		}else{
			$dataIngreso=array(
				'uni_in_cod'=>$currentIdUnidad,
				'ing_in_usu'=>$currentRowUnidad['propietarioId'],
				'ing_in_resi'=>$currentRowUnidad['residenteId'],
				'ing_da_fecemi'=>$fechaEmision,
				'ing_da_fecven'=>$fechaVence,
				'ing_in_nroser'=>$numeracionDocumento['serie'],
				'ing_in_nrodoc'=>$numeracionDocumento['nroDoc'],
				'ing_in_est'=>1,
			);

			$insertIngreso=$sql->insert('ingreso')->values($dataIngreso);
			$statementInsertIngreso=$sql->prepareStatementForSqlObject($insertIngreso);
			$ingresoId=$statementInsertIngreso->execute()->getGeneratedValue();
		}

		
		if(empty($ingresoId)){
			return array(
				'tipo'=>'error',
				'mensaje'=>'Ocurrió un error desconocido en el servidor al intentar registrar el concepto.'
			);
		}

		$dataConceptoIngreso=array(
			'con_in_cod'=>$conceptoId,
			'ing_in_cod'=>$ingresoId,
			'coi_da_fecemi'=>$fechaEmision,
			'coi_da_fecven'=>$fechaVence,
			'coi_do_imp'=>$totalImporteConcepto,
			'coi_do_subtot'=>$totalImporteConcepto,
			'coi_in_usureg'=>$usuarioId,
			'coi_te_com'=>$nota,
			'coi_in_nroser'=>$numeracionDocumento['serie'],
			'coi_in_nrodoc'=>$numeracionDocumento['nroDoc'],
		);

		$insertConceptoIngreso=$sql->insert('concepto_ingreso')->values($dataConceptoIngreso);
		$sql->prepareStatementForSqlObject($insertConceptoIngreso)->execute();

		$sumTotalConceptoIngreso=$this->getSumConceptosDeIngreso($ingresoId);
		$deudaIngreso=$sumTotalConceptoIngreso;
		/*
        *	Si la unidad tiene saldo a favor y la sumatoria de los conceptos es mayor a (0)
        *	registramos un ingreso parcial.
        */
		if($totalDeudaUnidad<0 && $sumTotalConceptoIngreso>0){

			$rowUltimoIngresoParcial=$this->getRowIngresoParcial($currentRowUnidad['codigoUltimoIP']);
			$totalDeudaUnidad=$totalDeudaUnidad + ($sumTotalConceptoIngreso * 1) ;

			$importe=($totalDeudaUnidad >= 0) ? ($sumTotalConceptoIngreso - $totalDeudaUnidad) : $sumTotalConceptoIngreso;
			$deudaIngreso=$sumTotalConceptoIngreso - $importe;

			//Determina el estado de ingreso
			$estadoIngreso=$deudaIngreso<=0 ? 0 :($importe==0 ? 1:2);
			
			$dataInsertPago=array(
				'ing_in_cod'=>$ingresoId,
				'usu_in_cod'=>$usuarioId,
				'ipa_in_nroser'=>$numeracionDocumento['serie'],
				'ipa_in_nrodoc'=>$numeracionDocumento['nroDoc'],
				'ipa_vc_numope'=>$rowUltimoIngresoParcial['numOperacion'],
				'ipa_vc_tipdoc'=>$rowUltimoIngresoParcial['tipoDoc'],
				'ipa_vc_ban'=>$rowUltimoIngresoParcial['banco'],
				'ipa_da_fecemi'=>$fechaEmision,
				'ipa_da_fecven'=>$fechaVence,
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
		}else{
			$totalDeudaUnidad+=$sumTotalConceptoIngreso;
		}

		//Actualizamos total en ingreso
		$dataUpdateIngreso=array(
			'ing_in_usu'=>$currentRowUnidad['propietarioId'],
			'ing_in_resi'=>$currentRowUnidad['residenteId'],
			'ing_da_fecemi'=>$fechaEmision,
			'ing_da_fecven'=>$fechaVence,
			'ing_do_sal'=>$deudaIngreso,
		);

		if(isset($estadoIngreso)){
			$dataUpdateIngreso['ing_in_est']=$estadoIngreso;
		}
		$updateIngreso=$sql->update()->table('ingreso')
			->set($dataUpdateIngreso)->where(array('ing_in_cod'=>$ingresoId));
		$statementUpdateIngreso=$sql->prepareStatementForSqlObject($updateIngreso);
		$statementUpdateIngreso->execute();

		//Actualizamos la deuda de unidad.
		$dataUpdateUnidad=array('uni_do_deu'=>$totalDeudaUnidad);
		if(isset($lastIngresoParcial)){
			$dataUpdateUnidad['uni_in_ultingpar']=$lastIngresoParcial;
		}
		$updateUnidad=$sql->update()->table('unidad')
			->set($dataUpdateUnidad)->where(array('uni_in_cod'=>$currentIdUnidad));
		$sql->prepareStatementForSqlObject($updateUnidad)->execute();

		//Actualizamos la numeracion de documentos en la tabla edificio.
		$dataNumeracion=array('edi_in_numser'=>$numeracionDocumento['serie'],'edi_in_numdoc'=>$numeracionDocumento['nroDoc']);
		$updateEdificio=$sql->update()->table('edificio')
			->set($dataNumeracion)->where(array('edi_in_cod'=>$params['edificioId']));
		$sql->prepareStatementForSqlObject($updateEdificio)->execute();

		/////////save auditoria////////
		$params['usuarioId']=$params['userId'];
		$params['tabla']='Ingreso';
		$params['numRegistro']=$ingresoId;
		$params['accion']='Guardar';
		$params['comNumRegistro']='';
		$this->saveAuditoria($params);
		///////////////////////////////

		return array(
			'tipo'=>'informativo',
			'mensaje'=>'Registro guardado con éxito.'
		);
	}

	private function getRowIngresoPrevioRegistro($currentIdUnidad,$fechaEmision){
		$mesEmision=date('m',strtotime($fechaEmision));
		$yearEmision=date('Y',strtotime($fechaEmision));

		$sql=new Sql($this->adapter);
		$selectIngreso=$sql->select()
			->from(array('ing'=>'ingreso'))
			->columns(array('ing_in_cod','ing_in_est'))
			->where(array('uni_in_cod'=>$currentIdUnidad,
				"MONTH(ing_da_fecemi)=".$mesEmision,
				"YEAR(ing_da_fecemi)=".$yearEmision));
		$statement=$sql->prepareStatementForSqlObject($selectIngreso);
		$rsIngreso=$statement->execute()->current();

		return $rsIngreso;
	}

	private function numeracionDeDocumento($edificioId){
		$sql=new Sql($this->adapter);
		$selectEdificio=$sql->select()
			->from(array('edi'=>'edificio'))
			->columns(
				array(
					'serie'=>'edi_in_numser',
					'nroDoc'=>'edi_in_numdoc',
				)
			)
			->where(array('edi_in_cod'=>$edificioId));

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
		return $rsEdificio;
	}

	private function getNombreEdificio($edificioId){
		$sql=new Sql($this->adapter);
		$selectEdificio=$sql->select()
			->from(array('edi'=>'edificio'))
			->columns(array('nombre'=>'edi_vc_des'))
			->where(array('edi_in_cod'=>$edificioId));

		$statement=$sql->prepareStatementForSqlObject($selectEdificio);
		$rsEdificio=$statement->execute()->current();

		return $rsEdificio['nombre']; 
	}

	private function getRowIngresoParcial($ingresoparcialId){
		$sql=new Sql($this->adapter);
		$selectIngesoParcial=$sql->select()
			->from(array('ipa'=>'ingreso_parcial'))
			->columns(array(
				'numOperacion'=>'ipa_vc_numope',
				'banco'=>'ipa_vc_ban',
				'fechaPago'=>'ipa_da_fecpag',
				'importeCodigo'=>'ipa_in_codimp',
				'tipoDoc'=>'ipa_vc_tipdoc'))
			->where(array('ipa_in_cod'=>$ingresoparcialId));
		$statement=$sql->prepareStatementForSqlObject($selectIngesoParcial);
		$rsIngreso=$statement->execute()->current();
		return $rsIngreso;
	}




	public function addConceptoIngreso($params){

		$sql=new Sql($this->adapter);
		$ingresoId=isset($params['ingresoId'])?$params['ingresoId']:null;

		//validar que los conceptos de ingreso no se repitan.
		$selectValidar=$sql->select()
			->from('concepto_ingreso')
			->columns(array('count'=>new Expression('COUNT(*)')))
			->where(
				array(
					'con_in_cod'=>$params['selConcepto'],
					'ing_in_cod'=>$ingresoId,
				)
			);
		$statementValidarDuplicado=$sql->prepareStatementForSqlObject($selectValidar);
		$countValid=$statementValidarDuplicado->execute()->current()['count'];
		if($countValid>0){
			return array(
				'tipo'=>'advertencia',
				'mensaje'=>'Imposible registrar el concepto seleccionado por que ya se encuentra registrado.'
			);
		}

		$rowIngreso=$this->getRowIngreso($ingresoId);
		if(!empty($rowIngreso)){
			//validacion de estado (0=Ingreso pagado).
			if($rowIngreso['estado']==0){
				return array(
					'tipo'=>'advertencia',
					'mensaje'=>'Imposible registrar el concepto, el ingreso se encuentra cerrado.'
				);
			}
		}else{
			return array('tipo'=>'error','mensaje'=>'Ocurrió un error desconocido en el servidor al intentar registrar el concepto.');
		}

		$dataConcepto=array(
			'con_in_cod'=>$params['selConcepto'],
			'ing_in_cod'=>$ingresoId,
			'coi_da_fecemi'=>$rowIngreso['fechaEmi'],
			'coi_da_fecven'=>$rowIngreso['fechaVence'],
			'coi_do_imp'=>$params['textTotalConcepto'],
			'coi_do_subtot'=>$params['textTotalConcepto'],
			'coi_in_usureg'=>$params['userId'],
			'coi_te_com'=>$params['ta_nota'],
			'coi_in_nroser'=>$rowIngreso['serie'],
			'coi_in_nrodoc'=>$rowIngreso['nroDoc']
		);

		$insertConcepto=$sql->insert('concepto_ingreso')->values($dataConcepto);
		$statementInsertConcepto=$sql->prepareStatementForSqlObject($insertConcepto);
		$rsInsert=$statementInsertConcepto->execute()->count();

		if($rsInsert>0){
			$rptaRecalcular=$this->recalcularAndActualizarDeuda($rowIngreso['unidadId']);
			if($rptaRecalcular['tipo']=='informativo'){
				/////////save auditoria////////
				$params['tabla']='Concepto Ingreso';
				$params['numRegistro']='';
				$params['comNumRegistro']='con_in_cod:'.$params['selConcepto'].', ing_in_cod:'.$ingresoId;
				$params['accion']='Guardar';
				$this->saveAuditoria($params);
				///////////////////////////////
				return array(
					'tipo'=>'informativo',
					'mensaje'=>'Concepto registrado con éxito.'
				);
			}
			return $rptaRecalcular;
		}

		return array(
			'tipo'=>'error',
			'mensaje'=>'Ocurrió un error desconocido en el servidor al intentar registrar el concepto'
		);
	}

	/*
	 * function updateConceptoIngreso(@params)
	 * Se permite la modificacion del total del concepto, solamente si no hay pagos parcial registrados en dicho ingreso.
	 * */
	public function updateConceptoIngreso($params){
		$sql=new Sql($this->adapter);
		if($this->existeIngresosParciales($params['ingresoId'])){
			return array(
				'tipo'=>'advertencia',
				'mensaje'=>'Imposible actualizar el concepto por que existe pagos registrados.'
			);
		}
		$dataUpdateCI=array(
			'coi_do_imp'=>$params['textTotalConcepto'],
			'coi_do_subtot'=>$params['textTotalConcepto'],
			'coi_te_com'=>$params['ta_nota'],
		);

		$updateCI=$sql->update()
		->table('concepto_ingreso')
		->set($dataUpdateCI)
		->where(
			array(
				'con_in_cod'=>$params['co_conceptoId'],
				'ing_in_cod'=>$params['ingresoId'],
			)
		);
		$statementUpdate=$sql->prepareStatementForSqlObject($updateCI);
		$rsUpdate=$statementUpdate->execute()->count();

		//Recalculamos la deuda si la actualización sufrió cambios.
		if($rsUpdate>0){
			$rptaRecalcular=$this->recalcularAndActualizarDeuda($params['unidadId']);
			if($rptaRecalcular['tipo']=='informativo'){
				/////////save auditoria////////
				$params['tabla']='Concepto Ingreso';
				$params['numRegistro']='';
				$params['comNumRegistro']='con_in_cod:'.$params['co_conceptoId'].', ing_in_cod:'.$params['ingresoId'];
				$params['accion']='Editar';
				$this->saveAuditoria($params);
				///////////////////////////////
				return array(
					'tipo'=>'informativo',
					'mensaje'=>'Concepto actualizado con éxito.'
				);
			}
			return $rptaRecalcular;
		}
		return array ("tipo"=>"advertencia","mensaje"=>"El registro no sufrió modificaciones");
	}

	/*
	 * function updateConceptoIngreso(@params)
	 * Se permite la eliminar del concepto, solamente si no hay pagos parcial registrados en dicho ingreso.
	 * */
	public function deleteConceptoIngreso($params){
		$sql=new Sql($this->adapter);
		$ingresoId=$params['ingresoId'];

		if($this->existeIngresosParciales($ingresoId)){
			return array(
				'tipo'=>'advertencia',
				'mensaje'=>'Imposible eliminar el concepto por que existe pagos registrados.'
			);
		}

		$deleteCI=$sql->delete('concepto_ingreso')
			->where(array('con_in_cod'=>$params['conceptoId'],'ing_in_cod'=>$params['ingresoId']));
		$resultDeleteCI=$sql->prepareStatementForSqlObject($deleteCI)->execute()->count();

		if($resultDeleteCI>0){
			$rptaRecalcular=$this->recalcularAndActualizarDeuda($params['unidadId']);
			if($rptaRecalcular['tipo']=='informativo'){
				/////////save auditoria////////
				$params['tabla']='Concepto Ingreso';
				$params['numRegistro']='';
				$params['comNumRegistro']='con_in_cod:'.$params['conceptoId'].', ing_in_cod:'.$params['ingresoId'];
				$params['accion']='Eliminar';
				$this->saveAuditoria($params);
				///////////////////////////////
				return array(
					'tipo'=>'informativo',
					'mensaje'=>'Concepto eliminado con éxito.'
				);
			}
			return $rptaRecalcular;
		}
		return array ("tipo"=>"advertencia","mensaje"=>"Ocurrió un error desconocido en el servidor al intentar eliminar el concepto.");
	}


	private function existeIngresosParciales($ingresoId){
		$sql=new sql($this->adapter);
		$selectConceptoIngreso=$sql->select()
			->from('ingreso_parcial')
			->columns(array('cantidad'=>new Expression('COUNT(*)')))
			->where(array('ing_in_cod'=>$ingresoId));
		$statement=$sql->prepareStatementForSqlObject($selectConceptoIngreso);
		$rsConceptoIngreso=$statement->execute()->current();
		if($rsConceptoIngreso['cantidad']>0){
			return true;
		}
		return false;
	}

	private function recalcularAndActualizarDeuda($unidadId){
		$sql=new Sql($this->adapter);

		$listIngresosPP=$this->ingresoPendientePagoPorUnidad($unidadId);

		if(!empty($listIngresosPP['rows'])){
			$connectionDb=null;
			try {
				$connectionDb=$this->getAdapter()->getDriver()->getConnection();
				$connectionDb->beginTransaction();

				$deudaTotalUnidad=0;
				foreach ($listIngresosPP['rows'] as $key => $value){
					$ingresoId=$value['ingresoId'];
					$totalEmision=(float)str_replace(',', '', $value['totalEmision']);
					$totalPagado=(float)str_replace(',', '', $value['totalPagado']);
					$debeEmision=(float)str_replace(',','',$value['debe']);
					$deudaTotalUnidad+=$debeEmision;

					$estadoIngreso=1;
					if($totalEmision>0) {
						$estadoIngreso = $debeEmision <= 0 ? 0 : ($totalPagado == 0 ? 1 : 2);
					}

					//Actualizando el ingreso
					$updateIngreso=$sql->update()
						->table('ingreso')->set(array('ing_do_sal'=>$debeEmision,'ing_in_est'=>$estadoIngreso))
						->where(array('ing_in_cod'=>$ingresoId));
					$stringUpdateIngreso=$sql->buildSqlString($updateIngreso);
					$connectionDb->execute($stringUpdateIngreso);
				}

				$dataUpdateUnidad=array('uni_do_deu'=>$deudaTotalUnidad);
				$updateUnidad=$sql->update()
					->table('unidad')->set($dataUpdateUnidad)
					->where(array('uni_in_cod'=>$unidadId));
				$stringUpdateUnidad=$sql->buildSqlString($updateUnidad);
				$connectionDb->execute($stringUpdateUnidad);
				$connectionDb->commit();
				return array('tipo'=>'informativo','mensaje'=>'Registro actualizado con éxito.');
			}catch (\Exception $e) {
				$connectionDb->rollback();
				throw $e;
			}
		}
		return array('tipo'=>'error','mensaje'=>'Ocurrió un error desconocido en el servidor.');
	}


	public function getConceptosExistentesByEmision($params){
		$mes=$params['mes'];
		$year=$params['year'];
		$edificioId=$params['edificioId'];
		$adapter=$this->getAdapter();

		$stringSql="SELECT distinct coi.con_in_cod as id,con_vc_des as descripcion from concepto_ingreso coi 
					INNER JOIN ingreso ing on ing.ing_in_cod=coi.ing_in_cod
					INNER JOIN unidad uni on uni.uni_in_cod=ing.uni_in_cod
					INNER JOIN concepto con on con.con_in_cod=coi.con_in_cod
					WHERE uni.edi_in_cod='$edificioId' 
						and uni.uni_in_pad IS NULL 
						and YEAR(ing.ing_da_fecemi)=$year
						and MONTH(ing.ing_da_fecemi)=$mes";

		$rsConceptos=$adapter->query($stringSql,$adapter::QUERY_MODE_EXECUTE)->toArray();
		return $rsConceptos;
	}

	private function getDetalleEdificio()
	{
		$adapter=$this->getAdapter();
		$stringSql="SELECT edi_vc_numcue as numeroCuenta, edi_vc_des as nombreEdificio FROM edificio WHERE edi_in_cod = '".$this->idEdificio."' ";
		$rsEdificio=$adapter->query($stringSql,$adapter::QUERY_MODE_EXECUTE)->current();
		return $rsEdificio;
	}

	public function crearExcelBcp($params)
	{
		
		$adapter=$this->getAdapter();
		$sql=new Sql($adapter);
		$response=array();

		//$mes=isset($params['mes'])? (($params['mes']<9)?"0".$params['mes']:$params['mes']):'0';
		$mes=$params['mes'];
		$year=$params['year'];
		$representaUnidad=trim($params['representaunidad']);

		$selectUnidad=$sql->select()
		->from(array('uni'=>'unidad'))
		->columns(array('unidadId'=>'uni_in_cod','propietarioId'=>'uni_in_pro','residenteId'=>'uni_in_pos','unidad'=>new Expression("CONCAT(uni_vc_tip,' ',uni_vc_nom)"),'unidadAbreviado'=>'uni_vc_nom', 'codigoPago'=>'uni_vc_codpag'))
		->join(array('ing'=>'ingreso'),'uni.uni_in_cod=ing.uni_in_cod')
		->where(array('edi_in_cod'=>$this->idEdificio,'uni_in_est'=>1,"MONTH(ing_da_fecemi)=".$mes,
				"YEAR(ing_da_fecemi)=".$year));
		$selectUnidad=$sql->buildSqlString($selectUnidad);
		$rowsUnidades=$adapter->query($selectUnidad,$adapter::QUERY_MODE_EXECUTE)->toArray();

		if(count($rowsUnidades)==0){
			$response = array('response'=>'nounidad','message'=>'No se generó ningún archivo con los datos enviados...','tipo'=>'advertencia');
			return $response;
			exit();
		}

		$i=8;

		/**/
		$name = 'RPT_INGRESO-'.time().'.xlsx';
		$file = 'public/temp/reportes/'.$name;

		$objPHPExcel = new \PHPExcel();
		$objSheet = $objPHPExcel->getActiveSheet();
		$phpColor = new \PHPExcel_Style_Color();
		$objSheet->setTitle('INGRESO');

		foreach($rowsUnidades as $unidades)
		{
			
			$fechaEmision=date('d/m/Y',strtotime($unidades['ing_da_fecemi']));
			$fechaMovimiento=date('d/m/Y',strtotime($unidades['ing_da_fecven']));

			$unidadId=$unidades['unidadId'];
			$stringSql="SELECT coi.con_in_cod as id,coi.coi_do_subtot as total,coi.coi_te_com as nota,ing.ing_in_nroser as serie,ing.ing_in_nrodoc as nroDoc, ing.ing_in_est as estadoIngreso 
					from concepto_ingreso coi 
					INNER JOIN ingreso ing on ing.ing_in_cod=coi.ing_in_cod
					INNER JOIN concepto con on con.con_in_cod=coi.con_in_cod
					WHERE ing.uni_in_cod=$unidadId
						and YEAR(ing.ing_da_fecemi)=$year
						and MONTH(ing.ing_da_fecven)=$mes";
			$rsDetallesDeEmision=$adapter->query($stringSql,$adapter::QUERY_MODE_EXECUTE)->toArray();

			switch ($representaUnidad) {
				case 'propietario':
					$depositante=$this->getNombreUsuario($unidades['propietarioId']);
					break;
				case 'residente':
					$depositante=$this->getNombreUsuario($unidades['residenteId']);
					break;
				default:
					return "error";
					break;
			}

			$sumaTotal=0;
			foreach ($rsDetallesDeEmision as $detallesEmision){
				$sumaTotal+=$detallesEmision['total'];
			}

			if($sumaTotal==0)continue;
			
			$indexFila=$i++;

			$objSheet->setCellValue('A'.$indexFila, $unidades['codigoPago']);
			$objSheet->setCellValue('B'.$indexFila, $depositante);
			$objSheet->setCellValue('C'.$indexFila, 'CUOTA MMTO '.strtoupper($this->nombreMesNumero[$mes]).' '.$year);
			$objSheet->setCellValue('D'.$indexFila, $fechaEmision);
			$objSheet->setCellValue('E'.$indexFila, $fechaMovimiento);
			$objSheet->setCellValue('F'.$indexFila, $sumaTotal);

		}

		//title
		$style = array(
	        'alignment' => array(
	            'horizontal' => \PHPExcel_Style_Alignment::HORIZONTAL_CENTER
	        ),
	        'font'  => array(
	        	'size'  => 16
		    )
	    );
	    $objSheet->getStyle("A1:F1")->applyFromArray($style);

		$objSheet->mergeCells('A1:F1');
		$objSheet->setCellValue('A1', 'INGRESO DE '.strtoupper($this->nombreMesNumero[$mes]). ' DEL '.$year);
		$objSheet->getStyle('A1')->getFont()->setBold(true);
		//$objSheet->getStyle('A1:F1')->applyFromArray(array('alignment' => array('horizontal' => \PHPExcel_Style_Alignment::HORIZONTAL_CENTER)));

		$style = array(
	        'alignment' => array(
	            'horizontal' => \PHPExcel_Style_Alignment::HORIZONTAL_LEFT,
	        )
	    );
	    $objSheet->getDefaultStyle()->applyFromArray($style);

		//tools
		$objSheet->getStyle('A10:F10')->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
		$objSheet->getRowDimension('10')->setRowHeight(20);

		//header
		
		$dataEdificio=$this->getDetalleEdificio();

		$objSheet->setCellValue('A3', 'Cuenta del Afiliado');
		$objSheet->setCellValue('B3', $dataEdificio['numeroCuenta']);

		$objSheet->setCellValue('A4', 'Nombre de la Empresa');
		$objSheet->setCellValue('B4', $dataEdificio['nombreEdificio']);

		$objSheet->setCellValue('A5', 'Total de Registros');
		$objSheet->setCellValue('B5', '232');

		//style
		$objSheet->getStyle('A3:A5')->applyFromArray(
		    array(
		        'fill' => array(
		            'type' => \PHPExcel_Style_Fill::FILL_SOLID,
		            'color' => array('rgb' => '004583')
		        ),
		        'font'  => array(
		        	'size'  => 11,
			        'color' => array('rgb' => 'FFFFFF'),
			        'name' => 'Calibri'
			    )
		    )
		);
		/**/

		//column
		$objSheet->setCellValue('A7', 'Código del Depositante');
		$objSheet->setCellValue('B7', 'Nombre del Depositante');
		$objSheet->setCellValue('C7', 'Información de Retorno');
		$objSheet->setCellValue('D7', 'Fecha de Emisión');
		$objSheet->setCellValue('E7', 'Fecha de Vencimiento');
		$objSheet->setCellValue('F7', 'Monto a Pagar');

		//style
		$objSheet->getStyle('A7:F7')->applyFromArray(
		    array(
		        'fill' => array(
		            'type' => \PHPExcel_Style_Fill::FILL_SOLID,
		            'color' => array('rgb' => 'f36e29')
		        ),
		        'font'  => array(
		        	'size'  => 11,
			        'color' => array('rgb' => 'FFFFFF'),
			        'name' => 'Calibri'
			    )
		    )
		);
		/**/

		$objSheet->getStyle('A7:F'.$indexFila)->applyFromArray(
		    array(
		        'borders' => array(
			          'allborders' => array(
			              'style' => \PHPExcel_Style_Border::BORDER_THIN
			          )
			    )
		    )
		);
		$objSheet->getDefaultStyle()->applyFromArray($style);

		//resize column
		for ($i = 'A'; $i !=  $objPHPExcel->getActiveSheet()->getHighestColumn(); $i++) {
		    $objPHPExcel->getActiveSheet()->getColumnDimension($i)->setAutoSize(TRUE);
		}

		//output
		$objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
		$objWriter->save($file);

		//response
		if(file_exists($file)){
			$response = array(
				'response'=>'success',
				'message'=>'El reporte se genero correctamente.',
				'tipo'=>'informativo',
				"ruta"=>"temp/reportes/".$name,
				"nombreFile"=>$name
			);
		}else{
			$response = array(
				'response'=>'error',
				'message'=>'Ocurrio un problema en el servidor, por favor vuelva a intertarlo.',
				'tipo'=>'advertencia'
			);
		}

		return $response;

	}

	public function crearExcelIngreso($params)
	{	

		$adapter=$this->getAdapter();
		$sql=new Sql($adapter);

		$name = 'RPT_INGRESO-'.time().'.xlsx';
		$file = 'public/temp/reportes/'.$name;

		$objPHPExcel = new \PHPExcel();
		$objSheet = $objPHPExcel->getActiveSheet();
		$objSheet->setTitle('INGRESO');



		$mes=isset($params['mes'])? (($params['mes']<9)?"0".$params['mes']:$params['mes']):'0';
		$year=$params['year'];
		$edificioId=$params['edificioId'];
		$listAbecedario=array("A","B","C","D","E","F","G","H","I","J","K","L","M","Ñ","O","P","Q");

		
		$representaUnidad=trim($params['representaunidad']);
		$tipoReporte=isset($params['tiporeporte'])?$params['tiporeporte']:null;
		$idsConcepto=array();
		
		if($tipoReporte=='total' || $tipoReporte=='detallado'){
			$idsConcepto=$this->getConceptosExistentesByEmision($params);
		}else{
			$_idsConcepto=explode(',', $params['ids']);
			if(count($_idsConcepto)>1){
				foreach ($_idsConcepto as $value) {
					if($value!=''){
						$idsConcepto[]=array('id'=>$value,'descripcion'=>$this->getConceptoNombre($value));
					}
				}
			}
		}

		//titulos

		$objSheet->setCellValue('A1', 'INGRESO DE '.strtoupper($this->nombreMes[((int)$mes -1)]). " ".$year." - ".$this->getNombreEdificio($edificioId));
		
		//header excel
		$currentIndexLetra=0;
		switch ($representaUnidad) {
			case 'propietario':
				$objSheet->setCellValue($listAbecedario[$currentIndexLetra].'2', 'PROPIETARIO');
				$currentIndexLetra++;
				break;
			case 'residente':
				$objSheet->setCellValue($listAbecedario[$currentIndexLetra].'2', 'RESIDENTE');
				$currentIndexLetra++;
				break;
			case 'ambos':
				$objSheet->setCellValue($listAbecedario[$currentIndexLetra].'2', 'PROPIETARIO');
				$currentIndexLetra++;
				$objSheet->setCellValue($listAbecedario[$currentIndexLetra].'2', 'RESIDENTE');
				$currentIndexLetra++;
				break;
			default:
				return "error";
				break;
		}

		$objSheet->setCellValue($listAbecedario[$currentIndexLetra]."2", 'UNIDAD');
		$currentIndexLetra++;

		$objSheet->setCellValue($listAbecedario[$currentIndexLetra]."2", '% Participación');
		$currentIndexLetra++;


		$objSheet->setCellValue($listAbecedario[$currentIndexLetra]."2", 'NUMERO DE RECIBO');
		$currentIndexLetra++;

		if($tipoReporte!='total'){
			foreach ($idsConcepto as $key=>$value){
				$objSheet->setCellValue($listAbecedario[$currentIndexLetra]."2", $value['descripcion']);
				$currentIndexLetra++;

				if(count($idsConcepto)==1){
					$objSheet->setCellValue($listAbecedario[$currentIndexLetra]."2", 'NOTA');
					$currentIndexLetra++;
				}
			}
		}

		$objSheet->setCellValue($listAbecedario[$currentIndexLetra].'2', 'TOTAL');

		if($tipoReporte=='total'){
			$currentIndexLetra++;
			$objSheet->setCellValue($listAbecedario[$currentIndexLetra].'2', 'ESTADO');
		}

		

		$selectUnidad=$sql->select()
		->from(array('uni'=>'unidad'))
		->columns(array('unidadId'=>'uni_in_cod','propietarioId'=>'uni_in_pro','residenteId'=>'uni_in_pos','unidad'=>new Expression("CONCAT(uni_vc_tip,' ',uni_vc_nom)"),'unidadAbreviado'=>'uni_vc_nom'))
		->join(array('ing'=>'ingreso'),'uni.uni_in_cod=ing.uni_in_cod')
		->where(array('edi_in_cod'=>$edificioId,'uni_in_est'=>1,"MONTH(ing_da_fecemi)=".$mes,
				"YEAR(ing_da_fecemi)=".$year));
		$selectUnidad=$sql->buildSqlString($selectUnidad);
		$rowsUnidades=$adapter->query($selectUnidad,$adapter::QUERY_MODE_EXECUTE)->toArray();

		
		$indexFila=3;
		foreach($rowsUnidades as $key=>$value){
			$unidadId=$value['unidadId'];

			$stringSelectSubUnidades="SELECT sum(uni_do_cm2) as pct from unidad WHERE (uni_in_cod=$unidadId and uni_in_est!=0) or (uni_in_pad = $unidadId and uni_in_est!=0)";
			$rsTotalPct=$adapter->query($stringSelectSubUnidades,$adapter::QUERY_MODE_EXECUTE)->current();
			//$totalPct=round($rsTotalPct['pct'],2);
			$totalPct=$rsTotalPct['pct'];



			$stringSql="SELECT coi.con_in_cod as id,coi.coi_do_subtot as total,coi.coi_te_com as nota,ing.ing_in_nroser as serie,ing.ing_in_nrodoc as nroDoc, ing.ing_in_est as estadoIngreso 
					from concepto_ingreso coi 
					INNER JOIN ingreso ing on ing.ing_in_cod=coi.ing_in_cod
					INNER JOIN concepto con on con.con_in_cod=coi.con_in_cod
					WHERE ing.uni_in_cod=$unidadId
						and YEAR(ing.ing_da_fecemi)=$year
						and MONTH(ing.ing_da_fecemi)=$mes";

			$rsDetallesDeEmision=$adapter->query($stringSql,$adapter::QUERY_MODE_EXECUTE)->toArray();

			$currentIndexLetra=0;
			switch ($representaUnidad) {
				case 'propietario':
					$objSheet->setCellValue($listAbecedario[$currentIndexLetra].$indexFila, $this->getNombreUsuario($value['propietarioId']));
					$currentIndexLetra++;
					break;
				case 'residente':
					$objSheet->setCellValue($listAbecedario[$currentIndexLetra].$indexFila, $this->getNombreUsuario($value['residenteId']));
					$currentIndexLetra++;
					break;
				case 'ambos':
					$objSheet->setCellValue($listAbecedario[$currentIndexLetra].$indexFila, $this->getNombreUsuario($value['propietarioId']));
					$currentIndexLetra++;
					$objSheet->setCellValue($listAbecedario[$currentIndexLetra].$indexFila, $this->getNombreUsuario($value['residenteId']));
					$currentIndexLetra++;
					break;
				default:
					return "error";
					break;
			}

			$serie=isset($rsDetallesDeEmision[0]['serie']) ? $rsDetallesDeEmision[0]['serie']:'';
			$nroDoc=isset($rsDetallesDeEmision[0]['nroDoc'])? $rsDetallesDeEmision[0]['nroDoc']:'';
			$estadoIngreso=isset($rsDetallesDeEmision[0]['estadoIngreso']) ? $rsDetallesDeEmision[0]['estadoIngreso']:'';

			$objSheet->setCellValue($listAbecedario[$currentIndexLetra].$indexFila, $value['unidadAbreviado']);
			$currentIndexLetra++;

			$objSheet->setCellValue($listAbecedario[$currentIndexLetra].$indexFila, $totalPct);
			$currentIndexLetra++;

			$objSheet->setCellValue($listAbecedario[$currentIndexLetra].$indexFila, $serie."-".$nroDoc);
			$currentIndexLetra++;

			$i=0;
			$sumaTotal=0;
			
			while($i<count($idsConcepto)){
				$existeConcepto=false;
				if(!empty($rsDetallesDeEmision)){
					foreach ($rsDetallesDeEmision as $keyCI => $valueCI){
						if($idsConcepto[$i]['id']==$valueCI['id']){
							if($tipoReporte!='total'){
								$objSheet->setCellValue($listAbecedario[$currentIndexLetra].$indexFila, ($valueCI['total']==0)? '':$valueCI['total']);
								$currentIndexLetra++;

								if(count($idsConcepto)==1){
									$objSheet->setCellValue($listAbecedario[$currentIndexLetra].$indexFila, $valueCI['nota']);
									$currentIndexLetra++;
								}
							}
							$sumaTotal+=$valueCI['total'];
							$existeConcepto=true;
							$i++;
							break 1;
						}
					}
				}
				

				if($existeConcepto==false){
					if($tipoReporte!='total'){
						$objSheet->setCellValue($listAbecedario[$currentIndexLetra].$indexFila, '');
						$currentIndexLetra++;

						if(count($idsConcepto)==1){
							$objSheet->setCellValue($listAbecedario[$currentIndexLetra].$indexFila, '');
							$currentIndexLetra++;
						}
					}
					$i++;
				}
			}

			$objSheet->setCellValue($listAbecedario[$currentIndexLetra].$indexFila, $sumaTotal);
			if($tipoReporte=='total'){
				$currentIndexLetra++;
				$estadoIngreso=($sumaTotal>0) ? $estadoIngreso : NULL;
				$nombreEstadoIngreso=$this->getNombreEstadoIngreso($estadoIngreso);
				
				$objSheet->setCellValue($listAbecedario[$currentIndexLetra].$indexFila, $nombreEstadoIngreso);
			}

	
			$indexFila++;
		}


		$objSheet->mergeCells('A1:'.$listAbecedario[$currentIndexLetra]."1");
		$objSheet->getStyle('A1')->getFont()->setBold(true);
		$objSheet->getStyle('A1')->applyFromArray(array('alignment' => array('horizontal' => \PHPExcel_Style_Alignment::HORIZONTAL_CENTER)));


		$objSheet->getStyle('A2:'.$listAbecedario[$currentIndexLetra]."2")->getAlignment()->setWrapText(true);
		$styleHeader = array(
			'rgb' => 'FFFFFF',
			'size'=>10,
	        'alignment' => array(
	            'horizontal' => \PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
	        ),
	        'fill' => array(
					'type' => \PHPExcel_Style_Fill::FILL_SOLID,
					'color' => array('rgb' => 'C15F9D')
			)
	    );
    	$objSheet->getStyle('A2:'.$listAbecedario[$currentIndexLetra]."2")->applyFromArray($styleHeader);

    	for($i=0;$i<$currentIndexLetra;$i++){
			$objSheet->getColumnDimension($listAbecedario[$i])->setAutoSize(false);
		}


		//BORDER HEADER
		$objSheet->getStyle('A2:'.$listAbecedario[$currentIndexLetra]."2")
			->getBorders()->applyFromArray(
					array(
						'allborders' => array(
							'style' => \PHPExcel_Style_Border::BORDER_DOTTED,
							'color' => array(
								'rgb' => '000000'
								)
							)
					)
				);

		//BORDER CELL
		$objSheet->getStyle('A3:'.$listAbecedario[$currentIndexLetra].(count($rowsUnidades)+2))
			->getBorders()->applyFromArray(
					array(
						'allborders' => array(
							'style' => \PHPExcel_Style_Border::BORDER_DASHED,
							'color' => array(
								'rgb' => '000000'
								)
							)
					)
				);


		$objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
		$objWriter->save($file);

		if(file_exists($file)){
			//auditoria
			/////////save auditoria////////
			$params['tabla']='';
			$params['numRegistro']='';
			$params['comNumRegistro']='';
			$params['accion']='Exportar Excel Ingreso del Mes de '.$this->nombreMes[$params['mes'] - 1]." del ".$params['year'];
			$this->saveAuditoria($params);
			///////////////////////////////
			$response = array(
				"message"=>"success",
				"ruta"=>"temp/reportes/".$name,
				"nombreFile"=>$name
				);
		}else{
			$response = array("message"=>"nofile");
		}

        return $response;
	}


	private function getNombreEstadoIngreso($estadoNumero){
		$estado='-';
		
		switch ($estadoNumero) {
			case NULL:
				$estado='-';
				break;
			case 0:
				$estado='Pagado';
				break;
			case 1:
				$estado='Adeudo total';
				break;
			case 2:
				$estado='Adeudo';
				break;
			
		}
		return $estado;
	}
	
	private function saveAuditoria($params)
    {   
        $adapter=$this->getAdapter();
        $sql=new Sql($adapter);
        $insert = $sql->insert('auditoria');
        $data = array(
            'usu_in_cod'=> $params['usuarioId'], //idusuario
            'edi_in_cod'=> $params['edificioId'], //idedificio
            'aud_opcion'=> 'Finanzas > Ingreso', //ruta
            'aud_accion'=> $params['accion'], //accion
            'aud_fecha'=> date('Y-m-d H:i:s'), //fecha y hora
            'aud_ip'=> $_SERVER['REMOTE_ADDR'], //ip publica
            'aud_tabla'=> $params['tabla'], //tabla
            'aud_in_numra'=> $params['numRegistro'], //registro afectado
            'aud_info_adi'=> $_SERVER['HTTP_USER_AGENT'], //agente
            'aud_comnumra'=> $params['comNumRegistro'] //comentario del registro afectado
        );
        $insert->values($data);
        $sql->prepareStatementForSqlObject($insert)->execute()->count();
    }

    /*******************************************************************/
    /*******************************************************************/
    public function generateCrep($params)
    {


        //include_once '../Scripts/DHConexion.php';
        //include_once 'Store_Procedure/SP_Supervisor.php';

        //$db = new MySQL();
        //$objTextoPlano = new TextoPlano();
    	$msgValidateCrep=false;
        $ResEnt = $this->SP_ListaEntidad($params['idEmpresa']);
        if(count($ResEnt)>0) $msgValidateCrep=true; //$msgValidateCrep='Este edificio no tiene un número de cuenta recaudadora.';

        //var_dump($db->fetch_assoc($ResEnt));
        //exit();

        //Configurando el GMT a la zona horaria "America/Lima" (GTM-05:00)
        date_default_timezone_set('America/Lima');

        $TextoPlano = "";
        $TextoPlanoHTML = "";

        /* Variables de la cabecera */

        $TipReg = "CC";         // Tipo de registro (CC = Cabecera)         [2 caracteres]
        $CodSuc = "193";        // Código de la sucursal                    [3 caracteres]
        $CodMon = "0";          // Código de la moneda                      [1  caracter]
        $NumCue = "1234567";    // Número de cuenta de la empresa afiliada  [7 caracteres]
        $TipVal = "C";          // Tipo de validacion (C = Completa)        [1 caracter]
        $NomEmp = "";           // Nombre de la empresa afiliada            [40 caracteres]
        $FecTra = date("Ymd");   // Fecha de transmición (AAAAMMDD)          [8 caracteres]
        $CanDet = "000000000";  // Cantidad total de registros del detalle  [9 caracteres]
        $MonTot = "000000000000000";    // Monto total enviado              [15 caracteres]
        $TipArc = " ";     // Tipo de archivo (R = Archivo de reemplazo, A = Archivo de Actualización) [1 caracter]
        $Filler = "";     // Libre                                    [114 caracteres]

        for ($j = 0; $j < 40; $j++) {
            $NomEmp.=" ";
        }

        for ($i = 0; $i < 114; $i++) {
            $Filler.=" ";
        }

        $conttttt = 0;
        /* CABECERAS DEL TEXTO PLANO */

        foreach ($ResEnt as $RowEnt) {

            //echo $RowEnt['edi_vc_numcue']."<br>";

            $TipReg = "CC"; // Cabecera
            if ($RowEnt['edi_vc_numcue'] != '') {
                $NumCueBD = explode("-", $RowEnt['edi_vc_numcue']);
            } else {
                $NumCueBD = explode("-", '000-0000000-0-00');
            }
            $CodSuc = utf8_decode($NumCueBD[0]); // Código de la sucursal 3 Primeros caracteres del Número de Cuenta
            $CodMon = utf8_decode($NumCueBD[2]); // Código de la moneda
            $NumCue = utf8_decode($NumCueBD[1]); // Número de cuenta de la empresa afiliada  7 Segundos caracteres del Número de Cuenta
            $NomEmp = utf8_decode($this->QuitaCaracteresEsp($RowEnt['edi_vc_nomcuerec']));
            $CodEnt = utf8_decode($RowEnt['edi_in_cod']);
            $CanDet = "";
            $CanCom = 40 - (strlen($RowEnt['edi_vc_nomcuerec']) * 1);
            for ($x = 0; $x < $CanCom; $x++) {
                $NomEmp .= " ";
            }

            $ResUni = $this->SP_ListaUnidad($CodEnt);
            $TotUni = count($ResUni);

            $UniCer = 9 - (strlen($TotUni) * 1);
            for ($y = 0; $y < $UniCer; $y++) {
                $CanDet .= "0";
            }

            $MonCupo2 = 0;
            $MonTot = "";
            $ResUni2 = $this->SP_ListaUnidad($CodEnt);
            foreach ($ResUni2 as $RowUni2) {
                $MonCupo2 += ($RowUni2['ing_do_sal'] * 1);
            }

            $MontoCupo2 = explode(".", $MonCupo2);

            if (!isset($MontoCupo2[1])) {
                $MonCupRea2 = $MontoCupo2[0] . "00";
            } else {
                $MonCupRea2 = $MontoCupo2[0] . $MontoCupo2[1];
            }

            $MonCupCer2 = 15 - (strlen(utf8_decode($MonCupRea2)) * 1);
            for ($h2 = 0; $h2 < $MonCupCer2; $h2++) {
                $MonTot .= "0";
            }
            $MonTot .= $MonCupRea2;

            $CanDet .= $TotUni;
            $TextoPlano .= $TipReg . $CodSuc . $CodMon . $NumCue . $TipVal . $NomEmp . $FecTra . $CanDet . $MonTot . $TipArc . $Filler . "\n";
            $TextoPlanoHTML .= $TipReg . $CodSuc . $CodMon . $NumCue . $TipVal . $NomEmp . $FecTra . $CanDet . $MonTot . $TipArc . $Filler . chr(13) . chr(10);

            /* DETALLES DEL TEXTO PLANO */

            foreach ($ResUni as $RowUni) {
                $TipReg = "DD"; // Detalle
                $CodDep = "";   // Código de Identificación del Depositante ó Usuario [14 caracteres]
                $NomDep = utf8_decode($this->QuitaCaracteresEsp($RowUni['poseedor']));   // Nombre del Depositante [40 caracteres]
                $CamRet = "";   // Campo con información de retorno [30 caracteres]
                $FEdb = explode("-", $RowUni['ing_da_fecemi']);
                $FVdb = explode("-", $RowUni['ing_da_fecven']);
                $FecEmi = $FEdb[0] . $FEdb[1] . $FEdb[2];   // Fecha de emisión del cupón [8 caracteres]
                $FecVen = $FVdb[0] . $FVdb[1] . $FVdb[2];   // Fecha de vencimiento del cupón [8 caracteres]
                $MontoCupo = explode(".", $RowUni['ing_do_sal']);
                if ($MontoCupo[1] == '') {
                    $MonCupRea = $MontoCupo[0] . "00";
                } else {
                    if (strlen($MontoCupo[1]) == 1) {
                        $MonCupRea = $MontoCupo[0] . $MontoCupo[1] . "0";
                    } else {
                        $MonCupRea = $MontoCupo[0] . $MontoCupo[1];
                    }
                }

                $MonCup = "";   // Monto del cupón [15 caracteres]
                $MonMor = "000000000000000";   // Monto de mora [15 caracteres]
                $MontoMinimo = explode(".", $RowEnt['edi_do_monmin']);
                if ($MontoMinimo[1] == '') {
                    $MonMinRea = $MontoMinimo[0] . "00";
                } else {
                    if (strlen($MontoMinimo[1]) == 1) {
                        $MonMinRea = $MontoMinimo[0] . $MontoMinimo[1] . "0";
                    } else {
                        $MonMinRea = $MontoMinimo[0] . $MontoMinimo[1];
                    }
                }
                $MonMin = "";   // Monto mínimo [9 caracteres]
                $TipRegAct = "A";    // Tipo de registro de actualizacion (A = Registro a Agregar, M = Registro a Modificar, E = Registro a Eliminar) [1 caracter]
                $FillerDet = "000000000000000000000000000000000000000000000000";    // LIbre [48 caracteres]

                $MonCupCer = 15 - (strlen(utf8_decode($MonCupRea)) * 1);
                for ($h = 0; $h < $MonCupCer; $h++) {
                    $MonCup .= "0";
                }
                $MonCup .= $MonCupRea;

                $MonMinCer = 9 - (strlen($MonMinRea) * 1);
                for ($t = 0; $t < $MonMinCer; $t++) {
                    $MonMin .= "0";
                }
                $MonMin .= $MonMinRea;

                $MesCuo = explode("-", $RowUni['ing_da_fecven']);
                $MesNomCuo = "";
                switch ($MesCuo[1]) {
                    case '1' : $MesNomCuo = "ENERO";
                        break;
                    case '2' : $MesNomCuo = "FEBRERO";
                        break;
                    case '3' : $MesNomCuo = "MARZO";
                        break;
                    case '4' : $MesNomCuo = "ABRIL";
                        break;
                    case '5' : $MesNomCuo = "MAYO";
                        break;
                    case '6' : $MesNomCuo = "JUNIO";
                        break;
                    case '7' : $MesNomCuo = "JULIO";
                        break;
                    case '8' : $MesNomCuo = "AGOSTO";
                        break;
                    case '9' : $MesNomCuo = "SEPTIEMBRE";
                        break;
                    case '10' : $MesNomCuo = "OCTUBRE";
                        break;
                    case '11' : $MesNomCuo = "NOVIEMBRE";
                        break;
                    case '12' : $MesNomCuo = "DICIEMBRE";
                        break;
                }

                $CamRet = "CUOTA MMTO " . $MesNomCuo . " " . $MesCuo[0];
                $NomDep = substr($NomDep, 0, 40);

                $CamRetCer = 30 - (strlen(utf8_decode($CamRet)) * 1);
                for ($w = 0; $w < $CamRetCer; $w++) {
                    $CamRet .= " ";
                }

                $UniCer = 14 - (strlen(utf8_encode($RowUni['uni_vc_codpag'])) * 1);
                $ParCar = "";
                if ($this->my_is_numeric($RowUni['uni_vc_codpag'])) {
                    $ParCar = "0";
                } else {
                    $ParCar = " ";
                }

                for ($y = 0; $y < $UniCer; $y++) {
                    $CodDep .= $ParCar;
                }

                $NomCar = 40 - (strlen($NomDep) * 1);
                for ($q = 0; $q < $NomCar; $q++) {
                    $NomDep .= " ";
                }

                $CodDep .= $RowUni['uni_vc_codpag'];
                $TextoPlano .= $TipReg . $CodSuc . $CodMon . $NumCue . $CodDep . $NomDep . $CamRet . $FecEmi . $FecVen . $MonCup . $MonMor . $MonMin . $TipRegAct . $FillerDet . "\n";
                $TextoPlanoHTML .= $TipReg . $CodSuc . $CodMon . $NumCue . $CodDep . $NomDep . $CamRet . $FecEmi . $FecVen . $MonCup . $MonMor . $MonMin . $TipRegAct . chr(13) . chr(10);

            }
        }

        $response=array();
        $time=time();

        if($msgValidateCrep){
	        $archivo = fopen("compress.zlib://" . "public/temp/crep/CREP_".$time.".txt" . ".gz", "wb");
	        fwrite($archivo, $TextoPlanoHTML);
	        fclose($archivo);
	        $archivorar = fopen("compress.zlib://" . "public/temp/crep/CREP_".$time.".txt" . ".rar", "wb");
	        fwrite($archivorar, $TextoPlanoHTML);
	        fclose($archivorar);

	        if(file_exists('public/temp/crep/CREP_'.$time.'.txt.rar') && file_exists('public/temp/crep/CREP_'.$time.'.txt.gz')){
	            $response = array(
	            	"response"=>true,
	            	"time"=>$time
	            );
	        }

        }else{
        	$response = array(
            	"response"=>false,
            	"mensaje"=>"Este edificio no tiene un número de cuenta recaudadora"
            );
        }

        return $response;

    }

    private function SP_ListaUnidad($codent)
    {
        $adapter = $this->getAdapter();
        $ResUni = "SELECT i.*, i.ing_do_sal, ui.uni_do_deu, (SELECT CASE WHEN usu_ch_tip='PN' THEN REPLACE(CONCAT(usu_vc_ape,' ',usu_vc_nom),'  ',' ') WHEN usu_ch_tip='PJ' THEN REPLACE(usu_vc_ape, '  ', ' ') END FROM usuario WHERE usu_in_cod=uni_in_pos) AS poseedor, ui.uni_vc_nom, ui.uni_vc_codpag, i.ing_da_fecemi, i.ing_da_fecven FROM unidad ui, ingreso i WHERE ui.uni_in_cod=i.uni_in_cod AND ui.edi_in_cod='$codent' AND ui.uni_in_est='1' AND ui.uni_in_pad IS NULL AND (i.ing_in_est='1' OR i.ing_in_est='2') AND ing_do_sal != 0";
        $rowUnidad = $adapter->query($ResUni,$adapter::QUERY_MODE_EXECUTE)->toArray();
        return $rowUnidad;
    }

    private function SP_ListaEntidad($idEmpresa)
    { 
        $adapter=$this->getAdapter();
        $sql=new Sql($adapter);
        $select = $sql->select();
        $select->from($this->table);
        $select->where(array(
            "edi_vc_numcue!=''",
            "edi_in_cod=".$this->idEdificio,
            "emp_in_cod=".$idEmpresa
        ));
        $selectString=$sql->buildSqlString($select);
        $data = $adapter->query($selectString,$adapter::QUERY_MODE_EXECUTE)->toArray();
        return $data;
    }

    private function SP_ListaEntidadPorNumeroCuenta($numCue)
    {
        $adapter=$this->getAdapter();
        $sql=new Sql($adapter);
        $select = $sql->select();
        $select->from('edificio')
         	   ->columns(array('edificio'=>'edi_vc_des'))
               ->where->like('edi_vc_numcue', '%'.$numCue.'%');
        $selectString=$sql->buildSqlString($select);
        $rowEdificio = $adapter->query($selectString,$adapter::QUERY_MODE_EXECUTE)->current();
        if($rowEdificio!='') return $rowEdificio['edificio'];
        return "-";
    }

    private function my_is_numeric($value) {
        return (preg_match("/^(-){0,1}([0-9]+)(,[0-9][0-9][0-9])*([.][0-9]){0,1}([0-9]*)$/", $value) == 1);
    }

    private function QuitaCaracteresEsp($cadena) {
        $cadena = str_replace("ñ", "N", $cadena);
        $cadena = str_replace("Ñ", "N", $cadena);
        $cadena = str_replace("Á", "A", $cadena);
        $cadena = str_replace("É", "E", $cadena);
        $cadena = str_replace("Í", "I", $cadena);
        $cadena = str_replace("Ó", "O", $cadena);
        $cadena = str_replace("Ú", "U", $cadena);
        $cadena = str_replace("á", "A", $cadena);
        $cadena = str_replace("é", "E", $cadena);
        $cadena = str_replace("í", "I", $cadena);
        $cadena = str_replace("ó", "O", $cadena);
        $cadena = str_replace("ú", "U", $cadena);
        $cadena = str_replace("'", " ", $cadena);
        $cadena = str_replace("–", " ", $cadena);
        $cadena = str_replace("-", " ", $cadena);
        return $cadena;
    }

    private function readCrep($fileCrep)
    {
        $response = array("NumCuenta"=>"","fecha"=>"","edificio"=>"");
        if(file_exists($fileCrep)){
            $file=file($fileCrep);
            $NumCuenta = substr($file[0], 2, 3) . substr($file[0], 6, 7);
            $fecha = substr($file[0],  20, 2). '/' . substr($file[0], 18, 2). '/' . substr($file[0],14,4);
            $edificio = $this->SP_ListaEntidadPorNumeroCuenta($NumCuenta);
            $response = array(
                "NumCuenta"=>$NumCuenta,
                "fecha"=>$fecha,
                "edificio"=>$edificio
            );
        }
        return $response;
    }

    private function validateDuplicateFileCrep($file)
    {  
        $adapter=$this->getAdapter();
        $queryFileRecaudacion = "SELECT * FROM ".$this->table_file_recaudacion." where usu_in_cod = '".$this->idUsuario."' AND emp_in_cod = '".$this->idEmpresa."' AND rec_vc_archivo like'%".$file."%'";
        $data=$adapter->query($queryFileRecaudacion,$adapter::QUERY_MODE_EXECUTE)->toArray();
        $rpta=(count($data)==0)?false:true;
        return $rpta;
    }

    private function insertRecaudacion($data = array())
    {   
        $rpta=false;
        $validFile=$this->validateDuplicateFileCrep($data['archivo']);
        if($validFile==0){
            $date=explode("/", $data['crep']['fecha']); //0000-00-00 format
            $dateCrep=$date[2]."-".$date[1]."-".$date[0];

            $adapter=$this->getAdapter();
            $sql=new Sql($adapter);
            $insert = $sql->insert($this->table_file_recaudacion);
            $data = array(
                'usu_in_cod'=> $this->idUsuario,
                'emp_in_cod'=> $this->idEmpresa,
                'rec_vc_numcuent'=> $data['crep']['NumCuenta'],
                'rec_da_fmov'=> $dateCrep,
                'rec_vc_edificio'=> $data['crep']['edificio'],
                'rec_vc_archivo'=> $data['archivo'],
                'rec_vc_hashcrep'=> $data['archivoCrepOk']
            );
            $insert->values($data);
            $sql->prepareStatementForSqlObject($insert)->execute();
            $rpta=true;
        }
        return $rpta;
    }

    public function uploadCrep($data)
    {   
        $nameCrep=$data['file']['filecrep']['name'];
        $rutaTemporalCrep=$data['file']['filecrep']['tmp_name'];
        $sizeCrep=$data['file']['filecrep']['size'];
        $crepExtension = strtolower(substr($nameCrep, strrpos($nameCrep, '.')+1));
        $nameCrepOk=$this->idUsuario.time().".".$crepExtension;
        $rutaArchivoCrep='public/temp/cdpg/'.$nameCrepOk;
        $ruta='public/temp/cdpg';
        $dataCrep=array();
        $dataCrep['idUsuario']=$this->idUsuario;
        $dataCrep['idEmpresa']=$this->idEmpresa;
        $dataCrep['archivo']=$nameCrep;
        $dataCrep['archivoCrepOk']=$nameCrepOk;
        $rpta=false;
        $validFile=false;
        $response=array();
        $response['rpta']=false;
        $response['format']='valid';
        $response['archive']=null;

        if($crepExtension=='txt'){
            $validFile=$this->validateDuplicateFileCrep($nameCrep);
            if(!$validFile){
                copy($rutaTemporalCrep, $rutaArchivoCrep);
                $dataCrep['crep']=$this->readCrep($rutaArchivoCrep);
                $rpta=$this->insertRecaudacion($dataCrep);
            }
        }elseif($crepExtension=='zip'){
            $archive = new PclZip($rutaTemporalCrep);
            $list = $archive->extract(PCLZIP_OPT_PATH, $ruta , PCLZIP_OPT_REMOVE_ALL_PATH);
            $listFile=array();
            for($i=0;$i<count($list);$i++){
                $listFile[]=$list[$i]['filename'];
            }
            
            foreach($listFile as $file){
                sleep(1);
                $extension=strtolower(substr($file, strrpos($file, '.')+1));
                if($extension!='txt'){
                    @unlink($file);
                }else{
                    $nameCrepTemp=explode("/", $file)[3];
                    $nameCrepOk=$this->idUsuario.time().".txt";
                    $validFile=$this->validateDuplicateFileCrep($nameCrepTemp);
                    if(!$validFile){
                        rename($ruta."/".$nameCrepTemp, $ruta."/".$nameCrepOk);
                        $dataCrep['crep']=$this->readCrep($ruta."/".$nameCrepOk);
                        $dataCrep['archivo']=$nameCrepTemp;
                        $dataCrep['archivoCrepOk']=$nameCrepOk;
                        $rpta=$this->insertRecaudacion($dataCrep);
                    }else{
                        @unlink($ruta."/".$nameCrepTemp);
                    }
                }
            }
        }else{
            $response['format']='invalid';
        }

        if ($validFile) {
            $response['archive']='exist';
        }

        if($rpta){
            $response['rpta']=true;
        }

        return $response;

    }

    public function changeEstadoCrep()
    {	
    	$adapter=$this->getAdapter();
        $sql=new Sql($adapter);
        $select = $sql->select();
        $select->from($this->table_file_recaudacion);
		$select->columns(array(
			"id"=>"rec_in_cod",
			"excel"=>"rec_vc_excel"
		));
		$selectString=$sql->buildSqlString($select);
		$dataFileRecaudacion = $adapter->query($selectString,$adapter::QUERY_MODE_EXECUTE)->toArray();
    	foreach($dataFileRecaudacion as $file){
    		if(""!=$file['excel']){
    			$data=["rec_in_estado"=>1];
				$update=$sql->update()->table('file_recaudacion')
					->set($data)->where(array('rec_in_cod'=>$file['id']));
				$sql->prepareStatementForSqlObject($update)->execute();
    		}
    	}
    	return ["response"=>"success"];
    }

    public function getListFileRecaudacion($params)
    {
        $adapter=$this->getAdapter();
        $sql=new Sql($adapter);
        $select = $sql->select();
        $select->from($this->table_file_recaudacion);
        
        if(isset($params['idcrep'])){
            $select->columns(array(
                "id"=>"rec_in_cod",
                "hashCrep"=>"rec_vc_hashcrep",
                "excel"=>"rec_vc_excel"
            ));
        }else{
            $select->columns(array(
                "id"=>"rec_in_cod",
                "numeroCuenta"=>"rec_vc_numcuent",
                "fechaMovimiento"=>"rec_da_fmov",
                "edificio"=>"rec_vc_edificio",
                "archivo"=>"rec_vc_archivo",
                "hashCrep"=>"rec_vc_hashcrep",
                "excel"=>"rec_vc_excel"
            ));
        }

        if(isset($params['idcrep'])){
            $select->where(array(
                "rec_in_cod"=>$params['idcrep']
            ));
        }else{
            $select->where(array(
                "usu_in_cod"=>$this->idUsuario,
                "emp_in_cod"=>$this->idEmpresa,
                "rec_in_estado"=>0
            ));
        }
        
        $selectString=$sql->buildSqlString($select);

        if(isset($params['idcrep'])) $data = $adapter->query($selectString,$adapter::QUERY_MODE_EXECUTE)->current();
        else $data = $adapter->query($selectString,$adapter::QUERY_MODE_EXECUTE)->toArray();

        return $data;
    }

    public function deleteCrep($params)
    {   
        $adapter=$this->getAdapter();
        $sql=new Sql($adapter);
        $response=null;

        $ruta='public/temp/cdpg/';
        $rutaExcel='public/file/resultado-cdpg/';
        $dataIdCrep=explode(",", $params['check']);
        foreach($dataIdCrep as $idcrep){
            $params['idcrep']=$idcrep;
            $dataCrep=$this->getListFileRecaudacion($params);
            //deleteRegisterCrep
            $delete=$sql->delete()
                ->from($this->table_file_recaudacion)
                ->where(array('rec_in_cod'=>$params['idcrep']));
            $sql->prepareStatementForSqlObject($delete)->execute();
            //deleteArchiveCrep
            if(file_exists($ruta.$dataCrep['hashCrep'])){
                @unlink($ruta.$dataCrep['hashCrep']);
            }
            //delete excel
            if(file_exists($rutaExcel.$dataCrep['excel'])){
                @unlink($rutaExcel.$dataCrep['excel']);
            }
        }   

        return $response=array("rpta"=>true);

    }

    public function deleteRegisterCrep($params)
    {
    	$adapter=$this->getAdapter();
        $sql=new Sql($adapter);
    	$ruta='public/temp/cdpg';
    	$fileCrep = explode(",", $params['file']);
    	foreach($fileCrep as $hashCrep){
    		$delete=$sql->delete()
                ->from($this->table_file_recaudacion)
                ->where(array('rec_vc_hashcrep'=>$hashCrep));
            $sql->prepareStatementForSqlObject($delete)->execute();
            //deleteCrep
            if(file_exists($ruta."/".$hashCrep)){
                @unlink($ruta."/".$hashCrep);
            }
    	}
    	return true;
    }

    public function transferirCrep($params)
    {
    	$response=array();
    	$response['rpta']=false;
        $ruta='public/temp/cdpg';
        $dataIdCrep=explode(",", $params['check']);
        $fileCrep=null;
        foreach($dataIdCrep as $idcrep){
            $params['idcrep']=$idcrep;
            $dataCrep=$this->getListFileRecaudacion($params);
            $fileCrep .=$dataCrep['hashCrep'].",";
            $filas = file($ruta."/".$dataCrep['hashCrep']);
            $NumCue = substr($filas[0], 2, 3) . substr($filas[0], 6, 7);
            $dataArrayUnidad=array();

            for ($i = 0; $i < count($filas); $i++) {
                $cad = $filas[$i];
                $arr = explode('DD', $cad);
                if (isset($arr[1])) {
                    $uniDep = ltrim(trim(substr($arr[1], 11, 14)), "0"); //unidad
                    $fecPag = trim((substr($arr[1], 55, 4) . '-' . substr($arr[1], 59, 2) . '-' . substr($arr[1], 61, 2))); //fecha de pago
                    $fecVen = trim(substr($arr[1], 63, 4) . '-' . substr($arr[1], 67, 2) . '-' . substr($arr[1], 69, 2)); //fecha de vencimiento
                    $monPag = (substr($arr[1], 71, 13) . '.' . substr($arr[1], 84, 2)) * 1; //Monto a pagar
                    $monMor = (substr($arr[1], 86, 13) . '.' . substr($arr[1], 99, 2)) * 1; //interes (mora)
                    $monTot = (substr($arr[1], 101, 13) . '.' . substr($arr[1], 114, 2)) * 1;//Importe Total (interes + $monPag)
                    $numOpe = trim(substr($arr[1], 122, 6)); //Numero de operacion
                    
                    //echo $NumCue."<br>";

                    $idUnidad=$this->getIdUnidad($uniDep);
                    $edificio=$this->SP_ListaEntidadPorNumeroCuenta($NumCue);

                    //$dataArrayUnidad['edificio']=$edificio;
                   	$dataArrayUnidad[$i] = array(
                   		'edificio'=>$edificio,
                   		'idUnidad'=>$idUnidad,
                   		'fechaPago'=>$fecPag,
                   		'fechaVencimiento'=>$fecVen,
                   		'montoPagar'=>$monPag,
                   		'montoMora'=>$monMor,
                   		'montoTotal'=>$monTot,
                   		'numOperacion'=>$numOpe,
                   		'estado'=>0
                   	);

                    $arrFecVen = explode('-', $fecVen);
                    $datos = $this->SP_CodIngreso($NumCue, $uniDep,$arrFecVen[0],$arrFecVen[1],$numOpe,$fecVen);

                    if($datos !=""){//valido datos para registrar
                        $arr2 = explode('_',$datos);
                        $codUni="";$codIng="";$fecEmi="";$nroser="";$nrodoc="";$totPag="";$fecVen='';
                        if(isset ($arr2[0])){ $codUni = $arr2[0];}
                        if(isset ($arr2[1])){ $codIng = $arr2[1];}
                        if(isset ($arr2[2])){ $fecEmi = $arr2[2];}
                        if(isset ($arr2[3])){ $nroser = $arr2[3];}
                        if(isset ($arr2[4])){ $nrodoc = $arr2[4];}
                        if(isset ($arr2[5])){ $totPag = $arr2[5];}//Deuda Total
                        if(isset ($arr2[6])){ $fecVen = $arr2[6];}
                        $textObser="";
                        $pagoCrep=0;
                        //Validar si se paga de la columna importe o importe total
                        if($monMor!=0){//si hay mora o interes
                            $pagoCrep = $monTot;
                            $totPag =   $totPag+$monMor;
                        }else {
                            $pagoCrep = $monPag;
                        }

                        $codUsu=$this->idUsuario;
                        $dataArrayUnidad[$i]['estado']=1;

                        $params=array(
	                        'unidadId'=>$codUni,
	                        'textImporte'=>$totPag,
	                        'userId'=>$codUsu,
	                        'textNroOperacion'=>$numOpe,
	                        'selTipoDoc'=>"BOLETA",
	                        'selBanco'=>"BANCO DE CREDITO DEL PERU",
	                        'textFechaPago'=>$fecPag,
	                        'taObservacion'=>"-",
                            'usuarioId'=>$codUsu,
                            'edificioId'=>$this->idEdificio
                        );  
                        $response=$this->addPagoEnDeudaPendiente($params);

                        $response['rpta']=true;
                        //$response['hashCrep']=substr($fileCrep, 0, -1);
                        //echo "Transferencia Completa";
                    }else{
                        //echo 'No hay datos';
                        $response['rpta']=false;
                    }

                }
            }

            //generate report
        	$this->generateReportCrep($dataArrayUnidad,$idcrep);
        	$response['hash']=substr($fileCrep, 0, -1);

        } //end foreach

        return $response;


    }

    private function generateReportCrep($dataUnidad,$idCrep)
    {
    	$name = 'RPT-CDPG-'.rand().'.xlsx';
		$file = 'public/file/resultado-cdpg/'.$name;

		$objPHPExcel = new \PHPExcel();
		$objSheet = $objPHPExcel->getActiveSheet();
		$phpColor = new \PHPExcel_Style_Color();
		$objSheet->setTitle('REPORTE CDPG');

		$objSheet->setCellValue('A1','Edificio');
		$objSheet->setCellValue('B1','Unidad');
		$objSheet->setCellValue('C1','Fecha de Pago');
		$objSheet->setCellValue('D1','Fecha de Vencimiento');
		$objSheet->setCellValue('E1','Monto Pagar');
		$objSheet->setCellValue('F1','Monto Mora');
		$objSheet->setCellValue('G1','Monto Total');
		$objSheet->setCellValue('H1','Numero de Operación');
		$objSheet->setCellValue('I1','Estado');

		$totalUnidades=count($dataUnidad)+1;

		$indice=2;
    	foreach($dataUnidad as $unidad){
 
    		$detallesDeUnidad=$this->getDetalleUnidadCrep($unidad['idUnidad']);

			$objSheet->setCellValue('A'.$indice, $unidad['edificio']);

			if($unidad['estado']===1){
				$objSheet->setCellValue('B'.$indice, $detallesDeUnidad['unidadNombre']);
				$objSheet->getStyle('A'.$indice.':I'.$indice)->applyFromArray(
				    array(
				        'fill' => array(
				            'type' => \PHPExcel_Style_Fill::FILL_SOLID,
				            'color' => array('rgb' => 'fde9d9')
				        )
				    )
				);
				$objSheet->setCellValue('I'.$indice, 'PAGADO');
			}else{
				$objSheet->setCellValue('B'.$indice, $detallesDeUnidad['unidadNombre']);
				$objSheet->setCellValue('I'.$indice, '-');
			}

			$objSheet->setCellValue('C'.$indice, date("d/m/Y", strtotime($unidad['fechaPago'])));
			$objSheet->setCellValue('D'.$indice, date("d/m/Y", strtotime($unidad['fechaVencimiento'])));
			$objSheet->setCellValue('E'.$indice, $unidad['montoPagar']);
			$objSheet->setCellValue('F'.$indice, $unidad['montoMora']);
			$objSheet->setCellValue('G'.$indice, $unidad['montoTotal']);
			$objSheet->setCellValue('H'.$indice, $unidad['numOperacion']);
			
			$indice++;
    	}
    	//style
    	$objSheet->getStyle('A1:I1')->applyFromArray(
		    array(
		        'fill' => array(
		            'type' => \PHPExcel_Style_Fill::FILL_SOLID,
		            'color' => array('rgb' => '004583')
		        ),
		        'font'  => array(
			        'color' => array('rgb' => 'FFFFFF')
			    ),
			    'borders' => array(
					'allborders' => array(
						'style' => \PHPExcel_Style_Border::BORDER_THIN
					)
			    )
		    )
		);

		$objSheet->getStyle('A2:I'.$totalUnidades)->applyFromArray(
		    array(
		        'borders' => array(
					'allborders' => array(
						'style' => \PHPExcel_Style_Border::BORDER_THIN
					)
			    )
		    )
		);

		//resize column
		for ($i = 'A'; $i !=  $objPHPExcel->getActiveSheet()->getHighestColumn(); $i++) {
		    $objPHPExcel->getActiveSheet()->getColumnDimension($i)->setAutoSize(TRUE);
		}

    	//output
		$objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
		$objWriter->save($file);

		if(file_exists($file)){
			$adapter=$this->getAdapter();
			$sql=new Sql($this->adapter);
			$dataFileRecaudacion=["rec_vc_excel"=>$name];
			$updateFileRecaudacion=$sql->update()->table('file_recaudacion')
			->set($dataFileRecaudacion)->where(array('rec_in_cod'=>$idCrep));
			$sql->prepareStatementForSqlObject($updateFileRecaudacion)->execute();
		}

    }

    private function getDetalleUnidadCrep($unidadId)
    {
    	//$detallesDeUnidad=$this->detallesDeUnidad($dataUnidad[$i]['idUnidad']);
    	$sql=new Sql($this->adapter);
		$select=$sql->select()
			->from(array('uni'=>'unidad'))
			->columns(
				array('unidadNombre'=>new Expression("CONCAT(uni_vc_tip,' ',uni_vc_nom)"))
			)
			->where(array('uni_in_cod'=>$unidadId));
		$statement=$sql->prepareStatementForSqlObject($select);
		$rowUnidad=$statement->execute()->current();
		return $rowUnidad;
    }

    private function getIdUnidad($codPag)
    {
    	//SELECT uni_in_cod FROM unidad WHERE edi_in_cod= 43 AND uni_vc_codpag = 704 AND uni_in_est !=0
    	$sql=new Sql($this->adapter);
		$selectSumCI=$sql->select()
			->from('unidad')
			->columns(array('idUnidad'=>'uni_in_cod'))
			->where(array(
					'edi_in_cod'=>$this->idEdificio,
					'uni_vc_codpag'=>$codPag,
					'uni_in_est!=0'
				)
			);
		$statement=$sql->prepareStatementForSqlObject($selectSumCI);
		$rowUnidad=$statement->execute()->current();
		return $rowUnidad['idUnidad'];
    }

    //Funcion para hallar la entidad y la unidad de propietario
    private function SP_CodIngreso($numCue, $numDep, $anio,$mes,$numOpe,$fecVen)
    {
        $adapter=$this->getAdapter();
        //echo $numCue."||||".$numDep."||||".$anio."||||".$mes."||||".$numOpe."||||".$fecVen."<br>";
        $queryEdificio = "SELECT edi_in_cod FROM edificio WHERE edi_vc_numcue LIKE '$numCue%' AND edi_in_est !=0";
        $dataEdificio=$adapter->query($queryEdificio,$adapter::QUERY_MODE_EXECUTE)->current();
        $codEni = $dataEdificio['edi_in_cod'];
        
        //$queryUnidad = "SELECT uni_in_cod FROM unidad  WHERE edi_in_cod= '$codEni'  AND uni_vc_nom = '$numDep' AND uni_in_est !=0"; //before
        $queryUnidad = "SELECT uni_in_cod FROM unidad  WHERE edi_in_cod= '$codEni'  AND uni_vc_codpag = '$numDep' AND uni_in_est !=0"; //after
        $dataUnidad=$adapter->query($queryUnidad,$adapter::QUERY_MODE_EXECUTE)->current();
        $codUni = $dataUnidad['uni_in_cod'];

        $queryIngreso = "SELECT ing_in_cod,ing_da_fecemi,ing_in_nroser, ing_in_nrodoc,ing_do_sal FROM ingreso WHERE uni_in_cod = '$codUni' AND MONTH(ing_da_fecven) = '$mes' AND year(ing_da_fecven) = '$anio' AND ing_in_est !='0'";
        $dataIngreso=$adapter->query($queryIngreso,$adapter::QUERY_MODE_EXECUTE)->current();
        $codIng = $dataIngreso['ing_in_cod'];
        $fecEmi = $dataIngreso['ing_da_fecemi'];
        $nroser = $dataIngreso['ing_in_nroser'];
        $nrodoc = $dataIngreso['ing_in_nrodoc'];
        $totPag = $dataIngreso['ing_do_sal'];
        
        //Validar la insercion de los pagos parciales (no se registren 2 veces dependiendo de ipa_vc10_numope del archivo CREP ) 
        $queryIngParcial = "SELECT COUNT(ipa_vc_numope) AS Cant FROM ingreso_parcial WHERE ing_in_cod ='$codIng' AND MONTH(ipa_da_fecven) = '$mes' AND year(ipa_da_fecven) = '$anio' AND ipa_vc_numope ='$numOpe'";
        $dataIngParcial=$adapter->query($queryIngParcial,$adapter::QUERY_MODE_EXECUTE)->current();
        $valida = $dataIngParcial['Cant'];

        //Validar Pago del mes Anterior  
        /*$queryPagoMesAnterior = "SELECT COUNT(*) as Cant FROM ingreso WHERE uni_in_cod ='$codUni' AND ing_da_fecven < '$fecVen' AND ing_in_est = '1'";
        $dataPagoMesAnterior=$adapter->query($queryPagoMesAnterior,$adapter::QUERY_MODE_EXECUTE)->current();
        $cantAnt= $dataPagoMesAnterior['Cant'];*/
        
        $cad = "";
        if ($valida  == 0 && $codIng != "" && $codIng != null) {
        	$cad = $codUni . '_' . $codIng . '_' . $fecEmi . '_' . $nroser . '_' . $nrodoc . '_' . $totPag.'_'.$fecVen;
        }

        return $cad;

    }

    public function getListGrupoUnidadConcepto($params)
    {	
    	$response=array();
    	$adapter=$this->getAdapter();
    	if(isset($params['idGrupoUnidad'])){
    		$idGrupoUnidad=$params['idGrupoUnidad'];
    		$queryListConceptoGrupo="SELECT c.con_vc_des as descripcion, guc.grup_in_cod idGrupoUnidad, guc.con_in_cod as idConcepto, guc.guc_vc_total as total, gu.uni_in_cod as idUnidad FROM grupo_unidad_concepto as guc INNER JOIN concepto as c ON guc.con_in_cod=c.con_in_cod INNER JOIN grupo_unidad as gu ON guc.grup_in_cod=gu.grup_in_cod WHERE guc.grup_in_cod = '".$idGrupoUnidad."' ";
    		$dataGrupoConcepto=$adapter->query($queryListConceptoGrupo,$adapter::QUERY_MODE_EXECUTE)->toArray();

    		$response['response']='success';

    		$response['rows']=array();
	        for ($i=0;$i<count($dataGrupoConcepto); $i++) {
	        	$response['rows'][$i]['descripcion']=$dataGrupoConcepto[$i]['descripcion'];
	        	$response['rows'][$i]['idGrupoUnidad']=$dataGrupoConcepto[$i]['idGrupoUnidad'];
	        	$response['rows'][$i]['idUnidad']=$dataGrupoConcepto[$i]['idUnidad'];
	        	$response['rows'][$i]['idConcepto']=$dataGrupoConcepto[$i]['idConcepto'];
	        	$response['rows'][$i]['total']=$dataGrupoConcepto[$i]['total'];
	        }

	        $response['totalEmision']=$this->getSumGrupoUnidadConceptos($idGrupoUnidad);

    	}else{
    		$response=array('response'=>'error','message'=>'Error interno en el servidor.','tipo'=>'error');
    	}

    	return $response;

    }

    private function getSumGrupoUnidadConceptos($idGrupoUnidad){
		$sql=new Sql($this->adapter);

		$selectSumCI=$sql->select()
			->from(array('coi'=>'grupo_unidad_concepto'))
			->columns(array('total'=>new Expression('SUM(coi.guc_vc_total)')))
			->where(array('coi.grup_in_cod'=>$idGrupoUnidad));
		
		$statement=$sql->prepareStatementForSqlObject($selectSumCI);
		$rowSumGrupoConceptoUnidad=$statement->execute()->current();

		return $rowSumGrupoConceptoUnidad['total'];
	}

    public function getAllGrupoUnidad($params)
    {
        $adapter=$this->getAdapter();
        $sql=new Sql($adapter);

        $queryValidateLastMonth="SELECT grup_in_cod as idGrupoUnidad, CONCAT(uni.uni_vc_tip,' ',uni.uni_vc_nom) as unidad, grupuni.uni_in_cod as idUnidad, grupuni.grup_in_month as month, grupuni.grup_in_year as year, grupuni.grup_vc_total as totalMonth FROM grupo_unidad AS grupuni INNER JOIN unidad AS uni ON grupuni.uni_in_cod=uni.uni_in_cod WHERE grupuni.uni_in_pad='".$params['idPadre']."' AND grupuni.grup_in_month='".$params['month']."' AND grupuni.grup_in_year='".$params['year']."' ";
        $dataGrupoUnidad=$adapter->query($queryValidateLastMonth,$adapter::QUERY_MODE_EXECUTE)->toArray();
        $response['rows']=array();
        for ($i=0;$i<count($dataGrupoUnidad); $i++) {
        	$response['rows'][$i]['idGrupoUnidad']=$dataGrupoUnidad[$i]['idGrupoUnidad'];
        	$response['rows'][$i]['unidad']=$dataGrupoUnidad[$i]['unidad'];
        	$response['rows'][$i]['idUnidad']=$dataGrupoUnidad[$i]['idUnidad'];
        	$response['rows'][$i]['month']=strtoupper($this->nombreMesNumero[$dataGrupoUnidad[$i]['month']]);
        	$response['rows'][$i]['numberMonth']=$dataGrupoUnidad[$i]['month'];
        	$response['rows'][$i]['year']=$dataGrupoUnidad[$i]['year'];
        	$response['rows'][$i]['totalMonth']=$dataGrupoUnidad[$i]['totalMonth'];
        }
        $response['totalUnidades']=$this->getTotalMesGrupoUnidad($params['idPadre'],$params['month'],$params['year']);

        //get unidades que tiene deudad
        $select=$sql->select()
            ->from('unidad')
            ->columns(array('id'=>'uni_in_cod','descripcion'=>new Expression("CONCAT(uni_vc_tip,' ',uni_vc_nom)")))
            ->where(array(
                'edi_in_cod'=>$this->idEdificio,
                'uni_in_est'=>1,
                'uni_in_pad'=>NULL,
                'uni_vc_tip!="GRUPO"'
            ));
        $selectString=$sql->buildSqlString($select);
        $listaUnidadesPadres=$adapter->query($selectString,$adapter::QUERY_MODE_EXECUTE)->toArray();

        for ($i=0;$i<count($listaUnidadesPadres); $i++) {
        	if($this->validateDebeCuotaUnidad($listaUnidadesPadres[$i]['id'],$params['month'],$params['year'])){
        		$response['listaUnidadesPadres'][$i]['idUnidad']=$listaUnidadesPadres[$i]['id'];
        		$response['listaUnidadesPadres'][$i]['descripcion']=$listaUnidadesPadres[$i]['descripcion'];
        	}
        }

        return $response;
    }

    private function validateDebeCuotaUnidad($idUnidad,$month,$year)
    {
    	$adapter=$this->getAdapter();
    	$sql=new Sql($adapter);
    	$select=$sql->select()
            ->from('ingreso')
            ->columns(array('count'=>new Expression('COUNT(*)')))
            ->where(array(
                'uni_in_cod'=>$idUnidad,
				'MONTH(ing_da_fecemi)='.$month,
				'YEAR(ing_da_fecemi)='.$year,
				'ing_in_est'=>1,
				'ing_do_sal!="0.00"'
            ));
        $selectString=$sql->buildSqlString($select);
        $dataUnidad=$adapter->query($selectString,$adapter::QUERY_MODE_EXECUTE)->current();
        if($dataUnidad['count']>0) return true;
        else return false;
    }

    private function getTotalMesGrupoUnidad($idPadre,$month,$year)
    {
    	$adapter=$this->getAdapter();
        $query="SELECT SUM(grup_vc_total) as totalMes FROM grupo_unidad WHERE uni_in_pad = '".$idPadre."' AND grup_in_month='".$month."' AND grup_in_year='".$year."' ";
        $data=$adapter->query($query,$adapter::QUERY_MODE_EXECUTE)->current();
    	return $data['totalMes']; 
    }

    private function validarCuotaReciente($idUnidad)
    {
    	$adapter=$this->getAdapter();
    	$queryIngreso="SELECT ing_in_cod,uni_in_cod,ing_da_fecemi as fecha,day(ing_da_fecemi) as day,month(ing_da_fecemi) as month,year(ing_da_fecemi) as year,ing_do_sal as saldo,ing_in_est as estado FROM ingreso AS ing WHERE uni_in_cod ='".$idUnidad."' AND ((SELECT COUNT(*) FROM concepto_ingreso AS ci WHERE ci.ing_in_cod=ing.ing_in_cod) > 0 ) ORDER BY ing_da_fecemi DESC limit 1";
    	$dataIngreso=$adapter->query($queryIngreso,$adapter::QUERY_MODE_EXECUTE)->current();
    	return $dataIngreso['year'];
    }

    public function validateCuota($params)
    {
    	
    	$adapter=$this->getAdapter();
    	$response=array();

    	if($params['ingresoId']!=''){
    		if($this->existeIngresosParciales($params['ingresoId'])){
				return array('response'=>'error','message'=>'Imposible actualizar el concepto por que existe pagos registrados.','tipo'=>'advertencia');
			}
    	}

    	//validar cuota reciente
    	$yearCuotaReciente=$this->validarCuotaReciente($params['idUnidad']);
    	//query
    	$queryIngreso="SELECT ing_in_cod,uni_in_cod,ing_da_fecemi as fecha,day(ing_da_fecemi) as day,month(ing_da_fecemi) as month,year(ing_da_fecemi) as year,ing_do_sal as saldo,ing_in_est as estado FROM ingreso AS ing  WHERE uni_in_cod ='".$params['idUnidad']."' AND ((SELECT COUNT(*) FROM concepto_ingreso AS ci WHERE ci.ing_in_cod=ing.ing_in_cod) > 0 ) AND year(ing.ing_da_fecemi)='".$yearCuotaReciente."' ORDER BY month(ing.ing_da_fecemi) desc limit 1";
    	$dataIngreso=$adapter->query($queryIngreso,$adapter::QUERY_MODE_EXECUTE)->current();
    	//
    	if($dataIngreso['estado']==0){ //pagado
    		$response = array('response'=>'error','message'=>'Imposible actualizar el concepto por que existe pagos registrados.','tipo'=>'advertencia');
    	}elseif($dataIngreso['estado']==1){ //debe
    		if($dataIngreso['month']==$params['month']){
    			if($dataIngreso['saldo']=='0.00' || $dataIngreso['saldo']==''){
    				$response = array('response'=>'error','message'=>'La unidad no se a podido añadir al grupo por que tiene una cuota de 0.00 ó esta vacio','tipo'=>'advertencia');
    			}else{
    				$response = array('response'=>'success','message'=>'La unidad si se puede agregar','tipo'=>'informativo','totalMes'=>$dataIngreso['saldo']);
    			}
    		}else{
    			//$response = array('response'=>'error','message'=>'La unidad no se puede agregar por que tiene una cuota reciente del '.date('d-m-Y',strtotime($dataIngreso['fecha'])),'tipo'=>'advertencia');
    			$response = array('response'=>'success','message'=>'La unidad si se puede agregar','tipo'=>'informativo','totalMes'=>$dataIngreso['saldo']);
    		} 
    	}elseif($dataIngreso['estado']==2){ //parcial
    		$response = array('response'=>'error','message'=>'Esta unidad no se puede registrar por que ya tiene pagos registrados','tipo'=>'advertencia');
    	}

    	return $response;

    }


    public function addGrupoUnidad($params)
    {
    	$fechaEmision=null;
    	$fechaVence=null;
    	$validIngreso=false;
    	$response=array();
    	$idIngresoPadre=trim($params['idGrupoIng']);
    	$adapter=$this->getAdapter();
    	$sql=new Sql($this->adapter);
    	$fechaEmision=date($params['year']."-".$params['month']."-".$params['dEmision']); //Y-m-d
		$fechaVence=date($params['year']."-".$params['month']."-".$params['dVence']);
		$numeracionDocumento=$this->numeracionDeDocumento($this->idEdificio);
        $detallesDeUnidad=$this->detallesDeUnidad($params['idPadre']);
        $totalMes=trim($params['totalMes']);

        $grupoUnidad="SELECT * FROM `grupo_unidad` WHERE uni_in_pad = '".$params['idPadre']."' AND uni_in_cod = '".$params['idUnidad']."' AND grup_in_month = '".$params['month']."' AND grup_in_year = '".$params['year']."' ";
        $dataGrupoUnidad=$adapter->query($grupoUnidad,$adapter::QUERY_MODE_EXECUTE)->toArray();
        
        if(count($dataGrupoUnidad)>0){
        	$response = array('response'=>'error','message'=>'La unidad ya esta agregado','tipo'=>'advertencia');
        }else{
        	//////////////////////////
        	if($idIngresoPadre==''){
	    		//insertar ingreso
	    		$query="INSERT INTO `ingreso` (`ing_in_cod`, `uni_in_cod`, `ing_in_usu`, `ing_in_resi`, `ing_do_monigv`, `ing_da_fecemi`, `ing_da_fecven`, `ing_do_sal`, `ing_in_est`, `ing_in_nroser`, `ing_in_nrodoc`) VALUES (NULL, '".$params['idPadre']."', '".$detallesDeUnidad['propietarioId']."', '".$detallesDeUnidad['residenteId']."', '0.00', '".$fechaEmision."', '".$fechaVence."', '".$params['totalMes']."', '1', '".$numeracionDocumento['serie']."', '".$numeracionDocumento['nroDoc']."')";
	    		$newIdIngreso=$adapter->query($query,$adapter::QUERY_MODE_EXECUTE)->getGeneratedValue();
	    		$idIngresoPadre=$newIdIngreso;

	    		//Actualizamos la numeracion de documentos en la tabla edificio.
				$dataNumeracion=array('edi_in_numser'=>$numeracionDocumento['serie'],'edi_in_numdoc'=>$numeracionDocumento['nroDoc']);
				$updateEdificio=$sql->update()->table('edificio')
					->set($dataNumeracion)->where(array('edi_in_cod'=>$params['edificioId']));
				$sql->prepareStatementForSqlObject($updateEdificio)->execute();

				$validIngreso=true;

	    	}else{
	    		//validar ingreso
	    		$query="SELECT * FROM ingreso WHERE ing_in_cod = '".$idIngresoPadre."' AND month(ing_da_fecemi)='".$params['month']."' AND year(ing_da_fecemi)='".$params['year']."' ";
	    		$dataIngreso=$adapter->query($query,$adapter::QUERY_MODE_EXECUTE)->toArray();
	    		if(count($dataIngreso)>0){
	    			//actualizar total mes (ingreso)
	    			$query="UPDATE ingreso SET ing_do_sal = (ing_do_sal + '$totalMes') WHERE ing_in_cod = '".$idIngresoPadre."' ";
	    			$adapter->query($query,$adapter::QUERY_MODE_EXECUTE);
	    		}
	    		$validIngreso=true;
	    	}

	    	if($validIngreso){

				//get list concepto ingreso
	    		$getIdIngreso="SELECT ing_in_cod as idIngreso FROM ingreso WHERE uni_in_cod = '".$params['idUnidad']."' AND month(ing_da_fecemi)='".$params['month']."' AND year(ing_da_fecemi)='".$params['year']."' ";
				$dataIngreso=$adapter->query($getIdIngreso,$adapter::QUERY_MODE_EXECUTE)->current();

				//list concepto ingreso
			    $dataConceptoIngreso=$this->listConceptoIngreso($dataIngreso['idIngreso']);
			    if(count($dataConceptoIngreso)>0){
			    	foreach($dataConceptoIngreso as $conceptoIngreso){
						$this->addGrupoUnidadConcepto($conceptoIngreso['idConcepto'],$conceptoIngreso['importe'],$conceptoIngreso['subTotal'],$conceptoIngreso['comentario'],$idIngresoPadre,$fechaEmision,$fechaVence,$numeracionDocumento['serie'],$numeracionDocumento['nroDoc']);
					}

					//insert grupo unidad
			        $queryGrupoUnidad="INSERT INTO `grupo_unidad` (`uni_in_pad`, `uni_in_cod`, `grup_in_month`, `grup_in_year`, `grup_vc_total`, `grup_da_fecregist`) VALUES ('".$params['idPadre']."','".$params['idUnidad']."','".$params['month']."','".$params['year']."','".$totalMes."','".date('Y-m-d')."')";
					$lastIdGrupoUnidad=$adapter->query($queryGrupoUnidad,$adapter::QUERY_MODE_EXECUTE)->getGeneratedValue();
			        
			        //insert grupo unidad concepto
					foreach($dataConceptoIngreso as $conceptoIngreso){
						//insert concepto ingreso
						$query="INSERT INTO `grupo_unidad_concepto` (`grup_in_cod`, `con_in_cod`, `guc_vc_total`) VALUES ('".$lastIdGrupoUnidad."','".$conceptoIngreso['idConcepto']."','".$conceptoIngreso['subTotal']."')";
						$adapter->query($query,$adapter::QUERY_MODE_EXECUTE);
					}

					//update unidad deuda
					$queryUpdateUnidad="UPDATE unidad SET uni_do_deu = (uni_do_deu - '$totalMes') WHERE uni_in_cod = '".$params['idUnidad']."' ";
					$adapter->query($queryUpdateUnidad,$adapter::QUERY_MODE_EXECUTE);

					//get total deuda ingreso
					/*$queryUpdateGrupoUnidad="SELECT SUM(ing_do_sal) as total FROM `ingreso` WHERE uni_in_cod = '".$params['idPadre']."' AND ing_in_est = 1";
					$dataUpdateGrupoUnidad=$adapter->query($queryUpdateGrupoUnidad,$adapter::QUERY_MODE_EXECUTE)->current();*/

					//update unidad grupo
					/*$queryUpdateUnidadGrupo="UPDATE unidad SET uni_do_deu = '".$totalGrupoUnidad."' WHERE uni_in_cod = '".$params['idPadre']."' ";
					$adapter->query($queryUpdateUnidadGrupo,$adapter::QUERY_MODE_EXECUTE);*/

					//update Grupo unidad
					$this->updateTotalGrupoUnidad($params['idPadre']);
					//delete ingreso de la unidad seleccionada
					$this->deleteGrupoConceptoIngreso($dataIngreso['idIngreso'], $totalMes);

					//rpta
					$response = array('response'=>'success','message'=>'Unidad agregado correctamente','tipo'=>'informativo','idIngreso'=>$idIngresoPadre);
			    }else{
			    	$response = array('response'=>'error','message'=>'El ingreso '.$dataIngreso['idIngreso'].' no tiene conceptos','tipo'=>'advertencia');
			    }
	    	}
	    	//////////////////////////

        }

		return $response;

    }

    private function updateTotalGrupoUnidad($idPadre)
    {
    	$sql=new Sql($this->adapter);
		$adapter=$this->getAdapter();
    	//SELECT SUM(ing_do_sal) as total FROM `ingreso` WHERE uni_in_cod = '".$params['idPadre']."' AND ing_in_est = 1
    	$select=$sql->select()
			->from('ingreso')
			->columns(array('total'=>new Expression('SUM(ing_do_sal)')))
			->where(array('uni_in_cod'=>$idPadre,'ing_in_est'=>1));
		$sqlString=$sql->buildSqlString($select);
		$rsSelect=$adapter->query($sqlString,$adapter::QUERY_MODE_EXECUTE)->current();
		//update grupo unidad
		//UPDATE unidad SET uni_do_deu = '".$totalGrupoUnidad."' WHERE uni_in_cod = '".$params['idPadre']."'
		$updateCI=$sql->update()
		->table('unidad')
		->set(array('uni_do_deu'=>$rsSelect['total']))
		->where(array('uni_in_cod'=>$idPadre));
		$sql->prepareStatementForSqlObject($updateCI)->execute()->count();
    }

    private function deleteGrupoConceptoIngreso($idIngreso, $totalMes)
    {
    	$adapter=$this->getAdapter();
    	//delete 
    	$queryDeleteIngreso="DELETE FROM concepto_ingreso WHERE ing_in_cod = '".$idIngreso."' ";
		$adapter->query($queryDeleteIngreso,$adapter::QUERY_MODE_EXECUTE);
		//update ingreso
		$queryUpdateIngreso="UPDATE ingreso SET ing_do_sal = (ing_do_sal - '$totalMes') WHERE ing_in_cod ='".$idIngreso."' ";
		$adapter->query($queryUpdateIngreso,$adapter::QUERY_MODE_EXECUTE);
    }

    private function listConceptoIngreso($idIngreso)
    {
    	$adapter=$this->getAdapter();
    	$query="SELECT con_in_cod as idConcepto, ing_in_cod as idIngreso, coi_da_fecemi as fechaEmision, coi_do_imp as importe, coi_do_subtot as subTotal, coi_te_com as comentario, coi_da_fecven as fechaVence, coi_in_usureg as idUsuario, coi_in_nroser as numeroSerie, coi_in_nrodoc as numeracionDocumento FROM concepto_ingreso WHERE ing_in_cod = '".$idIngreso."' ";
		$data=$adapter->query($query,$adapter::QUERY_MODE_EXECUTE)->toArray();
    	if(count($data)>0) return $data;
    	else return array(null);
    }

    private function addGrupoUnidadConcepto($idConcepto,$importe,$subTotal,$comentario,$idIngreso,$fechaEmision,$fechaVence,$numeroSerie,$numeracionDocumento)
	{
		$adapter=$this->getAdapter();
		//$numeracionDocumento=$this->numeracionDeDocumento($this->idEdificio);
		
		$query="SELECT con_in_cod as idConcepto, coi_do_imp as importe, coi_do_subtot as subTotal, coi_te_com as comentario FROM `concepto_ingreso` where con_in_cod = '".$idConcepto."' AND ing_in_cod='".$idIngreso."' ";
		$data=$adapter->query($query,$adapter::QUERY_MODE_EXECUTE)->toArray();
		if(count($data)>0){
			//echo "update::: ".$data[0]['idConcepto']."||".$data[0]['importe']."||".$importe."<br>";
			$query="UPDATE concepto_ingreso SET coi_do_imp = (coi_do_imp + '$importe'), coi_do_subtot = (coi_do_subtot + '$subTotal') WHERE con_in_cod = '".$idConcepto."' AND ing_in_cod = '".$idIngreso."' ";
    		$adapter->query($query,$adapter::QUERY_MODE_EXECUTE);
		}else{
			//echo "insert:: ".$idConcepto."||".$importe."<br>";
			$query="INSERT INTO `concepto_ingreso` (`con_in_cod`, `ing_in_cod`, `coi_da_fecemi`, `coi_do_imp`, `coi_do_subtot`, `coi_te_com`, `coi_da_fecven`, `coi_in_usureg`, `coi_in_nroser`, `coi_in_nrodoc`) VALUES ('".$idConcepto."','".$idIngreso."','".$fechaEmision."','".$importe."','".$subTotal."','".$comentario."','".$fechaVence."','".$this->idUsuario."','".$numeroSerie."','".$numeracionDocumento."')";
			$adapter->query($query,$adapter::QUERY_MODE_EXECUTE);
		}

	}

	public function deleteUnidadAsociado($params)
	{
		$sql=new Sql($this->adapter);
		$adapter=$this->getAdapter();
		
		$idUnidad=$params['idUnidad'];
		$month=$params['month'];
		$year=$params['year'];
		$response['response']='error';

		/*GRUPO UNIDAD*/
		$queryGrupoUnidad="SELECT grup_in_cod as idGrupoUnidad, uni_in_pad as idUnidadPadre, uni_in_cod as idUnidad, grup_vc_total as total, grup_in_month as mes, grup_in_year as anio from grupo_unidad where uni_in_cod = '".$idUnidad."' AND grup_in_month = '".$month."' AND grup_in_year = '".$year."' ";
		$dataGrupoUnidad=$adapter->query($queryGrupoUnidad,$adapter::QUERY_MODE_EXECUTE)->current();
		$idGrupoUnidad=$dataGrupoUnidad['idGrupoUnidad'];
		$idUnidadPadre=$dataGrupoUnidad['idUnidadPadre'];
		$mes=$dataGrupoUnidad['mes'];
		$anio=$dataGrupoUnidad['anio'];

		/*GRUPO UNIDAD CONCEPTO*/
		$queryGrupoUnidadConcepto="SELECT con_in_cod as idConcepto, guc_vc_total as total from grupo_unidad_concepto where grup_in_cod = '".$idGrupoUnidad."' ";
		$dataGrupoUnidadConcepto=$adapter->query($queryGrupoUnidadConcepto,$adapter::QUERY_MODE_EXECUTE)->toArray();

		/*INGRESO > GET ID INGRESO*/
		$queryIngreso="SELECT ing_in_cod as idIngreso FROM ingreso WHERE uni_in_cod = '".$idUnidadPadre."' AND MONTH(ing_da_fecemi) = '".$mes."' AND YEAR(ing_da_fecemi) = '".$anio."' ";
		$dataIngreso=$adapter->query($queryIngreso,$adapter::QUERY_MODE_EXECUTE)->current();
		$idIngreso=$dataIngreso['idIngreso'];

		/*CONCEPTO INGRESO*/
		$queryConceptoIngreso="SELECT con_in_cod as idConcepto, ing_in_cod as idIngreso, coi_do_imp as importe, coi_do_subtot as subtotal FROM concepto_ingreso WHERE ing_in_cod = '".$idIngreso."' ";
		$dataConceptoIngreso=$adapter->query($queryConceptoIngreso,$adapter::QUERY_MODE_EXECUTE)->toArray();

		$validUpdateConcepto=false;
		$connectionDb=null;
		try {
			$connectionDb=$this->getAdapter()->getDriver()->getConnection();
			$connectionDb->beginTransaction();	
			/****************************************************************/
			foreach($dataConceptoIngreso as $conceptoIngreso){

				foreach($dataGrupoUnidadConcepto as $grupoUnidadConcepto){
					if($grupoUnidadConcepto['idConcepto']==$conceptoIngreso['idConcepto']){
						$totalConcepto=$conceptoIngreso['importe'] - $grupoUnidadConcepto['total'];
						/*UPDATE CONCEPTO INGRESO*/
						$dataConcepto=[
							"coi_do_imp"=>$totalConcepto,
							"coi_do_subtot"=>$totalConcepto
						];
						$updateConceptoIngreso=$sql->update()
						->table('concepto_ingreso')
						->set($dataConcepto)
						->where(
							[
								'con_in_cod'=>$grupoUnidadConcepto['idConcepto'],
								'ing_in_cod'=>$idIngreso
							]
						);
						$statementUpdate=$sql->buildSqlString($updateConceptoIngreso);
						$connectionDb->execute($statementUpdate);
						$validUpdateConcepto=true;
					}
				}
			}

			if($validUpdateConcepto){
				/*GET TOTAL CONCEPTO INGRESO*/
				$queryConceptoIngreso="SELECT SUM(coi_do_imp) as total FROM concepto_ingreso WHERE ing_in_cod = '".$idIngreso."' ";
				$dataConceptoIngreso=$adapter->query($queryConceptoIngreso,$adapter::QUERY_MODE_EXECUTE)->current();
				$totalConceptoIngreso=$dataConceptoIngreso['total'];

				/*UPDATE TOTAL INGRESO*/
				$dataIngreso=["ing_do_sal"=>$totalConceptoIngreso];
				$updateIngreso=$sql->update()
				->table('ingreso')
				->set($dataIngreso)
				->where(['ing_in_cod'=>$idIngreso]);
				$statementUpdate=$sql->buildSqlString($updateIngreso);
				$connectionDb->execute($statementUpdate);

				/*UPDATE DEUDA UNIDAD*/
				$dataUnidad=["uni_do_deu"=>$totalConceptoIngreso];
				$updateUnidad=$sql->update()
				->table('unidad')
				->set($dataUnidad)
				->where(['uni_in_cod'=>$idUnidadPadre]);
				$statementUpdate=$sql->buildSqlString($updateUnidad);
				$connectionDb->execute($statementUpdate);

				/*DELETE GRUPO UNIDAD*/
				$deleteGrupoUnidad=$sql->delete('grupo_unidad')
				->where(
					[
						'uni_in_cod'=>$idUnidad,
						'grup_in_month'=>$mes,
						'grup_in_year'=>$anio
					]
				);
				$statementDelete=$sql->buildSqlString($deleteGrupoUnidad);
				$connectionDb->execute($statementDelete);

				/*DELETE GRUPO UNIDAD CONCEPTO*/
				$deleteGrupoUnidadConcepto=$sql->delete('grupo_unidad_concepto')
				->where(['grup_in_cod'=>$idGrupoUnidad]);
				$statementDelete=$sql->buildSqlString($deleteGrupoUnidadConcepto);
				$connectionDb->execute($statementDelete);
				$connectionDb->commit();
				$response['response']='success';

			}
			/****************************************************************/
		}catch (Exception $e) {
			$connectionDb->rollback();
			throw $e;
		}

		return $response;

	}
	
}

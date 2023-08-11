<?php
/**
 * edificioOk (https://www.edificiook.com).
 * creado por: Meler Carranza, 23/03/2016.
 * ultima modificacion por: Meler Carranza.
 * Fecha Modificacion: 09/04/2016.
 * Descripcion: Script para guardar datos del presupuesto.
 *
 * @author    Fidel Thompson Fonseca.
 * @link      https://www.edificiook.com
 * @copyright Copyright (c) 2011-2016 KND S.A.C (http://www.knd.pe)
 * @license   http://www.edificiook.com/license/comercial Software Comercial
 */

namespace Finanzas\Model;
use Zend\Db\Sql\Sql;
use Zend\Db\Sql\Select;
use Zend\Db\TableGateway\AbstractTableGateway;
use Zend\Db\Adapter\Adapter;
use Zend\Db\ResultSet\ResultSet;
use Zend\Db\Sql\Expression;

class PresupuestoTable extends AbstractTableGateway{
	private $edificioId=null;
	private $empresaId=null;
	private $nodeId=null;
	private $yearSelected=null;
	private $nombreMes=array("Enero","Febrero","Marzo","Abril","Mayo","Junio","Julio","Agosto","Septiembre","Octubre","Noviembre","Diciembre");

	private $nroNivel=null;
    private $parentId=null;

	public function __construct(Adapter $adapter)
	{
		$this->adapter=$adapter;
	}

	public function getParametrosDeEdificio($edificioId){
		$sql=new Sql($this->adapter);
		$selectEdificio=$sql->select()
			->from(array('edi'=>'edificio'))
			->columns(array(
						'fechaSuscripcion'=>'edi_da_fecsus',
					))
			->where(array('edi_in_cod'=>$edificioId));

		$statement=$sql->prepareStatementForSqlObject($selectEdificio);
		$rsEdificio=$statement->execute()->current();
		return $rsEdificio;
	}

	public function getConceptos($params){
		$this->edificioId=$params['edificioId'];
		$this->empresaId=$params['empresaId'];
		$tipo=isset($params['tipo'])?$params['tipo']:'INGRESO';
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


	public function getPresupuestoParaGrid($params){
	
		$this->edificioId=$params['edificioId'];
		$this->empresaId=$params['empresaId'];
		$this->tipousuario=$params['tipousuario'];
		$this->nodeId=isset($params['nodeid'])?$params['nodeid'] : null;
		$this->yearSelected=isset($params['year'])?$params['year']:date('Y');
	
		$this->nroNivel=isset($params['n_level'])? $params['n_level'] : null;
		if($this->tipousuario=='externo'){
			$ChildRowsNivel0=$this->getChildRowsNivel0();
		}else{
			$ChildRowsNivel0=$this->getChildRowsNivel0();
		}
		switch ($this->nroNivel){
				case '0':
					return $ChildRowsNivel0;
					break;
				case '1':
					return $this->getChildRowsNivel1();
					break;
				case '2':
					return $this->getChildRowsNivel2();
					break;
				default:
					return $this->getRowsDafault();
					break;
		}
	}


	public function addPresupuesto($params){
		$sql=new Sql($this->adapter);

		$data=array(
			'edi_in_cod'=>$params['edificioId'],
			'con_in_cod'=>$params['selConcepto'],
			'pre_ch_periodo'=>($params['selMes']<10)? '0'.$params['selMes']:$params['selMes'],
			'pre_ch_anio'=>$params['selYear'],
			'pre_vc_tip'=>'PROYECTADO',
			'pre_vc_mov'=>$params['selTipo'],
			'pre_do_mon'=>$params['textTotal'],
			'pre_in_est'=>1
		);

		$insertPresupuesto=$sql->insert('presupuesto')
			->values($data);
		$statement=$sql->prepareStatementForSqlObject($insertPresupuesto);
		$result=$statement->execute()->count();

		$response=array('tipo'=>'error','mensaje'=>'Ocurrió un error desconocido en el servidor al intentar registrar el presupuesto.');
		if($result>0){
			/////////save auditoria////////
			$params['tabla']='Presupuesto';
			$params['comNumRegistro']='edi_in_cod:'.$params['edificioId'].", con_in_cod:".$params['selConcepto'];
			$params['accion']='Guardar - Presuperto del Mes de '.$this->nombreMes[$params['selMes'] - 1].' del '.$params['selYear'];
			$this->saveAuditoria($params);
			///////////////////////////////
			$response=array('tipo'=>'informativo','mensaje'=>'Los datos se guardaron con éxito.');
		}
		return $response;
	}

	public function updatePresupuesto($params){
		
		$sql=new Sql($this->adapter);
		$data=array(
			'pre_vc_mov'=>$params['selTipo'],
			'pre_do_mon'=>$params['textTotal'],
			'pre_in_est'=>1,
		);

		$updatePresupuesto=$sql->update()
			->table('presupuesto')
			->set($data)
			->where(
				array(
					'edi_in_cod'=>$params['edificioId'],
					'con_in_cod'=>$params['selConcepto'],
					'pre_ch_periodo'=>($params['selMes']<10)?'0'.$params['selMes']:$params['selMes'],
					'pre_ch_anio'=>$params['selYear'],
				)
			);
		$statement=$sql->prepareStatementForSqlObject($updatePresupuesto);
		$result=$statement->execute()->count();

		$response=array('tipo'=>'advertencia','mensaje'=>'No surgió ningún cambio en el registro existente.');
		if($result>0){
			/////////save auditoria////////
			$params['tabla']='Presupuesto';
			$params['comNumRegistro']='edi_in_cod:'.$params['edificioId'].", con_in_cod:".$params['selConcepto'];
            $params['accion']='Editar - Presuperto del Mes de '.$this->nombreMes[$params['selMes'] - 1].' del '.$params['selYear'];
            $this->saveAuditoria($params);
            ///////////////////////////////
			$response=array('tipo'=>'informativo','mensaje'=>'Los datos se actualizaron con éxito.');
		}
		return $response;
	}

	public function existeConceptoPresupuestado($params){
		$sql=new Sql($this->adapter);
		$select=$sql->select()
		->from('presupuesto')
		->columns(array('count'=>new Expression('COUNT(*)')))
		->where(
			array(
				'edi_in_cod'=>$params['edificioId'],
				'con_in_cod'=>$params['selConcepto'],
				'pre_ch_periodo'=>($params['selMes']<10)?'0'.$params['selMes']:$params['selMes'],
				'pre_ch_anio'=>$params['selYear'],
			)
		);

		$statement=$sql->prepareStatementForSqlObject($select);
		$rsConceptos=$statement->execute($statement)->current();
		if($rsConceptos['count']>0){
			return true;
		}
		return false;
	}

	private function getRowsDafault(){
		$response=array();

		$response['rows'][0]['id'] = 'PROYECTADO';
		$response['rows'][0]['cell']=array('PROYECTADO','','','','','','','','','','','','','0','0',"NULL",false,false,false);

		$response['rows'][1]['id'] = 'EJECUTADO';
		$response['rows'][1]['cell']=array('EJECUTADO','','','','','','','','','','','','','0','0',"NULL",false,false,false);

		return $response;
	}

	private function getChildRowsNivel0(){
		$adapter=$this->getAdapter();
		$sql=new Sql($this->adapter);
		$response=array();
		if($this->nodeId=='PROYECTADO'){
			$response['rows'][0]['id']='INGRESO_PROYECTADO';
			$response['rows'][0]['cell']=$this->getGridRowParaNivel0('PROYECTADO','INGRESO');
			$response['rows'][1]['id']='EGRESO_PROYECTADO';
			$response['rows'][1]['cell']=$this->getGridRowParaNivel0('PROYECTADO','EGRESO');
		}elseif ($this->nodeId=='EJECUTADO') {
			$response['rows'][0]['id']='INGRESO_EJECUTADO';
			$response['rows'][0]['cell']=$this->getGridRowParaNivel0('EJECUTADO','INGRESO');
			$response['rows'][1]['id']='EGRESO_EJECUTADO';
			$response['rows'][1]['cell']=$this->getGridRowParaNivel0('EJECUTADO','EGRESO');
		}
		return $response;
	}

	private function getChildRowsNivel1(){
		$adapter=$this->getAdapter();
		$sql=new Sql($adapter);

		$arrayNodeId=explode('_', $this->nodeId);
		$movimiento=isset($arrayNodeId[0])?$arrayNodeId[0]:null; // ingreso | egreso ó NULL
		$tipo=isset($arrayNodeId[1])?$arrayNodeId[1]:null; //proyectado | ejecutado ó NUll

		$selectConceptos=$sql->select()
			->from('concepto')
			->columns(array('id'=>'con_in_cod','conceptoNombre'=>'con_vc_des'))
			->where(
				array(
					'con_vc_tip'=>$movimiento,
					'emp_in_cod'=>array(0,$this->empresaId),
					'con_in_est'=>1
				)
			);
		$selectConceptosString=$sql->buildSqlString($selectConceptos);

		//var_dump($selectConceptosString);

		$rsConceptos=$adapter->query($selectConceptosString,$adapter::QUERY_MODE_EXECUTE)->toArray();
		
		$response=array();
		if(!empty($rsConceptos)){
			$i=0;
			foreach ($rsConceptos as $key => $value){
				$currentConceptoId=$value['id'];
				$currentConceptoNombre=$value['conceptoNombre'];
				$response['rows'][$i]['id']=substr($movimiento,0,1).substr($tipo,0,1).'_'.$currentConceptoId;
				$response['rows'][$i]['cell']=$this->getGridRowParaNivel1($tipo,$movimiento,$currentConceptoId,$currentConceptoNombre);
				$i++;
			}
		}
		return $response;
	}

	private function getChildRowsNivel2(){
		$adapter=$this->getAdapter();
		$sql=new Sql($adapter);
		$conceptoId=str_replace("EE_", "", $this->nodeId);
		$selectConcepto=$sql->select()
			->from(array('egr'=>'egreso'))
			->columns(array(new Expression('DISTINCT (egr.prv_in_cod) as proveedorId')))
			->join(array('prv'=>'proveedor'),'prv.prv_in_cod=egr.prv_in_cod',array('razonsocial'=>'prv_vc_razsoc'))
			->where(
				array(
					'edi_in_cod'=>$this->edificioId,
					'con_in_cod'=>$conceptoId,
				)
			);
		$sqlString=$sql->buildSqlString($selectConcepto);

		$rsConceptosProveedor=$adapter->query($sqlString,$adapter::QUERY_MODE_EXECUTE)->toArray();

		$increment=0;
		$response=array();
		foreach ($rsConceptosProveedor as $key => $value){
			
			$currentProveedorId=$value['proveedorId'];
			$currentRazonSocial=$value['razonsocial'];

			$selectSum=$sql->select()
				->from('egreso')
				->columns(array('mes'=>new Expression('MONTH(egr_da_fecemi)'),'totalMes'=>new Expression('SUM(egr_do_imp)')))
				->where(array(
						'edi_in_cod'=>$this->edificioId,
						'YEAR(egr_da_fecemi)='.$this->yearSelected,
						'con_in_cod'=>$conceptoId,
						'prv_in_cod'=>$currentProveedorId,
					))->group('mes');
			$sqlStringSelectSum=$sql->buildSqlString($selectSum);
			$rsSelect=$adapter->query($sqlStringSelectSum,$adapter::QUERY_MODE_EXECUTE)->toArray();


			$rowGrid=array($currentRazonSocial);
			$totalAnual=0;
			$rsIndex=0;
			for($i=1;$i<=12;$i++){
				$sumMesPorConceptoProveedor=isset($rsSelect[$rsIndex]['mes'])?$rsSelect[$rsIndex]['mes']:null;
				if($i==(int)$sumMesPorConceptoProveedor){
					$rowGrid[]='<span style="cursor:pointer" onclick="Action.viewDetalle('.$conceptoId.','.$currentProveedorId.','.$i.')">'.number_format($rsSelect[$rsIndex]['totalMes'],2,'.',',')."</span>";
					$totalAnual+=$rsSelect[$rsIndex]['totalMes'];
					$rsIndex++;
				}else{
					$rowGrid[]='';
				}	
			}

			$rowGrid[]=number_format($totalAnual,2,'.',',');
			$rowGrid[]=$this->nroNivel+1;
			$rowGrid[]=$this->nodeId;
			$rowGrid[]=true;
			$rowGrid[]=true;

			$response['rows'][$increment]['id']='Prv_'.$currentProveedorId;
			$response['rows'][$increment]['cell']=$rowGrid;
			$increment++;
		}

		return $response;
	}



	#asistentes

	private function getGridRowParaNivel0($tipo,$movimiento){
		$adapter=$this->getAdapter();
		$sql=new Sql($adapter);

		if($tipo=='PROYECTADO'){
			$select=$sql->select()
			->from('presupuesto')
			->columns(array('mes'=>'pre_ch_periodo','totalMes'=>new Expression('SUM(pre_do_mon)')))
			->where(
				array(
					'edi_in_cod'=>$this->edificioId,
					'pre_ch_anio'=>$this->yearSelected,
					'pre_vc_tip'=>$tipo,
					'pre_vc_mov'=>$movimiento,
					'pre_in_est'=>1,
				))
			->group('mes');
		}else{
			switch($movimiento){
				case 'INGRESO':
					$select=$sql->select()
						->from(array('coi'=>'concepto_ingreso'))
						->columns(array(
									'mes'=>new Expression('MONTH(coi.coi_da_fecemi)'),
									'totalMes'=>new Expression('SUM(coi.coi_do_subtot)') 
								))
						->join(array('ing'=>'ingreso'),'coi.ing_in_cod=ing.ing_in_cod')
						->join(array('uni'=>'unidad'),'uni.uni_in_cod=ing.uni_in_cod')
						->where(array('uni.edi_in_cod'=>$this->edificioId,
									'YEAR(coi.coi_da_fecemi)='.$this->yearSelected))
						->group('mes');
					break;
				case 'EGRESO':
					$select=$sql->select()
						->from(array('egr'=>'egreso'))
						->columns(array(
									'mes'=>new Expression('MONTH(egr_da_fecemi)'),
									'totalMes'=>new Expression('SUM(egr_do_imp)') 
								))
						->where(array('edi_in_cod'=>$this->edificioId,
									'YEAR(egr_da_fecemi)='.$this->yearSelected))
						->group('mes');
					break;
				default:
					return null;
					break;
			}
		}

		$sqlString=$sql->buildSqlString($select);
		$rsSelect=$adapter->query($sqlString,$adapter::QUERY_MODE_EXECUTE)->toArray();
		
		$rowGrid=array($movimiento);
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
		$rowGrid[]=1;
		$rowGrid[]=$tipo;
		$rowGrid[]=false;
		$rowGrid[]=false;
		return $rowGrid;
	}


	private function getGridRowParaNivel1($tipo,$movimiento,$conceptoId,$conceptoNombre){

		$adapter=$this->getAdapter();
		$sql=new Sql($adapter);

		$nroProveedorPorConcepto=0;
		if($tipo=='PROYECTADO'){
			$select=$sql->select()
			->from('presupuesto')
			->columns(array('mes'=>'pre_ch_periodo','totalMes'=>new Expression('SUM(pre_do_mon)')))
			->where(
				array(
					'edi_in_cod'=>$this->edificioId,
					'con_in_cod'=>$conceptoId,
					'pre_ch_anio'=>$this->yearSelected,
					'pre_vc_tip'=>$tipo,
					'pre_vc_mov'=>$movimiento,
					'pre_in_est'=>1,
				))
			->group('mes');
		}else{
			$selectProveedorPorConcepto=$sql->select()
				->from('egreso')
				->columns(array('count'=>new Expression('COUNT(DISTINCT(prv_in_cod))')))
				->where(
					array(
						'edi_in_cod'=>$this->edificioId,
						'con_in_cod'=>$conceptoId,
						'YEAR(egr_da_fecemi)='.$this->yearSelected
						)
				);
			$sqlString=$sql->buildSqlString($selectProveedorPorConcepto);
			$rsProveedorPorConcepto=$adapter->query($sqlString,$adapter::QUERY_MODE_EXECUTE)->current();
			$nroProveedorPorConcepto=$rsProveedorPorConcepto['count'];
			switch($movimiento){
				case 'INGRESO':
					$select=$sql->select()
						->from(array('coi'=>'concepto_ingreso'))
						->columns(array(
									'mes'=>new Expression('MONTH(coi.coi_da_fecemi)'),
									'totalMes'=>new Expression('SUM(coi.coi_do_subtot)') 
								))
						->join(array('ing'=>'ingreso'),'coi.ing_in_cod=ing.ing_in_cod')
						->join(array('uni'=>'unidad'),'uni.uni_in_cod=ing.uni_in_cod')
						->where(array('uni.edi_in_cod'=>$this->edificioId,
									'coi.con_in_cod'=>$conceptoId,
									'YEAR(coi.coi_da_fecemi)='.$this->yearSelected))
						->group('mes');
					break;
				case 'EGRESO':
					$select=$sql->select()
						->from(array('egr'=>'egreso'))
						->columns(array(
									'mes'=>new Expression('MONTH(egr_da_fecemi)'),
									'totalMes'=>new Expression('SUM(egr_do_imp)') 
								))
						->where(array('edi_in_cod'=>$this->edificioId,
									'con_in_cod'=>$conceptoId,
									'YEAR(egr_da_fecemi)='.$this->yearSelected))
						->group('mes');
					break;
				default:
					return null;
					break;
			}
		}

		$sqlString=$sql->buildSqlString($select);
		
		$rsSelect=$adapter->query($sqlString,$adapter::QUERY_MODE_EXECUTE)->toArray();
		
		$rowGrid=array($conceptoNombre);
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
		$rowGrid[]=2;
		$rowGrid[]=$movimiento."_".$tipo;
		$rowGrid[]=$nroProveedorPorConcepto==0?true:false;
		$rowGrid[]=false;
		return $rowGrid;
	}

	private function saveAuditoria($params)
    {   
        $adapter=$this->getAdapter();
        $sql=new Sql($adapter);
        $insert = $sql->insert('auditoria');
        $data = array(
            'usu_in_cod'=> $params['idUsuario'], //idusuario
            'edi_in_cod'=> $params['edificioId'], //idedificio
            'aud_opcion'=> 'Finanzas > Presupuesto', //ruta
            'aud_accion'=> $params['accion'], //accion
            'aud_fecha'=> date('Y-m-d H:i:s'), //fecha y hora
            'aud_ip'=> $_SERVER['REMOTE_ADDR'], //ip publica
            'aud_tabla'=> $params['tabla'], //tabla
            'aud_in_numra'=> '', //registro afectado
            'aud_info_adi'=> $_SERVER['HTTP_USER_AGENT'], //agente
            'aud_comnumra'=> $params['comNumRegistro'] //comentario del registro afectado
        );
        $insert->values($data);
        $sql->prepareStatementForSqlObject($insert)->execute()->count();
    }

}
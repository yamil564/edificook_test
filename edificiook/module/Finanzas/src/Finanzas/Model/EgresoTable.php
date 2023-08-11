<?php
/**
 * edificioOk (https://www.edificiook.com).
 * creado por: Meler Carranza, 11/04/2016.
 * ultima modificacion por: Meler Carranza.
 * Fecha Modificacion: 05/05/2016.
 * Descripcion: Script para guardar datos de egreso.
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
use Zend\Db\Sql\Expression;

class EgresoTable extends AbstractTableGateway{
	private $edificioId=null;
    private $empresaId=null;
    private $nodeId=null;
    private $yearSelected=null;
    private $mesSelected=null;
    private $nombreMes=array('Enero','Febrero','Marzo','Abril','Mayo',
    					'Junio','Julio','Agosto','Septiembre',
    					'Octubre','Noviembre','Diciembre');
    private $nroNivel=null;
    private $parentId=null;

	public function __construct(Adapter $adapter)
	{
		$this->adapter=$adapter;
	}

	public function egresosParaGridAnual($params){
		$this->edificioId=$params['edificioId'];
		$this->empresaId=$params['empresaId'];
		$this->nodeId=isset($params['nodeid'])?$params['nodeid'] : null;
		$this->yearSelected=isset($params['year'])?$params['year']:date('Y');
		$this->nroNivel=isset($params['n_level'])? $params['n_level'] : null;
	
		switch ($this->nroNivel){
			case '0':
				return $this->getRowGridAnualNivel0();
				break;
			case '1':
				return $this->getRowGridAnualNivel1();
				break;
			case '2':
				return $this->getChildRowsNivel2();
				break;
			default:
				return $this->getRowsDafaultGridAnual();
				break;
		}


		$response['rows'][0]['id']='INGRESO_EJECUTADO';
		$response['rows'][0]['cell']=$this->getGridRowParaNivel0('EJECUTADO','INGRESO');
		$response['rows'][1]['id']='EGRESO_EJECUTADO';
		$response['rows'][1]['cell']=$this->getGridRowParaNivel0('EJECUTADO','EGRESO');

	}


	private function getRowsDafaultGridAnual(){

		$response=array();
		//row totales
		$rowTotalEgreso=$this->getGridRowTotal('TOTAL EGRESO');
		$response['rows'][0]['id']='TOTAL_EGRESO';
		$response['rows'][0]['cell']=$rowTotalEgreso;

		$rowTotalPagado=$this->getGridRowTotal('T. PAGADO');
		$response['rows'][1]['id']='TOTAL_PAGADO';
		$response['rows'][1]['cell']=$rowTotalPagado;

		$rowPendienteDePago=$this->getGridRowTotalPendientePago($rowTotalEgreso,$rowTotalPagado);
		$response['rows'][2]['id']='TOTAL_PENDIENTE';
		$response['rows'][2]['cell']=$rowPendienteDePago;

		$rowPendienteDeCobro=$this->getGridRowTotal('EGRESO BANCARIO');
		$response['rows'][3]['id']='TOTAL_BANCO';
		$response['rows'][3]['cell']=$rowPendienteDeCobro;

		//row grupo de conceptos.

		$adapter=$this->getAdapter();
		$sql=new Sql($this->adapter);
		$selectGrupoDeConceptosEgreso=$sql->select()
			->from(array('cog'=>'concepto_grupo'))
			->columns(
				array(
					'id'=>'cog_in_cod',
					'grupoNombre'=>"cog_vc_des"
				)
			)
			->join(array('con'=>'concepto'),'cog.cog_in_cod=con.cog_in_cod',array())
			->where(
				array(
					'cog.cog_in_est!=0',
					'con.con_vc_tip'=>'EGRESO'
				)
			)->group('id');

		$selectString=$sql->buildSqlString($selectGrupoDeConceptosEgreso);
		$rsGruposDeConceptosEgreso=$adapter->query($selectString,$adapter::QUERY_MODE_EXECUTE)->toArray();

		if(!empty($rsGruposDeConceptosEgreso)){
			$i=4;
			foreach ($rsGruposDeConceptosEgreso as $key => $value){
				$currentGrupoConceptoId=$value['id'];
				$currentGrupoConceptoNombre=$value['grupoNombre'];

				//comprobar si tiene conceptos este grupo_concepto.
				$selectCount=$sql->select()->from('concepto')->columns(array('count'=>new Expression('COUNT(*)')))
					->where(array('emp_in_cod'=>array(0,$this->empresaId),'cog_in_cod'=>$currentGrupoConceptoId));
				$statementCount=$sql->prepareStatementForSqlObject($selectCount);
				$countConceptos=$statementCount->execute()->current()['count'];
				
				$leaf=$countConceptos > 0 ? false:true;

				$response['rows'][$i]['id']='CG_'.$currentGrupoConceptoId;
				$response['rows'][$i]['cell']=array($currentGrupoConceptoNombre,'','','','','','','','','','','','','',0,'NULL',$leaf,false);
				$i++;
			}
		}
		return $response;
	}


	/*
		Retorna datos en las finas  [GASTO TOTAL, PAGADO, PENDIENTE].
	*/
	private function getGridRowTotal($tipoFila){
		$level=0;
		$adapter=$this->getAdapter();
		$sql=new Sql($adapter);
		if($tipoFila=='TOTAL EGRESO'){
			$select=$sql->select()
				->from(array('egr'=>'egreso'))
				->columns(array(
							'mes'=>new Expression('MONTH(egr.egr_da_fecemi)'),
							'totalMes'=>new Expression('SUM(egr.egr_do_imp)') 
				))
				->where(array('egr.edi_in_cod'=>$this->edificioId,
							'YEAR(egr.egr_da_fecemi)='.$this->yearSelected))
				->group('mes');
		}else if($tipoFila=='T. PAGADO'){
			$level=1;
			$select=$sql->select()
				->from(array('epa'=>'egreso_parcial'))
				->columns(array(
						'mes'=>new Expression('MONTH(egr.egr_da_fecemi)'),
						'totalMes'=>new Expression('SUM(epa.epa_do_imp)') 
				))
				->join(array('egr'=>'egreso'),'egr.egr_in_cod=epa.egr_in_cod',array())
				->where(array('egr.edi_in_cod'=>$this->edificioId,
							'YEAR(egr.egr_da_fecemi)='.$this->yearSelected))
				->group('mes');
		}else if($tipoFila=='EGRESO BANCARIO'){
			$select=$sql->select()
				->from(array('epa'=>'egreso_parcial'))
				->columns(array(
						'mes'=>new Expression('MONTH(epa.epa_da_fecpag)'),
						'totalMes'=>new Expression('SUM(epa.epa_do_imp)') 
				))
				->join(array('egr'=>'egreso'),'egr.egr_in_cod=epa.egr_in_cod',array())
				->where(array('egr.edi_in_cod'=>$this->edificioId,
							'YEAR(epa.epa_da_fecpag)='.$this->yearSelected))
				->group('mes');
		}

		$sqlString=$sql->buildSqlString($select);
		$rsSelect=$adapter->query($sqlString,$adapter::QUERY_MODE_EXECUTE)->toArray();
		
		$rowGrid=array($tipoFila);
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
		$rowGrid[]=$level;
		$rowGrid[]='NULL';
		$rowGrid[]=true;
		$rowGrid[]=false;
		return $rowGrid;
	}

	private function getGridRowTotalPendientePago($rowTotalEmitido,$rowTotalCobrado){
		$totalAnualPendienteCobro=0;
		$rowGrid=array('T. PENDIENTE');
		for($i=1;$i<=12;$i++){
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
		$rowGrid[]=1;
		$rowGrid[]='NULL';
		$rowGrid[]=true;
		$rowGrid[]=false;
		return $rowGrid;
	}



	private function getRowGridAnualNivel0(){
		$adapter=$this->getAdapter();
		$sql=new Sql($adapter);

		$nodeId=str_replace('CG_', '', $this->nodeId);
		
		$selectConcepto=$sql->select()
			->from('concepto')
			->columns(array('id'=>'con_in_cod','conceptoNombre'=>'con_vc_des'))
			->where(
				array(
					'emp_in_cod'=>array(0,$this->empresaId),
					'con_vc_tip'=>'EGRESO',
					'cog_in_cod'=>$nodeId,
				)
			);

		$sqlString=$sql->buildSqlString($selectConcepto);
		$rsConceptosPorGrupo=$adapter->query($sqlString,$adapter::QUERY_MODE_EXECUTE)->toArray();

		$increment=0;
		$response=array();
		foreach ($rsConceptosPorGrupo as $key => $value) {
			$currentConceptoId=$value['id'];
			$currentConceptoNombre=$value['conceptoNombre'];
			
			$selectSum=$sql->select()
				->from('egreso')
				->columns(array('mes'=>new Expression('MONTH(egr_da_fecemi)'),'totalMes'=>new Expression('SUM(egr_do_imp)')))
				->where(array(
						'edi_in_cod'=>$this->edificioId,
						'YEAR(egr_da_fecemi)='.$this->yearSelected,
						'con_in_cod'=>$currentConceptoId
					))->group('mes');
				$sqlStringSelectSum=$sql->buildSqlString($selectSum);
				$rsSelect=$adapter->query($sqlStringSelectSum,$adapter::QUERY_MODE_EXECUTE)->toArray();
			
			$rowGrid=array($currentConceptoNombre);
			$totalAnual=0;
			$rsIndex=0;
			for($i=1;$i<=12;$i++){
				$sumMesPorConcepto=isset($rsSelect[$rsIndex]['mes'])?$rsSelect[$rsIndex]['mes']:null;
				if($i==(int)$sumMesPorConcepto){
					$rowGrid[]=number_format($rsSelect[$rsIndex]['totalMes'],2,'.',',');
					$totalAnual+=$rsSelect[$rsIndex]['totalMes'];
					$rsIndex++;
				}else{
					$rowGrid[]='';
				}	
			}

			//consultar la cantidad de conceptos por año para encontrar el leaft del item
			$selectCount=$sql->select()->from('egreso')->columns(array('count'=>new Expression('COUNT(*)')))
					->where(array('edi_in_cod'=>$this->edificioId,'con_in_cod'=>$currentConceptoId,'YEAR(egr_da_fecemi)='.$this->yearSelected));
			$statementCount=$sql->prepareStatementForSqlObject($selectCount);
			$countConceptosEnEgreso=$statementCount->execute()->current()['count'];
			$leaf=$countConceptosEnEgreso > 0 ? false:true;


			$rowGrid[]=number_format($totalAnual,2,'.',',');
			$rowGrid[]=1;
			$rowGrid[]=$this->nodeId;
			$rowGrid[]=$leaf;
			$rowGrid[]=false;

			$response['rows'][$increment]['id']='C_'.$currentConceptoId;
			$response['rows'][$increment]['cell']=$rowGrid;
			$increment++;
		}

		return $response;
	}

	private function getRowGridAnualNivel1(){
		$adapter=$this->getAdapter();
		$sql=new Sql($adapter);
		$conceptoId=str_replace("C_", "", $this->nodeId);
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

	public function getEgresosPorConceptoAndProveedor($params){
		$adapter=$this->getAdapter();
		$sql=new Sql($adapter);

		$this->edificioId=$params['edificioId'];
		$year=$params['year'];
		$mes=isset($params['mes'])?(int)$params['mes']:0;
		$mes=($mes < 10) ? "0".$mes:$mes;

		$conceptoId=$params['conceptoId'];
		$proveedorId=$params['proveedorId'];

		$select=$sql->select()
			->from(array('egr'=>'egreso'))
			->columns(array(
				'id'=>'egr_in_cod',
				'adjunto'=>'egr_vc_adj',
				'fechaEmi'=>'egr_da_fecemi',
				'fechaVence'=>'egr_da_fecven',
				'tipoDoc'=>'egr_vc_tipdoc',
				'nroDoc'=>'egr_vc_nrodoc',
				'importe'=>'egr_do_imp',
				'estado'=>'egr_in_est',
				'nota'=>'egr_te_obser'
			))
			->join(array('con'=>'concepto'),'con.con_in_cod=egr.con_in_cod',array('conceptoNombre'=>'con_vc_des'))
			->join(array('prv'=>'proveedor'),'prv.prv_in_cod=egr.prv_in_cod',array('proveedorNombre'=>'prv_vc_razsoc'))
			->where(array('egr.edi_in_cod'=>$this->edificioId,
				'YEAR(egr.egr_da_fecemi)='.$year,
				'MONTH(egr.egr_da_fecemi)='.$mes,
				'con.con_in_cod'=>$conceptoId,
				'prv.prv_in_cod'=>$proveedorId,
			));
		$selectString=$sql->buildSqlString($select);
		$rsEgresos=$adapter->query($selectString,$adapter::QUERY_MODE_EXECUTE)->toArray();
		$rowConcepto=$this->getConceptoById($conceptoId);
		$rowProveedor=$this->getProveedorById($proveedorId);


		$response=array();
		$response['proveedor']=$rowProveedor['razonsocial'];
		$response['concepto']=$rowConcepto['descripcion'];
		$response['rows']=$rsEgresos;
		return $response;
	}





	public function egresosParaGrid($params){

		$this->edificioId=$params['edificioId'];
		$this->empresaId=$params['empresaId'];
		$this->nodeId=isset($params['nodeid'])?$params['nodeid'] : null;
		$this->yearSelected=isset($params['year'])?$params['year']:date('Y');
		$this->mesSelected=isset($params['mes'])? ($params['mes']<10 ?'0'.$params['mes'] : $params['mes']):date('m');
		
		$this->nroNivel=isset($params['n_level'])? $params['n_level'] : null;
		$this->parentId=isset($params['parentId'])?$params['parentId']:null;

		$data=null;
		switch ($params['query']) {
			case 'provisionado':
				$data=$this->getAllProvision($params);
				break;
			case 'pendienteDeAprobacion':
				$data=$this->getAllPendienteDeAprobacion($params);
				break;
			case 'pendienteDePago':
				$data=$this->getAllPendienteDePago($params);
				break;
			case 'pagado':
				$data=$this->getAllPagado($params);
				break;
			default:
				$data=array();
				break;
		}
		return $data;
	}

	private function getAllProvision($params){
		$page=isset($params['page'])?$params['page'] :null;
		$limit=isset($params['rows'])?$params['rows']:null;
		$sidx=isset($params['sidx'])?$params['sidx']:'id';
		$sord=isset($params['sord'])?$params['sord']:'desc';
		if(!$sidx) $sidx=1;

		$filter = "";
		$search_grid = $params['_search'];

		$getFidelDb=array(
			'fechaemi'=>'egr_da_fecemi',
            'fechavence'=>'egr_da_fecenv',
            'proveedor'=>'prv_vc_razsoc',
            'concepto'=>'con_vc_des',
            'documento'=>'egr_vc_nroser',
            'nrodoc'=>'egr_vc_tipdoc',
            'importe'=>'egr_do_imp'
		);

		if($search_grid == 'true'){
		    $filter_cad = json_decode(stripslashes($params['filters']));
		    $filter_opc = $filter_cad->{'groupOp'};
		    $filter_rul = $filter_cad->{'rules'};
		    $cont = 0;
		    foreach($filter_rul as $key => $value){
		        $fie = $filter_rul[$key]->{'field'};
		        $opt = $this->search($filter_rul[$key]->{'op'});
		        $dat = $filter_rul[$key]->{'data'};
		 
		        if($cont==0){
		            if($opt=="REGEXP2"){
		                $opt = "LIKE";
		                $filter .= " AND (".$getFidelDb[$fie]." $opt '$dat%'";
		            }else{
		                if($opt=="REGEXP3"){
		                    $opt = "LIKE";
		                    $filter .= " AND (".$getFidelDb[$fie]." $opt '%$dat'";
		                }else{
		                    if($opt=="NOT REGEXP2"){
		                        $opt = "NOT LIKE";
		                        $filter .= " AND (".$getFidelDb[$fie]." $opt '$dat%'";
		                    }else{
		                        if($opt=="NOT REGEXP3"){
		                            $opt = "NOT LIKE";
		                            $filter .= " AND (".$getFidelDb[$fie]." $opt '%$dat'";
		                        }else{
		                            if($opt=='LIKE' || $opt=='NOT LIKE'){
		                                $filter .= " AND (".$getFidelDb[$fie]." $opt '%$dat%'";
		                            }else{
		                                $filter .= " AND (".$getFidelDb[$fie]." $opt '$dat'";
		                            }
		                        }
		                    }
		                }
		            }
		            $cont++;
		        }else{
		            if($opt=="REGEXP2"){
		                $opt = "LIKE";
		                $filter .= " $filter_opc ".$getFidelDb[$fie]." $opt '$dat%'";
		            }else{
		                if($opt=="REGEXP3"){
		                    $opt = "LIKE";
		                    $filter .= " $filter_opc ".$getFidelDb[$fie]." $opt '%$dat'";
		                }else{
		                    if($opt=="NOT REGEXP2"){
		                        $opt = "NOT LIKE";
		                        $filter .= " $filter_opc ".$getFidelDb[$fie]." $opt '$dat%'";
		                    }else{
		                        if($opt=="NOT REGEXP3"){
		                            $opt = "NOT LIKE";
		                            $filter .= " $filter_opc ".$getFidelDb[$fie]." $opt '%$dat'";
		                        }else{
		                            if($opt=='LIKE' || $opt=='NOT LIKE'){
		                                $filter .= " $filter_opc ".$getFidelDb[$fie]." $opt '%$dat%'";
		                            }else{
		                                $filter .= " $filter_opc ".$getFidelDb[$fie]." $opt '$dat'";
		                            }
		                        }
		                    }
		                }
		            }
		        }
		    }
		    $filter .= ")";
		}


		$adapter=$this->getAdapter();
		$sql=new Sql($adapter);


		$selectString="SELECT count(*) as count FROM `egreso` AS `egr` 
						INNER JOIN `concepto` AS `con` ON `con`.`con_in_cod`=`egr`.`con_in_cod` 
						INNER JOIN `proveedor` AS `prv` ON `prv`.`prv_in_cod`=`egr`.`prv_in_cod` 
					WHERE `egr`.`edi_in_cod` = '$this->edificioId' AND `egr_in_est` = '3' ".$filter;
		$count=$adapter->query($selectString,$adapter::QUERY_MODE_EXECUTE)->current()['count'];
		
		if($count > 0 && $limit > 0) {
		    $total_pages = ceil($count / $limit);
		}else{
		    $total_pages = 0;
		}
		

		if($page > $total_pages) $page = $total_pages;
		$start = $limit * $page - $limit;
		if($start < 0) $start = 0;

		$select=$sql->select()
			->from(array('egr'=>'egreso'))
			->columns(array(
				'id'=>'egr_in_cod',
				'adjunto'=>'egr_vc_adj',
				'fechaEmi'=>'egr_da_fecemi',
				'fechaVence'=>'egr_da_fecven',
				'tipoDoc'=>'egr_vc_tipdoc',
				'nroDoc'=>'egr_vc_nrodoc',
				'importe'=>'egr_do_imp',
				'estado'=>'egr_in_est',
				'nota'=>'egr_te_obser'
			))
			->join(array('con'=>'concepto'),'con.con_in_cod=egr.con_in_cod',array('conceptoNombre'=>'con_vc_des'))
			->join(array('prv'=>'proveedor'),'prv.prv_in_cod=egr.prv_in_cod',array('proveedorNombre'=>'prv_vc_razsoc'))
			->where(array('egr.edi_in_cod'=>$this->edificioId,'egr_in_est'=>3));

		$selectString=$sql->buildSqlString($select);
		$selectString.=" ".$filter;
		$selectString.=" ORDER BY $sidx $sord  limit $start , $limit ";

		$EgresoProvisionado=$adapter->query($selectString,$adapter::QUERY_MODE_EXECUTE)->toArray();

		$response=array();
	    $response['page'] = $page;
	    $response['total'] = $total_pages;
	    $response['records'] = $count;

		if(!empty($EgresoProvisionado)){
			$i=0;

			foreach ($EgresoProvisionado as $key => $value){
				$response['rows'][$i]['id']=$value['id'];
				$response['rows'][$i]['cell']=array($value['id'],$value['adjunto'],$value['fechaEmi'],$value['fechaVence'],$value['proveedorNombre'],$value['conceptoNombre'],$value['tipoDoc'],$value['nroDoc'],$value['importe'],$value['nota']);
				$i++;
			}
		}
		return $response;
	}

	private function getAllPendienteDeAprobacion($params){
		$page=isset($params['page'])?$params['page'] :null;
		$limit=isset($params['rows'])?$params['rows']:null;
		$sidx=isset($params['sidx'])?$params['sidx']:'id';
		$sord=isset($params['sord'])?$params['sord']:'desc';
		if(!$sidx) $sidx=1;

		$adapter=$this->getAdapter();
		$sql=new Sql($adapter);


		$selectString="SELECT count(*) as count FROM `egreso` AS `egr` 
						INNER JOIN `concepto` AS `con` ON `con`.`con_in_cod`=`egr`.`con_in_cod` 
						INNER JOIN `proveedor` AS `prv` ON `prv`.`prv_in_cod`=`egr`.`prv_in_cod` 
					WHERE `egr`.`edi_in_cod` = '$this->edificioId' AND `egr_in_est` = '1' ";
		$count=$adapter->query($selectString,$adapter::QUERY_MODE_EXECUTE)->current()['count'];
		
		if($count > 0 && $limit > 0) {
		    $total_pages = ceil($count / $limit);
		}else{
		    $total_pages = 0;
		}
		

		if($page > $total_pages) $page = $total_pages;
		$start = $limit * $page - $limit;
		if($start < 0) $start = 0;


		$select=$sql->select()
			->from(array('egr'=>'egreso'))
			->columns(array(
				'id'=>'egr_in_cod',
				'adjunto'=>'egr_vc_adj',
				'fechaEmi'=>'egr_da_fecemi',
				'fechaVence'=>'egr_da_fecven',
				'tipoDoc'=>'egr_vc_tipdoc',
				'nroDoc'=>'egr_vc_nrodoc',
				'importe'=>'egr_do_imp',
				'estado'=>'egr_in_est',
				'nota'=>'egr_te_obser'
			))
			->join(array('con'=>'concepto'),'con.con_in_cod=egr.con_in_cod',array('conceptoNombre'=>'con_vc_des'))
			->join(array('prv'=>'proveedor'),'prv.prv_in_cod=egr.prv_in_cod',array('proveedorNombre'=>'prv_vc_razsoc'))
			->where(array('egr.edi_in_cod'=>$this->edificioId,'egr_in_est'=>1));

		$selectString=$sql->buildSqlString($select);
		$selectString.=" ORDER BY $sidx $sord  limit $start , $limit ";

		$rsEgresosPendientesDeAprobacion=$adapter->query($selectString,$adapter::QUERY_MODE_EXECUTE)->toArray();

		$response=array();
	    $response['page'] = $page;
	    $response['total'] = $total_pages;
	    $response['records'] = $count;

		$response=array();
		if(!empty($rsEgresosPendientesDeAprobacion)){
			$i=0;

			foreach ($rsEgresosPendientesDeAprobacion as $key => $value) {
				$response['rows'][$i]['id']=$value['id'];
				$response['rows'][$i]['cell']=array($value['id'],$value['adjunto'],$value['fechaEmi'],$value['fechaVence'],$value['proveedorNombre'],$value['conceptoNombre'],$value['tipoDoc'],$value['nroDoc'],$value['importe'],$value['nota']);
				$i++;
			}
		}
		return $response;
	}

	private function getAllPendienteDePago($params){
		$page=isset($params['page'])?$params['page'] :null;
		$limit=isset($params['rows'])?$params['rows']:null;
		$sidx=isset($params['sidx'])?$params['sidx']:'id';
		$sord=isset($params['sord'])?$params['sord']:'desc';
		if(!$sidx) $sidx=1;

		$adapter=$this->getAdapter();
		$sql=new Sql($adapter);


		$selectString="SELECT count(*) as count FROM `egreso` AS `egr` 
						INNER JOIN `concepto` AS `con` ON `con`.`con_in_cod`=`egr`.`con_in_cod` 
						INNER JOIN `proveedor` AS `prv` ON `prv`.`prv_in_cod`=`egr`.`prv_in_cod` 
					WHERE `egr`.`edi_in_cod` = '$this->edificioId' AND `egr_in_est` = '2' ";
		$count=$adapter->query($selectString,$adapter::QUERY_MODE_EXECUTE)->current()['count'];
		
		if($count > 0 && $limit > 0) {
		    $total_pages = ceil($count / $limit);
		}else{
		    $total_pages = 0;
		}
		

		if($page > $total_pages) $page = $total_pages;
		$start = $limit * $page - $limit;
		if($start < 0) $start = 0;


		$select=$sql->select()
			->from(array('egr'=>'egreso'))
			->columns(array(
				'id'=>'egr_in_cod',
				'adjunto'=>'egr_vc_adj',
				'fechaEmi'=>'egr_da_fecemi',
				'fechaVence'=>'egr_da_fecven',
				'tipoDoc'=>'egr_vc_tipdoc',
				'nroDoc'=>'egr_vc_nrodoc',
				'importe'=>'egr_do_imp',
				'estado'=>'egr_in_est',
				'nota'=>'egr_te_obser'
			))
			->join(array('con'=>'concepto'),'con.con_in_cod=egr.con_in_cod',array('conceptoNombre'=>'con_vc_des'))
			->join(array('prv'=>'proveedor'),'prv.prv_in_cod=egr.prv_in_cod',array('proveedorNombre'=>'prv_vc_razsoc'))
			->where(array('egr.edi_in_cod'=>$this->edificioId,'egr_in_est'=>2));

		$selectString=$sql->buildSqlString($select);
		$selectString.=" ORDER BY $sidx $sord  limit $start , $limit ";

		$rsEgresosPendientesDePago=$adapter->query($selectString,$adapter::QUERY_MODE_EXECUTE)->toArray();


		$response=array();
	    $response['page'] = $page;
	    $response['total'] = $total_pages;
	    $response['records'] = $count;

		if(!empty($rsEgresosPendientesDePago)){
			$i=0;

			foreach ($rsEgresosPendientesDePago as $key => $value) {
				$response['rows'][$i]['id']=$value['id'];
				$response['rows'][$i]['cell']=array($value['id'],$value['adjunto'],$value['fechaEmi'],$value['fechaVence'],$value['proveedorNombre'],$value['conceptoNombre'],$value['tipoDoc'],$value['nroDoc'],$value['importe'],$value['nota']);
				$i++;
			}
		}
		return $response;
	}
	
	private function getAllPagado($params){

		$page=isset($params['page'])?$params['page'] :null;
		$limit=isset($params['rows'])?$params['rows']:null;
		$sidx=isset($params['sidx'])?$params['sidx']:'id';
		$sord=isset($params['sord'])?$params['sord']:'desc';
		if(!$sidx) $sidx=1;

		$filter = "";
		$search_grid = $params['_search'];

		$getFidelDb=array(
			'fechaemi'=>'egr_da_fecemi',
            'fechavence'=>'egr_da_fecenv',
            'proveedor'=>'prv_vc_razsoc',
            'concepto'=>'con_vc_des',
            'documento'=>'egr_vc_nroser',
            'nrodoc'=>'egr_vc_tipdoc',
            'importe'=>'egr_do_imp'
		);

		if($search_grid == 'true'){
		    $filter_cad = json_decode(stripslashes($params['filters']));
		    $filter_opc = $filter_cad->{'groupOp'};
		    $filter_rul = $filter_cad->{'rules'};
		    $cont = 0;
		    foreach($filter_rul as $key => $value){
		        $fie = $filter_rul[$key]->{'field'};
		        $opt = $this->search($filter_rul[$key]->{'op'});
		        $dat = $filter_rul[$key]->{'data'};
		 
		        if($cont==0){
		            if($opt=="REGEXP2"){
		                $opt = "LIKE";
		                $filter .= " AND (".$getFidelDb[$fie]." $opt '$dat%'";
		            }else{
		                if($opt=="REGEXP3"){
		                    $opt = "LIKE";
		                    $filter .= " AND (".$getFidelDb[$fie]." $opt '%$dat'";
		                }else{
		                    if($opt=="NOT REGEXP2"){
		                        $opt = "NOT LIKE";
		                        $filter .= " AND (".$getFidelDb[$fie]." $opt '$dat%'";
		                    }else{
		                        if($opt=="NOT REGEXP3"){
		                            $opt = "NOT LIKE";
		                            $filter .= " AND (".$getFidelDb[$fie]." $opt '%$dat'";
		                        }else{
		                            if($opt=='LIKE' || $opt=='NOT LIKE'){
		                                $filter .= " AND (".$getFidelDb[$fie]." $opt '%$dat%'";
		                            }else{
		                                $filter .= " AND (".$getFidelDb[$fie]." $opt '$dat'";
		                            }
		                        }
		                    }
		                }
		            }
		            $cont++;
		        }else{
		            if($opt=="REGEXP2"){
		                $opt = "LIKE";
		                $filter .= " $filter_opc ".$getFidelDb[$fie]." $opt '$dat%'";
		            }else{
		                if($opt=="REGEXP3"){
		                    $opt = "LIKE";
		                    $filter .= " $filter_opc ".$getFidelDb[$fie]." $opt '%$dat'";
		                }else{
		                    if($opt=="NOT REGEXP2"){
		                        $opt = "NOT LIKE";
		                        $filter .= " $filter_opc ".$getFidelDb[$fie]." $opt '$dat%'";
		                    }else{
		                        if($opt=="NOT REGEXP3"){
		                            $opt = "NOT LIKE";
		                            $filter .= " $filter_opc ".$getFidelDb[$fie]." $opt '%$dat'";
		                        }else{
		                            if($opt=='LIKE' || $opt=='NOT LIKE'){
		                                $filter .= " $filter_opc ".$getFidelDb[$fie]." $opt '%$dat%'";
		                            }else{
		                                $filter .= " $filter_opc ".$getFidelDb[$fie]." $opt '$dat'";
		                            }
		                        }
		                    }
		                }
		            }
		        }
		    }
		    $filter .= ")";
		}


		$adapter=$this->getAdapter();
		$sql=new Sql($adapter);


		$selectString="SELECT count(*) as count FROM `egreso` AS `egr` 
						INNER JOIN `concepto` AS `con` ON `con`.`con_in_cod`=`egr`.`con_in_cod` 
						INNER JOIN `proveedor` AS `prv` ON `prv`.`prv_in_cod`=`egr`.`prv_in_cod` 
					WHERE `egr`.`edi_in_cod` = '$this->edificioId' AND `egr_in_est` = '0' ".$filter;
		$count=$adapter->query($selectString,$adapter::QUERY_MODE_EXECUTE)->current()['count'];
		

		if($count > 0 && $limit > 0) {
		    $total_pages = ceil($count / $limit);
		}else{
		    $total_pages = 0;
		}
		if($page > $total_pages) $page = $total_pages;
		$start = $limit * $page - $limit;
		if($start < 0) $start = 0;


		$select=$sql->select()
			->from(array('egr'=>'egreso'))
			->columns(array(
				'id'=>'egr_in_cod',
				'adjunto'=>'egr_vc_adj',
				'fechaEmi'=>'egr_da_fecemi',
				'fechaVence'=>'egr_da_fecven',
				'tipoDoc'=>'egr_vc_tipdoc',
				'nroDoc'=>'egr_vc_nrodoc',
				'importe'=>'egr_do_imp',
				'estado'=>'egr_in_est',
				'nota'=>'egr_te_obser'
			))
			->join(array('con'=>'concepto'),'con.con_in_cod=egr.con_in_cod',array('conceptoNombre'=>'con_vc_des'))
			->join(array('prv'=>'proveedor'),'prv.prv_in_cod=egr.prv_in_cod',array('proveedorNombre'=>'prv_vc_razsoc'))
			->where(array('egr.edi_in_cod'=>$this->edificioId,'egr_in_est'=>0));

		$selectString=$sql->buildSqlString($select);
		$selectString.=" ".$filter;
		$selectString.="ORDER BY $sidx $sord  limit $start , $limit ";
		$rsGruposDeConceptosEgreso=$adapter->query($selectString,$adapter::QUERY_MODE_EXECUTE)->toArray();


		$response=array();
	    $response['page'] = $page;
	    $response['total'] = $total_pages;
	    $response['records'] = $count;


		if(!empty($rsGruposDeConceptosEgreso)){
			$i=0;

			foreach ($rsGruposDeConceptosEgreso as $key => $value) {
				$response['rows'][$i]['id']=$value['id'];
				$response['rows'][$i]['cell']=array($value['id'],$value['adjunto'],$value['fechaEmi'],$value['fechaVence'],$value['proveedorNombre'],$value['conceptoNombre'],$value['tipoDoc'],$value['nroDoc'],$value['importe'],$value['nota']);
				$i++;
			}
		}
		return $response;
	}

	public function detalleDeEgreso($egresoId){
		$egresoId=isset($egresoId)?$egresoId:null;

		$adapter=$this->getAdapter();
		$sql=new Sql($adapter);


		$select=$sql->select()
			->from(array('egr'=>'egreso'))
			->columns(array(
				'conceptoId'=>'con_in_cod',
				'proveedorId'=>'prv_in_cod',
				'importe'=>'egr_do_imp',
				'tipoDoc'=>'egr_vc_tipdoc',
				'serie'=>'egr_vc_nroser',
				'nroDoc'=>'egr_vc_nrodoc',
				'fechaEmision'=>'egr_da_fecemi',
				'fechaVence'=>'egr_da_fecven',
				'observacion'=>'egr_te_obser',
				'ruta_file_digital'=>'egr_vc_adj',
				'metroscubicos'=>'egr_do_totm3',
				'estado'=>'egr_in_est',
				'registradoPor'=>'egr_in_usureg',
				'fechaRegistro'=>'egr_dt_freg',
				'procesadoPor'=>'egr_in_usuenv',
				'fechaProceso'=>'egr_da_fecenv',
				'aprobadoPor'=>'egr_in_usutel',
				'fechaAprobacion'=>'egr_da_fectel'
			))
			->where(array('egr_in_cod'=>$egresoId));
		$statement=$sql->prepareStatementForSqlObject($select);
		$rsEgreso=$statement->execute()->current();
		//format adicional:
		$rsEgreso['fechaEmision']=date('d-m-Y',strtotime($rsEgreso['fechaEmision']));
		$rsEgreso['fechaVence']=date('d-m-Y',strtotime($rsEgreso['fechaVence']));

		if($rsEgreso['ruta_file_digital']!=""){
			$rsEgreso['file']=true;
		}else{
			$rsEgreso['file']=false;
		}

		$rsEgreso['registradoPor']=$this->getNombreUsuario($rsEgreso['registradoPor']);
		if($rsEgreso['fechaRegistro']=='0000-00-00 00:00:00' || $rsEgreso['fechaRegistro']==null){
			$rsEgreso['fechaRegistro']='00-00-0000 00:00:00 __';
		}else{
			$rsEgreso['fechaRegistro']=date('d-m-Y H:i:s a',strtotime($rsEgreso['fechaRegistro']));
		}

		$rsEgreso['procesadoPor']=$this->getNombreUsuario($rsEgreso['procesadoPor']);
		if($rsEgreso['fechaProceso']=='0000-00-00 00:00:00' || $rsEgreso['fechaProceso']==null){
			$rsEgreso['fechaProceso']='00-00-0000 00:00:00 __';
		}else{
			$rsEgreso['fechaProceso']=date('d-m-Y H:i:s a',strtotime($rsEgreso['fechaProceso']));
		}
		
		$rsEgreso['aprobadoPor']=$this->getNombreUsuario($rsEgreso['aprobadoPor']);
		if($rsEgreso['fechaAprobacion']=='0000-00-00 00:00:00' || $rsEgreso['fechaAprobacion']==null){
			$rsEgreso['fechaAprobacion']='00-00-0000 00:00:00 __ ';
		}else{
			$rsEgreso['fechaAprobacion']=date('d-m-Y H:i:s a',strtotime($rsEgreso['fechaAprobacion']));
		}

		return $rsEgreso;
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


	public function getConceptos($params){
		$this->edificioId=$params['edificioId'];
		$this->empresaId=$params['empresaId'];
		$tipo=isset($params['tipo'])?$params['tipo']:'EGRESO';
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

	private function getConceptoById($conceptoId){

		$sql=new Sql($this->adapter);

		$select=$sql->select()
			->from('concepto')
			->columns(array('id'=>'con_in_cod','descripcion'=>'con_vc_des'))
			->where(array('con_in_cod'=>$conceptoId));
		$statement=$sql->prepareStatementForSqlObject($select);
		return $statement->execute()->current();
	}


	public function getProveedores($params){
		$this->empresaId=$params['empresaId'];
		$adapter=$this->getAdapter();
		$sql=new Sql($adapter);

		$select=$sql->select()
			->from(array('prv'=>'proveedor'))
			->columns(array('id'=>'prv_in_cod','razonsocial'=>'prv_vc_razsoc'))
			->where(
				array(
					'emp_in_cod'=>array(0,$this->empresaId),
				)
			);
		$sqlString=$sql->buildSqlString($select);
		$rsProveedor=$adapter->query($sqlString,$adapter::QUERY_MODE_EXECUTE)->toArray();

		return $rsProveedor;
	}

	private function getProveedorById($proveedorId){

		$sql=new Sql($this->adapter);
		$select=$sql->select()
			->from(array('prv'=>'proveedor'))
			->columns(array('id'=>'prv_in_cod','razonsocial'=>'prv_vc_razsoc'))
			->where(array('prv_in_cod'=>$proveedorId));
		$statement=$sql->prepareStatementForSqlObject($select);
		return $statement->execute()->current();
	}

	
	public function addEgreso($params){
		$this->edificioId=$params['edificioId'];
		$sql=new Sql($this->adapter);

		if($this->existeEgresoIgual($params,null)==true && !isset($params['chkValidarDoc'])){
			return array(
				'tipo'=>'error',
				'mensaje'=>'Imposible registrar por que existe un egreso con estas características.',
				'lastIdEgreso'=>0
				);	
		}
		
		$fechaSistema=date('Y-m-d H:i:s');
		$dataInsert=array(
			'egr_dt_freg'=>$fechaSistema,
			'con_in_cod'=>$params['selConcepto'],
			'edi_in_cod'=>$this->edificioId,
			'prv_in_cod'=>$params['selProveedor'],
			'egr_do_imp'=>$params['textImporte'],
			'egr_vc_tipdoc'=>$params['selTipoDoc'],
			'egr_vc_nroser'=>$params['textSerie'],
			'egr_vc_nrodoc'=>$params['textNroDocumento'],
			'egr_da_fecemi'=>date('Y-m-d',strtotime($params['textFechaEmi'])),
			'egr_da_fecven'=>date('Y-m-d',strtotime($params['textFechaVence'])),
			'egr_te_obser'=>$params['ta_nota'],
			'egr_in_est'=>3,
			'egr_in_usureg'=>$params['usuarioId'],
			'egr_do_totm3'=>$params['textM3'],
		);

		$insertEgreso=$sql->insert('egreso')->values($dataInsert);
		$statement=$sql->prepareStatementForSqlObject($insertEgreso);
		$lasInsertId=$statement->execute()->getGeneratedValue();
		$params['lasInsertId']=$lasInsertId;

		$response=array(
			'tipo'=>'error',
			'mensaje'=>'Ocurrió un error desconocido en el servidor al intentar registrar el egreso.',
			'lastIdEgreso'=>0
		);

		if($lasInsertId>0){
			if($params['file']==true){
				$nombre_recibo_digital="RD_".$lasInsertId.".pdf";
				$updateEgreso=$sql->update()->table('egreso')
				->set(array('egr_vc_adj'=>$nombre_recibo_digital))
				->where(array('egr_in_cod'=>$lasInsertId));
				$statement=$sql->prepareStatementForSqlObject($updateEgreso);
				$statement->execute();
			}else{
				$lasInsertId=0;
			}
			/////////save auditoria////////
			$params['tabla']='Egreso';
			$params['numRegistro']=$params['lasInsertId'];
			$params['accion']='Guardar';
			$this->saveAuditoria($params);
			///////////////////////////////
			$response=array(
				'tipo'=>'informativo',
				'mensaje'=>'Los datos se guardaron con éxito.',
				'lastIdEgreso'=>$lasInsertId,
			);
		}
		return $response;
	}

	public function updateEgreso($params){
		$sql=new Sql($this->adapter);

		$fechaSistema=date('Y-m-d');
		$egresoId=$params['co_egresoId'];

		$dataUpdate=array(
			'con_in_cod'=>$params['selConcepto'],
			'prv_in_cod'=>$params['selProveedor'],
			'egr_do_imp'=>$params['textImporte'],
			'egr_vc_tipdoc'=>$params['selTipoDoc'],
			'egr_vc_nroser'=>$params['textSerie'],
			'egr_vc_nrodoc'=>$params['textNroDocumento'],
			'egr_da_fecemi'=>date('Y-m-d',strtotime($params['textFechaEmi'])),
			'egr_da_fecven'=>date('Y-m-d',strtotime($params['textFechaVence'])),
			'egr_te_obser'=>$params['ta_nota'],
			'egr_do_totm3'=>$params['textM3'],
		);

		if($params['file']==true){
			$dataUpdate['egr_vc_adj']="RD_".$egresoId.".pdf";
		}

		if($params['estado_recibo_digital']=="eliminar"){
			$dataUpdate['egr_vc_adj']='';
		}

		$updateEgreso=$sql->update()->table('egreso')
		->set($dataUpdate)
		->where(array('egr_in_cod'=>$egresoId));
			
		$statement=$sql->prepareStatementForSqlObject($updateEgreso);
		$rs=$statement->execute()->count();

		$response=array(
				'tipo'=>'error',
				'mensaje'=>'El registro no sufrió modificaciones.',
		);

		if($rs>0){
			/////////save auditoria////////
			$params['tabla']='Egreso';
			$params['numRegistro']=$egresoId;
			$params['accion']='Editar';
			$this->saveAuditoria($params);
			///////////////////////////////
			$response=array(
				'tipo'=>'informativo',
				'mensaje'=>'Los datos se guardaron con éxito.'
			);	
		}
		return $response;
	}


	public function eliminarEgreso($params){
		
		$sql=new Sql($this->adapter);
		$deleteEgreso=$sql->delete('egreso')->where(array('egr_in_cod'=>$params['id']));
		$statement=$sql->prepareStatementForSqlObject($deleteEgreso);
		$rsDeleteEgreso=$statement->execute()->count();

		$response=array(
			'tipo'=>'error',
			'mensaje'=>'Ocurrió un error desconocido en el servidor al intentar el egreso seleccionado.',
		);

		if($rsDeleteEgreso>0){
			$sql=new Sql($this->adapter);
			$deleteEgresoParciales=$sql->delete('egreso_parcial')->where(array('egr_in_cod'=>$params['id']));
			$statement=$sql->prepareStatementForSqlObject($deleteEgresoParciales);
			$statement->execute();
			/////////save auditoria////////
			$params['tabla']='Egreso';
			$params['numRegistro']=$params['id'];
			$params['accion']='Eliminar';
			$this->saveAuditoria($params);
			///////////////////////////////
			$response=array(
				'tipo'=>'informativo',
				'mensaje'=>'Egreso eliminado con éxito.'
			);
		}
		return $response;
	}


	private function existeEgresoIgual($params,$where){
		$sql=new Sql($this->adapter);
		$selectValidar=$sql->select()
			->from('egreso')
			->columns(array('count'=>new Expression('COUNT(*)')))
			->where(array('egr_vc_tipdoc'=>$params['selTipoDoc'],
						'egr_vc_nroser'=>$params['textSerie'],
						'egr_vc_nrodoc'=>$params['textNroDocumento']));
		if(!empty($where)){
			$selectValidar->where($where);
		}

		$statement=$sql->prepareStatementForSqlObject($selectValidar);
		$result=$statement->execute()->current()['count'];

		if($result>0){
			return true;
		}
		return false;
	}

	public function procesarEgreso($params){
		$sql=new Sql($this->adapter);

		$egresoId=$params['egresoId'];
		$dataUpdate=array(
			'egr_in_est'=>1,
			'egr_in_usuenv'=>$params['usuarioId'],
			'egr_da_fecenv'=>date('Y-m-d H:i:s'),
		);
		$updateEgreso=$sql->update()->table('egreso')
		->set($dataUpdate)
		->where(array('egr_in_cod'=>$egresoId));
			
		$statement=$sql->prepareStatementForSqlObject($updateEgreso);
		$rs=$statement->execute()->count();

		/////////save auditoria////////
		$params['tabla']='Egreso';
		$params['numRegistro']=$egresoId;
		$params['accion']='Procesar';
		$this->saveAuditoria($params);
		///////////////////////////////

		$response=array(
				'tipo'=>'error',
				'mensaje'=>'Ocurrió un error al intentar procesar el egreso.',
		);

		if($rs>0){
			$response=array(
				'tipo'=>'informativo',
				'mensaje'=>'Egreso procesado con éxito.'
			);	
		}
		return $response;
	}

	public function aprobarEgreso($params){
		$sql=new Sql($this->adapter);

		$egresoId=$params['egresoId'];
		$dataUpdate=array(
			'egr_in_est'=>2,
			'egr_da_fectel'=>date('Y-m-d H:i:s'),
			'egr_in_usutel'=>$params['usuarioId'],
		);
		$updateEgreso=$sql->update()->table('egreso')
		->set($dataUpdate)
		->where(array('egr_in_cod'=>$egresoId));
			
		$statement=$sql->prepareStatementForSqlObject($updateEgreso);
		$rs=$statement->execute()->count();

		$response=array(
			'tipo'=>'error',
			'mensaje'=>'Ocurrió un error al intentar aprobar el egreso.',
		);

		if($rs>0){
			/////////save auditoria////////
			$params['tabla']='Egreso';
			$params['numRegistro']=$egresoId;
			$params['accion']='Aprobar';
			$this->saveAuditoria($params);
			///////////////////////////////
			$response=array(
				'tipo'=>'informativo',
				'mensaje'=>'Egreso aprobado con éxito.'
			);	
		}
		return $response;
	}

	public function registrarPago($params){
		$sql=new Sql($this->adapter);

		$detalleEgreso=$this->detalleDeEgreso($params['egresoId']);
		$totalEgreso=$detalleEgreso['importe'];

		$totalPagado=$this->sumEgresoParcial($params['egresoId']);
		$totalDebe=($totalEgreso - $totalPagado);
		$totalDebe.='';

		$importe=str_replace(',','', $params['textImporte']);

		if($importe==$totalDebe || $importe<$totalDebe){
			
			$deudaPendiente=$totalDebe - $importe;
			$estadoEgreso=($deudaPendiente==0)? 0:2;


			$dataInsert=array(
				'egr_in_cod'=>$params['egresoId'],
				'usu_in_cod'=>$params['usuarioId'],
				'epa_da_fecpag'=>date('Y-m-d',strtotime($params['textFechaPago'])),
				'epa_vc_nroope'=>$params['textNroOperacion'],
				'epa_vc_ban'=>$params['selBanco'],
				'epa_te_obs'=>$params['taObservacion'],
				'epa_do_int'=>0,
				'epa_do_tot'=>$importe,
				'epa_do_imp'=>$importe,
				'epa_do_sal'=>$deudaPendiente,
			);

			$insertEgresoParcial=$sql->insert('egreso_parcial')->values($dataInsert);
			$statement=$sql->prepareStatementForSqlObject($insertEgresoParcial);
			$rsInsertPago=$statement->execute()->count();
			$response=array(
				'tipo'=>'error',
				'mensaje'=>'Ocurrió un error desconocido en el servidor al intentar registrar el egreso.',
			);

			if($rsInsertPago>0){
				$dataUpdateEgreso=array('egr_in_est'=>$estadoEgreso);
				$updateEgreso=$sql->update()->table('egreso')->set($dataUpdateEgreso)
					->where(array('egr_in_cod'=>$params['egresoId']));

				$statement=$sql->prepareStatementForSqlObject($updateEgreso);
				$statement->execute();
				/////////save auditoria////////
				$params['tabla']='Egreso';
				$params['numRegistro']=$params['egresoId'];
				$params['accion']='Guardar Pago';
				$this->saveAuditoria($params);
				///////////////////////////////
				$response=array(
					'tipo'=>'informativo',
					'mensaje'=>'Pago guardaro con éxito.'
				);
			}
		}else{
			return array('tipo'=>'error','mensaje'=>'El importe que esta intentando registrar es mayor a la deuda pendiente.');
		}
		return $response;
	}

	public function eliminarEgresoParcial($params){
		
		$sql=new Sql($this->adapter);
		$deleteEP=$sql->delete('egreso_parcial')->where(array('epa_in_cod'=>$params['id']));
		$statement=$sql->prepareStatementForSqlObject($deleteEP);
		$rsDeleteEP=$statement->execute()->count();

		$response=array(
			'tipo'=>'error',
			'mensaje'=>'Ocurrió un error desconocido en el servidor al intentar eliminar el pago seleccionado.',
		);

		if($rsDeleteEP>0){
			$detalleEgreso=$this->detalleDeEgreso($params['egresoId']);
			
			$totalEgreso=$detalleEgreso['importe'];
			$totalPagado=$this->sumEgresoParcial($params['egresoId']);
			$totalDebe=($totalEgreso - $totalPagado);

			$estadoEgreso=($totalDebe==0)? 0:2;
			$updateEgreso=$sql->update()->table('egreso')->set(array('egr_in_est'=>$estadoEgreso))
			->where(array('egr_in_cod'=>$params['egresoId']));

			$statement=$sql->prepareStatementForSqlObject($updateEgreso);
			$statement->execute();
			/////////save auditoria////////
			$params['tabla']='Egreso';
			$params['numRegistro']=$params['egresoId'];
			$params['accion']='Pago Eliminado';
			$this->saveAuditoria($params);
			///////////////////////////////
			$response=array(
				'tipo'=>'informativo',
				'mensaje'=>'Pago eliminado con éxito.'
			);
		}
		return $response;
	}

	public function getDetallePago($egresoId){
		$detalleEgreso=$this->detalleDeEgreso($egresoId);
		$totalEgreso=$detalleEgreso['importe'];
		$totalPagado=$this->sumEgresoParcial($egresoId);
		$totalPagado=isset($totalPagado) ? $totalPagado:0;
		$totalDebe=$totalEgreso - $totalPagado;

		$rowsEgresoParcial=$this->getRowsEgresoParcial($egresoId);

		return array(
			'total'=>number_format($totalEgreso,2,".",","),
			'totalPagado'=>number_format($totalPagado,2,".",","),
			'totalDebe'=>number_format($totalDebe,2,".",","),
			'rowsPagos'=>$rowsEgresoParcial,
		);
	}	

	private function sumEgresoParcial($egresoId){
		$sql=new Sql($this->adapter);
		$selectSumEP=$sql->select()
		->from('egreso_parcial')
		->columns(array('totalPagado'=>new Expression('SUM(epa_do_imp)')))
		->where(array('egr_in_cod'=>$egresoId));

		$statement=$sql->prepareStatementForSqlObject($selectSumEP);
		$rowSumEgresoParcial=$statement->execute()->current();
		$totalPagado=$rowSumEgresoParcial['totalPagado'];
		return $totalPagado;
	}

	private function getRowsEgresoParcial($egresoId){
		$adapter=$this->getAdapter();
		$sql=new Sql($adapter);
		$selectEgresoParcial=$sql->select()
		->from('egreso_parcial')
		->columns(array(
					'id'=>'epa_in_cod',
					'fechaPago'=>'epa_da_fecpag',
					'nroOperacion'=>'epa_vc_nroope',
					'banco'=>'epa_vc_ban',
					'nota'=>'epa_te_obs',
					'importe'=>'epa_do_imp'
				))->where(array('egr_in_cod'=>$egresoId));

		$selectString=$sql->buildSqlString($selectEgresoParcial);
		$rowsEgresoParcial=$adapter->query($selectString,$adapter::QUERY_MODE_EXECUTE)->toArray();
		return $rowsEgresoParcial;
	}

	public function egresoParcialParaGridBanco($params){
		$sidx=isset($params['sidx'])?$params['sidx']:'id';
		$sord=isset($params['sord'])?$params['sord']:'desc';
		if(!$sidx) $sidx=1;

		$year=isset($params['year'])?$params['year']:'0';
		$mes=isset($params['mes'])?( ($params['mes']>10)? $params['mes']:"0".$params['mes'] ):'0';


		$filter = "";
		$search_grid = $params['_search'];

		$getFidelDb=array(
			'fechapago'=>'epa.epa_da_fecpag',
            'proveedor'=>'prv.prv_vc_razsoc',
            'nrooperacion'=>'epa.epa_vc_nroope',
            'banco'=>'epa.epa_vc_ban',
            'importe'=>'egr.egr_do_imp',
            'tipodoc'=>'epa.epa_do_imp',
            'serie'=>'egr.egr_vc_nroser',
            'nrodoc'=>'egr.egr_vc_nrodoc',
            'fechaemision'=>'egr.egr_da_fecemi'
		);

		if($search_grid == 'true'){
		    $filter_cad = json_decode(stripslashes($params['filters']));
		    $filter_opc = $filter_cad->{'groupOp'};
		    $filter_rul = $filter_cad->{'rules'};
		    $cont = 0;
		    foreach($filter_rul as $key => $value){
		        $fie = $filter_rul[$key]->{'field'};
		        $opt = $this->search($filter_rul[$key]->{'op'});
		        $dat = $filter_rul[$key]->{'data'};
		 
		        if($cont==0){
		            if($opt=="REGEXP2"){
		                $opt = "LIKE";
		                $filter .= " AND (".$getFidelDb[$fie]." $opt '$dat%'";
		            }else{
		                if($opt=="REGEXP3"){
		                    $opt = "LIKE";
		                    $filter .= " AND (".$getFidelDb[$fie]." $opt '%$dat'";
		                }else{
		                    if($opt=="NOT REGEXP2"){
		                        $opt = "NOT LIKE";
		                        $filter .= " AND (".$getFidelDb[$fie]." $opt '$dat%'";
		                    }else{
		                        if($opt=="NOT REGEXP3"){
		                            $opt = "NOT LIKE";
		                            $filter .= " AND (".$getFidelDb[$fie]." $opt '%$dat'";
		                        }else{
		                            if($opt=='LIKE' || $opt=='NOT LIKE'){
		                                $filter .= " AND (".$getFidelDb[$fie]." $opt '%$dat%'";
		                            }else{
		                                $filter .= " AND (".$getFidelDb[$fie]." $opt '$dat'";
		                            }
		                        }
		                    }
		                }
		            }
		            $cont++;
		        }else{
		            if($opt=="REGEXP2"){
		                $opt = "LIKE";
		                $filter .= " $filter_opc ".$getFidelDb[$fie]." $opt '$dat%'";
		            }else{
		                if($opt=="REGEXP3"){
		                    $opt = "LIKE";
		                    $filter .= " $filter_opc ".$getFidelDb[$fie]." $opt '%$dat'";
		                }else{
		                    if($opt=="NOT REGEXP2"){
		                        $opt = "NOT LIKE";
		                        $filter .= " $filter_opc ".$getFidelDb[$fie]." $opt '$dat%'";
		                    }else{
		                        if($opt=="NOT REGEXP3"){
		                            $opt = "NOT LIKE";
		                            $filter .= " $filter_opc ".$getFidelDb[$fie]." $opt '%$dat'";
		                        }else{
		                            if($opt=='LIKE' || $opt=='NOT LIKE'){
		                                $filter .= " $filter_opc ".$getFidelDb[$fie]." $opt '%$dat%'";
		                            }else{
		                                $filter .= " $filter_opc ".$getFidelDb[$fie]." $opt '$dat'";
		                            }
		                        }
		                    }
		                }
		            }
		        }
		    }
		    $filter .= ")";
		}

		$adapter=$this->getAdapter();
		$sql=new Sql($adapter);
		$select=$sql->select()
			->from(array('epa'=>'egreso_parcial'))
			->columns(array('id'=>'epa_in_cod','fechaPago'=>'epa_da_fecpag','nroOperacion'=>'epa_vc_nroope','banco'=>'epa_vc_ban','importe'=>'epa_do_imp'))
			->join(array('egr'=>'egreso'),'egr.egr_in_cod=epa.egr_in_cod',array(
				'fechaEmi'=>'egr_da_fecemi',
				'tipoDoc'=>'egr_vc_tipdoc',
				'serie'=>'egr_vc_nroser',
				'nroDoc'=>'egr_vc_nrodoc',
			))
			->join(array('prv'=>'proveedor'),'prv.prv_in_cod=egr.prv_in_cod',array('proveedorNombre'=>'prv_vc_razsoc'))
			->where(array('egr.edi_in_cod'=>$params['edificioId'],'YEAR(epa.epa_da_fecpag)='.$year,'MONTH(epa.epa_da_fecpag)='.$mes));

		$selectString=$sql->buildSqlString($select);
		$selectString.=" ".$filter;
		$selectString.="ORDER BY $sidx $sord";
		
		$rsEgresoBanco=$adapter->query($selectString,$adapter::QUERY_MODE_EXECUTE)->toArray();

		$response=array();

		if(!empty($rsEgresoBanco)){
			$i=0;
			$sumImporte=0;
			foreach ($rsEgresoBanco as $key => $value) {
				$response['rows'][$i]['id']=$value['id'];
				$response['rows'][$i]['cell']=array(date('d-m',strtotime($value['fechaPago'])),$value['proveedorNombre'],
													$value['nroOperacion'],$value['banco'],$value['importe'],
													$value['tipoDoc'],$value['serie'],$value['nroDoc'],$value['fechaEmi']);
				$sumImporte+=$value['importe'];
				$i++;
			}
		}
		$response['userdata']=array('importe'=>$sumImporte);
		return $response;
	}

	private function search($oper){
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

	private function saveAuditoria($params)
    {   
        $adapter=$this->getAdapter();
        $sql=new Sql($adapter);
        $insert = $sql->insert('auditoria');
        $data = array(
            'usu_in_cod'=> $params['usuarioId'], //idusuario
            'edi_in_cod'=> $params['edificioId'], //idedificio
            'aud_opcion'=> 'Finanzas > Egreso', //ruta
            'aud_accion'=> $params['accion'], //accion
            'aud_fecha'=> date('Y-m-d H:i:s'), //fecha y hora
            'aud_ip'=> $_SERVER['REMOTE_ADDR'], //ip publica
            'aud_tabla'=> $params['tabla'], //tabla
            'aud_in_numra'=> $params['numRegistro'], //registro afectado
            'aud_info_adi'=> $_SERVER['HTTP_USER_AGENT'], //agente
            'aud_comnumra'=> '' //comentario del registro afectado
        );
        $insert->values($data);
        $sql->prepareStatementForSqlObject($insert)->execute()->count();
    }
	
}
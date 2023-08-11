<?php
/**
 * edificioOk (https://www.edificiook.com)
 * creado por: Meler Carranza, 23/03/2016.
 * ultima modificacion por: Meler Carranza
 * Fecha Modificacion: 23/03/2016.
 * Descripcion: Script controller para home - proceso
 *
 * @link      https://www.edificiook.com
 * @copyright Copyright (c) 2011-2016 KND S.A.C (http://www.knd.pe)
 * @license   http://www.edificiook.com/license/comercial Software Comercial
 *
 */

namespace Reporte\Model;
use Zend\Db\Sql\Sql;
use Zend\Db\Sql\Select;
use Zend\Db\TableGateway\AbstractTableGateway;
use Zend\Db\Adapter\Adapter;
use Zend\Db\ResultSet\ResultSet;
use Zend\Db\Sql\Expression;
use Zend\Json\Expr;

class MorososTable extends AbstractTableGateway{
	private $edificioId=null;
    private $empresaId=null;
    private $yearSelected=null;
	private $tipoUnidad=null;
	private $fechasistema=null;
    private $nombreMes=array('Enero','Febrero','Marzo','Abril','Mayo',
    					'Junio','Julio','Agosto','Septiembre',
    					'Octubre','Noviembre','Diciembre');
    public function __construct(Adapter $adapter)
	{	
		$this->fechasistema=date('Y-m-d');
		$this->adapter=$adapter;
	}


	public function dataParaGrid($params){

		
		$this->edificioId=$params['edificioId'];
		$this->empresaId=$params['empresaId'];
		$this->yearSelected=isset($params['year'])?$params['year']:date('Y');
		$this->tipoUnidad=isset($params['tipo_unidad'])?$params['tipo_unidad']:'principales';

		

		$page=isset($params['page'])?$params['page']:0;
		$limit=isset($params['rows'])?$params['rows']:0;
		$sidx =isset($params['sidx'])?$params['sidx']:'';
		$sord =isset($params['sord'])?$params['sord']:'';
		if(!$sidx) $sidx = 1;


		$filter = "";
		$search_grid = $params['_search'];
		if($search_grid == 'true'){
		    $filter_cad = json_decode(stripslashes($params['filters']));
		    $filter_opc = $filter_cad->{'groupOp'};
		    $filter_rul = $filter_cad->{'rules'};
		    $cont = 0;
		    foreach($filter_rul as $key => $value){
		        $fie = $filter_rul[$key]->{'field'};
		        $opt = $this->search($filter_rul[$key]->{'op'});
		        $dat = trim($filter_rul[$key]->{'data'});
		        if($fie=='descripcion'){
		            $fie="CONCAT(uni.uni_vc_tip,' ',uni.uni_vc_nom)";
		        }

		        if($fie=='propietario')
		            $fie="(SELECT CASE WHEN usu.usu_ch_tip='PN' THEN CONCAT(usu_vc_ape,' ',usu_vc_nom) WHEN usu.usu_ch_tip='PJ' THEN usu_vc_ape END AS nombre FROM usuario WHERE usu_in_cod=uni.uni_in_pro)";

		        if($fie=='residente')
		            $fie="(SELECT CASE WHEN usu.usu_ch_tip='PN' THEN CONCAT(usu_vc_ape,' ',usu_vc_nom) WHEN usu.usu_ch_tip='PJ' THEN usu_vc_ape END AS nombre FROM usuario WHERE usu_in_cod=uni.uni_in_pos)";

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

		$adapter=$this->getAdapter();
		$stringSelectCount = "SELECT COUNT(*) AS count FROM unidad uni
								inner join usuario usu on usu.usu_in_cod=uni.uni_in_pro 
								WHERE  uni.edi_in_cod='$this->edificioId' and uni.uni_in_est=1 AND uni_in_pad IS NULL $filter";

        $row = $adapter->query($stringSelectCount,$adapter::QUERY_MODE_EXECUTE)->current();
    	$count=$row['count'];

		if($count > 0 && $limit > 0){
		    $total_pages = ceil($count / $limit);
		}else{
		    $total_pages = 0;
		}
		if ($page > $total_pages) $page = $total_pages;
		$start = $limit * $page - $limit;
		if($start <0) $start = 0;

		//row unidades
		$adapter=$this->getAdapter();
		$sql=new Sql($this->adapter);

		$selectString = "SELECT uni.uni_in_cod as id, CONCAT(uni_vc_tip,' ',uni_vc_nom) AS unidadNombre, uni_in_pos as propietarioId,
									uni_in_pos as residenteId FROM unidad uni
								inner join usuario usu on usu.usu_in_cod=uni.uni_in_pro 
								WHERE  uni.edi_in_cod='$this->edificioId' and uni.uni_in_est=1 AND uni_in_pad IS NULL $filter";
		$selectString.=" ".$filter;
		$selectString.=" limit $start , $limit";
	
		$rsUnidades=$adapter->query($selectString,$adapter::QUERY_MODE_EXECUTE)->toArray();

		if(!empty($rsUnidades)){
			$i=0;
			$response=array();
	        $response['page'] = $page;
	        $response['total'] = $total_pages;
	        $response['records'] = $count;
			foreach ($rsUnidades as $key => $value){
				
				$detalleDeDeudaPorUnidad=$this->ingresoPendientePagoPorUnidad($value['id']);

				$diasTranscurridos=$detalleDeDeudaPorUnidad['diasTranscurridos'];
				if($diasTranscurridos>0){
					$response['rows'][$i]['id']=$value['id'];
					$response['rows'][$i]['cell']=array($value['unidadNombre'],
						$this->getNombreUsuario($value['propietarioId']),
						$this->getNombreUsuario($value['residenteId']),
						$detalleDeDeudaPorUnidad['deudaVencida'],
						$detalleDeDeudaPorUnidad['diasTranscurridos'],
						$detalleDeDeudaPorUnidad['deudaProximaAVencer'],
						$detalleDeDeudaPorUnidad['deudaTotalUnidad'],
					);

					$i++;
				}
			}
		}
		return $response;
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



	/*
	* 	Nombre de funcion: ingresoPendientePagoPorUnidad(unidadId);
	*
	*	Consulta ingresos por periodo de una unidad cuyo estado este pendiente de pago (1,2).
		Luego por cada ingreso calcula el total pagado y el total y el total a pagar.
	*	Retorna una array();
	*/

	public function ingresoPendientePagoPorUnidad($unidadId){
		$adapter=$this->getAdapter();
		$sql=new Sql($adapter);

		$select=$sql->select()
			->from(array('ing'=>'ingreso'))
			->columns(
				array(
					'ingresoId'=>'ing_in_cod',
					'fechaEmi'=>'ing_da_fecemi',
					'fechaVence'=>'ing_da_fecven',
					'totalEmision'=>new Expression('SUM(coi.coi_do_subtot)')
				)
			)
			->join(array('coi'=>'concepto_ingreso'),'ing.ing_in_cod=coi.ing_in_cod',array())
			->where(array('ing.uni_in_cod'=>$unidadId,'ing.ing_in_est'=>array(1,2)))
			->group('ingresoId')
			->order('fechaEmi ASC');
		$selectString=$sql->buildSqlString($select);
		//echo $selectString."------------------------------";
	
		$rsIngresosDeudaVencida=$adapter->query($selectString,$adapter::QUERY_MODE_EXECUTE)->toArray();
	
		$totales=array();

		if(!empty($rsIngresosDeudaVencida)){
			$deudaVencida=0;
			$deudaProximaAVencer=0;
			$totalDeudaUnidad=0;
			$fechaVenceMasAntigua='';
			foreach ($rsIngresosDeudaVencida as $key => $value){
				if($fechaVenceMasAntigua==''){
					$fechaVenceMasAntigua=$value['fechaVence'];
				}

				$totalEmision=$value['totalEmision'];
				$totalPagado=$this->getSumIngresoParcial($value['ingresoId']);
				$debe=$totalEmision - $totalPagado;
				
				if(strtotime($value['fechaVence']) < strtotime($this->fechasistema)){
					//Deuda Vencida.
					$deudaVencida+=$debe;
				}else{
					//Deuda
					$deudaProximaAVencer+=$debe;
				}
			}
			$diasTranscurridos=(strtotime($this->fechasistema) - strtotime($fechaVenceMasAntigua))/86400;
			$diasTranscurridos=$diasTranscurridos <=0 ? 0: $diasTranscurridos;
			$response=array(
				'deudaVencida'=>number_format($deudaVencida,2,".",","),
				'diasTranscurridos'=>$diasTranscurridos,
				'deudaProximaAVencer'=>number_format($deudaProximaAVencer,2,".",","),
				'deudaTotalUnidad'=>number_format($deudaVencida + $deudaProximaAVencer,2,".",","),
			);
		}else{
			$response=array(
				'deudaVencida'=>0,
				'diasTranscurridos'=>0,
				'deudaProximaAVencer'=>0,
				'deudaTotalUnidad'=>0
			);
		}
		
		return $response;
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


	public function crearExcelMorosos($params)
	{
		
		$adapter = $this->getAdapter();
		$sql=new Sql($adapter);
		$name = 'RPT_MOROSOS-'.time().'.xlsx';
		$file = 'public/temp/consumo/'.$name;

		$mesIni=$params['textMesDesde'];
		$yearIni=$params['textYearDesde'];
		
		$yearHasta=$params['textYearHasta'];
		$mesHasta=$params['textMesHasta'];
		$representaUnidad=trim($params['representaunidad']);

		

		
		$fechaDesde=$yearIni."-".($mesIni<=9?'0'.$mesIni:$mesIni)."-01";
		$fechaHasta=$yearHasta."-".($mesHasta<=9?'0'.$mesHasta:$mesHasta)."-".date("d",(mktime(0,0,0,$mesHasta+1,1,$yearHasta)-1)); //fecha con el ultimo dia del mes.

		if(strtotime($fechaDesde)>strtotime($fechaHasta)){
			return array(
				"message"=>"error",
				"mc"=>"Verificar el intervalo de fechas",
				);
		}

		$fechaDesde_obj=new \DateTime($fechaDesde);
		$diferenciaDeFechas=$fechaDesde_obj->diff(new \DateTime($fechaHasta));
		$diferenciaDeYears=$diferenciaDeFechas->y;
		$diferenciaDeMeses=($diferenciaDeYears * 12) + $diferenciaDeFechas->m;
		

		$objPHPExcel = new \PHPExcel();
		$objSheet = $objPHPExcel->getActiveSheet();
		$objSheet->setTitle('Reporte Morosos');


	
		$listAbecedario=array("A","B","C","D","E","F","G","H","I","J","K","L","M","N","O","P","Q","R","S","T","U","V","W","X","Y","Z","AA","AB","AC","AD","AE","AF","AG","AH","AI","AJ","AK","AL","AM","AN","AO","AP","AQ","AR","AS","AT","AU","AV","AW","AX","AY","AZ");

		
		$objSheet->setCellValue('A1', 'REPORTE DE MOROSIDAD');

		$objSheet->setCellValue('A2', 'DESDE');
		$objSheet->setCellValue('B2', $this->nombreMes[($mesIni -1)]." ".$yearIni);

		$objSheet->setCellValue('A3', 'HASTA');
		$objSheet->setCellValue('B3', $this->nombreMes[($mesHasta -1)]." ".$yearHasta);

		$objSheet->setCellValue('A4', 'FECHA DE CREACIÓN');
		$objSheet->setCellValue('B4', date('d-m-Y H:m a'));



		$objSheet->setCellValue('A6', 'UNIDAD');		
		$currentIndexLetra=1;
		switch ($representaUnidad) {
			case 'propietario':
				$objSheet->setCellValue($listAbecedario[$currentIndexLetra].'6', 'PROPIETARIO');
				$currentIndexLetra++;
				break;
			case 'residente':
				$objSheet->setCellValue($listAbecedario[$currentIndexLetra].'6', 'RESIDENTE');
				$currentIndexLetra++;
				break;
			case 'ambos':
				$objSheet->setCellValue($listAbecedario[$currentIndexLetra].'6', 'PROPIETARIO');
				$currentIndexLetra++;
				$objSheet->setCellValue($listAbecedario[$currentIndexLetra].'6', 'RESIDENTE');
				$currentIndexLetra++;
				break;
			default:
				return "error";
				break;
		}



		$currentYear=$yearIni;
		$currentMes=$mesIni;
		$i=0;
		while ($i <= $diferenciaDeMeses) {

			if($currentMes>12){
				$currentYear++;
				$currentMes=1;
			}

			$nombreMes=$this->nombreMes[(intval($currentMes)-1)];
			$objSheet->setCellValue($listAbecedario[$currentIndexLetra]."6", $nombreMes." ".$currentYear);
			$currentIndexLetra++;

			$currentMes++;
			$i++;
		}


		$objSheet->setCellValue($listAbecedario[$currentIndexLetra++].'6', 'TOTAL');
		$currentIndexLetraColumnTotal=$currentIndexLetra;




		$selectUnidadData=$sql->select()
		->from('unidad')
		->columns(array(
			'unidadId'=>'uni_in_cod',
			'propietarioId'=>'uni_in_pro',
			'residenteId'=>'uni_in_pos',
			'unidad'=>new Expression("CONCAT(uni_vc_tip,' ',uni_vc_nom)")
			))
		->where(array(
			'edi_in_cod'=>$params['edificioId'],
			'uni_in_est'=>1,
			/*'uni_in_pad IS NULL'*/
			));
		$selectUnidad=$sql->buildSqlString($selectUnidadData);
		$dataUnidad=$adapter->query($selectUnidad,$adapter::QUERY_MODE_EXECUTE)->toArray();
		$totalUnidades = count($dataUnidad) + 6;

		$indexFila=7;
		foreach($dataUnidad as $val){
			$select=$sql->select()
			->from(array('ing'=>'ingreso'))
			->columns(array('ingresoId'=>'ing_in_cod','mes'=>new Expression("MONTH(ing.ing_da_fecemi)"),'year'=>new Expression("YEAR(ing.ing_da_fecemi)")))
			->where(array('ing.uni_in_cod'=>$val['unidadId'],"ing.ing_in_est!=0","ing.ing_da_fecven < '".$this->fechasistema."'") )->group('mes')->order(array('year asc','mes asc'));
			$select->where->between('ing.ing_da_fecemi',$fechaDesde,$fechaHasta);
			$selectString=$sql->buildSqlString($select);
			//echo $selectString."_______";
	
			$rsEmision=$adapter->query($selectString,$adapter::QUERY_MODE_EXECUTE)->toArray();

			if(!empty($rsEmision)){
				$objSheet->setCellValue('A'.$indexFila, $val['unidad']);
			

				$currentIndexLetra=1;

				switch ($representaUnidad) {
					case 'propietario':
						$objSheet->setCellValue($listAbecedario[$currentIndexLetra].$indexFila, $this->getNombreUsuario($val['propietarioId']));
						$currentIndexLetra++;
						break;
					case 'residente':
						$objSheet->setCellValue($listAbecedario[$currentIndexLetra].$indexFila, $this->getNombreUsuario($val['residenteId']));
						$currentIndexLetra++;
						break;
					case 'ambos':
						$objSheet->setCellValue($listAbecedario[$currentIndexLetra].$indexFila, $this->getNombreUsuario($val['propietarioId']));
						$currentIndexLetra++;
						$objSheet->setCellValue($listAbecedario[$currentIndexLetra].$indexFila, $this->getNombreUsuario($val['residenteId']));
						$currentIndexLetra++;
						break;
					default:
						return "error";
						break;
				}

				$deudaTotalUnidad=0;

				$rsIndex=0;
				$currentYear=$yearIni;
				$currentMes=(int)$mesIni;

				$i=0;
				while ($i <= $diferenciaDeMeses){

					if($currentMes>12){
						$currentYear++;
						$currentMes=1;
					}

					$ingresoMes=isset($rsEmision[$rsIndex]['mes']) ? $rsEmision[$rsIndex]['mes']:null;
					$ingresoYear=isset($rsEmision[$rsIndex]['year']) ? $rsEmision[$rsIndex]['year']:null;
						
					$ingresoId=0;
						
					if($currentMes==(int)$ingresoMes && $currentYear==(int)$ingresoYear){
						$ingresoId=$rsEmision[$rsIndex]['ingresoId'];
						$totalEmision=$this->getSumConceptosDeIngreso($ingresoId);
						$totalPagado=$this->getSumIngresoParcial($ingresoId);
						$debe=$totalEmision - $totalPagado;

						$objSheet->setCellValue($listAbecedario[$currentIndexLetra].$indexFila, ($debe==0)? '':$debe);
						$deudaTotalUnidad+=$debe;
						$rsIndex++;
					}else{
						$objSheet->setCellValue($listAbecedario[$currentIndexLetra].$indexFila, '');
					}

					$currentIndexLetra++;
					$currentMes++;
					$i++;
				}

				$objSheet->setCellValue($listAbecedario[$currentIndexLetra].$indexFila, $deudaTotalUnidad);
				$indexFila++;
			}
		}

		$currentIndexLetraColumnTotal=$currentIndexLetraColumnTotal - 1;

		for($i=0;$i<$currentIndexLetraColumnTotal;$i++){
			$objSheet->getColumnDimension($listAbecedario[$i])->setAutoSize(false);
		}

		$objSheet->mergeCells('A1:'.$listAbecedario[$currentIndexLetraColumnTotal]."1");
		$objSheet->getStyle('A1')->getFont()->setBold(true);
	
		

    	$objSheet->getStyle("A1:".$listAbecedario[$currentIndexLetraColumnTotal]."1")
    		->applyFromArray(array('alignment' => array(
	            'horizontal' => \PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
	        )));


    	//estilo row 2
    	$styleYEAR = array(
			'rgb' => 'FFFFFF',
			'size'=>10,
	        'alignment' => array(
	            'horizontal' => \PHPExcel_Style_Alignment::HORIZONTAL_LEFT,
	        )
	    );
    	$objSheet->getStyle('B2:'.$listAbecedario[$currentIndexLetraColumnTotal]."6")->applyFromArray($styleYEAR);


    	//stilos para head rows
		$objSheet->getStyle('A6:B6')->getFont()->setBold(true);
		$objSheet->getStyle($listAbecedario[$currentIndexLetraColumnTotal]."6")->getFont()->setBold(true);

		
		$objSheet->getStyle('A6:'.$listAbecedario[$currentIndexLetraColumnTotal]."6")->getFont()->getColor()->applyFromArray( array('rgb' => 'FFFFFF') );
		$objSheet->getStyle('A6:'.$listAbecedario[$currentIndexLetraColumnTotal]."6")->applyFromArray(
			array(
				'fill' => array(
					'type' => \PHPExcel_Style_Fill::FILL_SOLID,
					'color' => array('rgb' => 'C15F9D')
					)
				)
			);


		$objPHPExcel->getActiveSheet()->getStyle('A1:'.$listAbecedario[$currentIndexLetraColumnTotal].$indexFila)->getBorders()->applyFromArray(
			array(
				'allborders' => array(
					'style' => \PHPExcel_Style_Border::BORDER_DASHDOT,
					'color' => array(
						'rgb' => '000000'
						)
					)
				)
			);
		$objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
		$objWriter->save($file);

		if(file_exists($file)){

			/////////save auditoria////////
			$periodoInicio=$this->nombreMes[($mesIni -1)]." ".$yearIni;
			$periodoFin=$this->nombreMes[($mesHasta -1)]." ".$yearHasta;
			$params['accion']='Descarga reporte Morosos (de '.$periodoInicio.' hasta '.$periodoFin.')';
			$this->saveAuditoria($params);
			///////////////////////////////


			$response = array(
				"message"=>"success",
				"ruta"=>"temp/consumo/".$name,
				"nombreFile"=>$name
				);
		}else{
			$response = array("message"=>"nofile");
		}

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
            'aud_opcion'=> 'Reportes > Morosos', //ruta
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
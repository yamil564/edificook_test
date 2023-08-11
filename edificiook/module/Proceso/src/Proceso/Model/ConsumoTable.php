<?php
/**
 * edificioOk (https://www.edificiook.com)
 * creado por: Jhon Gómez, 11/04/2016.
 * ultima modificacion por: Jhon Gómez
 * Fecha Modificacion: 11/04/2016.
 * Descripcion: Script para guardar datos de la generación de cuota de mantenimiento
 *
 * @autor     Fidel J. Thompson
 * @link      https://www.edificiook.com
 * @copyright Copyright (c) 2011-2016 KND S.A.C (http://www.knd.pe)
 * @license   http://www.edificiook.com/license/comercial Software Comercial
 */

namespace Proceso\Model;

use Zend\Db\Sql\Sql;
use Zend\Db\Sql\Select;
use Zend\Db\TableGateway\AbstractTableGateway;
use Zend\Db\Adapter\Adapter;
use Zend\Db\ResultSet\ResultSet;
use Zend\Db\Sql\Expression;

class ConsumoTable extends AbstractTableGateway{

	public $tableConsumo="consumo";
	public $tableUnidad="unidad";
	private $year=null;
	private $servicio=null;
	private $idedificio=null;
	private $unidadMedida=null;
	private $tipoUnidadMedida=null;
	private $viewUnidadMedidad=null;
	private $columna=null;

	public function __construct(Adapter $adapter)
	{
		$this->adapter=$adapter;
	}

	public function listarConsumo($idedificio)
	{
		$adapter=$this->getAdapter();
        $sql=new Sql($adapter);
        $select=$sql->select()
            ->from(array('cons'=>$this->tableConsumo))
            ->columns(array('yearLectura'=> new Expression('YEAR(cns_da_fec)')))
            ->join(array('uni'=>$this->tableUnidad), 'cons.uni_in_cod=uni.uni_in_cod')
            ->where(array('uni.edi_in_cod'=>$idedificio))
            ->group('yearLectura');
        $selectString=$sql->buildSqlString($select);
        return $adapter->query($selectString,$adapter::QUERY_MODE_EXECUTE)->toArray();
	}

	public function cargarGridConsumo($params)
	{		
		//parametros globales
		$this->idedificio = $params['idedificio'];
		$this->servicio = $params['servicio'];
		$this->year = $params['year'];
		$this->tipoUnidadMedida=$params['tipo'];

		/*get parametros Grid*/
		$page = $params['page'];
		$limit = $params['rows'];

		$adapter = $this->getAdapter();
		$sql=new Sql($adapter);
		/******************************/
		$selectUnidad=$sql->select()
			->from(array('uni'=>'unidad'))
			->columns(array('count'=>new Expression('COUNT(*)')))
			->where(array(
					'edi_in_cod'=>$this->idedificio,
					'uni_in_est'=>1,
					'uni_in_pad IS NULL'
				));
		$select=$sql->buildSqlString($selectUnidad);
		$rowUnidad = $adapter->query($select,$adapter::QUERY_MODE_EXECUTE)->current();
		$count = $rowUnidad['count'];
		/******************************/
		if( $count > 0 && $limit > 0) {
		    $total_pages = ceil($count/$limit);
		}else{
		    $total_pages = 0;
		}
		if ($page > $total_pages) $page=$total_pages;
		$start = $limit*$page - $limit;
		if($start <0) $start = 0;
		/*********************************************************/
		$selectUnidadData=$sql->select()
			->from(array('uni'=>'unidad'))
			->where(array(
					'edi_in_cod'=>$this->idedificio,
					'uni_in_est'=>1,
					'uni_in_pad IS NULL'
				));
		$selectUnidad=$sql->buildSqlString($selectUnidadData);
		$dataUnidad=$adapter->query($selectUnidad,$adapter::QUERY_MODE_EXECUTE)->toArray();
        /********************************************************/
        $this->unidadMedida=array(
			'M3'=>array(
				'abr'=>'M3',
				'columnBd'=>'cns_do_conm3'
			),
			'LITRO'=>array(
				'abr'=>'LT',
				'columnBd'=>'cns_do_conl'
			),
			'GALON'=>array(
				'abr'=>'GAL',
				'columnBd'=>'cns_do_congal'
			)
		);
        /********************************************************/
		$this->columna=$this->unidadMedida[$this->tipoUnidadMedida]['columnBd'];
		$this->viewUnidadMedidad=$this->unidadMedida[$this->tipoUnidadMedida]['abr'];
		/********************************************************/
		$responce['page'] = $page;
        $responce['total'] = $total_pages;
        $responce['records'] = $count;
        $responce['fechaLecturas']= $this->getFechasLectura();
        /********************************************************/
		$rowTotalFacturaDelMes = $this->getGridRowGenerales('facturadoDelMes');        
        $rowTotalConsumoIndividual = $this->getGridRowGenerales('consumoIndividual');
        $rowTotalFacturadoEnSoles = $this->getGridRowGenerales('totalFacturadoEnSoles');
		$rowTotalConsumoAreasComunes = $this->getGridRowOperacion('TOTAL CONSUMO AREAS COMUNES', $rowTotalFacturaDelMes, $rowTotalConsumoIndividual, '-');
		$rowPrecio = $this->getGridRowOperacion('PRECIO POR', $rowTotalFacturadoEnSoles, $rowTotalFacturaDelMes, '/');
        /********************************************************/
		$responce['rows'][0]['id'] = "H0";
		$responce['rows'][0]['cell'] = $rowTotalConsumoIndividual;
		/********************************************************/
		$responce['rows'][1]['id'] = "H1";
		$responce['rows'][1]['cell'] = $rowTotalConsumoAreasComunes;
		/********************************************************/
		$responce['rows'][2]['id'] = "H2";
		$responce['rows'][2]['cell'] = $rowTotalFacturaDelMes;
		/********************************************************/
		$responce['rows'][3]['id'] = "H3";
		$responce['rows'][3]['cell'] = $rowTotalFacturadoEnSoles; 
		/*************************************************************/
		$responce['rows'][4]['id'] = "H4";
		$responce['rows'][4]['cell'] = $rowPrecio;
		/*************************************************************/
		$responce['rows'][5]['id'] = "H5";
		$responce['rows'][5]['cell'] = array('Header::<b>GENERAL</b>');
		/*************************************************************/
		$i=6;
		foreach($dataUnidad as $row){
		    $responce['rows'][$i]['id'] = $row['uni_in_cod'];
		    $responce['rows'][$i]['cell'] = $this->showRowData($row['uni_vc_tip'],$row['uni_vc_nom'],$row['uni_in_cod']);
		    $i ++;
		}
		return $responce;
	}

	private function getFechasLectura(){
		$adapter	=$this->getAdapter();
		$sql=new Sql($adapter);

		$sqlString="SELECT cns_da_fechalectura as fecha, month(cns_da_fec) as mes from consumo cns
		INNER JOIN unidad uni on uni.uni_in_cod	=cns.uni_in_cod	
		WHERE uni.edi_in_cod={$this->idedificio} and year(cns_da_fec)={$this->year}
		GROUP BY mes ORDER BY mes";

		$rsLectura=$adapter->query($sqlString,$adapter::QUERY_MODE_EXECUTE)->toArray();
		
		$responce=array();
		$rsIndex=0;
		
		for ($i=1; $i <= 12 ; $i++) {
			$lecturaMes=isset($rsLectura[$rsIndex]['mes']) ? $rsLectura[$rsIndex]['mes']:null;
			if($i==(int)$lecturaMes){

				$fechaLectura=$rsLectura[$rsIndex]['fecha']!='0000-00-00'  ? date('d/m/Y',strtotime($rsLectura[$rsIndex]['fecha'])): '';
				$responce['labelFec_'.$i]=$fechaLectura;
				$rsIndex++;
			}else{
				$responce['labelFec_'.$i]='';
			}
		}

		return $responce;

	}


	private function showRowData($nombre, $numero, $codigo){
		$rowGrid = array($nombre." ".$numero);
		for($i=1;$i<=12;$i++){
			$rowGrid[] = $this->mesAnioLectura($i, $codigo);
			$rowGrid[] = $this->mesAnioConsumo($i, $codigo);
		}
		return $rowGrid;
	}

	private function mesAnioLectura($month, $coduni){
	    
		$adapter = $this->getAdapter();
		$sql = new Sql($adapter);
		$selectConsumo=$sql->select()
			->from('consumo')
			->columns(array(
				'lectura'=>'cns_do_lec',
				'tipo'=>'cns_vc_tip'
				))
			->where(array(
					'MONTH(cns_da_fec)='.$month,
					'YEAR(cns_da_fec)='.$this->year,
					'uni_in_cod'=>$coduni,
					'cns_vc_ser'=>$this->servicio
				));
		$select=$sql->buildSqlString($selectConsumo);
		$rowConsumo=$adapter->query($select,$adapter::QUERY_MODE_EXECUTE)->current();

	    $tipoConsumo = $rowConsumo['tipo'];
	    $monto = 0;
	    if($this->viewUnidadMedidad == 'M3'){        
	        if($tipoConsumo == 'LITRO'){
	            $monto = $rowConsumo['lectura'] / 1000;
	        }else if($tipoConsumo == 'M3'){
	            $monto = $rowConsumo['lectura'];
	        }else if($tipoConsumo == 'GALON'){
	             $monto = $rowConsumo['lectura'] / 264.172874;
	        }
	    }else if($this->viewUnidadMedidad == 'LT'){
	        if($tipoConsumo == 'LITRO'){
	            $monto = $rowConsumo['lectura'];
	        }else if($tipoConsumo == 'M3'){
	            $monto = $rowConsumo['lectura'] * 1000;
	        }else if($tipoConsumo == 'GALON'){
	            $monto = $rowConsumo['lectura'] * 3.7854;
	        }        
	    }else if($this->viewUnidadMedidad == 'GAL'){
	         if($tipoConsumo == 'LITRO'){
	            $monto = $rowConsumo['lectura'] / 3.7854;
	        }else if($tipoConsumo == 'M3'){
	            $monto = $rowConsumo['lectura'] * 264.1728;
	        }else if($tipoConsumo == 'GALON'){
	            $monto = $rowConsumo['lectura'];
	        }
	    }

	    if($monto !=0) return number_format($monto, 2, ".", ",");
	    else return '';

	}

	private function mesAnioConsumo($month, $coduni)
	{
	    $adapter = $this->getAdapter();
		$sql = new Sql($adapter);
		$selectConsumo=$sql->select()
			->from('consumo')
			->columns(array($this->columna))
			->where(array(
					'MONTH(cns_da_fec)='.$month,	
					'YEAR(cns_da_fec)='.$this->year,
					'uni_in_cod'=>$coduni,
					'cns_vc_ser'=>$this->servicio
				));
		$select=$sql->buildSqlString($selectConsumo);
		$rowConsumo=$adapter->query($select,$adapter::QUERY_MODE_EXECUTE)->current();
        
        if($rowConsumo[$this->columna]!=0) return number_format($rowConsumo[$this->columna], 2, ".", ",");
        else if($rowConsumo[$this->columna]=='') return '';
        else return number_format(0, 2, ".", ",");

	}

	private function getGridRowGenerales($accion)
	{
		$adapter = $this->getAdapter();
		$sql = new Sql($adapter);

		switch ($accion) {
			case 'consumoIndividual':
				/********************************************************************/
				$selectConsumoUnidad=$sql->select()
					->from(array('cons'=>'consumo'))
					->columns(array(
						'mes'=>new Expression('MONTH(cons.cns_da_fec)'),
						'totalMes'=>new Expression('SUM('.$this->columna.')') 
					))
					->join(array('uni'=>'unidad'), 'uni.uni_in_cod=cons.uni_in_cod')
					->where(array(
						'uni.edi_in_cod'=>$this->idedificio,
						'cons.cns_vc_ser'=>$this->servicio,
						'YEAR(cns_da_fec)='.$this->year
					))
					->group('mes');
				$select=$sql->buildSqlString($selectConsumoUnidad);
				$rsSelect=$adapter->query($select,$adapter::QUERY_MODE_EXECUTE)->toArray();

				$rowGrid=array();
				$rsIndex=0;

				$rowGrid[]='Header::<b>TOTAL CONSUMO INDIVIDUAL '.$this->viewUnidadMedidad.'</b>';

				/********************************************************************/
				break;

			case 'facturadoDelMes':
				/********************************************************************/
				$selectEgreso=$sql->select()
					->from('egreso')
					->columns(array(
						'mes'=>new Expression('MONTH(egr_da_fecemi)'),
						'totalMes'=>'egr_do_totm3'
					))
					->where(array(
						'YEAR(egr_da_fecemi)='.$this->year,
						'edi_in_cod'=>$this->idedificio,
						'con_in_cod'=>25,
						'egr_do_totm3!=0'
					))
					->group('mes');
				$select=$sql->buildSqlString($selectEgreso);
				$rsSelect=$adapter->query($select,$adapter::QUERY_MODE_EXECUTE)->toArray();
		        
		        $rowGrid=array();
				$rsIndex=0;
				$rowGrid[]='Header::<b>TOTAL FACTURADO DEL MES EN '.$this->viewUnidadMedidad.'</b>';

		        for($i=1;$i<=12;$i++){
					$mes=isset($rsSelect[$rsIndex]['mes'])?$rsSelect[$rsIndex]['mes']:null;
					$monto = 0;
					if($i==(int)$mes){
						$rowGrid[]='';
				    	if($this->viewUnidadMedidad == 'M3') $monto = $rsSelect[$rsIndex]['totalMes'];
				    	else if($this->viewUnidadMedidad == 'LT') $monto = $rsSelect[$rsIndex]['totalMes'] * 1000;
					    else if($this->viewUnidadMedidad == 'GAL') $monto = $rsSelect[$rsIndex]['totalMes'] * 3.78;
						$rowGrid[]=number_format($monto,2,'.',',');
						$rsIndex++;
					}else{
						$rowGrid[]='';
						$rowGrid[]='';
					}	
				}

				return $rowGrid;

				exit();
				/********************************************************************/
			break;

			case 'totalFacturadoEnSoles':

				$selectEgreso=$sql->select()
					->from('egreso')
					->columns(array(
						'mes'=>new Expression('MONTH(egr_da_fecemi)'),
						'totalMes'=>'egr_do_imp'
					))
					->where(array(
						'YEAR(egr_da_fecemi)='.$this->year,
						'edi_in_cod'=>$this->idedificio,
						'con_in_cod'=>25,
						'egr_do_totm3!=0'
					))
					->group('mes');
				$select=$sql->buildSqlString($selectEgreso);
				$rsSelect=$adapter->query($select,$adapter::QUERY_MODE_EXECUTE)->toArray();

				$rowGrid=array();
				$rsIndex=0;
				$rowGrid[]='Header::<b>TOTAL FACTURADO EN S/.</b>';

				break;
			
			default:
				return null;
			break;
		}

		for($i=1;$i<=12;$i++){
			$mes=isset($rsSelect[$rsIndex]['mes'])?$rsSelect[$rsIndex]['mes']:null;
			if($i==(int)$mes){
				$rowGrid[]='';
				$rowGrid[]=number_format($rsSelect[$rsIndex]['totalMes'],2,'.',',');
				$rsIndex++;
			}else{
				$rowGrid[]='';
				$rowGrid[]='';
			}	
		}

		return $rowGrid;
	}

	private function getGridRowOperacion($titulo, $valor1, $valor2, $operacion)
	{
		$rowGrid=array('Header::<b>'.$titulo.' '.$this->viewUnidadMedidad.'</b>');
		for($i=2;$i<=25;$i+=2){
			if(empty($valor1[$i]) and empty($valor2[$i])) {
				$rowGrid[]='';
				$rowGrid[]='';
			}else{ 
				$mesTotalValor1=$valor1[$i]==''? 0: (float)str_replace(',','',$valor1[$i]); 
				$mesTotalValor2=$valor2[$i]=='' ? 0: (float)str_replace(',', '',$valor2[$i] );
				if($operacion == '-') $mesTotal=($mesTotalValor1 - $mesTotalValor2);
				else $mesTotal=($mesTotalValor1 / $mesTotalValor2);
				$rowGrid[]='';
				$rowGrid[]=number_format($mesTotal,2,'.',',');
			}
		}
		return $rowGrid;
	}

	public function listarUnidad($idedificio)
	{
		$adapter = $this->getAdapter();
		$sql=new Sql($adapter);
        $select=$sql->select()
				->from('unidad')
				->columns(array(
					'id'=>'uni_in_cod',
					'nombreDepartamento'=>'uni_vc_tip',
					'numeroDepartamento'=>'uni_vc_nom'))
				->where(array(
					'edi_in_cod'=>$idedificio,
					'uni_in_pad IS NULL',
					'uni_in_est'=>1
				));
		$query=$sql->buildSqlString($select);
        $data = $adapter->query($query,$adapter::QUERY_MODE_EXECUTE)->toArray();
		return $data;
	}

	public function listarTarifa()
	{
		$adapter = $this->getAdapter();
		$sql=new Sql($adapter);
        $select=$sql->select()
				->from('tarifa_consumo')
				->columns(array(
					'id'=>'tac_in_cod',
					'descripcion'=>'tac_vc_des',
					'precio1'=>'tac_do_prc1',
					'precio2'=>'tac_do_prc2',
					'precio3'=>'tac_do_prc3',
					'estado'=>'tac_in_est'
				))
				->where(array('tac_in_est'=>1));
		$query=$sql->buildSqlString($select);
        $data = $adapter->query($query,$adapter::QUERY_MODE_EXECUTE)->toArray();
		return $data;
	}

	public function getInformacionUnidad($params)
	{
		$adapter = $this->getAdapter();

		$codigoUnidad = $params['codigoUnidad'];
		$servicio = $params['servicio'];
		$fecha = $params['fecha'];
	    $fec = explode("/", $fecha);
	    $month = $fec[1];
	    $year = $fec[2];
	    
	    $sql=new Sql($adapter);
        $selectConsumo=$sql->select()
				->from('consumo')
				->columns(array(
					'cns_in_cod',
					'cns_do_lec',
					'cns_ti_hor',
					'cns_vc_tip',
					'cns_vc_tipver'
				))
				->where(array(
					'MONTH(cns_da_fec)='.$month,
					'YEAR(cns_da_fec)='.$year,
					'uni_in_cod'=>$codigoUnidad,
					'cns_vc_ser'=>$servicio
				));
		$queryConsumo=$sql->buildSqlString($selectConsumo);
        $rowConsumo1 = $adapter->query($queryConsumo,$adapter::QUERY_MODE_EXECUTE)->current();

	    $codigoConsumo = $rowConsumo1['cns_in_cod'];
	    $lectura1 = $rowConsumo1['cns_do_lec'];

	    if ($month - 1 == 0) {
	        $month = 12;
	        $year = $fec[2] - 1;
	    } else {
	        $month = $fec[1] - 1;
	    }

	    $sql=new Sql($adapter);
        $selectConsumo2=$sql->select()
				->from('consumo')
				->columns(array('cns_do_lec','cns_ti_hor','cns_vc_tip','cns_vc_tipver'))
				->where(array(
					'MONTH(cns_da_fec)='.$month,
					'YEAR(cns_da_fec)='.$year,
					'uni_in_cod'=>$codigoUnidad,
					'cns_vc_ser'=>$servicio
				));
		$queryConsumo2=$sql->buildSqlString($selectConsumo2);
        $rowConsumo2 = $adapter->query($queryConsumo2,$adapter::QUERY_MODE_EXECUTE)->current();

	    $lectura2 = $rowConsumo2['cns_do_lec'];
	    $horaConsumo1 = $rowConsumo1['cns_ti_hor'];
	    $tipoConsumo = $rowConsumo1['cns_vc_tip'];
	    $tipoVer = $rowConsumo1['cns_vc_tipver'];

	    if ($tipoConsumo == '' || $tipoConsumo == null) {
	        $tipoConsumo = $rowConsumo2['cns_vc_tip'];
	    }

	    if ($tipoVer == '' || $tipoVer == null) {
	        $tipoVer = $rowConsumo2['cns_vc_tipver'];
	    }

	    $sql=new Sql($adapter);
        $selectConsumo3=$sql->select()
				->from('consumo')
				->columns(array('cns_in_cod','cns_do_lec'))
				->where(array(
					'YEAR(cns_da_fec)='.$year,
					'uni_in_cod'=>$codigoUnidad,
					'cns_vc_ser'=>$servicio
				))
				->order('cns_da_fec DESC')
				->limit('1');
		$queryConsumo3=$sql->buildSqlString($selectConsumo3);
        $rowConsumo3 = $adapter->query($queryConsumo3,$adapter::QUERY_MODE_EXECUTE)->current();

	    $CodConVal3 = $rowConsumo3['cns_in_cod'];
	    if ($codigoConsumo == $CodConVal3) {
	    	return array(
	    		"lecturaAnterior"=>$lectura2,
	    		"lecturaActual"=>$lectura1,
	    		"hora"=>$horaConsumo1,
	    		"tipo"=>$tipoConsumo,
	    		"tipoVer"=>$tipoVer,
	    		"estado"=>1
	    	);
	    
	    } else {
	    	return array(
	    		"lecturaAnterior"=>$lectura2,
	    		"lecturaActual"=>$lectura1,
	    		"hora"=>$horaConsumo1,
	    		"tipo"=>$tipoConsumo,
	    		"tipoVer"=>$tipoVer,
	    		"estado"=>0
	    	);
	        
	    }
	}



	public function updateConsumo($params)
	{
		$adapter = $this->getAdapter();

		$coduni = $params['coduni'];
	    $servicio = $params['servicio'];
	    $fecha = $params['fecha'];
	    $fec = explode("/", $fecha);
	    $fecha = $fec[2] . "-" . $fec[1] . "-" . $fec[0];
	    $hora = $params['hora'];
	    $lecact = $params['lecact'];
	    $tipcon = $params['tipcon'];
	    $tipver = $params['tipver'];

	    if ($tipcon == 'LITRO') {
	        $consumoLitro = $params['consumo'];
	        $consumoM3 = $params['consumo'] / 1000;
	        $consumoGalon = $params['consumo'] / 3.7854;
	    } else if ($tipcon == 'M3') {
	        $consumoLitro = $params['consumo'] * 1000;
	        $consumoM3 = $params['consumo'];
	        $consumoGalon = $params['consumo'] * 264.172874;
	    } else if ($tipcon == 'GALON') {
	        $consumoLitro = $params['consumo'] * 3.7854;
	        $consumoM3 = $params['consumo'] / 264.172874;
	        $consumoGalon = $params['consumo'];
	    }

	    $consumoLitro = round($consumoLitro, 2);
	    $consumoM3 = round($consumoM3, 2);
	    $consumoGalon = round($consumoGalon, 2);

	    $sql=new Sql($adapter);
        $selectConsumo=$sql->select()
				->from('consumo')
				->columns(array('cns_in_cod'))
				->order('cns_in_cod DESC');
		$queryConsumo=$sql->buildSqlString($selectConsumo);
        $rowConsumo = $adapter->query($queryConsumo,$adapter::QUERY_MODE_EXECUTE)->current();

	    ($rowConsumo['cns_in_cod'] != '' && $rowConsumo['cns_in_cod'] != null) ? $codigo = $rowConsumo['cns_in_cod'] + 1 : $codigo = 1;

	    $sql=new Sql($adapter);
        $selectConsumo2=$sql->select()
				->from('consumo')
				->columns(array('cns_in_cod'))
				->where(array(
					'MONTH(cns_da_fec)='.$fec[1],
					'YEAR(cns_da_fec)='.$fec[2],
					'uni_in_cod'=>$coduni,
					'cns_vc_ser'=>$servicio
				));
		$queryConsumo2=$sql->buildSqlString($selectConsumo2);
        $rowCodigoConsumo = $adapter->query($queryConsumo2,$adapter::QUERY_MODE_EXECUTE)->current();

	    $codigoConsumo = $rowCodigoConsumo['cns_in_cod'];
	    //param auditoria
	    $params['numRegistro']='';
	    $params['accion']='';
	    $response=array();
	    if ($codigoConsumo != '' && $codigoConsumo != null) {

	    	$data = array(
	    		'cns_vc_tip'=>$tipcon,
	    		'cns_vc_tipver'=>$tipver,
	    		'cns_do_conl'=>$consumoLitro,
	    		'cns_do_conm3'=>$consumoM3,
	    		'cns_do_congal'=>$consumoGalon,
	    		'cns_do_lec'=>$lecact,
	    		'cns_da_fec'=>$fecha,
	    		'cns_ti_hor'=>$hora
	    	);
	    	$update=$sql->update()
	            ->table('consumo')
	            ->set($data)
	            ->where(array(
	            	'MONTH(cns_da_fec)='.$fec[1],
	            	'YEAR(cns_da_fec)='.$fec[2],
	            	'uni_in_cod'=>$coduni,
	            	'cns_vc_ser'=>$servicio
	            ));
            $sql->prepareStatementForSqlObject($update)->execute();
            //param auditoria
            $params['numRegistro']=$this->getIdConsumo($fec[1],$fec[2],$servicio,$coduni);
            $params['accion']='Editar';
        	$response = array(
        		"result"=>"success",
        		"titulo"=>"Consumo de agua",
        		"cuerpo"=>"Los datos se actualizaron correctamente..."
        	);
	    } else {

	    	$insert = $sql->insert('consumo');
            $dataLugar = array(
                'cns_in_cod'=> $codigo,
                'uni_in_cod'=> $coduni,
                'cns_vc_ser'=> $servicio,
                'cns_da_fec'=> $fecha,
                'cns_ti_hor'=> $hora,
                'cns_do_lec'=> $lecact,
                'cns_do_conl'=> $consumoLitro,
                'cns_do_conm3'=> $consumoM3,
                'cns_do_congal'=> $consumoGalon,
                'cns_vc_tip'=> $tipcon,
                'cns_vc_tipver'=> $tipver,
            );
            $insert->values($dataLugar);
            $idRegistro = $sql->prepareStatementForSqlObject($insert)->execute()->getGeneratedValue();
            //param auditoria
            $params['numRegistro']=$idRegistro;
            $params['accion']='Guardar';
        	$response = array(
        		"result"=>"success",
        		"titulo"=>"Consumo de agua",
        		"cuerpo"=>"Los datos se agregaron correctamente..."
        	);
	    }

	    /////////save auditoria////////
	    $params['tabla']='Consumo';
        $this->saveAuditoria($params);
        ///////////////////////////////
        return $response;

	}

	private function getIdConsumo($mes,$anio,$servicio,$codUnidad)
	{
		$adapter=$this->getAdapter();
		$queryConsumo = "SELECT cns_in_cod AS idConsumo FROM consumo WHERE uni_in_cod = $codUnidad AND MONTH(cns_da_fec) = $mes AND YEAR(cns_da_fec) = $anio AND cns_vc_ser='$servicio'";
		$rowConsumo = $adapter->query($queryConsumo,$adapter::QUERY_MODE_EXECUTE)->current();
		return $rowConsumo['idConsumo'];
	}

	public function formato($idedificio,$idusuario,$accion,$params)
	{
		$adapter = $this->getAdapter();
		$sql=new Sql($adapter);
		$name = 'formato-'.time().'.xlsx';
		$file = 'public/temp/consumo/'.$name;

		switch ($accion) {
			case 'dowload':
				
				$objPHPExcel = new \PHPExcel();
		        $objSheet = $objPHPExcel->getActiveSheet();
		        $objSheet->setTitle('Formato de ejemplo');
		        $objSheet->getStyle('A1:F1')->getFont()->setBold(true);

		        $objSheet->setCellValue('A1', 'UNID. INMOBILIARIA');
		        $objSheet->setCellValue('B1', 'AÑO');
		        $objSheet->setCellValue('C1', 'MES');
		        $objSheet->setCellValue('D1', 'TIPO');
		        $objSheet->setCellValue('E1', 'TIPO A VISUALIZAR');
		        $objSheet->setCellValue('F1', 'LECTURA ACTUAL');
		        
		        $selectUnidadData=$sql->select()
					->from('unidad')
					->columns(array(
						'unidad'=>new Expression("CONCAT(uni_vc_tip,' ',uni_vc_nom)")
					))
					->where(array(
						'edi_in_cod'=>$idedificio,
						'uni_in_est'=>1,
						'uni_in_pad IS NULL'
					));
				$selectUnidad=$sql->buildSqlString($selectUnidadData);
				$dataUnidad=$adapter->query($selectUnidad,$adapter::QUERY_MODE_EXECUTE)->toArray();
				$totalUnidades = count($dataUnidad) + 1;
				
				$i=2;
				foreach($dataUnidad as $val){
					$objSheet->setCellValue('A'.$i++, $val['unidad']);
				}

				$row = array("A","B","C","D","E","F");

		        foreach($row as $val){            
		            $objSheet->getColumnDimension($val)->setAutoSize(true);
		        }
		        
		        $objSheet->getStyle('A1:F1')->getFont()->getColor()->applyFromArray( array('rgb' => 'FFFFFF') );
		        $objSheet->getStyle('A1:F1')->applyFromArray(
			        array(
			            'fill' => array(
			                'type' => \PHPExcel_Style_Fill::FILL_SOLID,
			                'color' => array('rgb' => 'C15F9D')
			            )
			        )
				);
				$objPHPExcel->getActiveSheet()->getStyle('A1:F'.$totalUnidades)->getBorders()->applyFromArray(
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
					$params['tabla']='';
					$params['numRegistro']='';
					$params['accion']='Descarga Formato';
					$this->saveAuditoria($params);
					///////////////////////////////
		        	$response = array(
		        		"message"=>"success",
		        		"cuerpo"=>"formato generado correctamente...",
		        		"ruta"=>"temp/consumo/".$name,
		        		"nombreFile"=>$name
		        	);
		        }else{
		        	$response = array("message"=>"nofile");
		        }

			break;
			
			case 'delete':
				$archivo = 'public/temp/consumo/'.$params['file'];
				if(file_exists($archivo)){
					@unlink($archivo);
					$response = array(null);
				}
			break;

			default:
				return null;
			break;
		}

        return $response;

	}

	public function updateFechaLecturas($params){
		$adapter=$this->getAdapter();
		$sql=new Sql($adapter);

		$edificioId=$params['idEdificio'];
		$mes=$params['mes'];
		$year=$params['year'];
		$fecha=str_replace("/","-",$params['fecha']);
		$fecha=date("Y-m-d",strtotime($fecha) );

		if($mes=="" || $year==""){
			return ['tipo'=>'error','mensaje'=>'No se ingresaron los datos necesarios para actualizar las fechas de lectura.'];  
		}

		

		$sqlStringUpdate="UPDATE consumo as cns 
		INNER JOIN unidad as uni on uni.uni_in_cod=cns.uni_in_cod
		SET cns.cns_da_fechalectura='$fecha'
		WHERE uni.edi_in_cod=$edificioId AND MONTH(cns.cns_da_fec)=$mes AND YEAR(cns.cns_da_fec)=$year";

		$adapter->query($sqlStringUpdate,$adapter::QUERY_MODE_EXECUTE);

		/*Registrar Auditoria*/
		$params['tabla']='consumo';
		$params['numRegistro']='';
		$params['accion']='Actualizar Fecha de: '.$mes."/".$year;
		$this->saveAuditoria($params);


		return ['tipo'=>'informativo','mensaje'=>'Fechas de lectura actualizadas con éxito.'];  
	}

	private function saveAuditoria($params)
    {   
        $adapter=$this->getAdapter();
        $sql=new Sql($adapter);
        $insert = $sql->insert('auditoria');
        $data = array(
            'usu_in_cod'=> $params['idUsuario'], //idusuario
            'edi_in_cod'=> $params['idEdificio'], //idedificio
            'aud_opcion'=> 'Procesos > Consumo de agua', //ruta
            'aud_accion'=> $params['accion'], //accion
            'aud_fecha'=> date('Y-m-d H:i:s'), //fecha y hora
            'aud_ip'=> $_SERVER['REMOTE_ADDR'], //ip publica
            'aud_tabla'=> $params['tabla'], //tabla
            'aud_in_numra'=> $params['numRegistro'], //registro afectado
            'aud_info_adi'=> $_SERVER['HTTP_USER_AGENT'] //agente
        );
        $insert->values($data);
        $sql->prepareStatementForSqlObject($insert)->execute()->count();
    }


}
<?php
/**
 * EdificioOk (https://www.edificiook.com)
 * creado por: Meler Carranza, 13/05/2016.
 * ultima modificacion por: Meler Carranza
 * Fecha Modificación: 13/05/2016.
 * Descripcion: 
 *
 * @autor     Fidel J. Thompson
 * @link      https://www.edificiook.com
 * @copyright Copyright (c) KND S.A.C (http://www.knd.pe)
 * @license   http://www.edificiook.com/license/comercial Software Comercial
 */


namespace Reporte\Model;

use Zend\Db\Sql\Sql;
use Zend\Db\Sql\Select;
use Zend\Db\Adapter\Adapter;
use Zend\Db\Sql\Expression;
use Zend\Session\Container;



class BalanceTable{

	private $edificioId=null;
	private $usuarioId=null;

	private $fechaBalance=null;
	private $dia=null;
	private $mes=null;
	private $year=null;

	public function __construct(Adapter $adapter)
	{
		$this->adapter=$adapter;
	}


	public function getDataBalance($params){
		$adapter=$this->adapter;

		$this->edificioId=$params['edificioId'];
		$this->mes=$params['mes'];
		$this->year=$params['year'];

		
		$this->fechaBalance= date("Y-m-d",(mktime(0,0,0,$this->mes+1,1,$this->year)-1)); //Fecha con el ultimo dia del mes
		if(strtotime($this->fechaBalance) > strtotime(date('Y-m-d')) ) {
			$this->fechaBalance=date('Y-m-d');
		}

		$this->dia=date('d',strtotime($this->fechaBalance));


		$respuesta=array();
		$respuesta['edificio']=$this->getDatosEdificio($this->edificioId);
		$respuesta['fechaBalance']=date('d/m/Y',strtotime($this->fechaBalance));
		$respuesta['saldoBancoMesAnterior']=$this->saldoBancoMesAnterior();


		/*
		**INGRESOS AL
		*-----------------------------------------------------------------------------------------------------------------------------
		*/
		$consultarRegistroPagos="SELECT ing.ing_in_cod as ingresoId,
			CONCAT_WS(' ',SUBSTRING(uni.uni_vc_tip,1,3),uni.uni_vc_codpag) as unidad,
			MONTH(ing.ing_da_fecemi) as mesEmi,
			YEAR(ing.ing_da_fecemi) as yearEmi,
			(SELECT sum(coi.coi_do_subtot) FROM concepto_ingreso coi WHERE coi.ing_in_cod=ing.ing_in_cod) as totalEmitido,
			ipa.ipa_do_imp as importe,
			ing.ing_in_nroser as nro_serie,
			ing.ing_in_nrodoc as nro_doc
			FROM ingreso_parcial ipa
			INNER JOIN ingreso ing on ing.ing_in_cod=ipa.ing_in_cod
			INNER JOIN unidad uni on uni.uni_in_cod=ing.uni_in_cod
			WHERE uni.edi_in_cod=$this->edificioId 
				AND YEAR(ipa.ipa_da_fecpag)=$this->year 
				AND MONTH(ipa.ipa_da_fecpag)=$this->mes 
				AND DAY(ipa.ipa_da_fecpag)<=$this->dia";

		$rsIngresosAl=$adapter->query($consultarRegistroPagos,$adapter::QUERY_MODE_EXECUTE)->toArray();
		$respuesta['INGRESOS_AL']=$rsIngresosAl;
		/*
		*-------------------------------------------------------------------------------------------------------------------------------
		*/

		
		$sqlConceptoGrupos="SELECT cog_in_cod as id, cog_vc_des as grupo
			FROM concepto_grupo 
			WHERE cog_in_est !=0 
			AND cog_in_cod IN(SELECT DISTINCT(cog_in_cod) AS cog_in_cod FROM concepto WHERE con_vc_tip = 'EGRESO')";
		$grupoConceptos = $adapter->query($sqlConceptoGrupos, $adapter::QUERY_MODE_EXECUTE)->toArray();




		/*
		**EGRESOS AL
		*-----------------------------------------------------------------------------------------------------------------------------
		*/
		$i=0;
		foreach ($grupoConceptos as $grupoconcepto) {

			$dataEgresosPendientePago[$i]=$grupoconcepto;

			$sqlConceptos="SELECT c.con_in_cod AS id, con_vc_des AS concepto, SUM(epa_do_imp) AS total 
				FROM egreso_parcial epa, egreso e, concepto c
				WHERE epa.egr_in_cod = e.egr_in_cod
					AND c.con_in_cod = e.con_in_cod
					AND e.edi_in_cod =$this->edificioId
					AND MONTH(epa_da_fecpag) = $this->mes
					AND YEAR(epa_da_fecpag) = $this->year
					AND DAY(epa_da_fecpag) <= $this->dia
					AND cog_in_cod = ".$grupoconcepto['id']." GROUP BY c.con_in_cod";
			$listaConceptosEgreso = $adapter->query($sqlConceptos,$adapter::QUERY_MODE_EXECUTE)->toArray();


			$iArrayConceptoEgreso=array();
			$j=0;
			foreach ($listaConceptosEgreso as $conceptoEgreso) {

				$sqlProveedores="SELECT p.prv_vc_razsoc as razonsocial,
								e.egr_te_obser as observacion,
								CONCAT(e.egr_vc_nroser,' - ',e.egr_vc_nrodoc) AS numero_recibo,
								epa_do_imp AS total
								FROM egreso_parcial epa, egreso e, concepto c, proveedor p
								WHERE p.prv_in_cod = e.prv_in_cod
									AND epa.egr_in_cod = e.egr_in_cod 
									AND c.con_in_cod = e.con_in_cod 
									AND e.edi_in_cod = $this->edificioId
									AND MONTH(epa_da_fecpag) = $this->mes 
									AND YEAR(epa_da_fecpag) = $this->year 
									AND DAY(epa_da_fecpag) <= $this->dia
									AND c.con_in_cod=".$conceptoEgreso['id'];

				if($params['tipo']=='resumido'){
					$sqlProveedores="SELECT p.prv_vc_razsoc as razonsocial,
								e.egr_te_obser as observacion,
								CONCAT(e.egr_vc_nroser,' - ',e.egr_vc_nrodoc) AS numero_recibo,
								sum(epa_do_imp) AS total
								FROM egreso_parcial epa, egreso e, concepto c, proveedor p
								WHERE p.prv_in_cod = e.prv_in_cod
									AND epa.egr_in_cod = e.egr_in_cod 
									AND c.con_in_cod = e.con_in_cod 
									AND e.edi_in_cod = $this->edificioId
									AND MONTH(epa_da_fecpag) = $this->mes 
									AND YEAR(epa_da_fecpag) = $this->year 
									AND DAY(epa_da_fecpag) <= $this->dia
									AND c.con_in_cod=".$conceptoEgreso['id']." GROUP BY e.egr_in_cod";
				}

				$listaEgresosProveedor = $adapter->query($sqlProveedores,$adapter::QUERY_MODE_EXECUTE)->toArray();
		    	$iArrayConceptoEgreso[$j]=$conceptoEgreso;
		    	$iArrayConceptoEgreso[$j]['rows']=$listaEgresosProveedor;
		    	$j++;
			}

			$dataEgresosPendientePago[$i]["rows"]=$iArrayConceptoEgreso;

			$totalEgresoPendiente=0;
			$i++;
		}
		$respuesta['EGRESOS_AL']=$dataEgresosPendientePago;

		/*
		**INGRESOS PENDIENTES POR COBRAR AL:
		*-----------------------------------------------------------------------------------------------------------------------------
		*/
		$pendientesPago="SELECT ing.ing_in_cod as idIngreso,
					    uni.uni_in_cod as idUnidad,
					    uni_vc_nom as unidad,
					    IF(uni.uni_in_pad IS NULL, '1','2') as gerarquia,
					    IF(u.usu_ch_tip='PJ', u.usu_vc_ape, CONCAT(u.usu_vc_nom,' ',u.usu_vc_ape)  ) as responsable,
							ing.ing_da_fecemi as fecha_emision,
					    ing.ing_da_fecven as fecha_vence,
					    MONTH(ing.ing_da_fecemi) as mesEmi,
					    YEAR(ing.ing_da_fecemi) as yearEmi,
							ing.ing_in_nroser as nro_serie,
							ing.ing_in_nrodoc as nro_doc,
						(
							(SELECT SUM(coi.coi_do_subtot) FROM concepto_ingreso coi WHERE coi.ing_in_cod=ing.ing_in_cod) -
							(SELECT IFNULL(SUM(ipa.ipa_do_imp),0) FROM ingreso_parcial ipa WHERE ipa.ing_in_cod=ing.ing_in_cod AND ipa.ipa_da_fecpag <='$this->fechaBalance')
						) as debe,
						'1' as idUser_session
						FROM ingreso ing
							INNER JOIN unidad uni on uni.uni_in_cod=ing.uni_in_cod
							LEFT JOIN usuario u on u.usu_in_cod=IF(uni.uni_in_pos!=NULL,uni.uni_in_pos,uni.uni_in_pro)
						WHERE ing.ing_da_fecven<='$this->fechaBalance'
							AND uni.edi_in_cod=$this->edificioId and uni.uni_in_est!=0 HAVING debe!=0 ORDER BY idUnidad ASC, fecha_emision DESC;";
		$dataPendientesPago= $adapter->query($pendientesPago,$adapter::QUERY_MODE_EXECUTE)->toArray();
		$respuesta['INGRESOS_PENDIENTEPAGO_AL']=$dataPendientesPago;

		
		/*
		**EGRESOS PENDIENTES DE PAGO AL:
		*-----------------------------------------------------------------------------------------------------------------------------
		*/
		$i=0;
		foreach ($grupoConceptos as $grupoconcepto) {

			$dataEgresosPendientePago[$i]=$grupoconcepto;

			$sqlConceptos="SELECT DISTINCT con.con_in_cod AS id, con.con_vc_des AS concepto 
				FROM egreso egr
				INNER JOIN concepto con on con.con_in_cod=egr.con_in_cod 
				WHERE egr.edi_in_cod =$this->edificioId AND egr.egr_da_fecven<='$this->fechaBalance' AND cog_in_cod = ".$grupoconcepto['id'];
			$dataConceptosEgreso = $adapter->query($sqlConceptos,$adapter::QUERY_MODE_EXECUTE)->toArray();

			$iListaConceptosEgreso=array();
			
			$j=0;
			foreach ($dataConceptosEgreso as $conceptoEgreso) {

				$sqlProveedores="SELECT prv.prv_vc_razsoc as razonsocial,
								egr.egr_te_obser as observacion,
								CONCAT(egr.egr_vc_nroser,' - ',egr.egr_vc_nrodoc) AS numero_recibo,
								(egr_do_imp - 
							        (SELECT IFNULL(SUM(epa_do_imp),0)
							          FROM egreso_parcial epa
							          WHERE epa.egr_in_cod=egr.egr_in_cod AND epa.epa_da_fecpag <= '$this->fechaBalance'
							        )
							    )as debe
								FROM egreso egr
								INNER JOIN proveedor prv on prv.prv_in_cod = egr.prv_in_cod
								WHERE egr.edi_in_cod = $this->edificioId AND egr.egr_da_fecven<='$this->fechaBalance'
									AND egr.con_in_cod=".$conceptoEgreso['id']." HAVING debe!=0  ORDER BY egr.prv_in_cod ASC";
				
				$egresosProveedor = $adapter->query($sqlProveedores,$adapter::QUERY_MODE_EXECUTE)->toArray();
				
				if(!empty($egresosProveedor)){
					$iListaConceptosEgreso[$j]=$conceptoEgreso;
			    	$iListaConceptosEgreso[$j]['rows']=$egresosProveedor;
			    	$j++;
				}
			}

			$dataEgresosPendientePago[$i]["rows"]=$iListaConceptosEgreso;

			$totalEgresoPendiente=0;
			$i++;
		}
		$respuesta['EGRESOS_PENDIENTEPAGO_AL']=$dataEgresosPendientePago;

		return $respuesta;
	}





	
	public function saldoBancoMesAnterior(){
		$adapter=$this->adapter;

		$fechaMesAnterior=date('Y-m-d',strtotime('-1 day', strtotime($this->year."-".$this->mes."-01") ) );


		$consultaIngreso="SELECT sum(ipa.ipa_do_imp) as total
			FROM ingreso_parcial ipa
			INNER JOIN ingreso ing on ing.ing_in_cod=ipa.ing_in_cod
			INNER JOIN unidad uni on uni.uni_in_cod=ing.uni_in_cod
			WHERE uni.edi_in_cod=$this->edificioId
				AND ipa.ipa_da_fecpag<= '$fechaMesAnterior' ";
		$rsIngresosAl=$adapter->query($consultaIngreso, $adapter::QUERY_MODE_EXECUTE)->current();


		$consultaEgreso="SELECT SUM(epa_do_imp) AS total 
						FROM egreso_parcial ep
						INNER JOIN  egreso egr on egr.egr_in_cod=ep.egr_in_cod 
						WHERE egr.edi_in_cod = $this->edificioId AND epa_da_fecpag <= '$fechaMesAnterior' ";
		$rsEgresosAl=$adapter->query($consultaEgreso, $adapter::QUERY_MODE_EXECUTE)->current();

		$saldoInicial=0;
		$totalIngreso=$rsIngresosAl['total'];
		$totalEgreso=$rsEgresosAl['total'];
		$saldoBancoMesAnterior=($totalIngreso - $totalEgreso);

		return [
			"fecha"=>date('d/m/Y', strtotime($fechaMesAnterior)),
			"total"=>$saldoBancoMesAnterior,
		];
	}


	public function getYearsInIngreso($edificioId)
	{
		$adapter=$this->adapter;
        $sql=new Sql($adapter);

        $queryYears="SELECT DISTINCT(YEAR(ing_da_fecemi)) as year FROM ingreso ing
        inner join unidad uni on uni.uni_in_cod=ing.uni_in_cod
        WHERE uni.edi_in_cod=$edificioId order by year desc";
        
        $data = $adapter->query($queryYears,$adapter::QUERY_MODE_EXECUTE)->toArray();
        return $data;
	}

	public function getDatosEdificio($edificioId){
		$adapter=$this->adapter;

		$queryEdificio="SELECT edi_vc_des as nombre, edi_vc_img as logo, CONCAT(usu_vc_ape,', ',usu_vc_nom) AS administrador   
			FROM edificio edi
			LEFT JOIN usuario usu on usu.usu_in_cod=edi.edi_in_adm
			where edi_in_cod=$edificioId";
		$data = $adapter->query($queryEdificio,$adapter::QUERY_MODE_EXECUTE)->current();
        return $data;

	}
}

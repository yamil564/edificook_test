<?php

/*
@ Creado Por: Meler Carranza-
*/
namespace Finanzas\Model;
use Zend\Db\Sql\Sql;
use Zend\Db\Sql\Select;
use Zend\Db\Adapter\Adapter;
use Zend\Db\Sql\Expression;
use Zend\Session\Container;

class ConciliacionbancariaTable{

	private $adapter=null;
	private $idUsuario=null;
	private $yearSelected=null;
  private $mesSelected=null;
  private $edificioId=null;


	public function __construct(Adapter $adapter)
	{
		$this->adapter=$adapter;

		$session=new Container('User');
        $this->idUsuario=$session->offsetGet('userId');
        $this->edificioId=$session->offsetGet('edificioId');

	}


	public function listarGrid($params){
    $adapter=$this->adapter;
    $sql = new Sql($this->adapter);

		$this->yearSelected=isset($params['year'])?$params['year']:date('Y');
    $this->mesSelected=isset($params['mes'])?$params['mes']:date('m');

    

		
		$response=array();

		$queryString="SELECT uni.edi_in_cod AS edificioid,
                          ipa.ipa_in_cod AS codigo,
                          'INGRESO' AS tipo,
                          ipa.ipa_da_fecpag AS fechapago,
                          ipa.ipa_do_impban AS monto,
                          '' AS proveedor,
                          CONCAT(uni.uni_vc_tip,' ',uni.uni_vc_nom) AS descripcion,
                          ipa.ipa_vc_numope AS nro_operacion,
                          ipa.ipa_in_nroser AS nro_serie,
                          ipa.ipa_in_nrodoc AS nro_doc,
                          ipa.ipa_vc_tipdoc AS tipo_doc,
                          ipa.ipa_da_fecemi AS fecha_emi,
                          ipa.ipa_vc_ban AS banco,
                          '' AS adjunto,
                          ipa.ipa_te_obs AS comentario
                            FROM ingreso_parcial ipa
                              INNER JOIN ingreso ing ON ing.ing_in_cod=ipa.ing_in_cod
                              INNER JOIN unidad uni ON uni.uni_in_cod=ing.uni_in_cod
                            WHERE ipa.ipa_in_cod=ipa.ipa_in_codimp
                              AND uni.edi_in_cod={$this->edificioId}
                              AND YEAR(ipa.ipa_da_fecpag)={$this->yearSelected}
                              AND MONTH(ipa.ipa_da_fecpag)={$this->mesSelected}
                        UNION
                         (SELECT egr.edi_in_cod AS edificioid,
                          epa.egr_in_cod AS codigo,
                          'EGRESO' AS tipo,
                          epa.epa_da_fecpag AS fechapago,
                          epa.epa_do_imp AS monto,
                          prv.prv_vc_razsoc AS proveedor,
                          '' AS descripcion,
                          epa.epa_vc_nroope AS nro_operacion,
                          egr.egr_vc_nroser AS nro_serie,
                          egr.egr_vc_nrodoc AS nro_doc,
                          egr.egr_vc_tipdoc AS tipo_doc,
                          egr.egr_da_fecemi AS fecha_emi,
                          epa.epa_vc_ban AS banco ,
                          egr.egr_vc_adj AS adjunto,
                          egr.egr_te_obser as comentario
                        FROM egreso_parcial epa
                          INNER JOIN egreso egr ON egr.egr_in_cod=epa.egr_in_cod
                          INNER JOIN proveedor prv on prv.prv_in_cod=egr.prv_in_cod
                        WHERE egr.edi_in_cod={$this->edificioId} 
                        AND YEAR(epa.epa_da_fecpag)={$this->yearSelected} AND MONTH(epa.epa_da_fecpag)={$this->mesSelected} ) ORDER BY nro_operacion ASC";
        $rsMovimientosBancos=$adapter->query($queryString, adapter::QUERY_MODE_EXECUTE)->toArray();
		    

        

        $fecha=$this->yearSelected."-".$this->mesSelected."-"."01";
        $fechaSaldoAnterior=date('d/m',strtotime('- 1 day', strtotime($fecha) ));

        $ingresoBancarioAnterior=$this->getSumIngresoBancarioAntesDe($fecha);
        $egresoBancarioAnterior=$this->getSumEgresoBancarioAntesDe($fecha);
        $saldoAnterior=$ingresoBancarioAnterior - $egresoBancarioAnterior;


        
        $response['rows'][0]['id']='HEADER_MB';
        $response['rows'][0]['cell']=['','',$fechaSaldoAnterior, '', '<b>SALDO DEL MES ANTERIOR</b>','', number_format($saldoAnterior,2,".",","), '', '', '', '', '',''];

        $monto=0;
        $saldo=$saldoAnterior;
        $i=1;

    		foreach ($rsMovimientosBancos as $moviemiento) {

          if($moviemiento['tipo']=='INGRESO'){
            $monto=$moviemiento['monto'];
            $saldo+=$monto;
          }else{
            $monto="-".$moviemiento['monto'];
            $saldo+=$monto;
          }


    			$response['rows'][$i]['id'] =$moviemiento['codigo'];
    			$response['rows'][$i]['cell']=[
                    $moviemiento['comentario'],
                    $moviemiento['adjunto'],
                    date('d/m',strtotime($moviemiento['fechapago'])),
                    $moviemiento['proveedor'],
                    $moviemiento['descripcion'],
                    number_format($monto,2,".",","),
                    number_format($saldo,2,".",","),
                    $moviemiento['nro_operacion'],
                    $moviemiento['nro_serie'],
                    $moviemiento['nro_doc'],
                    $moviemiento['tipo_doc'],
                    date('d/m',strtotime($moviemiento['fecha_emi']) ),
                    $moviemiento['banco'],
                ];
    			$i++;
    		}

      
        //retornar el estatus de la conciliacion del mes.
        $saldoBanco=$this->getSaldoBancoPorMes();

      
        if((string)$saldo==(string)$saldoBanco){
          $this->actualizarEstadoConciliacion(1);
          $response['conciliacion']=['status'=>true,'mensaje'=>''];
        }else{
          $this->actualizarEstadoConciliacion(0);
          $response['conciliacion']=['status'=>false,'mensaje'=>'La diferencia con el saldo de Bancos es de S/ '.number_format(($saldo - $saldoBanco), 2, ".", ",")];
        }


		  return $response;
	}


  private function getSumIngresoBancarioMensual($mes,$year){
    $adapter=$this->adapter;
    $sql = new Sql($this->adapter);

    $queryString="SELECT SUM(ipa.ipa_do_impban) AS monto
      FROM ingreso_parcial ipa
        INNER JOIN ingreso ing ON ing.ing_in_cod=ipa.ing_in_cod
        INNER JOIN unidad uni ON uni.uni_in_cod=ing.uni_in_cod
      WHERE ipa.ipa_in_cod=ipa.ipa_in_codimp
        AND uni.edi_in_cod={$this->edificioId}
        AND YEAR(ipa.ipa_da_fecpag)={$year}
        AND MONTH(ipa.ipa_da_fecpag)= {$mes}";
      $rsMovimientosBancos=$adapter->query($queryString, adapter::QUERY_MODE_EXECUTE)->toArray();

      return $rsMovimientosBancos[0]['monto'];
  }

  private function getSumIngresoBancarioAntesDe($fecha){
    $adapter=$this->adapter;
    $sql = new Sql($this->adapter);

    $queryString="SELECT SUM(ipa.ipa_do_impban) AS monto
      FROM ingreso_parcial ipa
        INNER JOIN ingreso ing ON ing.ing_in_cod=ipa.ing_in_cod
        INNER JOIN unidad uni ON uni.uni_in_cod=ing.uni_in_cod
      WHERE ipa.ipa_in_cod=ipa.ipa_in_codimp
        AND uni.edi_in_cod={$this->edificioId}
        AND ipa.ipa_da_fecpag < '{$fecha}' ";
      $rsMovimientosBancos=$adapter->query($queryString, adapter::QUERY_MODE_EXECUTE)->toArray();

      return $rsMovimientosBancos[0]['monto'];
  }


  private function getSumEgresoBancarioMensual($mes,$year){
    $adapter=$this->adapter;
    $sql = new Sql($this->adapter);

    $queryString="SELECT SUM(epa.epa_do_imp) AS monto
                      FROM egreso_parcial epa
                      INNER JOIN egreso egr ON egr.egr_in_cod=epa.egr_in_cod
                      WHERE egr.edi_in_cod = {$this->edificioId}
                      AND YEAR(epa.epa_da_fecpag) = {$year}
                      AND MONTH(epa.epa_da_fecpag)={$mes}";
      $rsMovimientosBancos=$adapter->query($queryString, adapter::QUERY_MODE_EXECUTE)->toArray();

      return $rsMovimientosBancos[0]['monto'];
  }

  private function getSumEgresoBancarioAntesDe($fecha){
    $adapter=$this->adapter;
    $sql = new Sql($this->adapter);

    $queryString="SELECT SUM(epa.epa_do_imp) AS monto
                      FROM egreso_parcial epa
                      INNER JOIN egreso egr ON egr.egr_in_cod=epa.egr_in_cod
                      WHERE egr.edi_in_cod = {$this->edificioId}
                      AND epa.epa_da_fecpag < '{$fecha}' ";
    $rsMovimientosBancos=$adapter->query($queryString, adapter::QUERY_MODE_EXECUTE)->toArray();

    return $rsMovimientosBancos[0]['monto'];
  }

  public function getSaldoBancoPorMes(){
     $adapter=$this->adapter;
     $sql = new Sql($this->adapter);

      $queryString="SELECT sab_do_sal AS saldo FROM saldo_bancario
        WHERE edi_in_cod={$this->edificioId}
        AND YEAR(sab_da_fecha)={$this->yearSelected}
        AND MONTH(sab_da_fecha)={$this->mesSelected}
        ORDER BY sab_da_fecha desc limit 1";

      $rsSaldoBanco=$adapter->query($queryString, adapter::QUERY_MODE_EXECUTE)->toArray();

      return !empty($rsSaldoBanco[0]['saldo'])? $rsSaldoBanco[0]['saldo']: 0 ;
  }

  private function actualizarEstadoConciliacion($estado){
      $adapter=$this->adapter;
      $sql = new Sql($this->adapter);

      $queryString="SELECT cs_in_cod AS id FROM conciliacion_saldo 
          WHERE edi_in_cod={$this->edificioId} AND year={$this->yearSelected} and mes = {$this->mesSelected} limit 1";
      $rsExisteConciliacion=$adapter->query($queryString, adapter::QUERY_MODE_EXECUTE)->toArray();

      if(!empty($rsExisteConciliacion[0]['id']) ){
          $codigo=$rsExisteConciliacion[0]['id'];
          
          $update = $sql->update()
          ->table('conciliacion_saldo')->set(['estado'=>$estado])
          ->where(['cs_in_cod'=>$codigo]);
          $sql->prepareStatementForSqlObject($update)->execute();
      }else{

        $dataConciliacionStatus =[
          'edi_in_cod'=> $this->edificioId,
          'mes'=> $this->mesSelected,
          'year'=> $this->yearSelected,
          'estado'=>$estado,
        ];

        $insert = $sql->insert('conciliacion_saldo')->values($dataConciliacionStatus);
        $sql->prepareStatementForSqlObject($insert)->execute();

      }
  }

}
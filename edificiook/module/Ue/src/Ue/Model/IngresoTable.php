<?php
/**
 * edificioOk (https://www.edificiook.com)
 * creado por: Meler Carranza, 07/09/2016.
 * ultima modificacion por: Meler Carranza
 * Fecha Modificacion: 107/09/2016.
 * Descripcion: 
 *
 * @autor     Fidel J. Thompson
 * @link      https://www.edificiook.com
 * @copyright Copyright (c) 2011-2016 KND S.A.C (http://www.knd.pe)
 * @license   http://www.edificiook.com/license/comercial Software Comercial
 */

namespace Ue\Model;
use Zend\Db\Sql\Sql;
use Zend\Db\Sql\Select;
use Zend\Db\TableGateway\AbstractTableGateway;
use Zend\Db\Adapter\Adapter;
use Zend\Db\ResultSet\ResultSet;
use Zend\Db\Sql\Expression;
use Zend\Json\Expr;


class IngresoTable extends AbstractTableGateway{

    private $edificioId=null;
    private $empresaId=null;
    private $yearSelected=null;
    private $tipoUnidad=null;
    private $nombreMes=array('Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre');

    public function __construct(Adapter $adapter)
    {
        $this->adapter=$adapter;
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
            $i=1;
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


    private function saveAuditoria($params)
    {   
        $adapter=$this->getAdapter();
        $sql=new Sql($adapter);
        $insert = $sql->insert('auditoria');
        $data = array(
            'usu_in_cod'=> $params['idUsuario'], //idusuario
            'edi_in_cod'=> $params['idEdificio'], //idedificio
            'aud_opcion'=> 'Conserjeria > Visitas', //ruta
            'aud_accion'=> $params['accion'], //accion
            'aud_fecha'=> date('Y-m-d H:i:s'), //fecha y hora
            'aud_ip'=> $_SERVER['REMOTE_ADDR'], //ip publica
            'aud_tabla'=> 'Visita', //tabla
            'aud_in_numra'=> $params['numRegistro'], //registro afectado
            'aud_info_adi'=> $_SERVER['HTTP_USER_AGENT'], //agente
            'aud_comnumra'=> $params['comNumRegistro'] //comentario del registro afectado
        );
        $insert->values($data);
        $sql->prepareStatementForSqlObject($insert)->execute()->count();
    }

}
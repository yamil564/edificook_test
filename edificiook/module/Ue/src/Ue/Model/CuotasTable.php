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

namespace Ue\Model;

use Zend\Db\Sql\Sql;
use Zend\Db\Sql\Select;
use Zend\Db\TableGateway\AbstractTableGateway;
use Zend\Db\Adapter\Adapter;
use Zend\Db\ResultSet\ResultSet;
use Zend\Db\Sql\Expression;

class CuotasTable extends AbstractTableGateway{

    private $idUsuario = null;
    private $idEdificio = null;
    private $nombreMes=array('Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre');

	public function __construct(Adapter $adapter)
	{
		$this->adapter=$adapter;
	}

    public function listarUnidadPorPropietario($idEdificio,$idUsuario)
    {
        $adapter=$this->getAdapter();
        $select = "SELECT uni_in_cod AS id, CONCAT(uni_vc_tip,' ',uni_vc_nom) AS unidadNombre FROM unidad WHERE edi_in_cod = $idEdificio AND uni_in_est = 1 AND uni_in_pad IS NULL AND (uni_in_pro = $idUsuario OR uni_in_pos=$idUsuario)";

        $response = $adapter->query($select,$adapter::QUERY_MODE_EXECUTE)->toArray();
        return $response;
    }

    public function cargarGrid($params)
    {   
        $this->idUsuario = $params['idUsuario'];
        $this->idEdificio = $params['idEdificio'];
        $year = ($params['year']!='')?$params['year']:date('Y');
        
        $idUnidad=null;
        
        if($params['idUnidad']!=''){
            $idUnidad=$params['idUnidad'];
        }else{
            $rsUnidades=$this->listarUnidadPorPropietario($this->idEdificio,$this->idUsuario);
            $idUnidad=isset($rsUnidades[0]['id'])? $rsUnidades[0]['id'] : null;
        }
        
        $adapter=$this->getAdapter();
        $sql=new Sql($adapter);

        $select=$sql->select()
            ->from(array('coi'=>'concepto_ingreso'))
            ->columns(
                array(
                    'idIngreso'=>'ing_in_cod',
                    'mes'=>new Expression('MONTH(ing.ing_da_fecemi)'),
                    'year'=>new Expression('YEAR(ing.ing_da_fecemi)'),
                    'totalEmision'=>new Expression('SUM(coi_do_subtot)')
                )
            )
            ->join(array('ing'=>'ingreso'),'ing.ing_in_cod=coi.ing_in_cod',array())
            ->where(array('ing.uni_in_cod'=>$idUnidad,'YEAR(ing.ing_da_fecemi)='.$year))
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
                $totalPagado=$this->getSumIngresoParcial($value['idIngreso']);
                $debe=$totalEmision - $totalPagado;

                $sumEmision+=$totalEmision;
                $sumPagado+=$totalPagado;
                $sumDebe+=$debe;

                $indiceMes=(intval($value['mes']) -1);
                $items[]=array(
                    'idIngreso'=>$value['idIngreso'],
                    'mes'=>$this->nombreMes[$indiceMes],
                    'numMes'=>$value['mes'],
                    'year'=>$value['year'],
                    'totalEmision'=>number_format($totalEmision,2,'.',','),
                    'totalPagado'=>number_format($totalPagado,2,'.',','),
                    'debe'=>number_format($debe,2,'.',','),
                    'btnDescargar'=>'<span onClick="cuotas.descargarEECC(this)"><u>Ver recibo</u> <i class="fa fa-refresh fa-spin hidden"></i></span>'
                );
            }
            $totales=array(
                'idIngreso'=>'null',
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

    public function detallesDePago($params){

        $idIngreso = $params['idIngreso'];
        $rowIngreso=$this->getRowIngreso($idIngreso);

        //el número del mes funciona como indice en el vector $this->nombreMes[], esto para optener el nombre del mes.
        $indiceMes=(intval(date('m',strtotime($rowIngreso['fechaEmi']))) -1);
        $rowIngreso['mes']=$this->nombreMes[$indiceMes];
        $rowIngreso['year']=date('Y',strtotime($rowIngreso['fechaEmi']));
        $rowIngreso['fechaEmi']=date('d-m-Y',strtotime($rowIngreso['fechaEmi']));
        $rowIngreso['fechaVence']=date('d-m-Y',strtotime($rowIngreso['fechaVence']));
        $rowIngreso['propietario']=$this->getNombreUsuario($rowIngreso['propietarioId']);
        $rowIngreso['residente']=$this->getNombreUsuario($rowIngreso['residenteId']);

        $totalEmision=$this->getSumConceptosDeIngreso($idIngreso);
        $totalPagado=$this->getSumIngresoParcial($idIngreso);
        $debe=$totalEmision - $totalPagado;

        $rowIngreso['totalMesEmision']=number_format($totalEmision,2,'.',',');
        $rowIngreso['totalMesPagado']=number_format($totalPagado,2,'.',',');
        $rowIngreso['debeMes']=number_format($debe,2,'.',',');

        $dataConcepto = $this->conceptosDeIngreso($idIngreso);
        $rowIngreso['dataConcepto'] = $dataConcepto['rows'];
        $rowIngreso['totalConcepto'] = $dataConcepto['total'];

        $dataInformacion = $this->getInformacion($idIngreso);
        $rowIngreso['dataDetalle'] = $dataInformacion['detalle'];

        return $rowIngreso;
    }

    private function getInformacion($idIngreso)
    {
        $adapter=$this->getAdapter();
        $sql=new Sql($adapter);

        $select=$sql->select()
            ->from('ingreso_parcial')
            ->columns(array(
                'serie'=>'ipa_in_nroser',
                'doc'=>'ipa_in_nrodoc',
                'tipoDocumento'=>'ipa_vc_tipdoc',
                'fechaEmision'=>'ipa_da_fecemi',
                'fechaVence'=>'ipa_da_fecven',
                'interes'=>'ipa_do_int',
                'importe'=>'ipa_do_imp',
                'banco'=>'ipa_vc_ban',
                'numeroOperacion'=>'ipa_vc_numope',
                'fechaPago'=>'ipa_da_fecpag'
            ))
            ->where(array('ing_in_cod'=>$idIngreso));
        $selectString=$sql->buildSqlString($select);
        $data = $adapter->query($selectString,$adapter::QUERY_MODE_EXECUTE)->toArray();

        return array(
            'detalle'=>$data
        );

    }

    private function conceptosDeIngreso($idIngreso) //$params
    { 
        //$ingresoId=$params['ingresoId'];
        $adapter=$this->getAdapter();
        $sql=new Sql($adapter);

        $select=$sql->select()
            ->from(array('coi'=>'concepto_ingreso'))
            ->columns(array('conceptoId'=>'con_in_cod','total'=>'coi_do_subtot','comentario'=>'coi_te_com'))
            ->join(array('con'=>'concepto'),'con.con_in_cod=coi.con_in_cod',array('concepto'=>'con_vc_des'))
            ->where(array('coi.ing_in_cod'=>$idIngreso));
        $selectString=$sql->buildSqlString($select);
        $rsConceptos=$adapter->query($selectString,$adapter::QUERY_MODE_EXECUTE)->toArray();

        $totalPagado=$this->getSumConceptosDeIngreso($idIngreso);
        return array(
            "rows"=>$rsConceptos,
            'total'=>array(
                'label'=>'Total Emisión',
                'SumTotal'=>$totalPagado
            )
        );
    }

    private function getRowIngreso($idIngreso){
        $sql=new Sql($this->adapter);
        $select=$sql->select()
            ->from(array('ing'=>'ingreso'))
            ->columns(
                array(
                    'fechaEmi'=>'ing_da_fecemi',
                    'fechaVence'=>'ing_da_fecven',
                    'serie'=>'ing_in_nroser',
                    'nroDoc'=>'ing_in_nrodoc',
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
            ->where(array('ing.ing_in_cod'=>$idIngreso));
        $statement=$sql->prepareStatementForSqlObject($select);
        $rowIngreso=$statement->execute()->current();
        return $rowIngreso;
    }

    private function getSumIngresoParcial($idIngreso){
        $sql=new Sql($this->adapter);
        $selectSumIP=$sql->select()
                        ->from('ingreso_parcial')
                        ->columns(array('totalPagado'=>new Expression('SUM(ipa_do_imp)')))
                        ->where(array('ing_in_cod'=>$idIngreso));
                        $statement=$sql->prepareStatementForSqlObject($selectSumIP);
        $rowSumIngresoParcial=$statement->execute()->current();
        $totalPagado=$rowSumIngresoParcial['totalPagado'];
        return $totalPagado;
    }

    private function getNombreUsuario($usuarioId){
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

    private function getSumConceptosDeIngreso($idIngreso){
        $sql=new Sql($this->adapter);

        $selectSumCI=$sql->select()
            ->from(array('coi'=>'concepto_ingreso'))
            ->columns(array('total'=>new Expression('SUM(coi.coi_do_subtot)')))
            ->where(array('coi.ing_in_cod'=>$idIngreso));
        
        $statement=$sql->prepareStatementForSqlObject($selectSumCI);
        $rowSumConceptoIngreso=$statement->execute()->current();

        return $rowSumConceptoIngreso['total'];
    }



}
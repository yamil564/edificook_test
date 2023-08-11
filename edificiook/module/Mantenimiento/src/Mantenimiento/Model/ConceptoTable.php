<?php

/**
 * edificioOk (https://www.edificiook.com)
 * creado por: Meler Carranza, 11/03/2016.
 * ultima modificacion por: Jhnon Gómez, Meler Carranza
 * Fecha Modificacion: 21/04/2016, 02-06-2016
 * Descripcion: 
 *
 * @autor     Fidel J. Thompson 
 * @link      https://www.edificiook.com
 * @copyright Copyright (c) 2011-2016 KND S.A.C (http://www.knd.pe)
 * @license   http://www.edificiook.com/license/comercial Software Comercial
 * 
 */

namespace Mantenimiento\Model;

use Zend\Db\Sql\Sql;
use Zend\Db\Sql\Select;
use Zend\Db\TableGateway\AbstractTableGateway;
use Zend\Db\Adapter\Adapter;
use Zend\Db\ResultSet\ResultSet;
use Zend\Db\Sql\Expression;

class ConceptoTable extends AbstractTableGateway
{

	public function __construct(Adapter $adapter)
	{
		$this->adapter=$adapter;
	}

    public function listarGrupoConcepto()
    {
        $adapter=$this->getAdapter();
        $sql=new Sql($this->adapter);
        $select=$sql->select()
            ->from('concepto_grupo')
            ->columns(array(
                'codigo'=>'cog_in_cod',
                'descripcion'=>'cog_vc_des'
            ))
            ->where(array(
                'cog_in_est!=0'
            ));
        $sqlString=$sql->buildSqlString($select);
        $dataConcepto=$adapter->query($sqlString,$adapter::QUERY_MODE_EXECUTE)->toArray();
        return $dataConcepto;
    }

    public function read($params)
    {
        $adapter=$this->getAdapter();
        $sql=new Sql($this->adapter);
        $select=$sql->select()
            ->from('concepto')
            ->columns(array(
                'codigo'=>'con_in_cod',
                'codigoGrupo'=>'cog_in_cod',
                'codigoEmpresa'=>'emp_in_cod',
                'descripcion'=>'con_vc_des',
                'tipo'=>'con_vc_tip',
                'estado'=>'con_in_est',
            ))
            ->where(array(
                'con_in_cod'=>$params['id']
            ));
        $sqlString=$sql->buildSqlString($select);
        $dataConcepto=$adapter->query($sqlString,$adapter::QUERY_MODE_EXECUTE)->current();
        return $dataConcepto;
    }

    public function add($params)
    {
        $adapter=$this->getAdapter();
        $sql=new Sql($adapter);
        
        $rptaExisteConcepto=$this->existConcepto($params['descripcion'],'');

        if($rptaExisteConcepto==='existe'){
            $responce=array(
                'message'=>'existeConcepto',
                'body'=>'El concepto existe'
            );
        }else{
            $insert = $sql->insert('concepto');
            $data = array(
                'cog_in_cod'=> $params['grupo'],
                'emp_in_cod'=> $params['codEmpresa'],
                'con_vc_des'=> $params['descripcion'],
                'con_vc_tip'=> $params['tipo'],
                'con_in_est'=> 1
            );
            $insert->values($data);
            $lastId=$sql->prepareStatementForSqlObject($insert)->execute()->getGeneratedValue();
            $responce=array();
            if($lastId>0){
                //auditoria
                $params['numRegistro']=$lastId;
                $params['accion']='Guardar';
                $this->saveAuditoria($params);
                //fin auditoria
                $responce=array(
                    'message'=>'success',
                    'tipo'=>'informativo',
                    'body'=>'El Concepto se registro correctamente...'
                );
            }else{
                $responce=array(
                    'message'=>'error',
                    'tipo'=>'error',
                    'body'=>'Ocurrió un error desconocido en el servidor'
                );
            }
        }
        return $responce;
    }

    private function existConcepto($descripcion, $idConcepto)
    {
        $descripcion=strtoupper($descripcion);
        $adapter=$this->getAdapter();
        $sql=new Sql($this->adapter);
        if($idConcepto!=''){
            $select=$sql->select()
            ->from('concepto')
            ->where(array(
                'con_in_cod!='.$idConcepto,
                'con_in_est!=0',
                'con_vc_des'=>$descripcion
            ));
        }else{
            $select=$sql->select()
            ->from('concepto')
            ->where(array(
                'con_in_est!=0',
                'con_vc_des'=>$descripcion
            ));
        }
        $sqlString=$sql->buildSqlString($select);
        $dataConcepto=$adapter->query($sqlString,$adapter::QUERY_MODE_EXECUTE)->toArray();
        if(count($dataConcepto)>0){
            return 'existe';
        }else{
            return 'noexiste';
        }
    }

    public function updatee($params)
    {
        $adapter=$this->getAdapter();
        $sql=new Sql($adapter);

        $rptaExisteConcepto=$this->existConcepto($params['descripcion'],$params['id']);

        if($rptaExisteConcepto==='existe'){
            $responce=array(
                'message'=>'existeConcepto',
                'body'=>'El concepto existe'
            );
        }else{
            $data = array(
                'cog_in_cod'=> $params['grupo'],
                'con_vc_des'=> $params['descripcion'],
                'con_vc_tip'=> $params['tipo']
            );
            $update=$sql->update()
                ->table('concepto')
                ->set($data)
                ->where(array('con_in_cod'=>$params['id']));
            $rpta=$sql->prepareStatementForSqlObject($update)->execute()->count();
            //auditoria
            $params['numRegistro']=$params['id'];
            $params['accion']='Editar';
            $this->saveAuditoria($params);
            //fin auditoria
            if($rpta===1){
                $responce=array(
                    'message'=>'success',
                    'tipo'=>'informativo',
                    'body'=>'El Concepto se actualizo correctamente...'
                );
            }else{
                $responce=array(
                    'message'=>'success',
                    'tipo'=>'advertencia',
                    'body'=>'No se a actualizado ningún campo...'
                );
            }
        }

        return $responce;
    }

    public function deletee($params)
    {
        $adapter=$this->getAdapter();
        $sql=new Sql($adapter);
        $data = array(
            'con_in_est'=> 0
        );
        $update=$sql->update()
            ->table('concepto')
            ->set($data)
            ->where(array('con_in_cod'=>$params['id']));
        $rpta=$sql->prepareStatementForSqlObject($update)->execute()->count();
        if($rpta===1){
            //auditoria
            $params['numRegistro']=$params['id'];
            $params['accion']='Eliminar';
            $this->saveAuditoria($params);
            //fin auditoria
            $responce=array(
                'message'=>'success',
                'tipo'=>'informativo',
                'body'=>'El Concepto se eliminó correctamente...'
            );
        }else{
            $responce=array(
                'message'=>'error',
                'tipo'=>'error',
                'body'=>'Ocurrió un error desconocido en el servidor'
            );
        }
        return $responce;
    }

	public function loadGrid($params)
    {
        $adapter=$this->getAdapter();
        /* Parametros del jqGrid. */
        $codEmpresa = $params['codEmpresa'];
        $page   = $params['page'];
        $limit  = $params['rows'];
        $sidx   = $params['sidx'];
        $sord   = $params['sord'];
        if(!$sidx) $sidx = 1;
        //filter
        $filter = $this->filtrar($params['_search'],$params['filters']);
        //query
        $queryTotalConcepto = "SELECT COUNT(*) AS count FROM concepto where emp_in_cod IN (0,$codEmpresa)";
        $dataTotalConcepto = $adapter->query($queryTotalConcepto,$adapter::QUERY_MODE_EXECUTE)->current();
        //count
        $count = $dataTotalConcepto['count'];

        if( $count > 0 ) {
            $total_pages = ceil($count/$limit);
        }else {
            $total_pages = 0;
        }

        if ($page > $total_pages) $page=$total_pages;
        $start = $limit * $page - $limit;

        if($start <0){
            $start = 1;
            $total_pages = 1;
            $page = 1;
            $count = 1;
        }
        
        $queryConcepto = "SELECT emp_in_cod as codEmpresa,con_in_cod,con_vc_des,cog_vc_des,con_vc_tip,con_in_est FROM concepto c,concepto_grupo cg WHERE c.cog_in_cod = cg.cog_in_cod AND emp_in_cod IN (0,$codEmpresa) AND con_in_est!='0' $filter ORDER BY $sidx $sord LIMIT $start , $limit";
        $dataConcepto = $adapter->query($queryConcepto,$adapter::QUERY_MODE_EXECUTE)->toArray();

        $responce=array();
        $responce['page'] = $page;
        $responce['total'] = $total_pages;
        $responce['records'] = $count;

        $i=0;

        foreach($dataConcepto as $row)
        { 
            $checkRow='';
            if($row['codEmpresa']!=0){
                $checkRow ="<center><label class='position-relative tooltipmsg'><input type='checkbox' id='chkCol". $row['con_in_cod'] . "' name='chkCol". $row['con_in_cod'] . "' class='ace' onclick='Concepto.checkBoxSelected(".$row['con_in_cod'].");'/><span class='lbl'></span></label></center>";
            }
            $responce['rows'][$i]['id'] = $row['con_in_cod'];
            $responce['rows'][$i]['cell'] = array(
            	$checkRow,
            	$row['con_vc_des'],
            	$row['con_vc_tip'],
                $row['cog_vc_des']
            );
            $i++;
        }

        return $responce;

    }

    private function filtrar($search,$filters)
    {
        $filter = "";
        $search_ind = $search;
        if($search_ind == 'true'){
            $filter_cad = json_decode(stripslashes($filters));
            $filter_opc = $filter_cad->{'groupOp'};
            $filter_rul = $filter_cad->{'rules'};
            $cont = 0;
            foreach($filter_rul as $key => $value){

                $fie = $filter_rul[$key]->{'field'};
                $opt = $this->search($filter_rul[$key]->{'op'});
                $dat = $filter_rul[$key]->{'data'};
                if($fie=="uni_vc_tip"){
                    $fie = "CONCAT(uni_vc_tip,' ',uni_vc_nom)";
                }
                if($cont==0){
                   if($opt=="REGEXP2"){
                        $opt = "LIKE";
                        $filter .= " AND ($fie $opt '$dat%'";
                    }else{
                        if($opt=="REGEXP3"){
                            $opt = "LIKE";
                            $filter .= " AND ($fie $opt '%$dat'";
                        }else{
                            if($opt=="NOT REGEXP2"){
                                $opt = "NOT LIKE";
                                $filter .= " AND ($fie $opt '$dat%'";
                            }else{
                                if($opt=="NOT REGEXP3"){
                                    $opt = "NOT LIKE";
                                    $filter .= " AND ($fie $opt '%$dat'";
                                }else{
                                    if($opt=='LIKE' || $opt=='NOT LIKE'){
                                        $filter .= " AND ($fie $opt '%$dat%'";
                                    }else{
                                        $filter .= " AND ($fie $opt '$dat'";
                                    }
                                }
                            }
                        }
                    }
                    $cont++;
                }else{
                    if($opt=="REGEXP2"){
                        $opt = "LIKE";
                        $filter .= " $filter_opc $fie $opt '$dat%'";
                    }else{
                        if($opt=="REGEXP3"){
                            $opt = "LIKE";
                            $filter .= " $filter_opc $fie $opt '%$dat'";
                        }else{
                            if($opt=="NOT REGEXP2"){
                                $opt = "NOT LIKE";
                                $filter .= " $filter_opc $fie $opt '$dat%'";
                            }else{
                                if($opt=="NOT REGEXP3"){
                                    $opt = "NOT LIKE";
                                    $filter .= " $filter_opc $fie $opt '%$dat'";
                                }else{
                                    if($opt=='LIKE' || $opt=='NOT LIKE'){
                                        $filter .= " $filter_opc $fie $opt '%$dat%'";
                                    }else{
                                        $filter .= " $filter_opc $fie $opt '$dat'";
                                    }
                                }
                            }
                        }
                    }
                }
            }
            $filter .= ")";
        }

        return $filter;

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
            'usu_in_cod'=> $params['codUsuario'], //idusuario
            'edi_in_cod'=> $params['codEdificio'], //idedificio
            'aud_opcion'=> 'Mantenimiento > Conceptos', //ruta
            'aud_accion'=> $params['accion'], //accion
            'aud_fecha'=> date('Y-m-d H:i:s'), //fecha y hora
            'aud_ip'=> $_SERVER['REMOTE_ADDR'], //ip publica
            'aud_tabla'=> 'Concepto', //tabla
            'aud_in_numra'=> $params['numRegistro'], //registro afectado
            'aud_info_adi'=> $_SERVER['HTTP_USER_AGENT'], //agente
            'aud_comnumra'=> '' //comentario del registro afectado
        );
        $insert->values($data);
        $sql->prepareStatementForSqlObject($insert)->execute()->count();
    }

}
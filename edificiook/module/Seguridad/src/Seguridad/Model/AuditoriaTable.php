<?php

/**
* edificioOk (https://www.edificiook.com)
* creado por: Jhon Gómez, 11/03/2016.
* ultima modificacion por: Jhon Gómez
* Fecha Modificacion: 11/04/2016.
* Descripcion: Modelo Edificio
*
* @autor     Fidel J. Thompson
* @link      https://www.edificiook.com
* @copyright Copyright (c) 2011-2016 KND S.A.C (http://www.knd.pe)
* @license   http://www.edificiook.com/license/comercial Software Comercial
* 
*/

namespace Seguridad\Model;

use Zend\Db\Sql\Sql;
use Zend\Db\Sql\Select;
use Zend\Db\TableGateway\AbstractTableGateway;
use Zend\Db\Adapter\Adapter;
use Zend\Db\ResultSet\ResultSet;
use Zend\Db\Sql\Expression;

class AuditoriaTable extends AbstractTableGateway{

    public function __construct(Adapter $adapter)
    {
        $this->adapter=$adapter;
    }

    public function listar($params)
    { 
		$adapter=$this->getAdapter();
        $sql=new Sql($adapter);
        
		/* Parametros del jqGrid. */
		$page = $params['page']; 
		$limit = $params['rows']; 
		$sidx = $params['sidx']; 
		$sord = $params['sord'];

		if(!$sidx) $sidx = 1;

		$filter = $this->buscarAuditoria($params['_search'], $params['filters']);
		$queryAuditoria="SELECT COUNT(*) AS count FROM auditoria WHERE edi_in_cod='".$params['idEdificio']."'";
		$row = $adapter->query($queryAuditoria,$adapter::QUERY_MODE_EXECUTE)->current();
		$count = $row['count'];

		if( $count > 0 && $limit > 0) { 
		    $total_pages = ceil($count/$limit); 
		}else{ 
		    $total_pages = 0;
		}
		if ($page > $total_pages) $page=$total_pages;
		$start = $limit*$page - $limit;
		if($start <0) $start = 0;

		$queryAuditoria='';
		if($params['tipoPerfil']==='ur'){
			$queryAuditoria="SELECT * FROM auditoria WHERE edi_in_cod='".$params['idEdificio']."' $filter ORDER BY $sidx $sord LIMIT $start , $limit";
		}else{
			$queryAuditoria="SELECT * FROM auditoria au INNER JOIN usuario usu ON usu.usu_in_cod=au.usu_in_cod WHERE edi_in_cod='".$params['idEdificio']."' AND usu.usu_ch_tipoperfil!='ur' $filter ORDER BY $sidx $sord LIMIT $start , $limit";
		}
		$data = $adapter->query($queryAuditoria,$adapter::QUERY_MODE_EXECUTE)->toArray();

		$response=array();
        $response['page'] = $page;
        $response['total'] = $total_pages;
        $response['records'] = $count;
        $i=0;

		foreach($data as $row){ 
		    $response['rows'][$i]['id'] = $row['aud_in_cod'];
		    $response['rows'][$i]['cell'] = array(
		    	$this->getUsuarioDatos($row['usu_in_cod']),
		    	$row['aud_opcion'],
		    	$row['aud_accion'],
		    	$row['aud_fecha'],
		    	$row['aud_ip'],
		    	$row['aud_tabla'],
		    	$row['aud_info_adi']
		    );
		    $i++;
		}

		return $response;

    }

    private function getUsuarioDatos($idUsuario)
    {
        $adapter=$this->getAdapter();
        $sql=new Sql($adapter);
        $select=$sql->select()
            ->from('usuario')
            ->columns(array(
            	'nombre'=>'usu_vc_nom',
            	'apellido'=>'usu_vc_ape'
            ))
            ->where(array('usu_in_cod'=>$idUsuario));
        $selectString=$sql->buildSqlString($select);
        $dataUsuario=$adapter->query($selectString,$adapter::QUERY_MODE_EXECUTE)->current();
        return $dataUsuario['nombre']." ".$dataUsuario['apellido'];
    }

    private function buscarAuditoria($search,$filtro)
    {
    	/* En caso estemos realizando una busqueda o un filtro. */
		$filter = "";
		$search_ind = $search;
		if($search_ind == 'true'){
		    $filter_cad = json_decode($filtro);
		    $filter_opc = $filter_cad->{'groupOp'};
		    $filter_rul = $filter_cad->{'rules'};
		    $cont = 0;
		    foreach($filter_rul as $key => $value){
		        $fie = $filter_rul[$key]->{'field'};
		        if($fie=='usu_vc_nom'){
		        	$fie="CONCAT(usu_vc_nom,' ',usu_vc_ape)";
		        }
		        $opt = $this->search($filter_rul[$key]->{'op'});
		        $dat = $filter_rul[$key]->{'data'};        
		        if($cont==0){
		             if($opt=="REGEXP2"){
		                $opt = "LIKE";
		                $filter .= " AND ($fie $opt '%$dat%'";
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
		                $filter .= " $filter_opc $fie $opt '%$dat%'";
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
			case "bn" : $oper = "NOT REGEXP2"; break;
			case "in" : $oper = "IN"; break;
			case "ni" : $oper = "NOT IN"; break;
			case "ew" : $oper = "REGEXP3"; break;
			case "en" : $oper = "NOT REGEXP3"; break;
			case "cn" : $oper = "LIKE"; break;
			case "nc" : $oper = "NOT LIKE"; break;
		}
		return $oper;
	}

}
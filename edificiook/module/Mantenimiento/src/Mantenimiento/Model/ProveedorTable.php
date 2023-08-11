<?php
/**
 * edificioOk (https://www.edificiook.com)
 * creado por: Meler Carranza, 25/08/2016.
 * ultima modificación por: Meler Carranza
 * Fecha Modificacion: 17/08/2016
 * Descripcion: Mantenimiento de usuarios
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
use Zend\Db\Sql\Expression;

class ProveedorTable extends AbstractTableGateway{

    private $fechaSistema=null;
	public function __construct(Adapter $adapter)
	{  
        $this->fechaSistema=date('Y-m-d H:i:s');
		$this->adapter=$adapter;
	}

	public function getProveedoresByEmpresa($params) {
        $page=isset($params['page'])?$params['page'] :null;
        $limit=isset($params['rows'])?$params['rows']:null;
        $sidx=isset($params['sidx'])?$params['sidx']:'id';
        $sord=isset($params['sord'])?$params['sord']:'desc';
        if(!$sidx) $sidx=1;

        $filter = "";
        $search_grid = $params['_search'];

        $getFidelDb=array(
            'rz'=>'prv_vc_razsoc',
            'documento'=>'prv_ch_ruc',
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

        $selectString="SELECT count(*) as count FROM `proveedor` AS `prv`
                    WHERE `prv`.`emp_in_cod` in(0,".$params['empresaId'].") ".$filter;
        $count=$adapter->query($selectString,$adapter::QUERY_MODE_EXECUTE)->current()['count'];
        
        if($count > 0 && $limit > 0) {
            $total_pages = ceil($count / $limit);
        }else{
            $total_pages = 0;
        }


        if($page > $total_pages) $page = $total_pages;
        $start = $limit * $page - $limit;
        if($start < 0) $start = 0;


        $selectProveedor=$sql->select()
        ->from(array('prv'=>'proveedor'))
        ->columns(array('id'=>'prv_in_cod',
           'razonsocial'=>'prv_vc_razsoc',
           'ruc'=>'prv_ch_ruc',
           'empresaId'=>'emp_in_cod',
        ))->where(array('emp_in_cod'=>array(0,$params['empresaId'])));


        $selectString=$sql->buildSqlString($selectProveedor);
        $selectString.=" ".$filter;
        $selectString.=" ORDER BY $sidx $sord  limit $start , $limit ";
        $rsProveedores=$adapter->query($selectString,$adapter::QUERY_MODE_EXECUTE)->toArray();

        if(!empty($rsProveedores)){
            $i=0;
            $response=array();
            $response['page'] = $page;
            $response['total'] = $total_pages;
            $response['records'] = $count;

            foreach ($rsProveedores as $key => $value){
                $idProveedor=$value['id'];
                if($value['empresaId']==0){
                    $idProveedor=0;
                }

                $response['rows'][$i]['id']=$i;
                $response['rows'][$i]['cell']=array(
                    $idProveedor,
                    $value['razonsocial'],
                    $value['ruc'],
                );
                $i++;
            }
        }
        
        return $response;
	}

    public function getProveedorById($params){
        $adapter=$this->getAdapter();
        $sql=new Sql($adapter);

        $selectUser=$sql->select()
        ->from(array('prv'=>'proveedor'))
        ->columns(array('id'=>'prv_in_cod',
           'razonsocial'=>'prv_vc_razsoc',
           'ruc'=>'prv_ch_ruc',
           'empresaId'=>'emp_in_cod',
        ))->where(array('prv_in_cod'=>$params['id']));

        $selectString=$sql->buildSqlString($selectUser);
        $rsUsuario=$adapter->query($selectString,$adapter::QUERY_MODE_EXECUTE)->current();

        return $rsUsuario;
    }

    public function insertProveedor($params){
        $adapter=$this->getAdapter();
        $sql=new Sql($adapter);

        //seleccionar la empresa del edificio actual.


        if($this->existeRazonSocial($params, null)){
                return array('tipo'=>'error','mensaje'=>'Ya existe un proveedor con el nombre: '.$params['textRazonSocial']);
        }

        if (strlen($params['textRuc']) > 0){
            if(strlen($params['textRuc'])==8 || strlen($params['textRuc'])==11) {
                if($this->existeRUC($params,null)){
                    return array('tipo'=>'error','mensaje'=>'Ya existe un proveedor registrado con documento N°:'.$params['textRuc']);
                }
            }else {
                return array('tipo'=>'error','mensaje'=>'El número de documento deberá tener 8 ú 11 dígitos.');
            }
        }
        

        $empresaId=$params['empresaId'];


        $insert = $sql->insert('proveedor');
        $data = array(
            'emp_in_cod'=>$empresaId,
            'prv_vc_razsoc'=>strtoupper($params['textRazonSocial']),
            'prv_ch_ruc'=>$params['textRuc'],
        );

        $insert->values($data);
        $result=$sql->prepareStatementForSqlObject($insert)->execute()->count();

        if($result<=0){
            return array('tipo'=>'error','mensaje'=>'Error al intentar crear el proveedor.');
        }

        return array('tipo'=>'informativo','mensaje'=>'Proveedor creado con éxito.');  
    }




    public function updateProveedor($params){
        
        $adapter=$this->getAdapter();
        $sql=new Sql($adapter);

        $params['textRuc']=trim($params['textRuc']);
        $params['textRazonSocial']=trim($params['textRazonSocial']);


        if($this->existeRazonSocial($params, null)){
                return array('tipo'=>'error','mensaje'=>'Ya existe un proveedor con el nombre: '.$params['textRazonSocial']);
        }


        if (strlen($params['textRuc']) > 0){
            if(strlen($params['textRuc'])==8 || strlen($params['textRuc'])==11 ) {
                if($this->existeRUC($params,$params['id'])){
                    return array('tipo'=>'error','mensaje'=>'Ya existe un proveedor registrado con el documento N°:'.$params['textRuc']);
                }
            }else{
                return array('tipo'=>'error','mensaje'=>'El número de documento deberá tener 8 ú 11 dígitos.');
            }
        }

        $data = array(
            'prv_vc_razsoc'=>strtoupper($params['textRazonSocial']),
            'prv_ch_ruc'=>$params['textRuc'],
        );



        $updateProveedor=$sql->update()->table('proveedor')->set($data)->where( array('prv_in_cod'=>$params['id']));
        $result=$sql->prepareStatementForSqlObject($updateProveedor)->execute()->count();

        if($result<=0){
            return array('tipo'=>'advertencia','mensaje'=>'El registro no sufrió ningún cambio.');
        }

        return array('tipo'=>'informativo','mensaje'=>'Datos del proveedor actualizado con éxito.');
    }


    public function deleteProveedor($params){
        
        $adapter=$this->getAdapter();
        $sql=new Sql($adapter);


        $selectString="SELECT  count(*) as total from egreso WHERE prv_in_cod=".$params['id'];
        $rsValidar=$adapter->query($selectString,$adapter::QUERY_MODE_EXECUTE)->current();
        
        if( $rsValidar['total']>0){
            return array('tipo'=>'error','mensaje'=>'Imposible eliminar el proveedor seleccionado ya se encuentra en uso');
        }

        $delete=$sql->delete('proveedor')->where(array('prv_in_cod'=>$params['id']));
        $statement=$sql->prepareStatementForSqlObject($delete);
        $result=$statement->execute()->count();

        if($result<=0){
            return array('tipo'=>'error','mensaje'=>'Error al intentar eliminar el proveedor seleccionado.');
        }

        return array('tipo'=>'informativo','mensaje'=>'Proveedor eliminado con éxito.');
    }

   

    private function existeRUC($params,$id){
        $adapter=$this->getAdapter();
        $sql=new Sql($adapter);

        if($id==NULL){

            $selectString="SELECT  count(*) as total from proveedor WHERE prv_ch_ruc LIKE '".$params['textRuc']."' AND emp_in_cod in(0,".$params['empresaId']. ")";
        }else{
            $selectString="SELECT  count(*) as total from proveedor WHERE prv_ch_ruc LIKE '".$params['textRuc']."' AND emp_in_cod in(0,".$params['empresaId']. ") AND prv_in_cod!=".$id;
        }

        $rsValidar=$adapter->query($selectString,$adapter::QUERY_MODE_EXECUTE)->current();
        
        if( $rsValidar['total']>0){
            return true;
        }

        return false;
    }

    private function existeRazonSocial($params,$id){
        $adapter=$this->getAdapter();
        $sql=new Sql($adapter);

        /*
            Validamos la disponiblidad del usuario de manera general 
            (sin importar al edificio que pertenece ni estado en el que se encuentre [activo, eliminado].
        */ 

        if($id==NULL){
            $selectString="SELECT  count(*) as total from proveedor WHERE prv_vc_razsoc LIKE '".$params['textRazonSocial']."' AND emp_in_cod in(0,".$params['empresaId']. ")";
        }else{
            $selectString="SELECT  count(*) as total from proveedor WHERE prv_vc_razsoc LIKE '".$params['textRazonSocial']."' AND emp_in_cod in(0,".$params['empresaId']. ")
                AND  prv_in_cod!=".$id;
        }
        
        $rsValidar=$adapter->query($selectString,$adapter::QUERY_MODE_EXECUTE)->current();
        
        if($rsValidar['total']>0){
            return true;
        }

        return false;
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
}

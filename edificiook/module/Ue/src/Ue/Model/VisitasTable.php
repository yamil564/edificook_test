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

class VisitasTable extends AbstractTableGateway{

    public function __construct(Adapter $adapter)
    {
        $this->adapter=$adapter;
    }

    public function listarVisitas($params, $idEdificio)
    {

        $adapter=$this->getAdapter();

        /* Parametros del jqGrid. */
        $page   = $params['page'];
        $limit  = $params['rows'];
        $sidx   = $params['sidx'];
        $sord   = $params['sord'];
        if(!$sidx) $sidx = 1;

        $filter = $this->filtrar($params['_search'],$params['filters']);

        //echo $filter;
        
        $queryNovedadUnidad = "SELECT COUNT(*) AS count FROM visita n, unidad ui WHERE n.uni_in_cod = ui.uni_in_cod AND n.edi_in_cod='$idEdificio' $filter";
        $Resp = $adapter->query($queryNovedadUnidad,$adapter::QUERY_MODE_EXECUTE)->current();

        $count = $Resp['count'];

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

        $queryNovedad = "SELECT ui.uni_vc_tip,ui.uni_vc_nom, n.vis_in_cod, DATE_FORMAT(n.vis_da_focu, '%d/%m/%Y') AS vis_focu, n.vis_ti_hocu, DATE_FORMAT(n.vis_da_fing,'%d/%m/%Y') AS vis_fing, n.vis_ti_hing, n.vis_vc_imp, n.vis_vc_asu, n.vis_vc_tip, n.vis_vc_emp FROM visita n, unidad ui WHERE n.uni_in_cod = ui.uni_in_cod AND n.edi_in_cod='".$idEdificio."' $filter ORDER BY $sidx $sord LIMIT $start , $limit";
        $result = $adapter->query($queryNovedad,$adapter::QUERY_MODE_EXECUTE)->toArray();

        $responce=array();
        $responce['page'] = $page;
        $responce['total'] = $total_pages;
        $responce['records'] = $count;

        $i=0;

        foreach($result as $row){
            $responce['rows'][$i]['id'] = $row['vis_in_cod'];
            $responce['rows'][$i]['cell'] = array('',$row['vis_focu'], $row['vis_ti_hocu'], $row['vis_fing'], $row['vis_ti_hing'], $row['vis_vc_imp'], $row['vis_vc_asu'],$row['uni_vc_tip'].' '.$row['uni_vc_nom'],$row['vis_vc_tip'],$row['vis_vc_emp']);
            $i++;
        }

        return $responce;


    }

    private function filtrar($search,$filters)
    {

        /* En caso estemos realizando una busqueda o un filtro. */
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

    public function listarUnidad($idEdificio)
    {
        $adapter=$this->getAdapter();
        $queryUnidad = "SELECT CONCAT(ui.uni_vc_tip,' ',ui.uni_vc_nom) AS descrip, ui.uni_in_cod FROM unidad ui WHERE ui.edi_in_cod='$idEdificio'";
        $data = $adapter->query($queryUnidad,$adapter::QUERY_MODE_EXECUTE)->toArray();
        return $data;
    }

    public function mostrarPropietarioResidente($idUnidad)
    { 

        $adapter=$this->getAdapter();
        
        $queryUnidad = "SELECT * FROM unidad WHERE uni_in_cod='".$idUnidad."'";
        $Rowpuni = $adapter->query($queryUnidad,$adapter::QUERY_MODE_EXECUTE)->current();

        $queryUnidad = "SELECT usu_vc_nom, usu_vc_ape, usu_ch_tip FROM usuario WHERE usu_in_cod='".$Rowpuni['uni_in_pro']."'";
        $res = $adapter->query($queryUnidad,$adapter::QUERY_MODE_EXECUTE)->current();

        $queryUnidad = "SELECT usu_vc_nom, usu_vc_ape, usu_ch_tip FROM usuario WHERE usu_in_cod='".$Rowpuni['uni_in_pos']."'";
        $res2 = $adapter->query($queryUnidad,$adapter::QUERY_MODE_EXECUTE)->current();

        $cad ='';

        if($res['usu_ch_tip']=='PN'){
            $cad .=$res['usu_vc_ape'].' '.$res['usu_vc_nom'].'-:'.$Rowpuni['uni_in_pro'];
        }else{
            $cad .=$res['usu_vc_ape'].'-:'.$Rowpuni['uni_in_pro'];
        }

        if($res2['usu_ch_tip']=='PN'){
            $cad .='::'.$res2['usu_vc_ape'].' '.$res2['usu_vc_nom'].'-:'.$Rowpuni['uni_in_pos'];
        }else{
            $cad .='::'.$res2['usu_vc_ape'].'-:'.$Rowpuni['uni_in_pos'];
        }

        return $cad;
    }

    private function getInfoUsuario()
    {

    }

    public function saveVisita($params)
    {
        $idEdificio = $params['idEdificio'];
        $idUsuario = $params['idUsuario'];
        $unidad = $params['selUnidad'];
        $propietario = $params['txtPropietario'];
        $codPropietario = $params['txtCodPropietario'];
        $residente = $params['txtResidente'];
        $codResidente = $params['txtCodResidente'];
        $tipo = $params['selTipo'];
        $importancia = $params['selImportancia'];
        $asunto = $params['txtAsunto'];
        $fecha = $params['txtFecha'];
        $fecha = explode("/", $fecha);
        $hora = $params['txtHora'];
        $empresa = $params['txtEmpresa'];
        $ocurrencia = $params['txtOcurrencia'];

        $adapter=$this->getAdapter();
        $sql=new Sql($adapter);

        $insert = $sql->insert('visita');
        $data = array(
            'edi_in_cod'=> $idEdificio,
            'usu_in_cod'=> $idUsuario,
            'uni_in_cod'=> $unidad,
            'vis_ch_usu'=> $codPropietario,
            'vis_ch_pos'=> $codResidente,
            'vis_da_focu'=> $fecha[2]."-".$fecha[1]."-".$fecha[0],
            'vis_ti_hocu'=> $hora,
            'vis_da_fing'=> date('Y/m/d'),
            'vis_ti_hing'=> date("H:i:s"),
            'vis_vc_imp'=> $importancia,
            'vis_vc_asu'=> $asunto,
            'vis_te_ocu'=> $ocurrencia,
            'vis_vc_tip'=> $tipo,
            'vis_vc_emp'=> $empresa,
        );
        $insert->values($data);
        $lastIdVisita = $sql->prepareStatementForSqlObject($insert)->execute()->getGeneratedValue();
        if($lastIdVisita>0){
            /////////save auditoria////////
            $params['numRegistro']=$lastIdVisita;
            $params['accion']='Guardar';
            $this->saveAuditoria($params);
            ///////////////////////////////
            return array('message'=>'success','cuerpo'=>'Registro insertado correctamente...');
        }else{
            return array('message'=>'error','cuerpo'=>'Ocurrio un problema en el servidor, por favor intentelo de nuevo.');
        }

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
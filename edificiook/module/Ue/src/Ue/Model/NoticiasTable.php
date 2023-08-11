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

class NoticiasTable extends AbstractTableGateway{

    public function __construct(Adapter $adapter)
    {
        $this->adapter=$adapter;
    }

    public function noticias($params)
    {
        $idEdificio = $params['idEdificio'];
        $page = $params['page'];
        $maxPage = 20;

        $adapter=$this->getAdapter();
        $select = "SELECT not_in_cod as id, not_vc_tit as titulo, not_te_con as concepto, not_da_fec as fecha, not_vc_img as imagen FROM noticia WHERE edi_in_cod = $idEdificio ORDER BY not_da_fec DESC LIMIT $page,$maxPage";
        $data = $adapter->query($select,$adapter::QUERY_MODE_EXECUTE)->toArray();

        if(!empty($data)){
            foreach($data as $noticia){
                $imagen = ($noticia['imagen']!='')?$noticia['imagen']:'no-image.png';
                $result[] = array(
                    "id"=>$noticia['id'],
                    "titulo"=>$noticia['titulo'],
                    "concepto"=>$noticia['concepto'],
                    "fecha"=>$noticia['fecha'],
                    "imagen"=>$imagen
                );
            }
        }else{
            $result[] = array(null);
        }

        return array("row"=>$result);

    }

    public function info($params)
    {
        setlocale(LC_ALL,"es_PE","es_PE","esp");
        $idNoticia = $params['idNoticia'];
        $adapter=$this->getAdapter();
        $select = "SELECT not_in_cod as id, not_vc_tit as titulo, not_te_con as concepto, not_da_fec as fecha, not_vc_img as imagen FROM noticia WHERE not_in_cod =$idNoticia";
        $data = $adapter->query($select,$adapter::QUERY_MODE_EXECUTE)->current();
        $data['imagen'] = ($data['imagen']!='')?$data['imagen']:'noimage.jpg';
        $data['extension'] = explode(".", $data['imagen'])[1];
        $data['fecha'] = strftime("%d de %B del %Y", strtotime($data['fecha']));
        if(count($data) > 1){
            return array("mensaje"=>"success","info"=>$data);
        }else{
            return array("mensaje"=>"error");
        }
    }

    public function listarMaximoNoticias($idEdificio, $maxResultado)
    {
        setlocale(LC_ALL,"es_PE","es_PE","esp");
        $maxResultado = ($maxResultado!='')?$maxResultado:10;
        $adapter=$this->getAdapter();
        $sql = new Sql($adapter);
        $select = $sql->select()
            ->from('noticia')
            ->columns(array(
                'id'=>'not_in_cod',
                'titulo'=>'not_vc_tit',
                'concepto'=>'not_te_con',
                'fecha'=>'not_da_fec',
                'imagen'=>'not_vc_img'
            ))
            ->where(array('edi_in_cod'=>$idEdificio))
            ->order('id desc')
            ->limit($maxResultado);
        $selectString=$sql->buildSqlString($select);
        $data=$adapter->query($selectString,$adapter::QUERY_MODE_EXECUTE)->toArray();
        for($i=0;$i<count($data);$i++){
            $data[$i]['imagen'] = ($data[$i]['imagen']!='')?$data[$i]['imagen']:'no-image.png';
            $data[$i]['fecha'] = strftime("%d de %B del %Y", strtotime($data[$i]['fecha']));
            $data[$i]['concepto'] = substr($data[$i]['concepto'],0,50)."...";
        }
        return $data;
    }

}
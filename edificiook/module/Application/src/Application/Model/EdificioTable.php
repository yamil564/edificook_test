<?php
/**
 * edificioOk (https://www.edificiook.com)
 * creado por: Meler Carranza, 10/03/2016.
 * ultima modificacion por: 
 * Descripcion: 
 *
 * @link      https://www.edificiook.com
 * @copyright Copyright (c) 2011-2016 KND S.A.C (http://www.knd.pe)
 * @license   http://www.edificiook.com/license/comercial Software Comercial
 * 
 */

namespace Application\Model;

use Zend\Db\Sql\Sql;
use Zend\Db\TableGateway\AbstractTableGateway;
use Zend\Db\Adapter\Adapter;
use Zend\Db\ResultSet\ResultSet;


class EdificioTable extends AbstractTableGateway
{

    public $table = 'edificio';


    public function __construct(Adapter $adapter)
    {
        $this->adapter = $adapter;
        $this->resultSetPrototype = new ResultSet(ResultSet::TYPE_ARRAY);
        $this->initialize();
    }

    public function getEdificios($params)
    {
        try {
            $sql = new Sql($this->getAdapter());
            $select = $sql->select()
                ->from(array('uedificio' => 'usuario_edificio'))
                ->columns(array())
                ->join(array("edi"=>$this->table),'edi.edi_in_cod=uedificio.edi_in_cod',array('id'=>'edi_in_cod','nombre'=>'edi_vc_des'))
                ->where(array('edi_in_est'=>1,"uedificio.usu_in_cod"=>$params['userId']));
            $select->quantifier('DISTINCT');
            //printf($sql->buildSqlString($select));
            $statement = $sql->prepareStatementForSqlObject($select);
            $rsEdificios = $this->resultSetPrototype->initialize($statement->execute())->toArray();
            return $rsEdificios;
        } catch (\Exception $e) {
            throw new \Exception($e->getPrevious()->getMessage());
        }
    }

    public function getMenu($params){
        $sql=new Sql($this->adapter);
        $usuarioEdificioCodigo=$this->getUsuarioEdificioCodigo($params);
        $select=$sql->select()
            ->from(array('per'=>'permiso'))
            ->columns(array())
            ->join(array('m'=>'menu'),'m.m_in_cod=per.m_in_cod',array('menu'=>'m_vc_nombre','route'=>'m_vc_routename', 'url'=>'m_vc_url', 'm_vc_ico'),'LEFT')
            ->join(array('mg'=>'menu_grupo'),'mg.mg_in_cod=m.mg_in_cod',array('grupomenu'=>'mg_vc_nombre','ico'=>'mg_vc_ico'),'LEFT')
            ->where(array('per.uedi_in_cod'=>$usuarioEdificioCodigo,'per.perm_in_est'=>1))
            ->order('mg.mg_in_orden')
            ->order('m.m_in_orden');

        if($params['tipoPerfil']=='ue'){
            $select->where(array('m.m_vc_ambiente'=>'E'));
            $tipoMenu="usuario_externo";
        }

        $statement = $sql->prepareStatementForSqlObject($select);
        $rsEdificios = $this->resultSetPrototype->initialize($statement->execute())->toArray();

        return $rsEdificios;
    }

    public function getItemsDeMenuGrupo($params){
        $sql=new Sql($this->adapter);
        $usuarioEdificioCodigo=$this->getUsuarioEdificioCodigo($params);

        $select=$sql->select()
            ->from(array('per'=>'permiso'))
            ->columns(array())
            ->join(array('m'=>'menu'),'m.m_in_cod=per.m_in_cod',array('menu'=>'m_vc_nombre','route'=>'m_vc_routename', 'url'=>'m_vc_url', 'm_vc_ico'),'LEFT')
            ->join(array('mg'=>'menu_grupo'),'mg.mg_in_cod=m.mg_in_cod',array('grupomenu'=>'mg_vc_nombre','ico'=>'mg_vc_ico'),'LEFT')
            ->where(array('per.uedi_in_cod'=>$usuarioEdificioCodigo,'per.perm_in_est'=>1,'mg.mg_vc_nombre'=>$params['menugrupo']))
            ->order('mg.mg_in_orden')
            ->order('m.m_in_orden');

        $statement = $sql->prepareStatementForSqlObject($select);
        $rsEdificios = $this->resultSetPrototype->initialize($statement->execute())->toArray();

        return $rsEdificios;
    }

    private function getUsuarioEdificioCodigo($params){
        $sql=new Sql($this->adapter);
        $selectCodigoUEdi=$sql->select()
            ->from('usuario_edificio')
            ->columns(array('codigo'=>'uedi_in_cod'))->where(array('edi_in_cod'=>$params['edificioId'],'usu_in_cod'=>$params['userId']));
        $statement=$sql->prepareStatementForSqlObject($selectCodigoUEdi);
        $rsUEdi=$statement->execute()->current();

        $usuarioEntidadCodigo=0;

        if(!empty($rsUEdi)){
            $usuarioEntidadCodigo=$rsUEdi['codigo'];
        }
        return $usuarioEntidadCodigo;
    }

}

<?php
namespace ZF2AuthAcl\Model;

use Zend\Db\Sql\Sql;
use Zend\Db\TableGateway\AbstractTableGateway;
use Zend\Db\Adapter\Adapter;
use Zend\Db\ResultSet\ResultSet;

class User extends AbstractTableGateway
{

    public $table = 'usuario';
    
    public function __construct(Adapter $adapter)
    {
        $this->adapter = $adapter;
        $this->resultSetPrototype = new ResultSet(ResultSet::TYPE_ARRAY);
        $this->initialize();
    }
    
    public function getUsers($params)
    {
        try {
            $sql = new Sql($this->getAdapter());
            $select = $sql->select()
                ->from(array('user' => $this->table))
                ->columns(array('usu_in_cod','emp_in_cod','usu_ch_tipoperfil'))
                ->where(array('usu_vc_usu' => $params['username']));
            
            $statement = $sql->prepareStatementForSqlObject($select);
            $users = $this->resultSetPrototype->initialize($statement->execute())->toArray();

            //buscamos el primer edificio asociado.
            if(isset($users[0]['usu_in_cod'])){

                $selectUediDefault = $sql->select()
                ->from(array('uedificio' => 'usuario_edificio'))
                ->columns(array())
                ->join(array("edi"=>'edificio'),'edi.edi_in_cod=uedificio.edi_in_cod',array('id'=>'edi_in_cod','nombre'=>'edi_vc_des'))
                ->where(array('edi_in_est'=>1,"uedificio.usu_in_cod"=>$users[0]['usu_in_cod']))->limit(1);
    
                $statementUedi=$sql->prepareStatementForSqlObject($selectUediDefault);
                $rowUediDefault=$statementUedi->execute()->current();

                if(!empty($rowUediDefault)){
                   $users[0]['primeredificio']=$rowUediDefault['id']; 
                }  
            }
            

            return $users;

        } catch (\Exception $e) {
            throw new \Exception($e->getPrevious()->getMessage() );
        }
    }    
}

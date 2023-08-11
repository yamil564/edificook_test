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

class UsuarioTable extends AbstractTableGateway{

    private $fechaSistema=null;
	public function __construct(Adapter $adapter)
	{  
        $this->fechaSistema=date('Y-m-d H:i:s');
		$this->adapter=$adapter;
	}

	public function getUsuariosByEdificio($params) {


		$adapter=$this->getAdapter();
        $sql=new Sql($adapter);

        $selectUser=$sql->select()
        ->from(array('usu'=>'usuario'))
        ->columns(array('id'=>'usu_in_cod',
           'tipo'=>'usu_ch_tip',
           'nombre'=>'usu_vc_nom',
           'apellido'=>'usu_vc_ape',
           'email'=>'usu_vc_ema',
        ))->where(array('usu_in_ent'=>$params['edificioId'], 'usu_in_est'=>1));

        $selectString=$sql->buildSqlString($selectUser);
        $rsUsuarios=$adapter->query($selectString,$adapter::QUERY_MODE_EXECUTE)->toArray();

        if(!empty($rsUsuarios)){
            $i=0;
            $response=array();
            foreach ($rsUsuarios as $key => $value){

            	$usuarioNombre = ($value['tipo']=="PJ") ? $value['apellido'] : $value['nombre']. " ".$value['apellido'];

                $response['rows'][$i]['id']=$value['id'];
                $response['rows'][$i]['cell']=array(
                    $value['id'],
                    $usuarioNombre,
                    $value['email'],
                    date('d-m-Y H:m:s'),
                );
                
                $i++;
            }
        }
        
        return $response;
	}

    public function getUsuarioById ($params){
        $adapter=$this->getAdapter();
        $sql=new Sql($adapter);

        $selectUser=$sql->select()
        ->from(array('usu'=>'usuario'))
        ->columns(array('id'=>'usu_in_cod',
           'tipo'=>'usu_ch_tip',
           'nombre'=>'usu_vc_nom',
           'apellido'=>'usu_vc_ape',
           'email'=>'usu_vc_ema',
           'dni'=>' usu_ch_dni',
           'ruc'=>'usu_ch_ruc',
           'rpl'=>'usu_vc_rpl',
           'direccion'=>'usu_vc_dir',
           'telefono'=>'usu_vc_tel',
           'celular'=>'usu_vc_cel',
           'telefonoOficina'=>'usu_vc_telefofi',
           'fax'=>'usu_vc_fax',
           'username'=>'usu_vc_usu',
        ))->where(array('usu_in_ent'=>$params['edificioId'],'usu_in_cod'=>$params['id']));

        $selectString=$sql->buildSqlString($selectUser);
        $rsUsuario=$adapter->query($selectString,$adapter::QUERY_MODE_EXECUTE)->current();

        return $rsUsuario;
    }

    public function insertUsuario($params){
        $adapter=$this->getAdapter();
        $sql=new Sql($adapter);

        //seleccionar la empresa del edificio actual.

        if(strlen($params['textDni'])==8 ) {
            if($this->existeDNI($params,null)){
                return array('tipo'=>'error','mensaje'=>'Ya existe un usuario registrado con el DNI N°:'.$params['textDni']);
            }
        }else if (strlen($params['textDni']) > 0 && strlen($params['textDni'])!=8 ){
            return array('tipo'=>'error','mensaje'=>'El numero de DNI debera tener 8 digitos.');
        }

        if(strlen($params['textRuc'])==11 ) {
            if($this->existeRUC($params,null)){
                return array('tipo'=>'error','mensaje'=>'Ya existe un usuario registrado con el RUC N°:'.$params['textRuc']);
            }
        }else if (strlen($params['textRuc']) > 0 && strlen($params['textRuc'])!=8 ){
            return array('tipo'=>'error','mensaje'=>'El numero de DNI debera tener 8 digitos.');
        }


        if ($params['textCorreo'] != '' && !filter_var(trim($params['textCorreo']), FILTER_VALIDATE_EMAIL)) {
            return array('tipo'=>'error','mensaje'=>'El correo no tiene un formato válido.');
        }

        $username=trim($params['textUsername']);
        $password='';
        $passwordEncrypt='';

        if($params['tipoPass']!=''){
   
            if($this->existeUserName($username, null)){
                return array('tipo'=>'error','mensaje'=>'Ya existe un usuario registrado con el nombre de usuario :'.$params['textUsername']);
            }

            

            if($params['tipoPass']=='generado'){
                $password=trim($params['textPassDefault']);
            }else if($params['tipoPass']=='personalizado'){
                $password=trim($params['textPassPerzo']);
            }

            if($username=='' || $password=='' ){
                return array('tipo'=>'error','mensaje'=>'Los datos de acceso están en blanco');
            }else{
                $passwordEncrypt=md5('aUJGgadjasdgdj'.$password);
            }   
        }
 
        $selectRowEdificio="SELECT emp_in_cod as empresaId from edificio WHERE edi_in_cod=".$params['edificioId'];
        $rsEdificio=$adapter->query($selectRowEdificio, $adapter::QUERY_MODE_EXECUTE)->current();

        $empresaId=$rsEdificio['empresaId'];


        $insert = $sql->insert('usuario');
        $data = array(
            'emp_in_cod'=>$empresaId,
            'usu_ch_tip'=>$params['selTipo'],
            'usu_vc_nom'=>strtoupper($params['textNombre']),
            'usu_vc_ape'=>strtoupper($params['textApellido']),
            'usu_vc_ema'=>$params['textCorreo'],
            'usu_ch_dni'=>$params['textDni'],
            'usu_ch_ruc'=>$params['textRuc'],
            'usu_vc_rpl'=>$params['textRPL'],
            'usu_vc_dir'=>$params['textDireccion'],
            'usu_vc_tel'=>$params['textTelefono'],
            'usu_vc_cel'=>$params['textCelular'],
            'usu_vc_telefofi'=>$params['textTelefonoOfi'],
            'usu_vc_fax'=>$params['textFax'],
            'usu_in_ent'=>$params['edificioId'],
            'usu_in_est'=>1,
            'usu_dt_fecreg'=>$this->fechaSistema,
            'usu_dt_fedit'=>$this->fechaSistema,
            'usu_vc_usu'=>$username,
            'usu_vc_pas'=>$passwordEncrypt,
            'usu_vc_pasvis'=>$password,
            'usu_ch_tipoperfil'=>'ue',
        );

        $insert->values($data);
        $result=$sql->prepareStatementForSqlObject($insert)->execute()->count();

        if($result<=0){
            return array('tipo'=>'error','mensaje'=>'Error al intentar crear el usuario.');
        }

        return array('tipo'=>'informativo','mensaje'=>'Usuario creado con éxito.');  
    }




    public function updateUsuario($params){
        
        $adapter=$this->getAdapter();
        $sql=new Sql($adapter);

        $idUsuario=(int)$params['id'];
        
        if(strlen($params['textDni'])==8 ) {
            if($this->existeDNI($params,$idUsuario)){
                return array('tipo'=>'error','mensaje'=>'Ya existe un usuario registrado con el DNI N°:'.$params['textDni']);
            }

        }else if (strlen($params['textDni']) > 0 && strlen($params['textDni'])!=8 ){
            return array('tipo'=>'error','mensaje'=>'El numero de DNI debera tener 8 digitos.');
        }


        if(strlen($params['textRuc'])==11 ) {
            if($this->existeRUC($params,$idUsuario)){
                return array('tipo'=>'error','mensaje'=>'Ya existe un usuario registrado con el RUC N°:'.$params['textRuc']);
            }
        }else if (strlen($params['textRuc']) > 0 && strlen($params['textRuc'])!=8 ){
            return array('tipo'=>'error','mensaje'=>'El numero de DNI debera tener 8 digitos.');
        }

        if ($params['textCorreo'] != '' && !filter_var($params['textCorreo'], FILTER_VALIDATE_EMAIL)) {
            return array('tipo'=>'error','mensaje'=>'El correo no tiene un formato válido.');
        }

    
        $data = array(
            'usu_ch_tip'=>$params['selTipo'],
            'usu_vc_nom'=>strtoupper($params['textNombre']),
            'usu_vc_ape'=>strtoupper($params['textApellido']),
            'usu_vc_ema'=>$params['textCorreo'],
            'usu_ch_dni'=>$params['textDni'],
            'usu_ch_ruc'=>$params['textRuc'],
            'usu_vc_rpl'=>$params['textRPL'],
            'usu_vc_dir'=>$params['textDireccion'],
            'usu_vc_tel'=>$params['textTelefono'],
            'usu_vc_cel'=>$params['textCelular'],
            'usu_vc_telefofi'=>$params['textTelefonoOfi'],
            'usu_vc_fax'=>$params['textFax'],
            'usu_dt_fedit'=>$this->fechaSistema,
        );

        $username=trim($params['textUsername']);
        $password=trim($params['textPass']);
        $passwordEncrypt='';
        $salt='aUJGgadjasdgdj';

        if($username!='' && $password!=''){
            

            if($this->existeUserName($username, $idUsuario)){
                return array('tipo'=>'error','mensaje'=>'Ya existe un usuario registrado con el nombre de usuario :'.$username);
            }

            $passwordEncrypt=md5($salt.$password);
            $data['usu_vc_usu']=$username;
            $data['usu_vc_pas']=$passwordEncrypt;
            $data['usu_vc_pasvis']=$password;
        }

        $updateUsuario=$sql->update()->table('usuario')->set($data)->where( array('usu_in_cod'=>$idUsuario));
        $result=$sql->prepareStatementForSqlObject($updateUsuario)->execute()->count();

        if($result<=0){
            return array('tipo'=>'advertencia','mensaje'=>'El registro no sufrió ningún cambio.');
        }

        return array('tipo'=>'informativo','mensaje'=>'Datos de usuario actualizado con éxito.');
    }


    public function deleteUsuario($params){
        $adapter=$this->getAdapter();
        $sql=new Sql($adapter);

        $data=array("usu_in_est"=>0,'usu_dt_fedit'=>$this->fechaSistema);
        $updateUsuario=$sql->update()->table('usuario')->set($data)->where( array('usu_in_cod'=>$params['id']));
        $result=$sql->prepareStatementForSqlObject($updateUsuario)->execute()->count();

        if($result<=0){
            return array('tipo'=>'error','mensaje'=>'Error al intentar eliminar el usuario seleccionado.');
        }

        return array('tipo'=>'informativo','mensaje'=>'Usuario eliminado con éxito.');

    }

    private function existeDNI($params,$id){
        $adapter=$this->getAdapter();
        $sql=new Sql($adapter);

        if($id==NULL){
            $selectString="SELECT  count(*) as total from usuario WHERE usu_ch_dni LIKE '".$params['textDni']."' AND usu_in_ent=".$params['edificioId']." AND usu_in_est!=0";
        }else{
            
            //SELECT  count(*) as total from usuario WHERE usu_ch_dni LIKE '10274196' AND usu_in_ent=42 and usu_in_cod!=3314;
            $selectString="SELECT  count(*) as total from usuario WHERE usu_ch_dni LIKE '".$params['textDni']."' AND usu_in_ent=".$params['edificioId']. " AND usu_in_est!=0  AND usu_in_cod!=".$id;
        }
        
        $rsValidar=$adapter->query($selectString,$adapter::QUERY_MODE_EXECUTE)->current();

        if($rsValidar['total']>0){
            return true;
        }

        return false;
    }

    private function existeRUC($params,$id){
        $adapter=$this->getAdapter();
        $sql=new Sql($adapter);

        if($id==NULL){

            $selectString="SELECT  count(*) as total from usuario WHERE usu_ch_ruc LIKE '".$params['textRuc']."' AND usu_in_ent=".$params['edificioId']. " AND usu_in_est!=0";
        }else{
            $selectString="SELECT  count(*) as total from usuario WHERE usu_ch_ruc LIKE '".$params['textRuc']."' AND usu_in_ent=".$params['edificioId']. " AND usu_in_est!=0 AND usu_in_cod!=".$id;
        }
        
        $rsValidar=$adapter->query($selectString,$adapter::QUERY_MODE_EXECUTE)->current();
        
        if($rsValidar['total']>0){
            return true;
        }

        return false;
    }

    private function existeUserName($username,$id){
        $adapter=$this->getAdapter();
        $sql=new Sql($adapter);

        /*
            Validamos la disponiblidad del usuario de manera general 
            (sin importar al edificio que pertenece ni estado en el que se encuentre [activo, eliminado].
        */ 

        if($id==NULL){
            $selectString="SELECT  count(*) as total from usuario WHERE usu_vc_usu LIKE '".$username."'";
        }else{
            $selectString="SELECT  count(*) as total from usuario WHERE usu_vc_usu LIKE '".$username."' AND usu_in_cod!=".$id;
        }
        
        $rsValidar=$adapter->query($selectString,$adapter::QUERY_MODE_EXECUTE)->current();
        
        if($rsValidar['total']>0){
            return true;
        }

        return false;
    }
}

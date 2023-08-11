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

use Zend\Mail;
Use Zend\Mime;
use Zend\Mime\Message as MimeMessage;
use Zend\Mime\Part as MimePart;

class UseraccessTable extends AbstractTableGateway{

    private $fechaSistema=null;

    public function __construct(Adapter $adapter)
    {
        $this->adapter=$adapter;
        //$this->resultSetPrototype = new ResultSet(ResultSet::TYPE_ARRAY);
        //$this->initialize();
        $this->fechaSistema=date('Y-m-d H:i:s');
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
        ))->where(array('usu_in_ent'=>$params['edificioId']));

        $selectString=$sql->buildSqlString($selectUser);
        $rsUsuarios=$adapter->query($selectString,$adapter::QUERY_MODE_EXECUTE)->toArray();

        if(!empty($rsUsuarios)){
            $i=0;
            $response=array();
            /*
            $response['page'] = $page;
            $response['total'] = $total_pages;
            $response['records'] = $count;
            */
            foreach ($rsUsuarios as $key => $value){
                $response['rows'][$i]['id']=$value['id'];
                $response['rows'][$i]['cell']=array(
                    $value['id'],
                    $value['nombre']." ".$value['apellido'],
                    $value['email'],
                    date('d-m-Y H:m:s'),
                );
                
                $i++;
            }
        }
        
        return $response;
    }
    

    public function getMenuPorTipoAmbiente($params){
    	$adapter=$this->getAdapter();
        $sql=new Sql($this->adapter);
     	
        $select=$sql->select()
            ->from(array('m'=>'menu'))
            ->columns(array('mId'=>'m_in_cod','menu'=>'m_vc_nombre'))
            ->join(array('mg'=>'menu_grupo'),'mg.mg_in_cod=m.mg_in_cod',array('mgId'=>'mg_in_cod','grupomenu'=>'mg_vc_nombre','ico'=>'mg_vc_ico'),'LEFT')
            ->where(array('m.m_in_est'=>1))
            ->order('mg.mg_in_orden')
            ->order('m.m_in_orden');


        if($params['tipoAmbiente']=='I'){
            $select->where(array('m.m_vc_ambiente'=>'I'));
            $tipoMenu="usuario_externo";
        }else{
        	$select->where(array('m.m_vc_ambiente'=>'E'));
        }

        $selectString=$sql->buildSqlString($select);
        $rsMenu=$adapter->query($selectString,$adapter::QUERY_MODE_EXECUTE)->toArray();



        
        $arrayMenu=array();
        $indexNuevoGrupoMenu=0;
        $totalOpcionesMenu=count($rsMenu);
       
        $i=0;
       	while ($i <= $totalOpcionesMenu) {
       		$currentGrupoMenu=$rsMenu[$i]['grupomenu'];
       		

       		$arrayMenu[$indexNuevoGrupoMenu]=array('grupo'=>$currentGrupoMenu,"mgId"=>$rsMenu[$i]['mgId']);

            $arraySubMenu=array();
       		while (true) {
       			if($currentGrupoMenu==$rsMenu[$i]['grupomenu'] ) {
       				$arraySubMenu[]=array('id'=>$rsMenu[$i]['mId'], 'menu'=>$rsMenu[$i]['menu'] ) ;
       				$i++;
       				if($i >= $totalOpcionesMenu){
       					break;
       				}
       			}else{
       				$i--;
                    break;
       			}
        	}

            $arrayMenu[$indexNuevoGrupoMenu]['rows']=$arraySubMenu;
        	$indexNuevoGrupoMenu++;
        	$i++;
       	}
        

        return $arrayMenu;
    }



    public function save($params){
        $adapter=$this->getAdapter();
        $sql=new Sql($this->adapter);


        $usuarioIds=$params['usuarios'];
        $edificioId=$params['edificioId'];

        if(count($usuarioIds) < 0){
            return array("tipo"=>"error","mensaje"=>"Seleccionar al menos un usuario para asignar permiso");
        }

        $itemsMenu=$params['items'];
        if($itemsMenu!=''){
            $itemsMenu=substr($itemsMenu, 1);
            $itemsMenu=explode(",", $itemsMenu);
        }else{
            return;
        }

        if(count($itemsMenu)<=0){
            return array("tipo"=>"error","mensaje"=>"Seleccionar al menos un elemento de la lista");
        }

        foreach ($usuarioIds as $userId) {

            //Obtener el id de la tabla usuarioEdificio.
            $selectStringUE="SELECT uedi_in_cod as id FROM usuario_edificio 
                                WHERE edi_in_cod=$edificioId 
                                    AND usu_in_cod=$userId 
                                    AND uedi_in_est=1 
                                LIMIT 1";
            $rsUsuarioEdificio=$adapter->query($selectStringUE,$adapter::QUERY_MODE_EXECUTE)->current();

            $currentUsuarioEdificioId=null;
            if(empty($rsUsuarioEdificio)){
                $dataInsert=array(
                    'edi_in_cod'=>$params['edificioId'],
                    'usu_in_cod'=>$userId,
                    'car_ch_cod'=>'',
                    'uedi_da_fini'=>$this->fechaSistema,
                    'uedi_in_est'=>1,
                );

                $insertUsuarioEdificio=$sql->insert('usuario_edificio')->values($dataInsert);
                $statement=$sql->prepareStatementForSqlObject($insertUsuarioEdificio);
                $currentUsuarioEdificioId=$statement->execute()->getGeneratedValue();
            }else{
                $currentUsuarioEdificioId=$rsUsuarioEdificio['id'];
            }

            //Asignar permisos a id UsuarioEdificio (Le damos insert por que el campo es unico y no permitira duplicados).

            $deletePermisos=$sql->delete('permiso')->where(array('uedi_in_cod'=>$currentUsuarioEdificioId));
            $statement=$sql->prepareStatementForSqlObject($deletePermisos);
            $statement->execute()->count();

            foreach ($itemsMenu as $menuId) {
                $dataInsert=array(
                    'uedi_in_cod'=>$currentUsuarioEdificioId,
                    'm_in_cod'=>$menuId,
                    'perm_ch_acciones'=>'1-1-1-1-1-1-1',
                    'perm_in_est'=>1,
                );
                $insertPermiso=$sql->insert('permiso')->values($dataInsert);
                $statement=$sql->prepareStatementForSqlObject($insertPermiso);
                $statement->execute();
            }
        }

        return array(
                'tipo'=>'informativo',
                'mensaje'=>'Permisos Actualizados con éxito.'
        );

    }


    public function recordarDatos($params){
        $adapter=$this->getAdapter();
        $sql=new Sql($this->adapter);   

        $usuarioIds=$params['usuarios'];
        $edificioId=$params['edificioId'];

        if(count($usuarioIds) < 0){
            return array("tipo"=>"error","mensaje"=>"Seleccionar al menos un usuario para asignar permiso");
        }

        $queryEdificio = "SELECT emp_vc_des,emp_vc_dom,emp_vc_email 
                                FROM edificio en,empresa e WHERE en.emp_in_cod =e.emp_in_cod AND edi_in_cod ='$edificioId'";
        $rowEmpresa = $adapter->query($queryEdificio,$adapter::QUERY_MODE_EXECUTE)->current();


        foreach ($usuarioIds as $userId) {
            
            $queryUsuario = "SELECT usu_vc_ema, usu_vc_nom, usu_vc_ape, usu_ch_tip, usu_vc_usu, usu_vc_pasvis  FROM usuario WHERE usu_in_cod = '$userId' and usu_vc_ema!=''";
            $rowUsuario = $adapter->query($queryUsuario,$adapter::QUERY_MODE_EXECUTE)->current();

            if(!empty($rowUsuario)){

                $nombreDestinatario = $rowUsuario['usu_vc_nom'] . " " . $rowUsuario['usu_vc_ape'];
                if ($rowUsuario['usu_ch_tip'] == 'PJ'){
                    $nombreDestinatario = $rowUsuario['usu_vc_ape'];
                }

                $destinatario = $rowUsuario['usu_vc_ema'];
                $titulo = "Datos de acceso - sistema de inmueble";

                if($rowEmpresa['emp_vc_des']=='KND'){
                  $bgHeaderLogo='#7B004B';
                  $bgHeaderTitulo='#E3A2CC';
                  $colorTitulo='#7B004B';
                  $urlLogo='http://www.edificiook.com/';
                  $logo='http://www.edificiook.com/public/file/logo-empresa/edificiook_panel.png';
                }else{
                  $bgHeaderLogo='#2273b6';
                  $bgHeaderTitulo='#95c7f1';
                  $colorTitulo='#2273b6';
                  $urlLogo='http://sys.adinco.pe/';
                  $logo='http://sys.adinco.pe/public/file/logo-empresa/adinco_panel2.png';
                }

                $msg = '       
                    <table width="100%" border="0" cellspacing="0" cellpadding="0" bgcolor="'.$bgHeaderLogo.'">
                      <tbody>
                        <tr>
                          <td align="center" valign="top">
                            <table width="600" border="0" cellpadding="0" cellspacing="0" align="center">
                              <tbody>
                                <tr>
                                  <td>
                                  <table width="100%" border="0" cellspacing="0" cellpadding="0" bgcolor="'.$bgHeaderLogo.'">
                                    <tbody>
                                      <tr>
                                        <td style="line-height:10px">
                                          <div style="min-height:10px"></div>
                                        </td>
                                      </tr>
                                    </tbody>
                                  </table>
                                  <table width="100%" border="0" cellspacing="0" cellpadding="0">
                                    <tbody>
                                      <tr>
                                        <td width="120" align="center" valign="middle" style="padding-bottom:10px;padding-top:10px">
                                          <div align="center">
                                            <a href="'.$urlLogo.'">
                                              <img src="'.$logo.'" width="150" border="0" alt="" style="display:block" class="CToWUd">
                                            </a>
                                          </div>
                                        </td>
                                        <td width="520" valign="middle">
                                          <table border="0" cellspacing="0" cellpadding="0" width="100%">
                                            <tbody>
                                              <tr>
                                                <td style="padding-left:5px;padding-right:5px;padding-bottom:5px" align="right">
                                                  <font color="#FFFFFF">Acceso a Usuario</font>
                                                </td>
                                              </tr>
                                              <tr>
                                                <td style="padding-left:5px;padding-right:5px" align="right">
                                                  <font style="font-size:14px;line-height:14px" face="Arial, sans-serifArial, sans-serif" color="#dddddd">'.$nombreDestinatario.'</font>
                                                </td>
                                              </tr>   
                                            </tbody>
                                          </table>
                                        </td>
                                      </tr>
                                    </tbody>
                                  </table>
                                  </td>
                                </tr>
                              </tbody>
                            </table> 
                          </td>
                        </tr>
                      </tbody>
                    </table>

                    <table width="100%" border="0" cellspacing="0" cellpadding="0" bgcolor="'.$bgHeaderLogo.'">
                      <tbody>
                        <tr>
                          <td style="line-height:10px">
                            <div style="min-height:10px"></div>
                          </td>
                        </tr>
                      </tbody>
                    </table>

                    <table width="100%" border="0" cellspacing="0" cellpadding="0" bgcolor="'.$bgHeaderTitulo.'">
                      <tbody>
                        <tr>
                          <td align="center" valign="top">
                            <table width="640" border="0" cellpadding="0" cellspacing="0" align="center">
                              <tbody>
                                <tr>
                                  <td align="left" style="padding-top:40px;padding-bottom:40px;padding-left:20px;padding-right:20px;font-size:40px;line-height:40px">
                                    <font face="\'Boing-Bold\', \'Arial Black\', Arial, sans-serif" color="'.$colorTitulo.'" style="font-size:39px;line-height:40px;">Datos de Acceso al Sistema</font>
                                  </td>
                                </tr>
                              </tbody>
                            </table>
                          </td>
                        </tr>
                      </tbody>
                    </table>

                    <table width="100%" border="0" cellspacing="0" cellpadding="0" bgcolor="#ffffff">
                      <tbody>
                        <tr>
                          <td align="center" valign="top">
                            <table width="640" border="0" cellpadding="0" cellspacing="0" align="center">
                              <tbody>
                                <tr>
                                  <td style="padding-top:40px;padding-bottom:40px;padding-left:20px;padding-right:20px" align="left">
                                    <font face="Arial, sans-serif" color="#333333" style="font-size:14px;line-height:20px">
                                    Estimado usuario a continuación le recordamos sus datos de acceso al sistema de administración de inmuebles.
                                    </font><br><br>
                                    <font face="Arial, sans-serif" color="#333333" style="font-size:14px;line-height:20px">
                                    <strong>Usuario:</strong> '.$rowUsuario['usu_vc_usu'].'
                                    </font><br>
                                    <font face="Arial, sans-serif" color="#333333" style="font-size:14px;line-height:20px">
                                    <strong>Contraseña:</strong> '.$rowUsuario['usu_vc_pasvis'].'
                                    </font><br><br>
                                    <font face="Arial, sans-serif" color="#9E9E9E" style="font-size:14px;line-height:20px">
                                    Si tiene algún problema para acceder, por favor contactarse con la administración.
                                    </font><br><br>
                                    <font face="Arial, sans-serif" color="#333333" style="font-size:14px;line-height:20px">
                                    Atentamente:<br>
                                    '.$rowEmpresa['emp_vc_des'].'<br>
                                    <a href="http://www.'.$rowEmpresa['emp_vc_dom'].'/">http://www.'.$rowEmpresa['emp_vc_dom'].'/</a>
                                    </font>
                                  </td>
                                </tr>
                              </tbody>
                            </table>
                          </td>
                        </tr>
                      </tbody>
                    </table>

                    <table width="100%" border="0" cellspacing="0" cellpadding="0" bgcolor="#ffffff">
                      <tbody>
                        <tr>
                          <td align="center" valign="bottom" style="padding-bottom:0px">
                            <img src="http://www.edificiook.com/public/images/linea.png" border="0" style="display:block;width:100%;max-height:63px" class="CToWUd">
                          </td>
                        </tr>
                      </tbody>
                    </table>

                    <table width="100%" border="0" cellspacing="0" cellpadding="0" bgcolor="#bebebe">
                      <tbody>
                        <tr>
                          <td align="center" valign="top">
                            <table width="640" border="0" cellpadding="0" cellspacing="0" align="center">
                              <tbody>
                                <tr>
                                  <td style="padding-top:5px;padding-bottom:40px;padding-left:20px;padding-right:20px">
                                    <table width="100%" border="0" cellspacing="0" cellpadding="0">
                                      <tbody>
                                        <tr>
                                          <td align="left" valign="middle" style="padding-left:0px;padding-right:0px;padding-top:5px;padding-bottom:0px">
                                            <font face="Arial, sans-serif" color="#333333" style="font-size:12px;line-height:18px">
                                            No respondas este correo electrónico. Los correos electrónicos enviados a esta dirección no se responderán.<br>
                                            </font>
                                          </td>
                                        </tr>
                                      </tbody>
                                    </table>
                                  </td>
                                </tr>
                              </tbody>
                            </table>
                          </td>
                        </tr>
                      </tbody>
                    </table>
                ';

                $responder = $rowEmpresa['emp_vc_email'];
                $remite = $rowEmpresa['emp_vc_email'];
        
              
                if ($destinatario != '' && filter_var($destinatario, FILTER_VALIDATE_EMAIL)) {
                    $this->sendMailNoticia($destinatario, $titulo, $msg, $remite, $nombreDestinatario, $edificioId);
                }
            }
        }

        return array(
                'tipo'=>'informativo',
                'mensaje'=>'Permisos Actualizados con éxito.'
        );
    }


    private function sendMailNoticia($destinatario, $titulo, $msg, $remite, $nombreDestinatario, $edificioId)
    {

        // first create the parts
        $text = new Mime\Part();
        $text->type = Mime\Mime::TYPE_TEXT;
        $text->charset = 'utf-8';
        
        // message html
        $html = new MimePart($msg);
        $html->type = "text/html";// message html

      
        // then add them to a MIME message
        $mimeMessage = new Mime\Message();
        $mimeMessage->setParts(array($text, $html));
        
        // and finally we create the actual email
        $message = new Mail\Message();

        $message->setBody($mimeMessage);
        $message->setFrom($remite, 'Administrador');
        $message->addTo($destinatario, $nombreDestinatario);
        $message->setSubject($titulo);
        $message->setEncoding("UTF-8");

        $transport = new Mail\Transport\Sendmail();
        $transport->send($message);

        $this->saveLogCorreo($edificioId,$nombreDestinatario,$destinatario,$titulo);

    }


    private function saveLogCorreo($edificioId,$nombreDestinatario,$destinatario,$titulo)
    {

        $adapter=$this->getAdapter();
        $sql=new Sql($adapter);
        $codigo = "";
       
        //insert log correo
        $insert = $sql->insert('log_correo');
        $data = array(
            'log_dt_freg'=>date('Y-m-d H:i:s'),
            'log_vc_unidad'=>'',
            'log_vc_nombre'=>$nombreDestinatario,
            'edi_in_cod'=>$edificioId,
            'log_vc_email'=>$destinatario,
            'log_vc_asunto'=>$titulo
        );
        $insert->values($data);
        $sql->prepareStatementForSqlObject($insert)->execute();
    }


}
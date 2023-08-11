<?php
/**
 * edificioOk (https://www.edificiook.com).
 * creado por: Meler Carranza, 29/08/2016.
 * ultima modificacion por: Meler Carranza.
 * Fecha Modificacion: 29/08/2016.
 * Descripcion: Script controller para la opción de accesibilidad de usuarios.
 *
 * @author    Fidel Thompson Fonseca.
 * @link      https://www.edificiook.com.
 * @copyright Copyright (c) 2011-2016 KND S.A.C (http://www.knd.pe).
 * @license   http://www.edificiook.com/license/comercial Software Comercial.
 */

namespace Seguridad\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\Session\Container;

class UseraccessController extends AbstractActionController
{   

    private $edificioId=null;
    private $usuarioId=null;
    private $empresaId=null;

    public function __construct()
    {
        $session=new Container('User');
        $this->edificioId=$session->offsetGet('edificioId');
        $this->usuarioId=$session->offsetGet('userId');
        $this->empresaId=$session->offsetGet('empId');
    }


    public function indexAction()
    {
        return new ViewModel();
    }


    public function gridAction(){
    	$request=$this->getRequest();
    	$response = $this->getResponse();
    	
    	if($request->isPost()){
    		
	      
            $params=$request->getPost();
            $params['edificioId']= $this->edificioId;

	        $departamentos = $this->getServiceLocator()->get("Seguridad\Model\UseraccessTable");
	        $departamentos = $departamentos->getUsuariosByEdificio($params);

	    	$response->setContent(\Zend\Json\Json::encode($departamentos));
			
    	}else{
    		$response->setContent(\Zend\Json\Json::encode(array(null)));
    	}

        return $response;
    }

    public function readAction(){
        $request=$this->getRequest();
        $response = $this->getResponse();

        $data=array();
        if($request->isPost()){
            $params=$request->getPost();
            $params['edificioId']=$this->edificioId;
            $params['empresaId']=$this->empresaId;

            $useraccessTable = $this->getServiceLocator()->get("Seguridad\Model\UseraccessTable");
            $about=isset($params['about'])?$params['about']:'';
            switch ($about){
                case 'getMenuPorAmbiente':
                    $data=$useraccessTable->getMenuPorTipoAmbiente($params);
                    break;
                default:
                    $data=array();
                    break;
            }
            $response->setContent(\Zend\Json\Json::encode($data));
        }else{
            $response->setContent(\Zend\Json\Json::encode($data));
        }
        return $response;
    }




    public function saveAction(){
        $request=$this->getRequest();
        $response = $this->getResponse();

        $data=array();
        if($request->isPost()){
            $params=$request->getPost();
            $params['edificioId']=$this->edificioId;
            $params['empresaId']=$this->empresaId;

            $useraccessTable = $this->getServiceLocator()->get("Seguridad\Model\UseraccessTable");
            $about=isset($params['about'])?$params['about']:'';
            switch ($about){
                case 'savePermiso':
                    $data=$useraccessTable->save($params);
                    break;
                case 'recordarDatos':
                    $data=$useraccessTable->recordarDatos($params);
                    break;
                default:
                    $data=array();
                    break;
            }
            $response->setContent(\Zend\Json\Json::encode($data));
        }else{
            $response->setContent(\Zend\Json\Json::encode($data));
        }
        return $response;
    }

    
}

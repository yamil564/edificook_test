<?php

namespace Mantenimiento\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\session\Container;

class ProveedorController extends AbstractActionController
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
    		$session=new Container('User');
	      
            $params=$request->getPost();
            $params['edificioId']= $this->edificioId;
            $params['empresaId']=$this->empresaId;

	        $proveedorTable = $this->getServiceLocator()->get("Mantenimiento\Model\ProveedorTable");
	        $proveedorTable = $proveedorTable->getProveedoresByEmpresa($params);

	    	$response->setContent(\Zend\Json\Json::encode($proveedorTable));
			
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

            $proveedorTable = $this->getServiceLocator()->get("Mantenimiento\Model\ProveedorTable");
            $about=isset($params['about'])?$params['about']:'';
            switch ($about){
                case 'getRowProveedor':
                    $data=$proveedorTable->getProveedorById($params);
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
        $resultado=array();
        
        if($request->isPost()){
            $params=$request->getPost();
            $params['edificioId']=$this->edificioId;
            $params['usuarioId']=$this->usuarioId;
            $params['empresaId']=$this->empresaId;

            $proveedorTable = $this->getServiceLocator()->get("Mantenimiento\Model\ProveedorTable");
            $about=isset($params['about'])?$params['about']:'';
            
            switch ($about){
                case 'saveEditProveedor':
                    $resultado = $proveedorTable->updateProveedor($params);
                    break;
                case 'saveNewProveedor':
                    $resultado=$proveedorTable->insertProveedor($params);
                    break;
                default:
                    $resultado=array();
                    break;
            }
            $response->setContent(\Zend\Json\Json::encode($resultado));
        }else{
            $response->setContent(\Zend\Json\Json::encode($resultado));
        }
        return $response;
    }


    public function deleteAction(){
        $request=$this->getRequest();
        $response = $this->getResponse();
        $resultado=array();
        
        if($request->isPost()){
            $params=$request->getPost();
            $params['userId']=$this->usuarioId;
            $params['edificioId']=$this->edificioId;
            $params['usuarioId']=$this->usuarioId;

            $proveedorTable = $this->getServiceLocator()->get("Mantenimiento\Model\ProveedorTable");
            $about=isset($params['about'])?$params['about']:'';
            
            switch ($about){
                case 'deleteProveedor':
                    $resultado = $proveedorTable->deleteProveedor($params);
                    break;
                default:
                    $resultado=array();
                    break;
            }
            $response->setContent(\Zend\Json\Json::encode($resultado));
        }else{
            $response->setContent(\Zend\Json\Json::encode($resultado));
        }
        return $response;
    }

}

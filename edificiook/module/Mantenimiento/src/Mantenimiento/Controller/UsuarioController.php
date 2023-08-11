<?php

namespace Mantenimiento\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\session\Container;

class UsuarioController extends AbstractActionController
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

	        $usuarioTable = $this->getServiceLocator()->get("Mantenimiento\Model\UsuarioTable");
	        $usuarioTable = $usuarioTable->getUsuariosByEdificio($params);

	    	$response->setContent(\Zend\Json\Json::encode($usuarioTable));
			
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

            $usuarioTable = $this->getServiceLocator()->get("Mantenimiento\Model\UsuarioTable");
            $about=isset($params['about'])?$params['about']:'';
            switch ($about){
                case 'getRowUsuario':
                    $data=$usuarioTable->getUsuarioById($params);
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
            $params['userId']=$this->usuarioId;
            $params['edificioId']=$this->edificioId;
            $params['usuarioId']=$this->usuarioId;

            $usuarioTable = $this->getServiceLocator()->get("Mantenimiento\Model\UsuarioTable");
            $about=isset($params['about'])?$params['about']:'';
            
            switch ($about){
                case 'saveEditUsuario':
                    $resultado = $usuarioTable->updateUsuario($params);
                    break;
                case 'saveNewUsuario':
                    $resultado=$usuarioTable->insertUsuario($params);
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

            $usuarioTable = $this->getServiceLocator()->get("Mantenimiento\Model\UsuarioTable");
            $about=isset($params['about'])?$params['about']:'';
            
            switch ($about){
                case 'deleteUser':
                    $resultado = $usuarioTable->deleteUsuario($params);
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

    function generaPass(){
        $cadena = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz1234567890";
        $longitudCadena=strlen($cadena);
         
        $pass = "";
        $longitudPass=6;
         
        for($i=1 ; $i<=$longitudPass ; $i++){
            $pos=rand(0,$longitudCadena-1);
            $pass .= substr($cadena,$pos,1);
        }
        return $pass;
    }


}

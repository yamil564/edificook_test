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

namespace Application\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\Session\Container;

class IndexController extends AbstractActionController
{
    public function __construct()
    {
        $session=new Container('User');
        $this->codEdificio=$session->offsetGet('edificioId');
        $this->codUsuario=$session->offsetGet('userId');
        $this->codEmpresa=$session->offsetGet('empId');
        $this->tipoPefil=$session->offsetGet('tipoPerfil');
    }

    public function indexAction()
    {
        return new ViewModel();
    }

    public function changeAction(){
    	$request=$this->getRequest();
    	$response = $this->getResponse();

    	if($request->isPost()){
    		$post_data=$request->getPost();
    		$edificioId=(int)$post_data['id'];
    		if($edificioId>0){
    			$session = new Container('User');
	    		$session->offsetSet('edificioId',$edificioId);

				//get tipo perfil de usuario.

				$route='../edificio';
				if($session->offsetGet('tipoPerfil')=='ue'){
					$route='../ue';
				}
	    		$response->setContent(\Zend\Json\Json::encode(array('response' => true,'route'=>$route)));
    		}else{
    			$response->setContent(\Zend\Json\Json::encode(array('response' => false,'route'=>$route)));
    		}
    	}else{
    		$response->setContent(\Zend\Json\Json::encode(array('response' => false)));
    	}

    	return $response;
    }

	public function seleccionarEdificioAction(){
		return new ViewModel();
	}

	public function opcionpendientedecontruccionAction(){
		return new ViewModel();
	}
}

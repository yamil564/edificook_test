<?php
/**
 * edificioOk (https://www.edificiook.com).
 * creado por: Meler Carranza, 29/04/2016.
 * ultima modificacion por: Meler Carranza.
 * Fecha Modificacion: 29/04/2016.
 * Descripcion: Script controller para la opción egreso.
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

class AuditoriaController extends AbstractActionController
{

	public function __construct()
    {
        $session=new Container('User');
        $this->idEdificio=$session->offsetGet('edificioId');
        $this->idUsuario=$session->offsetGet('userId');
        $this->tipoPerfil=$session->offsetGet('tipoPerfil');
    }

    public function indexAction()
    {
        return new ViewModel();
    }

    public function gridAuditoriaAction()
    {
    	$request=$this->getRequest();
        $response = $this->getResponse();

        if($request->isPost()){
            $params=$request->getPost();
            $params['idEdificio'] = $this->idEdificio;
            $params['idUsuario'] = $this->idUsuario;
            $params['tipoPerfil'] = $this->tipoPerfil;
            $noticia = $this->getServiceLocator()->get("Seguridad\Model\AuditoriaTable");
            $noticia = $noticia->listar($params);
            $response->setContent(\Zend\Json\Json::encode($noticia));
        }else{
            $response->setContent(\Zend\Json\Json::encode(array(null)));
        }

        return $response;
    }
}

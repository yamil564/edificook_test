<?php
/**
 * edificioOk (https://www.edificiook.com)
 * creado por: Meler Carranza, 23/03/2016.
 * ultima modificacion por: Meler Carranza
 * Fecha Modificacion: 23/03/2016.
 * Descripcion: Script controller para home - proceso
 *
 * @link      https://www.edificiook.com
 * @copyright Copyright (c) 2011-2016 KND S.A.C (http://www.knd.pe)
 * @license   http://www.edificiook.com/license/comercial Software Comercial
 * 
 */

namespace Ue\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\Session\Container;

class VisitasController extends AbstractActionController
{

    public function __construct()
    {
        $session=new Container('User');
        $this->idEdificio = $session->offsetGet('edificioId');
        $this->idEmpresa = $session->offsetGet('empId');
        $this->idUsuario = $session->offsetGet('userId');
    }

    public function indexAction()
    {
        $visitas = $this->getServiceLocator()->get("Ue\Model\VisitasTable");
        return new ViewModel(array(
            'listarUnidad'=>$visitas->listarUnidad($this->idEdificio),
        ));
    }

    public function loadVisitasAction()
    {

        $request=$this->getRequest();
        $response = $this->getResponse();

        if($request->isPost()){
            $params=$request->getPost();
            $visitas = $this->getServiceLocator()->get("Ue\Model\VisitasTable");
            $visitas = $visitas->listarVisitas($params,$this->idEdificio);
            $response->setContent(\Zend\Json\Json::encode($visitas));
        }else{
            $response->setContent(\Zend\Json\Json::encode(array(null)));
        }

        return $response;

    }

    public function loadPResidenteAction()
    {

        $request=$this->getRequest();
        $response = $this->getResponse();

        if($request->isPost()){
            $params=$request->getPost();
            $idUnidad=$params['idUnidad'];
            $visitas = $this->getServiceLocator()->get("Ue\Model\VisitasTable");
            $visitas = $visitas->mostrarPropietarioResidente($idUnidad);
            $response->setContent(\Zend\Json\Json::encode($visitas));
        }else{
            $response->setContent(\Zend\Json\Json::encode(array(null)));
        }

        return $response;

    }

    public function saveAction()
    {

        $request=$this->getRequest();
        $response = $this->getResponse();

        if($request->isPost()){
            $params=$request->getPost();
            $params['idEdificio'] = $this->idEdificio;
            $params['idUsuario'] = $this->idUsuario;
            $visitas = $this->getServiceLocator()->get("Ue\Model\VisitasTable");
            $visitas = $visitas->saveVisita($params);
            $response->setContent(\Zend\Json\Json::encode($visitas));
        }else{
            $response->setContent(\Zend\Json\Json::encode(array(null)));
        }

        return $response;

    }

}

<?php

namespace Mantenimiento\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\Session\Container;

class ConceptoController extends AbstractActionController
{
	public function __construct()
    {
        $session=new Container('User');
        $this->codEdificio=$session->offsetGet('edificioId');
        $this->codUsuario=$session->offsetGet('userId');
        $this->codEmpresa=$session->offsetGet('empId');
    }

    public function indexAction()
    {   
        $dataConcepto = $this->getServiceLocator()->get("Mantenimiento\Model\ConceptoTable");
        return new ViewModel(array(
            'listarGrupoConcepto'=>$dataConcepto->listarGrupoConcepto()
        ));
    }

    public function readAction()
    {
        $request=$this->getRequest();
        $response = $this->getResponse();

        if($request->isPost()){
            $params=$request->getPost();
            $concepto = $this->getServiceLocator()->get("Mantenimiento\Model\ConceptoTable");
            $concepto = $concepto->read($params); 
            $response->setContent(\Zend\Json\Json::encode($concepto));
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
            $params['codUsuario']=$this->codUsuario;
            $params['codEmpresa']=$this->codEmpresa;
            $params['codEdificio']=$this->codEdificio;
            $concepto = $this->getServiceLocator()->get("Mantenimiento\Model\ConceptoTable");
            if($params['action']==='add'){
                $concepto = $concepto->add($params);   
            }else{
                $concepto = $concepto->updatee($params);   
            }
            $response->setContent(\Zend\Json\Json::encode($concepto));
        }else{
            $response->setContent(\Zend\Json\Json::encode(array(null)));
        }

        return $response;
    }

    public function deleteAction()
    {
        $request=$this->getRequest();
        $response = $this->getResponse();

        if($request->isPost()){
            $params=$request->getPost();
            $params['codUsuario']=$this->codUsuario;
            $params['codEdificio']=$this->codEdificio;
            $dataConcepto = $this->getServiceLocator()->get("Mantenimiento\Model\ConceptoTable");
            $concepto = $dataConcepto->deletee($params);
            $response->setContent(\Zend\Json\Json::encode($concepto));
        }else{
            $response->setContent(\Zend\Json\Json::encode(array()));
        }

        return $response;
    }

    public function gridConceptoAction()
    {
    	$request=$this->getRequest();
        $response = $this->getResponse();

        if($request->isPost()){
            $params=$request->getPost();
            $params['codUsuario']=$this->codUsuario;
            $params['codEmpresa']=$this->codEmpresa;
            $dataConcepto = $this->getServiceLocator()->get("Mantenimiento\Model\ConceptoTable");
            $concepto = $dataConcepto->loadGrid($params);
            $response->setContent(\Zend\Json\Json::encode($concepto));
        }else{
            $response->setContent(\Zend\Json\Json::encode(array(null)));
        }

        return $response;
    }
}

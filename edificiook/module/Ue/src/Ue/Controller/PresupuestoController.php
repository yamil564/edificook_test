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

class PresupuestoController extends AbstractActionController
{

    public function __construct()
    {
        $session=new Container('User');
        $this->edificioId = $session->offsetGet('edificioId');
        $this->empresaId = $session->offsetGet('empId');
        $this->usuarioId = $session->offsetGet('userId');
    }

    public function indexAction()
    {
        return new ViewModel(array(null));
    }

    public function gridAction(){
        $request=$this->getRequest();
        $response = $this->getResponse();

        if($request->isPost()){
            $params=$request->getPost();
            $params['edificioId']=$this->edificioId;
            $params['empresaId']=$this->empresaId;
            $params['tipousuario']='externo';
            $presupuesto = $this->getServiceLocator()->get("Finanzas\Model\PresupuestoTable");
            $dataParaGrid = $presupuesto->getPresupuestoParaGrid($params);

            $response->setContent(\Zend\Json\Json::encode($dataParaGrid));          
        }else{
            $response->setContent(\Zend\Json\Json::encode(array(null)));
        }

        return $response;
    }

    public function readAction(){
        $request=$this->getRequest();
        $response = $this->getResponse();
        if($request->isPost()){
            $egresoTable = $this->getServiceLocator()->get("Finanzas\Model\EgresoTable");
            
            $params=$request->getPost();
            $params['edificioId']=$this->edificioId;
            $params['empresaId']=$this->empresaId;
            
            $query=isset($params['query'])?$params['query']:'';

            switch ($query){
                case 'egresosPorConceptoAndProveedor':
                    $params['edificioId']=$this->edificioId;
                    $data=$egresoTable->getEgresosPorConceptoAndProveedor($params);
                    break;
                default:
                    $data=array();
                    break;
            }
            $response->setContent(\Zend\Json\Json::encode($data));
        }else{
            $response->setContent(\Zend\Json\Json::encode(array('result'=>'error','mensaje'=>'petición no autorizada.')));
        }
        return $response;
    }

    public function conceptoAction(){
        $request=$this->getRequest();
        $response = $this->getResponse();
        if($request->isPost()){
                    
            $params=$request->getPost();
            $params['edificioId']=$this->edificioId;
            $params['empresaId']=$this->empresaId;

            $presupuesto = $this->getServiceLocator()->get("Finanzas\Model\PresupuestoTable");
            $dataConceptos = $presupuesto->getConceptos($params);
            
            $response->setContent(\Zend\Json\Json::encode($dataConceptos));

        }else{
            $response->setContent(\Zend\Json\Json::encode(array()));
        }
        return $response;
    }

}

<?php
/**
 * edificioOk (https://www.edificiook.com)
 * creado por: Meler Carranza, 23/03/2016.
 * ultima modificacion por: Meler Carranza
 * Fecha Modificacion: 09/04/2016.
 * Descripcion: Script controller para la opción presupuesto
 *
 * @author    Fidel Thompson Fonseca.
 * @link      https://www.edificiook.com
 * @copyright Copyright (c) 2011-2016 KND S.A.C (http://www.knd.pe)
 * @license   http://www.edificiook.com/license/comercial Software Comercial
 * 
 */

namespace Finanzas\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\Session\Container;

class PresupuestoController extends AbstractActionController
{
    public function indexAction()
    {   
        $session=new Container('User');
        $edificioId=$session->offsetGet('edificioId');
        $empresaId=$session->offsetGet('empId');

        $params=array(
            'edificioId'=>$edificioId,
            'empresaId'=>$empresaId
        );

        $presupuesto = $this->getServiceLocator()->get("Finanzas\Model\PresupuestoTable");
        $dataConceptos = $presupuesto->getConceptos($params);
        $dataEdificio=$presupuesto->getParametrosDeEdificio($edificioId);

        return new ViewModel(array("conceptos"=>$dataConceptos,'parametrosEdificio'=>$dataEdificio));
    }

    public function gridAction(){
        $request=$this->getRequest();
        $response = $this->getResponse();

        if($request->isPost()){
            $session=new Container('User');
            $edificioIdSelected=$session->offsetGet('edificioId');
            $empresaId=$session->offsetGet('empId');

            $params=$request->getPost();
            $params['edificioId']=$edificioIdSelected;
            $params['empresaId']=$empresaId;

            $presupuesto = $this->getServiceLocator()->get("Finanzas\Model\PresupuestoTable");
            $dataParaGrid = $presupuesto->getPresupuestoParaGrid($params);

            $response->setContent(\Zend\Json\Json::encode($dataParaGrid));          
        }else{
            $response->setContent(\Zend\Json\Json::encode(array(null)));
        }

        return $response;
    }

    public function saveAction(){
        $request=$this->getRequest();
        $response = $this->getResponse();
        if($request->isPost()){
            $session=new Container('User');
            $edificioIdSelected=$session->offsetGet('edificioId');
            $idUsuario=$session->offsetGet('userId');
            $empresaId=$session->offsetGet('empId');

            $params=$request->getPost();
            $params['edificioId']=$edificioIdSelected;
            $params['idUsuario']=$idUsuario;
            $params['empresaId']=$empresaId;

            $resultadoPresupuesto=array();
            $presupuesto= $this->getServiceLocator()->get("Finanzas\Model\PresupuestoTable");
            if($presupuesto->existeConceptoPresupuestado($params)){
                if(isset($params['chkActualizar'])){
                    $resultadoPresupuesto=$presupuesto->updatePresupuesto($params);
                }else{
                    $resultadoPresupuesto=array('tipo'=>'error',"mensaje"=>'Imposible registrar los datos. Este concepto ya cuenta con un presupuesto en el periodo seleccionado.');
                }
            }else{
                $resultadoPresupuesto= $presupuesto->addPresupuesto($params);
            }
            $response->setContent(\Zend\Json\Json::encode($resultadoPresupuesto));
        }else{
            $response->setContent(\Zend\Json\Json::encode(array()));
        }
        return $response;
    }

    public function conceptoAction(){
        $request=$this->getRequest();
        $response = $this->getResponse();
        if($request->isPost()){
            $session=new Container('User');
            $edificioIdSelected=$session->offsetGet('edificioId');
            $empresaId=$session->offsetGet('empId');
            
            $params=$request->getPost();
            $params['edificioId']=$edificioIdSelected;
            $params['empresaId']=$empresaId;

            $presupuesto = $this->getServiceLocator()->get("Finanzas\Model\PresupuestoTable");
            $dataConceptos = $presupuesto->getConceptos($params);
            
            $response->setContent(\Zend\Json\Json::encode($dataConceptos));

        }else{
            $response->setContent(\Zend\Json\Json::encode(array()));
        }
        return $response;
    }


}

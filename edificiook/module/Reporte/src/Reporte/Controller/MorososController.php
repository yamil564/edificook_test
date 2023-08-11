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

namespace Reporte\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\Session\Container;

class MorososController extends AbstractActionController
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


    public function indexAction(){
        //return $this->forward()->dispatch('Application\Controller\Index',array('action'=>'opcionpendientedecontruccion'));
        $morososTable = $this->getServiceLocator()->get("Reporte\Model\MorososTable");
        
        $params=array();
        $params['edificioId']=$this->edificioId;
        $params['empresaId']=$this->empresaId;

        //$dataParaGrid = $morososTable->dataParaGrid($params);
        $dataParaGrid=null;
        return new ViewModel(array('grid'=>$dataParaGrid));
    }

    public function gridAction(){
        $request=$this->getRequest();
        $response= $this->getResponse();
        $dataParaGrid=array();
        if($request->isPost()){
            $params=$request->getPost();
            $params['edificioId']=$this->edificioId;
            $params['empresaId']=$this->empresaId;

            $morososTable = $this->getServiceLocator()->get("Reporte\Model\MorososTable");
            $dataParaGrid = $morososTable->dataParaGrid($params);
            $response->setContent(\Zend\Json\Json::encode($dataParaGrid));
        }else{
            $response->setContent(\Zend\Json\Json::encode($dataParaGrid));
        }
        return $response;
    }

    public function descargarAction()
    {
        $morososTable = $this->getServiceLocator()->get("Reporte\Model\MorososTable");
        
        $request=$this->getRequest();
        $response= $this->getResponse();
        $data=array();
        
        if($request->isPost()){
            $params=$request->getPost();
            $params['edificioId']=$this->edificioId;
            $params['empresaId']=$this->empresaId;
            $params['usuarioId']=$this->usuarioId;

            $morososTable = $this->getServiceLocator()->get("Reporte\Model\MorososTable");
            $data=$morososTable->crearExcelMorosos($params);
            $response->setContent(\Zend\Json\Json::encode($data));
        }else{
            $response->setContent(\Zend\Json\Json::encode($data));
        }
        return $response;
    }
}

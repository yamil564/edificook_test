<?php

/**
 * <edificio></edificio>Ok (https://www.edificiook.com)
 * creado por: Jhon Gómez, 11/04/2016.
 * ultima modificacion por: Jhon Gómez
 * Fecha Modificacion: 13/04/2016.
 * Descripcion: Script controller para consumos
 *
 * @autor     Fidel J. Thompson
 * @link      https://www.edificiook.com
 * @copyright Copyright (c) 2011-2016 KND S.A.C (http://www.knd.pe)
 * @license   http://www.edificiook.com/license/comercial Software Comercial
 * 
*/

namespace Reporte\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\Db\Adapter\Adapter;
use Zend\Session\Container;
use Zend\Db\Sql\Sql;
use Zend\Db\Sql\Expression;
use Zend\View\Helper\AbstractHelper;
use Zend\Mail\Message;

class BalanceController extends AbstractActionController 
{
    private $idedificio=null;
    private $idusuario=null;
    private $nombreMes=array("01"=>"Enero","02"=>"Febrero","03"=>"Marzo","04"=>"Abril","05"=>"Mayo","06"=>"Junio","07"=>"Julio","08"=>"Agosto","09"=>"Septiembre","10"=>"Octubre","11"=>"Noviembre","12"=>"Diciembre");

    public function __construct(){
        $session=new Container('User');
        $this->idedificio = $session->offsetGet('edificioId');
        $this->idusuario = $session->offsetGet('userId');
    }
   
    public function indexAction()
    {   
        // return $this->forward()->dispatch('Application\Controller\Index',array('action'=>'opcionpendientedecontruccion'));
        //return new ViewModel();
        
        $balance = $this->getServiceLocator()->get("Reporte\Model\BalanceTable");
        $anioConceptoIngreso = $balance->anioConceptoIngreso();

        $valores = array(
            'periodo'=>$anioConceptoIngreso,
        );

        return new ViewModel($valores);
    }

    public function formatoAction()
    {
        $request=$this->getRequest();
        $edificioId=$this->idedificio;

        if($request->isPost()){
            
            $getMes=$this->params()->fromPost('mes');
            $getAnio=$this->params()->fromPost('anio');
            $getDia=$this->params()->fromPost('dia');
            
            if(empty($this->idEdificio)){
                $edificioId=$this->params()->fromPost('edificioId');
            }

            if(is_null($edificioId)){
                return $this->redirect()->toRoute('logout');
            }

            $validateKeySecurity=$this->getServiceLocator()->get('config')['configbase']['documentos']['keySecurityForRoute'];
            if($validateKeySecurity!=$this->params()->fromPost('key')){
                return $this->redirect()->toRoute('logout');
            }

        }else{
            return $this->redirect()->toRoute('logout');
        }

        $balance = $this->getServiceLocator()->get("Reporte\Model\BalanceTable");
        $balance->generar($getMes,$getAnio,$getDia,$edificioId);

        $view = new ViewModel();
        return $view->setTerminal(true);
    }

    public function reporteAction()
    {
        $request=$this->getRequest();
        $response = $this->getResponse();

        if($request->isPost()){
            $params=$request->getPost();
            /////////save auditoria////////
            $params['accion']='Generar Balance del Mes de '.$this->nombreMes[$params['mes']].' del '.$params['anio'];
            $this->saveAuditoria($params);
            ///////////////////////////////
            //atributos
            $keySecurityForRoute=$this->getServiceLocator()->get('config')['configbase']['documentos']['keySecurityForRoute'];
            $mes = ($params['mes']!='')?$params['mes']:'null';
            $anio = ($params['anio']!='')?$params['anio']:'null';
            $dia = ($params['dia']!='')?$params['dia']:'null';
            //new report
            $postParams="--post mes ".$mes;
            $postParams.=" --post anio ".$anio;
            $postParams.=" --post dia ".$dia;
            $postParams.=" --post edificioId ".$this->idedificio;
            $postParams.=" --post key ".$keySecurityForRoute;

            $time=time();
            $orientacionPdf="portrait";
            $urlPageToRender="https://www.edificiook.com/public/reporte/balance/formato";
            $time = time();
            $nombrePdfOut='balance-'.$time.'.pdf';
            $rutaPdfOut='public/temp/balance/'.$nombrePdfOut;
            //$piePagina = '--header-right "Página [page]" --header-font-size 9';

            exec('/usr/local/bin/wkhtmltopdf '.$postParams.' -L 15 -R 15 -T 14 -B 14 -O '.$orientacionPdf.' "'.$urlPageToRender.'" '.$rutaPdfOut.' 2>&1');

            $response->setContent(\Zend\Json\Json::encode(array('mensaje'=>'success','file'=>$nombrePdfOut)));
        }else{
            $response->setContent(\Zend\Json\Json::encode(array(null)));
        }

        return $response;
    }

    private function saveAuditoria($params)
    {   
        $adapter=$this->getServiceLocator()->get('Zend\Db\Adapter\Adapter');
        $sql=new Sql($adapter);
        $insert = $sql->insert('auditoria');
        $data = array(
            'usu_in_cod'=> $this->idusuario, //idusuario
            'edi_in_cod'=> $this->idedificio, //idedificio
            'aud_opcion'=> 'Reportes > Balance', //ruta
            'aud_accion'=> $params['accion'], //accion
            'aud_fecha'=> date('Y-m-d H:i:s'), //fecha y hora
            'aud_ip'=> $_SERVER['REMOTE_ADDR'], //ip publica
            'aud_tabla'=> '', //tabla
            'aud_in_numra'=> '', //registro afectado
            'aud_info_adi'=> $_SERVER['HTTP_USER_AGENT'] //agente
        );
        $insert->values($data);
        $sql->prepareStatementForSqlObject($insert)->execute()->count();
    }

}
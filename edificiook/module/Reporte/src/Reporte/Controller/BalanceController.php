<?php

/**
 * <edificio></edificio>Ok (https://www.edificiook.com)
 * creado por: Jhon Gómez, 11/04/2016.
 * ultima modificacion por: Meler Carranza
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
    private $meses=["","Enero","Febrero","Marzo","Abril","Mayo","Junio","Julio","Agosto","Septiembre","Octubre","Noviembre","Diciembre"];

    public function __construct(){
        $session=new Container('User');
        $this->idedificio = $session->offsetGet('edificioId');
        $this->idusuario = $session->offsetGet('userId');
    }


    public function indexAction()
    {
        $balance = $this->getServiceLocator()->get("Reporte\Model\BalanceTable");
        $listYearsInIngreso = $balance->getYearsInIngreso($this->idedificio);

        $valores = array(
            'periodos'=>$listYearsInIngreso,
        );

        return new ViewModel($valores);
    }

    public function templateAction(){
        $request=$this->getRequest();

        $params=[];
        $tipo="";

        if($request->isPost()){
            $params['mes']=$this->params()->fromPost('mes');
            $params['year']=$this->params()->fromPost('anio');
            $params['edificioId']=$this->params()->fromPost('edificioId');
            $params['tipo']=$this->params()->fromPost('tipo');
            
            $validateKeySecurity=$this->getServiceLocator()->get('config')['configbase']['documentos']['keySecurityForRoute'];

            if($validateKeySecurity!=$this->params()->fromPost('key') && !empty($params['edificioId']) ){
                return $this->redirect()->toRoute('logout');
            }
        }else{
            return $this->redirect()->toRoute('logout');
        }

        $balanceModel = $this->getServiceLocator()->get("Reporte\Model\BalanceTable");
        $balanceData=$balanceModel->getDataBalance($params);

        $view = new ViewModel(['data_balance'=>$balanceData,"meses"=>$this->meses,"tipo_balance"=>$params['tipo'] ]);
        return $view->setTerminal(true);
    }

    public function reporteAction()
    {
        $request=$this->getRequest();
        $response = $this->getResponse();

        if($request->isPost()){
            $params=$request->getPost();
            
            /////////save auditoria////////
            $params['accion']='Generar Balance del Mes de '.$this->meses[intval($params['mes']) ].' del '.$params['anio'];
            $this->saveAuditoria($params);
            ///////////////////////////////

            
            //atributos
            $keySecurityForRoute=$this->getServiceLocator()->get('config')['configbase']['documentos']['keySecurityForRoute'];
            $mes = ($params['mes']!='')?$params['mes']:'null';
            $anio = ($params['anio']!='')?$params['anio']:'null';
            $tipo=($params['tipo'] !='')? $params['tipo']:'resumido';

            //Parametros POST
            $postParams="--post mes ".$mes;
            $postParams.=" --post anio ".$anio;
            $postParams.=" --post edificioId ".$this->idedificio;
            $postParams.=" --post tipo ".$tipo;
            $postParams.=" --post key ".$keySecurityForRoute;

            
            $orientacionPdf="portrait";
            $urlPageToRender="http://isoft3.com/reporte/balance/template";
            
            $nombrePdfOut=time().'.pdf';
			// $rutaPdfOut='public/temp/balance/'.$nombrePdfOut;
			$rutaPdfOut='/var/www/html/temp/balance/'.$nombrePdfOut;
			//$piePagina = '--header-right "Página [page]" --header-font-size 9';

            exec('/usr/local/bin/wkhtmltopdf '.$postParams.' -L 5 -R 5 -T 8 -B 14 -O '.$orientacionPdf.' "'.$urlPageToRender.'" '.$rutaPdfOut.' 2>&1');

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
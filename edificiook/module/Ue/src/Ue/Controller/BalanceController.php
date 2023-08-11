<?php
/**
 * edificioOk (https://www.edificiook.com)
 * creado por: Meler Carranza, 17/08/2016.
 * ultima modificacion por: Meler Carranza
 * Fecha Modificacion: 17/03/2016.
 * Descripcion: Script controller para el reporte de Balance usuario externo
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
use Zend\Db\Sql\Sql;

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
        //return $this->forward()->dispatch('Application\Controller\Index',array('action'=>'opcionpendientedecontruccion'));
        //return new ViewModel();

        $balance = $this->getServiceLocator()->get("Reporte\Model\BalanceTable");
        $listYearsInIngreso = $balance->getYearsInIngreso($this->idedificio);

        $valores = array(
            'periodos'=>$listYearsInIngreso,
        );

        return new ViewModel($valores);
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
			$postParams.=" --post typeRender pdf ";
            $postParams.=" --post edificioId ".$this->idedificio;
            $postParams.=" --post tipo ".$tipo;
            $postParams.=" --post key ".$keySecurityForRoute;

            
            $orientacionPdf="portrait";
            // $urlPageToRender="http://isoft3.com/public/reporte/balance/template";
            $urlPageToRender="http://172.17.0.1/reporte/balance/template";
            $nombrePdfOut=time().'.pdf';
            $rutaPdfOut='public/temp/balance/'.$nombrePdfOut;
            //$piePagina = '--header-right "Página [page]" --header-font-size 9';
// var_dump('command: /usr/local/bin/wkhtmltopdf '.$postParams.' -L 5 -R 5 -T 8 -B 14 -O '.$orientacionPdf.' "'.$urlPageToRender.'" '.$rutaPdfOut.' 2>&1');
// exit();
			exec('/usr/local/bin/wkhtmltopdf '.$postParams.' -L 5 -R 5 -T 8 -B 14 -O '.$orientacionPdf.' "'.$urlPageToRender.'" '.$rutaPdfOut.' 2>&1');
			
			// echo $rpt;


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
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

class CuotasController extends AbstractActionController
{

    private $nombreMes=array("1"=>"Enero","2"=>"Febrero","3"=>"Marzo","4"=>"Abril","5"=>"Mayo","6"=>"Junio","7"=>"Julio","8"=>"Agosto","9"=>"Septiembre","10"=>"Octubre","11"=>"Noviembre","12"=>"Diciembre");

	public function __construct()
	{
		$session=new Container('User');
        $this->idEdificio = $session->offsetGet('edificioId');
        $this->idEmpresa = $session->offsetGet('empId');
        $this->idUsuario = $session->offsetGet('userId');
	}

	public function indexAction()
	{
		$pagos = $this->getServiceLocator()->get("Ue\Model\CuotasTable");
		$listarUnidad = $pagos->listarUnidadPorPropietario($this->idEdificio, $this->idUsuario);
		$valores = array(
			'listarUnidad'=>$listarUnidad
		);
		return new ViewModel($valores);
	}

	public function gridAction()
	{
		$request=$this->getRequest();
        $response = $this->getResponse();

        if($request->isPost()){
            $params=$request->getPost();
            $params['idUsuario'] = $this->idUsuario;
            $params['idEdificio'] = $this->idEdificio;
            $pagos = $this->getServiceLocator()->get("Ue\Model\CuotasTable");
            $pagos = $pagos->cargarGrid($params);
            $response->setContent(\Zend\Json\Json::encode($pagos));
        }else{
            $response->setContent(\Zend\Json\Json::encode(array(null)));
        }

        return $response;
	}

	public function infoAction()
	{
		$request=$this->getRequest();
        $response = $this->getResponse();

        if($request->isPost()){
            $params=$request->getPost();
            $pagos = $this->getServiceLocator()->get("Ue\Model\CuotasTable");
            $pagos = $pagos->detallesDePago($params);
            $response->setContent(\Zend\Json\Json::encode($pagos));
        }else{
            $response->setContent(\Zend\Json\Json::encode(array(null)));
        }

        return $response;
	}

    public function crearPdfAction()
    {
        $request=$this->getRequest();
        $response = $this->getResponse();

        if($request->isPost()){
            $params=$request->getPost();
            $typePDF = $params['type'];
            $month = $params['month'];
            $year = $params['year'];
            $admin = $params['admin'];
            $idrow = $params['idrow'];
            $filterx = $params['filterx'];
        
            $baseUrl = $this->getRequest()->getUri()->getScheme().'://'.$this->getRequest()->getUri()->getHost().$this->request->getBasePath();

            $keySecurityForRoute=$this->getServiceLocator()->get('config')['configbase']['documentos']['keySecurityForRoute'];

            /////////save auditoria////////
            //$params['accion']='Estado de Cuenta del Mes de '.$this->nombreMes[$month].' del '.$year.' ('.$typePDF.')';
            //$this->saveAuditoria($params);
            ///////////////////////////////

            $postParams="--post month ".$month;
            $postParams.=" --post year ".$year;
            $postParams.=" --post admin ".$admin;
            $postParams.=" --post edificioId ".$this->idEdificio;   
            $postParams.=" --post typeRender pdf";
            $postParams.=" --post idrow ".$idrow;
            $postParams.=" --post key ".$keySecurityForRoute;

            $orientacionPdf="landscape";
            $urlPageToRender="";
            
            if('estandar'==$typePDF){
                $urlPageToRender="https://www.edificiook.com/public/reporte/estado-cuenta/recibo-estandar"; 
            }else if('detallado'==$typePDF){
                $orientacionPdf='portrait';
                $urlPageToRender="https://www.edificiook.com/public/reporte/estado-cuenta/recibo-detallado";
            }else if('impresion'==$typePDF){
                $urlPageToRender="https://www.edificiook.com/public/reporte/estado-cuenta/recibo-impresion";
            }else{
                $response->setContent(\Zend\Json\Json::encode(array('message'=>'error','cuerpo'=>'no se creo el pdf, ocurrio un problema en el servidor.')));
            }

            $nombrePdfOut=$this->idUsuario.'-recibo-'.$typePDF."-".time().".pdf";
            $rutaPdfOut='public/temp/recibo-eecc/'.$nombrePdfOut;
    
            exec('/usr/local/bin/wkhtmltopdf '.$postParams.' -L 5 -R 5 -T 5 -B 5 -O '.$orientacionPdf.' "'.$urlPageToRender.'" '.$rutaPdfOut.' 2>&1');
            
            $rutaPDF = 'temp/recibo-eecc/'.$nombrePdfOut;
            
            if(file_exists($rutaPdfOut)){
                $response->setContent(\Zend\Json\Json::encode(array('message'=>'success','cuerpo'=>'pdf creado correctamente.','ruta'=>$rutaPDF,'nombrePdf'=>time().'.pdf')));
            }else{
                $response->setContent(\Zend\Json\Json::encode(array('message'=>'error','cuerpo'=>'no se creo el pdf, ocurrio un problema en el servidor.')));
            }

        }else{
            $response->setContent(\Zend\Json\Json::encode(array('message'=>'error')));
        }

        return $response;

    }


}

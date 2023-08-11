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

use Zend\Mail;
use Zend\Mail\Message;
use Zend\Mail\Transport\Smtp as SmtpTransport;
use Zend\Mime\Message as MimeMessage;
use Zend\Mime\Part as MimePart;
use Zend\Mail\Transport\SmtpOptions;
use Zend\Mail\Transport\Sendmail as SendmailTransport;

class EstadoCuentaController extends AbstractActionController 
{
    private $idEdificio=null;
    private $idUsuario=null;
    private $nombreMes=array("1"=>"Enero","2"=>"Febrero","3"=>"Marzo","4"=>"Abril","5"=>"Mayo","6"=>"Junio","7"=>"Julio","8"=>"Agosto","9"=>"Septiembre","10"=>"Octubre","11"=>"Noviembre","12"=>"Diciembre");

    public function __construct(){
        $session=new Container('User');
        $this->idEdificio = $session->offsetGet('edificioId');
        $this->idUsuario = $session->offsetGet('userId');
    }
   
    public function indexAction()
    {   
        return new ViewModel(array(null));
    }

    public function gridAction()
    {
        $request=$this->getRequest();
        $response = $this->getResponse();

        if($request->isPost()){
            $params=$request->getPost();
            $estado = $this->getServiceLocator()->get("Reporte\Model\EstadoCuentaTable");
            $estado = $estado->listarEstado($params,$this->idEdificio,$this->idUsuario);
            $response->setContent(\Zend\Json\Json::encode($estado));
        }else{
            $response->setContent(\Zend\Json\Json::encode(array(null)));
        }

        return $response;
    }



    public function reciboEstandarAction()
    {   // var_dump($this->params()->fromPost());
    	// array(6) { ["admin"]=> string(1) "1" ["idrow"]=> string(5) "10879" ["typeRender"]=> string(4) "html" ["month"]=> string(1) "1" ["year"]=> string(4) "2018" ["type"]=> string(8) "estandar" }
    	// array(6) { ["admin"]=> string(1) "1" ["idrow"]=> string(5) "12285" ["typeRender"]=> string(4) "html" ["month"]=> string(1) "1" ["year"]=> string(4) "2018" ["type"]=> string(8) "estandar" }
        $request=$this->getRequest();
        $edificioId=$this->idEdificio;

        if($request->isPost()){
            
            $month = (int)$this->params()->fromPost('month');
            $year = $this->params()->fromPost('year');
            $admin = $this->params()->fromPost('admin');
            $idrow = $this->params()->fromPost('idrow');
            $typeRender=$this->params()->fromPost('typeRender');
            $typePDF=$this->params()->fromPost('type');
            
            if(empty($this->idEdificio)){
                $edificioId=$this->params()->fromPost('edificioId');
            }

            if(is_null($edificioId)){
                return $this->redirect()->toRoute('logout');
            }

            if($typeRender=='pdf'){
                $validateKeySecurity=$this->getServiceLocator()->get('config')['configbase']['documentos']['keySecurityForRoute'];
                if($validateKeySecurity!=$this->params()->fromPost('key')){
                    return $this->redirect()->toRoute('logout');
                }
            }

        }else{
            return $this->redirect()->toRoute('logout');
        }
        
        $filterx = '';
        if($admin=='') $admin = '2';

		$baseUrl = $this->getRequest()->getUri()->getScheme().'://'.$this->getRequest()->getUri()->getHost().$this->request->getBasePath();

        echo '<base href='.$baseUrl."/".'></base>';
        echo '<meta charset="utf-8">';
        $estado = $this->getServiceLocator()->get("Reporte\Model\EstadoCuentaTable");
        $result = $estado->getReciboEstandar3($edificioId,$month,$year,$typeRender,$idrow,$filterx,$admin,'ERROR',$baseUrl);

        // var_dump($result);
        // exit();

        $view = new ViewModel(array(
            'result'=>$result,
                'month'=>$month,
                'year'=>$year,
                'idrow'=>$idrow,
                'filterx'=>$filterx,
                'admin'=>$admin,
                'typeRender'=>$typeRender,
                'type'=>$typePDF,
        ));
        return $view->setTerminal(true);
    }

    public function reciboEstandar2Action()
    {

        $type = $this->params()->fromPost('type');
        $month = $this->params()->fromPost('month');
        $year = $this->params()->fromPost('year');
        $admin = $this->params()->fromPost('admin');
        $idrow = $this->params()->fromPost('idrow');
        $filterx = '';
        if($admin=='') $admin = '2';

        $baseUrl = $this->getRequest()->getUri()->getScheme().'://'.$this->getRequest()->getUri()->getHost().$this->request->getBasePath();
        echo '<base href='.$baseUrl."/".'></base>';
        echo '<meta charset="utf-8">';
        $estado = $this->getServiceLocator()->get("Reporte\Model\EstadoCuentaTable");


        $month = 7;
        $year = 2016;
        $type = 'estandar';
        $idrow = '3991,3993,3994,3995,3996,3997,3998,3999,4000';
        $admin = 2;
        $baseUrl = 'https://www.edificiook.com/public';
        $this->idEdificio = 43;


        $result = $estado->getReciboEstandar2($this->idEdificio,$month,$year,$type,$idrow,$filterx,$admin,'ERROR',$baseUrl);

        $view = new ViewModel(array(
            'result'=>$result,
            'month'=>$month,
            'year'=>$year,
            'type'=>$type,
            'idrow'=>$idrow,
            'filterx'=>$filterx,
            'admin'=>$admin
        ));
        return $view->setTerminal(true);
    }

    public function reciboDetalladoAction()
    {
        $type = $this->params()->fromPost('type');
        $month = $this->params()->fromPost('month');
        $year = $this->params()->fromPost('year');
        $admin = $this->params()->fromPost('admin');
        $idrow = $this->params()->fromPost('idrow');
        $filterx = '';
        if($admin=='') $admin = '2';

        $baseUrl = $this->getRequest()->getUri()->getScheme().'://'.$this->getRequest()->getUri()->getHost().$this->request->getBasePath();
        echo '<base href='.$baseUrl."/".'></base>';
        echo '<meta charset="utf-8">';
        echo '<div class="content-detallado" style="width: 55%;margin: 0 auto;">';
        $estado = $this->getServiceLocator()->get("Reporte\Model\EstadoCuentaTable");
        $estado->getReciboDetallado($this->idEdificio,$month,$year,$type,$idrow,$filterx,$admin,$baseUrl);
        echo '</div>';

        $view = new ViewModel(array(            
            'month'=>$month,
            'year'=>$year,
            'type'=>$type,
            'idrow'=>$idrow,
            'filterx'=>$filterx,
            'admin'=>$admin
        ));
        return $view->setTerminal(true);
    }

    public function reciboImpresionAction()
    {

        $request=$this->getRequest();
        $edificioId=$this->idEdificio;

        if($request->isPost()){
            
            $month = $this->params()->fromPost('month');
            $year = $this->params()->fromPost('year');
            $admin = $this->params()->fromPost('admin');
            $idrow = $this->params()->fromPost('idrow');
            $typeRender=$this->params()->fromPost('typeRender');
            $typePDF=$this->params()->fromPost('type');
            
            if(empty($this->idEdificio)){
                $edificioId=$this->params()->fromPost('edificioId');
            }

            if(is_null($edificioId)){
                return $this->redirect()->toRoute('logout');
            }

            if($typeRender=='pdf'){
                $validateKeySecurity=$this->getServiceLocator()->get('config')['configbase']['documentos']['keySecurityForRoute'];
                if($validateKeySecurity!=$this->params()->fromPost('key')){
                    return $this->redirect()->toRoute('logout');
                }
            }

        }else{
            return $this->redirect()->toRoute('logout');
        }
        
        $filterx = '';
        $admin = 0; //impresion

        $baseUrl = $this->getRequest()->getUri()->getScheme().'://'.$this->getRequest()->getUri()->getHost().$this->request->getBasePath();

        echo '<base href='.$baseUrl."/".'></base>';
        echo '<meta charset="utf-8">';
        $estado = $this->getServiceLocator()->get("Reporte\Model\EstadoCuentaTable");
        $result = $estado->getReciboEstandar3($edificioId,$month,$year,$typeRender,$idrow,$filterx,$admin,'ERROR',$baseUrl);

        $view = new ViewModel(array(
            'result'=>$result,
            'month'=>$month,
            'year'=>$year,
            'idrow'=>$idrow,
            'filterx'=>$filterx,
            'admin'=>$admin,
            'typeRender'=>$typeRender,
            'type'=>$typePDF,
        ));
        return $view->setTerminal(true);

    }

    public function crearPdf2Action()
    {
        $request=$this->getRequest();
        $response = $this->getResponse();

        if($request->isPost()){
            $params=$request->getPost();
            $type = $params['type'];
            $month = $params['month'];
            $year = $params['year'];
            $admin = $params['admin'];
            $idrow = $params['idrow'];
            $filterx = $params['filterx'];
            $admin = $params['admin'];
            $typeMail = $params['typeMail'];
            $correo = $params['correo'];

            $baseUrl = $this->getRequest()->getUri()->getScheme().'://'.$this->getRequest()->getUri()->getHost().$this->request->getBasePath();

            ob_start();
            $estado = $this->getServiceLocator()->get("Reporte\Model\EstadoCuentaTable");
            if($type == 'estandar') $estado->getReciboEstandar($this->idEdificio,$month,$year,$type,$idrow,$filterx,$admin,'RETURN',$baseUrl);
            else if($type == 'detallado') $estado->getReciboDetallado($this->idEdificio,$month,$year,$type,$idrow,$filterx,$admin,$baseUrl); 
            else $estado->getReciboEstandar($this->idEdificio,$month,$year,$type,$idrow,$filterx,0,'RETURN',$baseUrl);
            $content = ob_get_clean();
            $file = getcwd().'/public/temp/recibo-eecc/'.$this->idUsuario.'-recibo-'.$type.'.pdf';
            $ruta = 'temp/recibo-eecc/'.$this->idUsuario.'-recibo-'.$type.'.pdf';

            try {
                $html2pdf = new \HTML2PDF('P','A4', 'fr');
                $html2pdf->pdf->SetDisplayMode('fullpage');
                $html2pdf->writeHTML($content, isset($_GET['vuehtml']));
                $html2pdf->Output($file,'F');

                if(file_exists($file)){

                    /////////save auditoria////////
                    $params['accion']='Estado de Cuenta del Mes de '.$this->nombreMes[$month].' del '.$year.' ('.$type.')';
                    $this->saveAuditoria($params);
                    ///////////////////////////////

                    $response->setContent(\Zend\Json\Json::encode(array('message'=>'success','cuerpo'=>'pdf creado correctamente.','ruta'=>$ruta)));
                }else{
                    $response->setContent(\Zend\Json\Json::encode(array('message'=>'error','cuerpo'=>'no se creo el pdf, ocurrio un problema en el servidor.')));
                }

            } catch (\HTML2PDF_exception $e) {
                echo $e;
                exit();
            }

        }else{
            $response->setContent(\Zend\Json\Json::encode(array('message'=>'error')));
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
            $params['accion']='Estado de Cuenta del Mes de '.$this->nombreMes[$month].' del '.$year.' ('.$typePDF.')';
            $this->saveAuditoria($params);
            ///////////////////////////////

            $postParams="--post  month ".$month;
            $postParams.=" --post year ".$year;
            $postParams.=" --post admin ".$admin;
            $postParams.=" --post edificioId ".$this->idEdificio;   
            $postParams.=" --post typeRender pdf";
            $postParams.=" --post idrow ".$idrow;
            $postParams.=" --post key ".$keySecurityForRoute;

            $orientacionPdf="landscape";
            $urlPageToRender="";
            
            if('estandar'==$typePDF){
				 //$urlPageToRender="https://www.edificiook.com/public/reporte/estado-cuenta/recibo-estandar";
				 $urlPageToRender="http://isoft3.com/reporte/estado-cuenta/recibo-estandar";
            }else if('detallado'==$typePDF){
                $orientacionPdf='portrait';
				//$urlPageToRender="https://www.edificiook.com/public/reporte/estado-cuenta/recibo-detallado";
				$urlPageToRender="http://isoft3.com/reporte/estado-cuenta/recibo-detallado";
            }else if('impresion'==$typePDF){
				//$urlPageToRender="https://www.edificiook.com/public/reporte/estado-cuenta/recibo-impresion";
				$urlPageToRender="http://isoft3.com/reporte/estado-cuenta/recibo-impresion";
            }else{
                $response->setContent(\Zend\Json\Json::encode(array('message'=>'error','cuerpo'=>'no se creo el pdf, ocurrio un problema en el servidor.')));
            }

            $nombrePdfOut=$this->idUsuario.'-recibo-'.$typePDF."-".time().".pdf";
            $rutaPdfOut='/var/www/html/temp/recibo-eecc/'.$nombrePdfOut;
    
            exec('/usr/local/bin/wkhtmltopdf '.$postParams.' -L 5 -R 5 -T 5 -B 5 -O '.$orientacionPdf.' "'.$urlPageToRender.'" '.$rutaPdfOut.' 2>&1');
            
            $rutaPDF = 'temp/recibo-eecc/'.$nombrePdfOut;
            
            if(file_exists($rutaPdfOut)){
                $response->setContent(\Zend\Json\Json::encode(array('message'=>'success','cuerpo'=>'pdf creado correctamente.','ruta'=>$rutaPDF,'nombrePdf'=>time().'pdf')));
            }else{
                $response->setContent(\Zend\Json\Json::encode(array('message'=>'error','cuerpo'=>'no se creo el pdf, ocurrio un problema en el servidor.')));
            }

        }else{
            $response->setContent(\Zend\Json\Json::encode(array('message'=>'error')));
        }

        return $response;

    }

    public function crearPdfMailAction()
    {
        $request=$this->getRequest();
        $response = $this->getResponse();

        if($request->isPost()){
            $params=$request->getPost();

            $params['accion'] = '';

            if($params['typeMail'] == 1){ // estado cuenta (1)
                $params['accion'] = 'Envio de Correo (Estado de Cuenta)';
                $result = $this->mail($params);
                $response->setContent(\Zend\Json\Json::encode($result));
            }else if($params['typeMail'] == 2){ // balance (2)
                $params['accion'] = 'Envio de Correo (Balance)';
                $result = $this->balance($this->idEdificio);
                $response->setContent(\Zend\Json\Json::encode($result));
            }else{ // ambos (3)
                $params['accion'] = 'Envio de Correo (Estado de Cuenta y Balance)';
                $estadoCuenta = $this->mail($params);
                $balance = $this->balance($this->idEdificio);
                $result = array_merge($estadoCuenta,$balance);
                $response->setContent(\Zend\Json\Json::encode($result));
            }
            /////////save auditoria////////
            $this->saveAuditoria($params);
            ///////////////////////////////
        }else{
            $response->setContent(\Zend\Json\Json::encode(array('message'=>'error')));
        }   

        return $response;

    }

    public function balance($idEdificio)
    {
        $keySecurityForRoute=$this->getServiceLocator()->get('config')['configbase']['documentos']['keySecurityForRoute'];
        $mes = 'null';
        $anio = 'null';
        $tipo= 'detallado';

        $postParams="--post mes ".$mes;
        $postParams.=" --post anio ".$anio;
        $postParams.=" --post edificioId ".$idEdificio;
        $postParams.=" --post key ".$keySecurityForRoute;
        $postParams.=" --post tipo ".$tipo;

        $time=time();
        $orientacionPdf="portrait";
		// $urlPageToRender="https://www.edificiook.com/public/reporte/balance/template";
		$urlPageToRender="http://isoft3.com/reporte/balance/template";
        $time = time();
        $nombrePdfOut=$this->idUsuario.'-balance.pdf';
        $rutaPdfOut='/var/www/html/temp/recibo-eecc/'.$nombrePdfOut;

        exec('/usr/local/bin/wkhtmltopdf '.$postParams.' -L 15 -R 15 -T 14 -B 14 -O '.$orientacionPdf.' "'.$urlPageToRender.'" '.$rutaPdfOut.' 2>&1');

        return array(
            'message'=>'success',
            'fileBalance'=>$nombrePdfOut
        );

    }

    public function mail($params)
    {
        $baseUrl = $this->getRequest()->getUri()->getScheme().'://'.$this->getRequest()->getUri()->getHost().$this->request->getBasePath();
        $correo = $params['correo'];
        $idrow = $params['idrow'];
        $month = $params['mmonth'];
        $year = $params['myear'];
        $type = '';
        $filterx = '';

        if(isset($correo)) {
          $admin='4';
            if (isset($idrow)) {
              //$codsUni = implode(",", $idrow);
              //$codPar = explode(",", $codsUni);
              foreach($idrow  as $idUnidad){

                $keySecurityForRoute=$this->getServiceLocator()->get('config')['configbase']['documentos']['keySecurityForRoute'];
                $postParams="--post month ".$month;
                $postParams.=" --post year ".$year;
                $postParams.=" --post edificioId ".$this->idEdificio;   
                $postParams.=" --post typeRender pdf";
                $postParams.=" --post idrow ".$idUnidad;
                $postParams.=" --post key ".$keySecurityForRoute;

                $orientacionPdf="landscape";
                $urlPageToRender="http://isoft3.com/reporte/estado-cuenta/recibo-estandar";
                $nombrePdfOut=$this->idUsuario.'-'.$idUnidad.".pdf";
				// $rutaPdfOut='public/temp/recibo-eecc/'.$nombrePdfOut;
				$rutaPdfOut='/var/www/html/temp/recibo-eecc/'.$nombrePdfOut;

				exec('/usr/local/bin/wkhtmltopdf '.$postParams.' -L 5 -R 5 -T 5 -B 5 -O '.$orientacionPdf.' "'.$urlPageToRender.'" '.$rutaPdfOut.' 2>&1');
              }
            }
        }

        return array(
            'message'=>'success'
        );

    }

    public function correoAction()
    {
        $request=$this->getRequest();
        $response = $this->getResponse();

        if($request->isPost()){
            $params=$request->getPost();
            $params['idEdificio'] = $this->idEdificio;
            $estado = $this->getServiceLocator()->get("Reporte\Model\EstadoCuentaTable");
            $estado = $estado->mail($params,$this->idUsuario);
            $response->setContent(\Zend\Json\Json::encode($estado));
        }else{
            $response->setContent(\Zend\Json\Json::encode(array(null)));
        }
        return $response;
    }

    public function testJhonAction()
    {
        exec('/usr/local/bin/wkhtmltopdf -L 5 -R 5 -T 5 -B 5 -O landscape https://www.edificiook.com/public/reporte/estado-cuenta/recibo-estandar2 public/file/ok.pdf 2>&1');
        $view = new ViewModel();
        return $view->setTerminal(true);
    }


    private function saveAuditoria($params)
    {   
        $adapter=$this->getServiceLocator()->get('Zend\Db\Adapter\Adapter');
        $sql=new Sql($adapter);
        $insert = $sql->insert('auditoria');
        $data = array(
            'usu_in_cod'=> $this->idUsuario, //idusuario
            'edi_in_cod'=> $this->idEdificio, //idedificio
            'aud_opcion'=> 'Reportes > Estado de cuenta', //ruta
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
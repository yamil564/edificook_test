<?php
/**
 * edificioOk (https://www.edificiook.com).
 * creado por: Meler Carranza, 29/04/2016.
 * ultima modificacion por: Meler Carranza.
 * Fecha Modificacion: 29/04/2016.
 * Descripcion: Script controller para la opción egreso.
 *
 * @author    Fidel Thompson Fonseca.
 * @link      https://www.edificiook.com.
 * @copyright Copyright (c) 2011-2016 KND S.A.C (http://www.knd.pe).
 * @license   http://www.edificiook.com/license/comercial Software Comercial.
 */

namespace Finanzas\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\Session\Container;

class EgresoController extends AbstractActionController
{	
	private $usuarioId=null;
	private $empresaId=null;
	private $edificioId=null;


	public function __construct(){
		  $session=new Container('User');
		  $this->usuarioId=$session->offsetGet('userId');
		  $this->empresaId=$session->offsetGet('empId');
		  $this->edificioId=$session->offsetGet('edificioId');
	}

    public function indexAction()
    {   
        $params=array(
            'edificioId'=>$this->edificioId,
            'empresaId'=>$this->empresaId
        );

        $egresoTable = $this->getServiceLocator()->get("Finanzas\Model\EgresoTable");
        $dataConceptos = $egresoTable->getConceptos($params);
        $dataProveedor=$egresoTable->getProveedores($params);

        return new ViewModel(array("conceptos"=>$dataConceptos,"proveedor"=>$dataProveedor));
    }

    public function provisionadoAction(){
         $params=array(
            'edificioId'=>$this->edificioId,
            'empresaId'=>$this->empresaId
        );

        $egresoTable = $this->getServiceLocator()->get("Finanzas\Model\EgresoTable");
        $dataConceptos = $egresoTable->getConceptos($params);
        $dataProveedor=$egresoTable->getProveedores($params);

        return new ViewModel(array("conceptos"=>$dataConceptos,"proveedor"=>$dataProveedor));
    }

    public function pendienteDeAprobacionAction(){
         $params=array(
            'edificioId'=>$this->edificioId,
            'empresaId'=>$this->empresaId
        );

        $egresoTable = $this->getServiceLocator()->get("Finanzas\Model\EgresoTable");
        $dataConceptos = $egresoTable->getConceptos($params);
        $dataProveedor=$egresoTable->getProveedores($params);

        return new ViewModel(array("conceptos"=>$dataConceptos,"proveedor"=>$dataProveedor));
    }

    public function pendienteDePagoAction(){
         $params=array(
            'edificioId'=>$this->edificioId,
            'empresaId'=>$this->empresaId
        );

        $egresoTable = $this->getServiceLocator()->get("Finanzas\Model\EgresoTable");
        $dataConceptos = $egresoTable->getConceptos($params);
        $dataProveedor=$egresoTable->getProveedores($params);

        return new ViewModel(array("conceptos"=>$dataConceptos,"proveedor"=>$dataProveedor));
    }
    public function pagadoAction(){
         $params=array(
            'edificioId'=>$this->edificioId,
            'empresaId'=>$this->empresaId
        );

        $egresoTable = $this->getServiceLocator()->get("Finanzas\Model\EgresoTable");
        $dataConceptos = $egresoTable->getConceptos($params);
        $dataProveedor=$egresoTable->getProveedores($params);

        return new ViewModel(array("conceptos"=>$dataConceptos,"proveedor"=>$dataProveedor));
    }

    public function gridAction(){
        $request=$this->getRequest();
        $response = $this->getResponse();
        $dataParaGrid=array();
        if($request->isPost()){
            $params=$request->getPost();
            $params['edificioId']=$this->edificioId;
            $params['empresaId']=$this->empresaId;
   			
   			$egresoTable=$this->getServiceLocator()->get('Finanzas\Model\EgresoTable');
            $query=isset($params['query'])?$params['query']:'';
            if($query==''){
                $dataParaGrid=$egresoTable->egresosParaGridAnual($params);
            }else{
                $dataParaGrid=$egresoTable->egresosParaGrid($params);
            }

            $response->setContent(\Zend\Json\Json::encode($dataParaGrid));          
        }else{
            $response->setContent(\Zend\Json\Json::encode(array('result'=>'error','mensaje'=>'petición no autorizada.')));
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
                case 'dataegreso':
                    $data = $egresoTable->detalleDeEgreso($params['egresoId']);
                    break;
                case 'detallepago':
                    $data=$egresoTable->getDetallePago($params['egresoId']);
                    break;
                case 'egresoParcialParaGridBanco':
                    $data=$egresoTable->egresoParcialParaGridBanco($params);
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

    public function saveAction(){
        $request=$this->getRequest();
        $response = $this->getResponse();

        if($request->isPost()){


            $params=$request->getPost();
            $params['usuarioId']=$this->usuarioId;
            $params['edificioId']=$this->edificioId;

            $egresoTable=$this->getServiceLocator()->get('Finanzas\Model\EgresoTable');
            $query=isset($params['query'])?$params['query']:'';
            $data=array();
            switch ($query) {
                case 'procesarEgreso':
                    $data=$egresoTable->procesarEgreso($params);
                    break;
                case 'aprobarEgreso':
                    $data=$egresoTable->aprobarEgreso($params);
                    break;
                case 'registrarPago':
                    $data=$egresoTable->registrarPago($params);
                    break;
                default:
                    $data=$this->saveEgreso($params);
                    break;
            }
            $response->setContent(\Zend\Json\Json::encode($data));
        }else{
            $response->setContent(\Zend\Json\Json::encode(array('result'=>'error','mensaje'=>'petición no autorizada.')));
        }
        return $response;
    }


    private function saveEgreso($params){
        $ruta_recibo_digital = getcwd().'/public/file/egreso/';

        $recibo_digital=null;
        if($_FILES["recibo-digital"]['error']==0){
            $recibo_digital = $_FILES["recibo-digital"];
            $nombre_pdf = $recibo_digital["name"];
            $tipo = $recibo_digital["type"];
            $ruta_temp_recibo_digital= $recibo_digital["tmp_name"];
            $size = $recibo_digital["size"];

            if($tipo!='application/pdf'){
                $mensaje=array("tipo"=>'error','mensaje'=>"El recibo digital debera ser únicamente en formato .PDF");
                return $mensaje;
            }

            if($size>3698688){
                $mensaje=array("tipo"=>"error",'mensaje'=>"El archivo superó el tamaño máximo permitido (3 MB)");
                return $mensaje;
            }
        }
        
        $params['edificioId']=$this->edificioId;
        $params['empresaId']=$this->empresaId;
        $params['usuarioId']=$this->usuarioId;

        $egresoTable=$this->getServiceLocator()->get('Finanzas\Model\EgresoTable');

    
        $params['file']=isset($recibo_digital)?true:false;

        $idEgreso=$params['co_egresoId'];
        if($params['co_egresoId']=='0'){
            $data = $egresoTable->addEgreso($params);
            $idEgreso=$data['lastIdEgreso'];
        }else{
            $data = $egresoTable->updateEgreso($params);
        }

        if($data['tipo']!='informativo'){
            return $data;
        }

        if(isset($recibo_digital)){
            if($idEgreso>0){
                $ruta_recibo_digital.='RD_'.$idEgreso.".pdf";
                if(move_uploaded_file($ruta_temp_recibo_digital,$ruta_recibo_digital) ) {
                    $data=array(
                        'tipo'=>'informativo',
                        'mensaje'=>"Los datos y documento digital se guadaron con éxito."
                        );
                }else{
                    $data=array(
                        'tipo'=>'error',
                        "mensaje"=>"Los datos se guardaron con éxito sin embargo el recibo digital no se puedo adjuntar..."
                        );
                }
            }else{
                $data=array(
                    'tipo'=>'error',
                    'mensaje'=>"Ocurrió un error desconocido en el servidor al intentar guardar el egreso."
                    );
            }
        }else{
            if($params['estado_recibo_digital']=='eliminar'){
                if(file_exists($ruta_recibo_digital."RD_".$idEgreso.".pdf")){
                    unlink($ruta_recibo_digital."RD_".$idEgreso.".pdf");
                }
            }
        }
        return $data;
    }

    public function deleteAction(){
        
        $request=$this->getRequest();
        $response = $this->getResponse();

        if($request->isPost()){
            $params=$request->getPost();
            $params['usuarioId']=$this->usuarioId;
            $params['edificioId']=$this->edificioId;

            $egresoTable=$this->getServiceLocator()->get('Finanzas\Model\EgresoTable');
            
            $query=isset($params['query'])?$params['query']:'';
            $data=array();
            switch ($query) {
                case 'deleteEgreso':
                    $data=$egresoTable->eliminarEgreso($params);
                    break;
                case 'deleteEP':
                    $data=$egresoTable->eliminarEgresoParcial($params);
                    break;
                default:
                    $data=array();
                    break;
            }
            $response->setContent(\Zend\Json\Json::encode($data));
        }else{
            $response->setContent(\Zend\Json\Json::encode(array('result'=>'error','mensaje'=>'petición no autorizada')));
        }
        return $response;
    }
}

<?php
/**
 * edificioOk (https://www.edificiook.com)
 * creado por: Meler Carranza, 23/03/2016.
 * ultima modificacion por: Meler Carranza
 * Fecha Modificacion: 23/03/2016.
 * Descripcion: Script controller para generar cuota
 *
 * @link      https://www.edificiook.com
 * @copyright Copyright (c) 2011-2016 KND S.A.C (http://www.knd.pe)
 * @license   http://www.edificiook.com/license/comercial Software Comercial
 * 
 */

namespace Proceso\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\Session\Container;


class CuotaController extends AbstractActionController 
{

    public function generarAction(){
        $session=new Container('User');
        $edificioIdSeleccionado=$session->offsetGet('edificioId');
       

        $cuotaTable = $this->getServiceLocator()->get("Proceso\Model\CuotaTable");
        $getParamsEdificio=$cuotaTable->getParametrosDeEdificio($edificioIdSeleccionado);
        return new ViewModel(array('parametrosEdificio'=>$getParamsEdificio));
    }

    public function iniciarAction(){
        $session=new Container('User');
        $edificioIdSeleccionado=$session->offsetGet('edificioId');
        $usuarioId=$session->offsetGet('userId');

    	$request=$this->getRequest();
    	$response=$this->getResponse();

    	$mensaje=array(null);

    	if($request->isPost()){
    		$params=$request->getPost();
            $respuestaFormat=$this->formatFechaEmision($params);
            $params=array(
                'edificioId'=>$edificioIdSeleccionado,
                'usuarioId'=>$usuarioId,
                'fechaEmi'=>$respuestaFormat['fechaEmi'],
                'fechaVence'=>$respuestaFormat['fechaVence'],
                'selMes'=>$params['selMes'],
                'selYear'=>$params['selYear']
            );
            $cuotaTable = $this->getServiceLocator()->get("Proceso\Model\CuotaTable");
            $mensaje = $cuotaTable->iniciarGeneracionDeCuota($params);
    	}
    	$response->setContent(\Zend\Json\Json::encode($mensaje));
    	return $response;
    }



    public function parametrosfechaAction(){

        $session=new Container('User');
        $edificioIdSeleccionado=$session->offsetGet('edificioId');

        $cuotaTable = $this->getServiceLocator()->get("Proceso\Model\CuotaTable");
        $rowCuotaTable = $cuotaTable->getParametrosDeEdificio($edificioIdSeleccionado);
        $diaEmi=$rowCuotaTable['diaEmi'];
        $diaVence=$rowCuotaTable['diaVence'];
        

        $request=$this->getRequest();
        $response=$this->getResponse();
        
        if($this->getRequest()){
        	if($request->isPost()){
        		$params=$request->getPost();
                $respuesta=$this->formatFechaEmision($params);
        		$response->setContent(\Zend\Json\Json::encode($respuesta));
        		return $response;
        	}
        }
        return $response->setContent(\Zend\Json\Json::encode(array()));
    }




    private function formatFechaEmision($params){
        $session=new Container('User');
        $edificioIdSeleccionado=$session->offsetGet('edificioId');

        $diaEmi=0;
        $diaVence=0;

        $yearEmi=$params['selYear']== 0? date('Y'):$params['selYear'];
        $yearVence=$yearEmi;

        $mesEmi=$params['selMes'] == 0 ? date('m'):$params['selMes'];
        $mesVence=$mesEmi;

        $personalizar=$params['chkPersonalizar'];
        if($personalizar=='true'){
            $diaEmi=empty($params['textDiaEmi']) ? date('d'): $params['textDiaEmi'];
            $diaVence=empty($params['textDiaVence']) ? date('d'): $params['textDiaVence'];
        }else{
            $cuotaTable = $this->getServiceLocator()->get("Proceso\Model\CuotaTable");
            $rowCuotaTable = $cuotaTable->getParametrosDeEdificio($edificioIdSeleccionado);
            $diaEmi=$rowCuotaTable['diaEmi'];
            $diaVence=$rowCuotaTable['diaVence'];
        }

        $ultimoDiaMes=date("d",(mktime(0,0,0,$mesEmi+1,1,$yearEmi)-1));
        $diaEmi=$ultimoDiaMes<$diaEmi ? $ultimoDiaMes:$diaEmi;

        $ultimoDiaMes=date("d",(mktime(0,0,0,$mesVence+1,1,$yearVence)-1));
        $diaVence=$ultimoDiaMes<$diaVence ? $ultimoDiaMes:$diaVence;

        if($diaEmi>$diaVence){
            if($mesEmi==12){
                $yearVence++;
                $mesVence=1;    
            }else{
                $mesVence++;
            }
        }

        $fechaEmi=$diaEmi."-".$mesEmi."-".$yearEmi;
        $fechaEmi=strtotime($fechaEmi);
        $fechaEmi=date('d-m-Y',$fechaEmi);

        $fechaVence=$diaVence."-".$mesVence."-".$yearVence;
        $fechaVence=strtotime($fechaVence);
        $fechaVence=date('d-m-Y',$fechaVence);

        $respuesta=array("diaEmi"=>$diaEmi,"diaVence"=>$diaVence,"fechaEmi"=>$fechaEmi,"fechaVence"=>$fechaVence);
        return $respuesta;
    }

}




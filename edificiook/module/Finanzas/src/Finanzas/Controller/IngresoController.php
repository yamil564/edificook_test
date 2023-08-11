<?php
/**
 * edificioOk (https://www.edificiook.com).
 * creado por: Meler Carranza, 11/04/2016.
 * ultima modificacion por: Meler Carranza.
 * Fecha Modificacion: 28/04/2016.
 * Descripcion: Script controller para la opcion ingreso.
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

class IngresoController extends AbstractActionController
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

    public function indexAction()
    {
        return new ViewModel();
    }

    public function gridAction(){
        $request=$this->getRequest();
        $response= $this->getResponse();
        $dataParaGrid=array();
        if($request->isPost()){
            $params=$request->getPost();
            $params['edificioId']=$this->edificioId;
            $params['empresaId']=$this->empresaId;

            $ingresoTable = $this->getServiceLocator()->get("Finanzas\Model\IngresoTable");
            $dataParaGrid = $ingresoTable->ingresosParaGrid($params);
            $response->setContent(\Zend\Json\Json::encode($dataParaGrid));
        }else{
            $response->setContent(\Zend\Json\Json::encode($dataParaGrid));
        }
        return $response;
    }

    public function readAction(){
        $request=$this->getRequest();
        $response = $this->getResponse();
        $data=array();
        if($request->isPost()){
            $params=$request->getPost();
            $params['edificioId']=$this->edificioId;
            $params['empresaId']=$this->empresaId;

            $ingresoTable = $this->getServiceLocator()->get("Finanzas\Model\IngresoTable");
            $about=isset($params['about'])?$params['about']:'';
            switch ($about){
                case 'detalleUnidad':
                    $data=$ingresoTable->detallesDeUnidad($params['unidadId']);
                    break;
                case 'general':
                    $data = $ingresoTable->detallesDeEmision($params['ingresoId']);
                    break;
                case 'itemsConcepto':
                    $data = $ingresoTable->conceptosDeIngreso($params);
                    break;
                case 'allConceptos':
                    $data=$ingresoTable->getConceptos($params);
                    break;
                case 'itemsPendientesDePago':
                    $data = $ingresoTable->ingresoPendientePagoPorUnidad($params['unidadId']);
                    break; 
                case 'itemsPagosRegistrados':
                    $data = $ingresoTable->getAllIngresosParcialesByIngreso($params);
                    break;
                case 'conceptosExistentesByProvision':
                    $data = $ingresoTable->getConceptosExistentesByEmision($params);
                    break;
                case 'ingresosParcialesByMes':
                    $data = $ingresoTable->getAllIngresosParcialesByMes($params);
                    break;
                case 'addGrupoUnidad':
                    $data = $ingresoTable->addGrupoUnidad($params);
                    break;
                case 'allGrupoUnidad':
                    $data = $ingresoTable->getAllGrupoUnidad($params);
                    break;
                case 'validateCuota':
                    $data = $ingresoTable->validateCuota($params);
                    break;
                case 'listGrupoUnidadConcepto':
                    $data = $ingresoTable->getListGrupoUnidadConcepto($params);
                    break;
                default:
                    $data=array();
                    break;
            }
            $response->setContent(\Zend\Json\Json::encode($data));
        }else{
            $response->setContent(\Zend\Json\Json::encode($data));
        }
        return $response;
    }


    
    public function saveAction(){
        $request=$this->getRequest();
        $response = $this->getResponse();
        $resultado=array();



        if($request->isPost()){
            $params=$request->getPost();
            $params['userId']=$this->usuarioId;
            $params['edificioId']=$this->edificioId;
            $params['usuarioId']=$this->usuarioId;

            $ingresoTable = $this->getServiceLocator()->get("Finanzas\Model\IngresoTable");
            $about=isset($params['about'])?$params['about']:'';

            switch ($about){
                case 'pagoTotal':
                    $resultado = $ingresoTable->addPagoEnDeudaPendiente($params);
                    break;
                case 'pagoMensual':
                    $resultado = $ingresoTable->addPagoEnIngresoSeleccionado($params);
                    break;
                case 'updateIngresoParcial':
                    $resultado=$ingresoTable->updateIngresoParcial($params);
                    break;
                case 'registrarIngreso':
                    $resultado=$ingresoTable->registrarIngreso($params);
                    break;
                case 'updateConceptoIngreso':
                    $resultado = $ingresoTable->updateConceptoIngreso($params);
                    break;
                case 'updateGrupoUnidadConcepto':
                    $resultado = $ingresoTable->updateGrupoUnidadConcepto($params);
                    break;
                case 'addConceptoIngreso':
                    $resultado = $ingresoTable->addConceptoIngreso($params);
                    break;
                case 'registrarConceptosExcel':
                    $resultado=$this->registrarConceptosExcel($params);
                    break;
                default:
                    $resultado=array();
                    break;
            }
            $response->setContent(\Zend\Json\Json::encode($resultado));
        }else{
            $response->setContent(\Zend\Json\Json::encode($resultado));
        }
        return $response;
    }
    
    public function deleteAction(){
        $request=$this->getRequest();
        $response = $this->getResponse();
        $resultado=array();
        if($request->isPost()){
            $params=$request->getPost();
            $params['userId']=$this->usuarioId;
            $params['edificioId']=$this->edificioId;
            $params['usuarioId']=$this->usuarioId;
            $ingresoTable = $this->getServiceLocator()->get("Finanzas\Model\IngresoTable");
            $about=isset($params['about'])?$params['about']:'';
            switch ($about) {
                case 'deleteIngresoParcial':
                    $resultado=$ingresoTable->deletePagos($params);
                    break;
                case 'deleteConceptoIngreso':
                    $resultado = $ingresoTable->deleteConceptoIngreso($params);
                    break;
                case 'deleteUnidadAsociado':
                    $resultado = $ingresoTable->deleteUnidadAsociado($params);
                    break;
                default:
                    $resultado = array();
                    break;
            }
            $response->setContent(\Zend\Json\Json::encode($resultado));
        }else{
            $response->setContent(\Zend\Json\Json::encode($resultado));
        }
        return $response;
    }


    public function descargarAction()
    {
        $params=array();
        $params['edificioId']=$this->edificioId;
        $params['empresaId']=$this->empresaId;

        $request=$this->getRequest();
        $response= $this->getResponse();
        $data=array();
        
        if($request->isPost()){
            $params=$request->getPost();
            $params['edificioId']=$this->edificioId;
            $params['empresaId']=$this->empresaId;
            $params['usuarioId']=$this->usuarioId;
            $ingresoTable = $this->getServiceLocator()->get("Finanzas\Model\IngresoTable");
            if($params['route']=='generateExcelBcp'){
                $data=$ingresoTable->crearExcelBcp($params);
            }else{
                $data=$ingresoTable->crearExcelIngreso($params);
            }
            $response->setContent(\Zend\Json\Json::encode($data));
        }else{
            $response->setContent(\Zend\Json\Json::encode($data));
        }
        return $response;
    }


    public function uploadAction()
    {
        $session=new Container('User');
        $userId = $session->offsetGet('userId');
        $response = $this->getResponse();
        $nombre = $_FILES['filexls']['name'];
        $tipoArchivo = $_FILES['filexls']['type'];
        $tamanoImagen = $_FILES['filexls']['size'];
        $rutaTemporal = $_FILES['filexls']['tmp_name'];
        $extension = substr($nombre, strrpos($nombre, '.')+1);
        if($extension != 'xls' && $extension!='xlsx'){
            $response->setContent(\Zend\Json\Json::encode(array("message"=>"noformat")));
        }else{
            $file = time().'.'.$extension;
            $directorio = 'public/temp/ingreso/conceptos-cuota-'.$file;
            copy($rutaTemporal,$directorio);
            if(file_exists($directorio)){
                $response->setContent(\Zend\Json\Json::encode(array("message"=>"success","file"=>$file)));
            }
        }
        return $response;
    }

    private function registrarConceptosExcel($params){

        $ingresoTable = $this->getServiceLocator()->get('Finanzas\Model\ImportaringresoTable');

        $data=array();


        $fileName=$params['filename'];
        $pathFile = 'public/temp/ingreso/conceptos-cuota-'.$fileName;

        $excel = new \PHPExcel();
        $objPHPExcel = \PHPExcel_IOFactory::load($pathFile);
        $attrObjExcel = $objPHPExcel->getActiveSheet();
        $dataExcel = $attrObjExcel->toArray(null,true,true,true);
        $highestRow = $attrObjExcel->getHighestRow();
        $highestColumn = $attrObjExcel->getHighestColumn();

        $error = 0;

        //Validar concepto en el formato.
        if(trim($dataExcel[3]["A"]) !='CONCEPTO:') $error++;
        if(trim($dataExcel[3]["B"]) =='') $error++;

        //validar Mes en el formato
        if(trim($dataExcel[4]["A"]) !='MES:') $error++;
        if(trim($dataExcel[4]["B"]) =='') $error++;


        //Validar existencia de año en el formato
        if(trim($dataExcel[5]["A"]) !='AÑO:') $error++;
        if(trim($dataExcel[5]["B"]) =='') $error++;

        //Validar encabezado de registros en el formato.
        if(trim($dataExcel[8]["A"]) !='N°')$error++;
        if(trim($dataExcel[8]["B"]) !='UNIDAD')$error++;
        if(trim($dataExcel[8]["C"]) !='IMPORTE')$error++;

        if($error == 0){
            $data=['tipo'=>"advertencia","mensaje"=>"El formato cargado no es correcto"];
        }

        $fechaCuota=$this->formatFechaEmision([
          "chkPersonalizar"=>"false",
          "selMes"=>(int)$dataExcel[4]["B"],
          "selYear"=>(int)$dataExcel[5]["B"],
        ]);

        $dataExcelFomat=array(
                "conceptoNombre"=>trim($dataExcel[3]["B"]),
                "fechaEmision"=>$fechaCuota['fechaEmi'],
                "fechaVence"=>$fechaCuota['fechaVence'],
                "edificioId"=>$this->edificioId,
                "empresaId"=>$this->empresaId,
                "usuarioId"=>$this->usuarioId,
        );

        $i=0;
        for($row=9;$row<=$highestRow;$row++){
            $dataExcelFomat["rows"][$i]=[
                "nro"=>$dataExcel[$row]["A"],
                "unidadNombre"=>$dataExcel[$row]["B"],
                "importe"=>$dataExcel[$row]["C"],
            ];
            $i++;
        }

        $data=$ingresoTable->guardar($dataExcelFomat);

        return $data;
    }


    public function formatoconceptoAction()
    {
        $request=$this->getRequest();
        $response = $this->getResponse();

        $session=new Container('User');
        $edificioIdSeleccionado=$session->offsetGet('edificioId');
        $importarIngresoTable = $this->getServiceLocator()->get("Finanzas\Model\ImportaringresoTable");

        if($request->isPost()){
            $params=$request->getPost();
            $consumo = $importarIngresoTable->generarFormato($edificioIdSeleccionado);
            $response->setContent(\Zend\Json\Json::encode($consumo));
        }else{
            $response->setContent(\Zend\Json\Json::encode(array(null)));
        }

        return $response;
    }

    private function formatFechaEmision($params){

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
           $rowCuotaTable = $cuotaTable->getParametrosDeEdificio($this->edificioId);
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

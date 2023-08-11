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

namespace Proceso\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\Db\Adapter\Adapter;
use Zend\Session\Container;
use Zend\Db\Sql\Sql;
use Zend\Db\Sql\Expression;

class ConsumoController extends AbstractActionController 
{

    public function __construct()
    {
        $session=new Container('User');
        $this->idEdificio=$session->offsetGet('edificioId');
        $this->idUsuario=$session->offsetGet('userId');
    }
   
    public function aguaAction()
    {	
    	$session=new Container('User');
        $edificioIdSeleccionado=$session->offsetGet('edificioId');
        $edificio = $this->getServiceLocator()->get("Mantenimiento\Model\EdificioTable");
        $consumo = $this->getServiceLocator()->get("Proceso\Model\ConsumoTable");
        $valores = array(
        	'idedificio'=>$edificioIdSeleccionado,
        	'listarConsumo'=>$consumo->listarConsumo($edificioIdSeleccionado),
            'listarUnidad'=>$consumo->listarUnidad($edificioIdSeleccionado),
            'edificio'=>$edificio->listarEdificioPorId($edificioIdSeleccionado),
            'tarifa'=>$consumo->listarTarifa(),
        );
        return new ViewModel($valores);
    }

    public function saveAction()
    {
        $request=$this->getRequest();
        $response = $this->getResponse();

        $session=new Container('User');
        $edificioIdSeleccionado=$session->offsetGet('edificioId');
        $userId = $session->offsetGet('userId');

        if($request->isPost()){
            $params=$request->getPost();
            $params['idUsuario']=$userId;
            $params['idEdificio']=$edificioIdSeleccionado;
            $consumo = $this->getServiceLocator()->get("Proceso\Model\ConsumoTable");
            $consumo = $consumo->updateConsumo($params);
            $response->setContent(\Zend\Json\Json::encode($consumo));
        }else{
            $response->setContent(\Zend\Json\Json::encode(array(null)));
        }

        return $response;
    }

    public function actualizarFechaLecturaAction()
    {
        $request=$this->getRequest();
        $response = $this->getResponse();

        $session=new Container('User');
        $edificioIdSeleccionado=$session->offsetGet('edificioId');
        $userId = $session->offsetGet('userId');

        if($request->isPost()){
            $params=$request->getPost();
            $params['idUsuario']=$userId;
            $params['idEdificio']=$edificioIdSeleccionado;
            $consumo = $this->getServiceLocator()->get("Proceso\Model\ConsumoTable");
            $rptaConsumo = $consumo->updateFechaLecturas($params);
            $response->setContent(\Zend\Json\Json::encode($rptaConsumo));
        }else{
            $response->setContent(\Zend\Json\Json::encode(array(null)));
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
            //$folder = 'consumo';
            //$directorio = '/public/doc/'.$folder.'/'.$userId.'-consumo.'.$extension;
            $file = time().'.'.$extension;
            $directorio = 'public/temp/consumo/lectura-'.$file;
            copy($rutaTemporal,$directorio);
            if(file_exists($directorio)){
                $response->setContent(\Zend\Json\Json::encode(array("message"=>"success","file"=>$file))); 
            }
        }
        return $response;
    }

    public function registrarAction()
    {
        $request=$this->getRequest();
        $response = $this->getResponse();
        $adapter = $this->getServiceLocator()->get('Zend\Db\Adapter\Adapter');
        $sql=new Sql($adapter);

        $session=new Container('User');
        $edificioIdSeleccionado=$session->offsetGet('edificioId');
        $userId = $session->offsetGet('userId');
        $nameFile = $this->params()->fromPost('file');

        $excel = new \PHPExcel();
        $inputFileName = 'public/temp/consumo/lectura-'.$nameFile;
        $objPHPExcel = \PHPExcel_IOFactory::load($inputFileName);
        $attrObjExcel = $objPHPExcel->getActiveSheet();
        $data = $attrObjExcel->toArray(null,true,true,true);
        $highestRow = $attrObjExcel->getHighestRow();
        $highestColumn = $attrObjExcel->getHighestColumn();
        $error = 0;

        $unidep = trim($data[1]['A']);
        $mes = trim($data[1]['C']);
        $tipcon = trim($data[1]['D']);
        $tipver = trim($data[1]['E']);
        $lecact = trim($data[1]['F']);

        if ($unidep != 'UNID. INMOBILIARIA') {$error++;}
        if ($mes != 'MES') {$error++;}
        if ($tipcon != 'TIPO') {$error++;}
        if ($tipver != 'TIPO A VISUALIZAR') {$error++;}
        if ($lecact != 'LECTURA ACTUAL') {$error++;}

        $dia = date('d');
        $error2 = 0;
        $numRegistro='';

        if($error == 0){
            
            for($i=2;$i<=$highestRow;$i++){
                
                $unidep = trim($data[$i]['A']);
                $fechaxls = $dia.'/'.trim(date($data[$i]['C'])) . '/' . trim(date($data[$i]['B']));
                $hora = date('H:i:s');
                $tipcon = trim(strtoupper($data[$i]['D']));
                $tipver = trim(strtoupper($data[$i]['E']));
                $lecact = trim($data[$i]['F']);


                if ($lecact == '' || $unidep == '' || $tipcon == '' || $this->validarTipoDeConsumo($tipcon) == 1 || $tipver == '' || $this->validarTipoDeConsumo($tipver) == 1 || trim(date($data[$i]['C']) > 12) || trim(date($data[$i]['B'])) == '' || trim(date($data[$i]['C'])) == '') {
                    $error2++;
                }


                if($error2 == 0){
                    
                    $queryUnidad = "SELECT uni_in_cod FROM unidad WHERE edi_in_cod = '$edificioIdSeleccionado' AND CONCAT(TRIM(uni_vc_tip),' ',TRIM(uni_vc_nom)) = '$unidep' AND uni_in_est!=0";
                    $rowCodigoUnidad = $adapter->query($queryUnidad,$adapter::QUERY_MODE_EXECUTE)->current();
                    $unidCod = $rowCodigoUnidad['uni_in_cod'];

                    if ($unidCod !='') {

                        $fec = explode("/", $fechaxls);
                        $fecha = $fec[2]."-".$fec[1]."-".$fec[0];
                        $month = $fec[1];
                        $year = $fec[2];
                        if ($month - 1 == 0) {
                            $month = 12;
                            $year = $fec[2] - 1;
                        } else {
                            $month = $fec[1] - 1;
                        }

                        $queryConsumo = "SELECT cns_do_lec, cns_ti_hor, cns_vc_tip, cns_vc_tipver FROM consumo WHERE MONTH(cns_da_fec)='$month' AND YEAR(cns_da_fec)='$year' AND uni_in_cod='$unidCod' AND cns_vc_ser='AGUA'";
                        $RowCon2 = $adapter->query($queryConsumo,$adapter::QUERY_MODE_EXECUTE)->current();
                        $lecant = $RowCon2['cns_do_lec'];
                        $consumo = $lecact - $lecant;

                        //echo "Antes = ".$consumo."<br>";
                        if ($tipcon == 'LITRO') {
                            $consumoLitro = $consumo;
                            $consumoM3 = $consumo / 1000;
                            $consumoGalon = $consumo / 3.7854;
                        } else if ($tipcon == 'M3') {
                            $consumoLitro = $consumo * 1000;
                            $consumoM3 = $consumo;
                            $consumoGalon = $consumo * 264.172874;
                        } else if ($tipcon == 'GALON') {
                            $consumoLitro = $consumo * 3.7854;
                            $consumoM3 = $consumo / 264.172874;
                            $consumoGalon = $consumo;
                        }
                        // echo $unidep. " cl=".$consumoLitro. " cm3=".$consumoM3." cg=".$consumoGalon."--<br>";
                        $consumoLitro = round($consumoLitro, 2);
                        $consumoM3 = round($consumoM3, 2);
                        $consumoGalon = round($consumoGalon, 2);
                        //echo $unidep. " cl=".$consumoLitro. " cm3=".$consumoM3." cg=".$consumoGalon." |<br>";



                        $querySelectConsumo = "SELECT cns_in_cod FROM consumo WHERE MONTH(cns_da_fec)='" . $fec[1] . "' AND YEAR(cns_da_fec)='" . $fec[2] . "' AND uni_in_cod='$unidCod' AND cns_vc_ser='AGUA' ";
                        $RowCon = $adapter->query($querySelectConsumo,$adapter::QUERY_MODE_EXECUTE)->current();
                        $CodCon = $RowCon['cns_in_cod'];

                        if($CodCon !='' && $CodCon !=null) {
                            $updateConsumo = "UPDATE consumo SET cns_vc_tip='$tipcon', cns_vc_tipver='$tipver', cns_do_conl='$consumoLitro', cns_do_conm3='$consumoM3', cns_do_congal='$consumoGalon', cns_do_lec='$lecact', cns_da_fec='$fecha', cns_ti_hor='$hora' WHERE MONTH(cns_da_fec)='" . $fec[1] . "' AND YEAR(cns_da_fec)='" . $fec[2] . "' AND uni_in_cod='$unidCod' AND cns_vc_ser='AGUA'";
                            $adapter->query($updateConsumo,$adapter::QUERY_MODE_EXECUTE);
                        }else{

                            $insert = $sql->insert('consumo');
                            $dataConsumo = array(
                                'uni_in_cod'=> $unidCod,
                                'cns_vc_ser'=> 'AGUA',
                                'cns_da_fec'=> $fecha,
                                'cns_ti_hor'=> $hora,
                                'cns_do_lec'=> $lecact,
                                'cns_do_conl'=> $consumoLitro,
                                'cns_do_conm3'=> $consumoM3,
                                'cns_do_congal'=> $consumoGalon,
                                'cns_vc_tip'=> $tipcon,
                                'cns_vc_tipver'=> $tipver
                            );
                            $insert->values($dataConsumo);
                            $idRegistro = $sql->prepareStatementForSqlObject($insert)->execute()->getGeneratedValue();
                            /////////save auditoria////////
                            $params['tabla']='Consumo';
                            $params['numRegistro']=$idRegistro;
                            $params['accion']='Registrar Formato Excel';
                            $this->saveAuditoria($params);
                            ///////////////////////////////
                        }

                    }else{
                        $error++;
                    }

                }else{
                    $error++;
                }
            }

            if ($error > 0) {
                if ($error == 1) {
                    $text = " error";
                } else {
                    $text = " errores";
                }
                unlink($inputFileName);
                $response->setContent(\Zend\Json\Json::encode(array(
                    "message"=>"advertencia",
                    "cuerpo"=>"Se ejecutó el archivo pero se detecto posibles errores en $error registro(s):<br />1.- Verificar campos vacios.<br />2.- Verificar fechas mal ingresadas.<br/>3.- Verificar tipo de consumo mal ingresados(M3 LITRO GALÓN)<br/>4.- Verificar si tienen provisiones resgistradas, para el mes que figura en el formato (.exel)."
                )));
            } else {
                $response->setContent(\Zend\Json\Json::encode(array(
                    "message"=>"success",
                    "cuerpo"=>"Se ejecutó correctamente el registro de consumos !"
                )));
                unlink($inputFileName);
            }

        }else{
            unlink($inputFileName);
            $response->setContent(\Zend\Json\Json::encode(array("message"=>"advertencia","cuerpo"=>"No se ejecutó el registro de consumos porque no es el formato del sistema")));
        }

        /*//auditoria (atributos)
        $ruta = $this->params()->fromPost('ruta');
        $accion='Registrar Formato Excel';
        $tabla='Consumo';
        //function modulo
        $auditoria=$this->getServiceLocator()->get("AuditoriaTable");
        $auditoria->save($userId,$edificioIdSeleccionado,$ruta,$accion,$tabla,$numRegistro);*/

        return $response;

    }

    public function validarTipoDeConsumo($tipo) //fun_valTip
    {
        $cadena = "M3 LITRO GALÓN";
        $buscar = $tipo;
        $resultado = explode($buscar, $cadena);
        return COUNT($resultado);
    }

    public function formatoAction()
    {
        $request=$this->getRequest();
        $response = $this->getResponse();

        $session=new Container('User');
        $edificioIdSeleccionado=$session->offsetGet('edificioId');
        $userId = $session->offsetGet('userId');
        $consumo = $this->getServiceLocator()->get("Proceso\Model\ConsumoTable");

        if($request->isPost()){
            $params=$request->getPost();
            $accion = $params['accion'];
            $params['idUsuario']=$this->idUsuario;
            $params['idEdificio']=$this->idEdificio;
            $consumo = $consumo->formato($edificioIdSeleccionado,$userId,$accion,$params);
            $response->setContent(\Zend\Json\Json::encode($consumo));
        }else{
            $response->setContent(\Zend\Json\Json::encode(array(null)));
        }

        return $response;
    }

    public function cargarGridConsumoAction()
    {
    	$request=$this->getRequest();
        $response = $this->getResponse();

        if($request->isPost()){
            $params=$request->getPost();
            $consumo = $this->getServiceLocator()->get("Proceso\Model\ConsumoTable");
            $consumo = $consumo->cargarGridConsumo($params);
            $response->setContent(\Zend\Json\Json::encode($consumo));
        }else{
            $response->setContent(\Zend\Json\Json::encode(array(null)));
        }

        return $response;
    }

    public function eliminarArchivoAction()
    {
        $request=$this->getRequest();
        $response = $this->getResponse();

        $session=new Container('User');
        $userId = $session->offsetGet('userId');

        if($request->isPost()){
            $params=$request->getPost();
            $nameFile = $params['file'];
            $file = 'public/temp/consumo/lectura-'.$nameFile;
            if(file_exists($file)){
                @unlink($file);
                $response->setContent(\Zend\Json\Json::encode(array("message"=>"success","cuerpo"=>"El archivo se a eliminado correctamente.")));
            }else{
                $response->setContent(\Zend\Json\Json::encode(array("message"=>"error","cuerpo"=>"El archivo no se encuentra en nuestra BD.")));
            }
        }else{
            $response->setContent(\Zend\Json\Json::encode(array(null)));
        }

        return $response;
    }

    public function getLecturaAction()
    {
        $request=$this->getRequest();
        $response = $this->getResponse();

        if($request->isPost()){
            $params=$request->getPost();
            $consumo = $this->getServiceLocator()->get("Proceso\Model\ConsumoTable");
            $consumo = $consumo->getInformacionUnidad($params);
            $response->setContent(\Zend\Json\Json::encode($consumo));
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
            'usu_in_cod'=> $this->idUsuario, //idusuario
            'edi_in_cod'=> $this->idEdificio, //idedificio
            'aud_opcion'=> 'Procesos > Consumo de agua', //ruta
            'aud_accion'=> $params['accion'], //accion
            'aud_fecha'=> date('Y-m-d H:i:s'), //fecha y hora
            'aud_ip'=> $_SERVER['REMOTE_ADDR'], //ip publica
            'aud_tabla'=> $params['tabla'], //tabla
            'aud_in_numra'=> $params['numRegistro'], //registro afectado
            'aud_info_adi'=> $_SERVER['HTTP_USER_AGENT'] //agente
        );
        $insert->values($data);
        $sql->prepareStatementForSqlObject($insert)->execute()->count();
    }

}
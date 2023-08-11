<?php

/**
 * edificioOk (https://www.edificiook.com)
 * creado por: Meler Carranza, 11/03/2016.
 * ultima modificacion por: Jhnon Gómez, Meler Carranza
 * Fecha Modificacion: 21/04/2016, 02-06-2016
 * Descripcion: 
 *
 * @autor     Fidel J. Thompson 
 * @link      https://www.edificiook.com
 * @copyright Copyright (c) 2011-2016 KND S.A.C (http://www.knd.pe)
 * @license   http://www.edificiook.com/license/comercial Software Comercial
 * 
 */

namespace Mantenimiento\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\Session\Container;

use Zend\Db\Adapter\Adapter;
use Zend\Db\Sql\Sql;
use Zend\Db\Sql\Expression;

class DepartamentoController extends AbstractActionController
{
    public function __construct()
    {
        $session=new Container('User');
        $this->idEdificio=$session->offsetGet('edificioId');
        $this->idUsuario=$session->offsetGet('userId');
    }
    
    public function indexAction()
    {
        $session=new Container('User');
        $edificioIdSeleccionado=$session->offsetGet('edificioId');
        $departamentoTable=$this->getServiceLocator()->get("Mantenimiento\Model\DepartamentoTable");
        $tiposDeUnidad=$departamentoTable->getAllTiposDeUnidad();
        $usuariosPorEdificio=$departamentoTable->getAllUsuariosPorEdificio($edificioIdSeleccionado);
        $unidadespadresPorEdificio=$departamentoTable->getAllUnidadespadresPorEdificio($edificioIdSeleccionado);
        $valores = array(
            "tiposDeUnidad"=>$tiposDeUnidad,
            "usuariosPorEdificio"=>$usuariosPorEdificio,
            'unidadespadresPorEdificio'=>$unidadespadresPorEdificio,
        );
        return new ViewModel($valores);
    }

    public function departamentosAction(){
    	$response=$this->getResponse();
    	$response->setContent(\Zend\Json\Json::encode(array('response' => true)));
		return $response; 	
    }


    public function gridAction(){

    	$request=$this->getRequest();
    	$response = $this->getResponse();
      
    	if($request->isPost()){
    		$session=new Container('User');
	        $edificioIdSelected=$session->offsetGet('edificioId');

            $params=$request->getPost();
            $params['edificioId']=$edificioIdSelected;

	        $departamentos = $this->getServiceLocator()->get("Mantenimiento\Model\DepartamentoTable");
	        $departamentos = $departamentos->getUnidadesPorEdificioParaGrid($params);

	    	$response->setContent(\Zend\Json\Json::encode($departamentos));
			
    	}else{
    		$response->setContent(\Zend\Json\Json::encode(array(null)));
    	}

        return $response;
    }

    public function rowAction(){
        $session=new Container('User');
        $edificioIdSeleccionado=$session->offsetGet('edificioId');
        $request=$this->getRequest();
        $response=$this->getResponse();

        if($request->isPost()){
            $params=$request->getPost();
            $id=(int)$params['id'];
            $unidad=$this->getServiceLocator()->get("Mantenimiento\Model\DepartamentoTable");
            $unidad=$unidad->getUnidadPorId($id,$edificioIdSeleccionado);
            $response->setContent(\Zend\Json\Json::encode($unidad));
        }else{
            $response->setContent(\Zend\Json\Json::encode(array(null)));
        }

        return $response;
    }

	public function saveNuevoAction() {
		$request = $this->getRequest();
		$response = $this->getResponse();
		$session = new Container('User');
		$edificioId = $session->offsetGet('edificioId');
		$idUsuario = $session->offsetGet('userId');

		if($request->isPost()) {
			$params = $request->getPost();
			$params['edificioId'] = $edificioId;
			$params['idUsuario'] = $idUsuario;

			$unidadTable = $this->getServiceLocator()->get("Mantenimiento\Model\DepartamentoTable");
			$resultado = $unidadTable->addUnidad($params);
		}
		
		return $response->setContent(\Zend\Json\Json::encode($resultado));
	}

    public function saveAction(){
        $request=$this->getRequest();
        $response=$this->getResponse();$session=new Container('User');
        $edificioId=$session->offsetGet('edificioId');
        $idUsuario=$session->offsetGet('userId');

        if($request->isPost()){
            $params=$request->getPost();
            $params['edificioId']=$edificioId;
            $params['idUsuario']=$idUsuario;
            $id=(int)$params['id'];
            $unidadTable=$this->getServiceLocator()->get("Mantenimiento\Model\DepartamentoTable");
            $accion = '';
            if($id>0){
                $resultado=$unidadTable->updateUnidad($params);
                $accion = 'Editar';
            }else{
                $resultado=$unidadTable->addUnidad($params);
                $accion = 'Agregar';
            }
        }
        return $response->setContent(\Zend\Json\Json::encode($resultado));
    }

    public function exportarAction(){

        $session=new Container('User');
        $edificioId=$session->offsetGet('edificioId');
        $idUsuario=$session->offsetGet('userId');
       
        $request=$this->getRequest();
        $response= $this->getResponse();
        $data=array();
        
        if($request->isPost()){
            $params=$request->getPost();
            $params['edificioId']=$edificioId;
            $params['idUsuario']=$idUsuario;
            $unidadTable=$this->getServiceLocator()->get("Mantenimiento\Model\DepartamentoTable");
            $data=$unidadTable->crearExcelUnidades($params);
            $response->setContent(\Zend\Json\Json::encode($data));
        }else{
            $response->setContent(\Zend\Json\Json::encode($data));
        }
        return $response;
    }

    public function uniprincipalAction(){
        $session=new Container('User');
        $edificioIdSeleccionado=$session->offsetGet('edificioId');
        $request=$this->getRequest();
        $response=$this->getResponse();

        if($request->isPost()){
            $params=$request->getPost();
            $unidad=$this->getServiceLocator()->get("Mantenimiento\Model\DepartamentoTable");
            $unidad=$unidad->getAllUnidadespadresPorEdificio($edificioIdSeleccionado);
            $response->setContent(\Zend\Json\Json::encode($unidad));
        }else{
            $response->setContent(\Zend\Json\Json::encode(array(null)));
        }
        return $response;
	}
	
	public function uploadAction() {
		$session=new Container('User');
		$userId = $session->offsetGet('userId');
		$response = $this->getResponse();
		$nombre = $_FILES['filexls']['name'];
		$tipoArchivo = $_FILES['filexls']['type'];
		$tamanoImagen = $_FILES['filexls']['size'];
		$rutaTemporal = $_FILES['filexls']['tmp_name'];
		$extension = substr($nombre, strrpos($nombre, '.')+1);
		
		if($extension != 'xls' && $extension!='xlsx') {
			$response->setContent(\Zend\Json\Json::encode(array("message"=>"noformat"))); 
		} else {
			// $folder = 'consumo';
			// $directorio = '/public/doc/'.$folder.'/'.$userId.'-consumo.'.$extension;
			$file = time().'.'.$extension;
			$directorio = 'public/temp/departamento/importar-'.$file;
			copy($rutaTemporal,$directorio);
			
			if(file_exists($directorio)) {
				$response->setContent(\Zend\Json\Json::encode(array("message"=>"success","file"=>$file))); 
			}
		}
		
		return $response;
	}

	public function registrarAction() {
		$request = $this->getRequest();
        $response = $this->getResponse();
        $adapter = $this->getServiceLocator()->get('Zend\Db\Adapter\Adapter');
		
		$sql = new Sql($adapter);
        $session = new Container('User');
		
		$edificioIdSeleccionado = $session->offsetGet('edificioId');
        $userId = $session->offsetGet('userId');
        $nameFile = $this->params()->fromPost('file');

        $excel = new \PHPExcel();
        $inputFileName = 'public/temp/departamento/importar-'.$nameFile;
        $objPHPExcel = \PHPExcel_IOFactory::load($inputFileName);
        $attrObjExcel = $objPHPExcel->getActiveSheet();
        $data = $attrObjExcel->toArray(null,true,true,true);
        $highestRow = $attrObjExcel->getHighestRow();
        $highestColumn = $attrObjExcel->getHighestColumn();
		$error = 0;
		
		$qe = "SELECT * FROM edificio WHERE edi_in_cod = {$edificioIdSeleccionado}";
		$dataEdificio = $adapter->query($qe, $adapter::QUERY_MODE_EXECUTE)->current();

		$unidadNombre = trim($data[1]['A']);
		$unidadCodpago = trim($data[1]['B']);
		$unidadArea = trim($data[1]['C']);
		$unidadBloque = trim($data[1]['D']);
		$unidadTipo = trim($data[1]['E']);
		$unidadUso = trim($data[1]['F']);
		$unidadDesc = trim($data[1]['G']);
		$unidadCuotam = trim($data[1]['H']);
		$unidadPorcentaje = trim($data[1]['I']);

		// $unidadNombre = trim($data[1]['A']);
		// $unidadArea = trim($data[1]['B']);
		// $unidadBloque = trim($data[1]['C']);
		// $unidadTipo = trim($data[1]['D']);
		// $unidadUso = trim($data[1]['E']);
		// $unidadDesc = trim($data[1]['F']);
		// $unidadCuotam = trim($data[1]['G']);
		// $unidadPorcentaje = trim($data[1]['H']);

		$unidadDireccion = $dataEdificio['edi_vc_dir']." ".$dataEdificio['edi_vc_num']; // del query de edificio
		$unidadUrb = $dataEdificio['edi_vc_urb']; // del query de edificio

		if ($unidadNombre != 'UNIDAD') {$error++;}
		if ($unidadArea != 'AREA OCUPADA') {$error++;}
		if ($unidadBloque != 'BLOQUE') {$error++;}
		if ($unidadTipo != 'TIPO') {$error++;}
		if ($unidadUso != 'USO') {$error++;}
		if ($unidadDesc != 'DESCRIPCION') {$error++;}
		if ($unidadCuotam != 'CUOTA M2') {$error++;}
		if ($unidadPorcentaje != 'PORCENTAJE') {$error++;}

        if($error == 0) {
			$repetidos = "";
            for($i = 2; $i <= $highestRow; $i++) {

				// $unidadNombrer = str_replace("-", "", str_replace(".", "", str_replace(",", "", str_replace("/", "", $data[$i]['A'])))); // sin simbolos ni espacios
				$unidadNombrer = str_replace("-", "", str_replace(".", "", str_replace(",", "", str_replace("/", "", $data[$i]['B']))));
				
				$query = "SELECT count(*) AS conteo FROM unidad WHERE edi_in_cod = {$edificioIdSeleccionado} AND uni_vc_codpag = '{$unidadNombrer}'";
				$dataUnidad = $adapter->query($query, $adapter::QUERY_MODE_EXECUTE)->current();

				if($dataUnidad['conteo'] == 0) {
					$insert = $sql->insert('unidad');
					$dataExcel = array(
						'edi_in_cod'=> $edificioIdSeleccionado,
						'uni_vc_nom'=> $data[$i]['A'],
						'uni_vc_codpag'=> $unidadNombrer,
						'uni_vc_dir' => $unidadDireccion,
						'uni_vc_num' => $data[$i]['A'],
						'uni_vc_urb' => $unidadUrb,
						'uni_do_aocu' => $data[$i]['B'],
						'uni_vc_bloque' => $data[$i]['C'],
						'uni_vc_tip' => $data[$i]['D'],
						'uni_vc_nmun' => $data[$i]['A'],
						'uni_vc_uso' => $data[$i]['E'],
						'uni_do_aream2' => $data[$i]['B'],
						'uni_vc_npar' => $data[$i]['A'],
						'uni_te_desc' => $data[$i]['F'],
						'uni_do_cm2' => $data[$i]['G'],
						'uni_do_pct' => $data[$i]['H'],
						'uni_do_deu' => 0,
						'uni_in_est' => 1,
						'uni_in_ultingpar' => 0
					);
					$insert->values($dataExcel);
					$idRegistro = $sql->prepareStatementForSqlObject($insert)->execute()->getGeneratedValue();
					/////////save auditoria////////
					$params['tabla'] = 'Unidad';
					$params['numRegistro'] = $idRegistro;
					$params['accion'] = 'Registrar Formato Excel';
					$this->saveAuditoria($params);
					///////////////////////////////
				} else {
					$repetidos .= $unidadNombrer.", ";
				}
			}

			if($repetidos != "") {
				$cuerpo = "Se ejecutó el registro de unidades con observaciones. Las siguientes unidades ya existen: ".$repetidos;
			} else {
				$cuerpo = "Se ejecutó correctamente el registro de unidades!";
			}
			
			$response->setContent(\Zend\Json\Json::encode(array(
				"message" => "success",
				"cuerpo" => $cuerpo
			)));

			unlink($inputFileName);
		} else {
			if ($error == 1) {
				$text = " error";
            } else {
				$text = " errores";
			}
			unlink($inputFileName);
			$response->setContent(\Zend\Json\Json::encode(array(
				"message" => "advertencia",
				"cuerpo" => "Se ejecutó el archivo pero se detecto posibles errores en $error registro(s):<br />1.- Verificar campos vacios.<br />2.- Verificar fechas mal ingresadas.<br/>3.- Verificar tipo de consumo mal ingresados(M3 LITRO GALÓN)<br/>4.- Verificar si tienen provisiones resgistradas, para el mes que figura en el formato (.exel)."
            )));
            
		}
		
		return $response;
	}

	public function formatoAction() {
        $request=$this->getRequest();
        $response = $this->getResponse();

        $session=new Container('User');
        $edificioIdSeleccionado=$session->offsetGet('edificioId');
        $userId = $session->offsetGet('userId');
        $consumo = $this->getServiceLocator()->get("Mantenimiento\Model\DepartamentoTable");

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

	private function saveAuditoria($params) {   
		$adapter = $this->getServiceLocator()->get('Zend\Db\Adapter\Adapter');
		$sql = new Sql($adapter);
		
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

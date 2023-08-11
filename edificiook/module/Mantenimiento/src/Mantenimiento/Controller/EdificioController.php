<?php

/**
* edificioOk (https://www.edificiook.com)
* creado por: Jhon Gómez, 11/03/2016.
* ultima modificacion por: Jhon Gómez
* Fecha Modificacion: 11/04/2016.
* Descripcion: Controlador Edificio
*
* @link      https://www.edificiook.com
* @copyright Copyright (c) 2011-2016 KND S.A.C (http://www.knd.pe)
* @license   http://www.edificiook.com/license/comercial Software Comercial
* 
*/

namespace Mantenimiento\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\Db\Adapter\Adapter;
use Mantenimiento\Model\Entity\Edificio;
use Mantenimiento\Model\Entity\Tipo;
use Mantenimiento\Model\Entity\Departamento;
use Zend\Session\Container;
use Zend\Db\Sql\Sql;
use Zend\Db\Sql\Expression;

class EdificioController extends AbstractActionController
{

    public function __construct()
    {
        $session=new Container('User');
        $this->idEdificio=$session->offsetGet('edificioId');
        $this->idUsuario=$session->offsetGet('userId');
    }

    public function indexAction()
    {
        $edificio = $this->getServiceLocator()->get("Mantenimiento\Model\EdificioTable");
        $noticias = $this->getServiceLocator()->get("Ue\Model\NoticiasTable");
        $valores = array(
            'edificio'=>$edificio->listarEdificioPorId($this->idEdificio),
            'noticias'=>$noticias->listarMaximoNoticias($this->idEdificio, 5)
        );
        return new ViewModel($valores);
    }

    public function datosGeneralesAction()
    {  
        $session=new Container('User');
        $edificioIdSeleccionado=$session->offsetGet('edificioId');
        $edificio = $this->getServiceLocator()->get("Mantenimiento\Model\EdificioTable");
        $valores = array(
            'edificio' => $edificio->listarEdificioPorId($edificioIdSeleccionado),
            'tipo' => $edificio->listarTipo(),
            'lugar' => $edificio->listarLugar(),
            'ambienteLugar' => $edificio->getLugarDeEntidadPorEdificio($edificioIdSeleccionado),
        );
        return new ViewModel($valores);
	}
	
	public function nuevoAction() {

		$session = new Container('User');
		$idUsuario=$session->offsetGet('userId');

		$request = $this->getRequest();
		$response = $this->getResponse();
		$edificio = $this->getServiceLocator()->get("Mantenimiento\Model\EdificioTable");

		if($request->isPost()) {

			$params = $request->getPost();
			$params['idUsuario'] = $idUsuario;
			
			$edificio = $edificio->nuevoEdificio($params);

			$response->setContent(\Zend\Json\Json::encode($edificio));

			return $response;
		}
	}

	public function saveAction()
    {
        $session=new Container('User');
        $edificioIdSeleccionado=$session->offsetGet('edificioId');
        $idUsuario=$session->offsetGet('userId');
        $request=$this->getRequest();
        $response = $this->getResponse();
        $edificio = $this->getServiceLocator()->get("Mantenimiento\Model\EdificioTable");
        if($request->isPost()){
            $params=$request->getPost();
            $params['idUsuario']=$idUsuario;
            $params['edificioId']=$edificioIdSeleccionado;
            $file = $this->multipleFiles($_FILES);
            $edificio = $edificio->updateEdificio($params,$edificioIdSeleccionado,$file);
            $response->setContent(\Zend\Json\Json::encode($edificio));
        }
        return $response;
    }

    public function cargosYOtrosAction()
    {
        $session=new Container('User');
        $empresaIdSeleccionado=$session->offsetGet('empId');
        $edificioIdSeleccionado=$session->offsetGet('edificioId');
        $edificio = $this->getServiceLocator()->get("Mantenimiento\Model\EdificioTable");
        $valores = array(
            'listarUsuarios'=>$edificio->listarUsuarios($empresaIdSeleccionado,$edificioIdSeleccionado),
            'edificio' => $edificio->listarEdificioPorId($edificioIdSeleccionado),
            'listaCargoJuntaDirectiva'=>$edificio->listaCargoJuntaDirectiva(),
            'listarJuntaDirectiva'=>$edificio->listarJuntaDirectiva($edificioIdSeleccionado),
        );
        return new ViewModel($valores);
    }

    public function equipoAction()
    {
        return new ViewModel();
    }

    public function testAction()
    {
        exec('/usr/local/bin/wkhtmltopdf https://www.edificiook.com/public/reporte/estado-cuenta/recibo-estandar public/file/example.pdf 2>&1');
        return new ViewModel();
    }

    public function configuracionAction()
    {
        $session=new Container('User');
        $empresaIdSeleccionado=$session->offsetGet('empId');
        $edificioIdSeleccionado=$session->offsetGet('edificioId');
        $edificio = $this->getServiceLocator()->get("Mantenimiento\Model\EdificioTable");
        $valores = array(
            'edificio' => $edificio->listarEdificioPorId($edificioIdSeleccionado),
        );
        return new ViewModel($valores);
    }

    public function archivoDeRecaudacionBcpAction()
    {
        return new ViewModel();
    }

    public function listarUbigeoAction()
    {
        $request=$this->getRequest();
        $response = $this->getResponse();

        if($request->isPost()){
            $params=$request->getPost();
            $edificio = $this->getServiceLocator()->get("Mantenimiento\Model\EdificioTable");
            $edificio = $edificio->listarUbigeo($params);
            $response->setContent(\Zend\Json\Json::encode($edificio));
        }else{
            $response->setContent(\Zend\Json\Json::encode(array(null)));
        }

        return $response;
    }

    public function uploadImagenPrincipalAction()
    {
        $session=new Container('User');
        $idEdificio=$session->offsetGet('edificioId');
        $request=$this->getRequest();
        $response = $this->getResponse();

        if($request->isPost()){
            $params=$request->getPost();
            //variables
            $tipoImagen = $params['type'];
            $folder = $params['folder'];
            //variables banner
            $nombreImagen = $_FILES['imgupdate']['name'];
            $tipoArchivo = $_FILES['imgupdate']['type'];
            $tamanoImagen = $_FILES['imgupdate']['size'];
            $rutaTemporal = $_FILES['imgupdate']['tmp_name'];
            //extension de la imagen
            $imagenExtension = substr($nombreImagen, strrpos($nombreImagen, '.')+1);
            //tipo de carpeta           
            //if($tipoImagen == 'logo') $tipo = '_logo';
            //else $tipo = '_banner';
            //ruta
            //$directorioImagen = '/public/images/'.$folder.'/'.$idEdificio.$tipo.".".$imagenExtension;
            //$rutaImagen = 'images/'.$folder.'/'.$idEdificio.$tipo.".".$imagenExtension;

            $directorioImagen = 'public/file/banner-edificio/'.$idEdificio.".".$imagenExtension;
            $rutaImagen = 'file/banner-edificio/'.$idEdificio.".".$imagenExtension;

            if($imagenExtension!='png' && $imagenExtension!='jpg'){
                $response->setContent(\Zend\Json\Json::encode(array('response'=>'noformat')));
            }else{
                if($tamanoImagen <= 1143385){ 
                    copy($rutaTemporal, $directorioImagen);
                    if(file_exists($directorioImagen)){
                        //guardar ruta de la imagen
                        //if($tipoImagen == 'logo') $data = array("edi_vc_logo"=>$idEdificio."_logo.".$imagenExtension);
                        //else $data = array("edi_vc_img"=>$idEdificio."-banner.".$imagenExtension);
                        $data = array("edi_vc_img"=>$idEdificio.".".$imagenExtension);
                        $adapter = $this->getServiceLocator()->get('Zend\Db\Adapter\Adapter');
                        $sql=new Sql($adapter);
                        $update=$sql->update()
                        ->table('edificio')
                        ->set($data)
                        ->where(array('edi_in_cod'=>$idEdificio));
                        try{
                            $statement=$sql->prepareStatementForSqlObject($update)->execute();
                            $response->setContent(\Zend\Json\Json::encode(array('response'=>'success','rutaimagen'=>$rutaImagen)));
                        }catch(\Exception $err) {
                            throw $err;
                            $response->setContent(\Zend\Json\Json::encode(array('response'=>'error_update')));
                        }
                    }else{
                        $response->setContent(\Zend\Json\Json::encode(array('response'=>'error')));
                    }
                }else{
                    $response->setContent(\Zend\Json\Json::encode(array('response'=>'tamanoexcedido')));
                }
            }

            return $response;
        }
    }

    public function eliminarArchivoAction()
    {
        $session=new Container('User');
        $edificioIdSeleccionado=$session->offsetGet('edificioId');
        $request=$this->getRequest();
        $response = $this->getResponse();
        $edificio = $this->getServiceLocator()->get("Mantenimiento\Model\EdificioTable");
        if($request->isPost()){
            $params=$request->getPost();
            $edificio = $edificio->deleteArchivo($params,$edificioIdSeleccionado);
            $response->setContent(\Zend\Json\Json::encode($edificio));
        }
        return $response;
    }

    

    public function multipleFiles(array $_files, $top = TRUE)
    {
        $files = array();
        foreach($_files as $name=>$file){
            if($top) $sub_name = $file['name'];
            else $sub_name = $name;
            
            if(is_array($sub_name)){
                foreach(array_keys($sub_name) as $key){
                    $files[$name][$key] = array(
                        'name'     => $file['name'][$key],
                        'type'     => $file['type'][$key],
                        'tmp_name' => $file['tmp_name'][$key],
                        'error'    => $file['error'][$key],
                        'size'     => $file['size'][$key],
                    );
                    $files[$name] = multiple($files[$name], FALSE);
                }
            }else{
                $files[$name] = $file;
            }
        }
        return $files;
    }

}

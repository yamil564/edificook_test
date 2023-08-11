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

class CrepController extends AbstractActionController
{
    private $idEdificio=null;
    private $idUsuario=null;
    private $idEmpresa=null;

    public function __construct()
    {
        $session=new Container('User');
        $this->idEdificio=$session->offsetGet('edificioId');
        $this->idUsuario=$session->offsetGet('userId');
        $this->idEmpresa=$session->offsetGet('empId');
    }

    public function indexAction()
    {
        return new ViewModel();
    }

    public function generateCrepAction()
    {
        $request=$this->getRequest();
        $response = $this->getResponse();
        $ingresoModel=$this->getServiceLocator()->get("Finanzas\Model\IngresoTable");

        if($request->isPost()){
            $params=$request->getPost();
            $params['idEmpresa']=$this->idEmpresa;
            $data=$ingresoModel->generateCrep($params);
            $response->setContent(\Zend\Json\Json::encode($data));
        }else{
            $response->setContent(\Zend\Json\Json::encode(array("response"=>false)));
        }

        return $response;
    }

    public function changeEstadoCrepAction()
    {
        $request=$this->getRequest();
        $response=$this->getResponse();
        $ingresoModel=$this->getServiceLocator()->get("Finanzas\Model\IngresoTable");
        $data=$ingresoModel->changeEstadoCrep();
        $response->setContent(\Zend\Json\Json::encode($data));
        return $response;
    }

    public function uploadCrepAction()
    {
        $request=$this->getRequest();
        $response=$this->getResponse();
        $ingresoModel=$this->getServiceLocator()->get("Finanzas\Model\IngresoTable");

        if($request->isPost()){
            $params=$request->getPost();
            $file = $this->multipleFiles($_FILES);
            $filter_data=array(
                "file"=>$file
            );
            $data = $ingresoModel->uploadCrep($filter_data);
            $response->setContent(\Zend\Json\Json::encode($data));
        }else{
            $response->setContent(\Zend\Json\Json::encode(array("response"=>false)));
        }

        return $response;
    }

    public function listarFileRecaudacionAction()
    {
        $request=$this->getRequest();
        $response=$this->getResponse();
        $ingresoModel=$this->getServiceLocator()->get("Finanzas\Model\IngresoTable");

        if($request->isPost()){
            $params=$request->getPost();
            $data = $ingresoModel->getListFileRecaudacion($params);
            $response->setContent(\Zend\Json\Json::encode($data));
        }else{
            $response->setContent(\Zend\Json\Json::encode(array("response"=>false)));
        }

        return $response;
    }

    public function deleteCrepAction()
    {
        $request=$this->getRequest();
        $response=$this->getResponse();
        $ingresoModel=$this->getServiceLocator()->get("Finanzas\Model\IngresoTable");

        if($request->isPost()){
            $params=$request->getPost();
            $data = $ingresoModel->deleteCrep($params);
            $response->setContent(\Zend\Json\Json::encode($data));
        }else{
            $response->setContent(\Zend\Json\Json::encode(array("response"=>false)));
        }

        return $response;
    }

    public function deleteRegisterCrepAction()
    {
        $request=$this->getRequest();
        $response=$this->getResponse();
        $ingresoModel=$this->getServiceLocator()->get("Finanzas\Model\IngresoTable");

        if($request->isPost()){
            $params=$request->getPost();
            $data = $ingresoModel->deleteRegisterCrep($params);
            $response->setContent(\Zend\Json\Json::encode($data));
        }else{
            $response->setContent(\Zend\Json\Json::encode(array("response"=>false)));
        }

        return $response;
    }

    public function transferirCrepAction()
    {
        $request=$this->getRequest();
        $response=$this->getResponse();
        $ingresoModel=$this->getServiceLocator()->get("Finanzas\Model\IngresoTable");

        if($request->isPost()){
            $params=$request->getPost();
            $data = $ingresoModel->transferirCrep($params);
            $response->setContent(\Zend\Json\Json::encode($data));
        }else{
            $response->setContent(\Zend\Json\Json::encode(array("response"=>false)));
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

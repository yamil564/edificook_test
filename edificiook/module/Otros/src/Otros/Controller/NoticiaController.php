<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/ZendSkeletonModule for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Otros\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\Db\Adapter\Adapter;
use Zend\Session\Container;
use Zend\Db\Sql\Sql;
use Zend\Db\Sql\Expression;
use Zend\View\Helper\AbstractHelper;
use Zend\Mail\Message;

class NoticiaController extends AbstractActionController
{
    
    private $idedificio=null;
    private $idusuario=null;

    public function __construct(){
        $session=new Container('User');
        $this->idedificio = $session->offsetGet('edificioId');
        $this->idusuario = $session->offsetGet('userId');
    }

    public function indexAction()
    {
        return new ViewModel();
    }

    public function gridNoticiaAction()
    {
    	$request=$this->getRequest();
        $response = $this->getResponse();

        if($request->isPost()){
            $params=$request->getPost();
            $noticia = $this->getServiceLocator()->get("Otros\Model\NoticiaTable");
            $noticia = $noticia->listarNoticia($this->idedificio, $params);
            $response->setContent(\Zend\Json\Json::encode($noticia));
        }else{
            $response->setContent(\Zend\Json\Json::encode(array(null)));
        }

        return $response;
    }

    public function listarAction()
    {
        $request=$this->getRequest();
        $response = $this->getResponse();

        if($request->isPost()){
            $params=$request->getPost();
            $noticia = $this->getServiceLocator()->get("Otros\Model\NoticiaTable");
            $noticia = $noticia->listarNoticias($this->idedificio, $params);
            $response->setContent(\Zend\Json\Json::encode($noticia));
        }else{
            $response->setContent(\Zend\Json\Json::encode(array(null)));
        }

        return $response;
    }

    public function saveAction()
    {
        $request=$this->getRequest();
        $response = $this->getResponse();

        if($request->isPost()){
            $params=$request->getPost();
            $accion = $params['accion'];

            $params['filesnoticia'] = $_FILES['imagen'];
            $params['idUsuario']=$this->idusuario;
            $params['idEdificio']=$this->idedificio;
            $noticia = $this->getServiceLocator()->get("Otros\Model\NoticiaTable");

            $accionTabla='';
            if($accion == 'update'){
                $noticia = $noticia->updatex($params);
                $accionTabla='Editar';
            }else{
                $noticia = $noticia->add($params);
                $accionTabla='Guardar';
            }

            $response->setContent(\Zend\Json\Json::encode($noticia));
        }else{
            $response->setContent(\Zend\Json\Json::encode(array(null)));
        }

        return $response;
    }

    public function deleteAction()
    {
        $request=$this->getRequest();
        $response = $this->getResponse();

        if($request->isPost()){
            $params=$request->getPost();
            $accion = $params['accion'];
            $params['idUsuario']=$this->idusuario;
            $params['idEdificio']=$this->idedificio;
            $noticia = $this->getServiceLocator()->get("Otros\Model\NoticiaTable");
            $noticia = $noticia->del($params);
            $response->setContent(\Zend\Json\Json::encode($noticia));
        }else{
            $response->setContent(\Zend\Json\Json::encode(array(null)));
        }

        return $response;
    }

}

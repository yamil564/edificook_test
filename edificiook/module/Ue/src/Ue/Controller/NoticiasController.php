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

class NoticiasController extends AbstractActionController
{

    public function __construct()
    {
        $session=new Container('User');
        $this->idEdificio = $session->offsetGet('edificioId');
        $this->idEmpresa = $session->offsetGet('empId');
        $this->idUsuario = $session->offsetGet('userId');
    }

    public function indexAction()
    {
        return new ViewModel(array(null));
    }

    public function loadAction()
    {
        $request=$this->getRequest();
        $response = $this->getResponse();

        if($request->isPost()){
            $params=$request->getPost();
            $params['idEdificio'] = $this->idEdificio;
            $noticias = $this->getServiceLocator()->get("Ue\Model\NoticiasTable");
            $noticias = $noticias->noticias($params);
            $response->setContent(\Zend\Json\Json::encode($noticias));
        }else{
            $response->setContent(\Zend\Json\Json::encode(array(null)));
        }

        return $response;
    }

    public function detalleAction()
    {
        $request=$this->getRequest();
        $response = $this->getResponse();

        if($request->isPost()){
            $params=$request->getPost();
            $noticias = $this->getServiceLocator()->get("Ue\Model\NoticiasTable");
            $noticias = $noticias->info($params);
            
            $rp['noticia'] = $noticias;

            $directorio = opendir(getcwd()."/public/file/noticia/".$params['idNoticia']); //ruta actual
            // getcwd()."/public/file/noticia/".$idnoticia.".".$extension;
            while ($archivo = readdir($directorio)) //obtenemos un archivo y luego otro sucesivamente
            {
                if (!is_dir($archivo))//verificamos si es o no un directorio
                {

                    //Explode parte en trozos el string cada vez que encuentre el signo de puntuación "."

                    $valores = explode(".", $archivo);

                    //Formato valores

                    /*

                    $valores[0] = "imagen";

                    $valores[1] = "png";
                    Para coger la extensión debemos retornar el ultimo elemento del array $valores.

                    */

                    $ext = $valores[count($valores)-1];
                    // echo "[".$archivo . "]<br />"; //de ser un directorio lo envolvemos entre corchetes
                    // $rp['archivo'] = $archivo;
                    // $ext = new SplFileInfo($archivo);
                    // var_dump($info->getExtension());

                    if($ext == 'pdf'){
                        $rp['archivo'] = $archivo;
                    }
                }
            }

            $response->setContent(\Zend\Json\Json::encode($rp));

        }else{
            $response->setContent(\Zend\Json\Json::encode(array(null)));
        }

        

        return $response;
    }


}

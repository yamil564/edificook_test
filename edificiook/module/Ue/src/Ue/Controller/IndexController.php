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

class IndexController extends AbstractActionController
{

	public function __construct()
    {
        $session=new Container('User');
        $this->idEdificio=$session->offsetGet('edificioId');
    }

    public function indexAction()
    {
        $edificio = $this->getServiceLocator()->get("Mantenimiento\Model\EdificioTable");
        $noticias = $this->getServiceLocator()->get("Ue\Model\NoticiasTable");
        $valores = array(
            'tipo' => $edificio->listarTipo(),
            'edificio'=>$edificio->listarEdificioPorId($this->idEdificio),
            'noticias'=>$noticias->listarMaximoNoticias($this->idEdificio, 5)
        );
        return new ViewModel($valores);
    }
    
}

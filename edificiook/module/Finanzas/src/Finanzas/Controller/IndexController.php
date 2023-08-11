<?php
/**
 * Zend Framework (http://framework.zend.com/)
 * 
 * @author    Meler Carranza Silva
 * @link      www.edificiook.com
 * @copyright www.knd.pe 2016
 * @license   KND.SAC
 */

namespace Finanzas\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\Session\Container;

class IndexController extends AbstractActionController
{
    private $edificioId=null;
    private $usuarioId=null;

    public function __construct()
    {
        $session=new Container('User');
        $this->edificioId=$session->offsetGet('edificioId');
        $this->usuarioId=$session->offsetGet('userId');
    }

    public function indexAction()
    {
        $directorioRaiz=$this->getServiceLocator()->get('config')['configbase']['proyecto']['raiz'];
        $edificioTable = $this->getServiceLocator()->get('EdificioTable');
        $params=array('edificioId'=>$this->edificioId,'userId'=>$this->usuarioId,'menugrupo'=>'finanzas');
        $itemCurrentMenuGrupo=$edificioTable->getItemsDeMenuGrupo($params);

        return new ViewModel(array('pageItems'=>$itemCurrentMenuGrupo,'directorioraiz'=>$directorioRaiz));
    }
}

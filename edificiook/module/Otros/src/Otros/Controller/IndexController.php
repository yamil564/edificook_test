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
        $params=array('edificioId'=>$this->edificioId,'userId'=>$this->usuarioId,'menugrupo'=>'otros');
        $itemCurrentMenuGrupo=$edificioTable->getItemsDeMenuGrupo($params);

        return new ViewModel(array('pageItems'=>$itemCurrentMenuGrupo,'directorioraiz'=>$directorioRaiz));
    }
}

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

class SaldoenbancosController extends AbstractActionController
{
    public function indexAction()
    {
        return $this->forward()->dispatch('Application\Controller\Index',array('action'=>'opcionpendientedecontruccion'));
        return new ViewModel();
    }
}

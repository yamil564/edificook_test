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

namespace Reporte\Controller;

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
		$params=array('edificioId'=>$this->edificioId,'userId'=>$this->usuarioId,'menugrupo'=>'reportes');
		$itemCurrentMenuGrupo=$edificioTable->getItemsDeMenuGrupo($params);

		return new ViewModel(array('pageItems'=>$itemCurrentMenuGrupo,'directorioraiz'=>$directorioRaiz));
	}
}

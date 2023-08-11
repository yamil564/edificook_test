<?php
/**
 * edificioOk (https://www.edificiook.com)
 * creado por: Meler Carranza, 24/03/2016.
 * ultima modificacion por: Meler Carranza
 * Fecha Modificacion: 24/03/2016.
 * Descripcion: Formulario
 *
 * @link      https://www.edificiook.com
 * @copyright Copyright (c) 2011-2016 KND S.A.C (http://www.knd.pe)
 * @license   http://www.edificiook.com/license/comercial Software Comercial
 * 
 */

namespace Proceso\Form;
use Zend\Form\Form;


class CuotaForm extends Form {

	public function __construct($name=null){
		parent::__construct('cuota');

		$this->add(array(
			'name'=>'selMes',
			'type'=>'Select',
			
		));

		$this->add(array(
			'name'=>'selYear',
			'type'=>'Select',
		));
	}
}
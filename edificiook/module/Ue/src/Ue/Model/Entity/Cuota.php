<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      www.ejemplosdeprogramacion.com
 * @copyright Meler Carranza
 * @license   Meler Carranza
 */

namespace Proceso\Model\Entity;

 use Zend\InputFilter\InputFilter;
 use Zend\InputFilter\InputFilterAwareInterface;
 use Zend\InputFilter\InputFilterInterface;


class Cuota{
	
    
	public $mes;
	public $year;
	protected $inputFilter;

	public function exchangeArray($data){
		$this->mes=(!empty($data['selMes']))? $data['selMes']:null;
		$this->year=(!empty($data['selYear'])) ? $data['selYear']:null;
	}

	// Add the following method:
    public function getArrayCopy()
    {
         return get_object_vars($this);
    }

	public function setInputFilter(InputFilterInterface $inputFilter){
		throw new Exception("No usado");
	}

	public function getInputFilter(){
		if(!$this->inputFilter){
			$inputFilter=new InputFilter();	

			$inputFilter->add(array(
				'name'=>'selMes',
				'require'=>true,
			));

			$inputFilter->add(array(
				'name'=>'selYear',
				'required'=>true,
			));

			$this->inputFilter=$inputFilter;
		}

		return $this->inputFilter;
	}

}
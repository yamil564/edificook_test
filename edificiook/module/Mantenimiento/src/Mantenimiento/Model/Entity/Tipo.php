<?php

namespace Mantenimiento\Model\Entity;

use Zend\Db\TableGateway\TableGateway;
use Zend\Db\Adapter\Adapter;

class Tipo extends TableGateway
{
    public function __construct(Adapter $adapter = null, $databaseSchema = null, ResultSet $selectResultPrototype = null)
    {
        return parent::__construct('tipo', $adapter, $databaseSchema,$selectResultPrototype);
    }

	public function listarTipo()
	{
	    $datos = $this->select();
	    return $datos->toArray();
	}
}


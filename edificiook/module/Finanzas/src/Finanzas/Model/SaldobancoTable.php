<?php

/*
@ Creado Por: Meler Carranza-
*/
namespace Finanzas\Model;
use Zend\Db\Sql\Sql;
use Zend\Db\Sql\Select;
use Zend\Db\Adapter\Adapter;
use Zend\Db\Sql\Expression;
use Zend\Session\Container;

class SaldobancoTable{

	private $adapter=null;
	private $idUsuario=null;
	private $yearSelected=null;


	public function __construct(Adapter $adapter)
	{
		$this->adapter=$adapter;

		$session=new Container('User');
        $this->idUsuario=$session->offsetGet('userId');

	}


	public function listarGridAnual($params){


		$this->yearSelected=isset($params['year'])?$params['year']:date('Y');
		
		$response=array();

		$listaEdificios=$this->getEdificios();
		if(empty($listaEdificios)){
			return ;
		}

		$i=0;
		foreach ($listaEdificios as $edificio) {
			$cells=array();
			$cells[0]=$edificio['id'];
			$cells[1]=$edificio['nombre'];


			$saldoBancario=$this->getSaldoBancarioByEdificio($edificio['id']);

			$totalAnual=0;
			$rsIndex=0;
			for ($j=1; $j <= 12; $j++) {
				$mes_saldo=isset($saldoBancario[$rsIndex]['mes'])? $saldoBancario[$rsIndex]['mes']:null;
				if($j==$mes_saldo){
					$cells[]=$saldoBancario[$rsIndex]['id']."|".$saldoBancario[$rsIndex]['saldo'];
					$totalAnual+=$saldoBancario[$rsIndex]['saldo'];
					$rsIndex++;
				}else{
					$cells[]='';
				}
			}

			$cells[]=number_format($totalAnual,2,'.',',');

			$response['rows'][$i]['id'] =$edificio['id'];
			$response['rows'][$i]['cell']=$cells;

			$i++;
		}

		return $response;
	}


	private function getEdificios(){
		$adapter=$this->adapter;
        $sql = new Sql($this->adapter);

        $select = $sql->select()
            ->from(array('uedificio' =>'usuario_edificio'))
            ->columns(array())
            ->join(array("edi"=>'edificio'),'edi.edi_in_cod=uedificio.edi_in_cod',array('id'=>'edi_in_cod','nombre'=>'edi_vc_des'))
            ->where(array('edi_in_est'=>1,"uedificio.usu_in_cod"=>$this->idUsuario));
        $select->quantifier('DISTINCT');
        $selectEdificio=$sql->buildSqlString($select);


		$rsEdificios=$adapter->query($selectEdificio, $adapter::QUERY_MODE_EXECUTE)->toArray();

        return $rsEdificios;
    }



    private function getSaldoBancarioByEdificio($edificioId){
    	$adapter=$this->adapter;
        $sql = new Sql($this->adapter);


        $queryString='';
        for ($i=1; $i <= 12; $i++) {
        	
        	if($i>1){
				$queryString.="UNION";
			}
			
        	$queryString.="(SELECT sab_in_cod as id, sab_do_sal AS saldo, MONTH (sab_da_fecha) as mes FROM saldo_bancario
			WHERE edi_in_cod={$edificioId}
			AND YEAR(sab_da_fecha)={$this->yearSelected}
			AND MONTH(sab_da_fecha)=$i
			ORDER BY sab_da_fecha desc limit 1)";
        }

		$rsSaldoBancario=$adapter->query($queryString, adapter::QUERY_MODE_EXECUTE)->toArray();


		return $rsSaldoBancario;
    }

    public function guardar($params){

    	$adapter=$this->adapter;
    	$sql=new Sql($adapter);

    	$row_id=$params['row-id'];
    	$fecha=str_replace("/", "", $params['fecha']);
    	$fecha=date('Y-m-d',strtotime($fecha));
    	$saldo=$params['saldo'];



    	//Validar para no permitir registrar movimientos en una misma fecha en un edificio.
    	$sqlCount="SELECT count(*) as total FROM saldo_bancario where edi_in_cod=$row_id and sab_da_fecha='$fecha'";
    	$rsCount=$adapter->query($sqlCount, adapter::QUERY_MODE_EXECUTE)->toArray();
		if($rsCount[0]['total']>0){
			return ["tipo"=>"advertencia","mensaje"=>"Ya existe un movimiento registrado con la misma fecha."];
		}


    	$dataConsumo =[
    		'edi_in_cod'=> $row_id,
            'sab_da_fecha'=> $fecha,
            'sab_do_sal'=> $saldo,
    	];

    	$insert = $sql->insert('saldo_bancario');
    	$insert->values($dataConsumo);
        $sql->prepareStatementForSqlObject($insert)->execute();

        return ['tipo'=>'informativo',"mensaje"=>"Saldo guardado con exito."];
    }



    public function actualizar($params){
    	$adapter=$this->adapter;
    	$sql=new Sql($adapter);

    	$codigo=$params['codigo'];
    	$row_id=$params['row-id'];
    	$fecha=str_replace("/", "", $params['fecha']);
    	$fecha=date('Y-m-d',strtotime($fecha));

    	$saldo=$params['saldo'];

    	//Validar para no permitir registrar movimientos en una misma fecha en un edificio.
    	$sqlCount="SELECT count(*) as total FROM saldo_bancario where edi_in_cod=$row_id and sab_da_fecha='$fecha' and sab_in_cod!=$codigo ";
    	$rsCount=$adapter->query($sqlCount, adapter::QUERY_MODE_EXECUTE)->toArray();
		if($rsCount[0]['total']>0){
			return ["tipo"=>"advertencia","mensaje"=>"Ya existe un movimiento registrado con con la misma fecha, por favor verificar"];
		}

    	$dataSaldo =[
    		'edi_in_cod'=> $row_id,
            'sab_da_fecha'=> $fecha,
            'sab_do_sal'=> $saldo,
    	];

    	$update = $sql->update()
    	->table('saldo_bancario')->set($dataSaldo)
    	->where(['sab_in_cod'=>$codigo]);
		
        $sql->prepareStatementForSqlObject($update)->execute();

        return ['tipo'=>'informativo',"mensaje"=>"Saldo guardado con exito."];
    }


    public function getSaldoByCodigo($codigo){
    	$adapter=$this->adapter;
    	$sql=new Sql($adapter);

    	$queryString="SELECT sab_in_cod as id, sab_da_fecha as fecha, sab_do_sal AS saldo FROM saldo_bancario WHERE sab_in_cod=$codigo";
		$rsSaldoBancario=$adapter->query($queryString, adapter::QUERY_MODE_EXECUTE)->toArray();


		return $rsSaldoBancario[0];
    }

}
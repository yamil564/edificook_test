<?php

/**
 * edificioOk (https://www.edificiook.com).
 * creado por: Meler Carranza, 04/07/2017.
 * ultima modificacion por: Meler Carranza.
 * Fecha Modificacion: .
 * Descripcion: Script para guardar datos de ingreso en la opcion de importación.
 *
 * @author    Fidel Thompson Fonseca.
 * @link      https://www.edificiook.com.
 * @copyright Copyright (c) 2011-2017 KND S.A.C (http://www.knd.pe).
 * @license   http://www.edificiook.com/license/comercial Software Comercial.
 */

namespace Finanzas\Model;

use Zend\Db\Sql\Sql;
use Zend\Db\Sql\Select;
//use Zend\Db\TableGateway\AbstractTableGateway;
use Zend\Db\Adapter\Adapter;
use Zend\Db\Sql\Expression;

class ImportaringresoTableGWM{

	private $edificioId=null;
	private $usuarioId=null;
	protected $adapter=null;

	private $conceptoId=null;
	private $mesEmision=null;
	private $yearEmision=null;
	

	public function __construct(Adapter $adapter){
		$this->adapter=$adapter;
	}
	

	public function guardar($dataExcelFomat){
		$adapter=$this->adapter;
		$sql=new Sql($adapter);

		$fechaEmisionDb=date("Y-m-d",strtotime($dataExcelFomat['fechaEmision']));
		$fechaVenceDb=date("Y-m-d",strtotime($dataExcelFomat['fechaVence']));

		$this->mesEmision=date("m",strtotime($fechaEmisionDb));
		$this->yearEmision=date("Y",strtotime($fechaEmisionDb));

	    $this->edificioId=$dataExcelFomat['edificioId'];
	    $this->empresaId=$dataExcelFomat['empresaId'];
	    $this->usuarioId=$dataExcelFomat['usuarioId'];

	    /*
	    *
	    */
	    //Buscar id del concepto.
		$conceptoNombre=$dataExcelFomat['conceptoNombre'];
	    $queryConcepto="SELECT con_in_cod as conceptoId from concepto
	                      where emp_in_cod in(0,{$this->empresaId})
	                      and con_vc_des like '$conceptoNombre'
	                      and con_vc_tip='INGRESO'
	                      and con_in_est!=0 order by emp_in_cod asc limit 1";
	    
	    $rowConcepto = $adapter->query($queryConcepto,$adapter::QUERY_MODE_EXECUTE)->current();
	    if(empty($rowConcepto['conceptoId'])){
	      return ["tipo"=>"error","mensaje"=>"El concepto ingresado en el formato no se encuentra registrado en el sistema"];
	    }

	    $this->conceptoId=$rowConcepto['conceptoId'];


    // Iterar la data importada, e ir registrando los datos en el respectiva unidad.

		foreach ($dataExcelFomat['rows'] as $key => $currentItem) {
			$ingresoId=null;

			$unidadNombre=$currentItem["unidadNombre"];
			$queryUnidad = "SELECT uni_in_cod FROM unidad WHERE edi_in_cod ={$this->edificioId} AND CONCAT(TRIM(uni_vc_tip),' ',TRIM(uni_vc_nom)) ='$unidadNombre' AND uni_in_est!=0";
			$rowCodigoUnidad = $adapter->query($queryUnidad,$adapter::QUERY_MODE_EXECUTE)->current();

			if(!isset($rowCodigoUnidad['uni_in_cod'])){
			    continue;
			}

            $currentIdUnidad = $rowCodigoUnidad['uni_in_cod'];
            $currentRowUnidad=$this->getRowUnidadPorId($currentIdUnidad);
            $totalDeudaUnidad=(float)$currentRowUnidad['deuda'];

            /*
			*Si no existe un ingreso, insertamos un nuevo registro.
			*/
            $rowIngresoPrevioReg=$this->getRowIngresoPrevioRegistro($currentIdUnidad);

            if(!empty($rowIngresoPrevioReg)){
                /*
                *	Si existe un ingreso previamente registrado y el estado es [ 1 ]
                    podemos modificar los conceptos.

                *	Si el estado es [0,2] no podemos modificar conceptos, puesto que ya existe
                    ingresos parciales(Asignamos null a la variable $ingresoId).
                */
                if($rowIngresoPrevioReg['ing_in_est']==1){
                    $ingresoId=$rowIngresoPrevioReg['ing_in_cod'];
                    /*
                        *Restamos a la deuda de unidad la sumatoria de todos los conceptos de
                        ingreso en modificación ( cuando concluya hacemos el mismo procedimiento
                        y lo sumamos a la deuda de unidad).
                    */
                    $totalDeudaUnidad-=$this->getSumTotalConceptoIngreso($ingresoId);
                    $this->deleteConceptoIngresoPreRegistro($this->conceptoId, $ingresoId);
                }else{
                    $ingresoId=null;
                }
            }else{
                $dataIngreso=array(
                    'uni_in_cod'=>$currentIdUnidad,
                    'ing_in_usu'=>$currentRowUnidad['propietario'],
                    'ing_in_resi'=>$currentRowUnidad['residente'],
                    'ing_da_fecemi'=>$fechaEmisionDb,
                    'ing_da_fecven'=>$fechaVenceDb,
                    'ing_in_nroser'=>1,
                    'ing_in_nrodoc'=>1,
                    'ing_in_est'=>1,
                );
                $insertIngreso=$sql->insert('ingreso')->values($dataIngreso);
                $statementInsertIngreso=$sql->prepareStatementForSqlObject($insertIngreso);
                $ingresoId=$statementInsertIngreso->execute()->getGeneratedValue();
            }

            if($ingresoId==null){
              continue;
            }

            $numeracionDoc=$this->numeracionDeDocumento();
            $nroSerie=$numeracionDoc['serie'];
            $nroDocumento=$numeracionDoc['nroDoc'];

            $ci_importe=0;
            if(!empty($currentItem['importe'])){
                $ci_importe=$currentItem['importe'];
            }
		
            $dataConceptoCuotaMant=array(
                        'con_in_cod'=>$this->conceptoId,
                        'ing_in_cod'=>$ingresoId,
                        'coi_da_fecemi'=>$fechaEmisionDb,
                        'coi_da_fecven'=>$fechaVenceDb,
                        'coi_do_imp'=>$ci_importe,
                        'coi_do_subtot'=>$ci_importe,
                        'coi_in_nroser'=>$nroSerie,
                        'coi_in_nrodoc'=>$nroDocumento
            );
            $insertConceptoCuota=$sql->insert('concepto_ingreso')->values($dataConceptoCuotaMant);
            $statementInsertConceptoCuota=$sql->prepareStatementForSqlObject($insertConceptoCuota);
            $statementInsertConceptoCuota->execute();
				

            $sumTotalConceptoIngreso=$this->getSumTotalConceptoIngreso($ingresoId);
            $format_total=$this->getRedondeo($sumTotalConceptoIngreso);
            $this->actualizarConceptoRedondeo(
                $ingresoId,
                [
                    "fechaEmi"=>$fechaEmisionDb,
                    "fechaVence"=>$fechaVenceDb,
                    "serie"=>$nroSerie,
                    "nroDoc"=>$nroDocumento
                ]);

            $sumTotalConceptoIngreso=$format_total['monto'];

				
            $deudaIngreso=$sumTotalConceptoIngreso;

            /*
            *	Si la unidad tiene saldo a favor y la sumatoria de los conceptos es mayor a (0)
            *	registramos un ingreso parcial.
            */
            if($totalDeudaUnidad<0 && $sumTotalConceptoIngreso>0){

                $rowUltimoIngresoParcial=$this->getRowIngresoParcial($currentRowUnidad['codigoUltimoIP']);
                if(!empty($rowUltimoIngresoParcial)){
                    $totalDeudaUnidad=$totalDeudaUnidad + ($sumTotalConceptoIngreso * 1);
                    $importe=($totalDeudaUnidad>=0) ? ($sumTotalConceptoIngreso - $totalDeudaUnidad) : $sumTotalConceptoIngreso;
                    $deudaIngreso=$sumTotalConceptoIngreso - $importe;

                    //Determina el estado de ingreso de acuerdo al a la suma de ingresos parciales vs concepto de ingreso.
                    $estadoIngreso=$deudaIngreso<=0 ? 0 :($importe==0 ? 1:2);


                    $dataInsertPago=array(
                        'ing_in_cod'=>$ingresoId,
                        'usu_in_cod'=>$this->usuarioId,
                        'ipa_in_nroser'=>$nroSerie,
                        'ipa_in_nrodoc'=>$nroDocumento,
                        'ipa_vc_numope'=>$rowUltimoIngresoParcial['numOperacion'],
                        'ipa_vc_tipdoc'=>"",
                        'ipa_vc_ban'=>$rowUltimoIngresoParcial['banco'],
                        'ipa_da_fecemi'=>$fechaEmisionDb,
                        'ipa_da_fecven'=>$fechaVenceDb,
                        'ipa_da_fecpag'=>$rowUltimoIngresoParcial['fechaPago'],
                        'ipa_do_int'=>'0.00',
                        'ipa_te_obs'=>'',
                        'ipa_do_tot'=>$importe,
                        'ipa_do_imp'=>$importe,
                        'ipa_do_sal'=>$deudaIngreso,
                        'ipa_do_impban'=>0,
                        'ipa_in_codimp'=>$rowUltimoIngresoParcial['importeCodigo'],
                    );

                    $insertPago=$sql->insert('ingreso_parcial')->values($dataInsertPago);
                    $statementUpdateIngresoParcial=$sql->prepareStatementForSqlObject($insertPago);
                    $lastIngresoParcial=$statementUpdateIngresoParcial->execute()->getGeneratedValue();
                }
            }else{
                $totalDeudaUnidad+=$sumTotalConceptoIngreso;
            }

            //Actualizamos total en ingreso
            $dataUpdateIngreso=array(
                'ing_in_usu'=>$currentRowUnidad['propietario'],
                'ing_in_resi'=>$currentRowUnidad['residente'],
                'ing_da_fecemi'=>$fechaEmisionDb,
                'ing_da_fecven'=>$fechaVenceDb,
                'ing_in_nroser'=>$nroSerie,
                'ing_in_nrodoc'=>$nroDocumento,
                'ing_do_sal'=>$deudaIngreso,
            );

            if(!empty($estadoIngreso)){
                $dataUpdateIngreso['ing_in_est']=$estadoIngreso;
            }

            $updateIngreso=$sql->update()->table('ingreso')->set($dataUpdateIngreso)->where(array('ing_in_cod'=>$ingresoId));
            $statementUpdateIngreso=$sql->prepareStatementForSqlObject($updateIngreso);
            $statementUpdateIngreso->execute();

            //Actualizamos la deuda de unidad.
            $dataUpdateUnidad=array('uni_do_deu'=>$totalDeudaUnidad);
            if(isset($lastIngresoParcial)){
                $dataUpdateUnidad['uni_in_ultingpar']=$lastIngresoParcial;
            }
            $updateUnidad=$sql->update()->table('unidad')->set($dataUpdateUnidad)->where(array('uni_in_cod'=>$currentIdUnidad));
            $statementUpdateUnidad=$sql->prepareStatementForSqlObject($updateUnidad);
            $statementUpdateUnidad->execute();
		}

		return ["tipo"=>"Excelente","mensaje"=>"Excel importado con éxito."];
  }




	private function getRowUnidadPorId($unidadId){
		$sql=new Sql($this->adapter);
		$selectUnidad=$sql->select()
			->from('unidad')
			->columns(array('propietario'=>'uni_in_pro','residente'=>'uni_in_pos','deuda'=>'uni_do_deu','codigoUltimoIP'=>'uni_in_ultingpar'))
			->where(array('uni_in_cod'=>$unidadId));
		$statement=$sql->prepareStatementForSqlObject($selectUnidad);
		return $statement->execute()->current();
	}

	private function getRowIngresoPrevioRegistro($unidadId){

		$sql=new Sql($this->adapter);
		$selectIngreso=$sql->select()
			->from(array('ing'=>'ingreso'))
			->columns(array('ing_in_cod','ing_in_est'))
			->where(array('uni_in_cod'=>$unidadId,
						"MONTH(ing_da_fecemi)=".$this->mesEmision,
						"YEAR(ing_da_fecemi)=".$this->yearEmision));

		$statement=$sql->prepareStatementForSqlObject($selectIngreso);
		$rsIngreso=$statement->execute()->current();
		return $rsIngreso;
	}

	private function getSumTotalConceptoIngreso($ingresoId){
		$sql=new Sql($this->adapter);
		$selectSumCOI=$sql->select()
			->from('concepto_ingreso')
			->columns(array('total'=>new Expression('SUM(coi_do_subtot)')))
			->where(array('ing_in_cod'=>$ingresoId));
		$statement=$sql->prepareStatementForSqlObject($selectSumCOI);
		$rsSum=$statement->execute()->current();
		return $rsSum['total'];
	}

	//eliminar todo los conceptos de ingreso registrados automaticamente(producto de una generación de cuota)
	private function deleteConceptoIngresoPreRegistro($conceptoId,$ingresoId){
		$sql=new Sql($this->adapter);
		$delete=$sql->delete('concepto_ingreso')
		->where(array('ing_in_cod'=>$ingresoId,'con_in_cod'=>$conceptoId));
		
		$statement=$sql->prepareStatementForSqlObject($delete);
		$statement->execute();
	}

	private function numeracionDeDocumento(){
		$sql=new Sql($this->adapter);
		$selectEdificio=$sql->select()
			->from(array('edi'=>'edificio'))
			->columns(
				array(
					'serie'=>'edi_in_numser',
					'nroDoc'=>'edi_in_numdoc',
				)
			)
			->where(array('edi_in_cod'=>$this->edificioId));

		$statement=$sql->prepareStatementForSqlObject($selectEdificio);
		$rsEdificio=$statement->execute()->current();

		if(empty($rsEdificio['nroDoc'])){
			$rsEdificio['nroDoc']=1;
		}else{
			$rsEdificio['nroDoc']+=1;
		}

		if(empty($rsEdificio['serie'])){
			$rsEdificio['serie']=1;
		}else{
			if(strlen($rsEdificio['serie'])>4){
				$rsEdificio['serie']+=1;
				$rsEdificio['nroDoc']=1;
			}
		}

		//Actualizamos la numeracion en la tabla edificio la numeracion del documento generado.
		$dataNumeracion=array('edi_in_numser'=>$rsEdificio['serie'],'edi_in_numdoc'=>$rsEdificio['nroDoc']);
		$updateEdificio=$sql->update()->table('edificio')
			->set($dataNumeracion)->where(array('edi_in_cod'=>$this->edificioId));
		$sql->prepareStatementForSqlObject($updateEdificio)->execute();

		return $rsEdificio;
	}

	private function getRowIngresoParcial($ingresoparcialId){
		$sql=new Sql($this->adapter);
		$selectIngesoParcial=$sql->select()
			->from(array('ipa'=>'ingreso_parcial'))
			->columns(array(
				'numOperacion'=>'ipa_vc_numope',
				'banco'=>'ipa_vc_ban',
				'fechaPago'=>'ipa_da_fecpag',
				'importeCodigo'=>'ipa_in_codimp',
				'tipoDoc'=>'ipa_vc_tipdoc'))
			->where(array('ipa_in_cod'=>$ingresoparcialId));
		$statement=$sql->prepareStatementForSqlObject($selectIngesoParcial);
		$rsIngreso=$statement->execute()->current();
		return $rsIngreso;
	}


	private function actualizarConceptoRedondeo($ingresoId, $rowIngreso=null){
		$sql=new Sql($this->adapter);

		$idConceptoRedondeo=395;

		$rowEdificio=$this->getDetalleEdificio($this->edificioId);
		if($rowEdificio['formatoDeRedondeo']!=1){
			return;
		}

		$delete=$sql->delete('concepto_ingreso')->where(array('ing_in_cod'=>$ingresoId,'con_in_cod'=>$idConceptoRedondeo));
		$statement=$sql->prepareStatementForSqlObject($delete);
		$statement->execute();


		$sumTotalConceptoIngreso=$this->getSumTotalConceptoIngreso($ingresoId);

		$format_total=$this->getRedondeo($sumTotalConceptoIngreso);
		if( (string) $format_total['redondeo']!='0.00') {

			$dataConceptoRedondeo=array(
				'con_in_cod'=>$idConceptoRedondeo,
				'ing_in_cod'=>$ingresoId,
				'coi_da_fecemi'=>$rowIngreso['fechaEmi'],
				'coi_da_fecven'=>$rowIngreso['fechaVence'],
				'coi_do_imp'=>$format_total['redondeo'],
				'coi_do_subtot'=>$format_total['redondeo'],
				'coi_in_nroser'=>$rowIngreso['serie'],
				'coi_in_nrodoc'=>$rowIngreso['nroDoc'],
			);
			$insertConceptoRedondeo=$sql->insert('concepto_ingreso')->values($dataConceptoRedondeo);
			$statementInsertConceptoRedondeo=$sql->prepareStatementForSqlObject($insertConceptoRedondeo);
			$statementInsertConceptoRedondeo->execute();
		}
	}


	private function getRedondeo($monto){

		$monto=number_format($monto,2,".","");
		$number_array=explode(".", $monto);

		$firstHalf=$number_array[0];
		$secondaryHalf=$number_array[1];

		$substr_centecimo2=substr($secondaryHalf, 1,1);

		$redondeo="";
		$numero_format=0;

		if($substr_centecimo2>=0 && $substr_centecimo2<5){
			$substr_centecimo2=floatval("0.0".$substr_centecimo2); // format=> 0.00
			$numero_format=$monto - $substr_centecimo2; 
			$redondeo="-".$substr_centecimo2;
		}else{
			$numero_format=$monto + floatval("0.0". (10 - $substr_centecimo2) );
			$redondeo=floatval("0.0". (10 - $substr_centecimo2) );
		}

		return [
			"monto"=>number_format($numero_format,2,".",""),
			"redondeo"=>$redondeo,
		];
	}


	private function getDetalleEdificio()
	{


		$adapter=$this->adapter;

		$stringSql="SELECT edi_vc_numcue as numeroCuenta, edi_vc_des as nombreEdificio, edi_in_round as formatoDeRedondeo FROM edificio WHERE edi_in_cod ={$this->edificioId}";
		$rsEdificio=$adapter->query($stringSql,$adapter::QUERY_MODE_EXECUTE)->current();
		return $rsEdificio;
	}



	public function generarFormato($idedificio)
	{
		$adapter = $this->adapter;
		$sql=new Sql($adapter);

		$name = 'formato-'.time().'.xlsx';
		$file = 'public/temp/ingreso/'.$name;
				
		$objPHPExcel = new \PHPExcel();
        $objSheet = $objPHPExcel->getActiveSheet();
        $objSheet->setTitle('GrupoConcepto de Ingreso');
        $objSheet->getStyle('A1:F1')->getFont()->setBold(true);

        $objSheet->setCellValue('A1', 'FORMATO DE IMPORTACION DE CONCEPTOS');
        $objPHPExcel->setActiveSheetIndex(0)->mergeCells('A1:C1');
       
        $objSheet->setCellValue('A3', 'CONCEPTO');
        $objSheet->setCellValue('A4', 'MES');
        $objSheet->setCellValue('A5', 'AÑO');

        $objSheet->setCellValue('A8', 'N°');
        $objSheet->setCellValue('B8', 'UNIDAD');
        $objSheet->setCellValue('C8', 'IMPORTE S/');

        
        $selectUnidadData=$sql->select()
			->from('unidad')
			->columns(array(
				'unidad'=>new Expression("CONCAT(uni_vc_tip,' ',uni_vc_nom)")
			))
			->where(array(
				'edi_in_cod'=>$idedificio,
				'uni_in_est'=>1,
				'uni_in_pad IS NULL'
			));
		$selectUnidad=$sql->buildSqlString($selectUnidadData);
		$dataUnidad=$adapter->query($selectUnidad,$adapter::QUERY_MODE_EXECUTE)->toArray();
		$totalUnidades = count($dataUnidad) + 8;
		
		$i=9;
		$rowNro=1;
		foreach($dataUnidad as $val){
			$objSheet->setCellValue('A'.$i, $rowNro);
			$objSheet->setCellValue('B'.$i, $val['unidad']);
			$i++;
			$rowNro++;
		}

		$row = array("A","B","C");

        foreach($row as $val){  
            $objSheet->getColumnDimension($val)->setAutoSize(true);
        }
        
        $objSheet->getStyle('A3:A3')->getFont()->getColor()->applyFromArray( array('rgb' => 'FFFFFF') );
        $objSheet->getStyle('A4:A4')->getFont()->getColor()->applyFromArray( array('rgb' => 'FFFFFF') );
        $objSheet->getStyle('A5:A5')->getFont()->getColor()->applyFromArray( array('rgb' => 'FFFFFF') );



        $objSheet->getStyle('A3:A3')->applyFromArray( array('fill' => array('type' => \PHPExcel_Style_Fill::FILL_SOLID, 'color' => array('rgb' => 'C15F9D') )));
        $objSheet->getStyle('A4:A4')->applyFromArray( array('fill' => array('type' => \PHPExcel_Style_Fill::FILL_SOLID, 'color' => array('rgb' => 'C15F9D') )));
        $objSheet->getStyle('A5:A5')->applyFromArray( array('fill' => array('type' => \PHPExcel_Style_Fill::FILL_SOLID, 'color' => array('rgb' => 'C15F9D') )));



        $objSheet->getStyle('A8:C8')->getFont()->getColor()->applyFromArray( array('rgb' => 'FFFFFF') );
        $objSheet->getStyle('A8:C8')->applyFromArray(
	        array(
	            'fill' => array(
	                'type' => \PHPExcel_Style_Fill::FILL_SOLID,
	                'color' => array('rgb' => 'C15F9D')
	            )
	        )
		);


		$objPHPExcel->getActiveSheet()->getStyle('A8:C'.$totalUnidades)->getBorders()->applyFromArray(
         	array(
				'allborders' => array(
					'style' => \PHPExcel_Style_Border::BORDER_DASHED,
					'color' => array(
						'rgb' => '000000'
					)
				)
			)
		);

        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        $objWriter->save($file);
		        
        if(file_exists($file)){
			/////////save auditoria////////
			/*$params['tabla']='';
			$params['numRegistro']='';
			$params['accion']='Descarga Formato';
			$this->saveAuditoria($params);*/
			///////////////////////////////
        	$response = array(
        		"message"=>"success",
        		"cuerpo"=>"formato generado correctamente...",
        		"ruta"=>"temp/ingreso/".$name,
        		"nombreFile"=>$name
        	);
        }else{
        	$response = array("message"=>"nofile");
        }

        return $response;
	}


}

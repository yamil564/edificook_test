<?php

/**
* edificioOk (https://www.edificiook.com)
* creado por: Jhon Gómez, 11/03/2016.
* ultima modificacion por: Jhon Gómez
* Fecha Modificacion: 11/04/2016.
* Descripcion: Modelo Edificio
*
* @autor     Fidel J. Thompson
* @link      https://www.edificiook.com
* @copyright Copyright (c) 2011-2016 KND S.A.C (http://www.knd.pe)
* @license   http://www.edificiook.com/license/comercial Software Comercial
* 
*/

namespace Otros\Model;

use Zend\Db\Sql\Sql;
use Zend\Db\Sql\Select;
use Zend\Db\TableGateway\AbstractTableGateway;
use Zend\Db\Adapter\Adapter;
use Zend\Db\ResultSet\ResultSet;
use Zend\Db\Sql\Expression;

use Zend\Mail\Message;
use Zend\Mail\Transport\Smtp as SmtpTransport;
use Zend\Mail\Transport\SmtpOptions;

// use Zend\Mail\Message;
use Zend\Mime\Message as MimeMessage;
use Zend\Mime\Part as MimePart;
use Zend\Mime\Mime;
// use Zend\Mail\Transport\Sendmail;

/*
use Zend\Mail;
Use Zend\Mime;
use Zend\Mime\Message as MimeMessage;
use Zend\Mime\Part as MimePart;
*/
class NoticiaTable extends AbstractTableGateway{

    public function __construct(Adapter $adapter)
    {
        $this->adapter=$adapter;
    }

    public function listarNoticia($idedificio, $params)
    {
		//$text =$params['text']; 
		$adapter=$this->getAdapter();
        $sql=new Sql($adapter);
		/* Parametros del jqGrid. */
		$page = $params['page']; 
		$limit = $params['rows']; 
		$sidx = $params['sidx']; 
		$sord = $params['sord']; 

		if(!$sidx) $sidx = 1;

		$filter = $this->buscarNoticia($params['_search'], $params['filters']);

		$queryNoticia="SELECT COUNT(*) AS count FROM noticia WHERE edi_in_cod='".$idedificio."'";
		$row = $adapter->query($queryNoticia,$adapter::QUERY_MODE_EXECUTE)->current();
		$count = $row['count'];

		if( $count > 0 && $limit > 0) { 
		    $total_pages = ceil($count/$limit); 
		}else{ 
		    $total_pages = 0;
		}
		if ($page > $total_pages) $page=$total_pages;
		$start = $limit*$page - $limit;
		if($start <0) $start = 0;

		$queryNoticia="SELECT * FROM noticia WHERE edi_in_cod='".$idedificio."' $filter ORDER BY $sidx $sord LIMIT $start , $limit";
		$result = $adapter->query($queryNoticia,$adapter::QUERY_MODE_EXECUTE)->toArray();

		$response=array();
        $response['page'] = $page;
        $response['total'] = $total_pages;
        $response['records'] = $count;
        $i=0;

		foreach($result as $row){
		    $fecha = explode("-",$row['not_da_fec']);
		    $fec = $fecha[2]."/".$fecha[1]."/".$fecha[0];    
		    $response['rows'][$i]['id'] = $row['not_in_cod'];
		    $response['rows'][$i]['cell'] = array($row['not_in_cod'],$fec,$row['not_vc_tit']);
		    $i++;
		}

		return $response;

    }

    private function buscarNoticia($search,$filtro)
    {
    	/* En caso estemos realizando una busqueda o un filtro. */
		$filter = "";
		$search_ind = $search;
		if($search_ind == 'true'){
		    $filter_cad = json_decode($filtro);
		    $filter_opc = $filter_cad->{'groupOp'};
		    $filter_rul = $filter_cad->{'rules'};
		    $cont = 0;
		    foreach($filter_rul as $key => $value){
		        $fie = $filter_rul[$key]->{'field'};
		        $opt = $this->search($filter_rul[$key]->{'op'});
		        $dat = $filter_rul[$key]->{'data'};        
		        if($cont==0){
		             if($opt=="REGEXP2"){
		                $opt = "LIKE";
		                $filter .= " AND ($fie $opt '%$dat%'";
		            }else{
		                if($opt=="REGEXP3"){
		                    $opt = "LIKE";
		                    $filter .= " AND ($fie $opt '%$dat%'";
		                }else{
		                    if($opt=="NOT REGEXP2"){
		                        $opt = "NOT LIKE";
		                        $filter .= " AND ($fie $opt '%$dat%'";
		                    }else{
		                        if($opt=="NOT REGEXP3"){
		                            $opt = "NOT LIKE";
		                            $filter .= " AND ($fie $opt '%$dat%'";
		                        }else{
		                            if($opt=='LIKE' || $opt=='NOT LIKE'){
		                                $filter .= " AND ($fie $opt '%$dat%'";
		                            }else{
		                                $filter .= " AND ($fie $opt '%$dat%'";
		                            }
		                        }
		                    }
		                }
		            }
		            $cont++;
		        }else{
		          if($opt=="REGEXP2"){
		                $opt = "LIKE";
		                $filter .= " $filter_opc $fie $opt '%$dat%'";
		            }else{
		                if($opt=="REGEXP3"){
		                    $opt = "LIKE";
		                    $filter .= " $filter_opc $fie $opt '%$dat%'";
		                }else{
		                    if($opt=="NOT REGEXP2"){
		                        $opt = "NOT LIKE";
		                        $filter .= " $filter_opc $fie $opt '%$dat%'";
		                    }else{
		                        if($opt=="NOT REGEXP3"){
		                            $opt = "NOT LIKE";
		                            $filter .= " $filter_opc $fie $opt '%$dat%'";
		                        }else{
		                            if($opt=='LIKE' || $opt=='NOT LIKE'){
		                                $filter .= " $filter_opc $fie $opt '%$dat%'";
		                            }else{
		                                $filter .= " $filter_opc $fie $opt '%$dat%'";
		                            }
		                        }
		                    }
		                }
		            }
		        }
		    }
		    $filter .= ")";
		}

		return $filter;
    }

    private function search($oper){
		switch($oper){
			case "eq" : $oper = "="; break;
			case "ne" : $oper = "!="; break;
			case "lt" : $oper = "<"; break;
			case "le" : $oper = "<="; break;
			case "gt" : $oper = ">"; break;
			case "ge" : $oper = ">="; break;
			case "bw" : $oper = "REGEXP2"; break;
			case "bn" : $oper = "NOT REGEXP2"; break;
			case "in" : $oper = "IN"; break;
			case "ni" : $oper = "NOT IN"; break;
			case "ew" : $oper = "REGEXP3"; break;
			case "en" : $oper = "NOT REGEXP3"; break;
			case "cn" : $oper = "LIKE"; break;
			case "nc" : $oper = "NOT LIKE"; break;
		}
		return $oper;
	}

    public function listarNoticias($idedificio, $params)
    {
    	$adapter=$this->getAdapter();
        $idnoticia = $params['idnoticia'];
        $queryNoticia="SELECT * FROM noticia WHERE edi_in_cod=$idedificio AND not_in_cod=$idnoticia";
		$data = $adapter->query($queryNoticia,$adapter::QUERY_MODE_EXECUTE)->current();
		if($data['not_vc_img'] == '') $data['not_vc_img'] = "NULL";
		else $data['not_vc_img'] = "file/noticia/".$data['not_vc_img'];
		if(count($data) != 1) return array("message"=>"success", "result"=>$data);
		else return array("message"=>"error");			
    }

    public function updatex($params)
    {

    	$adapter=$this->getAdapter();
        $sql=new Sql($adapter);

        $nombre=$params['nombre'];
        $tamano=$params['tamano'];
        $rutaTemporal=$params['rutaTemporal'];
        $idedificio=$params['idEdificio'];
        $titulo = $params['titulo'];
	    $fecha='';

        if(isset($params['fecha'])){
        	$fecha=str_replace("/", "-", $params['fecha']);
        	$fecha=date('Y-m-d',strtotime($fecha));
        }else{
        	$fecha =date('Y-m-d');
        }

	    $contenido = $params['contenido'];
	    $idnoticia = $params['idnoticia'];
	    $successImage = 0;

        if($nombre !=''){
    		$extension = substr($nombre, strrpos($nombre, '.')+1);
	   		$directorio = getcwd()."/public/file/noticia/".$idnoticia.".".$extension;
	    	copy($rutaTemporal, $directorio);
	    	if(file_exists($directorio)){
	    		$dataImagen = array('not_vc_img'=>$idnoticia.".".$extension);
	    		$updateNoticiaImagen=$sql->update()
					->table('noticia')
					->set($dataImagen)
					->where(array('not_in_cod'=>$idnoticia));
				$sql->prepareStatementForSqlObject($updateNoticiaImagen)->execute();
	    		$successImage = 1;
	    	}
    	}else{
    		$adapter=$this->getAdapter();
	   		$queryNoticia = "SELECT RIGHT(not_vc_img,INSTR(REVERSE(not_vc_img),'.')) AS extension FROM noticia WHERE not_in_cod = '$idnoticia'";
			$rowImagenExtension = $adapter->query($queryNoticia,$adapter::QUERY_MODE_EXECUTE)->current();
			$path = 'public/file/noticia/'.$idnoticia.$rowImagenExtension['extension'];
			if(file_exists($path)){
				//clear imagen
				$dataImagen = array('not_vc_img'=>'');
	    		$updateNoticiaImagen=$sql->update()
					->table('noticia')
					->set($dataImagen)
					->where(array('not_in_cod'=>$idnoticia));
				$sql->prepareStatementForSqlObject($updateNoticiaImagen)->execute();
				//delete imagen
				@unlink($path);
				$successImage = 1;
			}
    	}

		$data=array(	
			'edi_in_cod'=>$idedificio,
			'not_da_fec'=>$fecha,
			'not_vc_tit'=>$titulo,
			'not_te_con'=>$contenido
		);
		$updateNoticia=$sql->update()
			->table('noticia')
			->set($data)
			->where(array('not_in_cod'=>$idnoticia, 'edi_in_cod'=>$idedificio));
		$result=$sql->prepareStatementForSqlObject($updateNoticia)->execute()->count();

		if($result == 1 || $successImage == 1){

			/////////save auditoria////////
       		$params['idUsuario']=$params['idUsuario'];
       		$params['idEdificio']=$idedificio;
       		$params['accion']='Editar';
       		$params['numRegistro']=$idnoticia;
       		$this->saveAuditoria($params);
       		///////////////////////////////

			return array(
				"message"=>"success",
				"cuerpo"=>"los datos se modificaron correctamente"
			);
		}else{
			return array(
				"message"=>"error",
				"cuerpo"=>"no se actualizo ninguna informacion"
			);  
		}

        
   	}

   	public function add($params)
   	{
   		$titulo = $params['titulo'];
        $archivos = $params['filesnoticia'];
        
        $fecha='';
        if(isset($params['fecha'])){
        	$fecha=str_replace("/", "-", $params['fecha']);
        	$fecha=date('Y-m-d',strtotime($fecha));
        }else{
        	$fecha =date('Y-m-d');
        }

    	$idedificio=$params['idEdificio'];
        $contenido = $params['contenido'];

   		$adapter=$this->getAdapter();
        $sql=new Sql($adapter);

   		$insert = $sql->insert('noticia');
        $data = array(
            'edi_in_cod'=>$idedificio,
			'not_da_fec'=>$fecha,
			'not_vc_tit'=>$titulo,
			'not_te_con'=>$contenido
        );

        $insert->values($data);
        $lastId = $sql->prepareStatementForSqlObject($insert)->execute()->getGeneratedValue();
       	
       	$response = '';
       	if($lastId > 0) {
       		$cantidad = count($archivos['name']);

			$directorio = getcwd()."/public/file/noticia/".$lastId;
       		if(!file_exists($directorio)) {
       			mkdir($directorio, 0777);
       		}
       		
       		for ($i = 0; $i < $cantidad; $i++) {
       			$extension = substr($archivos['name'][$i], strrpos($archivos['name'][$i], '.')+1);
	       		// $directorio = getcwd()."/public/file/noticia/".$lastId.".".$extension;
	       		$dir = $directorio."/".$lastId.".".$extension;
	        	if($archivos['tmp_name'][$i] != "" || $archivos['tmp_name'][$i] != NULL) { copy($archivos['tmp_name'][$i], $dir); }
	        	
	        	if($extension == "jpg" || $extension == "jpeg" || $extension == "png") {
	        		
	        		// $data=array('not_vc_img'=>$archivos['name'][$i]);
	        		$data=array('not_vc_img'=>$lastId.".".$extension);
	        		$updateNoticia=$sql->update()
						->table('noticia')
						->set($data)
						->where(array('not_in_cod'=>$lastId, 'edi_in_cod'=>$idedificio));
					$sql->prepareStatementForSqlObject($updateNoticia)->execute()->count();
	        	}
	        	
       		}

       		$logparams['idUsuario']=$params['idUsuario'];
       		$logparams['idEdificio']=$idedificio;
       		$logparams['accion']='Guardar';
       		$logparams['numRegistro']=$lastId;
       		$this->saveAuditoria($logparams);
       		///////////////////////////////

       		$response = array(
       			"message"=>"success",
       			"cuerpo"=>"los datos se guardaron correctamente"
       		);

       		$this->mailNoticia($lastId,$idedificio);
       	}else{
       		$response =  array(
       			"message"=>"error",
       			"cuerpo"=>"no se inserto el registro"
       		);
       	}

       	

       	return $response;

   	}

   	private function mailNoticia($idNoticia,$idEdificio)
   	{
   		$adapter=$this->getAdapter();
   		//$db = new MySQL(); $data = 0;
	    $codNot = $idNoticia;
	    $entidad = $idEdificio;
	    $nomPro = '';

	    $queryUnidad="SELECT uni_in_cod, uni_in_pos FROM unidad WHERE edi_in_cod = '$entidad' AND uni_in_est = '1' and (uni_in_pos!=0 OR uni_in_pos!='') group by uni_in_pos";
		$consMail = $adapter->query($queryUnidad,$adapter::QUERY_MODE_EXECUTE)->toArray();
		
		/**
		*****
			CONSIDERA IMPLICITAMENTE AL ADMINISTRADOR DEL EDIFICIO EN LA LISTA DE USUARIOS 
			PARA EL ENVIO DE NOTICIAS CORREO.
		*****
		*/
		/*
		$queryAdministradorEdificio = "SELECT edi_in_adm as id FROM edificio where edi_in_cod={$entidad}";
		$rsAdministradorEdificio = $adapter->query($queryAdministradorEdificio,$adapter::QUERY_MODE_EXECUTE)->current();
		if(!empty($consMail) && !empty($rsAdministradorEdificio['id']) ){
			$consMail[]=["uni_in_cod"=>0,"uni_in_pos"=>$rsAdministradorEdificio['id']];
		}
		*/

	    $queryEdificio = "SELECT emp_vc_des,emp_vc_dom,emp_vc_email FROM edificio en,empresa e WHERE en.emp_in_cod =e.emp_in_cod AND edi_in_cod ='$entidad'";
	    $rowEmp = $adapter->query($queryEdificio,$adapter::QUERY_MODE_EXECUTE)->current();


	    $queryNoticia = "SELECT RIGHT(not_vc_img,INSTR(REVERSE(not_vc_img),'.')) AS ext, not_da_fec, not_vc_tit, not_te_con,not_vc_img FROM noticia WHERE not_in_cod = '$codNot'";
	    
	    $rowExte = $adapter->query($queryNoticia,$adapter::QUERY_MODE_EXECUTE)->current();

	    // var_dump($consMail);
	    foreach ($consMail as $rowMail){
	        $coduni = $rowMail['uni_in_cod'];
	        $codPoseedor = $rowMail['uni_in_pos'];
	        
	        $queryUsuario = "SELECT usu_vc_ema, usu_vc_nom, usu_vc_ape, usu_ch_tip FROM usuario WHERE usu_in_cod = '$codPoseedor' and usu_vc_ema!=''";
	        $rowPoseedor = $adapter->query($queryUsuario,$adapter::QUERY_MODE_EXECUTE)->current();

	        if(!empty($rowPoseedor)){

	        	if ($rowPoseedor['usu_ch_tip'] == 'PN'){
	                $NomPos = $rowPoseedor['usu_vc_nom'] . " " . $rowPoseedor['usu_vc_ape'];
	            } else {
	                $NomPos = $rowPoseedor['usu_vc_ape'];
	            }

	        	$destinatario = $rowPoseedor['usu_vc_ema'];
	        	$nombrePoseedor = $NomPos;

	        	$titulo = "Noticia - ".$rowExte['not_vc_tit'];

		        $msg = '
					<table style="width: 100%;border: 1px solid #ddd;background: #f8f8f8;">
						<tr>
							<td><p style="margin: 0 0 3px 0;"><strong>Estimado(a)</strong> '.$nombrePoseedor.'</p></td>
						</tr>
						<tr>
							<td><p style="margin: 0 0 3px 0;"><strong>Fecha:</strong> '.date('d-m-Y',strtotime($rowExte['not_da_fec'])).'</p></td>
						</tr>
						<tr>
							<td><p style="margin: 0 0 3px 0;"><strong>Título:</strong> '.$rowExte['not_vc_tit'].'</p></td>
						</tr>
						<tr>
							<td><p style="margin: 0 0 3px 0;">'.nl2br($rowExte['not_te_con']).'</p></td>
						</tr>
						<tr>
							<td><p style="margin: 0 0 3px 0;"><strong>Atentamente</strong></p></td>
						</tr>
						<tr>
							<td><p style="margin: 0 0 3px 0;"><strong>'.$rowEmp['emp_vc_des'].'</strong></p></td>
						</tr>
						<tr>
							<td><br> <p style="font-size: 10px; margin:0px;"><b>Este mensaje ha sido enviado con fines informativos, por favor no responder.<b></p></td>
						</tr>
						<tr>
							<td><a href="http://www.'.$rowEmp['emp_vc_dom'].'/">http://www.'.$rowEmp['emp_vc_dom'].'/</a></td>
						</tr>
					</table>
				';

		        $responder = $rowEmp['emp_vc_email'];
		        $remite = $rowEmp['emp_vc_email'];
		        //$separador = $objMail->Separador();
		        $extencion = $rowExte['ext'];
		        
		        //Condicion que indica si la noticia tiene una imagen adjunto.
		        if($rowExte['not_vc_img']!=''){
		        	$adj1 = '1';
		        } else {
		            $adj1 = "";
		        }

		        $fecha = date("Y-m-d H:i:s");
		        // var_dump($destinatario);
		        if ($destinatario != '' && filter_var($destinatario, FILTER_VALIDATE_EMAIL)) {
				    $this->enviarcorreodeprueba($destinatario, $titulo, $msg, $remite, $fecha, $coduni, $nombrePoseedor, $codNot) ;
				    // $this->sendMailNoticia($destinatario, $titulo, $msg, $remite, $fecha, $coduni, $nombrePoseedor, $adj1, $extencion, $codNot, $rowExte['not_vc_img']);
		        }
	        } 
	    }
   	}


public function enviarcorreodeprueba($destinatario, $titulo, $msg, $emisor, $fecha, $coduni, $nombrePoseedor, $codNot)
{
	// $message = new Message();

    // $message->addTo($to);
    // $message->addFrom($from);
    // $message->setSubject($subject);
	$htmlContent = '<b>Hola Mundo</b>';
    // HTML part
    $htmlPart           = new MimePart($msg);
    $htmlPart->encoding = Mime::ENCODING_QUOTEDPRINTABLE;
    $htmlPart->type     = "text/html; charset=UTF-8";

	$textContent = 'Noticia';
    // Plain text part
    $textPart           = new MimePart($textContent);
    $textPart->encoding = Mime::ENCODING_QUOTEDPRINTABLE;
    $textPart->type     = "text/plain; charset=UTF-8";

    $body = new MimeMessage();
   	
	// $destinatario='cbravolozada@gmail.com';
	// $emisor='info@knd.pe';
	//Enviar email
	$message = new Message();
	$message->addTo($destinatario);
	$message->addFrom($emisor);
	// $message->setEncoding("UTF-8");
	$message->setSubject($titulo);
	
	$somefilePath = 'public/file/noticia/'.$codNot.'/'.$codNot.'.pdf';

	// $message->setBody("Hola mundo este es un correo de prueba");;

	// With attachments, we need a multipart/related email. First part
	// is itself a multipart/alternative message        
	$content = new MimeMessage();
	$content->addPart($textPart);
	$content->addPart($htmlPart);

	$contentPart = new MimePart($content->generateMessage());
	$contentPart->type = "multipart/alternative;\n boundary=\"" . $content->getMime()->boundary() . '"';

	$body->addPart($contentPart);
	$messageType = 'multipart/related';

	// $somefilePath = 'public/file/noticia/'.$codNot.'/'.$codNot.'.pdf';
	$fileContent = fopen($somefilePath, 'r');

	$attachment = new MimePart($fileContent);
	$attachment->filename    = $codNot.'.pdf';
	$attachment->type        = Mime::TYPE_OCTETSTREAM;
	$attachment->encoding    = Mime::ENCODING_BASE64;
	$attachment->disposition = Mime::DISPOSITION_ATTACHMENT;

	$body->addPart($attachment);


    // attach the body to the message and set the content-type
    $message->setBody($body);
    $message->getHeaders()->get('content-type')->setType($messageType);
    $message->setEncoding('UTF-8');
	
	// Utilizamos el smtp de gmail con nuestras credenciales
	$transport = new SmtpTransport();
	$options   = new SmtpOptions(array(
		'name'  => 'smtp.gmail.com',
		'host'  => 'smtp.gmail.com',
		'port'  => 465,
		'connection_class'  => 'login',
		'connection_config' => array(
			'username' => 'christian.bravo@knd.pe',
			'password' => 'CB46291407@1990',
			'ssl' => 'ssl',
		),
	));
	
	$transport->setOptions($options); //Establecemos la configuración
	$transport->send($message); //Enviamos el correo

	$this->saveLogCorreo($fecha,$coduni,$nombrePoseedor,$destinatario,$titulo);
}

/*
   	private function sendMailNoticia($destinatario, $titulo, $msg, $remite, $fecha, $coduni, $nombrePoseedor, $adj1, $extencion, $codNot, $rutaimg)
   	{
   		$tipoFileMail=[
   			".pdf"=>'application/pdf',
   			".jpg"=>'image/jpg',
   			".png"=>'image/png',
   		];

   		$somefilePath = 'public/file/noticia/'.$codNot.'/'.$codNot.'.pdf';
   		if(file_exists($somefilePath)){
   			// @unlink($path);
   			$attachments = true;
   		}
	
	// $attachments = true;
	$message = new Message();

    $message->addTo($destinatario);
    $message->addFrom($remite);
    $message->setSubject($titulo);


    // HTML part
    $htmlPart           = new MimePart($msg);
    // $htmlPart->encoding = Mime::ENCODING_QUOTEDPRINTABLE;
    $htmlPart->type     = "text/html; charset=UTF-8";
    // $htmlPart->type = Mime::TYPE_HTML;

    // Plain text part
    $textPart           = new MimePart();
    // $textPart->type = Mime::TYPE_TEXT;
    // $textPart->encoding = Mime::ENCODING_QUOTEDPRINTABLE;
    $textPart->type     = "text/plain; charset=UTF-8";

    $body = new MimeMessage();
    if ($attachments) {
        // With attachments, we need a multipart/related email. First part
        // is itself a multipart/alternative message        
        $content = new MimeMessage();
        $content->addPart($textPart);
        $content->addPart($htmlPart);

        $contentPart = new MimePart($content->generateMessage());
        $contentPart->type = "multipart/alternative;\n boundary=\"" .
            $content->getMime()->boundary() . '"';

        $body->addPart($contentPart);
        $messageType = 'multipart/related';

			// $somefilePath = 'public/file/noticia/'.$codNot.'/'.$codNot.'.pdf';
			$fileContent = fopen($somefilePath, 'r');

			$attachment = new MimePart($fileContent);
            $attachment->filename    = $codNot.'.pdf';
            $attachment->type        = Mime::TYPE_OCTETSTREAM;
            $attachment->encoding    = Mime::ENCODING_BASE64;
            $attachment->disposition = Mime::DISPOSITION_ATTACHMENT;

            $body->addPart($attachment);

    } else {
        // No attachments, just add the two textual parts to the body
        $body->setParts(array($textPart, $htmlPart));
        $messageType = 'multipart/alternative';
    }

    // attach the body to the message and set the content-type
    $message->setBody($body);
    $message->getHeaders()->get('content-type')->setType($messageType);
    $message->setEncoding('UTF-8');

    $transport = new Sendmail();
    $transport->send($message);


    $this->saveLogCorreo($fecha,$coduni,$nombrePoseedor,$destinatario,$titulo);
	   }*/

   	private function saveLogCorreo($fecha,$coduni,$nombrePoseedor,$destinatario,$titulo)
	{
		$adapter=$this->getAdapter();
		$sql=new Sql($adapter);
        $codigo = "";
        $queryUnidad = "SELECT * FROM unidad WHERE uni_in_cod='$coduni' ";
        $rowUni = $adapter->query($queryUnidad,$adapter::QUERY_MODE_EXECUTE)->current();

        if(empty($rowUni) ){
        	return;
        }

        $desUni = $rowUni['uni_vc_tip']." ".$rowUni['uni_vc_nom'];
        $codeni = $rowUni['edi_in_cod'];

        //insert log correo
        $insert = $sql->insert('log_correo');
        $data = array(
            'log_dt_freg'=>$fecha,
            'log_vc_unidad'=>$desUni,
            'log_vc_nombre'=>$nombrePoseedor,
            'edi_in_cod'=>$codeni,
            'log_vc_email'=>$destinatario,
            'log_vc_asunto'=>$titulo
        );
        $insert->values($data);
        $sql->prepareStatementForSqlObject($insert)->execute();
	}

   	public function del($params)
   	{
   		$adapter=$this->getAdapter();
   		$idnoticia = $params['idnoticia'];
   		$queryNoticia = "SELECT RIGHT(not_vc_img,INSTR(REVERSE(not_vc_img),'.')) AS extension FROM noticia WHERE not_in_cod = '$idnoticia'";
		$rowImagenExtension = $adapter->query($queryNoticia,$adapter::QUERY_MODE_EXECUTE)->current();

        //delete
        $sql=new Sql($adapter);
   		$delete=$sql->delete()
            ->from('noticia')
            ->where(array('not_in_cod'=>$idnoticia));
        $result = $sql->prepareStatementForSqlObject($delete)->execute()->count();

   		if($result == 1){
   			$path = 'public/file/noticia/'.$idnoticia.$rowImagenExtension['extension'];
   			if(file_exists($path)){
   				@unlink($path);
   			}
   			/////////save auditoria////////
       		$params['idUsuario']=$params['idUsuario'];
       		$params['idEdificio']=$params['idEdificio'];
       		$params['accion']='Eliminar';
       		$params['numRegistro']=$params['idnoticia'];
       		$this->saveAuditoria($params);
       		///////////////////////////////
   			$response = array(
   				"message"=>"success",
   				"cuerpo"=>"la noticia se elimino correctamente"
   			);
   		}else{
   			$response = array(
   				"message"=>"error",
   				"cuerpo"=>"ocurrio un problema en el servidor"
   			);
   		} 

		return $response;
   	}

   	private function saveAuditoria($params)
    {	
        $adapter=$this->getAdapter();
        $sql=new Sql($adapter);
        $insert = $sql->insert('auditoria');
        $data = array(
            'usu_in_cod'=> $params['idUsuario'], //idusuario
            'edi_in_cod'=> $params['idEdificio'], //idedificio
            'aud_opcion'=> 'Otros > Noticias', //ruta
            'aud_accion'=> $params['accion'], //accion
            'aud_fecha'=> date('Y-m-d H:i:s'), //fecha y hora
            'aud_ip'=> $_SERVER['REMOTE_ADDR'], //ip publica
            'aud_tabla'=> 'Noticia', //tabla
            'aud_in_numra'=> $params['numRegistro'], //registro afectado
            'aud_info_adi'=> $_SERVER['HTTP_USER_AGENT'] //agente
        );
        $insert->values($data);
        $sql->prepareStatementForSqlObject($insert)->execute()->count();
    }


}
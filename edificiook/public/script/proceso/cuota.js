var Action={
	cuotaController:"proceso/cuota/iniciar",
	ruta:$(".breadcrumb").text().trim(),
	iniciar:function(){
		var params=$("#frmCuotaMensual").serializeArray();
		var valChkPersonalizar=$("#chkPersonalizar").is(':checked');
		params.push({name:'chkPersonalizar',value:valChkPersonalizar});
		params.push({name:'ruta',value:this.ruta});

		if($("#btn-iniciar #label").html()=="Generando..."){
			alerta('Generar cuota','Espere a que termine el proceso de generación de cuota.',"error");
			return;
		}
		$("#btn-iniciar").toggleClass('active');
		$("#btn-iniciar #label").html('Generando...');

		$("#modalConfirmacionGenerarCuota").modal('hide');

		$.post(this.cuotaController,params,function(data){
			$("#btn-iniciar").toggleClass('active');
			$("#btn-iniciar #label").html('Iniciar Proceso');
			if(data.tipo){
                alerta('Generar cuota',data.mensaje,data.tipo,100000);
            }else{
              alerta('Generar cuota','Ocurrió un error desconocido en el servidor mientras se generaba la cuota.','error',100000);  
            }
		},'json').fail(function(){
			alerta("Generar cuota","Ocurrió errores en el servidor mientras se generaba la cuota. Por favor contactar al administrador del sistema.","error",100000);
			$("#btn-iniciar").toggleClass('active');
			$("#btn-iniciar #label").html('Iniciar Proceso');
		});
	},
}



var Validacion={
	parametrosfechaController:"proceso/cuota/parametrosfecha",
	personalizarParams:function(){
		Validacion.escribirParametrosfecha();
		if($("#chkPersonalizar").is(':checked')){
			$("#textDiaEmi").prop('disabled',false);
			$("#textDiaVence").prop('disabled',false);
		}else{
			$("#textDiaEmi").prop('disabled',true);
			$("#textDiaVence").prop('disabled',true);
		}
	},
	escribirParametrosfecha:function(){
		var params=$("#frmCuotaMensual").serializeArray();
		var valChkPersonalizar=$("#chkPersonalizar").is(':checked');
		params.push({name:'chkPersonalizar',value:valChkPersonalizar});
		$.post(this.parametrosfechaController,params,function(data){
			$("#textDiaEmi").val(data.diaEmi);
			$("#textDiaVence").val(data.diaVence);
			$("span#labelFechaEmi").html(data.fechaEmi);
			$("span#labelFechaVen").html(data.fechaVence);
		},'json');
	}
}

$("#btn-iniciar").click(function(){
	if($('#selMes').val()=='0'){
		alerta("Generar Cuota","No hay ningún mes seleccionado","error");
		return;
	}
	if($("#selYear").val()==''){
		alerta("Generar Cuota","No hay ningún año seleccionado","error");
		return;
	}

	$("#modalConfirmacionGenerarCuota").modal('show');
	$(".modal-dialog").css({'width':'30%'});
});

$("#selMes").change(function(){
	var textMesSelected=$("#selMes option:selected").text();
	var textYearSelected=$("#selYear option:selected").text();
	$("#msg_mesconfirmacion").html(textMesSelected + ' '+textYearSelected);
});

Validacion.escribirParametrosfecha();

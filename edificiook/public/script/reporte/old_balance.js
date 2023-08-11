var balance = {

	ruta:$(".breadcrumb").text().trim(),

	reporte:function()
	{
		var anio=$("#cboAnio").val();
		var mes=$("#cboPeriodo").val();
		/*Validar fecha de emision del reporte*/
		var fec_act=new Date();
		var ani_act=fec_act.getYear();
		var mes_act=fec_act.getMonth()+1;
		var dia_act=fec_act.getDate();

		if (ani_act < 1000) ani_act+=1900;
		if (mes_act<10) mes_act="0"+mes_act;
		if (dia_act<10)dia_act="0"+dia_act;
		 
		if(anio==ani_act && mes== mes_act){
			this.data(mes,anio,dia_act);
		}else{
			if(anio==ani_act && mes > mes_act){
			   alert('No se generó el balance, verifique si hay movimientos en el periodo y mes seleccionado');
			}else{
				if((anio<=ani_act )){
					this.data(mes,anio,'');
				}
			}
		}

	},

	data:function(mes,anio,dia_act)
	{
		$(".fa-circle-o-notch").removeClass('hide');
		$("#balance").find('span').text('Generando reporte...');
		$("#cboAnio, #cboPeriodo").prop('disabled', true).trigger("chosen:updated");
		$("#balance").parent().find(".center").remove();
		$("#balance").attr("disabled","");

		$.post("reporte/balance/reporte", {
			mes:mes,
			anio:anio,
			dia:dia_act,
			ruta:this.ruta
		}, function(data){
			$("#cboAnio, #cboPeriodo").prop('disabled', false).trigger("chosen:updated");
			$(".fa-circle-o-notch").addClass('hide');
			$("#balance").find('span').text('Generar Reporte');
			$("#balance").attr("disabled","");
			$("#balance").parent().append('<div class="center"><p><a href="temp/balance/'+data.file+'" download="'+data.file+'">Descargar balance</a> | <a href="temp/balance/'+data.file+'" target="_blank">Vista Previa</a> </p></div>')
		},'json').fail(function(){
			$(".fa-circle-o-notch").addClass('hide');
			$("#balance").find('span').text(data.cuerpo);
		});
	}

}

$("#cboAnio, #cboPeriodo").change(function(){
	$("#balance").removeAttr("disabled");
	$(".fa-circle-o-notch").addClass('hide');
	$("#balance").find('span').text('Descargar reporte');
});	
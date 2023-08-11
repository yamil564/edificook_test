var balance = {

	ruta:$(".breadcrumb").text().trim(),

	reporte:function(mes,anio)
	{
		$(".fa-circle-o-notch").removeClass('hide');
		$("#balance").find('span').text('Generando reporte...');
		$("#cboAnio, #cboPeriodo").prop('disabled', true).trigger("chosen:updated");
		$("#balance").parent().find(".center").remove();
		$("#balance").attr("disabled","");
		
		var anio=$("#cboAnio").val();
		var mes=$("#cboPeriodo").val();
		var tipo=$("#cboTipo").val();

		$.post("ue/balance/reporte", {
			mes:mes,
			anio:anio,
			tipo:tipo,
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

$("#cboAnio, #cboPeriodo, #cboTipo").change(function(){
	$("#balance").removeAttr("disabled");
	$(".fa-circle-o-notch").addClass('hide');
	$("#balance").find('span').text('Descargar reporte');
});	
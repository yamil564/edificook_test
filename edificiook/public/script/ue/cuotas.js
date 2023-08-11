var DateControls = {

    yearSelected:$("#currentYear").html(),
    idUnidad:$("#unidad").val(),

    nextYear:function(){
        this.yearSelected++;
        $("#currentYear").html(this.yearSelected);
        cuotas.loadData(this.yearSelected,this.idUnidad);
    },

    backYear:function(){
        this.yearSelected--;
        $("#currentYear").html(this.yearSelected);
       	cuotas.loadData(this.yearSelected,this.idUnidad);
    }

}

var cuotas = {

	loadData:function(year,idUnidad){

        $("#tblPagos tbody").html('');
        $("#tblPagos tfoot th").text('null');

        $.post('ue/cuotas/grid', {
            year:year,
            idUnidad:idUnidad
        },function(data){
            contentTable='';
            if(data.rows.length>0){
                
            
                $.each(data.rows,function(index,value){
                    contentTable+="<tr class='rest' data-id='"+value['idIngreso']+"' mes="+value['numMes']+">"
                        +"<td><span class='right fa fa-chevron-right orange'></span></td>"
                        +"<td>"+value['mes']+"</td>"
                        +"<td class='td-right'>"+value['totalEmision']+"</td>"
                        +"<td class='td-right'>"+value['totalPagado']+"</td>"
                        +"<td class='td-right'>"+value['debe']+"</td>"
                        +"<td class='center'>"+value['btnDescargar']+"</td>"
                    +"</tr>";
                });

                totalesTable="<tr>"
                    +"<th></th>"
                    +"<th>"+data.total['label']+"</th>"
                    +"<th class='td-right'>"+data.total['sumEmision']+"</th>"
                    +"<th class='td-right'>"+data.total['sumPagado']+"</th>"
                    +"<th class='td-right'><span class='label label-danger arrowed'>S/. "+data.total['sumDebe']+"</span></th>"
                +"</tr>";


                $("#tblPagos tbody").html(contentTable);
                $("#tblPagos tfoot").html(totalesTable);
                $("#tblPagos tr").css("cursor","pointer");



            }else{
                $("#tblPagos tbody").html('');
                $("#tblPagos tfoot th").text('null');
            }
        
        },'json').fail(function(){
            alert('error');
        });
    },

    info:function(idIngreso)
    {
        $.post('ue/cuotas/info', {
            idIngreso:idIngreso
        },function(data){

            $('#tab-info a:first').tab('show');
            $("#labelModalInfo").text('Cuota de '+data.mes+' del '+data.year);
            $("#txtUnidad").text(data.unidadNombre);
            $("#txtPropietario").text(data.propietario);
            $("#txtResidente").text(data.residente);
            $("#txtFechaEmision").text(data.fechaEmi);
            $("#txtFechaVence").text(data.fechaVence);
            $("#txtEmision").text(data.totalMesEmision);
            $("#txtPagado").text(data.totalMesPagado);
            $("#txtDebe").text(data.debeMes);
            $("#txtNroDocumento").text(data.nroDoc);
            
            contentTable='';
            $.each(data.dataConcepto,function(index,value){ 
                contentTable+="<tr>"
                    +"<td>"+value['concepto']+"</td>"
                    +"<td>"+value['total']+"</td>"
                    +"<td style='cursor:pointer;' data-toggle='tooltip' title='"+value['comentario']+"'><i class='ace-icon fa fa-comment blue'></i></td>"
                +"</tr>";
            });
            //rows total
            contentTable+="<tr>"
                +"<td><strong>TOTAL</strong></td>"
                +"<td><strong>S/. "+data.totalConcepto.SumTotal+"</strong></td>"
                +"<td>&nbsp;</td>"
            +"</tr>";
            $("#tblConceptos tbody").html(contentTable);

            //data detalle ingreso parcial
            contentTableDetalle='';
            $.each(data.dataDetalle, function(index,value){
                contentTableDetalle+="<tr>"
                +"<td>"+value['tipoDocumento']+"</td>"
                +"<td>"+value['interes']+"</td>"
                +"<td>"+value['importe']+"</td>"
                +"<td>"+value['banco']+"</td>"
                +"<td>"+value['numeroOperacion']+"</td>"
                +"<td>"+value['fechaPago']+"</td>"
            });
            $("#tblDetalle tbody").html(contentTableDetalle);

            $("#modalInfo").modal('show');

            $('[data-toggle="tooltip"]').tooltip();

        },'json').fail(function(){
            alerta('Ingreso',"Error al intentar recuperar datos del servidor.",'error');
        });
        setTimeout(function(){
            $('[data-toggle="tooltip"]').tooltip(); 
        },500);
    },

    descargarEECC:function(obj)
    {
        var mes=$(obj).parent().parent().attr("mes");
        var anio=DateControls.yearSelected;
        var idrow=$("#unidad").val();
        if(idrow!=''){
            $(obj).find("i").removeClass("hidden");
            $.post('ue/cuotas/crear-pdf',{
                'month':mes,
                'year':anio,
                'type':'estandar',
                'idrow':idrow,
                'filterx':'',
                'admin':'1'
            },function(data){
                console.log(data);
                if(data.message==='success'){
                    $(obj).find("i").addClass("hidden");
                    setTimeout(function(){
                      window.open(data.ruta,'_blank');
                    },1000);
                }else{
                    console.log('error');
                }
            },'json').fail(function(){
                console.log('error');
            });
        }else if($idrow==0){
            alerta('Cuotas',"No hay unidades.",'advertencia');
        }else{
            alerta('Cuotas',"Seleccione una unidad.",'advertencia');
        }
    }

}



$(function(){
   
    cuotas.loadData(DateControls.yearSelected,DateControls.idUnidad);

    $("#unidad").change(function(e){
        DateControls.idUnidad = $(this).val();
        
        cuotas.loadData(DateControls.yearSelected, $(this).val());
    });
});

$("#modalInfo").find(".modal-dialog").css("width","60%");
setTimeout(function(){
	$("#tblPagos").delegate(".rest","dblclick", function(){
		var idIngreso = $(this).attr("data-id");
		cuotas.info(idIngreso);
	});
},250);

$(document).ready(function(){

	var idedificio = $(".id").attr("data-id");

	var servicio = $("#servicio").val();

	//var year = $("#periodo").val();

	var tipo = $("#tipo").val();

	

	consumo.cargaGrid(idedificio, servicio, DateControls.yearSelected, tipo);



	//resize to fit page size

	$(window).on('resize.jqGrid', function () {

	    $("#tblConsumo").jqGrid( 'setGridWidth', $(".page-content").width());

	})

	//resize on sidebar collapse/expand

	var parent_column = $("#tblConsumo").closest('[class*="col-"]');

	$(document).on('settings.ace.jqGrid' , function(ev, event_name, collapsed) {

	    if( event_name === 'sidebar_collapsed' || event_name === 'main_container_fixed' ) {

	        //setTimeout, solo para dar tiempo a los cambios del DOM y luego volver a dibujar el grid.

	        setTimeout(function(){

	            $("#tblConsumo").jqGrid( 'setGridWidth', parent_column.width() );

	        }, 0);

	     }

	});

	$(window).triggerHandler('resize.jqGrid');



	$("#cboSer").change(function (){

        consumo.recargaGrid(idedificio,DateControls.yearSelected);

    });

    $("#periodo").change(function(){

        consumo.recargaGrid(idedificio,DateControls.yearSelected);

    });

    $("#tipo").change(function(){

        consumo.recargaGrid(idedificio,DateControls.yearSelected);

    });

    $('.fecha').datepicker({

		'language': 'es',

		autoclose: true,

		todayHighlight: true,

		yearRange: "2010:2015"

	}).on('changeDate', function(e) {

        consumo.mostrarLecturaAnterior();

    });

    $(".btn-save").click(function(){

    	consumo.save();

    });

    $('a[ data-original-title]').tooltip();

    $(".btn-adjuntar").click(function(){

    	$("#filexls").click();

    });	

	

	consumo.mostrarLecturaAnterior();

    consumo.calculoConsumo();



    $("#txtLecFin").keyup(function(){

    	$("#txtLecFin").removeAttr("style");

    	$(".msg-error").text('');

        consumo.calculoConsumo();

    });



    $("#cboUnidadMedida").change(function(){

        consumo.calculoConsumo();

    });



    $("#cboTipoUnidadMedida").change(function(){

        consumo.calculoConsumo();

    });



    $("#cboServicio").change(function(){

        consumo.mostrarLecturaAnterior();        

    });



    $("#cboUnidadInmobiliaria").change(function(){

        consumo.mostrarLecturaAnterior();        

    });



    $(".moneda").keypress(function(e){

	  if(e.which == 0) return true;

	  if(e.which == 8) return true;

	  if(e.which == 45) return false;                

	  if(e.which < 46) return false;

	  if(e.which > 46 && e.which<48) return false;

	  if(e.which > 57 ) return false;

	});



	$('#txtHora').timepicker({

		minuteStep: 1,

		showSeconds: true,

		showMeridian: false

	}).next().on(ace.click_event, function(){

		$(this).prev().focus();

	});



	$(".btn-close").click(function(){

		consumo.eliminarArchivo();

	});


	$('.datepicker').datepicker({
	    'language': 'es',
	    autoclose: true,
	    todayHighlight: true,
	    format:'dd-mm-yyyy',
	});

	$('.datepicker').on('show', function(e){
    //console.debug('show', e.date, $(this).data('stickyDate'));
	    if ( e.date ) {
	         $(this).data('stickyDate', e.date);
	    }
	    else {
	         $(this).data('stickyDate', null);
	    }
	});

	$('.datepicker').on('hide', function(e){
	    console.debug('hide', e.date, $(this).data('stickyDate'));
	    var stickyDate = $(this).data('stickyDate');
	    
	    if ( !e.date && stickyDate ) {
	        console.debug('restore stickyDate', stickyDate);
	        $(this).datepicker('setDate', stickyDate);
	        $(this).data('stickyDate', null);
	    }
	});

	$("#btn-change-fecha").click(function() {
		var year=DateControls.yearSelected;
		var mes=$("#co_mesLectura").val();
		var fecha=$("#text_fechaLectura").val();

		$.post(consumo.consumoController+"actualizar-fecha-lectura",{
			year:year,
			mes:mes,
			fecha:fecha
		},function(data){
			$(".msg-error").text('');
			
        	if(data.tipo == 'informativo'){
        		$("#modalInfoLectura").modal('hide');
            	setTimeout(function(){
            		alerta("Consumo de agua",data.mensaje,'informativo');
            		$("#tblConsumo").trigger("reloadGrid");
            	},1200);
        	}else{
        		alerta('Error','Ocurrio un problema en el servidor, por favor vuelva a intentarlo.','error');
        	}
		},'json');

	});



});

function modalChangeFechaLectura(control){
	var idMes=control.getAttribute('id').replace('labelFec_','');
	var fecha=control.children[0].innerHTML;
	var year=DateControls.yearSelected;


	$("#text_fechaLectura").val('');
	var fecha_format='';

	if(fecha!=""){
		fecha=fecha.split("/");
		fecha_format=fecha[0]+"/"+fecha[1]+"/"+fecha[2];
		$("#text_fechaLectura").datepicker('setDate', fecha_format);
	}
	$("#co_mesLectura").val(idMes);

	var meses=["","enero","febrero","marzo","abril","mayo","junio","julio","agosto","septiembre","octubre","noviembre","diciembre"];
	
	//

	$("#modalInfoLectura .modal-title").html("Lectura "+meses[idMes]);
	$("#modalInfoLectura").modal("show");
}

var DateControls={

	idedificio:$(".id").attr("data-id"),

    yearSelected:$("#currentYear").html(),

    nextYear:function(){

        this.yearSelected++;

        $("#currentYear").html(this.yearSelected);

        consumo.recargaGrid(this.idedificio,this.yearSelected);

    },

    backYear:function(){

        this.yearSelected--;

        $("#currentYear").html(this.yearSelected);

        consumo.recargaGrid(this.idedificio,this.yearSelected);

    }

}



var consumo = {



	idedificio: '',

	consumoController: 'proceso/consumo/',

	lecturaNombreTemporal:null,

	formatoNombreTemporal:null,

	ruta:$(".breadcrumb").text().trim(),



	cargaGrid:function(idedificio, servicio, year, tipo)

	{

	    $("#tblConsumo").jqGrid({

	        url: this.consumoController+"cargar-grid-consumo",

	        postData: {idedificio:idedificio,servicio:servicio,year:year,tipo:tipo},

    		mtype: 'post',

	        treeGrid: true,

	        treeGridModel : 'adjacency',

	        ExpandColumn : 'unidad',

	        datatype: 'json',

	        colNames: ['UNIDAD',
        		'LECTURA <div class="wrapper-flectura" onclick="modalChangeFechaLectura(this)" id="labelFec_1"> <span></span> </div> ' , 'CONSUMO ',
        		'LECTURA <div class="wrapper-flectura" onclick="modalChangeFechaLectura(this)" id="labelFec_2"> <span></span> </div> ' ,'CONSUMO ',
         		'LECTURA <div class="wrapper-flectura" onclick="modalChangeFechaLectura(this)" id="labelFec_3"> <span></span> </div> ' , 'CONSUMO ',
          		'LECTURA <div class="wrapper-flectura" onclick="modalChangeFechaLectura(this)" id="labelFec_4"> <span></span> </div> ' , 'CONSUMO ',
           		'LECTURA <div class="wrapper-flectura" onclick="modalChangeFechaLectura(this)" id="labelFec_5"> <span></span> </div> ' , 'CONSUMO ',
            	'LECTURA <div class="wrapper-flectura" onclick="modalChangeFechaLectura(this)" id="labelFec_6"> <span></span> </div> ' , 'CONSUMO ',
             	'LECTURA <div class="wrapper-flectura" onclick="modalChangeFechaLectura(this)" id="labelFec_7"> <span></span> </div> ' , 'CONSUMO ',
              	'LECTURA <div class="wrapper-flectura" onclick="modalChangeFechaLectura(this)" id="labelFec_8"> <span></span> </div> ' , 'CONSUMO ',
               	'LECTURA <div class="wrapper-flectura" onclick="modalChangeFechaLectura(this)" id="labelFec_9"> <span></span> </div> ' , 'CONSUMO ',
                'LECTURA <div class="wrapper-flectura" onclick="modalChangeFechaLectura(this)" id="labelFec_10"> <span></span> </div>  ', 'CONSUMO ',
                'LECTURA <div class="wrapper-flectura" onclick="modalChangeFechaLectura(this)" id="labelFec_11"> <span></span> </div>  ','CONSUMO ',
                'LECTURA <div class="wrapper-flectura" onclick="modalChangeFechaLectura(this)" id="labelFec_12"> <span></span> </div>  ', 'CONSUMO '
	        ],

	        colModel: [

	              {name: 'unidad', index: 'unidad', width: 250, formatter: this.tUnidad, sortable:false},

	              {name: 'enero1', index: 'enero1',width: 90, align: 'right', sortable:false},

	              {name: 'enero2', index: 'enero2',width: 90, align: 'right', sortable:false},

	              {name: 'febrero1', index: 'febrero1',width: 90, align: 'right', sortable:false},

	              {name: 'febrero2', index: 'febrero2',width: 90, align: 'right', sortable:false},

	              {name: 'marzo1', index: 'marzo1',width: 90, align: 'right', sortable:false},

	              {name: 'marzo2', index: 'marzo2',width: 90, align: 'right', sortable:false},

	              {name: 'abril1', index: 'abril1',width: 90, align: 'right', sortable:false},

	              {name: 'abril2', index: 'abril2',width: 90, align: 'right', sortable:false},

	              {name: 'mayo1', index: 'mayo1',width: 90, align: 'right', sortable:false},

	              {name: 'mayo2', index: 'mayo2',width: 90, align: 'right', sortable:false},

	              {name: 'junio1', index: 'junio1',width: 90, align: 'right', sortable:false},

	              {name: 'junio2', index: 'junio2',width: 90, align: 'right', sortable:false},

	              {name: 'julio1', index: 'julio1',width: 90, align: 'right', sortable:false},

	              {name: 'julio2', index: 'julio2',width: 90, align: 'right', sortable:false},

	              {name: 'agosto1', index: 'agosto1',width: 90, align: 'right', sortable:false},

	              {name: 'agosto2', index: 'agosto2',width: 90, align: 'right', sortable:false},

	              {name: 'septiembre1', index: 'septiembre1',width: 90, align: 'right', sortable:false},

	              {name: 'septiembre2', index: 'septiembre2',width: 90, align: 'right', sortable:false},

	              {name: 'octubre1', index: 'octubre1',width: 90, align: 'right', sortable:false},

	              {name: 'octubre2', index: 'octubre2',width: 90, align: 'right', sortable:false},

	              {name: 'noviembre1', index: 'noviembre1',width: 90, align: 'right', sortable:false},

	              {name: 'noviembre2', index: 'noviembre2',width: 90, align: 'right', sortable:false},

	              {name: 'diciembre1', index: 'diciembre1',width: 90, align: 'right', sortable:false},

	              {name: 'diciembre2', index: 'diciembre2',width: 90, align: 'right', sortable:false}

	        ],

	        pager: '#pager',

	        rowNum:0,

	        height:267,

	        shrinkToFit: false,

	        toolbar: [true, "top"],

	        //caption: 'edificio OK',

	        gridComplete: function(){

	            $(".ui-jqgrid-bdiv").attr("id", "id_prov");            

	            $("#t_tblConsumo").append("<table style='width: 2395px;'><tr>"+

	                  "<td style='width: 215px;'></td>"+
	                  "<td class='label-meses' align='center'><span data-id='1'>ENERO</span></td>"+
	                  "<td class='label-meses' align='center'><span data-id='2'>FEBRERO</span></td>"+
	                  "<td class='label-meses' align='center'><span data-id='3'>MARZO</span></td>"+
	                  "<td class='label-meses' align='center'><span data-id='4'>ABRIL</span></td>"+
	                  "<td class='label-meses' align='center'><span data-id='5'>MAYO</span></td>"+
	                  "<td class='label-meses' align='center'><span data-id='6' >JUNIO</span></td>"+
	                  "<td class='label-meses' align='center'><span data-id='7'>JULIO</span></td>"+
	                  "<td class='label-meses' align='center'><span data-id='8'>AGOSTO</span></td>"+
	                  "<td class='label-meses' align='center'><span data-id='9'>SEPTIEMBRE</span></td>"+
	                  "<td class='label-meses' align='center'><span data-id='10'>OCTUBRE</span></td>"+
	                  "<td class='label-meses' align='center'><span data-id='11'>NOVIEMBRE</span></td>"+
	                  "<td class='label-meses' align='center'><span data-id='12'>DICIEMBRE</span></td>"+
	                  "</tr>"+
	                  "</table>");

	              $("#id_prov").scroll(function(){

	                var scr = document.getElementById("id_prov").scrollLeft;                

	                document.getElementById("t_tblConsumo").scrollLeft = scr;

	              });

	        },
	        loadComplete: function(data){
	        	var fechaLecturas=data['fechaLecturas'];

	        	$.each(fechaLecturas, function(idLabelMes,value){
					$("#"+idLabelMes).children('span').html(value);	
	        	});

	        }

	    });

	},



	tUnidad:function(cellvalue, options, rowObject)

	{

	    var data = cellvalue.split("::");

	    var texto = "";

	    (data[0] != "Header") ? texto = "<img src='images/unidad.png' style='width: 18px; height: 18px;' /> "+data[0] : texto = data[1];

	    return texto;

	},



	recargaGrid:function(idedificio, year){

		var servicio = $("#servicio").val();

		var tipo = $("#tipo").val();

	    $('#tblConsumo').setGridParam({

	    	url: this.consumoController+"cargar-grid-consumo",

	        postData: {idedificio:idedificio,servicio:servicio,year:year,tipo:tipo}

	    });

	    $("#tblConsumo").trigger("reloadGrid");

	},



	uploadArchivo:function(obj)

	{



		var file = $(obj)[0].files[0];

		var name = file.name;

		var extension = name.split(".")[1];



		if(extension == "xls" || extension =="xlsx"){

			if(extension!="xlsx"){

				alerta('Consumo de agua','Intente subir un formato excel con versión posterior a 2003 (.xlsx).','advertencia');

				return false;

			}	

		}else{

			alerta('Consumo de agua','No se acepta archivos con este formato (.'+extension+')','advertencia');

			return false;

		}



		if(file){

		var fd = new FormData();

		fd.append("filexls", file);

		var xhr = new XMLHttpRequest();



		xhr.upload.addEventListener("progress", function(event){

		  if (event.lengthComputable) {

		    var percentComplete = Math.round(event.loaded * 100 / event.total);

		    $(".progress").fadeIn(1000).find(".progress-bar").css("width",percentComplete+"%");

		  }

		}, false);

		xhr.addEventListener("error", function(){

			alerta('Consumo','La subida de su imagen ha fallado.','advertencia');

		}, false);

		xhr.addEventListener("abort", function(){

			alerta('Consumo','Se cancelara la subida de la imagen de perfil.','advertencia');

		}, false);

		xhr.open("POST","proceso/consumo/upload");

		xhr.send(fd);

		xhr.onreadystatechange = function (aEvt) {

		  if (xhr.readyState == 4) {

		     if(xhr.status == 200){

		        var data = JSON.parse(xhr.responseText);

		        if(data.message == 'success'){

		        	consumo.lecturaNombreTemporal = data.file;

		        	$(".progress").fadeOut(1000).find(".progress-bar").hide();

		        	$(obj).parent().find(".btn-adjuntar").hide();

		        	$(obj).parent().find(".btn-close").show();

		        	alerta('Excelente','El archivo se subio correctamente al servidor.','informativo');

		        	setTimeout(function(){

		        		$(".btn-registrar").fadeIn(2000).show();

		        	},1000);

		        }else if(data.message == 'noformat'){

		        	$(".progress").fadeOut(1000).find(".progress-bar").hide();

		        	alerta('Formato no recomendado','Por favor intente subir archivos con formato .XLS ó .XLSX','advertencia');

		        }

		     }else{

		        alerta('Error','Error al subir la imagen, por favor vuelva a intentarlo.','error');

		     }                                  

		  }

		};

	}

		

	},



	eliminarArchivo:function()

	{

		$.post("proceso/consumo/eliminar-archivo",{

			file:consumo.lecturaNombreTemporal

		},function(data){

			var data = JSON.parse(data);

			if(data.message == 'success'){

				$(".btn-close").hide();

				$(".btn-registrar").hide();

				$(".btn-adjuntar").show();

				//alerta('Excelente',data.cuerpo,'informativo');

			}else{

				alerta('Error',data.cuerpo,'error');

			}

		}).fail(function(){

			alerta('Error','Ocurrio un problema en el servidor, por favor vuelva a intertarlo.','error');

		});

	},



	registrarArchivo:function(obj)

	{

		$(obj).find(".spinner").removeClass("hidden");

		$(obj).find(".no-text-shadow").text("Registrando...");

		$.post("proceso/consumo/registrar",{

			file:consumo.lecturaNombreTemporal,

			ruta:this.ruta

		},function(data){

			if(data.message == 'advertencia'){

				$(obj).find(".spinner").addClass("hidden");

				$(obj).find(".no-text-shadow").text("Correcto");

				alerta('Excelente',data.cuerpo,'advertencia');

			}else{

				alerta('Excelente',data.cuerpo,'informativo');

			}



			$("#myModalAdjuntarArchivo").modal('hide');

			$("#tblConsumo").trigger("reloadGrid");



		},'json').fail(function(){

			alerta('Error','Ocurrio un problema en el servidor, por favor vuelva a intertarlo.','error');

		});

	},



	view:function(accion)

	{

		$("body").css("padding-right","0px");



		//buton registrar lecturas de agua 

		$(".btn-registrar, .btn-close").hide();

		$(".btn-registrar").find(".no-text-shadow").text("Registrar");

		$(".btn-adjuntar").show();



		var accion = $(accion).attr("btn-accion");

		if(accion == 'tarifa') $("#myModalTarifaSedapal").modal('show');

		else if(accion == 'new') $("#myModalNewConsumo").modal('show');

		else $("#myModalAdjuntarArchivo").modal('show');

		$(".modal-dialog").css("width","600px");

	},



	downloadURI:function(uri, name) {

      var link = document.createElement("a");

      link.download = name;

      link.href = uri;

      link.click();

    },



	formato:function(obj)

	{

		var key = $(obj).attr("key");

		$.post("proceso/consumo/formato",{

			accion:key,

			ruta:this.ruta

		},function(data){

			if(data.message == 'success'){

				alerta('Consumo','Formato generado correctamente.','informativo');

				var url = data.ruta;

	            var name = data.nombreFile;

	            consumo.formatoNombreTemporal = data.nombreFile;

	            consumo.downloadURI(url,name);

	            setTimeout(function(){

	            	$.post("proceso/consumo/formato",{

	            		accion:'delete',

	            		file:consumo.formatoNombreTemporal

	            	},function(data){},'json').fail(function(){});

	            },3000);

			}else{

				alerta('Error','Ocurrio un problema en el servidor, por favor vuelva a intertarlo.','error');

			}

		},'json').fail(function(){

			alerta('Error','Ocurrio un problema en el servidor, por favor vuelva a intertarlo.','error');

		});

	},



	mostrarLecturaAnterior:function()

	{

	    var codigoUnidad = $("#cboUnidadInmobiliaria").val();

	    var fecha = $("#txtFecha").val();

	    var servicio = $("#cboServicio").val();

	    $.post(this.consumoController+"get-lectura", {

	    	codigoUnidad:codigoUnidad,

	    	fecha:fecha,

	    	servicio:servicio

	    },function(data){

	    	var data = JSON.parse(data);

	        $("#txtLecIni").val(data.lecturaAnterior);        

	        $("#txtLecFin").val(data.lecturaActual);

	        $("#txtHora").val(data.hora);

	        $("#cboTipoUnidadMedida option[value="+data.tipo+"]").attr("selected", "selected");

	        $("#cboUnidadMedida option[value="+data.tipoVer+"]").attr("selected", "selected");

	        consumo.calculoConsumo();

	    });

	},



	calculoConsumo:function(){

	    var LecIni = $("#txtLecIni").val();

	    var LecFin = $("#txtLecFin").val();

	    var cboUniMed = $("#cboUnidadMedida").val();

	    var cboTipoUniMed = $("#cboTipoUnidadMedida").val();

	    var Consumo = 0;

	    if(cboUniMed == 'LITRO'){

	        if(cboTipoUniMed == 'LITRO'){

	            Consumo = LecFin - LecIni;

	        }else if(cboTipoUniMed == 'M3'){

	            Consumo = (LecFin - LecIni) * 1000;

	        }else if(cboTipoUniMed == 'GALON'){

	            Consumo = (LecFin - LecIni) * 3.7854;

	        }

	    }else if(cboUniMed == 'M3'){

	        if(cboTipoUniMed == 'LITRO'){

	            Consumo = (LecFin - LecIni) / 1000;

	        }else if(cboTipoUniMed == 'M3'){            

	            Consumo = LecFin - LecIni;

	        }else if(cboTipoUniMed == 'GALON'){

	            Consumo = (LecFin - LecIni) / 264.17;

	        }

	    }else if(cboUniMed == 'GALON'){

	        if(cboTipoUniMed == 'LITRO'){

	            Consumo = (LecFin - LecIni) / 3.7854;

	        }else if(cboTipoUniMed == 'M3'){

	            Consumo = (LecFin - LecIni) * 264.1728;

	        }else if(cboTipoUniMed == 'GALON'){

	            Consumo = LecFin - LecIni;

	        }

	    }

	    Consumo = Math.round(Consumo * 100) / 100;

	    (LecFin != '') ? $("#txtConsumo").val(Consumo) : $("#txtConsumo").val(0);

	},



	save:function()

	{ 

	    var coduni = $.trim($("#cboUnidadInmobiliaria").val());

	    var servicio = $.trim($("#cboServicio").val());

	    var txtFecha = $.trim($("#txtFecha").val());

	    var txtHora = $.trim($("#txtHora").val());

	    var lecact = $.trim($("#txtLecFin").val());

	    var lecant = $.trim($("#txtLecIni").val());

	    var tipcon = $("#cboTipoUnidadMedida").val();

	    var tipver = $("#cboUnidadMedida").val();

	    var consumo = (lecact - lecant);

	    var estLecFin = $("#txtLecFin").attr("readonly");7



	    if(lecact == ''){

	    	$("#txtLecFin").css("border","1px solid rgba(255, 0, 0, 0.72)");

	    	$(".msg-error").text('Debe ingresar la lectura actual.');

	    }else{

	    	$.ajax({

	            type: "POST",

	            url: this.consumoController+"save",

	            data: "coduni="+coduni+"&servicio="+servicio+"&fecha="+txtFecha+"&hora="+txtHora+"&lecact="+lecact+"&consumo="+consumo+"&tipcon="+tipcon+"&tipver="+tipver+"&ruta="+this.ruta,

	            success:function(data){

	            	$(".msg-error").text('');

	            	var data = JSON.parse(data);

	            	if(data.result == 'success'){

	            		$("#myModalNewConsumo").modal('hide');

		            	setTimeout(function(){

		            		alerta(data.titulo,data.cuerpo,'informativo');

		            		$("#tblConsumo").trigger("reloadGrid");

		            	},1200);

	            	}else{

	            		alerta('Error','Ocurrio un problema en el servidor, por favor vuelva a intentarlo.','error');

	            	}

	            },

	            fail:function(){

	            	alerta('Error','Ocurrio un problema en el servidor, por favor vuelva a intentarlo.','error');

	            }

	        });



	    }



	}



}










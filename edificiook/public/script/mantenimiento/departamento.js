/**
 * edificioOk (https://www.edificiook.com)
 * creado por: Meler Carranza, 11/03/2016.
 * ultima modificacion por: Jhnon Gómez
 * Fecha Modificacion: 21/04/2016.
 * Descripcion: 
 *
 * @autor     Fidel J. Thompson 
 * @link      https://www.edificiook.com
 * @copyright Copyright (c) 2011-2016 KND S.A.C (http://www.knd.pe)
 * @license   http://www.edificiook.com/license/comercial Software Comercial
 * 
 */

var GridUnidad={

    unidadController:"mantenimiento/departamento/grid",
    gridId:"#grid-table",
    pageGridId:"#grid-pager",

    init:function(){

        $(this.gridId).jqGrid({
            url: this.unidadController,
            datatype: "json",
            mtype: "POST",
            colNames:["","Descripción","Residente",'Email',"Area m2","% Cuota","% Participación"],
            colModel:[
                {name:'botones', index:'botones', width:50, align:'center',sortable:false,search: false},
                {name:'unidad',index:'unidad', width:220},
                {name:'residente',index:'residente', width:250},
                {name:'email',index:'email', width:250,sortable:false},
                {name:'aocupada_m2',index:'aocupada_m2', width:80, align:'right',search: false,sortable:true},
                {name:'pct_cuota_m2',index:'pct_cuota_m2', width:80,align:'right',search: false,sotable:true},
                {name:'pct_participacion',index:'pct_participacion', width:60,align:'right',search: false,sortable:false}
            ],
            sortname: 'unidad',
            treeGrid:true,
            treeGridModel: 'adjacency',
            ExpandColumn: 'unidad',
            height: '100%',
            ExpandColClick: true,
            rowList:[10,20,30],
            pager : this.pageGridId,
            sortname: 'id',
            viewrecords: true,
            sortorder: "asc",
            loadComplete:function(){
                setTimeout(function(){
                    GridUnidad.updatePagerIcons(this.gridId)
                },0)
            },
            'treeIcons':{
                plus:'ace-icon fa fa-plus center bigger-110 blue',
                minus:'ace-icon fa fa-minus center bigger-110 blue',
                leaf:'ace-icon fa fa-chevron-right center orange'
            },
        });

        //$(".ui-jqgrid-hdiv").html("<div class='filterText margin-top-10 margin-bottom-10'>&nbsp; <strong>Buscar Unidad:</strong> <input type='text' id='txtBuscar' onkeypress='rowAction.buscarUnidad(event)' name='txtBuscar' style='width: 320px;' /></div>");
        $(this.gridId).jqGrid('filterToolbar',{stringResult: true,searchOnEnter : true});
        $(this.gridId).jqGrid('navGrid','#grid-pager',{edit:false,add:false,del:false});
    },
    updatePagerIcons:function(table){
        var replacement = 
        {
            'ui-icon-seek-first' : 'ace-icon fa fa-angle-double-left bigger-140',
            'ui-icon-seek-prev' : 'ace-icon fa fa-angle-left bigger-140',
            'ui-icon-seek-next' : 'ace-icon fa fa-angle-right bigger-140',
            'ui-icon-seek-end' : 'ace-icon fa fa-angle-double-right bigger-140'
        };
        $('.ui-pg-table:not(.navtable) > tbody > tr > .ui-pg-button > .ui-icon').each(function(){
            var icon = $(this);
            var $class = $.trim(icon.attr('class').replace('ui-icon', ''));
            if($class in replacement) icon.attr('class', 'ui-icon '+replacement[$class]);
        })
    
    },

    checkBoxSelected:function(id){
        inputSelectedId = 'chkCol'+id;
        if($("#"+inputSelectedId).is(':checked')){
            $("#co_unidadId").val(id);

            $("input[type='checkbox']").each(function(index,value){
                idCurrentInput=$(value).attr('id');

                if(idCurrentInput != inputSelectedId){
                    $("#"+idCurrentInput).attr('checked', false);
                } else{
                    $("#btn-actions").removeClass('hidden');
                    $("#btn-actions-default").addClass('hidden');
                }
            });
        }else{
            $("#co_unidadId").val(0);
            $("#btn-actions").addClass('hidden');
            $("#btn-actions-default").removeClass('hidden');
        }
    }
}

$(".btn-adjuntar").click(function() {
	$("#filexls").click();
});

//operaciones de una fila seleccionada
var rowAction = {
    idFilaSeleccionada:null,
    filaSeleccionada:null,
    rowAction:'mantenimiento/departamento/row',
    saveAction:'mantenimiento/departamento/save',
    delAction:'mantenimiento/departamento/del',
    exportarAction:'mantenimiento/departamento/exportar',
	uniprincipalAction:'mantenimiento/departamento/uniprincipal',
	lecturaNombreTemporal: null,
	formatoNombreTemporal: null,
    temporal: '',
    ruta:$(".breadcrumb").text().trim(),

    init: function() {
        this.idFilaSeleccionada=$("#co_unidadId").val();//$(GridUnidad.gridId).jqGrid('getGridParam','selrow');
        this.filaSeleccionada=$(GridUnidad.gridId).jqGrid('getRowData', this.idFilaSeleccionada);
	},
	viewimport: function() {
		$("#myModalAdjuntarArchivo").modal('show');
	},
    view:function() {
        rowAction.init();
        this.read();
        $(".modal-body input").attr("readonly","");
        $("#labelModalFormUnidades").html('Unidad');
        $(".btn-save").hide();
    },
    edit:function() {
        $("#form-unidad")[0].reset();
        rowAction.init();
        this.read();
        $(".btn-save").show();
        $(".modal-body input").removeAttr("readonly");
        $("#labelModalFormUnidades").html('Editar unidad');
	},
	nuevo: function() {
		$("#form-unidadn")[0].reset();
		//rowAction.init();
		//this.read();
		$("#modalFormUnidadesNuevas").modal('show');
        $(".modal-dialog").css('width', '70%');
		$(".btn-save").show();
		$(".modal-body input").removeAttr("readonly");
		$("#labelModalFormUnidadesNuevas").html('Nueva unidad');
	},
	saveN: function() {
        // rowAction.init();
        var form = $("#form-unidadn").serializeArray();
        // form.push({name:'id',value:this.idFilaSeleccionada});
        $.post('mantenimiento/departamento/save-nuevo', form, function(data) {
            if(data.message == 'success') {
                $("#form-unidadn")[0].reset();
                // $("#co_unidadId").val(0);
                $("#modalFormUnidadesNuevas").modal('hide');
                rowAction.reloadComboUnidadesPrincipales();
                rowAction.reloadGrid();
                alerta('Excelente',data.mensaje,'informativo');
            }
        },'json')
        .fail(function() {
            alert('error');
        });
	},
    save:function() {
        rowAction.init();
        var form=$("#form-unidad").serializeArray();
        form.push({name:'id',value:this.idFilaSeleccionada});
        $.post(this.saveAction,form,function(data) {
            if(data.message == 'success') {
                $("#form-unidad")[0].reset();
                $("#co_unidadId").val(0);
                $("#modalFormUnidades").modal('hide');
                rowAction.reloadComboUnidadesPrincipales();
                rowAction.reloadGrid();
                alerta('Excelente',data.mensaje,'informativo');
            }
        },'json')
        .fail(function() {
            alert('error');
        });
	},
	downloadURI:function(uri, name) {
		var link = document.createElement("a");
		link.download = name;
		link.href = uri;
		link.click();
	},
	formato:function(obj) {
		var key = $(obj).attr("key");
		$.post("mantenimiento/departamento/formato", {
			accion: key,
			ruta: this.ruta
		}, function(data) {
			if(data.message == 'success') {
				alerta('Unidad','Formato generado correctamente.','informativo');
				var url = data.ruta;
				var name = data.nombreFile;
				rowAction.formatoNombreTemporal = data.nombreFile;
				rowAction.downloadURI(url,name);
				setTimeout(function() {
					$.post("mantenimiento/departamento/formato",{
						accion:'delete',
						file:rowAction.formatoNombreTemporal
					}, function(data){},'json').fail(function(){});
				},3000);
			} else {
				alerta('Error','Ocurrio un problema en el servidor, por favor vuelva a intertarlo.','error');
			}
		},'json').fail(function() {
			alerta('Error','Ocurrio un problema en el servidor, por favor vuelva a intertarlo.','error');
		});
	},
	uploadArchivo:function(obj) {
		var file = $(obj)[0].files[0];
		var name = file.name;
		var extension = name.split(".")[1];

		if(extension == "xls" || extension =="xlsx") {
			if(extension!="xlsx") {
				alerta('Unidades','Intente subir un formato excel con versión posterior a 2003 (.xlsx).','advertencia');
				return false;
			}
		} else {
			alerta('Unidades','No se acepta archivos con este formato (.'+extension+')','advertencia');
			return false;
		}
		
		if(file) {
			var fd = new FormData();
			fd.append("filexls", file);
			var xhr = new XMLHttpRequest();

			xhr.upload.addEventListener("progress", function(event) {
				if (event.lengthComputable) {
					var percentComplete = Math.round(event.loaded * 100 / event.total);
					$(".progress").fadeIn(1000).find(".progress-bar").css("width",percentComplete+"%");
				}
			}, false);

			xhr.addEventListener("error", function() {
				alerta('Consumo','La subida de su imagen ha fallado.','advertencia');
			}, false);

			xhr.addEventListener("abort", function() {
				alerta('Consumo','Se cancelara la subida de la imagen de perfil.','advertencia');
			}, false);

			xhr.open("POST","mantenimiento/departamento/upload");
			xhr.send(fd);

			xhr.onreadystatechange = function (aEvt) {
				if (xhr.readyState == 4) {
					if(xhr.status == 200) {
						var data = JSON.parse(xhr.responseText);
						if(data.message == 'success') {
							rowAction.lecturaNombreTemporal = data.file;
							$(".progress").fadeOut(1000).find(".progress-bar").hide();
							$(obj).parent().find(".btn-adjuntar").hide();
							$(obj).parent().find(".btn-close").show();
							alerta('Excelente','El archivo se subio correctamente al servidor.','informativo');
							setTimeout(function() {
								$(".btn-registrar").fadeIn(2000).show();
							},1000);
						} else if(data.message == 'noformat') {
							$(".progress").fadeOut(1000).find(".progress-bar").hide();
							alerta('Formato no recomendado','Por favor intente subir archivos con formato .XLS ó .XLSX','advertencia');
						}
					} else {
						alerta('Error','Error al subir la imagen, por favor vuelva a intentarlo.','error');
					}
				}
			};
		}
	},
	registrarArchivo:function(obj) {
		$(obj).find(".spinner").removeClass("hidden");
		$(obj).find(".no-text-shadow").text("Registrando...");
		
		$.post("mantenimiento/departamento/registrar", {
			file:rowAction.lecturaNombreTemporal,
			ruta:this.ruta
		}, function(data) {
			if(data.message == 'advertencia') {
				$(obj).find(".spinner").addClass("hidden");
				$(obj).find(".no-text-shadow").text("Correcto");
				alerta('Excelente',data.cuerpo,'advertencia');
			} else {
				alerta('Excelente',data.cuerpo,'informativo');
			}

			$("#myModalAdjuntarArchivo").modal('hide');
			$("#tblConsumo").trigger("reloadGrid");
		},'json').fail(function() {
			alerta('Error','Ocurrio un problema en el servidor, por favor vuelva a intertarlo.','error');
		});
	},
    reloadGrid:function() {
        $(GridUnidad.gridId).setGridParam({ 
                url: GridUnidad.unidadController,
                postData: '',
            }
        );
        $(GridUnidad.gridId).trigger("reloadGrid");
    },
    reloadComboUnidadesPrincipales:function() {
        $.post(this.uniprincipalAction,{'q':'222121'},function(data){
            var option='<option value="0">Ninguno</option>';
            $.each(data,function(index,value){
                option+='<option value="'+value['id']+'">'+value['descripcion']+'</option>';
            });
            $("#selUnidadPadre").html(option);
            $("#selUnidadPadre").trigger("chosen:updated");
        },'json')
        .fail(function(){
            alert('error');
        });
    },
    del:function() {
        rowAction.init();
        alerta('Unidades','Esta opción esta bloqueada temporalmente.','advertencia');
    },
    read:function()
    {
        this.idFilaSeleccionada=$("#co_unidadId").val();
        
        $.post(this.rowAction,{
            id:this.idFilaSeleccionada
        },function(unidad){
            $("#selTipo").val(unidad.uni_tip).trigger("chosen:updated");
            $("#textNombre").val(unidad.uni_nom);
            $("#textAream2").val(unidad.uni_aream2);
            $("#textAreaOcupada").val(unidad.uni_aocu);
            $("#textPct").val(unidad.uni_pct);
            $("#textCuota").val(unidad.uni_cm2);
            $("#selPropietario").val(unidad.propietarioId).trigger("chosen:updated");
            $("#selResidente").val(unidad.residenteId).trigger("chosen:updated");
            $("#textNroMunicipal").val(unidad.uni_nmun);
            $("#textNroPartida").val(unidad.uni_npar);
            $("#textDireccion").val(unidad.uni_dir);
            
            if(unidad.padre == false){ //principal
                $(".unidad-padre").show();
                $("#selUnidadPadre option[value="+unidad.uni_cod+"]").hide();
                rowAction.temporal = unidad.uni_cod;
                $("#selUnidadPadre").find("option").removeAttr("selected");
                if(unidad.uni_pad!=null){
                    //$("#selUnidadPadre option[value="+unidad.uni_pad+"]").attr("selected", true);
                    $("#selUnidadPadre").val(unidad.uni_pad).trigger("chosen:updated");
                }
            }else{
                if(rowAction.temporal) $("#selUnidadPadre option[value="+rowAction.temporal+"]").show();
                if(unidad.uni_pad==null){
                    $(".unidad-padre").hide();
                }
            }

            var numeroSotano = (unidad.numeroSotano - (unidad.numeroSotano * 2));
            $("#selPiso").find("option").remove();
            var dataPiso = '';
            for(i=numeroSotano;i<=unidad.numeroPiso;i++){
                (i==unidad.uni_pis)?select='selected="select"':select='';
                if(i<0) dataPiso += '<option value="'+i+'" '+select+'>Sotano '+i+'</option>';
                else if(i>0) dataPiso += '<option value="'+i+'" '+select+'>Piso '+i+'</option>';
            }
            $("#selPiso").html(dataPiso);
            $("#taDescripcion").html(unidad.uni_desc);
            $("#form-unidad input[type='text']").attr('enabled');
        },'json')
        .fail(function() {
            alert( "error" );
        });

        $("#modalFormUnidades").modal('show');
        $(".modal-dialog").css('width', '70%');
    },
    descargar:function(){
        $(".modal-dialog").css("width","300px").css("margin","0 auto").css("margin-top","25%");
        $(".modal-dialog").css("width","300px");
        $("#modalLoading").modal('show');


        $.post(this.exportarAction,{
            ruta:this.ruta
        },function(data){
            if(data.message == 'success'){
                $("#modalLoading").modal('hide');

                var link = document.createElement("a");
                link.download = data.nombreFile;
                link.href = data.ruta;
                link.click();
            }else{
                alerta('Error','Ocurrió un problema en el servidor al intentar generar el archivo, por favor vuelva a intertarlo.','error');
            }
        },'json').fail(function(){
            alerta('Error','Ocurrió un problema en el servidor, por favor vuelva a intertarlo.','error');
        });
    }
}

GridUnidad.init();

//resize to fit page size
$(window).on('resize.jqGrid', function () {
    $(GridUnidad.gridId).jqGrid( 'setGridWidth', $(".page-content").width());
    $(GridUnidad.gridId).parents(".ui-jqgrid-bdiv").css('max-height', $(window).height()-270);
})

//resize on sidebar collapse/expand
var parent_column = $(GridUnidad.gridId).closest('[class*="col-"]');
$(document).on('settings.ace.jqGrid' , function(ev, event_name, collapsed) {
    if( event_name === 'sidebar_collapsed' || event_name === 'main_container_fixed' ) {
        //setTimeout is for webkit only to give time for DOM changes and then redraw!!!
        setTimeout(function(){
            $(GridUnidad.gridId).jqGrid( 'setGridWidth', parent_column.width() );
        }, 0);
     }
});
$(window).triggerHandler('resize.jqGrid');//trigger window resize to make the grid get the correct size
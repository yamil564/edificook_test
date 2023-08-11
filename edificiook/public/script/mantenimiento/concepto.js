/**
 * edificioOk (https://www.edificiook.com)
 * creado por: Jhon Gomez, 10/05/2016.
 * ultima modificacion por: Meler Carranza
 * Fecha Modificacion: 25/05/2016.
 * Descripcion: 
 *
 * @autor     Fidel J. Thompson
 * @link      https://www.edificiook.com
 * @copyright Copyright (c) 2011-2016 KND S.A.C (http://www.knd.pe)
 * @license   http://www.edificiook.com/license/comercial Software Comercial
 */

var Concepto = {

	gridId:'#grid-concepto',
	gridPager:'#grid-pager',

    init:function()
    {
        $(this.gridId).jqGrid({
            url:'mantenimiento/concepto/grid-concepto',
            mtype: 'post',
            datatype: 'json',
            colNames: ['','Descripción','Tipo','Grupo'],
            colModel :[
		          {name:'botones', index:'botones',width:40,search: false},
		          {name:'con_vc_des', index:'con_vc_des',width:350},
                  {name:'con_vc_tip', index:'con_vc_tip',width:350},
		          {name:'cog_vc_des', index:'cog_vc_des',width:350}
		    ],
            pager: this.gridPager,
            rowNum: 10,
            rowList: [10,30,50],
            sortname: 'con_in_cod', //con_vc_tip
            sortorder: 'desc',
            sortable: true,
            viewrecords: true,
            toolbar: [true,"top"],
            height: 'auto',
            shrinkToFit: false,
            grouping: true, 
              groupingView : { 
                groupColumnShow : [true],
                groupText : ['<b>{0} - {1} fila(s)</b>'],
                groupCollapse : false,
                groupOrder: ['asc'],
                groupSummary : [false],
                groupDataSorted : true
              },
            footerrow: false,
            userDataOnFooter: false,
            gridComplete: function()
            {
            	$("#t_grid-concepto").append("<div class='filterText'>&nbsp; Agrupar por: <select id='cbo_columns' style='width: 120px;'><option value='clear'>&nbsp;NINGUNA</option><option value='cog_vc_des'>&nbsp;GRUPO</option><option value='con_vc_tip'>&nbsp;TIPO</option></select></div>");
                /* Evento change del combobox cbo_columns el cual agrupa y desagrupa los datos del jqGrid segun la opción seleccioanda. */
                $("#cbo_columns").change(function(){
                    var vl = $(this).val();
                    if(vl){
                        if(vl == "clear"){
                            $(Concepto.gridId).jqGrid('groupingRemove',true);
                        }else{
                            $(Concepto.gridId).jqGrid('groupingGroupBy',vl);
                        }
                    }
                });
            }
        });

        $(this.gridId).jqGrid('filterToolbar',{stringResult: true,searchOnEnter : true});
        $(this.gridId).jqGrid('navGrid',this.gridPager,{edit:false,add:false,del:false});

        this.resizeGrid();

    },

    resizeGrid:function(){
    	//resize to fit page size
		$(window).on('resize.jqGrid', function () {
		    $(Concepto.gridId).jqGrid( 'setGridWidth', $(".page-content").width());
		    $(Concepto.gridId).parents(".ui-jqgrid-bdiv").css('max-height', $(window).height()-300);
		})

		//resize on sidebar collapse/expand
		var parent_column = $(Concepto.gridId).closest('[class*="col-"]');
		$(document).on('settings.ace.jqGrid' , function(ev, event_name, collapsed) {
		    if( event_name === 'sidebar_collapsed' || event_name === 'main_container_fixed' ) {
		        //setTimeout is for webkit only to give time for DOM changes and then redraw!!!
		        setTimeout(function(){
		            $(Concepto.gridId).jqGrid( 'setGridWidth', parent_column.width() );
		        }, 0);
		     }
		});
		$(window).triggerHandler('resize.jqGrid');//trigger window resize to make the grid get the correct size
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

//iniciar table
Concepto.init();

var action = {

    filaSeleccionada:null,

    init:function()
    {
    	this.filaSeleccionada=$(Concepto.gridId).jqGrid('getGridParam','selrow');
    },

    new:function()
    {
        $(".formConcepto")[0].reset();
        $("#msg-descripcion").text('');
        $(".formConcepto").attr("action","add");
    	$("#myModalFormConcepto").modal('show');
    },
    
    edit:function()
    {	
    	this.init()
        $(".formConcepto")[0].reset();
        $("#msg-descripcion").text('');
        $(".formConcepto").attr("action","edit");
        $("#myModalFormConcepto").modal('show');
        //read concepto
        $.post('mantenimiento/concepto/read',{
            id:this.filaSeleccionada
        }, function(data){
            $("input[name$='descripcion']").val(data.descripcion);
            $("select[name='grupo'] option").removeAttr("selected");
            $("select[name='grupo'] option[value='"+data.codigoGrupo+"']").prop('selected', 'selected');
            $("select[name='tipo'] option[value='"+data.tipo+"']").prop('selected', 'selected');
        },'json').fail(function(){
            alerta('Concepto','Ocurrió un error desconocido en el servidor.','error');
        });
    },

    delete:function()
    {
        this.init()
        $.post('mantenimiento/concepto/delete',{
            id:this.filaSeleccionada
        },function(data){
            if(data.message === 'success'){
                $("#btn-actions-default").removeClass("hidden");
                $("#btn-actions").addClass("hidden");
                $(Concepto.gridId).trigger("reloadGrid");
                $("#myModalFormDeleteConcepto").modal('hide');
                alerta('Concepto',data.body,data.tipo);
            }else{
                alerta('Concepto',data.body,data.tipo);
            }
        },'json').fail(function(){
            alerta('Concepto','Ocurrió un error desconocido en el servidor.','error');
        });
    },

    formDelete:function()
    {
        $("#myModalFormDeleteConcepto").modal('show');
    },

    save:function(accion)
    {   
        this.init()
        formData=$(".formConcepto").serializeArray();
        formData.push({name: 'action', value: accion});
        formData.push({name: 'id', value: this.filaSeleccionada});
        $.post('mantenimiento/concepto/save',formData,function(data){
            if(data.message === 'success'){
                $("#btn-actions-default").removeClass("hidden");
                $("#btn-actions").addClass("hidden");
                $(Concepto.gridId).trigger("reloadGrid");
                $("#myModalFormConcepto").modal('hide');
                $(".formConcepto")[0].reset();
                alerta('Concepto',data.body,data.tipo);
            }else if(data.message === 'existeConcepto'){
                $("#msg-descripcion").text(data.body);
            }else{
                alerta('Concepto',data.body,data.tipo);
            }
        },'json').fail(function(){
            alerta('Concepto','Ocurrió un error desconocido en el servidor.','error');
        });
    }
}



var $Validate = $("#validateForm").validate({
  rules : {
    descripcion : { required : true },
    'grupo' : { required : true },
    'tipo' : { required : true },
  },
  messages : {
    descripcion : { required : 'Por favor, ingrese la Descripción' },
    'grupo' : { required : 'Por favor, ingrese el Grupo del Concepto' },
    'tipo' : { required : 'Por favor, ingrese el Tipo de Concepto' },
  },
  errorPlacement : function(error, element) {
    error.insertAfter(element.parent());
  },
  submitHandler: function(obj){
    var accion = $(obj).attr("action");
     action.save(accion);
  }
});
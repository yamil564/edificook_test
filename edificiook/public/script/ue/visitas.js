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

var visitas = {

    actions:null,

    init:function()
    {
        var importancia = ":TODOS;ALTA:ALTA;INTERMEDIA:INTERMEDIA;BAJA:BAJA";
        /* Sección en la que se genera el jqGrid. */
        $("#grid-visitas").jqGrid({
            url:'ue/visitas/loadVisitas',
            mtype: 'post',
            datatype: 'json',
            colNames: ['','&nbsp;Fecha &nbsp;','&nbsp;Hora &nbsp;','&nbsp;F. Ingresadadddd &nbsp;','&nbsp;H. Ingresada &nbsp;','Importancia','Asunto','Unidad inmobiliaria','Tipo','Empresa'],
            colModel: [
                        {name: 'vis_in_cod', index: 'vis_in_cod', width: 65, align: 'center', hidedlg:true},
                        {name: 'vis_da_focu', index: 'vis_da_focu', width: 100, align: 'center', sortable: true, searchoptions:{}},
                        {name: 'vis_ti_hocu', index: 'vis_ti_hocu', width: 100, align: 'center', sortable: true},
                        {name: 'vis_da_fing', index: 'vis_da_fing', width: 100, align: 'center', sortable: true, searchoptions:{}},
                        {name: 'vis_ti_hing', index: 'vis_ti_hing', width: 100, align: 'center', sortable: true},
                        {name: 'vis_vc_imp', index: 'vis_vc_imp', width: 150, align: 'center', sortable: true, stype: 'select', searchoptions:{ sopt:['cn'], value: importancia }},
                        {name: 'vis_vc_asu', index: 'vis_vc_asu', width: 150, align: 'left', sortable: true},
                        {name: 'uni_vc_tip', index: 'uni_vc_tip', width: 180, align: 'left', sortable: true},
                        {name: 'vis_vc_tip', index: 'vis_vc_tip', width: 100, align: 'left', sortable: true},
                        {name: 'vis_vc_emp', index: 'vis_vc_emp', width: 250, align: 'left', sortable: true}
            ],
            pager: '#grid-pager',
            rowNum: 10,
            rowList: [10,15,20,25,30],
            sortname: 'vis_in_cod',
            sortorder: 'desc',
            sortable: true,
            viewrecords: true,
            toolbar: [true,"top"],
            height: 300,
            shrinkToFit:false,
            grouping:true, 
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
                
                $("#t_grid-visitas").append("<div class='filterText'>&nbsp; Agrupar por: <select id='cbo_columns' style='width: 120px;'><option value='clear'>&nbsp;NINGUNA</option><option value='vis_da_focu'>&nbsp;F. OCURRENCIA</option><option value='vis_ti_hocu'>&nbsp;H. OCURRENCIA</option><option value='vis_da_fing'>&nbsp;F. INGRESADA</option><option value='vis_ti_hing'>&nbsp;H. INGRESADA</option><option value='vis_vc_imp'>&nbsp;IMPORTANCIA</option><option value='vis_vc_asu'>&nbsp;ASUNTO</option><option value='uni_vc_tip'>&nbsp;Unidad Inmobiliaria</option><option value='vis_vc_tip'>&nbsp;TIPO</option><option value='vis_vc_emp'>&nbsp;EMPRESA</option></select></div>");
                /* Evento change del combobox cbo_columns el cual agrupa y desagrupa los datos del jqGrid segun la opción seleccioanda. */
                $("#cbo_columns").change(function(){
                    var vl = $(this).val();
                    if(vl){
                        if(vl == "clear"){
                            $("#grid-visitas").jqGrid('groupingRemove',true);
                        }else{
                            console.log(vl);
                            $("#grid-visitas").jqGrid('groupingGroupBy',vl);
                        }
                    }
                });

            }
        });
        $("#cb_grid-visitas").attr('style','margin-left:3px; margin-top:0px;');
        $("#grid-visitas").jqGrid('hideCol',["vis_in_cod"]);
        /* Sección en donde se agregan funcionalidades al jqGrid. */
        $("#grid-visitas").jqGrid('navGrid','#grid-pager',{add:false,edit:false,del:false,refresh:true},{},{},{},{multipleSearch:true});
        $("#grid-visitas").jqGrid('navButtonAdd','#grid-pager',{caption: "Columnas", title: "Reordenamiento de Columnas", onClickButton : function (){$("#grid-visitas").jqGrid('columnChooser');}});
        $("#grid-visitas").jqGrid('filterToolbar',{stringResult: true,searchOnEnter: true});
        $("#grid-visitas").jqGrid('setLabel','vis_da_focu','&nbsp; Fecha &nbsp;', {'text-align':'left'},{'title':'Fecha Ocurrencia'});
        $("#grid-visitas").jqGrid('setLabel','vis_ti_hocu','&nbsp; Hora &nbsp;', {'text-align':'left'},{'title':'Hora Ocurrencia'});
        $("#grid-visitas").jqGrid('setLabel','vis_da_fing','&nbsp; F. Ingresada &nbsp;', {'text-align':'left'},{'title':'Fecha Ingresada'});
        $("#grid-visitas").jqGrid('setLabel','vis_ti_hing','&nbsp; H. Ingresada &nbsp;', {'text-align':'left'},{'title':'Hora Ingresada'});
        $("#grid-visitas").jqGrid('setLabel','vis_vc_imp','&nbsp; Importancia &nbsp;', {'text-align':'left'},{'title':'Importancia'});
        $("#grid-visitas").jqGrid('setLabel','vis_vc_asu','&nbsp; Asunto &nbsp;', {'text-align':'left'},{'title':'Asunto'});
        $("#grid-visitas").jqGrid('setLabel','uni_vc_tip','&nbsp; Unidad Inmobiliaria &nbsp;', {'text-align':'left'},{'title':'Unidad Inmobiliaria'});
        $("#grid-visitas").jqGrid('setLabel','vis_vc_tip','&nbsp; Tipo &nbsp;', {'text-align':'left'},{'title':'Tipo'});
        $("#grid-visitas").jqGrid('setLabel','vis_vc_emp','&nbsp; Empresa &nbsp;', {'text-align':'left'},{'title':'Empresa'});

        //resize to fit page size
        $(window).on('resize.jqGrid', function () {
            $('#grid-visitas').jqGrid( 'setGridWidth', $(".page-content").width());
        })
        //resize on sidebar collapse/expand
        var parent_column = $('#grid-visitas').closest('[class*="col-"]');
        $(document).on('settings.ace.jqGrid' , function(ev, event_name, collapsed) {
            if( event_name === 'sidebar_collapsed' || event_name === 'main_container_fixed' ) {
                //setTimeout, solo para dar tiempo a los cambios del DOM y luego volver a dibujar el grid.
                setTimeout(function(){
                    $('#grid-visitas').jqGrid( 'setGridWidth', parent_column.width() );
                }, 0);
             }
        });
        $(window).triggerHandler('resize.jqGrid');

    },

    /*getTime:function()
    {
        $.post('ue/visitas/time',{
            hora:1
        },function(data){
            $("#hora").val(data.time);
        },'json').fail(function(){
            console.log('error');
        });
    },*/

    save:function()
    {
        var data = $(".frmVisitas").serialize();
        $.ajax({
            type:'post',
            dataType:'json',
            url:'ue/visitas/save',
            data:data,
            success:function(data){
                if(data.message == 'success'){
                    alerta('Visitas',data.cuerpo,'informativo');
                    setTimeout(function(){
                        $("#myModalVisitas").modal('hide');
                        visitas.reloadGrid();
                    },3000);
                }else{
                    alerta('Visitas',data.cuerpo,'error');
                }
            },
            error:function(){
                console.log('error');
            }
        })
    },

    reloadGrid:function()
    {
        $("#grid-visitas").jqGrid('setGridParam',{
           url:'ue/visitas/loadVisitas'
        }).trigger("reloadGrid");
    }

}

//iniciar table
visitas.init();


var rowAction = {

    descargar:function(){
        alert('descargar');
    },

    new:function()
    {
        $(".frmVisitas").trigger("reset");
        $("#unidad, #tipo, #importancia").find('option:first-child').prop('selected', true).end().trigger('chosen:updated');
        $("#tipo").find('option:nth-child(2)').prop('selected', true).end().trigger('chosen:updated');
        $("#myModalVisitas").modal('show');
        $("#myModalVisitas").find(".modal-dialog").css("width","50%");
        $('#hora').timepicker({
            minuteStep: 1,
            showSeconds: true,
            showMeridian: false
        }).next().on(ace.click_event, function(){
            $(this).prev().focus();
        });
        /*setInterval(function(){
            visitas.getTime();
        }, 1000)*/
    },

    view:function(){
        alert('view');
    },

    edit:function(){
        alert('edit');
    },

    del:function(){
        alert('del');
    }

}


$("#gs_vis_da_focu, #gs_vis_da_fing").datepicker({
    'language': 'es',
    format:'yyyy-mm-dd',
    autoclose: true,
    todayHighlight: true,
    yearRange: "2010:2015"
}).on('changeDate', function() {
    setTimeout(function(){
        $(".datepicker").removeAttr("style");
    },100);
});

$("#gs_vis_da_focu, #gs_vis_da_fing").change(function(){
    var focu = $(this).val();
    if(focu!=''){
        $(this).focus();
    }
});

$('#fecha').datepicker({
  'language': 'es',
  autoclose: true,
  todayHighlight: true,
  yearRange: "2010:2015"
});

$("#unidad").change(function(){
    var idUnidad = $(this).val();
    $.post('ue/visitas/load-presidente',{
            idUnidad:idUnidad
    },function(data){
        console.log(data);
        var arr = data.split('::');
        var dato = arr[0].split('-');
        var dato2 = arr[1].split('-');
        $("#propietario").val(dato[0]);
        $("#codPropietario").val(dato[1]);
        $("#residente").val(dato2[0]);
        $("#codResidente").val(dato2[1]);
    },'json').fail(function(){
        console.log('error');
    });
});

//validar campos (visitas)
$("#validate").validate({
  rules : {
    txtAsunto : { required : true },
    txtFecha : { required : true },
    txtHora : { required : true },
    selUnidad : { required : true },
    selTipo : { required : true },
    selImportancia : { required : true },
    txtEmpresa : { required : true },
    txtOcurrencia : { required : true }
  },
  messages : {
    txtAsunto : { required : 'Por favor, ingrese el Asunto' },
    txtFecha : { required : 'Por favor, seleccione la Fecha' },
    txtHora : { required : 'Por favor, ingrese la Hora' },
    selUnidad : { required : 'Por favor, ingrese la Hora' },
    selTipo : { required : 'Por favor, seleccione el Tipo' },
    selImportancia : { required : 'Por favor, seleccione la Importancia' },
    txtEmpresa : { required : 'Por favor, ingrese la Empresa' },
    txtOcurrencia : { required : 'Por favor, ingrese la Ocurrencia' }
  },
  errorPlacement : function(error, element) {
    error.insertAfter(element.parent());
  },
  submitHandler: function(){
    visitas.save();
  }
});
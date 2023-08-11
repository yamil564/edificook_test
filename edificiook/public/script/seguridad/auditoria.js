var auditoria = {

	gridId:"#grid-auditoria",
    pageGridId:"#grid-pager",

	init:function()
	{

		$(this.gridId).jqGrid({
		    url:'seguridad/auditoria/grid-auditoria',
		    datatype: 'json',
		    mtype: 'POST',
		    colNames:['Usuario','Ruta','Acción','Fecha','IP','Tabla','Información Adicional'],
		    colModel :[
				{name:'usu_vc_nom', index:'usu_vc_nom', width:300},
				{name:'aud_opcion', index:'aud_opcion',width:200},
				{name:'aud_accion', index:'aud_accion', width:300},
				{name:'aud_fecha', index:'aud_fecha',width:150},
				{name:'aud_ip', index:'aud_ip',width:100},
				{name:'aud_tabla', index:'aud_tabla', width:100},
				{name:'aud_info_adi', index:'aud_info_adi',width:750}
		    ],
		    pager: this.pageGridId,
		    rowNum:10,
		    height:290,
		    width:814,
		    shrinkToFit : false,
		    rowList:[10,20,30,50,100],
		    sortname: 'aud_in_cod',
		    sortorder: 'desc',
		    viewrecords: true,
		    filterToolbar: {
		    	stringResult: true,
		    	searchOnEnter: true
		    },
		    gridComplete: function(){
		    	console.log('complete');
		    }
		});

        $(this.gridId).jqGrid('filterToolbar',{stringResult: true,searchOnEnter: true});

	}

}

auditoria.init();

$(window).on('resize.jqGrid', function () {
    $(auditoria.gridId).jqGrid( 'setGridWidth', $(".page-content").width());
})

var parent_column = $(auditoria.gridId).closest('[class*="col-"]');

$(document).on('settings.ace.jqGrid' , function(ev, event_name, collapsed) {
    if( event_name === 'sidebar_collapsed' || event_name === 'main_container_fixed' ) {
        setTimeout(function(){
            $(auditoria.gridId).jqGrid( 'setGridWidth', parent_column.width() );
        }, 0);
     }
});

$(window).triggerHandler('resize.jqGrid');
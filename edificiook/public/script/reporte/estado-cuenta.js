var estadoCuenta = {
	
	gridId:"#grid-estado",
    pageGridId:"#grid-pager",
    data:'',
    month:'',
    year:'',
    type:'',
    idrow:'',
    filterx:'',
    admin:'',

	init:function()
	{
		$(this.gridId).jqGrid({
		    url:'reporte/estado-cuenta/grid',
		    datatype: 'json',
		    //postData: {},
    		mtype: 'post',
		    colNames:['Unidad','Propietario','Poseedor','Deuda total','Condicion'],
		    colModel :[
		      {name:'unidad', index:'unidad', width:180, align:'left'},
		      {name:'propietario', index:'propietario', width:200, align:'left'},
		      {name:'poseedor', index:'poseedor', width:150, align:'left'},
		      {name:'deuda', index:'deuda', width:100, align:'right'},
		      {name:'condicion', index:'condicion', width:100, align:'right', hidedlg:true}
		    ],
		    pager: this.pageGridId,
		    rowNum:10,
		    height:'auto',
		    width:1300,
		    multiselect:true,
		    viewrecords: true,
		    rowList:[10,20,50,100],
		    sortname: 'unidad',
		    sortorder: 'asc',
		    buttonicon: 'ui-icon-disk',
		    footerrow: false,
		    userDataOnFooter: false,
		    gridComplete: function(){},
		    onSelectRow: function(id){
		    	if($('#jqg_grid-estado_'+id).is(':checked')){
					$(".btn-actions").show();
				}else{
					$(".btn-actions").hide();
					estadoCuenta.validarCheckBox();
				}
		    }

		});
	},

	validarCheckBox:function(){
		$("input[type='checkbox']").each(function(index,value){
			if($('#'+$(value).attr('id')).is(':checked')){
				$(".btn-actions").show();
			}
		});
	},
	
	modal:function(obj)
	{
		var accion = $(obj).attr("data-accion");
		var idrow = $(estadoCuenta.gridId).jqGrid('getGridParam','selarrrow');
		$("#idrow").attr("value",idrow);
		switch (accion) {
			case 'email': 
				idobj = $("#modalEmail").modal('show');
				$("#modalEmail").find("button").removeAttr("disabled");
			break;
			case 'print': idobj = $("#modalPrint").modal('show'); break;
			default: idobj = $("#modalPdf").modal('show'); break;
		}
		$(idobj.selector).find(".modal-dialog").css("width","40%");
	},

	crearPdfMail:function(typeMail,idrow, obj, mmonth, myear)
	{
		$.ajax({ 
			url: 'reporte/estado-cuenta/crear-pdf-mail',
			data: {
				'typeMail':typeMail,
	            'idrow':idrow,
				'correo':1,
				'mmonth': mmonth,
				'myear':myear
			},
			success: function(data){
				var data = JSON.parse(data);
				if(data.message == 'success'){
					$(obj).find(".fa").remove();
					//enviar mail
					$.post("reporte/estado-cuenta/correo", {
						'typeMail':typeMail,
						'fileBalance':data.fileBalance,
						'idrow':idrow
					},function(data){
						if(data.message == 'success'){
							$(".btn-mail").removeAttr("disabled");
							alerta('Estado de Cuenta',data.result,'informativo');
						}else{
							$(".btn-mail").removeAttr("disabled");
							alerta('Estado de Cuenta','Ocurrio un problema en el servidor, por favor vuelva a intentarlo.','error');
						}
					},'json').fail(function(){
						alerta('Estado de Cuenta','Ocurrio un problema en el servidor, por favor vuelva a intentarlo.','error');
					});
				}
			},
			error: function(){
				alerta('Estado de Cuenta','Ocurrio un problema en el servidor, por favor vuelva a intentarlo.','error');
			},
			type: 'POST'
		});
	},

	email:function() //null
	{
		alert('email');
	}

}

estadoCuenta.init();

$(estadoCuenta.gridId).jqGrid('navGrid',estadoCuenta.pageGridId,{add:false,edit:false,del:false,refresh:true},{},{},{},{multipleSearch:true});
$(estadoCuenta.gridId).jqGrid('navButtonAdd',estadoCuenta.pageGridId,{caption: "Columnas", title: "Reordenamiento de Columnas", onClickButton : function (){$(estadoCuenta.gridId).jqGrid('columnChooser');}});
$(estadoCuenta.gridId).jqGrid('filterToolbar',{stringResult: true,searchOnEnter: true});

$(estadoCuenta.gridId).jqGrid('setLabel','descrip','&nbsp;Unidad',{'text-align':'left'},{'title':'Unidad'});
$(estadoCuenta.gridId).jqGrid('setLabel','periodo','&nbsp;Periodo',{'text-align':'left'},{'title':'Periodo'});
$(estadoCuenta.gridId).jqGrid('setLabel','dei_do_subtot','&nbsp;Saldo',{'text-align':'rigth'},{'title':'Saldo'});
$(estadoCuenta.gridId).jqGrid('hideCol',["condicion"]);


//resize to fit page size
$(window).on('resize.jqGrid', function () {
    $(estadoCuenta.gridId).jqGrid( 'setGridWidth', $(".page-content").width());
    $(estadoCuenta.gridId).parents(".ui-jqgrid-bdiv").css('max-height', $(window).height()-270);
});

//resize on sidebar collapse/expand
var parent_column = $(estadoCuenta.gridId).closest('[class*="col-"]');
$(document).on('settings.ace.jqGrid' , function(ev, event_name, collapsed) {
    if( event_name === 'sidebar_collapsed' || event_name === 'main_container_fixed' ) {
        //setTimeout is for webkit only to give time for DOM changes and then redraw!!!
        setTimeout(function(){
            $(estadoCuenta.gridId).jqGrid( 'setGridWidth', parent_column.width() );
        }, 0);
     }
});

$(window).triggerHandler('resize.jqGrid');//trigger window resize to make the grid get the correct size


$("#validate").validate({
    rules : {
      month : {
        required : true
      },
      year: {
      	required : true
      },
      type: {
      	required : true
      }
    },
    messages : {
      month : {
        required : 'Seleccione el mes'
      },
      year: {
      	required : 'Ingrese el año'
      },
      type: {
      	required : 'Seleccione el tipo'
      }
    },
    errorPlacement : function(error, element) { 
		error.insertAfter(element.parent());
    }
});

$('#mtype').change(function(){
	var val = $(this).val();
	$('.btn-mail').attr('data-accion', val);
});

$("#mvalidate").validate({
	rules : {
	  mmonth : {
		required : true
	  },
	  myear: {
		  required : true
	  },
	  mtype: {
		  required : true
	  }
	},
	messages : {
	  mmonth : {
		required : 'Seleccione el mes'
	  },
	  myear: {
		  required : 'Ingrese el año'
	  },
	  mtype: {
		  required : 'Seleccione el tipo'
	  }
	},
	errorPlacement : function(error, element) { 
		error.insertAfter(element.parent());
	}
});

$(".btn-mail").click(function(){
	if($('#mvalidate').valid()){
		var obj = $(this);
		$(".btn-mail").attr("disabled","");
		$(this).find(".fa-spinner").remove();
		$(this).prepend('<i class="ace-icon fa fa-spinner fa-spin"></i>');	
		var mmonth = $('#mmonth').val();
		var myear = $('#myear').val();
		var typeMail = $(this).attr("data-accion");
		var idrow = $(estadoCuenta.gridId).jqGrid('getGridParam','selarrrow');
		if(typeMail == 'estado-cuenta') typeMail = 1;
		else if(typeMail == 'balance') typeMail = 2;
		else typeMail = 3;
		estadoCuenta.crearPdfMail(typeMail,idrow, obj, mmonth, myear);
	}
});

$("#type").change(function(){
	var idrow = $(estadoCuenta.gridId).jqGrid('getGridParam','selarrrow');
	var type = $(this).val();
	var form = $(this).parent().parent().parent().parent().parent();
	var controller = 'reporte/estado-cuenta/recibo-';
	form.find("#idrow").attr("value",idrow);
	if(type == 'estandar') form.attr("action",controller+type);
	else form.attr("action",controller+type);
});

$("#cb_grid-estado").click(function(){
	if($(this).is(':checked')){
	    $(".btn-actions").show();
	}else{
	    $(".btn-actions").hide();
	}
});

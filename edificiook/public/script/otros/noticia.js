var noticia = {



	gridId:"#grid-noticia",

    pageGridId:"#grid-pager",

    idnoticia:'',

    ruta:$(".breadcrumb").text().trim(),



	init:function()

	{



		$(this.gridId).jqGrid({

		    url:'otros/noticia/grid-noticia',

		    datatype: 'json',

		    mtype: 'POST',

		    colNames:['','Fecha','Noticia'],

		    colModel :[

				{name:'not_in_cod', index:'not_in_cod', width:20,formatter:this.formatChk,sortable:false,search:false},

				{name:'not_da_fec', index:'not_da_fec',width:50,align:'center',search:false},

				{name:'not_vc_tit', index:'not_vc_tit',width:300,align:'left'}

		    ],

		    pager: this.pageGridId,

		    rowNum:10,

		    height:290,

		    width:814,

		    rowList:[10,20,30],

		    sortname: 'not_da_fec',

		    sortorder: 'desc',

		    viewrecords: true,

		    filterToolbar: {

		    	stringResult: true,

		    	searchOnEnter: true

		    },

		    gridComplete: function(){

		        $(".ace").click(function(){

					var id = $(this).attr("id");

					var id = id.split("chkCol")[1];

					noticia.idnoticia = id;

				});



                $('#gs_not_da_fec').datepicker({

                  'language': 'es',

                  autoclose: true,

                  todayHighlight: true,

                  yearRange: "2010:2015"

                });

		    }

		});



        $(this.gridId).jqGrid('filterToolbar',{stringResult: true,searchOnEnter: true});



	},



	formatChk:function(cellvalue,options,rowObject){

        if(cellvalue=='' || cellvalue == null){

            return "";

        }

        return "<center><label class='position-relative tooltipmsg'><input type='checkbox' id='chkCol"+cellvalue+ "' name='chkCol"+cellvalue+"' class='ace' onclick='noticia.checkBoxSelected("+cellvalue+");'/><span class='lbl'></span></label></center>";

    },



    checkBoxSelected:function(id){

        inputSelectedId ='chkCol'+id;

        if($("#"+inputSelectedId).is(':checked')){

           $("input[type='checkbox']").each(function(index,value){

                idCurrentInput=$(value).attr('id');

                if(idCurrentInput != inputSelectedId){

                    $("#"+idCurrentInput).attr('checked', false);

                } else{

                    $("#btn-actions").removeClass('hidden');

                }

            });

        }else{

            $("#btn-actions").addClass('hidden');

        }

    },



    listarNoticia:function()

    {

    	$.post("otros/noticia/listar", {

    		idnoticia:this.idnoticia,

    	},function(data){

    		var data = JSON.parse(data);

    		if(data.message == "success"){

    			noticia.body(data);

    		}else{

    			alerta('Noticia','ocurrio un problema en el servidor','error');

    		}	

    	}).fail(function(){

    		alerta('Noticia','ocurrio un problema en el servidor','error');

    	});	

    },



    loadArchivo:function(obj) {
    	/*
        console.log(obj.value);
		var filePath = obj.value;
		var allowedExtensions = /(*[.jpg|.jpeg|.png|.pdf])$/i;
		
		if(!allowedExtensions.exec(filePath))
		{
			alerta('Noticia','EL archivo que intenta subir no es un formato valido, por favor suba archivo con extensión válida (.jpg, .jpeg, .png, .pdf)','advertencia');
			obj.value = '';
			return false;
		}*/
        var extensionesPermitidas = new Array(".pdf", ".jpg", ".jpeg", ".png");
        var filePath = obj.value;

        var extension = (filePath.substring(filePath.lastIndexOf("."))).toLowerCase();
        var permitida = false;

        for (var i = 0; i < extensionesPermitidas.length; i++)
        {
            if(extensionesPermitidas[i] == extension)
            {
                permitida = true;
                break;
            }
        }

        if (!permitida)
        {
            alerta('Noticia','EL archivo que intenta subir no es un formato valido, por favor suba archivo con extensión válida (.jpg, .jpeg, .png, .pdf)','advertencia');
            obj.value = '';
            return false;
        }

		/*
		var file = $(obj)[0].files[0];
		var name = file.name;
		var extension = name.split(".")[1];

		if(extension!='jpg' && extension!='JPG' && extension!='JPEG' && extension!='PNG' && extension!='png' && extension!='pdf'){
			alerta('Noticia','EL archivo que intenta subir no es un formato valido, por favor suba archivo con extensión .JPG','advertencia');
			$('#id-input-file-3').ace_file_input('reset_input');
		}
		*/

    },



    body:function(data)

    {

    	if(data == ''){

    		$("#titulo").val('');

			$("#contenido").val('');

            $(".noticia-image").hide();

            $(".ace-file-input").show();

    	}else{

    		$("#titulo").val(data.result.not_vc_tit);

            var fecha = data.result.not_da_fec.split("-");

            var day = fecha[2]; //2016-07-11

            var month = fecha[1];

            var year = fecha[0];

			$("#id-date-picker").val(day+"-"+month+"-"+year);

			$("#contenido").val(data.result.not_te_con);

            //input file

            $("#id-input-file-3").parent().find(".ace-file-container").find("span").remove();

            $("#id-input-file-3").parent().find(".ace-file-container").html('<span class="ace-file-name" data-title="No File ..."><i class=" ace-icon ace-icon fa fa-cloud-upload"></i></span>');

            $("#id-input-file-3").val('');

            //fin

            if(data.result.not_vc_img == 'NULL'){

                $(".noticia-image").hide();

            }else{

                var time = new Date();

                $(".ace-file-input").hide();

                $(".remove-file").show();

                $(".noticia-image").show().css("background","#eee url('"+data.result.not_vc_img+"?t="+time.getTime()+"')");

            }

        }	

    },



    add:function()

    {

        var formData = new FormData($(".form-data")[0]);
        // console.log(formData);

        $.ajax({

            url: 'otros/noticia/save',

            type: 'POST',

            data: formData,

            async: false,

            cache: false,

            contentType: false,

            processData: false,

            success: function (data) {

                var data = JSON.parse(data);

                if(data.message == "success"){

                alerta('Noticia',data.cuerpo,'informativo');

                    $("#grid-noticia").trigger("reloadGrid");

                    noticia.ocultarModal();

                }else{

                    alerta('Noticia','ocurrio un problema en el servidor','error');

                }   

            },

            error: function(){

                alerta('Noticia','ocurrio un problema en el servidor','error');

            }

        });



    },



    ocultarModal:function(){

        setTimeout(function(){

            $("#btn-actions").addClass('hidden');

            $("#myModalNoticia").modal('hide');

            $("input:checkbox").prop('checked', false);

        },500);

    },



    delete:function()

    {

        $.post("otros/noticia/delete", {

            idnoticia:this.idnoticia,

            ruta:this.ruta

        }, function(data){

            var data = JSON.parse(data);

            if(data.message == "success"){

                alerta('Noticia',data.cuerpo,'informativo');

                $("#grid-noticia").trigger("reloadGrid");

                setTimeout(function(){

                    $("#btn-actions").addClass('hidden');

                    $("#myModalNoticiaEliminar").modal('hide');

                },500);

            }else{

                alerta('Noticia',data.cuerpo,'advertencia');

            }   

        }).fail(function(){

            alerta('Noticia','ocurrio un problema en el servidor','error');

        });

    },



    update:function()

    {



        var formData = new FormData($(".form-data")[0]);

        $.ajax({

            url: 'otros/noticia/save',

            type: 'POST',

            data: formData,

            async: false,

            cache: false,

            contentType: false,

            processData: false,

            success: function (data) {

                var data = JSON.parse(data);

                if(data.message == "success"){

                    alerta('Noticia',data.cuerpo,'informativo');

                    $("#grid-noticia").trigger("reloadGrid");

                    noticia.ocultarModal();

                }else{

                    alerta('Noticia',data.cuerpo,'advertencia');

                    noticia.ocultarModal();

                }   

            },

            error: function(){

                alerta('Noticia','ocurrio un problema en el servidor','error');

                noticia.ocultarModal();

            }

        });



    },



    edit:function()

    {

        this.modalInformacion('update','Editar Noticia','',this.idnoticia);

        this.listarNoticia();

    },



    new:function()

    {

        this.modalInformacion('add','Agregar nueva Noticia','','');

        $('#id-input-file-3').ace_file_input('reset_input');

    	this.body('');

    },



    view:function()

    {

        this.modalInformacion('','Noticia','true','');

        this.listarNoticia();

    },



    del:function()

    {

        $("#myModalNoticiaEliminar").modal('show');

        $("#myModalNoticiaEliminar").find(".modal-dialog").css("width","300px");

        this.listarNoticia();

    },



    modalInformacion:function(accion,titulo,btnhiden,idnoticia)

    {

        $("#myModalNoticia").modal('show');

        $("#myModalNoticia").find(".modal-dialog").css("width","600px");

        $("#myModalNoticia").find("#accion").val(accion);

        $("#myModalNoticia").find("#idnoticia").val(idnoticia);

        $("#myModalNoticia").find(".modal-title").text(titulo);

        if(btnhiden == 'true'){

            $("#myModalNoticia").find("textarea, input").attr("readonly","");

            $(".btn-save").hide();

        }else{

            $(".btn-save").show();

        }

    }



}



noticia.init();



$(window).on('resize.jqGrid', function () {

    $(noticia.gridId).jqGrid( 'setGridWidth', $(".page-content").width());

})



var parent_column = $(noticia.gridId).closest('[class*="col-"]');



$(document).on('settings.ace.jqGrid' , function(ev, event_name, collapsed) {

    if( event_name === 'sidebar_collapsed' || event_name === 'main_container_fixed' ) {

        setTimeout(function(){

            $(noticia.gridId).jqGrid( 'setGridWidth', parent_column.width() );

        }, 0);

     }

});



$(window).triggerHandler('resize.jqGrid');



$(".remove-file").click(function(){

    $(this).hide();

    $(".noticia-image").hide();

    $(".ace-file-input").show();

});



$('#id-date-picker').datepicker({

  'language': 'es',

  autoclose: true,

  todayHighlight: true,

  yearRange: "2010:2015"

});



$('#id-input-file-3').ace_file_input({

	style:'well',

	btn_choose:'Soltar los archivos o haga clic para elegir.',

	btn_change:null,

	no_icon:'ace-icon fa fa-cloud-upload',

	droppable:true,

	thumbnail:'small',

	preview_error : function(filename, error_code){}

}).on('change', function(e){});



$(".modal-dialog").css("width","800px");



$("#validate").validate({

  rules : {

    titulo : { required : true },

    fecha : { required : true },

    contenido : { required : true }

  },

  messages : {

    titulo : { required : 'Ingrese el Título' },

    fecha : { required : 'Ingrese la Fecha' },

    contenido : { required : 'Ingrese el Contenido' }

  },

  errorPlacement : function(error, element) {

    error.insertAfter(element.parent());

  },

  submitHandler: function(e){

    var accion = $(e).find("#accion").val();

    if(accion == 'update') noticia.update();

    else noticia.add();

  }

});



$(".remove").click(function(e){

    e.preventDefault();

    $(this).parent().find(".ace-file-container").find("span").remove();

    $(this).parent().find(".ace-file-container").html('<span class="ace-file-name" data-title="No File ..."><i class=" ace-icon ace-icon fa fa-cloud-upload"></i></span>');

    $(this).parent().find("input").val('');

});


/**
* edificioOk (https://www.edificiook.com)
* creado por: Jhon Gómez, 11/03/2016.
* ultima modificacion por: Jhon Gómez
* Fecha Modificacion: 08/04/2016.
* Descripcion: Javascript Edificio
*
* @link      https://www.edificiook.com
* @copyright Copyright (c) 2011-2016 KND S.A.C (http://www.knd.pe)
* @license   http://www.edificiook.com/license/comercial Software Comercial
* 
*/

var edificio = {

  grid_selector: "#grid-table_ubigeo",
  pager_selector: "#grid-pager_ubigeo",
  sifile: '<div class="si-file" style="margin-top: 10px;position: relative;height: 50px;"><i class="fa fa-file-pdf-o" style="font-size: 40px;color: #000000;position: relative;top: 0;"></i></div>',

  viewUbigeo:function()
  {
      $("#myModalUbigeo").modal('show');
      edificio.clearInput();
      setTimeout(function(){
          edificio.resizeTableUbigeo();
      },500); 
  },

  resizeTableUbigeo:function()
  {
    $(edificio.grid_selector).jqGrid( 'setGridWidth', $(".content-Ubigeo").width() );
  },

  clearInput:function()
  {
    var valFiltro = $("#txtFiltarUbigeo").val('');
    $(edificio.grid_selector).jqGrid('setGridParam',{
       url:'mantenimiento/edificio/listar-ubigeo',
       postData: {filtro:''},
    }).trigger("reloadGrid");
  },

  getGridUbigeo:function(){
      var edificio = this;
      $(this.grid_selector).jqGrid({
      url:'mantenimiento/edificio/listar-ubigeo',
      postData: {filtro:'null'},
      mtype: 'post',
      datatype: "json",
      scrollerbar:true,
      height:200,
      colNames:['DEPARTAMENTO','PROVINCIA','DISTRITO'],
      colModel:[
        {name:'departamento',index:'departamento', width:100,editable: true},
        {name:'provincia',index:'provincia', width:100,editable: true},
        {name:'distrito',index:'distrito', width:100,editable: true}
      ],
      rowNum:7,
      rowList:[7,20,50],
      pager: this.pager_selector,
      sortname: 'distrito',
      viewrecords: true,
      sortorder: "desc",
      altRows: true,
      gridview :  true,
      multiboxonly: true,
      loadComplete : function(){
                var table = this;
                setTimeout(function(){
                  edificio.updatePagerIcons(table);
                },0);
      },
      ondblClickRow: function (rowid,iRow,iCol){
           edificio.selectRowUbigeo(rowid);
        }
    });

    $(this.grid_selector).jqGrid('bindKeys',{
      "onEnter":function(rowid){
           edificio.selectRowUbigeo(rowid);
      }

    });

    //resize to fit page size
    $(window).on('resize.jqGrid', function () {
        $(this.grid_selector).jqGrid( 'setGridWidth', $(".page-content").width());
    })
    //resize on sidebar collapse/expand
    var parent_column = $(this.grid_selector).closest('[class*="col-"]');
    $(document).on('settings.ace.jqGrid' , function(ev, event_name, collapsed) {
        if( event_name === 'sidebar_collapsed' || event_name === 'main_container_fixed' ) {
            //setTimeout, solo para dar tiempo a los cambios del DOM y luego volver a dibujar el grid.
            setTimeout(function(){
                $(this.grid_selector).jqGrid( 'setGridWidth', parent_column.width() );
            }, 0);
         }
    });
    $(window).triggerHandler('resize.jqGrid');


  },

  updatePagerIcons:function(table) {
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

  jqGridWidth:function()
  {
    setTimeout(function(){
      $(edificio.grid_selector).jqGrid('setGridWidth', $(".table-body").width());
    },100);
  },

  selectRowUbigeo:function(rowid){
      if(rowid!=null){
        var objUbigeo = jQuery('#grid-table_ubigeo').jqGrid('getRowData', rowid);
        var txtUbigeo=objUbigeo.departamento+'/'+objUbigeo.provincia+'/'+objUbigeo.distrito;
        $("#txtDetalleUbigeo").val(txtUbigeo);
        $("#txtDetalleUbigeo").attr("id-ubigeo",rowid);
        $('#myModalUbigeo').modal('toggle');
      }
  },

  detalleNoticia:function(obj)
    {
        var id = $(obj).attr("id");
        $.post("ue/noticias/detalle",{
            idNoticia:id
        },function(data){
          
            /*
            if(data.mensaje == 'success'){
                var imagen='';
                var showImagen='';
                if(data.info.extension == 'pdf'){
                    showImagen = 'inline';
                    imagen = 'no-image.png';   
                }else{
                    showImagen = 'none';
                    imagen = data.info.imagen;  
                } 
                $("#myModalNoticia").find(".modal-dialog").css("width","60%");
                $("#myModalNoticia").modal('show');

                var concepto=data.info.concepto;
                concepto = concepto.replace(/\r?\n/g, "<br>");

                var content = '';
                content+='<div class="modal-header padding-top-10 padding-bottom-10">'
                    +'<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>'
                    +'<h4 class="modal-title" id="exampleModalLabel">'+data.info.titulo+'</h4>'
                +'</div>'
                +'<div class="modal-body">'
                    +'<div style="background: url(file/noticia/'+id+'/'+imagen+') no-repeat center #ddd;height: 400px;background-size: contain" class="margin-bottom-15"></div>'
                    +'<p style="margin: 0;font-size: 12px;font-weight: 100;">Publicado el '+data.info.fecha+'</p>'
                    +'<p style="text-align: justify;">'+ concepto +'</p>'
                    +'<p><a href="file/noticia/'+data.info.imagen+'" dowload="'+data.info.imagen+'" style="display:'+showImagen+'">Descargar documento .PDF</a></p>'
                +'</div>'
                +'<div class="modal-footer">'
                    +'<button type="button" class="btn btn-default btn-sm" data-dismiss="modal"><i class="ace-icon fa fa-times bigger-110"></i> Cerrar</button>'
                +'</div>';
                $("#myModalNoticia .modal-content").html(content);
            }*/
            if(data.noticia.mensaje == 'success'){
                var imagen='';
                var showImagen='';
                if(data.noticia.info.extension == 'pdf'){
                    showImagen = 'inline';
                    imagen = 'no-image.png';   
                }else{
                    showImagen = 'none';
                    imagen = data.noticia.info.imagen;  
                }

                if(data.archivo != '')
                {
                  showImagen = 'inline';
                } else {
                  showImagen = 'none';
                }

                if(data.archivo != undefined)
                {
                  showImagen = 'inline';
                } else {
                  showImagen = 'none';
                }

                $("#myModalNoticia").find(".modal-dialog").css("width","60%");
                $("#myModalNoticia").modal('show');

                var concepto=data.noticia.info.concepto;
                concepto = concepto.replace(/\r?\n/g, "<br>");
                console.log("archivo : "+data.archivo);

                var content = '';
                content+='<div class="modal-header padding-top-10 padding-bottom-10">'
                    +'<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>'
                    +'<h4 class="modal-title" id="exampleModalLabel">'+data.noticia.info.titulo+'</h4>'
                +'</div>'
                +'<div class="modal-body">'
                    +'<div style="background: url(file/noticia/'+id+'/'+imagen+') no-repeat center #ddd;height: 400px;background-size: contain" class="margin-bottom-15"></div>'
                    +'<p style="margin: 0;font-size: 12px;font-weight: 100;">Publicado el '+data.noticia.info.fecha+'</p>'
                    +'<p style="text-align: justify;">'+ concepto +'</p>'
                    +'<p><a href="file/noticia/'+id+'/'+data.archivo+'" dowload="'+data.archivo+'" style="display:'+showImagen+'">Descargar documento .Pdf</a></p>'
                +'</div>'
                +'<div class="modal-footer">'
                    +'<button type="button" class="btn btn-default btn-sm" data-dismiss="modal"><i class="ace-icon fa fa-times bigger-110"></i> Cerrar</button>'
                +'</div>';
                $("#myModalNoticia .modal-content").html(content);
            }
            else{
                alerta('Error','Ocurrio un problema en el servidor.','error');
            }
        },'json')
        .fail(function(){
            alerta('Error','Ocurrio un problema en el servidor.','error');
        });
  },

  cambiarImagenLogo:function(event)
  { 
    var timestamp = Number(new Date());
    var reader = new FileReader();
    reader.onload = function(){
      $(".img-edificio").addClass("hide");
      $("#preview-logo").removeClass("hide").attr("src",reader.result);
    };
    reader.readAsDataURL(event.target.files[0]);
  },

  cambiarImagenBanner:function(obj, event)
  {
      /*if(file.size > 87699){
        alerta('Tamaño excedido','Por favor la imagen que intenta subir es muy pesado...','advertencia');
        edificio.ocultarProcesoImagen();
        return false;
      }*/
      var file=$(obj)[0].files[0];
      var timestamp=Number(new Date());
      var urlImageTemp=URL.createObjectURL(event.target.files[0]);
      var img=new Image();
      img.onload=function(){
        if(this.width>1600 && this.height>430){
          alerta('Edificio','La imagen excedio el tamaño, por favor de recomienda subir imagenes de 1600x430','advertencia');
        }else{
            if(file){
              var fd = new FormData();
              fd.append("imgupdate", file);
              var xhr = new XMLHttpRequest();
              xhr.upload.addEventListener("progress", function(event){
                if (event.lengthComputable) {
                  var percentComplete = Math.round(event.loaded * 100 / event.total);
                  $(".bg-load").fadeIn("slow");;
                  $(".number-load").show().text(percentComplete+"%");
                  $(".content-progress").show();
                  $(".progress-bar").css("width",percentComplete+"%");
                }
              }, false);
              xhr.addEventListener("error", function(){
                alerta('Edificio','La subida de su imagen ha fallado.','advertencia');
              }, false);
              xhr.addEventListener("abort", function(){
                alerta('Edificio','Se cancelara la subida de la imagen de perfil.','advertencia');
              }, false);
              xhr.open("POST","mantenimiento/edificio/upload-imagen-principal");
              xhr.send(fd);
              xhr.onreadystatechange = function (aEvt) {
                if (xhr.readyState == 4) {
                   if(xhr.status == 200){
                      var data = JSON.parse(xhr.responseText);
                      if(data.response == 'deny'){
                        alerta('Edificio','La imagen no es el formato recomendado.','advertencia');
                        edificio.ocultarProcesoImagen();
                      }else if(data.response == 'noformat'){
                        alerta('Edificio','Formato no recomendado, por favor intente subir imagenes con formato .JPG ó .PNG','advertencia');
                        edificio.ocultarProcesoImagen();
                      }else if(data.response == 'error'){
                        alerta('Edificio','Ocurrio un problema al subir la imágen al servidor. Por favor vuelva a subir la imágen.','error');
                        edificio.ocultarProcesoImagen();
                      }else if(data.response == 'tamanoexcedido'){
                        alerta('Edificio','Tamaño excedido, por favor la imagen que intenta subir es muy pesado...','advertencia');
                        edificio.ocultarProcesoImagen();
                      }else if(data.response == 'error_update'){
                        alerta('Edificio','No se pudo actualizar el archivo en el servidor, por favor vuelva a intentarlo.','advertencia');
                        edificio.ocultarProcesoImagen();
                      }else{
                        if(data.response == 'success'){
                          alerta('Edificio','Correcto, la imágen fue subido correctamente al servidor.','informativo');
                          edificio.ocultarProcesoImagen();
                          $(".bg-img").css("background","#eee url(" + data.rutaimagen + "?t="+timestamp+") no-repeat center");
                        }
                      }
                   }else{
                      alerta('Error','Error al subir la imagen, por favor vuelva a intentarlo.','advertencia');
                   }                                  
                }
              };
            }
        }
      }

      img.src = urlImageTemp;

  },

  ocultarProcesoImagen:function()
  {
      $(".bg-load").fadeOut(3000);
      $(".number-load").hide();
      $(".content-progress").hide().fadeOut('slow');
  },

  subirArchivo:function(obj)
  {
      var file = $(obj)[0].files[0];
      var name = file.name;
      var type = name.substring(name.lastIndexOf('.') + 1);
      if(type != 'pdf'){
        $(obj).parent().find("input").val('');
        $(obj).parent().find("span").attr("data-title","Subir")
        $(obj).parent().find("span").find("span").attr("data-title","Subir archivo...");
        alerta('Edificio','Formato no recomendado, por favor intente subir archivos con formato .PDF','advertencia');
      }
  },
  /**********************************************************/
  /********************** GUARDAR DATOS EDIFICIO  ***********/
  /**********************************************************/

  guardarDatos:function()
  {
      var formData = new FormData($(".contact-form")[0]);
      var accion = $('.contact-form').attr("accion");
      var juntaDirectiva =$.trim($("#select-junta-directiva").text());
      var iddistrito = $("#txtDetalleUbigeo").attr("id-ubigeo");

      //parametros adicionales
      formData.append("iddistrito", iddistrito);
      formData.append("fechaActual", this.recuperarFecha());
      formData.append("juntaDirectiva", juntaDirectiva);
      formData.append("delReglamentoInterno", $(".del-pdf-i").attr("state"));
      formData.append("delManualOperaciones", $(".del-pdf-m").attr("state"));
      formData.append("delArchivoextra", $(".del-pdf-a").attr("state"));
      formData.append("accion", accion);

      $.ajax({
          url: 'mantenimiento/edificio/save',
          type: 'POST',
          data: formData,
          async: false,
          cache: false,
          contentType: false,
          processData: false,
          dataType: 'json',
          success: function (data) {
              if(data.tipo == 1){
                alerta('Excelente',data.cuerpo,'informativo');
              }else{
               alerta('advertencia',data.cuerpo,'confirmacion');
              }
              $(".btn-edit").fadeIn(3000);
              edificio.herramientasFormulario('ocultar');
          },
          error: function(){
              alerta('Error','Ocurrio un problema en el servidor. Intentelo nuevamente.','error');
          }
      });
  },

  recuperarFecha:function(){
    fec=new Date;
    dia=fec.getDate();
    if (dia<10) dia='0'+dia;
    mes=fec.getMonth()+1;
    if (mes<10) mes='0'+mes;
    anio=fec.getFullYear();
    fecha=anio+'-'+mes+'-'+dia;
    return fecha;
  },

  herramientasFormulario:function(accion)
  {
    switch (accion) {
      case 'mostrar':
        $("#moperaciones , #rinterno").show();
        $(".m-pdf").removeClass("disabled").css("color","#E75840");
        $(".contact-form input[type=text], textarea").removeAttr("readonly");
        $(".contact-form select, input[type=checkbox], input[type=radio], #btn_agregar, #btn_quitar").removeAttr("disabled");
        $("#idambcomun, #selSupervidor, #selAdministrador, #selConserje, #junta-directiva, #cargo").prop('disabled', false).trigger("chosen:updated");
        $(".btn-img, .btn-search, input[type=file]").removeAttr("disabled");
        $(".btn-datepicker").removeAttr("disabled").attr("id","id-date-picker-1");
        $('#id-date-picker-1').datepicker({
          'language': 'es',
          autoclose: true,
          todayHighlight: true,
          yearRange: "2010:2015"
        });

        if($("#cobro-consumo").is(":checked") == true) $("#tipo-calculo").removeAttr("disabled");
        else $("#tipo-calculo").attr("disabled","");
        if($("#aplicar-mora").is(":checked") == true) $("#costo-mora").removeAttr("disabled");
        else $("#costo-mora").attr("disabled","");

        $(".btn-save").show();
      break;
      
      default:
        $(".m-pdf").addClass("disabled").css("color","#c8c8c8");
        $(".contact-form input[type=text], textarea").attr("readonly","");
        $(".contact-form select, input[type=checkbox], input[type=radio], #btn_agregar, #btn_quitar").attr("disabled","");
        $(".btn-search, .btn-img, .btn-datepicker, input[type=file]").attr("disabled","");
        $("#idambcomun, #selSupervidor, #selAdministrador, #selConserje, #junta-directiva, #cargo").prop('disabled', true).trigger("chosen:updated");
        $(".btn-save").hide();
      break;
    }

  },

  /**********************************************************/
  /********************** GOOGLE MAPS  **********************/
  /**********************************************************/
  initMap:function(modal){
    if(modal == 2) idMaps = 'googleMaps2';
    else idMaps = 'googleMaps1';
    var map = new google.maps.Map(document.getElementById(idMaps), {
      zoom: 12,
      center: {lat: -34.397, lng: 150.644}
    });
    var geocoder = new google.maps.Geocoder();
    this.geocodeAddress(geocoder, map);
  },
  geocodeAddress:function(geocoder, resultsMap) {
    var address = document.getElementById('direccion').value + ' - ' + document.getElementById('urbanizacion').value;
    geocoder.geocode({'address': address}, function(results, status) {
      if (status === google.maps.GeocoderStatus.OK) {
        resultsMap.setCenter(results[0].geometry.location);
        var marker = new google.maps.Marker({
          map: resultsMap,
          position: results[0].geometry.location
        });
      } else {
        $("#msg-maps").html('<i class="fa fa-mail-forward"></i> No podemos encontrar la dirección en Google Maps, por favor sea mas especifico.');
      }
    });
  },
  /**********************************************************/
  /********************** FIN - GOOGLE MAPS  ****************/
  /**********************************************************/
  checked:function(id){
    var chk = $("input[id='"+id+"']:checked").length;
    if(chk == 0){
        return '0';
    }else{
        return '1';
    }
  },

  loadInputFile:function()
  {
    $('.id-input-file-2').ace_file_input({
      no_file:'Subir archivo...',
      btn_choose:'Subir',
      btn_change:'Cambiar',
      droppable:false,
      onchange:null,
      thumbnail:false, //| true | large
      //whitelist:'gif|png|jpg|jpeg'
      //blacklist:'exe|php'
      //onchange:''
      //
    });
  }

}

//init
edificio.getGridUbigeo();


/******************************************************/
/****************** ELIMINAR ARCHIVO ******************/
/******************************************************/
setTimeout(function(){
  $(".ace-file-input").delegate(".delete-archivo", "click" , function(e){
  e.preventDefault();
  alert('click');
  $(this).css("display","none");
  $(this).parent().find(".ace-file-container").attr("data-title","Subir");
  $(this).parent().find(".ace-file-name").attr("data-title","Subir archivo...");
  $(this).parent().parent().find(".si-file").remove();
  var nombre = $(this).attr("data-name");
  var folder = $(this).attr("data-folder");
  edificio.eliminarArchivo(nombre,folder);
  return false;
});
},500);

$(".del-pdf-i, .del-pdf-m").click(function(){
  $(this).attr("state","0");
  $(this).parent().parent().hide();
});
/******************************************************/
/****************** FIN - ELIMINAR ARCHIVO ******************/
/******************************************************/

$(document).on("keyup","#txtFiltarUbigeo", function(){
  var valFiltro = $(this).val();
  if(valFiltro!='') {
    $(edificio.grid_selector).jqGrid('setGridParam',{
       url:'mantenimiento/edificio/listar-ubigeo',
       postData: {filtro:valFiltro},
    }).trigger("reloadGrid");
  }else{
    $(edificio.grid_selector).jqGrid('setGridParam',{
       url:'mantenimiento/edificio/listar-ubigeo',
       postData: {filtro:'null'},
    }).trigger("reloadGrid");
  } 
});

$(document).ready(function(){
  $(".m-pdf").css("color","#c8c8c8");
  $("#idambcomun, #selSupervidor, #selAdministrador, #selConserje, #junta-directiva, #cargo").prop('disabled', true).trigger("chosen:updated");
  edificio.loadInputFile();
});

$(".m-pdf").click(function(){
  $(this).parent().parent().parent().find("label").find("input").val('');
  $(this).parent().parent().parent().find("label").find("span").attr("data-title","Subir")
  $(this).parent().parent().parent().find("label").find("span").find("span").attr("data-title","Subir archivo...");
  var archivo = $(this).parent().parent().parent().attr("content");
  if(archivo == 'rinterno'){
    $(this).parent().parent().parent().append('<input type="file" class="id-input-file-2" accept="application/pdf" data-type="interno" id="reglamento-interno" onchange="edificio.subirArchivo(this);" name="reglamentoInterno">');
  }else{
    $(this).parent().parent().parent().append('<input type="file" class="id-input-file-2" accept="application/pdf" data-type="operaciones" id="manual-operaciones" onchange="edificio.subirArchivo(this);" name="manualOperaciones">');
  }
  edificio.loadInputFile();
});

$(".btn-edit").click(function(){
  $(this).addClass("hide");
  $(".btn-return").addClass("hide");
  $(".btn-cancel").removeClass("hide");
  $(".btn-save").removeClass("hide");
  edificio.herramientasFormulario('mostrar');
});

$(".btn-cancel").click(function(){
  $(this).addClass("hide");
  $(".btn-edit").removeClass("hide");
  $(".btn-return").removeClass("hide");
  $(".btn-save").addClass("hide");
  $(".img-edificio").removeClass("hide");
  $("#preview-logo").addClass("hide");
  edificio.herramientasFormulario('ocultar');
});

$(".btn-save").click(function(){
  $(this).addClass("hide");
  $(".btn-edit").removeClass("hide");
  $(".btn-return").removeClass("hide");
  $(".btn-cancel").addClass("hide");
  $(".btn-submit").click();
});

$(".modal-dialog").css("width","50%");

$(".btn-img").click(function(e){
  e.preventDefault();
  $("#logoedificio").click();
});

$(".img-principal").click(function(){
  $("#imgprincipal").click();
});

$('#idambcomun').addClass('tag-input-style');

$("#view-maps-big").click(function(){
  $("#myModalGoogleMaps").modal('show');
  setTimeout(function(){
    edificio.initMap(2);
  },500);
});

$(".img-interno").click(function(){
  $("#reglamento-interno").click();
});
$(".img-operaciones").click(function(){
  $("#manual-operaciones").click();
});
/**********************************************************/
/******************** VALIDAR FORMULARIO ******************/
/**********************************************************/

var $Validate = $("#validate").validate({
  rules : {
    textNombre : { required : true },
    selIdTipo : { required : true },
    textFechaCons : { required : true },
    textNumero : { required : true },
    textAreaUtil : { required : true },
    textAreaOcupada : { required : true },
    textNumeroPisos : { required : true },
    textNumeroSotanos : { required : true },
    textAreaPropiedadExclusiva : { required : true },
    textDireccion : { required : true },
    textUrbanizacion : { required : true },
    textNumeroUnidades : { required : true },
    textAreaTotal : { required : true },
    textAreaTerreno : { required : true },
    textServiciosComunes : { required : true }
  },
  messages : {
    textNombre : { required : 'Por favor, ingrese su nombre' },
    selIdTipo : { required : 'Por favor, seleccione el tipo' },
    textFechaCons : { required : 'Por favor, seleccione la fecha' },
    textNumero : { required : 'Por favor, ingrese el número' },
    textAreaUtil : { required : 'Por favor, ingrese el área util' },
    textAreaOcupada : { required : 'Por favor, ingrese el área ocupada' },
    textNumeroPisos : { required : 'Por favor, ingrese el número de piso' },
    textNumeroSotanos : { required : 'Por favor, ingrese el número de sotano' },
    textAreaPropiedadExclusiva : { required : 'Por favor, ingrese el área propiedad exclusiva' },
    textDireccion : { required : 'Por favor, ingrese la direccón' },
    textUrbanizacion : { required : 'Por favor, ingrese la urbanización' },
    textNumeroUnidades : { required : 'Por favor, ingrese el número de unidades' },
    textAreaTotal : { required : 'Por favor, ingrese el área total' },
    textAreaTerreno : { required : 'Por favor, ingrese el área de terreno' },
    textServiciosComunes : { required : 'Por favor, ingrese los servicios comunes' }
  },
  errorPlacement : function(error, element) {
    error.insertAfter(element.parent());
  },
  submitHandler: function(){
    edificio.guardarDatos();
  }
});

$("#btn_agregar").click(function(){
    var opcion = $("#junta-directiva").val();
    var opcion2 = '';
    var opcion3='';
    var opcion4='';
    opcion2 = $("#cargo").val();
    opcion4 = $("#cargo").find("option[value='"+opcion2+"']").text();
    opcion3 = $("#junta-directiva").find("option[value='"+opcion+"']").text();
    opcion = $.trim(opcion);
    opcion2 = $.trim(opcion2);
    var cbo_texto = $("#select-junta-directiva").text();
    if(opcion == '0'){
        alerta('Por favor','Debe seleccionar un usuario.','advertencia');
    }else{
        if(opcion!='' && opcion2!=''){
            var val_cbo = cbo_texto.indexOf(opcion4);
            if(val_cbo == -1){
                $("#select-junta-directiva").find("option[value='"+opcion+"_"+opcion2+"']").remove();
                var cadena = "<option value='"+opcion+"_"+opcion2+"'>#"+opcion3+"   ->   "+opcion4+"</option>";
                $("#select-junta-directiva").append(cadena);
            }else{
                alerta('Advertencia','Este cargo de junta directiva ya fue agregado.','advertencia');
            }
        }
    }
});

$("#aplicar-mora").change(function(){
    var apliMora = edificio.checked('aplicar-mora');
   if(apliMora == 1){
      $("#costo-mora").removeAttr("disabled");
   }else{
      $("#costo-mora").attr("disabled","disabled");
   }
});

$("#cobro-consumo").change(function(){
   var tipCalCon = edificio.checked('cobro-consumo');
   if(tipCalCon == 1){
      $("#tipo-calculo").removeAttr("disabled");
   }else{
      $("#tipo-calculo").attr("disabled","disabled");
   }
});

$("#btn_quitar").click(function(){
    var cod = $("#select-junta-directiva").val();
    $("#select-junta-directiva").find("option[value='"+cod+"']").remove();
});

$(".moneda").keypress(function(e){
  if(e.which == 0) return true;
  if(e.which == 8) return true;
  if(e.which == 45) return false;                
  if(e.which < 46) return false;
  if(e.which > 46 && e.which<48) return false;
  if(e.which > 57 ) return false;
});

$(".numero").keypress(function(e){
    if(e.which == 0) return true;
    if(e.which == 8) return true;
    if(e.which < 46) return false;
    if(e.which<48 || e.which > 57 ) return false;
});


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

var noticias = {

    page:0,
    pageStop:0,

    cargaProgresivo:function()
    {   
        $.ajax({
            url: 'ue/noticias/load',
            type: 'post',
            dataType: 'json',
            data: {
                page: this.page
            },
            beforeSend: function(){
                $(".process .fa").css("display","inline");
            }
        })
        .done(function(data){
            if(noticias.pageStop == 0) noticias.page +=20;
            if(data.row.length !=1){
                var content = '';
                $.each(data.row,function(index,value){
                    var extension = value['imagen'].split(".")[1];
                    if(extension == 'pdf') imagen = 'noimagen.jpg';
                    else imagen = value['imagen']
                    content+='<div class="col-lg-3 col-xs-6 col-sm-4 col-md-4 margin-bottom-15">'
                            +'<div class="widget-article">'
                                +'<div class="widget-header margin-bottom-10">'
                                    +'<img class="cover-image" src="file/noticia/'+value['id']+'/'+value['imagen']+'" alt="'+value['titulo']+'" style="width:100%;">'
                                +'</div>'
                                +'<div class="widget-body margin-top-5">'
                                    +'<h3 class="widget-title center margin-top-0 padding-top-5">'+value['titulo']+'</h3>'
                                    +'<p class="text-justify">'+value['concepto'].substring(0,100)+'... <span id="'+value['id']+'" onclick="noticias.detalle(this)">Ver más</span></p>'
                                    +'<div class="widget-body-footer center">'
                                    +'<p>'+value['fecha']+'</p>'
                                    +'</div>'
                                +'</div>'
                            +'</div>'
                    +'</div>';
                });
                noticias.pageStop++;
                $(".content-noticia").append(content);
                noticias.reloadMasonry();
            }else{
                $(".process .fa").remove();
                $("#result").removeClass('hide');
            }    
        })
        .fail(function(){
            alerta('Error','Ocurrio un problema en el servidor.','error');
        })
        .always(function(){
            $(".process .fa").hide();
        });
    },

    reloadMasonry:function()
    {
        $(".grid").masonry('reloadItems');
        $(".grid").masonry('layout');
    },

    detalle:function(obj)
    {
        var id = $(obj).attr("id");
        $.post("ue/noticias/detalle",{
            idNoticia:id
        },function(data){
            if(data.noticia.mensaje == 'success'){
                var imagen='';
                var showImagen='';
                if(data.noticia.info.extension == 'pdf'){
                    showImagen = 'inline';
                    imagen = 'noimage.jpg';   
                }else{
                    showImagen = 'none';
                    imagen = data.noticia.info.imagen;  
                }

                if(data.archivo != '')
                {
                  showImagen = 'inline';
                }

                $("#myModalNoticia").find(".modal-dialog").css("width","60%");
                $("#myModalNoticia").modal('show');
                
                var content = '';
                content+='<div class="modal-header padding-top-10 padding-bottom-10">'
                    +'<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>'
                    +'<h4 class="modal-title" id="exampleModalLabel">'+data.noticia.info.titulo+'</h4>'
                +'</div>'
                +'<div class="modal-body">'
                    +'<div style="background: url(file/noticia/'+id+'/'+imagen+') no-repeat center #ddd;height: 400px;background-size: contain" class="margin-bottom-15"></div>'
                    +'<p style="margin: 0;font-size: 12px;font-weight: 100;">Publicado el '+data.noticia.info.fecha+'</p>'
                    +'<p style="text-align: justify;">'+data.noticia.info.concepto+'</p>'
                    +'<p><a href="file/noticia/'+id+'/'+data.archivo+'" dowload="'+data.archivo+'" style="display:'+showImagen+'">Descargar documento .pdf</a></p>'
                +'</div>'
                +'<div class="modal-footer">'
                    +'<button type="button" class="btn btn-default btn-sm" data-dismiss="modal"><i class="ace-icon fa fa-times bigger-110"></i> Cerrar</button>'
                +'</div>';
                $("#myModalNoticia .modal-content").html(content);
            }else{
                alerta('Error','Ocurrio un problema en el servidor.','error');
            }
        },'json')
        .fail(function(){
            alerta('Error','Ocurrio un problema en el servidor.','error');
        });
    }

}

noticias.cargaProgresivo();

setTimeout(function(){
    noticias.reloadMasonry();
},1500);

$('.grid').masonry({
  itemSelector: '.col-lg-3',
});

$(window).scroll(function(){
    if  ($(window).scrollTop() == $(document).height() - $(window).height()){
       noticias.reloadMasonry();
       noticias.cargaProgresivo();
       noticias.page +=20;
    }
});



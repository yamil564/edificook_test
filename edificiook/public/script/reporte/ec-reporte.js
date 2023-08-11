var recibo = {

  month:null,
  year:null,
  type:null,
  idrow:null,
  filterx:null,
  admin:null,

  crearPdf:function()
    {
      $(".modal-dialog").css("width","300px").css("margin","0 auto").css("margin-top","25%");
      $(".modal-dialog").css("width","300px");
      $("#myModalReciboProgress").modal('show');
      $.ajax({ 
        url: 'reporte/estado-cuenta/crear-pdf',
        data: {
          'month':this.month,
          'year':this.year,
          'type':this.type,
          'idrow':this.idrow,
          'filterx':this.filterx,
          'admin':this.admin
        },
        dataType: "json",
        success: function(data){
          if(data.message = 'success'){
            $("#myModalReciboProgress").modal('hide');
            setTimeout(function(){
              window.open(data.ruta,'_blank');
            },1000);
          }
        },
        error: function(){
          console.log('error');
        },
        type: 'POST'
      });
    },

    cambiarFormatoRecibo:function(obj)
    {
      formato = $(obj).attr("formato");
      this.admin = formato;
    }

}

$(document).ready(function(){
  $('[data-toggle="tooltip"]').tooltip()

  $("body").delegate(".format-white","click",function(){
    $(this).attr("data-original-title","Ver formato Impreso");
    $(".tooltip").find(".tooltip-inner").text("Ver formato Impreso");
    $(this).attr("formato","1");
    $(this).removeClass("format-white");
    $(this).addClass("format-return");
    var bg = $("table").attr("idcolor"); 
    $(".view").css("background","#FFF");
    $(this).css("background-color",bg);
  });

  $("body").delegate(".format-return","click",function(){
    $(this).attr("data-original-title","Ver formato Pre Impreso");
    $(".tooltip").find(".tooltip-inner").text("Ver formato Pre Impreso");
    $(this).attr("formato","0");
    $(this).removeClass("format-return");
    $(this).addClass("format-white");
    var bg = $("table").attr("idcolor"); 
    $(".view").css("background",bg);
    $(this).css("background-color","#FFF");
  });

  $(".generate").hover(function() {
    $(".tooltip-arrow").removeAttr("style");
  });

});
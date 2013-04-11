function get_ajax(co, kto) {
	if($("#bmem").is(":checked")){
		$("#mem").hide("slow");
		$("#bmem").attr('checked',false);
		$('#bmem').button("refresh");
	}
	//alert(co+" "+kto);
	var timestmp = new Date().getTime();
	var tmp = "a"+timestmp;
	var tmpe = "e"+timestmp;
	switch(co){
		case 'rozwin':
			var myKeyVals = { "rozwin" : kto, "AJAX" : 0, "time" : timestmp};
			break;
		case 'burst':
			var myKeyVals = { "rozwin" : kto, "AJAX" : 0, "time" : timestmp};
			break;
		case 'open':
			var myKeyVals = { "open" : kto, "AJAX" : 0, "time" : timestmp};
			break;
		case 'close':
			var myKeyVals = { "close" : kto, "AJAX" : 0, "time" : timestmp};
			break;
		case 'cut':
			var myKeyVals = { "cut" : kto, "AJAX" : 0, "time" : timestmp};
			break;
		case 'reset':
			var myKeyVals = { "reset" : kto, "AJAX" : 0, "time" : timestmp};
			break;
		case 'mind':
			var myKeyVals = { "mind" : kto, "AJAX" : 0, "time" : timestmp};
			break;
		case 'get':
			var myKeyVals = { "get" : kto, "AJAX" : 0, "time" : timestmp};
			break;
		case 'rep':
			var myKeyVals = { "rep" : kto, "AJAX" : 0, "time" : timestmp};
			break;
		case 'info':
			var myKeyVals = { "info" : kto, "AJAX" : 0, "time" : timestmp};
			break;
		default:
			break;
	}

			if(co == "burst" || co == "rozwin" || co == "open" || co == "close") {
				$('#li-'+kto).fadeTo('slow', 0.3);
			} else if(co == 'mind') {
				$('#li-'+kto+' > div').fadeTo('slow', 0.3, function(){$('#li-'+kto+' > div').fadeTo('slow', 1);});
			}

			$('#task').append("<div id='"+tmp+"' style='display: none'>"+kto+": "+co+"</div>");
			$('#'+tmp).show({duration: "slow", queue: "false"});
			$('#err').prepend("<div id='"+tmpe+"'>Doing: "+co+": "+kto+"</div>");

	$.ajax({
		url: 'index.php',
		type: 'POST',
		data: myKeyVals,
		dataType: 'json',
		cache: false,

		success: function(data) {
			console.log(data);
			var rtmp = "a"+data.time;
			var rtmpe = "e"+data.time;
			if(("target" in data) && (co == 'mind')){
				$('#'+data.target+' > div.text > .icon-mind').toggleClass('icon-ui');
				$('#'+data.target+' > div.text > .icon-mind').toggleClass('icon-ui2');
			}
			if("episode" in data){
				if(data.episode == "yes"){
					$('#'+data.target).addClass('epi');
				} else {
					$('#'+data.target).removeClass('epi');
				}
			}
			if(("target" in data) && ("data" in data)) {
				$('#'+data.target).html(data.data);
			}
			if("err" in data) {
				$('#err').prepend("<div>"+data.err+"</div>");
				if(data.err != '') {
					console.log('test');
					$("#menur > label[for='berr']").addClass('ui-state-error');
				}
			}
			if("link" in data) {
				$('#page').attr('src', data.link);
			}
			if(("delay" in data) && ("target" in data)) {
				if(data.delay == 'yes')
					$('#'+data.target).delay(200 - (new Date().getTime() - data.time)).fadeTo('slow', 1);
				else if (data.delay == 'no')
					$('#li-'+data.target+' > div').fadeTo('slow', 0.3, function(){$('#li-'+data.target+' > div').fadeTo('slow', 1);});
			}
			$('#'+rtmp).delay(1500 - (new Date().getTime() - data.time)).hide({duration: "slow", queue: "false", complete: function(){ $('#'+rtmp).remove();}});
			$('#'+rtmpe).append(" ...Done ("+((new Date().getTime() - data.time)/1000)+"s).");
		}
	});
}

function log(co){
	$('#err').prepend("<div>Reading: "+co+"</div>");
}

function get_ajax_mem(){

	var timestmp = new Date().getTime();
	var tmp = "a"+timestmp;
	var tmpe = "e"+timestmp;
	var myKeyVals = { "memory" : 0, "AJAX" : 0, "time" : timestmp};
	$.ajax({
		url: 'index.php',
		type: 'POST',
		data: myKeyVals,
		dataType: 'json',
		cache: false,
		beforeSend: function() {
			$('#task').append("<div id='"+tmp+"' style='display: none'>Remembering...</div>");
			$('#err').prepend("<div id='"+tmpe+"'>Remembering </div>");
			$('#'+tmp).show({duration: "slow", queue: "false"});
		},
		error: function() {
			$('#err').html("Error Ajaxa.");
			$('#'+tmp).hide({duration: "slow", queue: "true", complete: function(){ $('#'+tmp).remove();}});
			$('#'+tmpe).append(" ...Done ("+((new Date().getTime() - timestmp)/1000)+"s).");
			$("#mem").toggle("slow");
		},
		success: function(data) {
			console.log(data);
			rtmp = "a"+data.time;
			rtmpe = "e"+data.time;
			if("data" in data) {
				$('#mem').html(data.data);
			}
			if("err" in data) {
				$('#err').prepend("<div>"+data.err+"</div>");
			}
			$('#'+rtmp).delay(1500 - (new Date().getTime() - data.time)).hide({duration: "slow", queue: "false", complete: function(){ $('#'+rtmp).remove();}});
			$('#'+rtmpe).append(" ...Done ("+((new Date().getTime() - data.time)/1000)+"s).");
			$("#mem").toggle("slow");
		}
	});
}

$(function(){
	$("ul#tree").on({
		mouseover: function(event) {
				// Handle mouseenter...
				$('.active').removeClass('active');
				$(this).addClass('active');
				event.stopPropagation();
				return false;
		},
		mouseleave: function(event) {
				// Handle mouseleave...
				$(this).removeClass('active');
				event.stopPropagation();
				return false;
		}
	}, "li");

	$("#reset-dialog").dialog({
		autoOpen: false,
		show: {
			effect: "blind",
			duration: 500
		},
		hide: {
			effect: "blind",
			duration: 500
		},
		resizable: false,
		modal: true,
		buttons: {
			"Reset Database?": function() {
				$(this).dialog("close");
				get_ajax("reset","0");
			},
			Cancel: function() {
				$(this).dialog("close");
			}
		}
  });
	$("#menul").buttonset();

	$("#ball").button({
      icons: {
        primary: "ui-icon-home"
      },
      text: false
    }).click(function() {get_ajax("cut","0");});

	$("#bres").button({
      icons: {
        primary: "ui-icon-trash"
      },
      text: false
    }).click(function() {$("#reset-dialog").dialog("open");});

	$("#bref").button({
      icons: {
        primary: "ui-icon-refresh"
      },
      text: false
    }).click(function() {get_ajax("cut","A");});
	$("#bbck").button({
      icons: {
        primary: "ui-icon-seek-prev"
      },
      text: false
    }).click(function() {get_ajax("cut","B");});
	$("#bmem").button({
      icons: {
        primary: "ui-icon-star"
      }
    }).click(function() {
		if($("#bmem").is(':checked')) {get_ajax_mem();}
		else $("#mem").toggle("slow");
	});

	$("#menur").buttonset();

	$("#btas").button({
      icons: {
        primary: "ui-icon-script"
      }
    }).click(function() {$("#task").toggle("slow");});

	$("#berr").button({
			icons: {
        primary: "ui-icon-alert"
      }
    }).click(function() {$("#err").toggle("slow"); $("#menur > label[for='berr']").removeClass('ui-state-error');});

	$("#berrclr").button({
			icons: {
        primary: "ui-icon-print"
      },
			text: false
    }).click(function() {$("#err").html("")});

	$("#bdat").button({
      icons: {
        primary: "ui-icon-bookmark"
      }
    }).click(function() {$("#dbs").toggle("slow");});

	$("#menudb").buttonset();

	$("#dbex").button({
      icons: {
        primary: "ui-icon-print",
				secondary: "ui-icon-bookmark"
      }
	});

	$("#dbim").button({
      icons: {
        primary: "ui-icon-disk",
				secondary: "ui-icon-bookmark"
      }
	});
});



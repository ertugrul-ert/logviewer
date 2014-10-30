$(function(){ 
  
	$("#del_visible").click(function(){

		var filename = $("#active-file-name").text();
		var dates = [];

		for (var i = 1; i < 100; i++) 
		{ 
			if ( $('#date'+i).text().length == 0 ) { break; };
			dates.push( $( '#date'+i).text() ) ;
		};

		$.ajax({
			url:"/ctrl/test/delete_log_line/",
			type: "POST",
			data: { dates: dates , filename: filename }
		});

		window.location.reload();
	});

	//  			
	$( "button[id*='btdel']" ).click(function(event){

		var filename = $("#active-file-name").text();
		var dates = [];
		var dateid = $( '#'+event.target.id ).val();
		dates.push( $( '#'+dateid ).text()  ) ;

		$.ajax({
			url:"/ctrl/test/delete_log_line/",
			type: "POST",
			data: { dates: dates , filename: filename }
		});

		window.location.reload();
	});

}); 


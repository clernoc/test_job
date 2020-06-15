$(document).ready( function(){
	//var p = BX.message('TEMPLATE_PATH');
	//jQuery.ajax({
	//	url: p,
	//	beforeSend: function(){
	//	},
	//	success: function( data ){
	//	},
	//	error: function(){},
	//	data: { },
	//	//dataType : "json",
	//	type: 'post'
	//});
	var d = BX.date;
	
	setInterval( function(){
		
		var date = new Date();
		var diff = date.getTimezoneOffset() * 60;
		
		var i = date.valueOf()/1000 + diff + parseInt($('.zone-select').val());
		
		$('.timer').html( d.format( $('.format-select').val(), i ) );
		
	}, 1000 );
	
});

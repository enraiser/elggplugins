$(function(){
	$('input.elgg-input-text[name="name"]').keyup(function(){
		$('input.elgg-input-text[name="alias"]').val(function(title){
			return title.replace(/[^\w ]/g, "")
							.replace(/^\s+/g,'').replace(/\s+$/g,'') //trim
							.replace(/ /g, "_")
							.replace(/__/g, "_")
							.toLowerCase();
		}($(this).val()));
	});
});

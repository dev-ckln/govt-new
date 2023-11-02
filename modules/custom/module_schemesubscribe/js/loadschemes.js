jQuery( document ).ready(function() {
    if(jQuery('#edit-state-scheme-types').length){
     	setShemeTypes();
	}
   jQuery('#edit-state-schemes').on('change', function() {
	   setShemeTypes();
   });
}); 


function setShemeTypes(){
   var state_id =  jQuery('#edit-state-schemes').val();
   var langcode = jQuery("[name='langcode']").val();
   
   jQuery('#edit-state-scheme-types').empty().append('<option selected="selected" value="">Scheme types are loading...please wait...</option>');
   jQuery("#edit-next").attr("disabled",true);
   
   var identifier = 0;
   
   var url = '/subscription/schemelisting/' + state_id;
   if(langcode!='en')
   url = '/' + langcode + '/subscription/schemelisting/' + state_id;
   
   jQuery.ajax({url: url, success: function(result){
	   jQuery('#edit-state-scheme-types').empty();
	   
	  
	    jQuery.each(result, function() {
			identifier = this.node_id;
			var selected = '';
			if(this.selected==1)selected = 'selected';
  			jQuery('#edit-state-scheme-types').append('<option value=' + this.node_id + '    '+selected+'>' + this.title + '</option>');
		});
	   
		 if(identifier!=0)
		 jQuery("#edit-next").attr("disabled",false);
  }});
}
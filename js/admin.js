jQuery(function() {
	// set unique class for fields table
	jQuery( ".form-table:first" ).addClass( "exif_fields" );
	// set IDs on TRs for sorting
	jQuery( ".exif_fields td input" ).each(function() {
			var field = jQuery(this).val();
			jQuery( this ).parents( "tr" ).attr( "id", field );
		}
	)
	// initiate sorting
	jQuery( ".exif_fields tbody" ).sortable();

	// save sortable order to DATABASE
	jQuery( ".exifography" ).submit(function() {
  	var order = jQuery( ".exif_fields tbody" ).sortable( "toArray" );
		var input = jQuery("<input>").attr("type", "hidden").attr("name", "exifography_options[order]").val( order );
		jQuery( this ).append( input );
	});
});

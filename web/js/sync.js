
$(function(){

	$('.uploadable').change(updateModifiedLogs);

});

function toggleAllCheckBox (elem) {

		$('tbody input', '#'+ $(elem).attr('data-table') ).prop('checked', $(elem).is(':checked') );
		if( $(elem).attr('data-table') == 'modified-list' ){
			updateModifiedLogs();
		}

}

function uploadSelected () {


		if( $('.uploadable:checked').length == 0 )	{

			alert('No selected file to upload');

		}else{

			if ( !confirm('Are you sure to upload selected file?') ) {
				return;
			};

			$('#upload-progressbar').show()
			$('#upload-progress').css('width', '0%')

			var totalFilesSelected = $('.uploadable:checked').length;
			var totalFilesUploaded = 0;

			function _up () {

				if( $('.uploadable:checked').length > 0 ){

					var file = $('.uploadable:checked:eq(0)').val();

					$.ajax({
						url:'_upload.php',
						type:'post',
						data:'file='+file,
						dataType:'json',
						success:function(json){
							if( json.success ){

								totalFilesUploaded++;
								$('.uploadable:checked:eq(0)').parents('tr').remove();

								var progress = parseInt(totalFilesUploaded/totalFilesSelected * 100 );
								$('#upload-progress').css('width', progress +'%')

								if( progress < 100 ){
									_up();
								}else{
									updateModifiedLogs();
									alert('Upload done');
									$('#upload-progressbar').hide()
								}

							}
						}
					});

				}
			}

			_up ();
			
		}



}

function addSelected () {


		if( $('.addable:checked').length == 0 )	{

			alert('No selected file to add');

		}else{

			function _add () {

				if( $('.addable:checked').length > 0 ){

					var file = $('.addable:checked:eq(0)').val();

					$.ajax({
						url:'_add.php',
						type:'post',
						data:'file='+file,
						dataType:'json',
						success:function(json){
							if( json.success ){

								$('.addable:checked:eq(0)').parents('tr').remove();
								addToModifiedList(file)
								_add();

							}
						}
					});

				}
			}

			_add ();
			
		}


}


function compareFile (elem) {

	var _data = $(elem).parents('tr');

	$.ajax({
		url:'_download.php',
		type:'post',
		data:'file='+ $('input',_data).val(),
		dataType:'json',
		success:function(json){

			if( json.success ){

				window.open("/compare.php?file="+ $('input',_data).val() );

			}

		}
	})
	return false;
	
}

function uploadFile (elem) {

	var _data = $(elem).parents('tr');

	$.ajax({
		url:'_upload.php',
		type:'post',
		data:'file='+$('input',_data).val(),
		dataType:'json',
		success:function(json){
			if( json.success ){

				$(elem).parents('tr').remove();
			}

		}
	})
	return false;

	
}

function addFile (elem) {

	var _data = $(elem).parents('tr');
	var _file = $('input',_data).val();
	$.ajax({
		url:'_add.php',
		type:'post',
		data:'file='+_file,
		dataType:'json',
		success:function(json){
			if( json.success ){

				$(elem).parents('tr').remove();
				addToModifiedList(_file);

			}
		}
	})


	return false;

}

function addToModifiedList(_file) {

	var _html = '<tr>';
	_html += '<td><input type="checkbox"  class="uploadable" name="files[]"  value="'+ _file +'" /></td>';
	_html += '<td>'+ _file +'</td>';
	_html += '<td style="text-align:center">A</td>';
	_html += '<td style="text-align:center"><a href="#" onclick="return uploadFile(this)">Upload</a></td>';
	_html += '</tr>';

	$(_html).appendTo('#modified-list tbody');

}

function backupRemoteFileLocallyModified() {

	if( $('#modified-list tbody tr').length == 0 ){
		alert('Nothing to backup');
		return;
	}

	$('#backup-progressbar').show();

	function _bkp (id) {
		
		$.ajax({
			url:'_backupfile.php?id='+id,
			dataType:'json',
			success:function(json){
				if( json.success ){

					$('#backup-progress').css('width', json.progress +'%' )

					if(  json.progress < 100 ){
						_bkp(json.id);
					}else{
						alert('Backup done');
						$('#backup-progressbar').hide();
					}

				}
			}
		});

	}

	_bkp (0);

	return false;

}

function updateModifiedLogs () {
	
	$("#upload-total").html( $('.uploadable:checked').length )
	$("#total-uploadable").html( $('.uploadable').length )

}

function fastUploadAll () {

	if( $('.uploadable').length == 0 )	{

			alert('No file to upload');

		}else{

			if ( !confirm('Are you sure to upload all modified files?') ) {
				return;
			};

			$('#upload-progressbar').show()
			$('#upload-progress').css('width', '0%')


			function _upload (id) {
				
				$.ajax({
					url:'_fastupload.php?id='+id,
					dataType:'json',
					success:function(json){
						if( json.success ){

							$('#upload-progress').css('width', json.progress +'%' )

							$(json.files).each(function(i, e){
								$('#m'+e).remove();
							})								


							if(  json.progress < 100 ){

								_upload(json.id);

							}else{
								alert('Upload done');
								$('#upload-progressbar').hide();
							}

						}
					}
				});

			}
			_upload (0);
 

		}

	
}

function fastAddAll () {

	if( $('.addable').length == 0 )	{

			alert('No file to add');

		}else{

			if ( !confirm('Are you sure to upload all files?') ) {
				return;
			};

			$('#upload-progressbar').show()
			$('#upload-progress').css('width', '0%')


			function _add (id) {
				
				$.ajax({
					url:'_fastadd.php?id='+id,
					dataType:'json',
					success:function(json){
						if( json.success ){

							$('#upload-progress').css('width', json.progress +'%' )

							$(json.files).each(function(i, e){
								$('#m'+e).remove();
							})								


							if(  json.progress < 100 ){

								_add(json.id);

							}else{
								alert('Upload done');
								$('#upload-progressbar').hide();
							}

						}
					}
				});

			}
			_add (0);
 

		}


}
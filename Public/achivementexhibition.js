$(document).ready(function(){
	$('#add-new-feature-submit').on('click', function() {
	 
		var featurename = $('#name').val();
		if(featurename == "")
		{
			alert("Please input featurename");
			exit();
		}
		var process = $('#process option:selected').text();
		//alert(process);
		
		var description = $('#description').val();
		var projectid = $('#project-id').val();
		var url = '/Home/Project/addfeature?projectid='+ projectid;
		$.post(url,
			{
				name:featurename,
				process:process,
				description:description
			},
			function(data){
			if(data.errno === 0) {
				$('#feature-list-table').append(
						'<tr><td class="textleft">'+ featurename + '</td>'+
						'<td class="textleft">'+ process + '</td>'+
						'<td class="textleft">'+'0'+ '</td></tr>'+
						'<td class="elememtleft"><button id="edit-btn" type="button">Edit</button>'
						);
			}else{ 
				alert(data.errmsg);
			}
		}, 'json');
	});
});

$(document).ready(function(){
	$('#save-feature-btn').on('click', function(){
	 
		//save to data base and update the table
		var rowindex = $('#feature-id-edit').text();
		var featureid = $('#feature-id-edit').val();
		var newName = $('#name-edit').val();
		var newProcess = $('#process-edit option:selected').text();
		var newDescription = $('#description-edit').val();
		
		var url = '/Home/Project/editfeature?featureid='+ featureid;
		$.post(	url,
				{
					name:newName, 
					description:newDescription, 
					process:newProcess
				},
				function(data){
					if(data.errno !== 0) {
						alert(data.errmsg);
						exit();
					}
				}
		, 'json');
		
		var newNameCell = '#feature-list-table tr:eq('+ rowindex+ ') td:eq(2)';
		$(newNameCell).text(newName);
		
		var newProcessCell = '#feature-list-table tr:eq('+ rowindex+ ') td:eq(3)';
		$(newProcessCell).text(newProcess);
		
		var newDescriptionCell = '#feature-list-table tr:eq('+ rowindex+ ') td:eq(1)';
		$(newDescriptionCell).text(newDescription);
	});
});

$(document).ready(function(){
	$('.edit-btn').on('click', function(){
	 var featureid = $(this).parents('tr').find('td:eq(0)').text();	
 	 var oldname = $(this).parents('tr').find('td:eq(2)').text();	 
	 var olddescription = $(this).parents('tr').find('td:eq(1)').text();
     var oldprocess= $(this).parents('tr').find('td:eq(3)').text()
	 var rowindex = $(this).parents('tr').index();
     
     $('#feature-id-edit').val(featureid);
     $('#feature-id-edit').text(rowindex);
	 $('#name-edit').val(oldname);
	
	 $("#process-edit option").filter(function() {
	     return $(this).text() == oldprocess; 
	 }).prop('selected', true);
//	 $('#process-edit option:selected').text(oldprocess);
	 $('#description-edit').text(olddescription);
	});
});

$(document).ready(function(){
	$('.delete-btn').on('click', function(){
		 var tr = $(this).parents('tr');
		 var featureid = $(this).parents('tr').find('td:eq(0)').text();	
		 var url = '/Home/Project/deletefeature?featureid='+ featureid;
		 $.post(url,
				 { },
				 function(data){
					 if(data.errno !== 0){
						 alert(data.errmsg);
						 exit();
					 }
				 },
				 'json');
		 tr.remove();
 });
});
$(document).ready(function(){
	$('#add-new-member-submit').on('click', function() {
		var projectId = $('#project-id').text();
		var alias = $('#new-member-name').val();
		var teamId = $('#team-id').text();
		var url = '/Home/Project/addmember?teamid='+ teamId;
		$.post(url, {alias:alias,projectId:projectId}, function(data) {
			if(data.errno === 0) {
        			$('#member-list').append('<div class="member">'+
						'<img src="'+data.photolink+'" alt="no photo"/><div>'+
						'<a href="http://directory.slb.com/query.cgi?alias='+alias+'">'+data.name+'</a></div></div>');
			        var count = parseInt($('#member-count').text()) + 1;
        			$('#member-count').text(count);
			} else {
				alert(data.errmsg);
			}
			$('#add-new-member').modal('hide');
		}, 'json');
	});

$('#add-relatedlink-submit').on('click', function() {
	var projectId = $('#project-id').text();
  var linkedId=$("#chooselink").val();
	var url = '/Home/Project/addlink?projectid='+ projectId;
	$.post(url, {linkedId:linkedId}, function(data) {
		if(data.errno !== 0){
			alert(data.errmsg);
		}
	}, 'json');

    var userAgent = '';
    if(/MSIE/i['test'](navigator['userAgent'])==true||/rv/i['test'](navigator['userAgent'])==true||/Edge/i['test'](navigator['userAgent'])==true){
       userAgent='ie';
       alert("add successfully!");
    } else {
       userAgent='other';
    }
	location.reload();
});

$('#edit-descritpion').on('click', function() {
	setEditableStatus();
});

$('#save-edited-description-btn').on('click', function(){
	//take all the update values
  var id = $('#project-id').text();
  //to not editable
	var status = $('#status option:selected').text();
  //		var status = $('#status-text').text();
	$('#status-text').text(status);
	var background =  $("#background-div").text();
	var solution = $("#solution-div").text();
	var benefit = $("#benifit-div").text();
	var link = $("#repository-link").text().trim();
  var maturityscore=$("#editmaturityscore").val();
  var innovationscore=$("#editinnovationscore").val();
  var businessscore=$("#editbusinessscore").val();
	if(link.length > 0 && strStartsWith(link, 'http') === false)
	{
		alert("make sure input the correct url, start with http or https");
		$("#repository-link").parent().focus();
		return;
	}
	$('#repository-link').prop('href', link);
	var slogan = $("#slogan-div").text();
	//save to into project table and update the description table
	var url = "/Home/Project/updateInnovationIdea/";
	$.post(url,{ maturityscore:maturityscore,
					     innovationscore:innovationscore,
				       businessscore:businessscore,
			         id:id,
					     status:status,
					     background:background,
					     solution:solution,
					     benefit:benefit,
					     link:link,
					     slogan:slogan }, function(data){
		if(data.errno !== 0){
			alert(data.errmsg);
		}
	}, 'json');
	setNotEditableStatus();
	$('#add-edit-summary').modal('show');
  //location.reload();
});

$('#cancel-edit-btn').on('click', function(){
	var id = $('#project-id').text();
	location.href = "/Home/Project/dashboard?id="+id;
});

	// $('#deletefile1').on('click', function() {
	// 	$('.showfile1').css("visibility", 'hidden');
	// 	$('#deletefile1').css("visibility", 'hidden');
	// 	var projectId = $('#project-id').text();
	// 	var fileserialnumber=1;
	// 	console.log(projectId);
	// 	console.log(fileserialnumber);
	// 	var url = '/Home/Project/deleteattachedfile/'; //addmember?teamid='+ teamId;
	// 	$.post(url, {projectId:projectId,fileserialnumber:fileserialnumber}, function(data) {
	// 		if(data.errno !== 0){
	// 			alert(data.errmsg);
	// 		}
	// 	},'json');
	// 	console.log("yes1");
	// });
	// $('#deletefile2').on('click', function() {

	// 	console.log("yes2");
	// });
	// $('#deletefile3').on('click', function() {

	// 	console.log("yes");
	// });

function setEditableStatus(){
	//image upload
	$('#upload-screenshot-form').css('display', 'block');
	$('#image-path-input').focus();
	$('#upload-screenshot-form').css("visibility", 'visible');

	$('#ms').css("visibility", 'hidden');
  $('#edit_maturity_score').css('display', 'block');
  $('#is').css("visibility", 'hidden');
  $('#edit_innovation_score').css('display', 'block');
  $('#bs').css("visibility", 'hidden');
  $('#edit_business_score').css('display', 'block');

  //$('#title-div').prop('contenteditable', 'true');
	$('#background-div').prop('contenteditable', 'true');
	$('#solution-div').prop('contenteditable', 'true');
	$('#benifit-div').prop('contenteditable', 'true');
	$('#repository-link').prop('contenteditable', 'true');
	$('#slogan-div').prop('contenteditable', 'true');
	$('#benifit-div').prop('contenteditable', 'true');

	//$('.delete-file').css("visibility", 'visible');
	//set save and cancel button visible
	$('#save-edited-description-btn').css("visibility", 'visible');
	$('#cancel-edit-btn').css("visibility", 'visible');
	$('#edit-descritpion').css("visibility", 'hidden');

	$('.description-div').addClass('description-editable');
 }

 if($('#existfilecount').text()=="3"){}
 else{
		$('#upload-file-form').css("visibility", 'visible');
		$('.uploadwarning').css("visibility", 'visible');
 };

 function setNotEditableStatus(){
	$('#upload-screenshot-form').css("visibility", 'hidden');
	$('#upload-screenshot-form').css('display', 'none');

	$('#ms').css("visibility", 'visible');
  $('#edit_maturity_score').css('display', 'none');
  $('#is').css("visibility", 'visible');
  $('#edit_innovation_score').css('display', 'none');
  $('#bs').css("visibility", 'visible');
  $('#edit_business_score').css('display', 'none');

	$('#status-text').css("visibility", 'visible');
  $('#status').css("visibility", 'hidden');
  $('#status-text').css("display", "block");
	$('#background-div').prop('contenteditable', 'false');
	$('#solution-div').prop('contenteditable', 'false');
	$('#benifit-div').prop('contenteditable', 'false');
  $('#repository-link').prop('contenteditable', 'false');
	$('#slogan-div').prop('contenteditable', 'false');
	$('#benifit-div').prop('contenteditable', 'false')

	//$('.delete-file').css("visibility", 'hidden');

	$('#save-edited-description-btn').css("visibility", 'hidden');
	$('#cancel-edit-btn').css("visibility", 'hidden');
	$('#edit-descritpion').css("visibility", 'visible');

	$('.description-div').removeClass('description-editable');
}

function strStartsWith(str, prefix) {
	  return str.indexOf(prefix) === 0;
}
	// use the UI plug-in, some issue happened
//	$("#upload-image").fileinput({
//		 uploadUrl: "/index.php/Home/Project/uploadImage",
//		 showUpload:'false',
//		 showPreview: false,
//		 previewFileType:'any',
//		 allowedFileTypes: ['image'],
//	     allowedFileExtensions: ["jpg", "png", "gif"],
//	     maxFileSize:3072,
//	     elErrorContainer: "#errorBlock"
//	});

	$("#upload-image").on("click", function(){
//		var projectId = $('#project-id').text();
//
//		if($("#image-path").val().trim() === ""){
//			alert("No file is chosen");
//			return;
//		}
//        return;
	});
});

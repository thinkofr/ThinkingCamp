$(document).ready(function(){
$("#login").click(function(){
var usernameinput = $("#username").val();
var passwordinput = $("#password").val();
// Checking for blank fields.
if(usernameinput=='' || passwordinput==''){
	$('input[type="text"],input[type="password"]').css("border","2px solid red");
	$('input[type="text"],input[type="password"]').css("box-shadow","0 0 3px red");
	alert("input password or username");
}
else {
	var url="/Home/User/login";
	$.post(url,{username: usernameinput, password:passwordinput},
			function(data) {
	if(data==2000){
		$('input[type="text"],input[type="password"]').css({"border":"2px solid red","box-shadow":"0 0 3px red"});
		alert("Could not connect to LDAP server");
		} else if(data=='4000'){
			
		} else if(data=='3000'){
			//window.location.href("{:U('Project/index')}");
		}else{
			alert(data);
			}
	 });
    }
  });
});
<?php
include("connection.php");
 ?>
<html>
	<head>
		<style>
			body {
				background-color:#f1f3f4 !important;
			}
			table tr input[type='number'],table tr input[type='text'] {
				width:100%
			}
			.upload {
				border-radius:5px;
				float:none !important;
				margin:auto;
				margin-top:50px;
			}
		</style>
		<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
		<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
		<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/1.3.5/jspdf.debug.js"></script>
		<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
		<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css" />
	</head>
	<body>
		<div class="container-fluid">
			<a href="logout.php" class="btn btn-warning pull-right" style="margin-top:20px;">Logout</a>
			<div class="col-md-6 upload">
				<div class="panel panel-primary">
					<div class="panel-heading">
						Upload Microsoft Access file(.mdb)
					</div>
					<div class="panel-body">
						<form method="post" action="#" id="send" enctype="multipart/form-data" >
					<div class="form-group">
						<label style="float:left;width:50%;">Choose file</label>
						<input type="file" name="mdb_files" id="mdb_files" required />
					</div>
					<div class="form-group">
						<input type="submit" value="Submit" class="btn btn-primary pull-right">
						<img src="loader.gif" id="img" style="display:none;max-width: 74px;margin-right: -17px;margin-top: 3px;" class="pull-right"/>
					</div>
						</form>
					<span id="mess"></span>
					</div>
				</div>
			</div>
		</div>
	</div>
<script>
		$("#mdb_files").change(function(){
        	var file=$(this)[0].files[0].name;
            
			// if(!file.match(".mdb")){
			// 	alert("Please choose a mdb file");
			// 	$(this).val("");
			// }
		});
			$(document).ready(function(){
				$("#send").submit(function(event){                
                	var a=0;
					event.preventDefault();
					var formData = new FormData();
					formData.append('file', $('#mdb_files').prop('files')[0]);
                    $.ajax({
                           url : 'handle.php',
                           type : 'POST',
                           data : formData,
                           processData: false,
                           contentType: false,
                           beforeSend:function(){
                              $("#img").show();
                              setInterval(function(){a+=1;},1000);
                          },
                          complete:function(){
                              $("#img").hide();
                          },
                          success : function(data) {
                          		$("#mess").html(data);
                          		$("#mdb_files").val("");
                          },
                          error:function(r) {
                              alert("An error caught");
                          }
                    });                                       
				});
			//download pdf file
			
		});
		</script>
	</body>
</html>

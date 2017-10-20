<?php
$config = new ApiportalModelapiportal();

$usersModels = new APIPortalModelUsers();

$users = $usersModels->getItems();

$newvalue = array_values( (array)$users );



?>

<head>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
</head>


<div class='mydiv'>    
    <textarea id="txt" class='txtarea' name="hide" style="display:none;"><?php echo json_encode($newvalue); ?></textarea>
    <button class='gen_btn'>Generate File</button>
</div>




<script>

$(document).ready(function(){
    $('button').click(function(){
        var data = $('#txt').val();
        if(data == '')
            return;
        
        JSONToCSVConvertor(data, true);
    });
});

function JSONToCSVConvertor(JSONData, ShowLabel) {
  
  
    //If JSONData is not an object then JSON.parse will parse the JSON string in an Object
    var arrData = typeof JSONData != 'object' ? JSON.parse(JSONData) : JSONData;

function data_to_array(arrData) {
    var array = [];
    for (var key in arrData) {
        var value = arrData[key];
        if (typeof value === 'string') {
            array[key] = value;
        } else {
            array[key] = data_to_array(value);
        }
    }
    return array;
}

var array = data_to_array(arrData);
    
  console.log(array);
 
  
array = array.map(function(array) {
    return { email: array.email, name: array.name, cpf: array.cpf, description: array.description, phone: array.phone, mobile: array.mobile, enabled: array.enabled, createdOn: array.createdOn, state: array.state};
});  
  
  
    var CSV = '';    
    

    //This condition will generate the Label/Header
    if (ShowLabel) {
        var row = "";
        
        //This loop will extract the label from 1st index of on array
        for (var index in array[0]) {
            
            //Now convert each value to string and comma-seprated
            row += index + ',';
          
          
        }
      
      
        row = row.slice(0, -1);
        
        //append Label row with line break
        CSV += row + '\r\n';

    }
    
 
    //1st loop is to extract each row
    for (var i = 0; i < array.length; i++) {
        var row = "";
        
        //2nd loop will extract each column and convert it in string comma-seprated
        for (var index in array[i]) {
            row += '"' + array[i][index] + '",';
         
        }

        row.slice(0, row.length - 1);
        
        //add a line break after each row
        CSV += row + '\r\n';
    }

    if (CSV == '') {        
        alert("Invalid data");
        return;
    }   
    
    //Generate a file name
    var fileName = "MyReport_";
    //this will remove the blank-spaces from the title and replace it with an underscore
  
    
    //Initialize file format you want csv or xls
    var uri = 'data:text/csv;charset=utf-8,' + escape(CSV);
    
    // Now the little tricky part.
    // you can use either>> window.open(uri);
    // but this will not work in some browsers
    // or you will not get the correct file extension    
    
    //this trick will generate a temp <a /> tag
    var link = document.createElement("a");    
    link.href = uri;
    
    //set the visibility hidden so it will not effect on your web-layout
    link.style = "visibility:hidden";
    link.download = fileName + ".csv";
    
    //this part will append the anchor tag and remove it after automatic click
    document.body.appendChild(link);
    link.click();


document.body.removeChild(link);


}




</script>
<?php

defined( '_JEXEC' ) or die( 'Restricted access' );
//Get database connection and query

$db = JFactory::getDbo();
$query = $db->getQuery(true);
$query->select($db->quoteName('orgCode'));
$query->from($db->quoteName('#__parameters'));

//set query
$db->setQuery($query);

//return query data
$results = $db->loadObjectList();
$json= json_encode($results, true);
$json_decode = json_decode($json,true);
$count = count($results);


//Post data
$orgCode = $_POST['orgCode'];



?>

<legend>
  API portal Parâmetros
</legend>

<!----------Form---------->
  
<form name="names" id="orgCodeLabel" action="<?php echo JURI::current(); ?>" method="post">
  <div style="width:400px" class="hasPopover"  title data-content="Código da organização usado no auto registro de usuário">
 <label>Código da Organização: </label>
 <input name="orgCode" id="orgCode" value="<?php echo $json_decode[0]['orgCode'] ?>" />
  </div>
 
    <p><input id="submit" class="btn btn-primary" name="submit" type="submit" onclick="window.location.reload()" value="Save Parameter" /></p>
  
</form>


<script>


jQuery(function($){ $(".hasTooltip").tooltip({"html": true,"container": "body"}); });
jQuery(function($){ $(".hasPopover").popover({"html": true,"trigger": "hover focus","container": "body"}); });
			jQuery(document).ready(function(){
			
				
			});

</script>

  

<?php
if( (isset($orgCode)) ) {
   //orgCode set, continue-->
   $data =new stdClass();
   
   $data->orgCode=$orgCode;
   
   $db = JFactory::getDBO();
  
  
  if($count == 0){
    
   $db->insertObject('#__parameters', $data);
    echo "<meta http-equiv='refresh' content='0'>";
    
  }
  else{
   
    $data->id = 1;
    $db->updateObject('#__parameters', $data,'id');
    echo "<meta http-equiv='refresh' content='0'>";
    
  }
    
}  

else {
  
return false;  
  
}


?>
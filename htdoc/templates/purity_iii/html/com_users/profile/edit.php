<?php

/** post form to db module **/

// No direct access
defined( '_JEXEC' ) or die( 'Restricted access' );



//--POST YOUR FORM DATA HERE-->
$create_url = $_POST['create_url'];
$update_url = $_POST['update_url'];

//--END POST YOUR FORM DATA---|
//--build the form------------>
?>
<form name="names" id="names" action="<?php echo JURI::current(); ?>" method="post">
 <label>create url</label>
  <p><input type="url" name="create_url" id="create_url" value="" /></p>
 <label>update url</label>
  <p><input type="url" name="update_url" id="update_url" value="" /></p>
  <p><input id="submit" name="submit" type="submit" value="Submit parameters" /></p>
</form>


<?php
if( (isset($create_url)) ) {
   //first name or last name set, continue-->
   $data =new stdClass();
   
   $data->create_url=$create_url;
   $data->update_url=$update_url;
  
   
   

   $db = JFactory::getDBO();
   $db->insertObject('#__parameters', $data);
}  else {
  
}


?>

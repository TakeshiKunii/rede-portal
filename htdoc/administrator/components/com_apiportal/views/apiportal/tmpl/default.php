<?php
/*------------------------------------------------------------------------
# default.php - API Portal Component
# ------------------------------------------------------------------------
# author    Axway
# copyright Copyright (C) 2014. All Rights Reserved
# license   GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html
# website   www.axway.com
-------------------------------------------------------------------------*/

// No direct access to this file
defined('_JEXEC') or die('Restricted access');

// load tooltip behavior
JHtml::_('behavior.tooltip');
JHtml::_('behavior.multiselect');
JHtml::_('dropdown.init');
JHtml::_('formbehavior.chosen', 'select');
?>
<form action="<?php echo JRoute::_('index.php?option=com_apiportal&view=apiportal'); ?>" method="post" name="adminForm" id="adminForm"
      enctype="multipart/form-data" >
    <?php if(!empty( $this->sidebar)){ ?>
    <div id="j-sidebar-container" class="span2">
        <?php echo $this->sidebar; ?>
    </div>
    <div id="j-main-container" class="span10">
        <?php } else { ?>
        <div id="j-main-container">
            <?php }; ?>

            <fieldset class="form-horizontal">
                <legend><?php echo JText::_('COM_APIPORTAL_CONFIGURATION_LEGEND'); ?></legend>
                <?php foreach($this->form->getFieldset() as $field): ?>
                    <?php if (!$field->hidden && $field->id != "jform_oauthPath" && $field->id != "jform_oauthPort" && $field->id != 'jform_ssoEntityID' && $field->id != 'jform_isSsoOn' && $field->id != 'jform_ssoPath' && $field->id != 'jform_clientSdk' && $field->id != 'jform_publicApi' && $field->id != 'jform_publicApiAccountLoginName' && $field->id != 'jform_publicApiAccountPassword'): ?>
                    <div class="control-group <?php echo $field->id == "jform_cert" || $field->id == "jform_certFileName" ? "hidden" : ""; ?>">
                       
                        <div class="api-control-label">
                            <?php if (!$field->hidden && $field->id != "jform_oauthPath" && $field->id != "jform_oauthPort" && $field->id != 'jform_ssoEntityID' && $field->id != 'jform_isSsoOn' && $field->id != 'jform_ssoPath' && $field->id != 'jform_clientSdk' && $field->id != 'jform_publicApi' && $field->id != 'jform_publicApiAccountLoginName' && $field->id != 'jform_publicApiAccountPassword'): ?>
                                <?php echo $field->label; ?>
                            <?php endif; ?>
                        </div>
                        <div class="controls">
                            <?php if($field->id == "jform_host"){
                                $field->value = $this->host;
                            } else if($field->id == "jform_port"){
                                $field->value = $this->port;
                            } else if($field->id == "jform_verifyCrt"){
                                $field->value = $this->verifySSL;
                            } else if($field->id == "jform_certFileName"){
                                $field->value = $this->certFileName;
                            } else if ($field->id == "jform_verifySslHost") {
                                $field->value = $this->verifySslHost;
                            } 

                            if($field->id != "jform_oauthPath" && $field->id != "jform_oauthPort" && $field->id != 'jform_ssoEntityID' && $field->id != 'jform_isSsoOn' && $field->id != 'jform_ssoPath'  && $field->id != 'jform_clientSdk' && $field->id != 'jform_publicApi' && $field->id != 'jform_publicApiAccountLoginName' && $field->id != 'jform_publicApiAccountPassword'){
                                echo $field->input;
                            }?>
                        </div>
                    </div>
                    <?php endif;?>
                <?php endforeach; ?>
            </fieldset>

            <fieldset class="form-horizontal" style="margin-top: 60px;">
                <legend><?php echo JText::_('COM_APIPORTAL_OAUTH_CONFIGURATION_LEGEND'); ?></legend>
                <?php foreach($this->form->getFieldset() as $field): ?>
                    <?php if($field->id == "jform_oauthPath" || $field->id == "jform_oauthPort"){ ?>
                        <div class="control-group" >
                            <div class="api-control-label">
                                <?php echo $field->label; ?>
                            </div>
                            <div class="controls">
                                <?php if($field->id == "jform_oauthPath"){
                                    $field->value = $this->oauthPath;
                                } else if ($field->id == "jform_oauthPort"){
                                    $field->value = $this->oauthPort;
                                }
                                echo $field->input;
                                ?>
                            </div>
                        </div>
                    <?php } ?>
                <?php endforeach; ?>
            </fieldset>

	        <fieldset class="form-horizontal" style="margin-top: 60px;">
		        <legend><?php echo JText::_('COM_APIPORTAL_SSO_CONFIGURATION_LEGEND'); ?></legend>

                <?php
                $isSsoOn = $this->form->getField('isSsoOn')
                ?>
                <div class="control-group">
                    <div class="api-control-label">
                        <?php echo $isSsoOn->label; ?>
                    </div>
                    <div class="controls">
                        <?php
                        $isSsoOn->value = $this->isSsoOn;
                        echo $isSsoOn->input;
                        ?>
                    </div>
                </div>

                <?php
                $ssoPath = $this->form->getField('ssoPath')
                ?>
                <div class="control-group hidden">
                    <div class="api-control-label">
                        <?php echo $ssoPath->label; ?>
                    </div>
                    <div class="controls">
                        <?php
                        $ssoPath->value = $this->ssoPath;
                        echo '/'.$ssoPath->input;
                        ?>
                    </div>
                </div>

                <?php
		        $ssoEntity = $this->form->getField('ssoEntityID')
		        ?>
		        <div class="control-group hidden">
			        <div class="api-control-label">
				        <?php echo $ssoEntity->label; ?>
			        </div>
			        <div class="controls">
				        <?php
				            $ssoEntity->value = $this->ssoEntityID;
				            echo $ssoEntity->input;
				        ?>
			        </div>
		        </div>
		        
		       
	        </fieldset>
	        
	        <!-- PUBLIC API SETTINGS -->
	        <fieldset class="form-horizontal" style="margin-top: 60px;">
                <legend><?php echo JText::_('COM_APIPORTAL_CONFIGURATION_PUBLICAPI_SETTING_LEGEND'); ?></legend>
                	
                	<?php $publicApi = $this->form->getField('publicApi') ?>
	                    <div class="control-group">
	               		    <div class="api-control-label">
	                            <?php echo $publicApi->label; ?>
	                        </div>
	                        <div class="controls">
	                           <?php
	                           		$publicApi->value = $this->publicApi;
	                           		echo $publicApi->input;
	                     	   ?>
	                       </div>
                        </div>
                        
                        <?php $publicApiAccountLoginName = $this->form->getField('publicApiAccountLoginName') ?>
	                    <div class="control-group hidden">
	               		    <div class="api-control-label">
	                            <?php echo $publicApiAccountLoginName->label; ?>
	                        </div>
	                        <div class="controls">
	                           <?php
	                           		$publicApiAccountLoginName->value = $this->publicApiAccountLoginName;
	                           		echo $publicApiAccountLoginName->input;
	                     	   ?>
	                       </div>
                        </div>
                        
                        <?php $publicApiAccountPassword = $this->form->getField('publicApiAccountPassword') ?>
	                    <div class="control-group hidden">
	               		    <div class="api-control-label">
	                            <?php echo $publicApiAccountPassword->label; ?>
	                        </div>
	                        <div class="controls">
	                           <?php
	                           		$publicApiAccountPassword->value = $this->publicApiAccountPassword;
	                           		echo $publicApiAccountPassword->input;
	                     	   ?>
	                       </div>
                        </div>
                	 
            </fieldset>
	        
            <input type="hidden" name="task" value="" />
            <input type="hidden" name="boxchecked" value="0" />
            <?php echo JHtml::_('form.token'); ?>
</form>

<script type="text/javascript">
    // jQuery is loaded in 'noconflict' mode.
    jQuery(document).ready(function($) {

        //Yes
        if($("#jform_verifyCrt0").attr("checked")){
            $('#jform_cert').parent().parent().removeClass("hidden");
            $("#jform_certFileName").parent().parent().removeClass("hidden");
        }

        $('#jform_verifyCrt0').click(function(){
            $('#jform_cert').parent().parent().removeClass("hidden");
            $("#jform_certFileName").parent().parent().removeClass("hidden");
        });

        //No
        $('#jform_verifyCrt1').click(function(){
            $('#jform_cert').parent().parent().addClass("hidden");
            $("#jform_certFileName").parent().parent().addClass("hidden");
        });


        var sso = $("#jform_isSsoOn0");
        //
        if(sso.attr("checked")){
            $('#jform_ssoEntityID').parent().parent().removeClass("hidden");
            $('#jform_ssoPath').parent().parent().removeClass("hidden");
        }

        sso.click(function(){
            $('#jform_ssoEntityID').parent().parent().removeClass("hidden");
            $('#jform_ssoPath').parent().parent().removeClass("hidden");
        });

        $('#jform_isSsoOn1').click(function(){
            $('#jform_ssoEntityID').parent().parent().addClass("hidden");
            $('#jform_ssoPath').parent().parent().addClass("hidden");
        });

        var publicApi = $("#jform_publicApi0");

        if(publicApi.attr("checked")){
            $('#jform_publicApiAccountLoginName').parent().parent().removeClass("hidden");
            $('#jform_publicApiAccountPassword').parent().parent().removeClass("hidden");
        }

        publicApi.click(function(){
            $('#jform_publicApiAccountLoginName').parent().parent().removeClass("hidden");
            $('#jform_publicApiAccountPassword').parent().parent().removeClass("hidden");
        });

        $('#jform_publicApi1').click(function(){
            $('#jform_publicApiAccountLoginName').parent().parent().addClass("hidden");
            $('#jform_publicApiAccountPassword').parent().parent().addClass("hidden");
        });
        

        
    });
</script>
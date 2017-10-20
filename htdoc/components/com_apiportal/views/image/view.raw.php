<?php

defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.view');

class ApiPortalViewImage extends JViewLegacy
{
    public function display($tpl = null) {
        // Called on the 'image' model
        $image = $this->get('Image');

        if (count($errors = $this->get('Errors'))) {
            throw new Exception(implode("\n", $errors));
        }

        $document = JFactory::getDocument();
        $document->setMimeEncoding($image->mimeType);
        echo $image->content;
    }
}

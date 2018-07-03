<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ComPagesControllerPage extends KControllerModel
{
    protected function _initialize(KObjectConfig $config)
    {
        $config->append([
            'formats'   => ['json'],
            'behaviors' => ['redirectable'],
        ]);
        parent::_initialize($config);
    }

    public function getRequest()
    {
        $request = parent::getRequest();

        //Remove the view query parameter
        $request->query->remove('view');

        return $request;
    }

    public function getFormats()
    {
        $formats = parent::getFormats();

        if($page = $this->getRequest()->query->page)
        {
            $format = $this->getRequest()->getFormat();
            if($this->getObject('page.registry')->isPageFormat($page, $format)) {
                $formats = array($format);
            } else {
                $formats = array();
            }
        }

        return $formats;
    }

    protected function _beforeRender(KControllerContextInterface $context)
    {
        //Set the entity content in the response to allow for view decoration
        if($context->request->getFormat() == 'html')
        {
            $entity = $this->getModel()->fetch();
            $context->response->setContent($entity->content);
        }
    }

    protected function _afterRender(KControllerContextInterface $context)
    {
        if($context->request->getFormat() == 'html')
        {
            //Set the metadata
            foreach($this->getView()->getMetadata() as $name => $content) {
                JFactory::getDocument()->setMetaData($name, $content);
            }

            //Set the title
            JFactory::getDocument()->setTitle($this->getView()->getTitle());
        }
    }
}
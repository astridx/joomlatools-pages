<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ComPagesViewHtml extends ComKoowaViewPageHtml
{
    protected function _initialize(KObjectConfig $config)
    {
        $config->append([
            'auto_fetch'  => false,
            'decorator'   => 'joomla',
            'template_filters' => ['asset'], //Redefine asset to run before the script filter
            'template_functions' => [
                'page'        => [$this, 'getPage'],
                'collection'  => [$this, 'getCollection'],
                'state'       => [$this, 'getState']
            ],
        ]);

        parent::_initialize($config);
    }

    protected function _actionRender(KViewContext $context)
    {
        $data       = $context->data;
        $parameters = $context->parameters;

        //Render the page if it hasn't been rendered yet
        if(empty($this->getPage()->content))
        {
            //Create template (add parameters BEFORE cloning)
            $page = clone $this->getTemplate()->setParameters($parameters);
            $page->addFilters($this->getPage()->process->filters)
                ->loadFile('page://pages/'.$this->getPage()->route);

            //Render page
            $content = $page->render(KObjectConfig::unbox($data->append($page->getData())));
            $this->getPage()->content = $content;
        }
        else $content = $this->getPage()->content;

        //Set the rendered page in the view to allow for view decoration
        $this->setContent($content);

        if($layout = $this->getLayout())
        {
            //Render the layout
            $renderLayout = function($layout, $data, $parameters) use(&$renderLayout)
            {
                $template = $this->getTemplate()
                    ->setParameters($parameters)
                    ->loadFile($layout);

                //Append the template layout data
                //
                //Do not overwrite existing data, only add it not defined yet
                $this->getLayoutData()->append($template->getData());

                //Merge the page layout data
                //
                //Allow the layout data to be modified during template rendering
                $data->merge($this->getLayoutData());

                //Render the template
                $this->setContent($template->render(KObjectConfig::unbox($data)));

                //Handle recursive layout
                if($layout = $template->getLayout()) {
                    $renderLayout($layout, $data, $parameters);
                }
            };

            Closure::bind($renderLayout, $this, get_class());
            $renderLayout($layout, $data, $parameters);
        }

        return KViewAbstract::_actionRender($context);
    }

    public function getLayout()
    {
        if($layout = $this->getPage()->layout) {
            $layout = $layout->path;
        }

        return $layout;
    }

    public function getLayoutData()
    {
        $data = array();
        if($layout = $this->getPage()->layout)
        {
            unset($layout->path);
            $data = $layout;
        }

        return $data;
    }

    public function getPage($path = null)
    {
        if(!is_null($path)) {
            $result = $this->getObject('com:pages.model.factory')->createPage($path);
        } else {
            $result = $this->getModel()->getPage();
        }

        return $result;
    }

    public function getCollection($source = '', $state = array())
    {
        if($source) {
            $result = $this->getObject('com:pages.model.factory')->createCollection($source, $state)->fetch();
        } else {
            $result = $this->getModel()->fetch();
        }

        return $result;
    }

    public function getState()
    {
        return $this->getModel()->getState();
    }

    public function getTitle()
    {
        $result = '';
        if($page = $this->getPage()) {
            $result = $page->title ? $page->title :  '';
        }

        return $result;
    }

    public function getMetadata()
    {
        $metadata = array();
        if($page = $this->getPage())
        {
            if($page->metadata)
            {
                $metadata = KObjectConfig::unbox($page->metadata);

                if($page->metadata->has('og:type'))
                {
                    $metadata['og:image'] = rtrim($this->getObject('request')->getBaseUrl(), '/').'/'.ltrim($metadata['og:image'], '/');

                    if(!$metadata['og:url']) {
                        $metadata['og:url'] = (string) $this->getRoute($page);
                    }
                }
            }
        }

        return $metadata;
    }

    public function getRoute($page, $query = array(), $escape = false)
    {
        if(!is_array($query)) {
            $query = array();
        }

        if($route = $this->getObject('dispatcher')->getRouter()->generate($page, $query)) {
            $route->setEscape($escape)->toString(KHttpUrl::BASE + KHttpUrl::QUERY);
        }

        return $route;
    }
}
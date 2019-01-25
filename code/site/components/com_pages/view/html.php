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
            'decorator' => 'joomla',
            'template_filters' => ['asset'], //Redefine asset to run before the script filter
            'template_functions' => [
                'page'   => [$this, 'getPage'],
                'pages'  => [$this, 'getPages'],
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
        $result   = array();
        $registry = $this->getObject('page.registry');

        if (!is_null($path))
        {
            if ($data = $registry->getPage($path)) {
                $result = $this->getObject('com:pages.model.pages')->create($data->toArray());
            }

        }
        else $result = $this->getModel()->getPage();

        return $result;
    }

    public function getPages($path = '.', $state = array())
    {
        $result = array();

        if ($path && $this->getObject('page.registry')->isPage($path))
        {
            if(is_string($state)) {
                $state = json_decode('{'.preg_replace('/(\w+)/', '"$1"', $state).'}', true);
            }

            $result = $this->getObject('com:pages.model.pages')
                ->setState($state)
                ->path($path)
                ->fetch();
        }

        return $result;
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
        if($data = $this->getPage())
        {
            if(isset($data->metadata)) {
                $metadata = KObjectConfig::unbox($data->metadata);
            }

            //Set the description into the metadata if it doesn't exist.
            if(!empty($data->summary) && !isset($data->metadata->description)) {
                $metadata['description'] = $data->summary;
            }
        }

        return $metadata;
    }

    public function getRoute($page = '', $query = array(), $escape = false)
    {
        if($page instanceof KModelEntityInterface) {
            $page = $page->route;
        }

        if(!is_array($query)) {
            $query = array();
        }

        //Add the model state only for routes to the same page
        if($page == $this->getPage()->route)
        {
            if($collection = $this->getPage($page)->collection)
            {
                $states = array();
                foreach ($this->getModel()->getState() as $name => $state)
                {
                    if ($state->default != $state->value && !$state->internal) {
                        $states[$name] = $state->value;
                    }
                }

                $query = array_merge($states, $query);
            }
        }

        $route = $this->getObject('dispatcher')->getRouter()
            ->generate($page, $query)
            ->setEscape($escape)
            ->toString(KHttpUrl::BASE + KHttpUrl::QUERY);

        return $route;
    }
}
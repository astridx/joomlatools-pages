<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ComPagesRouter extends KObject implements KObjectSingleton
{
    private $__valid = false;

    public function build(&$query)
    {
        $segments = array();

        //Path
        if(isset($query['path']))
        {
            //Remove hardcoded states
            if($collection = $this->getObject('page.registry')->isCollection($query['path']))
            {
                if(isset($collection['state'])) {
                    $query = array_diff_key($query, $collection['state']);
                }
            }

            //Handle frontpage
            $segments[] = $query['path'];
            unset($query['path']);
        }



        //Slug
        if(isset($query['slug']))
        {
            //Handle frontpage
            if($query['slug'] != 'index') {
                $segments[] = $query['slug'];
            }

            unset($query['slug']);
        }

        //Format
        if(isset($query['format'])) {
            JFactory::getConfig()->set('sef_suffix', 1);
        }

        return $segments;
    }

    public function parse($segments)
    {
        $query = array();

        //Replace all the ':' with '-' again
        $segments = array_map(function($segment) {
            return str_replace(':', '-', $segment);
        }, $segments);

        $page = array_pop($segments);
        if($format = pathinfo($page, PATHINFO_EXTENSION))
        {
            $query['format'] = $format;
            $segments[] = basename($page, '.'.$format);
        }
        else $segments[] = $page;

        //Path and page
        $route = implode($segments, '/');

        if($this->getObject('page.registry')->isPage($route))
        {
            $query['page'] = $route;

            if($collection = $this->getObject('page.registry')->isCollection($route))
            {
                $query['path']   = $route;
                $query['layout'] = $route;

                //Add hardcoded states
                if(isset($collection['state'])) {
                    $query = array_merge($query, $collection['state']);
                }
            }
            else
            {
                $query['slug'] = array_pop($segments);
                $query['path'] = implode($segments, '/') ?: '.';
            }

            $this->__valid = true;
        }
        else
        {
            $query['slug']   = '';
            $query['path']   = '';
            $query['format'] = '';
            $query['page']   = '';

            $this->__valid = false;
        }

        return $query;
    }

    public function isValid()
    {
        return $this->__valid;
    }
}

function PagesBuildRoute(&$query)
{
    return KObjectManager::getInstance()->getObject('com:pages.router')->build($query);
}

function PagesParseRoute($segments)
{
    return KObjectManager::getInstance()->getObject('com:pages.router')->parse($segments);
}
<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

/**
 * Router Page Route
 *
 * @author  Johan Janssens <https://github.com/johanjanssens>
 * @package Koowa\Library\Dispatcher\Router\Route
 */
class ComPagesDispatcherRouterRoutePage extends ComPagesDispatcherRouterRouteAbstract
{
    public function getPage()
    {
        if($this->isGenerated()) {
            $path = $this->_initial_route->getPath();
        } else {
            $path = $this->getPath();
        }

        return $this->getObject('page.registry')->getPage(trim($path, '/'));
    }

    public function getState()
    {
        $state = array();

        if($page = $this->getPage())
        {
            if(($collection = $page->isCollection()) && isset($collection['state'])) {
                $state = $collection['state'];
            }
        }

        return $state;
    }

    public function setGenerated()
    {
        parent::setGenerated();

        //Remove any hardcoded states from the generated route
        $this->query = array_diff_key($this->query, $this->getState());

        return $this;
    }
}
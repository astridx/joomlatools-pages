<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ComPagesControllerPage extends ComPagesControllerAbstract
{
    protected function _beforeRender(KControllerContextInterface $context)
    {
        if($context->request->getFormat() == 'html')
        {
            //Set the path in the pathway to allow for module injection
            $router = $this->getObject('dispatcher')->getRouter();

            $page_route = $router->getPath();
            $menu_route = JFactory::getApplication()->getMenu()->getActive();

            if($path = ltrim(str_replace($menu_route->route, '', $page_route), '/'))
            {
                $pathway = JFactory::getApplication()->getPathway();

                $segments = array();
                foreach(explode('/', $path) as $segment)
                {
                    $segments[] = $segment;
                    $route      = $router->generate(implode('/', $segments));

                    $pathway->addItem(ucfirst($segment), (string) $route);
                }
            }
        }
    }
}
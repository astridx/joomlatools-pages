<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ComPagesModelBehaviorAccessible extends ComPagesModelBehaviorFilterable
{
    protected function _accept($page, $context)
    {
        if($result = $page['published'])
        {
            //Check groups
            if(isset($page['access']['groups']))
            {
                $groups = $this->getObject('com:pages.database.table.groups')
                    ->select($this->getObject('user')->getGroups(), KDatabase::FETCH_ARRAY_LIST);

                $groups = array_map('strtolower', array_column($groups, 'title'));

                if(!array_intersect($groups, $page['access']['groups'])) {
                    $result = false;
                }
            }

            //Check roles
            if($result && isset($page['access']['roles']))
            {
                $roles = $this->getObject('com:pages.database.table.roles')
                    ->select($this->getObject('user')->getRoles(), KDatabase::FETCH_ARRAY_LIST);

                $roles = array_map('strtolower', array_column($roles, 'title'));

                if(!array_intersect($roles, $page['access']['roles'])) {
                    $result = false;
                }
            }
        }
        else $result = true;

        return $result;
    }
}
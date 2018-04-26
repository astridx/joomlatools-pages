<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-framework-pages for the canonical source repository
 */

class ComPagesTemplateLocatorPage extends KTemplateLocatorFile
{
    protected static $_name = 'page';

    protected function _initialize(KObjectConfig $config)
    {
        $config->append(array(
            'base_path' =>  Koowa::getInstance()->getRootPath().'/joomlatools-pages',
        ));

        parent::_initialize($config);
    }

    public function find(array $info)
    {
        $path = ltrim(str_replace(parse_url($info['url'], PHP_URL_SCHEME).'://', '', $info['url']), '/');

        $file = pathinfo($path, PATHINFO_FILENAME);
        $path = ltrim(pathinfo($path, PATHINFO_DIRNAME), '.');

        //Prepend the base path
        if($path) {
            $path = $this->getBasePath().'/'.$path;
        } else {
            $path = $this->getBasePath();
        }

        if($this->realPath($path.'/'.$file)) {
            $pattern = $path.'/'.$file.'/index.'.'*';
        } else {
            $pattern = $path.'/'.$file.'.*';
        }

        //Try to find the file
        $result = false;
        if ($results = glob($pattern))
        {
            foreach($results as $file)
            {
                if($result = $this->realPath($file)) {
                    break;
                }
            }
        }

        return $result;
    }
}
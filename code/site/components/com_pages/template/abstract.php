<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ComPagesTemplateAbstract extends KTemplate
{
    protected $_filename;

    public function __construct(KObjectConfig $config)
    {
        parent::__construct($config);

        //Intercept template exception
        $this->getObject('exception.handler')->addExceptionCallback(array($this, 'handleException'), true);
    }

    protected function _initialize(KObjectConfig $config)
    {
        $config->append([
            'filters'   => ['frontmatter', 'asset'],
            'functions' => [
                'date'       => [$this, '_formatDate'],
                'data'       => [$this, '_fetchData'],
                'page'       => [$this, '_fetchPage'],
                'pages'      => [$this, '_fetchPages'],
                'slug'       => [$this, '_createSlug'],
                'attribute'  => [$this, '_createAttribute'],
            ],
            'cache'           => false,
            'cache_namespace' => 'pages',
            'excluded_types' => ['html', 'txt', 'svg', 'css', 'js'],
        ]);

        parent::_initialize($config);
    }

    public function handleException(Exception &$exception)
    {
        if($exception instanceof KTemplateExceptionError)
        {
            $file   = $exception->getFile();
            $buffer = $exception->getPrevious()->getFile();

            //Get the real file if it can be found
            $line = count(file($file)) - count(file($buffer)) + $exception->getLine() - 1;

            $exception = new KTemplateExceptionError(
                $exception->getMessage(),
                $exception->getCode(),
                $exception->getSeverity(),
                $file,
                $line,
                $exception->getPrevious()
            );
        }
    }

    protected function _formatDate($date, $format = '')
    {
        if(!$date instanceof KDate)
        {
            if(empty($format)) {
                $format = $this->getObject('translator')->translate('DATE_FORMAT_LC3');
            }

            $result = $this->createHelper('date')->format(array('date' => $date, 'format' => $format));
        }
        else $result = $date->format($format);

        return $result;
    }

    protected function _createSlug($string)
    {
        return $this->getObject('filter.factory')->createFilter('slug')->sanitize($string);
    }

    protected function _createAttribute($name, $value)
    {
        $result = '';

        if($value)
        {
            if(is_array($value)) {
                $value = implode(' ', $value);
            }

            $result = ' '.$name.'="'.$value.'"';
        }

        return $result;
    }

    protected function _fetchData($path, $format = '')
    {
        $result = false;
        if(is_array($path))
        {
            foreach($path as $directory)
            {
                if (!$result instanceof ComPagesDataObject) {
                    $result = $this->getObject('data.registry')->getData($directory, $format);
                } else {
                    $result->append($this->getObject('data.registry')->getData($directory, $format));
                }
            }
        }
        else $result = $this->getObject('data.registry')->getData($path, $format);

        return $result;
    }

    protected function _fetchPage($path)
    {
        $result = array();
        if($path && $this->getObject('page.registry')->isPage($path))
        {
            $data   = $this->getObject('page.registry')->getPage($path);
            $result = $this->getObject('com:pages.model.pages')->create($data->toArray());
        }

        return $result;
    }

    protected function _fetchPages($path = '.', $state = array())
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
}
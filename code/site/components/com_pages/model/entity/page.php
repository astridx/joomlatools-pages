<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-framework-pages for the canonical source repository
 */

class ComPagesModelEntityPage extends KModelEntityAbstract
{
    public function __construct(KObjectConfig $config)
    {
        parent::__construct($config);

        //Create the config object (cannot use factory)
        $config = (new ComPagesObjectConfigPage())->fromFile($this->file);

        //Set the properties
        $this->setProperties($config->toArray(), false);

        //Se the content
        $this->_content = $config->getContent();
    }

    protected function _initialize(KObjectConfig $config)
    {
        $config->append(array(
            'data' => array(
                'title'       => '',
                'description' => '',
                'date'        => '',
                'published'   => true,
                'access'      => array(
                    'roles'  => array('public'),
                    'groups' => array('public', 'guest'),
                    'redirect'    => '',
                    'metadata'    => array(),
                    'process'     => array(
                        'plugins' => true
                    ),
                    'layout'      => 'default'
                ),
            )));

        parent::_initialize($config);
    }

    public function getPropertyContent()
    {
        $type   = pathinfo($this->file, PATHINFO_EXTENSION);
        $result = $this->getObject('com:pages.template')
            ->loadString($this->_content, $type != 'html' ? $type : null, $this->path)
            ->render();

        //Run page content through content plugins
        if($this->process->plugins)
        {
            $result = JHtml::_('content.prepare', $result);

            // Make sure our script filter does not screw up email cloaking
            if (strpos($result, '<script') !== false) {
                $result = str_replace('<script', '<script data-inline', $result);
            }
        }

        return $result;
    }

    public function setPropertyAccess($value)
    {
        return new KObjectConfig($value);
    }

    public function setPropertyProcess($value)
    {
        return new KObjectConfig($value);
    }

    public function setPropertyDate($value)
    {
        //Set the date based on the modified time of the file
        if(empty($value)) {
            $date = $this->getObject('date')->setTimestamp(filemtime($this->file));
        } else {
            $date = $this->getObject('date', array('date' => $value));
        }

        return $date;
    }

    public function toArray()
    {
        $data = parent::toArray();

        foreach($data as $key => $value)
        {
            if(empty($value)) {
                unset($data[$key]);
            }
        }

        $data['content']= $this->content;
        $data['access'] = $this->access->toArray();
        $data['date']   = $this->date->format(DateTime::ATOM);

        unset($data['file']);
        unset($data['path']);

        return $data;
    }
}
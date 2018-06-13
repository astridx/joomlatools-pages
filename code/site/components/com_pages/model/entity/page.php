<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ComPagesModelEntityPage extends KModelEntityAbstract implements JsonSerializable
{
    protected function _initialize(KObjectConfig $config)
    {
        $config->append([
            'identity_key'   => 'path',
            'data' => [
                'title'       => '',
                'summary'     => '',
                'slug'        => '',
                'content'     => '',
                'excerpt'     => '',
                'date'        => 'now',
                'author'      => '',
                'published'   => true,
                'access'      => [
                    'roles'  => ['public'],
                    'groups' => ['public', 'guest']
                ],
                'redirect'    => '',
                'metadata'    => [],
                'process'     => [
                    'plugins' => true
                ],
                'layout'      => '',
                'colllection' => false,
            ],
        ]);

        parent::_initialize($config);
    }

    public function getPropertyDay()
    {
        return $this->date->format('d');
    }

    public function getPropertyMonth()
    {
        return $this->date->format('m');
    }

    public function getPropertyYear()
    {
        return $this->date->format('y');
    }

    public function getPropertyExcerpt()
    {
        $parts = preg_split('#<!--(.*)more(.*)-->#i', $this->content, 2);
        $excerpt = $parts[0];

        return $excerpt;
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
        if(is_integer($value)) {
            $date = $this->getObject('date')->setTimestamp($value);
        } else {
            $date = $this->getObject('date', array('date' => trim($value)));
        }

        return $date;
    }

    public function jsonSerialize()
    {
        $data = parent::toArray();

        foreach($data as $key => $value)
        {
            if(empty($value)) {
                unset($data[$key]);
            }
        }

        $data['content']  = $this->content;
        $data['excerpt']  = $this->excerpt;
        $data['access']   = KObjectConfig::unbox($this->access);
        $data['metadata'] = KObjectConfig::unbox($this->metadata);
        $data['date']     = $this->date->format(DateTime::ATOM);

        unset($data['filename']);
        unset($data['process']);
        unset($data['layout']);
        unset($data['path']);

        return $data;
    }
}
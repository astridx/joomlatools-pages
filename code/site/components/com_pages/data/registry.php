<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

final class ComPagesDataRegistry extends KObject implements KObjectSingleton
{
    private $__data    = array();
    private $__locator = null;

    protected $_cache;
    protected $_cache_path;
    protected $_cache_time;

    public function __construct(KObjectConfig $config)
    {
        parent::__construct($config);

        //Create the locator
        $this->__locator = $this->getObject('com:pages.data.locator');

        //Set the cache
        $this->_cache = $config->cache;

        //Set the cache time
        $this->_cache_time = $config->cache_time;

        if(empty($config->cache_path)) {
            $this->_cache_path = $this->getObject('com:pages.page.locator')->getBasePath().'/cache';
        } else {
            $this->_cache_path = $config->cache_path;
        }
    }

    protected function _initialize(KObjectConfig $config)
    {
        $config->append([
            'cache'      => true,
            'cache_path' => '',
            'cache_time' => 60*60*24 //1 day
        ]);

        parent::_initialize($config);
    }

    public function getLocator()
    {
        return $this->__locator;
    }

    public function getData($path, $format = '')
    {
        $result = array();
        if(!isset($this->__data[$path]))
        {
            $segments = explode('/', $path);
            $root     = array_shift($segments);

            //Load the cache and do not refresh it
            $data = $this->loadCache($root, false);

            foreach($segments as $segment)
            {
                if(!isset($data[$segment]))
                {
                    $data = array();
                    break;
                }
                else $data = $data[$segment];
            }

            //Create the data object
            $class = $this->getObject('manager')->getClass('com:pages.data.object');
            $this->__data[$path] = new $class($data);
        }

        return $this->__data[$path];
    }

    private function __fromPath($path, $format = '')
    {
        if(!parse_url($path, PHP_URL_SCHEME) == 'http')
        {
            //Locate the data file
            if (!$file = $this->getLocator()->locate('data://'.$path)) {
                throw new InvalidArgumentException(sprintf('The data "%s" does not exist.', $path));
            }

            if(is_dir($file)) {
                $result = $this->__fromDirectory($file);
            } else {
                $result = $this->__fromFile($file);
            }
        }
        else $result = $this->__fromUrl($path, $format);

        return $result;
    }

    private function __fromFile($file)
    {
        //Get the data
        $result = array();

        $url = trim(fgets(fopen($file, 'r')));
        if(strpos($url, '://') === 0) {
            $result = $this->__fromUrl($url, pathinfo($file, PATHINFO_EXTENSION));
        } else {
            $result = $this->getObject('object.config.factory')->fromFile($file, false);
        }

        return $result;
    }

    private function __fromDirectory($path)
    {
        $data  = array();
        $nodes = array();

        $basepath = $this->getLocator()->getBasePath();
        $basepath = ltrim(str_replace($basepath, '', $path), '/');

        //List
        foreach (new DirectoryIterator($path) as $node)
        {
            if(strpos($node->getFilename(), '.order.') !== false) {
                $nodes = array_merge($this->__fromFile((string)$node->getFileInfo()), $nodes);
            }

            if (!in_array($node->getFilename()[0], array('.', '_'))) {
                $nodes[] = $node->getFilename();
            }
        }

        $nodes = array_unique($nodes);

        //Files
        $files = array();
        $dirs  = array();
        foreach($nodes as $node)
        {
            $info = pathinfo($node);

            if($info['extension']) {
                $files[$info['filename']] = $basepath.'/'.$node;
            } else {
                $dirs[$node] = $basepath.'/'.$node;
            }
        }

        foreach($files as $name => $file)
        {
            if($name !== basename(dirname($file))) {
                $data[$name] = $this->__fromPath($file);
            } else {
                $data = $this->__fromPath($file);
            }
        }

        foreach($dirs as $name => $dir) {
            $data[$name] = $this->__fromPath($dir);
        }

        return $data;
    }

    private function __fromUrl($url, $format = '')
    {
        if(!ini_get('allow_url_fopen')) {
            throw new RuntimeException(sprintf('Cannot open url: "%s".', $url));
        }

        if(empty($format))
        {
            if(!$format = pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION)) {
                throw new InvalidArgumentException(sprintf('Cannot determine data type of "%s".', $url));
            }
        }

        //Set the user agents
        $version = $this->getObject('com:pages.version');
        $context = stream_context_create(array('http' => array(
            'user_agent' => 'Joomlatools/Pages/'.$version,
        )));

        if(!$content = file_get_contents($url, false, $context))
        {
            if($error = error_get_last()) {
                throw new RuntimeException(sprintf('Cannot get content from url error: "%s"', trim($error['message'])));
            } else {
                throw new RuntimeException(sprintf('Cannot get content from url: "%s"', $url));
            }
        }

        $result = $this->getObject('object.config.factory')->fromString($format, $content, false);
        return $result;
    }

    public function buildCache()
    {
        if($this->_cache)
        {
            $basedir = $this->getLocator()->getBasePath();

            foreach (new DirectoryIterator($basedir) as $node)
            {
                if (!in_array($node->getFilename()[0], array('.', '_'))) {
                    $this->loadCache(pathinfo($node, PATHINFO_FILENAME), true);
                }
            }
        }

        return false;
    }

    public function loadCache($basedir, $refresh = true)
    {
        if (!$cache = $this->isCached($basedir))
        {
            $data = $this->__fromPath($basedir);
            $this->storeCache($basedir, KObjectConfig::unbox($data));
        }
        else
        {
            if (!$data = require($cache)) {
                throw new RuntimeException(sprintf('The data "%s" cannot be loaded from cache.', $cache));
            }
        }

        return $data;
    }

    public function storeCache($file, $data)
    {
        if($this->_cache)
        {
            $path = $this->_cache_path;

            if(!is_dir($path) && (false === @mkdir($path, 0755, true) && !is_dir($path))) {
                throw new RuntimeException(sprintf('The data cache path "%s" does not exist', $path));
            }

            if(!is_writable($path)) {
                throw new RuntimeException(sprintf('The data cache path "%s" is not writable', $path));
            }

            $hash = crc32($file.PHP_VERSION);
            $file  = $this->_cache_path.'/data_'.$hash.'.php';

            if(!is_string($data)) {
                $data = '<?php return '.var_export($data, true).';';
            }

            if(@file_put_contents($file, $data) === false) {
                throw new RuntimeException(sprintf('The data cannot be cached in "%s"', $file));
            }

            //Override default permissions for cache files
            @chmod($file, 0666 & ~umask());

            return $file;
        }

        return false;
    }

    public function isCached($file)
    {
        $result = false;

        if($this->_cache)
        {
            $hash   = crc32($file.PHP_VERSION);
            $cache  = $this->_cache_path.'/data_'.$hash.'.php';
            $result = is_file($cache) ? $cache : false;

            if($result && file_exists($file))
            {
                //Refresh cache if the file has changed or if the cache expired
                if((filemtime($cache) < filemtime($file)) || ((time() - filemtime($cache)) > $this->_cache_time)) {
                    $result = false;
                }
            }
        }

        return $result;
    }
}
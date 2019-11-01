<?php
/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

class ComPagesDispatcherHttp extends ComKoowaDispatcherHttp
{
    private $__router;

    public function __construct( KObjectConfig $config)
    {
        parent::__construct($config);

        $this->__router = $config->router;

        //Re-register the exception event listener to run through pages scope
        $this->addEventListener('onException', array($this, 'fail'),  KEvent::PRIORITY_NORMAL);
    }

    protected function _initialize(KObjectConfig $config)
    {
        $config->append([
            'behaviors' => [
                'redirectable',
                'routable',
                'cacheable',
            ],
            'router'  => 'com://site/pages.dispatcher.router',
        ]);

        parent::_initialize($config);
    }

    public function setRouter(ComPagesDispatcherRouterInterface $router)
    {
        $this->__router = $router;
        return $this;
    }

    public function getRouter()
    {
        if(!$this->__router instanceof ComPagesDispatcherRouterInterface)
        {
            $this->__router = $this->getObject($this->__router, array(
                'request' => $this->getRequest(),
            ));

            if(!$this->__router instanceof ComPagesDispatcherRouterInterface)
            {
                throw new UnexpectedValueException(
                    'Router: '.get_class($this->__router).' does not implement ComPagesDispatcherRouterInterface'
                );
            }
        }

        return $this->__router;
    }

    protected function _beforeDispatch(KDispatcherContextInterface $context)
    {
        //Do not call parent
    }

    protected function _actionDispatch(KDispatcherContextInterface $context)
    {
        //Throw 404 if the page cannot be found
        if(!$context->page) {
            throw new KHttpExceptionNotFound('Page Not Found');
        }

        //Throw 405 if the method is not allowed
        $method = strtolower($context->request->getMethod());
        if (!in_array($method, $this->getHttpMethods())) {
            throw new KDispatcherExceptionMethodNotAllowed('Method not allowed');
        }

        //Set the controller
        $this->setController( $context->page->getType(), ['page' =>  $context->page]);

        //Execute the component method
        $this->execute($method, $context);

        KDispatcherAbstract::_actionDispatch($context);
    }

    protected function _actionGet(KDispatcherContextInterface $context)
    {
        if($collection =  $context->page->isCollection())
        {
            if(isset($collection['state']) && isset($collection['state']['limit']))
            {
                $this->getConfig()->limit->default  = $collection['state']['limit'];
                $this->getConfig()->limit->max      = $collection['state']['limit'];
            }
        }

        return parent::_actionGet($context);
    }

    protected function _actionPost(KDispatcherContextInterface $context)
    {
        if($context->page->form->model)
        {
            if(!$context->request->data->has('_action'))
            {
                $action = $this->getController()->getModel()->getState()->isIdentity() ? 'edit' : 'add';
                $context->request->data->set('_action', $action);
            }

            $result = parent::_actionPost($context);

        }
        else $result = $this->getController()->execute('submit', $context);

        return $result;
    }

    protected function _renderError(KDispatcherContextInterface $context)
    {
            //Get the exception object
            if($context->param instanceof KEventException) {
                $exception = $context->param->getException();
            } else {
                $exception = $context->param;
            }

        if(!JDEBUG && $this->getObject('request')->getFormat() == 'html')
        {
            //If the error code does not correspond to a status message, use 500
            $code = $exception->getCode();
            if(!isset(KHttpResponse::$status_messages[$code])) {
                $code = '500';
            }

            foreach([(int) $code, '500'] as $code)
            {
                if($page = $this->getObject('page.registry')->getPage($code))
                {
                    //Set the controller
                    $this->setController($page->getType(), ['model' => $page]);

                    //Render the error
                    $content = $this->getController()->render($exception);

                    //Set error in the response
                    $context->response->setContent($content);

                    //Set status code
                    $context->response->setStatus($code);

                    return true;
                }
            }
        }
        else $context->response->setStatus($exception->getCode(), $exception->getMessage());

        return parent::_renderError($context);
    }

    public function getContext()
    {
        $context = new ComPagesDispatcherContext();
        $context->setSubject($this);
        $context->setRequest($this->getRequest());
        $context->setResponse($this->getResponse());
        $context->setUser($this->getUser());
        $context->setRouter($this->getRouter());

        return $context;
    }

    public function getHttpMethods()
    {
        $page = $this->getRoute()->getPage();

        if($page->isForm())
        {
            if($page->layout || !empty($this->getObject('page.registry')->getPageContent($page))) {
                $methods =  array('get', 'head', 'options', 'post');
            } else {
                $methods =  array('post');
            }
        }
        else $methods =  array('get', 'head', 'options');

        return $methods;
    }
}
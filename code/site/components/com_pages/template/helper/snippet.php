<?php

class ComPagesTemplateHelperSnippet extends ComPagesTemplateHelperAbstract
{
    private $__snippets = array();

    public function define(string $name, string $snippet, $overwrite = false)
    {
        if(!isset($this->__snippets[$name]) || $overwrite) {
            $this->__snippets[$name] = $snippet;
        }
    }

    public function expand(string $name, array $variables = array())
    {
        $result = false;

        if(isset($this->__snippets[$name]))
        {
            $snippet = $this->__snippets[$name];

            //Use the php template engine to evaluate
            $str = "<?php \n echo <<<SNIPPET\n$snippet\nSNIPPET;\n";

            $result = $this->getObject('template.engine.factory')
                ->createEngine('php')
                ->loadString($str)
                ->render($variables);

            //Find single whitespace before " or before > in html tags and remove it
            preg_match_all('#<\s*\w.*?>#', $result, $tags);

            foreach($tags as $tag) {
                $result = str_replace($tag,  str_replace(array(' >', ' "'), array('>', '"'), $tag), $result);
            }
        }

        return $result;
    }
}


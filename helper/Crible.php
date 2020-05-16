<?php
namespace OmekaTheme\Helper;

use Zend\View\Helper\AbstractHelper;

class Crible extends AbstractHelper
{
    /**
     * Select a crible of concept.
     *
     * @param string $id identifiant du crible
     * @return array
     */
    public function __invoke($id)
    {
        $view = $this->getView();

        $inScheme = $view->api()->search('properties', ['term' => 'skos:inScheme'])->getContent()[0];
        $param = array();
        $param['property'][0]['property']= $inScheme->id()."";
        $param['property'][0]['type']='res';
        $param['property'][0]['text']=$id; 

        $result = $view->api()->search('items',$param)->getContent();

        return $result;

    }
}

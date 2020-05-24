<?php
namespace OmekaTheme\Helper;

use Zend\View\Helper\AbstractHelper;

class getItem extends AbstractHelper
{
    /**
     * Récupère les propriétés utiles pour une page
     * 
     * @param array $props
     *
     * @return o:item
     */
    public function __invoke($props)
    {
        $view = $this->getView();

        $idConcept =$view->params()->fromQuery('idConcept', '');
        $concept = $view->params()->fromQuery('concept', '');
        if($idConcept){
                $item = $view->api()->read('items', $idConcept)->getContent();
        }elseif($concept){
                $param = array();        
                $param['property'][0]['property']= $props['skos:prefLabel']->id()."";
                $param['property'][0]['type']='eq';
                $param['property'][0]['text']=$concept; 
                $result = $view->api()->search('items',$param)->getContent();
                if(count($result))
                        $item = $result[0];
        }else{
                $aC = $view->AleaConcept(1);
                $item = $aC[0];        
        }

        return $item;
    }
}
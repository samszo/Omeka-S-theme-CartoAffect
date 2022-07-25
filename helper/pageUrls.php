<?php
namespace OmekaTheme\Helper;

use Zend\View\Helper\AbstractHelper;

class pageUrls extends AbstractHelper
{
    /**
     * Récupère les url utiles pour une page
     *
     * @param array   $props    les propriétés utiles
     * @param o:item  $item     la ressource
     * @param string  $type     le type de ressource
     * 
     * @return array
     */
    public function __invoke($props, $item, $type)
    {
        $view = $this->getView();
        $urls = [];
        $urls["urlSendRapports"] = "ajout-rapports";
        //paramètre l'url de l'autocompletion
        $urls["autoComplete"] = $view->basePath()."/api/items?";
        if($type=='concept'){
            //requête pour récupèrer les positions sémantique sonar de cet item
            $urls["urlDataSonar"] = '../../../api/items?resource_template_id='.$props['rtSonar']->id()
                .'&property[0][joiner]=and&property[0][property]='.$props['oa:hasSource']->id().'&property[0][type]=res&property[0][text]='.$item->id()
                .'&property[1][joiner]=and&property[1][property]='.$props['ma:hasRatingSystem']->id().'&property[1][type]=res&property[1][text]=';
            $urls["autoComplete"] .= "resource_classes_id=".$props['skos:Concept']->id()
                ."&property[0][joiner]=and&property[0][property]=".$props['skos:prefLabel']->id();
            $urls["autoCompleteRedir"] = "explorer-concept?id=";
        }
        if($type=='document'){
            $urls["autoCompleteRedir"] = "explorer-document?id=";
            $urls["autoComplete"] .= "&item_set_id[]=1&item_set_id[]=4"
                . "&property[0][joiner]=and&property[0][property]=".$props['dcterms:title']->id();
            //requête pour récupèrer les positions sémantique sonar de cet item
            $urls["urlDataSonar"] = 'get-rapports?ajax=1&idRt='.$props['rtSonar']->id();
        }
        
        $urls["autoComplete"] .= "&sort_by=title"
                ."&property[0][type]=in&property[0][text]=";        

        return $urls;
    }
}
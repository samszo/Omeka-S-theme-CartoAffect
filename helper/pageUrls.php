<?php
namespace OmekaTheme\Helper;

use Zend\View\Helper\AbstractHelper;

class pageUrls extends AbstractHelper
{
    /**
     * Récupère les url utiles pour une page
     *
     * @param array   $props  les propriétés utiles
     * @return array
     */
    public function __invoke($props)
    {
        $view = $this->getView();
        $urls = [];
        //paramètre l'url de l'autocompletion
        $urls["autoComplete"] = $view->basePath()."/api/items?resource_classes_id=".$props['skos:Concept']->id()
                ."&property[0][joiner]=and&property[0][property]=".$props['skos:prefLabel']->id()
                ."&sort_by=title"
                ."&property[0][type]=in&property[0][text]=";        

        return $urls;
    }
}
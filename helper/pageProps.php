<?php
namespace OmekaTheme\Helper;

use Zend\View\Helper\AbstractHelper;

class pageProps extends AbstractHelper
{
    /**
     * Récupère les propriétés utiles pour une page
     *
     * @return array
     */
    public function __invoke()
    {
        $api = $this->getView()->api();
        //récupère les propriétés utiles
        $props = [];
        $props['skos:prefLabel'] = $api->search('properties', ['term' => 'skos:prefLabel'])->getContent()[0];
        $props['skos:Concept'] = $api->search('resource_classes', ['term' => 'skos:Concept'])->getContent()[0];
        $props['oa:hasSource'] = $api->search('properties', ['term' => 'oa:hasSource'])->getContent()[0];
        $props['ma:hasRatingSystem'] = $api->search('properties', ['term' => 'ma:hasRatingSystem'])->getContent()[0];
        
        

        return $props;
    }
}
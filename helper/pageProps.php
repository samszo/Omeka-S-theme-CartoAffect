<?php
namespace OmekaTheme\Helper;

use Laminas\View\Helper\AbstractHelper;

class pageProps extends AbstractHelper
{
    /**
     * Récupère les propriétés utiles pour une page
     *
     * @return array
     */
    public function __invoke()
    {
        $view = $this->getView();
        $api = $view->api();
        //récupère les propriétés utiles
        $props = [];
        $props['skos:prefLabel'] = $api->search('properties', ['term' => 'skos:prefLabel'])->getContent()[0];
        $props['skos:Concept'] = $api->search('resource_classes', ['term' => 'skos:Concept'])->getContent()[0];
        $props['oa:hasSource'] = $api->search('properties', ['term' => 'oa:hasSource'])->getContent()[0];
        $props['ma:hasRatingSystem'] = $api->search('properties', ['term' => 'ma:hasRatingSystem'])->getContent()[0];
        $props['dcterms:title'] = $api->search('properties', ['term' => 'dcterms:title'])->getContent()[0];
        $props['schema:target'] = $api->search('properties', ['term' => 'schema:target'])->getContent()[0];

        $props['rtRapport'] = $api->read('resource_templates', $view->themeSetting('rt_idRapport'))->getContent();
        $props['rtSonar'] = $api->read('resource_templates', $view->themeSetting('rt_idSonar'))->getContent();
        $props['rtCartoSemantique'] = $api->read('resource_templates', $view->themeSetting('rt_idCartoSemantique'))->getContent();
        
        


        return $props;
    }
}
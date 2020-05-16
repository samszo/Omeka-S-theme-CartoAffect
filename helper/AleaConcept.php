<?php
namespace OmekaTheme\Helper;

use Zend\View\Helper\AbstractHelper;

class AleaConcept extends AbstractHelper
{
    /**
     * Select a random list of concept.
     *
     * @param string $nb Nombre de concepts
     * @return array
     */
    public function __invoke($nb)
    {
        $view = $this->getView();

        $clsConcept = $view->api()->search('resource_classes', ['term' => 'skos:Concept'])->getContent();

        return $view->api()->search('items',[
            'resource_class_id'=>$clsConcept[0]->id(),
            'limit' => $nb,
            'sort_by'=>'random'
        ])->getContent();

    }
}

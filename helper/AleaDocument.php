<?php
namespace OmekaTheme\Helper;

use Zend\View\Helper\AbstractHelper;

class AleaDocument extends AbstractHelper
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
        //TODO:récupérer les identifiants des items set sélectionné dans le site cf. 
        // http://localhost/samszo/omk/admin/site/s/cartoaffect/resources#item-pool-section
        return $view->api()->search('items',['item_set_id'=>[1,4],
            'limit' => $nb,
            'sort_by'=>'random'
        ])->getContent();

    }
}

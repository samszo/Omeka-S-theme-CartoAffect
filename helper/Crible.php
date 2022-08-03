<?php
namespace OmekaTheme\Helper;

use Laminas\View\Helper\AbstractHelper;

class Crible extends AbstractHelper
{
    /**
     * Construction des cribles et des concepts.
     *
     * @param string $id identifiant de la collection des cribles
     * @return array
     */
    public function __invoke($id)
    {
        $view = $this->getView();

        //récupère la liste des cribles
        $inScheme = $view->api()->search('properties', ['term' => 'skos:inScheme'])->getContent()[0];        
        $cribles = $view->api()->search('items',['item_set_id' => $id])->getContent();
        $result = [];
        foreach ($cribles as $c) {
            //récupère la liste des concepts
            $cpts = array();
            $param = array();
            $param['property'][0]['property']= $inScheme->id()."";
            $param['property'][0]['type']='res';
            $param['property'][0]['text']=$c->id();
            $param['sort_by']="jdc:ordreCrible";     
            $concepts = $view->api()->search('items',$param)->getContent();
            foreach ($concepts as $cpt) {
                //TODO: rendre accessible la propriété concepts qui disparait lors du json encode
                //$c->concepts[]=$cpt;
                $cpts[] = $cpt;
            }
            //récupère la définition des cartos
            $cartos = [];
            foreach ($c->value('jdc:hasCribleCarto',['all'=>true]) as $carto) {
                $cartos[]= $carto->valueResource();
            }
            $result[]=['item'=>$c,'concepts'=>$cpts,'cartos'=>$cartos];             
        }
        return $result;

    }
}

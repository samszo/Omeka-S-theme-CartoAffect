<?php
namespace OmekaTheme\Helper;

use Zend\View\Helper\AbstractHelper;

class HistoConcept extends AbstractHelper
{
    /**
     * Récupère l'historique d'utilisation d'un concept
     *
     * @param string $id identifiant du concept
     * @return array
     */
    public function __invoke($id)
    {
        $view = $this->getView();

        $prop = $view->api()->search('properties', ['term' => 'skos:semanticRelation'])->getContent()[0];
        $param = array();
        $param['property'][0]['property']= $prop->id()."";
        $param['property'][0]['type']='res';
        $param['property'][0]['text']=$id; 

        $result = $view->api()->search('items',$param)->getContent();
        $dates = [];

        /*pour une représentation sans label
        foreach ($result as $item) {
            $dC = $item->value('dcterms:created')->asHtml();
            $dS = $item->value('dcterms:dateSubmitted')->asHtml();
            array_push($dates,array('timeRange'=>[$dC, $dS],'val'=>$item->displayTitle())); 
        }
        $timeline = [
            ['group'=>'Périodes',
              'data'=>[
                  ['label'=>'utilisations',
                  'data'=>$dates
                ]
              ]
            ]
        ];
        */                
        
      //pour regrouper par document
      $labels = [];
      foreach ($result as $item) {
          $dS = $item->value('dcterms:dateSubmitted')->asHtml();
          $dC = $item->value('dcterms:created') ? $item->value('dcterms:created')->asHtml() : $dS;
          /*ajoute une temporalité pour voir la marque dans la timeline
          if($dC == $dS){
            $date = new \DateTime($dS);
            $d = $date->modify('+1 day');
            $dS = $date->format('Y-m-d H:i:s');
          }
          */
          $valid = rand(0, 3) > 1 ? 'valide' : '404';
          $label = ['label'=>$item->displayTitle()
            ,'data'=>[['timeRange'=>[$dC, $dS],'val'=>$valid]]
          ];
          array_push($labels,$label); 
      }
      $timeline = [
          ['group'=>'Documents',
            'data'=>$labels
          ]
      ];         


        return $timeline;

    }
}

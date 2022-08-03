<?php
namespace OmekaTheme\Helper;

use Laminas\View\Helper\AbstractHelper;
use \Datetime;

class HistoDocument extends AbstractHelper
{
    var $item;
    var $dataGroup;
    var $api;
    var $props;

    /**
     * Récupère l'historique d'utilisation d'un document
     *
     * @param o:item  $item item du document
     * @param array   $props propriétés utilisées
     * @return array
     */
    public function __invoke($item, $props=[])
    {
      $this->api = $this->getView()->api();
      $this->item = $item;
      $this->props = $props;

      //initialisation des datas de la timeline
      $timeline = [];         

      //initialisation des groupes
      $this->dataGroup['Actants'] = [];//val = dcterms:creator, cito:isCompiledBy, tagging (annotation)
      $this->dataGroup['Tags'] = [];//val = tagging (annotation)
      $this->dataGroup['Parties'] = [];//val = tagging (annotation)

      $this->getActant($this->item);
      $this->getAnnotations($this->item);
      $this->getDocLies($this->item);
            
      //compilation des datas de la timeline
      foreach ($this->dataGroup as $g => $vals) {
        $timeline[]= ['group'=>$g, 'data'=>$vals];
    }

      return $timeline;

    }

    /**
     * Récupère les documents liés
     *
     * @param o:item $item  item du document
     * 
     */
    function getActant($item){
      //récupère les dates pour les actants de l'item
      $this->getTimeLineData($item,'Actants','dcterms:creator', 'dcterms:date');
      $this->getTimeLineData($item,'Actants','bibo:editor', 'dcterms:date');
      $this->getTimeLineData($item,'Actants','cito:isCompiledBy', 'dcterms:dateSubmitted');
    }

    /**
     * Récupère les données pour les actant
     *
     * @param o:item $item  item du document
     * 
     */
    function getDocLies($item){
      if(!isset($this->props['dcterms:isPartOf']))$this->props['dcterms:isPartOf'] = $this->api->search('properties', ['term' => 'dcterms:isPartOf'])->getContent()[0];
      $param = array();
      $param['property'][0]['property']= $this->props['dcterms:isPartOf']->id()."";
      $param['property'][0]['type']='res';
      $param['property'][0]['text']=$this->item->id(); 
      $doclies = $this->api->search('items', $param)->getContent();
      foreach ($doclies as $dl) {
        $this->getActant($dl);
        $this->getAnnotations($dl);
        $this->getTimeLineData($dl,'Parties','dcterms:title','o:created','',$dl->resourceClass()->label());        
      }
    }

    /**
     * Récupère les données pour les actant
     *
     * @param o:item $item  item du document
     * 
     */
    function getAnnotations($item){
      //récupère les annotations de l'item
      if(!isset($this->props['oa:hasSource']))$this->props['oa:hasSource'] = $this->api->search('properties', ['term' => 'oa:hasSource'])->getContent()[0];
      $param = array();
      $param['property'][0]['property']= $this->props['oa:hasSource']->id()."";
      $param['property'][0]['type']='res';
      $param['property'][0]['text']=$this->item->id(); 
      $annos = $this->api->search('annotations', $param)->getContent();
      foreach ($annos as $a) {
        $this->getTimeLineData($a,'Actants','dcterms:creator','o:created','oa:motivatedBy');        
        $tgts = $a->targets();
        foreach ($tgts as $t) {
          $this->getTimeLineData($t,'Tags','rdf:value','o:created',"",$a->value('oa:motivatedBy')->asHtml());        
        }
      }
    }

    /**
     * Récupère les données pour un type de date
     *
     * @param o:item $item  item du document
     * @param string $g     nom du groupe
     * @param string $p     propriété pour le groupe
     * @param string $pDate propriété pour la date
     * @param string $pVal  propriété pour la valeur
     * @param string $val   valeur pour la valeur
     * 
     */
    function getTimeLineData($item, $g, $p, $pDate, $pVal="", $val=""){

      $data = $item->value($p,['all'=>true]);
      if($data){
        foreach ($data as $v) {
          if($pDate=='o:created'){
            $dS = $item->created()->format('Y-m-d H:i:sP');  
          }elseif($pDate=='o:modified'){
            $dS =$item->modified()->format('Y-m-d H:i:sP');  
          }else 
            $dS = $item->value($pDate) ? $item->value($pDate)->asHtml() : $item->value('dcterms:created')->asHtml();
          /*TODO:gérer les dates de suppression d'activité
          */
          //calcul la période pour un affichage plus lisible
          //suivant la date du jour
          $date = new DateTime();
          $dF = $date->format('Y-m-d H:i:s');

          $rv = $v->valueResource();
          $class = $item->resourceClass() ? $item->resourceClass()->label() : "item";
          if ($rv) {
            $label = ['label'=>$rv->displayTitle(),'o:id'=>$rv->id(),'class'=>$class];
          }else{
            $label = ['label'=>$v->asHtml(),'o:id'=>$item->id(),'class'=>$class];
          }        
          $label['data']=[['timeRange'=>[$dS, $dF]
            ,'val'=> $pVal ? $item->value($pVal)->asHtml() : ($val ? $val : $p)]];
          $this->dataGroup[$g][]=$label; 
        }  
      }
    }
}

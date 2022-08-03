<?php
namespace OmekaTheme\Helper;

use Laminas\View\Helper\AbstractHelper;

class ReseauConcept extends AbstractHelper
{
    var $rs;
    var $doublons;
    var $nivMax=2;//profondeur de la recherche
    //propriétés des relations avec leur direction
    var $relations=[
        ['term'=>'skos:related','dir'=>'='],
        ['term'=>'skos:narrower','dir'=>'<'],
        ['term'=>'skos:broader','dir'=>'>']
    ];
    var $querySemanticPositionSource;
    var $querySemanticPositionTarget;
    var $deb;
    var $fin;
    var $api;

    /**
     * Récupère le réseau skos d'un concept
     *
     * @param o:item                $item       item omeka
     * @param o:item                $actant     item omeka
     * @param o:resourceTemplate    $rtRapport  ressource template contenant la liste des rapports possible
     * @param int                   $nivMax     pronfondeur du reseau
     * 
     * @return array
     */
    public function __invoke($item,$actant,$rtRapport,$nivMax=false)
    {
        $this->api = $this->getView()->api();
        if($nivMax)$this->nivMax=$nivMax;

        //récupère la définition des rapports
        $this->relations = [];
        $pIdLink = false;
        $pIdCreator = false;
        foreach ($rtRapport->resourceTemplateProperties() as $p) {
                $oP = $p->property();
                if($oP->term()=='oa:hasSource')$pIdLink = $oP->id();
                if($oP->term()=='dcterms:creator')$pIdCreator = $oP->id();
                //on ne prend pas toutes les propriétés
                if($oP->id()!=1 && $oP->term()!='oa:hasSource' 
                        && $oP->term()!='dcterms:isReferencedBy' && $oP->term()!='dcterms:creator'){
                        $dir = $p->alternateComment();
                        $this->relations[] = ['term'=>$oP->term(),'id'=>$oP->id(),'dir'=>$dir];
                }
        }

        //requête pour récupèrer le réseau d'un concept dont il est la source
        $this->querySemanticPositionSource = array();
        $this->querySemanticPositionSource['resource_template_id']= $rtRapport->id()."";
        $this->querySemanticPositionSource['property'][0]['property']= $pIdLink."";
        $this->querySemanticPositionSource['property'][0]['type']='res';
        $this->querySemanticPositionSource['property'][0]['text']=$item->id(); 
        $this->querySemanticPositionSource['property'][0]['joiner']="and"; 

        //POUR UN ACTANT DONNÉ
        if(isset($actant)){
            $this->querySemanticPositionSource['property'][1]['property']= $pIdCreator."";
            $this->querySemanticPositionSource['property'][1]['type']='res';
            $this->querySemanticPositionSource['property'][1]['text']=$actant->id(); 
            $this->querySemanticPositionSource['property'][1]['joiner']="and";     
        }
        //requête pour récupèrer le réseau d'un concept dont il est la destination
        $this->querySemanticPositionTarget = $this->querySemanticPositionSource;
        unset($this->querySemanticPositionTarget['property'][0]['property']);
        $this->querySemanticPositionTarget['property'][2]['property']= $pIdLink."";
        $this->querySemanticPositionTarget['property'][2]['type']='nres';
        $this->querySemanticPositionTarget['property'][2]['text']=$item->id(); 
        $this->querySemanticPositionTarget['property'][2]['joiner']="and"; 
        $first = true;
        foreach ($this->relations as $r) {
            $prop = ['property'=>$r['id'],'type'=>'res','text'=>$item->id(),'joiner'=> $first ? "and" : "or"];
            $this->querySemanticPositionTarget['property'][]=$prop;
            $first=false;
        }
        

        //récupère le réseau d'un concept
        $this->getConcept($item, 0);

        //ajoute le premier élément de la liste des rapports
        array_unshift($this->relations,['term'=>'Choisissez...','id'=>0]);

        return ['cptRapports'=>$this->relations, 'dataReseauConcept'=>$this->rs];

    }

	/**
     * Ajout un concept au résultat
     *
     * @param  o:item   $item
     * @param  int      $niv
     *
     * @return int
     */
    function getConcept($item, $niv){

		/*construction de la réponse pour un affichage réseau
		 * {"nodes":[{"name":"Agricultural 'waste'"},...],
		 * "links":[{"source":0,"target":1,"value":124.729},...]
		 * }
		 */
		if(!$this->rs){
            //ajoute les noeuds "plus large" et "plus précis" 
            $this->deb = ["name"=>"Plus générique","uri"=>"","id"=>0,"type"=>"deb"];
            $this->fin = ["name"=>"Plus spécifique","uri"=>"","id"=>1,"type"=>"fin"];
            $this->rs = [
                "nodes" => [$this->deb,$this->fin], 
                "links" => []
            ];
            $this->doublons = array();
        }

        //creation des noeud de concepts
        $node = $this->ajoutReseau($item, $niv);
        $this->querySemanticPositionSource['property'][0]['text']=$node['o:item']->id();
        $rs = $this->api->search('items',$this->querySemanticPositionSource)->getContent();
        foreach ($rs as $r) {
            $this->ajoutReseau($r,$niv,$node);
        }

        if($niv==0){
            //création des noeuds dont la source est la destination
            $rs = $this->api->search('items',$this->querySemanticPositionTarget)->getContent();
            foreach ($rs as $r) {
                $this->ajoutReseau($r,$niv+1,$node,true);
            } 
            //   
            //création des relations quand tous les concepts sont créés
            $this->ajoutRelation();
        }
    }

	/**
     * Traitement des relations
     *
     * @param  o:item   $item
     * @param  int      $niv
     * @param  array    $itemBase
     *
     * @return array
     */
    function ajoutRelation(){

        foreach ($this->rs['nodes'] as $n) {
            if(!isset($n['liens']))continue;
            foreach ($n['liens'] as $l) {
                $rela = array_filter($this->relations, function($rlt) use ($l){
                    return $rlt['term'] == $l['term'];
                });
                foreach ($rela as $r) {
                    //ajoute les relations
                    if($r['dir']=='def') $this->rs['nodes'][$this->doublons[$n["name"]]['id']][$r['term']][] = $n;
                    if($r['dir']=='=') $this->rs['nodes'][$this->doublons[$n["name"]]['id']][$r['term']][] = $n;			
                    //if($r['dir']=='-')$this->ajoutLien($s, $n);			
                    if($r['dir']=='>'){
                        $this->ajoutLien($this->rs['nodes'][$l['id']], $n);
                        /*
                        if(!$l['target'])$this->ajoutLien($this->rs['nodes'][$l['id']], $n);
                        else $this->ajoutLien($n, $this->rs['nodes'][$l['id']]);			
                        */
                    }
                    if($r['dir']=='<'){
                        $this->ajoutLien($n, $this->rs['nodes'][$l['id']]);
                        /*
                        if(!$l['target'])$this->ajoutLien($n, $this->rs['nodes'][$l['id']]);			
                        else $this->ajoutLien($this->rs['nodes'][$l['id']],$n);	
                        */
                    }
                }
            }
        }
        //ajoute les liens manquants
        $liensManquants = [];
        foreach ($this->rs['nodes'] as $n) {
            if(!isset($n['liens']))continue;
            //vérifie s'il faut relier au début
            $rela = array_filter($this->rs['links'], function($l) use ($n){
                return $l['target'] == $n['id'];
            });
            if(count($rela)==0)$this->ajoutLien($this->deb, $n);
            //vérifie s'il faut relier à la fin
            $rela = array_filter($this->rs['links'], function($l) use ($n){
                return $l['source'] == $n['id'];
            });
            if(count($rela)==0)$this->ajoutLien($n, $this->fin);
        }


    }

	/**
     * Traitement du réseau de concept
     *
     * @param  o:item   $item
     * @param  int      $niv
     * @param  array    $itemBase
     * @param  boolean  $target
     *
     * @return array
     */
    function ajoutReseau($item, $niv, $itemBase=false, $target=false){
        //création du noeud de base
        if($itemBase)$s=$itemBase;
        else $s = $this->ajoutNoeud(array("name"=>$item->displayTitle(),"uri"=>$item->adminUrl(),"o:item"=>$item,"type"=>"concept","liens"=>[]));
        foreach ($this->relations as $r) {
            if($item->value($r['term'])){
                foreach ($item->value($r['term'],['all'=>true]) as $cpt) {
                    $arrN = false;
                    switch ($cpt->type()) {
                        case "resource":
                            //récupère l'item
                            $oN = $this->api->read('items', $cpt->valueResource()->id())->getContent();
                            $arrN = array("name"=>$oN->displayTitle(),"uri"=>$oN->adminUrl(),"o:item"=>$oN,"type"=>$r['term'],"liens"=>[]);                            
                            //ajoute le noeud
                            $n = $this->ajoutNoeud($arrN);
                            //récupère le réseau du noeud
                            if($niv <= $this->nivMax)$this->getConcept($oN,$niv+1);
                            if($target){
                                $s = $n;
                                $n = $s;
                            } 

                            break;
                        case "uri":                            
                            $arrN = array("name"=>$cpt->asHtml(),"uri"=>$cpt->uri(),"type"=>$r['term']);                            
                            //ajoute le noeud
                            $n = $this->ajoutNoeud($arrN);
                            break;
                        default:
                            $arrN = array("name"=>$cpt->asHtml(),"type"=>$r['term']);                            
                            //ajoute le noeud
                            $n = $this->ajoutNoeud($arrN);
                            break;
                    }
                    //ajoute le lien
                    $this->rs['nodes'][$s['id']]['liens'][]=['term'=>$r['term'],'id'=>$n['id'],'target'=>$target];

                }
            }
        }

        return $s;		

    }


	/**
     * Ajout un noeud au résultat
     *
     * @param  array $arr
     *
     * @return int
     */
    function ajoutNoeud($arr){
		if(!isset($this->doublons[$arr["name"]])){
            $arr["id"] = count($this->rs['nodes']);				
            $this->rs['nodes'][] = $arr;
            $this->doublons[$arr["name"]] = ['nb'=>1,'id'=>$arr["id"]];    
		}else{
            $arr["id"] =  $this->doublons[$arr["name"]]['id'];				
            $this->doublons[$arr["name"]]['nb'] ++;
        }
		return $arr;		
    }

	/**
     * Ajout un lien au résultat
     *
     * @param  int $s
     * @param  int $t
     * @param  int $v
     *
     * @return int
     */
    function ajoutLien($s, $t, $v=1){
        if($s['id']==$t['id'])return;
		if(!isset($this->doublons[$s['id']."_".$t['id']]) && !isset($this->doublons[$t['id']."_".$s['id']])){
            $this->rs['links'][] = array("source"=>$s['id'],"target"=>$t['id']
                , 'names'=>[$s['type'].'='.$s['name'],$t['type'].'='.$t['name']],"value"=>$v);
			$this->doublons[$s['id']."_".$t['id']] = count($this->rs['links'])-1;						
        }
        if(isset($this->doublons[$s['id']."_".$t['id']])){
            $this->rs['links'][$this->doublons[$s['id']."_".$t['id']]]["value"]++;
            return $this->doublons[$s['id']."_".$t['id']];		
        }
        if(isset($this->doublons[$t['id']."_".$s['id']])){
            $this->rs['links'][$this->doublons[$t['id']."_".$s['id']]]["value"]++;
            return $this->doublons[$t['id']."_".$s['id']];		
        }
    }
    

}

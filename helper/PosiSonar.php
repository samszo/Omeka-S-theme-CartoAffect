<?php
namespace OmekaTheme\Helper;

use Zend\View\Helper\AbstractHelper;

class PosiSonar extends AbstractHelper
{

    /**
     * Récupère le position sonar pour un concept et un crible
     *
     * @param integer               $idDoc      
     * @param o:item                $idCrible
     * 
     * @return array
     */
    public function __invoke($idDoc, $idCrible)
    {
        $this->api = $this->getView()->api();


        //requête pour récupèrer les positions pour un crible et un document      
        $pHasDoc = $this->api->search('properties', ['term' => 'jdc:hasDoc'])->getContent()[0];
        $pHasRatingSystem = $this->api->search('properties', ['term' => 'ma:hasRatingSystem'])->getContent()[0];

        $query = array();
        $query['property'][0]['property']= $pHasDoc->id();
        $query['property'][0]['type']='res';
        $query['property'][0]['text']=$idDoc; 
        $query['property'][0]['joiner']="and"; 
        $query['property'][1]['property']= $pHasRatingSystem->id();
        $query['property'][1]['type']='res';
        $query['property'][1]['text']=$idCrible; 
        $query['property'][1]['joiner']="and"; 

        $result = $this->api->search('items', $query)->getContent();

        return $result;

    }
}

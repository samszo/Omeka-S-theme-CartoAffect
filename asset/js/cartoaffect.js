var flux, searchTerm, mHaut, cptRapports=[], cartoExploSkos, oPolarclock, oCartoaxes;


function setAutoComplete(id){

  $("#"+id)
          // don't navigate away from the field on tab when selecting an item
          .on("keydown", function (event) {
                  if (event.keyCode === $.ui.keyCode.TAB &&
                          $(this).autocomplete("instance").menu.active) {
                          event.preventDefault();
                  }
          })
          .autocomplete({
                  minLength: 3,
                  source: function (request, response) {
                          searchTerm = request.term;
                          d3.select('#spin-'+id).style('display', 'inline-block');
                          d3.select('#icon-'+id).style('display', 'none');
                          $.ajax({
                                  url: urlAutoComplete + searchTerm,
                                  dataType: "json",
                                  success: function (data) {
                                          d3.select('#spin-'+id).style('display', 'none');
                                          d3.select('#icon-'+id).style('display', 'inline-block');
                                          let rs = data.map(d => {
                                                  return {
                                                          'value': d['o:title'],
                                                          'id': d['o:id']
                                                  }
                                          })
                                          response(rs);
                                  }
                          })
                  },
                  focus: function () {
                          // prevent value inserted on focus
                          return false;
                  },
                  select: function (event, ui) {
                          if(id=='tags') drawRapport(ui.item);
                          if(id=='findTags') document.location.href=urlAutoCompleteRedir+ui.item.value;
                          if(id=='findDocs') document.location.href=urlAutoCompleteRedir+ui.item.id;
                          this.value = "";
                          return false;
                  },
          }).data("ui-autocomplete")._renderItem = function (ul, item) {
                  const regex = new RegExp(searchTerm, "gi");
                  let html = '<a>' + item.label.replace(regex, '<span class="findTerm">' + searchTerm + '</span>') + '</a>';
                  return $("<li></li>")
                          .data("item.autocomplete", item)
                          .append(html)
                          .appendTo(ul);
          };
}

        //fonctions des icons
        function ajoutSkos(d){
                console.log(d);
                //met à jour le type de rapports
                d3.select('#ajoutConceptLabel').text("Relier « "+item['o:title']+" » à d'autres concepts");
                //supprime les précends ajouts
                d3.selectAll('.addCptRapports').remove();
                cptRapports = [];
                //affiche la fenêtre d'ajout
                $('#modalAjoutSkos').modal('show');
        }
        function sendrapports() {
                let cancel = false;
                cptRapports.forEach((r,i) => {
                        if(!r.rapport){
                                cancel = true;
                                $('#alertChoixRapport'+i).show();                      
                        }  
                });
                if(cancel)return;
                /*regroupe les rapports par type
                let rapports = d3.nest()
                        .key(function(d){
                                return d.rapport;
                        }).entries(cptRapports);         
                */       
               $('#modWait').modal('show');
               $.ajax({
                        type: 'POST',
                        dataType: 'json',
                        url: urlSendRapports,
                        data: {
                                'ajax':true,
                                'id': item['o:id'],
                                'idRt': rtRapport,
                                'rapports': cptRapports
                        }
                }).done(function(data) {
                        $('#modalAjoutSkos').modal('hide');
                        cartoExploSkos.draw(data.dataReseauConcept);
                })
                .fail(function(e) {
                        throw new Error("Sauvegarde imposible : " + e);
                })
                .always(function() {
                        $('#modWait').modal('hide');
                });
        }


    function drawRapport(item){
        cptRapports.push(item);                                
        let colGen = d3.select("#contAddCptRapports")
                .selectAll('.addCptRapports').data(cptRapports).enter()
                        .append('div').attr('class','row addCptRapports')
                        .append('div').attr('class','col-12');
        r = colGen.append('div')
                .attr('id',(d,i) => 'rChoixRapport'+i)
                .attr('class','row')
        let colName = r.append('div').attr('class','col-5');
        colName.append('span')
                //.style("margin-left","3px")
                .text(d=>d.value)
        let colButton = r.append('div').attr('class','col-1');
        colButton.append('span')
                .style("cursor","pointer")
                .on('click',
                        (d,i) => delRapport(d,i))
                .append('i')
                .attr('class','far fa-trash-alt');
        let colChoix = r.append('div').attr('class','col-6');
        colChoix.append('select')
                .attr('id',d=>'sltCptRapports'+d.id)
                .attr('class','form-control')
                .on('change',(d,i)=>choixRapport(d,i))
                .selectAll('option').data(rapports).enter()
                .append('option').attr('value',(o,i)=>i).text(o=>o.term);

        let rAlert = colGen.append('div')
                .attr('id',(d,i) => 'rChoixRapportA'+i)
                .attr('class','row');
        let colAlert =rAlert.append('div').attr('class','col-12');
        colAlert.append('div')
                .attr('class',"alert alert-danger alertRapport")
                .attr('id',(d,i)=>"alertChoixRapport"+i)
                .attr('role','alert')
                .text('Veuillez choisir/supprimer un rapport...');
        colAlert.append('div')
                .attr('class',"alert alert-danger alertRapport")
                .attr('id',(d,i)=>"alertExisteRapport"+i)
                .attr('role','alert')
                .text('Ce rapport existe déjà : supprimer ou remplacer');
        colAlert.append('div')
                .attr('class',"alert alert-warning alertRapport")
                .attr('id',(d,i)=>"alertCoherenceRapport"+i)
                .attr('role','alert')
                .text('Le rapport existant sera modifié...');                                        
        colAlert.append('div')
                .attr('class',"alert alert-success alertRapport")
                .attr('id',(d,i)=>"alertAjoutRapport"+i)
                .attr('role','alert')
                .text('Le rapport sera ajouté...');                                        
        colAlert.append('div').attr('class',"dropdown-divider");
        }
    function delRapport(d,i){
        cptRapports.splice(i, 1);            
        d3.select('#rChoixRapportA'+i).remove();
        d3.select('#rChoixRapport'+i).remove();
    }
    function choixRapport(d, i){
        let v = d3.select('#sltCptRapports'+d.id).property('value');
        let r = rapports[v].term;
        d3.select('#modalBtnValiderRapports')
                .attr('class','btn btn-dark disabled')
                .attr('aria-disabled','true');
        if(existeRapport(d, r)){
                $('#alertCoherenceRapport'+i).hide();
                $('#alertAjoutRapport'+i).hide();
                $('#alertExisteRapport'+i).show();
        }else if(!coherenceRapport(d, r)){
                $('#alertCoherenceRapport'+i).show();
                $('#alertAjoutRapport'+i).hide();
                $('#alertExisteRapport'+i).hide();
                d3.select('#modalBtnValiderRapports')
                        .attr('class','btn btn-dark')
                        .attr('aria-disabled','false');
                d.rapport = {'term':r,'action':'replace'};
        }else{
                d.rapport = {'term':r,'action':'append'};
                $('#alertExisteRapport'+i).hide();
                $('#alertCoherenceRapport'+i).hide();
                $('#alertAjoutRapport'+i).show();
                d3.select('#modalBtnValiderRapports')
                        .attr('class','btn btn-dark')
                        .attr('aria-disabled','false');
        }
        $('#alertChoixRapport'+i).hide();            
    }
    function existeRapport(d, r){
        if(!cartoExploSkos.noeudBase.liens.length)return false;

        let noeuds = cartoExploSkos.data.nodes.filter(
                n => n["o:item"] && n['o:item']["o:id"]==d.id);
        if(!noeuds.length) return false;

        let liens = cartoExploSkos.noeudBase.liens.filter(
                        l => l["term"]==r && l["id"]==noeuds[0]["id"]);
        return liens.length;       
    }
    function coherenceRapport(d, r){
        if(!cartoExploSkos.noeudBase.liens.length)return true;

        let noeuds = cartoExploSkos.data.nodes.filter(
                n => n["o:item"] && n['o:item']["o:id"]==d.id);
        if(!noeuds.length) return true;

        let liens = cartoExploSkos.noeudBase.liens.filter(
                        l => l["id"]==noeuds[0]["id"]);
        return liens.length == 0;       
    }

    function savePosi(d){
        console.log(d);

        d.idCarto = selectCarto['o:id'];
        d.idCrible = selectCrible.item['o:id'];
    let omk = {
        'dcterms:title':flux+' _ '+item['o:title']+' : '+d.degrad.date
        ,'dcterms:isReferencedBy':item['o:id']+'_'+d.idCarto+'_'+d.idCrible+' : '+d.degrad.date+' : '+actant['o:id']
        ,'jdc:creationDate':d.degrad.date
        ,'ma:hasCreator':[{'type':'resource','value':actant['o:id']}]
        ,'jdc:hasActant':[{'type':'resource','value':actant['o:id']}]
        ,'ma:hasRating':[]
        ,'ma:isRatingOf':[]
        ,'ma:ratingScaleMax':oCartoaxes.xMax
        ,'ma:ratingScaleMin':oCartoaxes.xMin
        ,'ma:hasRatingSystem':[{'type':'resource','value':d.idCrible}]
        ,'ma:locationLatitude':geo.coords.latitude
        ,'ma:locationLongitude':geo.coords.longitude
        ,'oa:hasSource':[{'type':'resource','value':d.id}]
        ,'jdc:degradName':d.degrad.nom
        ,'jdc:degradColors':d.degrad.colors
        ,'jdc:hasDoc':[{'type':'resource','value':d.id}]
        ,'jdc:distanceCenter':d.distance
        ,'jdc:hasConcept':[]
        ,'jdc:distanceConcept':[]
        ,'jdc:x':d.x
        ,'jdc:y':d.y
        ,'jdc:xRatingValue':d.numX
        ,'jdc:yRatingValue':d.numY
    }
    d.crible.forEach(function(c){
        omk['ma:hasRating'].push(c.p);
        omk['ma:isRatingOf'].push({'type':'resource','value':c.t.id});
        omk['jdc:hasConcept'].push({'type':'resource','value':c.t.id});
        omk['jdc:distanceConcept'].push(c.p);
    })
    //message pour patienter
    d3.select('#modWaitLbl').text("Merci de patienter...");                
    d3.select('#waitError').style('display','none');
    d3.select('#waitFermer').style('display','none');
    d3.select('#waitloader').style('display','block');    
    $('#modWait').modal('show');

    $.ajax({
        type: 'POST',
        dataType: 'json',
        url: urlSendRapports,
        data: {
                'ajax':true,
                'id': item['o:id'],
                'idRt': rtSonar,
                'rapports': omk
        }
        }).done(function(data) {
                $('#modalAjoutSkos').modal('hide');
                cartoExploSkos.draw(data.dataReseauConcept);
                $('#modWait').modal('hide');
        })
        .fail(function(e) {
                //throw new Error("Sauvegarde imposible : " + e);
                d3.select('#modWaitLbl').text("Sauvegarde imposible");                
                d3.select('#waitError').style('display','block').html(e.responseText);
                d3.select('#waitFermer').style('display','block');
                d3.select('#waitloader').style('display','none');    
                 
        });

}

function getTimelineLabelData(l,g){
        let dataG = dataTimeline.filter(grp=>grp.group==g)[0];
        let dataL = dataG.data.filter(lgn=>lgn.label==l)[0];
        return dataL;
}

function timelineShowHide(){
        let tld = d3.select('#tlGraph svg')
        if(tld.style('display')=='none'){
                tld.style('display','block')
                d3.select('#btnShowHideTimeline').attr('class','btn btn-ligth btn-sm');                        
        }else{
                tld.style('display','none');
                d3.select('#btnShowHideTimeline').attr('class','btn btn-dark btn-sm');                        
        } 
}


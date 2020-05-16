/*!
  * exploskos v0.0.1 
  * Copyright 2020 Samuel Szoniecky
  * Licensed under MIT (https://github.com/twbs/bootstrap/blob/master/LICENSE)
  */
 class exploskos {
    constructor(params) {
        var me = this;
        this.cont = d3.select("#"+params.idCont);
        this.margin = params.margin ? params.margin : {top: 10, right: 10, bottom: 10, left: 0},            
        this.data = params.data ? params.data : [];
        this.dataUrl = params.dataUrl ? params.dataUrl : false;
        this.dataType = params.dataType ? params.dataType : 'json';
        this.width = params.width ? params.width : 0,
        this.height = params.height ? params.height : 0,
        this.colorFond = params.colorFond ? params.colorFond : "transparent";
        this.fctCallBackInit = params.fctCallBackInit ? params.fctCallBackInit : false;
        this.fctAjoutSkos = params.fctAjoutSkos ? params.fctAjoutSkos : false;
        this.idItem = params.idItem ? params.idItem : false;
        this.fontSize = params.fontSize ? params.fontSize : 14;
        this.noeudBase;

        var svg, container, color, colorSkos, tooltip, graph, edgeColor = "path";            


        this.init = function () {
        
            let nodeCont = this.cont.node();
            if(!me.width)
                me.width = nodeCont.clientWidth - me.margin.left - me.margin.right;
            if(!me.height)
                me.height = window.innerHeight - nodeCont.offsetTop - me.margin.top - me.margin.bottom;

            color = d3.scaleSequential().domain([me.width, 0]).interpolator(d3['interpolateInferno']),
            colorSkos = d3.scaleOrdinal(d3.schemeCategory10);

            svg = this.cont.append("svg")
                .attr("width", me.width+'px')
                .attr("height", me.height+'px')
                .style("background-color","black");                
            container = svg.append("g");
            
            svg.call(
                d3.zoom()
                    .scaleExtent([.1, 4])
                    .on("zoom", function() { container.attr("transform", d3.event.transform); })
            );

            graph = d3.sankey()
                .nodeSort(null)
                .linkSort(null)
                .nodeWidth(10)
                .nodePadding(20)
                .extent([[0, 5], [me.width, me.height - 5]])

            me.draw(false);

            //ajout du tooltip
            tooltip = d3.select("body").append("div")
                .attr("class", "tooltip")
                .style('position','absolute')
                .style('padding','4px')
                .style('background-color','black')
                .style('color','white')
                .style('pointer-events','none');
                
        }

        this.hide = function(){
          svg.attr('visibility',"hidden");
        }
        this.show = function(){
          svg.attr('visibility',"visible");
        }
        this.draw = function(data){
            if(data)me.data=data;
            const {nodes, links} = graph({
                nodes: me.data.nodes,
                links: me.data.links
              });
            me.noeudBase = nodes[2];
            //initialise le graph
            container.selectAll("g").remove();

            const link = container.append("g")
                .attr("fill", "none")
                .attr("stroke-opacity", 0.5)
              .selectAll("g")
              .data(links)
              .join("g")
                .style("mix-blend-mode", "multiply");
                    
            //ajout dégradé
            const gradient = link.append("linearGradient")
              .attr("id", d => (
                d.uid = "gdtLink"+d.index
                ))
              .attr("gradientUnits", "userSpaceOnUse")
              .attr("x1", d => d.source.x1)
              .attr("x2", d => d.target.x0);          
            
            gradient.append("stop")
              .attr("offset", "0%")
              .attr("stop-color", d => 
                //color(d.source.id)
                color(d.source.x0)
              );
    
            gradient.append("stop")
              .attr("offset", "100%")
              .attr("stop-color", d => 
              //color(d.target.id)
              color(d.target.x0)
            );
                
            link.append("path")
                .attr("d", d3.sankeyLinkHorizontal())
                .attr("stroke", d => 
                    edgeColor === "none" ? "#aaa"
                    : edgeColor === "path" ? "url(#"+d.uid+")" 
                    : edgeColor === "input" ? color(d.source.x0) 
                    : color(d.target.x0))
                .attr("stroke-width", 
                  d => Math.max(1, d.width)
                  );
          
            link.append("title")
              .text(d => `${d.source.name} → ${d.target.name}`);

            //ajoute le noeud
            container.append("g")
              .selectAll("rect")
              .data(nodes)
              .join("rect")
                .attr("x", d => d.x0)
                .attr("y", d => d.y0)
                .attr("fill", d => d.type=='deb' || d.type=='fin' ? color(d.x0) : colorSkos(d.type))
                .attr("height", d => d.y1 - d.y0)
                .attr("width", d => d.x1 - d.x0)
              .append("title")
                .text(d => {
                  let n = d.type=='deb' || d.type=='fin' ? d.name : "";
                  if(!n){
                    d.sourceLinks.forEach(l => n+= d.type+'='+d.name)                    
                  }
                });
              
            //ajoute le texte
            var txts = container.append("g")
              .attr("font-family", "sans-serif")
              .selectAll("text")
              .data(nodes)
              .join("text")
                .attr("x", d => d.x0 < me.width / 2 ? d.x1 + 6 : d.x0 - 6)
                .attr("y", d => (d.y1 + d.y0) / 2)
                .attr("dy", "0.35em")
                .attr("text-anchor", d => d.x0 < me.width / 2 ? "start" : "end")
                .attr("font-size", d => d.type == "concept" ? me.fontSize*2 : me.fontSize)
                .style("fill", d => d.type == "concept" ? "white" : "white")
                .style("cursor","zoom-in")
                .text(d => d.name)
                .on('click',d => 
                  document.location.href="editer-concept?concept="+d.name);
            /*
            txts.append("tspan")
                .attr("fill-opacity", 0.7)
                .text(d => 
                  `${d.value.toLocaleString()}`);
            */

            /*ajoute les boutons d'actions
            var icons = svg.selectAll(".iconButton")
              .data(nodes)
              .join("g")
                .attr('class','iconButton')
                //.attr("x", d => d.x0 < me.width / 2 ? d.x1 + 6 : d.x0 - 6)
                //.attr("y", d => (d.y1 + d.y0) / 2)
                .attr("transform",d => "translate("
                    +(d.x0 < me.width / 2 ? d.x1 + 6 : d.x0 - 50)
                    +" "+ (10 + (d.y1 + d.y0) / 2)+ ") scale(0.05)")
                .html('<i class="fas fa-search-plus"></i>')
                .on("click",me.fctAjoutSkos);
            */
          }
        
        function fctExecute(p) {
            switch (p.data.fct) {
                case 'showRoueEmotions':
                  me.hide();
                  if(!objEW)
                    objEW = new emotionswheel({'idCont':me.cont.attr('id'),'width':me.width,'height':me.width});
                  else
                    objEW.show();
                  break;
                default:
                  console.log(p);
            }            
        }

        function getTooltip(d){
            //calcule les élément du tooltip
            //if(totalTemps[dt.temps]==0)totalTemps[dt.temps] = 0.1;			
            var s = me.data.sujets[d.keys[0]];	    	
            var o = me.data.objets[d.keys[1]];	    	
            var p = me.data.predicats[d.keys[2]];	    	
            tooltip.html("<h3>Sujet : "+s.name+"</h3>"
                +"<h3>Objet : "+o.name+"</h3>"
                +"<h3>Predicat : "+p.name+"</h3>"
                )
                .style("left", (d3.event.pageX + 12) + "px")
                .style("top", (d3.event.pageY - 28) + "px");
	    }

        if(me.dataUrl){
            if(me.dataType=='json'){
                d3.json(me.dataUrl).then(function(data) {
                    me.data = data;
                    me.init();
                });        
            }
            if(me.dataType=='csv'){
                d3.csv(me.dataUrl).then(function(data) {
                    //TODO:formatage des données csv
                    me.data = data;
                    me.init();
                });
                
            }
        }else{
            me.init();
        }


    }
}

  


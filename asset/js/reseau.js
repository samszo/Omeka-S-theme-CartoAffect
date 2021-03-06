class reseau {
    constructor(params) {
        var me = this;
        this.cont = d3.select("#"+params.idCont);
        this.width = params.width ? params.width : 400;
        this.height = params.height ? params.height : 400;
        this.dataUrl = params.dataUrl ? params.dataUrl : false;
        this.fontSize = params.fontSize ? params.fontSize : 12;
        this.fctClickNode = params.fctClickNode ? params.fctClickNode : false;
        this.data = params.data ? params.data : 	{
            "nodes": [
                {
                "id": "Myriel",
                "group": 1
                },
                {
                "id": "Napoleon",
                "group": 1
                },
            ],
            "links": [
                {
                "source": "Napoleon",
                "target": "Myriel",
                "value": 1
                },
                {
                "source": "Mlle.Baptistine",
                "target": "Myriel",
                "value": 8
                },
            ]
        }; 

        var svg, container, link, node, labelNode, color, label
        ,adjlist, labelLayout, graphLayout
        ,objEW, lgdSize, legende;            

        this.init = function () {
            
                color = d3.scaleOrdinal(d3.schemeCategory10);
                lgdSize = {x: 0, y:0, width: me.width-15, height: me.fontSize*3};

                label = {
                    'nodes': [],
                    'links': []
                };
            
                me.data.nodes.forEach(function(d, i) {
                    label.nodes.push({node: d});
                    label.nodes.push({node: d});
                    label.links.push({
                        source: i * 2,
                        target: i * 2 + 1
                    });
                });
            
                labelLayout = d3.forceSimulation(label.nodes)
                    .force("charge", d3.forceManyBody().strength(-50))
                    .force("link", d3.forceLink(label.links).distance(0).strength(2));
            
                let heightForce = (me.height / 2);// + (lgdSize.height*2);
                graphLayout = d3.forceSimulation(me.data.nodes)
                    .force("charge", d3.forceManyBody().strength(-3000))
                    .force("center", d3.forceCenter(me.width / 2, heightForce))
                    .force("x", d3.forceX(me.width / 2).strength(1))
                    .force("y", d3.forceY(heightForce).strength(1))
                    .force("link", d3.forceLink(me.data.links).id(function(d) {return d.id; }).distance(50).strength(1))
                    .on("tick", ticked);
            
                adjlist = [];
            
                me.data.links.forEach(function(d) {
                    adjlist[d.source.index + "-" + d.target.index] = true;
                    adjlist[d.target.index + "-" + d.source.index] = true;
                });


                svg = this.cont.append("svg").attr("width", me.width+'px').attr("height", me.height+'px');
                //ajoute la légende
                legende = svg.append('g').attr('id','reseauLegende')
                legende.append('rect')
                    .attr("x", 0)
                    .attr("y", 0)
                    .attr("fill", 'black')
                    .attr("height", lgdSize.height*2)
                    .attr("width", lgdSize.width)

                //création du conteneur pour le graph
                container = svg.append("g");                
                svg.call(
                    d3.zoom()
                        .scaleExtent([.1, 4])
                        .on("zoom", function() { container.attr("transform", d3.event.transform); })
                );
                
                link = container.append("g").attr("class", "links")
                    .selectAll("line")
                    .data(me.data.links)
                    .enter()
                    .append("line")
                    .attr("stroke", d => d.group ? color(d.group) : "#aaa")
                    .attr("stroke-width", "3px");
                
                node = container.append("g").attr("class", "nodes")
                    .selectAll("g")
                    .data(me.data.nodes)
                    .enter()
                    .append("circle")
                    .attr("r", function(d) { 
                        return d.size ? 5*d.size : 5; 
                    })
                    .style("cursor","zoom-in")
                    .attr("fill", function(d) { return color(d.group); });
                
                node.on("mouseover", focus)
                    .on("mouseout", unfocus)
                    .on("click", d => me.fctClickNode ? me.fctClickNode(d) : console.log(d));
                
                node.call(
                    d3.drag()
                        .on("start", dragstarted)
                        .on("drag", dragged)
                        .on("end", dragended)
                );
                
                labelNode = container.append("g").attr("class", "labelNodes")
                    .selectAll("text")
                    .data(label.nodes)
                    .enter()
                    .append("text")
                    .text(function(d, i) { 
                        let txt = d.node.title ? d.node.title : d.node.id;
                        return i % 2 == 0 ? "" : txt; 
                    })
                    .style("fill", "white")
                    .style("font-family", "Arial")
                    .style("font-size", me.fontSize)
                    .style("pointer-events", "none"); // to prevent mouseover/drag capture
                
                node.on("mouseover", focus).on("mouseout", unfocus);


            //ajoute la légende
            addLegende('noeuds', me.data.nodes,{y:0});
            addLegende('liens', me.data.links,{y:lgdSize.height/2});
         
                           
        }

        function addLegende(nom, data,ori){
            let types = d3.nest()
              .key(function(d){
                  return d.group;
              }).entries(data).map(e => e.key);
            types.unshift(nom+' : ');              
            let scaleLgdHori = d3
              .scaleBand()
              //.paddingInner(0.2)
              .domain(types)
              .range([lgdSize.x, lgdSize.width]);            
            let itemsLgd = legende.selectAll('.ilgd'+nom).data(types).enter().append('g').attr('class','ilgd'+nom);
            itemsLgd.append('rect')         
              .attr("x", d => scaleLgdHori(d))
              .attr("y", ori.y+lgdSize.height/4)
              .attr("fill", (d,i) => i==0 ? 'black' : color(d))
              .attr("height", lgdSize.height/2)
              .attr("width", scaleLgdHori.bandwidth());
            itemsLgd.append('text')         
              .attr("x", (d,i) => i==0 ? scaleLgdHori(d) : scaleLgdHori(d)+scaleLgdHori.bandwidth()/2)
              .attr("y", ori.y+lgdSize.height/2 + me.fontSize/2)
              .attr("text-anchor", (d,i) => i==0 ? "start" : "middle")
              .attr("font-size", me.fontSize)
              .style("fill", "white")
              .text(d => d);
        }

        this.hide = function(){
          svg.attr('visibility',"hidden");
        }
        this.show = function(){
          svg.attr('visibility',"visible");
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

        function neigh(a, b) {
            return a == b || adjlist[a + "-" + b];
        }
        
        function ticked() {
        
            node.call(updateNode);
            link.call(updateLink);
        
            labelLayout.alphaTarget(0.3).restart();
            labelNode.each(function(d, i) {
                if(i % 2 == 0) {
                    d.x = d.node.x;
                    d.y = d.node.y;
                } else {
                    var b = this.getBBox();
        
                    var diffX = d.x - d.node.x;
                    var diffY = d.y - d.node.y;
        
                    var dist = Math.sqrt(diffX * diffX + diffY * diffY);
        
                    var shiftX = b.width * (diffX - dist) / (dist * 2);
                    shiftX = Math.max(-b.width, Math.min(0, shiftX));
                    var shiftY = 16;
                    this.setAttribute("transform", "translate(" + shiftX + "," + shiftY + ")");
                }
            });
            labelNode.call(updateNode);
        
        }
        
        function fixna(x) {
            if (isFinite(x)) return x;
            return 0;
        }
        
        function focus(d) {
            var index = d3.select(d3.event.target).datum().index;
            node.style("opacity", function(o) {
                return neigh(index, o.index) ? 1 : 0.1;
            });
            labelNode.attr("display", function(o) {
                return neigh(index, o.node.index) ? "block": "none";
            });
            link.style("opacity", function(o) {
                return o.source.index == index || o.target.index == index ? 1 : 0.1;
            });
        }
        
        function unfocus() {
            labelNode.attr("display", "block");
            node.style("opacity", 1);
            link.style("opacity", 1);
        }
        
        function updateLink(link) {
            link.attr("x1", function(d) { return fixna(d.source.x); })
                .attr("y1", function(d) { return fixna(d.source.y); })
                .attr("x2", function(d) { return fixna(d.target.x); })
                .attr("y2", function(d) { return fixna(d.target.y); });
        }
        
        function updateNode(node) {
            node.attr("transform", function(d) {
                return "translate(" + fixna(d.x) + "," + fixna(d.y) + ")";
            });
        }
        
        function dragstarted(d) {
            d3.event.sourceEvent.stopPropagation();
            if (!d3.event.active) graphLayout.alphaTarget(0.3).restart();
            d.fx = d.x;
            d.fy = d.y;
        }
        
        function dragged(d) {
            d.fx = d3.event.x;
            d.fy = d3.event.y;
        }
        
        function dragended(d) {
            if (!d3.event.active) graphLayout.alphaTarget(0);
            d.fx = null;
            d.fy = null;
        }              

        if(me.dataUrl){
            d3.json(me.dataUrl).then(function(graph) {
                me.data = graph;
                me.init();
            });    
        }else{
            me.init();
        }

    }
}

  



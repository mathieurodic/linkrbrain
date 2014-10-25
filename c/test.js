var size = 720;


var paper = new Raphael('paper', 1.1 * size, 1.1 * size);




//	Recalculate nodes
for (var i=0; i<graph.nodes.length; i++){
	var node = graph.nodes[i];
	node.X = (node.x + 0.05) * size;
	node.Y = (node.y + 0.05) * size;
	node.R = size / 3 * node.r;
	node.selected = false;
}
	
//	Edges
for (var i=0; i<graph.edges.length; i++){
	var edge = graph.edges[i];
	var node1 = graph.nodes[edge.i1];
	var node2 = graph.nodes[edge.i2];
	var weight = 0.5 + 0.5 * Math.log(1 + edge.weight) / Math.log(2);
	var color = (node1.type == 'pointset' || node2.type == 'pointset') ? '#0C4' : '#888';
	paper.path('M' + node1.X + ',' + node1.Y + ' L' + node2.X + ',' + node2.Y)
		.attr({'stroke':'#FFF', 'stroke-opacity':0.7, 'stroke-width':3*weight+1});
	edge.paper = paper.path('M' + node1.X + ',' + node1.Y + ' L' + node2.X + ',' + node2.Y)
		.attr({'stroke':color, 'stroke-opacity':1, 'stroke-width':3*weight-1})
		.data('index', i);
}

		
//	Nodes
for (var i=0; i<graph.nodes.length; i++){
	var node = graph.nodes[i];
	var color = '#FFF';
	switch (node.type){
		case 'task':
			color = '#CCC';
			break;
		case 'pointset':
			color = '#0C4';
			break;
	}
	var set = paper.set();
	set.push
	(	paper.circle(node.X, node.Y, node.R)
			.attr({stroke:'#040', fill:color})
			.data('index', i)
	,	paper.text(node.X, node.Y, node.name.replace(/ /g, '\n'))
			.attr({'font-family':'Verdana', 'font-size':node.R/2.8})
			.data('index', i)
	);
	node.paper = set;
}
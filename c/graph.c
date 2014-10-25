#include <stdio.h>
#include <string.h>

#include "includes/graph.h"


int main(int argc, char* argv[]){
	
	//	Definitions
	size_t i, j;
	double k;
	FILE* f;
	Graph graph;
	
	//	Initialization
	if (argc >= 2){
		f = fopen(argv[1], "r");
	} else{
		fputs("Please specify a file to open.\n", stderr);
		return 1;
	}
	fscanf(f, "%u", &i);
	graph_init(&graph, i);
	
	//	Nodes
	for (i=0; i<graph.count; i++){
		Node* node = &graph.nodes[i];
		strcpy(node->type, "task");
		fscanf(f, "%d", &node->id);
	}
	
	//	Edges
	while (fscanf(f, "%u %u %lf", &i, &j, &k) == 3){
		size_t a, b;
		a = graph_node_index(&graph, "task", i);
		b = graph_node_index(&graph, "task", j);
		if (a != -1  &&  b != -1){
			graph_edge_add(&graph, a, b, k);
		}
	}
	
	//	Conclusion
	fclose(f);
	graph_iterate(&graph);
	graph_display(&graph);
	return 0;
}
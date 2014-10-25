#include <stdio.h>
#include <string.h>

#include "includes/graph.h"


int main(int argc, char* argv[]){

	Graph* graph;
	FILE* f;
	size_t i, j;
	double strength;
	
	//	Initialization
	if (argc >= 2){
		f = fopen(argv[1], "r");
	} else{
		fputs("Please specify a file to open.\n", stderr);
		return 1;
	}
	fscanf(f, "%u", &i);
	graph = new Graph(i);
	
	//	Nodes
	for (i=0; i<graph->count; i++){
		fscanf(f, "%d", &j);
		graph->nodes[i]->setTypeId("task", j);
	}
	
	//	Edges
	while (fscanf(f, "%u %u %lf", &i, &j, &strength) == 3){
		size_t a, b;
		a = graph->getNodeIndexByTypeId("task", i);
		b = graph->getNodeIndexByTypeId("task", j);
		if (a != -1  &&  b != -1){
			graph->addLink(a, b, strength);
		}
	}
	
	//	Conclusion
	fclose(f);
	graph->compute();
	graph->display();
	printf("Sortie...\n");exit(0);
	return 0;

}
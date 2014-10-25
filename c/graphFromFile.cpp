#include <stdio.h>
#include <string.h>

#include "includes/graph.h"


int main(int argc, char* argv[]){

	Graph* graph;
	FILE* f;
	size_t n, i, j, c;
	double strength;
	
	//	Initialization
	if (argc >= 2){
		f = fopen(argv[1], "r");
	} else{
		fputs("Please specify a file to open.\n", stderr);
		return 1;
	}
	fscanf(f, "%u", &n);
	graph = new Graph(n);
	
	//	Edges
	for (i=0; i<n; i++){
		for (j=0; j<n; j++){
			if (fscanf(f, "%lf", &strength) == 1){
				graph->addLink(i, j, strength);
			} else{
				return 2;
			}
		}
	}
	
	//	Conclusion
	fclose(f);
	graph->compute();
	graph->displayCoordinates();
	return 0;

}
// http://carma.rodic.fr/search/19/visualization/zones/

#include <math.h>
#include <stdio.h>
#include <mysql/mysql.h>


typedef struct {
	int id;
	double x;
	double y;
	double vx;
	double vy;
	double ax;
	double ay;
} Node;
typedef struct {
	double k;
	double l;
} Edge;
typedef struct {
	int count;
	double dt;
	double e;
	double f;
	double f_max;
	double df;
	double r;
	double k;
	double Emax;
	Node* nodes;
	Edge** edges;
} Graph;


void load(char* filename, Graph* graph){
	int i, j;
	double k, R;
	FILE* f;
	f = fopen(filename, "r");
	fscanf(f, "%d", &graph->count);
	//	Nodes
	graph->nodes = (Node*) malloc(graph->count * sizeof(Node));
	R = 2 * graph->r * graph->count;
	for (i=0; i<graph->count; i++){
		Node* node = &graph->nodes[i];
		double theta = 2 * M_PI * (double)i / (double)graph->count;
		fscanf(f, "%d", &node->id);
		node->vx = 0;
		node->vy = 0;
		node->x = R * cos(theta);
		node->y = R * sin(theta);
	}
	//	Edges
	graph->edges = (Edge**)malloc(graph->count * sizeof(Edge));
	for (i=0; i<graph->count; i++){
		graph->edges[i] = (Edge*)malloc(graph->count * sizeof(Edge));
		for (j=0; j<graph->count; j++){
			graph->edges[i][j].k = 0;
		}
	}
	while (fscanf(f, "%d %d %lf", &i, &j, &k) == 3){
		int a, b;
		for (a=0; a<graph->count; a++){
			if (graph->nodes[a].id == i){
				break;
			}
		}
		for (b=0; b<graph->count; b++){
			if (graph->nodes[b].id == j){
				break;
			}
		}
		graph->edges[a][b].k += k;
		graph->edges[b][a].k += k;
	}
	//
	fclose(f);
}
void save(char* filename, Graph* graph){
	FILE* f;
	f = fopen(filename, "w");
	//
	int i;
	for (i=0; i<graph->count; i++){
		Node* node = &graph->nodes[i];
		fprintf(f, "%lf\t%lf\n", node->x, node->y);
	}
	//
	fclose(f);
}
void show(Graph* graph){
	int i;
	for (i=0; i<graph->count; i++){
		Node* node = &graph->nodes[i];
		printf("%lf\t%lf\n", node->x, node->y);
	}
}


double distance2(Node* node1, Node* node2){
	double dx = node1->x - node2->x;
	double dy = node1->y - node2->y;
	return dx*dx + dy*dy;
}
double distance(Node* node1, Node* node2){
	return sqrt(distance2(node1, node2));
}
double energy(Graph* graph){
	double E;
	int i;
	for (i=0; i<graph->count; i++){
		Node* node = &graph->nodes[i];
		E += node->vx * node->vx + node->vy * node->vy;
	}
	return E;
}


void iterateInteractions(Graph* graph){
	int i, j;
	for (i=0; i<graph->count; i++){
		Node* node = &graph->nodes[i];
		node->ax = node->ay = 0;
	}
	for (i=0; i<graph->count; i++){
		Node* node_i = &graph->nodes[i];
		for (j=0; j<i; j++){
			Node* node_j = &graph->nodes[j];
			Edge* edge_ij = &graph->edges[i][j];
			double l2 = distance2(node_i, node_j);
			double l = sqrt(l2);
			double a = 0;
			//	Elastic
			a += graph->k * edge_ij->k * (l - 2 * graph->r);
			//	Electrostatic
			a -= graph->e / l2;
			//	Direction
			double ax, ay;
			ax = a * (node_j->x - node_i->x) / l;
			ay = a * (node_j->y - node_i->y) / l;
			//	Assignment
			node_i->ax += ax;
			node_i->ay += ay;
			node_j->ax -= ax;
			node_j->ay -= ay;
		}
	}
}
void iterateFriction(Graph* graph){
	int i;
	for (i=0; i<graph->count; i++){
		Node* node = &graph->nodes[i];
		node->ax -= graph->f * node->vx;
		node->ay -= graph->f * node->vy;
	}
}
void iterateNewton(Graph* graph){
	int i;
	for (i=0; i<graph->count; i++){
		Node* node = &graph->nodes[i];
		node->vx += node->ax * graph->dt;
		node->vy += node->ay * graph->dt;
		node->x += node->vx * graph->dt;
		node->y += node->vy * graph->dt;
	}
}

void iterate(Graph* graph){
	double E = 2 * graph->Emax;
	int t;
	for (t=1; E>graph->Emax; t++){
		if (graph->f < graph->f_max){
			graph->f += graph->df;
		}
		iterateInteractions(graph);
		iterateFriction(graph);
		iterateNewton(graph);
		if (t % 100 == 0){
			t = 0;
			E = energy(graph);
		}
	}
}

int main(int argc, char* argv[]){
	
	int i, j, t;
	double E, Eo;
	
	//	Initialize graph
	Graph graph;
	graph.dt = 0.01;
	graph.e = 1.50;
	graph.k = 1;
	graph.r = 0.5;
	
	if (argc == 2){
		load(argv[1], &graph);		
	} else{
		return 1;
	}
	
	//	Other graph variables
	graph.f = 0;
	graph.f_max = graph.count;
	graph.df = 0.0001 * graph.count;	
	graph.Emax = 1e-1 / (double)graph.count;
	
	//	Calculate & show coordinates
	iterate(&graph);
	show(&graph);

	return 0;

}
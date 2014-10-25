#include <stdio.h>
#include <math.h>


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
	double f;
	double r;
	double k;
	double E0;
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
		graph->edges[i][j].k += k;
		graph->edges[j][i].k += k;
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

void iterateEdges(Graph* graph){
	int i, j;
	for (i=0; i<graph->count; i++){
		for (j=0; j<i; j++){
			graph->edges[i][j].l = distance(&graph->nodes[i], &graph->nodes[j]);
		}
	}
}
void iterateNodes(Graph* graph){
	int i, j;
	//	Remise à zéro des accélérations
	for (i=0; i<graph->count; i++){
		Node* node = &graph->nodes[i];
		node->ax = node->ay = 0;
	}
	//	Forces d'interactions
	for (i=0; i<graph->count; i++){
		Node* node1 = &graph->nodes[i];
		for (j=0; j<i; j++){
			Edge* edge = &graph->edges[i][j];
			Node* node2 = &graph->nodes[j];
			double a;
			double ax, ay;
			double l, dl;
			l = edge->l;
			dl = l - 2 * graph->r;
			a = 0;
			a = graph->k * edge->k * dl;
			// a -= 1 / (l * l);
			// printf("%f\n", a);
			ax += a * (node2->x - node1->x) / l;
			ay += a * (node2->y - node1->y) / l;
			node1->ax += ax;
			node1->ay += ay;
			// node2->ax -= ax;
			// node2->ay -= ay;
		}
	}
	//	Lois de Newton
	for (i=0; i<graph->count; i++){
		Node* node = &graph->nodes[i];
		node->ax -= graph->f * node->vx;
		node->ay -= graph->f * node->vx;
		node->vx += node->ax * graph->dt;
		node->vy += node->ay * graph->dt;
		node->x += node->vx * graph->dt;
		node->y += node->vy * graph->dt;
	}
}



int main(int argc, char* argv[]){
	
	int i, j, t;
	double E, Eo;
	
	//	Initialize graph
	Graph graph;
	graph.dt = 0.01;
	graph.f = 0.01;
	graph.k = 1;
	graph.r = 0.5;
	graph.E0 = 1e-6;
	
	if (argc == 2){
		load(argv[1], &graph);		
	} else{
		return 1;
	}
	
	
	//	Calculate
	E = 10 * graph.E0;
	for (t=1; E>graph.E0; t++){
		iterateEdges(&graph);
		iterateNodes(&graph);
		if (t % 100 == 0){
			t = 0;
			E = energy(&graph);
		}
	}
	
	//	Show resulting coordinates
	show(&graph);

	return 0;

}
#ifndef GRAPH_H_INCLUDED
#define GRAPH_H_INCLUDED

#include <math.h>
#include <stdio.h>
#include <stdlib.h>
#include <string.h>



//	Node, Edge and Graph structures
typedef struct {
	int id;
	char type[16];
	double r;
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



//	Creation and rendering
void graph_init(Graph* graph, size_t count){
	size_t i, j;
	double R;
	//	Graph initialization
	graph->count = count;
	graph->dt = 0.01;
	graph->e = 1.50;
	graph->k = 1;
	graph->r = 0.5;
	graph->f = 0;
	graph->f_max = graph->count;
	graph->df = 0.0001 * graph->count;	
	graph->Emax = 1e-1 / (double)graph->count;
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
}
void graph_display(Graph* graph){
	size_t i;
	for (i=0; i<graph->count; i++){
		Node* node = &graph->nodes[i];
		printf("%s\t%d\t%lf\t%lf\n", node->type, node->id, node->x, node->y);
	}
}

//	Nodes manipulation
size_t graph_node_index(Graph* graph, char* type, int id){
	size_t i;
	for (i=0; i<graph->count; i++){
		if (graph->nodes[i].id == id  &&  strcmp(graph->nodes[i].type, type) == 0){
			return i;
		}
	}
	return -1;
}

//	Edges manipulation
void graph_edge_add(Graph* graph, size_t nodeIndex1, size_t nodeIndex2, double strength){
	graph->edges[nodeIndex1][nodeIndex2].k += strength;
	graph->edges[nodeIndex2][nodeIndex1].k += strength;
}

//	Simple operations
double nodes_distance2(Node* node1, Node* node2){
	double dx = node1->x - node2->x;
	double dy = node1->y - node2->y;
	return dx*dx + dy*dy;
}
double nodes_distance(Node* node1, Node* node2){
	return sqrt(nodes_distance2(node1, node2));
}
double graph_energy(Graph* graph){
	double E;
	int i;
	for (i=0; i<graph->count; i++){
		Node* node = &graph->nodes[i];
		E += node->vx * node->vx + node->vy * node->vy;
	}
	return E;
}

//	Iterate the whole graph
void graph_iterate_interactions(Graph* graph){
	size_t i, j;
	for (i=0; i<graph->count; i++){
		Node* node = &graph->nodes[i];
		node->ax = node->ay = 0;
	}
	for (i=0; i<graph->count; i++){
		Node* node_i = &graph->nodes[i];
		for (j=0; j<i; j++){
			Node* node_j = &graph->nodes[j];
			Edge* edge_ij = &graph->edges[i][j];
			double l2 = nodes_distance2(node_i, node_j);
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
void graph_iterate_friction(Graph* graph){
	int i;
	for (i=0; i<graph->count; i++){
		Node* node = &graph->nodes[i];
		node->ax -= graph->f * node->vx;
		node->ay -= graph->f * node->vy;
	}
}
void graph_iterate_newton(Graph* graph){
	int i;
	for (i=0; i<graph->count; i++){
		Node* node = &graph->nodes[i];
		node->vx += node->ax * graph->dt;
		node->vy += node->ay * graph->dt;
		node->x += node->vx * graph->dt;
		node->y += node->vy * graph->dt;
	}
}
void graph_iterate(Graph* graph){
	double E = 2 * graph->Emax;
	int t;
	for (t=1; E>graph->Emax; t++){
		if (graph->f < graph->f_max){
			graph->f += graph->df;
		}
		graph_iterate_interactions(graph);
		graph_iterate_friction(graph);
		graph_iterate_newton(graph);
		if (t % 100 == 0){
			t = 0;
			E = graph_energy(graph);
		}
	}
}


#endif
#ifndef GRAPH_H_INCLUDED
#define GRAPH_H_INCLUDED

#include <math.h>
#include <stdio.h>
#include <stdlib.h>
#include <string.h>


class Node {
	
	public:
	
		int id;
		char type[16];
		char name[32];
		double r, r_1;
		double x;
		double y;
		double vx;
		double vy;
		double ax;
		double ay;
		
		Node(double r, char* type, int id){
			this->setRadius(r);
			this->setTypeId(type, id);
			//
			this->x = this->y = 0.0;
			this->vx = this->vy = 0.0;
			this->ax = this->ay = 0.0;
		}
		Node(double r){
			char type[16];
			this->setRadius(r);
			strcpy(type, "[default]");
			this->setTypeId(type, 0);
			//
			this->x = this->y = 0.0;
			this->vx = this->vy = 0.0;
			this->ax = this->ay = 0.0;
		}
		void resetAcceleration(){
			this->ax = this->ay = 0.f;
		}
		void computeFriction(double f){
			this->ax -= f * this->vx;
			this->ay -= f * this->vy;
		}		
		void computeNewton(double dt){
			vx += ax * dt;
			vy += ay * dt;
			x += vx * dt;
			y += vy * dt;
		}
		void display(){
			printf("%s\t%d\t%s\t%lf\t%lf\t%lf\n", this->type, this->id, this->name, this->r, this->x, this->y);
		}
		void displayCoordinates(){
			printf("%lf\t%lf\n", this->x, this->y);
		}
		double energy(){
			return this->vx * this->vx + this->vy * this->vy;
		}
		void setTypeId(char* type, int id){
			strcpy(this->type, type);
			this->id = id;
		}
		void setRadius(double r){
			this->r = r;
			this->r_1 = 1 / r;
		}
		void setName(char* name){
			strcpy(this->name, name);
		}
	private:
	
};


class Edge {
	
	public:
		
		//	Positive: attraction
		//	Negative: repulsion
		
		double length2, length_2, length, length_1;
		double orientationX, orientationY;
		double force, forceX, forceY;
		double kSpring, kRepulsion;
		
		Edge(Node* node_i, Node* node_j){
			this->node_i = node_i;
			this->node_j = node_j;
			this->kSpring = 0.f;
			this->kRepulsion = 1.5;
		}
		void computePosition(){
			double dx, dy;
			dx = this->node_j->x - this->node_i->x;
			dy = this->node_j->y - this->node_i->y;
			//
			this->length2 = dx * dx + dy * dy;
			this->length_2 = 1 / this->length2;
			this->length = sqrt(this->length2);
			this->length_1 = 1 / this->length;
			//
			this->orientationX = dx * this->length_1;
			this->orientationY = dy * this->length_1;
		}
		void computeForce(){
			double springForce = this->kSpring * (this->length - this->node_i->r - this->node_j->r);
			double repulsionForce = this->kRepulsion * this->length_2;
			this->force = springForce - repulsionForce;
			this->forceX = this->force * orientationX;
			this->forceY = this->force * orientationY;
		}
		void compute(){
			this->computePosition();
			this->computeForce();
		}
	
	private:
		Node* node_i;
		Node* node_j;
};



class Graph {
	
	public:

		size_t count;
		double dt;
		double e;
		double kSpring;
		double f_max, df;
		double r, r_1;
		double Emax;
		Node** nodes;
		Edge*** edges;
		
		Graph(size_t count){
			size_t i, j;
			//	Graph properties
			this->count = count;
			this->dt = 0.01;
			this->kSpring = 5;
			this->r = 0.5;
			this->f_max = log(1 + this->count);
			this->df = 0.00001 * log(1 + this->count);
			this->Emax = 1e-1 / (double)this->count;
			//	Nodes
			double R = 2 * this->r * this->count;
			double k = 2 * M_PI / (double)this->count;
			this->nodes = (Node**) malloc(this->count * sizeof(Node*));
			for (i=0; i<this->count; i++){
				double theta = k * i;
				Node* node = this->nodes[i] = new Node(this->r);
				node->x = R * cos(theta);
				node->y = R * sin(theta);
			}
			//	Edges
			this->edges = (Edge***)malloc(this->count * sizeof(Edge**));
			for (i=0; i<this->count; i++){
				if (i>0){
					this->edges[i] = (Edge**)malloc(i * sizeof(Edge*));
					for (j=0; j<i; j++){
						this->edges[i][j] = new Edge(this->nodes[i], this->nodes[j]);
					}
				}
			}
		}
		~Graph(){
			size_t i, j;
			for (i=0; i<this->count; i++){
				for (j=0; j<this->count; j++){
					delete this->edges[i][j];
				}
				delete this->edges[i];
				delete this->nodes[i];				
			}
			delete this->edges;
			delete this->nodes;
		}
		
		void addLink(size_t i, size_t j, double strength){
			if (i != j){
				Edge* edge = (j<i) ? this->edges[i][j] : this->edges[j][i];
				edge->kSpring += this->kSpring * strength;
			}
		}
		
		void iterate(double friction){
			size_t i, j;
			for (i=0; i<this->count; i++){
				Node* node_i = this->nodes[i];
				this->nodes[i]->resetAcceleration();
				this->nodes[i]->computeFriction(friction);
				for (j=0; j<i; j++){
					Node* node_j = this->nodes[j];
					Edge* edge = this->edges[i][j];
					edge->compute();
					node_i->ax += edge->forceX;
					node_j->ax -= edge->forceX;
					node_i->ay += edge->forceY;
					node_j->ay -= edge->forceY;
				}
			}
			for (i=0; i<this->count; i++){
				this->nodes[i]->computeNewton(this->dt);
			}
		}
		
		double energy(){
			size_t i;
			double energy;
			energy = 0.f;
			for (i=0; i<this->count; i++){
				energy += this->nodes[i]->energy();
			}
			return energy;
		}
		
		void compute(){
			double friction = 0;
			size_t i = 0;
			do {
				for (i=0; i<100; i++){
					if (friction < this->f_max){
						friction += this->df;
					}
					this->iterate(friction);
				}
			} while (this->energy() > this->Emax);
		}
		
		void normalizeCoordinates(){
			size_t i;
			double xMin, xMax, Dx;
			double yMin, yMax, Dy;
			double scale;
			//	Detect extrema
			xMin = xMax = this->nodes[0]->x;
			yMin = yMax = this->nodes[0]->y;
			for (i=1; i<this->count; i++){
				Node* node = this->nodes[i];
				if (node->x < xMin){
					xMin = node->x;
				} else if (node->x > xMax){
					xMax = node->x;
				}
				if (node->y < yMin){
					yMin = node->y;
				} else if (node->y > yMax){
					yMax = node->y;
				}
			}
			//	Scale factor
			Dx = xMax - xMin;
			Dy = yMax - yMin;
			scale = (Dx>Dy) ? 1/Dx : 1/Dy;
			//	Normalize
			for (i=0; i<this->count; i++){
				Node* node = this->nodes[i];
				node->r *= scale;
				node->x -= xMin;
				node->x *= scale;
				node->y -= yMin;
				node->y *= scale;
			}
		}
		void normalizeLinks(){
			size_t i, j;
			double kMin, kMax, Dk;
			kMin = kMax = this->edges[0][0]->kSpring;
			for (i=0; i<this->count; i++){
				for (j=0; j<i; j++){
					Edge* edge = this->edges[i][j];
					if (kMin > edge->kSpring){
						kMin = edge->kSpring;
					} else if (kMax < edge->kSpring){
						kMax = edge->kSpring;
					}
				}
			}
			Dk = kMax - kMin;
			for (i=0; i<this->count; i++){
				for (j=0; j<i; j++){
					Edge* edge = this->edges[i][j];
					edge->kSpring -= kMin;
					edge->kSpring /= Dk;
				}
			}
		}
		void normalize(){
			this->normalizeCoordinates();
			this->normalizeLinks();
		}
		
		void displayNodes(){
			size_t i;
			for (i=0; i<this->count; i++){
				Node* node = this->nodes[i];
				node->display();
			}
		}
		void displayEdges(){
			size_t i, j;
			for (i=0; i<this->count; i++){
				for (j=0; j<i; j++){
					printf("%u\t%u\t%lf\n", i, j, this->edges[i][j]->kSpring);
				}
			}
		}
		void display(){
			this->displayNodes();
			this->displayEdges();
		}
		void displayCoordinates(){
			size_t i;
			for (i=0; i<this->count; i++){
				Node* node = this->nodes[i];
				node->displayCoordinates();
			}
		}
		
		int getNodeIndexByTypeId(char* type, int id){
			int i;
			for (i=0; i<this->count; i++){
				Node* node = this->nodes[i];
				if (node->id == id  &&  strcmp(node->type, type) == 0){
					return i;
				}
			}
			return -1;
		}
	
	private:
	
};


#endif
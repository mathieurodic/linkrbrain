#include <math.h>
#include <stdio.h>
#include <string.h>

#include "includes/my.h"
#include "includes/graph.h"


int main(int argc, char* argv[]){

	My* my;
	Graph* graph;
	size_t i;
	char** row;
	char* type;
	char sql[1024];
	int query_id, id;
	int pointsetCount;
	int taskCount, taskCountMax;
	
	//	Read arguments
	if (argc < 5){
		return 1;
	}
	sscanf(argv[1], "%d", &query_id);
	sscanf(argv[2], "%d", &group_subid_max);
	type = argv[3];
	sscanf(argv[4], "%d", &taskCountMax);
	
	//	Database initialization
	my = new My();
	sprintf(sql, "CREATE TEMPORARY TABLE tmp__task ENGINE = memory AS SELECT task_id AS id, (SELECT name FROM task WHERE id = task_id) AS name FROM query_resulttask WHERE query_id = %d ORDER BY score DESC LIMIT %d", query_id, taskCountMax);
	sprintf(sql, "CREATE TEMPORARY TABLE tmp__task ENGINE = memory AS SELECT task_id AS id, (SELECT name FROM task WHERE id = task_id) AS name FROM query_resulttask WHERE query_id = %d ORDER BY score DESC LIMIT %d", query_id, taskCountMax);
	my->query(sql);
	
	//	Graph initialization
	sprintf(sql, "SELECT COUNT(*) FROM pointset WHERE query_id = %d", query_id);
	pointsetCount = my->intValue(sql);
	taskCount = my->intValue("SELECT COUNT(*) FROM tmp__task");
	graph = new Graph(pointsetCount + taskCount);
	
	//	Pointsets
	sprintf(sql, "SELECT id, title FROM pointset WHERE query_id = %d", query_id);
	i = 0;
	my->query(sql);
	while (id = my->intValue()){
		Node* node = graph->nodes[i++];
		node->setTypeId("pointset", id);
		node->setName(my->_row[1]);
	}
	
	//	Tasks
	i = 0;
	my->query("SELECT id, name FROM tmp__task");	
	while (id = my->intValue()){
		Node* node = graph->nodes[pointsetCount + i++];
		node->setTypeId("task", id);
		node->setName(my->_row[1]);
	}
	
	//	Task-Task
	my->query("CREATE TEMPORARY TABLE tmp__task_task AS SELECT task1_id, task2_id, score FROM task_task WHERE task1_id IN (SELECT id FROM tmp__task)");
	my->query("DELETE FROM tmp__task_task WHERE task2_id NOT IN (SELECT id FROM tmp__task)");
	my->query("SELECT task1_id, task2_id, score FROM tmp__task_task WHERE task2_id < task1_id ORDER BY task1_id, task2_id");
	while (row = my->row()){
		int id_i, id_j;
		int i, j;
		double score;
		sscanf(row[0], "%d", &id_i);
		sscanf(row[1], "%d", &id_j);
		sscanf(row[2], "%lf", &score);
		i = graph->getNodeIndexByTypeId("task", id_i);
		j = graph->getNodeIndexByTypeId("task", id_j);
		graph->addLink(i, j, exp(score) - 1);
		// graph->addLink(i, j, score);
	}
	
	//	Task-Pointset
	sprintf(sql, "SELECT pointset_id, task_id, score FROM pointset_resulttask WHERE pointset_id IN (SELECT id FROM pointset WHERE query_id = %d) AND task_id IN (SELECT id FROM tmp__task)", query_id);
	my->query(sql);
	while (row = my->row()){
		int pointset_id, task_id;
		int i, j;
		double score;
		sscanf(row[0], "%d", &pointset_id);
		sscanf(row[1], "%d", &task_id);
		sscanf(row[2], "%lf", &score);
		i = graph->getNodeIndexByTypeId("pointset", pointset_id);
		j = graph->getNodeIndexByTypeId("task", task_id);
		graph->addLink(i, j, exp(score) - 1);
		// graph->addLink(i, j, score);
	}
	
	
	
	//	Conclusion
	printf("%d\n", graph->count);
	graph->compute();
	graph->normalize();
	graph->display();
	return 0;	

}
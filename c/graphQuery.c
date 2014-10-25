#include <stdio.h>
#include <mysql.h>

#include "includes/graph.h"



	
int main(int argc, char* argv[]){
	
	//	Definitions
	MYSQL mysql;
	MYSQL_RES* result;
    MYSQL_ROW row;
	Graph graph;
	char* sqlPointsets = "SELECT id FROM pointset WHERE query_id = %u";
	char* sqlTasksCount = "SELECT COUNT(*) FROM query_resulttask WHERE query_id = %u";
	char* sqlTasksTmp = "CREATE TEMPORARY TABLE tmp__task AS SELECT task_id AS id FROM query_resulttask WHERE query_id = %u ORDER BY score DESC LIMIT %u";
	char* sqlTasks = "SELECT pointset_id, task_id, score FROM pointset_resulttask WHERE task_id IN (SELECT id FROM tmp__task) AND pointset_id IN (SELECT id FROM pointset WHERE query_id = %u)";
	char sql[1024];
	unsigned int query_id;
	unsigned int pointsetCount, taskCount, taskCountMax;
	unsigned int i, j;
	
	//	MySQL initialization
	mysql_init(&mysql);
	if (!mysql_real_connect(&mysql, "localhost", "root", "poiu01AZ3", "carma", 0, NULL, 0)){
		fputs("Error while connecting to database.\n", stderr);
		return 1;
    }
	
	//	Read arguments
	if (argc < 2){
		fputs("Please specify a query to compute.\n", stderr);
		return 2;
	}
	if (argc >= 3){
		sscanf(argv[2], "%u", &taskCountMax);
	} else{
		taskCountMax = 32;
	}
	sscanf(argv[1], "%u", &query_id);
	
	
	//	Count pointsets
	sprintf(sql, "SELECT COUNT(*) FROM pointset WHERE query_id = %u", query_id);
	mysql_query(&mysql, sql);
	result = mysql_use_result(&mysql);
	row = mysql_fetch_row(result);
	sscanf(row[0], "%u", &pointsetCount);
	mysql_free_result(result);
	
	//	Count tasks
	sprintf(sql, sqlTasksCount, query_id);
	mysql_query(&mysql, sql);
	result = mysql_use_result(&mysql);
	row = mysql_fetch_row(result);
	sscanf(row[0], "%u", &taskCount);
	if (taskCount > taskCountMax){
		taskCount = taskCountMax;
	}
	mysql_free_result(result);
	
	//	Initialize graph
	graph_init(&graph, pointsetCount + taskCount);
	
	//	Integrate pointsets
	sprintf(sql, sqlTasksTmp, query_id, taskCount);
	printf("%s\n", sql);
	mysql_query(&mysql, sql);
	sprintf(sql, sqlTasks, query_id);
	printf("%s\n", sql);
	mysql_query(&mysql, sql);
	result = mysql_use_result(&mysql);
	i = 0;
	while ((row = mysql_fetch_row(result))){
		printf("%u\t%s\t%s\t%s\n", i++, row[0], row[1]);
	}
	mysql_free_result(result);
	
	printf("%u\n", graph.count); return 0;
	
	
	
	
	//	Query
	

	//	Conclusion
	mysql_close(&mysql);
	return 0;
	
}
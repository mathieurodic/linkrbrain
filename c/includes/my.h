#ifndef MY_H_INCLUDED
#define MY_H_INCLUDED

#include <stdio.h>
#include <stdlib.h>
#include <mysql.h>



class My {

	public:
	
		MYSQL_ROW _row;
		
		My(){
			this->mysql = &_mysql;
			mysql_init(this->mysql);
			mysql_real_connect(this->mysql, "localhost", "root", "poiu01AZ3", "carma", 0, NULL, 0);
		}
		~My(){
			mysql_free_result(this->result);
			mysql_close(this->mysql);
		}
		
		MYSQL_RES* query(char* sql){
			mysql_free_result(this->result);
			if (mysql_query(this->mysql, sql)){
				printf("MySQL error n°%d:\n%s\n", mysql_errno(this->mysql), mysql_error(this->mysql));
				exit(2);
			}
			if (this->result = mysql_store_result(this->mysql)){
				return this->result;
			}
			return NULL;
		}
		
		MYSQL_ROW row(char* sql){
			this->query(sql);
			return this->row();
		}
		MYSQL_ROW row(){
			return this->_row = mysql_fetch_row(this->result);
		}
		
		char* value(char* sql){
			this->query(sql);
			return this->value();
		}
		char* value(){
			MYSQL_ROW row;
			if ((row = this->row()) != NULL){
				return row[0];
			}
			return NULL;
		}
		
		int intValue(char* sql){
			this->query(sql);
			return this->intValue();
		}
		int intValue(){
			int d;
			char* value = this->value();
			if (value != NULL){
				sscanf(value, "%d", &d);
			} else{
				d = 0;
			}
			return d;
		}
		
		double doubleValue(char* sql){
			this->query(sql);
			return this->intValue();
		}
		double doubleValue(){
			double lf;
			char* value = this->value();
			if (value != NULL){
				sscanf(value, "%lf", &lf);
			} else{
				lf = 0.f;
			}
			return lf;
		}
	
	private:
		MYSQL _mysql;
		MYSQL* mysql;
		MYSQL_RES* result;

};

#endif
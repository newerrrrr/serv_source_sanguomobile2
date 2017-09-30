copy /B loginServerTables_origin.sql loginServerTables.sql
echo. >>loginServerTables.sql
copy /B loginServerTables.sql+create_static\Android_Channel.sql+update_static\Android_Channel.sql loginServerTables.sql

copy /B change_sql\v1.6.2.login_origin.sql+create_static\Android_Channel.sql+update_static\Android_Channel.sql change_sql\v1.6.2.login.sql
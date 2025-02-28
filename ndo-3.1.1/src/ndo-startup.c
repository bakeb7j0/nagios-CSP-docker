
int ndo_set_all_objects_inactive(ndo_query_context *q_ctx)
{
    trace_func_void();
    int ndo_return = NDO_OK;

    char * deactivate_sql = "UPDATE nagios_objects SET is_active = 0";

    ndo_return = mysql_query(q_ctx->conn, deactivate_sql);
    if (ndo_return != 0) {

        char err[BUFSZ_LARGE] = { 0 };
        snprintf(err, BUFSZ_LARGE - 1, "query(%s) failed with rc (%d), mysql (%d: %s)", deactivate_sql, ndo_return, mysql_errno(q_ctx->conn), mysql_error(q_ctx->conn));
        err[BUFSZ_LARGE - 1] = '\0';
        ndo_log(err, NSLOG_RUNTIME_WARNING);
    }

    trace_return_ok();
}


int ndo_clear_tables(ndo_query_context *q_ctx)
{
    trace_func_void();
    int ndo_return = NDO_OK;

    int i = 0;

    char * truncate_sql[] = {
        "TRUNCATE TABLE nagios_programstatus",
        "TRUNCATE TABLE nagios_timedeventqueue",
        "TRUNCATE TABLE nagios_comments",
        "TRUNCATE TABLE nagios_scheduleddowntime",
        "TRUNCATE TABLE nagios_runtimevariables",
        "TRUNCATE TABLE nagios_customvariablestatus",
        "TRUNCATE TABLE nagios_configfiles",
        "TRUNCATE TABLE nagios_configfilevariables",
        "TRUNCATE TABLE nagios_customvariables",
        "TRUNCATE TABLE nagios_commands",
        "TRUNCATE TABLE nagios_timeperiods",
        "TRUNCATE TABLE nagios_timeperiod_timeranges",
        "TRUNCATE TABLE nagios_contactgroups",
        "TRUNCATE TABLE nagios_contactgroup_members",
        "TRUNCATE TABLE nagios_hostgroups",
        "TRUNCATE TABLE nagios_hostgroup_members",
        "TRUNCATE TABLE nagios_servicegroups",
        "TRUNCATE TABLE nagios_servicegroup_members",
        "TRUNCATE TABLE nagios_hostescalations",
        "TRUNCATE TABLE nagios_hostescalation_contacts",
        "TRUNCATE TABLE nagios_serviceescalations",
        "TRUNCATE TABLE nagios_serviceescalation_contacts",
        "TRUNCATE TABLE nagios_hostdependencies",
        "TRUNCATE TABLE nagios_servicedependencies",
        "TRUNCATE TABLE nagios_contact_addresses",
        "TRUNCATE TABLE nagios_contact_notificationcommands",
        "TRUNCATE TABLE nagios_host_parenthosts",
        "TRUNCATE TABLE nagios_host_contacts",
        "TRUNCATE TABLE nagios_service_parentservices",
        "TRUNCATE TABLE nagios_service_contacts",
        "TRUNCATE TABLE nagios_service_contactgroups",
        "TRUNCATE TABLE nagios_host_contactgroups",
        "TRUNCATE TABLE nagios_hostescalation_contactgroups",
        "TRUNCATE TABLE nagios_serviceescalation_contactgroups",
        "TRUNCATE TABLE nagios_timeperiod_exceptions",
        "TRUNCATE TABLE nagios_timeperiod_exception_timeranges",
        "TRUNCATE TABLE nagios_timeperiod_exclusions",
    };

    for (i = 0; i < ARRAY_SIZE(truncate_sql); i++) {

        ndo_return = mysql_query(q_ctx->conn, truncate_sql[i]);
        if (ndo_return != 0) {

            char err[BUFSZ_LARGE] = { 0 };
            snprintf(err, BUFSZ_LARGE - 1, "query(%s) failed with rc (%d), mysql (%d: %s)", truncate_sql[i], ndo_return, mysql_errno(q_ctx->conn), mysql_error(q_ctx->conn));
            err[BUFSZ_LARGE - 1] = '\0';
            ndo_log(err, NSLOG_RUNTIME_WARNING);
        }
    }

    trace_return_ok();
}


// LCOV_EXCL_START
/**
 * Currently, these locks are only held until a specific task is completed in the startup thread,
 * which allows certain other threads to begin working. So, we lock each mutex immediately after
 * initialization.
 */
int ndo_init_queue_coordinator(ndo_queue_coordinator *nqc)
{
    int result;
    result |= pthread_mutex_init(&(nqc->finished_ndo_write_commands), NULL);
    result |= pthread_mutex_lock(&(nqc->finished_ndo_write_commands));
    result |= pthread_mutex_init(&(nqc->finished_ndo_write_timeperiods), NULL);
    result |= pthread_mutex_lock(&(nqc->finished_ndo_write_timeperiods));
    result |= pthread_mutex_init(&(nqc->finished_ndo_write_contacts), NULL);
    result |= pthread_mutex_lock(&(nqc->finished_ndo_write_contacts));
    result |= pthread_mutex_init(&(nqc->finished_ndo_write_contactgroups), NULL);
    result |= pthread_mutex_lock(&(nqc->finished_ndo_write_contactgroups));
    result |= pthread_mutex_init(&(nqc->finished_ndo_write_hosts), NULL);
    result |= pthread_mutex_lock(&(nqc->finished_ndo_write_hosts));
    result |= pthread_mutex_init(&(nqc->finished_ndo_write_hostgroups), NULL);
    result |= pthread_mutex_lock(&(nqc->finished_ndo_write_hostgroups));
    result |= pthread_mutex_init(&(nqc->finished_ndo_write_services), NULL);
    result |= pthread_mutex_lock(&(nqc->finished_ndo_write_services));
    result |= pthread_mutex_init(&(nqc->finished_ndo_write_servicegroups), NULL);
    result |= pthread_mutex_lock(&(nqc->finished_ndo_write_servicegroups));
    result |= pthread_mutex_init(&(nqc->finished_ndo_write_hostescalations), NULL);
    result |= pthread_mutex_lock(&(nqc->finished_ndo_write_hostescalations));
    result |= pthread_mutex_init(&(nqc->finished_ndo_write_serviceescalations), NULL);
    result |= pthread_mutex_lock(&(nqc->finished_ndo_write_serviceescalations));
    return result;
}

int ndo_write_db_init(ndo_query_context * q_ctx);

void * ndo_startup_thread(void * args)
{
    trace_func_args("args=%s", "NULL");
    int result;

    startup_thread_context = calloc(1, sizeof(ndo_query_context));
    ndo_write_db_init(startup_thread_context);

    ndo_queue_coordinator *startup_coordinator = malloc(sizeof(ndo_queue_coordinator));
    ndo_init_queue_coordinator(startup_coordinator);
    result = ndo_start_queues(startup_coordinator);
    if (result != NDO_OK) {
        ndo_log("NDO startup thread failed at ndo_start_queues - some data may be inaccurate.", NSLOG_RUNTIME_WARNING);
    }

    result = ndo_write_object_config(startup_thread_context, NDO_CONFIG_DUMP_ORIGINAL, startup_coordinator);
    if (result != NDO_OK) { 
        // If we couldn't write the object config, the rest of NDO should be disabled.
        ndo_log("NDO startup thread failed at ndo_write_object_config() - disabling NDO.", NSLOG_RUNTIME_ERROR);
        ndo_deregister_queue_functions();
        return NULL;
    }

    result = ndo_write_config_files(startup_thread_context);
    if (result != NDO_OK) {
        ndo_log("NDO startup thread failed at ndo_write_config_files() - disabling NDO.", NSLOG_RUNTIME_ERROR);
        ndo_deregister_queue_functions();
        return NULL;
    }

    result = ndo_write_config(NDO_CONFIG_DUMP_ORIGINAL);
    if (result != NDO_OK) {
        ndo_log("NDO startup thread failed at ndo_write_config() - disabling NDO.", NSLOG_RUNTIME_ERROR);
        ndo_deregister_queue_functions();
        return NULL;
    }

    ndo_disconnect_database(startup_thread_context);
    ndo_close_query_context(startup_thread_context);

    trace_return_ok();
}

// LCOV_EXCL_STOP

int ndo_write_config_files(ndo_query_context *q_ctx)
{
    trace_func_void();

    int result = ndo_process_nagios_config(q_ctx);

    trace_return("%d", result);
}

int ndo_process_nagios_config(ndo_query_context *q_ctx)
{
    trace_func_void();
    int ndo_return = NDO_OK;

    if (config_file == NULL) {
        //ndo_log("No nagios config_file found!");
        trace_return_error_cond("config_file == NULL");
    }

    if (q_ctx->connection_severed) {
        trace_return_error_cond("connection_severed during ndo_process_nagios_config");
    }

    GENERIC_RESET_SQL();
    GENERIC_RESET_BIND();

    GENERIC_SET_SQL("INSERT INTO nagios_configfiles (instance_id, configfile_type, configfile_path) VALUES (1, 0, ?)");

    GENERIC_PREPARE();

    GENERIC_BIND_STR(config_file);

    GENERIC_BIND();
    GENERIC_EXECUTE();

    nagios_config_file_id = mysql_insert_id(q_ctx->conn);

    GENERIC_RESET_SQL();
    GENERIC_RESET_BIND();

    GENERIC_SET_SQL("INSERT INTO nagios_configfilevariables (instance_id, configfile_id, varname, varvalue) VALUES (1, ?, ?, ?)");

    GENERIC_PREPARE();

    ndo_process_file(q_ctx, config_file, ndo_process_nagios_config_line);

    trace_return_ok();
}

int ndo_write_runtime_variables(ndo_query_context *q_ctx, sched_info scheduling_info)
{
    trace_func_void();
    int ndo_return = NDO_OK;

    if (q_ctx->connection_severed) {
        trace_return_error_cond("connection_severed during ndo_write_runtime_variables");
    }

    char * varname[19] = { NULL };

    GENERIC_RESET_SQL();
    GENERIC_RESET_BIND();

    GENERIC_SET_SQL("INSERT INTO nagios_runtimevariables (instance_id, varname, varvalue) VALUES (1, ?, ?),(1, ?, ?),(1, ?, ?),(1, ?, ?),(1, ?, ?),(1, ?, ?),(1, ?, ?),(1, ?, ?),(1, ?, ?),(1, ?, ?),(1, ?, ?),(1, ?, ?),(1, ?, ?),(1, ?, ?),(1, ?, ?),(1, ?, ?),(1, ?, ?),(1, ?, ?),(1, ?, ?) ON DUPLICATE KEY UPDATE varname = VALUES(varname), varvalue = VALUES(varvalue)");

    GENERIC_PREPARE();

    varname[0] = "total_services";
    GENERIC_BIND_STR(varname[0]);
    GENERIC_BIND_INT(scheduling_info.total_services);

    varname[1] = "total_scheduled_services";
    GENERIC_BIND_STR(varname[1]);
    GENERIC_BIND_INT(scheduling_info.total_scheduled_services);

    varname[2] = "total_hosts";
    GENERIC_BIND_STR(varname[2]);
    GENERIC_BIND_INT(scheduling_info.total_hosts);

    varname[3] = "total_scheduled_hosts";
    GENERIC_BIND_STR(varname[3]);
    GENERIC_BIND_INT(scheduling_info.total_scheduled_hosts);

    varname[4] = "average_services_per_host";
    GENERIC_BIND_STR(varname[4]);
    GENERIC_BIND_DOUBLE(scheduling_info.average_services_per_host);

    varname[5] = "average_scheduled_services_per_host";
    GENERIC_BIND_STR(varname[5]);
    GENERIC_BIND_DOUBLE(scheduling_info.average_scheduled_services_per_host);

    varname[6] = "service_check_interval_total";
    GENERIC_BIND_STR(varname[6]);
    GENERIC_BIND_LONG(scheduling_info.service_check_interval_total);

    varname[7] = "host_check_interval_total";
    GENERIC_BIND_STR(varname[7]);
    GENERIC_BIND_LONG(scheduling_info.host_check_interval_total);

    varname[8] = "average_service_check_interval";
    GENERIC_BIND_STR(varname[8]);
    GENERIC_BIND_DOUBLE(scheduling_info.average_service_check_interval);

    varname[9] = "average_host_check_interval";
    GENERIC_BIND_STR(varname[9]);
    GENERIC_BIND_DOUBLE(scheduling_info.average_host_check_interval);

    varname[10] = "average_service_inter_check_delay";
    GENERIC_BIND_STR(varname[10]);
    GENERIC_BIND_DOUBLE(scheduling_info.average_service_inter_check_delay);

    varname[11] = "average_host_inter_check_delay";
    GENERIC_BIND_STR(varname[11]);
    GENERIC_BIND_DOUBLE(scheduling_info.average_host_inter_check_delay);

    varname[12] = "service_inter_check_delay";
    GENERIC_BIND_STR(varname[12]);
    GENERIC_BIND_DOUBLE(scheduling_info.service_inter_check_delay);

    varname[13] = "host_inter_check_delay";
    GENERIC_BIND_STR(varname[13]);
    GENERIC_BIND_DOUBLE(scheduling_info.host_inter_check_delay);

    varname[14] = "service_interleave_factor";
    GENERIC_BIND_STR(varname[14]);
    GENERIC_BIND_INT(scheduling_info.service_interleave_factor);

    varname[15] = "max_service_check_spread";
    GENERIC_BIND_STR(varname[15]);
    GENERIC_BIND_INT(scheduling_info.max_service_check_spread);

    varname[16] = "max_host_check_spread";
    GENERIC_BIND_STR(varname[16]);
    GENERIC_BIND_INT(scheduling_info.max_host_check_spread);

    /* These BIND macros take the memory location of the value that gets passed in,
     * so we need to define an lvalue to hold our constant
     */
    int zero_lvalue = 0;
    varname[17] = "rebalance_lock";
    GENERIC_BIND_STR(varname[17]);
    GENERIC_BIND_INT(zero_lvalue);

    varname[18] = "last_rebalance";
    GENERIC_BIND_STR(varname[18]);
    GENERIC_BIND_INT(zero_lvalue);

    GENERIC_BIND();
    GENERIC_EXECUTE();

    trace_return_ok();
}

int ndo_process_nagios_config_line(ndo_query_context *q_ctx, char * line)
{
    trace_func_args("line=%s", line);
    int ndo_return = NDO_OK;

    if (q_ctx->connection_severed) {
        trace_return_error_cond("connection_severed in ndo_process_nagios_config_line");
    }

    char * key = NULL;
    char * val = NULL;

    if (line == NULL) {
        trace_return_ok_cond("line == NULL");
    }

    key = strtok(line, "=");
    if (key == NULL) {
        trace_return_ok_cond("key == NULL");
    }

    val = strtok(NULL, "\0");
    if (val == NULL) {
        trace_return_ok_cond("val == NULL");
    }

    key = ndo_strip(key);
    if (key == NULL || strlen(key) == 0) {
        trace_return_ok_cond("key == NULL || strlen(key) == 0");
    }

    val = ndo_strip(val);

    if (val == NULL || strlen(val) == 0) {

        free(key);

        if (val != NULL) {
            free(val);
        }

        trace_return_ok_cond("val == NULL || strlen(val) == 0");
    }

    /* skip comments */
    if (key[0] == '#' || key[0] == ';') {
        free(key);
        free(val);
        trace_return_ok_cond("key[0] == '#' || key[0] == ';'");
    }

    else {

        q_ctx->bind_i[GENERIC] = 0;

        GENERIC_BIND_INT(nagios_config_file_id);
        GENERIC_BIND_STR(key);
        GENERIC_BIND_STR(val);

        GENERIC_BIND();
        GENERIC_EXECUTE();
    }

    free(key);
    free(val);

    trace_return_ok();
}

// LCOV_EXCL_START 

int ndo_write_object_config(ndo_query_context *q_ctx, int config_type, ndo_queue_coordinator *coordinator)
{
    trace_func_args("config_type=%d", config_type);

    ndo_write_timing("ndo_write_object_config begin");
    int write_result;

    ndo_writing_object_configuration = TRUE;

    write_result = ndo_write_commands(q_ctx, config_type, command_list);
    pthread_mutex_unlock(&(coordinator->finished_ndo_write_commands));
    if (write_result != NDO_OK) {
        ndo_log("ndo_write_commands() failed. Disabling NDO.", NSLOG_RUNTIME_ERROR);
        ndo_failed_load = TRUE;
        return write_result;
    }
    ndo_write_timing("ndo_write_commands");

    write_result = ndo_write_timeperiods(q_ctx, config_type, timeperiod_list);
    pthread_mutex_unlock(&(coordinator->finished_ndo_write_timeperiods));
    if (write_result != NDO_OK) {
        ndo_log("ndo_write_timeperiods() failed. Disabling NDO.", NSLOG_RUNTIME_ERROR);
        ndo_failed_load = TRUE;
        return write_result;
    }
    ndo_write_timing("ndo_write_timeperiods");

    write_result = ndo_write_contacts(q_ctx, config_type, contact_list);
    pthread_mutex_unlock(&(coordinator->finished_ndo_write_contacts));
    if (write_result != NDO_OK) {
        ndo_log("ndo_write_contacts() failed. Disabling NDO.", NSLOG_RUNTIME_ERROR);
        ndo_failed_load = TRUE;
        return write_result;
    }
    ndo_write_timing("ndo_write_contacts");

    write_result = ndo_write_contactgroups(q_ctx, config_type, contactgroup_list);
    pthread_mutex_unlock(&(coordinator->finished_ndo_write_contactgroups));
    if (write_result != NDO_OK) {
        ndo_log("ndo_write_contactgroups() failed. Disabling NDO.", NSLOG_RUNTIME_ERROR);
        ndo_failed_load = TRUE;
        return write_result;
    }
    ndo_write_timing("ndo_write_contactgroups");

    write_result = ndo_write_hosts(q_ctx, config_type, host_list);
    pthread_mutex_unlock(&(coordinator->finished_ndo_write_hosts));
    if (write_result != NDO_OK) {
        ndo_log("ndo_write_hosts() failed. Disabling NDO.", NSLOG_RUNTIME_ERROR);
        ndo_failed_load = TRUE;
        return write_result;
    }
    ndo_write_timing("ndo_write_hosts");
    
    write_result = ndo_write_hostgroups(q_ctx, config_type, hostgroup_list);
    pthread_mutex_unlock(&(coordinator->finished_ndo_write_hostgroups));
    if (write_result != NDO_OK) {
        ndo_log("ndo_write_hostgroups() failed. Disabling NDO.", NSLOG_RUNTIME_ERROR);
        ndo_failed_load = TRUE;
        return write_result;
    }
    ndo_write_timing("ndo_write_hostgroups");

    write_result = ndo_write_services(q_ctx, config_type, service_list);
    pthread_mutex_unlock(&(coordinator->finished_ndo_write_services));
    if (write_result != NDO_OK) {
        ndo_log("ndo_write_services() failed. Disabling NDO.", NSLOG_RUNTIME_ERROR);
        ndo_failed_load = TRUE;
        return write_result;
    }
    ndo_write_timing("ndo_write_services");

    write_result = ndo_write_servicegroups(q_ctx, config_type, servicegroup_list);
    pthread_mutex_unlock(&(coordinator->finished_ndo_write_servicegroups));
    if (write_result != NDO_OK) {
        ndo_log("ndo_write_servicegroups() failed. Disabling NDO.", NSLOG_RUNTIME_ERROR);
        ndo_failed_load = TRUE;
        return write_result;
    }
    ndo_write_timing("ndo_write_servicegroups");

    write_result = ndo_write_hostescalations(q_ctx, config_type, hostescalation_ary);
    pthread_mutex_unlock(&(coordinator->finished_ndo_write_hostescalations));
    if (write_result != NDO_OK) {
        ndo_log("ndo_write_hostescalations() failed. Disabling NDO.", NSLOG_RUNTIME_ERROR);
        ndo_failed_load = TRUE;
        return write_result;
    }
    ndo_write_timing("ndo_write_hostescalations");

    write_result = ndo_write_serviceescalations(q_ctx, config_type, serviceescalation_ary);
    pthread_mutex_unlock(&(coordinator->finished_ndo_write_serviceescalations));
    if (write_result != NDO_OK) {
        ndo_log("ndo_write_serviceescalations() failed. Disabling NDO.", NSLOG_RUNTIME_ERROR);
        ndo_failed_load = TRUE;
        return write_result;
    }
    ndo_write_timing("ndo_write_serviceescalations");

    ndo_writing_object_configuration = FALSE;
    write_result = ndo_set_loaded_runtimevariable(q_ctx);
    if (write_result != NDO_OK) {
        ndo_log("ndo_set_loaded_runtimevariable() failed. May not be able to tell that NDO is finished loading.", NSLOG_RUNTIME_ERROR);
        /* we don't need to disable NDO in this case - this is just to verify that NDO has finished loading */
    }

    ndo_write_timing("ndo_write_object_config end");

    write_result = ndo_cleanup_inactive_statusinfo(q_ctx);
    if (write_result != NDO_OK) {
        ndo_log("ndo_cleanup_inactive_statusinfo() failed. Some status information may be inaccurate.", NSLOG_RUNTIME_ERROR);
        /* we don't need to disable NDO in this case - some information will be incorrect, but the status table is still useful */
    }
    ndo_close_timing();

    trace_return_ok();
}



int ndo_write_db_init(ndo_query_context * q_ctx)
{
    trace_func_void();

    q_ctx->conn = mysql_init(NULL);
    int result = ndo_connect_database(q_ctx);
    if (result != NDO_OK) {
        trace_return_init_error_cond("ndo_connect_database() != NDO_OK");
    }

    trace_return_ok();
}

// LCOV_EXCL_STOP


int send_subquery(ndo_query_context *q_ctx, int stmt, int * counter, char * query, char * query_on_update, size_t * query_len, size_t query_base_len, size_t query_on_update_len)
{
    trace_func_args("stmt=%d, *counter=%d, query=%s, query_on_update=%s, *query_len=%zu, query_base_lan=%zu, query_on_update_len=%zu", stmt, *counter, query, query_on_update, *query_len, query_base_len, query_on_update_len);
    int ndo_return = NDO_OK;

    strncpy(q_ctx->query[stmt], query, *query_len);
    strncpy(q_ctx->query[stmt] + (*query_len) - 1, query_on_update, query_on_update_len);
    q_ctx->query[stmt][(*query_len) + query_on_update_len - 1] = 0;

    _MYSQL_PREPARE(q_ctx->stmt[stmt], q_ctx->query[stmt]);
    MYSQL_BIND(stmt);
    MYSQL_EXECUTE(stmt);

    memset(query + query_base_len, 0, MAX_SQL_BUFFER - query_base_len);

    *query_len = query_base_len;
    *counter = 0;
    q_ctx->bind_i[stmt] = 0;

    trace_return_ok();
}


int ndo_write_commands(ndo_query_context *q_ctx, int config_type, command *command_list)
{
    trace_func_args("config_type=%d", config_type);
    int ndo_return = NDO_OK;

    command * tmp = command_list;
    int object_id = 0;

    GENERIC_RESET_SQL();

    GENERIC_SET_SQL("INSERT INTO nagios_commands (instance_id, object_id, config_type, command_line) VALUES (1,?,?,?) ON DUPLICATE KEY UPDATE instance_id = VALUES(instance_id), object_id = VALUES(object_id), config_type = VALUES(config_type), command_line = VALUES(command_line)");

    GENERIC_PREPARE();
    GENERIC_RESET_BIND();

    while (tmp != NULL) {

        q_ctx->bind_i[GENERIC] = 0;

        object_id = ndo_get_object_id_name1(q_ctx, TRUE, NDO_OBJECTTYPE_COMMAND, tmp->name);

        GENERIC_BIND_INT(object_id);
        GENERIC_BIND_INT(config_type);
        GENERIC_BIND_STR(tmp->command_line);

        GENERIC_BIND();
        GENERIC_EXECUTE();

        tmp = tmp->next;
    }

    trace_return_ok();
}


int ndo_write_timeperiods(ndo_query_context *q_ctx, int config_type, timeperiod *timeperiod_list)
{
    trace_func_args("config_type=%d", config_type);
    int ndo_return = NDO_OK;

    timeperiod * tmp = timeperiod_list;
    int object_id = 0;
    int i = 0;

    int * timeperiod_ids = NULL;

    timeperiod_ids = calloc(num_objects.timeperiods, sizeof(int));

    GENERIC_RESET_SQL();

    GENERIC_SET_SQL("INSERT INTO nagios_timeperiods (instance_id, timeperiod_object_id, config_type, alias) VALUES (1,?,?,?) ON DUPLICATE KEY UPDATE instance_id = VALUES(instance_id), timeperiod_object_id = VALUES(timeperiod_object_id), config_type = VALUES(config_type), alias = VALUES(alias)");

    GENERIC_PREPARE();
    GENERIC_RESET_BIND();

    while (tmp != NULL) {

        q_ctx->bind_i[GENERIC] = 0;

        object_id = ndo_get_object_id_name1(q_ctx, TRUE, NDO_OBJECTTYPE_TIMEPERIOD, tmp->name);

        GENERIC_BIND_INT(object_id);
        GENERIC_BIND_INT(config_type);
        GENERIC_BIND_STR(tmp->alias);

        GENERIC_BIND();
        GENERIC_EXECUTE();

        timeperiod_ids[i] = mysql_insert_id(q_ctx->conn);
        i++;
        tmp = tmp->next;
    }

    ndo_write_timeperiod_timeranges(q_ctx, timeperiod_ids, timeperiod_list);
    ndo_write_timeperiod_exceptions(q_ctx, timeperiod_ids, timeperiod_list);
    ndo_write_timeperiod_exclusions(q_ctx, timeperiod_ids, timeperiod_list);

    free(timeperiod_ids);

    trace_return_ok();
}


int ndo_write_timeperiod_timeranges(ndo_query_context *q_ctx, int * timeperiod_ids, timeperiod *timeperiod_list)
{
    trace_func_args("timeperiod_ids=%p", timeperiod_ids);
    int ndo_return = NDO_OK;
    
    timeperiod * tmp = timeperiod_list;
    timerange * range = NULL;
    int i = 0;

    int day = 0;

    GENERIC_RESET_SQL();

    GENERIC_SET_SQL("INSERT INTO nagios_timeperiod_timeranges (instance_id, timeperiod_id, day, start_sec, end_sec) VALUES (1,?,?,?,?) ON DUPLICATE KEY UPDATE instance_id = VALUES(instance_id), timeperiod_id = VALUES(timeperiod_id), day = VALUES(day), start_sec = VALUES(start_sec), end_sec = VALUES(end_sec)");

    GENERIC_PREPARE();
    GENERIC_RESET_BIND();

    while (tmp != NULL) {

        for (day = 0; day < 7; day++) {
            for (range = tmp->days[day]; range != NULL; range = range->next) {

                q_ctx->bind_i[GENERIC] = 0;

                GENERIC_BIND_INT(timeperiod_ids[i]);
                GENERIC_BIND_INT(day);
                GENERIC_BIND_INT(range->range_start);
                GENERIC_BIND_INT(range->range_end);

                GENERIC_BIND();
                GENERIC_EXECUTE();
            }
        }

        i++;
        tmp = tmp->next;
    }

    trace_return_ok();
}

int ndo_write_timeperiod_exclusions(ndo_query_context *q_ctx, int * timeperiod_ids, timeperiod *timeperiod_list)
{

    trace_func_args("timeperiod_ids=%p", timeperiod_ids);
    int ndo_return = NDO_OK;

    GENERIC_RESET_SQL();
    // This is a many-to-many assignment, and we truncate on restart, so there's never a reason to update an existing entry.
    GENERIC_SET_SQL("INSERT INTO nagios_timeperiod_exclusions (parent_id, child_id) "
                    "VALUES (?,?)");
    
    GENERIC_PREPARE();
    GENERIC_RESET_BIND();

    timeperiod * tmp = timeperiod_list;
    int exception_type = 0;
    int parent_index = 0;
    for (;tmp != NULL; tmp = tmp->next) {

        timeperiodexclusion *current_exclusion = NULL;
        for (current_exclusion = tmp->exclusions; current_exclusion != NULL; current_exclusion = current_exclusion->next) {
            int excluded_timeperiod_internal_id = current_exclusion->timeperiod_ptr->id;

            // Using internal IDs to verify timeperiod equality, translate the parent/child to indices in our timeperiod_ids list.
            int child_index = 0;
            int found_child = 0;
            timeperiod *child = timeperiod_list;
            for (child = timeperiod_list; child != NULL; child = child->next) {
                if (child->id == excluded_timeperiod_internal_id) {
                    found_child = 1;
                    break;
                } 
                ++child_index;
            }

            if (!found_child) {
                // We couldn't find the timeperiod that we're excluding in the list of timeperiods that we wrote to DB.
                // We probably want to error, but for now skip this exclusion
                continue;
            }

            // Now turn the indices in our list into database IDs.
            GENERIC_BIND_INT(timeperiod_ids[parent_index]);
            GENERIC_BIND_INT(timeperiod_ids[child_index]);

            GENERIC_BIND();
            GENERIC_EXECUTE();
        }

        ++parent_index;
    }

    trace_return_ok();
}

int ndo_write_timeperiod_exceptions(ndo_query_context *q_ctx, int * timeperiod_ids, timeperiod *timeperiod_list)
{
    trace_func_args("timeperiod_ids=%p", timeperiod_ids);
    int ndo_return = NDO_OK;
    
    timeperiod * tmp = timeperiod_list;
    daterange * range = NULL;
    timerange * range_time = NULL;
    int i = 0;
    int j = 0;

    int exception_type = 0;
    int num_timeperiod_exceptions = 0;
    for (;tmp != NULL; tmp = tmp->next) {

        for (exception_type = 0; exception_type < DATERANGE_TYPES; ++exception_type) {
            for (range = tmp->exceptions[exception_type]; range != NULL; range = range->next) {
                ++num_timeperiod_exceptions;
            }
        }
    }

    int *timeperiod_exception_ids = calloc(num_timeperiod_exceptions, sizeof(int));

    GENERIC_RESET_SQL();

    GENERIC_SET_SQL("INSERT INTO nagios_timeperiod_exceptions (instance_id, timeperiod_id, exception_type, syear, smon, smday, swday, swday_offset, eyear, emon, emday, ewday, ewday_offset, skip_interval) "
                    "VALUES (1,?,?,?,?,?,?,?,?,?,?,?,?,?) "
                    "ON DUPLICATE KEY UPDATE "
                    "instance_id = VALUES(instance_id), "
                    "timeperiod_id = VALUES(timeperiod_id), "
                    "exception_type = VALUES(exception_type), "
                    "syear = VALUES(syear), "
                    "smon = VALUES(smon), "
                    "smday = VALUES(smday), "
                    "swday = VALUES(swday), "
                    "swday_offset = VALUES(swday_offset), "
                    "eyear = VALUES(eyear), "
                    "emon = VALUES(emon), "
                    "emday = VALUES(emday), "
                    "ewday = VALUES(ewday), "
                    "ewday_offset = VALUES(ewday_offset), "
                    "skip_interval = VALUES(skip_interval)");

    GENERIC_PREPARE();
    GENERIC_RESET_BIND();

    tmp = timeperiod_list;
    i = 0;
    while (tmp != NULL) {

        for (exception_type = 0; exception_type < DATERANGE_TYPES; ++exception_type) {
            for (range = tmp->exceptions[exception_type]; range != NULL; range = range->next) {

                q_ctx->bind_i[GENERIC] = 0;

                GENERIC_BIND_INT(timeperiod_ids[i]);
                GENERIC_BIND_INT(exception_type);
                GENERIC_BIND_INT(range->syear);
                GENERIC_BIND_INT(range->smon);
                GENERIC_BIND_INT(range->smday);
                GENERIC_BIND_INT(range->swday);
                GENERIC_BIND_INT(range->swday_offset);
                GENERIC_BIND_INT(range->eyear);
                GENERIC_BIND_INT(range->emon);
                GENERIC_BIND_INT(range->emday);
                GENERIC_BIND_INT(range->ewday);
                GENERIC_BIND_INT(range->ewday_offset);
                GENERIC_BIND_INT(range->skip_interval);

                GENERIC_BIND();
                GENERIC_EXECUTE();

                timeperiod_exception_ids[j++] = mysql_insert_id(q_ctx->conn);
            }
        }

        i++;
        tmp = tmp->next;
    }


    GENERIC_RESET_SQL();
    GENERIC_SET_SQL("INSERT INTO nagios_timeperiod_exception_timeranges (instance_id, timeperiod_exception_id, start_sec, end_sec) "
                    "VALUES (1,?,?,?) ON DUPLICATE KEY UPDATE "
                    "instance_id = VALUES(instance_id), "
                    "timeperiod_exception_id = VALUES(timeperiod_exception_id), "
                    "start_sec = VALUES(start_sec), "
                    "end_sec = VALUES(end_sec)");

    GENERIC_PREPARE();
    GENERIC_RESET_BIND();

    tmp = timeperiod_list;
    j = 0;
    while (tmp != NULL) {
        for (exception_type = 0; exception_type < DATERANGE_TYPES; ++exception_type) {
            for (range = tmp->exceptions[exception_type]; range != NULL; range = range->next) {
                for (range_time = range->times; range_time != NULL; range_time = range_time->next) {

                    q_ctx->bind_i[GENERIC] = 0;

                    GENERIC_BIND_INT(timeperiod_exception_ids[j]);
                    GENERIC_BIND_INT(range_time->range_start);
                    GENERIC_BIND_INT(range_time->range_end);
                    GENERIC_BIND();
                    GENERIC_EXECUTE();

                }

                j++;
            }
        }

        tmp = tmp->next;
    }

    trace_return_ok();
}

int ndo_write_contacts(ndo_query_context *q_ctx, int config_type, contact * contact_list)
{
    trace_func_args("config_type=%d", config_type);
    int ndo_return = NDO_OK;

    contact * tmp = contact_list;
    int i = 0;

    int max_object_insert_count = 0;
    int loops = 0;
    int loop = 0;
    int write_query = FALSE;
    int dont_reset_query = FALSE;

    size_t cur_pos = 0;

    int object_ids[MAX_OBJECT_INSERT] = { 0 };
    int host_timeperiod_object_id[MAX_OBJECT_INSERT] = { 0 };
    int service_timeperiod_object_id[MAX_OBJECT_INSERT] = { 0 };

    char *query = q_ctx->query[WRITE_CONTACTS];

    char query_base[] = "INSERT INTO nagios_contacts (instance_id, config_type, contact_object_id, alias, email_address, pager_address, host_timeperiod_object_id, service_timeperiod_object_id, host_notifications_enabled, service_notifications_enabled, can_submit_commands, notify_service_recovery, notify_service_warning, notify_service_unknown, notify_service_critical, notify_service_flapping, notify_service_downtime, notify_host_recovery, notify_host_down, notify_host_unreachable, notify_host_flapping, notify_host_downtime, retain_status_information, retain_nonstatus_information, minimum_importance) VALUES ";
    size_t query_base_len = STRLIT_LEN(query_base);
    size_t query_len = query_base_len;

    char query_values[] = "(1,?,?,?,?,?,?,?,X,X,X,X,X,X,X,X,X,X,X,X,X,X,X,X,?),";
    size_t query_values_len = STRLIT_LEN(query_values);

    char query_on_update[] = " ON DUPLICATE KEY UPDATE instance_id = VALUES(instance_id), config_type = VALUES(config_type), contact_object_id = VALUES(contact_object_id), alias = VALUES(alias), email_address = VALUES(email_address), pager_address = VALUES(pager_address), host_timeperiod_object_id = VALUES(host_timeperiod_object_id), service_timeperiod_object_id = VALUES(service_timeperiod_object_id), host_notifications_enabled = VALUES(host_notifications_enabled), service_notifications_enabled = VALUES(service_notifications_enabled), can_submit_commands = VALUES(can_submit_commands), notify_service_recovery = VALUES(notify_service_recovery), notify_service_warning = VALUES(notify_service_warning), notify_service_unknown = VALUES(notify_service_unknown), notify_service_critical = VALUES(notify_service_critical), notify_service_flapping = VALUES(notify_service_flapping), notify_service_downtime = VALUES(notify_service_downtime), notify_host_recovery = VALUES(notify_host_recovery), notify_host_down = VALUES(notify_host_down), notify_host_unreachable = VALUES(notify_host_unreachable), notify_host_flapping = VALUES(notify_host_flapping), notify_host_downtime = VALUES(notify_host_downtime), retain_status_information = VALUES(retain_status_information), retain_nonstatus_information = VALUES(retain_nonstatus_information), minimum_importance = VALUES(minimum_importance)";
    size_t query_on_update_len = STRLIT_LEN(query_on_update);
    /*
    ndo_return = mysql_query(startup_connection, "LOCK TABLES nagios_logentries WRITE, nagios_objects WRITE, nagios_contacts WRITE");
    if (ndo_return != 0) {
        char msg[1024];
        snprintf(msg, 1023, "ret = %d, (%d) %s", ndo_return, mysql_errno(startup_connection), mysql_error(startup_connection));
        //ndo_log(msg);
        return NDO_ERROR;
    }
*/

    strcpy(query, query_base);

    max_object_insert_count = ndo_max_object_insert_count;
    while ((max_object_insert_count * query_values_len + query_base_len + query_on_update_len) > (MAX_SQL_BUFFER - 1)) {
        max_object_insert_count--;
    }

    loops = num_objects.contacts / max_object_insert_count;

    if (num_objects.contacts % max_object_insert_count != 0) {
        loops++;
    }

    /* if num contacts is evenly divisible, we never need to write 
       the query after the first time */
    else {
        dont_reset_query = TRUE;
    }

    write_query = TRUE;
    loop = 1;

    MYSQL_RESET_BIND(WRITE_CONTACTS);

    while (tmp != NULL) {

        if (write_query == TRUE) {
            memcpy(query + query_len, query_values, query_values_len);
            query_len += query_values_len;
        }
        /* put our "cursor" at the beginning of whichever query_values we are at
           specifically at the '(' character of current values section */
        cur_pos = query_base_len + (i * query_values_len);

        object_ids[i] = ndo_get_object_id_name1(q_ctx, TRUE, NDO_OBJECTTYPE_CONTACT, tmp->name);

        host_timeperiod_object_id[i] = 0;
        if (tmp->host_notification_period != NULL) {
            host_timeperiod_object_id[i] = ndo_get_object_id_name1(q_ctx, TRUE, NDO_OBJECTTYPE_TIMEPERIOD, tmp->host_notification_period);
        }
        service_timeperiod_object_id[i] = 0;
        if (tmp->service_notification_period != NULL) {
            service_timeperiod_object_id[i] = ndo_get_object_id_name1(q_ctx, TRUE, NDO_OBJECTTYPE_TIMEPERIOD, tmp->service_notification_period);
        }

        MYSQL_BIND_INT(WRITE_CONTACTS, config_type);
        MYSQL_BIND_INT(WRITE_CONTACTS, object_ids[i]);
        MYSQL_BIND_STR(WRITE_CONTACTS, tmp->alias);
        MYSQL_BIND_STR(WRITE_CONTACTS, tmp->email);
        MYSQL_BIND_STR(WRITE_CONTACTS, tmp->pager);
        MYSQL_BIND_INT(WRITE_CONTACTS, host_timeperiod_object_id[i]);
        MYSQL_BIND_INT(WRITE_CONTACTS, service_timeperiod_object_id[i]);

        UPDATE_QUERY_X_POS(query, cur_pos, 17, tmp->host_notifications_enabled);
        UPDATE_QUERY_X_POS(query, cur_pos, 19, tmp->service_notifications_enabled);
        UPDATE_QUERY_X_POS(query, cur_pos, 21, tmp->can_submit_commands);

        UPDATE_QUERY_X_POS(query, cur_pos, 23, flag_isset(tmp->service_notification_options, OPT_RECOVERY));
        UPDATE_QUERY_X_POS(query, cur_pos, 25, flag_isset(tmp->service_notification_options, OPT_WARNING));
        UPDATE_QUERY_X_POS(query, cur_pos, 27, flag_isset(tmp->service_notification_options, OPT_UNKNOWN));
        UPDATE_QUERY_X_POS(query, cur_pos, 29, flag_isset(tmp->service_notification_options, OPT_CRITICAL));
        UPDATE_QUERY_X_POS(query, cur_pos, 31, flag_isset(tmp->service_notification_options, OPT_FLAPPING));
        UPDATE_QUERY_X_POS(query, cur_pos, 33, flag_isset(tmp->service_notification_options, OPT_DOWNTIME));
        UPDATE_QUERY_X_POS(query, cur_pos, 35, flag_isset(tmp->host_notification_options, OPT_RECOVERY));
        UPDATE_QUERY_X_POS(query, cur_pos, 37, flag_isset(tmp->host_notification_options, OPT_DOWN));
        UPDATE_QUERY_X_POS(query, cur_pos, 39, flag_isset(tmp->host_notification_options, OPT_UNREACHABLE));
        UPDATE_QUERY_X_POS(query, cur_pos, 41, flag_isset(tmp->host_notification_options, OPT_FLAPPING));
        UPDATE_QUERY_X_POS(query, cur_pos, 43, flag_isset(tmp->host_notification_options, OPT_DOWNTIME));
        UPDATE_QUERY_X_POS(query, cur_pos, 45, tmp->retain_status_information);
        UPDATE_QUERY_X_POS(query, cur_pos, 47, tmp->retain_nonstatus_information);

        MYSQL_BIND_INT(WRITE_CONTACTS, tmp->minimum_value);

        i++;

        /* we need to finish the query and execute */
        if (i >= max_object_insert_count || tmp->next == NULL) {

            if (write_query == TRUE) {
                memcpy(query + query_len - 1, query_on_update, query_on_update_len);
                query_len += query_on_update_len;
            }

            if (loop == 1 || loop == loops) {
                _MYSQL_PREPARE(q_ctx->stmt[WRITE_CONTACTS], query);
            }
            MYSQL_BIND(WRITE_CONTACTS);
            MYSQL_EXECUTE(WRITE_CONTACTS);

            q_ctx->bind_i[WRITE_CONTACTS] = 0;
            i = 0;
            write_query = FALSE;

            /* if we're on the second to last loop we reset to build the final query */
            if (loop == loops - 1 && dont_reset_query == FALSE) {
                memset(query + query_base_len, 0, MAX_SQL_BUFFER - query_base_len);
                query_len = query_base_len;
                write_query = TRUE;
            }

            loop++;
        }

        tmp = tmp->next;
    }
    /*
    ndo_return = mysql_query(startup_connection, "UNLOCK TABLES");
    if (ndo_return != 0) {
        char msg[1024];
        snprintf(msg, 1023, "ret = %d, (%d) %s", ndo_return, mysql_errno(startup_connection), mysql_error(startup_connection));
        //ndo_log(msg);
    }
*/

    int write_result = ndo_write_contact_objects(q_ctx, config_type, contact_list);
    if (write_result != NDO_OK) {
        return write_result;
    }

    trace_return_ok();
}


int ndo_write_contact_objects(ndo_query_context *q_ctx, int config_type, contact * contact_list)
{
    trace_func_args("config_type=%d", config_type);
    int ndo_return = NDO_OK;

    contact * tmp = contact_list;

    int address_number = 0;
    commandsmember * cmd = NULL;
    customvariablesmember * var = NULL;

    int max_object_insert_count = 0;
    int subquery_result = NDO_OK;

    int host_notification_command_type = HOST_NOTIFICATION;
    int service_notification_command_type = SERVICE_NOTIFICATION;

    int addresses_count = 0;
    char addresses_query[MAX_SQL_BUFFER] = { 0 };
    char addresses_query_base[] = "INSERT INTO nagios_contact_addresses (instance_id, contact_id, address_number, address) VALUES ";
    size_t addresses_query_base_len = STRLIT_LEN(addresses_query_base);
    size_t addresses_query_len = addresses_query_base_len;
    char addresses_query_values[] = "(1,(SELECT contact_id FROM nagios_contacts WHERE contact_object_id = (SELECT object_id FROM nagios_objects WHERE objecttype_id = 10 AND name1 = ? AND is_active = 1)),?,?),";
    size_t addresses_query_values_len = STRLIT_LEN(addresses_query_values);
    char addresses_query_on_update[] = " ON DUPLICATE KEY UPDATE instance_id = VALUES(instance_id), contact_id = VALUES(contact_id), address_number = VALUES(address_number), address = VALUES(address)";
    size_t addresses_query_on_update_len = STRLIT_LEN(addresses_query_on_update);

    int notificationcommands_count = 0;
    char notificationcommands_query[MAX_SQL_BUFFER] = { 0 };
    char notificationcommands_query_base[] = "INSERT INTO nagios_contact_notificationcommands (instance_id, contact_id, notification_type, command_object_id) VALUES ";
    size_t notificationcommands_query_base_len = STRLIT_LEN(notificationcommands_query_base);
    size_t notificationcommands_query_len = notificationcommands_query_base_len;
    char notificationcommands_query_values[] = "(1,(SELECT contact_id FROM nagios_contacts WHERE contact_object_id = (SELECT object_id FROM nagios_objects WHERE objecttype_id = 10 AND name1 = ? AND is_active = 1)),?,(SELECT object_id FROM nagios_objects WHERE objecttype_id = 12 AND name1 = ? LIMIT 1)),";
    size_t notificationcommands_query_values_len = STRLIT_LEN(notificationcommands_query_values);
    char notificationcommands_query_on_update[] = " ON DUPLICATE KEY UPDATE instance_id = VALUES(instance_id), contact_id = VALUES(contact_id), notification_type = VALUES(notification_type), command_object_id = VALUES(command_object_id)";
    size_t notificationcommands_query_on_update_len = STRLIT_LEN(notificationcommands_query_on_update);

    int var_count = 0;
    char var_query[MAX_SQL_BUFFER] = { 0 };
    char var_query_base[] = "INSERT INTO nagios_customvariables (instance_id, object_id, config_type, has_been_modified, varname, varvalue) VALUES ";
    size_t var_query_base_len = STRLIT_LEN(var_query_base);
    size_t var_query_len = var_query_base_len;
    char var_query_values[] = "(1,(SELECT object_id FROM nagios_objects WHERE objecttype_id = 10 AND name1 = ? AND is_active = 1 LIMIT 1),?,?,?,?),";
    size_t var_query_values_len = STRLIT_LEN(var_query_values);
    char var_query_on_update[] = " ON DUPLICATE KEY UPDATE instance_id = VALUES(instance_id), object_id = VALUES(object_id), config_type = VALUES(config_type), has_been_modified = VALUES(has_been_modified), varname = VALUES(varname), varvalue = VALUES(varvalue)";
    size_t var_query_on_update_len = STRLIT_LEN(var_query_on_update);

    int var_status_count = 0;
    char var_status_query[MAX_SQL_BUFFER] = { 0 };
    char var_status_query_base[] = "INSERT INTO nagios_customvariablestatus (instance_id, object_id, status_update_time, has_been_modified, varname, varvalue) VALUES ";
    size_t var_status_query_base_len = STRLIT_LEN(var_status_query_base);
    size_t var_status_query_len = var_status_query_base_len;
    char var_status_query_values[] = "(1,(SELECT object_id FROM nagios_objects WHERE objecttype_id = 10 AND name1 = ? LIMIT 1),NOW(),?,?,?),";
    size_t var_status_query_values_len = STRLIT_LEN(var_status_query_values);
    char var_status_query_on_update[] = " ON DUPLICATE KEY UPDATE instance_id = VALUES(instance_id), object_id = VALUES(object_id), status_update_time = VALUES(status_update_time), has_been_modified = VALUES(has_been_modified), varname = VALUES(varname), varvalue = VALUES(varvalue)";
    size_t var_status_query_on_update_len = STRLIT_LEN(var_status_query_on_update);

    MYSQL_RESET_BIND(WRITE_CONTACT_ADDRESSES);
    MYSQL_RESET_BIND(WRITE_CONTACT_NOTIFICATIONCOMMANDS);
    MYSQL_RESET_BIND(WRITE_CUSTOMVARS);
    MYSQL_RESET_BIND(WRITE_CUSTOMVAR_STATUS);

    strcpy(addresses_query, addresses_query_base);
    strcpy(notificationcommands_query, notificationcommands_query_base);
    strcpy(var_query, var_query_base);
    strcpy(var_status_query, var_status_query_base);

    /* temporarily reduce max_object_insert_count so that it's guaranteed to fit inside of 
     * MAX_SQL_BUFFER for all of our queries. Otherwise, we risk a buffer overflow.
     */
    max_object_insert_count = ndo_max_object_insert_count;
    while ((max_object_insert_count * addresses_query_values_len + addresses_query_base_len + addresses_query_on_update_len) > (MAX_SQL_BUFFER - 1)) {
        max_object_insert_count--;
    }
    while ((max_object_insert_count * notificationcommands_query_values_len + notificationcommands_query_base_len + notificationcommands_query_on_update_len) > (MAX_SQL_BUFFER - 1)) {
        max_object_insert_count--;
    }
    while ((max_object_insert_count * var_query_values_len + var_query_base_len + var_query_on_update_len) > (MAX_SQL_BUFFER - 1)) {
        max_object_insert_count--;
    }
    while ((max_object_insert_count * var_status_query_values_len + var_status_query_base_len + var_status_query_on_update_len) > (MAX_SQL_BUFFER - 1)) {
        max_object_insert_count--;
    }

    while (tmp != NULL) {

        for (address_number = 1; address_number < (MAX_CONTACT_ADDRESSES + 1); address_number++) {

            strcpy(addresses_query + addresses_query_len, addresses_query_values);
            addresses_query_len += addresses_query_values_len;

            MYSQL_BIND_STR(WRITE_CONTACT_ADDRESSES, tmp->name);
            MYSQL_BIND_INT(WRITE_CONTACT_ADDRESSES, address_number);
            MYSQL_BIND_STR(WRITE_CONTACT_ADDRESSES, tmp->address[address_number - 1]);

            addresses_count++;

            if (addresses_count >= max_object_insert_count) {
                subquery_result = send_subquery(q_ctx, WRITE_CONTACT_ADDRESSES, &addresses_count, addresses_query, addresses_query_on_update, &addresses_query_len, addresses_query_base_len, addresses_query_on_update_len);
                if (subquery_result != NDO_OK) {
                    ndo_log("subquery failed to send in ndo_write_contact_objects", NSLOG_RUNTIME_ERROR);
                    return subquery_result;
                }
            }
        }


        cmd = tmp->host_notification_commands;
        while (cmd != NULL) {

            strcpy(notificationcommands_query + notificationcommands_query_len, notificationcommands_query_values);
            notificationcommands_query_len += notificationcommands_query_values_len;

            MYSQL_BIND_STR(WRITE_CONTACT_NOTIFICATIONCOMMANDS, tmp->name);
            MYSQL_BIND_INT(WRITE_CONTACT_NOTIFICATIONCOMMANDS, host_notification_command_type);
            MYSQL_BIND_STR(WRITE_CONTACT_NOTIFICATIONCOMMANDS, cmd->command);

            cmd = cmd->next;
            notificationcommands_count++;

            if (notificationcommands_count >= max_object_insert_count) {
                subquery_result = send_subquery(q_ctx, WRITE_CONTACT_NOTIFICATIONCOMMANDS, &notificationcommands_count, notificationcommands_query, notificationcommands_query_on_update, &notificationcommands_query_len, notificationcommands_query_base_len, notificationcommands_query_on_update_len);
                if (subquery_result != NDO_OK) {
                    ndo_log("subquery failed to send in ndo_write_contact_objects", NSLOG_RUNTIME_ERROR);
                    return subquery_result;
                }
            }
        }

        cmd = tmp->service_notification_commands;
        while (cmd != NULL) {

            strcpy(notificationcommands_query + notificationcommands_query_len, notificationcommands_query_values);
            notificationcommands_query_len += notificationcommands_query_values_len;

            MYSQL_BIND_STR(WRITE_CONTACT_NOTIFICATIONCOMMANDS, tmp->name);
            MYSQL_BIND_INT(WRITE_CONTACT_NOTIFICATIONCOMMANDS, service_notification_command_type);
            MYSQL_BIND_STR(WRITE_CONTACT_NOTIFICATIONCOMMANDS, cmd->command);

            cmd = cmd->next;
            notificationcommands_count++;

            if (notificationcommands_count >= max_object_insert_count) {
                subquery_result = send_subquery(q_ctx, WRITE_CONTACT_NOTIFICATIONCOMMANDS, &notificationcommands_count, notificationcommands_query, notificationcommands_query_on_update, &notificationcommands_query_len, notificationcommands_query_base_len, notificationcommands_query_on_update_len);
                if (subquery_result != NDO_OK) {
                    ndo_log("subquery failed to send in ndo_write_contact_objects", NSLOG_RUNTIME_ERROR);
                    return subquery_result;
                }
            }
        }

        var = tmp->custom_variables;
        while (var != NULL) {

            strcpy(var_query + var_query_len, var_query_values);
            var_query_len += var_query_values_len;

            MYSQL_BIND_STR(WRITE_CUSTOMVARS, tmp->name);
            MYSQL_BIND_INT(WRITE_CUSTOMVARS, config_type);
            MYSQL_BIND_INT(WRITE_CUSTOMVARS, var->has_been_modified);
            MYSQL_BIND_STR(WRITE_CUSTOMVARS, var->variable_name);
            MYSQL_BIND_STR(WRITE_CUSTOMVARS, var->variable_value);

            strcpy(var_status_query + var_status_query_len, var_status_query_values);
            var_status_query_len += var_status_query_values_len;

            MYSQL_BIND_STR(WRITE_CUSTOMVAR_STATUS, tmp->name);
            MYSQL_BIND_INT(WRITE_CUSTOMVAR_STATUS, var->has_been_modified);
            MYSQL_BIND_STR(WRITE_CUSTOMVAR_STATUS, var->variable_name);
            MYSQL_BIND_STR(WRITE_CUSTOMVAR_STATUS, var->variable_value);

            var = var->next;
            var_count++;
            var_status_count++;

            if (var_count >= max_object_insert_count) {
                subquery_result = send_subquery(q_ctx, WRITE_CUSTOMVARS, &var_count, var_query, var_query_on_update, &var_query_len, var_query_base_len, var_query_on_update_len);
                if (subquery_result != NDO_OK) {
                    ndo_log("subquery failed to send in ndo_write_contact_objects", NSLOG_RUNTIME_ERROR);
                    return subquery_result;
                }
                subquery_result = send_subquery(q_ctx, WRITE_CUSTOMVAR_STATUS, &var_status_count, var_status_query, var_status_query_on_update, &var_status_query_len, var_status_query_base_len, var_status_query_on_update_len);
                if (subquery_result != NDO_OK) {
                    ndo_log("subquery failed to send in ndo_write_contact_objects", NSLOG_RUNTIME_ERROR);
                    return subquery_result;
                }
            }
        }

        if (addresses_count > 0 && (addresses_count >= max_object_insert_count || tmp->next == NULL)) {
            subquery_result = send_subquery(q_ctx, WRITE_CONTACT_ADDRESSES, &addresses_count, addresses_query, addresses_query_on_update, &addresses_query_len, addresses_query_base_len, addresses_query_on_update_len);
            if (subquery_result != NDO_OK) {
                ndo_log("subquery failed to send in ndo_write_contact_objects", NSLOG_RUNTIME_ERROR);
                return subquery_result;
            }
        }

        if (notificationcommands_count > 0 && (notificationcommands_count >= max_object_insert_count || tmp->next == NULL)) {
            subquery_result = send_subquery(q_ctx, WRITE_CONTACT_NOTIFICATIONCOMMANDS, &notificationcommands_count, notificationcommands_query, notificationcommands_query_on_update, &notificationcommands_query_len, notificationcommands_query_base_len, notificationcommands_query_on_update_len);
            if (subquery_result != NDO_OK) {
                ndo_log("subquery failed to send in ndo_write_contact_objects", NSLOG_RUNTIME_ERROR);
                return subquery_result;
            }
        }

        if (var_count > 0 && (var_count >= max_object_insert_count || tmp->next == NULL)) {
            subquery_result = send_subquery(q_ctx, WRITE_CUSTOMVARS, &var_count, var_query, var_query_on_update, &var_query_len, var_query_base_len, var_query_on_update_len);
            if (subquery_result != NDO_OK) {
                ndo_log("subquery failed to send in ndo_write_contact_objects", NSLOG_RUNTIME_ERROR);
                return subquery_result;
            }
            subquery_result = send_subquery(q_ctx, WRITE_CUSTOMVAR_STATUS, &var_status_count, var_status_query, var_status_query_on_update, &var_status_query_len, var_status_query_base_len, var_status_query_on_update_len);
            if (subquery_result != NDO_OK) {
                ndo_log("subquery failed to send in ndo_write_contact_objects", NSLOG_RUNTIME_ERROR);
                return subquery_result;
            }
        }

        tmp = tmp->next;
    }

    trace_return_ok();
}


int ndo_write_contactgroups(ndo_query_context *q_ctx, int config_type, contactgroup * contactgroup_list)
{
    trace_func_args("config_type=%d", config_type);
    int ndo_return = NDO_OK;

    contactgroup * tmp = contactgroup_list;
    int object_id = 0;
    int i = 0;

    int * contactgroup_ids = NULL;

    contactgroup_ids = calloc(num_objects.contactgroups, sizeof(int));

    GENERIC_RESET_SQL();

    GENERIC_SET_SQL("INSERT INTO nagios_contactgroups (instance_id, contactgroup_object_id, config_type, alias) VALUES (1,?,?,?) ON DUPLICATE KEY UPDATE instance_id = VALUES(instance_id), contactgroup_object_id = VALUES(contactgroup_object_id), config_type = VALUES(config_type), alias = VALUES(alias)");

    GENERIC_PREPARE();
    GENERIC_RESET_BIND();

    while (tmp != NULL) {

        q_ctx->bind_i[GENERIC] = 0;

        object_id = ndo_get_object_id_name1(q_ctx, TRUE, NDO_OBJECTTYPE_CONTACTGROUP, tmp->group_name);


        GENERIC_BIND_INT(object_id);
        GENERIC_BIND_INT(config_type);
        GENERIC_BIND_STR(tmp->alias);

        GENERIC_BIND();
        GENERIC_EXECUTE();

        contactgroup_ids[i] = mysql_insert_id(q_ctx->conn);
        i++;
        tmp = tmp->next;
    }

    ndo_write_contactgroup_members(q_ctx, contactgroup_ids, contactgroup_list);

    free(contactgroup_ids);

    trace_return_ok();
}


int ndo_write_contactgroup_members(ndo_query_context *q_ctx, int * contactgroup_ids, contactgroup * contactgroup_list)
{
    trace_func_args("contactgroup_ids=%p", contactgroup_ids);
    int ndo_return = NDO_OK;

    contactgroup * tmp = contactgroup_list;
    contactsmember * member = NULL;
    int object_id = 0;
    int i = 0;

    GENERIC_RESET_SQL();

    GENERIC_SET_SQL("INSERT INTO nagios_contactgroup_members (instance_id, contactgroup_id, contact_object_id) VALUES (1,?,?) ON DUPLICATE KEY UPDATE instance_id = VALUES(instance_id), contactgroup_id = VALUES(contactgroup_id), contact_object_id = VALUES(contact_object_id)");

    GENERIC_PREPARE();
    GENERIC_RESET_BIND();

    while (tmp != NULL) {

        member = tmp->members;

        while (member != NULL) {

            q_ctx->bind_i[GENERIC] = 0;

            object_id = ndo_get_object_id_name1(q_ctx, TRUE, NDO_OBJECTTYPE_CONTACT, member->contact_name);


            GENERIC_BIND_INT(contactgroup_ids[i]);
            GENERIC_BIND_INT(object_id);

            GENERIC_BIND();
            GENERIC_EXECUTE();

            member = member->next;
        }

        i++;
        tmp = tmp->next;
    }

    trace_return_ok();
}


int ndo_write_hosts(ndo_query_context *q_ctx, int config_type, host * host_list)
{
    trace_func_args("config_type=%d", config_type);
    int ndo_return = NDO_OK;

    host * tmp = host_list;
    int i = 0;
    int j = 0;

    int max_object_insert_count = 0;
    int loops = 0;
    int loop = 0;
    int write_query = FALSE;
    int dont_reset_query = FALSE;

    size_t cur_pos = 0;

    int object_ids[MAX_OBJECT_INSERT] = { 0 };

    int check_command_id[MAX_OBJECT_INSERT] = { 0 };
    char * check_command[MAX_OBJECT_INSERT] = { NULL };
    char * check_command_args[MAX_OBJECT_INSERT] = { NULL };
    int event_handler_id[MAX_OBJECT_INSERT] = { 0 };
    char * event_handler[MAX_OBJECT_INSERT] = { NULL };
    char * event_handler_args[MAX_OBJECT_INSERT] = { NULL };
    int check_timeperiod_id[MAX_OBJECT_INSERT] = { 0 };
    int notification_timeperiod_id[MAX_OBJECT_INSERT] = { 0 };

    char * tmp_check_command[MAX_OBJECT_INSERT] = { NULL };
    char * tmp_event_handler[MAX_OBJECT_INSERT] = { NULL };

    char *query = q_ctx->query[WRITE_HOSTS];

    char query_base[] = "INSERT INTO nagios_hosts (config_type, host_object_id, alias, display_name, address, check_command_object_id, check_command_args, eventhandler_command_object_id, eventhandler_command_args, check_timeperiod_object_id, notification_timeperiod_object_id, failure_prediction_options, check_interval, retry_interval, max_check_attempts, first_notification_delay, notification_interval, notify_on_down, notify_on_unreachable, notify_on_recovery, notify_on_flapping, notify_on_downtime, stalk_on_up, stalk_on_down, stalk_on_unreachable, flap_detection_enabled, flap_detection_on_up, flap_detection_on_down, flap_detection_on_unreachable, low_flap_threshold, high_flap_threshold, process_performance_data, freshness_checks_enabled, freshness_threshold, passive_checks_enabled, event_handler_enabled, active_checks_enabled, retain_status_information, retain_nonstatus_information, notifications_enabled, obsess_over_host, failure_prediction_enabled, notes, notes_url, action_url, icon_image, icon_image_alt, vrml_image, statusmap_image, have_2d_coords, x_2d, y_2d, have_3d_coords, x_3d, y_3d, z_3d, importance, should_be_drawn) VALUES ";
    size_t query_base_len = STRLIT_LEN(query_base);
    size_t query_len = query_base_len;

    char query_values[] = "(?,?,?,?,?,?,?,?,?,?,?,'',?,?,?,?,?,X,X,X,X,X,X,X,X,X,X,X,X,?,?,X,X,?,X,X,X,X,X,X,X,0,?,?,?,?,?,?,?,X,?,?,X,?,?,?,?,?),";
    size_t query_values_len = STRLIT_LEN(query_values);

    char query_on_update[] = " ON DUPLICATE KEY UPDATE config_type = VALUES(config_type), host_object_id = VALUES(host_object_id), alias = VALUES(alias), display_name = VALUES(display_name), address = VALUES(address), check_command_object_id = VALUES(check_command_object_id), check_command_args = VALUES(check_command_args), eventhandler_command_object_id = VALUES(eventhandler_command_object_id), eventhandler_command_args = VALUES(eventhandler_command_args), check_timeperiod_object_id = VALUES(check_timeperiod_object_id), notification_timeperiod_object_id = VALUES(notification_timeperiod_object_id), failure_prediction_options = VALUES(failure_prediction_options), check_interval = VALUES(check_interval), retry_interval = VALUES(retry_interval), max_check_attempts = VALUES(max_check_attempts), first_notification_delay = VALUES(first_notification_delay), notification_interval = VALUES(notification_interval), notify_on_down = VALUES(notify_on_down), notify_on_unreachable = VALUES(notify_on_unreachable), notify_on_recovery = VALUES(notify_on_recovery), notify_on_flapping = VALUES(notify_on_flapping), notify_on_downtime = VALUES(notify_on_downtime), stalk_on_up = VALUES(stalk_on_up), stalk_on_down = VALUES(stalk_on_down), stalk_on_unreachable = VALUES(stalk_on_unreachable), flap_detection_enabled = VALUES(flap_detection_enabled), flap_detection_on_up = VALUES(flap_detection_on_up), flap_detection_on_down = VALUES(flap_detection_on_down), flap_detection_on_unreachable = VALUES(flap_detection_on_unreachable), low_flap_threshold = VALUES(low_flap_threshold), high_flap_threshold = VALUES(high_flap_threshold), process_performance_data = VALUES(process_performance_data), freshness_checks_enabled = VALUES(freshness_checks_enabled), freshness_threshold = VALUES(freshness_threshold), passive_checks_enabled = VALUES(passive_checks_enabled), event_handler_enabled = VALUES(event_handler_enabled), active_checks_enabled = VALUES(active_checks_enabled), retain_status_information = VALUES(retain_status_information), retain_nonstatus_information = VALUES(retain_nonstatus_information), notifications_enabled = VALUES(notifications_enabled), obsess_over_host = VALUES(obsess_over_host), failure_prediction_enabled = VALUES(failure_prediction_enabled), notes = VALUES(notes), notes_url = VALUES(notes_url), action_url = VALUES(action_url), icon_image = VALUES(icon_image), icon_image_alt = VALUES(icon_image_alt), vrml_image = VALUES(vrml_image), statusmap_image = VALUES(statusmap_image), have_2d_coords = VALUES(have_2d_coords), x_2d = VALUES(x_2d), y_2d = VALUES(y_2d), have_3d_coords = VALUES(have_3d_coords), x_3d = VALUES(x_3d), y_3d = VALUES(y_3d), z_3d = VALUES(z_3d), importance = VALUES(importance), should_be_drawn = VALUES(should_be_drawn)";
    size_t query_on_update_len = STRLIT_LEN(query_on_update);

    /*
    ndo_return = mysql_query(startup_connection, "LOCK TABLES nagios_logentries WRITE, nagios_objects WRITE, nagios_hosts WRITE");
    if (ndo_return != 0) {
        char msg[1024];
        snprintf(msg, 1023, "ret = %d, (%d) %s", ndo_return, mysql_errno(startup_connection), mysql_error(startup_connection));
        //ndo_log(msg);
        return NDO_ERROR;
    }
*/

    strcpy(query, query_base);

    max_object_insert_count = ndo_max_object_insert_count;
    while ((max_object_insert_count * query_values_len + query_base_len + query_on_update_len) > (MAX_SQL_BUFFER - 1)) {
        max_object_insert_count--;
    }

    loops = num_objects.hosts / max_object_insert_count;

    if (num_objects.hosts % max_object_insert_count != 0) {
        loops++;
    }

    /* if num hosts is evenly divisible, we never need to write 
       the query after the first time */
    else {
        dont_reset_query = TRUE;
    }

    write_query = TRUE;
    loop = 1;

    MYSQL_RESET_BIND(WRITE_HOSTS);

    while (tmp != NULL) {

        if (write_query == TRUE) {
            memcpy(query + query_len, query_values, query_values_len);
            query_len += query_values_len;
        }
        /* put our "cursor" at the beginning of whichever query_values we are at
           specifically at the '(' character of current values section */
        cur_pos = query_base_len + (i * query_values_len);

        object_ids[i] = ndo_get_object_id_name1(q_ctx, TRUE, NDO_OBJECTTYPE_HOST, tmp->name);

        if (tmp->check_command == NULL) {
            check_command[i] = NULL;
        } else {
            tmp_check_command[i] = strdup(tmp->check_command);

            check_command[i] = strtok(tmp_check_command[i], "!");
            if (check_command[i] != NULL) {
                check_command_args[i] = strtok(NULL, "\0");
                check_command_id[i] = ndo_get_object_id_name1(q_ctx, TRUE, NDO_OBJECTTYPE_COMMAND, check_command[i]);
            }
        }

        if (check_command[i] == NULL) {
            check_command[i] = "";
            check_command_args[i] = "";
            check_command_id[i] = 0;
        }

        if (tmp->event_handler == NULL) {
            event_handler[i] = NULL;
        } else {
            tmp_event_handler[i] = strdup(tmp->event_handler);

            event_handler[i] = strtok(tmp_event_handler[i], "!");
            if (event_handler[i] != NULL) {
                event_handler_args[i] = strtok(NULL, "\0");
                event_handler_id[i] = ndo_get_object_id_name1(q_ctx, TRUE, NDO_OBJECTTYPE_COMMAND, event_handler[i]);
            }
        }

        if (event_handler[i] == NULL) {
            event_handler[i] = "";
            event_handler_args[i] = "";
            event_handler_id[i] = 0;
        }

        check_timeperiod_id[i] = 0;
        if (tmp->check_period != NULL) {
            check_timeperiod_id[i] = ndo_get_object_id_name1(q_ctx, TRUE, NDO_OBJECTTYPE_TIMEPERIOD, tmp->check_period);
        }

        notification_timeperiod_id[i] = 0;
        if (tmp->notification_period != NULL) {
            notification_timeperiod_id[i] = ndo_get_object_id_name1(q_ctx, TRUE, NDO_OBJECTTYPE_TIMEPERIOD, tmp->notification_period);
        }


        MYSQL_BIND_INT(WRITE_HOSTS, config_type);
        MYSQL_BIND_INT(WRITE_HOSTS, object_ids[i]);
        MYSQL_BIND_STR(WRITE_HOSTS, tmp->alias);
        MYSQL_BIND_STR(WRITE_HOSTS, tmp->display_name);
        MYSQL_BIND_STR(WRITE_HOSTS, tmp->address);
        MYSQL_BIND_INT(WRITE_HOSTS, check_command_id[i]);
        MYSQL_BIND_STR(WRITE_HOSTS, check_command_args[i]);
        MYSQL_BIND_INT(WRITE_HOSTS, event_handler_id[i]);
        MYSQL_BIND_STR(WRITE_HOSTS, event_handler_args[i]);
        MYSQL_BIND_INT(WRITE_HOSTS, check_timeperiod_id[i]);
        MYSQL_BIND_INT(WRITE_HOSTS, notification_timeperiod_id[i]);
        MYSQL_BIND_DOUBLE(WRITE_HOSTS, tmp->check_interval);
        MYSQL_BIND_DOUBLE(WRITE_HOSTS, tmp->retry_interval);
        MYSQL_BIND_INT(WRITE_HOSTS, tmp->max_attempts);
        MYSQL_BIND_DOUBLE(WRITE_HOSTS, tmp->first_notification_delay);
        MYSQL_BIND_DOUBLE(WRITE_HOSTS, tmp->notification_interval);

        UPDATE_QUERY_X_POS(query, cur_pos, 36, flag_isset(tmp->notification_options, OPT_DOWN));
        UPDATE_QUERY_X_POS(query, cur_pos, 38, flag_isset(tmp->notification_options, OPT_UNREACHABLE));
        UPDATE_QUERY_X_POS(query, cur_pos, 40, flag_isset(tmp->notification_options, OPT_RECOVERY));
        UPDATE_QUERY_X_POS(query, cur_pos, 42, flag_isset(tmp->notification_options, OPT_FLAPPING));
        UPDATE_QUERY_X_POS(query, cur_pos, 44, flag_isset(tmp->notification_options, OPT_DOWNTIME));
        UPDATE_QUERY_X_POS(query, cur_pos, 46, flag_isset(tmp->stalking_options, OPT_UP));
        UPDATE_QUERY_X_POS(query, cur_pos, 48, flag_isset(tmp->stalking_options, OPT_DOWN));
        UPDATE_QUERY_X_POS(query, cur_pos, 50, flag_isset(tmp->stalking_options, OPT_UNREACHABLE));
        UPDATE_QUERY_X_POS(query, cur_pos, 52, tmp->flap_detection_enabled);
        UPDATE_QUERY_X_POS(query, cur_pos, 54, flag_isset(tmp->flap_detection_options, OPT_UP));
        UPDATE_QUERY_X_POS(query, cur_pos, 56, flag_isset(tmp->flap_detection_options, OPT_DOWN));
        UPDATE_QUERY_X_POS(query, cur_pos, 58, flag_isset(tmp->flap_detection_options, OPT_UNREACHABLE));

        MYSQL_BIND_DOUBLE(WRITE_HOSTS, tmp->low_flap_threshold);
        MYSQL_BIND_DOUBLE(WRITE_HOSTS, tmp->high_flap_threshold);

        UPDATE_QUERY_X_POS(query, cur_pos, 64, tmp->process_performance_data);
        UPDATE_QUERY_X_POS(query, cur_pos, 66, tmp->check_freshness);

        MYSQL_BIND_INT(WRITE_HOSTS, tmp->freshness_threshold);

        UPDATE_QUERY_X_POS(query, cur_pos, 70, tmp->accept_passive_checks);
        UPDATE_QUERY_X_POS(query, cur_pos, 72, tmp->event_handler_enabled);
        UPDATE_QUERY_X_POS(query, cur_pos, 74, tmp->checks_enabled);
        UPDATE_QUERY_X_POS(query, cur_pos, 76, tmp->retain_status_information);
        UPDATE_QUERY_X_POS(query, cur_pos, 78, tmp->retain_nonstatus_information);
        UPDATE_QUERY_X_POS(query, cur_pos, 80, tmp->notifications_enabled);
        UPDATE_QUERY_X_POS(query, cur_pos, 82, tmp->obsess);

        MYSQL_BIND_STR(WRITE_HOSTS, tmp->notes);
        MYSQL_BIND_STR(WRITE_HOSTS, tmp->notes_url);
        MYSQL_BIND_STR(WRITE_HOSTS, tmp->action_url);
        MYSQL_BIND_STR(WRITE_HOSTS, tmp->icon_image);
        MYSQL_BIND_STR(WRITE_HOSTS, tmp->icon_image_alt);
        MYSQL_BIND_STR(WRITE_HOSTS, tmp->vrml_image);
        MYSQL_BIND_STR(WRITE_HOSTS, tmp->statusmap_image);

        UPDATE_QUERY_X_POS(query, cur_pos, 100, tmp->have_2d_coords);

        MYSQL_BIND_INT(WRITE_HOSTS, tmp->x_2d);
        MYSQL_BIND_INT(WRITE_HOSTS, tmp->y_2d);

        UPDATE_QUERY_X_POS(query, cur_pos, 106, tmp->have_3d_coords);

        MYSQL_BIND_DOUBLE(WRITE_HOSTS, tmp->x_3d);
        MYSQL_BIND_DOUBLE(WRITE_HOSTS, tmp->y_3d);
        MYSQL_BIND_DOUBLE(WRITE_HOSTS, tmp->z_3d);
        MYSQL_BIND_INT(WRITE_HOSTS, tmp->hourly_value);
        MYSQL_BIND_INT(WRITE_HOSTS, tmp->should_be_drawn);

        i++;

        /* we need to finish the query and execute */
        if (i >= max_object_insert_count || tmp->next == NULL) {

            if (write_query == TRUE) {
                memcpy(query + query_len - 1, query_on_update, query_on_update_len);
                query_len += query_on_update_len;
            }

            if (loop == 1 || loop == loops) {
                _MYSQL_PREPARE(q_ctx->stmt[WRITE_HOSTS], query);
            }
            MYSQL_BIND(WRITE_HOSTS);
            MYSQL_EXECUTE(WRITE_HOSTS);

            for (j = 0; j < MAX_OBJECT_INSERT; j += 1)
            {
                if (tmp_check_command[j] != NULL) {
                    free(tmp_check_command[j]);
                    tmp_check_command[j] = NULL;
                    check_command[j] = NULL;
                    check_command_args[j] = NULL;
                }
                if (tmp_event_handler[j] != NULL) {
                    free(tmp_event_handler[j]);
                    tmp_event_handler[j] = NULL;
                    event_handler[j] = NULL;
                    event_handler_args[j] = NULL;
                }
            }

            q_ctx->bind_i[WRITE_HOSTS] = 0;
            i = 0;
            write_query = FALSE;

            /* if we're on the second to last loop we reset to build the final query */
            if (loop == loops - 1 && dont_reset_query == FALSE) {
                memset(query + query_base_len, 0, MAX_SQL_BUFFER - query_base_len);
                query_len = query_base_len;
                write_query = TRUE;
            }

            loop++;
        }

        tmp = tmp->next;
    }

    /* remove temp check/event data */
    for (i = 0; i < MAX_OBJECT_INSERT; i++) {
        my_free(tmp_check_command[i]);
        my_free(tmp_event_handler[i]);
    }

    /*
    ndo_return = mysql_query(startup_connection, "UNLOCK TABLES");
    if (ndo_return != 0) {
        char msg[1024];
        snprintf(msg, 1023, "ret = %d, (%d) %s", ndo_return, mysql_errno(startup_connection), mysql_error(startup_connection));
        //ndo_log(msg);
    }
*/

    int write_hosts_objects_result = ndo_write_hosts_objects(q_ctx, config_type, host_list);
    if (write_hosts_objects_result != NDO_OK) {
        return write_hosts_objects_result;
    }
    trace_return_ok();
}


int ndo_write_hosts_objects(ndo_query_context *q_ctx, int config_type, struct host * host_list)
{
    trace_func_args("config_type=%d", config_type);
    int ndo_return = NDO_OK;

    host * tmp = host_list;

    customvariablesmember * var = NULL;
    hostsmember * parent = NULL;
    contactgroupsmember * group = NULL;
    contactsmember * cnt = NULL;

    int max_object_insert_count = 0;
    int subquery_result = NDO_OK;

    int parenthosts_count = 0;
    char parenthosts_query[MAX_SQL_BUFFER] = { 0 };
    char parenthosts_query_base[] = "INSERT INTO nagios_host_parenthosts (instance_id, host_id, parent_host_object_id) VALUES ";
    size_t parenthosts_query_base_len = STRLIT_LEN(parenthosts_query_base);
    size_t parenthosts_query_len = parenthosts_query_base_len;
    char parenthosts_query_values[] = "(1,(SELECT host_id FROM nagios_hosts WHERE host_object_id = (SELECT object_id FROM nagios_objects WHERE objecttype_id = 1 AND name1 = ? AND is_active = 1)),(SELECT object_id FROM nagios_objects WHERE objecttype_id = 1 AND name1 = ? AND is_active = 1)),";
    size_t parenthosts_query_values_len = STRLIT_LEN(parenthosts_query_values);
    char parenthosts_query_on_update[] = " ON DUPLICATE KEY UPDATE instance_id = VALUES(instance_id), host_id = VALUES(host_id), parent_host_object_id = VALUES(parent_host_object_id)";
    size_t parenthosts_query_on_update_len = STRLIT_LEN(parenthosts_query_on_update);

    int contactgroups_count = 0;
    char contactgroups_query[MAX_SQL_BUFFER] = { 0 };
    char contactgroups_query_base[] = "INSERT INTO nagios_host_contactgroups (instance_id, host_id, contactgroup_object_id) VALUES ";
    size_t contactgroups_query_base_len = STRLIT_LEN(contactgroups_query_base);
    size_t contactgroups_query_len = contactgroups_query_base_len;
    char contactgroups_query_values[] = "(1,(SELECT host_id FROM nagios_hosts WHERE host_object_id = (SELECT object_id FROM nagios_objects WHERE objecttype_id = 1 AND name1 = ? AND is_active = 1)),(SELECT object_id FROM nagios_objects WHERE objecttype_id = 11 AND name1 = ? AND is_active = 1)),";
    size_t contactgroups_query_values_len = STRLIT_LEN(contactgroups_query_values);
    char contactgroups_query_on_update[] = " ON DUPLICATE KEY UPDATE instance_id = VALUES(instance_id), host_id = VALUES(host_id), contactgroup_object_id = VALUES(contactgroup_object_id)";
    size_t contactgroups_query_on_update_len = STRLIT_LEN(contactgroups_query_on_update);

    int contacts_count = 0;
    char contacts_query[MAX_SQL_BUFFER] = { 0 };
    char contacts_query_base[] = "INSERT INTO nagios_host_contacts (instance_id, host_id, contact_object_id) VALUES ";
    size_t contacts_query_base_len = STRLIT_LEN(contacts_query_base);
    size_t contacts_query_len = contacts_query_base_len;
    char contacts_query_values[] = "(1,(SELECT host_id FROM nagios_hosts WHERE host_object_id = (SELECT object_id FROM nagios_objects WHERE objecttype_id = 1 AND name1 = ? AND is_active = 1)),(SELECT object_id FROM nagios_objects WHERE objecttype_id = 10 AND name1 = ? AND is_active = 1)),";
    size_t contacts_query_values_len = STRLIT_LEN(contacts_query_values);
    char contacts_query_on_update[] = " ON DUPLICATE KEY UPDATE instance_id = VALUES(instance_id), host_id = VALUES(host_id), contact_object_id = VALUES(contact_object_id)";
    size_t contacts_query_on_update_len = STRLIT_LEN(contacts_query_on_update);

    int var_count = 0;
    char var_query[MAX_SQL_BUFFER] = { 0 };
    char var_query_base[] = "INSERT INTO nagios_customvariables (instance_id, object_id, config_type, has_been_modified, varname, varvalue) VALUES ";
    size_t var_query_base_len = STRLIT_LEN(var_query_base);
    size_t var_query_len = var_query_base_len;
    char var_query_values[] = "(1,(SELECT object_id FROM nagios_objects WHERE objecttype_id = 1 AND name1 = ? AND is_active = 1),?,?,?,?),";
    size_t var_query_values_len = STRLIT_LEN(var_query_values);
    char var_query_on_update[] = " ON DUPLICATE KEY UPDATE instance_id = VALUES(instance_id), object_id = VALUES(object_id), config_type = VALUES(config_type), has_been_modified = VALUES(has_been_modified), varname = VALUES(varname), varvalue = VALUES(varvalue)";
    size_t var_query_on_update_len = STRLIT_LEN(var_query_on_update);

    int var_status_count = 0;
    char var_status_query[MAX_SQL_BUFFER] = { 0 };
    char var_status_query_base[] = "INSERT INTO nagios_customvariablestatus (instance_id, object_id, status_update_time, has_been_modified, varname, varvalue) VALUES ";
    size_t var_status_query_base_len = STRLIT_LEN(var_status_query_base);
    size_t var_status_query_len = var_status_query_base_len;
    char var_status_query_values[] = "(1,(SELECT object_id FROM nagios_objects WHERE objecttype_id = 1 AND name1 = ? AND is_active = 1),NOW(),?,?,?),";
    size_t var_status_query_values_len = STRLIT_LEN(var_status_query_values);
    char var_status_query_on_update[] = " ON DUPLICATE KEY UPDATE instance_id = VALUES(instance_id), object_id = VALUES(object_id), status_update_time = VALUES(status_update_time), has_been_modified = VALUES(has_been_modified), varname = VALUES(varname), varvalue = VALUES(varvalue)";
    size_t var_status_query_on_update_len = STRLIT_LEN(var_status_query_on_update);

    MYSQL_RESET_BIND(WRITE_HOST_PARENTHOSTS);
    MYSQL_RESET_BIND(WRITE_HOST_CONTACTGROUPS);
    MYSQL_RESET_BIND(WRITE_HOST_CONTACTS);
    MYSQL_RESET_BIND(WRITE_CUSTOMVARS);
    MYSQL_RESET_BIND(WRITE_CUSTOMVAR_STATUS);

    strcpy(parenthosts_query, parenthosts_query_base);
    strcpy(contactgroups_query, contactgroups_query_base);
    strcpy(contacts_query, contacts_query_base);
    strcpy(var_query, var_query_base);
    strcpy(var_status_query, var_status_query_base);

    /* temporarily reduce max_object_insert_count so that it's guaranteed to fit inside of 
     * MAX_SQL_BUFFER for all of our queries. Otherwise, we risk a buffer overflow.
     */
    max_object_insert_count = ndo_max_object_insert_count;
    while ((max_object_insert_count * parenthosts_query_values_len + parenthosts_query_base_len + parenthosts_query_on_update_len) > (MAX_SQL_BUFFER - 1)) {
        max_object_insert_count--;
    }
    while ((max_object_insert_count * contactgroups_query_values_len + contactgroups_query_base_len + contactgroups_query_on_update_len) > (MAX_SQL_BUFFER - 1)) {
        max_object_insert_count--;
    }
    while ((max_object_insert_count * contacts_query_values_len + contacts_query_base_len + contacts_query_on_update_len) > (MAX_SQL_BUFFER - 1)) {
        max_object_insert_count--;
    }
    while ((max_object_insert_count * var_query_values_len + var_query_base_len + var_query_on_update_len) > (MAX_SQL_BUFFER - 1)) {
        max_object_insert_count--;
    }
    while ((max_object_insert_count * var_status_query_values_len + var_status_query_base_len + var_status_query_on_update_len) > (MAX_SQL_BUFFER - 1)) {
        max_object_insert_count--;
    }

    while (tmp != NULL) {


        parent = tmp->parent_hosts;
        while (parent != NULL) {

            strcpy(parenthosts_query + parenthosts_query_len, parenthosts_query_values);
            parenthosts_query_len += parenthosts_query_values_len;

            MYSQL_BIND_STR(WRITE_HOST_PARENTHOSTS, tmp->name);
            MYSQL_BIND_STR(WRITE_HOST_PARENTHOSTS, parent->host_name);

            parent = parent->next;
            parenthosts_count++;

            if (parenthosts_count >= max_object_insert_count) {
                subquery_result = send_subquery(q_ctx, WRITE_HOST_PARENTHOSTS, &parenthosts_count, parenthosts_query, parenthosts_query_on_update, &parenthosts_query_len, parenthosts_query_base_len, parenthosts_query_on_update_len);

                if (subquery_result != NDO_OK) {
                    ndo_log("subquery failed to send in ndo_write_hosts_objects", NSLOG_RUNTIME_ERROR);
                    return subquery_result;
                }
            }
        }


        group = tmp->contact_groups;
        while (group != NULL) {

            strcpy(contactgroups_query + contactgroups_query_len, contactgroups_query_values);
            contactgroups_query_len += contactgroups_query_values_len;

            MYSQL_BIND_STR(WRITE_HOST_CONTACTGROUPS, tmp->name);
            MYSQL_BIND_STR(WRITE_HOST_CONTACTGROUPS, group->group_name);

            group = group->next;
            contactgroups_count++;

            if (contactgroups_count >= max_object_insert_count) {
                subquery_result = send_subquery(q_ctx, WRITE_HOST_CONTACTGROUPS, &contactgroups_count, contactgroups_query, contactgroups_query_on_update, &contactgroups_query_len, contactgroups_query_base_len, contactgroups_query_on_update_len);
                if (subquery_result != NDO_OK) {
                    ndo_log("subquery failed to send in ndo_write_hosts_objects", NSLOG_RUNTIME_ERROR);
                    return subquery_result;
                }
            }
        }


        cnt = tmp->contacts;
        while (cnt != NULL) {

            strcpy(contacts_query + contacts_query_len, contacts_query_values);
            contacts_query_len += contacts_query_values_len;

            MYSQL_BIND_STR(WRITE_HOST_CONTACTS, tmp->name);
            MYSQL_BIND_STR(WRITE_HOST_CONTACTS, cnt->contact_name);

            cnt = cnt->next;
            contacts_count++;

            if (contacts_count >= max_object_insert_count) {
                subquery_result = send_subquery(q_ctx, WRITE_HOST_CONTACTS, &contacts_count, contacts_query, contacts_query_on_update, &contacts_query_len, contacts_query_base_len, contacts_query_on_update_len);
                if (subquery_result != NDO_OK) {
                    ndo_log("subquery failed to send in ndo_write_hosts_objects", NSLOG_RUNTIME_ERROR);
                    return subquery_result;
                }
            }
        }


        var = tmp->custom_variables;
        while (var != NULL) {

            strcpy(var_query + var_query_len, var_query_values);
            var_query_len += var_query_values_len;

            MYSQL_BIND_STR(WRITE_CUSTOMVARS, tmp->name);
            MYSQL_BIND_INT(WRITE_CUSTOMVARS, config_type);
            MYSQL_BIND_INT(WRITE_CUSTOMVARS, var->has_been_modified);
            MYSQL_BIND_STR(WRITE_CUSTOMVARS, var->variable_name);
            MYSQL_BIND_STR(WRITE_CUSTOMVARS, var->variable_value);

            strcpy(var_status_query + var_status_query_len, var_status_query_values);
            var_status_query_len += var_status_query_values_len;

            MYSQL_BIND_STR(WRITE_CUSTOMVAR_STATUS, tmp->name);
            MYSQL_BIND_INT(WRITE_CUSTOMVAR_STATUS, var->has_been_modified);
            MYSQL_BIND_STR(WRITE_CUSTOMVAR_STATUS, var->variable_name);
            MYSQL_BIND_STR(WRITE_CUSTOMVAR_STATUS, var->variable_value);

            var = var->next;
            var_count++;
            var_status_count++;

            if (var_count >= max_object_insert_count) {
                subquery_result = send_subquery(q_ctx, WRITE_CUSTOMVARS, &var_count, var_query, var_query_on_update, &var_query_len, var_query_base_len, var_query_on_update_len);
                if (subquery_result != NDO_OK) {
                    ndo_log("subquery failed to send in ndo_write_hosts_objects", NSLOG_RUNTIME_ERROR);
                    return subquery_result;
                }
                subquery_result = send_subquery(q_ctx, WRITE_CUSTOMVAR_STATUS, &var_status_count, var_status_query, var_status_query_on_update, &var_status_query_len, var_status_query_base_len, var_status_query_on_update_len);
                if (subquery_result != NDO_OK) {
                    ndo_log("subquery failed to send in ndo_write_hosts_objects", NSLOG_RUNTIME_ERROR);
                    return subquery_result;
                }
            }
        }

        if (parenthosts_count > 0 && (parenthosts_count >= max_object_insert_count || tmp->next == NULL)) {
            subquery_result = send_subquery(q_ctx, WRITE_HOST_PARENTHOSTS, &parenthosts_count, parenthosts_query, parenthosts_query_on_update, &parenthosts_query_len, parenthosts_query_base_len, parenthosts_query_on_update_len);
            if (subquery_result != NDO_OK) {
                ndo_log("subquery failed to send in ndo_write_hosts_objects", NSLOG_RUNTIME_ERROR);
                return subquery_result;
            }
        }

        if (contactgroups_count > 0 && (contactgroups_count >= max_object_insert_count || tmp->next == NULL)) {
            subquery_result = send_subquery(q_ctx, WRITE_HOST_CONTACTGROUPS, &contactgroups_count, contactgroups_query, contactgroups_query_on_update, &contactgroups_query_len, contactgroups_query_base_len, contactgroups_query_on_update_len);
            if (subquery_result != NDO_OK) {
                ndo_log("subquery failed to send in ndo_write_hosts_objects", NSLOG_RUNTIME_ERROR);
                return subquery_result;
            }
        }

        if (contacts_count > 0 && (contacts_count >= max_object_insert_count || tmp->next == NULL)) {
            subquery_result = send_subquery(q_ctx, WRITE_HOST_CONTACTS, &contacts_count, contacts_query, contacts_query_on_update, &contacts_query_len, contacts_query_base_len, contacts_query_on_update_len);
            if (subquery_result != NDO_OK) {
                ndo_log("subquery failed to send in ndo_write_hosts_objects", NSLOG_RUNTIME_ERROR);
                return subquery_result;
            }
        }

        if (var_count > 0 && (var_count >= max_object_insert_count || tmp->next == NULL)) {
            subquery_result = send_subquery(q_ctx, WRITE_CUSTOMVARS, &var_count, var_query, var_query_on_update, &var_query_len, var_query_base_len, var_query_on_update_len);
            if (subquery_result != NDO_OK) {
                ndo_log("subquery failed to send in ndo_write_hosts_objects", NSLOG_RUNTIME_ERROR);
                return subquery_result;
            }

            subquery_result = send_subquery(q_ctx, WRITE_CUSTOMVAR_STATUS, &var_status_count, var_status_query, var_status_query_on_update, &var_status_query_len, var_status_query_base_len, var_status_query_on_update_len);
            if (subquery_result != NDO_OK) {
                ndo_log("subquery failed to send in ndo_write_hosts_objects", NSLOG_RUNTIME_ERROR);
                return subquery_result;
            }

        }

        tmp = tmp->next;
    }

    trace_return_ok();
}


int ndo_write_hostgroups(ndo_query_context *q_ctx, int config_type, hostgroup *hostgroup_list)
{
    trace_func_args("config_type=%d", config_type);
    int ndo_return = NDO_OK;

    hostgroup * tmp = hostgroup_list;
    int object_id = 0;
    int i = 0;

    int * hostgroup_ids = NULL;

    hostgroup_ids = calloc(num_objects.hostgroups, sizeof(int));

    GENERIC_RESET_SQL();

    GENERIC_SET_SQL("INSERT INTO nagios_hostgroups (instance_id, hostgroup_object_id, config_type, alias, notes, notes_url, action_url) VALUES (1,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE instance_id = VALUES(instance_id), hostgroup_object_id = VALUES(hostgroup_object_id), config_type = VALUES(config_type), alias = VALUES(alias), notes = VALUES(notes), notes_url = VALUES(notes_url), action_url = VALUES(action_url)");

    GENERIC_PREPARE();
    GENERIC_RESET_BIND();

    while (tmp != NULL) {

        q_ctx->bind_i[GENERIC] = 0;

        object_id = ndo_get_object_id_name1(q_ctx, TRUE, NDO_OBJECTTYPE_HOSTGROUP, tmp->group_name);


        GENERIC_BIND_INT(object_id);
        GENERIC_BIND_INT(config_type);
        GENERIC_BIND_STR(tmp->alias);
        GENERIC_BIND_STR(tmp->notes);
        GENERIC_BIND_STR(tmp->notes_url);
        GENERIC_BIND_STR(tmp->action_url);

        GENERIC_BIND();
        GENERIC_EXECUTE();

        hostgroup_ids[i] = mysql_insert_id(q_ctx->conn);
        i++;
        tmp = tmp->next;
    }

    ndo_write_hostgroup_members(q_ctx, hostgroup_ids, hostgroup_list);

    free(hostgroup_ids);

    trace_return_ok();
}


int ndo_write_hostgroup_members(ndo_query_context *q_ctx, int * hostgroup_ids, hostgroup *hostgroup_list)
{
    trace_func_args("hostgroup_ids=%p", hostgroup_ids);
    int ndo_return = NDO_OK;

    hostgroup * tmp = hostgroup_list;
    hostsmember * member = NULL;
    int object_id = 0;
    int i = 0;

    GENERIC_RESET_SQL();

    GENERIC_SET_SQL("INSERT INTO nagios_hostgroup_members (instance_id, hostgroup_id, host_object_id) VALUES (1,?,?) ON DUPLICATE KEY UPDATE instance_id = VALUES(instance_id), hostgroup_id = VALUES(hostgroup_id), host_object_id = VALUES(host_object_id)");

    GENERIC_PREPARE();
    GENERIC_RESET_BIND();

    while (tmp != NULL) {

        member = tmp->members;

        while (member != NULL) {

            q_ctx->bind_i[GENERIC] = 0;

            object_id = ndo_get_object_id_name1(q_ctx, TRUE, NDO_OBJECTTYPE_HOST, member->host_name);


            GENERIC_BIND_INT(hostgroup_ids[i]);
            GENERIC_BIND_INT(object_id);

            GENERIC_BIND();
            GENERIC_EXECUTE();

            member = member->next;
        }

        i++;
        tmp = tmp->next;
    }

    trace_return_ok();
}


int ndo_write_services(ndo_query_context *q_ctx, int config_type, service *service_list)
{
    trace_func_args("config_type=%d", config_type);
    int ndo_return = NDO_OK;

    service * tmp = service_list;
    int i = 0;

    int max_object_insert_count = 0;
    int loops = 0;
    int loop = 0;
    int write_query = FALSE;
    int dont_reset_query = FALSE;

    size_t cur_pos = 0;

    int object_ids[MAX_OBJECT_INSERT] = { 0 };
    int host_object_id[MAX_OBJECT_INSERT] = { 0 };

    int check_command_id[MAX_OBJECT_INSERT] = { 0 };
    char * check_command[MAX_OBJECT_INSERT] = { NULL };
    char * check_command_args[MAX_OBJECT_INSERT] = { NULL };
    int event_handler_id[MAX_OBJECT_INSERT] = { 0 };
    char * event_handler[MAX_OBJECT_INSERT] = { NULL };
    char * event_handler_args[MAX_OBJECT_INSERT] = { NULL };
    int check_timeperiod_id[MAX_OBJECT_INSERT] = { 0 };
    int notification_timeperiod_id[MAX_OBJECT_INSERT] = { 0 };

    char * tmp_check_command[MAX_OBJECT_INSERT] = { NULL };
    char * tmp_event_handler[MAX_OBJECT_INSERT] = { NULL };

    char *query = q_ctx->query[WRITE_SERVICES];

    char query_base[] = "INSERT INTO nagios_services (config_type, host_object_id, service_object_id, display_name, check_command_object_id, check_command_args, eventhandler_command_object_id, eventhandler_command_args, check_timeperiod_object_id, notification_timeperiod_object_id, failure_prediction_options, check_interval, retry_interval, max_check_attempts, first_notification_delay, notification_interval, notify_on_warning, notify_on_unknown, notify_on_critical, notify_on_recovery, notify_on_flapping, notify_on_downtime, stalk_on_ok, stalk_on_warning, stalk_on_unknown, stalk_on_critical, is_volatile, flap_detection_enabled, flap_detection_on_ok, flap_detection_on_warning, flap_detection_on_unknown, flap_detection_on_critical, low_flap_threshold, high_flap_threshold, process_performance_data, freshness_checks_enabled, freshness_threshold, passive_checks_enabled, event_handler_enabled, active_checks_enabled, retain_status_information, retain_nonstatus_information, notifications_enabled, obsess_over_service, failure_prediction_enabled, notes, notes_url, action_url, icon_image, icon_image_alt, importance, parallelize_check) VALUES ";
    size_t query_base_len = STRLIT_LEN(query_base);
    size_t query_len = query_base_len;

    char query_values[] = "(?,?,?,?,?,?,?,?,?,?,'',?,?,?,?,?,X,X,X,X,X,X,X,X,X,X,X,X,X,X,X,X,?,?,X,X,?,X,X,X,X,X,X,X,0,?,?,?,?,?,?,?),";
    size_t query_values_len = STRLIT_LEN(query_values);

    char query_on_update[] = " ON DUPLICATE KEY UPDATE config_type = VALUES(config_type), host_object_id = VALUES(host_object_id), service_object_id = VALUES(service_object_id), display_name = VALUES(display_name), check_command_object_id = VALUES(check_command_object_id), check_command_args = VALUES(check_command_args), eventhandler_command_object_id = VALUES(eventhandler_command_object_id), eventhandler_command_args = VALUES(eventhandler_command_args), check_timeperiod_object_id = VALUES(check_timeperiod_object_id), notification_timeperiod_object_id = VALUES(notification_timeperiod_object_id), failure_prediction_options = VALUES(failure_prediction_options), check_interval = VALUES(check_interval), retry_interval = VALUES(retry_interval), max_check_attempts = VALUES(max_check_attempts), first_notification_delay = VALUES(first_notification_delay), notification_interval = VALUES(notification_interval), notify_on_warning = VALUES(notify_on_warning), notify_on_unknown = VALUES(notify_on_unknown), notify_on_critical = VALUES(notify_on_critical), notify_on_recovery = VALUES(notify_on_recovery), notify_on_flapping = VALUES(notify_on_flapping), notify_on_downtime = VALUES(notify_on_downtime), stalk_on_ok = VALUES(stalk_on_ok), stalk_on_warning = VALUES(stalk_on_warning), stalk_on_unknown = VALUES(stalk_on_unknown), stalk_on_critical = VALUES(stalk_on_critical), is_volatile = VALUES(is_volatile), flap_detection_enabled = VALUES(flap_detection_enabled), flap_detection_on_ok = VALUES(flap_detection_on_ok), flap_detection_on_warning = VALUES(flap_detection_on_warning), flap_detection_on_unknown = VALUES(flap_detection_on_unknown), flap_detection_on_critical = VALUES(flap_detection_on_critical), low_flap_threshold = VALUES(low_flap_threshold), high_flap_threshold = VALUES(high_flap_threshold), process_performance_data = VALUES(process_performance_data), freshness_checks_enabled = VALUES(freshness_checks_enabled), freshness_threshold = VALUES(freshness_threshold), passive_checks_enabled = VALUES(passive_checks_enabled), event_handler_enabled = VALUES(event_handler_enabled), active_checks_enabled = VALUES(active_checks_enabled), retain_status_information = VALUES(retain_status_information), retain_nonstatus_information = VALUES(retain_nonstatus_information), notifications_enabled = VALUES(notifications_enabled), obsess_over_service = VALUES(obsess_over_service), failure_prediction_enabled = VALUES(failure_prediction_enabled), notes = VALUES(notes), notes_url = VALUES(notes_url), action_url = VALUES(action_url), icon_image = VALUES(icon_image), icon_image_alt = VALUES(icon_image_alt), importance = VALUES(importance), parallelize_check = VALUES(parallelize_check)";
    size_t query_on_update_len = STRLIT_LEN(query_on_update);

    /*
    ndo_return = mysql_query(startup_connection, "LOCK TABLES nagios_logentries WRITE, nagios_objects WRITE, nagios_services WRITE, nagios_hosts READ");
    if (ndo_return != 0) {
        char msg[1024];
        snprintf(msg, 1023, "ret = %d, (%d) %s", ndo_return, mysql_errno(startup_connection), mysql_error(startup_connection));
        //ndo_log(msg);
        return NDO_ERROR;
    }
*/

    strcpy(query, query_base);

    max_object_insert_count = ndo_max_object_insert_count;
    while ((max_object_insert_count * query_values_len + query_base_len + query_on_update_len) > (MAX_SQL_BUFFER - 1)) {
        max_object_insert_count--;
    }

    loops = num_objects.services / max_object_insert_count;

    if (num_objects.services % max_object_insert_count != 0) {
        loops++;
    }

    /* if num services is evenly divisible, we never need to write 
       the query after the first time */
    else {
        dont_reset_query = TRUE;
    }

    write_query = TRUE;
    loop = 1;

    MYSQL_RESET_BIND(WRITE_SERVICES);

    while (tmp != NULL) {

        if (write_query == TRUE) {
            memcpy(query + query_len, query_values, query_values_len);
            query_len += query_values_len;
        }

        /* put our "cursor" at the beginning of whichever query_values we are at
           specifically at the '(' character of current values section */
        cur_pos = query_base_len + (i * query_values_len);

        object_ids[i] = ndo_get_object_id_name2(q_ctx, TRUE, NDO_OBJECTTYPE_SERVICE, tmp->host_name, tmp->description);
        host_object_id[i] = ndo_get_object_id_name1(q_ctx, TRUE, NDO_OBJECTTYPE_HOST, tmp->host_name);

        if (tmp->check_command == NULL) {
            check_command[i] = NULL;
        } else {
            tmp_check_command[i] = strdup(tmp->check_command);

            check_command[i] = strtok(tmp_check_command[i], "!");
            if (check_command[i] != NULL) {
                check_command_args[i] = strtok(NULL, "\0");
                check_command_id[i] = ndo_get_object_id_name1(q_ctx, TRUE, NDO_OBJECTTYPE_COMMAND, check_command[i]);
            }
        }

        if (check_command[i] == NULL) {
            check_command[i] = "";
            check_command_args[i] = "";
            check_command_id[i] = 0;
        }

        if (tmp->event_handler == NULL) {
            event_handler[i] = NULL;
        } else {
            tmp_event_handler[i] = strdup(tmp->event_handler);

            event_handler[i] = strtok(tmp_event_handler[i], "!");
            if (event_handler[i] != NULL) {
                event_handler_args[i] = strtok(NULL, "\0");
                event_handler_id[i] = ndo_get_object_id_name1(q_ctx, TRUE, NDO_OBJECTTYPE_COMMAND, event_handler[i]);
            }
        }

        if (event_handler[i] == NULL) {
            event_handler[i] = "";
            event_handler_args[i] = "";
            event_handler_id[i] = 0;
        }

        check_timeperiod_id[i] = 0;
        if (tmp->check_period != NULL) {
            check_timeperiod_id[i] = ndo_get_object_id_name1(q_ctx, TRUE, NDO_OBJECTTYPE_TIMEPERIOD, tmp->check_period);
        }

        notification_timeperiod_id[i] = 0;
        if (tmp->notification_period != NULL) {
            notification_timeperiod_id[i] = ndo_get_object_id_name1(q_ctx, TRUE, NDO_OBJECTTYPE_TIMEPERIOD, tmp->notification_period);
        }


        MYSQL_BIND_INT(WRITE_SERVICES, config_type);
        MYSQL_BIND_INT(WRITE_SERVICES, host_object_id[i]);
        MYSQL_BIND_INT(WRITE_SERVICES, object_ids[i]);
        MYSQL_BIND_STR(WRITE_SERVICES, tmp->display_name);
        MYSQL_BIND_INT(WRITE_SERVICES, check_command_id[i]);
        MYSQL_BIND_STR(WRITE_SERVICES, check_command_args[i]);
        MYSQL_BIND_INT(WRITE_SERVICES, event_handler_id[i]);
        MYSQL_BIND_STR(WRITE_SERVICES, event_handler_args[i]);
        MYSQL_BIND_INT(WRITE_SERVICES, check_timeperiod_id[i]);
        MYSQL_BIND_INT(WRITE_SERVICES, notification_timeperiod_id[i]);
        MYSQL_BIND_DOUBLE(WRITE_SERVICES, tmp->check_interval);
        MYSQL_BIND_DOUBLE(WRITE_SERVICES, tmp->retry_interval);
        MYSQL_BIND_INT(WRITE_SERVICES, tmp->max_attempts);
        MYSQL_BIND_DOUBLE(WRITE_SERVICES, tmp->first_notification_delay);
        MYSQL_BIND_DOUBLE(WRITE_SERVICES, tmp->notification_interval);

        UPDATE_QUERY_X_POS(query, cur_pos, 34, flag_isset(tmp->notification_options, OPT_WARNING));
        UPDATE_QUERY_X_POS(query, cur_pos, 36, flag_isset(tmp->notification_options, OPT_UNKNOWN));
        UPDATE_QUERY_X_POS(query, cur_pos, 38, flag_isset(tmp->notification_options, OPT_CRITICAL));
        UPDATE_QUERY_X_POS(query, cur_pos, 40, flag_isset(tmp->notification_options, OPT_RECOVERY));
        UPDATE_QUERY_X_POS(query, cur_pos, 42, flag_isset(tmp->notification_options, OPT_FLAPPING));
        UPDATE_QUERY_X_POS(query, cur_pos, 44, flag_isset(tmp->notification_options, OPT_DOWNTIME));
        UPDATE_QUERY_X_POS(query, cur_pos, 46, flag_isset(tmp->stalking_options, OPT_OK));
        UPDATE_QUERY_X_POS(query, cur_pos, 48, flag_isset(tmp->stalking_options, OPT_WARNING));
        UPDATE_QUERY_X_POS(query, cur_pos, 50, flag_isset(tmp->stalking_options, OPT_UNKNOWN));
        UPDATE_QUERY_X_POS(query, cur_pos, 52, flag_isset(tmp->stalking_options, OPT_CRITICAL));
        UPDATE_QUERY_X_POS(query, cur_pos, 54, tmp->is_volatile);
        UPDATE_QUERY_X_POS(query, cur_pos, 56, tmp->flap_detection_enabled);
        UPDATE_QUERY_X_POS(query, cur_pos, 58, flag_isset(tmp->flap_detection_options, OPT_OK));
        UPDATE_QUERY_X_POS(query, cur_pos, 60, flag_isset(tmp->flap_detection_options, OPT_WARNING));
        UPDATE_QUERY_X_POS(query, cur_pos, 62, flag_isset(tmp->flap_detection_options, OPT_UNKNOWN));
        UPDATE_QUERY_X_POS(query, cur_pos, 64, flag_isset(tmp->flap_detection_options, OPT_CRITICAL));

        MYSQL_BIND_DOUBLE(WRITE_SERVICES, tmp->low_flap_threshold);
        MYSQL_BIND_DOUBLE(WRITE_SERVICES, tmp->high_flap_threshold);

        UPDATE_QUERY_X_POS(query, cur_pos, 70, tmp->is_volatile);
        UPDATE_QUERY_X_POS(query, cur_pos, 72, tmp->flap_detection_enabled);

        MYSQL_BIND_INT(WRITE_SERVICES, tmp->freshness_threshold);

        UPDATE_QUERY_X_POS(query, cur_pos, 76, tmp->accept_passive_checks);
        UPDATE_QUERY_X_POS(query, cur_pos, 78, tmp->event_handler_enabled);
        UPDATE_QUERY_X_POS(query, cur_pos, 80, tmp->checks_enabled);
        UPDATE_QUERY_X_POS(query, cur_pos, 82, tmp->retain_status_information);
        UPDATE_QUERY_X_POS(query, cur_pos, 84, tmp->retain_nonstatus_information);
        UPDATE_QUERY_X_POS(query, cur_pos, 86, tmp->notifications_enabled);
        UPDATE_QUERY_X_POS(query, cur_pos, 88, tmp->obsess);

        MYSQL_BIND_STR(WRITE_SERVICES, tmp->notes);
        MYSQL_BIND_STR(WRITE_SERVICES, tmp->notes_url);
        MYSQL_BIND_STR(WRITE_SERVICES, tmp->action_url);
        MYSQL_BIND_STR(WRITE_SERVICES, tmp->icon_image);
        MYSQL_BIND_STR(WRITE_SERVICES, tmp->icon_image_alt);
        MYSQL_BIND_INT(WRITE_SERVICES, tmp->hourly_value);
        MYSQL_BIND_INT(WRITE_SERVICES, tmp->parallelize);

        i++;

        /* we need to finish the query and execute */
        if (i >= max_object_insert_count || tmp->next == NULL) {

            if (write_query == TRUE) {
                memcpy(query + query_len - 1, query_on_update, query_on_update_len);
                query_len += query_on_update_len;
            }

            if (loop == 1 || loop == loops) {
                _MYSQL_PREPARE(q_ctx->stmt[WRITE_SERVICES], query);
            }
            MYSQL_BIND(WRITE_SERVICES);
            MYSQL_EXECUTE(WRITE_SERVICES);

            q_ctx->bind_i[WRITE_SERVICES] = 0;
            i = 0;
            write_query = FALSE;

            /* if we're on the second to last loop we reset to build the final query */
            if (loop == loops - 1 && dont_reset_query == FALSE) {
                memset(query + query_base_len, 0, MAX_SQL_BUFFER - query_base_len);
                query_len = query_base_len;
                write_query = TRUE;
            }

            loop++;
        }

        tmp = tmp->next;
    }

    /* remove temp check/event data */
    for (i = 0; i < MAX_OBJECT_INSERT; i++) {
        my_free(tmp_check_command[i]);
        my_free(tmp_event_handler[i]);
    }

    /*
    ndo_return = mysql_query(startup_connection, "UNLOCK TABLES");
    if (ndo_return != 0) {
        char msg[1024];
        snprintf(msg, 1023, "ret = %d, (%d) %s", ndo_return, mysql_errno(startup_connection), mysql_error(startup_connection));
        //ndo_log(msg);
    }
*/

    int write_services_objects_result = ndo_write_services_objects(q_ctx, config_type, service_list);
    if (write_services_objects_result != NDO_OK) {
        return write_services_objects_result;
    }

    trace_return_ok();
}


int ndo_write_services_objects(ndo_query_context *q_ctx, int config_type, service *service_list)
{
    trace_func_args("config_type=%d", config_type);
    int ndo_return = NDO_OK;

    service * tmp = service_list;

    servicesmember * parent = NULL;
    contactgroupsmember * group = NULL;
    contactsmember * cnt = NULL;
    customvariablesmember * var = NULL;

    int max_object_insert_count = 0;
    int subquery_result = NDO_OK;

    int parentservices_count = 0;
    char parentservices_query[MAX_SQL_BUFFER] = { 0 };
    char parentservices_query_base[] = "INSERT INTO nagios_service_parentservices (instance_id, service_id, parent_service_object_id) VALUES ";
    size_t parentservices_query_base_len = STRLIT_LEN(parentservices_query_base);
    size_t parentservices_query_len = parentservices_query_base_len;
    char parentservices_query_values[] = "(1,(SELECT service_id FROM nagios_services WHERE service_object_id = (SELECT object_id FROM nagios_objects WHERE objecttype_id = 2 AND name1 = ? AND name2 = ? AND is_active = 1)),(SELECT object_id FROM nagios_objects WHERE objecttype_id = 2 AND name1 = ? AND name2 = ? AND is_active = 1)),";
    size_t parentservices_query_values_len = STRLIT_LEN(parentservices_query_values);
    char parentservices_query_on_update[] = "ON DUPLICATE KEY UPDATE instance_id = VALUES(instance_id), service_id = VALUES(service_id), parent_service_object_id = VALUES(parent_service_object_id)";
    size_t parentservices_query_on_update_len = STRLIT_LEN(parentservices_query_on_update);

    int contactgroups_count = 0;
    char contactgroups_query[MAX_SQL_BUFFER] = { 0 };
    char contactgroups_query_base[] = "INSERT INTO nagios_service_contactgroups (instance_id, service_id, contactgroup_object_id) VALUES ";
    size_t contactgroups_query_base_len = STRLIT_LEN(contactgroups_query_base);
    size_t contactgroups_query_len = contactgroups_query_base_len;
    char contactgroups_query_values[] = "(1,(SELECT service_id FROM nagios_services WHERE service_object_id = (SELECT object_id FROM nagios_objects WHERE objecttype_id = 2 AND name1 = ? AND name2 = ? AND is_active = 1)),(SELECT object_id FROM nagios_objects WHERE objecttype_id = 11 AND name1 = ? AND is_active = 1)),";
    size_t contactgroups_query_values_len = STRLIT_LEN(contactgroups_query_values);
    char contactgroups_query_on_update[] = "ON DUPLICATE KEY UPDATE instance_id = VALUES(instance_id), service_id = VALUES(service_id), contactgroup_object_id = VALUES(contactgroup_object_id)";
    size_t contactgroups_query_on_update_len = STRLIT_LEN(contactgroups_query_on_update);

    int contacts_count = 0;
    char contacts_query[MAX_SQL_BUFFER] = { 0 };
    char contacts_query_base[] = "INSERT INTO nagios_service_contacts (instance_id, service_id, contact_object_id) VALUES ";
    size_t contacts_query_base_len = STRLIT_LEN(contacts_query_base);
    size_t contacts_query_len = contacts_query_base_len;
    char contacts_query_values[] = "(1,(SELECT service_id FROM nagios_services WHERE service_object_id = (SELECT object_id FROM nagios_objects WHERE objecttype_id = 2 AND name1 = ? AND name2 = ? AND is_active = 1)),(SELECT object_id FROM nagios_objects WHERE objecttype_id = 10 AND name1 = ? AND is_active = 1)),";
    size_t contacts_query_values_len = STRLIT_LEN(contacts_query_values);
    char contacts_query_on_update[] = "ON DUPLICATE KEY UPDATE instance_id = VALUES(instance_id), service_id = VALUES(service_id), contact_object_id = VALUES(contact_object_id)";
    size_t contacts_query_on_update_len = STRLIT_LEN(contacts_query_on_update);

    int var_count = 0;
    char var_query[MAX_SQL_BUFFER] = { 0 };
    char var_query_base[] = "INSERT INTO nagios_customvariables (instance_id, object_id, config_type, has_been_modified, varname, varvalue) VALUES ";
    size_t var_query_base_len = STRLIT_LEN(var_query_base);
    size_t var_query_len = var_query_base_len;
    char var_query_values[] = "(1,(SELECT object_id FROM nagios_objects WHERE objecttype_id = 2 AND name1 = ? AND name2 = ? AND is_active = 1),?,?,?,?),";
    size_t var_query_values_len = STRLIT_LEN(var_query_values);
    char var_query_on_update[] = " ON DUPLICATE KEY UPDATE instance_id = VALUES(instance_id), object_id = VALUES(object_id), config_type = VALUES(config_type), has_been_modified = VALUES(has_been_modified), varname = VALUES(varname), varvalue = VALUES(varvalue)";
    size_t var_query_on_update_len = STRLIT_LEN(var_query_on_update);

    int var_status_count = 0;
    char var_status_query[MAX_SQL_BUFFER] = { 0 };
    char var_status_query_base[] = "INSERT INTO nagios_customvariablestatus (instance_id, object_id, status_update_time, has_been_modified, varname, varvalue) VALUES ";
    size_t var_status_query_base_len = STRLIT_LEN(var_status_query_base);
    size_t var_status_query_len = var_status_query_base_len;
    char var_status_query_values[] = "(1,(SELECT object_id FROM nagios_objects WHERE objecttype_id = 2 AND name1 = ? AND name2 = ? AND is_active = 1),NOW(),?,?,?),";
    size_t var_status_query_values_len = STRLIT_LEN(var_status_query_values);
    char var_status_query_on_update[] = " ON DUPLICATE KEY UPDATE instance_id = VALUES(instance_id), object_id = VALUES(object_id), status_update_time = VALUES(status_update_time), has_been_modified = VALUES(has_been_modified), varname = VALUES(varname), varvalue = VALUES(varvalue)";
    size_t var_status_query_on_update_len = STRLIT_LEN(var_status_query_on_update);

    MYSQL_RESET_BIND(WRITE_SERVICE_PARENTSERVICES);
    MYSQL_RESET_BIND(WRITE_SERVICE_CONTACTGROUPS);
    MYSQL_RESET_BIND(WRITE_SERVICE_CONTACTS);
    MYSQL_RESET_BIND(WRITE_CUSTOMVARS);
    MYSQL_RESET_BIND(WRITE_CUSTOMVAR_STATUS);

    strcpy(parentservices_query, parentservices_query_base);
    strcpy(contactgroups_query, contactgroups_query_base);
    strcpy(contacts_query, contacts_query_base);
    strcpy(var_query, var_query_base);
    strcpy(var_status_query, var_status_query_base);

    /* temporarily reduce max_object_insert_count so that it's guaranteed to fit inside of 
     * MAX_SQL_BUFFER for all of our queries. Otherwise, we risk a buffer overflow.
     */
    max_object_insert_count = ndo_max_object_insert_count;
    while ((max_object_insert_count * parentservices_query_values_len + parentservices_query_base_len + parentservices_query_on_update_len) > (MAX_SQL_BUFFER - 1)) {
        max_object_insert_count--;
    }
    while ((max_object_insert_count * contactgroups_query_values_len + contactgroups_query_base_len + contactgroups_query_on_update_len) > (MAX_SQL_BUFFER - 1)) {
        max_object_insert_count--;
    }
    while ((max_object_insert_count * contacts_query_values_len + contacts_query_base_len + contacts_query_on_update_len) > (MAX_SQL_BUFFER - 1)) {
        max_object_insert_count--;
    }
    while ((max_object_insert_count * var_query_values_len + var_query_base_len + var_query_on_update_len) > (MAX_SQL_BUFFER - 1)) {
        max_object_insert_count--;
    }
    while ((max_object_insert_count * var_status_query_values_len + var_status_query_base_len + var_status_query_on_update_len) > (MAX_SQL_BUFFER - 1)) {
        max_object_insert_count--;
    }

    while (tmp != NULL) {


        parent = tmp->parents;
        while (parent != NULL) {

            strcpy(parentservices_query + parentservices_query_len, parentservices_query_values);
            parentservices_query_len += parentservices_query_values_len;

            MYSQL_BIND_STR(WRITE_SERVICE_PARENTSERVICES, tmp->host_name);
            MYSQL_BIND_STR(WRITE_SERVICE_PARENTSERVICES, tmp->description);
            MYSQL_BIND_STR(WRITE_SERVICE_PARENTSERVICES, parent->host_name);
            MYSQL_BIND_STR(WRITE_SERVICE_PARENTSERVICES, parent->service_description);

            parent = parent->next;
            parentservices_count++;

            if (parentservices_count >= max_object_insert_count) {
                subquery_result = send_subquery(q_ctx, WRITE_SERVICE_PARENTSERVICES, &parentservices_count, parentservices_query, parentservices_query_on_update, &parentservices_query_len, parentservices_query_base_len, parentservices_query_on_update_len);
                if (subquery_result != NDO_OK) {
                    ndo_log("subquery failed to send in ndo_write_services_objects", NSLOG_RUNTIME_ERROR);
                    return subquery_result;
                }
            }
        }


        group = tmp->contact_groups;
        while (group != NULL) {

            strcpy(contactgroups_query + contactgroups_query_len, contactgroups_query_values);
            contactgroups_query_len += contactgroups_query_values_len;

            MYSQL_BIND_STR(WRITE_SERVICE_CONTACTGROUPS, tmp->host_name);
            MYSQL_BIND_STR(WRITE_SERVICE_CONTACTGROUPS, tmp->description);
            MYSQL_BIND_STR(WRITE_SERVICE_CONTACTGROUPS, group->group_name);

            group = group->next;
            contactgroups_count++;

            if (contactgroups_count >= max_object_insert_count) {
                subquery_result = send_subquery(q_ctx, WRITE_SERVICE_CONTACTGROUPS, &contactgroups_count, contactgroups_query, contactgroups_query_on_update, &contactgroups_query_len, contactgroups_query_base_len, contactgroups_query_on_update_len);
                if (subquery_result != NDO_OK) {
                    ndo_log("subquery failed to send in ndo_write_services_objects", NSLOG_RUNTIME_ERROR);
                    return subquery_result;
                }
            }
        }


        cnt = tmp->contacts;
        while (cnt != NULL) {

            strcpy(contacts_query + contacts_query_len, contacts_query_values);
            contacts_query_len += contacts_query_values_len;

            MYSQL_BIND_STR(WRITE_SERVICE_CONTACTS, tmp->host_name);
            MYSQL_BIND_STR(WRITE_SERVICE_CONTACTS, tmp->description);
            MYSQL_BIND_STR(WRITE_SERVICE_CONTACTS, cnt->contact_name);

            cnt = cnt->next;
            contacts_count++;

            if (contacts_count >= max_object_insert_count) {
                subquery_result = send_subquery(q_ctx, WRITE_SERVICE_CONTACTS, &contacts_count, contacts_query, contacts_query_on_update, &contacts_query_len, contacts_query_base_len, contacts_query_on_update_len);
                if (subquery_result != NDO_OK) {
                    ndo_log("subquery failed to send in ndo_write_services_objects", NSLOG_RUNTIME_ERROR);
                    return subquery_result;
                }
            }
        }


        var = tmp->custom_variables;
        while (var != NULL) {

            strcpy(var_query + var_query_len, var_query_values);
            var_query_len += var_query_values_len;

            MYSQL_BIND_STR(WRITE_CUSTOMVARS, tmp->host_name);
            MYSQL_BIND_STR(WRITE_CUSTOMVARS, tmp->description);
            MYSQL_BIND_INT(WRITE_CUSTOMVARS, config_type);
            MYSQL_BIND_INT(WRITE_CUSTOMVARS, var->has_been_modified);
            MYSQL_BIND_STR(WRITE_CUSTOMVARS, var->variable_name);
            MYSQL_BIND_STR(WRITE_CUSTOMVARS, var->variable_value);

            strcpy(var_status_query + var_status_query_len, var_status_query_values);
            var_status_query_len += var_status_query_values_len;

            MYSQL_BIND_STR(WRITE_CUSTOMVAR_STATUS, tmp->host_name);
            MYSQL_BIND_STR(WRITE_CUSTOMVAR_STATUS, tmp->description);
            MYSQL_BIND_INT(WRITE_CUSTOMVAR_STATUS, var->has_been_modified);
            MYSQL_BIND_STR(WRITE_CUSTOMVAR_STATUS, var->variable_name);
            MYSQL_BIND_STR(WRITE_CUSTOMVAR_STATUS, var->variable_value);

            var = var->next;
            var_count++;
            var_status_count++;

            if (var_count >= max_object_insert_count) {
                subquery_result = send_subquery(q_ctx, WRITE_CUSTOMVARS, &var_count, var_query, var_query_on_update, &var_query_len, var_query_base_len, var_query_on_update_len);
                if (subquery_result != NDO_OK) {
                    ndo_log("subquery failed to send in ndo_write_services_objects", NSLOG_RUNTIME_ERROR);
                    return subquery_result;
                }
                subquery_result = send_subquery(q_ctx, WRITE_CUSTOMVAR_STATUS, &var_status_count, var_status_query, var_status_query_on_update, &var_status_query_len, var_status_query_base_len, var_status_query_on_update_len);
                if (subquery_result != NDO_OK) {
                    ndo_log("subquery failed to send in ndo_write_services_objects", NSLOG_RUNTIME_ERROR);
                    return subquery_result;
                }
            }
        }

        if (parentservices_count > 0 && (parentservices_count >= max_object_insert_count || tmp->next == NULL)) {
            subquery_result = send_subquery(q_ctx, WRITE_SERVICE_PARENTSERVICES, &parentservices_count, parentservices_query, parentservices_query_on_update, &parentservices_query_len, parentservices_query_base_len, parentservices_query_on_update_len);
            if (subquery_result != NDO_OK) {
                ndo_log("subquery failed to send in ndo_write_services_objects", NSLOG_RUNTIME_ERROR);
                return subquery_result;
            }
        }

        if (contactgroups_count > 0 && (contactgroups_count >= max_object_insert_count || tmp->next == NULL)) {
            subquery_result = send_subquery(q_ctx, WRITE_SERVICE_CONTACTGROUPS, &contactgroups_count, contactgroups_query, contactgroups_query_on_update, &contactgroups_query_len, contactgroups_query_base_len, contactgroups_query_on_update_len);
            if (subquery_result != NDO_OK) {
                ndo_log("subquery failed to send in ndo_write_services_objects", NSLOG_RUNTIME_ERROR);
                return subquery_result;
            }
        }

        if (contacts_count > 0 && (contacts_count >= max_object_insert_count || tmp->next == NULL)) {
            subquery_result = send_subquery(q_ctx, WRITE_SERVICE_CONTACTS, &contacts_count, contacts_query, contacts_query_on_update, &contacts_query_len, contacts_query_base_len, contacts_query_on_update_len);
            if (subquery_result != NDO_OK) {
                ndo_log("subquery failed to send in ndo_write_services_objects", NSLOG_RUNTIME_ERROR);
                return subquery_result;
            }
        }

        if (var_count > 0 && (var_count >= max_object_insert_count || tmp->next == NULL)) {
            subquery_result = send_subquery(q_ctx, WRITE_CUSTOMVARS, &var_count, var_query, var_query_on_update, &var_query_len, var_query_base_len, var_query_on_update_len);
            if (subquery_result != NDO_OK) {
                ndo_log("subquery failed to send in ndo_write_services_objects", NSLOG_RUNTIME_ERROR);
                return subquery_result;
            }
            subquery_result = send_subquery(q_ctx, WRITE_CUSTOMVAR_STATUS, &var_status_count, var_status_query, var_status_query_on_update, &var_status_query_len, var_status_query_base_len, var_status_query_on_update_len);
            if (subquery_result != NDO_OK) {
                ndo_log("subquery failed to send in ndo_write_services_objects", NSLOG_RUNTIME_ERROR);
                return subquery_result;
            }
        }

        tmp = tmp->next;
    }

    trace_return_ok();
}


int ndo_write_servicegroups(ndo_query_context *q_ctx, int config_type, servicegroup *servicegroup_list)
{
    trace_func_args("config_type=%d", config_type);
    int ndo_return = NDO_OK;

    servicegroup * tmp = servicegroup_list;
    int object_id = 0;
    int i = 0;

    int * servicegroup_ids = NULL;

    servicegroup_ids = calloc(num_objects.servicegroups, sizeof(int));

    GENERIC_RESET_SQL();

    GENERIC_SET_SQL("INSERT INTO nagios_servicegroups (instance_id, servicegroup_object_id, config_type, alias, notes, notes_url, action_url) VALUES (1,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE instance_id = VALUES(instance_id), servicegroup_object_id = VALUES(servicegroup_object_id), config_type = VALUES(config_type), alias = VALUES(alias), notes = VALUES(notes), notes_url = VALUES(notes_url), action_url = VALUES(action_url)");

    GENERIC_PREPARE();
    GENERIC_RESET_BIND();

    while (tmp != NULL) {

        q_ctx->bind_i[GENERIC] = 0;

        object_id = ndo_get_object_id_name1(q_ctx, TRUE, NDO_OBJECTTYPE_SERVICEGROUP, tmp->group_name);

        GENERIC_BIND_INT(object_id);
        GENERIC_BIND_INT(config_type);
        GENERIC_BIND_STR(tmp->alias);
        GENERIC_BIND_STR(tmp->notes);
        GENERIC_BIND_STR(tmp->notes_url);
        GENERIC_BIND_STR(tmp->action_url);

        GENERIC_BIND();
        GENERIC_EXECUTE();

        servicegroup_ids[i] = mysql_insert_id(q_ctx->conn);
        i++;
        tmp = tmp->next;
    }

    ndo_write_servicegroup_members(q_ctx, servicegroup_ids, servicegroup_list);

    free(servicegroup_ids);

    trace_return_ok();
}


int ndo_write_servicegroup_members(ndo_query_context *q_ctx, int * servicegroup_ids, servicegroup *servicegroup_list)
{
    trace_func_args("servicegroup_ids=%p", servicegroup_ids);
    int ndo_return = NDO_OK;

    servicegroup * tmp = servicegroup_list;
    servicesmember * member = NULL;
    int object_id = 0;
    int i = 0;

    GENERIC_RESET_SQL();

    GENERIC_SET_SQL("INSERT INTO nagios_servicegroup_members (instance_id, servicegroup_id, service_object_id) VALUES (1,?,?) ON DUPLICATE KEY UPDATE instance_id = VALUES(instance_id), servicegroup_id = VALUES(servicegroup_id), service_object_id = VALUES(service_object_id)");

    GENERIC_PREPARE();
    GENERIC_RESET_BIND();

    while (tmp != NULL) {

        member = tmp->members;

        while (member != NULL) {

            q_ctx->bind_i[GENERIC] = 0;

            object_id = ndo_get_object_id_name2(q_ctx, TRUE, NDO_OBJECTTYPE_SERVICE, member->host_name, member->service_description);

            GENERIC_BIND_INT(servicegroup_ids[i]);
            GENERIC_BIND_INT(object_id);

            GENERIC_BIND();
            GENERIC_EXECUTE();

            member = member->next;
        }

        i++;
        tmp = tmp->next;
    }

    trace_return_ok();
}


int ndo_write_hostescalations(ndo_query_context *q_ctx, int config_type, hostescalation **hostescalation_ary)
{
    trace_func_args("config_type=%d", config_type);
    int ndo_return = NDO_OK;

    hostescalation * tmp = NULL;
    int host_object_id = 0;
    int timeperiod_object_id = 0;
    int i = 0;

    int * object_ids = calloc(num_objects.hostescalations, sizeof(int));
    int * hostescalation_ids = calloc(num_objects.hostescalations, sizeof(int));

    int hostescalation_options[3] = { 0 };

    GENERIC_RESET_SQL();

    GENERIC_SET_SQL("INSERT INTO nagios_hostescalations (instance_id, config_type, host_object_id, timeperiod_object_id, first_notification, last_notification, notification_interval, escalate_on_recovery, escalate_on_down, escalate_on_unreachable) VALUES (1,?,?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE instance_id = VALUES(instance_id), config_type = VALUES(config_type), host_object_id = VALUES(host_object_id), timeperiod_object_id = VALUES(timeperiod_object_id), first_notification = VALUES(first_notification), last_notification = VALUES(last_notification), notification_interval = VALUES(notification_interval), escalate_on_recovery = VALUES(escalate_on_recovery), escalate_on_down = VALUES(escalate_on_down), escalate_on_unreachable = VALUES(escalate_on_unreachable)");

    GENERIC_PREPARE();
    GENERIC_RESET_BIND();

    for (i = 0; i < num_objects.hostescalations; i++) {

        q_ctx->bind_i[GENERIC] = 0;

        tmp = hostescalation_ary[i];

        host_object_id = ndo_get_object_id_name1(q_ctx, TRUE, NDO_OBJECTTYPE_HOST, tmp->host_name);
        timeperiod_object_id = 0;
        if (tmp->escalation_period != NULL) {
            timeperiod_object_id = ndo_get_object_id_name1(q_ctx, TRUE, NDO_OBJECTTYPE_TIMEPERIOD, tmp->escalation_period);
        }

        hostescalation_options[0] = flag_isset(tmp->escalation_options, OPT_RECOVERY);
        hostescalation_options[1] = flag_isset(tmp->escalation_options, OPT_DOWN);
        hostescalation_options[2] = flag_isset(tmp->escalation_options, OPT_UNREACHABLE);

        GENERIC_BIND_INT(config_type);
        GENERIC_BIND_INT(host_object_id);
        GENERIC_BIND_INT(timeperiod_object_id);
        GENERIC_BIND_INT(tmp->first_notification);
        GENERIC_BIND_INT(tmp->last_notification);
        GENERIC_BIND_FLOAT(tmp->notification_interval);
        GENERIC_BIND_INT(hostescalation_options[0]);
        GENERIC_BIND_INT(hostescalation_options[1]);
        GENERIC_BIND_INT(hostescalation_options[2]);

        GENERIC_BIND();
        GENERIC_EXECUTE();

        hostescalation_ids[i] = mysql_insert_id(q_ctx->conn);
    }

    ndo_write_hostescalation_contactgroups(q_ctx, hostescalation_ids, hostescalation_ary);
    ndo_write_hostescalation_contacts(q_ctx, hostescalation_ids, hostescalation_ary);

    free(object_ids);
    free(hostescalation_ids);

    trace_return_ok();
}


int ndo_write_hostescalation_contactgroups(ndo_query_context *q_ctx, int * hostescalation_ids, hostescalation ** hostescalation_ary)
{
    trace_func_args("hostescalation_ids=%p", hostescalation_ids);
    int ndo_return = NDO_OK;

    hostescalation * tmp = NULL;
    contactgroupsmember * group = NULL;
    int object_id = 0;
    int i = 0;

    GENERIC_RESET_SQL();

    GENERIC_SET_SQL("INSERT INTO nagios_hostescalation_contactgroups (instance_id, hostescalation_id, contactgroup_object_id) VALUES (1,?,?) ON DUPLICATE KEY UPDATE instance_id = VALUES(instance_id), hostescalation_id = VALUES(hostescalation_id), contactgroup_object_id = VALUES(contactgroup_object_id)");

    GENERIC_PREPARE();
    GENERIC_RESET_BIND();

    for (i = 0; i < num_objects.hostescalations; i++) {

        tmp = hostescalation_ary[i];

        group = tmp->contact_groups;

        while (group != NULL) {

            q_ctx->bind_i[GENERIC] = 0;

            object_id = ndo_get_object_id_name1(q_ctx, TRUE, NDO_OBJECTTYPE_CONTACTGROUP, group->group_name);

            GENERIC_BIND_INT(hostescalation_ids[i]);
            GENERIC_BIND_INT(object_id);

            GENERIC_BIND();
            GENERIC_EXECUTE();

            group = group->next;
        }
    }

    trace_return_ok();
}


int ndo_write_hostescalation_contacts(ndo_query_context *q_ctx, int * hostescalation_ids, hostescalation ** hostescalation_ary)
{
    trace_func_args("hostescalation_ids=%p", hostescalation_ids);
    int ndo_return = NDO_OK;

    hostescalation * tmp = NULL;
    contactsmember * cnt = NULL;
    int object_id = 0;
    int i = 0;

    GENERIC_RESET_SQL();

    GENERIC_SET_SQL("INSERT INTO nagios_hostescalation_contacts (instance_id, hostescalation_id, contact_object_id) VALUES (1,?,?) ON DUPLICATE KEY UPDATE instance_id = VALUES(instance_id), hostescalation_id = VALUES(hostescalation_id), contact_object_id = VALUES(contact_object_id)");

    GENERIC_PREPARE();
    GENERIC_RESET_BIND();

    for (i = 0; i < num_objects.hostescalations; i++) {

        tmp = hostescalation_ary[i];

        cnt = tmp->contacts;

        while (cnt != NULL) {

            q_ctx->bind_i[GENERIC] = 0;

            object_id = ndo_get_object_id_name1(q_ctx, TRUE, NDO_OBJECTTYPE_CONTACT, cnt->contact_name);

            GENERIC_BIND_INT(hostescalation_ids[i]);
            GENERIC_BIND_INT(object_id);

            GENERIC_BIND();
            GENERIC_EXECUTE();

            cnt = cnt->next;
        }
    }

    trace_return_ok();
}


int ndo_write_serviceescalations(ndo_query_context *q_ctx, int config_type, serviceescalation ** serviceescalation_ary)
{
    trace_func_args("config_type=%d", config_type);
    int ndo_return = NDO_OK;

    serviceescalation * tmp = NULL;
    int service_object_id = 0;
    int timeperiod_object_id = 0;
    int i = 0;

    size_t count = (size_t) num_objects.serviceescalations;
    int * object_ids = calloc(count, sizeof(int));
    int * serviceescalation_ids = calloc(count, sizeof(int));

    int serviceescalation_options[4] = { 0 };

    GENERIC_RESET_SQL();

    GENERIC_SET_SQL("INSERT INTO nagios_serviceescalations (instance_id, config_type, service_object_id, timeperiod_object_id, first_notification, last_notification, notification_interval, escalate_on_recovery, escalate_on_warning, escalate_on_unknown, escalate_on_critical) VALUES (1,?,?,?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE instance_id = VALUES(instance_id), config_type = VALUES(config_type), service_object_id = VALUES(service_object_id), timeperiod_object_id = VALUES(timeperiod_object_id), first_notification = VALUES(first_notification), last_notification = VALUES(last_notification), notification_interval = VALUES(notification_interval), escalate_on_recovery = VALUES(escalate_on_recovery), escalate_on_warning = VALUES(escalate_on_warning), escalate_on_unknown = VALUES(escalate_on_unknown), escalate_on_critical = VALUES(escalate_on_critical)");

    GENERIC_PREPARE();
    GENERIC_RESET_BIND();

    for (i = 0; i < num_objects.serviceescalations; i++) {

        q_ctx->bind_i[GENERIC] = 0;

        tmp = serviceescalation_ary[i];

        service_object_id = ndo_get_object_id_name2(q_ctx, TRUE, NDO_OBJECTTYPE_SERVICE, tmp->host_name, tmp->description);
        timeperiod_object_id = 0;
        if (tmp->escalation_period != NULL) {
            timeperiod_object_id = ndo_get_object_id_name1(q_ctx, TRUE, NDO_OBJECTTYPE_TIMEPERIOD, tmp->escalation_period);
        }

        serviceescalation_options[0] = flag_isset(tmp->escalation_options, OPT_RECOVERY);
        serviceescalation_options[1] = flag_isset(tmp->escalation_options, OPT_WARNING);
        serviceescalation_options[2] = flag_isset(tmp->escalation_options, OPT_UNKNOWN);
        serviceescalation_options[3] = flag_isset(tmp->escalation_options, OPT_CRITICAL);

        GENERIC_BIND_INT(config_type);
        GENERIC_BIND_INT(service_object_id);
        GENERIC_BIND_INT(timeperiod_object_id);
        GENERIC_BIND_INT(tmp->first_notification);
        GENERIC_BIND_INT(tmp->last_notification);
        GENERIC_BIND_FLOAT(tmp->notification_interval);
        GENERIC_BIND_INT(serviceescalation_options[0]);
        GENERIC_BIND_INT(serviceescalation_options[1]);
        GENERIC_BIND_INT(serviceescalation_options[2]);
        GENERIC_BIND_INT(serviceescalation_options[3]);

        GENERIC_BIND();
        GENERIC_EXECUTE();

        serviceescalation_ids[i] = mysql_insert_id(q_ctx->conn);
    }

    ndo_write_serviceescalation_contactgroups(q_ctx, serviceescalation_ids, serviceescalation_ary);
    ndo_write_serviceescalation_contacts(q_ctx, serviceescalation_ids, serviceescalation_ary);

    free(object_ids);
    free(serviceescalation_ids);

    trace_return_ok();
}


int ndo_write_serviceescalation_contactgroups(ndo_query_context *q_ctx, int * serviceescalation_ids, serviceescalation ** serviceescalation_ary)
{
    trace_func_args("serviceescalation_ids=%p", serviceescalation_ids);
    int ndo_return = NDO_OK;

    serviceescalation * tmp = NULL;
    contactgroupsmember * group = NULL;
    int object_id = 0;
    int i = 0;

    GENERIC_RESET_SQL();

    GENERIC_SET_SQL("INSERT INTO nagios_serviceescalation_contactgroups (instance_id, serviceescalation_id, contactgroup_object_id) VALUES (1,?,?) ON DUPLICATE KEY UPDATE instance_id = VALUES(instance_id), serviceescalation_id = VALUES(serviceescalation_id), contactgroup_object_id = VALUES(contactgroup_object_id)");

    GENERIC_PREPARE();
    GENERIC_RESET_BIND();

    for (i = 0; i < num_objects.serviceescalations; i++) {

        q_ctx->bind_i[GENERIC] = 0;

        tmp = serviceescalation_ary[i];

        group = tmp->contact_groups;

        while (group != NULL) {

            object_id = ndo_get_object_id_name1(q_ctx, TRUE, NDO_OBJECTTYPE_CONTACTGROUP, group->group_name);

            GENERIC_BIND_INT(serviceescalation_ids[i]);
            GENERIC_BIND_INT(object_id);

            GENERIC_BIND();
            GENERIC_EXECUTE();

            group = group->next;
        }
    }

    trace_return_ok();
}


int ndo_write_serviceescalation_contacts(ndo_query_context *q_ctx, int * serviceescalation_ids, serviceescalation ** serviceescalation_ary)
{
    trace_func_args("serviceescalation_ids=%p", serviceescalation_ids);
    int ndo_return = NDO_OK;

    serviceescalation * tmp = NULL;
    contactsmember * cnt = NULL;
    int object_id = 0;
    int i = 0;

    GENERIC_RESET_SQL();

    GENERIC_SET_SQL("INSERT INTO nagios_serviceescalation_contacts (instance_id, serviceescalation_id, contact_object_id) VALUES (1,?,?) ON DUPLICATE KEY UPDATE instance_id = VALUES(instance_id), serviceescalation_id = VALUES(serviceescalation_id), contact_object_id = VALUES(contact_object_id)");

    GENERIC_PREPARE();
    GENERIC_RESET_BIND();

    for (i = 0; i < num_objects.serviceescalations; i++) {

        q_ctx->bind_i[GENERIC] = 0;

        tmp = serviceescalation_ary[i];

        cnt = tmp->contacts;

        while (cnt != NULL) {

            object_id = ndo_get_object_id_name1(q_ctx, TRUE, NDO_OBJECTTYPE_CONTACT, cnt->contact_name);

            GENERIC_BIND_INT(serviceescalation_ids[i]);
            GENERIC_BIND_INT(object_id);

            GENERIC_BIND();
            GENERIC_EXECUTE();

            cnt = cnt->next;
        }
    }

    trace_return_ok();
}

// LCOV_EXCL_START
/* Note:
 * This code was probably intended for NDO 3.0, but wasn't called anywhere, and the tables don't exist in the schema.
 * I'm leaving it alone for now, in case we want to export host/servicedependencies to the database in a future version
 */

int ndo_write_hostdependencies(ndo_query_context *q_ctx, int config_type)
{
    trace_func_args("config_type=%d", config_type);
    int ndo_return = NDO_OK;

    hostdependency * tmp = NULL;
    int host_object_id = 0;
    int dependent_host_object_id = 0;
    int timeperiod_object_id = 0;
    int i = 0;

    int hostdependency_options[3] = { 0 };

    GENERIC_RESET_SQL();

    GENERIC_SET_SQL("INSERT INTO nagios_hostdependencies (instance_id, config_type, host_object_id, dependent_host_object_id, dependency_type, inherits_parent, timeperiod_object_id, fail_on_up, fail_on_down, fail_on_unreachable) VALUES (1,?,?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE instance_id = VALUES(instance_id), config_type = VALUES(config_type), host_object_id = VALUES(host_object_id), dependent_host_object_id = VALUES(dependent_host_object_id), dependency_type = VALUES(dependency_type), inherits_parent = VALUES(inherits_parent), timeperiod_object_id = VALUES(timeperiod_object_id), fail_on_up = VALUES(fail_on_up), fail_on_down = VALUES(fail_on_down), fail_on_unreachable = VALUES(fail_on_unreachable)");

    GENERIC_PREPARE();
    GENERIC_RESET_BIND();

    for (i = 0; i < num_objects.hostdependencies; i++) {

        q_ctx->bind_i[GENERIC] = 0;

        tmp = hostdependency_ary[i];

        host_object_id = ndo_get_object_id_name1(q_ctx, TRUE, NDO_OBJECTTYPE_HOST, tmp->host_name);
        dependent_host_object_id = ndo_get_object_id_name1(q_ctx, TRUE, NDO_OBJECTTYPE_HOST, tmp->dependent_host_name);
        timeperiod_object_id = 0;
        if (tmp->dependency_period != NULL) {
            timeperiod_object_id = ndo_get_object_id_name1(q_ctx, TRUE, NDO_OBJECTTYPE_TIMEPERIOD, tmp->dependency_period);
        }

        hostdependency_options[0] = flag_isset(tmp->failure_options, OPT_UP);
        hostdependency_options[1] = flag_isset(tmp->failure_options, OPT_DOWN);
        hostdependency_options[2] = flag_isset(tmp->failure_options, OPT_UNREACHABLE);

        GENERIC_BIND_INT(config_type);
        GENERIC_BIND_INT(host_object_id);
        GENERIC_BIND_INT(dependent_host_object_id);
        GENERIC_BIND_INT(tmp->dependency_type);
        GENERIC_BIND_INT(tmp->inherits_parent);
        GENERIC_BIND_INT(timeperiod_object_id);
        GENERIC_BIND_INT(hostdependency_options[0]);
        GENERIC_BIND_INT(hostdependency_options[1]);
        GENERIC_BIND_INT(hostdependency_options[2]);

        GENERIC_BIND();
        GENERIC_EXECUTE();
    }

    trace_return_ok();
}


int ndo_write_servicedependencies(ndo_query_context *q_ctx, int config_type)
{
    trace_func_args("config_type=%d", config_type);
    int ndo_return = NDO_OK;

    servicedependency * tmp = NULL;
    int service_object_id = 0;
    int dependent_service_object_id = 0;
    int timeperiod_object_id = 0;
    int i = 0;

    int servicedependency_options[4] = { 0 };

    GENERIC_RESET_SQL();

    GENERIC_SET_SQL("INSERT INTO nagios_servicedependencies (instance_id, config_type, service_object_id, dependent_service_object_id, dependency_type, inherits_parent, timeperiod_object_id, fail_on_ok, fail_on_warning, fail_on_unknown, fail_on_critical) VALUES (1,?,?,?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE instance_id = VALUES(instance_id), config_type = VALUES(config_type), service_object_id = VALUES(service_object_id), dependent_service_object_id = VALUES(dependent_service_object_id), dependency_type = VALUES(dependency_type), inherits_parent = VALUES(inherits_parent), timeperiod_object_id = VALUES(timeperiod_object_id), fail_on_ok = VALUES(fail_on_ok), fail_on_warning = VALUES(fail_on_warning), fail_on_unknown = VALUES(fail_on_unknown), fail_on_critical = VALUES(fail_on_critical)");

    GENERIC_PREPARE();
    GENERIC_RESET_BIND();

    for (i = 0; i < num_objects.servicedependencies; i++) {

        q_ctx->bind_i[GENERIC] = 0;

        tmp = servicedependency_ary[i];

        service_object_id = ndo_get_object_id_name2(q_ctx, TRUE, NDO_OBJECTTYPE_SERVICE, tmp->host_name, tmp->service_description);
        dependent_service_object_id = ndo_get_object_id_name2(q_ctx, TRUE, NDO_OBJECTTYPE_SERVICE, tmp->dependent_host_name, tmp->dependent_service_description);

        timeperiod_object_id = 0;
        if (tmp->dependency_period != NULL) {
            timeperiod_object_id = ndo_get_object_id_name1(q_ctx, TRUE, NDO_OBJECTTYPE_TIMEPERIOD, tmp->dependency_period);
        }

        servicedependency_options[0] = flag_isset(tmp->failure_options, OPT_OK);
        servicedependency_options[1] = flag_isset(tmp->failure_options, OPT_WARNING);
        servicedependency_options[2] = flag_isset(tmp->failure_options, OPT_UNKNOWN);
        servicedependency_options[3] = flag_isset(tmp->failure_options, OPT_CRITICAL);

        GENERIC_BIND_INT(config_type);
        GENERIC_BIND_INT(service_object_id);
        GENERIC_BIND_INT(dependent_service_object_id);
        GENERIC_BIND_INT(tmp->dependency_type);
        GENERIC_BIND_INT(tmp->inherits_parent);
        GENERIC_BIND_INT(timeperiod_object_id);
        GENERIC_BIND_INT(servicedependency_options[0]);
        GENERIC_BIND_INT(servicedependency_options[1]);
        GENERIC_BIND_INT(servicedependency_options[2]);
        GENERIC_BIND_INT(servicedependency_options[3]);

        GENERIC_BIND();
        GENERIC_EXECUTE();
    }

    trace_return_ok();
}

// LCOV_EXCL_STOP

int ndo_cleanup_inactive_statusinfo(ndo_query_context *q_ctx)
{
    trace_func_void();
    int ndo_return = NDO_OK;

    int i = 0;
    char * delete_sql[] = {
        "DELETE nagios_services FROM nagios_services INNER JOIN nagios_objects ON nagios_services.service_object_id = nagios_objects.object_id WHERE nagios_objects.is_active = 0",
        "DELETE nagios_hosts FROM nagios_hosts INNER JOIN nagios_objects ON nagios_hosts.host_object_id = nagios_objects.object_id WHERE nagios_objects.is_active = 0",
        "DELETE nagios_contacts FROM nagios_contacts INNER JOIN nagios_objects ON nagios_contacts.contact_object_id = nagios_objects.object_id WHERE nagios_objects.is_active = 0",
        "DELETE nagios_servicestatus FROM nagios_servicestatus INNER JOIN nagios_objects ON nagios_servicestatus.service_object_id = nagios_objects.object_id WHERE nagios_objects.is_active = 0",
        "DELETE nagios_hoststatus FROM nagios_hoststatus INNER JOIN nagios_objects ON nagios_hoststatus.host_object_id = nagios_objects.object_id WHERE nagios_objects.is_active = 0",
        "DELETE nagios_contactstatus FROM nagios_contactstatus INNER JOIN nagios_objects ON nagios_contactstatus.contact_object_id = nagios_objects.object_id WHERE nagios_objects.is_active = 0",
    };

    for (i = 0; i < ARRAY_SIZE(delete_sql); i++) {
        ndo_return = mysql_query(q_ctx->conn, delete_sql[i]);
        if (ndo_return != 0) {

            char err[BUFSZ_LARGE] = { 0 };
            snprintf(err, BUFSZ_LARGE - 1, "query(%s) failed with rc (%d), mysql (%d: %s)", delete_sql[i], ndo_return, mysql_errno(q_ctx->conn), mysql_error(q_ctx->conn));
            err[BUFSZ_LARGE - 1] = '\0';
            ndo_log(err, NSLOG_RUNTIME_WARNING);
        }
    }

    trace_return_ok();
}

int ndo_set_loaded_runtimevariable(ndo_query_context *q_ctx)
{
    trace_func_void();
    int ndo_return = NDO_OK;
    ndo_return = mysql_query(q_ctx->conn, "INSERT INTO nagios_runtimevariables (instance_id, varname, varvalue) VALUES (1, 'object_config_has_fully_loaded', 1)");

    if (ndo_return != 0) {
        trace_return_error();
    }
    trace_return_ok();
}

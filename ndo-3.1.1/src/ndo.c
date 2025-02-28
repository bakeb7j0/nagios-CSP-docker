
#define NSCORE 1
#define _GNU_SOURCE // asprintf()
#define NDO_VERSION "3.1.1"


#include "../include/nagios/logging.h"
#include "../include/nagios/nebstructs.h"
#include "../include/nagios/nebmodules.h"
#include "../include/nagios/nebcallbacks.h"
#include "../include/nagios/broker.h"
#include "../include/nagios/common.h"
#include "../include/nagios/nagios.h"
#include "../include/nagios/downtime.h"
#include "../include/nagios/comments.h"
#include "../include/nagios/macros.h"

#include "../include/ndo.h"
#include "../include/mysql-helpers.h"

#include <stdio.h>
#include <string.h>
#include <errmsg.h>
#include <time.h>
#include <stdarg.h>
#include <pthread.h>

NEB_API_VERSION(CURRENT_NEB_API_VERSION)

extern command * command_list;
extern timeperiod * timeperiod_list;
extern contact * contact_list;
extern contactgroup * contactgroup_list;
extern host * host_list;
extern hostgroup * hostgroup_list;
extern service * service_list;
extern servicegroup * servicegroup_list;
extern hostescalation * hostescalation_list;
extern hostescalation ** hostescalation_ary;
extern serviceescalation * serviceescalation_list;
extern serviceescalation ** serviceescalation_ary;
extern hostdependency * hostdependency_list;
extern hostdependency ** hostdependency_ary;
extern servicedependency * servicedependency_list;
extern servicedependency ** servicedependency_ary;
extern char * config_file;
extern sched_info scheduling_info;
extern char * global_host_event_handler;
extern char * global_service_event_handler;
extern int __nagios_object_structure_version;
extern struct object_count num_objects;

int ndo_database_connected = FALSE;
int ndo_debugging = NDO_DEBUGGING_OFF;
int ndo_is_logging = FALSE;

char * ndo_db_host = NULL;
int ndo_db_port = 3306;
char * ndo_db_socket = NULL;
char * ndo_db_user = NULL;
char * ndo_db_pass = NULL;
char * ndo_db_name = NULL;

int ndo_db_max_reconnect_attempts = 5;

ndo_query_context * main_thread_context = NULL;
ndo_query_context * startup_thread_context = NULL;
ndo_query_context * logging_context = NULL;
pthread_mutex_t logging_context_mtx = PTHREAD_MUTEX_INITIALIZER;
int ndo_logging_pid;

int num_result_bindings[NUM_QUERIES] = { 0 };

int num_bindings[NUM_QUERIES] = { 0 };

// Note: This is where ndo_return was initialized when it was a global.
char ndo_error_msg[BUFSZ_LARGE] = { 0 };

int ndo_bind_i = 0;
int ndo_result_i = 0;

int ndo_max_object_insert_count = 200;

int ndo_log_failed_queries = 1;

int ndo_writing_object_configuration = FALSE;

int ndo_startup_check_enabled = FALSE;
char * ndo_startup_hash_script_path = NULL;
int ndo_startup_skip_writing_objects = FALSE;

int ndo_timing_debugging_enabled = FALSE;

char *mysql_opt_ssl_ca = NULL;
char *mysql_opt_ssl_capath = NULL;
char *mysql_opt_ssl_cert = NULL;
char *mysql_opt_ssl_cipher = NULL;
char *mysql_opt_ssl_crl = NULL;
char *mysql_opt_ssl_crlpath = NULL;
char *mysql_opt_ssl_key = NULL;
unsigned int *mysql_opt_ssl_mode = NULL;
char *mysql_opt_tls_ciphersuites = NULL;
char *mysql_opt_tls_version = NULL;
char *mysql_set_charset_name = NULL;

void * ndo_handle = NULL;
int ndo_process_options = 0;
int ndo_config_dump_options = 0;
int ndo_die_on_failed_load = 0;
int ndo_failed_load = 0;
char * ndo_config_file = NULL;

long ndo_last_notification_id = 0L;
long ndo_last_contact_notification_id = 0L;
long nagios_config_file_id = 0L;

int ndo_debug_stack_frames = 0;

pthread_t startup_thread;

ndo_queue nebstruct_queue_timed_event = {};
ndo_queue nebstruct_queue_event_handler = {};
ndo_queue nebstruct_queue_host_check = {};
ndo_queue nebstruct_queue_service_check = {};
ndo_queue nebstruct_queue_comment = {};
ndo_queue nebstruct_queue_downtime = {};
ndo_queue nebstruct_queue_flapping = {};
ndo_queue nebstruct_queue_host_status = {};
ndo_queue nebstruct_queue_service_status = {};
ndo_queue nebstruct_queue_contact_status = {};
ndo_queue nebstruct_queue_notification = {};
ndo_queue nebstruct_queue_acknowledgement = {};
ndo_queue nebstruct_queue_statechange = {};

pthread_mutex_t queue_timed_event_mutex = PTHREAD_MUTEX_INITIALIZER;
pthread_mutex_t queue_event_handler_mutex = PTHREAD_MUTEX_INITIALIZER;
pthread_mutex_t queue_host_check_mutex = PTHREAD_MUTEX_INITIALIZER;
pthread_mutex_t queue_service_check_mutex = PTHREAD_MUTEX_INITIALIZER;
pthread_mutex_t queue_comment_mutex = PTHREAD_MUTEX_INITIALIZER;
pthread_mutex_t queue_downtime_mutex = PTHREAD_MUTEX_INITIALIZER;
pthread_mutex_t queue_flapping_mutex = PTHREAD_MUTEX_INITIALIZER;
pthread_mutex_t queue_host_status_mutex = PTHREAD_MUTEX_INITIALIZER;
pthread_mutex_t queue_service_status_mutex = PTHREAD_MUTEX_INITIALIZER;
pthread_mutex_t queue_contact_status_mutex = PTHREAD_MUTEX_INITIALIZER;
pthread_mutex_t queue_notification_mutex = PTHREAD_MUTEX_INITIALIZER;
pthread_mutex_t queue_acknowledgement_mutex = PTHREAD_MUTEX_INITIALIZER;
pthread_mutex_t queue_statechange_mutex = PTHREAD_MUTEX_INITIALIZER;

/* Note: This will produce a compiler warning but was done intentionally.
 * This global is only defined for Nagios Core 5. In Nagios Core 4, we want
 * to use an instance id of 1 for all queries. If some compiler doesn't handle
 * this gracefully, #define around it instead of changing this.
 * swolf 2022-06-02
 */
extern int database_instance_id = 1;


#include "timing.c"
/* Avoid a compile warning. This is an ugly hack, but so is the whole include structure */
int ndo_write_db_init(ndo_query_context * q_ctx);
#include "ndo-startup-queue.c"
#include "ndo-startup.c"
#include "ndo-handlers.c"
#include "ndo-handlers-queue.c"



void ndo_log(char * buffer, unsigned long level)
{
    char *tmp = NULL;
    if (asprintf(&tmp, "NDO-3: %s", buffer) < 0) {
        free(tmp);
        return;
    }
    ndo_is_logging = TRUE;
    write_to_log(tmp, level, NULL);
    ndo_is_logging = FALSE;
    free(tmp);
    return;
}


void ndo_debug(int write_to_log, const char * fmt, ...)
{
    int frame_indentation = 2;
    char frame_fmt[BUFSZ_SMOL] = { 0 };
    char buffer[BUFSZ_XL] = { 0 };
    va_list ap;
    va_start(ap, fmt);
    vsnprintf(buffer, BUFSZ_XL - 1, fmt, ap);
    va_end(ap);

    if (strlen(buffer) >= BUFSZ_XL - 1) {
        char * warning = "[LINE TRUNCATED]";
        memcpy(buffer, warning, strlen(warning));
    }

    buffer[BUFSZ_XL - 1] = '\0';

    /* create the padding */
    if (ndo_debug_stack_frames > 0) {
        snprintf(frame_fmt, BUFSZ_SMOL - 1, "%%%ds", (frame_indentation * ndo_debug_stack_frames));
        printf(frame_fmt, " ");
    }

    printf("%s\n", buffer);
}


/* Special function to log queries associated with prepared statements.
 *
 * Reconstructs the plaintext query of the prepared statement designated by stmt (e.g. GENERIC, GET_OBJECT_ID_NAME1, HANDLE_SERVICE_CHECK, etc)
 * last executed on the connection in q_ctx, and then logs it using ndo_log().
 */
void ndo_log_query(ndo_query_context *q_ctx, int stmt_id) 
{

    /* TODO: see todo in mysql_helpers. Maybe push these into the parameters */
    MYSQL *conn = q_ctx->conn;
    char *query = q_ctx->query[stmt_id];
    MYSQL_BIND *bind = q_ctx->bind[stmt_id];
    int bind_count = q_ctx->bind_i[stmt_id];

    if (conn == NULL) {
        ndo_log("Tried to log query, but MySQL connection pointer was NULL\n", NSLOG_RUNTIME_WARNING);
        return;
    }
    if (query == NULL) {
        ndo_log("Tried to log query, but MySQL query pointer was NULL\n", NSLOG_RUNTIME_WARNING);
        return;
    }
    if (bind == NULL) {
        ndo_log("Tried to log query, but MySQL bind pointer was NULL\n", NSLOG_RUNTIME_WARNING);
        return;
    }
    if (bind_count < 0) {
        ndo_log("Tried to log query, but MySQL bind_count was negative\n", NSLOG_RUNTIME_WARNING);
        return;
    }


    char final_query[MAX_SQL_BUFFER * 4];
    memset(final_query, 0, MAX_SQL_BUFFER * 4);

    int query_index = 0;
    int query_len = strlen(query);
    int bind_index = 0;
    int final_query_index = 0;

    // Specific to MYSQL_TYPE_STRING handling
    char quoted[MAX_SQL_BUFFER]; // space for a quoted individual string binding. MAX_SQL_BUFFER as array size is intentionally overkill.
    int quoted_length;
    int write_size;

    for (query_index = 0; query_index < query_len; ++query_index) {
        // Just assume that ? doesn't show up as a character literal for now. I think this is accurate as of 3.0.3
        if (query[query_index] != '?') {
            // literal character
            final_query[final_query_index++] = query[query_index];
        }
        else {
            if (bind_index >= bind_count) {
                /* Don't run off the end of the binding array */
                final_query[final_query_index++] = query[query_index];
                continue;
            }
            // bound variable
            switch (bind[bind_index].buffer_type) {
            case MYSQL_TYPE_LONG: 
                final_query_index += snprintf(final_query + final_query_index, (MAX_SQL_BUFFER * 4) - final_query_index, "%d", *((int *) bind[bind_index].buffer));
                break;
            case MYSQL_TYPE_DOUBLE: 
                final_query_index += snprintf(final_query + final_query_index, (MAX_SQL_BUFFER * 4) - final_query_index, "%f", *((double *) bind[bind_index].buffer));
                break;
            case MYSQL_TYPE_FLOAT: 
                final_query_index += snprintf(final_query + final_query_index, (MAX_SQL_BUFFER * 4) - final_query_index, "%f", *((double *) bind[bind_index].buffer));
                break;
            case MYSQL_TYPE_SHORT: 
                final_query_index += snprintf(final_query + final_query_index, (MAX_SQL_BUFFER * 4) - final_query_index, "%d", *((int *) bind[bind_index].buffer));
                break;
            case MYSQL_TYPE_TINY: 
                final_query_index += snprintf(final_query + final_query_index, (MAX_SQL_BUFFER * 4) - final_query_index, "%c", *((int *) bind[bind_index].buffer));
                break;
            case MYSQL_TYPE_LONGLONG: 
                final_query_index += snprintf(final_query + final_query_index, (MAX_SQL_BUFFER * 4) - final_query_index, "%lld", *((int *) bind[bind_index].buffer));
                break;
            case MYSQL_TYPE_STRING: 
                write_size = strlen(bind[bind_index].buffer);
                write_size = MAX_SQL_BUFFER < write_size ? MAX_SQL_BUFFER : write_size;

                quoted_length = mysql_real_escape_string(conn, quoted, bind[bind_index].buffer, write_size);
                final_query_index += snprintf(final_query + final_query_index, (MAX_SQL_BUFFER * 4) - final_query_index, "'%s'", quoted);
                break;
            default:
                final_query_index += snprintf(final_query + final_query_index, (MAX_SQL_BUFFER * 4) - final_query_index, "<unknown bind type>");
            }

            bind_index += 1;

        }
    }

    ndo_log(final_query, NSLOG_RUNTIME_WARNING);
    if (final_query_index >= MAX_SQL_BUFFER) {
        ndo_log("Note: preceding query is longer than allowed according to maximum SQL buffer size.", NSLOG_RUNTIME_WARNING);
    }

}

int nebmodule_init(int flags, char * args, void * handle)
{
    trace_func_args("flags=%d, args=%s, handle=%p", flags, args, handle);

    int result = NDO_ERROR;

    /* save handle passed from core */
    ndo_handle = handle;

    ndo_log("NDO "NDO_VERSION" (c) Copyright 2009-2024 Nagios - Nagios Core Development Team", NSLOG_INFO_MESSAGE);

    result = ndo_process_arguments(args);
    if (result != NDO_OK) {
        ndo_log("NDO was not able to process arguments and will not start. nagios will continue running.\n", NSLOG_RUNTIME_ERROR);
        trace_return_ok_cond("ndo_process_arguments() != NDO_OK");
    }

    /* this needs to happen before we process the config file so that
       mysql options are valid for the upcoming session */
    MYSQL *mysql_connection = mysql_init(NULL);
    main_thread_context = calloc(1, sizeof(ndo_query_context));
    main_thread_context->conn = mysql_connection;

    /* Logging can cause a mysql query to be used, and can happen from any thread
     * This is a mutex-protected context for handling those queries 
     */
    MYSQL *logging_connection = mysql_init(NULL);
    logging_context = calloc(1, sizeof(ndo_query_context));
    logging_context->conn = logging_connection;

    pthread_mutexattr_t logging_context_mtx_attr;
    pthread_mutexattr_init(&logging_context_mtx_attr);
    pthread_mutexattr_settype(&logging_context_mtx_attr, PTHREAD_MUTEX_NORMAL);
    pthread_mutex_init(&logging_context_mtx, &logging_context_mtx_attr);

    ndo_logging_pid = getpid();

    result = ndo_process_file(main_thread_context, ndo_config_file, ndo_process_ndo_config_line);
    if (result != NDO_OK) {
        ndo_log("NDO was not able to process its config file and will not start. nagios will continue running.", NSLOG_RUNTIME_ERROR);
        trace_return_ok_cond("ndo_process_file() != NDO_OK");
    }

    result = ndo_config_sanity_check();
    if (result != NDO_OK) {
        ndo_log("NDO's configuration failed the sanity check and NDO will not start.\n", NSLOG_RUNTIME_ERROR);
        trace_return_init_error_cond("ndo_config_sanity_check() != NDO_OK");
    }    

    result = ndo_connect_database(main_thread_context);
    if (result != NDO_OK) {
        ndo_log("NDO was not able to initialize the database (main context) and will not start.\n", NSLOG_RUNTIME_ERROR);
        trace_return_init_error_cond("ndo_connect_database(main_thread_context) != NDO_OK");
    }

    result = ndo_connect_database(logging_context);
    if (result != NDO_OK) {
        ndo_log("NDO was not able to initialize the database (logging context) and will not start.", NSLOG_RUNTIME_ERROR);
        trace_return_init_error_cond("ndo_connect_database(logging_context) != NDO_OK");
    }

    if (ndo_startup_check_enabled == TRUE) {
        ndo_calculate_startup_hash();
    }

    result = ndo_register_static_callbacks();
    if (result != NDO_OK) {
        ndo_log("NDO was not able to register static callbacks and will not start.", NSLOG_RUNTIME_ERROR);
        trace_return_init_error_cond("ndo_register_static_callbacks() != NDO_OK");
    }

    result = ndo_register_queue_callbacks();
    if (result != NDO_OK) {
        ndo_log("NDO was not able to register queue callbacks and will not start.", NSLOG_RUNTIME_ERROR);
        trace_return_init_error_cond("ndo_register_queue_callbacks() != NDO_OK");
    }

    trace_return_ok();
}


int nebmodule_deinit(int flags, int reason)
{
    trace_func_args("flags=%d, reason=%d", flags, reason);

    ndo_deregister_callbacks();
    ndo_disconnect_database(main_thread_context);
    ndo_close_query_context(main_thread_context);

    mysql_library_end();
    if (ndo_config_file != NULL) {
        free(ndo_config_file);
    }

    free(ndo_db_user);
    free(ndo_db_pass);
    free(ndo_db_name);
    free(ndo_db_host);

    free(mysql_opt_ssl_ca);
    free(mysql_opt_ssl_capath);
    free(mysql_opt_ssl_cert);
    free(mysql_opt_ssl_cipher);
    free(mysql_opt_ssl_crl);
    free(mysql_opt_ssl_crlpath);
    free(mysql_opt_ssl_key);
    free(mysql_opt_ssl_mode);
    free(mysql_opt_tls_ciphersuites);
    free(mysql_opt_tls_version);
    free(mysql_set_charset_name);

    free(ndo_startup_hash_script_path);

    ndo_log("NDO - Shutdown complete", NSLOG_INFO_MESSAGE);

    trace_return_ok();
}


/* free whatever this returns (unless it's null duh) */
char * ndo_strip(char * s)
{
    trace_func_args("s=%s", s);

    int i = 0;
    int len = 0;
    char * str = NULL;
    char * orig = NULL;
    char * tmp = NULL;

    if (s == NULL || strlen(s) == 0) {
        trace_return_null_cond("s == NULL || strlen(s) == 0");
    }

    str = strdup(s);
    orig = str;

    if (str == NULL) {
        trace_return_null_cond("str == NULL");
    }

    len = strlen(str);

    for (i = 0; i < len; i++) {
        if (str[i] == ' ' || str[i] == '\t' || str[i] == '\n' || str[i] == '\r') {

            continue;
        }
        break;
    }

    str += i;

    if (i >= (len - 1)) {
        trace_return("%s", str);
    }

    len = strlen(str);

    for (i = (len - 1); i >= 0; i--) {
        if (str[i] == ' ' || str[i] == '\t' || str[i] == '\n' || str[i] == '\r') {

            continue;
        }
        break;
    }

    str[i + 1] = '\0';

    tmp = strdup(str);
    free(orig);
    str = tmp;

    trace_return("%s", str);
}


int ndo_process_arguments(char * args)
{
    trace_func_args("args=%s", args);

    /* the only argument we accept is a config file location */
    ndo_config_file = ndo_strip(args);

    if (ndo_config_file == NULL || strlen(ndo_config_file) <= 0) {
        ndo_log("No config file specified! (broker_module=/path/to/ndo.o /PATH/TO/CONFIG/FILE)", NSLOG_RUNTIME_ERROR);
        trace_return_error_cond("ndo_config_file == NULL || strlen(ndo_config_file) <= 0");
    }

    trace_return_ok();
}


int ndo_process_file(ndo_query_context *q_ctx, char * file, int (* process_line_cb)(ndo_query_context *q_ctx, char * line))
{
    trace_func_args("file=%s", file);

    FILE * fp = NULL;
    char * contents = NULL;
    int file_size = 0;
    int read_size = 0;
    int process_result = 0;

    if (file == NULL) {
        ndo_log("NULL file passed, skipping ndo_process_file()", NSLOG_INFO_MESSAGE);
        trace_return_error_cond("file == NULL");
    }

    fp = fopen(file, "r");

    if (fp == NULL) {
        char err[BUFSZ_LARGE] = { 0 };
        snprintf(err, BUFSZ_LARGE - 1, "Unable to open config file specified - %s", file);
        ndo_log(err, NSLOG_RUNTIME_ERROR);
        trace_return_error_cond("fp == NULL");
    }

    /* see how large the file is */
    fseek(fp, 0, SEEK_END);
    file_size = ftell(fp);
    rewind(fp);

    contents = calloc(file_size + 1, sizeof(char));

    if (contents == NULL) {
        ndo_log("Could not allocate 'contents' in ndo_process_file.", NSLOG_RUNTIME_ERROR);
        fclose(fp);
        trace_return_error_cond("contents == NULL");
    }

    read_size = fread(contents, sizeof(char), file_size, fp);

    if (read_size != file_size) {
        ndo_log("Unable to fread() file", NSLOG_RUNTIME_ERROR);
        free(contents);
        fclose(fp);
        trace_return_error_cond("read_size != file_size");
    }

    fclose(fp);

    process_result = ndo_process_file_lines(q_ctx, contents, process_line_cb);

    free(contents);

    trace_return("%d", process_result);
}


int ndo_process_file_lines(ndo_query_context *q_ctx, char * contents, int (* process_line_cb)(ndo_query_context *q_ctx, char * line))
{
    trace_func_args("contents=%s", contents);

    int process_result = NDO_ERROR;
    char * current_line = contents;

    if (contents == NULL) {
        trace_return_error_cond("contents == NULL");
    }

    while (current_line != NULL) {

        char * next_line = strchr(current_line, '\n');

        if (next_line != NULL) {
            (*next_line) = '\0';
        }

        process_result = process_line_cb(q_ctx, current_line);

        if (process_result == NDO_ERROR) {
            trace("line with error: [%s]", current_line);
            trace_return_error_cond("process_result == NDO_ERROR");
        }

        if (next_line != NULL) {
            (*next_line) = '\n';
            current_line = next_line + 1;
        }
        else {
            current_line = NULL;
            break;
        }
    }

    trace_return_ok();
}

int ndo_process_ndo_config_line(ndo_query_context *q_ctx, char * line)
{
    trace_func_args("line=%s", line);

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
    if (key[0] == '#') {
        free(key);
        free(val);
        trace_return_ok_cond("key[0] == '#'");
    }

    /* database connectivity */
    else if (!strcmp("db_host", key)) {
        ndo_db_host = strdup(val);
    }
    else if (!strcmp("db_name", key)) {
        ndo_db_name = strdup(val);
    }
    else if (!strcmp("db_user", key)) {
        ndo_db_user = strdup(val);
    }
    else if (!strcmp("db_pass", key)) {
        ndo_db_pass = strdup(val);
    }
    else if (!strcmp("db_port", key)) {
        ndo_db_port = atoi(val);
    }
    else if (!strcmp("db_socket", key)) {
        ndo_db_socket = strdup(val);
    }
    else if (!strcmp("db_max_reconnect_attempts", key)) {
        ndo_db_max_reconnect_attempts = atoi(val);
    }

    else if (!strcmp("debugging", key)) {
        ndo_debugging=atoi(val);
    }

    /* configuration dumping */
    else if (!strcmp("config_dump_options", key)) {
        ndo_config_dump_options = atoi(val);
    }

    /* causes Core to die if NDO fails to initialize properly */
    else if (!strcmp("die_on_failed_load", key)) {
        ndo_die_on_failed_load = atoi(val);
    }
    else if (!strcmp("log_failed_queries", key)) {
        ndo_log_failed_queries = atoi(val);
    }

    /* determine the maximum amount of objects to send to mysql
       in a single bulk insert statement */
    else if (!strcmp("max_object_insert_count", key)) {
        ndo_max_object_insert_count = atoi(val);

        if (ndo_max_object_insert_count > (MAX_OBJECT_INSERT - 1)) {
            ndo_max_object_insert_count = MAX_OBJECT_INSERT - 1;
        }
    }

    /* should we check the contents of the nagios config directory
       before doing massive table operations in some cases? */
    else if (!strcmp("enable_startup_hash", key)) {
        ndo_startup_check_enabled = atoi(val);
    }
    else if (!strcmp("startup_hash_script_path", key)) {
        ndo_startup_hash_script_path = strdup(val);
    }

    else if (!strcmp("core5_role_parser", key)) {
        ndo_process_options = 0; // TODO FIXME: won't work properly if this line is in front of the various ndo_process_options
    }
    else if (!strcmp("core5_role_scheduler", key)) {
        ndo_startup_skip_writing_objects = TRUE;
    }

    /* If enabled, causes ndo to write startup timing information 
     * to a file -- see timing.c.
     */
    else if (!strcmp("timing_debugging_enabled", key)) {
        ndo_timing_debugging_enabled = atoi(val);
    }

    /* neb handlers */
    else if (!strcmp("process_data", key)) {
        if (atoi(val) > 0) {
            ndo_process_options |= NDO_PROCESS_PROCESS;
        }
    }
    else if (!strcmp("timed_event_data", key)) {
        if (atoi(val) > 0) {
            ndo_process_options |= NDO_PROCESS_TIMED_EVENT;
        }
    }
    else if (!strcmp("log_data", key)) {
        if (atoi(val) > 0) {
            ndo_process_options |= NDO_PROCESS_LOG;
        }
    }
    else if (!strcmp("system_command_data", key)) {
        if (atoi(val) > 0) {
            ndo_process_options |= NDO_PROCESS_SYSTEM_COMMAND;
        }
    }
    else if (!strcmp("event_handler_data", key)) {
        if (atoi(val) > 0) {
            ndo_process_options |= NDO_PROCESS_EVENT_HANDLER;
        }
    }
    else if (!strcmp("host_check_data", key)) {
        if (atoi(val) > 0) {
            ndo_process_options |= NDO_PROCESS_HOST_CHECK;
        }
    }
    else if (!strcmp("service_check_data", key)) {
        if (atoi(val) > 0) {
            ndo_process_options |= NDO_PROCESS_SERVICE_CHECK;
        }
    }
    else if (!strcmp("comment_data", key)) {
        if (atoi(val) > 0) {
            ndo_process_options |= NDO_PROCESS_COMMENT;
        }
    }
    else if (!strcmp("comment_history_data", key)) {
        if (atoi(val) > 0) {
            ndo_process_options |= NDO_PROCESS_COMMENT_HISTORY;
        }
    }
    else if (!strcmp("downtime_data", key)) {
        if (atoi(val) > 0) {
            ndo_process_options |= NDO_PROCESS_DOWNTIME;
        }
    }
    else if (!strcmp("downtime_history_data", key)) {
        if (atoi(val) > 0) {
            ndo_process_options |= NDO_PROCESS_DOWNTIME_HISTORY;
        }
    }
    else if (!strcmp("flapping_data", key)) {
        if (atoi(val) > 0) {
            ndo_process_options |= NDO_PROCESS_FLAPPING;
        }
    }
    else if (!strcmp("program_status_data", key)) {
        if (atoi(val) > 0) {
            ndo_process_options |= NDO_PROCESS_PROGRAM_STATUS;
        }
    }
    else if (!strcmp("host_status_data", key)) {
        if (atoi(val) > 0) {
            ndo_process_options |= NDO_PROCESS_HOST_STATUS;
        }
    }
    else if (!strcmp("service_status_data", key)) {
        if (atoi(val) > 0) {
            ndo_process_options |= NDO_PROCESS_SERVICE_STATUS;
        }
    }
    else if (!strcmp("contact_status_data", key)) {
        if (atoi(val) > 0) {
            ndo_process_options |= NDO_PROCESS_CONTACT_STATUS;
        }
    }
    else if (!strcmp("notification_data", key)) {
        if (atoi(val) > 0) {
            ndo_process_options |= NDO_PROCESS_NOTIFICATION;
        }
    }
    else if (!strcmp("external_command_data", key)) {
        if (atoi(val) > 0) {
            ndo_process_options |= NDO_PROCESS_EXTERNAL_COMMAND;
        }
    }
    else if (!strcmp("acknowledgement_data", key)) {
        if (atoi(val) > 0) {
            ndo_process_options |= NDO_PROCESS_ACKNOWLEDGEMENT;
        }
    }
    else if (!strcmp("state_change_data", key)) {
        if (atoi(val) > 0) {
            ndo_process_options |= NDO_PROCESS_STATE_CHANGE;
        }
    }

    /* SSL options */
    /* This first set of options seems to be supported for mariadb >= 10.0
     * The other options (further below) are not supported by any mariadb wizard 
     */
#if MYSQL_VERSION_ID > 50635
    else if (!strcasecmp("mysql_opt_ssl_ca", key)) {
        /* file path to ca certificate */
        mysql_opt_ssl_ca = strdup(val);
    }
    else if (!strcasecmp("mysql_opt_ssl_capath", key)) {
        /* directory path to ca certificate */
        mysql_opt_ssl_capath = strdup(val);
    }
    else if (!strcasecmp("mysql_opt_ssl_cert", key)) {
        /* file path to client public key */
        mysql_opt_ssl_cert = strdup(val);
    }
    else if (!strcasecmp("mysql_opt_ssl_cipher", key)) {
        /* list of acceptable ciphers */
        mysql_opt_ssl_cipher = strdup(val);
    }
    else if (!strcasecmp("mysql_opt_ssl_crl", key)) {
        /* file path to CRL list */
        mysql_opt_ssl_crl = strdup(val);
    }
    else if (!strcasecmp("mysql_opt_ssl_crlpath", key)) {
        /* directory path to CRL lists */
        mysql_opt_ssl_crlpath = strdup(val);
    }
    else if (!strcasecmp("mysql_opt_ssl_key", key)) {
        /* file path to client private key */
        mysql_opt_ssl_key = strdup(val);
    }
#endif
#if !defined(MARIADB_BASE_VERSION) && (MYSQL_VERSION_ID > 50635 || (MYSQL_VERSION_ID > 50554 && MYSQL_VERSION_ID < 50600))
    if (!strcasecmp("mysql_opt_ssl_mode", key)) {

        mysql_opt_ssl_mode = calloc(1, sizeof(unsigned int));
        if (!strcasecmp("SSL_MODE_REQUIRED", val)) {
            *mysql_opt_ssl_mode = SSL_MODE_REQUIRED;
        }
#if MYSQL_VERSION_ID > 50710
        else if (!strcasecmp("SSL_MODE_DISABLED", val)) {
            *mysql_opt_ssl_mode = SSL_MODE_DISABLED;
        }
        else if (!strcasecmp("SSL_MODE_PREFERRED", val)) {
            *mysql_opt_ssl_mode = SSL_MODE_PREFERRED;
        }
        else if (!strcasecmp("SSL_MODE_VERIFY_CA", val)) {
            *mysql_opt_ssl_mode = SSL_MODE_VERIFY_CA;
        }
        else if (!strcasecmp("SSL_MODE_VERIFY_IDENTITY", val)) {
            *mysql_opt_ssl_mode = SSL_MODE_VERIFY_IDENTITY;
        }
#endif
    }
#endif
#if !defined(MARIADB_BASE_VERSION) && (MYSQL_VERSION_ID > 80015)
    else if (!strcasecmp("mysql_opt_tls_ciphersuites", key)) {
        mysql_opt_tls_ciphersuites = strdup(val);
    }
#endif
#if !defined(MARIADB_BASE_VERSION) && (MYSQL_VERSION_ID > 50709)
    else if (!strcasecmp("mysql_opt_tls_version", key)) {
        mysql_opt_tls_version = strdup(val);
    }
#endif

    /* mysql options */
    else if (!strcmp("mysql_set_charset_name", key)) {

        mysql_set_charset_name = strdup(val);
    }

    free(key);
    free(val);

    trace_return_ok();
}


int ndo_config_sanity_check()
{
    trace_func_void();
    trace_return_ok();
}

void * ndo_reconnect_thread(void *arg)
{
    ndo_query_context *q_ctx = (ndo_query_context *)arg;
    ndo_reconnect_loop(q_ctx);
}

void ndo_reconnect_loop(ndo_query_context *q_ctx)
{
    while(q_ctx->connection_severed == TRUE) {
        sleep(1);
        ndo_reconnect_database(q_ctx);
    }
}

void ndo_start_reconnection_thread(ndo_query_context *q_ctx)
{
    // Create reconnection thread
    pthread_t reconnect_thread = 0;
    pthread_attr_t reconnect_thread_attr = {};
    pthread_attr_init(&reconnect_thread_attr);
    pthread_attr_setdetachstate(&reconnect_thread_attr, PTHREAD_CREATE_DETACHED);

    pthread_create(&reconnect_thread, 0, ndo_reconnect_thread, (void *) q_ctx);
}

int ndo_connect_database(ndo_query_context * q_ctx)
{

    trace_func_void();

    int reconnect = 1;
    MYSQL * connected = NULL;

    if (q_ctx->conn == NULL) {
        ndo_log("Unable to initialize mysql connection", NSLOG_RUNTIME_ERROR);
        trace_return_error_cond("q_ctx->conn == NULL");
    }

    /* Also set the rest of the options that were retrieved from the config file */
    if (mysql_set_charset_name != NULL) {
        mysql_options(q_ctx->conn, MYSQL_SET_CHARSET_NAME, mysql_set_charset_name);
    }

/* SSL options - these are added to over time, so check version numbers (the options themselves are enum members) */
#if MYSQL_VERSION_ID > 50635
    if (mysql_opt_ssl_ca != NULL) {
        mysql_options(q_ctx->conn, MYSQL_OPT_SSL_CA, mysql_opt_ssl_ca);
    }
    if (mysql_opt_ssl_capath != NULL) {
        mysql_options(q_ctx->conn, MYSQL_OPT_SSL_CAPATH, mysql_opt_ssl_capath);
    }
    if (mysql_opt_ssl_cert != NULL) {
        mysql_options(q_ctx->conn, MYSQL_OPT_SSL_CERT, mysql_opt_ssl_cert);
    }
    if (mysql_opt_ssl_cipher != NULL) {
        mysql_options(q_ctx->conn, MYSQL_OPT_SSL_CIPHER, mysql_opt_ssl_cipher);
    }
    if (mysql_opt_ssl_crl != NULL) {
        mysql_options(q_ctx->conn, MYSQL_OPT_SSL_CRL, mysql_opt_ssl_crl);
    }
    if (mysql_opt_ssl_crlpath != NULL) {
        mysql_options(q_ctx->conn, MYSQL_OPT_SSL_CRLPATH, mysql_opt_ssl_crlpath);
    }
    if (mysql_opt_ssl_key != NULL) {
        mysql_options(q_ctx->conn, MYSQL_OPT_SSL_KEY, mysql_opt_ssl_key);
    }
#endif
#if !defined(MARIADB_BASE_VERSION) && (MYSQL_VERSION_ID > 50635 || (MYSQL_VERSION_ID > 50554 && MYSQL_VERSION_ID < 50600))
    if (mysql_opt_ssl_mode != NULL) {
        mysql_options(q_ctx->conn, MYSQL_OPT_SSL_MODE, mysql_opt_ssl_mode);
    }
#endif
#if !defined(MARIADB_BASE_VERSION) && (MYSQL_VERSION_ID > 80015)
    if (mysql_opt_tls_ciphersuites != NULL) {
        mysql_options(q_ctx->conn, MYSQL_OPT_TLS_CIPHERSUITES, mysql_opt_tls_ciphersuites);
    }
#endif
#if !defined(MARIADB_BASE_VERSION) && (MYSQL_VERSION_ID > 50709)
    if (mysql_opt_tls_version != NULL) {
        mysql_options(q_ctx->conn, MYSQL_OPT_TLS_VERSION, mysql_opt_tls_version);
    }
#endif

    if (ndo_db_host == NULL) {
        ndo_db_host = strdup("localhost");
    }

    connected = mysql_real_connect(
        q_ctx->conn,
        ndo_db_host,
        ndo_db_user,
        ndo_db_pass,
        ndo_db_name,
        ndo_db_port,
        ndo_db_socket,
        CLIENT_REMEMBER_OPTIONS);

    if (connected == NULL) {
        ndo_log("Unable to connect to mysql. Configuration may be incorrect or database may have temporarily disconnected.", NSLOG_RUNTIME_ERROR);
        trace_return_error_cond("connected == NULL");
    }

    //ndo_log("Database initialized", NSLOG_INFO_MESSAGE);
    q_ctx->connected = TRUE;
    q_ctx->connection_severed = FALSE;

#if defined(DEBUG) && DEBUG != FALSE
    mysql_debug("d:t:O,/tmp/client.trace");
    mysql_dump_debug_info(q_ctx->conn);
#endif

    initialize_stmt_data(q_ctx);
    //init = initialize_stmt_data();
    trace_return("%d", NDO_OK);
}


int ndo_should_reconnect_database(ndo_query_context * q_ctx)
{

    if (q_ctx->connected == FALSE) {
        ndo_log("ndo_reconnect_database was called before the connection was established.", NSLOG_RUNTIME_WARNING);
    }

    int result = mysql_ping(q_ctx->conn);

    char *error_msg = NULL;
    switch (result) {
    case 0:
        /* Connection is still okay, do not reconnect */
        q_ctx->connection_severed = FALSE;
        q_ctx->reconnect_counter = 0;
        trace_return_ok();
        break;

    case CR_COMMANDS_OUT_OF_SYNC:
        error_msg = strdup("mysql_ping: Commands out of sync");
        break;

    case CR_SERVER_GONE_ERROR:
        error_msg = strdup("mysql_ping: Server has gone away");
        break;

    case CR_UNKNOWN_ERROR:
    default:
        error_msg = strdup("mysql_ping: Unknown error. Is the database running?");
        break;
    }

    /* If we get to this point, the connection was interrupted or is dead.
     * We should treat the connection as though it won't work any longer.
     */
    ndo_log(error_msg, NSLOG_RUNTIME_ERROR);
    free(error_msg);
    trace_return_error_cond("mysql_ping() != OK");

}
/* MySQL reconnection logic does not work as documented in a concurrent environment.
   In order to work around issues with automatic reconnection and with mysql_reconnect(),
   we manually manage our MySQL connections via this function.

   This function doesn't destroy q_ctx, but will free/reallocate some of its members.

   Only call this function if ndo_should_reconnect_database() returns TRUE for the current connection.
*/
int ndo_reconnect_database(ndo_query_context * q_ctx)
{
    trace_func_void();

    /* Free resources from the old connection */
    if (q_ctx->conn != NULL) {
        ndo_disconnect_database(q_ctx);
    }

    /* Try to create a new connection */
    q_ctx->conn = mysql_init(NULL);
    int connection_reinit_result = ndo_connect_database(q_ctx);

    if (connection_reinit_result != NDO_OK) {
        /* Establishing a new connection to the database failed.
         * Indicate that the connection is now severed for compatibility with old reconnection logic,
         * and let the caller decide how to proceed.
         */
        q_ctx->connection_severed = TRUE;
        mysql_close(q_ctx->conn);
        q_ctx->conn = NULL;
    }
    trace_return("%d", connection_reinit_result);
}

int ndo_register_static_callbacks()
{
    trace_func_void();
    int result = 0;

    /* this callback is always registered, as thats where the configuration writing
       comes from. ndo_process_options is actually checked in the case of a
       shutdown or restart */
    result += neb_register_callback(NEBCALLBACK_PROCESS_DATA, ndo_handle, 10, ndo_neb_handle_process);

    if (ndo_process_options & NDO_PROCESS_LOG) {
        result += neb_register_callback(NEBCALLBACK_LOG_DATA, ndo_handle, 10, ndo_neb_handle_log);
    }
    if (ndo_process_options & NDO_PROCESS_SYSTEM_COMMAND) {
        result += neb_register_callback(NEBCALLBACK_SYSTEM_COMMAND_DATA, ndo_handle, 10, ndo_neb_handle_system_command);
    }
    if (ndo_process_options & NDO_PROCESS_PROGRAM_STATUS) {
        result += neb_register_callback(NEBCALLBACK_PROGRAM_STATUS_DATA, ndo_handle, 10, ndo_neb_handle_program_status);
    }
    if (ndo_process_options & NDO_PROCESS_EXTERNAL_COMMAND) {
        result += neb_register_callback(NEBCALLBACK_EXTERNAL_COMMAND_DATA, ndo_handle, 10, ndo_neb_handle_external_command);
    }
    if (ndo_config_dump_options & NDO_CONFIG_DUMP_RETAINED) {
        result += neb_register_callback(NEBCALLBACK_RETENTION_DATA, ndo_handle, 10, ndo_neb_handle_retention);
    }

    if (result != 0) {
        ndo_log("Something went wrong registering callbacks!", NSLOG_RUNTIME_ERROR);
        trace_return_error_cond("result != 0");
    }

    ndo_log("Callbacks registered", NSLOG_INFO_MESSAGE);
    trace_return_ok();
}


int ndo_register_queue_callbacks()
{
    trace_func_void();
    int result = 0;
    neb_callback_fn handler = (neb_callback_fn)NULL;

    /* Change on 2022-06-02 (swolf):
     * In cases where we skip writing objects, we were still sending neb structs to the queue,
     * even though the object-writing thread is never spawned. Current solution (check for skip and change callback)
     * seems inelegant, like maybe ndo_neb_handle_x should check for queue complete and handle events based on that,
     * and the empty_queue functions shouldn't mess with neb callbacks at all, but I think this is the lower volume of code. 
     */
    if (ndo_process_options & NDO_PROCESS_TIMED_EVENT) {
        handler = ndo_handle_queue_timed_event;
        if (ndo_startup_skip_writing_objects) {
            handler = ndo_neb_handle_timed_event;
        }
        result += neb_register_callback(NEBCALLBACK_TIMED_EVENT_DATA, ndo_handle, 10, handler);
    }
    if (ndo_process_options & NDO_PROCESS_EVENT_HANDLER) {
        handler = ndo_handle_queue_event_handler;
        if (ndo_startup_skip_writing_objects) {
            handler = ndo_neb_handle_event_handler;
        }
        result += neb_register_callback(NEBCALLBACK_EVENT_HANDLER_DATA, ndo_handle, 10, handler);
    }
    if (ndo_process_options & NDO_PROCESS_HOST_CHECK) {
        handler = ndo_handle_queue_host_check;
        if (ndo_startup_skip_writing_objects) {
            handler = ndo_neb_handle_host_check;
        }
        result += neb_register_callback(NEBCALLBACK_HOST_CHECK_DATA, ndo_handle, 10, handler);
    }
    if (ndo_process_options & NDO_PROCESS_SERVICE_CHECK) {
        handler = ndo_handle_queue_service_check;
        if (ndo_startup_skip_writing_objects) {
            handler = ndo_neb_handle_service_check;
        }
        result += neb_register_callback(NEBCALLBACK_SERVICE_CHECK_DATA, ndo_handle, 10, handler);
    }
    if (ndo_process_options & NDO_PROCESS_COMMENT) {
        handler = ndo_handle_queue_comment;
        if (ndo_startup_skip_writing_objects) {
            handler = ndo_neb_handle_comment;
        }
        result += neb_register_callback(NEBCALLBACK_COMMENT_DATA, ndo_handle, 10, handler);
    }
    if (ndo_process_options & NDO_PROCESS_DOWNTIME) {
        handler = ndo_handle_queue_downtime;
        if (ndo_startup_skip_writing_objects) {
            handler = ndo_neb_handle_downtime;
        }
        result += neb_register_callback(NEBCALLBACK_DOWNTIME_DATA, ndo_handle, 10, handler);
    }
    if (ndo_process_options & NDO_PROCESS_FLAPPING) {
        handler = ndo_handle_queue_flapping;
        if (ndo_startup_skip_writing_objects) {
            handler = ndo_neb_handle_flapping;
        }
        result += neb_register_callback(NEBCALLBACK_FLAPPING_DATA, ndo_handle, 10, handler);
    }
    if (ndo_process_options & NDO_PROCESS_HOST_STATUS) {
        handler = ndo_handle_queue_host_status;
        if (ndo_startup_skip_writing_objects) {
            handler = ndo_neb_handle_host_status;
        }
        result += neb_register_callback(NEBCALLBACK_HOST_STATUS_DATA, ndo_handle, 10, handler);
    }
    if (ndo_process_options & NDO_PROCESS_SERVICE_STATUS) {
        handler = ndo_handle_queue_service_status;
        if (ndo_startup_skip_writing_objects) {
            handler = ndo_neb_handle_service_status;
        }
        result += neb_register_callback(NEBCALLBACK_SERVICE_STATUS_DATA, ndo_handle, 10, handler);
    }
    if (ndo_process_options & NDO_PROCESS_CONTACT_STATUS) {
        handler = ndo_handle_queue_contact_status;
        if (ndo_startup_skip_writing_objects) {
            handler = ndo_neb_handle_contact_status;
        }
        result += neb_register_callback(NEBCALLBACK_CONTACT_STATUS_DATA, ndo_handle, 10, handler);
    }
    if (ndo_process_options & NDO_PROCESS_NOTIFICATION) {
        handler = ndo_handle_queue_notification;
        if (ndo_startup_skip_writing_objects) {
            handler = ndo_neb_handle_notification;
        }
        result += neb_register_callback(NEBCALLBACK_NOTIFICATION_DATA, ndo_handle, 10, handler);

        handler = ndo_handle_queue_contact_notification;
        if (ndo_startup_skip_writing_objects) {
            handler = ndo_neb_handle_contact_notification;
        }
        result += neb_register_callback(NEBCALLBACK_CONTACT_NOTIFICATION_DATA, ndo_handle, 10, handler);

        handler = ndo_handle_queue_contact_notification_method;
        if (ndo_startup_skip_writing_objects) {
            handler = ndo_neb_handle_contact_notification_method;
        }
        result += neb_register_callback(NEBCALLBACK_CONTACT_NOTIFICATION_METHOD_DATA, ndo_handle, 10, handler);
    }
    if (ndo_process_options & NDO_PROCESS_ACKNOWLEDGEMENT) {
        handler = ndo_handle_queue_acknowledgement;
        if (ndo_startup_skip_writing_objects) {
            handler = ndo_neb_handle_acknowledgement;
        }
        result += neb_register_callback(NEBCALLBACK_ACKNOWLEDGEMENT_DATA, ndo_handle, 10, handler);
    }
    if (ndo_process_options & NDO_PROCESS_STATE_CHANGE) {
        handler = ndo_handle_queue_statechange;
        if (ndo_startup_skip_writing_objects) {
            handler = ndo_neb_handle_statechange;
        }
        result += neb_register_callback(NEBCALLBACK_STATE_CHANGE_DATA, ndo_handle, 10, handler);
    }

    if (result != 0) {
        ndo_log("Something went wrong registering callbacks!", NSLOG_RUNTIME_ERROR);
        trace_return_error_cond("result != 0");
    }

    ndo_log("Callbacks registered", NSLOG_INFO_MESSAGE);
    trace_return_ok();
}


int ndo_deregister_callbacks()
{
    trace_func_void();

    /* just to make sure these get deregistered if they were missed. i.e.: if the queue
       never gets empty? */
    neb_deregister_callback(NEBCALLBACK_TIMED_EVENT_DATA, ndo_handle_queue_timed_event);
    neb_deregister_callback(NEBCALLBACK_EVENT_HANDLER_DATA, ndo_handle_queue_event_handler);
    neb_deregister_callback(NEBCALLBACK_HOST_CHECK_DATA, ndo_handle_queue_host_check);
    neb_deregister_callback(NEBCALLBACK_SERVICE_CHECK_DATA, ndo_handle_queue_service_check);
    neb_deregister_callback(NEBCALLBACK_COMMENT_DATA, ndo_handle_queue_comment);
    neb_deregister_callback(NEBCALLBACK_DOWNTIME_DATA, ndo_handle_queue_downtime);
    neb_deregister_callback(NEBCALLBACK_FLAPPING_DATA, ndo_handle_queue_flapping);
    neb_deregister_callback(NEBCALLBACK_HOST_STATUS_DATA, ndo_handle_queue_host_status);
    neb_deregister_callback(NEBCALLBACK_SERVICE_STATUS_DATA, ndo_handle_queue_service_status);
    neb_deregister_callback(NEBCALLBACK_CONTACT_STATUS_DATA, ndo_handle_queue_contact_status);
    neb_deregister_callback(NEBCALLBACK_NOTIFICATION_DATA, ndo_handle_queue_notification);
    neb_deregister_callback(NEBCALLBACK_CONTACT_NOTIFICATION_DATA, ndo_handle_queue_contact_notification);
    neb_deregister_callback(NEBCALLBACK_CONTACT_NOTIFICATION_METHOD_DATA, ndo_handle_queue_contact_notification_method);
    neb_deregister_callback(NEBCALLBACK_ACKNOWLEDGEMENT_DATA, ndo_handle_queue_acknowledgement);
    neb_deregister_callback(NEBCALLBACK_STATE_CHANGE_DATA, ndo_handle_queue_statechange);

    /* static callbacks */
    neb_deregister_callback(NEBCALLBACK_PROCESS_DATA, ndo_neb_handle_process);
    neb_deregister_callback(NEBCALLBACK_LOG_DATA, ndo_neb_handle_log);
    neb_deregister_callback(NEBCALLBACK_SYSTEM_COMMAND_DATA, ndo_neb_handle_system_command);
    neb_deregister_callback(NEBCALLBACK_PROGRAM_STATUS_DATA, ndo_neb_handle_program_status);
    neb_deregister_callback(NEBCALLBACK_EXTERNAL_COMMAND_DATA, ndo_neb_handle_external_command);
    neb_deregister_callback(NEBCALLBACK_RETENTION_DATA, ndo_neb_handle_retention);

    /* normal handlers */
    neb_deregister_callback(NEBCALLBACK_TIMED_EVENT_DATA, ndo_neb_handle_timed_event);
    neb_deregister_callback(NEBCALLBACK_EVENT_HANDLER_DATA, ndo_neb_handle_event_handler);
    neb_deregister_callback(NEBCALLBACK_HOST_CHECK_DATA, ndo_neb_handle_host_check);
    neb_deregister_callback(NEBCALLBACK_SERVICE_CHECK_DATA, ndo_neb_handle_service_check);
    neb_deregister_callback(NEBCALLBACK_COMMENT_DATA, ndo_neb_handle_comment);
    neb_deregister_callback(NEBCALLBACK_DOWNTIME_DATA, ndo_neb_handle_downtime);
    neb_deregister_callback(NEBCALLBACK_FLAPPING_DATA, ndo_neb_handle_flapping);
    neb_deregister_callback(NEBCALLBACK_HOST_STATUS_DATA, ndo_neb_handle_host_status);
    neb_deregister_callback(NEBCALLBACK_SERVICE_STATUS_DATA, ndo_neb_handle_service_status);
    neb_deregister_callback(NEBCALLBACK_CONTACT_STATUS_DATA, ndo_neb_handle_contact_status);
    neb_deregister_callback(NEBCALLBACK_NOTIFICATION_DATA, ndo_neb_handle_notification);
    neb_deregister_callback(NEBCALLBACK_CONTACT_NOTIFICATION_DATA, ndo_neb_handle_contact_notification);
    neb_deregister_callback(NEBCALLBACK_CONTACT_NOTIFICATION_METHOD_DATA, ndo_neb_handle_contact_notification_method);
    neb_deregister_callback(NEBCALLBACK_ACKNOWLEDGEMENT_DATA, ndo_neb_handle_acknowledgement);
    neb_deregister_callback(NEBCALLBACK_STATE_CHANGE_DATA, ndo_neb_handle_statechange);

    ndo_log("Callbacks deregistered", NSLOG_INFO_MESSAGE);
    trace_return_ok();
}


void ndo_calculate_startup_hash()
{
    trace_func_void();

    int result = 0;
    int early_timeout = FALSE;
    double exectime = 0.0;
    char * output = NULL;

    if (ndo_startup_hash_script_path == NULL) {
        ndo_startup_hash_script_path = strdup(DEFAULT_STARTUP_HASH_SCRIPT_PATH);
    }

    result = my_system_r(NULL, ndo_startup_hash_script_path, 0, &early_timeout, &exectime, &output, 0);

    /* 0 ret code means that the new hash of the directory matches
       the old hash of the directory */
    if (result == 0) {
        ndo_log("Startup hashes match - SKIPPING OBJECT TRUNCATION/RE-INSERTION", NSLOG_INFO_MESSAGE);
        ndo_startup_skip_writing_objects = TRUE;
    }

    else if (result == 2) {
        char msg[BUFSZ_LARGE] = { 0 };
        snprintf(msg, BUFSZ_LARGE - 1, "Bad permissions on hashfile in (%s)", ndo_startup_hash_script_path);
        ndo_log(msg, NSLOG_RUNTIME_WARNING);
    }

    trace_return_void();
}


long ndo_get_object_id_name1(ndo_query_context *q_ctx, int insert, int object_type, char * name1)
{
    if (q_ctx->connection_severed) { return NDO_ERROR; }
    trace_func_args("insert=%d, object_type=%d, name1=%s", insert, object_type, name1);
    long object_id = NDO_ERROR;
    int ndo_return = NDO_OK;

    if (name1 == NULL || strlen(name1) == 0) {
        ndo_log("ndo_get_object_id_name1() - name1 is null", NSLOG_RUNTIME_WARNING);
        trace_return_error_cond("name1==NULL, returning error");
    }

    MYSQL_RESET_BIND(GET_OBJECT_ID_NAME1);
    MYSQL_RESET_RESULT(GET_OBJECT_ID_NAME1);

    MYSQL_BIND_INT(GET_OBJECT_ID_NAME1, object_type);
    MYSQL_BIND_STR(GET_OBJECT_ID_NAME1, name1);

    MYSQL_RESULT_LONG(GET_OBJECT_ID_NAME1, object_id);

    MYSQL_BIND(GET_OBJECT_ID_NAME1);
    MYSQL_BIND_RESULT(GET_OBJECT_ID_NAME1);
    MYSQL_EXECUTE(GET_OBJECT_ID_NAME1);
    MYSQL_STORE_RESULT(GET_OBJECT_ID_NAME1);

    if (MYSQL_FETCH(GET_OBJECT_ID_NAME1)) {
        object_id = NDO_ERROR;
    }

    trace("got object_id=%d", object_id);

    if (insert == TRUE) {

        if(object_id == NDO_ERROR) {

            object_id = ndo_insert_object_id_name1(q_ctx, object_type, name1);
        }
        else if (ndo_writing_object_configuration == TRUE) {

            MYSQL_RESET_BIND(HANDLE_OBJECT_WRITING);
            MYSQL_BIND_INT(HANDLE_OBJECT_WRITING, object_id);
            MYSQL_BIND(HANDLE_OBJECT_WRITING);
            MYSQL_EXECUTE(HANDLE_OBJECT_WRITING);
        }
    } 

    trace_return("%d", object_id);
}


long ndo_get_object_id_name2(ndo_query_context *q_ctx, int insert, int object_type, char * name1, char * name2)
{
    if (q_ctx->connection_severed) { return NDO_ERROR; }
    trace_func_args("insert=%d, object_type=%d, name1=%s, name2=%s", insert, object_type, name1, name2);
    long object_id = NDO_ERROR;
    int ndo_return = NDO_OK;

    if (name1 == NULL || strlen(name1) == 0) {
        ndo_log("ndo_get_object_id_name2() - name1 is null", NSLOG_RUNTIME_WARNING);
        trace_return_error_cond("name1==NULL, returning error");
    }

    if (name2 == NULL || strlen(name2) == 0) {
        trace_return_error_cond("name2 == NULL || strlen(name2) == 0");
    }

    MYSQL_RESET_BIND(GET_OBJECT_ID_NAME2);
    MYSQL_RESET_RESULT(GET_OBJECT_ID_NAME2);

    MYSQL_BIND_INT(GET_OBJECT_ID_NAME2, object_type);
    MYSQL_BIND_STR(GET_OBJECT_ID_NAME2, name1);
    MYSQL_BIND_STR(GET_OBJECT_ID_NAME2, name2);

    MYSQL_RESULT_INT(GET_OBJECT_ID_NAME2, object_id);

    MYSQL_BIND(GET_OBJECT_ID_NAME2);
    MYSQL_BIND_RESULT(GET_OBJECT_ID_NAME2);
    MYSQL_EXECUTE(GET_OBJECT_ID_NAME2);
    MYSQL_STORE_RESULT(GET_OBJECT_ID_NAME2);

    if (MYSQL_FETCH(GET_OBJECT_ID_NAME2)) {
        object_id = NDO_ERROR;
    }

    trace("got object_id=%d", object_id);

    if (insert == TRUE) {
        
        if (object_id == NDO_ERROR) {

            trace_info("insert==TRUE, calling ndo_insert_object_id_name2");
            object_id = ndo_insert_object_id_name2(q_ctx, object_type, name1, name2);
        }
        else if (ndo_writing_object_configuration == TRUE) {

            trace_info("ndo_writing_object_configuration==TRUE, setting is_active=1");
            MYSQL_RESET_BIND(HANDLE_OBJECT_WRITING);
            MYSQL_BIND_INT(HANDLE_OBJECT_WRITING, object_id);
            MYSQL_BIND(HANDLE_OBJECT_WRITING);
            MYSQL_EXECUTE(HANDLE_OBJECT_WRITING);
        }
    }


    trace_return("%d", object_id);
}


/* todo: these insert_object_id functions should be broken into a prepare
   and then an insert. the reason for this is that usually, this function
   will be called by get_object_id() functions ..and the object_bind is already
   appropriately set */
long ndo_insert_object_id_name1(ndo_query_context *q_ctx, int object_type, char * name1)
{
    if (q_ctx->connection_severed) { return NDO_ERROR; }
    trace_func_args("object_type=%d, name1=%s", object_type, name1);
    long object_id = NDO_ERROR;
    int ndo_return = NDO_OK;

    if (name1 == NULL || strlen(name1) == 0) {
        ndo_log("ndo_insert_object_id_name1() - name1 is null", NSLOG_RUNTIME_WARNING);
        trace_return_error_cond("name1 == NULL || strlen(name1) == 0");
    }

    MYSQL_RESET_BIND(INSERT_OBJECT_ID_NAME1);
    MYSQL_RESET_RESULT(INSERT_OBJECT_ID_NAME1);

    MYSQL_BIND_INT(INSERT_OBJECT_ID_NAME1, object_type);
    MYSQL_BIND_STR(INSERT_OBJECT_ID_NAME1, name1);

    MYSQL_BIND(INSERT_OBJECT_ID_NAME1);
    MYSQL_EXECUTE(INSERT_OBJECT_ID_NAME1);

    object_id = mysql_insert_id(q_ctx->conn);
    trace_return("%ld", object_id);
}


long ndo_insert_object_id_name2(ndo_query_context *q_ctx, int object_type, char * name1, char * name2)
{
    if (q_ctx->connection_severed) { return NDO_ERROR; }
    trace_func_args("object_type=%d, name1=%s, name2=%s", object_type, name1, name2);
    long object_id = NDO_ERROR;
    int ndo_return = NDO_OK;

    if (name1 == NULL || strlen(name1) == 0) {
        ndo_log("ndo_insert_object_id_name2() - name1 is null", NSLOG_RUNTIME_WARNING);
        trace_return_error_cond("name1 == NULL || strlen(name1) == 0");
    }

    if (name2 == NULL || strlen(name2) == 0) {
        trace_info("name2==NULL, calling ndo_insert_object_id_name1");
        object_id = ndo_insert_object_id_name1(q_ctx, object_type, name1);
        trace_return("%lu", object_id);
    }

    MYSQL_RESET_BIND(INSERT_OBJECT_ID_NAME2);
    MYSQL_RESET_RESULT(INSERT_OBJECT_ID_NAME2);

    MYSQL_BIND_INT(INSERT_OBJECT_ID_NAME2, object_type);
    MYSQL_BIND_STR(INSERT_OBJECT_ID_NAME2, name1);
    MYSQL_BIND_STR(INSERT_OBJECT_ID_NAME2, name2);

    MYSQL_BIND(INSERT_OBJECT_ID_NAME2);
    MYSQL_EXECUTE(INSERT_OBJECT_ID_NAME2);

    object_id = mysql_insert_id(q_ctx->conn);
    trace_return("%ld", object_id);
}


int ndo_write_config(int type)
{
    trace_func_args("type=%d", type);
    trace_return_ok();
}

/* Workaround for MySQL/MariaDB's limitation that FROM_UNIXTIME() can only operate on signed 32-bit integers.
 * If the time_t overflows the sign of a 32-bit integer, set it to the maximum 32-bit integer.
 * Otherwise, keep it as is.
 */
static inline time_t ndo_truncate_time(time_t original_time)
{
    if ( original_time > (time_t) 2147483647) {
        return (time_t) 2147483647; // INT_MAX, but guaranteed to be 32-bit.
    }
    return original_time;
}

void initialize_bindings_array()
{
    trace_func_void();

    num_bindings[GENERIC] = 50 * MAX_OBJECT_INSERT;
    num_bindings[GET_OBJECT_ID_NAME1] = 2;
    num_bindings[GET_OBJECT_ID_NAME2] = 3;
    num_bindings[INSERT_OBJECT_ID_NAME1] = 2;
    num_bindings[INSERT_OBJECT_ID_NAME2] = 3;
    num_bindings[HANDLE_LOG_DATA] = 5;
    num_bindings[HANDLE_PROCESS] = 6;
    num_bindings[HANDLE_PROCESS_SHUTDOWN] = 1;
    num_bindings[HANDLE_TIMEDEVENT_ADD] = 6;
    num_bindings[HANDLE_TIMEDEVENT_REMOVE] = 4;
    num_bindings[HANDLE_TIMEDEVENT_EXECUTE] = 1;
    num_bindings[HANDLE_SYSTEM_COMMAND] = 11;
    num_bindings[HANDLE_EVENT_HANDLER] = 17;
    num_bindings[HANDLE_HOST_CHECK] = 21;
    num_bindings[HANDLE_SERVICE_CHECK] = 21;
    num_bindings[HANDLE_COMMENT_ADD] = 13;
    num_bindings[HANDLE_COMMENT_HISTORY_ADD] = 13;
    num_bindings[HANDLE_COMMENT_DELETE] = 2;
    num_bindings[HANDLE_COMMENT_HISTORY_DELETE] = 4;
    num_bindings[HANDLE_DOWNTIME_ADD] = 11;
    num_bindings[HANDLE_DOWNTIME_HISTORY_ADD] = 11;
    num_bindings[HANDLE_DOWNTIME_START] = 7;
    num_bindings[HANDLE_DOWNTIME_HISTORY_START] = 7;
    num_bindings[HANDLE_DOWNTIME_STOP] = 5;
    num_bindings[HANDLE_DOWNTIME_HISTORY_STOP] = 8;
    num_bindings[HANDLE_FLAPPING] = 11;
    num_bindings[HANDLE_PROGRAM_STATUS] = 19;
    num_bindings[HANDLE_HOST_STATUS] = 46;
    num_bindings[HANDLE_SERVICE_STATUS] = 46;
    num_bindings[HANDLE_CONTACT_STATUS] = 9;
    num_bindings[HANDLE_CUSTOMVARS_CHANGE] = 4;
    num_bindings[HANDLE_NOTIFICATION] = 12;
    num_bindings[HANDLE_CONTACT_NOTIFICATION] = 6;
    num_bindings[HANDLE_CONTACT_NOTIFICATION_METHOD] = 7;
    num_bindings[HANDLE_EXTERNAL_COMMAND] = 4;
    num_bindings[HANDLE_ACKNOWLEDGEMENT] = 10;
    num_bindings[HANDLE_STATE_CHANGE] = 11;
    num_bindings[HANDLE_OBJECT_WRITING] = 1;

    num_bindings[WRITE_HANDLE_OBJECT_WRITING] = MAX_SQL_BINDINGS;
    num_bindings[WRITE_ACTIVE_OBJECTS] = MAX_SQL_BINDINGS;
    num_bindings[WRITE_CUSTOMVARS] = MAX_SQL_BINDINGS;
    num_bindings[WRITE_CUSTOMVAR_STATUS] = MAX_SQL_BINDINGS;
    num_bindings[WRITE_CONTACT_ADDRESSES] = MAX_SQL_BINDINGS;
    num_bindings[WRITE_CONTACT_NOTIFICATIONCOMMANDS] = MAX_SQL_BINDINGS;
    num_bindings[WRITE_HOST_PARENTHOSTS] = MAX_SQL_BINDINGS;
    num_bindings[WRITE_HOST_CONTACTGROUPS] = MAX_SQL_BINDINGS;
    num_bindings[WRITE_HOST_CONTACTS] = MAX_SQL_BINDINGS;
    num_bindings[WRITE_SERVICE_PARENTSERVICES] = MAX_SQL_BINDINGS;
    num_bindings[WRITE_SERVICE_CONTACTGROUPS] = MAX_SQL_BINDINGS;
    num_bindings[WRITE_SERVICE_CONTACTS] = MAX_SQL_BINDINGS;
    num_bindings[WRITE_CONTACTS] = MAX_SQL_BINDINGS;
    num_bindings[WRITE_HOSTS] = MAX_SQL_BINDINGS;
    num_bindings[WRITE_SERVICES] = MAX_SQL_BINDINGS;
    num_bindings[WRITE_CONFIG] = MAX_SQL_BINDINGS;

    num_result_bindings[GET_OBJECT_ID_NAME1] = 1;
    num_result_bindings[GET_OBJECT_ID_NAME2] = 1;

    trace_return_void();
}


int initialize_stmt_data(ndo_query_context * q_ctx)
{
    trace_func_void();

    int i = 0;
    int status = 0;
    int errors = 0;
    int memory_errors_flag = FALSE;

    initialize_bindings_array();

    if (q_ctx == NULL) {
        q_ctx = calloc(1, sizeof(ndo_query_context));
    }

    if (q_ctx == NULL) {
        ndo_log("Unable to allocate memory for q_ctx", NSLOG_RUNTIME_ERROR);
        trace_return_error_cond("q_ctx == NULL");
    }

    for (i = 0; i < NUM_QUERIES; i++) {

        if (q_ctx->stmt[i] != NULL) {
            mysql_stmt_close(q_ctx->stmt[i]);
        }

        q_ctx->stmt[i] = mysql_stmt_init(q_ctx->conn);

        if (num_bindings[i] > 0) {
            if (q_ctx->bind[i] == NULL) {
                q_ctx->bind[i] = calloc(num_bindings[i], sizeof(MYSQL_BIND));
            }
            if (q_ctx->strlen[i] == NULL) {
                q_ctx->strlen[i] = calloc(num_bindings[i], sizeof(long));
            }
        }

        if (num_result_bindings[i] > 0) {
            if (q_ctx->result[i] == NULL) {
                q_ctx->result[i] = calloc(num_result_bindings[i], sizeof(MYSQL_BIND));
            }
            if (q_ctx->result_strlen[i] == NULL) {
                q_ctx->result_strlen[i] = calloc(num_bindings[i], sizeof(long));
            }
        }

        q_ctx->bind_i[i] = 0;
        q_ctx->result_i[i] = 0;
    }

    if (q_ctx->query[GENERIC] == NULL) {
        q_ctx->query[GENERIC] = calloc(MAX_SQL_BUFFER, sizeof(char));

        q_ctx->query[GET_OBJECT_ID_NAME1] = strdup("SELECT object_id FROM nagios_objects WHERE objecttype_id = ? AND name1 = ?");
        q_ctx->query[GET_OBJECT_ID_NAME2] = strdup("SELECT object_id FROM nagios_objects WHERE objecttype_id = ? AND name1 = ? AND name2 = ?");
        q_ctx->query[INSERT_OBJECT_ID_NAME1] = strdup("INSERT INTO nagios_objects (instance_id, objecttype_id, name1, name2, is_active) VALUES (1,?,?,'',1) ON DUPLICATE KEY UPDATE is_active = 1");
        q_ctx->query[INSERT_OBJECT_ID_NAME2] = strdup("INSERT INTO nagios_objects (instance_id, objecttype_id, name1, name2, is_active) VALUES (1,?,?,?,1) ON DUPLICATE KEY UPDATE is_active = 1");
        q_ctx->query[HANDLE_LOG_DATA] = strdup("INSERT INTO nagios_logentries (instance_id, logentry_time, entry_time, entry_time_usec, logentry_type, logentry_data, realtime_data, inferred_data_extracted) VALUES (1,FROM_UNIXTIME(?),FROM_UNIXTIME(?),?,?,?,1,1)");
        q_ctx->query[HANDLE_PROCESS] = strdup("INSERT INTO nagios_processevents (instance_id, event_type, event_time, event_time_usec, process_id, program_name, program_version, program_date) VALUES (1,?,FROM_UNIXTIME(?),?,?,'Nagios',?,?)");
        q_ctx->query[HANDLE_PROCESS_SHUTDOWN] = strdup("UPDATE nagios_programstatus SET program_end_time = FROM_UNIXTIME(?), is_currently_running = 0");
        q_ctx->query[HANDLE_TIMEDEVENT_ADD] = strdup("INSERT INTO nagios_timedeventqueue (instance_id, event_type, queued_time, queued_time_usec, scheduled_time, recurring_event, object_id) VALUES (1,?,FROM_UNIXTIME(?),?,FROM_UNIXTIME(?),?,?) ON DUPLICATE KEY UPDATE instance_id = VALUES(instance_id), event_type = VALUES(event_type), queued_time = VALUES(queued_time), queued_time_usec = VALUES(queued_time_usec), scheduled_time = VALUES(scheduled_time), recurring_event = VALUES(recurring_event), object_id = VALUES(object_id)");
        q_ctx->query[HANDLE_TIMEDEVENT_REMOVE] = strdup("DELETE FROM nagios_timedeventqueue WHERE instance_id = 1 AND event_type = ? AND scheduled_time = FROM_UNIXTIME(?) AND recurring_event = ? AND object_id = ?");
        q_ctx->query[HANDLE_TIMEDEVENT_EXECUTE] = strdup("DELETE FROM nagios_timedeventqueue WHERE instance_id = 1 AND scheduled_time < FROM_UNIXTIME(?)");
        q_ctx->query[HANDLE_SYSTEM_COMMAND] = strdup("INSERT INTO nagios_systemcommands (instance_id, start_time, start_time_usec, end_time, end_time_usec, command_line, timeout, early_timeout, execution_time, return_code, output, long_output) VALUES (1,FROM_UNIXTIME(?),?,FROM_UNIXTIME(?),?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE instance_id = VALUES(instance_id), start_time = VALUES(start_time), start_time_usec = VALUES(start_time_usec), end_time = VALUES(end_time), end_time_usec = VALUES(end_time_usec), command_line = VALUES(command_line), timeout = VALUES(timeout), early_timeout = VALUES(early_timeout), execution_time = VALUES(execution_time), return_code = VALUES(return_code), output = VALUES(output), long_output = VALUES(long_output)");
        q_ctx->query[HANDLE_EVENT_HANDLER] = strdup("INSERT INTO nagios_eventhandlers (instance_id, start_time, start_time_usec, end_time, end_time_usec, eventhandler_type, object_id, state, state_type, command_object_id, command_args, command_line, timeout, early_timeout, execution_time, return_code, output, long_output) VALUES (1,FROM_UNIXTIME(?),?,FROM_UNIXTIME(?),?,?,?,?,?,?,?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE instance_id = VALUES(instance_id), start_time = VALUES(start_time), start_time_usec = VALUES(start_time_usec), end_time = VALUES(end_time), end_time_usec = VALUES(end_time_usec), eventhandler_type = VALUES(eventhandler_type), object_id = VALUES(object_id), state = VALUES(state), state_type = VALUES(state_type), command_object_id = VALUES(command_object_id), command_args = VALUES(command_args), command_line = VALUES(command_line), timeout = VALUES(timeout), early_timeout = VALUES(early_timeout), execution_time = VALUES(execution_time), return_code = VALUES(return_code), output = VALUES(output), long_output = VALUES(long_output)");
        q_ctx->query[HANDLE_HOST_CHECK] = strdup("INSERT INTO nagios_hostchecks (instance_id, start_time, start_time_usec, end_time, end_time_usec, host_object_id, check_type, current_check_attempt, max_check_attempts, state, state_type, timeout, early_timeout, execution_time, latency, return_code, output, long_output, perfdata, command_object_id, command_args, command_line) VALUES (1,FROM_UNIXTIME(?),?,FROM_UNIXTIME(?),?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE instance_id = VALUES(instance_id), start_time = VALUES(start_time), start_time_usec = VALUES(start_time_usec), end_time = VALUES(end_time), end_time_usec = VALUES(end_time_usec), host_object_id = VALUES(host_object_id), check_type = VALUES(check_type), current_check_attempt = VALUES(current_check_attempt), max_check_attempts = VALUES(max_check_attempts), state = VALUES(state), state_type = VALUES(state_type), timeout = VALUES(timeout), early_timeout = VALUES(early_timeout), execution_time = VALUES(execution_time), latency = VALUES(latency), return_code = VALUES(return_code), output = VALUES(output), long_output = VALUES(long_output), perfdata = VALUES(perfdata), command_object_id = VALUES(command_object_id), command_args = VALUES(command_args), command_line = VALUES(command_line)");
        q_ctx->query[HANDLE_SERVICE_CHECK] = strdup("INSERT INTO nagios_servicechecks (instance_id, start_time, start_time_usec, end_time, end_time_usec, service_object_id, check_type, current_check_attempt, max_check_attempts, state, state_type, timeout, early_timeout, execution_time, latency, return_code, output, long_output, perfdata, command_object_id, command_args, command_line) VALUES (1,FROM_UNIXTIME(?),?,FROM_UNIXTIME(?),?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE instance_id = VALUES(instance_id), start_time = VALUES(start_time), start_time_usec = VALUES(start_time_usec), end_time = VALUES(end_time), end_time_usec = VALUES(end_time_usec), service_object_id = VALUES(service_object_id), check_type = VALUES(check_type), current_check_attempt = VALUES(current_check_attempt), max_check_attempts = VALUES(max_check_attempts), state = VALUES(state), state_type = VALUES(state_type), timeout = VALUES(timeout), early_timeout = VALUES(early_timeout), execution_time = VALUES(execution_time), latency = VALUES(latency), return_code = VALUES(return_code), output = VALUES(output), long_output = VALUES(long_output), perfdata = VALUES(perfdata), command_object_id = VALUES(command_object_id), command_args = VALUES(command_args), command_line = VALUES(command_line)");
        q_ctx->query[HANDLE_COMMENT_ADD] = strdup("INSERT INTO nagios_comments (instance_id, comment_type, entry_type, object_id, comment_time, internal_comment_id, author_name, comment_data, is_persistent, comment_source, expires, expiration_time, entry_time, entry_time_usec) VALUES (1,?,?,?,FROM_UNIXTIME(?),?,?,?,?,?,?,FROM_UNIXTIME(?),FROM_UNIXTIME(?),?) ON DUPLICATE KEY UPDATE instance_id = VALUES(instance_id), comment_type = VALUES(comment_type), entry_type = VALUES(entry_type), object_id = VALUES(object_id), comment_time = VALUES(comment_time), internal_comment_id = VALUES(internal_comment_id), author_name = VALUES(author_name), comment_data = VALUES(comment_data), is_persistent = VALUES(is_persistent), comment_source = VALUES(comment_source), expires = VALUES(expires), expiration_time = VALUES(expiration_time), entry_time = VALUES(entry_time), entry_time_usec = VALUES(entry_time_usec)");
        q_ctx->query[HANDLE_COMMENT_HISTORY_ADD] = strdup("INSERT INTO nagios_commenthistory (instance_id, comment_type, entry_type, object_id, comment_time, internal_comment_id, author_name, comment_data, is_persistent, comment_source, expires, expiration_time, entry_time, entry_time_usec) VALUES (1,?,?,?,FROM_UNIXTIME(?),?,?,?,?,?,?,FROM_UNIXTIME(?),FROM_UNIXTIME(?),?) ON DUPLICATE KEY UPDATE instance_id = VALUES(instance_id), comment_type = VALUES(comment_type), entry_type = VALUES(entry_type), object_id = VALUES(object_id), comment_time = VALUES(comment_time), internal_comment_id = VALUES(internal_comment_id), author_name = VALUES(author_name), comment_data = VALUES(comment_data), is_persistent = VALUES(is_persistent), comment_source = VALUES(comment_source), expires = VALUES(expires), expiration_time = VALUES(expiration_time), entry_time = VALUES(entry_time), entry_time_usec = VALUES(entry_time_usec)");
        q_ctx->query[HANDLE_COMMENT_DELETE] = strdup("DELETE FROM nagios_comments WHERE instance_id = 1 AND comment_time = FROM_UNIXTIME(?) AND internal_comment_id = ?");
        q_ctx->query[HANDLE_COMMENT_HISTORY_DELETE] = strdup("UPDATE nagios_commenthistory SET deletion_time = FROM_UNIXTIME(?), deletion_time_usec = ? WHERE instance_id = 1 AND comment_time = FROM_UNIXTIME(?) AND internal_comment_id = ?");
        q_ctx->query[HANDLE_DOWNTIME_ADD] = strdup("INSERT INTO nagios_scheduleddowntime (instance_id, downtime_type, object_id, entry_time, author_name, comment_data, internal_downtime_id, triggered_by_id, is_fixed, duration, scheduled_start_time, scheduled_end_time) VALUES (1,?,?,FROM_UNIXTIME(?),?,?,?,?,?,?,FROM_UNIXTIME(?),FROM_UNIXTIME(?)) ON DUPLICATE KEY UPDATE instance_id = VALUES(instance_id), downtime_type = VALUES(downtime_type), object_id = VALUES(object_id), entry_time = VALUES(entry_time), author_name = VALUES(author_name), comment_data = VALUES(comment_data), internal_downtime_id = VALUES(internal_downtime_id), triggered_by_id = VALUES(triggered_by_id), is_fixed = VALUES(is_fixed), duration = VALUES(duration), scheduled_start_time = VALUES(scheduled_start_time), scheduled_end_time = VALUES(scheduled_end_time)");
        q_ctx->query[HANDLE_DOWNTIME_HISTORY_ADD] = strdup("INSERT INTO nagios_downtimehistory (instance_id, downtime_type, object_id, entry_time, author_name, comment_data, internal_downtime_id, triggered_by_id, is_fixed, duration, scheduled_start_time, scheduled_end_time) VALUES (1,?,?,FROM_UNIXTIME(?),?,?,?,?,?,?,FROM_UNIXTIME(?),FROM_UNIXTIME(?)) ON DUPLICATE KEY UPDATE instance_id = VALUES(instance_id), downtime_type = VALUES(downtime_type), object_id = VALUES(object_id), entry_time = VALUES(entry_time), author_name = VALUES(author_name), comment_data = VALUES(comment_data), internal_downtime_id = VALUES(internal_downtime_id), triggered_by_id = VALUES(triggered_by_id), is_fixed = VALUES(is_fixed), duration = VALUES(duration), scheduled_start_time = VALUES(scheduled_start_time), scheduled_end_time = VALUES(scheduled_end_time)");
        q_ctx->query[HANDLE_DOWNTIME_START] = strdup("UPDATE nagios_scheduleddowntime SET actual_start_time = FROM_UNIXTIME(?), actual_start_time_usec = ?, was_started = 1 WHERE instance_id = 1 AND object_id = ? AND downtime_type = ? AND entry_time = FROM_UNIXTIME(?) AND scheduled_start_time = FROM_UNIXTIME(?) AND scheduled_end_time = FROM_UNIXTIME(?)");
        q_ctx->query[HANDLE_DOWNTIME_HISTORY_START] = strdup("UPDATE nagios_downtimehistory SET actual_start_time = FROM_UNIXTIME(?), actual_start_time_usec = ?, was_started = 1 WHERE instance_id = 1 AND object_id = ? AND downtime_type = ? AND entry_time = FROM_UNIXTIME(?) AND scheduled_start_time = FROM_UNIXTIME(?) AND scheduled_end_time = FROM_UNIXTIME(?)");
        q_ctx->query[HANDLE_DOWNTIME_STOP] = strdup("DELETE FROM nagios_scheduleddowntime WHERE instance_id = 1 AND downtime_type = ? AND object_id = ? AND entry_time = FROM_UNIXTIME(?) AND scheduled_start_time = FROM_UNIXTIME(?) AND scheduled_end_time = FROM_UNIXTIME(?)");
        q_ctx->query[HANDLE_DOWNTIME_HISTORY_STOP] = strdup("UPDATE nagios_downtimehistory SET actual_end_time = FROM_UNIXTIME(?), actual_end_time_usec = ?, was_cancelled = ? WHERE instance_id = 1 AND object_id = ? AND downtime_type = ? AND entry_time = FROM_UNIXTIME(?) AND scheduled_start_time = FROM_UNIXTIME(?) AND scheduled_end_time = FROM_UNIXTIME(?)");
        q_ctx->query[HANDLE_FLAPPING] = strdup("INSERT INTO nagios_flappinghistory (instance_id, event_time, event_time_usec, event_type, reason_type, flapping_type, object_id, percent_state_change, low_threshold, high_threshold, comment_time, internal_comment_id) VALUES (1,FROM_UNIXTIME(?),?,?,?,?,?,?,?,?,FROM_UNIXTIME(?),?) ON DUPLICATE KEY UPDATE instance_id = VALUES(instance_id), event_time = VALUES(event_time), event_time_usec = VALUES(event_time_usec), event_type = VALUES(event_type), reason_type = VALUES(reason_type), flapping_type = VALUES(flapping_type), object_id = VALUES(object_id), percent_state_change = VALUES(percent_state_change), low_threshold = VALUES(low_threshold), high_threshold = VALUES(high_threshold), comment_time = VALUES(comment_time), internal_comment_id = VALUES(internal_comment_id)");
        q_ctx->query[HANDLE_PROGRAM_STATUS] = strdup("INSERT INTO nagios_programstatus (instance_id, status_update_time, program_start_time, is_currently_running, process_id, daemon_mode, last_command_check, last_log_rotation, notifications_enabled, active_service_checks_enabled, passive_service_checks_enabled, active_host_checks_enabled, passive_host_checks_enabled, event_handlers_enabled, flap_detection_enabled, failure_prediction_enabled, process_performance_data, obsess_over_hosts, obsess_over_services, modified_host_attributes, modified_service_attributes, global_host_event_handler, global_service_event_handler) VALUES (1,FROM_UNIXTIME(?),FROM_UNIXTIME(?),1,?,?,FROM_UNIXTIME(0),FROM_UNIXTIME(?),?,?,?,?,?,?,?,0,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE instance_id = VALUES(instance_id), status_update_time = VALUES(status_update_time), program_start_time = VALUES(program_start_time), is_currently_running = VALUES(is_currently_running), process_id = VALUES(process_id), daemon_mode = VALUES(daemon_mode), last_command_check = VALUES(last_command_check), last_log_rotation = VALUES(last_log_rotation), notifications_enabled = VALUES(notifications_enabled), active_service_checks_enabled = VALUES(active_service_checks_enabled), passive_service_checks_enabled = VALUES(passive_service_checks_enabled), active_host_checks_enabled = VALUES(active_host_checks_enabled), passive_host_checks_enabled = VALUES(passive_host_checks_enabled), event_handlers_enabled = VALUES(event_handlers_enabled), flap_detection_enabled = VALUES(flap_detection_enabled), failure_prediction_enabled = VALUES(failure_prediction_enabled), process_performance_data = VALUES(process_performance_data), obsess_over_hosts = VALUES(obsess_over_hosts), obsess_over_services = VALUES(obsess_over_services), modified_host_attributes = VALUES(modified_host_attributes), modified_service_attributes = VALUES(modified_service_attributes), global_host_event_handler = VALUES(global_host_event_handler), global_service_event_handler = VALUES(global_service_event_handler)");
        q_ctx->query[HANDLE_HOST_STATUS] = strdup("INSERT INTO nagios_hoststatus (instance_id, host_object_id, status_update_time, output, long_output, perfdata, current_state, has_been_checked, should_be_scheduled, current_check_attempt, max_check_attempts, last_check, next_check, check_type, check_options, last_state_change, last_hard_state_change, last_hard_state, last_time_up, last_time_down, last_time_unreachable, state_type, last_notification, next_notification, no_more_notifications, notifications_enabled, problem_has_been_acknowledged, acknowledgement_type, current_notification_number, passive_checks_enabled, active_checks_enabled, event_handler_enabled, flap_detection_enabled, is_flapping, percent_state_change, latency, execution_time, scheduled_downtime_depth, failure_prediction_enabled, process_performance_data, obsess_over_host, modified_host_attributes, event_handler, check_command, normal_check_interval, retry_check_interval, check_timeperiod_object_id) VALUES (?,?,FROM_UNIXTIME(?),?,?,?,?,?,?,?,?,FROM_UNIXTIME(?),FROM_UNIXTIME(?),?,?,FROM_UNIXTIME(?),FROM_UNIXTIME(?),?,FROM_UNIXTIME(?),FROM_UNIXTIME(?),FROM_UNIXTIME(?),?,FROM_UNIXTIME(?),FROM_UNIXTIME(?),?,?,?,?,?,?,?,?,?,?,?,?,?,?,0,?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE instance_id = VALUES(instance_id), host_object_id = VALUES(host_object_id), status_update_time = VALUES(status_update_time), output = VALUES(output), long_output = VALUES(long_output), perfdata = VALUES(perfdata), current_state = VALUES(current_state), has_been_checked = VALUES(has_been_checked), should_be_scheduled = VALUES(should_be_scheduled), current_check_attempt = VALUES(current_check_attempt), max_check_attempts = VALUES(max_check_attempts), last_check = VALUES(last_check), next_check = VALUES(next_check), check_type = VALUES(check_type), check_options = VALUES(check_options), last_state_change = VALUES(last_state_change), last_hard_state_change = VALUES(last_hard_state_change), last_hard_state = VALUES(last_hard_state), last_time_up = VALUES(last_time_up), last_time_down = VALUES(last_time_down), last_time_unreachable = VALUES(last_time_unreachable), state_type = VALUES(state_type), last_notification = VALUES(last_notification), next_notification = VALUES(next_notification), no_more_notifications = VALUES(no_more_notifications), notifications_enabled = VALUES(notifications_enabled), problem_has_been_acknowledged = VALUES(problem_has_been_acknowledged), acknowledgement_type = VALUES(acknowledgement_type), current_notification_number = VALUES(current_notification_number), passive_checks_enabled = VALUES(passive_checks_enabled), active_checks_enabled = VALUES(active_checks_enabled), event_handler_enabled = VALUES(event_handler_enabled), flap_detection_enabled = VALUES(flap_detection_enabled), is_flapping = VALUES(is_flapping), percent_state_change = VALUES(percent_state_change), latency = VALUES(latency), execution_time = VALUES(execution_time), scheduled_downtime_depth = VALUES(scheduled_downtime_depth), failure_prediction_enabled = VALUES(failure_prediction_enabled), process_performance_data = VALUES(process_performance_data), obsess_over_host = VALUES(obsess_over_host), modified_host_attributes = VALUES(modified_host_attributes), event_handler = VALUES(event_handler), check_command = VALUES(check_command), normal_check_interval = VALUES(normal_check_interval), retry_check_interval = VALUES(retry_check_interval), check_timeperiod_object_id = VALUES(check_timeperiod_object_id)");
        q_ctx->query[HANDLE_SERVICE_STATUS] = strdup("INSERT INTO nagios_servicestatus (instance_id, service_object_id, status_update_time, output, long_output, perfdata, current_state, has_been_checked, should_be_scheduled, current_check_attempt, max_check_attempts, last_check, next_check, check_type, check_options, last_state_change, last_hard_state_change, last_hard_state, last_time_ok, last_time_warning, last_time_unknown, last_time_critical, state_type, last_notification, next_notification, no_more_notifications, notifications_enabled, problem_has_been_acknowledged, acknowledgement_type, current_notification_number, passive_checks_enabled, active_checks_enabled, event_handler_enabled, flap_detection_enabled, is_flapping, percent_state_change, latency, execution_time, scheduled_downtime_depth, failure_prediction_enabled, process_performance_data, obsess_over_service, modified_service_attributes, event_handler, check_command, normal_check_interval, retry_check_interval, check_timeperiod_object_id) VALUES (1,?,FROM_UNIXTIME(?),?,?,?,?,?,?,?,?,FROM_UNIXTIME(?),FROM_UNIXTIME(?),?,?,FROM_UNIXTIME(?),FROM_UNIXTIME(?),?,FROM_UNIXTIME(?),FROM_UNIXTIME(?),FROM_UNIXTIME(?),FROM_UNIXTIME(?),?,FROM_UNIXTIME(?),FROM_UNIXTIME(?),?,?,?,?,?,?,?,?,?,?,?,?,?,?,0,?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE instance_id = VALUES(instance_id), service_object_id = VALUES(service_object_id), status_update_time = VALUES(status_update_time), output = VALUES(output), long_output = VALUES(long_output), perfdata = VALUES(perfdata), current_state = VALUES(current_state), has_been_checked = VALUES(has_been_checked), should_be_scheduled = VALUES(should_be_scheduled), current_check_attempt = VALUES(current_check_attempt), max_check_attempts = VALUES(max_check_attempts), last_check = VALUES(last_check), next_check = VALUES(next_check), check_type = VALUES(check_type), check_options = VALUES(check_options), last_state_change = VALUES(last_state_change), last_hard_state_change = VALUES(last_hard_state_change), last_hard_state = VALUES(last_hard_state), last_time_ok = VALUES(last_time_ok), last_time_warning = VALUES(last_time_warning), last_time_unknown = VALUES(last_time_unknown), last_time_critical = VALUES(last_time_critical), state_type = VALUES(state_type), last_notification = VALUES(last_notification), next_notification = VALUES(next_notification), no_more_notifications = VALUES(no_more_notifications), notifications_enabled = VALUES(notifications_enabled), problem_has_been_acknowledged = VALUES(problem_has_been_acknowledged), acknowledgement_type = VALUES(acknowledgement_type), current_notification_number = VALUES(current_notification_number), passive_checks_enabled = VALUES(passive_checks_enabled), active_checks_enabled = VALUES(active_checks_enabled), event_handler_enabled = VALUES(event_handler_enabled), flap_detection_enabled = VALUES(flap_detection_enabled), is_flapping = VALUES(is_flapping), percent_state_change = VALUES(percent_state_change), latency = VALUES(latency), execution_time = VALUES(execution_time), scheduled_downtime_depth = VALUES(scheduled_downtime_depth), failure_prediction_enabled = VALUES(failure_prediction_enabled), process_performance_data = VALUES(process_performance_data), obsess_over_service = VALUES(obsess_over_service), modified_service_attributes = VALUES(modified_service_attributes), event_handler = VALUES(event_handler), check_command = VALUES(check_command), normal_check_interval = VALUES(normal_check_interval), retry_check_interval = VALUES(retry_check_interval), check_timeperiod_object_id = VALUES(check_timeperiod_object_id)");
        q_ctx->query[HANDLE_CONTACT_STATUS] = strdup("INSERT INTO nagios_contactstatus (instance_id, contact_object_id, status_update_time, host_notifications_enabled, service_notifications_enabled, last_host_notification, last_service_notification, modified_attributes, modified_host_attributes, modified_service_attributes) VALUES (1,?,FROM_UNIXTIME(?),?,?,FROM_UNIXTIME(?),FROM_UNIXTIME(?),?,?,?) ON DUPLICATE KEY UPDATE instance_id = VALUES(instance_id), contact_object_id = VALUES(contact_object_id), status_update_time = VALUES(status_update_time), host_notifications_enabled = VALUES(host_notifications_enabled), service_notifications_enabled = VALUES(service_notifications_enabled), last_host_notification = VALUES(last_host_notification), last_service_notification = VALUES(last_service_notification), modified_attributes = VALUES(modified_attributes), modified_host_attributes = VALUES(modified_host_attributes), modified_service_attributes = VALUES(modified_service_attributes)");
        q_ctx->query[HANDLE_CUSTOMVARS_CHANGE] = strdup("INSERT INTO nagios_customvariablestatus (instance_id, object_id, status_update_time, has_been_modified, varname, varvalue) VALUES (1,?,FROM_UNIXTIME(?),1,?,?) ON DUPLICATE KEY UPDATE status_update_time = VALUES(status_update_time), has_been_modified = VALUES(has_been_modified), varname = VALUES(varname), varvalue = VALUES(varvalue)");
        q_ctx->query[HANDLE_NOTIFICATION] = strdup("INSERT INTO nagios_notifications (instance_id, start_time, start_time_usec, end_time, end_time_usec, notification_type, notification_reason, object_id, state, output, long_output, escalated, contacts_notified) VALUES (1,FROM_UNIXTIME(?),?,FROM_UNIXTIME(?),?,?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE instance_id = VALUES(instance_id), start_time = VALUES(start_time), start_time_usec = VALUES(start_time_usec), end_time = VALUES(end_time), end_time_usec = VALUES(end_time_usec), notification_type = VALUES(notification_type), notification_reason = VALUES(notification_reason), object_id = VALUES(object_id), state = VALUES(state), output = VALUES(output), long_output = VALUES(long_output), escalated = VALUES(escalated), contacts_notified = VALUES(contacts_notified)");
        q_ctx->query[HANDLE_CONTACT_NOTIFICATION] = strdup("INSERT INTO nagios_contactnotifications (instance_id, notification_id, start_time, start_time_usec, end_time, end_time_usec, contact_object_id) VALUES (1,?,FROM_UNIXTIME(?),?,FROM_UNIXTIME(?),?,?) ON DUPLICATE KEY UPDATE instance_id = VALUES(instance_id), notification_id = VALUES(notification_id), start_time = VALUES(start_time), start_time_usec = VALUES(start_time_usec), end_time = VALUES(end_time), end_time_usec = VALUES(end_time_usec), contact_object_id = VALUES(contact_object_id)");
        q_ctx->query[HANDLE_CONTACT_NOTIFICATION_METHOD] = strdup("INSERT INTO nagios_contactnotificationmethods (instance_id, contactnotification_id, start_time, start_time_usec, end_time, end_time_usec, command_object_id, command_args) VALUES (1,?,FROM_UNIXTIME(?),?,FROM_UNIXTIME(?),?,?,?) ON DUPLICATE KEY UPDATE instance_id = VALUES(instance_id), contactnotification_id = VALUES(contactnotification_id), start_time = VALUES(start_time), start_time_usec = VALUES(start_time_usec), end_time = VALUES(end_time), end_time_usec = VALUES(end_time_usec), command_object_id = VALUES(command_object_id), command_args = VALUES(command_args)");
        q_ctx->query[HANDLE_EXTERNAL_COMMAND] = strdup("INSERT INTO nagios_externalcommands (instance_id, command_type, entry_time, command_name, command_args) VALUES (1,?,FROM_UNIXTIME(?),?,?) ON DUPLICATE KEY UPDATE instance_id = VALUES(instance_id), command_type = VALUES(command_type), entry_time = VALUES(entry_time), command_name = VALUES(command_name), command_args = VALUES(command_args)");
        q_ctx->query[HANDLE_ACKNOWLEDGEMENT] = strdup("INSERT INTO nagios_acknowledgements (instance_id, entry_time, entry_time_usec, acknowledgement_type, object_id, state, author_name, comment_data, is_sticky, persistent_comment, notify_contacts) VALUES (1,FROM_UNIXTIME(?),?,?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE instance_id = VALUES(instance_id), entry_time = VALUES(entry_time), entry_time_usec = VALUES(entry_time_usec), acknowledgement_type = VALUES(acknowledgement_type), object_id = VALUES(object_id), state = VALUES(state), author_name = VALUES(author_name), comment_data = VALUES(comment_data), is_sticky = VALUES(is_sticky), persistent_comment = VALUES(persistent_comment), notify_contacts = VALUES(notify_contacts)");
        q_ctx->query[HANDLE_STATE_CHANGE] = strdup("INSERT INTO nagios_statehistory SET instance_id = 1, state_time = FROM_UNIXTIME(?), state_time_usec = ?, object_id = ?, state_change = 1, state = ?, state_type = ?, current_check_attempt = ?, max_check_attempts = ?, last_state = ?, last_hard_state = ?, output = ?, long_output = ?");
        q_ctx->query[HANDLE_OBJECT_WRITING] = strdup("UPDATE nagios_objects SET is_active = 1 WHERE object_id = ?");

        for (i = NUM_INITIALIZED_QUERIES; i < NUM_QUERIES; i += 1) {
            q_ctx->query[i] = calloc(sizeof(char), MAX_SQL_BUFFER);
        }
    }

    /* now check to make sure all those strdups worked */
    for (i = 0; i < NUM_QUERIES; i++) {
        if (q_ctx->query[i] == NULL) {
            char msg[BUFSZ_MED] = { 0 };
            snprintf(msg, BUFSZ_MED - 1, "Unable to allocate memory for query (%d)", i);
            ndo_log(msg, NSLOG_RUNTIME_ERROR);
            errors++;
        }
    }

    if (errors > 0) {
        /* We still want to prepare valid statements so that not all data is lost */
        memory_errors_flag = TRUE;
    }

    /* now prepare each statement that has an assigned query (i.e. just the handlers) */
    for (i = 1; i < NUM_INITIALIZED_QUERIES; i++) {
        if (q_ctx->query[i] == NULL) {
            continue;
        }
        status = mysql_stmt_prepare(q_ctx->stmt[i], q_ctx->query[i], strlen(q_ctx->query[i]));
        if (status) {
            char msg[BUFSZ_MED] = { 0 };
            snprintf(msg, BUFSZ_MED - 1, "Unable to prepare statement for query (%d): %s", i, mysql_stmt_error(q_ctx->stmt[i]));
            ndo_log(msg, NSLOG_RUNTIME_WARNING);
            errors++;
        }
    }

    if (errors > 0) {
        ndo_log(memory_errors_flag ? "Error allocating memory" : "Error preparing statements", NSLOG_RUNTIME_WARNING);
        trace_return_error_cond("errors > 0");
    }

    trace_return_ok();
}


int ndo_disconnect_database(ndo_query_context * q_ctx)
{
    trace_func_void();

    int i = 0;

    if (q_ctx == NULL) {
        trace_return_ok_cond("q_ctx == NULL");
    }

    for (i = 0; i < NUM_QUERIES; i++) {
        if (q_ctx->stmt[i] != NULL) {
            mysql_stmt_close(q_ctx->stmt[i]);
            q_ctx->stmt[i] = NULL;
        }
    }

    if (q_ctx->connected == TRUE) {
        mysql_close(q_ctx->conn);
        q_ctx->conn = NULL;
    }

    trace_return_ok();
}

int ndo_close_query_context(ndo_query_context * q_ctx)
{

    trace_func_void();

    int i = 0;

    if (q_ctx == NULL) {
        trace_return_ok_cond("q_ctx == NULL");
    }

    for (i = 0; i < NUM_QUERIES; i++) {
        if (q_ctx->stmt[i] != NULL) {
            mysql_stmt_close(q_ctx->stmt[i]);
            q_ctx->stmt[i] = NULL;
        }

        if (q_ctx->query[i] != NULL) {
            free(q_ctx->query[i]);
            q_ctx->query[i] = NULL;
        }

        if (q_ctx->bind[i] != NULL) {
            free(q_ctx->bind[i]);
            q_ctx->bind[i] = NULL;
        }

        if (q_ctx->strlen[i] != NULL) {
            free(q_ctx->strlen[i]);
            q_ctx->strlen[i] = NULL;
        }

        if (q_ctx->result[i] != NULL) {
            free(q_ctx->result[i]);
            q_ctx->result[i] = NULL;
        }

        if (q_ctx->result_strlen[i] != NULL) {
            free(q_ctx->result_strlen[i]);
            q_ctx->result_strlen[i] = NULL;
        }
    }

    if (q_ctx->connected == TRUE) {
        mysql_close(q_ctx->conn);
        q_ctx->conn = NULL;
    }
    free(q_ctx);

    trace_return_ok();
}

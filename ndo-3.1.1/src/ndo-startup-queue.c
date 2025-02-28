
/*
    if you want to see the expanded source, you can either:

        `make expanded`

    or:

        `gcc -E src/ndo-startup-queue.c | clang-format`
*/

/* If NDO fails startup before the empty_queue functions are called, use this function to remove the callbacks */
int ndo_deregister_queue_functions() 
{
    int result = NDO_OK;

    result |= neb_deregister_callback(NEBCALLBACK_TIMED_EVENT_DATA, ndo_handle_queue_timed_event);
    result |= neb_deregister_callback(NEBCALLBACK_EVENT_HANDLER_DATA, ndo_handle_queue_event_handler);
    result |= neb_deregister_callback(NEBCALLBACK_HOST_CHECK_DATA, ndo_handle_queue_host_check);
    result |= neb_deregister_callback(NEBCALLBACK_SERVICE_CHECK_DATA, ndo_handle_queue_service_check);
    result |= neb_deregister_callback(NEBCALLBACK_COMMENT_DATA, ndo_handle_queue_comment);
    result |= neb_deregister_callback(NEBCALLBACK_DOWNTIME_DATA, ndo_handle_queue_downtime);
    result |= neb_deregister_callback(NEBCALLBACK_FLAPPING_DATA, ndo_handle_queue_flapping);
    result |= neb_deregister_callback(NEBCALLBACK_HOST_STATUS_DATA, ndo_handle_queue_host_status);
    result |= neb_deregister_callback(NEBCALLBACK_SERVICE_STATUS_DATA, ndo_handle_queue_service_status);
    result |= neb_deregister_callback(NEBCALLBACK_CONTACT_STATUS_DATA, ndo_handle_queue_contact_status);
    result |= neb_deregister_callback(NEBCALLBACK_ACKNOWLEDGEMENT_DATA, ndo_handle_queue_acknowledgement);
    result |= neb_deregister_callback(NEBCALLBACK_STATE_CHANGE_DATA, ndo_handle_queue_statechange);
    result |= neb_deregister_callback(NEBCALLBACK_NOTIFICATION_DATA, ndo_handle_queue_notification);
    result |= neb_deregister_callback(NEBCALLBACK_CONTACT_NOTIFICATION_DATA, ndo_handle_queue_contact_notification);
    result |= neb_deregister_callback(NEBCALLBACK_CONTACT_NOTIFICATION_METHOD_DATA, ndo_handle_queue_contact_notification_method);

    return result;
}

void *ndo_thread_timed_event(void *arg);
void *ndo_thread_event_handler(void *arg);
void *ndo_thread_host_check(void *arg);
void *ndo_thread_service_check(void *arg);
void *ndo_thread_comment(void *arg);
void *ndo_thread_downtime(void *arg);
void *ndo_thread_flapping(void *arg);
void *ndo_thread_host_status(void *arg);
void *ndo_thread_service_status(void *arg);
void *ndo_thread_contact_status(void *arg);
void *ndo_thread_acknowledgement(void *arg);
void *ndo_thread_statechange(void *arg);
void *ndo_thread_notification(void *arg);


int ndo_start_queues(ndo_queue_coordinator *coordinator)
{
    // This feels like it should be in an array, but they all go to different functions.
    pthread_t timed_event, event_handler, host_check, service_check, 
    comment, downtime, flapping, host_status, service_status,
    contact_status, acknowledgement, statechange, notification;

    pthread_create(&timed_event, NULL, &ndo_thread_timed_event, (void *)coordinator);
    pthread_detach(timed_event);
    pthread_create(&event_handler, NULL, &ndo_thread_event_handler, (void *)coordinator);
    pthread_detach(event_handler);
    pthread_create(&host_check, NULL, &ndo_thread_host_check, (void *)coordinator);
    pthread_detach(host_check);
    pthread_create(&service_check, NULL, &ndo_thread_service_check, (void *)coordinator);
    pthread_detach(service_check);
    pthread_create(&comment, NULL, &ndo_thread_comment, (void *)coordinator);
    pthread_detach(comment);
    pthread_create(&downtime, NULL, &ndo_thread_downtime, (void *)coordinator);
    pthread_detach(downtime);
    pthread_create(&flapping, NULL, &ndo_thread_flapping, (void *)coordinator);
    pthread_detach(flapping);
    pthread_create(&host_status, NULL, &ndo_thread_host_status, (void *)coordinator);
    pthread_detach(host_status);
    pthread_create(&service_status, NULL, &ndo_thread_service_status, (void *)coordinator);
    pthread_detach(service_status);
    pthread_create(&contact_status, NULL, &ndo_thread_contact_status, (void *)coordinator);
    pthread_detach(contact_status);
    pthread_create(&acknowledgement, NULL, &ndo_thread_acknowledgement, (void *)coordinator);
    pthread_detach(acknowledgement);
    pthread_create(&statechange, NULL, &ndo_thread_statechange, (void *)coordinator);
    pthread_detach(statechange);
    pthread_create(&notification, NULL, &ndo_thread_notification, (void *)coordinator);
    pthread_detach(notification);

    return NDO_OK;
}

void *ndo_thread_timed_event(void *arg)
{
    ndo_log("Started timed_event thread", NSLOG_INFO_MESSAGE);
    ndo_queue_coordinator *coordinator = (ndo_queue_coordinator *)arg;
    ndo_query_context *query_context = calloc(1, sizeof(ndo_query_context));
    ndo_write_db_init(query_context);

    /* This part is specific to each thread function, and is why we're not using 
     * macros like the actual empty_queue functions. */
    pthread_mutex_lock(&(coordinator->finished_ndo_write_hosts));
    pthread_mutex_unlock(&(coordinator->finished_ndo_write_hosts));
    pthread_mutex_lock(&(coordinator->finished_ndo_write_services));
    pthread_mutex_unlock(&(coordinator->finished_ndo_write_services));

    ndo_empty_queue_timed_event(query_context);
    ndo_disconnect_database(query_context);
    ndo_close_query_context(query_context);
    ndo_log("Ended timed_event thread", NSLOG_INFO_MESSAGE);
}

void *ndo_thread_event_handler(void *arg)
{
    ndo_log("Started event_handler thread", NSLOG_INFO_MESSAGE);
    ndo_queue_coordinator *coordinator = (ndo_queue_coordinator *)arg;
    ndo_query_context *query_context = calloc(1, sizeof(ndo_query_context));
    ndo_write_db_init(query_context);

    pthread_mutex_lock(&(coordinator->finished_ndo_write_commands));
    pthread_mutex_unlock(&(coordinator->finished_ndo_write_commands));
    pthread_mutex_lock(&(coordinator->finished_ndo_write_hosts));
    pthread_mutex_unlock(&(coordinator->finished_ndo_write_hosts));
    pthread_mutex_lock(&(coordinator->finished_ndo_write_services));
    pthread_mutex_unlock(&(coordinator->finished_ndo_write_services));

    ndo_empty_queue_event_handler(query_context);
    ndo_disconnect_database(query_context);
    ndo_close_query_context(query_context);
    ndo_log("Ended event_handler thread", NSLOG_INFO_MESSAGE);
}

void *ndo_thread_host_check(void *arg)
{
    ndo_log("Started host_check thread", NSLOG_INFO_MESSAGE);
    ndo_queue_coordinator *coordinator = (ndo_queue_coordinator *)arg;
    ndo_query_context *query_context = calloc(1, sizeof(ndo_query_context));
    ndo_write_db_init(query_context);

    pthread_mutex_lock(&(coordinator->finished_ndo_write_commands));
    pthread_mutex_unlock(&(coordinator->finished_ndo_write_commands));
    pthread_mutex_lock(&(coordinator->finished_ndo_write_hosts));
    pthread_mutex_unlock(&(coordinator->finished_ndo_write_hosts));

    ndo_empty_queue_host_check(query_context);
    ndo_disconnect_database(query_context);
    ndo_close_query_context(query_context);
    ndo_log("Ended host_check thread", NSLOG_INFO_MESSAGE);
}

void *ndo_thread_service_check(void *arg)
{
    ndo_log("Started service_check thread", NSLOG_INFO_MESSAGE);
    ndo_queue_coordinator *coordinator = (ndo_queue_coordinator *)arg;
    ndo_query_context *query_context = calloc(1, sizeof(ndo_query_context));
    ndo_write_db_init(query_context);

    pthread_mutex_lock(&(coordinator->finished_ndo_write_commands));
    pthread_mutex_unlock(&(coordinator->finished_ndo_write_commands));
    pthread_mutex_lock(&(coordinator->finished_ndo_write_services));
    pthread_mutex_unlock(&(coordinator->finished_ndo_write_services));

    ndo_empty_queue_service_check(query_context);
    ndo_disconnect_database(query_context);
    ndo_close_query_context(query_context);
    ndo_log("Ended service_check thread", NSLOG_INFO_MESSAGE);
}

void *ndo_thread_comment(void *arg)
{
    ndo_log("Started comment thread", NSLOG_INFO_MESSAGE);
    ndo_queue_coordinator *coordinator = (ndo_queue_coordinator *)arg;
    ndo_query_context *query_context = calloc(1, sizeof(ndo_query_context));
    ndo_write_db_init(query_context);

    pthread_mutex_lock(&(coordinator->finished_ndo_write_hosts));
    pthread_mutex_unlock(&(coordinator->finished_ndo_write_hosts));
    pthread_mutex_lock(&(coordinator->finished_ndo_write_services));
    pthread_mutex_unlock(&(coordinator->finished_ndo_write_services));

    ndo_empty_queue_comment(query_context);
    ndo_disconnect_database(query_context);
    ndo_close_query_context(query_context);
    ndo_log("Ended comment thread", NSLOG_INFO_MESSAGE);
}

void *ndo_thread_downtime(void *arg)
{
    ndo_log("Started downtime thread", NSLOG_INFO_MESSAGE);
    ndo_queue_coordinator *coordinator = (ndo_queue_coordinator *)arg;
    ndo_query_context *query_context = calloc(1, sizeof(ndo_query_context));
    ndo_write_db_init(query_context);

    pthread_mutex_lock(&(coordinator->finished_ndo_write_hosts));
    pthread_mutex_unlock(&(coordinator->finished_ndo_write_hosts));
    pthread_mutex_lock(&(coordinator->finished_ndo_write_services));
    pthread_mutex_unlock(&(coordinator->finished_ndo_write_services));

    ndo_empty_queue_downtime(query_context);
    ndo_disconnect_database(query_context);
    ndo_close_query_context(query_context);
    ndo_log("Ended downtime thread", NSLOG_INFO_MESSAGE);
}

void *ndo_thread_flapping(void *arg)
{
    ndo_log("Started flapping thread", NSLOG_INFO_MESSAGE);
    ndo_queue_coordinator *coordinator = (ndo_queue_coordinator *)arg;
    ndo_query_context *query_context = calloc(1, sizeof(ndo_query_context));
    ndo_write_db_init(query_context);

    pthread_mutex_lock(&(coordinator->finished_ndo_write_hosts));
    pthread_mutex_unlock(&(coordinator->finished_ndo_write_hosts));
    pthread_mutex_lock(&(coordinator->finished_ndo_write_services));
    pthread_mutex_unlock(&(coordinator->finished_ndo_write_services));

    ndo_empty_queue_flapping(query_context);
    ndo_disconnect_database(query_context);
    ndo_close_query_context(query_context);
    ndo_log("Ended flapping thread", NSLOG_INFO_MESSAGE);
}

void *ndo_thread_host_status(void *arg)
{
    ndo_log("Started host_status thread", NSLOG_INFO_MESSAGE);
    ndo_queue_coordinator *coordinator = (ndo_queue_coordinator *)arg;
    ndo_query_context *query_context = calloc(1, sizeof(ndo_query_context));
    ndo_write_db_init(query_context);

    pthread_mutex_lock(&(coordinator->finished_ndo_write_hosts));
    pthread_mutex_unlock(&(coordinator->finished_ndo_write_hosts));
    pthread_mutex_lock(&(coordinator->finished_ndo_write_timeperiods));
    pthread_mutex_unlock(&(coordinator->finished_ndo_write_timeperiods));

    ndo_empty_queue_host_status(query_context);
    ndo_disconnect_database(query_context);
    ndo_close_query_context(query_context);
    ndo_log("Ended host_status thread", NSLOG_INFO_MESSAGE);
}

void *ndo_thread_service_status(void *arg)
{
    ndo_log("Started service_status thread", NSLOG_INFO_MESSAGE);
    ndo_queue_coordinator *coordinator = (ndo_queue_coordinator *)arg;
    ndo_query_context *query_context = calloc(1, sizeof(ndo_query_context));
    ndo_write_db_init(query_context);

    pthread_mutex_lock(&(coordinator->finished_ndo_write_services));
    pthread_mutex_unlock(&(coordinator->finished_ndo_write_services));
    pthread_mutex_lock(&(coordinator->finished_ndo_write_timeperiods));
    pthread_mutex_unlock(&(coordinator->finished_ndo_write_timeperiods));
    
    ndo_empty_queue_service_status(query_context);
    ndo_disconnect_database(query_context);
    ndo_close_query_context(query_context);
    ndo_log("Ended service_status thread", NSLOG_INFO_MESSAGE);
}

void *ndo_thread_contact_status(void *arg)
{
    ndo_log("Started contact_status thread", NSLOG_INFO_MESSAGE);
    ndo_queue_coordinator *coordinator = (ndo_queue_coordinator *)arg;
    ndo_query_context *query_context = calloc(1, sizeof(ndo_query_context));
    ndo_write_db_init(query_context);

    pthread_mutex_lock(&(coordinator->finished_ndo_write_contacts));
    pthread_mutex_unlock(&(coordinator->finished_ndo_write_contacts));

    ndo_empty_queue_contact_status(query_context);
    ndo_disconnect_database(query_context);
    ndo_close_query_context(query_context);
    ndo_log("Ended contact_status thread", NSLOG_INFO_MESSAGE);
}

void *ndo_thread_acknowledgement(void *arg)
{
    ndo_log("Started acknowledgement thread", NSLOG_INFO_MESSAGE);
    ndo_queue_coordinator *coordinator = (ndo_queue_coordinator *)arg;
    ndo_query_context *query_context = calloc(1, sizeof(ndo_query_context));
    ndo_write_db_init(query_context);

    pthread_mutex_lock(&(coordinator->finished_ndo_write_hosts));
    pthread_mutex_unlock(&(coordinator->finished_ndo_write_hosts));
    pthread_mutex_lock(&(coordinator->finished_ndo_write_services));
    pthread_mutex_unlock(&(coordinator->finished_ndo_write_services));

    ndo_empty_queue_acknowledgement(query_context);
    ndo_disconnect_database(query_context);
    ndo_close_query_context(query_context);
    ndo_log("Ended acknowledgement thread", NSLOG_INFO_MESSAGE);
}

void *ndo_thread_statechange(void *arg)
{
    ndo_log("Started statechange thread", NSLOG_INFO_MESSAGE);
    ndo_queue_coordinator *coordinator = (ndo_queue_coordinator *)arg;
    ndo_query_context *query_context = calloc(1, sizeof(ndo_query_context));
    ndo_write_db_init(query_context);

    pthread_mutex_lock(&(coordinator->finished_ndo_write_hosts));
    pthread_mutex_unlock(&(coordinator->finished_ndo_write_hosts));
    pthread_mutex_lock(&(coordinator->finished_ndo_write_services));
    pthread_mutex_unlock(&(coordinator->finished_ndo_write_services));

    ndo_empty_queue_statechange(query_context);
    ndo_disconnect_database(query_context);
    ndo_close_query_context(query_context);
    ndo_log("Ended statechange thread", NSLOG_INFO_MESSAGE);
}

void *ndo_thread_notification(void *arg)
{
    ndo_log("Started notification thread", NSLOG_INFO_MESSAGE);
    ndo_queue_coordinator *coordinator = (ndo_queue_coordinator *)arg;
    ndo_query_context *query_context = calloc(1, sizeof(ndo_query_context));
    ndo_write_db_init(query_context);

    pthread_mutex_lock(&(coordinator->finished_ndo_write_commands));
    pthread_mutex_unlock(&(coordinator->finished_ndo_write_commands));
    pthread_mutex_lock(&(coordinator->finished_ndo_write_hosts));
    pthread_mutex_unlock(&(coordinator->finished_ndo_write_hosts));
    pthread_mutex_lock(&(coordinator->finished_ndo_write_services));
    pthread_mutex_unlock(&(coordinator->finished_ndo_write_services));
    pthread_mutex_lock(&(coordinator->finished_ndo_write_contacts));
    pthread_mutex_unlock(&(coordinator->finished_ndo_write_contacts));

    ndo_empty_queue_notification(query_context);
    ndo_disconnect_database(query_context);
    ndo_close_query_context(query_context);
    ndo_log("Ended notification thread", NSLOG_INFO_MESSAGE);
}


int ndo_empty_startup_queues(ndo_query_context *q_ctx)
{
    int result = NDO_OK;

    result |= ndo_empty_queue_timed_event(q_ctx);
    result |= ndo_empty_queue_event_handler(q_ctx);
    result |= ndo_empty_queue_host_check(q_ctx);
    result |= ndo_empty_queue_service_check(q_ctx);
    result |= ndo_empty_queue_comment(q_ctx);
    result |= ndo_empty_queue_downtime(q_ctx);
    result |= ndo_empty_queue_flapping(q_ctx);
    result |= ndo_empty_queue_host_status(q_ctx);
    result |= ndo_empty_queue_service_status(q_ctx);
    result |= ndo_empty_queue_contact_status(q_ctx);
    result |= ndo_empty_queue_acknowledgement(q_ctx);
    result |= ndo_empty_queue_statechange(q_ctx);
    result |= ndo_empty_queue_notification(q_ctx);

    return result;
}


/* le sigh... this just makes it way easier */
#define EMPTY_QUEUE_FUNCTION(_type, _callback) \
int ndo_empty_queue_## _type(ndo_query_context *q_ctx) \
{ \
    trace_func_void(); \
\
    nebstruct_## _type ##_data * data = NULL; \
    int type = 0; \
    int result = NDO_OK; \
    int result_acc = NDO_OK; \
\
    /* first we deregister our queue callback and make sure that the */ \
    /* data goes straight into the database so that our queue doesn't get */ \
    /* backed up */ \
    int deregister_result = neb_deregister_callback(_callback, ndo_handle_queue_## _type); \
    if (NDO_OK == deregister_result) { \
        /* most common "failure" will be that the user set their own process_options - i.e. they don't want this data type handled */ \
        neb_register_callback(_callback, ndo_handle, 10, ndo_neb_handle_## _type); \
    } \
\
    while (TRUE) { \
\
        /* we probably don't need the lock and unlock because of the unreg/reg */ \
        /* prior to here, but i'm not willing to take that chance without */ \
        /* testing */ \
        pthread_mutex_lock(&queue_## _type ##_mutex); \
        data = ndo_dequeue(&nebstruct_queue_## _type, &type); \
        pthread_mutex_unlock(&queue_## _type ##_mutex); \
\
        /* the queue is empty */ \
        if (data == NULL || type == -1) { \
            break; \
        } \
\
        result = ndo_handle_## _type(q_ctx, type, data); \
        result_acc |= result; \
        if (result != NDO_OK) { \
            ndo_log("Query failed in ndo_empty_queue_"#_type, NSLOG_RUNTIME_ERROR); \
        } \
        ndo_free_members_## _type(data); \
        free(data); \
        data = NULL; \
    } \
\
    if (result_acc != NDO_OK) { \
        trace_return_error(); \
    } \
    trace_return_ok(); \
}

void ndo_free_members_timed_event(nebstruct_timed_event_data *data) {
    if (data->event_type == EVENT_SCHEDULED_DOWNTIME && data->event_data) {
        free(data->event_data);
    }
    return;
}
EMPTY_QUEUE_FUNCTION(timed_event, NEBCALLBACK_TIMED_EVENT_DATA)

void ndo_free_members_event_handler(nebstruct_event_handler_data *data) {
    free(data->host_name);
    free(data->service_description);
    free(data->command_name);
    free(data->command_args);
    free(data->command_line);
    free(data->output);
}
EMPTY_QUEUE_FUNCTION(event_handler, NEBCALLBACK_EVENT_HANDLER_DATA)

void ndo_free_members_host_check(nebstruct_host_check_data *data) {
    free(data->host_name);
    free(data->command_name);
    free(data->command_args);
    free(data->command_line);
    free(data->output);
    free(data->long_output);
    free(data->perf_data);
}
EMPTY_QUEUE_FUNCTION(host_check, NEBCALLBACK_HOST_CHECK_DATA)

void ndo_free_members_service_check(nebstruct_service_check_data *data) {
    free(data->host_name);
    free(data->service_description);
    free(data->command_name);
    free(data->command_args);
    free(data->command_line);
}
EMPTY_QUEUE_FUNCTION(service_check, NEBCALLBACK_SERVICE_CHECK_DATA)

void ndo_free_members_comment(nebstruct_comment_data *data) {
    free(data->host_name);
    free(data->service_description);
    free(data->author_name);
    free(data->comment_data);
}
EMPTY_QUEUE_FUNCTION(comment, NEBCALLBACK_COMMENT_DATA)

void ndo_free_members_downtime(nebstruct_downtime_data *data) {
    free(data->host_name);
    free(data->service_description);
    free(data->author_name);
    free(data->comment_data);
}
EMPTY_QUEUE_FUNCTION(downtime, NEBCALLBACK_DOWNTIME_DATA)

void ndo_free_members_flapping(nebstruct_flapping_data *data) {
    free(data->host_name);
    free(data->service_description);
}
EMPTY_QUEUE_FUNCTION(flapping, NEBCALLBACK_FLAPPING_DATA)

void ndo_free_members_host_status(nebstruct_host_status_data *data) {
    return;
}
EMPTY_QUEUE_FUNCTION(host_status, NEBCALLBACK_HOST_STATUS_DATA)

void ndo_free_members_service_status(nebstruct_service_status_data *data) {
    return;
}
EMPTY_QUEUE_FUNCTION(service_status, NEBCALLBACK_SERVICE_STATUS_DATA)

void ndo_free_members_contact_status(nebstruct_contact_status_data *data) {
    return;
}
EMPTY_QUEUE_FUNCTION(contact_status, NEBCALLBACK_CONTACT_STATUS_DATA)

void ndo_free_members_acknowledgement(nebstruct_acknowledgement_data *data) {
    free(data->host_name);
    free(data->service_description);
    free(data->author_name);
    free(data->comment_data);
}
EMPTY_QUEUE_FUNCTION(acknowledgement, NEBCALLBACK_ACKNOWLEDGEMENT_DATA)

void ndo_free_members_statechange(nebstruct_statechange_data *data) {
    free(data->host_name);
    free(data->service_description);
    free(data->output);
    free(data->longoutput);
}
EMPTY_QUEUE_FUNCTION(statechange, NEBCALLBACK_STATE_CHANGE_DATA)


void ndo_free_members_notification(nebstruct_notification_data *data) {
    free(data->host_name);
    free(data->service_description);
    free(data->output);
    free(data->ack_author);
    free(data->ack_data);
}

void ndo_free_members_contact_notification(nebstruct_contact_notification_data *data) {
    free(data->host_name);
    free(data->service_description);
    free(data->contact_name);
    free(data->output);
    free(data->ack_author);
    free(data->ack_data);
}

void ndo_free_members_contact_notification_method(nebstruct_contact_notification_method_data *data) {
    free(data->host_name);
    free(data->service_description);
    free(data->contact_name);
    free(data->command_name);
    free(data->command_args);
    free(data->output);
    free(data->ack_author);
    free(data->ack_data);
}
/* so, the reason this one doesn't use the prototype is because the order of
   all three callbacks that notification encapsulates is actually very important

   if they aren't executed in the exact order, then the linking ids will be
   wrong - since we don't use relational ids (e.g.: find the ids based on some
   proper linking) and instead rely on mysql_insert_id - which is fine, IF
   THEY'RE EXECUTED IN ORDER. */
int ndo_empty_queue_notification(ndo_query_context *q_ctx)
{
    trace_func_void();

    void * data = NULL;
    int type = -1;
    int result = NDO_OK;
    int result_acc = NDO_OK;
    int notification_deregister_result;
    int contact_notification_deregister_result;
    int contact_notification_method_deregister_result;

    /* unlike the EMPTY_QUEUE_FUNCTION() prototype, we can't deregister and
       then register the new ones UNTIL the queue is proven empty. if we do
       that, then we run the risk of messing up some notification ids or
       whatever */
    while (TRUE) {

        /* again, unlike the EMPTY_QUEUE_FUNCTION() prototype, the mutex
           locking and unlocking is ABSOLUTELY NECESSARY here - since we're
           popping queue members WHILE they are potentially still being added */
        pthread_mutex_lock(&queue_notification_mutex);
        data = ndo_dequeue(&nebstruct_queue_notification, &type);
        pthread_mutex_unlock(&queue_notification_mutex);

        if (data == NULL || type == -1) {

            /* there may be some contention here between deregistering and re-registering
               i don't know of a good way to solve this - or if it's really even a problem in practice
               maybe have some blocking mechanism in core to see if ndo-3 is present and if so, add some blocking
               in place */
            notification_deregister_result = neb_deregister_callback(NEBCALLBACK_NOTIFICATION_DATA, ndo_handle_queue_notification);
            contact_notification_deregister_result = neb_deregister_callback(NEBCALLBACK_CONTACT_NOTIFICATION_DATA, ndo_handle_queue_contact_notification);
            contact_notification_method_deregister_result = neb_deregister_callback(NEBCALLBACK_CONTACT_NOTIFICATION_METHOD_DATA, ndo_handle_queue_contact_notification_method);

            /* If deregister failed, assume that it's because the callback was never registeres, due to process_options */
            if (notification_deregister_result == NDO_OK) {
                neb_register_callback(NEBCALLBACK_NOTIFICATION_DATA, ndo_handle, 10, ndo_neb_handle_notification);
            }
            if (contact_notification_deregister_result == NDO_OK) {
                neb_register_callback(NEBCALLBACK_CONTACT_NOTIFICATION_DATA, ndo_handle, 10, ndo_neb_handle_contact_notification);
            }
            if (contact_notification_method_deregister_result == NDO_OK) {
                neb_register_callback(NEBCALLBACK_CONTACT_NOTIFICATION_METHOD_DATA, ndo_handle, 10, ndo_neb_handle_contact_notification_method);
            }


            break;
        }
        else if (type == NEBCALLBACK_NOTIFICATION_DATA) {
            result = ndo_handle_notification(q_ctx, type, data);
            result_acc |= result;
            if (result != NDO_OK) {
                ndo_log("Query failed in ndo_empty_queue_notification (handle_notification)", NSLOG_RUNTIME_ERROR);
            }
            ndo_free_members_notification(data);
        }
        else if (type == NEBCALLBACK_CONTACT_NOTIFICATION_DATA) {
            result = ndo_handle_contact_notification(q_ctx, type, data);
            result_acc |= result;
            if (result != NDO_OK) {
                ndo_log("Query failed in ndo_empty_queue_notification (handle_contact_notification)", NSLOG_RUNTIME_ERROR);
            }
            ndo_free_members_contact_notification(data);
        }
        else if (type == NEBCALLBACK_CONTACT_NOTIFICATION_METHOD_DATA) {
            result = ndo_handle_contact_notification_method(q_ctx, type, data);
            result_acc |= result;
            if (result != NDO_OK) {
                ndo_log("Query failed in ndo_empty_queue_notification (handle_contact_notification_method)", NSLOG_RUNTIME_ERROR);
            }
            ndo_free_members_contact_notification_method(data);
        }

        free(data);
        data = NULL;
    }

    if (result_acc != NDO_OK) {
        trace_return_error();
    }
    trace_return_ok();
}

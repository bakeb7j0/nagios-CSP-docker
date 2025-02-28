

void ndo_enqueue(ndo_queue * queue, void * data, int type)
{
    if (queue == NULL) {
        return;
    }

    if (queue->head == NULL) {
        queue->head = calloc(1, sizeof(ndo_queue_node));
        queue->tail = queue->head;
        queue->size = 1;
    }
    else {
        queue->tail->next = calloc(1, sizeof(ndo_queue_node));
        queue->tail = queue->tail->next;
        queue->size += 1;
    }

    queue->tail->data = data;
    queue->tail->type = type;
    queue->tail->next = NULL;

    return;
};

void * ndo_dequeue(ndo_queue * queue, int * type)
{
    if (queue == NULL || queue->head == NULL || queue->size == 0) {
        *type = -1;
        return NULL;
    }

    void *data = queue->head->data;
    *type = queue->head->type;
    queue->size -= 1;

    if (queue->head->next == NULL) {
        free(queue->head);
        queue->head = NULL;
        queue->tail = NULL;
    }
    else {
        ndo_queue_node *old_head = queue->head;
        queue->head = queue->head->next;
        free(old_head);
    }

    return data;
}

ndo_queue_node * ndo_queue_peek(ndo_queue *queue, int index)
{
    ndo_queue_node * ret = queue->head;
    int cur_index = 0;

    while(cur_index != index) {
        cur_index += 1;
        ret = ret->next;
    }

    return ret;
}


void nebstructcpy(void ** dest, const void * src, size_t n)
{
    void * ptr = calloc(1, n);
    memcpy(ptr, src, n);
    *dest = ptr;
}

char * nebstrdup(char * src)
{
    if (src == NULL) {
        return NULL;
    }
    return strdup(src);
}


int ndo_handle_queue_timed_event(int type, void * d)
{
    trace_func_handler(timed_event);
    nebstruct_timed_event_data * data = NULL;
    nebstructcpy((void *)&data, d, sizeof(*data));

    /* EVENT_SCHEDULED_DOWNTIME specifically frees its event data.
     * This doesn't currently happen for other values of event_type,
     * but in the future check handle_timed_event to make sure this is still true.
     */
    if (data->event_type == EVENT_SCHEDULED_DOWNTIME) {
        unsigned long tmp_downtime_id = 0;
        if (data->event_data != NULL) {
            tmp_downtime_id = *(unsigned long *)data->event_data;
        }
        data->event_data = malloc(sizeof(unsigned long));
        *((unsigned long *)(data->event_data)) = tmp_downtime_id;
    }

    /* event_ptr is not currently used in the timed_event handler.
     * Otherwise, we might need to duplicate that too.
     */

    pthread_mutex_lock(&queue_timed_event_mutex);
    ndo_enqueue(&nebstruct_queue_timed_event, data, type);
    pthread_mutex_unlock(&queue_timed_event_mutex);
    trace_return_ok();
}


int ndo_handle_queue_event_handler(int type, void * d)
{
    trace_func_handler(event_handler);
    nebstruct_event_handler_data * data = NULL;
    nebstructcpy((void *)&data, d, sizeof(*data));

    /* copy data before we add to queue so it's not pointing to data that changes later */
    data->host_name = nebstrdup(data->host_name);
    data->service_description = nebstrdup(data->service_description);
    data->command_name = nebstrdup(data->command_name);
    data->command_args = nebstrdup(data->command_args);
    data->command_line = nebstrdup(data->command_line);
    data->output = nebstrdup(data->output);

    pthread_mutex_lock(&queue_event_handler_mutex);
    ndo_enqueue(&nebstruct_queue_event_handler, data, type);
    pthread_mutex_unlock(&queue_event_handler_mutex);
    trace_return_ok();
}


int ndo_handle_queue_host_check(int type, void * d)
{
    trace_func_handler(host_check);
    nebstruct_host_check_data * data = NULL;
    nebstructcpy((void *)&data, d, sizeof(*data));

    /* copy data before we add to queue so it's not pointing to data that changes later */
    data->host_name = nebstrdup(data->host_name);
    data->command_name = nebstrdup(data->command_name);
    data->command_args = nebstrdup(data->command_args);
    data->command_line = nebstrdup(data->command_line);
    data->output = nebstrdup(data->output);
    data->long_output = nebstrdup(data->long_output);
    data->perf_data = nebstrdup(data->perf_data);

    pthread_mutex_lock(&queue_host_check_mutex);
    ndo_enqueue(&nebstruct_queue_host_check, data, type);
    pthread_mutex_unlock(&queue_host_check_mutex);
    trace_return_ok();
}


int ndo_handle_queue_service_check(int type, void * d)
{
    trace_func_handler(service_check);
    nebstruct_service_check_data * data = NULL;
    nebstructcpy((void *)&data, d, sizeof(*data));

    /* copy data before we add to queue so it's not pointing to data that changes later */
    data->host_name = nebstrdup(data->host_name);
    data->service_description = nebstrdup(data->service_description);
    data->command_name = nebstrdup(data->command_name);
    data->command_args = nebstrdup(data->command_args);
    data->command_line = nebstrdup(data->command_line);

    pthread_mutex_lock(&queue_service_check_mutex);
    ndo_enqueue(&nebstruct_queue_service_check, data, type);
    pthread_mutex_unlock(&queue_service_check_mutex);
    trace_return_ok();
}


int ndo_handle_queue_comment(int type, void * d)
{
    trace_func_handler(comment);
    nebstruct_comment_data * data = NULL;
    nebstructcpy((void *)&data, d, sizeof(*data));

    /* copy data before we add to queue so it's not pointing to data that changes later */
    data->host_name = nebstrdup(data->host_name);
    data->service_description = nebstrdup(data->service_description);
    data->author_name = nebstrdup(data->author_name);
    data->comment_data = nebstrdup(data->comment_data);

    pthread_mutex_lock(&queue_comment_mutex);
    ndo_enqueue(&nebstruct_queue_comment, data, type);
    pthread_mutex_unlock(&queue_comment_mutex);
    trace_return_ok();
}


int ndo_handle_queue_downtime(int type, void * d)
{
    trace_func_handler(downtime);
    nebstruct_downtime_data * data = NULL;
    nebstructcpy((void *)&data, d, sizeof(*data));

    /* copy data before we add to queue so it's not pointing to data that changes later */
    data->host_name = nebstrdup(data->host_name);
    data->service_description = nebstrdup(data->service_description);
    data->author_name = nebstrdup(data->author_name);
    data->comment_data = nebstrdup(data->comment_data);

    pthread_mutex_lock(&queue_downtime_mutex);
    ndo_enqueue(&nebstruct_queue_downtime, data, type);
    pthread_mutex_unlock(&queue_downtime_mutex);
    trace_return_ok();
}


int ndo_handle_queue_flapping(int type, void * d)
{
    trace_func_handler(flapping);
    nebstruct_flapping_data * data = NULL;
    nebstructcpy((void *)&data, d, sizeof(*data));

    /* copy data before we add to queue so it's not pointing to data that changes later */
    data->host_name = nebstrdup(data->host_name);
    data->service_description = nebstrdup(data->service_description);

    pthread_mutex_lock(&queue_flapping_mutex);
    ndo_enqueue(&nebstruct_queue_flapping, data, type);
    pthread_mutex_unlock(&queue_flapping_mutex);
    trace_return_ok();
}


int ndo_handle_queue_host_status(int type, void * d)
{
    trace_func_handler(host_status);
    nebstruct_host_status_data * data = NULL;
    nebstructcpy((void *)&data, d, sizeof(*data));
    pthread_mutex_lock(&queue_host_status_mutex);
    ndo_enqueue(&nebstruct_queue_host_status, data, type);
    pthread_mutex_unlock(&queue_host_status_mutex);
    trace_return_ok();
}


int ndo_handle_queue_service_status(int type, void * d)
{
    trace_func_handler(service_status);
    nebstruct_service_status_data * data = NULL;
    nebstructcpy((void *)&data, d, sizeof(*data));
    pthread_mutex_lock(&queue_service_status_mutex);
    ndo_enqueue(&nebstruct_queue_service_status, data, type);
    pthread_mutex_unlock(&queue_service_status_mutex);
    trace_return_ok();
}


int ndo_handle_queue_contact_status(int type, void * d)
{
    trace_func_handler(contact_status);
    nebstruct_contact_status_data * data = NULL;
    nebstructcpy((void *)&data, d, sizeof(*data));
    pthread_mutex_lock(&queue_contact_status_mutex);
    ndo_enqueue(&nebstruct_queue_contact_status, data, type);
    pthread_mutex_unlock(&queue_contact_status_mutex);
    trace_return_ok();
}


int ndo_handle_queue_notification(int type, void * d)
{
    trace_func_handler(notification);
    nebstruct_notification_data * data = NULL;
    nebstructcpy((void *)&data, d, sizeof(*data));

    /* copy data before we add to queue so it's not pointing to data that changes later */
    data->host_name = nebstrdup(data->host_name);
    data->service_description = nebstrdup(data->service_description);
    data->output = nebstrdup(data->output);
    data->ack_author = nebstrdup(data->ack_author);
    data->ack_data = nebstrdup(data->ack_data);

    pthread_mutex_lock(&queue_notification_mutex);
    ndo_enqueue(&nebstruct_queue_notification, data, type);
    pthread_mutex_unlock(&queue_notification_mutex);
    trace_return_ok();
}


int ndo_handle_queue_contact_notification(int type, void * d)
{
    trace_func_handler(contact_notification);
    nebstruct_contact_notification_data * data = NULL;
    nebstructcpy((void *)&data, d, sizeof(*data));

    /* copy data before we add to queue so it's not pointing to data that changes later */
    data->host_name = nebstrdup(data->host_name);
    data->service_description = nebstrdup(data->service_description);
    data->contact_name = nebstrdup(data->contact_name);
    data->output = nebstrdup(data->output);
    data->ack_author = nebstrdup(data->ack_author);
    data->ack_data = nebstrdup(data->ack_data);

    pthread_mutex_lock(&queue_notification_mutex);
    ndo_enqueue(&nebstruct_queue_notification, data, type);
    pthread_mutex_unlock(&queue_notification_mutex);
    trace_return_ok();
}


int ndo_handle_queue_contact_notification_method(int type, void * d)
{
    trace_func_handler(contact_notification_method);
    nebstruct_contact_notification_method_data * data = NULL;
    nebstructcpy((void *)&data, d, sizeof(*data));

    /* copy data before we add to queue so it's not pointing to data that changes later */
    data->host_name = nebstrdup(data->host_name);
    data->service_description = nebstrdup(data->service_description);
    data->contact_name = nebstrdup(data->contact_name);
    data->command_name = nebstrdup(data->command_name);
    data->command_args = nebstrdup(data->command_args);
    data->output = nebstrdup(data->output);
    data->ack_author = nebstrdup(data->ack_author);
    data->ack_data = nebstrdup(data->ack_data);

    pthread_mutex_lock(&queue_notification_mutex);
    ndo_enqueue(&nebstruct_queue_notification, data, type);
    pthread_mutex_unlock(&queue_notification_mutex);
    trace_return_ok();
}


int ndo_handle_queue_acknowledgement(int type, void * d)
{
    trace_func_handler(acknowledgement);
    nebstruct_acknowledgement_data * data = NULL;
    nebstructcpy((void *)&data, d, sizeof(*data));

    /* copy data before we add to queue so it's not pointing to data that changes later */
    data->host_name = nebstrdup(data->host_name);
    data->service_description = nebstrdup(data->service_description);
    data->author_name = nebstrdup(data->author_name);
    data->comment_data = nebstrdup(data->comment_data);

    pthread_mutex_lock(&queue_acknowledgement_mutex);
    ndo_enqueue(&nebstruct_queue_acknowledgement, data, type);
    pthread_mutex_unlock(&queue_acknowledgement_mutex);
    trace_return_ok();
}


int ndo_handle_queue_statechange(int type, void * d)
{
    trace_func_handler(statechange);
    nebstruct_statechange_data * data = NULL;
    nebstructcpy((void *)&data, d, sizeof(*data));

    /* copy data before we add to queue so it's not pointing to data that changes later */
    data->host_name = nebstrdup(data->host_name);
    data->service_description = nebstrdup(data->service_description);
    data->output = nebstrdup(data->output);
    data->longoutput = nebstrdup(data->longoutput);

    pthread_mutex_lock(&queue_statechange_mutex);
    ndo_enqueue(&nebstruct_queue_statechange, data, type);
    pthread_mutex_unlock(&queue_statechange_mutex);
    trace_return_ok();
}

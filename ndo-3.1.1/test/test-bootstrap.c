
#include <mysql.h>
#include <string.h>

#include "../include/test.h"
#include "../include/ndo.h"

/* This file contains functions which allocate/populate sample structs and
 * return pointers to them, for use in testing the handler functions.
 * It also populates nagios_objects and related tables with similar sample objects.
 */

void populate_commands()
{

    mysql_query(main_thread_context->conn, "INSERT INTO nagios_objects SET "
                                  "instance_id = 1, objecttype_id = 12, name1 = 'check_xi_host_ping', name2 = '', is_active = 1");
    mysql_query(main_thread_context->conn, "INSERT INTO nagios_commands SET "
                                  "instance_id = 1, config_type = 1, "
                                  "object_id = (SELECT object_id from nagios_objects WHERE name1 = 'check_xi_host_ping' AND objecttype_id = 12), "
                                  "command_line = '$USER1$/check_icmp -H $HOSTADDRESS$ -w $ARG1$,$ARG2$ -c $ARG3$,$ARG4$ -p 5'");

    mysql_query(main_thread_context->conn, "INSERT INTO nagios_objects SET "
                                  "instance_id = 1, objecttype_id = 12, name1 = 'check_xi_service_ping', name2 = '', is_active = 1");
    mysql_query(main_thread_context->conn, "INSERT INTO nagios_commands SET "
                                  "instance_id = 1, config_type = 1, "
                                  "object_id = (SELECT object_id from nagios_objects WHERE name1 = 'check_xi_service_ping' AND objecttype_id = 12) "
                                  "command_line = '$USER1$/check_icmp -H $HOSTADDRESS$ -w $ARG1$,$ARG2$ -c $ARG3$,$ARG4$ -p 5'");
}

struct host populate_hosts(timeperiod * tp)
{
    mysql_query(main_thread_context->conn, "INSERT INTO nagios_objects SET "
                                  "instance_id = 1, objecttype_id = 1, name1 = '_testhost_1', name2 = '', is_active = 1");

    struct host the_host = {
        .id = 0,
        .name = strdup("_testhost_1"),
        .display_name = strdup("_testhost_1"),
        .alias = strdup("_testhost_1"),
        .address = strdup("127.0.0.1"),
        .parent_hosts = NULL,
        .child_hosts = NULL,
        .services = NULL, // Originally set to 0x6f95f
        .check_command = strdup("check_xi_host_ping!3000.0!80%!5000.0!100%"),
        .initial_state = 0,
        .check_interval = 5,
        .retry_interval = 1,
        .max_attempts = 5,
        .event_handler = NULL,
        .contact_groups = NULL,
        .contacts = NULL, // Originally set to 0x6de3c
        .notification_interval = 60,
        .first_notification_delay = 0,
        .notification_options = 4294967295,
        .hourly_value = 0,
        .notification_period = strdup("xi_timeperiod_24x7"),
        .check_period = strdup("xi_timeperiod_24x7"),
        .flap_detection_enabled = 1,
        .low_flap_threshold = 0,
        .high_flap_threshold = 0,
        .flap_detection_options = -1,
        .stalking_options = 0,
        .check_freshness = 0,
        .freshness_threshold = 0,
        .process_performance_data = 1,
        .checks_enabled = 1,
        .check_source = "Core Worker 107565",
        .accept_passive_checks = 1,
        .event_handler_enabled = 1,
        .retain_status_information = 1,
        .retain_nonstatus_information = 1,
        .obsess = 1,
        .notes = NULL,
        .notes_url = NULL,
        .action_url = NULL,
        .icon_image = NULL,
        .icon_image_alt = NULL,
        .statusmap_image = NULL,
        .vrml_image = NULL,
        .have_2d_coords = 0,
        .x_2d = -1,
        .y_2d = -1,
        .have_3d_coords = 0,
        .x_3d = 0,
        .y_3d = 0,
        .z_3d = 0,
        .should_be_drawn = 1,
        .custom_variables = NULL,
        .problem_has_been_acknowledged = 0,
        .acknowledgement_type = 0,
        .check_type = 0,
        .current_state = 0,
        .last_state = 0,
        .last_hard_state = 0,
        .plugin_output = strdup("OK - 127.0.0.1 rta 0.012ms lost 0%"),
        .long_plugin_output = NULL,
        .perf_data = strdup("rta=0.012ms;3000.000;5000.000;0; rtmax=0.036ms;;;; rtmin=0.005ms;;;; pl=0%;80;100;0;100"),
        .state_type = 1,
        .current_attempt = 1,
        .current_event_id = 0,
        .last_event_id = 0,
        .current_problem_id = 0,
        .last_problem_id = 0,
        .latency = 0.0019690000917762518,
        .execution_time = 0.0041120000000000002,
        .is_executing = 0,
        .check_options = 0,
        .notifications_enabled = 1,
        .last_notification = 0,
        .next_notification = 0,
        .next_check = 1567627058,
        .should_be_scheduled = 1,
        .last_check = 1567626758,
        .last_state_change = 1565516515,
        .last_hard_state_change = 1565516515,
        .last_time_up = 1567626758,
        .last_time_down = 0,
        .last_time_unreachable = 0,
        .has_been_checked = 1,
        .is_being_freshened = 0,
        .notified_on = 0,
        .current_notification_number = 0,
        .no_more_notifications = 0,
        .current_notification_id = 1106495,
        .check_flapping_recovery_notification = 0,
        .scheduled_downtime_depth = 0,
        .pending_flex_downtime = 0,
        .state_history = {0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0},
        .state_history_index = 20,
        .last_state_history_update = 1567626758,
        .is_flapping = 0,
        .flapping_comment_id = 0,
        .percent_state_change = 0,
        .total_services = 2,
        .total_service_check_interval = 0,
        .modified_attributes = 0,
        .event_handler_ptr = NULL,
        .check_command_ptr = NULL, // Originally set to 0x6f711
        .check_period_ptr = tp, // Originally set to 0x6c352
        .notification_period_ptr = tp, // Originally set to 0x6c352
        .hostgroups_ptr = NULL,
        .exec_deps = NULL,
        .notify_deps = NULL,
        .escalation_list = NULL,
        .next = NULL,
        .next_check_event = NULL, // Originally set to 0x6d8f9
    };

    return the_host;
}

void free_host(struct host the_host)
{

    free(the_host.name);
    free(the_host.display_name);
    free(the_host.alias);
    free(the_host.address);
    free(the_host.check_command);
    free(the_host.notification_period);
    free(the_host.check_period);
    free(the_host.plugin_output);
    free(the_host.perf_data);
}

struct service populate_service(timeperiod * tp, host * hst)
{
    mysql_query(main_thread_context->conn, "INSERT INTO nagios_objects SET "
                                  "instance_id = 1, objecttype_id = 2, name1 = '_testhost_1', name2 = '_testservice_ping', is_active = 1");

    mysql_query(main_thread_context->conn, "INSERT INTO nagios_objects SET "
                                  "instance_id = 1, objecttype_id = 2, name1 = '_testhost_1', name2 = '_testservice_http', is_active = 1");

    struct service the_service = {
        .id = 0,
        .host_name = strdup("_testhost_1"),
        .description = strdup("_testservice_http"),
        .display_name = strdup("_testservice_http"),
        .parents = NULL,
        .children = NULL,
        .check_command = strdup("check_xi_service_http"),
        .event_handler = NULL,
        .initial_state = 0,
        .check_interval = 5,
        .retry_interval = 1,
        .max_attempts = 5,
        .parallelize = 1,
        .contact_groups = NULL,
        .contacts = NULL, // Originally set to 0x6f91f0
        .notification_interval = 60,
        .first_notification_delay = 0,
        .notification_options = 4294967295,
        .stalking_options = 0,
        .hourly_value = 0,
        .is_volatile = 0,
        .notification_period = strdup("xi_timeperiod_24x7"),
        .check_period = strdup("xi_timeperiod_24x7"),
        .flap_detection_enabled = 0,
        .low_flap_threshold = 0,
        .high_flap_threshold = 0,
        .flap_detection_options = 4294967295,
        .process_performance_data = 1,
        .check_freshness = 0,
        .freshness_threshold = 0,
        .accept_passive_checks = 1,
        .event_handler_enabled = 1,
        .checks_enabled = 1,
        .check_source = "Core Worker 107566",
        .retain_status_information = 1,
        .retain_nonstatus_information = 1,
        .notifications_enabled = 1,
        .obsess = 1,
        .notes = NULL,
        .notes_url = NULL,
        .action_url = NULL,
        .icon_image = NULL,
        .icon_image_alt = NULL,
        .custom_variables = NULL,
        .problem_has_been_acknowledged = 0,
        .acknowledgement_type = 0,
        .host_problem_at_last_check = 0,
        .check_type = 0,
        .current_state = 0,
        .last_state = 0,
        .last_hard_state = 0,
        .plugin_output = strdup("This is not real output"),
        .long_plugin_output = NULL,
        .perf_data = NULL,
        .state_type = 1,
        .next_check = 1567709609,
        .should_be_scheduled = 1,
        .last_check = 1567709309,
        .current_attempt = 1,
        .current_event_id = 100176,
        .last_event_id = 100175,
        .current_problem_id = 0,
        .last_problem_id = 22135,
        .last_notification = 0,
        .next_notification = 3600,
        .no_more_notifications = 0,
        .check_flapping_recovery_notification = 0,
        .last_state_change = 1567621885,
        .last_hard_state_change = 1567621885,
        .last_time_ok = 1567630417,
        .last_time_warning = 1567542993,
        .last_time_unknown = 0,
        .last_time_critical = 1567539221,
        .has_been_checked = 1,
        .is_being_freshened = 0,
        .notified_on = 0,
        .current_notification_number = 0,
        .current_notification_id = 1106501,
        .latency = 0.0020620001014322042,
        .execution_time = 0.0032190000000000001,
        .is_executing = 0,
        .check_options = 0,
        .scheduled_downtime_depth = 0,
        .pending_flex_downtime = 0,
        .state_history = {0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0},
        .state_history_index = 20,
        .is_flapping = 0,
        .flapping_comment_id = 0,
        .percent_state_change = 0,
        .modified_attributes = 16,
        .host_ptr = hst, // Originally set to 0x6f8b00
        .event_handler_ptr = NULL,
        .event_handler_args = NULL,
        .check_command_ptr = NULL, // Originally set to 0x6f7950
        .check_command_args = NULL,
        .check_period_ptr = tp, // Originally set to 0x6c3520
        .notification_period_ptr = tp, // Originally set to 0x6c3520
        .servicegroups_ptr = NULL,
        .exec_deps = NULL,
        .notify_deps = NULL,
        .escalation_list = NULL,
        .next = NULL, // Originally set to 0x6f9210
        .next_check_event = NULL, // Originally set to 0x6d9fb0
    };

    return the_service;
}

void free_service(struct service the_service)
{

    free(the_service.host_name);
    free(the_service.description);
    free(the_service.display_name);
    free(the_service.check_command);
    free(the_service.notification_period);
    free(the_service.check_period);
    free(the_service.plugin_output);
}

struct contact populate_contact(timeperiod * tp)
{

    mysql_query(main_thread_context->conn, "INSERT INTO nagios_objects SET "
                                  "instance_id = 1, objecttype_id = 10, name1 = 'nagiosadmin', name2 = '', is_active = 1");

    struct contact the_contact = {
        .id = 0,
        .name = strdup("nagiosadmin"),
        .alias = strdup("Nagios Admin"),
        .email = strdup("nagios@localhost"),
        .pager = NULL,
        .address = {NULL, NULL, NULL, NULL, NULL, NULL},
        .host_notification_commands = NULL, // Originally set to 0x6e07d0
        .service_notification_commands = NULL, // Originally set to 0x6e0bf0
        .host_notification_options = 6151,
        .service_notification_options = 6159,
        .minimum_value = 0,
        .host_notification_period = strdup("24x7"),
        .service_notification_period = strdup("24x7"),
        .host_notifications_enabled = 1,
        .service_notifications_enabled = 1,
        .can_submit_commands = 1,
        .retain_status_information = 1,
        .retain_nonstatus_information = 1,
        .custom_variables = NULL,
        .last_host_notification = 1567525739,
        .last_service_notification = 1567543267,
        .modified_attributes = 0,
        .modified_host_attributes = 0,
        .modified_service_attributes = 0,
        .host_notification_period_ptr = tp,
        .service_notification_period_ptr = tp,
        .contactgroups_ptr = NULL, // Originally set to 0x6f9690
        .next = NULL,
    };

    return the_contact;
}

struct contact *bootstrap_contacts = NULL;
struct contactgroup *bootstrap_contactgroups = NULL;
struct host *bootstrap_hosts = NULL;
struct hostgroup *bootstrap_hostgroups = NULL;
struct service *bootstrap_services = NULL;
struct servicegroup *bootstrap_servicegroups = NULL;
struct hostescalation *bootstrap_hostescalations = NULL;
struct serviceescalation *bootstrap_serviceescalations = NULL;

struct hostescalation **bootstrap_hostescalations_ary = NULL;
struct serviceescalation ** bootstrap_serviceescalations_ary = NULL;

void bootstrap_write_initialize()
{
    if (bootstrap_contacts == NULL) {

        num_objects.contacts = 251;
        #define NUM_CONTACTS 251

        int i;
        
        bootstrap_contacts = calloc(num_objects.contacts, sizeof(contact));
        contact *contacts = bootstrap_contacts; 
        //contact contacts[251] = {};
        //char **names = calloc(251, sizeof(char *));
        char *names[NUM_CONTACTS] = {};
        //char **aliases = calloc(251, sizeof(char *));
        char *aliases[NUM_CONTACTS] = {};
        #undef NUM_CONTACTS
        for (i = 0; i < num_objects.contacts; i += 1) {
            asprintf(&(names[i]), "Contact %d", i);
            asprintf(&(aliases[i]), "c%d", i);
        }

      
        contacts[0].id = 0;
        contacts[0].name = names[0];
        contacts[0].alias = aliases[0];
        contacts[0].email = "root@localhost";
        contacts[0].pager = NULL;
        contacts[0].address[0] = "Home";
        contacts[0].host_notification_commands = NULL;
        contacts[0].service_notification_commands = NULL;
        contacts[0].host_notification_options = 0;
        contacts[0].service_notification_options = 0;
        contacts[0].minimum_value = 0;
        contacts[0].host_notification_period = NULL;
        contacts[0].service_notification_period = NULL;
        contacts[0].host_notifications_enabled = 0;
        contacts[0].service_notifications_enabled = 0;
        contacts[0].can_submit_commands = 1;
        contacts[0].retain_status_information = 1;
        contacts[0].retain_nonstatus_information = 1;
        contacts[0].custom_variables = NULL;
        contacts[0].last_host_notification = 0;
        contacts[0].last_service_notification = 0;
        contacts[0].modified_attributes = 0;
        contacts[0].modified_host_attributes = 0;
        contacts[0].modified_service_attributes = 0;
        contacts[0].host_notification_period_ptr = NULL;
        contacts[0].service_notification_period_ptr = NULL;
        contacts[0].contactgroups_ptr = NULL;
        contacts[0].next = &(contacts[1]);

        for (i = 1; i < num_objects.contacts; i += 1) {
            memcpy(&(contacts[i]), &(contacts[0]), sizeof(contact));
            contacts[i].id = i;
            contacts[i].name = names[i];
            contacts[i].alias = aliases[i];
            contacts[i].next = &(contacts[i+1]);
            if (i == (num_objects.contacts - 1)) {
                contacts[i].next = NULL;
            }
        }

    }

    if (bootstrap_contactgroups == NULL) {
        int i;
        num_objects.contactgroups = 252;
        #define NUM_CONTACTGROUPS 252
        bootstrap_contactgroups = calloc(num_objects.contactgroups, sizeof(contactgroup));

        char *names[NUM_CONTACTGROUPS] = {};
        //char **aliases = calloc(251, sizeof(char *));
        char *aliases[NUM_CONTACTGROUPS] = {};

        #undef NUM_CONTACTGROUPS

        contactsmember *contactsmembers = calloc(num_objects.contactgroups + 1, sizeof(contactsmember));
        for (i = 0; i < num_objects.contactgroups; i += 1) {
            asprintf(&(names[i]), "Contact Group %d", i);
            asprintf(&(aliases[i]), "cg%d", i);

            if (i == num_objects.contactgroups - 1) {
                contactsmembers[i].contact_ptr =&(bootstrap_contacts[1]);
                contactsmembers[i].contact_name = bootstrap_contacts[1].name;
            }
            else {
                contactsmembers[i].contact_ptr = &(bootstrap_contacts[i]);
                contactsmembers[i].contact_name = bootstrap_contacts[i].name;
            }
            contactsmembers[i].next = NULL;
        }
        // Last member is special, contains 2nd contact points to 1st member struct.
        contactsmembers[num_objects.contactgroups].contact_ptr = &(bootstrap_contacts[1]);
        contactsmembers[num_objects.contactgroups].contact_name = bootstrap_contacts[1].name;
        contactsmembers[0].next = &(contactsmembers[num_objects.contactgroups]);

        for (i = 0; i < num_objects.contactgroups; i++) {
            bootstrap_contactgroups[i].id = i;
            bootstrap_contactgroups[i].group_name = names[i];
            bootstrap_contactgroups[i].alias = aliases[i];
            bootstrap_contactgroups[i].members = &(contactsmembers[i]);
            bootstrap_contactgroups[i].next = &(bootstrap_contactgroups[i+1]);
            if (i == (num_objects.contactgroups - 1)) {
                bootstrap_contactgroups[i].next = NULL;
            }
        }
    }

    if (bootstrap_hosts == NULL) {

        num_objects.hosts = 254;
        #define NUM_HOSTS 254

        int i;
        
        bootstrap_hosts = calloc(num_objects.hosts, sizeof(host));
        host *hosts = bootstrap_hosts; 
        //contact contacts[251] = {};
        //char **names = calloc(251, sizeof(char *));
        char *names[NUM_HOSTS] = {};
        //char **aliases = calloc(251, sizeof(char *));
        char *aliases[NUM_HOSTS] = {};
        char *display_names[NUM_HOSTS] = {};
        #undef NUM_HOSTS
        for (i = 0; i < num_objects.hosts; i += 1) {
            asprintf(&(names[i]), "Host %d", i);
            asprintf(&(display_names[i]), "Host %d", i);
            asprintf(&(aliases[i]), "h%d", i);
        }

        // This is similar to "the_host" initialized much earlier, 
        // except no strdups + notification_period/check_period are set to NULL.
        hosts[0].id = 0;
        hosts[0].name = names[0];
        hosts[0].display_name = display_names[0];
        hosts[0].alias = aliases[0];
        hosts[0].address = "127.0.0.1";
        hosts[0].parent_hosts = NULL;
        hosts[0].child_hosts = NULL;
        hosts[0].services = NULL, // Originally set to 0x6f95;
        hosts[0].check_command = "check_xi_host_ping!3000.0!80%!5000.0!100%";
        hosts[0].initial_state = 0;
        hosts[0].check_interval = 5;
        hosts[0].retry_interval = 1;
        hosts[0].max_attempts = 5;
        hosts[0].event_handler = NULL;
        hosts[0].contact_groups = NULL;
        hosts[0].contacts = NULL, // Originally set to 0x6de3;
        hosts[0].notification_interval = 60;
        hosts[0].first_notification_delay = 0;
        hosts[0].notification_options = 4294967295;
        hosts[0].hourly_value = 0;
        hosts[0].notification_period = NULL; // was originally strdup("xi_timeperiod_24x7");
        hosts[0].check_period = NULL; // was originally strdup("xi_timeperiod_24x7");
        hosts[0].flap_detection_enabled = 1;
        hosts[0].low_flap_threshold = 0;
        hosts[0].high_flap_threshold = 0;
        hosts[0].flap_detection_options = -1;
        hosts[0].stalking_options = 0;
        hosts[0].check_freshness = 0;
        hosts[0].freshness_threshold = 0;
        hosts[0].process_performance_data = 1;
        hosts[0].checks_enabled = 1;
        hosts[0].check_source = "Core Worker 107565";
        hosts[0].accept_passive_checks = 1;
        hosts[0].event_handler_enabled = 1;
        hosts[0].retain_status_information = 1;
        hosts[0].retain_nonstatus_information = 1;
        hosts[0].obsess = 1;
        hosts[0].notes = NULL;
        hosts[0].notes_url = NULL;
        hosts[0].action_url = NULL;
        hosts[0].icon_image = NULL;
        hosts[0].icon_image_alt = NULL;
        hosts[0].statusmap_image = NULL;
        hosts[0].vrml_image = NULL;
        hosts[0].have_2d_coords = 0;
        hosts[0].x_2d = -1;
        hosts[0].y_2d = -1;
        hosts[0].have_3d_coords = 0;
        hosts[0].x_3d = 0;
        hosts[0].y_3d = 0;
        hosts[0].z_3d = 0;
        hosts[0].should_be_drawn = 1;
        hosts[0].custom_variables = NULL;
        hosts[0].problem_has_been_acknowledged = 0;
        hosts[0].acknowledgement_type = 0;
        hosts[0].check_type = 0;
        hosts[0].current_state = 0;
        hosts[0].last_state = 0;
        hosts[0].last_hard_state = 0;
        hosts[0].plugin_output = "OK - 127.0.0.1 rta 0.012ms lost 0%";
        hosts[0].long_plugin_output = NULL;
        hosts[0].perf_data = "rta=0.012ms;3000.000;5000.000;0; rtmax=0.036ms;;;; rtmin=0.005ms;;;; pl=0%;80;100;0;100";
        hosts[0].state_type = 1;
        hosts[0].current_attempt = 1;
        hosts[0].current_event_id = 0;
        hosts[0].last_event_id = 0;
        hosts[0].current_problem_id = 0;
        hosts[0].last_problem_id = 0;
        hosts[0].latency = 0.0019690000917762518;
        hosts[0].execution_time = 0.0041120000000000002;
        hosts[0].is_executing = 0;
        hosts[0].check_options = 0;
        hosts[0].notifications_enabled = 1;
        hosts[0].last_notification = 0;
        hosts[0].next_notification = 0;
        hosts[0].next_check = 1567627058;
        hosts[0].should_be_scheduled = 1;
        hosts[0].last_check = 1567626758;
        hosts[0].last_state_change = 1565516515;
        hosts[0].last_hard_state_change = 1565516515;
        hosts[0].last_time_up = 1567626758;
        hosts[0].last_time_down = 0;
        hosts[0].last_time_unreachable = 0;
        hosts[0].has_been_checked = 1;
        hosts[0].is_being_freshened = 0;
        hosts[0].notified_on = 0;
        hosts[0].current_notification_number = 0;
        hosts[0].no_more_notifications = 0;
        hosts[0].current_notification_id = 1106495;
        hosts[0].check_flapping_recovery_notification = 0;
        hosts[0].scheduled_downtime_depth = 0;
        hosts[0].pending_flex_downtime = 0;
        // Following memory was already set to 0, syntax here is incorrect and the only way i know to fix it is annoying.
        //hosts[0].state_history = {0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0};
        hosts[0].state_history_index = 20;
        hosts[0].last_state_history_update = 1567626758;
        hosts[0].is_flapping = 0;
        hosts[0].flapping_comment_id = 0;
        hosts[0].percent_state_change = 0;
        hosts[0].total_services = 2;
        hosts[0].total_service_check_interval = 0;
        hosts[0].modified_attributes = 0;
        hosts[0].event_handler_ptr = NULL;
        hosts[0].check_command_ptr = NULL; // Originally set to 0x6f711
        hosts[0].check_period_ptr = NULL; //tp; // Originally set to 0x6c352
        hosts[0].notification_period_ptr = NULL; //tp; // Originally set to 0x6c352
        hosts[0].hostgroups_ptr = NULL;
        hosts[0].exec_deps = NULL;
        hosts[0].notify_deps = NULL;
        hosts[0].escalation_list = NULL;
        hosts[0].next = &(hosts[1]);
        hosts[0].next_check_event = NULL; // Originally set to 0x6d8f9


        for (i = 1; i < num_objects.hosts; i += 1) {
            memcpy(&(hosts[i]), &(hosts[0]), sizeof(host));
            hosts[i].id = i;
            hosts[i].name = names[i];
            hosts[i].display_name = display_names[i];
            hosts[i].alias = aliases[i];
            hosts[i].next = &(hosts[i+1]);
            if (i == (num_objects.hosts - 1)) {
                hosts[i].next = NULL;
            }
        }
    }

    if (bootstrap_hostgroups == NULL) {
        int i;
        num_objects.hostgroups = 253;
        #define NUM_HOSTGROUPS 253
        bootstrap_hostgroups = calloc(num_objects.hostgroups, sizeof(hostgroup));

        char *group_names[NUM_HOSTGROUPS] = {};
        //char **aliases = calloc(251, sizeof(char *));
        char *aliases[NUM_HOSTGROUPS] = {};

        #undef NUM_HOSTGROUPS

        //aiya

        hostsmember *hostsmembers = calloc(num_objects.hostgroups + 1, sizeof(hostsmember));


        for (i = 0; i < num_objects.hostgroups; i += 1) {

            asprintf(&(group_names[i]), "Host Group %d", i);
            asprintf(&(aliases[i]), "hg%d", i);

            if (i == num_objects.hostgroups - 1) {
                hostsmembers[i].host_ptr =&(bootstrap_hosts[1]);
                hostsmembers[i].host_name = bootstrap_hosts[1].name;
            }
            else {
                hostsmembers[i].host_ptr = &(bootstrap_hosts[i]);
                hostsmembers[i].host_name = bootstrap_hosts[i].name;
            }
            hostsmembers[i].next = NULL;
        }
        // Last member is special, contains 2nd contact points to 1st member struct.
        hostsmembers[num_objects.hostgroups].host_ptr = &(bootstrap_hosts[1]);
        hostsmembers[num_objects.hostgroups].host_name = bootstrap_hosts[1].name;
        hostsmembers[0].next = &(hostsmembers[num_objects.hostgroups]);

        bootstrap_hostgroups[0].id = 0;
        bootstrap_hostgroups[0].group_name = group_names[0];
        bootstrap_hostgroups[0].alias = aliases[0];
        bootstrap_hostgroups[0].members = &(hostsmembers[0]);
        bootstrap_hostgroups[0].notes = "";
        bootstrap_hostgroups[0].notes_url = "";
        bootstrap_hostgroups[0].action_url = "";
        bootstrap_hostgroups[0].next = &(bootstrap_hostgroups[1]);

        for (i = 1; i < num_objects.hostgroups; i++) {
            memcpy(&(bootstrap_hostgroups[i]), &(bootstrap_hostgroups[0]), sizeof(hostgroup));
            bootstrap_hostgroups[i].id = i;
            bootstrap_hostgroups[i].group_name = group_names[i];
            bootstrap_hostgroups[i].alias = aliases[i];
            bootstrap_hostgroups[i].members = &(hostsmembers[i]);
            bootstrap_hostgroups[i].next = &(bootstrap_hostgroups[i+1]);
            if (i == (num_objects.hostgroups - 1)) {
                bootstrap_hostgroups[i].next = NULL;
            }
        }
    }

    if (bootstrap_services == NULL) {

        num_objects.services = 252;
        #define NUM_SERVICES 252

        int i;
        
        bootstrap_services = calloc(num_objects.services, sizeof(service));
        service *services = bootstrap_services; 

        char *descriptions[NUM_SERVICES] = {};
        char *display_names[NUM_SERVICES] = {};
        #undef NUM_SERVICES
        for (i = 0; i < num_objects.services; i += 1) {
            asprintf(&(descriptions[i]), "Service %d", i);
            asprintf(&(display_names[i]), "s%d", i);
        }

        services[0].id = 0;
        services[0].host_name = bootstrap_hosts[0].name;
        services[0].description = descriptions[0];
        services[0].display_name = display_names[0];
        services[0].parents = NULL;
        services[0].children = NULL;
        services[0].check_command = "check_xi_service_http";
        services[0].event_handler = NULL;
        services[0].initial_state = 0;
        services[0].check_interval = 5;
        services[0].retry_interval = 1;
        services[0].max_attempts = 5;
        services[0].parallelize = 1;
        services[0].contact_groups = NULL;
        services[0].contacts = NULL; // Originally set to 0x6f91f0
        services[0].notification_interval = 60;
        services[0].first_notification_delay = 0;
        services[0].notification_options = 4294967295;
        services[0].stalking_options = 0;
        services[0].hourly_value = 0;
        services[0].is_volatile = 0;
        services[0].notification_period = "xi_timeperiod_24x7";
        services[0].check_period = "xi_timeperiod_24x7";
        services[0].flap_detection_enabled = 0;
        services[0].low_flap_threshold = 0;
        services[0].high_flap_threshold = 0;
        services[0].flap_detection_options = 4294967295;
        services[0].process_performance_data = 1;
        services[0].check_freshness = 0;
        services[0].freshness_threshold = 0;
        services[0].accept_passive_checks = 1;
        services[0].event_handler_enabled = 1;
        services[0].checks_enabled = 1;
        services[0].check_source = "Core Worker 107566";
        services[0].retain_status_information = 1;
        services[0].retain_nonstatus_information = 1;
        services[0].notifications_enabled = 1;
        services[0].obsess = 1;
        services[0].notes = NULL;
        services[0].notes_url = NULL;
        services[0].action_url = NULL;
        services[0].icon_image = NULL;
        services[0].icon_image_alt = NULL;
        services[0].custom_variables = NULL;
        services[0].problem_has_been_acknowledged = 0;
        services[0].acknowledgement_type = 0;
        services[0].host_problem_at_last_check = 0;
        services[0].check_type = 0;
        services[0].current_state = 0;
        services[0].last_state = 0;
        services[0].last_hard_state = 0;
        services[0].plugin_output = "This is not real output";
        services[0].long_plugin_output = NULL;
        services[0].perf_data = NULL;
        services[0].state_type = 1;
        services[0].next_check = 1567709609;
        services[0].should_be_scheduled = 1;
        services[0].last_check = 1567709309;
        services[0].current_attempt = 1;
        services[0].current_event_id = 100176;
        services[0].last_event_id = 100175;
        services[0].current_problem_id = 0;
        services[0].last_problem_id = 22135;
        services[0].last_notification = 0;
        services[0].next_notification = 3600;
        services[0].no_more_notifications = 0;
        services[0].check_flapping_recovery_notification = 0;
        services[0].last_state_change = 1567621885;
        services[0].last_hard_state_change = 1567621885;
        services[0].last_time_ok = 1567630417;
        services[0].last_time_warning = 1567542993;
        services[0].last_time_unknown = 0;
        services[0].last_time_critical = 1567539221;
        services[0].has_been_checked = 1;
        services[0].is_being_freshened = 0;
        services[0].notified_on = 0;
        services[0].current_notification_number = 0;
        services[0].current_notification_id = 1106501;
        services[0].latency = 0.0020620001014322042;
        services[0].execution_time = 0.0032190000000000001;
        services[0].is_executing = 0;
        services[0].check_options = 0;
        services[0].scheduled_downtime_depth = 0;
        services[0].pending_flex_downtime = 0;
        // Following memory was already set to 0, syntax here is incorrect and the only way i know to fix it is annoying.
        // services[0].state_history = {0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0};
        services[0].state_history_index = 20;
        services[0].is_flapping = 0;
        services[0].flapping_comment_id = 0;
        services[0].percent_state_change = 0;
        services[0].modified_attributes = 16;
        services[0].host_ptr = NULL; //hst; // Originally set to 0x6f8b00
        services[0].event_handler_ptr = NULL;
        services[0].event_handler_args = NULL;
        services[0].check_command_ptr = NULL; // Originally set to 0x6f7950
        services[0].check_command_args = NULL;
        services[0].check_period_ptr = NULL; //tp; // Originally set to 0x6c3520
        services[0].notification_period_ptr = NULL; //tp; // Originally set to 0x6c3520
        services[0].servicegroups_ptr = NULL;
        services[0].exec_deps = NULL;
        services[0].notify_deps = NULL;
        services[0].escalation_list = NULL;
        services[0].next = &(services[1]); // Originally set to 0x6f9210
        services[0].next_check_event = NULL; // Originally set to 0x6d9fb0


        for (i = 1; i < num_objects.services; i += 1) {
            memcpy(&(services[i]), &(services[0]), sizeof(service));
            services[i].id = i;
            services[i].host_name = bootstrap_hosts[i].name;
            services[i].description = descriptions[i];
            services[i].display_name = display_names[i];
            services[i].next = &(services[i+1]);
            if (i == (num_objects.services - 1)) {
                services[i].next = NULL;
            }
        }
    }


    if (bootstrap_servicegroups == NULL) {
        int i;
        num_objects.servicegroups = 251;
        #define NUM_SERVICEGROUPS 251
        bootstrap_servicegroups = calloc(num_objects.servicegroups, sizeof(servicegroup));

        char *group_names[NUM_SERVICEGROUPS] = {};
        //char **aliases = calloc(251, sizeof(char *));
        char *aliases[NUM_SERVICEGROUPS] = {};

        #undef NUM_SERVICEGROUPS

        servicesmember *servicesmembers = calloc(num_objects.servicegroups + 1, sizeof(servicesmember));

        for (i = 0; i < num_objects.servicegroups; i += 1) {

            asprintf(&(group_names[i]), "Service Group %d", i);
            asprintf(&(aliases[i]), "sg%d", i);

            if (i == num_objects.servicegroups - 1) {
                servicesmembers[i].service_ptr =&(bootstrap_services[1]);
                servicesmembers[i].host_name = bootstrap_hosts[1].name;
                servicesmembers[i].service_description = bootstrap_services[1].description;
            }
            else {
                servicesmembers[i].service_ptr = &(bootstrap_services[i]);
                servicesmembers[i].host_name = bootstrap_hosts[i].name;
                servicesmembers[i].service_description = bootstrap_services[i].description;
            }
            servicesmembers[i].next = NULL;
        }
        // Last member is special, contains 2nd contact points to 1st member struct.
        servicesmembers[num_objects.servicegroups].service_ptr = &(bootstrap_services[1]);
        servicesmembers[num_objects.servicegroups].host_name = bootstrap_hosts[1].name;
        servicesmembers[0].next = &(servicesmembers[num_objects.servicegroups]);

        bootstrap_servicegroups[0].id = 0;
        bootstrap_servicegroups[0].group_name = group_names[0];
        bootstrap_servicegroups[0].alias = aliases[0];
        bootstrap_servicegroups[0].members = &(servicesmembers[0]);
        bootstrap_servicegroups[0].notes = "";
        bootstrap_servicegroups[0].notes_url = "";
        bootstrap_servicegroups[0].action_url = "";
        bootstrap_servicegroups[0].next = &(bootstrap_servicegroups[1]);

        for (i = 1; i < num_objects.servicegroups; i++) {
            memcpy(&(bootstrap_servicegroups[i]), &(bootstrap_servicegroups[0]), sizeof(servicegroup));
            bootstrap_servicegroups[i].id = i;
            bootstrap_servicegroups[i].group_name = group_names[i];
            bootstrap_servicegroups[i].alias = aliases[i];
            bootstrap_servicegroups[i].members = &(servicesmembers[i]);
            bootstrap_servicegroups[i].next = &(bootstrap_servicegroups[i+1]);
            if (i == (num_objects.servicegroups - 1)) {
                bootstrap_servicegroups[i].next = NULL;
            }
        }
    }

    if (bootstrap_hostescalations == NULL) {

        num_objects.hostescalations = 135;

        int i;
        
        bootstrap_hostescalations = calloc(num_objects.hostescalations, sizeof(hostescalation));
        bootstrap_hostescalations_ary = calloc(num_objects.hostescalations, sizeof(hostescalation*));
        hostescalation *hostescalations = bootstrap_hostescalations; 

        hostescalations[0].id = 0;
        hostescalations[0].host_name = "Host 0";
        hostescalations[0].first_notification = 1;
        hostescalations[0].last_notification = 1;
        hostescalations[0].notification_interval = 90;
        hostescalations[0].escalation_period = "xi_timeperiod_24x7";
        hostescalations[0].escalation_options = -1;
        hostescalations[0].contact_groups = NULL;
        hostescalations[0].contacts = NULL; 
        hostescalations[0].host_ptr = NULL;
        hostescalations[0].escalation_period_ptr = NULL; // originally set to 0xa558b0; 

        bootstrap_hostescalations_ary[0] = &(hostescalations[0]);

        for (i = 1; i < num_objects.hostescalations; i += 1) {
            memcpy(&(hostescalations[i]), &(hostescalations[0]), sizeof(hostescalation));
            hostescalations[i].id = i;
            hostescalations[i].first_notification = i+1;
            hostescalations[i].last_notification = i+1;

            bootstrap_hostescalations_ary[i] = &(hostescalations[i]);
        }
    }

    if (bootstrap_serviceescalations == NULL) {

        num_objects.serviceescalations = 140;

        int i;
        
        bootstrap_serviceescalations = calloc(num_objects.serviceescalations, sizeof(serviceescalation));
        serviceescalation *serviceescalations = bootstrap_serviceescalations;

        bootstrap_serviceescalations_ary = calloc(num_objects.serviceescalations, sizeof(serviceescalation *));

        serviceescalations[0].id = 0;
        serviceescalations[0].host_name = "Host 0";
        serviceescalations[0].description = "Service 0";
        serviceescalations[0].first_notification = 1;
        serviceescalations[0].last_notification = 1;
        serviceescalations[0].notification_interval = 90;
        serviceescalations[0].escalation_period = "xi_timeperiod_24x7";
        serviceescalations[0].escalation_options = -1;
        serviceescalations[0].contact_groups = NULL;
        serviceescalations[0].contacts = NULL; 
        serviceescalations[0].service_ptr = NULL;
        serviceescalations[0].escalation_period_ptr = NULL; // originally set to 0xa558b0; 

        bootstrap_serviceescalations_ary[0] = &(serviceescalations[0]);

        for (i = 1; i < num_objects.serviceescalations; i += 1) {
            memcpy(&(serviceescalations[i]), &(serviceescalations[0]), sizeof(serviceescalation));
            serviceescalations[i].id = i;
            serviceescalations[i].first_notification = i+1;
            serviceescalations[i].last_notification = i+1;

            bootstrap_serviceescalations_ary[i] = &(serviceescalations[i]);
        }
    }
}

void bootstrap_write_teardown() {
    if (bootstrap_contacts != NULL) {
        int i = 0;
        for (i = 0; i < 251; i++) {
            free(bootstrap_contacts[i].name);
            free(bootstrap_contacts[i].alias);
        }
        free(bootstrap_contacts);
        bootstrap_contacts = NULL;
    }

    if (bootstrap_contactgroups != NULL) {
        // contactsmembers allocated as a block
        free(bootstrap_contactgroups[0].members);
        int i = 0;
        for (i = 0; i < 252; i++) {
            free(bootstrap_contactgroups[i].group_name);
            free(bootstrap_contactgroups[i].alias);
        }
        free(bootstrap_contactgroups);
        bootstrap_contactgroups = NULL;
    }

    if (bootstrap_hosts != NULL) {
        int i = 0;
        for (i = 0; i < 254; i++) {
            free(bootstrap_hosts[i].name);
            free(bootstrap_hosts[i].display_name);
            free(bootstrap_hosts[i].alias);
        }
        free(bootstrap_hosts);
        bootstrap_hosts = NULL;
    }

    if (bootstrap_hostgroups != NULL) {
        int i = 0;
        for (i = 0; i < 253; i++) {
            free(bootstrap_hostgroups[i].group_name);
            free(bootstrap_hostgroups[i].alias);
        }
        free(bootstrap_hostgroups);
        bootstrap_hostgroups = NULL;
    }

    if (bootstrap_services != NULL) {
        int i = 0;
        for (i = 0; i < 252; i++) {
            free(bootstrap_services[i].description);
            free(bootstrap_services[i].display_name);
        }
        free(bootstrap_services);
        bootstrap_services = NULL;
    }

    if (bootstrap_servicegroups != NULL) {
        int i = 0;
        for (i = 0; i < 251; i++) {
            free(bootstrap_servicegroups[i].group_name);
            free(bootstrap_servicegroups[i].alias);
        }
        free(bootstrap_servicegroups);
        bootstrap_servicegroups = NULL;
    }
    if (bootstrap_hostescalations != NULL) {
        free(bootstrap_hostescalations);
        free(bootstrap_hostescalations_ary);
    }
    if (bootstrap_serviceescalations != NULL) {
        free(bootstrap_serviceescalations);
        free(bootstrap_serviceescalations_ary);
    }
}

struct contact * bootstrap_get_contacts() {
    bootstrap_write_initialize();
    return bootstrap_contacts;
}

struct contactgroup * bootstrap_get_contactgroups() {
    bootstrap_write_initialize();
    return bootstrap_contactgroups;
}

struct host * bootstrap_get_hosts() {
    bootstrap_write_initialize();
    return bootstrap_hosts;
}

struct hostgroup * bootstrap_get_hostgroups() {
    bootstrap_write_initialize();
    return bootstrap_hostgroups;
}

struct service * bootstrap_get_services() {
    bootstrap_write_initialize();
    return bootstrap_services;
}

struct servicegroup * bootstrap_get_servicegroups() {
    bootstrap_write_initialize();
    return bootstrap_servicegroups;
}

struct hostescalation * bootstrap_get_hostescalations() {
    bootstrap_write_initialize();
    return bootstrap_hostescalations;
}

struct hostescalation ** bootstrap_get_hostescalations_ary() {
    bootstrap_write_initialize();
    return bootstrap_hostescalations_ary;
}

struct serviceescalation * bootstrap_get_serviceescalations() {
    bootstrap_write_initialize();
    return bootstrap_serviceescalations;
}

struct serviceescalation ** bootstrap_get_serviceescalations_ary() {
    bootstrap_write_initialize();
    return bootstrap_serviceescalations_ary;
}

void free_contact(struct contact the_contact)
{

    free(the_contact.name);
    free(the_contact.alias);
    free(the_contact.email);
    free(the_contact.host_notification_period);
    free(the_contact.service_notification_period);
}

struct timeperiod populate_timeperiods()
{

    mysql_query(main_thread_context->conn, "INSERT INTO nagios_objects SET "
                                  "instance_id = 1, objecttype_id = 9, name1 = 'xi_timeperiod_24x7', is_active = 1");
    struct timerange * tr = calloc(1, sizeof(struct timerange));

    tr->range_start = 0;
    tr->range_end = 86400;
    tr->next = NULL;

    struct timeperiod the_timeperiod = {
        .id = 6,
        .name = strdup("xi_timeperiod_24x7"),
        .alias = strdup("24 Hours A Day, 7 Days A Week"),
        .days = {tr, tr, tr, tr, tr, tr, tr},
        .exceptions = {NULL, NULL, NULL, NULL, NULL},
        .exclusions = NULL,
        .next = NULL,
    };

    return the_timeperiod;
}

void free_timeperiods(struct timeperiod the_timeperiod)
{

    free(the_timeperiod.name);
    free(the_timeperiod.alias);
    free(the_timeperiod.days[0]);
}

void populate_all_objects()
{
    populate_commands();

    test_tp = populate_timeperiods();

    test_host = populate_hosts(&test_tp);
    test_service = populate_service(&test_tp, &test_host);
    test_contact = populate_contact(&test_tp);
}

void free_all_objects()
{
    free_timeperiods(test_tp);
    free_contact(test_contact);
    free_service(test_service);
    free_host(test_host);
}
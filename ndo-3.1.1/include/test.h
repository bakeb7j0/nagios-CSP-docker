
#ifndef NAGIOS_TEST_H_INCLUDED
#define NAGIOS_TEST_H_INCLUDED

#include "nagios/objects.h"

struct timeperiod test_tp;
struct host test_host;
struct service test_service;
struct contact test_contact;

void populate_all_objects();
void free_all_objects();

struct contact * bootstrap_get_contacts();
struct contactgroup * bootstrap_get_contactgroups();
struct host * bootstrap_get_hosts();
struct hostgroup * bootstrap_get_hostgroups();
struct service * bootstrap_get_services();
struct servicegroup * bootstrap_get_servicegroups();
struct hostescalation *bootstrap_get_hostescalations();
struct serviceescalation *bootstrap_get_serviceescalations();

struct hostescalation ** bootstrap_get_hostescalations_ary();
struct serviceescalation ** bootstrap_get_serviceescalations_ary();

#endif
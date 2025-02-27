// History Tab Component
// Copyright (c) 2015-2024 Troy Lea aka Box293
// Copyright (c) 2024-present Nagios Enterprises, LLC. All rights reserved.

// TODO: What is this for??
// If this is for disabling this tab, what else is required and where does this appear?
function historytab_toggleMe(a) {
    $('#'+ a).slideToggle();
}

function historytab_load_data() {
    // Define the div we are going to load
    var div_id = '.historytab_data';
    
    // This stopped graphs from displaying on certain version of XI?
    // Load the table via an ajax request
    //$.ajaxSetup({
    //    async: false,
    //    cache: false,
    //    });

    // NOTE: Because we are doing .load(), instead of an ajax call,
    //       it affects where and how some of the other javascript works.
    //       This makes it necessary to "overload" some functions in
    //       js/reports.js for this to work.
    $(div_id).load('/nagiosxi/includes/components/historytab/historytab_content.php', {
            'object_id':    object_id,
            'args':         args,
        },

        function() {
            $(div_id).show();
        }
    );
}

/* Overrides the load_page() from js/reports.js */
function load_page(init) {
    args.page = current_page;
    args.mode = (typeof init === "undefined") ? 'getpage' : 'init';

    if (typeof args.page === "undefined") {
        args.records = record_limit;
    }

    // Get the data first, then adjust the paging.
    historytab_load_data();
}

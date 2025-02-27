// Service stack
var stack = {};
var throbber = '<div class="childcontentthrobber"><div class="sk-spinner sk-spinner-pulse"></div></div>';

// Set type sets up the graph view!
function setType(graphtype) {
    type = graphtype;
    window.location.hash = type;

    if (type == 'servicepie' || type == 'hostpie') {
        type = 'pie';
    }

    $("#visContainer" + rand).html(throbber);

    if (type == 'timeline' || type == 'stack' || type == 'multistack') {
        $('#manageGraphContainer').hide();
        $('#selectService').trigger('change');

        if (type == 'multistack') {
            $('#grouping').show();
            update_service_stack();
        } else if (type == 'stack') {
            $('#filterButton').attr('disabled', false);
        } else {
            $('#grouping').hide();
            $('#filterButton').attr('disabled', false);
        }
    } else {
        // Make sure the controls drawer is "closed", so these pages resize correctly
        $('#manageGraphContainer').hide();
        $('.neptune-drawer-options').removeClass("drawer-options-visible");
        readjustPage();
    }
}


function fetch_bar() {
    var url = 'visApi.php?type=' + type + '&div=visContainer' + rand + '&opt=topalerts';
    //alert(url); 

    $('#hiddenUrl').val(url);
    $("#visContainer" + rand).load(url);
}


function fetch_pie(arg) {
    var url = 'visApi.php?type=' + type + '&div=visContainer' + rand + '&opt=' + arg;
    //alert(url);   

    $('#hiddenUrl').val(url);
    //alert($('#hiddenUrl').val());
    $("#visContainer" + rand).load(url);
}


// Fetch timeline
function fetch_timeline(inhost, inservice, f) {
    if (f) {
        filtering = 'true';
    } else {
        filtering = false; //clear data type upon fresh graph loading 
    }

    if (type == '') {
        alert('No Graph Type Specified');
        return false;
    }

    host = inhost;
    service = inservice;

    if (type == 'timeline') {
        if (filtering == false) {
            var url = 'visApi.php?type=' + type + '&host=' + host + '&service=' + service + '&div=visContainer' + rand;
        } else {
            var url = 'visApi.php?type=' + type + '&host=' + host + '&service=' + service + '&start=' + start + '&end=' + end + '&div=visContainer' + rand + '&filter=' + filter;
        }
    }
    if (type == 'stack') {
        if (filtering == false) {
            var url = 'visApi.php?type=' + type + '&host=' + host + '&service=' + service + '&div=visContainer' + rand + '&opt=' + opt;
        } else {
            var url = 'visApi.php?type=' + type + '&host=' + host + '&service=' + service + '&start=' + start + '&end=' + end + '&div=visContainer' + rand + '&opt=' + opt + '&filter=' + filter;
        }
    }

    if (!url) {
        return false;
    }
    //alert(url); 

    $('#hiddenUrl').val(url);
    $("#visContainer" + rand).load(url);

    return false;
}


// Fetch multistack timeline
function fetch_multistack_timeline() {
    var stack_str = '';

    // Get the line type (only for multistacked)
    var linetype = $('#linetype').val();
    //console.log(linetype);

    // Create string from stack
    var idx = 0;

    $.each(stack, function (key, datatype_ref) {
        host = datatype_ref.host.replace(/ /g, "_");
        service = datatype_ref.service.replace(/ /g, "_");
        stack_str += '&hslist['+idx+'][host]='+host+'&hslist['+idx+'][service]='+service+'&hslist['+idx+'][datatype]='+datatype_ref.datatype;
        idx++;
    });

    // Send string to visAPI.php to create javascript
    var url = 'visApi.php?type=' + type + stack_str + '&start=' + start + '&end=' + end + '&div=visContainer' + rand + '&linetype=' + linetype;

    $('#hiddenUrl').val(url);
    $("#visContainer" + rand).load(url);
    $('#graphText').hide();
    $('#graphDisplay').show();

    return false;
}


/*
 * Handle the user resizing the browser (so they do not have to reload the page).
 */

// timeOutFunctionId stores a numeric ID which is used by clearTimeOut to reset timer .
var timeOutFunctionId; 
  
// This function is executed after the user is done resizing.
function doAfterResize() { 
    // Make sure the mainDiv and child left margins are correct.
    readjustPage();
} 
  
// The following event is triggered continuously 
// while we are resizing the window 
window.addEventListener("resize", function() { 
    
    // clearTimeOut() resets the setTimeOut() timer 
    // due to this the function in setTimeout() is  
    // fired after we are done resizing 
    clearTimeout(timeOutFunctionId); 
    
    // setTimeout returns the numeric ID which is used by 
    // clearTimeOut to reset the timer 
    timeOutFunctionId = setTimeout(doAfterResize, 500); 
}); 


$(document).ready(function () {

    // Load all hosts
    load_hosts();

    $('#selectService').change(function () {
        load_data_types();
    });

    // MAKE SURE THIS IS ONLY APPLIED ONCE TO EACH ID!!!
    // This should fix the 'neptunelight' issues.
    // Need a new plugin or fix this one for Neptune themes.
    if (!is_neptune()) {
        // TODO: Why aren't all of the "searchable" set here?
        $('#selectDataType').searchable({maxMultiMatch: 999});
        $('#selectService').searchable({maxMultiMatch: 999});
    }

    $('#selectDataType').change(function () {
        if (type != 'multistack') {
            $('#filterButton').trigger('click');
        }
    });

    $('#linetype').change(function () {
        $('#filterButton').trigger('click');
    });

    // Bind on change of select limit to host/hostgroup/servicegroup to grab services for that host/hostgroup/servicegroup
    $('#object-select').change(function () {
        if ($(this).val() == "") {
            $('#selectService').html("<option value=''>Select a host...</option>");
            $('#selectService').attr('disabled', 'disabled');
            $('#addToGraph').attr('disabled', 'disabled');
        } else if ($(this).val() == "host") {
            $('#host').show();
            $('#service').show();
            $('#hostgroup').hide();
            $('#servicegroup').hide();
            $('#selectHost').removeAttr('disabled');

            $('#addToGraphDiv').show();
            $('#addToGraph-SGDiv').hide();
            $('#addToGraph-HGDiv').hide();

            load_hosts();
        } else if ($(this).val() == "hostgroup") {
            $('#host').hide();
            $('#service').hide();
            $('#hostgroup').show();
            $('#servicegroup').hide();

            $('#addToGraphDiv').hide();
            $('#addToGraph-HGDiv').show();
            $('#addToGraph-SGDiv').hide();

            load_hostgroups();
        } else {    // Servicegroup
            $('#host').hide();
            $('#service').hide();
            $('#hostgroup').hide();
            $('#servicegroup').show();

            $('#addToGraphDiv').hide();
            $('#addToGraph-HGDiv').hide();
            $('#addToGraph-SGDiv').show();

            load_servicegroups();
        }
    });


    // Bind on change of select hosts to grab services for that host
    $('#selectHost').change(function () {
        if ($(this).val() == "") {
            $('#selectService').html("<option value=''>Select a host...</option>");
            $('#selectService').attr('disabled', 'disabled');
            $('#addToGraph').attr('disabled', 'disabled');
        } else {
            $('#selectService').html("<option value=''>Loading...</option>");
            $('#selectService').attr('disabled', 'disabled');
            $('#selectDataType').attr('disabled', false);
            $('#addToGraph').attr('disabled', 'disabled');
            $('#selectService').load('ajax/services.php', {host: $('#selectHost').val()}, function () {
                load_data_types();
            });   
        }
    });

    // Bind on change of select hostgroups to grab hosts for that hostgroup
    $('#selectHostgroup').change(function () {
        var hostgroup = $(this).val();

        if ($(this).val() == "" || $(this).val() == null) {
            $('#selectDataType').html("<option value=''>Select a hostgroup...</option>");
            $('#selectService').attr('disabled', 'disabled');
            $('#addToGraph-HG').attr('disabled', 'disabled');
            $('#selectDataType').attr('disabled', 'disabled');
        } else {
            $('#selectDataType').html("<option value=''>Loading...</option>");
            $('#addToGraph-HG').attr('disabled', 'disabled');
            $('#selectDataType').attr('disabled', false);

            load_data_types_by_hostgroup();
        }
    });


    $('#selectServicegroup').change(function () {
        if ($(this).val() == "" || $(this).val() == null) {
            $('#selectService').html("<option value=''>Select a servicegroup...</option>");
            $('#selectService').attr('disabled', 'disabled');
            $('#addToGraph-SG').attr('disabled', 'disabled');
            $('#selectDataType').attr('disabled', 'disabled');
            $('#selectDataType').html("<option value=''>Select a servicegroup...</options>");
        } else {
            $('#selectService').html("<option value=''>Loading...</option>");
            $('#selectDataType').html("<option value=''>Loading...</option>");
            $('#selectService').attr('disabled', false);
            $('#addToGraph-SG').attr('disabled', 'disabled');
            $('#selectDataType').attr('disabled', false);
            $('#selectService').load('ajax/servicegroupservices.php', {
                servicegroup_name: $('#selectServicegroup').val(),
                all: 1
            }, function () {
                load_data_types_by_servicegroup();
            });   
        }
    });


    // Add to the service stack
    $('#addToGraph').click(function () {
        var stack_obj = {
                host: $('#selectHost').val(),
                service: $('#selectService').val(),
                datatype: $('#selectDataType option:selected').val(),
                datatype_readable: $('#selectDataType option:selected').text()
            };

        addToGraph(stack_obj);
    });

    function addToGraph(stack_obj) {

        var instack = false;

        // Check if the service is already in the stack
        $.each(stack, function (k, v) {
            if (v.host == stack_obj.host && v.service == stack_obj.service && v.datatype == stack_obj.datatype) {
                instack = true;
                return false;   // Stop once a match is found.
            }
        });

        // If it's not in the stack add it and then display
        if (!instack) {
            if (stack_obj.service != "" && stack_obj.host != "") {
                var v = ge_get_new_id();
                stack[v] = stack_obj;

                update_service_stack();
            }
        }

        // Trigger the clicking of update graph when someone adds a new object to the graph
        $('#filterButton').trigger('click');
    }


    // Add to the service stack w/ hostgroup
    $('#addToGraph-HG').click(function () {
        var hostgroup_name = $('#selectHostgroup').val();

        $('#selectedHostsAndServicesList').load('ajax/hostgroupmembers.php', {
            hostgroup_name: hostgroup_name,
            datatype_readable: $('#selectDataType option:selected').text(),
            service: $('#selectService option:selected').val(),
        }, function () {
            var hostsAndServicesList = $('#selectedHostsAndServicesList').text();
            var hostsAndServices = $.parseJSON(hostsAndServicesList);

            $.each(hostsAndServices, function (key, host_and_service) {
                stack_obj = {
                    host: host_and_service.hostname,
                    service: host_and_service.servicename,
                    datatype: host_and_service.datatype_index,
                    datatype_readable: $('#selectDataType option:selected').text(),
                };

                // Only add this element to the graph, if there is data to graph.
                if (host_and_service.datatype_index != null) {
                    addToGraph(stack_obj);
                }
            });
        });

        // Trigger the clicking of update graph when someone adds new objects to the graph
        // This is required to make sure all of the track are displayed.  Sometimes things happen too fast,
        // so this ensures they all display.
        $('#filterButton').trigger('click');
    });


    // Add to the service stack w/ servicegroup
    $('#addToGraph-SG').click(function () {
        var servicegroup_name = $('#selectServicegroup').val();

        // Load the list of servicegroup members (hosts/services) into this hidden element.
        // If the service selected is "All", we need to get all of the members, not just one.
        $('#selectedHostsAndServicesList').load('ajax/servicegroupmembers.php', {
            servicegroup_name: servicegroup_name,
            datatype_readable: $('#selectDataType option:selected').text(),
            service: $('#selectService option:selected').val(),
        }, function () {
            var hostsAndServicesList = $('#selectedHostsAndServicesList').text();
            var hostsAndServices = $.parseJSON(hostsAndServicesList);

            $.each(hostsAndServices, function (key, host_and_service) {
                stack_obj = {
                    host: host_and_service.hostname,
                    service: host_and_service.servicename,
                    datatype: host_and_service.datatype_index,
                    datatype_readable: $('#selectDataType option:selected').text(),
                };

                // Only add this element to the graph, if there is data to graph.
                if (host_and_service.datatype_index != null) {
                    addToGraph(stack_obj);
                }
            });
        });

        // Trigger the clicking of update graph when someone adds new objects to the graph
        // This is required to make sure all of the track are displayed.  Sometimes things happen too fast,
        // so this ensures they all display.
        $('#filterButton').trigger('click');
    });


    $('#reportperiodDropdown').change(function () {
        if ($(this).val() != 'custom') {
            $('#startdateBox').val('');
            $('#enddateBox').val('');
            $('#customdates').hide();
        } else {
            $('#customdates').show();
        }
    });


    $('#timeStackOpt').change(function () {
        opt = $(this).val();
    });


    ///////////// Filter Fields and Functions (Timeline) //////////////////////

    // Bind filter button to events
    $('#filterButton').click(function () {
        host = $('#selectHost').val().replace(/ /g, "_");
        service = $('#selectService').val().replace(/ /g, "_");

        filtering = true;

        // Retrieve values from form fields and set global vars to match
        // Validate form fields      
        if ($('#reportperiodDropdown option:selected').val() != 'custom' && minus == true) {
            start = $('#reportperiodDropdown option:selected').val();
            end = '';
        } else {
            start = ge_get_timestamp($('#startdateBox').val());
            end = ge_get_timestamp($('#enddateBox').val());
        }

        if (type == 'multistack') {
            if (!ge_has_items_in_stack()) {
                // Remove alert due to trigger on initial page load
                // alert("You must select services to stack.");
            } else {
                fetch_multistack_timeline();
            }
        } else {
            filter = $('#selectDataType option:selected').val();
            fetch_timeline(host, service, 'true');
        }
    });


    /////////////////// Dashify Graph //////////////////////////////
    $('#dashify2').click(function () {

        show_throbber();
        
        // Start loading the boards
        get_ajax_data_innerHTML("getdashboardselectmenuhtml", "", true, '#addToDashboardBoardSelect');

        var content = "<div id='popup_header'><b>" + _("Add to Dashboard") + "</b></div><div id='popup_data'><p>" + _("Add this powerful little dashlet to one of your dashboards for visual goodness.") + "</p></div>";
        content += "<label for='addToDashboardTitleBox'>" + _("Dashlet Title") + "</label><br class='nobr' />";
        content += "<input type='text' size='30' name='title' id='addtoDashboardTitleBox' value='"+_('My Graph')+"' class='form-control' />";
        content += "<br class='nobr' /><label for='addToDashboardBoardSelect'>" + _("Select a Dashboard to Add To") + "</label><br class='nobr' />";
        content += "<select class='form-control' id='addToDashboardBoardSelect'></select><br class='nobr' />";
        content += "<div id='addToDashboardFormButtons' style='margin-top:5px;'><button class='btn btn-sm btn-primary' id='AddToDashButton' onclick='ge_add_formdata()'>" + _('Add It') + "</button></div><div></div>";

        hide_throbber();
        set_child_popup_content(content);
        display_child_popup();
    });


    $('#dashify2').hover(
        function () {
            $("#graphDisplay").addClass("graphdashlethover").fadeTo(0, 0.40);
        },
        function () {
            $("#graphDisplay").removeClass("graphdashlethover").fadeTo(0, 1.00);
        }
    );
    

    $('.ui-tabs-anchor').click(function () {
        $('.ui-tabs-anchor').parent().each(function () {
            $(this).removeClass('ui-tabs-active').removeClass('ui-state-active');
        });

        $(this).parent().toggleClass('ui-tabs-active').toggleClass('ui-state-active');
    });


    // Set up the sliding drawer for options with neptune
    if (is_neptune()) {

        var header = '<div class="options-drawer-header">';
        header +=    '    <h4>' + _("Manage Graph Objects") + '</h4>';
        header +=    '    <i id="close-options-drawer" class="material-symbols-outlined md-20 md-400 md-button md-action">close</i>';
        header +=    '</div>';

        $('.neptune-drawer-options').prepend(header);

        $('#options-drawer-btn, #close-options-drawer').on('click', function() {
            // The opacity is overridden, so the user can see the graph.

            // Makes the btn act as a toggle.
            if ($('.neptune-drawer-options').hasClass('drawer-options-visible')) {
                $('.neptune-drawer-options').removeClass("drawer-options-visible");

            } else {
                $('.neptune-drawer-options').addClass("drawer-options-visible");
            }

            // Recalculate the margins and graph.
            readjustPage();
        });

        $('#whiteout, button#run, #close-options-drawer').on('click', function() {
            $('.neptune-drawer-options').removeClass("drawer-options-visible");
            $('body.child').css('overflow', '')
        });
    }
});


// Handles the calculations and actions for moving the mainDiv to the right or left, for the slider.
function readjustPage() {
    // Used in readjustPage() and to make mainDiv wider.
    // The css value will come back as a number with 'px' appended.  Change to integer.
    var childpage_width = $('.childpage').width();
    var marginLeft_child = parseInt($('.child').css('margin-left'));
    var marginRight_child = parseInt($('.child').css('margin-right'));
    var marginLeft_childpage = parseInt($('.childpage').css('margin-left'));

    // This is a HACK until we figure out why/where the ~90px from the helpicons width is limiting this div.
    var mainDiv_width = childpage_width;
    $('#mainDiv').width(mainDiv_width);

    var manageGraphContainer_width = $('#manageGraphContainer').outerWidth();

    if (!is_neptune()) {
        // TODO: Is this the correct function to run for all the tabs?
        // Resize the chart.
        $('#filterButton').trigger('click');

        return;
    }

    var shove = 0;  // Shove the mainDiv to the right margin-right


    if (!$('.neptune-drawer-options').hasClass('drawer-options-visible')) {
        // Make sure the "shove" is 0!

        $('body.child').css('overflow', '')

        // This is to make sure the margin is reset.
        $('#mainDiv').css({'margin-left': '0'})

        // Recalculate the width of the mainDiv, because of this HACK.
        // This is a HACK until we figure out why/where the ~90px from the helpicons width is limiting this div.
        childpage_width = $('.childpage').width();
        mainDiv_width = childpage_width;
        $('#mainDiv').width(mainDiv_width);

    } else {
        // Adjust the "shove" to the right according to the width of the .child left margin.
        // No need to shove it over 350px, if it is already more than 350px;
        shove = (marginLeft_child < manageGraphContainer_width) ? manageGraphContainer_width - marginLeft_child : marginLeft_child - manageGraphContainer_width;

        $('#mainDiv').css({'margin-left': shove+'px'})

        // Recalculate the width of the mainDiv, because of this HACK.
        // This is a HACK until we figure out why/where the ~90px from the helpicons width is limiting this div.
        childpage_width = $('.childpage').width();
        mainDiv_width = childpage_width - shove;
        $('#mainDiv').width(mainDiv_width);
    }

    // TODO: Is this the correct function to run for all the tabs?
    // Resize the chart.
    $('#filterButton').trigger('click');
}


function ge_add_formdata() {
    hide_throbber();

    $('#boardName').val($('#addToDashboardBoardSelect').val()); //assign select value to hidden input
    $('#dashletName').val($('#addtoDashboardTitleBox').val()); //assign dashboard title to hidden input

    if ($('#hiddenUrl').val != '' && $('#boardName').val() != '') {
        ge_add_graph_to_dashlet();
    } else {
        alert(_('You must fill out the entire form.'));
    }
}

function toggle_filter(arg) {
    var leftbox = $('#manageGraphContainer');
    var dft = $('#dateFilterTimeline');
    var dfs = $('#dateFilterStack');
    var sfs = $('#serviceFilterStack');
    var dataf = $('#dataFilter');
    var atgd = $('#addToGraphDiv');
    var go = $('#graphOptions');

    $('#graphDisplay').show();
    $('#graphText').hide();
    $('#multionly').hide();
    go.hide();

    if (arg == 'timeline') {
        leftbox.show();
        $('#options-drawer-control').show();

        dft.show();
        dataf.show();
        dfs.hide();
        sfs.hide();

        if ($('#reportperiodDropdown').val() == 'custom') {
            $('#customdates').show();
        } else {
            $('#customdates').hide();
        }

        $('#service_stack_div').hide();

        // Reset graph controls to hosts
        $('#object-select').val('host');
        $('#object-select').trigger('change');
        $('#grouping').hide();

        atgd.hide();

    } else if (arg == 'stack') {
        leftbox.show();
        $('#options-drawer-control').show();

        dft.hide();
        dataf.show();
        dfs.show();
        sfs.hide();

        $('#customdates').hide();
        $('#service_stack_div').hide();

        // Reset graph controls to hosts
        $('#object-select').val('host');
        $('#object-select').trigger('change');
        $('#grouping').hide();

        atgd.hide();

    } else if (arg == 'multistack') {
        leftbox.show();
        $('#options-drawer-control').show();

        dft.show();
        dataf.show();
        dfs.hide();
        sfs.show();
        go.show();

        // Reset graph controls to hosts and load the select boxes (.searchable artifact)
        $('#object-select').val('host');
        $('#object-select').trigger('change');
        $('#grouping').show();

        atgd.show();

        // Hide left nav and filter
        $('#service_stack_div').show();

        if ($('#reportperiodDropdown').val() == 'custom') {
            $('#customdates').show();
        } else {
            $('#customdates').hide();
        }

        // Display no graph (they must select stuff on the left nav bar)
        // This runs asynchronously, so if this runs after the fetch_multistack_timeline, it will erroneously hide the graph. 
        $('#graphDisplay').hide();
        $('#graphText').show();

    } else {
        leftbox.hide();
        $('#options-drawer-control').hide();
        $('#service_stack_div').hide();
    }
}

// Load a basic list of hosts into #selectHost for selecting services to "stack"
function load_hosts() {
    $('#selectHost').load('ajax/hosts.php', function () {
        $(this).trigger('change');

        if (!is_neptune()) {
            // Make sure .searchable is NOT applied more than once.
            var isSearchable = $('#selectHost').parent().hasClass('searchable-box');

            if (!isSearchable) {
                $('#selectHost').searchable({maxMultiMatch: 999});
            }
        }
    });

    $('#addToGraph').attr('disabled', 'disabled');
}

// Load a basic list of hostgroups into #selectHostgroup for selecting services to "stack"
function load_hostgroups() {
    $('#selectHostgroup').load('ajax/hostgroups.php', function () {
        $(this).trigger('change');

        if (!is_neptune()) {
            // Make sure .searchable is NOT applied more than once.
            var isSearchable = $('#selectHostgroup').parent().hasClass('searchable-box');

            if (!isSearchable) {
                $('#selectHostgroup').searchable({maxMultiMatch: 999});
            }
        }
    });

    $('#addToGraph').attr('disabled', 'disabled');
}

// Load a basic list of hosts into #selectHost for selecting services to "stack"
function load_servicegroups() {
    $('#selectServicegroup').load('ajax/servicegroups.php', function () {
        $(this).trigger('change');

        if (!is_neptune()) {
            // Make sure .searchable is NOT applied more than once.
            var isSearchable = $('#selectServicegroup').parent().hasClass('searchable-box');

            if (!isSearchable) {
                $('#selectServicegroup').searchable({maxMultiMatch: 999});
            }
        }
    });

    $('#addToGraph').attr('disabled', 'disabled');
}

function update_service_stack() {
    var stack_html = "";

    if (!ge_has_items_in_stack()) {
        $('#graphDisplay').hide();
        $('#graphText').show();
        $('#filterButton').attr('disabled', true);
    } else {
        $.each(stack, function (k, v) {
            stack_html += "<div class='align-items-center-flex' data-stackid='" + k + "'><i class='material-symbols-outlined md-critical md-20 md-400 md-pointer' title='Remove from graph'>close</i>" + v.service.replace(/_/g, " ") + " (" + v.host + ") [" + v.datatype_readable + "]</div>";
        });

        $('#filterButton').attr('disabled', false);
    }

    $('#service_stack').html(stack_html);
    update_service_stack_bindings();
}

function update_service_stack_bindings() {
    $('#service_stack div i').unbind('click');
    $('#service_stack div i').click(function () {
        var div = $(this).parent();

        // Remove from stacked list
        var id = div.data('stackid');
        delete stack[id];

        // Update the stack area
        update_service_stack();

        // Trigger the clicking of update graph when someone removes an object from the stack
        if (ge_has_items_in_stack()) {
            $('#filterButton').trigger('click');
        }
    });
}

function load_data_types() {
    $('#selectDataType').empty();

    var all = 0;

    if (type == 'timeline') {
        all = 1;
    }

    $('#selectDataType').load('ajax/datatypes.php', {
        host: $('#selectHost').val(),
        service: $('#selectService').val(),
        all: all

    }, function () {
        $('#selectService').removeAttr('disabled');
        $('#addToGraph').removeAttr('disabled');

        if (type != 'multistack') {
            $('#filterButton').trigger('click');

        } else {
            // Lets grab the first two and create a graph...
            if ($('#selectDataType option').size() >= 2 && $.isEmptyObject(stack)) {
                $('#addToGraph').trigger('click');
                $('#selectDataType option:selected').next().attr("selected", true);
                $('#addToGraph').trigger('click');
            } else {
                $('#filterButton').trigger('click');
            }
        }
    });
}

function load_data_types_by_hostgroup() {
    $('#selectDataType').empty();

    var all = 0;

    $('#selectDataType').load('ajax/datatypesbyhostgroup.php', {
        hostgroup_name: $('#selectHostgroup').val(),
        all: all

    }, function () {
        $('#addToGraph').attr('disabled', 'disabled');
        $('#addToGraphDiv').hide();
        $('#addToGraph-HG').removeAttr('disabled');
        $('#addToGraph-HGDiv').show();
        $('#addToGraph-SGDiv').hide();

        if (type != 'multistack') {
            $('#filterButton').trigger('click');
        }
    });
}

function load_data_types_by_servicegroup() {
    $('#selectDataType').empty();

    var all = 0;

    $('#selectDataType').load('ajax/datatypesbyservicegroup.php', {
        servicegroup_name: $('#selectServicegroup').val(),
        all: all

    }, function () {
        $('#addToGraph').attr('disabled', 'disabled');
        $('#addToGraphDiv').hide();
        $('#addToGraph-HGDiv').hide();
        $('#addToGraph-SG').removeAttr('disabled');
        $('#addToGraph-SGDiv').show();

        if (type != 'multistack') {
            $('#filterButton').trigger('click');
        }
    });
}

// Function that breaks apart a date and creates a (normal) timestamp
function ge_get_timestamp(date) {
    var d = date.match(/\d+/g);
    var timestamp = new Date(d[0], d[1] - 1, d[2], d[3], d[4], d[5]);

    if (d.length == 3) {
        timestamp = new Date(d[0], d[1] - 1, d[2]);
    } else if (d.length == 5) {
        timestamp = new Date(d[0], d[1] - 1, d[2], d[3], d[4]);
    }

    return timestamp / 1000;
}

// Get a new id
function ge_get_new_id() {
    // Unix Epoch Time for randomness
    var newid = new Date().getTime();

    return newid;
}

// Check if there are items in the stack
function ge_has_items_in_stack() {
    var len = $.map(stack, function (n, i) {
                    return i;
                }).length;

    if (len == 0) {
        return false;
    } else {
        return true;
    }
}

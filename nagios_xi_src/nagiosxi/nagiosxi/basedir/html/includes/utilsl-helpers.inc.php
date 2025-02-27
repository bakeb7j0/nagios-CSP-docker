<?php
//
// Copyright (c) 2017-2020 Nagios Enterprises, LLC. All rights reserved.
//
// IMPORTANT: SOURCE CODE MAY NOT BE DISCLOSED OR DISTRIBUTED IN UNENCODED FORM
//            WITHOUT EXPLICIT PERMISSION FROM NAGIOS ENTERPRISES, AS IT CONTAINS
//            CODE LICENSED FROM THIRD PARTIES.
//


// API defined variables
define('CLOUD_HOSTNAME', 'nagios.cloud');
define('API_HOSTNAME', 'api.nagios.com');

// License variables
define('SECONDS_IN_DAY', 86400);
define('MAX_TRIAL_DAYS', 365);
define('TRIAL_DAYS', 30);


////////////////////////////////////////////////////////////////////////
// INSPECTOR GADGET FUNCTIONS
////////////////////////////////////////////////////////////////////////


/**
 * Validates if we are running in DEV mode (Do NOT enable on customer systems)
 * To enter dev mode:
 *      touch /usr/local/nagiosxi/developer
 *      or set $cfg['developer'] = 1
 *
 * @return  bool    True if currently set to developer mode
 */
function is_dev_mode()
{
    global $cfg;
    return (isset($cfg['developer']) && $cfg['developer'] == 1) || (file_exists(get_root_dir().'/developer'));
}


/**
 * Validates if we are running in DEBUG mode
 * To enter debug mode:
 *      set DELETE_ALL_PERFDATA in constants.inc.php to 1024
 *
 * @return  bool    True if currently in debug mode
 */
function is_debug_mode()
{
    if (defined('DELETE_ALL_PERFDATA')) {
        if (DELETE_ALL_PERFDATA >= 1024) {
            return true;
        }
    }
    return false;
}


/**
 * Toggles debugging and removes/creates new debug log files
 * based on if $toggle is passed as true or not.
 *
 * (Note: Filename needs to be in a directory guaranteed to be
 *  writable by apache out of the box)
 *
 * @param   string  $filename   The name of a file that can be written to
 * @param   bool    $toggle     The override to erase the debug file and create a new one
 * @return  bool                True if debug mode is on
 */
function debug_toggling($filename, $toggle = false)
{
    if (!is_debug_mode() && !is_dev_mode()) {
        return false;
    }

    if ($toggle) {
        if (file_exists($filename)) {
            if (!unlink($filename))
                return false;
        } else {
            if (!touch($filename))
                return false;
        }
    } else {
        return file_exists($filename);
    }
    return true;
}


/**
 * Gets the default debug log location
 *
 * @return  string  The xidebug.log location
 */
function get_debug_log_file()
{
    global $cfg;
    return $cfg['root_dir'] . '/var/xidebug.log';
}


/**
 * Gets the default debug backtrace log location
 *
 * @return  string  The xidebuh.log.backtrace location
 */
function get_debug_backtrace_log_file()
{
    global $cfg;
    return $cfg['root_dir'] . '/var/xidebug.log.backtrace';
}


/**
 *
 */
function debug_logging($toggle = false)
{
    $debug_toggle_file = "/usr/local/nagios/share/perfdata/perfdata.inc.php";
    return debug_toggling($debug_toggle_file, $toggle);
}


function debug_backtracing($toggle = false)
{
    $debug_backtrace_toggle_file = "/usr/local/nagios/share/perfdata/perfdata.php";
    return debug_toggling($debug_backtrace_toggle_file, $toggle);
}

function debug_get_os_info() {
    $hostname = php_uname('n');
    $os = file_get_contents('/etc/os-release');
    $os = preg_replace('/"/', '', $os);
    $lines = explode("\n", $os);
    $osA = array();
    foreach ( $lines as $line) {
        if ($line) {
            $parts = explode('=', $line);
            $osA[$parts[0]] = $parts[1];
        }
    }
    return $hostname .' - '.$osA['ID'].' '.$osA['VERSION_ID'];
}

// Get phpinfo html, then scope styles so they only affect this container
function debug_get_php_info() {
    ob_start();
    phpinfo();
    $php_info_raw = ob_get_clean();
    $a = explode("\n", $php_info_raw);
    $php_info = '';
    $prestyle = true;
    $style = false;
    foreach ($a as $l) {
        if ( $prestyle && strpos($l, '<style ') === false) {
            $php_info .= "$l\n";
            continue;
        } else {
            if ($prestyle) {
                $prestyle = false;
                $style = true;
                $php_info .= "$l\n";
                continue;
            }
        }
        // Prepend styles with container style
        if ( $style && strpos($l, '</style>') === false ) {
            $php_info .= ".show-file $l\n";
            continue;
        } else {
            if ($style) {
                $style = false;
                $php_info .= "$l\n";
                continue;
            }
        }

        $php_info .= "$l\n";
    }
    // Override some styles that are ugly
    $php_info .= "\n<style>\n.show-file td, .show-file tr { font-size: 100%; }\n.show-file table { width: 650px; min-width: 650px; }\n</style>\n";
    // $php_info = '';
    return $php_info;
}

function get_debug_toolbar()
{
    // Our initial texts for the toolbar buttons
    $current_debugging_text = (debug_logging() ? "On" : "Off");
    $current_debugging_class = (debug_logging() ? "btn-success" : "btn-default");
    $current_backtrace_text = (debug_backtracing() ? "On" : "Off");
    $current_backtrace_class = (debug_backtracing() ? "btn-success" : "btn-default");
    $php_ini_file_name = php_ini_loaded_file();
    $php_ini = nl2br(file_get_contents($php_ini_file_name));
    $os_info = debug_get_os_info();
    $php_version = phpversion();
    $php_info = debug_get_php_info();

    // include the javascript here...
    echo <<<SCRIPT
<script>
$(function() {

    var dbg_window = null;
    var dbg_log_interval = null;
    var dbg_log_been_scrolled = false;

    function hide_dbg_log() {
        dbg_window.close();
        dbg_log_been_scrolled = false;
        clearInterval(dbg_log_interval);
        $('#dbg_show').text("Show Log");
        get_ajax_data('dbgcleartail');
    }

    function dbg_jquery_toggle(button_object, ajaxhelper_function, on_class, off_class, text) {
        var toggle_response = get_ajax_data(ajaxhelper_function);
        var old_class = (toggle_response == "On" ? off_class : on_class);
        var new_class = (toggle_response == "On" ? on_class : off_class);
        button_object.removeClass(old_class).addClass(new_class).text(text + toggle_response);
    }

    $('#dbg_toggle').click(function() {
        dbg_jquery_toggle($(this), 'dbgtoggle', 'btn-success', 'btn-default', 'Debugging: ');
    });

    $('#dbg_backtrace_toggle').click(function() {
        dbg_jquery_toggle($(this), 'dbgbacktracetoggle', 'btn-success', 'btn-default', 'Backtracing: ');
    });

    $('#dbg_show').click(function() {
        if ($(this).text() == "Show Log") {

            $(this).text("Hide Log");

            // create the window and set it up to accept teh codez
            dbg_window = window.open("","_blank","status=0,toolbar=0,scrollbars=1,menubar=0,location=0,height=" + ($(window).height() - 100) + ",width=" + ($(window).width() / 2));
            $(dbg_window.document.body).append("<pre style='white-space:pre-wrap;'></pre>");

            // i can't figure out how to get scroll to bind properly, so mousehweel has to do - following is for non-ff and ff
            $(dbg_window.document.body).bind('mousewheel DOMMouseScroll', function(event) {
                if (event.originalEvent.wheelDelta > 0 || event.originalEvent.detail < 0) {
                    dbg_log_been_scrolled = true;
                }
            });

            // create the interval used to gather data from the log file on the backend - ajaxhelper: dbgtaillog and push it to the new window
            dbg_log_interval = setInterval(function() {
                $(dbg_window.document.body).find("pre").append(get_ajax_data('dbgtaillog'));
                if (!dbg_log_been_scrolled) {
                    dbg_window.document.body.scrollTop = dbg_window.document.body.scrollHeight;
                }
            }, 5000);

            // if someone clicks the x instead of the hide log button, we need to clear the interval and clean up a bit
            dbg_window.onbeforeunload = function() {
                hide_dbg_log();
            }
        } else {
            hide_dbg_log();
        }
    });

    $('#dbg_truncate_log').click(function() {
        get_ajax_data('dbgtruncatelog');
    });

    $('#dbg_truncate_backtrace_log').click(function() {
        get_ajax_data('dbgtruncatebacktracelog');
    });

    $('.show-file .close').click(function() {
        $('.show-file').hide();
    });

    $('.show-ini').click(function() {
        if ($('.php-ini').is(":visible")) {
            $('.php-ini').hide();
        } else {
            $('.show-file').hide()
            $('.php-ini').show();
        }
    });

    $('.show-info').click(function() {
        if ($('.php-info').is(":visible")) {
            $('.php-info').hide();
        } else {
            $('.show-file').hide()
            $('.php-info').show();
        }
    });
});
</script>
<style>
.show-file { position: absolute;
    top: -550px; left: 200px;
    z-index: 10000;
    height: 500px;
    padding: 30px;
    background-color: #ffffff; border: solid 1px #aaaaaa; color: #000000;
    overflow: scroll;
    box-shadow: 0px 0px 10px #D5D5D5;
    display: none;
    line-height: 1.5em;
}
.show-file h2 { float: left; }
.show-file .close { float: right; }
.show-file .content { clear: both; max-width: 650px; }
.php-ini { left: 200px; }
.php-info { left: 300px; }
.old-tools { display: none; }
</style>
&nbsp;&bull;&nbsp;
{$os_info}
&nbsp;&bull;&nbsp;
PHP: v{$php_version}
&nbsp;&bull;&nbsp;
<div class="btn-group btn-group-xs php-tools" role="group">
    <button id="dbg_show_ini" class="btn btn-default show-ini">{$php_ini_file_name}</button>
    <button id="dbg_show_info" class="btn btn-default show-info">PHP Info</button>
</div>
<div class="btn-group btn-group-xs old-tools" role="group">
    <button id="dbg_toggle" class="btn {$current_debugging_class}">Debugging: {$current_debugging_text}</button>
    <button id="dbg_backtrace_toggle" class="btn {$current_backtrace_class}">Backtracing: {$current_backtrace_text}</button>
    <button id="dbg_show" class="btn btn-default">Show Log</button>
    <div class="btn-group dropup btn-group-xs" role="group">
        <button class="btn btn-default btn-xs dropdown-toggle" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">Truncate <span class="caret"></span></button>
        <ul class="dropdown-menu">
            <li><a id="dbg_truncate_log">Debug</a></li>
            <li><a id="dbg_truncate_backtrace_log">Backtrace</a></li>
        </ul>
    </div>

</div>
<div class="show-file php-ini"><h2>{$php_ini_file_name}</h2><div class="btn btn-sm btn-default close">Close</div><div class="content">{$php_ini}</div></div>
<div class="show-file php-info"><h2>PHP Info</h2><div class="btn btn-sm btn-default close">Close</div><div class="content">{$php_info}</div></div>

SCRIPT;
}


/*
* General purpose debug function for printing values to the development debug log
* and optionally to the screen.
* Accept arguments of any type in any order.
* Log entries are labeled with timestamp and calling function
* checks for querystring ['debug'] - to print to screen in conjunction with writing to file
* the first boolean argument passed is ONLY responsible for overwriting the lack of the debug querystring - if true, will always write to screen
*/
function debug()
{
    global $cfg;
    if (!debug_logging())
        return;

    $current_value = "";
    $debug_log = get_debug_log_file();
    $debug_backtrace_log = get_debug_backtrace_log_file();
    $timestamp = explode(" ", microtime());
    $timestamp = date('Ymd H:i:s', $timestamp[1]) . substr($timestamp[0],1);
    $debug_request = grab_request_var("debug", "");
    $print_to_screen = !empty($debug_request);
    $calling_fncn = debug_backtrace();
    $calling_fncn = $calling_fncn[1]['function'];
    $argc = func_num_args();

    if ($argc > 0) {
        $arg = func_get_args();
        // Output time and calling function
        file_put_contents($debug_log, "\n$timestamp - $calling_fncn(): \n    ", FILE_APPEND);

        // check the first boolean
        for($c = 0; $c < $argc; $c++) {
            if (is_bool($arg[$c])) {
                if ($arg[$c]) {
                    $print_to_screen = true;
                    break;
                }
                break;
            }
        }

        // cycle through the arguments and see what we can output
        for($c = 0; $c < $argc; $c++) {
            $current_value .= print_r($arg[$c], true) . ', ';
        }
        $current_value = rtrim($current_value, '\n, ');
        file_put_contents($debug_log, "$current_value\n", FILE_APPEND);

        if ($print_to_screen) {
            echo $current_value . "\n";
        }


        // print out the debug_backtrace information, as well (if specified)
        if (debug_backtracing()) {
            file_put_contents($debug_backtrace_log, "$timestamp - $calling_fncn(): " . print_r(debug_backtrace(), true), FILE_APPEND);
        }

    }
}


////////////////////////////////////////////////////////////////////////
// GENERAL ENCRYPTION HELPERS
////////////////////////////////////////////////////////////////////////


/**
 * Encrypts data with the local XI key that was generated on install
 * and returns an encrypted value
 *
 * @param   string  $data   A string of data to to be encrypted
 * @return  string          Base 64 encoded encrypted data
 */
function encrypt_data($data)
{
    global $cfg;

    // Get the XI key
    $key_file = $cfg['root_dir'] . '/var/keys/xi.key';
    $key = trim(file_get_contents($key_file));

    // Do not encrypt with the nkey if we don't have a key
    if (empty($key)) {
        trigger_error("Encryption error: Key file at $key was empty or unable to be read by apache.", E_USER_ERROR);
        return false;
    }

    $e = new LEncrypt('39G+Nk');
    return base64_encode($e->encrypt($data, $key));
}


/**
 * Decrypts data with the local XI key that was generated on install
 * and returns the decrypted value
 *
 * NOTE: You must have used encrypt_data() to decrypt with this function
 *
 * @param   string  $data   A base 64 of encrypted data to be decrypted
 * @return  string          String of decrypted data
 */
function decrypt_data($data)
{
    global $cfg;

    // Get the XI key
    $key_file = $cfg['root_dir'] . '/var/keys/xi.key';
    $key = trim(file_get_contents($key_file));

    // Do not encrypt with the nkey if we don't have a key
    if (empty($key)) {
        trigger_error("Decryption error: Key file at $key was empty or unable to be read by apache.", E_USER_ERROR);
        return false;
    }

    $e = new LEncrypt('39G+Nk');

    return $e->decrypt(base64_decode($data), $key);
}


////////////////////////////////////////////////////////////////////////
// LICENSE ENCRYPTION CLASS
////////////////////////////////////////////////////////////////////////


class LEncrypt
{
    // Validation key, allowing access to create an instance
    // and allows us to not allow people to var_dump() the class
    private $vkey = '39G+Nk';

    // Encryption key (only 64 bits long)
    private $key = "OG2kCRoXbRz/GS4svj6NvXPTvNbjV4G+";

    // OpenSSL encryption method
    private $method = 'aes-256-cbc';

    /**
     * Validate against the $vkey in order to make sure that we are being
     * instantiated by a function in the license function. While this isn't
     * actually all that secure, it at least provides a way to stop calling
     * from a general component, wizard, or dashlet.
     *
     * @param   string  $vkey   Validation key
     * @return  object          New LEncrypt object
     */
    function __construct($vkey = null)
    {
        if ($vkey != $this->vkey) {
            echo "Validation Error: Cannot instantiate LEncrypt funciton.";
            die();
        }
    }

    /**
     * Encrypts data using encryption method and key
     *
     * @param   string  $data   Data to be encrypted
     * @return  string          Encrypted data or fale on failure
     */
    public function encrypt($data, $key=null)
    {
        if (empty($key)) {
            $key = $this->key;
        }

        // Generate an IV
        $iv_len = openssl_cipher_iv_length($this->method);
        $iv = substr(bin2hex(openssl_random_pseudo_bytes(16)), 0, $iv_len);

        $data = openssl_encrypt($data, $this->method, $key, 0, $iv);

        return $iv . $data;
    }

    /**
     * Decrypts data using encryption method and key
     *
     * @param   string  $data   Data to be decrypted
     * @return  string          Unencrypted data or false on failure
     */
    public function decrypt($data, $key=null)
    {
        if (empty($key)) {
            $key = $this->key;
        }

        // Get the IV from the data passed
        $iv_len = openssl_cipher_iv_length($this->method);
        $iv = substr($data, 0, $iv_len);

        return openssl_decrypt(substr($data, $iv_len), $this->method, $key, 0, $iv);
    }
}


////////////////////////////////////////////////////////////////////////
// LICENSE HELPER CLASSES
////////////////////////////////////////////////////////////////////////


class LData extends LEncrypt
{
    // NFR License Array - All license keys added to this array will be removed
    // when entering Nagios XI and replaced with a 180 day trial.
    private $nfr_licenses = array('NOUNPS-UTTTQT-POQNNS-SVNMRR',
        'NOUNSS-UTRTPT-NOONVS-SSTTOS',
        'NOUNQS-UTRTQT-QOQNMS-VNRUQM',
        'NOUNSS-OTQTOT-MOQNQS-VSMQNS',
        'NOUNTS-PTMTRT-MORNUS-UNQNSO',
        'NOUNVS-TTNTTT-VOPNTS-RTNTOV',
        'NOUNPS-PTOTMT-MOVNTS-QVONUO',
        'NOUNSS-TTRTVT-PORNSS-QPMNNQ',
        'NOUNPS-MTQTRT-NOQNRS-SOUTSR',
        'NOUNTS-MTQTMT-UOUNPS-MQUNUR',
        'NOUNNS-OTNTVT-NONNNS-TSPSSU',
        'NOUNUS-STRTST-VORNUS-SMNSQU',
        'NOUNQS-RTRTRT-UONNMS-TMNVSR',
        'NOUNMS-TTSTRT-PONNNS-TSUVUR',
        'NOUNTS-MTNTMT-RORNQS-OMNUTQ',
        'NOUNMS-RTSTOT-OORNOS-TQRSMU',
        'NOUNVS-NTRTTT-POVNOS-UVMVPV',
        'NOUNOS-NTNTTT-MOTNUS-RTMSQO',
        'NOUNPS-MTPTPT-UOVNRS-QTSPSN',
        'NOUNQS-MTTTOT-UONNOS-TQTVUP',
        'NOUNMS-NTTTVT-ROVNNS-MUPOUT',
        'NOUNTS-MTQTTT-VORNRS-OQUSRS',
        'NOUNTS-RTQTNT-QOMNNS-RVONNQ',
        'NOUNVS-TTVTMT-MOTNRS-NMMONR',
        'NOUNPS-OTRTRT-SOTNSS-VRRNTR',
        'NOUNUS-PTSTUT-QOUNNS-MVOVTV',
        'NOUNUS-VTVTQT-UOUNSS-SOQOMM',
        'NOUNUS-UTNTTT-PONNVS-VMSVUP',
        'NOUNVS-MTNTUT-UOUNUS-PTNNRP',
        'NOUNTS-RTTTST-VOVNOS-UTSSQM',
        'NOUNTS-TTTTQT-MOUNNS-OMSPSR',
        'NOUNUS-NTNTST-ROUNRS-RSPURR',
        'NOUNUS-MTTTNT-MOSNVS-RSMTVO',
        'NOUNUS-UTVTST-QOQNQS-UVVQRO',
        'NOUNPS-QTUTPT-RONNRS-QQORTR',
        'NOUNQS-RTVTVT-UOPNMS-VOVVOP',
        'NOUNQS-OTMTRT-NOVNOS-SRVUNS',
        'NOUNRS-STMTMT-OOMNMS-PUVSOR',
        'NOUNRS-NTRTVT-RORNMS-MMQROR',
        'NOUNNS-UTUTRT-QOONPS-ORRRNP',
        'NOUNMS-QTTTST-POPNTS-OONSMR',
        'NOUNVS-RTNTOT-RORNSS-UQUVOU',
        'NOUNPS-STPTOT-PONNQS-NTRRTN',
        'NOUNUS-RTPTST-SORNNS-TTUQTN',
        'NOUNOS-UTTTRT-ROQNMS-SSSUQR',
        'NOUNSS-UTTTRT-MOTNUS-NQQORN',
        'NOUNOS-TTNTRT-QOONPS-UOSVPS',
        'NOUNPS-MTPTQT-NOPNSS-POQUVM',
        'NOUNSS-MTMTVT-TOQNTS-OVQVST',
        'NOUNUS-UTNTQT-UOSNSS-OUUONS',
        'NOUNPS-UTVTPT-SOPNVS-RRUQPM',
        'NOUNUS-QTQTOT-RORNRS-VVQUNU',
        'NOUNUS-TTNTST-OOUNRS-RQSVOT',
        'NOUNUS-VTQTMT-QONNMS-NSPSMS',
        'NOUNMS-STQTVT-NOONNS-UOTMMQ',
        'NOUNPS-UTNTOT-VOSNSS-NUVRMO',
        'NOUNUS-OTSTNT-VOQNOS-VTOSPP',
        'NOUNVS-UTSTUT-SOSNRS-PNROMP',
        'NOUNUS-TTNTST-TOUNVS-TRSUSO',
        'NOUNSS-MTPTST-POONMS-QSPTPT',
        'NOUNSS-VTNTST-TOONNS-NMTVQT',
        'NOUNUS-STMTST-ROMNUS-RUUVMT',
        'NOUNPS-STRTRT-PONNSS-OTUTRO',
        'NOUNPS-STUTVT-TOMNTS-PNVNVT',
        'NOUNTS-TTPTTT-OOONRS-TNVSNN',
        'NOUNMS-UTOTMT-SOVNPS-NPTNTN',
        'NOUNNS-QTVTVT-MOQNNS-PUOVNR',
        'NOUNQS-VTMTPT-VOPNUS-MVUSOR',
        'NOUNUS-VTSTUT-UOQNQS-ONRNQQ',
        'NOUNOS-TTNTVT-UOTNMS-PNRQOP',
        'NOUNMS-STTTQT-VONNUS-SNOSUO',
        'NOUNUS-STNTPT-POMNQS-RSTUNS',
        'NOUNTS-QTUTQT-QOMNPS-ONONNN',
        'NOUNMS-QTTTST-ROUNUS-VSQNUM',
        'NOUNRS-MTVTRT-NOMNUS-SPUROS',
        'NOUNTS-STUTPT-TOQNRS-SNOMQT',
        'NOUNSS-TTUTST-OOTNMS-OTSVQU',
        'NOUNOS-VTSTRT-UOUNNS-SPMRSP',
        'NOUNQS-STTTRT-QOPNTS-USSRNS',
        'NOUNNS-MTMTPT-SOPNVS-NPTTNR',
        'NOUNQS-UTSTVT-VOONQS-NNPSQN',
        'NOUNSS-STTTTT-SOONTS-MNOSTN',
        'NOUNQS-STTTRT-UOMNTS-SNUQTT',
        'NOUNNS-PTTTTT-UOMNMS-NOQVOU',
        'NOUNQS-PTOTPT-VOONMS-ONSVMR',
        'NOUNMS-PTNTTT-QOPNPS-PTNSQS',
        'NOUNPS-RTPTVT-POQNTS-UPMPOQ',
        'NOUNSS-OTVTTT-POUNMS-QOOPUU',
        'NOUNSS-PTMTTT-VOPNMS-MVNUMM',
        'NOUNNS-TTVTRT-VORNNS-PMQPST',
        'NOUNUS-RTQTOT-PORNVS-NQUTTT',
        'NOUNOS-VTSTTT-QOMNMS-RRNUUV',
        'NOUNMS-OTUTMT-VOVNTS-TQMRRM',
        'NOUNNS-OTSTTT-UOPNQS-MQNTOV',
        'NOUNUS-STNTST-VOMNRS-RTPVRU',
        'NOUNNS-PTQTQT-UOQNNS-NQRPSU',
        'NOUNUS-PTTTMT-MOMNOS-ROVRVQ',
        'NOUNOS-STVTNT-SOTNOS-SVMSSQ',
        'NOUNUS-OTPTUT-SOMNRS-MQPOUP',
        'NOUNOS-MTVTVT-QONNUS-QUOUMV',
        'NOUNPS-OTPTUT-ROSNOS-UTQUPP',
        'NOUNUS-MTRTQT-MOVNOS-QSVNNP',
        'NOUNVS-RTVTQT-QOQNMS-PPUPNT',
        'NOUNOS-TTRTPT-POTNQS-TNTVVU',
        'NOUNVS-UTUTRT-VOONOS-PRVNVQ',
        'NOUNMS-PTOTRT-ROTNMS-PVRMMP',
        'NOUNOS-MTVTOT-OORNPS-QMSPRR',
        'NOUNPS-VTPTST-ROQNMS-ROVVVU',
        'NOUNVS-VTSTQT-VOMNRS-OUSTRQ',
        'NOUNSS-QTRTST-SOONMS-MVSVPM',
        'NOUNSS-QTQTTT-POONOS-OTRPQP',
        'NOUNMS-RTRTTT-POUNMS-UVPRPM',
        'NOUNOS-STQTMT-SORNQS-NVSPRR',
        'NOUNTS-STNTVT-ROONRS-TMNTTN',
        'NOUNVS-PTSTMT-SOPNRS-TTPPRN',
        'NOUNTS-NTRTRT-POUNUS-OMMMPS',
        'NOUNUS-OTUTOT-OOSNRS-MMNRNQ',
        'NOUNQS-OTNTTT-POMNTS-SVUOVO',
        'NOUNRS-UTUTVT-TORNVS-SNVUSR',
        'NOUNPS-OTOTPT-VOMNOS-RORRVN',
        'NOUNPS-MTNTPT-NOTNSS-UPTRTR',
        'NOUNSS-PTVTQT-QORNQS-SVUQTP',
        'NOUNPS-STRTQT-VOUNVS-ROOMMT',
        'NOUNUS-STVTST-VOVNQS-OTNQSR',
        'NOUNNS-VTQTVT-ROVNMS-RTTMTT',
        'NOUNVS-TTNTVT-VOVNTS-MMUSQP',
        'NOUNSS-OTOTVT-TORNTS-RMPUTR',
        'NOUNUS-NTRTNT-MOMNQS-NSUTUP',
        'NOUNVS-STUTRT-ROONOS-MUUQRS',
        'NOUNSS-TTSTTT-ROSNRS-SRRRTQ',
        'NOUNRS-MTVTQT-MONNNS-URVQTT',
        'NOUNRS-VTQTRT-NORNVS-QUUOSM',
        'NOUNPS-OTNTTT-POONSS-TVTNSU',
        'NOUNMS-PTTTST-NOONTS-OOSSOU',
        'NOUNRS-OTPTTT-TOVNSS-RTOPVV',
        'NOUNMS-TTOTPT-OONNMS-TPTUVP',
        'NOUNRS-VTVTST-VORNOS-PSOPPS',
        'NOUNTS-RTVTST-VOPNUS-MQQURO',
        'NOUNPS-NTMTUT-NOTNVS-MVPURU',
        'NOUNMS-VTUTNT-VOVNSS-UUOVNQ',
        'NOUNMS-STSTPT-OOONOS-VQVTPT',
        'NOUNRS-PTTTOT-QOVNUS-VNUTUR',
        'NOUNNS-TTVTQT-OOQNNS-SVSROM',
        'NOUNTS-QTSTQT-POONRS-TTOVSS',
        'NOUNMS-UTSTOT-UOTNMS-TUSTUN',
        'NOUNTS-TTRTRT-ROUNQS-VNMMNS',
        'NOUNRS-UTMTQT-TOVNOS-OVMQMQ',
        'NOUNNS-QTRTNT-VONNQS-POOPMV',
        'NOUNSS-UTUTTT-SORNPS-OMSPSQ',
        'NOUNSS-UTMTMT-ROSNUS-MQUTMS',
        'NOUNQS-MTMTTT-NOVNNS-UQNOTU',
        'NOUNRS-RTQTQT-VOPNTS-NOQNUT',
        'NOUNNS-STTTMT-SOPPTS-NQSTMQ',
        'NOUNTS-OTPTST-NORNUS-NOPVOP',
        'NOUNUS-TTTTVT-MOPNQS-VVQOTP',
        'NOUNQS-VTVTST-TORNSS-NRTRMU',
        'NOUNTS-UTRTTT-ROUNUS-NMNVNQ',
        'NOUNPS-RTOTQT-UORNOS-VRNTPU',
        'NOUNQS-UTRTNT-QOONSS-SNMNVN',
        'NOUNQS-UTTTNT-UOSNOS-OTPPUP',
        'NOUNRS-UTPTMT-VONNPS-NSOVVM',
        'NOUNUS-QTQTTT-TORNRS-TSSPRN',
        'NOUNPS-QTUTNT-SOQNNS-TNTVUP',
        'NOUNVS-MTMTOT-OOPNRS-TUQMSM',
        'NOUNPS-UTSTST-VOPNUS-UROOPT',
        'NOUNPS-TTRTVT-OOSNQS-QVVMRT',
        'NOUNPS-UTQTMT-VOSNVS-QUQUVS',
        'NOUNQS-TTVTTT-ROQNQS-MMQVNU',
        'NOUNSS-OTTTVT-VOVNSS-VQURUS',
        'NOUNQS-OTSTMT-ROVNMS-ORQRVR',
        'NOUNRS-QTRTVT-MORNSS-PSMTMQ',
        'NOUNNS-UTOTQT-OOTNNS-OSRQTP',
        'NOUNNS-NTTTOT-MOVNPS-MRSSVP',
        'NOUNSS-PTSTOT-QONNVS-TSMSVP',
        'NOUNSS-TTNTNT-TOVNQS-VMMVNU',
        'NOUNRS-STRTST-POPNTS-MOTVPM',
        'NOUNVS-UTTTVT-NOMNMS-RVMRQN',
        'NOUNNS-PTPTQT-NOUNVS-SQPVRM',
        'NOUNUS-PTUTUT-MORNTS-PVVRTS',
        'NOUNPS-TTUTNT-ROQNMS-PMSRVR',
        'NOUNQS-RTUTMT-MOPNNS-NUPUQV',
        'NOUNVS-VTMTOT-MOVNPS-OVNVQN',
        'NOUNNS-MTTTQT-NOVNRS-NQMNVS',
        'NOUNUS-MTSTMT-SOSNVS-OSRMVN',
        'NOUNQS-NTMTUT-VORNVS-NNOQUS',
        'NOUNUS-VTOTPT-NOSNUS-PUQRNO',
        'NOUNVS-STSTTT-ROONUS-SQTVMP',
        'NOUNQS-TTVTST-SORNUS-VSNVUO',
        'NOUNOS-VTPTTT-SOUNVS-UQMTPM',
        'NOUNNS-UTPTMT-TOSNVS-UTPTQT',
        'NOUNVS-RTTTMT-QOMNNS-PMOMUP',
        'NOUNPS-UTTTRT-TOTNOS-QPVSVQ',
        'NOUNMS-VTOTOT-TOUNMS-VMPPOV',
        'NOUNVS-MTOTOT-UOPNMS-QVOQMT',
        'NOUNMS-UTQTST-TOPNPS-UQMSVO',
        'NOUNVS-UTQTTT-TOTNNS-TQNRQU',
        'NOUNVS-UTVTMT-QOTNTS-RRUSOQ',
        'NOUNPS-STPTTT-VOONVS-USRNPS',
        'NOUNMS-VTOTNT-UORNMS-ROQUUS',
        'NOUNQS-VTSTTT-ROQNOS-NTNQQU',
        'NOUNRS-STQTOT-SOSNMS-UQTTNM',
        'NOUNPS-OTVTST-POPNQS-UQRONQ',
        'NOUNMS-RTVTQT-SOQNVS-SMUOSP',
        'NOUNUS-NTQTTT-POONVS-UOUUMQ',
        'NOUNQS-TTUTRT-NONNNS-SONPVO',
        'NOUNNS-VTRTTT-ROUNMS-PQSTMV',
        'NOUNSS-QTRTUT-UOONPS-TQVTPM',
        'NOUNOS-TTQTRT-MOSNTS-VMSQQU',
        'NOUNTS-QTUTUT-PORNSS-TTRONT',
        'NOUNUS-OTPTMT-POQNNS-NPMVNV',
        'NOUNOS-MTSTMT-MOPNQS-MSTTQP',
        'NOUNNS-OTPTST-VOVNNS-UMVRNU',
        'NOUNSS-UTVTQT-ROONNS-MNSTRU',
        'NOUNVS-VTQTTT-TOPNUS-MQRQPT',
        'NOUNTS-VTQTPT-NOSNTS-QTOVRU',
        'NOUNNS-OTUTTT-OOONVS-RPUPNR',
        'NOUNPS-QTUTMT-TOTNSS-QSUNQO',
        'NOUNMS-STQTRT-VOONPS-OVMOVR',
        'NOUNSS-PTOTRT-QORNSS-NUMTTU',
        'NOUNSS-MTTTST-UOQNSS-UPQVPU',
        'NOUNRS-QTOTQT-MOMNVS-MTMPTR',
        'NOUNSS-OTTTTT-TONNRS-NNNUPP',
        'NOUNPS-TTPTRT-QOMNSS-QMSNQT',
        'NOUNTS-PTSTTT-QOSNRS-PPTUUN',
        'NOUNOS-MTNTQT-NOVNRS-RTQMRT',
        'NOUNUS-RTMTQT-VOTNMS-UPQQRN',
        'NOUNSS-MTSTRT-OOMNPS-VMTNOR',
        'NOUNVS-QTNTVT-UONNPS-USNTMN',
        'NOUNVS-STQTST-NOONVS-SMQMNO',
        'NOUNNS-STMTQT-MORNOS-OSRNUQ',
        'NOUNQS-PTRTMT-ROUNNS-PNNORO',
        'NOUNQS-STMTPT-POSNUS-URVOUS',
        'NOUNRS-PTRTRT-OOMNPS-TNPSRV',
        'NOUNRS-RTVTST-MONNQS-TUUTNR',
        'NOUNQS-VTMTTT-NORNUS-RMQQQR',
        'NOUNMS-OTOTUT-VOSNUS-PQMTMP',
        'NOUNOS-TTNTST-SOONOS-SSSOSS',
        'NOUNTS-UTVTPT-MOUNMS-TQNPTN',
        'NOUNMS-NTQTQT-QOONOS-UNNPSM',
        'NOUNUS-TTQTUT-SOQNRS-OTPVUR',
        'NOUNTS-PTRTVT-SONNPS-VMNPVM',
        'NOUNQS-QTSTQT-VOVNRS-PSVTTT',
        'NOUNUS-STOTQT-OONNUS-NURQNQ',
        'NOUNPS-MTMTTT-NOUNSS-UOSPOM',
        'NOUNSS-PTSTST-VOPNMS-QOOVVP',
        'NOUNNS-VTSTST-POTNPS-QNMTRS',
        'NOUNPS-RTTTVT-UONNPS-NUMSTO',
        'NOUNPS-QTQTMT-POQNSS-SQNTTP',
        'NOUNMS-QTQTQT-MOONUS-PMPNTM',
        'NOUNSS-TTVTTT-TOUNSS-VNPSPS',
        'NOUNQS-MTMTVT-PONNNS-SPMTPN',
        'NOUNUS-QTNTQT-UOONOS-VOSSVR',
        'NOUNSS-PTTTTT-QOQNOS-TOPVQO',
        'NOUNTS-QTSTUT-ROPNMS-SNSROT',
        'NOUNPS-TTMTOT-NOQNOS-OQNQTN',
        'NOUNMS-PTQTNT-TOVNVS-MPNOOR',
        'NOUNUS-RTOTUT-TOPNMS-SQQVVV',
        'NOUNPS-VTSTMT-SOMNPS-TQSSTN',
        'NOUNUS-QTVTST-OOVNNS-MVNUST',
        'NOUNSS-NTVTRT-QONNQS-UNVQRN',
        'NOUNVS-OTOTTT-MONNSS-QPVRQS',
        'NOUNTS-OTTTVT-UOMNRS-TMQOUP',
        'NOUNUS-VTTTNT-ROONQS-QSMONP',
        'NOUNOS-QTTTOT-MOMNNS-NOTRRM',
        'NOUNTS-QTOTNT-TONNMS-RTPRTP',
        'NOUNUS-UTPTTT-TOQNNS-MMNPOU',
        'NOUNPS-STVTVT-ROVNRS-RSMOUN',
        'NOUNSS-STSTUT-PONNUS-VRUPOV',
        'NOUNMS-STTTST-UONNQS-PQMQSP',
        'NOUNPS-TTUTOT-OOPNUS-TTSTNR',
        'NOUNVS-PTTTMT-TOMNSS-PVOOTV',
        'NOUNSS-PTPTQT-QOSNUS-URSSOR',
        'NOUNUS-STMTVT-MOONQS-UUTMNT',
        'NOUNTS-VTRTMT-SOUNPS-TTRPVP',
        'NOUNVS-TTQTMT-VOSNRS-VRQVPQ',
        'NOUNQS-STVTTT-ROVNSS-SSOTVM',
        'NOUNPS-PTVTMT-QORNQS-UMSTOR',
        'NOUNOS-PTQTST-QOQNSS-TRPVVS',
        'NOUNSS-QTTTMT-UOQNVS-QPTUOS',
        'NOUNMS-PTMTTT-MOUNNS-VVTUTP',
        'NOUNSS-MTSTNT-NOONTS-TRPORV',
        'NOUNVS-UTVTQT-RORNUS-PRTMPM',
        'NOUNOS-PTNTRT-UOQNRS-NSMOPQ',
        'NOUNNS-NTUTOT-MOPNVS-RQUTVN',
        'NOUNOS-VTVTNT-QOPNVS-NVQPPN',
        'NOUNOS-MTSTPT-SOTNOS-USNSVM',
        'NOUNRS-MTTTOT-MOMNNS-UNURTU',
        'NOUNMS-QTVTOT-NOMNRS-OMTTNO',
        'NOUNSS-NTPTTT-POUNUS-TSSRPT',
        'NOUNUS-QTNTTT-NOMNUS-SOVRPU',
        'NOUNTS-MTUTOT-SORNPS-UTNNQS',
        'NOUNTS-RTSTPT-POVNSS-VRTQRP',
        'NOUNSS-NTRTRT-VOPNRS-RVSTRM',
        'NOUNMS-STRTNT-TOSNVS-RTMSVR',
        'NOUNQS-UTOTTT-ROSNRS-TOMVSR',
        'NOUNOS-STMTNT-OOQNMS-SORROV',
        'NOUNMS-PTQTPT-QOUNUS-RVOQRV',
        'NOUNTS-NTSTVT-UONNVS-MOQMNO',
        'NOUNQS-QTOTUT-POPNRS-RPNRUR',
        'NOUNPS-TTTTUT-TOPNSS-NPPPNS',
        'NOUNOS-QTVTVT-NORNSS-RPQVPQ',
        'NOUNVS-MTUTRT-POQNSS-PPMNSO',
        'NOUNSS-TTTTST-POMNSS-OMPMMQ',
        'NOUNQS-MTUTRT-OOPNSS-QTPTOR',
        'NOUNVS-PTUTPT-TORNUS-VPTRVM',
        'NOUNQS-STQTPT-ROPNRS-MTRQSS',
        'NOUNTS-QTUTRT-QOPNUS-OPVSSV',
        'NOUNOS-PTNTST-MOVNPS-OQTOVO',
        'NOUNNS-STQTOT-MOSNSS-NQUSSQ',
        'NOUNQS-RTNTQT-SONNSS-RUUVTU',
        'NOUNVS-QTSTOT-PORNQS-NQVOVO',
        'NOUNVS-PTVTTT-QOPNSS-MVNVMQ',
        'NOUNRS-STPTST-SOMNSS-RPSPTM',
        'NOUNOS-PTNTPT-NOSNSS-RQOMSP',
        'NOUNNS-PTUTRT-UOMNRS-UUOVOP',
        'NOUNPS-TTRTTT-NOQNVS-QMTNVN',
        'NOUNQS-OTRTQT-VOPNMS-VOMRPM',
        'NOUNUS-NTNTUT-VOQNNS-USRTVN',
        'NOUNUS-STRTTT-POQNQS-NSVOQP',
        'NOUNMS-MTQTPT-POVNRS-MPTNPO',
        'NOUNNS-MTNTVT-ROSNPS-RNOMNU',
        'NOUNSS-QTRTRT-SOTNQS-VUQVPQ',
        'NOUNVS-UTMTRT-MOSNRS-SOMURU',
        'NOUNVS-MTOTTT-UOVNMS-SPTQTS',
        'NOUNRS-UTNTST-VONNOS-OTUMQT',
        'NOUNTS-QTUTRT-NOTNUS-OUVTQQ',
        'NOUNRS-RTTTUT-SOONQS-NTRRNM',
        'NOUNRS-PTPTMT-OOMNSS-QOROOU',
        'NOUNVS-UTSTQT-MOMNRS-NRTRTN',
        'NOUNOS-VTMTMT-OOPNQS-NOVPUT',
        'NOUNVS-PTVTRT-UOUNSS-MSQQPV',
        'NOUNRS-TTRTUT-VOQNMS-OPQQVQ',
        'NOUNPS-VTPTRT-MOMNRS-QQMRNP',
        'NOUNMS-NTTTUT-UONNRS-VVMNVO',
        'NOUNUS-TTPTMT-MOVNRS-VUTNRS',
        'NOUNMS-PTNTST-POVNSS-NTSRQR',
        'NOUNNS-VTSTUT-SOVNUS-VQNQPO',
        'NOUNSS-NTOTST-SOQNSS-VVRNON',
        'NOUNTS-OTOTST-QOUNVS-PNSMOQ',
        'NOUNMS-NTRTST-POUNSS-UVOSUS',
        'NOUNRS-VTOTPT-POPNMS-QPQQNR',
        'NOUNQS-STTTRT-UOVNVS-SSOVMR',
        'NOUNOS-TTVTQT-MOSNUS-UNVTNS',
        'NOUNSS-VTVTVT-POQNUS-NTTQQR',
        'NOUNSS-STVTOT-SORNVS-MVSNTM',
        'NOUNTS-MTMTOT-MOONRS-RQPOOS',
        'NOUNPS-UTNTQT-PORNPS-VNOSPO',
        'NOUNSS-QTRTQT-SOUNMS-VOUOQO',
        'NOUNPS-QTRTPT-SORNUS-QUPOQV',
        'NOUNQS-UTMTQT-NOSNVS-MUNNNT',
        'NOUNUS-VTOTMT-SOMNSS-VVVUPP',
        'NOUNRS-NTQTPT-SONNMS-NMTUQQ',
        'NOUNOS-MTTTMT-TORNQS-VMNSQV',
        'NOUNMS-QTMTTT-TONNNS-RMQVRT',
        'NOUNQS-TTMTQT-TOSNUS-SQUTSN',
        'NOUNOS-UTTTVT-PORNNS-OORVUR',
        'NOUNVS-STQTUT-QOUNNS-UMVQTO',
        'NOUNQS-UTPTMT-TORNPS-TVRRNN',
        'NOUNUS-RTRTTT-UOUNUS-VUOSPN',
        'NOUNUS-TTOTST-SOTNPS-VTRMPT',
        'NOUNRS-RTSTQT-VOMNMS-MPPTRN',
        'NOUNRS-MTMTPT-SOSNMS-UOOMMS',
        'NOUNOS-VTSTPT-TOTNRS-PPSVPT',
        'NOUNVS-TTRTNT-UONNQS-OVQQTR',
        'NOUNVS-TTUTRT-OOVNRS-MUNVOV',
        'NOUNOS-UTUTST-OOQNTS-QNNUPU',
        'NOUNVS-NTMTVT-UONNMS-NVNTRR',
        'NOUNNS-VTNTVT-VOONTS-NOMOOV',
        'NOUNRS-QTSTOT-ROTNQS-SSRUQS',
        'NOUNVS-TTPTOT-SOVNQS-MSRUTM',
        'NOUNNS-QTUTVT-QOMNPS-TQQVRO',
        'NOUNVS-QTOTQT-TORNMS-USSQVU',
        'NOUNMS-STNTRT-UOONNS-NOVUUR',
        'NOUNRS-PTQTUT-NOQNTS-SNMQOV',
        'NOUNSS-NTMTQT-SOVNPS-RQPVNR',
        'NOUNSS-TTOTNT-NOPNQS-UTNTQR',
        'NOUNRS-RTUTVT-TONNNS-QPNQRT',
        'NOUNPS-TTOTVT-UOTNPS-MVTUNU',
        'NOUNQS-TTOTQT-TOTNPS-SVSQQT',
        'NOUNSS-TTMTVT-UOMNSS-ONQQPQ',
        'NOUNRS-OTOTVT-POTNUS-SURQSV',
        'NOUNSS-TTTTPT-UOTNTS-RMPPVV',
        'NOUNOS-TTPTNT-VOMNTS-ONMTSV',
        'NOUNSS-QTRTMT-NORNSS-SMSSRQ',
        'NOUNPS-PTRTQT-QOONNS-NQPPRO',
        'NOUNSS-RTUTMT-ROQNVS-TMPOPS',
        'NOUNUS-STVTRT-TOOPMS-QMOOVM',
        'NOUNRS-PTRTOT-NOTNNS-RPRSUR',
        'NOUNUS-STVTVT-ROSNTS-UUQVMP',
        'NOUNOS-RTNTMT-SOVNUS-TSSQOM',
        'NOUNQS-PTQTTT-TOTOVS-UPOMPO',
        'NOUNQS-QTMTMT-UOONRS-UMOUTO',
        'NOUNUS-NTRTOT-SOUNQS-SQNPNP',
        'NOUNNS-PTRTVT-UONNNS-SNOMMN',
        'NOUNOS-NTNTRT-OOPOOS-RPPORV',
        'NOUNPS-RTUTUT-QOSORS-PVRUSR',
        'NOUNUS-TTUTRT-NOPORS-ONONQO',
        'NOUNTS-RTNTUT-UOMOPS-RPORQS',
        'NOUNOS-MTNTVT-ROMNPS-VURRRS',
        'NOUNRS-VTNTNT-ROVNVS-STTSSR');

    // Invalid License Array - Any license in this array will be deemed invalid
    // even if they pass the validation function.
    private $invalid_licenses = array('NOUNNS-PTQTUT-MOTNTS-TROQQS',
        'NOUNSS-PTRTNT-TOONRS-NNRROT', // Old demo license key
        'NOUNTS-VTSTNT-QONTSS-NPQVUV', // Avaya - OEM - GA / OA 1.5 License */
        'NOUNTS-PTRTQT-TOSTTS-OROVUR', // Avaya - OEM */
        'NOUNUS-STNTST-VOQTSS-SOQOSM' //Avaya - OEM - Test Lab License */
    );

    // Demo License Array - These license are only "activated" when they are
    // in demo mode, if they are in normal mode it will show inactive
    private $demo_licenses = array ('NOUNSS-PTRTNT-TOONRS-NNRROT', 'NOUNRS-TTPTMT-MOONUS-OVTPMM');

    /**
     * Checks to see if a license key is in the NFR license list.
     *
     * @param   string  $key    License key
     * @return  bool            True if NFR license key
     */
    public function is_nfr_license($key = null)
    {
        if ($key != null && in_array($key, $this->nfr_licenses)) {
            return true;
        }
        return false;
    }

    /**
     * Checks to see if a license key is in the invalid license list
     *
     * @param   string  $key    License key
     * @return  bool            True if invalid license key
     */
    public function is_invalid_license($key = null)
    {
        if ($key != null && in_array($key, $this->invalid_licenses)) {
            return true;
        }
        return false;
    }

    /**
     * Checks if license key is in the demo license list
     *
     * @param   string  $key    License key
     * @return  bool            True if invalid license key
     */
    public function is_demo_license($key = null)
    {
        if ($key != null && in_array($key, $this->demo_licenses)) {
            return true;
        }
        return false;
    }

}


// This class does license functions that are not supposed to be able
// to be called from anywhere in the code besides utilsl
class LHelper extends LData
{

    /**
     * Set the amount of trial days to $days
     *
     * @param   int     $days   The amount of days the trial should be
     * @param   string  $key    The trial extension key (optional)
     * @return  bool            True if days set
     */
    public function set_trial_days($days=30, $key='')
    {
        $start_day = $days - TRIAL_DAYS;
        $start_day_ts = $start_day * SECONDS_IN_DAY;

        // Because the trial is hardcoded to 30 days, we have to set the
        // trial start date to 30 days from when we want it to end, which
        // means that trials < 30 days will set a timestamp in the past
        $ts = time() + $start_day_ts;

        // Verify that the trial extension timestamp is valid
        if ($ts < time()) {
            return false;
        }

        return $this->set_trial_start($ts, $key);
    }

    /**
     * Set the actual start date timestamp of the trial
     *
     * @param   int     $days   The amount of days the trial should be
     * @param   string  $key    The trial extension key (optional)
     * @return  bool            True if days set
     */
    public function set_trial_start($ts=null, $key='')
    {
        // If the extension key is over MAX_TRIAL_DAYS, set to max
        $max_trial_days = MAX_TRIAL_DAYS - TRIAL_DAYS;
        $max = time() + ($max_trial_days * SECONDS_IN_DAY);
        if ($ts > $max) {
            $ts = $max;
        }

        // Set the start date timestamp for trial
        $e = new LEncrypt('39G+Nk');
        $homi = array('ts' => $ts, 'uuid' => get_product_uuid());
        $homi = base64_encode($e->encrypt(json_encode($homi)));
        set_option("subsystem_homi", $homi);

        // Set enterprise features trial and reset maintenance
        set_option("elkts", $ts);
        set_option("lmd", "");

        // Update trial extension amount
        $trial_extensions = intval(get_option("trial_extensions"));
        set_option("trial_extensions", $trial_extensions + 1);

        // Set the trial extension date
        set_option("trial_key", $key);
        set_option("trial_extension_date", time());

        return true;
    }

}


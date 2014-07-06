<?php
/**
 * Verbose, don't use in production
 */
// ini_set('display_errors', 1);
// error_reporting(-1);

/**
 * Full wget binary path, if not found automatic by web server running user,
 * you can test with
 * # wget -V
 * If not found, run
 * # which wget
 */
$wget = 'wget';
// $wget = '/usr/bin/wget';

/**
 * Additional options for wget, here some useful settings,
 * see manual for full reference:
 * http://www.gnu.org/software/wget/manual/wget.html
 */
$wget_options =
    // Disable server-side cache. In this case, Wget will send the remote server
    // an appropriate directive (‘Pragma: no-cache’) to get the file from the
    // remote service, rather than returning the cached version.
    '--no-cache '.
    // Identify as agent-string to the HTTP server. The HTTP protocol allows
    // the clients to identify themselves using a User-Agent header field.
    // This enables distinguishing the WWW software, usually for statistical
    // purposes or for tracing of protocol violations. Wget normally identifies
    // as ‘Wget/version’, version being the current version number of Wget.
    '--user-agent="Mozilla/5.0 (Windows NT 6.1; rv:24.0) Gecko/20140216 Firefox/24.0"';

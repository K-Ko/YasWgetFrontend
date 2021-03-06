<?php
/**
 *
 *
 * @author     Knut Kohl <github@knutkohl.de>
 * @copyright  (c) 2014 Knut Kohl
 * @licence    MIT License - http://opensource.org/licenses/MIT
 * @version    1.0.0
 */

define('APPNAME',    'Yet another simple Wget Frontend');
define('APPSHORT',   'YasWF');

ini_set('display_errors', 0);
error_reporting(0);

define('DS', DIRECTORY_SEPARATOR);
define('APPVERSION', trim(file_get_contents(__DIR__.DS.'.version')));

/**
 * Definitions
 */
// Make hidden server unique directory name to store files
define('F_DIR',      '.f'.substr(md5(__DIR__), -11));
define('FILES_DIR',  __DIR__.DS.F_DIR);
define('TEMP_EXT',   '.~wget');
define('LOG_DIR',    FILES_DIR.DS.'.log');

/**
 * Check directories
 */
is_writable(FILES_DIR) || mkdir(FILES_DIR, 0700);
is_writable(LOG_DIR)   || mkdir(LOG_DIR,  0700);

if (!is_writable(FILES_DIR)) {
    die('Can\'t create/write to "'.FILES_DIR.'", please check permissions.');
}

/**
 * Load settings
 */
require 'config'.DS.'config.default.php';
// Custom settings?!
$local = 'config'.DS.'config.local.php';
if (file_exists($local)) require $local;

session_start();

exec('ps ax | grep wget | grep "[/]'.F_DIR.'"', $processes);

if (!count($processes)) $_POST['off'] = TRUE;

$files = glob(LOG_DIR.DS.'*');

// Purge orphan log files
foreach ($files as $id=>$file) {
    if (!(file_exists(FILES_DIR.DS.basename($file)) OR file_exists(FILES_DIR.DS.basename($file).TEMP_EXT))) {
        unlink($file);
        unset($files[$id]);
    }
}
// Resort files list for odd/even table rows
sort($files);

if (isset($_POST['off'])) {
    $_SESSION['refresh'] = FALSE;
} elseif (array_key_exists('refresh', $_POST)) {
    $_SESSION['refresh'] = is_numeric($_POST['refresh']) ? $_POST['refresh'] : FALSE;
}
unset($_POST['refresh'], $_POST['off']);

if (!array_key_exists('refresh', $_SESSION)) $_SESSION['refresh'] = FALSE;

if (isset($_GET['get'])) {
    // Download file
    $file = FILES_DIR.DS.$_GET['get'];
    if (file_exists($file)) {
        // Headers for direct download
        Header('Content-Description: File Transfer');
        Header('Content-Type: application/octet-stream');
        Header('Content-Disposition: attachment; filename="'.basename($file).'"');
        Header('Content-Transfer-Encoding: binary');
        Header('Connection: Keep-Alive');
        Header('Expires: 0');
        Header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        Header('Pragma: public');
        Header('Content-Length: '.filesize($file));
        readfile($file);
        exit;
    } else {
        die('File "'.$_GET['get'].'" unknown!');
    }

} elseif (!empty($_POST)) {

    if (isset($_POST['kill']) AND isset($_POST['confirmed']) AND $_POST['confirmed'] == 'X') {
        // Stop wget process

        exec('kill '.$_POST['kill']);

    } elseif (isset($_POST['delete']) AND isset($_POST['confirmed']) AND $_POST['confirmed'] == 'X') {
        // Delete file (and log)

        $file = FILES_DIR.DS.$_POST['delete'];
        file_exists($file) && unlink($file);

        $file = FILES_DIR.DS.$_POST['delete'].TEMP_EXT;
        file_exists($file) && unlink($file);

    } elseif (!empty($_POST['url'])) {
        // Add new file to queue

        if (!empty($_POST['name'])) {
            $name = $_POST['name'];
        } else {
            $parsed = parse_url($_POST['url']);
            $name = basename(preg_replace('~\?.*~', '', $parsed['path']));
        }

        $name = urldecode($name);

        // Make file name file system save
        $name = preg_replace('~[\s()]+~', '_', $name);

        $file = escapeshellarg(FILES_DIR.DS.$name);
        $log  = escapeshellarg(LOG_DIR.DS.$name);
        $url  = escapeshellarg($_POST['url']);

        $cmd  = $config['wget'].' '.implode(' ', $config['wget_options'])
              . (!empty($_POST['limit']) ? ' --limit-rate='.$_POST['limit'] : '')
              // Default setting always needed:
              . ' --background --random-wait --progress=bar:force:noscroll '
              // Files and URL
              . ' -O '.$file.TEMP_EXT.' -a '.$log.' '.$url;

        // Init log file before with command
        exec('(echo '.escapeshellarg('# '.$cmd).'; echo) >'.$log. '; '.$cmd);
    }

    // Always reload page after POST
    Header('Location: '.$_SERVER['DOCUMENT_URI']);
    exit;
}

include 'tpl' . DS . 'head.tpl.php';
include 'tpl' . DS . 'header.tpl.php';

/**
 * Active downloads
 */

if (!empty($processes)) {
    echo '
<fieldset>
    <legend>Active downloads</legend>
    <table class="list">
    <tbody>
';

    foreach ($processes as $id=>$process) {
        if (preg_match('~^\s*(\d+).* ([^\s]+)$~', $process, $args)) {
            $url = $url1 = $args[2];
            if (strlen($url1) > 100) {
                $url1 = parse_url($url1);
                $query = explode('&', $url1['query']);
                $query = count($query) <= 2
                       ? $url1['query']
                       : $query = $query[0].'&...&'.$query[count($query)-1];
                $url1 = $url1['scheme'].'://'.$url1['host'].$url1['path'].'?'.$query;
            }
            echo '<tr class="'.($id&1?'odd':'even').'">
                  <td style="width:1%;padding-left:5px">
                      <form method="post" onsubmit="return confirm(\'Stop download.\n\nSure?\')">
                      <input type="hidden" name="kill" value="'.$args[1].'">
                      <input type="submit" name="confirmed" value="X" title="Stop process">
                      </form>
                  </td>
                  <td>
                       <pre title="'.$url.'">'.$url1.'</pre>
                  </td>
                  </tr>';
        }
    }
    echo '
    </tbody>
    </table>
</fieldset>';
}

if (!empty($files)) {

  echo '
<!-- Files list -->
<fieldset>
    <legend>Files</legend>

    <table class="list">
    <tbody>';

    // Files table row template
    $tr = '
    <tr class="%5$s">
        <td style="width:1%%;padding-left:5px">
            <form method="post" onsubmit="return confirm(\'Delete %1$s\n\nSure?\')">
                <input type="hidden" name="delete" value="%1$s">
                <input type="submit" name="confirmed" value="X" title="Delete file and log">
            </form>
        </td>
        <td><pre>%2$s</pre></td>
        <td style="width:60%%"><pre data-hint="%4$s">%3$s</pre></td>
    </tr>
';
    // http://snipplr.com/view/4633/convert-size-in-kb-mb-gb-/
    $filesizename = array(' Bytes', ' KB', ' MB', ' GB', ' TB', ' PB', ' EB', ' ZB', ' YB');
    $filesizedec  = array(       0,     1,     2,     3,     3,     3,     3,     3,     3);

    foreach ($files as $id=>$file) {
        $a = basename($file);

        unset($log, $saved, $logHint);
        // Replace all carriage returns with new lines
        // Files/streams without delivered size have a [  <=>  ] progress bar
        exec('sed -e "s~\r~\n~g" '.$file.' | grep -e "%\|<=>" | tail -n 1 ', $log);
        exec('sed -e "s~\r~\n~g" '.$file.' | grep " saved "', $saved);
        exec('sed -e "s~\r~\n~g" '.$file, $logHint);

        ## echo '<pre>',implode("\n", $logHint),'</pre>';

        if (count($logHint) > 30) {
            array_splice($logHint, 20, count($logHint)-30, '. . .');
        }
        $logHint = str_replace('"', '&quot;', implode('<br/>', $logHint));

        if (!isset($log[0])) {
            $log = '[ Starting ]';
        } else {
            $log = preg_replace('~^.*?([\d,.]+%\s*)~', '$1 ', $log[0]);
            if (strstr($log, '100%') OR count($saved)) {
                $dl = FILES_DIR.DS.basename($file).TEMP_EXT;
                if (file_exists($dl)) rename($dl, FILES_DIR.DS.basename($file));
                $a = sprintf('<a href="'.$_SERVER['DOCUMENT_URI'].'?get=%1$s" title="Download">%2$s</a>', urlencode(basename($file)), basename($file));
                // Show downloaded file size
                $size = sprintf('%u', filesize(FILES_DIR.DS.basename($file)));
                $log = $size ? round($size/pow(1024, ($i=floor(log($size, 1024)))), $filesizedec[$i]) . $filesizename[$i] : '0 Bytes';
                $log = sprintf('%78s', $log);
                $enabled = 1;
            }
        }
        printf($tr, urlencode(basename($file)), $a, $log, $logHint, $id&1?'odd':'even');
    }

    echo '
    </tbody></table>

</fieldset>
';

}

include 'tpl' . DS . 'add.tpl.php';
include 'tpl' . DS . 'footer.tpl.php';

// Some statistics
printf(
    PHP_EOL.PHP_EOL.'<!-- time: %d ms, memory: %d kByte (max. %d kByte) -->',
    (microtime(TRUE)-$_SERVER['REQUEST_TIME'])*1000,
    memory_get_usage() / 1024,
    memory_get_peak_usage() / 1024
);


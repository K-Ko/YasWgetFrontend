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
define('APPVERSION', '1.3.0');

ini_set('display_errors', 0);
error_reporting(0);
ini_set('display_errors', 1);
error_reporting(-1);
define('DS', DIRECTORY_SEPARATOR);

/**
 * Load settings
 */
include 'config'.DS.'config.default.php';
// Custom settings?!
if (file_exists('config'.DS.'config.php')) include 'config'.DS.'config.local.php';

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
            // Make file name file system save
            $name = str_replace(' ', '_', $_POST['name']);
        } else {
            $parsed = parse_url($_POST['url']);
            $name = basename($parsed['path']);
        }

        $file  = escapeshellarg(FILES_DIR.DS.$name);
        $log   = escapeshellarg(LOG_DIR.DS.$name);
        $url   = escapeshellarg($_POST['url']);

        $cmd   = $config['wget'].' '.implode(' ', $config['wget_options'])
               . (!empty($_POST['limit']) ? ' --limit-rate='.$_POST['limit'] : '')
                 // Default setting always needed:
               . ' --background --random-wait --progress=bar:force '
                 // Files and URL
               . ' -O '.$file.TEMP_EXT.' -o '.$log.' '.$url;

        #echo '<p>Run: '.$cmd.'</p><hr />';
        #exit;
        exec($cmd);
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
            echo '<tr class="'.($id&1?'odd':'even').'">
                  <td style="width:1%;padding-left:5px">
                      <form method="post" onsubmit="return confirm(\'Stop download.\n\nSure?\')">
                      <input type="hidden" name="kill" value="'.$args[1].'">
                      <input type="submit" name="confirmed" value="X" title="Stop process">
                      </form>
                  </td>
                  <td>
                       <pre>'.$args[2].'</pre>
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
        <td style="width:60%%"><pre title="%4$s">%3$s</pre></td>
    </tr>
';
    // http://snipplr.com/view/4633/convert-size-in-kb-mb-gb-/
    $filesizename = array(' Bytes', ' KB', ' MB', ' GB', ' TB', ' PB', ' EB', ' ZB', ' YB');
    $filesizedec  = array(0,        1,     2,     3,     3,     3,     3,     3,     3);

    foreach ($files as $id=>$file) {
        $a = basename($file);

        unset($log);
        // Replace all carriage returns with new lines
        exec('sed -e "s~\r~\n~g" '.$file.' | grep % | tail -n 1 ', $log);
        exec('sed -e "s~\r~\n~g" '.$file.' | head -n 20', $logHint);

        $logHint = implode("\n", $logHint);

        if (!isset($log[0])) {
            $log = 'Starting ...';
        } else {
            $log = $log[0];
            if (strstr($log, '100%')) {
                $dl = FILES_DIR.DS.basename($file).TEMP_EXT;
                if (file_exists($dl)) rename($dl, FILES_DIR.DS.basename($file));
                $a = sprintf('<a href="'.$_SERVER['DOCUMENT_URI'].'?get=%1$s" title="Download">%2$s</a>', urlencode(basename($file)), basename($file));
                // Show downloaded file size
                $size = sprintf('%u', filesize(FILES_DIR.DS.basename($file)));
                $log = $size ? round($size/pow(1024, ($i=floor(log($size, 1024)))), $filesizedec[$i]) . $filesizename[$i] : '0 Bytes';
                $logHint = '';
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


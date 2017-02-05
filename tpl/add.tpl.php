<?php if (!defined('APPNAME')) return; ?>

<!-- Add file form -->
<fieldset>
    <legend>Add file to queue</legend>

    <form method="post">

        <table id="add">
        <tbody>
            <tr>
                <td class="col1">URL</td>
                <td class="col1">:</td>
                <td><input type="text" name="url" style="width:98%" required="required" placeholder="http://..."></td>
            </tr>
            <tr>
                <td class="col1">Target file name</td>
                <td class="col1">:</td>
                <td><input type="text" name="name" size="60" placeholder="optional, get from URL by default"></td>
            </tr>
            <tr>
                <td class="col1">Download speed limit</td>
                <td class="col1">:</td>
                <td>
                  <input type="text" name="limit" size="5" placeholder="100k" style="margin-right:1em" value="<?php echo $config['bandwidth']; ?>">
                    <small>(Amount may be expressed in bytes, kilobytes with the <strong>k</strong> suffix, or megabytes with the <strong>m</strong> suffix.)</small>
                </td>
            </tr>
            <tr>
                <td colspan="2"></td>
              <td><input style="width:10em" type="submit" value="Start"></td>
            </tr>
        </tbody>
        </table>

    </form>
</fieldset>

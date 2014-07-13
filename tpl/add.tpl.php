<?php if (!defined('APPNAME')) return; ?>

<!-- Add file form -->
<fieldset>
    <legend>Add file to queue</legend>

    <form method="post">

        <table id="add">
        <tbody>
            <tr>
                <td>URL: </td>
                <td><input type="text" name="url" size="120" required="required" placeholder="http://..."></td>
                <td></td>
            </tr>
            <tr>
                <td>Target file name:</td>
                <td><input type="text" name="name" size="60" placeholder="optional, get from URL by default"></td>
                <td></td>
            </tr>
            <tr>
                <td>Limit download speed:</td>
                <td>
                  <input type="text" name="limit" size="5" placeholder="100k" style="margin-right:1em">
                    <small>(Amount may be expressed in bytes, kilobytes with the <strong>k</strong> suffix, or megabytes with the <strong>m</strong> suffix.)</small>
                </td>
                <td></td>
            </tr>
            <tr>
                <td></td>
                <td><input type="submit" value="       Start       "></td>
                <td></td>
            </tr>
        </tbody>
        </table>

    </form>
</fieldset>


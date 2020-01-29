<form id="wikiloadversionform" action="/wiki/viewhistoryversion/<?php echo $wiki->name ?>/<?php echo $page->name ?>/<?php echo $history->id ?>#edit" method="POST" target="_self" class=" small-12 columns">
    <input type="hidden" name="wikieeditform" value="9d23d65bae7144">

    <div id="view">
        <input class="button tiny" type='submit' value='Load this version' />&nbsp;&nbsp;<input class="button tiny" type='submit' value='Cancel' onclick="window.history.back()">
        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<b>Preview as at <?php echo formatDateTime($history->dt_created) ?></b>
        <ul class="breadcrumbs">
            <li <?php echo ($page->name === "HomePage" ? "class='current'" : ""); ?>>
                <a href="<?php echo htmlentities(WEBROOT . "/wiki/view/" . $wiki->name . "/HomePage"); ?>">Home</a>
            </li>
            <?php
            if (array_key_exists('wikicrumbs', $_SESSION) and array_key_exists($wiki->name, $_SESSION['wikicrumbs'])) { // $_SESSION['wikicrumbs'][$wiki->name]) {
                foreach (array_keys($_SESSION['wikicrumbs'][$wiki->name]) as $pn) : ?>
                    <li <?php echo ($page->name === "HomePage" ? "class='current'" : ""); ?>>
                        <a href="<?php echo htmlentities(WEBROOT . "/wiki/view/{$wiki->name}/{$pn}"); ?>"><?php echo $pn; ?></a>
                    </li>
            <?php endforeach;
            }
            ?>
        </ul>
        <div id="viewbody">
            <?php echo $body ?>
        </div>
        <hr />
    </div>
</form>
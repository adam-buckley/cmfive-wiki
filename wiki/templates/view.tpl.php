<form id="wikieditform" action="/wiki/edit/<?php echo $wiki->name ?>/<?php echo $page->name ?>#edit" method="POST" target="_self" class=" small-12 columns">
<input type="hidden" name="wikieeditform" value="9d23d65bae7144">

	<div class="tabs">
		<div>
			<div class="tab-head">
				<a href="#view">View</a>
				<a href="#wiki-history">Wiki History</a>
				<a href="#page-history">Page History</a>
				<?php if ($wiki->canEdit($w->Auth->user())):?>
					<a href="#edit">Edit</a>
				<?php endif; ?>
				<?php if ($wiki->isOwner($w->Auth->user()) && $page->name == "HomePage"):?>
					<a href="#members">Members</a>
				<?php endif; ?>
				<?php if ($w->Auth->hasRole('comment')):?>
				<a href="#comments">Comments</a>
				<?php endif; ?>
				<?php if ($w->Auth->hasRole('file_upload') && $w->Auth->hasRole('file_download')):?>
				<a href="#attachments">Attachments</a>
				<?php endif; ?>
				<?php 
					$tab_headers= $w->callHook('core_template', 'tab_headers', $page);  
					if (!empty($tab_headers)) {
						echo implode('', $tab_headers);
					}
				?>
				<!--span style="float:right;"><button class="button tiny " onclick="modal_history.push(&quot;/wiki/markup?isbox=1&quot;); $(&quot;#cmfive-modal&quot;).foundation(&quot;reveal&quot;, &quot;open&quot;, &quot;/wiki/markup?isbox=1&quot;);return false;">Markup Help</button></span-->
				<span id="wikibuttons" style="float:right; display: none;" ><button class="button tiny button savebutton" type="submit">Save</button><button class="button tiny tiny button cancelbutton" style="margin-right: 2em;" type="button" onclick="if($('#cmfive-modal').is(':visible')){ $('#cmfive-modal').foundation('reveal', 'close'); } else { window.history.back(); }">Cancel</button></span>
				
				<span id="wikiautosavebuttons" style="float:right; display: none;" ><button class="button tiny tiny button savedbutton" disabled="true" type="submit">Saved</button><button class="button tiny tiny button savebutton" disabled="true" type="submit">Saving</button></span>

				<?php echo $w->partial('listTags',['object' => $page], 'tag'); ?>
				<?php echo $w->Favorite->getFavoriteButton($page);?>
                        

			</div>
			
		</div>
		
		<div class="tab-body">
		
			<div id="view">
				<ul class="breadcrumbs">
					<li <?php echo ($page->name === "HomePage" ? "class='current'" : ""); ?>>
						<a href="<?php echo htmlentities(WEBROOT."/wiki/view/".$wiki->name."/HomePage"); ?>">Home</a>
					</li>
					<?php 
						if (array_key_exists('wikicrumbs', $_SESSION) and array_key_exists($wiki->name, $_SESSION['wikicrumbs'])) { // $_SESSION['wikicrumbs'][$wiki->name]) {
							foreach(array_keys($_SESSION['wikicrumbs'][$wiki->name]) as $pn) : ?>
								<li <?php echo ($page->name === "HomePage" ? "class='current'" : ""); ?>>
									<a href="<?php echo htmlentities(WEBROOT . "/wiki/view/{$wiki->name}/{$pn}"); ?>"><?php echo $pn; ?></a>
								</li>
							<?php endforeach;
						}
					?> 
				</ul>
				<div id="viewbody" >
					<?php echo $body?>
				</div>
				<hr/>
				<div id="viewattachments">
				<?php echo $w->partial("listattachmentsplain", array("object" => $page, "redirect" => "wiki/view/{$wiki->name}/{$page->name}#attachments"), "file"); ?>
				</div>
				<script>
					$(document).ready(function() {
						$('#viewattachments button').remove();
					});
					</script>
			</div>
			
			
			<div id="wiki-history">
				<?php 
				
				
				$table = array();
				if (!empty($wiki_hist)){
						$table[] = array("Date", "Page", "User");
						foreach($wiki_hist as $wh) {
							$table[]=array(
								formatDateTime($wh["dt_created"]),
								Html::a(WEBROOT."/wiki/viewhistoryversion/".$wiki->name."/".$wh['name']."/".$wh['id'],"<b>".$wh['name']."</b>"),
								$w->Auth->getUser($wh['creator_id'])->getFullName()
							);
						}
						echo Html::table($table,"history","tablesorter",true);
				} else {
						echo "No changes yet.";
				}
				?>
			</div>
			
			
			<div id="page-history">
				<?php 
				$table = array();
				if ($page_hist){
						$table[]=array("Date", "User", "Action");
						foreach($page_hist as $ph) {
							$table[]=array(
								formatDateTime($ph->dt_created),
								$w->Auth->getUser($ph->creator_id)->getFullName(),
								Html::a(WEBROOT."/wiki/viewhistoryversion/".$wiki->name."/".$wh['name']."/".$ph->id,"View",true),
							);
						}
						echo Html::table($table,"history","tablesorter",true);
				} else {
						echo "No changes yet.";
				}
				?>
			</div>
			
			
			<?php if ($wiki->canEdit($w->Auth->user())):?>
				<script>
					// into global scope to facilitate testing
					var simplemde;
					function my_updateCallBack(record) {
						if (simplemde) {
							$('#viewbody').load('<?php echo WEBROOT."/wiki/preview/".$wiki->name."/".$page->name ?>');
						} else {
							$('#viewbody').html(record.body);
						}
						$('#page-history').load('<?php echo WEBROOT."/wiki/pagehistory/".$wiki->name."/".$page->name ?>');
						$('#wiki-history').load('<?php echo WEBROOT."/wiki/history/".$wiki->name."/".$page->name ?>');
					}
					function my_changeCallBack() {
						$('#wikiautosavebuttons').show();
						$('#wikiautosavebuttons .savebutton').show();
						$('#wikiautosavebuttons .savedbutton').hide();
					}
					function my_saveCallBack(record) {
						if (simplemde) {
							$('#viewbody').load('<?php echo WEBROOT."/wiki/preview/".$wiki->name."/".$page->name ?>');
						} else {
							$('#viewbody').html(record.body);
						}
						$('#wikiautosavebuttons .savebutton').hide();
						$('#wikiautosavebuttons .savedbutton').show();
						$('#page-history').load('<?php echo WEBROOT."/wiki/pagehistory/".$wiki->name."/".$page->name ?>');
						$('#wiki-history').load('<?php echo WEBROOT."/wiki/history/".$wiki->name."/".$page->name ?>');
					}
				</script>
					<?php if ($wiki->type=="markdown"):?>
						<link rel="stylesheet" href="/modules/wiki/assets/css/font-awesome.min.css">
						<link rel="stylesheet" href="/modules/wiki/assets/simplemde.min.css">
						<script src="/modules/wiki/assets/simplemde.min.js"></script>
						<?php if (Config::get('wiki.liveedit')==true):?>
							<script src="/modules/wiki/assets/simplemde.liveedit.js"></script>
							<script>
								$(document).ready(function() {
									/*************************************************
									 * AUTH TOKEN
									 *************************************************/
									$.ajax(
										"/rest/token?apikey=<?php echo Config::get("system.rest_api_key") ?>",
										{
											cache: false,
											dataType: "json"
										}
										
									/*************************************************
									 * NOW CREATE EDITOR
									 *************************************************/
									).done(function(token) {
										simplemde = new SimpleMDE({
											element: document.getElementById("body"),
											spellChecker: false,
											insertTexts: {
												link: ["[](", ")"],
												image: ["![](", ")"],
												table: ["", "\n\n| Column 1 | Column 2 | Column 3 |\n| -------- | -------- | -------- |\n| Text     | Text     | Text     |\n\n"],
												horizontalRule: ["", "\n\n-----\n\n"]
											}
										});
										simplemde.config={
											lastModified: '<?php echo $page->dt_modified ?>',
											pollUrl: '/rest/index/WikiPage/id___equal/<?php echo $page->id; ?>/dt_modified___greater/',
											saveUrl: '/rest/save/WikiPage/',
											updateCallBack: 'my_updateCallBack',
											changeCallBack: 'my_changeCallBack',
											saveCallBack: 'my_saveCallBack',
											saveTimeOut: 2000,
											pollTimeOut: 3000,
											requestParameters: 'token=' + token.success ,
											saveData : {"id": "<?php echo $page->id ?>" }
											
											
										};
										SimpleMde_BindLiveEditing(simplemde);
										simplemde.codemirror.on("change", function(){
											$('#wikiautosavebuttons').show();
										});
									});
								});
							</script>
						<?php else: ?>	
							<script>
							$(document).ready(function() {
								simplemde = new SimpleMDE({
									element: document.getElementById("body"),
									spellChecker: false,
									insertTexts: {
										link: ["[](", ")"],
										image: ["![](", ")"],
										table: ["", "\n\n| Column 1 | Column 2 | Column 3 |\n| -------- | -------- | -------- |\n| Text     | Text     | Text     |\n\n"],
										horizontalRule: ["", "\n\n-----\n\n"]
									},
								});
								simplemde.codemirror.on("change", function(){
									$('#wikibuttons').show();
								});
							});
							</script>
						<?php endif; ?>							
					<?php endif; ?>
					<?php if ($wiki->type=="richtext"):?>
						<?php if (Config::get('wiki.liveedit')==true):?>
							<script src="/modules/wiki/assets/CSSelector.js" ></script>
							<script>
								$(document).ready(function() {
									CKEDITOR.plugins.addExternal( 'wikipage', '/modules/wiki/assets/ckeditorplugins/wikipage/','plugin.js','' );
									CKEDITOR.plugins.addExternal( 'liveedit', '/modules/wiki/assets/ckeditorplugins/liveedit/','plugin.js','' );
									CKEDITOR.plugins.addExternal( 'maximize', '/modules/wiki/assets/ckeditorplugins/maximize/','plugin.js','' );
									CKEDITOR.plugins.addExternal( 'autogrow', '/modules/wiki/assets/ckeditorplugins/autogrow/','plugin.js','' );
									CKEDITOR.config.extraPlugins = 'wikipage,liveedit,maximize,autogrow';
									/*************************************************
									 * AUTH TOKEN
									 *************************************************/
									$.ajax(
										"/rest/token?apikey=<?php echo Config::get("system.rest_api_key") ?>",
										{
											cache: false,
											dataType: "json"
										}
										
									/*************************************************
									 * NOW CREATE EDITOR
									 *************************************************/
									).done(function(token) {
										$('#body').each(function(){
											CKEDITOR.replace(this,{
												lastModified: '<?php echo $page->dt_modified ?>',
												pollUrl: '/rest/index/WikiPage/id___equal/<?php echo $page->id; ?>/dt_modified___greater/',
												saveUrl: '/rest/save/WikiPage/',
												updateCallBack: 'my_updateCallBack',
												changeCallBack: 'my_changeCallBack',
												saveCallBack: 'my_saveCallBack',
												saveTimeOut: 2000,
												pollTimeOut: 3000,
												requestParameters: 'token=' + token.success ,
												saveData : {"id": "<?php echo $page->id ?>" }
											});
										});
									});
								});
							</script>	
						<?php else: ?>
							<script>
								$(document).ready(function() {
									CKEDITOR.plugins.addExternal( 'wikipage', '/modules/wiki/assets/ckeditorplugins/wikipage/','plugin.js','' );
									CKEDITOR.plugins.addExternal( 'maximize', '/modules/wiki/assets/ckeditorplugins/autogrow/','plugin.js','' );
									CKEDITOR.plugins.addExternal( 'autogrow', '/modules/wiki/assets/ckeditorplugins/maximize/','plugin.js','' );
									CKEDITOR.config.extraPlugins = 'wikipage,autogrow,maximize';
									$('#body').each(function(){
										CKEDITOR.replace(this);
										
									});
									$('#wikibuttons').show();
									var editor=CKEDITOR.instances.body;
									editor.on('contentDom', function() {
										var editable = editor.editable();
										editable.attachListener( editor.document, 'keyup', function() {
											$('#wikibuttons').show();
										} );
									});
									
								});
							</script>
						<?php endif; ?>
					<?php endif; ?>
					<?php if ($wiki->type=="text"):?>
					<script>
							$(document).ready(function() {
								CodeMirror.fromTextArea(document.getElementById("body"));
							});
					</script>	
					<?php endif; ?>
					<?php if (true || $wiki->type=="mindmap"):?>
					<script>
							$(document).ready(function() {
								
							});
					</script>	<?php endif; ?>
					
					
				<div id="edit" class="clearfix">
					
					<?php echo $editForm; ?>

					<div id="editattachments">
					<?php echo $w->partial("listattachmentsplain", array("object" => $page, "redirect" => "wiki/view/{$wiki->name}/{$page->name}#attachments"), "file"); ?>
					</div>
					<script>
					$(document).ready(function() {
						$('#editattachments button').remove();
					});
					</script>
				</div>
			<?php endif; ?>
		   
			
			<?php if ($wiki->isOwner($w->Auth->user()) && $page->name == "HomePage"):?>
				<div id="members">
					<?php echo Html::ab(WEBROOT."/wiki/editmember/".$wiki->id, "Add Member", true); ?>
					<?php if (!empty($wiki_users)): ?>
						<table class="tablesorter">
							<thead>
								<tr>
									<th>Name</th>
									<th>Role</th>
									<th>Action</th>
								</tr>
							</thead>
							<tbody>
								<?php foreach($wiki_users as $mem) : ?>
									<tr>
										<td><?php echo $mem->getFullName(); ?></td>
										<td><?php echo $mem->role; ?></td>
										<td>
											<?php 
												echo Html::ab($webroot."/wiki/editmember/".$wiki->id."/".$mem->id, "Edit","editbutton")."&nbsp;&nbsp;&nbsp;";
												echo Html::ab($webroot."/wiki/delmember/".$wiki->id."/".$mem->id,"Delete","deletebutton"); 
											?>
										</td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					<?php endif; ?>
				</div>
			<?php endif; ?>
			<?php if ($w->Auth->hasRole('comment')):?>
			<div id="comments">
				<?php echo $w->partial("listcomments", array("object" => $page, "redirect" => "wiki/view/{$wiki->name}/{$page->name}#comments"), "admin"); ?>
			</div>
			<?php endif; ?>
			<?php if ($w->Auth->hasRole('file_upload') && $w->Auth->hasRole('file_download')):?>
			<div id="attachments">
			<?php echo $w->partial("listattachments", array("object" => $page, "redirect" => "wiki/view/{$wiki->name}/{$page->name}#attachments"), "file"); ?>
			</div>
			<?php endif; ?>
			<?php 
				$tab_content = $w->callHook('core_template', 'tab_content', ['object' => $page, 'redirect_url' => "/wiki/view/{$wiki->name}/{$page->name}"]); 
				if (!empty($tab_content)) {
					echo implode('', $tab_content);
				}
			?>
		</div>
	</div>

</form>

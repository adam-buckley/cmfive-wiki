<form action="/wiki/edit/<?php echo $wiki->name ?>/<?php echo $page->name ?>" method="POST" target="_self" class=" small-12 columns"><input type="hidden" name="wikieeditform" value="9d23d65bae7144">

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
				<a href="#comments">Comments</a>
				<a href="#attachments">Attachments</a>
				<!--span style="float:right;"><button class="button tiny " onclick="modal_history.push(&quot;/wiki/markup?isbox=1&quot;); $(&quot;#cmfive-modal&quot;).foundation(&quot;reveal&quot;, &quot;open&quot;, &quot;/wiki/markup?isbox=1&quot;);return false;">Markup Help</button></span-->
				<span id="wikibuttons" style="float:right; display: none;" ><button class="button tiny tiny button savebutton" type="submit">Save</button><button class="button tiny tiny button cancelbutton" style="margin-right: 2em;" type="button" onclick="if($('#cmfive-modal').is(':visible')){ $('#cmfive-modal').foundation('reveal', 'close'); } else { window.history.back(); }">Cancel</button></span>
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
				<div>
					<?php echo $body?>
				</div>
				<hr/>
				<div id="viewattachments">
				<?php echo $w->partial("listattachments", array("object" => $page, "redirect" => "wiki/view/{$wiki->name}/{$page->name}#attachments"), "file"); ?>
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
								Html::a(WEBROOT."/wiki/view/".$wiki->name."/".$wh['name'],"<b>".$wh['name']."</b>"),
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
								$ph->getDateTime("dt_created","d/m/Y H:i"),
								$w->Auth->getUser($ph->creator_id)->getFullName(),
								Html::box(WEBROOT."/wiki/pageversion/".$wiki->name."/".$ph->id,"View",true),
							);
						}
						echo Html::table($table,"history","tablesorter",true);
				} else {
						echo "No changes yet.";
				}
				?>
			</div>
			
			
			<?php if ($wiki->canEdit($w->Auth->user())):?>
					<?php if ($wiki->type=="markdown"):?>
						<link rel="stylesheet" href="/modules/wiki/assets/css/font-awesome.min.css">
						<link rel="stylesheet" href="/modules/wiki/assets/simplemde.min.css">
						<script src="/modules/wiki/assets/simplemde.min.js"></script>
						
						<script>
							$(document).ready(function() {
								var simplemde = new SimpleMDE({
									element: document.getElementById("body"),
									spellChecker: false,
								});
								simplemde.codemirror.on("change", function(){
									$('#wikibuttons').show();
								});

							});
						</script>
					<?php endif; ?>
					<?php if ($wiki->type=="richtext"):?>
					<script>
						$(document).ready(function() {
							CKEDITOR.config.extraPlugins = 'wikipage';
							$('#body').each(function(){
								CKEDITOR.replace(this);
								
							});
							//$("#body").on('keyup paste',function() {console.log('change');});
							var editor=CKEDITOR.instances.body;
							console.log(editor.on('contentDom', function() {
								var editable = editor.editable();
								editable.attachListener( editor.document, 'keyup', function() {
									$('#wikibuttons').show();
								} );
							}));
							
						});
					</script>	
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
					<?php echo $w->partial("listattachments", array("object" => $page, "redirect" => "wiki/view/{$wiki->name}/{$page->name}#attachments"), "file"); ?>
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
					<?php echo Html::box(WEBROOT."/wiki/editmember/".$wiki->id, "Add Member", true); ?>
					<?php if ($wiki_users): ?>
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
												echo Html::b($webroot."/wiki/editmember/".$wiki->id."/".$mem->id, "Edit");
												echo Html::b($webroot."/wiki/delmember/".$wiki->id."/".$mem->id,"Delete"); 
											?>
										</td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					<?php endif; ?>
				</div>
			<?php endif; ?>
			<div id="comments">
				<?php echo $w->partial("listcomments", array("object" => $page, "redirect" => "wiki/view/{$wiki->name}/{$page->name}#comments"), "admin"); ?>
			</div>
			<div id="attachments">
			<?php echo $w->partial("listattachments", array("object" => $page, "redirect" => "wiki/view/{$wiki->name}/{$page->name}#attachments"), "file"); ?>
			</div>
		</div>
	</div>

</form>

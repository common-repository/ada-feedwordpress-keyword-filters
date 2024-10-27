<?php
/*
Plugin Name: Ada FeedWordPress Keyword Filters
Plugin URI: https://adadaa.net/1uthavi/ada-feedwordpress-keyword-filters/
Description: Filters posts syndicated through FeedWordPress by keywords.  You can do complicated keyword filters using AND, OR, and NOT logics.  Plugin will look for user entered keywords in post_title, and post_content
Version: 2024.0210
Author: Adadaa
Author URI: https://adadaa.news/
License: GPL
*/

class AdaFWPKeyFilters {

	private $debug = false;
	private $handle;
	
	function __construct() {
		add_action(
			/*hook=*/ 'feedwordpress_admin_page_posts_meta_boxes',
			/*function=*/ array(&$this, 'posts_meta_boxes'),
			/*priority=*/ 100,
			/*arguments=*/ 1
		);
		add_action(
			/*hook=*/ 'feedwordpress_admin_page_posts_save',
			/*function=*/ array(&$this, 'posts_save'),
			/*priority=*/ 100,
			/*arguments=*/ 2
		);
		
		add_filter(
			/*hook=*/ 'syndicated_feed_items',
			/*function=*/ array(&$this, 'the_content'),
			/*priority=*/ 1,
			/*arguments=*/ 2
		);
	}

	function posts_meta_boxes ($page) {
		add_meta_box(
			/*id=*/ 'feedwordpress_filter_keywords_box',
			/*title=*/ __('Ada Keyword Filters'),
			/*callback=*/ array($this, 'html_tags_metabox'),
			/*page=*/ $page->meta_box_context(),
			/*context=*/ $page->meta_box_context()
		);
	}

	function html_tags_metabox ($page, $box = NULL) {

	if ($page->for_feed_settings()) :
		$ada_key_filters = maybe_unserialize(isset($page->link->settings['ada key filters']));
		$include_sitewide_ada_key_filters = isset($page->link->settings['include sitewide ada key filters']);
		$syndicatedPosts = 'this feed\'s posts';
	else :
		$ada_key_filters = get_option('feedwordpress_ada_key_filters');
		$syndicatedPosts = 'syndicated posts';
	endif;
	
?>
<style type="text/css">
	.ada-settings{
		float: left;
	width: 67.5%;
	margin: 0px 3px 3px;
	padding: 5px;
	top-padding:5px;
	background-color: #f7f7f7;
	}
	table.edit-form.narrow th{
		width: 30% !important;
	}
.ada-key-filter-help-box {
	float: right;
	width: 400px;
	border: 1px dotted #777;
	margin: 10px 3px 3px;
	padding: 5px;
	top-padding:25px;
	background-color: #f7f7f7;
}
.ada-key-filter-help-box dt {
	font-weight: bold;
	font-size: 85%;
}
.ada-key-filter-help-box dd {
	font-size: 80%;
	line-height: 100%;
	font-style: italic;
	padding-left: 1.5em;
}
.ada-key-filter-help-box code {
	font-size: inherit;
	font-style: normal;
	background-color: inherit;
}
.ada-key-filter-li {
	padding-bottom: 5px;
	margin-bottom: 5px;
	border-bottom: 1px dotted black;
}
</style>
<div class="ada-inside">
<div class="ada-key-filter-help-box">
  <p style="border-bottom: 1px dotted #777;">To remove a keyword filter, just blank out the text box - leave it empty - and save.</p>
  <p>Keywords are case in-sensitive. Enter comma seperated list of words along with your selection of logics: OR, AND, OR NOT, AND NOT.  Do not leave extra space as it will be used to match exactly'</p>
  <dl>
    <dt><code>OR</code></dt>
    <dd>If any of the word in a comma seperated word list is found, include the syndicated content.</dd>
    <dt><code>AND</code></dt>
    <dd>Only when all of the words in a comma seperated word list is found, include the syndicated content.</dd>
    <dt><code>OR NOT</code></dt>
    <dd>If any of the word in a comma seperated word list is found, do NOT include the syndicated content.</dd>
    <dt><code>AND NOT</code></dt>
    <dd>Only when all of the words in a comma seperated word list is found, do NOT include the syndicated content.</dd>
    <p>When you only use *NOT logics [OR NOT, AND NOT], the syndicated content will be included if those words are not found</p>
    <p>When there are *NOT logics [OR NOT, AND NOT] along with other logics [OR, AND], then *NOT logics take precedence.  If *NOT logic's keyword is found within the syndicated content, all the other rules are ignored and the content is not included.</p>
  </dl>
</div>
<?php

		$filterSelector = array(
		'no' => "No, must match keywords in %s",
		'yes' => "Yes, skip keyword filters from %s",
		);
		foreach ($filterSelector as $index => $value) :
			$filterSelector[$index] = sprintf(__($value), $syndicatedPosts);
		endforeach;
		
		$params = array(
		'input_name' => 'disable_ada_key_filters',
		'setting-default' => 'default',
		'global-setting-default' => 'no',
		'labels' => $filterSelector,
		'default-input-value' => 'default',
		);
?>
<div class="ada-settings">
<table class="edit-form narrow">
  <tr>
    <th scope="row" ><?php _e('Disable Ada Keyword Filters:'); ?></th>
    <td><?php $page->setting_radio_control(
			'disable ada key filters', 'disable_ada_key_filters',
			$filterSelector, $params
		);
		?></td>
  </tr>
</table>
</div>
</div>
<?php

	print "<ul id='adaFilterRulesList'>\n";
	
	if ($page->for_feed_settings()) {	//do not show this on global feed settings
		print '<input type="checkbox" value="1"' . ($include_sitewide_ada_key_filters ?  ' checked="checked"' : '') . ' name="include_sitewide_ada_key_filters"> Include site-wide Ada Keyword Filter rules as well<br /><br />';
	}

	if (!is_array($ada_key_filters)) : $ada_key_filters = array(); endif;

	print '<input type="hidden" id="next-filter-rule-index" name="next_filter_rule_index" value="'.count($ada_key_filters).'" />';

	// In AJAX environment, this is a dummy rule that stays hidden. In a
	// non-AJAX environment, this provides a blank rule that we can fill in.
	$ada_key_filters['new'] = array(
		'class' => array('hide-if-js'),
		'logic' => 'OR',
		'keywords' => '',
	);
	

	foreach ($ada_key_filters as $index => $line) :
		if (isset($line['keywords'])) :
			$selected['OR'] = (($line['logic']=='OR') ? ' selected="selected"' : '');
			$selected['AND'] = (($line['logic']=='AND') ? ' selected="selected"' : '');
			$selected['ORNOT'] = (($line['logic']=='ORNOT') ? ' selected="selected"' : '');
			$selected['ANDNOT'] = (($line['logic']=='ANDNOT') ? ' selected="selected"' : '');
			
			if (!isset($line['class'])) : $line['class'] = array(); endif;
			$line['class'][] = 'ada-key-filter-li';
			
			$keywords = maybe_unserialize($line['keywords']);
			$keywords = is_array($keywords) ? implode(',',$keywords) : '';
		?>
<li id="ada-key-filter-<?php print esc_attr($index); ?>-li" class="<?php print implode(' ', $line['class']); ?>">&raquo; <strong>Add</strong>
  <select id="ada-key-filter-<?php print esc_attr($index); ?>-logic" name="ada_key_filters[<?php print esc_attr( $index); ?>][logic]" style="width: 8.0em">
    <option value="OR"<?php print esc_attr($selected['OR']); ?>>OR</option>
    <option value="AND"<?php print esc_attr($selected['AND']); ?>>AND</option>
    <option value="ORNOT"<?php print esc_attr($selected['ORNOT']); ?>>ORNOT</option>
    <option value="ANDNOT"<?php print esc_attr($selected['ANDNOT']); ?>>ANDNOT</option>
  </select>
  of <?php print esc_attr($syndicatedPosts); ?>:
  <textarea style="vertical-align: top" rows="2" cols="30" class="ada-key-filter-keywords" id="ada-key-filter-<?php print esc_attr($index); ?>-keywords" name="ada_key_filters[<?php print esc_attr($index); ?>][keywords]"><?php print htmlspecialchars($keywords); ?></textarea>
</li>
<?php
		endif;
	endforeach;
	?>
</ul>
<br style="clear: both" />
<script type="text/javascript">
		jQuery(document).ready( function($) {
			
		//CAPitalZ{	
		visibleAdaFilterRule(); //for onload
		$('input[name=disable_ada_key_filters]').change(visibleAdaFilterRule);
		
		function visibleAdaFilterRule(){
			if($('input[name=disable_ada_key_filters]:checked').val() == 'no') $('#adaFilterRulesList').show();
			else $('#adaFilterRulesList').hide();
		}
		//CAPitalZ}
				
			$('.ada-key-filter-keywords').blur( function() {
				if (this.value.length == 0) {
					var theLi = $('li:has(#'+this.id+")");
					theLi.hide('normal')
				}
			} );

			var addRuleLi = document.createElement('li');
			addRuleLi.innerHTML = '<strong style="vertical-align: middle; font-size: 110%">[+]</strong> <a style="font-variant: small-caps" id="add-new-filter-rule" href="#">Add a new keyword filter</a> ….';
			$('#ada-key-filter-new-li').after(addRuleLi);

			$('#add-new-filter-rule').click( function() {
				// Get index counter
				var nextIndex = parseInt($('#next-filter-rule-index').val());

				var newIdPrefix = 'ada-key-filter-'+nextIndex;
				var newNamePrefix = 'ada_key_filters['+nextIndex+']';

				var dummy = {};
				dummy['li'] = {'el': $('#ada-key-filter-new-li') }
				dummy['logic'] = {'el': $('#ada-key-filter-new-logic') };
				dummy['keywords'] = {'el': $('#ada-key-filter-new-keywords') };

				for (var element in dummy) {
					dummy[element]['save'] = {
						'id': dummy[element]['el'].attr('id'),
						'name': dummy[element]['el'].attr('name')
					};
					dummy[element]['el'].attr('id', newIdPrefix+'-'+element);
					dummy[element]['el'].attr('name', newNamePrefix+'['+element+']');
				}
	
				var newLi = $('#'+newIdPrefix+'-li').clone(/*events=*/ true);
				//newLi.attr('id', null);
				newLi.removeClass('hide-if-js');
				newLi.addClass('ada-key-filter-li');
				newLi.css('display', 'none');

				// Switch back
				for (var element in dummy) {
					dummy[element]['el'].attr('id', dummy[element]['save']['id']);
					dummy[element]['el'].attr('name', dummy[element]['save']['name']);
				}

				$('#ada-key-filter-new-li').before(newLi);
				newLi.show('normal');

				$('#next-filter-rule-index').val(nextIndex+1);

				return false;
			} )
		} );
	</script>
<?php
	
	} /* AdaFWPKeyFilters::html_tags_box() */	

	function posts_save($params, $page) {
		
	if (isset($params['disable_ada_key_filters'])) {
		
	  if($params['disable_ada_key_filters'] != "yes") {
	  
		foreach ($params['ada_key_filters'] as $index => $line) :
			if( count( array_filter(array_map('trim',explode(',',$line['keywords']))) ) == 0 ) {	//remove any empty space elements, empty commas submitted by user & check
		
				unset($params['ada_key_filters'][$index]);
			}
			else {
				$line['keywords'] = serialize(array_filter(explode(',',$line['keywords'])));	//trying to save performance by exploding and array_filtering on saving than on syndication.  See ALREADY_ARRAY_KEYWORDS
				$params['ada_key_filters'][$index] = $line;
			}
		endforeach;

		// Convert indexes to 0..(N-1) to avoid possible collisions
		$params['ada_key_filters'] = array_values($params['ada_key_filters']);
		
		if(count($params['ada_key_filters']) > 0) {

			if($this->ada_is_found_in($params['ada_key_filters'], "NOT"))	//If NOT logic found sort the logics so that all the NOT logics are at the top
																		//could check for ada_is_not_logic_all(), but then I don't know which is costly
				$params['ada_key_filters'] = $this->ada_sort_logic_precedence($params['ada_key_filters']);
	  }
		else	//if no filters found
			unset($params['ada_key_filters']);

		}
		else	//if disable_ada_key_filters == 'yes'
			unset($params['ada_key_filters']);
	
		if ($page->for_feed_settings()) :
		   if ('default'==$params['disable_ada_key_filters']) {	//for a particular feed, the setting is 'default', then no need to save anything as everything is pulled from Global setting
			unset($page->link->settings['disable ada key filters']);
			unset($page->link->settings['ada key filters']);
			}
		   else {	//for both 'no' or 'yes'		   
			
			$page->link->settings['disable ada key filters'] = $params['disable_ada_key_filters'];
			
			if(!empty($params['ada_key_filters'])) {	//it will be empty if 'yes'.  it was made previously
			 $page->link->settings['ada key filters'] = serialize($params['ada_key_filters']);	//$line['keywords'] is double serialized
			 $page->link->settings['include sitewide ada key filters'] = $params['include_sitewide_ada_key_filters'];	//If there is no other filters, user should use the global 'default' option
			}
		    else {
			 unset($page->link->settings['ada key filters']);
			 unset($page->link->settings['include sitewide ada key filters']);
			 }
			}
			
		   $page->link->save_settings(/*reload=*/ true);
		else :
		   update_option('feedwordpress_disable_ada_key_filters', $params['disable_ada_key_filters']);
		   if(!empty($params['ada_key_filters']))
			update_option('feedwordpress_ada_key_filters', $params['ada_key_filters']);
		   else
			delete_option('feedwordpress_ada_key_filters');
		endif;
		
	}

	} /* AdaFWPKeyFilters::posts_save() */

function ada_include($post,$meta){
		
		$not_logic_found = $this->ada_is_found_in($meta, "NOT");	//is at least one NOT logic found?
		
		if($this->debug) { 
			fprintf($this->handle, "Checking *TITLE*: '%s'\n", $post['post_title']);			
		}
		$include = $this->ada_include_content($post['post_title'],$meta);

		if($this->debug) { 
			fprintf($this->handle, "Should include by *TITLE*? %s\n", is_null($include) ? '***NOT DECIDED***' : ($include ? '***YES***' : '***NO***') );			
		}
		if(!empty($post['post_content']) && (is_null($include) || ($include && $not_logic_found))) {	//$include == null means 'could not able to come to a decision'
													//even if $include is TRUE, we should check the post_content, if there is a NOT logic,
													//as there could be blacklisted keywords found in post_content
													//if $include is false, then no need to check the post_content.
			
		if($this->debug) { 
			fprintf($this->handle, "Checking *POST CONTENT*\n");			
		}
			$include = $this->ada_include_content($post['post_content'],$meta, $include);
		}

	if(is_null($include)) {
		if($this->ada_is_found_all($meta, "NOT")) {	//is only the NOT logic found?
			$include = true;	//since NOT keywords are not found, $include is still null, and user only specified NOT keywords and nothing else, we can safely include this post
			if($this->debug) { 
				fprintf($this->handle, "Could not find any keywords && all NOT logic, hence INCLUDE.\n");			
			}
		}
		else{	//The user defined other logics also - not just NOT logics
			if($this->debug) { 
				fprintf($this->handle, "Could not find any keywords, hence NOT INCLUDE.\n");			
			}
			$include = false;	//NOT keywords are not found, as $include is still null. Yet, the user defined other logics are not found as well since this filter has not just NOT logics but others as well
		}
	}
	
	return $include;
	
} /* AdaFWPKeyFilters::ada_include() */

//is 'needle' in every element of 2D 'haystack' array?
function ada_is_found_all($haystack, $needle){

	$correct = true;
	
	if(is_array($haystack))
		foreach ($haystack as $rule) {
			if(!$this->ada_is_found($rule['logic'], $needle)){
				$correct = false;
				break;
			}
		}
	else
		$correct = false;
		
	return $correct;
}

//is 'needle' found in at least one element of 2D 'haystack' array?
function ada_is_found_in($haystack, $needle){

	$found = false;
	
	if(is_array($haystack))
		foreach ($haystack as $rule) {
			$found = $this->ada_is_found($rule['logic'], $needle);
			if($found) break;
		}
	return $found;
	
}

//is 'needle' found in at least one element of 'haystack' string list?
function ada_is_found($haystack, $needle){
	return stripos($haystack,$needle) !== false;
}

//Give precedence to NOT logic.  Reorder logics so that NOT logics comes at the top
function ada_sort_logic_precedence($meta){
	$sorted_meta = array();
	
	foreach($meta as $rule){

		if($this->ada_is_found($rule['logic'], "NOT"))

			$sorted_meta[] = $rule;
		else
			$sorted_meta_others[] = $rule;
		
	}
	
	return is_array($sorted_meta_others) ? array_merge($sorted_meta, $sorted_meta_others) : $sorted_meta;
}
	
function ada_include_content($text,$meta,$include = null){	//$include = the result of step 1; checked against post title
															//$include = to say the logic already said to include the Post - after looking at post's Title,
															//but there is NOT logic [user entered] that needs to be checked for post_content
															//then only we can for sure say, include the Post
															
  if(is_array($meta))
	foreach ($meta as $rule) {
		
		$keywords = unserialize($rule['keywords']);	//unserializing the double serialized $rule['keywords']
													//ALREADY_ARRAY_KEYWORDS:  no need to explode & array_filter as the data from DB is optimized
		
		if($this->debug) { 
			fprintf($this->handle, "Checking %s: '%s'\n", $rule['logic'], implode(',', $keywords));			
		}
		
		if($include && !$this->ada_is_found($rule['logic'], "NOT"))	//After post_title's iteration, the $include = true.
																	// now this function is called first time and there was a NOT logic found and the whole funciton is executed.
																	// 	but the 2nd time if the logic is not NOT, then no need to check further.
																	//	as we made all NOT logics to be at the top and we already established the post is $include=true based on other logics in the post_title's iteration
			break;	//see comment SORTED_NOT_LOGIC
		else	//if NOT logic found
			$is_found = $this->ada_is_keyword_found($text, $keywords, $rule['logic']);
		
		if($is_found) {
			if($this->debug) { 
				fprintf($this->handle, "%s: '%s' found\n", $rule['logic'], implode(',', $keywords));			
			}
			
			if($this->ada_is_found($rule['logic'], "NOT")) {	//ANDNOT or ORNOT
				$include = false;
				break;	//since black list keyword is found already
			}
			else {	//AND or OR
			
				$include = true;
				break;	//SORTED_NOT_LOGIC
						//originally we should not break yet, as there could be more NOTs
						//but assuming we would have the logics sorted that all the NOTs are at the top of the array - higher precedence
						//we can avoid some extra loops
				}
		}
		
	}
	
	return	$include;
	
} /* AdaFWPKeyFilters::ada_include_content() */

function ada_is_keyword_found($haystack, $array_of_needles, $logic)
{
	$found = false;
	$count_of_keywords_to_match = -1;

	if($this->ada_is_found($logic, "AND"))	//AND or ANDNOT
		$count_of_keywords_to_match = count($array_of_needles);	//must match as many as keywords found
		
   foreach ($array_of_needles as $needle)
   {
	   
		if($this->ada_is_found($haystack, $needle))
        {
			
			if($count_of_keywords_to_match < 0) {	//ORNOT and OR
				$found = true;
				break;
			}
			
			else	//ANDNOT and AND
				$count_of_keywords_to_match--;	//all keywords should be found
				
        }
		
		elseif($count_of_keywords_to_match > -1) {	//ANDNOT and AND
			$found = false;	//one keyword is not found in the ANDNOT or AND logic, hence we can break the loop
			break;
		}
		
   }
   
   if($count_of_keywords_to_match===0) {	//if AND or ANDNOT logic found all keywords
   
		$found = true;
	}

   return $found;
   
} /* AdaFWPKeyFilters::ada_is_keyword_found() */


function the_content ($posts, $syndiPost) {
	
    global $wpdb;
	
    if ( $this->debug ) 
    {
        $wpdb->show_errors();
        $this->handle = fopen("ada_key_filters.log", "a+"); 
		fprintf($this->handle, "==============================================");
		fprintf($this->handle, "Started on %s\n", date('Y-m-d h:i:s a', time()));
    }
	
		$link = $syndiPost;
		
				$disable_ada_key_filters = $link->setting('disable ada key filters', 'feedwordpress_disable_ada_key_filters', 'yes');
				
						
					if ( $this->debug )
					{
						fprintf($this->handle, "%s=disable_ada_key_filters\n", $disable_ada_key_filters);
					}
				if($disable_ada_key_filters == 'yes'){			
					if ( $this->debug )
					{
        				fclose($this->handle);
					}
					
					return $posts;
				}
			
					$meta = maybe_unserialize($link->setting('ada key filters', 'feedwordpress_ada_key_filters', false));
					
					
		   if(is_array($meta)){
	   
		 $include_sitewide_ada_key_filters = $link->setting('include sitewide ada key filters', '', 0);		 
		 if($include_sitewide_ada_key_filters) {
			 	
    if ( $this->debug )
    {
		fprintf($this->handle, "meta is ARRAY\n");
				fprintf($this->handle, "[include sitewide ada key filters] is set\n");
	}
			//[include sitewide ada key filters] is not set in feed settings if there was no ada keyword filters.  Even if you try to manually set it without any keywords and save, the program will un-set.  Its not a bug
			// Then only we won't duplicate filters in here
			 $sitewide_meta = get_option('feedwordpress_ada_key_filters');
			
			if(is_array($sitewide_meta)) {		
			if(!$this->ada_is_found_in($sitewide_meta, "NOT")) {
			 $meta = array_merge($meta, $sitewide_meta);
			} else {	//If NOT logic found, sort the logics so that all the NOT logics are at the top
			 $meta = array_merge($meta, $sitewide_meta);
			 $meta = $this->ada_sort_logic_precedence($meta);
			}
			}	//if(is_array($sitewide_meta))
			
		 }	//if($include_sitewide_ada_key_filters)
		
	if ( is_array( $posts )) {
		foreach ( $posts as $key => $item ) {
			$post = new SyndicatedPost( $item , $link );
			
					
			if(!$this->ada_include($post->post,$meta)) {		
				if($this->debug) { 
					fprintf($this->handle, "***NOT INCLUDE***\n");
				}
				unset( $posts[$key] );
			}		
			else if ( $this->debug ) { 
				fprintf($this->handle, "***INCLUDE***\n");
			}		
		}	//foreach ( $posts as $key => $item )
	}	//if ( is_array( $posts ))
		
		
	   }	//if(is_array($meta))
	   
	   
    if ( $this->debug ) {
        fclose($this->handle);
    }
	
	return $posts;	//if using syndicated_post filter hook
			
	} /* AdaFWPKeyFilters::the_content() */
	
} /* class AdaFWPKeyFilters */

// Hook us in
$ada_key_filters = new AdaFWPKeyFilters;

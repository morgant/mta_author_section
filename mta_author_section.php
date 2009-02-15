<?php

// This is a PLUGIN TEMPLATE.

// Copy this file to a new name like abc_myplugin.php.  Edit the code, then
// run this file at the command line to produce a plugin for distribution:
// $ php abc_myplugin.php > abc_myplugin-0.1.txt

// Plugin name is optional.  If unset, it will be extracted from the current
// file name. Uncomment and edit this line to override:
$plugin['name'] = 'mta_author_section';

$plugin['version'] = '0.1';
$plugin['author'] = 'Morgan Aldridge';
$plugin['author_uri'] = 'http://www.makkintosshu.com/';
$plugin['description'] = 'Author bio/profile section plus glue to continue using built-in author functionality.';

// Plugin types:
// 0 = regular plugin; loaded on the public web side only
// 1 = admin plugin; loaded on both the public and admin side
// 2 = library; loaded only when include_plugin() or require_plugin() is called
$plugin['type'] = 1; 


@include_once('zem_tpl.php');

if (0) {
?>
# --- BEGIN PLUGIN HELP ---

h2. mta_author_section

*Note*: _Clean URLs" must be enabled._

This plug-in allows you to link author names to a section containing an article per author, much like "bos_author":http://textpattern.org/plugins/486/bos_author, but also provides some extra behind-the-scenes glue between author sections and article searches by author name.

The following preferences need to be set and can be later changed from the "mta_author_section" tab under the "Extensions" tab:

* *Author section name* - The section you intend to store author bio/profile articles.
* *Author article title as* - Whether you want the author's bio/profile article's title to be their real name or user ID.

It also implements the following single tags:

h3. mta_author

h4. Syntax

The @mta_author@ tag has the following syntactic structure:

@<txp:mta_author />@

h4. Attributes

The @mta_author@ tag will accept the following attribute (note: attributes are *case sensitive*):

@link="integer"@

When set to *1*, returns a link to the author's article in the author section, otherwise returns just the author's name if set to *0*. Available values: *0* or *1* (default).

@display="string"@

When set to *realname*, returns the user's full name or returns the user's ID if set to *id*. Available values: *id* or *realname* (default).

h4. Example

@<txp:mta_author link="0" display="id" />@

h3. mta_author_article

h4. Syntax

The @mta_author_article@ tag has the following syntactic structure:

@<txp:mta_author_article />@

h4. Attributes

The @mta_author_article@ tag is an extension of the built-in "article_custom":http://textbook.textpattern.net/wiki/index.php?title=Txp:article_custom_/ tag, so it accepts the same attributes. The only modification is to the following attribute:

@author="string"@

Restrict to articles by specified author, otherwise restrict to the current author search if left blank. Default is unset, so restrict to the current author search.

h4. Example

@<mta_author_article section="article" />@

h3. Change Log

v0.1 Initial release.

# --- END PLUGIN HELP ---
<?php
}

# --- BEGIN PLUGIN CODE ---


/* 
 * Admin Interface
 */
if ( @txpinterface == 'admin' )
{
	// only publishers & managing editrs should have permission to use this plug-in
	add_privs('mta_author_section', '1,2');
	
	// add the tab & register the callback
	register_tab('extensions', 'mta_author_section', 'mta_author_section');
	register_callback('mta_author_section_admin_tab', 'mta_author_section');
}
else
{
	// apply our glue after the url has been parsed, vars init'd, etc., but
	// before the page is parsed & rendered
	register_callback('mta_author_section_glue', 'textpattern');
}

function mta_author_section_admin_tab($event, $step)
{
	global $prefs;
	
	$author_section = isset($prefs['mta_author_section']) ? $prefs['mta_author_section'] : '';
	$author_section_article_title = isset($prefs['mta_author_section_article_title']) ? $prefs['mta_author_section_article_title'] : '';
	
	$publish_form = '';
	$sections = array();
	
	//$prefs = get_prefs();
	
	pagetop('mta_author_section ', ($step == 'update' ? 'mta_author_section Preferences Saved' : ''));
	
	// was the 'publish' button clicked?
	if ( $step == 'update' )
	{
		// store our one preference: what section are author info/bio articles stored?
		$author_section = ps('author_section');
		if ( isset($prefs['mta_author_section']) )
		{
			safe_update('txp_prefs', "val = '".$author_section."'", "name = 'mta_author_section'");
		}
		else
		{
			safe_insert('txp_prefs', "prefs_id=1,name='mta_author_section',val='".$author_section."'");
		}
		$prefs['mta_author_section'] = $author_section;
		
		$author_section_article_title = ps('author_section_article_title');
		if ( isset($prefs['mta_author_section_article_title']) )
		{
			safe_update('txp_prefs', "val = '".$author_section_article_title."'", "name = 'mta_author_section_article_title'");
		}
		else
		{
			safe_insert('txp_prefs', "prefs_id=1,name='mta_author_section_article_title',val='".$author_section_article_title."'");
		}
		$prefs['mta_author_section_article_title'] = $author_section_article_title;
	}
		
	// build the publish form
	$publish_form .= eInput('mta_author_section')."\n";
	$publish_form .= sInput('update')."\n";
	$publish_form .= "<fieldset><legend>Section</legend>\n";
	$publish_form .= "<label for=\"author_section\">Author section name:</label>&nbsp;";
	$textpattern_sections = safe_rows('name', 'txp_section', 'name != \'\'');
	$sections[''] = '';
	foreach ( $textpattern_sections as $row )
	{
		$sections[$row['name']] = $row['name'];
	}
	$publish_form .= selectInput('author_section', $sections, $author_section)."<br />\n";
	$article_title_as = array('realname' => 'Real Name', 'id' => 'User ID');
	$publish_form .= "<label for=\"author_section_article_title\">Author article title as:</label>&nbsp;";
	$publish_form .= selectInput('author_section_article_title', $article_title_as, $author_section_article_title)."\n";
	$publish_form .= "\n</fieldset>\n";
	$publish_form .= fInput('submit', 'submit', 'Save')."\n";
	
	// output the publish form
	print(form($publish_form, 'width: 300px; margin-left: auto; margin-right: auto;'));
	
}

function mta_author_section_glue($event, $step)
{
	global $s, $is_article_list, $thisarticle, $author, $prefs;
	
	if ( ($s == $prefs['mta_author_section']) && empty($author) && !$is_article_list  )
	{
		$textpattern_users = safe_rows('name,RealName', 'txp_users', 'name != \'\'');
		switch ($prefs['mta_author_section_article_title'])
		{
			case 'realname':
				foreach ( $textpattern_users as $user )
				{
					if ( strtolower(sanitizeForUrl($user['RealName'])) == $thisarticle['url_title'] )
					{
						$author = $user['name'];
					}
				}
				break;
			case 'id':
				foreach ( $textpattern_users as $user )
				{
					if ( strtolower(sanitizeForUrl($user['name'])) == $thisarticle['url_title'] )
					{
						$author = $user['name'];
					}
				}
				break;
		}
		
	}
}

function mta_author($atts)
{
	global $thisarticle, $prefs;
	
	extract(lAtts(array(
		'link' => '1',
		'display' => 'realname'
	),$atts));
	
	switch ($display)
	{
		case 'realname':
			$author_name = get_author_name($thisarticle['authorid']);
			break;
		case 'id':
			$author_name = $thisarticle['authorid'];
			break;
	}
	switch ($prefs['mta_author_section_article_title'])
	{
		case 'realname':
			$article_title = get_author_name($thisarticle['authorid']);
			break;
		case 'id':
			$article_title = $thisarticle['authorid'];
			break;
	}
    
    return $link ? '<a href="'.hu.$prefs['mta_author_section'].'/'.strtolower(sanitizeForUrl($article_title)).'">'. $author_name.'</a>' : $author_name;
}

function mta_author_article($atts)
{
	global $author;
	
	// if no author was specified, set the attribute to the current author
	$atts['author'] = !empty($atts['author']) ? $atts['author'] : $author;
	
	// this is just an extension to article_custom, so we'll pass the modified
	// attributes through to it for normal operation
	return article_custom($atts);
}

# --- END PLUGIN CODE ---

?>
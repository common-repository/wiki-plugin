<?php
/*
Plugin Name: GetWIKI
Version: 1.1
Plugin URI: http://saj.in/blog/plugins
Author: Sajin Kunhambu
Author URI: http://saj.in/
Description: Get a WIKI article anywhere on yout blog (e.g. ~GetWIKI(Your_Search_Term)~ )
*/


function cleanUp( $article ) {
    global $getwiki_settings;
    $article = str_replace("\n","",$article);
    if(preg_match("@(?<content>\<\!\-\- start content \-\-\>.*\<\!\-\- end content \-\-\>)@i",$article,$match)!=0) $article = $match[content];
//print "[[[".$article."]]]";die();
    $article = preg_replace("#\<\!\-\-.*\-\-\>#imseU","",$article);
    $article = preg_replace("#\[\!\&\#.*\]#imseU","",$article);
    if(!$getwiki_settings['show_retrieved']) $article = preg_replace("#\<div\sclass=\"printfooter\".*\<\/div\>#imseU","",$article);
    if(!$getwiki_settings['show_edit']) $article = preg_replace("#\s*\<div\s*class=\"editsection\".*\<\/div\>\s*#imseU","",$article);
    if(!$getwiki_settings['show_edit']) $article = preg_replace("#\s*\<span\s*class=\"editsection\".*\<\/span\>\s*#imseU","",$article);
    $article = addHost( $article, "/w/" );
    $article = addHost( $article, "/wiki/" );
    $article = addHost( $article, "/skins-1.5/" );
    $article = "<div class=\"wiki\">".$article.$getwiki_settings['copyleft']."</div>";
    return $article;
}

function addHost( $article, $keyword )
{
    global $getwiki_settings;
    return str_replace($keyword,"http://".$getwiki_settings['host'].$keyword,$article);
}

function getArticleFromHost( $title ) {
    global $getwiki_settings;
    if($use_cache) { 
		if(!function_exists('cache_recall')) return("Cache not installed");
        $function_string = "getArticle(".$title.")"; 
        if($article = cache_recall($function_string,$getwiki_settings['cache_life'])) return $article; 
    } 
    $out = "GET ".$getwiki_settings['path'].$title." HTTP/1.0\r\nHost: ".$getwiki_settings['host']."\r\nUser-Agent: GetWiki for WordPress\r\n\r\n";
    $fp = fsockopen($getwiki_settings['host'], $getwiki_settings['port'], $errno, $errstr, 30);
    fwrite($fp, $out);
    $article = "";
    while (!feof($fp)) {
        $article .= fgets($fp, 128);
    }
    if(substr($article,0,12)=="HTTP/1.0 301")
    {
        if(preg_match("/^.*Location\:\s(\S*).*$/im",$article,$match)!=0) {
            $article = str_replace("http://en.wikipedia.org/wiki/","",$match[1]);
            $article = getArticleFromHost( $article );
        } else {
            $article = "== WIKI Error ==";
        }
    }
    fclose($fp);
	$article = cleanUp($article);
    if($use_cache) cache_store($function_string,$article); 
    return $article;
}

function getArticle( $title ) {
    return getArticleFromHost( $title );
}

function wikify( $text ) {
    $text = preg_replace(
        "#\~GetWIKI\((\S*)\,(\S*)\)\~#imseU",
        "getArticleFromHost('$1','$2')",
        $text
    );
    $text = preg_replace(
        "#\~GetWIKI\((\S*)\)\~#imseU",
        "getArticle('$1')",
        $text
    );
    return $text;
}

function wiki_css() {
    echo "
    <style type='text/css'>
    div.wiki {
        border: 1px dashed silver;
        background-color: #f0f0f0;
    }
    div.gfdl {
        font-size: 80%;
    }
    </style>
    ";
}


//Options Section - Begin
$copyleft = "<div class=\"gfdl\">&copy; This material from <a href=\"http://en.wikipedia.org\">Wikipedia</a> is licensed under the <a href=\"http://www.gnu.org/copyleft/fdl.html\">GFDL</a>.</div>";
if(!get_option('getwiki_settings')) {
  $getwiki_settings = array(
	  'host' => "en.wikipedia.org",
	  'path' => "/wiki/",
	  'port' => 80,
	  'cache' => (function_exists(cache_recall) || function_exists(cache_store)),
	  'cache_life' => 10080,
	  'show_edit' => false,
	  'show_retrieved' => false,
	  'copyleft' => $copyleft
  );
	add_option('getwiki_settings', $getwiki_settings);
} else {
  $getwiki_settings = get_option('getwiki_settings');
  $getwiki_settings['copyleft'] = $copyleft;
}

if( !function_exists(cache_recall) || !function_exists(cache_store) ) { 
        // caching function not available 
        $getwiki_settings['cache'] = false; 
} 

function getwiki_add_page()
{
     add_options_page("GetWIKI Options", "GetWIKI", 8, "wiki-plugin", getwiki_options);
}

function getwiki_options() {
	global $getwiki_settings,$copyleft;
	if( isset( $_POST['update_options'] ) )
	{
    $getwiki_settings = $_POST['getwiki_settings'];
    $getwiki_settings['copyleft'] = $copyleft;
		update_option('getwiki_settings', $_POST['getwiki_settings']);
	}
	?>
<div class="wrap">
  <h2>GetWIKI Options</h2>
  <form name="getwiki_form" method="post">
    <fieldset class="options">
      <legend>Server</legend>
      <table width="100%" cellspacing="2" cellpadding="5" class="editform">
        <tr valign="top">
          <th width="45%" scope="row">WIKI Domain (en.wikipedia.org)</th>
          <td>
            <input type="text" name="getwiki_settings[host]" value="<?php echo $getwiki_settings['host'] ?>" size="25" />
          </td>
        </tr>
        <tr valign="top">
          <th width="45%" scope="row">Path (/path/)</th>
          <td>
            <input type="text" name="getwiki_settings[path]" value="<?php echo $getwiki_settings['path'] ?>" size="25" />
          </td>
        </tr>
        <tr valign="top">
          <th width="45%" scope="row">Port (80)</th>
          <td>
            <input type="text" name="getwiki_settings[port]" value="<?php echo $getwiki_settings['port'] ?>" size="5" maxlength="5" />
          </td>
        </tr>
      </table>
    </fieldset>
    <fieldset class="options">
      <legend>Cache</legend>
      <table width="100%" cellspacing="2" cellpadding="5" class="editform">
        <tr valign="top">
          <th width="45%" scope="row">Enable Cache</th>
          <td>
            <select size="1" name="getwiki_settings[cache]">
              <option value="1" 
                <?php if($getwiki_settings['cache']==true) echo "selected"; ?>>True
              </option>
              <option value="0" 
                <?php if($getwiki_settings['cache']==false) echo "selected"; ?>>False
              </option>
            </select>
          </td>
        </tr>
        <tr valign="top">
          <th width="45%" scope="row">Cache Lifetime</th>
          <td>
            <input type="text" name="getwiki_settings[cache_life]" value="<?php echo $getwiki_settings['cache_life'] ?>" size="4" />
          </td>
        </tr>
      </table>
    </fieldset>
    <fieldset class="options">
      <legend>Presentation</legend>
      <table width="100%" cellspacing="2" cellpadding="5" class="editform">
        <tr valign="top">
          <th width="45%" scope="row">Show 'Edit' Links</th>
          <td>
            <select size="1" name="getwiki_settings[show_edit]">
              <option value="1" 
                <?php if($getwiki_settings['show_edit']==true) echo "selected"; ?>>True
              </option>
              <option value="0" 
                <?php if($getwiki_settings['show_edit']==false) echo "selected"; ?>>False
              </option>
            </select>
          </td>
        </tr>
        <tr valign="top">
          <th width="45%" scope="row">Show 'Retrieved' Links</th>
          <td>
            <select size="1" name="getwiki_settings[show_retrieved]">
              <option value="1" 
                <?php if($getwiki_settings['show_retrieved']==true) echo "selected"; ?>>True
              </option>
              <option value="0" 
                <?php if($getwiki_settings['show_retrieved']==false) echo "selected"; ?>>False
              </option>
            </select>
          </td>
        </tr>
      </table>
    </fieldset>
    <input type="submit" name="update_options" value="Update Options" />
  </form>
  <br /><br />
  Notice Shown: <?php echo $getwiki_settings['copyleft']?>
</div>
<?php
}
//Options Section - End


//Hooks Section - Begin
add_action('admin_menu', 'getwiki_add_page');
add_action('wp_head', 'wiki_css');
add_filter('the_content', 'wikify', 2);
add_filter('the_excerpt', 'wikify', 2);
//Hooks Section - End
?>

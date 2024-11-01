GetWIKI plugin lets you embed a Wikipedia article anywhere in your blog. Installation is the simple drop-in-your-plugins-directory method that most WP plugins follow. Once installed, you can embed a WIKI article in your post with ease. The plugin currently looks for redirects, but does not handle other HTTP responses. 

Usage: ~GetWIKI(Wiki_Article_Slug)~ 
For example, ~GetWIKI(Bekal_Fort)~ will bring embed the Wikipedia article on Bekal Fort in your blog. 

The plugin has been modified to add these: - use the simple-cache plugin by Jeff (originally used in GetWeather). - add css for wiki articles - make edit/retrieval links optional 

Configuration:
The default cache duration is 10080 minutes (1 week), which you can change in the plugin. Also, the cache feature can be enabled/disabled in the plugin. 

$use_cache = true; //Use cache or not
$cache_life = 10080; //Cache life in minutes after which article is refreshed
$edit_link = false; //[edit] links in the article to be displayed or not
$retrieved_link = false; //[Retrieved from] information to be displayed or not

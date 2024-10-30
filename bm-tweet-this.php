<?php
/*
Plugin Name: BM Tweet This
Plugin URI: http://www.binarymoon.co.uk/projects/bm-tweet-this/
Description: Adds the new Twitter Tweet button to your WordPress posts
Version: 1.0
Author: Simon Van Blerk & Ben Gillbanks
Author URI: http://vanblerk.co.uk
License: GPL2

Copyright 2010  simon van blerk  (email : simonandrew@gmail.com)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License, version 2, as 
published by the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

define('BM_TWEET_OPTIONS', 'bm_tweet_this');

class BM_Tweet_This {
 	
   private $version = '0.1';

   function __construct()
   {
	  // add an admin options menu
	  add_action('admin_menu', array(&$this, 'admin_menu'));
	  
	  // define custom boxes on posts and pages
	  add_action('admin_menu', array(&$this, 'add_custom_box'));
	  
	  // handle saving of page and post options
	  add_action('save_post', array(&$this, 'save_post_data'));
	  
	  // add shortcode handler for displaying
	  add_shortcode('tweet_button', array(&$this, 'shortcode_handler'));

	  // add filter to add to content
	  add_filter('the_content', array(&$this, 'insert_new_tweet') , 99);
   }

   /**
    * Admin menu entry.
    *
    * @access	public
    */
   public function admin_menu()
   {
	  if (function_exists('add_options_page')) {
		 $id = add_options_page('Tweet Button Options', 'Tweet Button Options', 10, basename(__FILE__), array(&$this, 'admin_options'));
		 //add_action('admin_print_scripts-'.$id, array(&$this, 'add_admin_js'));
	  }
   }

   /**
    * Add jQuery preview handler to admin options page.
    *
    * @access	public
    */
   public function add_admin_js()
   {
		wp_enqueue_script('bm-tweet-this',
		plugins_url('bm-tweet-this-admin.js', __FILE__),
		array('jquery'));
   }

   /**
    * Display the Twitter Tweet button within the
    * page or post.
    *
    * @access	public
    * @param	string	$html
    */
   public function insert_new_tweet($html)
   {
	  global $post;
	  
	  // get user defined options
	  $options = get_option(BM_TWEET_OPTIONS);

	  // check if the button is enabled
	  $enable_bm_tweet_this = get_post_meta($post->ID, 'enable_bm_tweet_this_button', true);
	  if (empty($enable_bm_tweet_this) || $enable_bm_tweet_this == '1') {
		// add to content
		$html = $this->build_bm_tweet_this_button($html, $options['position']);
	}
      return $html;
   }
   
   /**
    * Display the shortcode.
    */
   public function bm_tweet_this_shortcode($attr, $content)
   {
	  return bm_tweet_this_button(false);
   }

    /**
     * Add a custom section to post and page screens for whether
     * to enable the Like button.
     *
     * @access	public
     */
   public function add_custom_box()
   {
      add_meta_box('bm_tweet_this_enable_button', __( 'Me Likey "Like" Button', 'bm_tweet_this'),
                    array(&$this, 'display_custom_box'), 'post', 'side');
      add_meta_box('bm_tweet_this_enable_button', __( 'Me Likey "Like" Button', 'bm_tweet_this'),
                    array(&$this, 'display_custom_box'), 'page', 'side');
   }

   /**
    * Display the inner fields on the page and post admin areas.
    *
    * @access	public
    */
   public function display_custom_box()
   {
	  global $post;
	  if (is_object($post)) $post_id = $post->ID;
      else $post_id = $post;

      $option_value = '';

      if ($post_id > 0) {
         $enable_bm_tweet_this = get_post_meta($post_id, 'enable_bm_tweet_this_button', true);
         if (!empty($enable_bm_tweet_this)) {
            $option_value = $enable_bm_tweet_this;
         }
      }
      
	  // sse nonce for verification
?>

      <input type="hidden" name="bm_tweet_this_noncename" id="bm_tweet_this_noncename" value="<?php echo wp_create_nonce(plugin_basename(__FILE__) ); ?>" />
      <p>
		 <label>
			<input type="radio" name="bm_tweet_this_button" value ="1" <?php checked('1', $option_value); ?> />
			<?php _e('Enabled', 'bm_tweet_this'); ?>
		 </label>
		 <label>
			<input type="radio" name="bm_tweet_this_button" value ="0"  <?php checked('0', $option_value); ?> />
			<?php _e('Disabled', 'bm_tweet_this'); ?>
		 </label>
      </p>

<?php
   }
   
   /**
    * Save post settings for whether to enable or disable open graph.
    *
    * @access	public
    */
   public function save_post_data($post_id)
   {
	  // do some verification
	  if (!wp_verify_nonce($_POST['bm_tweet_this_noncename'], plugin_basename(__FILE__))) {
		 return $post_id;
      }

	  // ensure user's have proper privileges
      if ('page' == $_POST['post_type']) {
         if (!current_user_can('edit_page', $post_id))
            return $post_id;
      } else {
         if (!current_user_can('edit_post', $post_id ))
            return $post_id;
      }
	  
	  // save data
	  if (isset($_POST['bm_tweet_this_button'])) {
		 $enabled = ($_POST['bm_tweet_this_button'] == '1') ? '1' : '0';
		 update_post_meta($post_id, 'enable_bm_tweet_this_button', $enabled);
	  }
   }

   /**
    * Options page.
    *
    * @access	public
    */
   public function admin_options()
   {
		// default option values
		$defaultOptionVals = array(
			'data_text' 	=> '',						// overide button text
			'data_count' 	=> 'horizontal',			// button layout
			'data_lang' 	=> '',						// display language
			'data_via' 		=> '',						// tweet via account
			'data_related1' => '',						// related twitter accounts
			'data_related2' => '',						// related twitter accounts
			'class'			=> 'twitter-share-button',	// default container class name
			'position'		=> 'manual',				// before, after, both, manual
		);
		
		// get all options
		$options = get_option(BM_TWEET_OPTIONS);
		if (!empty($options)) {
			foreach ($options as $k => $v) {
				$defaultOptionVals[$k] = $v;
			}
		}
	
		// arrays for testing and html form
		$count_types = array('vertical', 'horizontal', 'none');
		$langs = array('en' => 'English', 'fr' => 'French', 'de' => 'German', 'es' => 'Spanish', 'ja' => 'Japanese');
		$positions = array('before', 'after', 'both', 'manual');

		// watch for submission
		if (!empty($_POST)) {
			 
			// validate referrer
			check_admin_referer('bm_tweet_this_valid');

			if (isset($_POST['bm_tweet_this_data_text'])) {
				$defaultOptionVals['data_text'] = $_POST['bm_tweet_this_data_text'];
			}	
			if (isset($_POST['bm_tweet_this_data_count'])) {
				$data_count = $_POST['bm_tweet_this_data_count'];
				if (in_array($data_count, $count_types)) {
					$defaultOptionVals['data_count'] = $data_count;
				}
			}
			if (isset($_POST['bm_tweet_this_data_lang'])) {
				$data_lang = $_POST['bm_tweet_this_data_lang'];
				if (in_array($data_lang, $langs)) {
					$defaultOptionVals['data_lang'] = $data_lang;
				}
			}
			if (isset($_POST['bm_tweet_this_data_via'])) {
				$defaultOptionVals['data_via'] = $_POST['bm_tweet_this_data_via'];
			}
			if (isset($_POST['bm_tweet_this_data_related1'])) {
				$defaultOptionVals['data_related1'] = $_POST['bm_tweet_this_data_related1'];
			}
			if (isset($_POST['bm_tweet_this_data_related2'])) {
				$defaultOptionVals['data_related2'] = $_POST['bm_tweet_this_data_related2'];
			}
			if (isset($_POST['bm_tweet_this_class'])) {
				$defaultOptionVals['class'] = $_POST['bm_tweet_this_class'];
			}
			if (isset($_POST['bm_tweet_this_position'])) {
				if (in_array($_POST['bm_tweet_this_position'], $positions)) {
				   $defaultOptionVals['position'] = $_POST['bm_tweet_this_position'];
				}
			}

			 // update options
			 update_option(BM_TWEET_OPTIONS, $defaultOptionVals);
			 
			 // show success
			 echo '<div id="message" class="updated fade"><p><strong>' . __('Your settings have been saved.') . '</strong></p></div>';
			 
		}
	  
		// display the admin page
?>

	  <div style="width: 620px; padding: 10px">
		 <h2><?php _e('Tweet Button Options'); ?></h2>
		 <p>
			New Tweet Button has a number of configuration options. Details regarding the configuration items can be found on the
			<a href="http://twitter.com/goodies/tweetbutton" target="_blank">Twitter Tweet Button Page</a>.
		 </p>
		 <form action="" method="post" id="bm_tweet_this_form" accept-charset="utf-8" style="position:relative">
			 <?php wp_nonce_field('bm_tweet_this_valid'); ?>
			 <input type="hidden" name="action" value="update" />
			 <input type="hidden" name="page_options" value="bm_tweet_this_type" />
			 <table class="form-table">
				<tr valign="top">
					<th scope="row">Tweet Text</th>
					<td>
						<input name="bm_tweet_this_data_text" id="bm_tweet_this_data_text" value="<?php echo htmlentities($defaultOptionVals['data_text']); ?>" />
					</td>
				</tr>
				<tr valign="top">
					<th scope="row">&nbsp;</th>
					<td>
						Optional: This is the text that people will include in their Tweet when they share from your website. Leave blank to use the title of the page the button is on.
					</td>
				</tr>
				<tr valign="top">
					<th scope="row">Button Type</th>
					<td>
						<select name="bm_tweet_this_data_count" id="bm_tweet_this_data_count">
						   <?php foreach ($count_types as $count_type): ?>
						   <option value="<?php echo $count_type; ?>"<?php echo ($defaultOptionVals['data_count'] == $count_type) ? ' selected="selected"' : ''; ?>><?php echo ucwords($count_type); ?></option>
						   <?php endforeach; ?>
						</select>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row">Language</th>
					<td>
						<select name="bm_tweet_this_data_lang" id="bm_tweet_this_data_lang">
							 <?php foreach ($langs as $key => $lang): ?>
							 <option value="<?php echo $key; ?>"<?php echo ($defaultOptionVals['data_lang'] == $key) ? ' selected="selected"' : ''; ?>><?php echo ucwords($lang); ?></option>
							 <?php endforeach; ?>
						</select>
					</td>
				 </tr>
				 <tr valign="top">
					 <th scope="row">&nbsp;</th>
					 <td>
						This is the language that the button will render in on your website. People will see the Tweet dialog in their selected language for Twitter.com.
					 </td>
				 </tr>
				 <tr valign="top">
					 <th scope="row">Via Account</th>
					 <td>
						<input name="bm_tweet_this_data_via" id="bm_tweet_this_data_via" value="<?php echo stripslashes(htmlentities($defaultOptionVals['data_via'])); ?>" />
					 </td>
				 </tr>
				 <tr valign="top">
					 <th scope="row">&nbsp;</th>
					 <td>
						Optional: This user will be @ mentioned in the suggested Tweet.
					 </td>
				 </tr>
				 <tr valign="top">
					 <th scope="row">Recommended Accounts</th>
					 <td>
						<input name="bm_tweet_this_data_related1" id="bm_tweet_this_data_related1" value="<?php echo stripslashes(htmlentities($defaultOptionVals['data_related1'])); ?>" />
						<input name="bm_tweet_this_data_related2" id="bm_tweet_this_data_related2" value="<?php echo stripslashes(htmlentities($defaultOptionVals['data_related2'])); ?>" />
					 </td>
				 </tr>
				 <tr valign="top">
					 <th scope="row">&nbsp;</th>
					 <td>
						Optional: Recommend up to two Twitter accounts for users to follow after they share content from your website. These accounts could include your own, or that of a contributor or a partner.
					 </td>
				 </tr>
				 <tr valign="top">
					 <th scope="row">Container Class Name</th>
					 <td>
						<input name="bm_tweet_this_class" id="bm_tweet_this_class" value="<?php echo htmlentities($defaultOptionVals['class']); ?>" />
					 </td>
				 </tr>
				 <tr valign="top">
					 <th scope="row">Display Position</th>
					 <td>
						<select name="bm_tweet_this_position" id="bm_tweet_this_position">
						   <?php foreach ($positions as $position): ?>
						   <option value="<?php echo $position; ?>"<?php echo ($defaultOptionVals['position'] == $position) ? ' selected="selected"' : ''; ?>><?php echo $position; ?></option>
						   <?php endforeach; ?>
						</select>
						<p>
						   By default, the position is set to <em>manual</em> which allows you to manually place the button in your theme using the tag
						<code>bm_tweet_this_button()</code> within the_loop or use the shortcode <code>[tweet_button]</code> in your post body.
						</p>
						<p>
						   Even when this option is set to <em>before, after, or both</em>, you can still use the custom tag or shortcode to include
						   Tweet Button multiple times on your pages.
						</p>
					 </td>
				 </tr>
				 <tr valign="top">
					 <th scope="row">&nbsp;</th>
					 <td>
						<!-- button type="button" id="ctw-new-tweet-preview" class="button-primary" value="Preview">Preview</button -->
						<input type="submit" name="Submit" class="button-primary" value="<?php _e('Save Changes') ?>"/>
					 </td>
				 </tr>
				 <tr valign="top">
					 <th scope="row">&nbsp;</th>
					 <td>
						<div id="ctw-new-tweet-preview-window" style="display: none; width: 400px; height: 200px; padding: 10px; border: 1px solid #ccc; overflow-x: hidden; overflow-y: auto;">
						   <span style="color: #ccc">preview window</span>
						</div>					 
					 </td>
				 </tr>
			 </table>
   
		 </form>
	  </div>
	  
<?php
   /**
	* Short code handler for the button.
	*
	* @param mixed $attr
	* @param string $content
	*/
	function shortcode_handler($attr, $content = null) {
		return bm_tweet_this_button(false);
	}

   }
   
   /**
    * Places the button on the page depending on the position
    * selected by the user.
    *
    * @access	private
    * @param	string	$content
    * @param	string	$position
    * @return	string
    */
   private function build_bm_tweet_this_button($content, $position)
   {
	  $button = bm_tweet_this_button(false);
	  if ($position == 'before') {
		 $content = $button . $content;
	  } else if ($position == 'after') {
		 $content .= $button;
	  } else if ($position == 'both') {
		 $content = $button . $content . $button;
	  } else {
		 // assume manual position, do nothing
	  }
	  return $content;
   }
   
}

/**
 * Template function to add the like button.
 */
function bm_tweet_this_button($display = true) {

	global $wp_query;
	$post = (isset($wp_query->post)) ? $wp_query->post : false;
	if (is_object($post)) $post_id = $post->ID;
	else $post_id = $post;

	// initialize output
	$output = '';

	// get options
	$options = get_option(BM_TWEET_OPTIONS);
	$option_str = 'href="http://twitter.com/share" ' . 
				  'class="' . $options['class'] . '" '. 
				  'data-count="' . $options['data_count'] . '" ';
   
	if (!empty($options['data_text'])) {
		$option_str .= 'data-text="' . $options['data_text'] . '" ';
	}
	if (!empty($options['data_lang']) && $options['data_lang'] != 'en') {
		$option_str .= 'data-lang="' . $options['data_lang'] . '" ';
	}
	if (!empty($options['data_via'])) {
		$option_str .= 'data-via="' . $options['data_via'] . '" ';
	}
	$data_related = trim($options['data_related1'] . ':' . $options['data_related2'], ':');
	if (!empty($data_related)) {
		$option_str .= 'data-related="' . $data_related . '" ';
	}
	
	// determine if in the loop
	if (!empty($post_id) && in_the_loop()) {
		// determine if enabled
		$enabled = get_post_meta($post_id, 'enable_bm_tweet_this_button', true);
		if ($enabled == '' || $enabled == '1') {
			// create the output
			$output =  	'<a  ' . $option_str . ' data-url="' . get_permalink($post->ID) . '">Tweet</a>';
		}  	  
	} else {
		// create the output
		$output =  	'<a  ' . $option_str . ' data-url="' . get_permalink($post->ID) . '">Tweet</a>';
	}
   
	$output .= '<script type="text/javascript" src="http://platform.twitter.com/widgets.js"></script>';
   
	if ($display) echo $output;
	return $output;
}

// enable plugin on init
add_action('init', 'BMTweetThisInit');

function BMTweetThisInit() {
   $bm_tweet_this = new bm_tweet_this();
}
?>

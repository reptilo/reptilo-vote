<?php

/**
  Plugin Name: Reptilo Vote
  Plugin URI: https://github.com/reptilo/reptilo-vote
  Description: Ajax based voting utility for posts and pasges. Vote yes or no and the procentage will show. Use shortcode [reptilo-vote] in your post. Uses hidden postmeta fields for storage. It also calculates statistics and store it in wp_options with key "reptiloVoteStats"
  Version: 1.0
  Author: Kristian Erendi
  Author URI: http://reptilo.se
  License: GPL2
 */
/*
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
class ReptiloVote {

  public $postId;
  public $yes;
  public $no;
  public $total;
  public $percent;

  /**
   * Read the values from the post meta and initialize variables
   */
  function __construct($postId = null) {
    if ($postId == null) {
      $this->postId = get_the_ID();
    } else {
      $this->postId = $postId;
    }
    $this->yes = get_post_meta($this->postId, '_votes-yes', true);
    $this->no = get_post_meta($this->postId, '_votes-no', true);

    if (empty($this->yes)) {
      $this->yes = 0;
      add_post_meta($postId, '_votes-yes', 0, true);
    }
    if (empty($this->no)) {
      $this->no = 0;
      add_post_meta($postId, '_votes-no', 0, true);
    }
    $this->total = $this->yes + $this->no;
    if ($this->total == 0) {
      $this->percent = 100;
    } else {
      $percent = $this->yes / $this->total * 100;
      $this->percent = (int) $percent;
    }
  }

  /**
   * jQuery action
   * Update the number of yes or no votes and total, calculate a new percent
   * return/print results as json array
   * Updates also the stats.
   *
   * @param <type> $answer
   */
  public function update($answer) {
    if ($answer == 'yes') {
      $this->yes = $this->yes + 1;
      update_post_meta($this->postId, '_votes-yes', $this->yes);
    } else {
      $this->no = $this->no + 1;
      update_post_meta($this->postId, '_votes-no', $this->no);
    }
    $this->total = $this->total + 1;
    $percent = $this->yes / $this->total * 100;
    $this->percent = (int) $percent;
    $this->calcStats();   //update the stats

    $response = array(
        'status' => 'ok',
        'percent' => $this->percent,
        'yes' => $this->yes,
        'no' => $this->no,
        'total' => $this->total
    );
    return $response;
  }

  /**
   * Calculate statistics
   * Get data from the db, put it together and store it in wp_options with key "reptiloVoteStats"
   *
   * @global <type> $wpdb
   */
  public function calcStats() {
    global $wpdb;
    $table_postmeta = $wpdb->prefix . 'postmeta';
    $table_posts = $wpdb->prefix . 'posts';

    $sqlNofRated = "SELECT count(meta_id) AS nof_rated FROM " . $table_postmeta . " WHERE meta_key = '_votes-yes';";
    $sqlYesNo = "SELECT meta_key, sum(meta_value) AS votes FROM " . $table_postmeta . " WHERE meta_key in ('_votes-yes' ,'_votes-no') group by meta_key;";
    $sqlNofArt = "SELECT count(ID) FROM " . $table_posts . " WHERE post_status = 'publish';";
    $resNofRated = $wpdb->get_results($sqlNofRated);
    $resYesNo = $wpdb->get_results($sqlYesNo);
    $resNofArt = $wpdb->get_var($sqlNofArt);

    $stats['total_pub_arts'] = $resNofArt;
    foreach ($resNofRated as $res) {
      $stats['nof_rated'] = $res->nof_rated;
    }
    foreach ($resYesNo as $res) {
      $stats[$res->meta_key] = $res->votes;
    }

    //store it to wp_options
    $statsWPOptions = get_option("reptiloVoteStats");
    if (empty($statsWPOptions)) {
      add_option('reptiloVoteStats', $stats, '', 'yes');
    } else {
      update_option('reptiloVoteStats', $stats);
    }
  }

  /**
   * Print the javascript and HTLM code to the page
   * All text strings are translateable
   */
  public function includeCode() {
    $s1 = __("Is this information helpful?", 'reptilo-vote');
    $s2 = __("Think that this information is helpful", 'reptilo-vote');
    $s3 = __("of", 'reptilo-vote');
    $s4 = __("Yes", 'reptilo-vote');
    $s5 = __("No", 'reptilo-vote');

    $pluginRoot = plugins_url("", __FILE__);
    $actionFile = $pluginRoot . "/api/vote.php";
    $ajax_nonce = wp_create_nonce("reptilo-vote".$this->postId);
    $code = '<script type="text/javascript">
  var j$ = jQuery.noConflict();
  j$(document).ready(function(){
    j$("a.vote").click(function(event) {
      event.preventDefault();
      var self = jQuery(this);
      if(!self.parent().hasClass("voted")){  //continue only if no class "voted"
        if(self.hasClass("yes")){
          answer = "yes";
        } else {
          answer = "no";
        }
	      var data = {
            security: "' . $ajax_nonce . '",
            answer: answer,
            postid:"' . $this->postId . '"  
	      };          
          j$.ajax({
            type: "POST",
            url: "' . $actionFile . '",
            data: data,
            cache: false,
            success: function(data){
              console.log(data);
              j$("p.votes").addClass("voted");
              j$("span.large").html(data.percent + "%");
              j$("a.yes").attr("title", data.yes + " (' . $s3 . ' " + data.total + ")");
              j$("a.no").attr("title", data.no + " (' . $s3 . ' " + data.total + ")");
              j$("#reptilo-vote .vote p.votes a.yes").removeAttr("href");
              j$("#reptilo-vote .vote p.votes a.no").removeAttr("href");
            }
          });
        return false;
      }
    });
  });
</script>';


    $code .= '<div style="clear:both;"></div>
    <div id="reptilo-vote">
      <div class="vote">
        <p>' . $s1 . '</p>
        <div class="grade">
          <span class="large">' . $this->percent . '%</span>
          ' . $s2 . '
        </div>
        <p class="votes">
          <a class="vote yes" title="' . $this->yes . ' (' . $s3 . ' ' . $this->total . ')" href="#" tabindex="2">' . $s4 . '</a>
          <a class="vote no" title="' . $this->no . ' (' . $s3 . ' ' . $this->total . ')" href="#" tabindex="2">' . $s5 . '</a>
        </p>
     </div>
  </div>';

    return $code;
  }

}

/**
 * Enqueue jQuery och CSS
 */
function reptilo_load_scripts() {
  wp_deregister_script('jquery');
  wp_register_script('jquery', 'http://code.jquery.com/jquery-latest.min.js');
  wp_enqueue_script('jquery');

  wp_register_style('vote-style', plugins_url('style.css', __FILE__));
  wp_enqueue_style('vote-style');
}

add_action('wp_enqueue_scripts', 'reptilo_load_scripts');

/**
 * Shortcode for [reptilo-vote]
 * 
 * @param type $atts
 * @return string 
 */
function reptilo_display_vote($atts) {
  $rv = new ReptiloVote();
  return $rv->includeCode();
}

add_shortcode('reptilo-vote', 'reptilo_display_vote');


/**
 * enable language internationalization 
 */
load_plugin_textdomain('reptilo-vote', false, basename(dirname(__FILE__)) . '/languages');
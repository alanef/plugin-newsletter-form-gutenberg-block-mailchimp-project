<?php
/*
Plugin Name: Newsletter Form Block for  Mailchimp
Description: Simple, no-nonsense Mailchimp form block for Gutenberg editor.
Plugin URI: https://badlywired.com/foss-plugins/newsletter-form-gutenberg-block-mailchimp.php/
Version: 1.02
Requires at least: 4.9
Requires PHP: 5.6
Author URI: https://badlywired.com/
Author: Alan
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl-3.0.html

  Copyright 2018  

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

//  Exit if accessed directly.
defined('ABSPATH') || exit;

if (!class_exists('DrewM\MailChimp')) {
  require_once dirname( __FILE__ ) . '/mailchimp-api.php';
}

class wf_mailchimp_block {
  static $version;

  // get plugin version from header
  static function get_plugin_version() {
    $plugin_data = get_file_data(__FILE__, array('version' => 'Version'), 'plugin');
    self::$version = $plugin_data['version'];

    return $plugin_data['version'];
  } // get_plugin_version


  // hook things up
  static function init() {
    if (is_admin()) {
      if (false === self::check_gutenberg()) {
        return false;
      }

      add_filter('plugin_row_meta', array(__CLASS__, 'plugin_meta_links'), 10, 2);
      add_action('enqueue_block_editor_assets', array(__CLASS__, 'enqueue_block_editor_assets'));
      add_action('wp_ajax_wf_mailchimp_block_save_key', array(__CLASS__, 'save_key'));
      add_action('wp_ajax_nopriv_wf_mailchimp_block_save_key', array(__CLASS__, 'save_key'));
      add_action('wp_ajax_wf_mailchimp_block_save_list', array(__CLASS__, 'save_list'));
      add_action('wp_ajax_nopriv_wf_mailchimp_block_save_list', array(__CLASS__, 'save_list'));
      add_action('wp_ajax_wf_mailchimp_block_submit_form', array(__CLASS__, 'submit_form'));
      add_action('wp_ajax_nopriv_wf_mailchimp_block_submit_form', array(__CLASS__, 'submit_form'));
    } else {
      add_action('wp_enqueue_scripts', array(__CLASS__, 'enqueue_scripts'));
    }
  } // init


  static function save_key(){
    $options = get_option('wf-mailchimp-block');

    $options['api_key'] = '';
    $options['list'] = false;
    $options['lists'] = array();

    $lists = array();
    $lists_obj = array();
    
    if (!empty($_POST['api_key'])) {
      $api_key = sanitize_title($_POST['api_key']);
      $api_key = substr($api_key, 0, 40);
    } else {
      update_option('wf-mailchimp-block', $options);
      wp_send_json_error('Invalid API key.');
    }
    
    try {
      $mc = new DrewM\MailChimp($api_key);
      $options['api_key'] = $api_key;
      $raw_lists = $mc->get('lists');
      if ($mc->success()) {
        foreach($raw_lists['lists'] as $list) {
          $lists[$list['id']] = strip_tags($list['name']);
          $lists_obj[] = (object) array('label' => $list['name'], 'value' => $list['id']);
          if(!$options['list']) {
            $options['list'] = $list['id'];
          }
        }
      }

      $options['lists'] = $lists_obj;
      update_option('wf-mailchimp-block', $options);
      wp_send_json_success($lists);
    } catch(Exception $e) {
      update_option('wf-mailchimp-block', $options);
      wp_send_json_error('Invalid API key.');
    }
    die();
  } // save_key


  static function submit_form() {
    $fields = array();
    if (empty($_POST['email']) || !is_email($_POST['email']) || empty($_POST['name']) || !preg_match("/^[a-zA-Z ]*$/", $_POST['name'])) {
      wp_send_json_error(4);
    }

    $options = get_option('wf-mailchimp-block');

    try {
      $mc = new DrewM\MailChimp($options['api_key']);
    } catch(Exception $e) {
      wp_send_json_error();
    }

    $list = $options['list'];
    $email = sanitize_email($_POST['email']);
    $name = sanitize_text_field($_POST['name']);

    $member_info = $mc->get('search-members', array('list_id' => $list, 'query' => $email));

    if (isset($member_info['exact_matches']) && $member_info['exact_matches']['total_items'] == 0) {
      $status = 'pending';

      $mc->post('lists/' . $list . '/members', array('email_address' => $email, 'status' => $status, 'merge_fields' => array('FNAME' => $name)));

      if($mc->success()) {
        wp_send_json_success(1);
      } else {
        wp_send_json_error(2);
      }
    } else {
      wp_send_json_error(3);
    }

    die();
  } // submit_form


  static function save_list() {
    $options = get_option('wf-mailchimp-block');
    $options['list'] = sanitize_title($_POST['list']);
    update_option('wf-mailchimp-block', $options);
    die();
  } // save_list


  // some things have to be loaded earlier
  static function plugins_loaded() {
    self::$version = self::get_plugin_version();
  } // plugins_loaded


  // add links to plugin's description in plugins table
  static function plugin_meta_links($links, $file) {
    $support_link = '<a target="_blank" href="https://wordpress.org/support/plugin/mailchimp-block-gutenberg" title="' . __('Problems? We are here to help!', 'mailchimp-block-gutenberg') . '">' . __('Support', 'mailchimp-block-gutenberg') . '</a>';
    $review_link = '<a target="_blank" href="https://wordpress.org/support/view/plugin-reviews/mailchimp-block-gutenberg?filter=5#pages" title="' . __('If you like it, please review the plugin', 'mailchimp-block-gutenberg') . '">' . __('Review the plugin', 'mailchimp-block-gutenberg') . '</a>';

    if ($file == plugin_basename(__FILE__)) {
      $links[] = $support_link;
      $links[] = $review_link;
    }

    return $links;
  } // plugin_meta_links


  static function enqueue_scripts(){
    wp_register_script('wf-mailchimp-block-frontend', plugins_url('/assets/js/wf-mailchimp-block.js', __FILE__), array('jquery'), self::$version);

    $wf_mailchimp_block = array('ajaxurl' => admin_url( 'admin-ajax.php'));

    wp_localize_script('wf-mailchimp-block-frontend', 'wf_mailchimp_block', $wf_mailchimp_block);
    wp_enqueue_script('wf-mailchimp-block-frontend');
  } // enqueue_scripts


  // enqueue block files
  static function enqueue_block_editor_assets() {
    // enqueue the bundled block JS file
    wp_register_script('wf-mailchimp-block', plugins_url('/assets/js/editor.blocks.js', __FILE__), array('wp-editor', 'wp-i18n', 'wp-element', 'wp-blocks', 'wp-components'), self::$version);

    $options = get_option('wf-mailchimp-block');
    $wf_mailchimp_block = array(
      'api_key' => isset($options['api_key'])?$options['api_key']:'',
      'mc_list' => isset($options['list'])?$options['list']:'',
      'mc_lists' => !empty($options['lists'])?$options['lists']:array((object)array('label'=>'No Lists Found','value'=>'-1')),
      '_description' => __('Simple yet powerfull Mailchimp subscribe form.', 'mailchimp-block-gutenberg'),
      '_mailchimp' => __('Mailchimp Form', 'mailchimp-block-gutenberg'),
      '_mailchimp_lc' => __('mailchimp', 'mailchimp-block-gutenberg'),
      '_mc_list' => __('List', 'mailchimp-block-gutenberg'),
      '_form_css' => __('Form Style', 'mailchimp-block-gutenberg'),
      '_email_field_label' => __('Email Field Label', 'mailchimp-block-gutenberg'),
      '_name_field_label' => __('Name Field Label', 'mailchimp-block-gutenberg'),
      '_submit_field_label' => __('Submit Button Label', 'mailchimp-block-gutenberg'),
      '_success_message' => __('Success Message', 'mailchimp-block-gutenberg'),
      '_error_message' => __('Error Message', 'mailchimp-block-gutenberg'),
      '_submit_message' => __('Submit Error Message', 'mailchimp-block-gutenberg'),
      '_duplicate_message' => __('Duplicate Message', 'mailchimp-block-gutenberg'),
      '_api_key' => __('API Key', 'mailchimp-block-gutenberg'),
      '_api_info_start' => __('Open your', 'mailchimp-block-gutenberg'),
      '_api_info_console' => __('MailChimp account', 'mailchimp-block-gutenberg'),
      '_api_info_end' => __('to get an API Key.', 'mailchimp-block-gutenberg')
    );

    wp_localize_script('wf-mailchimp-block', 'wf_mailchimp_block', $wf_mailchimp_block);
    wp_enqueue_script('wf-mailchimp-block');
    wp_enqueue_script('wf-mailchimp-block-frontend', plugins_url('/assets/js/wf-mailchimp-block.js', __FILE__), array('jquery'), self::$version);

    // enqueue optional editor only styles
    wp_enqueue_style('wf-mailchimp-block', plugins_url('/assets/css/blocks.editor.css', __FILE__), array('wp-editor'), self::$version);
  } // enqueue_block_editor_assets


  // check if Gutenberg is available
  static function check_gutenberg() {
    if (false === defined('GUTENBERG_VERSION') && false === version_compare(get_bloginfo('version'), '4.9.9', '>')) {
        add_action('admin_notices', array(__CLASS__, 'notice_gutenberg_missing'));
        return false;
    }
  } // check_gutenberg


  // complain if Gutenberg is not available
  static function notice_gutenberg_missing() {
    echo '<div class="error"><p><b>Mailchimp Block</b> plugin requires Gutenberg editor to work. It is after all a block for Gutenberg ;)<br>Install the <a href="' . admin_url('plugin-install.php?s=gutenberg&tab=search&type=term') . '">Gutenberg plugin</a> and this notice will go away. Or update to WordPress v5.0.</p></div>';
  } // notice_gutenberg_missing
} // class wf_mailchimp_block


// get the party started
add_action('init', array('wf_mailchimp_block', 'init'));
add_action('plugins_loaded', array('wf_mailchimp_block', 'plugins_loaded'));

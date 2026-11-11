<?php
/**
 * Plugin Name: ABCOFIT Academy Site Setup
 * Description: Creates editable Academy starter pages and provides dynamic course catalogues with Tutor LMS support.
 * Version: 0.3.0
 * Requires PHP: 8.2
 * License: GPL-2.0-or-later
 */
if ( ! defined('ABSPATH') ) { exit; }

add_action('admin_menu', function() {
  add_management_page('ABCOFIT Setup', 'ABCOFIT Setup', 'manage_options', 'abcofit-setup', 'abcofit_setup_screen');
});

function abcofit_setup_screen() {
  if ( ! current_user_can('manage_options') ) { wp_die(esc_html__('Insufficient permissions.')); }
  $notice = '';
  if ( isset($_POST['abcofit_build_articles']) ) {
    check_admin_referer('abcofit_build_articles');
    $result = abcofit_build_article_drafts();
    $notice = sprintf('Created %d article drafts; kept %d existing articles unchanged.', $result['created'], $result['existing']);
  }
  if ( isset($_POST['abcofit_build_pages']) ) {
    check_admin_referer('abcofit_build_pages');
    $result = abcofit_build_pages();
    $notice = sprintf('Created %d pages; kept %d existing pages unchanged.', $result['created'], $result['existing']);
  }
  echo '<div class="wrap"><h1>ABCOFIT Academy Setup</h1>';
  if ($notice) { echo '<div class="notice notice-success"><p>'.esc_html($notice).'</p></div>'; }
  echo '<p>Create the editable Home, Courses, Learning Paths, Instructor-Led, Resources, About and My Learning pages from the approved site design. Existing pages with these slugs will not be overwritten.</p>';
  echo '<p><strong>Before using:</strong> Activate the ABCOFIT Academy Blocks theme, then create a verified backup or staging snapshot.</p>';
  echo '<form method="post">'; wp_nonce_field('abcofit_build_pages');
  submit_button('Create missing Academy pages', 'primary', 'abcofit_build_pages'); echo '</form>';
  echo '<h2>Optional: starter Resources article drafts</h2><p>Create three unpublished drafts for review: an IT Support Analyst career guide, a DNS explainer, and a Windows troubleshooting checklist. They will not appear publicly until you publish them.</p>';
  echo '<form method="post">'; wp_nonce_field('abcofit_build_articles');
  submit_button('Create article drafts', 'secondary', 'abcofit_build_articles'); echo '</form>';
  echo '<h2>Next steps</h2><ol><li>Go to Settings &gt; Reading and set Home as the static homepage.</li><li>Go to Settings &gt; Permalinks and use Post name.</li><li>Edit copy in Pages. Edit header/footer in Appearance &gt; Editor.</li><li>Publish Resources using Posts &gt; Add New.</li><li>Install Tutor LMS, then assign My Learning as its Dashboard page.</li><li>Configure course categories Short Courses, Self-Paced Courses and Instructor-Led.</li></ol>';
  echo '<p>No demo students, paid orders, course records, or fictional testimonials are created.</p></div>';
}

function abcofit_build_pages() {
  $list = [
    'home' => 'Home', 'courses' => 'Explore Courses', 'learning-paths' => 'Learning Paths',
    'instructor-led' => 'Instructor-Led Training', 'resources' => 'Resources',
    'about' => 'About ABCOFIT', 'my-learning' => 'My Learning'
  ];
  $results = ['created'=>0,'existing'=>0];
  foreach ($list as $slug=>$title) {
    $existing = get_page_by_path($slug, OBJECT, 'page');
    if ($existing) { $results['existing']++; continue; }
    $path = plugin_dir_path(__FILE__).'starter-pages/'.$slug.'.html';
    if (!is_readable($path)) { continue; }
    $content = file_get_contents($path);
    if ($content === false) { continue; }
    $page_id = wp_insert_post([
      'post_type'=>'page','post_status'=>'publish','post_name'=>$slug,
      'post_title'=>$title,'post_content'=>$content,'comment_status'=>'closed',
    ], true);
    if (!is_wp_error($page_id) && $page_id) { $results['created']++; }
  }
  return $results;
}

/** Prefer Tutor's native course-category taxonomy, but detect it rather than assuming. */
function abcofit_course_taxonomy() {
  if (!post_type_exists('courses')) { return ''; }
  foreach (['course-category', 'tutor_course_category'] as $tax) {
    if (taxonomy_exists($tax) && is_object_in_taxonomy('courses',$tax)) { return $tax; }
  }
  foreach ((array)get_object_taxonomies('courses') as $tax) {
    if (stripos($tax,'categor') !== false) { return $tax; }
  }
  return '';
}
function abcofit_format_title($post_id) {
  $tax = abcofit_course_taxonomy();
  if ($tax) {
    $terms = wp_get_post_terms($post_id, $tax);
    if (!is_wp_error($terms)) {
      foreach ($terms as $term) {
        if (in_array($term->slug,['short-courses','self-paced-courses','instructor-led'],true)) {
          return $term->name;
        }
      }
    }
  }
  return 'Course';
}
function abcofit_course_card($post) {
  $url = get_permalink($post);
  $title = get_the_title($post);
  $excerpt = get_the_excerpt($post);
  if (!$excerpt) { $excerpt = wp_trim_words(wp_strip_all_tags($post->post_content), 20); }
  $img = get_the_post_thumbnail($post,'medium_large',['loading'=>'lazy']);
  $art = $img ? $img : '<span class="ab-course-card-art-placeholder" aria-hidden="true">IT</span>';
  $is_free = function_exists('tutor_utils') && method_exists(tutor_utils(), 'is_course_free') ? tutor_utils()->is_course_free($post->ID) : null;
  $label = $is_free === true ? 'Free' : ($is_free === false ? 'See course price' : 'View course');
  return '<article class="ab-course-card"><a href="'.esc_url($url).'" class="ab-course-card-link"><div class="ab-course-card-art">'.$art.'</div><div class="ab-course-card-body"><div class="ab-course-card-labels"><span>'.esc_html($label).'</span><span>'.esc_html(abcofit_format_title($post->ID)).'</span></div><h3>'.esc_html($title).'</h3><p>'.esc_html($excerpt).'</p><span class="ab-card-open">View course &rarr;</span></div></a></article>';
}
function abcofit_course_query($limit=12, $search='', $type='all') {
  $args = ['post_type'=>'courses','post_status'=>'publish','posts_per_page'=>$limit,'no_found_rows'=>true];
  if ($search !== '') $args['s']=$search;
  $format_slugs=['short'=>'short-courses','self'=>'self-paced-courses','live'=>'instructor-led'];
  if (isset($format_slugs[$type])) {
    $tax=abcofit_course_taxonomy();
    if ($tax) $args['tax_query'] = [[ 'taxonomy'=>$tax,'field'=>'slug','terms'=>[$format_slugs[$type]] ]];
  }
  return new WP_Query($args);
}
function abcofit_course_list($atts=[], $featured=false) {
  $atts=shortcode_atts([], $atts, $featured?'abcofit_featured_courses':'abcofit_course_catalogue');
  $search='';$type='all';
  if (!$featured) {
    if (isset($_GET['abcofit_q']) && is_string($_GET['abcofit_q'])) {
      $search=sanitize_text_field(wp_unslash($_GET['abcofit_q']));
      $search=substr($search,0,100);
    }
    if (isset($_GET['abcofit_type']) && is_string($_GET['abcofit_type'])) {
      $possible=sanitize_key(wp_unslash($_GET['abcofit_type']));
      if (in_array($possible,['all','short','self','live'],true)) $type=$possible;
    }
  }
  ob_start();
  echo '<div class="ab-course-catalogue">';
  if (!$featured) {
    echo '<form method="get" class="ab-course-filter" action="'.esc_url(get_permalink()).'">';
    echo '<label for="abcofit-q">Search courses</label><input id="abcofit-q" name="abcofit_q" type="search" value="'.esc_attr($search).'" placeholder="Search by topic or skill">';
    echo '<label for="abcofit-type">Learning format</label><select id="abcofit-type" name="abcofit_type">';
    foreach (['all'=>'All courses','short'=>'Short courses','self'=>'Self-paced courses','live'=>'Instructor-led training'] as $key=>$name) {
      echo '<option value="'.esc_attr($key).'"'.selected($type,$key,false).'>'.esc_html($name).'</option>';
    }
    echo '</select><button type="submit">Find courses</button></form>';
  }
  if (!post_type_exists('courses')) {
    echo '<div class="ab-empty-courses"><h3>Our course library is in development.</h3><p>We are preparing free short courses, self-paced programmes and instructor-led training. Tutor LMS will power enrolment once installed.</p></div>';
  } else {
    $query=abcofit_course_query($featured?4:12,$search,$type);
    if ($query->have_posts()) {
      echo '<div class="ab-courses-grid">';
      foreach ($query->posts as $post) echo abcofit_course_card($post);
      echo '</div>';
    } else {
      echo '<div class="ab-empty-courses"><h3>'.($search || $type!=='all' ? 'No matching published courses yet.' : 'Our first courses are being prepared.').'</h3><p>Check back as we publish new learning materials.</p></div>';
    }
  }
  echo '</div>';
  return ob_get_clean();
}
add_shortcode('abcofit_course_catalogue',function($atts){return abcofit_course_list($atts,false);});
add_shortcode('abcofit_featured_courses',function($atts){return abcofit_course_list($atts,true);});
add_shortcode('abcofit_student_portal',function(){
  if (shortcode_exists('tutor_dashboard')) return do_shortcode('[tutor_dashboard]');
  return '<div class="ab-empty-courses"><h3>Student dashboard coming soon</h3><p>We will enable the learning portal when Tutor LMS is configured.</p></div>';
});

/** Optional editorial starter drafts. Never publish automatically and never overwrite posts. */
function abcofit_build_article_drafts() {
  $file=plugin_dir_path(__FILE__).'starter-articles/metadata.json';
  if (!is_readable($file)) return ['created'=>0,'existing'=>0];
  $data=json_decode(file_get_contents($file),true);
  if (!is_array($data)) return ['created'=>0,'existing'=>0];
  $result=['created'=>0,'existing'=>0];
  foreach ($data as $slug=>$spec) {
    $existing=get_page_by_path($slug, OBJECT, 'post');
    if ($existing) { $result['existing']++; continue; }
    $article_path=plugin_dir_path(__FILE__).'starter-articles/'.$slug.'.html';
    if (!is_readable($article_path)) continue;
    $content=file_get_contents($article_path);
    if ($content===false) continue;
    $term=term_exists($spec['category'],'category');
    if (!$term) $term=wp_insert_term($spec['category'],'category');
    $category_id=(!is_wp_error($term) && is_array($term)) ? (int)$term['term_id'] : (is_numeric($term)?(int)$term:0);
    $args=['post_type'=>'post','post_status'=>'draft','post_name'=>sanitize_title($slug),
       'post_title'=>sanitize_text_field($spec['title']),'post_content'=>$content,
       'comment_status'=>'closed'];
    if ($category_id) $args['post_category']=[$category_id];
    $id=wp_insert_post($args,true);
    if (!is_wp_error($id) && $id) $result['created']++;
  }
  return $result;
}

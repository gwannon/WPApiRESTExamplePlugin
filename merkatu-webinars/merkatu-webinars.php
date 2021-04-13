<?php
/**
 * Plugin Name: MerkatuWebinars
 * Plugin URI:  https://www.merkatu.com/
 * Description: Shortcode que conecta con un API REST y saca los eventos del plugin "Events Managers" que esten en la categoría con slug webinars. Hay que instalarlo en el wordpress al que se llame y en que se quiera sacar los datos. Ejemplo: [webinars apiurl="https://midominio.com"]
 * Version:     1.0
 * Author:      Merkatu
 * Author URI:  https://www.merkatu.com/
 * License:     GNU General Public License v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: merkatu-webinars
 *
 * PHP 7.3
 * WordPress 5.5.3
 */

add_action('rest_api_init', function () {
  register_rest_route( 'merkatu-events/v1', '/webinars', array(
    'methods' => 'GET',
    'callback' => 'merkatu_rest_get_webinars',
  ));
});

function merkatu_rest_get_webinars( $data ) {
  $args = array(
    'post_type' => 'tribe_events',
    'posts_per_page' => -1,
    'post_status' => 'publish',
    'tax_query' => array(
    'relation' => 'AND',
      array(
        'taxonomy' => 'tribe_events_cat',
        'field'    => 'slug',
        'terms'    => 'webinars',
      )
    ),
  );
  $posts = get_posts($args); 
  if (empty( $posts )) return null;

  $events = array();
  foreach ($posts as $post) {
    $date = explode(" ", tribe_get_start_date($post->ID, false, 'Y M d H:i'));
    $events[] = array(
      'title' => $post->post_title,
      'url' => get_the_permalink($post->ID),
      'excerpt' => apply_filters('the_excerpt', wp_trim_words($post->post_content, 55)),
      'year' => $date[0],
      'month' => $date[1],  	
      'day' => $date[2],
      'hour' => $date[3],
      'image' => get_the_post_thumbnail_url($post->ID, 'large'),
    );
  }
  return $events;
}


//Shortocodes --------------------------------------------------------------
function merkatu_webminars_shortcode($params = array(), $content = null) {
  global $post;
  ob_start(); 

  //Llamada CURL
  $ch = curl_init($params['apidomain']."/wp-json/merkatu-events/v1/webinars");
  curl_setopt($ch, CURLOPT_FILE, $fp);
  curl_setopt($ch, CURLOPT_HEADER, 0);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  $response = json_decode(curl_exec($ch), false);
  curl_close($ch);

  if(count($response) > 0) {
    foreach($response as $webinar) { /*print_r($webinar);*/ ?>
      <div>
        <h3><a href=""><?php echo $webinar->title; ?></a></h3>
        <div class="date"><span class="dia"><?php echo $webinar->day; ?></span><br/><span class="mes"><?php echo $webinar->month; ?></span><br/><span class="hora"><?php echo $webinar->hour; ?></span></div>
        <?php if($webinar->image != '') { ?><img src="<?php echo $webinar->image; ?>" alt="<?php echo $webinar->title; ?>" /><?php } ?>
        <?php echo $webinar->excerpt; ?>
      </div>
    <?php } 
  } else { ?>
    <h3>No hay webinars</h3>
  <?php }
  $html = ob_get_clean(); 
  return $html;
}
add_shortcode('webinars', 'merkatu_webminars_shortcode');

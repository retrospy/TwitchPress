<?php
/**
 * TwitchPress Shortcode - Channel List
 * 
 * @author Ryan Bayne  
 */
 
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if( !class_exists( 'TwitchPress_Shortcode_Channel_List' ) ) :

class TwitchPress_Shortcode_Channel_List {
    
    var $atts = array( 'empty' );
    var $response= null;
    var $channels = array();

    public function init() {
        add_action( 'wp_enqueue_scripts', array( $this, 'register_styles'), 4 );   
        $this->register_styles();
        $this->get_twitch_data();
        $this->prepare_data(); // orderby, blacklist, priority channel positioning etc 
    }
    
    /**
    * Get data from Twitch...
    * 
    * @version 1.0
    */
    public function get_twitch_data() {
        $twitch_api = new TwitchPress_Twitch_API();
        switch ( $this->atts['type'] ) {
           case 'team':  
                return false;
             break;          
           case 'followers':

             break;
           case 'specific':
                
             break;
           default:                          
                $returned_channels = $twitch_api->get_streams( null, null, null, 10, null, null, null, null ); 
                  
                foreach( $returned_channels->data as $key => $user ) {  
                    // Get broadcasters followers...
                    $followers = $twitch_api->get_users_follows_to_id( null, null, $user->user_id );
                        
                    // Get broadcasters total views...
                    $channel = $twitch_api->get_user_by_id( $user->user_id );

                    $this->channels[$key]['user_id'] = $user->user_id;      
                    $this->channels[$key]['name'] = $user->user_login;      
                    $this->channels[$key]['logo'] = $channel->data[0]->profile_image_url;   
                    $this->channels[$key]['display_name'] = $user->user_name;   
                    $this->channels[$key]['status'] = __( 'Live', 'twitchpress' );   
                    $this->channels[$key]['followers'] = $followers->total;   
                    $this->channels[$key]['game'] = $user->game_name;   
                    $this->channels[$key]['thumbnail_url'] = $user->thumbnail_url;   
                    $this->channels[$key]['viewer_count'] = $user->viewer_count; // Live Viewers      
                    $this->channels[$key]['display'] = 'online';   
                    $this->channels[$key]['views'] = $channel->data[0]->view_count;   
                }
                 
             break;             
        }  
        unset($twitch_api);          
    }
    
    /**
    * Make additional alterations to data that can be applied to
    * the results for all endpoints...
    * 
    * @version 1.0
    */
    public function prepare_data() {
        // Order
        if( $this->atts['orderby'] ) {
            $this->channels = wp_list_sort(
                $this->channels,
                $this->atts['orderby'],
                'DESC',
                true
            );
        }        
    }
    
    public function register_scripts() {
  
    }  
    
    /**
    * Register styles for channel list shortcode. 
    * Constants currently set in core pending proper integration using API. 
    *   
    * @version 1.0
    */
    public function register_styles() {
        wp_enqueue_style( 'dashicons' );                                             
        wp_register_style( 'twitchpress_shortcode_channellist', TWITCHPRESS_PLUGIN_DIR_PATH . 'shortcodes/channellist/twitchpress-shortcode-channellist.css' );   
        wp_enqueue_style( 'twitchpress_shortcode_channellist', TWITCHPRESS_PLUGIN_DIR_PATH . 'shortcodes/channellist/twitchpress-shortcode-channellist.css' );
    }
    
    public function output() {
        switch ( $this->atts['style'] ) {
           case 'error':
                return $this->atts['error'];
             break; 
           case 'shutters':
                return $this->style_shutters();
             break;
           default:
                return $this->style_shutters();
             break;
        }    
    }
    
    public function style_shutters() {
        ob_start(); 
        
        $online = '';
        $offline = '';
        $closed = '';
        $articles = 0; /* number of html articles generated */

        // Get all the user ID's for adding to a single API call...
        $user_id_array = array();
        
        // If no team members, the team name is probably incorrect...
        if( !$this->channels ) {?>
            <main>
                <section>No channels were returned by Twitch.tv</section>   
                <section id="open"></section>
            </main>
            <?php 
            return ob_get_clean();
        }
        
        foreach( $this->channels as $key => $user ) {   
                 
            // Build article HTML based on the output demanded i.e. online or offline only or all...
            if( $user['display'] !== 'offline' ) {
                $thumbnail_url = str_replace( array( '{width}', '{height}'), array( '640', '360' ), $user['thumbnail_url'] );
                $online .= $this->shutter_article( $user, 'online', $user['viewer_count'], $thumbnail_url );
            } elseif( $this->$user['display'] == 'all' || $this->user['display'] == 'offline' ) {
                $offline .= $this->shutter_article( $user, 'offline', 0 );
            } 
               
            unset( $stream_obj );
        }         
        
        // Wrap articles in section html...
        $online_section = '<section id="online">' . $online . '</section>';
        $html_offline = '<section id="offline">' . $offline . '</section>'; 
        ?>
        
        <main>
            <?php 
            // All this is simply to avoid outputting empty section HTML...
            if( $user['display'] == 'all' || $user['display'] == 'online' ){ echo $online_section; } 
            if( $user['display'] == 'all' || $user['display'] == 'offline' ){ echo $html_offline; } 
            ?>
        </main>
           
        <?php  
        return ob_get_clean();
    }
    
    /**
    * HTML structure for a single channel (article)
    * 
    * @param mixed $user
    * @param mixed $status
    * @param mixed $viewer_count
    * @param mixed $preview
    * 
    * @version 2.0
    */
    static function shutter_article( $user, $status, $viewer_count = 0, $preview = '' ) {
        ob_start(); 
        ?>
            <article class="channel" id="<?php echo esc_attr( $user['name'] ); ?>">                                
            
                <a class="channel-link" href="https://www.twitch.tv/<?php echo esc_url( $user['name'] ); ?>" target="_blank">                                    
                
                    <header class="channel-primary row">                                        
                        <div class="channel-logo col-s">
                            <img src="<?php echo esc_url( $user['logo'] ); ?>">
                        </div>                                        
                        <div class="col-lg">                                            
                            <div class="row">                                                
                                <h3 class="channel-name"><?php echo esc_attr( $user['display_name'] ); ?></h3>                                                
                                <div class="channel-curr-status"><?php echo esc_attr( $status ); ?></div>                                            
                            </div>                                            
                            <div class="channel-status row"><?php echo esc_attr( $user['status'] ); ?></div>                                        
                        </div>                                    
                    </header>
                    
                    <div class="stream-preview row">
                        <img src="<?php echo esc_attr( $preview ); ?>">
                    </div>
                    <div class="channel-details row">                                    
                        <ul class="channel-stats">                                        
                            <li><i class="dashicons dashicons-heart"></i><?php echo esc_attr( $user['followers'] ); ?></li>  
                            <li><i class="dashicons dashicons-visibility"></i><?php echo esc_attr( $user['views'] ); ?></li>                                    
                        </ul>
                        <div class="stream-details">                                    
                            <span class="stream-game"><?php echo esc_attr( $user['game'] ); ?></span>
                            <span class="stream-stats">
                            <i class="dashicons dashicons-admin-users"></i><?php echo esc_attr( $viewer_count ); ?></span>                                
                        </div>
                        <div class="more-btn">
                            <i class="fa fa-chevron-down"></i> 
                        </div>
                    </div>
                </a>
            </article>
        <?php        
        return ob_get_clean();   
    }

}

endif;

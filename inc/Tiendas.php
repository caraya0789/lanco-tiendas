<?php

use Carbon_Fields\Container;
use Carbon_Fields\Field;

class Lanco_Tiendas {

	protected static $_instance;

	public static function get_instance() {
		if(self::$_instance === null)
			self::$_instance = new self();

		return self::$_instance;
	}

	public function hooks() {
		add_action( 'after_setup_theme', [ $this, 'load_fields' ] );
		add_action('init', [ $this, 'remove_editor' ]);
		add_filter( 'manage_edit-shop_order_columns', [ $this, 'add_orders_column' ] );
		add_action( 'manage_shop_order_posts_custom_column', [ $this, 'populate_orders_column' ] );

		add_action( 'show_user_profile', [ $this, 'add_user_fields' ] );
		add_action( 'edit_user_profile', [ $this, 'add_user_fields' ] );

		add_action( 'personal_options_update', [ $this, 'save_user_fields' ] );
		add_action( 'edit_user_profile_update', [ $this, 'save_user_fields' ] );

		add_action( 'pre_get_posts', [ $this, 'restrict_orders_for_shop_managers' ] );

		add_filter( 'views_edit-shop_order', [ $this, 'hide_shop_order_views_for_shop_managers' ] );

	}

	public function add_orders_column( $columns ) {
	    $columns['tienda'] = 'Tienda';
	    return $columns;
	}

	public function populate_orders_column( $column ) {
		global $post;
		switch( $column ) {
			case 'tienda': 
				$tienda_id = get_post_meta( $post->ID, 'store_id', true );
				echo (!empty($tienda_id)) ? get_the_title( $tienda_id ) : 'Lanco Store';
			break;
		}
	}

	public function load_fields() {
		require_once LANCORE_PATH . '/vendor/autoload.php';
    	\Carbon_Fields\Carbon_Fields::boot();

    	add_action( 'carbon_fields_register_fields', [ $this, 'create_fields' ] );
	}

	public function create_fields() {

		$options = [];

		$provincias = get_posts([
			'post_type' => 'provincia',
			'posts_per_page' => -1,
			'orderby' => 'title',
			'order' => 'ASC'
		]);

		foreach($provincias as $provincia) {
			$cantones = get_posts([
				'post_type' => 'canton',
				'posts_per_page' => -1,
				'orderby' => 'title',
				'order' => 'ASC',
				'meta_key' => 'provincia',
				'meta_value' => $provincia->ID
			]);

			foreach($cantones as $canton) {
				$key = $provincia->ID . '_' . $canton->ID;
				$value = $provincia->post_title . ' - ' . $canton->post_title;
				$options[$key] = $value;
			}
		}

		Container::make( 'post_meta', 'Información de Pedidos' )
		    ->where( 'post_type', '=', 'tienda' )
		    ->add_fields([

		    	Field::make( 'text', 'email', 'Correo Electrónico' ),

		    	Field::make( 'set', 'cantones', 'Cantones' )
		    		->add_options( $options ),

		    	Field::make( 'text', 'timestamp', '	TimeStamp' )

		    ]);

		Container::make( 'post_meta', 'Información de Pagos' )
		    ->where( 'post_type', '=', 'tienda' )
		    ->add_fields([

		    	Field::make( 'text', 'adquiriente', 'ID Adquiriente' ),
		    	
		    	Field::make( 'text', 'comercio', 'ID Comercio' ),
		    	
		    	Field::make( 'text', 'clave', 'Clave V-POS2' ),
		    	
		    	Field::make( 'text', 'wallet', 'Clave Wallet' )

		    ]);
	}

	public function remove_editor() {
		if( is_admin() ) {
			remove_post_type_support( 'tienda', 'editor' );
	    }
	}

	public function add_user_fields( $user ) { 
		if( !current_user_can( 'manage_options' ) || !in_array( 'shop_manager', (array) $user->roles ) )
			return;

		$tiendas = get_posts([
			'post_type' => 'tienda',
			'posts_per_page' => -1,
			'post_status' => 'publish'
		]);

		$user_tiendas = get_user_meta( $user->ID, 'user_tiendas', true );
		?>
		<h3>Que tiendas puede accesar</h3>
		<table class="form-table">
		    <tr>
		        <th><label>Tiendas</label></th>
		        <td>
		        	<?php foreach($tiendas as $t): ?>
	        		<p>
						<input type="checkbox" name="user_tiendas[]" <?php echo in_array($t->ID, $user_tiendas) ? 'checked' : '' ?> id="user_tiendas_<?php echo $t->ID ?>" value="<?php echo $t->ID ?>">
						<label for="user_tiendas_<?php echo $t->ID ?>"><?php echo $t->post_title ?></label>
					</p>
					<?php endforeach ?>
				</td>
		    </tr>
		</table> <?php
	}

	public function save_user_fields( $user_id ) {
		$user = get_user_by( 'id', $user_id );
		if( !current_user_can( 'manage_options' )  || !in_array( 'shop_manager', (array) $user->roles ))
			return;

		update_user_meta( $user_id, 'user_tiendas', $_POST['user_tiendas'] );
	}

	public function restrict_orders_for_shop_managers( $query ) {

		if( !is_admin() || !$query->is_main_query() || $query->get('post_type') != 'shop_order')
			return;

		$user = wp_get_current_user();
		if( !in_array('shop_manager', $user->roles) )
			return;

		$user_tiendas = get_user_meta( $user->ID, 'user_tiendas', true );
		if(empty($user_tiendas))
			return;

		$meta_query = $query->get('meta_query');

		$meta_query[] = [
			'key' => 'store_id',
			'value' => $user_tiendas,
			'compare' => 'IN'
		];

		$query->set('meta_query', $meta_query);

	}

	public function hide_shop_order_views_for_shop_managers( $views ) {
		$user = wp_get_current_user();
		if( in_array('shop_manager', $user->roles) )
			return [];

		return $views;
	}

}
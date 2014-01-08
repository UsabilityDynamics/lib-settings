<?php
/**
 * Settings Library
 *
 * @namespace UsabilityDynamics
 */
namespace UsabilityDynamics {

  if( !class_exists( 'UsabilityDynamics\Settings' ) ) {

    /**
     * Class Settings
     *
     * @package UsabilityDynamics
     */
    class Settings {

      /**
       * Settings Class version.
       *
       * @public
       * @static
       * @property $version
       * @type {Object}
       */
      static public $version = '0.2.1';

      /**
       * Prefix for option keys to unique
       *
       * @var string
       */
      private $prefix = '';

      /**
       * Whether or not to hash option keys before saving
       *
       * @var bool
       */
      private $hash_keys = false;

      /**
       * Data storage
       *
       * @var array
       */
      private $_data = array();

      /**
       * Settings Schema.
       *
       * @static
       * @property $_schema
       * @type {Object}
       */
      private $_schema = false;

      /**
       * Settings Namespace.
       *
       * @static
       * @property $_namespace
       * @type {String}
       */
      private $_namespace = false;

      /**
       * Storage Key.
       *
       * @static
       * @property $_key
       * @type {String}
       */
      private $_key = false;

      /**
       * Settings Format.
       *
       * Options are: 'json', 'object', 'array', 'hash-map'.
       *
       * @static
       * @property $_format
       * @type {String}
       */
      private $_format = null;

      /**
       * Storage Location
       *
       * @static
       * @property $_store
       * @type {String}
       */
      private $_store = false;

      /**
       * Toggle Debugger.
       *
       * @static
       * @property $_debug
       * @type {Boolean}
       */
      private $_debug = false;

      /**
       * Automatically Save.
       *
       * @static
       * @property $_debug
       * @type {Boolean}
       */
      private $auto_commit = false;

      /**
       * Instance Valid.
       *
       * @static
       * @property $is_valid
       * @type {Boolean}
       */
      public $is_valid = true;

      /**
       * Constructor
       *
       * @param bool $args
       *
       * @example
       *
       *    $_settings = new Settings(array(
       *      "store" => "options"
       *    ));
       *
       *    $_settings->set( 'my.key', 'my.value' );
       *
       * @internal param \UsabilityDynamics\type $defaults
       * @internal param bool|\UsabilityDynamics\type $force_save
       * @internal param string|\UsabilityDynamics\type $prefix
       * @internal param bool|\UsabilityDynamics\type $hash_keys
       */
      public function __construct( $args = false ) {

        $args = Utility::parse_args( $args, array(
          "namespace"   => "",
          "key"         => "",
          "auto_commit" => false,
          "debug"       => false,
          "store"       => false,
          "schema"      => false
        ) );

        // Load Schema.
        if( $args->schema ) {
          $this->set_schema( $args->schema );
        }

        // Set Storage Location(s).
        if( $args->store ) {
          $this->_store = $args->store;
        }

        // Set Storage Key.
        if( $args->key ) {
          $this->_key = $args->key;
        }

        // Set Format to enforce.
        if( $args->format ) {
          $this->_format = $args->format;
        }
        // Toggle Debugger.
        if( $args->debug ) {
          $this->_debug = $args->debug;
        }

        // Set Settings Namespace.
        if( $args->namespace ) {
          $this->_namespace = $args->namespace;
        }

        // Set Auto Commit.
        if( $args->auto_commit ) {
          $this->auto_commit = $args->auto_commit;
        }

        // Load Initial.
        $this->_load();

      }

      /**
       * Getter for options
       *
       * @param bool|\UsabilityDynamics\type $key
       *
       * @param bool                         $default
       *
       * @return type
       */
      public function get( $key = false, $default = false ) {

        // Return all data.
        if( !$key ) {
          return $this->_output( $this->_data );
        }

        // Resolve dot-notated key.
        if( strpos( $key, '.' ) ) {
          return $this->_resolve( $this->_data, $key, $default );
        }

        // Return value or default.
        return $this->_data[ $key ] ? $this->_data[ $key ] : $default;

      }

      /**
       * Setter for options
       *
       * @param string|\UsabilityDynamics\type $key
       * @param bool|\UsabilityDynamics\type   $value
       * @param bool                           $bypass_validation
       *
       * @internal param bool|\UsabilityDynamics\type $force_save
       *
       * @return \UsabilityDynamics\Settings
       */
      public function set( $key = '', $value = false, $bypass_validation = false ) {

        if( !$this->_data ) {
          $this->_data = array();
        }
        // First argument is an object/array.
        if( Utility::get_type( $key ) === 'object' || Utility::get_type( $key ) === 'array' ) {
          Utility::extend( $this->_data, (object) $key );
        }

        // Standard key & value pair
        if( Utility::get_type( $key ) === 'string' && ( Utility::get_type( $value ) === 'string' || Utility::get_type( $value ) === 'number' || Utility::get_type( $value ) === 'boolean' ) ) {

          if( strpos( $key, '.' ) ) {
            self::set_val( $this->_data, $key, $value );
          } else {
            $this->_data[ $key ] = $value;
          }

        }

        // echo "<br />$key " . Utility::get_type( $value ) . ' - ';

        // Standard key with complex value.
        if( Utility::get_type( $key ) === 'string' ) {

          if( Utility::get_type( $value ) === 'object' ) {

            if( strpos( $key, '.' ) ) {
              self::set_val( $this->_data, $key, $value );
            } else {

              if( Utility::get_type( $this->_data[ $key ] ) === 'object' ) {
                $this->_data[ $key ] = Utility::extend( $this->_data[ $key ], $value );
              } else {
                $this->_data[ $key ] = $value;
              }

            }

          }

          // Standard key with array value
          if( Utility::get_type( $value ) === 'array' ) {

            if( strpos( $key, '.' ) ) {
              self::set_val( $this->_data, $key, $value );
            } else {
              $this->_data[ $key ] = array_unique( array_merge( (array) $this->_data[ $key ], $value ) );
            }

          }

        }

        // Validate if we have a schema.
        if( $this->_schema ) {
          // $this->_validate();
        }

        // Commit to Storage if validation passed.
        if( $this->is_valid && $this->auto_commit ) {
          $this->commit();
        }

        return $this;

      }

      /**
       * Handle File Transfer for downloading
       *
       * @param array $args
       *
       * @return bool
       */
      public
      function file_transfer( $args = array() ) {

        $args = Utility::parse_args( $args, array(
          "name"     => "settings",
          "format"   => "json",
          "cache"    => 'public',
          "filename" => null,
          "charset"  => 'utf8'
        ) );

        $args->filename = $args->filename ? $args->filename : $args->name . '-' . date( 'Y-m-d' ) . '.' . $args->format;

        // Ensure headers have not been sent.
        if( headers_sent() ) {
          return false;
        }

        // Send headers.
        header( "Cache-Control: {$args->cache}" );
        header( "Content-Disposition: attachment; filename={$args->filename}" );
        header( "Content-Type: text/plain; charset={$args->charset}" );
        header( "Content-Description: File Transfer" );
        header( "Content-Transfer-Encoding: binary" );

        // Prepare in needed format.
        $_data = $this->_output( $this->get(), $args->format );

        // Write data.
        die( $_data );

      }

      /**
       * Set Schema from a string or objct.
       *
       * @param bool $schema
       *
       * @return array|bool|mixed|object
       */
      private
      function set_schema( $schema = false ) {

        //$_retriever = new \JsonSchema\Uri\UriRetriever;

        try {

          // Take schema as given.
          if( gettype( $schema ) === 'array' ) {
            $this->_schema = (object) $schema;
          }

          // Take schema as given.
          if( gettype( $schema ) === 'object' ) {
            $this->_schema = $schema;
          }

          // Load schema from a file.
          if( gettype( $schema ) === 'string' ) {
            $this->_schema = json_decode( file_get_contents( $schema ) );
          }

        } catch( Exception $error ) {
          $this->console( 'Caught exception: ' . $error->getMessage() );
        }

        return $this->_schema ? $this->_schema : false;

      }

      /**
       * Commit Settings to Storage.
       *
       */
      public
      function commit() {

        switch( $this->_store ) {

          case 'options':
            // Convert to JSON String.
            $_value = json_encode( $this->_data, JSON_FORCE_OBJECT );
            $_value = \update_option( $this->_key, $_value );
            break;

        }

        return $this;

      }

      /**
       * Validate Settings against Schema
       *
       */
      private
      function _validate() {
        $validator = new \JsonSchema\Validator();

        // Process Validation.
        $validator->check( $this->_data, $this->_schema );

        if( $validator->isValid() ) {
          $this->is_valid = true;
          $this->_console( "The supplied JSON validates against the schema." );
        } else {
          $this->is_valid = false;

          $this->_console( "JSON does not validate. Violations:" );

          foreach( $validator->getErrors() as $error ) {
            $this->_console( sprintf( "[%s] %s\n", $error[ 'property' ], $error[ 'message' ] ) );
          }

        }

      }

      /**
       * Library Debugger
       *
       * @param $data
       */
      private
      function _console( $data ) {

        if( $this->_debug ) {
          echo sprintf( "lib-settings debug: [%s].", $data );
        }

      }

      /**
       * Load options from DB
       *
       * @return \UsabilityDynamics\Settings
       */
      private
      function _load() {

        switch( $this->_store ) {

          // WordPress Site Options.
          case 'options':

            // Load from options.
            $_value = \get_option( $this->_key );

            // If already an array it must have been serialized
            if( gettype( $_value ) === 'array' ) {
              return $this->_output( $this->_data = $_value );
            }

            try {

              $_value = json_decode( $_value, true );

            } catch( Exception $error ) {
              $this->_console( 'Caught exception: ' . $error->getMessage() );
            }

            $this->_data = $_value;

            break;

          default:

            break;

        }

        return $this->_data;

      }

      /**
       * Prepare Data for Output
       *
       * @param      $data
       *
       * @param bool $format
       *
       * @return array|mixed|string|void
       */
      private
      function _output( $data, $format = false ) {

        $format = $format ? $format : $this->_format;

        // Stringify.
        if( $format === 'json' ) {
          return json_encode( $data );
        }

        // Deeep Object.
        if( $format === 'object' ) {
          return json_decode( json_encode( $data ) );
        }

        return $data;

      }

      function set_val( array &$arr, $path, $val ) {
        $loc = & $arr;

        foreach( explode( '.', $path ) as $step ) {
          $loc = & $loc[ $step ];
        }

        return $loc = $val;

      }

      /**
       * Expand Array
       * @source http://stackoverflow.com/questions/17365059/how-to-unflatten-array-in-php-using-dot-notation
       *
       * @param     $array
       * @param int $level
       *
       * @return array
       */
      private
      function _expand( $array, $level = 0 ) {
        $result = array();
        $next   = $level + 1;

        foreach( $array as $key => $value ) {
          $tree = explode( '.', $key );

          if( isset( $tree[ $level ] ) ) {
            if( !isset( $tree[ $next ] ) ) {
              $result[ $tree[ $level ] ][ 'id' ]    = $key;
              $result[ $tree[ $level ] ][ 'title' ] = $value;
              if( !isset( $result[ $tree[ $level ] ][ 'children' ] ) ) {
                $result[ $tree[ $level ] ][ 'children' ] = array();
              }
            } else {
              if( isset( $result[ $tree[ $level ] ][ 'children' ] ) ) {
                $result[ $tree[ $level ] ][ 'children' ] = array_merge_recursive( $result[ $tree[ $level ] ][ 'children' ], self::_expand( array( $key => $value ), $next ) );
              } else {
                $result[ $tree[ $level ] ][ 'children' ] = self::_expand( array( $key => $value ), $next );
              }
            }

          }
        }

        return $result;

      }

      /**
       * Resolve dot-notated key.
       *
       * @source http://stackoverflow.com/questions/14704984/best-way-for-dot-notation-access-to-multidimensional-array-in-php
       *
       * @param       $a
       * @param       $path
       * @param null  $default
       *
       * @internal param array $a
       * @return array|null
       */
      private
      function _resolve( $a, $path, $default = null ) {

        $current = $a;
        $p       = strtok( $path, '.' );

        while( $p !== false ) {

          if( !isset( $current[ $p ] ) ) {
            return $default;
          }

          $current = $current[ $p ];
          $p       = strtok( '.' );

        }

        return $current;

      }

    }

  }

}
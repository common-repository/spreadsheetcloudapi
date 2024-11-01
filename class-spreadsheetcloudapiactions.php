<?php
if ( ! defined( 'ABSPATH' ) ) exit; 

class Spreadsheet_Cloud_API_Actions {
    public static function sclapi_admin_init() {
        $options = get_option( Sclapi_Plugin_Const::SCLAPI_OPTIONS );
        if ( empty( $options ) ) {
            $options = array(
                Sclapi_Plugin_Const::API_KEY  => '',
            );
            update_option( Sclapi_Plugin_Const::SCLAPI_OPTIONS, $options ); 
        }

        wp_register_script ( 'sclapi_options_script', plugins_url('/options/options.js', __FILE__), array( 'jquery' ), NULL, true );
        wp_register_script ( 'sclapi_tiny_mce_popup_script', site_url().'/wp-includes/js/tinymce/tiny_mce_popup.js' );
        wp_register_script ( 'sclapi_generatorform_script', plugins_url('/widget/generatorform.js', __FILE__), array( 'jquery', 'sclapi_tiny_mce_popup_script' ) );

        wp_register_style ( 'sclapi_options_style', plugins_url('/options/options.css', __FILE__) );
        wp_register_style ( 'sclapi_generatorform_style', plugins_url('/widget/generatorform.css', __FILE__) );
	}
    public static function sclapi_get_action ( $atts ) {
        $command = $atts[ Sclapi_Parameters::COMMAND ];
        switch ( $command ) {
            case Sclapi_Commands::GET_HTML_RANGE:
                $response = self::sclapi_get_HTML_range( $atts );
                break;
            case Sclapi_Commands::GET_IMAGE:
                $response = self::sclapi_get_image( $atts );
                break;
            case Sclapi_Commands::GET_IMAGE_BYTES:
                $response = self::sclapi_get_image_bytes( $atts );
                break;
            default:
                $response = 'Method error!';
                break;
        };
        return $response;
    }
    public static function sclapi_upload_file( $file ) {
        return Spreadsheet_Request::sclapi_upload_file( $file );
    }
    public static function sclapi_download_file( $file_name ) {
        $params = array( Sclapi_Parameters::FILE_NAME => $file_name );
        $download_response = Spreadsheet_Request::sclapi_download_file( $params );
        return $download_response;
    }
    public static function sclapi_delete_file( $file_name ) {
        $params = array( Sclapi_Parameters::FILE_NAME => $file_name );
        return Spreadsheet_Request::sclapi_delete_file( $params );
    }
    public static function sclapi_rename_file( $file_name, $new_file_name ) {
        $params = array( Sclapi_Parameters::FILE_NAME => $file_name,
        Sclapi_Parameters::NEW_FILE_NAME => $new_file_name, );
        return Spreadsheet_Request::sclapi_rename_file( $params );
    }

    static function sclapi_get_HTML_range( $atts ) {
        $params = self::sclapi_extract_get_HTML_range_params( $atts );
        $output = Spreadsheet_Request::sclapi_get_HTML( $params );
        if ( $output[ Sclapi_Plugin_Const::RESPONSE_STATUS ] != 200 ) {
            return "Error";
        } else
            return self::sclapi_fix_HTML_style( $output[ Sclapi_Plugin_Const::RESPONSE_DATA ] );
    }
    static function sclapi_fix_HTML_style( $HTML_code ) {
        $style = "<style>
        .initial-style table {
            border: initial;
        }
        .initial-style table td {
            border: initial;
            padding: initial;
        }
        </style>
        <div class=\"";
        return $style."initial-style \"".">".$HTML_code."</div>";
    }
    static function sclapi_extract_get_HTML_range_params( $atts ) {
        $params = shortcode_atts(array(
            Sclapi_Parameters::FILE_NAME                =>'',
            Sclapi_Parameters::SHEET_INDEX              =>NULL,
            Sclapi_Parameters::RANGE                   =>'',
            Sclapi_Parameters::SHEET_NAME               =>'',
            Sclapi_Parameters::START_ROW_INDEX           =>NULL,
            Sclapi_Parameters::START_COLUMN_INDEX        =>NULL,
            Sclapi_Parameters::END_ROW_INDEX             =>NULL,
            Sclapi_Parameters::END_COLUMN_INDEX          =>NULL,
            Sclapi_Parameters::EXPORT_DRAWING_OBJECTS    =>'true',
            Sclapi_Parameters::EXPORT_GRID_LINES    =>'false',
            Sclapi_Parameters::WPP => 'true',
            ), $atts);
        return $params;
    }

    static function sclapi_get_image( $atts ) {
        $style = self::sclapi_get_image_style( $atts );
        $imgBytes = self::sclapi_get_image_bytes( $atts );
        return "<img ".$style." src='data:image/jpeg;base64,".$imgBytes."' />";
    }
    static function sclapi_get_image_bytes( $atts ) {
        $params = self::sclapi_extract_get_image_parameters( $atts );
        $output = Spreadsheet_Request::sclapi_get_pictures( $params );
        if ( $output[ Sclapi_Plugin_Const::RESPONSE_STATUS ] != 200 ) {
            return "Error";
        } else{
            $imgJSON = $output[ Sclapi_Plugin_Const::RESPONSE_DATA ];
            $response = json_decode( $imgJSON, true );
            return $response[0]['PictureBytes'];
        }
    }
    static function sclapi_get_image_style( $atts ) {
        $style = '';
        if ( in_array(Sclapi_Parameters::WIDTH, $atts) && $atts[Sclapi_Parameters::WIDTH]<>'' )
            $style = $style.Sclapi_Parameters::WIDTH.":".$atts[ Sclapi_Parameters::WIDTH ].";";
        if ( in_array(Sclapi_Parameters::HEIGHT, $atts) &&  $atts[ Sclapi_Parameters::HEIGHT ]<>'' )
            $style = $style." ".Sclapi_Parameters::HEIGHT.":".$atts[ Sclapi_Parameters::HEIGHT ]."\"";
        if ( $style<>'' )
            return "style=\"".$style;
        return '';
    }
    static function sclapi_extract_get_image_parameters( $atts ) {
        $params = shortcode_atts( array(
            Sclapi_Parameters::FILE_NAME                =>'',
            Sclapi_Parameters::SCALE                   =>0.1,
            Sclapi_Parameters::SHEET_INDEX              =>NULL,
            Sclapi_Parameters::SHEET_NAME               =>'',
            Sclapi_Parameters::RANGE                   =>'',
            Sclapi_Parameters::START_ROW_INDEX           =>NULL,
            Sclapi_Parameters::START_COLUMN_INDEX        =>NULL,
            Sclapi_Parameters::END_ROW_INDEX             =>NULL,
            Sclapi_Parameters::END_COLUMN_INDEX          =>NULL,
            Sclapi_Parameters::OBJECT_INDEX             =>NULL,
            Sclapi_Parameters::HEIGHT                   =>NULL,
            Sclapi_Parameters::PICTURE_TYPE             =>Sclapi_Picture_Type::PICTURE,
            Sclapi_Parameters::WPP                      =>'true',
            ), $atts );
        return $params;
    }
    public static function sclapi_get_files_list( $size ) {
        $output = Spreadsheet_Request::sclapi_get_files_list();
        $response = json_decode( $output[ Sclapi_Plugin_Const::RESPONSE_DATA ], true );
        $result = '<select class="filename" name="filename" size="'.$size.'" ';
        $base_connected = $output[ Sclapi_Plugin_Const::RESPONSE_STATUS ] == 200;
        if ( ! $base_connected ) {
            $result = $result.'disabled="disabled"';
        }
        $result = $result.'>';
        if ( $base_connected ) {
            $counter = 0;
            foreach( $response as $current ) {
                if ( $counter == 0 && $size == 1 ) {
                    $result = $result.'<option value="'.$current['Name'].'">';
                }
                else {
                    $result = $result.'<option>';
                }
                $result = $result.$current['Name'].'</option>';
                $counter = $counter + 1;
            }
        } else {
            $result = $result.'<option>Sorry, there is a database connection problem.</option><option>Please try again shortly.</option>';
        }
        $result = $result.'</select>';
        return $result;
    }
}
?>
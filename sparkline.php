<?php
/*
 * Title:      Sparkline
 * Class:      phpSparklines
 * URL:        http://github.com/jamiebicknell/Sparkline
 * Author:     Jamie Bicknell
 * Twitter:    @jamiebicknell
 * Forked by:  Robert Mackie - robmackie.co.uk
*/
class phpSparklines {

    /**
     * Size of the image Canvas.
     * Default - 80 by 20 pixels.
     *
     * @var string
     */
    private $imageSize = '80x20';

    /**
     * Background color of Sparkline image.
     * Hexadecimal string without
     * @var string
     */
    private $backgroundColor = 'ffffff';

    /**
     * Line color in Hexadecimal format.
     * @var string
     */
    private $lineColor = '1388db';

    /**
     * Negative line color for falling values
     * Hexadecimal string
     * @var string
     */
    private $negativeLineColor = '570000';

    /**
     * The colour of the filled area between the image base & line.
     * Hexadecimal string
     * @var string
     */
    private $fillColor = 'e6f2fa';

    /**
     * Imploded comma delimited Sparkline plot points.
     * E.g. 2,60,30,10,30
     * @var array
     */
    private $data = array( 1, 40, 30 );

    /**
     * @var string
     */
    private $hash;

    /**
     * @var string
    */
    private $fileType = "image/png"; 

    /**
     * @var string
    **/
    private $fileExtension = ".png";	

    /*
     */
    private $sparkline;

    /**
     *	GD Image Resource 
     */
    private $image;

    /**
     * Check class dependencies. 
     */
   public function __construct( $options = array() ) {
        if ( !extension_loaded( 'gd' ))
            die('GD extension is not installed, please check your PHP Configuration.');
            
        $this->setOptions();
    }

    /**
     * Sets Options from Query String or given array.
     * @return void
     */
    public function setOptions( $userOptions = array() ){

        //Set Size.
        if( isset( $_GET['size'] ) && str_replace( 'x', '', $_GET['size'] ) != '' )
            $this->imageSize = $_GET['size'];
        elseif( isset( $userOptions["size"] ) && strlen( $userOptions["size"] ) > 0 )
            $this->imageSize = $userOptions["size"];

        //Set Background
        if( isset( $_GET['back'] ) &&  $this->isHex( $_GET['back'] ))
            $this->backgroundColor = $_GET['back'];
        elseif ( isset( $userOptions["back"] ) && strlen( $userOptions["back"] ) > 0 && $this->isHex( $userOptions["back"] ))
            $this->backgroundColor = $userOptions["back"];

        //Set Line Color.
        if( isset( $_GET['line'] ) &&  $this->isHex( $_GET['line'] ))
            $this->lineColor = $_GET['line'];
        elseif ( isset( $userOptions["line"] ) && strlen( $userOptions["line"] ) > 0 && $this->isHex( $userOptions["line"] ))
            $this->lineColor = $userOptions["line"];

        //Set Negative
        if( isset( $_GET['negative'] ) && $this->isHex( $_GET['negative'] ))
            $this->negativeLineColor = $_GET['negative'];
        elseif( isset( $userOptions["negative"] ) && strlen( $userOptions["negative"] ) > 0 && $this->isHex( $userOptions["negative"] ))

        //Set Fill Color
        if( isset( $_GET['fill'] ) && $this->isHex( $_GET['fill'] ))
            $this->negativeLineColor = $_GET['fill'];
        elseif( isset( $userOptions["fill"] ) && strlen( $userOptions["fill"] ) > 0 && $this->isHex( $userOptions["fill"] ))
            $this->fillColor = $userOptions["fill"];

        //Set Plot Data.
        if ( isset( $_GET["data"] ) && strlen( $_GET["data"] ) > 0 )
            $this->data = explode( ',', $_GET["data"] );
        elseif ( isset( $userOptions["data"] ) && is_string( $userOptions["data"] ) && strlen( $userOptions["data"] ) > 0 )
            $this->data = explode( ',', $userOptions["data"] );
        elseif ( isset( $userOptions["data"] ) && is_array( $userOptions["data"] ))
            $this->data = $userOptions["data"];
    }
    
    /**
    * Sets the extension of the image to be printed.
    * @return self
    **/
    public function setExtension( $fileType = '.png' ) {
        $file_extension = strtolower(substr( strrchr( $fileType, "." ), 1 ));
            switch( $file_extension ) {
            case "gif": 
                $this->fileType = "image/gif"; 
                $this->fileExtension = ".gif";
            break;
            case "png": 
                $this->fileType = "image/png"; 
                $this->fileExtension = ".png";	
            break;
            case "jpeg":
            case "jpg": 
                $this->fileType = "image/jpeg"; 
                $this->fileExtension = ".jpg";	
            break;
        }

        return $this;
    }
    
    /**
     * If Hash will match a subsequent call to the script, re-use cached.
     * @return bool
     */
    private function isCached() {

        $salt = 'v1.5.0';
        $this->hash = md5( $salt . $_SERVER['QUERY_STRING'] );

        if (isset($_SERVER['HTTP_IF_NONE_MATCH'])) {
            if ( $_SERVER['HTTP_IF_NONE_MATCH'] == $this->hash ) {
                header( $_SERVER['SERVER_PROTOCOL'] . ' 304 Not Modified' );
                die();
            }
        }

        return false;
    }

    /**
     * Converts a Hexadecimal value to an RGB Format in an array format.
     * @param $hexString
     * @return array
     */
    private function hexToRgb( $hexString ){
        $hex = ltrim(strtolower( $hexString ), '#');
        $hex = isset( $hex[3] ) ? $hex : $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        $dec = hexdec( $hex );
        return array(0xFF & ($dec >> 0x10), 0xFF & ($dec >> 0x8), 0xFF & $dec);
    }

    /**
     * Tests if given string is in Hexadecimal format.
     * @param $string
     * @return int
     */
    function isHex( $string ){
        return preg_match('/^#?+[0-9a-f]{3}(?:[0-9a-f]{3})?$/i', $string);
    }

    /**
     * Generates
     * @return void
     */
    public function generate(){

        if ( !$this->isCached() ) { 

            list($w, $h) = explode( 'x', $this->imageSize );
            $w = floor( max( 50, min( 800, $w )));
            $h = !strstr( $this->imageSize, 'x') ? $w : floor( max( 20, min( 800, $h )));
            $t = 1.75;
            $s = 4;

            $w *= $s;
            $h *= $s;
            $t *= $s;

            $data = ( count( $this->data ) < 2 ) ? array_fill( 0, 2, $this->data[0] ) : $this->data;
            $count = count( $data );
            $step = $w / ( $count - 1 );
            $max = max( $data );

            $this->sparkline = imagecreatetruecolor($w, $h);
            list($r, $g, $b) = $this->hexToRgb($this->backgroundColor);

            $bg = imagecolorallocate($this->sparkline, $r, $g, $b);
            list($r, $g, $b) = $this->hexToRgb($this->lineColor);

            $fg = imagecolorallocate($this->sparkline, $r, $g, $b);
            list($r, $g, $b) = $this->hexToRgb($this->negativeLineColor);

            $ng = imagecolorallocate($this->sparkline, $r, $g, $b);
            list($r, $g, $b) = $this->hexToRgb($this->fillColor);

            $lg = imagecolorallocate($this->sparkline, $r, $g, $b);
            imagefill($this->sparkline, 0, 0, $bg);
            imagesetthickness($this->sparkline, $t);

            foreach ($data as $k => $v) {
                $v = $v > 0 ? round($v / $max * $h) : 0;
                $data[$k] = max($s, min($v, $h - $s));
            }

            $x1 = 0;
            $y1 = $h - $data[0];
            $line = array();
            $poly = array(0, $h + 50, $x1, $y1);
            for ($i = 1; $i < $count; $i++) {
                $x2 = $x1 + $step;
                $y2 = $h - $data[$i];
                array_push($line, array($x1, $y1, $x2, $y2));
                array_push($poly, $x2, $y2);
                $x1 = $x2;
                $y1 = $y2;
            }

            array_push($poly, $x2, $h + 50);

            imagefilledpolygon( $this->sparkline, $poly, $count + 2, $lg );

            foreach ($line as $k => $v) {
                list($x1, $y1, $x2, $y2) = $v;
                imageline($this->sparkline, $x1, $y1, $x2, $y2, ( $y1 < $y2 ? $ng : $fg ));
            }

            $this->image = imagecreatetruecolor( $w / $s, $h / $s );
            imagecopyresampled( $this->image, $this->sparkline, 0, 0, 0, 0, $w / $s, $h / $s, $w, $h);
            imagedestroy( $this->sparkline );
    
        }

        return $this;
    }

    /**
     *
     */
    public function render() {
        header('Content-Type: '. $this->fileType );
        header('Content-Disposition: inline; filename="sparkline_' . time() . substr(microtime(), 2, 3) . $this->fileExtension .'"');
        header('ETag: '. $this->hash );
        header('Accept-Ranges: none');
        header('Cache-Control: max-age=604800, must-revalidate' );
        header('Expires: ' . gmdate('D, d M Y H:i:s T', strtotime('+7 days')));

        switch( $this->fileType ) {       
            case "image/png":
                imagepng( $this->image );
            break;
            case "image/jpeg":
            case "image/jpg":
                imagejpeg( $this->image );
            break;
            case "image/gif":
                imagegif( $this->image );
            break;
        }
    
        imagedestroy( $this->image );
    }
    
    /**
     *  TODO: Save to tmp file instead of printing to browser.
    **/
    public function save() {}
}
			  
<?php

/**
* 
*/
class ChartGraphic
{

/**
 * El nombre del gráfico, que servirá para identificarlo en el Component y en el Helper
 *
 * @var string
 */
  public $name;
/**
 * El nombre del contenedor del DOM HTML
 *
 * @var string
 */
  public $container;
  
  private $_defaults = array(

	    'legend' => array(
	        'itemStyle' => array(
	          'fontSize' => '11px'
	        ),
	        'align' => 'right',
	        'layout' => 'vertical',
	        'borderWidth' => 0,
	        'itemWidth' => 110,
	        'x' => -10,
          'y' => 60,
	        'verticalAlign' => 'top',
	    ),
	    'credits' => array(
        'enabled' => false,
      ),
  );
  
/**
 * El conjunto de opciones del objeto JS
 *
 * @var array
 */
  public $options = array();
  
  public function __construct( $name, $container = false, $options = array())
  {
    $this->name = $name;
    $this->container = $container;
    $this->options = array_merge( $this->_defaults, $options);
    $this->options ['yAxis']['min'] = 0;
    $this->options ['xAxis']['offset'] = 10;
  }
  
  public function getChartOptionsObject()
  {
    // return trim( json_encode( $this->options));
    return $this->jsonize( $this->options);
  }
  
  function jsonize( $foo)
  {
    $data = $this->jsonize_values( $foo);
    extract( $data);

    $json = json_encode( $foo);

    foreach( $value_arr as $value)
    {
      $json = str_replace( '"' . $value .'"', $value, $json);
    }


    return trim( $json);
  }

  function jsonize_values( $array)
  {
    $replace_keys = $value_arr = array();
    foreach( $array as $key => $value){
      // Look for values starting with 'function('
      if( !is_array( $value) && strpos($value, 'function(') !== false){
        // Store function string.
        $value_arr [] = $value;
        // Replace function string in $foo with a 'unique' special key.
        $value = '%' . $key . '%';
        // Later on, we'll look for the value, and replace it.
        $replace_keys [] = '"' . $value . '"';

      }
      elseif( is_array( $value))
      {
        $data = $this->jsonize_values( $value);
        $replace_keys = array_merge( $replace_keys, $data ['replace_keys']);
        $value_arr = array_merge( $value_arr, $data ['value_arr']);
      }
    }

    return compact( 'replace_keys', 'value_arr');
  }
  
  public function title( $title, $x = -20)
  {
    $this->options ['title']['text'] = $title;
    $this->options ['title']['x'] = $x;
  }
  
  public function categories( $axis, $categories)
  {
    $this->options [$axis .'Axis']['categories'] = $categories;
    $this->options [$axis .'Axis']['tickInterval'] = round( count( $categories) / 8);
  }
  
  public function axisTitle( $axis, $title)
  {
    $this->options [$axis .'Axis']['title']['text'] = $title;
  }
  
  public function tooltip( $value_suffix, $options = array())
  {
    $options ['valueSuffix'] = $value_suffix;
    $this->options ['tooltip'] = $options;
  }
  
  public function addSerie( $data, $ref = false, $only_data = false)
  {
    if( !isset( $data ['type']) || $data ['type'] != 'pie')
    {
      $data ['visible'] = array_sum( $data ['data']) > 0;
    }
    
    if( !$only_data)
    {
      $this->options ['series'][] = $data;
    }
    
    $this->options ['tables'][] = array(
        'name' => $data ['name'],
        'data' => $data ['data']
    );
  }

}

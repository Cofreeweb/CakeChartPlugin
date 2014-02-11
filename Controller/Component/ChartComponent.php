<?php
App::import( 'Lib', 'Chart.ChartGraphic');


class ChartComponent extends Component 
{
  public $components = array( 'Chart.Stats');
  
  public $charts = array();
  

  function initialize( Controller $controller, $settings = array()) 
  {
    $this->Controller = $controller;
    
    if (!isset( $this->Controller->helpers ['Chart.Chart'])) 
    {
      $this->Controller->helpers[] = 'Chart.Chart';
    }
  }


  function beforeRender( Controller $controller) 
  {
    Configure::write( 'Chart.charts', $this->charts);
  }
  
  public function create( $name, $container, $options = array())
  {
    $_options = array(
        'chart' => array(
  	      'type' => 'line',
  	      'marginRight' =>  130,
          'marginBottom' => 45,
          'zoomType' => 'x',
  	    ),
    );
    
    $options = Set::merge( $_options, $options);
    
    $options ['yAxis']['allowDecimals'] = false;
    
    $options ['title']['margin'] = 40;
    
    $chart = new ChartGraphic( $name, $container, $options);
        
    if( isset( $options ['categories']['x']))
    {
      $chart->categories( 'x', $options ['categories']['x']);
    }
    
    if( isset( $options ['axisTitle']['y']))
    {
      $chart->axisTitle( 'y', $options ['axisTitle']['y']);
    }
    
    

    $this->charts [$name] = $chart;
    return $chart;
  }
  
  
}
?>
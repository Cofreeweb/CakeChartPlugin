<?php

class ChartHelper extends AppHelper 
{
  public $helpers = array('Html', 'Session', 'Js', 'Stats');
  public $charts = null;
  public $chart_name = '';
  public $theme = '';

  public function __construct(View $View, $options = array()) 
  {
    parent::__construct($View, $options);
    $this->charts = $this->_getCharts();    
  }	
  
  public function afterRender( $viewFile) 
  {
    Configure::delete( 'Chart.charts');
  }
  
  public function beforeLayout( $viewFile) 
  {       
    parent::beforeLayout($viewFile);
    
    $js = array( '/chart/js/highcharts', '/chart/js/modules/exporting', '/chart/daterangepicker/daterangepicker.js', '/chart/moment.min.js/moment.min.js');        
    $this->Html->script( $js, false);
    $this->Html->css( '/chart/daterangepicker/daterangepicker-bs3', false, array(
        'inline' => false
    ));

    return true;
  }
  
  public function out( $data)
  {
    return implode( "\n", $data);
  }
  
  private function _getCharts() 
  {
    static $read = false;
    
    if ($read === true) 
    {
      return $this->charts;
    } 
    else
    {
      $this->charts = Configure::read( 'Chart.charts');
      $read = true;
      return $this->charts;
    }
  }
  
  private function _getTheme($name) 
  {
    if(isset($name) && (!empty($this->charts[$name]->chart->className)))
    {
      return $this->charts[$name]->chart->className;
    } 
    else 
    {
      return null;
    }
  }
  
/**
 * Retorna una tabla de los datos que han sido añadidos a un gráfico
 *
 * @param string $name 
 * @param string $namedata 
 * @param array $header Array con los textos de cabecera
 * @param integer $options ['max_rows'] El número máximo de filas a devolver
 * @return HTML
 */
  public function table( $name, $options = array())
  {
    $_options = array(
        'max_rows' => 10
    );
    
    $options = array_merge( $_options, $options);
    
    if( !isset($this->charts [$name])) 
    {
      return;
    }
    
    $data = $this->charts [$name]->options ['tables'];
       
    $out = $tr = $tbody = array();
    $odd = false;
    $i = 1;
    
    $tr = array( '<th></th>');
    
    foreach( $data as $values)
    {
      $tr [] = '<th>'. $values ['name'] .'</th>';
    }
    
    $out [] = $this->Html->tag( 'thead', $this->Html->tag( 'tr', $this->out( $tr)));
    $tr = array();
    
    $total = count( $data [0]['data']);
    
    for( $i= 0; $i < $total ; $i++) 
    { 
      $tr [] = '<td>'. $this->charts [$name]->options ['categories']['x'][$i] .'</td>';
      
      foreach( $data as $values)
      {
        $value = isset( $values ['data'][$i]) ? $values ['data'][$i] : '0';
        $tr [] = '<td>'. $value .'</td>';
      }
      
      $tbody [] = $this->Html->tag( 'tr', $this->out( $tr), array(
          'class' => $odd ? 'odd' : false
      ));
      $tr = array();
      $odd = !$odd;
    }
    
    
    foreach( $data as $values)
    {
      $tr [] = '<td>'. $values ['name'] .'</td>';
    }
    
    
    $out [] = $this->Html->tag( 'tbody', $this->out( $tbody));

    return $this->Html->tag( 'table', $this->out( $out), array(
        'class' => 'table'
    ));
  }
  
  public function tableHeaderLabel( $label)
  {
    $out = array();
    
    if( isset( $this->request->query ['compare']))
    {
      $date_start_1 = date( 'd-m-Y', strtotime( $this->Stats->getDateStart( 1)));
      $date_end_1 = date( 'd-m-Y', strtotime( $this->Stats->getDateEnd( 1)));
      $date_start_2 = date( 'd-m-Y', strtotime( $this->Stats->getDateStart( 2)));
      $date_end_2 = date( 'd-m-Y', strtotime( $this->Stats->getDateEnd( 2)));
      
      $out [] = $this->Html->tag( 'th', __( "Período 1"), array(
          'title' => __( "%s del %s al %s", array(
              $label,
              $date_start_1,
              $date_end_1
          )),
          'class' => 'poshytip'
      ));
      $out [] = $this->Html->tag( 'th', __( "Período 2"), array(
          'title' => __( "%s del %s al %s", array(
              $label,
              $date_start_2,
              $date_end_2
          )),
          'class' => 'poshytip'
      ));
    }
    else
    {
      $out [] = $this->Html->tag( 'th', $label);
    }
    
    return $this->out( $out);
  }
  
/**
 * Verifica si un gráfico tiene los datos en blanco
 *
 * @param object $chart 
 * @return boolean
 * @since Shokesu 0.2
 */
  public function isEmpty( $chart)
  {
    $empty = true;
    $series = $chart->options ['series'];
    
    if( empty( $series))
    {
      return true;
    }
    
    foreach( $series as $serie)
    {
      foreach( $serie ['data'] as $data)
      {
        if( is_array( $data))
        {
          if( $data [1] > 0)
          {
            return false;
          }
        }
        else
        {
          if( $data > 0)
          {
            return false;
          }
        }
      }
      
    }
    
    return true;
  }
  
  public function isChartEmpty( $chart)
  {
    $sum = 0;
    
    if( !isset( $chart->options ['series']))
    {
      return true;
    }
    
    if( isset( $chart->options ['type']) && $chart->options ['type'] == 'pie')
    {
      return false;
    }
    
    foreach( $chart->options ['series'] as $serie)
    {
      $sum += array_sum( $serie ['data']);
    }
    
    return $sum == 0;
  }
  
  public function render( $name) 
  {
    if( !isset($this->charts [$name])) 
    {
      // trigger_error(sprintf(__('Chart: "%s" could not be found. Ensure that Chart Name is the same string that is passed to $this->HighCharts->render() in your view.', true), $name), E_USER_ERROR);
      return;
    }
    
    if( $this->isChartEmpty( $this->charts [$name]))
    {
      return null;
    }
    
    $_jsonOptions = $this->charts [$name]->getChartOptionsObject();
    $chartJS = <<<EOF
$(function(){
  var j = $_jsonOptions;
$("{$this->charts[$name]->container}").highcharts( j)
})
EOF;

    $out = trim($chartJS);
    $this->Js->buffer( $out);
  }
  
  
  public function json( $name)
  {
    if( !isset($this->charts [$name])) 
    {
      // trigger_error(sprintf(__('Chart: "%s" could not be found. Ensure that Chart Name is the same string that is passed to $this->HighCharts->render() in your view.', true), $name), E_USER_ERROR);
      return;
    }
    
    if( $this->isChartEmpty( $this->charts [$name]))
    {
      return null;
    }
    
    $_jsonOptions = $this->charts [$name]->getChartOptionsObject();
    return $_jsonOptions;
  }
  
	
}